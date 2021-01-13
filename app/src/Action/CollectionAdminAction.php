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
use Slim\Http\Response as Response;
use Slim\Http\Request as Request;
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
use Whoops\Handler\JsonResponseHandler;

final class CollectionAdminAction extends \App\Helper\LoggedAction
{

    use \App\Helper\OscTrait;

    private $data;


    private function init(Request $request, Response $response, $args)
    {
        
                
        $this->data = $this->initOscerMenuData( $this->userId );
        $this->data['flash'] = $this->flash->getMessage('flash');
        $route = $request->getAttribute('route');
        $menu = new Menu($route, $this->user);
        $this->data['menu'] = $menu;

        
    }

    public function index(Request $request, Response $response, $args)
    { //list all posts
        $this->init($request, $response, $args);



        //$postAuthor = $this->userId;
       $this->data['arr'] = ['a'=>1,'b'=>2];

        $this->view->render($response, 'collection-admin/index.twig', $this->data);
    }

    public function collectionNew(Request $request, Response $response, $args)
    {

        
    }


    /**
     * 保存预览的session
     */
    public function savePreview(Request $request, Response $response, $args)
    {
        //@todo : check csrf

        if (!empty(Input::post()) && is_array(Input::post())) {
            $post = new Post;
            foreach (Input::post()  as $field => $value) {
                if (strpos($field, 'post') === 0) {
                    $post->$field = $value;
                }
            }
            $post->post_author = $this->userId;

            $this->flash->addMessage('preview_post', maybe_serialize($post));
            $data = [
                'url' => add_query_arg('preview', 'true', $this->router->pathFor('post', ['name' => 'burn-after-reading']))
            ];
            $this->JsonRender->render($response, 200, $data);
        }
    }
}
