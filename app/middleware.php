<?php
$app->add($app->getContainer()->get('csrf'));


$app->add(function($request, $response, $next){
	$path = $request->getUri()->getPath();
	switch ($path) {
		case '/':
		case '/login':
		case '/register':
		case '/logout':			
			break;
		case '/dashboard':
			if(! App\Helper\Acl::isLogged()){
		        return $response->withRedirect('/login');
		    }
			break;

		default:

			if(strpos($path,'/verify/') === 0){ //验证邮件时不需要ACL
			    break;
            }
			
			if(! App\Helper\Acl::isLogged()){
		        return $response->withRedirect('/login');
			}
			
			$routes = App\Helper\Acl::getRoute($request->getUri()->getPath());
			if($routes){
				if(! $routes->count() == 0){
					$acl = new App\Helper\Acl();
					if(! $acl->cekPermission($routes->page,$routes->action)){
						return $this->view->render($response, 'dashboard.twig',['flash' => 'You dont have permission to access '.$request->getUri()->getPath() ] );
					} 
				}
			}		
			break;
	}
	$response = $next($request, $response);
	return $response;
});
