<?php

namespace App\Api;

use App\Helper\Hash;
use App\Helper\JsonRenderer;
use App\Helper\Session;
use App\Model\Group;
use App\Model\Post;
use App\Model\Collection;


use Psr\Http\Message\ResponseInterface as Response; // http://docs.guzzlephp.org/en/stable/index.html
use Psr\Http\Message\ServerRequestInterface as Request;



final class Info extends \App\Helper\ApiAction
{
    public function read(Request $request, Response $response, $args)
    {

        //phpinfo();
        $collection = Collection::firstOrNew(
            ['title' => 'My Collection', 'slug' => 'my-collection','description'=>'my first collection']
        );
        $collection->save();
        
        //return JsonRenderer::success($response,200,null,['date'=>date('Y-m-d')]);
        
    }
    public function create(Request $request, Response $response, $args)
    {
       
    }
    public function update(Request $request, Response $response, $args)
    {

    }
    public function delete(Request $request, Response $response, $args)
    {
        
    }
    
}
