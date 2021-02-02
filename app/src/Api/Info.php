<?php

namespace App\Api;

use App\Helper\Hash;
use App\Model\MediaMap;
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
        // $collection = Collection::firstOrNew(
        //     ['title' => 'My Collection2', 'slug' => 'my-collection2','description'=>'my collection2']
        // );
        //$collection->save();
        //$collection->posts()->sync(123);

        
        //$collection->posts()->attach([345,789,10],['order'=>110]);
        
        //$collection->posts()->detach(10);
        //$collection->posts()->toggle(10);

        //echo Collection::find(1)->media->origin_url;
        
        // $post = Post::find(1);
        // $post->collections()->attach(1,['order'=>888]);

        // Collection::find(1)->posts()->attach(1024,['order'=>8848]); //post_id=1024 ，collection_id=1, order=8848
        //var_dump(Collection::find(1)->posts()->get());

        
        //return JsonRenderer::success($response,200,null,['date'=>date('Y-m-d')]);

        //var_dump(MediaMap::find(1)->media_author);
        //var_dump(Collection::find(15)->append('is_admin')->toArray());
        
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
