<?php
/**
 * 给模板注入路由当前名称变量 r
 */
namespace App\Middleware;

use \Psr\Container\ContainerInterface;

class ViewRouteNameMiddleware
{
        /*
     * @var \Psr\Container\ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     * @param ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $next)
    {
        
        $container = $this->container;

        if($request->getAttribute('route')){            
            $routeName = $request->getAttribute('route')->getName();		
            $view = $container->get('view');		
            $view->getEnvironment()->addGlobal('r', $routeName);
        }
                
        return $next($request, $response);
    }
}
