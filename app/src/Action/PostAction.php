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


final class PostAction extends \App\Helper\BaseAction
{

    use \App\Helper\OscTrait;

    public function index(Request $request, Response $response, $args)
    {
        //echo "coming soon...";

        // $translator = new Translator('zh_CN');
        // $translator->addLoader('array', new ArrayLoader());
        // $translator->addResource('array', [
        //     'coming soon...' => '正在开发，敬请期待...',
        // ], 'zh_CN');
        // echo $this->translator->transChoice(
        //     'Apple String',

        //     1,
        //     array('%count%' => 1)
        // );
        // echo $this->translator->transChoice(
        //     'Apple String',

        //     2,
        //     array('%count%' => 2)
        // );
        // exit;
        // print($this->translator->trans('comming soon'));
        // exit;
            


        if (Input::get('preview') && !empty($this->flash->getMessage('preview_post'))) {

            $preview = $this->flash->getMessage('preview_post');
            $post = unserialize($preview[0]);
            $post->post_preview = true;
        } else {
            //正常预览

            $post_name = $args['name'];
            // if (!preg_match('#\w{12}#', $post_name)) {
            //     exit("bad request");
            // }
            $post = Post::where('post_name', $post_name)->first();
            if(is_null($post)){
                exit("Post Not Found");
            }

            if (($post->post_status !== 'publish'
                || $post->post_visibility !== 'public')) {

                if (
                    $post->post_author !== $this->userId
                    || ($this->user !== null && $this->user->group > 2)
                ) {
                    exit("not pervilage to read"); //非当前用户可见
                }
            }
        }




        $post->post_author_name = User::where('id', $post->post_author)->first()->username;
        if ( in_array($post->post_author_name, ['Falcon' ,'小小编辑','HackerNews']) ) {
            $post->post_content_clean = $post->post_content;
        } else {
            $antiXss = new AntiXSS();
            $post->post_content_clean = $antiXss->xss_clean($post->post_content);
        }
        
        
        $post->osc_link = getOscPostLink($post->post_id);


        $tmpl = 'post/content-'.$post->post_type .'.twig';
        if(!$this->view->getLoader()->exists($tmpl)) {
            $tmpl = 'post/content-index.twig';
        }
        
        $this->view->render($response, $tmpl, ['post' => $post]);
    }
    /**
     * 同步到osc
     */
    public function syncOsc(Request $request, Response $response, $args)
    {



        try {
            
            $currentTime = date('Y-m-d H:i');
            $posts = Post::where(['post_status'=>'future','post_type'=>'post'])->where('post_date', '<=', $currentTime)->get();

            if ($posts->count() > 0) {
                // 过滤未绑定osc帐户的      
                foreach ($posts as $k => $post) {
                    $hasOscBind = UserMeta::where('user_id', $post->post_author)
                        ->where('meta_key', 'osc_cookie')->count();
                    if (!$hasOscBind) {
                        unset($posts[$k]);
                    }
                }
            }

            if ($posts->count() == 0) {  //再次检查                           
                exit('None To Sync Post');
            }

            foreach ($posts as $postDbData) {

                try {
                    
                    $this->logger->info('ready to sync post in cron job, post_id: '. $postDbData->post_id);
                   
                    $syncOptions = PostMeta::where(['post_id' => $postDbData->post_id, 'meta_key' => 'osc_sync_options'])->first();

                    if ($syncOptions === NULL) {
                        throw new Exception('No OSC Sync Options');
                    }
                    $oscSyncOptions = unserialize($syncOptions->meta_value);

                    $lock = $this->fileLock('sync_post_' . $postDbData->post_id, false);
                    $lock->acquire();
                    
                    $result = $this->doSyncPostOsc($postDbData->post_id,$oscSyncOptions);
                    $lock->release();

                    $this->logger->info('sync post result', ['post_id' => $postDbData->post_id, 'post_title' => $postDbData->post_title, 'result' => $result]);

                    sleep(1);

                } catch (Exception $e) {
                    
                    $this->logger->error('Sync post error', [ 'post_id'=>$postDbData->post_id,'error' => $e->getMessage(), 'detail' => $e->getTraceAsString()]);
                    @$lock->release();
                    continue;
                }

            }

        } catch (Exception $e) {
            $this->logger->error('cron Sync post error', ['error' => $e->getMessage(), 'detail' => $e->getTraceAsString()]);
        } finally {
            //is_file($lockedFilePath) && unlink($lockedFilePath);
        }
    }


    
}
