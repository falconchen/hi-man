<?php
namespace App\Action;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\User;
use App\Model\UserMeta;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Slim\Http\Response as Response;
use Slim\Http\Request as Request;
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

        return $response->withRedirect($this->router->pathFor('post-admin'));//普通用户转向

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

        


        // if ($request->getAttribute('csrf_status') === false){
        //     return JsonRenderer::render($response,200,['success'=>false, 'msg'=>'csrf error','data'=>'']);
        // }


        $userMail = Input::post('userMail');
        $userPassword = Input::post('userPassword');
        $v = new Validator(new User);
        $v->validate([
            'userMail' => [$userMail, 'required|emailOrTel'],
            'userPassword' => [$userPassword, 'required|min(40)'],
        ]);

        if (!$v->passes()) {
            return JsonRenderer::render($response,200,['success'=>false, 'msg'=>implode(' | ', $v->errors()->all() ),'data'=>$v->errors()]);
        }

        


        try {
            
            $loginUrl = 'https://www.oschina.net/action/user/hash_login?from=';
            //$args = $this->settings['guzzle'];
            $client = new Client($this->settings['guzzle']);
            $formParams = [
                'email' => $userMail,
                'pwd' => $userPassword,
                //'verifyCode'=>'',
                'save_login'=>1,
            ];
            $oscResponse = $client->request('POST', $loginUrl,[
                'form_params' => $formParams
            ]);
            $this->c->logger->debug('osc login form params', $formParams);
            $body = (string) $oscResponse->getBody();
            if($body == ''){ //登录成功返回空值

                //带cookie去获取osc用户名和头像
                $oscResponse = $client->request('GET', 'https://my.oschina.net/');
                $body = (string) $oscResponse->getBody();
                

                $dom = new \PHPHtmlParser\Dom;
                $dom->load($body,['whitespaceTextNode' => false]);
                $imgNode = $dom->find('.osc-avatar img');
                $homepageNode = $dom->find('.avatar-image__inner');
                $userIdNode = $dom->find('.current-user-avatar');
                $oscer = [];


                if( count($imgNode) && count($homepageNode) && count($userIdNode))  {
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
                    //$userMeta = new UserMeta();
                    $userMeta = UserMeta::firstOrNew(['user_id'=>$userId,'meta_key'=>'osc_login']);
                    //$userMeta->user_id = $userId;
                    //$userMeta->meta_key = 'osc_login';
                    

                    $userMeta->meta_value = maybe_serialize(
                        ['userMail'=>$userMail,'userPassword'=>$userPassword]
                    );
                    $userMeta->save();
                    


                    //获取cookie,保存到DB
                    $cookieJar = $client->getConfig('cookies');
                    //$cookieJar->toArray();
                    // $userMeta = new UserMeta();
                    // $userMeta->user_id = $userId;
                    // $userMeta->meta_key = 'osc_cookie';
                    $userMeta = UserMeta::firstOrNew(['user_id'=>$userId,'meta_key'=>'osc_cookie']);
                    $userMeta->meta_value = maybe_serialize(
                        $cookieJar
                    );
                    
                    $userMeta->save();

                    //保存osc用户信息
                    // $userMeta = new UserMeta();
                    // $userMeta->user_id = $userId;
                    // $userMeta->meta_key = 'osc_userinfo';
                    $userMeta = UserMeta::firstOrNew(['user_id'=>$userId , 'meta_key'=>'osc_userinfo']);
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
                //{"error":1,"msg":"验证码错误"}
                
                
                JsonRenderer::render($response,200,['success'=>false, 'msg'=>$body,'data'=>[]]);
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

    public function unbindOscerPost(Request $request, Response $response, $args)
    {

        if ($request->getAttribute('csrf_status') === false){
            return JsonRenderer::render($response,200,['success'=>false, 'msg'=>'csrf error','data'=>'']);
        }
        $userId = $this->userId;        
        $deletedRows = UserMeta::where('user_id', $userId)->delete();
        

        $redirectUrl = is_null(Input::post('currentPath')) ? 
                        $this->router->pathFor('post-admin') : 
                        Input::post('currentPath');
                                
        return $response->withRedirect($redirectUrl);
    }


}
