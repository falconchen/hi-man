<?php

namespace App\Action;

use App\Helper\Hash;
use App\Helper\Menu;
use App\Helper\Session;
use App\Model\Group;
use App\Model\User;
use App\Model\Post;
use App\Model\PostMeta;
use App\Model\UserMeta;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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

final class UserAction extends \App\Helper\BaseAction 
{

    private $view;
    private $logger;

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
        

        $postsQuery = Post::where(['post_status' => 'publish', 'post_visibility' => 'public', 'post_type'=>'post','post_author'=>$uid]);

        if ( $this->userId === $uid ) {
            //$postsQuery->orWhereRaw('post_author = ? and post_status <> ?', [$this->userId, 'trash']);
            $postsQuery->orWhereRaw('post_author = ? and post_status <> "trash" and post_type="post"', [$this->userId]);
            
        }
        $posts = $postsQuery->orderBy('post_date', 'DESC')->paginate(10);

        $posts->withPath(remove_query_arg('page'));

        if ($posts->count() > 0) {
            foreach ($posts as &$post) {
                $post->post_author_name = User::where('id', $post->post_author)->first()->username;
            }
        }
        
        
        if(is_null($this->view)){
            $this->view = $this->c->get('view');
        } 
        
        $this->view->render($response, 'user/index.twig', ['posts' => $posts,'space_user'=>$user]);
        


    }


}