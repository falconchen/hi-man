<?php
namespace App\Helper;
use \App\Model\User;
use \App\Model\UserPermission;
use \App\Model\Route;
use App\Helper\Url;
//use Illuminate\Database\Query\Builder as DB;
use Illuminate\Database\Capsule\Manager as DB;
/**
* 
*/
class Acl
{
	private $session;

	public function __construct()
	{
		$this->session = new \App\Helper\Session;
		
	}

	public function profile()
	{
		$user = User::find($this->session->get('user_id'));
		return $user;
	}
	
	public function isAllow($page,$action)
	{
		$user_perm = UserPermission::where('page',$page)->where('action',$action)->where('group_id',$this->session->get('group_id'))->get();
		if(empty($user_perm->toArray())){
			$this->session->set('flash','You dont have permission ');
			return Url::redirect($location='dashboard');
		}
		
	}

	public function cekPermission($page,$action)
	{
		
		$user_perm = UserPermission::where('page',$page)->where('action',$action)->where('group_id',$this->session->get('group_id'))->get();
		if(empty($user_perm->toArray())){
			return false;
		}
		return true;
		
	}

	public function getRoute($routes)
	{
		$route = preg_replace('#^/hi\-admin/#iUs', '', $routes);
		return Route::where('route',$route)->first();
	}
	
	public function isLogged()
	{				
		if(isset($_SESSION['user_id'])){
			
            return true;
        }
        return false;

	}

	public function getPermission($group_id)
	{
		$role = UserPermission::where('group_id',$group_id)->get();
		if($role->toArray())
		{

		}
	}

    /**
     *
     * @param $group_id
     */
	public static function getPermissionRoutes($group_id)
    {

        $result = DB::table('routes')
            ->join('users_permission',function($join){
                $join->on('routes.page','=','users_permission.page')
                ->on('routes.action','=','users_permission.action');
            }, null,null,'inner')
            ->where('users_permission.group_id','=',$group_id)
            ->get();

        return $result;
    }

	public function getResource()
	{
		return $privateResources = array(
	        'user' => array(
	            'index',
	            'search',
	            'edit',
	            'create',
	            'delete',
	            'changePassword'
	        ),
	        'group' => array(
	            'index',
	            'search',
	            'edit',
	            'create',
	            'delete'            
	        ),
	        'permission' => array(
	            'index',
	            'search',
	            'edit',
	            'create',
	            'delete'
	        )
	    );
	}

	public function getUser()
	{
		return User::all();
	}

}