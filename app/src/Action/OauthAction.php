<?php

namespace App\Action;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use App\Model\UserMeta;

use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Exception;

use Slim\Http\Response;
use Slim\Http\Request;
use RuntimeException;

final class OauthAction extends \App\Helper\BaseAction
{


    private function getProvider($platform)
    {

        $config = $this->settings['oauth']['github'];
        $config['redirectUri'] = $config['redirectUri'] ?? hiGetSettings('app')['url'] . $this->router->pathFor('oauth.redirect', ['platform' => $platform]);
        return new \League\OAuth2\Client\Provider\Github($config);
    }
    public function index(Request $request, Response $response, $args)
    {
        
        $platform = $args['platform'];
        $provider = $this->getProvider($platform);


        $options = [
            'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
            'scope' => ['read:user', 'user:email', 'user:follow'] // array or string
        ];

        $authUrl = $provider->getAuthorizationUrl($options);
        $this->session->set('oauth2state', $provider->getState());
        return $response->withRedirect($authUrl);
    }

    public function redirect(Request $request, Response $response, $args)
    {

        // $metaKey = 'platform-github-id';
        // $metaValue = '123456'      ;


        // $userPlatformIdMeta = UserMeta::where(['meta_key'=>$metaKey,'meta_value'=>$metaValue])->first();
        //         var_dump($userPlatformIdMeta);exit;

        // if( !$$userPlatformIdMeta ){
        //     //新建用户
        //     echo "create user";
        // }

        $platform = $args['platform'];
        $provider = $this->getProvider($platform);
        $code = $request->getParam('code');
        $state = $request->getParam('state');
        $sessonState = $this->session->get('oauth2state');

        if (is_null($code)) {
            return $response->withRedirect($this->router->pathFor('oauth', ['platform' => $platform]));
        } elseif (is_null($state) || ($state !== $sessonState)) {

            $this->session->delete('oauth2state');
            exit('Invalid state');
        } else {




            // Optional: Now you have a token you can look up a users profile data
            try {
                // Try to get an access token (using the authorization code grant)
                $token = $provider->getAccessToken('authorization_code', [
                    'code' => $code
                ]);

                // We got an access token, let's now get the user's details
                $owner = $provider->getResourceOwner($token);

                // Use these details to create a new profile
                //printf('Hello %s! Id:%s,Email:%s', $owner->getNickname(),$owner->getId(),$owner->getEmail());
                $metaKeyPlatformId = 'platform-github-id';
                $metaValuePlatformId = $owner->getId();

                $userPlatformIdMeta = UserMeta::where(['meta_key' => $metaKeyPlatformId, 'meta_value' => $metaValuePlatformId])->first();


                if (is_null($userPlatformIdMeta)) {

                    $email = $owner->getEmail();

                    //是否已存在相同的email注册用户，视为同一用户，绑定
                    $user = User::where('email', $email)->first();

                    if (is_null($user)) { //新建用户
                        $passwordRaw = hi_random();
                        $username = $owner->getNickname() .'-'.$platform;

                        $user = new User();
                        $user->email = $email;
                        $user->username = $username;
                        $user->password = $this->hash->password($passwordRaw);
                        $user->group_id = 3;
                        $user->status =  1;
                        $user->active_code = uniqid();
                        $user->save();
                    }

                    $userPlatformIdMeta = new UserMeta(
                        ['user_id' => $user->id, 'meta_key' => $metaKeyPlatformId, 'meta_value' => $metaValuePlatformId]
                    );
                    $userPlatformIdMeta->save();
                } else {
                    $user = User::where('id', $userPlatformIdMeta->user_id)->first();
                }

                //更新access token
                $metaKeyPlatformIdAccessToken = 'platform-github-access-token';
                $metaValuePlatformAccessToken = maybe_serialize($token);

                $userTokenMeta = UserMeta::firstOrNew(
                    [
                        'user_id' => $user->id,
                        'meta_key' => $metaKeyPlatformIdAccessToken,
                    ]
                );
                $userTokenMeta->meta_value = $metaValuePlatformAccessToken;
                $userTokenMeta->save();

                //写入session
                $this->session->set($this->auth['session'], $user->id);
                $this->session->set($this->auth['session'], $user->id);
                $this->session->set($this->auth['group'], $user->group_id);
                $this->session->set('user', $user);

                //$fromPage = $this->route->pathFor('homepage');

                return $this->view->render($response, '_static/close-frame.twig',['title'=>'登录成功']);
                //return $response->withRedirect($fromPage);

            } catch (Exception $e) {

                // Failed to get user details

                echo $e->getMessage();
                exit('Failed');
            }

            // Use this to interact with an API on the users behalf
            //echo $token->getToken();
        }
    }
}
