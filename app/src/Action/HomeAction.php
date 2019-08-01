<?php
namespace App\Action;

use App\Helper\Hash;
use App\Model\Group;
use App\Model\User;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class HomeAction extends \App\Helper\BaseAction
{

    public function dispatch(Request $request, Response $response, $args)
    {
        $this->logger->info("Home page action dispatched");
        $this->view->render($response, 'home.twig', [
            'user' => User::all(),
        ]);
        return $response;
    }

    public function dashboard(Request $request, Response $response, $args)
    {
        $pannel = $this->getPannelMessage();

        return $this->view->render($response, 'dashboard.twig', ['pannel' => $pannel]);
    }

    public function logout(Request $request, Response $response, $args)
    {
        $session = new \App\Helper\Session;
        $session::destroy();
        return $response->withRedirect($this->route->pathFor('login'));
    }

    public function login(Request $request, Response $response, $args)
    {
        
        $this->view->render($response, 'login.twig');
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
            $flash = 'CSRF faiure';
            $this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);
        } else {
            if ($v->passes()) {
                $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();
                if ($user && $this->hash->passwordCheck($password, $user->password)) {
                    $this->session->set($this->auth['session'], $user->id);
                    $this->session->set($this->auth['group'], $user->group_id);
                    $this->addPannelMessage("Welcome back ,".$user->username,"success","Hi ");
                    return $response->withRedirect($this->router->pathFor('dashboard'));
                } else {
                    $flash = 'Sorry, you couldn\'t be logged in. Username/Email Or Password Wrong?';
                    $this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);
                }

            } else {
                $this->view->render($response, 'login.twig',
                    ['errors' => $v->errors(),
                        'request' => $request,
                    ]);
            }
        }

        return $response;
    }

    public function register(Request $request, Response $response, $args)
    {

        $this->view->render($response, 'register.twig');
        return $response;
    }

    public function registerPost(Request $request, Response $response, $args)
    {

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
            $user->group_id = $inactive_group->id;
            $user->status = 0;
            $user->active_code = uniqid();
            $user->save();

            $mailSubject = "Verify Your Email Address";
            $mailContent = sprintf("
                    <h1>Dear %s , Thanks for signing up for Hi !</h1>
                     We're happy you're here. Let's get your email address verified: <a href='%s'>Click to Verify Email</a> .",
                $user->username,
                $this->get('app')['url'] . $this->router->pathFor('verify.email', ['user' => $user->username, 'code' => $user->active_code])
            );
            $this->logger->debug($mailContent);

            $sendAddress = $user->email;
            $this->mailer->Subject = $mailSubject;
            $this->mailer->Body = $mailContent;
            $this->mailer->AddAddress($sendAddress);

            if (!$this->mailer->send()) {
                $this->logger->info("failed to send mail to " . $user->email);
            } else {
                $this->logger->info("send mail to " . $user->email);
                $this->flash->addMessage('success', $mailSubject);
                //$response = $response->withRedirect($this->router->pathFor('thanks'));
            }
            $flash = "You have been registered, check your email to verify!";
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
        if ($user->exists()) {
            if ($user->status == 0) {
                $user_group = Group::where('group_name', 'User')->first();
                $user->update(['status' => 1, 'group_id' => $user_group->id]);
                $this->logger->info('active user id:' . $user->id);
                //$this->flash->addMessage('suce', 'Verify email address successfully! go to login') ;
                return $response->withRedirect($this->route->pathFor('login'));
            } else {
                $flash = 'This email address was verified before';
            }

        }
        
        return $response;

    }

}
