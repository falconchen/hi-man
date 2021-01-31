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

        $user = $this->getAuthorFromArgs($request, $response, $args);
        $uid = $user->id;


        $allowPostTypes = ['post', 'tweet',];

        $collections = Collection::where('author', $uid)->orderBy('updated_at', 'desc')->paginate(9);
        $collections->withPath(remove_query_arg('page'));


        $this->view->render(
            $response,
            'collection/index.twig',

            [
                'collections' => $collections,
                'spaceUser' => $user,
                'currentPostType' => 'collection',
                'allowPostTypes' => $allowPostTypes
            ]
        );
    }

    public function detail(Request $request, Response $response, $args)
    {
        $user = $this->getAuthorFromArgs($request, $response, $args);
        $collection = Collection::where(['author' => $user->id, 'slug' => $args['slug']])->first();
        
        $posts = array();
        if (!is_null($collection)) {
            
            $posts = $collection->posts()->orderBy('post_date','DESC')->paginate(10);
            $posts->withPath(remove_query_arg('page'));            
        }
        $this->view->render($response,'collection/detail.twig',[
            'collection'=>$collection,
            'posts'=>$posts,
            'author'=>$user,
        ]);
    }

    private function getAuthorFromArgs(Request $request, Response $response, $args)
    {

        $username = isset($args['username']) ? strval($args['username']) : $this->user->username;

        $user = User::where('username', $username)->first();
        if (is_null($user)) {
            return $response->withRedirect($this->router->pathFor('homepage')); // invalid request redirect to homepage
        }
        return $user;
    }
}
