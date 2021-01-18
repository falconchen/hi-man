<?php
namespace App\Api;

use Ramsey\Uuid\Uuid;
use App\Model\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response; // http://docs.guzzlephp.org/en/stable/index.html
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helper\JsonRenderer;

final class Tokens extends \App\Helper\ApiAction
{
    public function read(Request $request, Response $response, $args)
    {

        if( $this->userId > 0 ) {
             //@todo 列出用户api权限
            $scopes = [
                "collections.create",
                "collections.read",
                "collections.update",
                "collections.delete",
                "collections.list",
                "collections.all"
            ];
            $payload = [
                'uid' => $this->user->id,
                'scopes' =>$scopes
            ];
            $data = $this->genToken($payload);
            return JsonRenderer::success($response,201,$this->trans('OK'),$data);
        }

        return JsonRenderer::error($response,403,$this->trans('Access deny'));

    }
    public function create(Request $request, Response $response, $args)
    {                

        $requestedBody = $request->getParsedBody() ?: [];
        $identifier = $requestedBody['username'] ?? $requestedBody['email'] ?? null;            
        $password = $requestedBody['password'] ?? null;        

        
        if( (is_null($identifier) || is_null($password))  &&  is_null($this->user) ) {

             return  JsonRenderer::error($response,403, $this->trans('Empty fields'));
        }

        $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();        
        if ($user && $this->hash->passwordCheck($password, $user->password) && $user->status > 0) {
            $this->user = $user;
        }else{
            return JsonRenderer::error($response,403,$this->trans('Identify failed or user inactived'));

        }
            
        $requestedBody['scopes']  = isset( $requestedBody['scopes'] ) ? [$requestedBody['scopes']]  :[];

        //@todo 列出用户api权限
        $validScopes = [
            "collections.create",
            "collections.read",
            "collections.update",
            "collections.delete",
            "collections.list",
            "collections.all"
        ];
    
        $scopes = array_filter($requestedBody['scopes'], function ($needle) use ($validScopes) {
            return in_array($needle, $validScopes);
        });
    

        $payload = [
            'uid' => $this->user->id,
            'scopes' =>$scopes
        ];
        $data = $this->genToken($payload);
        
        return JsonRenderer::success($response,201,$this->trans('OK'),$data);

    }

    private function genToken($payload) {

        $now = new \DateTime();
        $future = new \DateTime("now ".$this->settings['jwt']['timeout']);

        //$jti = (new Base62)->encode(random_bytes(16));
        $jti = Uuid::uuid4()->toString();
    
        $payloadDefault = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "sub" => "HI_AUTH_USER",            
        ];

        $payload = array_merge($payloadDefault,$payload);
        
            
        $secret = $this->settings["jwt"]['secret'];
        $token = JWT::encode($payload, $secret, "HS256");
    
        $data["token"] = $token;
        $data["expires"] = $future->getTimeStamp();
        return $data;
    }

}