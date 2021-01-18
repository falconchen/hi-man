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

        

        //$uuid = Uuid::uuid4();
        $requested_scopes = $request->getParsedBody() ?: [];

        $valid_scopes = [
            "collections.create",
            "collections.read",
            "collections.update",
            "collections.delete",
            "collections.list",
            "collections.all"
        ];
    
        $scopes = array_filter($requested_scopes, function ($needle) use ($valid_scopes) {
            return in_array($needle, $valid_scopes);
        });
    
        $now = new \DateTime();
        $future = new \DateTime("now +2 hours");
        $server = $request->getServerParams();
    
        
        //$jti = (new Base62)->encode(random_bytes(16));
        $jti = Uuid::uuid4()->toString();
    
        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "sub" => $server["PHP_AUTH_USER"],
            "scope" => $scopes
        ];
    
        $secret = getenv("JWT_SECRET");
        $token = JWT::encode($payload, $secret, "HS256");
    
        $data["token"] = $token;
        $data["expires"] = $future->getTimeStamp();
    
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));


        printf(
            "UUID: %s\nVersion: %d\n",
            $uuid->toString(),
            $uuid->getFields()->getVersion()
        );
        
    }
}