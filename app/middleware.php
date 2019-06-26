<?php
$app->add($app->getContainer()->get('csrf'));
//$app->add(new App\Helper\Csrf4view($app));
$app->add(function($request, $response, $next){
	switch ($request->getUri()->getPath()) {
		case '/':
			break;
		case '/login':
			//$response->write(' Please Insert Username and password ');

			break;
		case '/register':
			
			break;
		case '/logout':
			//$response->write(' logout ');
			break;
		case '/dashboard':
			if(! App\Helper\Acl::isLogged()){
		        return $response->withRedirect('login');
		    }
			break;
		default:
			if(! App\Helper\Acl::isLogged()){
		        return $response->withRedirect('login');
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
