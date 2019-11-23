<?php
namespace App\Action;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\User;
use App\Model\UserMeta;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helper\JsonRenderer;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client; // http://docs.guzzlephp.org/en/stable/index.html
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Config\Definition\Exception\Exception;

use Violin\Violin;

final class OscerAction extends \App\Helper\LoggedAction
{


    public function index(Request $request, Response $response, $args)
    {

        $userId = $this->userId;
        $data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $userId)->where('meta_key','osc_userinfo')->first();
        if( $oscer ){
            $data['oscer'] = unserialize($oscer->meta_value);
        }
        $this->view->render($response, 'oscer/oscer.twig',$data);


    }


    public function bindOscerPost(Request $request, Response $response, $args)
    {

        //JsonRenderer::render($response,200,['data'=>'abc']);

        $userMail = Input::post('userMail');
        $userPassword = Input::post('userPassword');


        if ($request->getAttribute('csrf_status') === false){
            return JsonRenderer::render($response,200,['success'=>false, 'msg'=>'csrf error','data'=>'']);
        }
        $v = new Validator(new User);
        $v->validate([
            'userMail' => [$userMail, 'required|emailOrTel'],
            'userPassword' => [$userPassword, 'required|min(40)'],
        ]);

        if (!$v->passes()) {
            return JsonRenderer::render($response,200,['success'=>false, 'msg'=>implode(' | ', $v->errors()->all() ),'data'=>$v->errors()]);
        }

        $loginUrl = 'https://www.oschina.net/action/user/hash_login?from=';
        //$args = $this->settings['guzzle'];
        $client = new Client($this->settings['guzzle']);


        try {
            $oscResponse = $client->request('POST', $loginUrl,[
                'form_params' => [
                    'email' => $userMail,
                    'pwd' => $userPassword,
                    'verifyCode'=>'',
                    'save_login'=>1,
                ]
            ]);
            $body = (string) $oscResponse->getBody();
            if($body == ''){ //登录成功返回空值

                //带cookie去获取osc用户名和头像
                $oscResponse = $client->request('GET', 'https://my.oschina.net/');
                $body = (string) $oscResponse->getBody();

                $dom = new \PHPHtmlParser\Dom;
                $dom->load($body,['whitespaceTextNode' => false]);
                $imgNode = $dom->find('.osc-avatar img');
                $homepageNode = $dom->find('a.avatar');
                $userIdNode = $dom->find('.current-user-avatar');
                $oscer = [];

                if( count($imgNode) && count($homepageNode) &&count($userIdNode))  {
                    $oscer['userName'] = $imgNode[0]->getAttribute('title');
                    $oscer['avatar'] = $imgNode[0]->getAttribute('src');
                    $oscer['userId'] = $userIdNode[0]->getAttribute('data-user-id');
                    $oscer['homepage'] = $homepageNode[0]->getAttribute('href');
                    $oscer['signature'] = '';
                    $signature_node = $dom->find('.user-signature');
                    if( count($signature_node) ){
                        $oscer['signature'] = $signature_node[0]->text;
                    }

                    //保存用户名密码
                    $userId = $this->userId;

                    $userMail = Input::post('userMail');
                    $userPassword = Input::post('userPassword');
                    $userMeta = new UserMeta();
                    $userMeta->user_id = $userId;
                    $userMeta->meta_key = 'osc_login';
                    $userMeta->meta_value = maybe_serialize(
                        ['userMail'=>$userMail,'userPassword'=>$userPassword]
                    );
                    $userMeta->save();


                    //获取cookie,保存到DB
                    $cookieJar = $client->getConfig('cookies');
                    //$cookieJar->toArray();
                    $userMeta = new UserMeta();
                    $userMeta->user_id = $userId;
                    $userMeta->meta_key = 'osc_cookie';
                    $userMeta->meta_value = maybe_serialize(
                        $cookieJar
                    );
                    $userMeta->save();

                    //保存osc用户信息
                    $userMeta = new UserMeta();
                    $userMeta->user_id = $userId;
                    $userMeta->meta_key = 'osc_userinfo';
                    $userMeta->meta_value = maybe_serialize(
                        $oscer
                    );
                    $userMeta->save();



                }else{
                    throw new \Exception('fail to get OSCer info');
                }


                return JsonRenderer::render($response,200,['success'=>true, 'msg'=>'已连接','data'=>$oscer]);



            }else{
                $this->logger->debug( $body ); // "{"msg":"登录失败，请确认是否输入正确的用户名和密码","failCount":1}"
            }

        } catch (ClientException $e) { //40x

            $this->logger->log( Psr7\str($e->getRequest()) );
            $this->logger->log(Psr7\str($e->getResponse()));

        } catch (Exception $e){ //others

        }
        return $response;
    }


    public function updateBinding(){

        $userId = $this->userId;
        $oscCookie = UserMeta::where('user_id', $userId)->where('meta_key','osc_cookie')->first();
        $cookieJar = unserialize($oscCookie->meta_value);

//        new \GuzzleHttp\Cookie\CookieJar;
//        $cookieJar->setCookie(new \GuzzleHttp\Cookie\SetCookie([
//            'Domain'  => '.oschina.net',
//            'Name'    => 'oscid',
//            'Value'   => 'failed',
//            'Discard' => true
//        ]));

        $args = $this->settings['guzzle'];
        $args['cookies'] = $cookieJar;

        $client = new Client($args);
        $oscResponse = $client->request('GET', 'https://my.oschina.net/');
        $body = (string) $oscResponse->getBody();
        var_dump($body);


    }


}
