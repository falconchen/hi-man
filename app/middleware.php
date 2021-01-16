<?php
use Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware;
use App\Middleware\ViewRouteNameMiddleware;

$app->add(new WhoopsMiddleware);

$app->add($app->getContainer()->get('csrf'));
$app->add(new App\Middleware\PaginationMiddleware);
$app->add(\adrianfalleiro\SlimCLIRunner::class); //Slim CLI Runner

$app->add(ViewRouteNameMiddleware::class);

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
				'/p/', '/verify/','/u/','/tweets',
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
