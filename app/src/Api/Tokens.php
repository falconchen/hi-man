<?php
namespace App\Api;

use \Ramsey\Uuid\Uuid;
use Psr\Http\Message\ResponseInterface as Response; // http://docs.guzzlephp.org/en/stable/index.html
use Psr\Http\Message\ServerRequestInterface as Request;

final class Tokens extends \App\Helper\ApiAction
{
    public function read(Request $request, Response $response, $args)
    {
        //var_dump(session_id());
        exit("read");
    }
    public function create(Request $request, Response $response, $args)
    {                
        $body = $request->getParsedBody() ?: [];

        

        // $uuid = Uuid::uuid4();

        // printf(
        //     "UUID: %s\nVersion: %d\n",
        //     $uuid->toString(),
        //     $uuid->getFields()->getVersion()
        // );
        
    }
}