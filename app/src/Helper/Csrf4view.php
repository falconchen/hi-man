<?php

namespace App\Helper;
use Slim\Http\Request;
use Slim\Http\Response;



class Csrf4view
{
    private $container;

    function __construct($app) {

        $this->container = $app->getContainer();
    }

    public function __invoke(Request $request, Response $response, $next){

        $nameKey = $this->container->csrf->getTokenNameKey();
        $valueKey = $this->container->csrf->getTokenValueKey();

        $name = $this->container->csrf->getTokenName();
        $value = $this->container->csrf->getTokenValue();

        // Render HTML form which POSTs to /bar with two hidden input fields for the
        // name and value:
        $output  = '<input type="hidden" name="'.$nameKey .'" value="'.$name .'">';
        $output .= '<input type="hidden" name="'.$valueKey.'" value="'.$value.'">';

        // Append The CSRF Gards To The View
        $this->container->view->addAttribute('csrf_gards', $output );

        // Pass the Request to the next one
        $response = $next($request, $response);
        return $response;
    }

}