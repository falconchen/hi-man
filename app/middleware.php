<?php
use Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware;
use App\Middleware\ViewRouteNameMiddleware;
use App\Helper\JsonRenderer;

$app->add(new WhoopsMiddleware);

$app->add($app->getContainer()->get('csrf'));
$app->add(new App\Middleware\PaginationMiddleware);
$app->add(\adrianfalleiro\SlimCLIRunner::class); //Slim CLI Runner

$app->add(ViewRouteNameMiddleware::class);

//按jwt的scope字段确认访问的权限
// $app->add( function ($request,$response,$next) use ($app){ //很奇怪，这个要放JwtAuthentication前面
	
// 	if( $request->getAttribute("token") && $request->getAttribute('route')){      
		
// 		$routeName = $request->getAttribute('route')->getName();
// 		$token = $request->getAttribute("token")	;
// 		if(!in_array($routeName,$token['scopes'])){			
// 			return JsonRenderer::error($response, 401, $app->getContainer()->translator->trans('No Permission'));
// 		}
// 	}
// 	return $next($request, $response);
// }) ;


$app->add( $app->getContainer()->get("JwtAuthentication"));

$app->add(function ($request, $response, $next) {

	$path = $request->getUri()->getPath();
	
	switch ($path) {
		case '/':
		case '/login':
		case '/register':
		case '/logout':
		case '/p/':
		case '/search/':	
		case '/testing':
		case '/p/sync-osc':
		
			break;

			// case '/oscer':
			// case '/dashboard':
			// case '/hi-admin/':
			// case '/post-admin/':


		case '/hi-admin':

			if (!App\Helper\Acl::isLogged()) {
				return $response->withRedirect('/login');
			}

			$routes = App\Helper\Acl::getRoute($path);

			if ($routes) {

				if (!$routes->count() == 0) {
					$acl = new App\Helper\Acl();
					if (!$acl->cekPermission($routes->page, $routes->action)) {
						return $this->view->render($response, 'error.twig', ['flash' => '[error] You dont have permission to access ' . $path]);
					}
				}
			}
			break;

		default:
			$allow = false;
			$allowStartWith = [
				'/p/', '/verify/','/u/','/tweets','/c','/@',
				'/api/'
			];

			foreach ($allowStartWith as $word) {
				if (strpos($path, $word) === 0) {
					$allow = true;
					break;
				}
			}

			if ($allow) {
				break;
			} elseif (!App\Helper\Acl::isLogged()) {
				return $response->withRedirect('/login');
			}
	}
	$response = $next($request, $response);
	return $response;
});
