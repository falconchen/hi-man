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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;



final class SearchAction extends \App\Helper\BaseAction
{

    use \App\Helper\OscTrait;

    public function index(Request $request, Response $response, $args)
    {
        
        $query = $request->getQueryParams();
        $keyword = trim($query['kw']);        
        
        if(strlen($keyword) == 0){
            exit("No Query String");
        }
        
        $data = array();

        $postsQuery = Post::where(
            function($query) use ($keyword) {
                $query->where('post_content', 'like', "%$keyword%")
                ->orWhere('post_title', 'like', "%$keyword%");
            }
        );
            
        $postsQuery = $postsQuery->where(function($query){

            $query->where(['post_status' => 'publish','post_visibility' => 'public','post_status'=>'publish','post_type'=>'post']);//非登录状态下不能搜索动弹

            if ($this->userId > 0) {                        
                $query->orWhere(function($query){
                    $query->where(['post_author' => $this->userId ])->where('post_status','<>','trash')->whereIn('post_type',['post','tweet']);//登录后能搜索文章和 只能搜索自己的动弹，暂时不允许搜索他人动弹
                });
            }

        });

        //$postsQuery = $postsQuery->whereIn('post_type',['post','tweet']);//全面开放搜索动弹和文章    
        //echo $this->getSQL($postsQuery);exit;
        $posts = $postsQuery->orderBy('post_date', 'DESC')->paginate(10);
        $posts->withPath(urldecode(remove_query_arg('page')));
        
                
        if ($posts->count() > 0) {

            foreach ($posts as &$post) {
                // $post->post_modified = $this->dateTolocal('Y-m-d H:i:s', $post->post_modified);
                $post->post_author_name = User::where('id', $post->post_author)->first()->username;
            }
        }


     
        $data['posts'] = $posts;        

        $this->view->render($response, 'search.twig', $data);
        return $response;
    }
    
}