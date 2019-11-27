<?php
$app->add($app->getContainer()->get('csrf'));


$app->add(function($request, $response, $next){
	$path = $request->getUri()->getPath();
	
	switch ($path) {
		case '/':
		case '/login':
		case '/register':
		case '/logout':
		case '/verify':
        case '/p/':
		break;

        // case '/oscer':
		// case '/dashboard':
		// case '/hi-admin/':
		// case '/post-admin/':			
			

		case '/hi-admin':

			if(! App\Helper\Acl::isLogged()){
		        return $response->withRedirect('/login');
			}

			$routes = App\Helper\Acl::getRoute($path);

			if($routes){

				if(! $routes->count() == 0){
					$acl = new App\Helper\Acl();
					if(! $acl->cekPermission($routes->page,$routes->action)){
						return $this->view->render($response, 'error.twig',['flash' => '[error] You dont have permission to access '.$path ] );
					} 
				}
			}		
			break;

		default :
			if(! App\Helper\Acl::isLogged()){					
				return $response->withRedirect('/login');
			}			
            break;
	}
	$response = $next($request, $response);
	return $response;
});
