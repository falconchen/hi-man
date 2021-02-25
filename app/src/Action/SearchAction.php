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

        if(!isset($query['searchPostType']) || empty($query['searchPostType']) || !is_array($query['searchPostType'])) {
            $searchPosType = ['tweet','post','gallery'];
        }else{
            $searchPosType = $query['searchPostType'];
        }


        if(!isset($query['searchUserId']) || empty($query['searchUserId']) ) {
            $searchUserId = 0;
        }else{
            $searchUserId = intval($query['searchUserId']);
        }

        
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
            
        $postsQuery = $postsQuery->where( function($query) use($searchUserId) {

            $query->where(['post_status' => 'publish','post_visibility' => 'public','post_status'=>'publish']);

            if ($this->userId > 0) {

                $query->orWhere(function($query){
                    $query->where(['post_author' => $this->userId ])->where('post_status','<>','trash');
                });
            }



        });

        $postsQuery = $postsQuery->whereIn('post_type',$searchPosType); //全面开放搜索动弹和文章
        // if($searchUserId > 0) {
        //     $postsQuery = $postsQuery->where('post_author',$searchUserId);
        // }
//        echo $this->getSQL($postsQuery);exit;
        $posts = $postsQuery->orderBy('post_date', 'DESC')->paginate(10);
        $posts->withPath(urldecode(remove_query_arg('page')));
        
                
        if ($posts->count() > 0) {

            foreach ($posts as &$post) {
                // $post->post_modified = $this->dateTolocal('Y-m-d H:i:s', $post->post_modified);
                $post->post_author_name = User::where('id', $post->post_author)->first()->username;
            }
        }


     
        $data['posts'] = $posts;
        $data['searchConditions'] = $query;

        $this->view->render($response, 'search.twig', $data);
        return $response;
    }
    
}
