<?php

namespace App\Api;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;

use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Exception;
use Psr\Http\Message\ResponseInterface as Response; // http://docs.guzzlephp.org/en/stable/index.html
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;


final class Collection extends \App\Helper\ApiAction
{
    public function read(Request $request, Response $response, $args)
    {
        //var_dump(session_id());
        exit("read");
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
