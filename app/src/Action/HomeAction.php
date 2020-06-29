<?php

namespace App\Action;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;

use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response; // http://docs.guzzlephp.org/en/stable/index.html
use Psr\Http\Message\ServerRequestInterface as Request;
//use App\Helper\Paginator;
// use Illuminate\Pagination;

// use Illuminate\Pagination\Paginator;


//use Illuminate\Support\Facades\DB;

final class HomeAction extends \App\Helper\BaseAction
{

    public function dispatch(Request $request, Response $response, $args)
    {
        
        $data = array();

        $postsQuery = Post::where(['post_status' => 'publish', 'post_visibility' => 'public','post_type'=>'post']);

        if ($this->userId > 0) {
            //$postsQuery->orWhereRaw('post_author = ? and post_status <> ?', [$this->userId, 'trash']);
            $postsQuery->orWhereRaw('post_author = ? and post_status <> "trash" and post_type="post"', [$this->userId]);
            
        }
        $posts = $postsQuery->orderBy('post_date', 'DESC')->paginate(10);

        
        if ($posts->count() > 0) {
            foreach ($posts as &$post) {
                // $post->post_modified = $this->dateTolocal('Y-m-d H:i:s', $post->post_modified);
                $post->post_author_name = User::where('id', $post->post_author)->first()->username;
            }
        }
        //$posts->render();

        //$pager = $data->render(); //如果路径在二级目录下 分页访问的url 会指向根目录
        //exit;
        //$pager = $posts->render(); //如果路径在二级目录下 分页访问的url 会指向根目录
        //var_dump($list);
        //exit;

        $data['posts'] = $posts;

        //extract

        $this->view->render($response, 'home.twig', $data);
        return $response;
    }

    public function testing()
    {
        echo $this->getPostLink('hello',true);exit;
        //$this->get('eventManager')->emit('site.visit');
        
        // foreach (range(1, 10) as $i) {
        //     //$emitter->emit('hello', 'hello ' . $i);
        //     $this->get('eventManager')->emit('user.visit',$_SERVER['REMOTE_ADDR']);
        // }

        //var_dump(getOscPostId(28));
        //exit;
        // $this->logger->error('test error', ['error' => 'hello', 'detail' => 'my details']);
        // $this->logger->info("Home page action dispatched");
        //echo hi_generate_uuid4();exit;

        //echo date('Y-m-d H:i:s');
        // $this->view->render($response, 'home.twig', [
        //     'user' => User::all(),
        // ]);

        // $item = PostMeta::firstOrNew(['meta_key'=>'osc_sync_options','post_id'=>18]);
        // $item['meta_value'] = "test";
        // $item->save();

        //echo $this->utcTimestamp();
        // try{
        //     $this->testing();
        // }catch(Exception $e){
        //     $this->logger->debug($e->getMessage());
        // }finally{
        //     $this->logger->debug("hello finally",['aaa'=>'bbb']);
        // }

        //echo "hello world";exit;

        // echo 'start' . date('H:i:s');
        // $lock = $this->fileLock('Hello');
        // $lock->acquire();
        // echo 'acquire' .date('H:i:s');
        // sleep(18);
        // $lock->release();
        // echo 'release' . date('H:i:s');
        // exit;

        //throw new Exception("hello exception");
        echo "hello";
        //$this->logger->debug("hello world");
        //echo "asd"
        //$this->scNofify("hello 你是 认","world");
        //$this->logger->abc('aaa');
        echo $this->dateToLocal('Y-m-d H:i:s', '2019-11-30 15:39');
        
    }

    public function dashboard(Request $request, Response $response, $args)
    {
        $user_id = $this->session->get($this->auth['session']);
        $user = User::where('id', $user_id)->first();
        if ($user) {
            return $response->withRedirect($this->router->pathFor('post-admin'));
        }

        //return $this->view->render($response, 'admin/dashboard.twig', ['flash' => $this->flash->getMessage('flash'), 'user' => $user]);

        //绑定
        // curl 'https://www.oschina.net/action/user/hash_login?from=' -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:69.0) Gecko/20100101 Firefox/69.0' -H 'Accept: */*' -H 'Accept-Language: zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2' --compressed -H 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8' -H 'X-Requested-With: XMLHttpRequest' -H 'DNT: 1' -H 'Connection: keep-alive' -H 'Referer: https://www.oschina.net/home/login?goto_page=https%3A%2F%2Fmy.oschina.net%2FTimeCarving%3Ftab%3Dactivity%26scope%3Dreply' -H 'Cookie: aliyungf_tc=AQAAAGmbp28auwAAdm/teIpbLDCs/qE1; Hm_lvt_a411c4d1664dd70048ee98afe7b28f0b=1572770300; Hm_lpvt_a411c4d1664dd70048ee98afe7b28f0b=1572770408; _user_behavior_=2a96fe87-c201-4de1-85a5-572dd8885d55; _reg_key_=XX5mbtBtp3SK7pF1gKd7' -H 'Pragma: no-cache' -H 'Cache-Control: no-cache' --data 'email=13714681456&pwd=65a772ebd247c49e7f7b1dba2f202ed344b59973&verifyCode=&save_login=1'
    }

    public function logout(Request $request, Response $response, $args)
    {
        $session = new Session();
        $session::destroy();
        return $response->withRedirect($this->router->pathFor('login'));
    }

    public function login(Request $request, Response $response, $args)
    {
        $this->view->render($response, 'login.twig', ['flash' => $this->flash->getMessage('flash')]);
        return $response;
    }

    public function loginPost(Request $request, Response $response, $args)
    {
        $identifier = Input::post('identifier');
        $password = Input::post('password');
        $v = new Validator(new User);
        $v->validate([
            'identifier' => [$identifier, 'required'],
            'password' => [$password, 'required'],
        ]);
        if ($request->getAttribute('csrf_status') === false) {
            $flash = array('[error] CSRF faiure');
            $this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);
        } else {
            if ($v->passes()) {
                $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();
                if ($user && $this->hash->passwordCheck($password, $user->password) && $user->status > 0) {
                    $this->session->set($this->auth['session'], $user->id);
                    $this->session->set($this->auth['session'], $user->id);
                    $this->session->set($this->auth['group'], $user->group_id);
                    $this->session->set('user', $user);
                    //$this->addPannelMessage("Welcome back ,".$user->username,"success","Hi ");

                    $this->flash->addMessage('flash', "[success] Welcome back ," . $user->username);

                    if ($user->group_id <= 2) {
                        return $response->withRedirect($this->router->pathFor('admin')); //admin和mod转向管理页
                    } else {
                        return $response->withRedirect($this->router->pathFor('post-admin')); //普通用户转向
                    }
                } else {
                    $flash = ['[error] Sorry, you couldn\'t be logged in. <br/>Wrong Username/Email/Password Or account Inactive ? '];
                    $this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);
                }
            } else {
                $this->view->render(
                    $response,
                    'login.twig',
                    [
                        'errors' => $v->errors(),
                        'request' => $request,
                    ]
                );
            }
        }

        return $response;
    }

    public function register(Request $request, Response $response, $args)
    {

        $this->view->render($response, 'register.twig', ['flash' => $this->flash->getMessage('flash')]);
        return $response;
    }

    public function registerPost(Request $request, Response $response, $args)
    {

        if (isset($this->settings['email.verify']) && $this->settings['email.verify'] == true) {
            $needEmailVerify = true;
        } else {
            $needEmailVerify = false;
        }

        $email = Input::post('email');
        $username = Input::post('username');
        $password = Input::post('password');

        $passwordConfirm = Input::post('password_confirm');
        $v = new Validator(new User);
        $v->validate([
            'email' => [$email, 'required|email|uniqueEmail'],
            'username' => [$username, 'required|alnumDash|max(20)|uniqueUsername'],
            'password' => [$password, 'required|min(6)'],
            'password_confirm' => [$passwordConfirm, 'required|matches(password)'],
        ]);

        if ($v->passes()) {
            $inactive_group = Group::where('group_name', 'inactive')->first();

            $user = new User();
            $user->email = $email;
            $user->username = $username;
            $user->password = $this->hash->password($password);
            $user->group_id = $needEmailVerify ? $inactive_group->id : 3;
            $user->status = $needEmailVerify ? 0 : 1;
            $user->active_code = uniqid();
            $user->save();

            $mailSubject = "Verify Your Email Address";
            $mailContent = sprintf(
                "
                    <h1>Dear %s , Thanks for signing up Hi !</h1>
                     We're happy you're here. Let's get your email address verified: <a href='%s'>Click to Verify Email</a> .",
                $user->username,
                $this->get('app')['url'] . $this->router->pathFor('verify.email', ['user' => $user->username, 'code' => $user->active_code])
            );
            $this->logger->debug($mailContent);

            $sendAddress = $user->email;
            $this->mailer->Subject = $mailSubject;
            $this->mailer->Body = $mailContent;
            $this->mailer->AddAddress($sendAddress);

            if ($needEmailVerify) {
                if (!$this->mailer->send()) {
                    $this->logger->info("failed to send mail to " . $user->email);
                } else {
                    $this->logger->info("send mail to " . $user->email);
                    //$response = $response->withRedirect($this->router->pathFor('thanks'));
                }
                $flash = "[info] An Email has been sent to your mailbox, please check the email to verify!";
            } else {
                $flash = "[info] You can login now !";
            }
            $this->flash->addMessage('flash', $flash);
            return $response->withRedirect($this->router->pathFor('login'));
        } else {
            $flash = "registration failed.";
        }

        $this->view->render($response, 'register.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request->getParsedBody()]);

        return $response;
    }

    /**
     *  验证邮件
     */
    public function verifyEmail(Request $request, Response $response, $args)
    {
        $userName = $args['user'];
        $activeCode = $args['code'];

        $user = User::where('username', $userName)->where('active_code', $activeCode)->first();

        if (!is_null($user) && $user->exists()) {

            if ($user->status == 0) {
                $user_group = Group::where('group_name', 'User')->first();
                $user->update(['status' => 1, 'group_id' => $user_group->id]);
                $this->logger->info('active user id:' . $user->id);
                $this->flash->addMessage('flash', '[success] Verified email address successful,you can login now :)');
                return $response->withRedirect($this->router->pathFor('login'));
            } else {
                $flash = 'This email address was verified before';
                exit($flash);
            }
        } else {
            $this->logger->info(sprintf('invalid user verify with %1$s :%2$s', $userName, $activeCode));
            $this->flash->addMessage('flash', '[error] Bad Request. Try register again.');
            return $response->withRedirect($this->router->pathFor('register'));
        }

        return $response;
    }

    public function sendmail(Request $request, Response $response, $args)
    {
        $sendAddress = 'falcon_chen@qq.com';
        $this->mailer->Subject = "test send mail";
        $this->mailer->Body = 'text send mail content';
        $this->mailer->AddAddress($sendAddress);

        $this->mailer->SMTPDebug = 3;
        $t = $this;
        $this->mailer->Debugoutput = function ($e) use ($t) {
            $t->scNofify($e);
        };
        $result = $this->mailer->send();
        var_dump($result);
    }
}
