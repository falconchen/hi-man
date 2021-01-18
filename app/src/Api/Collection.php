<?php

namespace App\Api;

use App\Helper\Hash;
use App\Helper\JsonRenderer;
use App\Helper\Session;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;


use Carlosocarvalho\SimpleInput\Input\Input;
use Exception;
use Psr\Http\Message\ResponseInterface as Response; // http://docs.guzzlephp.org/en/stable/index.html
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;


final class Collection extends \App\Helper\ApiAction
{
    public function read(Request $request, Response $response, $args)
    {


        // phpinfo();
        // return $response;
        return JsonRenderer::success($response,200,null,['date'=>date('Y-m-d')]);
        
    }
    public function create(Request $request, Response $response, $args)
    {
        var_dump(Input::post());
        
        $body = $request->getParsedBody();
        var_dump($body);
    }
    public function update(Request $request, Response $response, $args)
    {

    }
    public function delete(Request $request, Response $response, $args)
    {
        
    }
    
}
