<?php
namespace App\Action;

use App\Model\Group;
use App\Model\User;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
//use \Slim\Http\Response as Responses;
use App\Helper\Acl;
use App\Helper\Session;
use App\Helper\Menu;
/**
* 
*/
class Admin extends \App\Helper\LoggedAction
{


    public function index(Request $request, Response $response, $args)
	{
        //var_dump($this->menu->getUserItems());
        $path = $request->getUri()->getPath();
       // var_dump($path);exit;
		$this->view->render($response, 'admin/index.twig',['menu'=>$this->menu]);
	}


	public function users(Request $request, Response $response, $args)
	{
        $this->view->render($response, 'admin/user.twig',['menu'=>$this->menu]);
	}

	public function userEdit(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'form.twig');
	}

	public function userDelete(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'admin.twig');
	}

	public function groups(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'group.twig');
	}

	public function groupsEdit(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'admin.twig');
	}
	public function groupsDelete(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'admin.twig');
	}

	public function permissions(Request $request, Response $response, $args)
	{
		
		//Acl::isAllow('permission','index');
		$resource 	= Acl::getResource();
		$user 		= Acl::getUser();
		$this->view->render($response, 'admin.twig',['resource' => $resource , 'user' => $user ]);
    }

	public function permissionsEdit(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'admin.twig');
	}

	public function permissionsDelete(Request $request, Response $response, $args)
	{
		$this->view->render($response, 'admin.twig');
	}
}