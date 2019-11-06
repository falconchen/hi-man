<?php
namespace App\Action;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\User;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class OscerAction extends \App\Helper\BaseAction
{

    public function bindOscerPost(Request $request, Response $response, $args)
    {
        var_dump(__CLASS__ .':'. __FUNCTION__);exit;
        $identifier = Input::post('identifier');
        $password = Input::post('password');
        $v = new Validator(new User);
        $v->validate([
            'identifier' => [$identifier, 'required'],
            'password' => [$password, 'required'],
        ]);
        if ($request->getAttribute('csrf_status') === false) {
            $flash = array('CSRF faiure');
            $this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);
        } else {
            if ($v->passes()) {
                $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();
                if ($user && $this->hash->passwordCheck($password, $user->password)) {
                    $this->session->set($this->auth['session'], $user->id);
                    $this->session->set($this->auth['session'], $user->id);
                    $this->session->set($this->auth['group'], $user->group_id);
                    $this->session->set('user', $user);
                    //$this->addPannelMessage("Welcome back ,".$user->username,"success","Hi ");
                    
                    $this->flash->addMessage('info', "Welcome back ,".$user->username);
                    return $response->withRedirect($this->router->pathFor('dashboard'));
                } else {
                    $flash = ['Sorry, you couldn\'t be logged in. Username/Email Or Password Wrong?'];
                    
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

    
}
