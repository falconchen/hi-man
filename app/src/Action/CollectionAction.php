<?php

namespace App\Action;

use App\Helper\Hash;
use App\Helper\Menu;
use App\Helper\Session;
use App\Model\Group;
use App\Model\User;
use App\Model\Post;
use App\Model\Collection;
use App\Model\PostMeta;
use App\Model\UserMeta;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;

use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Http\StatusCode;


use App\Helper\JsonRenderer;

use App\Helper\Url;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client; // http://docs.guzzlephp.org/en/stable/index.html
use GuzzleHttp\Exception\ClientException;
use Response as GlobalResponse;
use Route;
use stdClass;
use Symfony\Component\Config\Definition\Exception\Exception;
use Illuminate\Database\Capsule\Manager as DB;

use Violin\Violin;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

use voku\helper\AntiXSS;

final class CollectionAction extends \App\Helper\BaseAction 
{

        

    public function index(Request $request, Response $response, $args)
    
    {       
        
        
 
        $uid = isset($args['uid']) ? intval($args['uid']) : $this->userId; 
        

        if( $uid === 0 ) {            
            return $response->withRedirect($this->router->pathFor('homepage')); // invalid request redirect to homepage
        }


        
        // check user exists
        $user = User::where('id', $uid)->first();

        if( is_null($user) ) {
            return $response->withRedirect($this->router->pathFor('homepage')); // invalid request redirect to homepage
        }

        
        
        $allowPostTypes = ['post','tweet',];

        $collections = Collection::where('author',$uid)->paginate(10);
        $collections->withPath(remove_query_arg('page'));
        
        
        $this->view->render($response, 'collection/index.twig', 

                        [                            
                            'collections'=>$collections,
                            'spaceUser'=>$user,
                            'currentPostType'=>'collection',
                            'allowPostTypes'=>$allowPostTypes
                        ]
                    );        

    }

    public function detail(Request $request, Response $response, $args){
        exit("here is the detail");
    }


}