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
            if (!preg_match('#\w{12}#', $post_name)) {
                exit("bad request");
            }
            $post = Post::where('post_name', $post_name)->first();

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
        if ($post->post_author_name == 'Falcon' || $post->post_author_name == '小小编辑') {
            $post->post_content_clean = $post->post_content;
        } else {
            $antiXss = new AntiXSS();
            $post->post_content_clean = $antiXss->xss_clean($post->post_content);
        }
        $post->osc_link = getOscPostLink($post->post_id);

        $this->view->render($response, 'post/index.twig', ['post' => $post]);
    }
    /**
     * 同步到osc
     */
    public function syncOsc(Request $request, Response $response, $args)
    {



        try {
            $currentTime = date('Y-m-d H:i');
            $posts = Post::where('post_status', 'future')->where('post_date', '<=', $currentTime)->get();

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

                if (!$hasOscBind) {
                    continue;
                }
                $lock = $this->fileLock('sync_post_' . $postDbData->post_id, false);
                $lock->acquire();
                $result = $this->doSyncOsc($postDbData->post_id);
                $lock->release();

                $this->logger->info('sync post result', ['post_id' => $postDbData->post_id, 'post_title' => $postDbData->post_title, 'result' => $result]);

                $notifyTitle =  '文章 《' . $postDbData->post_title . '》 同步到osc: ' . $result->message;
                $notifyBody = sprintf(
                    '网站文章ID: %d , OSC链接 [%s](%s)',
                    $postDbData->post_id,
                    $postDbData->post_title,
                    $postDbData->getOscLink()
                );

                if (@$this->settings['sync']['email.notify']) {

                    // $mailBody = $result->message . sprintf( 'https://my.oschina.net/u/%s/blog/%s',
                    // $result->result->space,$result->result->id);            
                    $user = User::find($postDbData->post_author);
                    $sendAddress = $user->email;
                    $this->mailer->Subject = $notifyTitle;
                    $this->mailer->Body = $notifyBody;
                    $this->mailer->AddAddress($sendAddress);

                    if (!$this->mailer->send()) {
                        $this->logger->info("failed to send mail to " . $user->email);
                    } else {
                        $this->logger->info("send mail to " . $user->email);
                    }
                }

                if (@isset($this->settings['admin']['sckey'])) {
                    //$body =  
                    $this->scNofify($notifyTitle, $notifyBody);
                }

                sleep(1);
            }
        } catch (Exception $e) {
            $this->logger->error('Sync post error', ['error' => $e->getMessage(), 'detail' => $e->getTraceAsString()]);
        } finally {
            //is_file($lockedFilePath) && unlink($lockedFilePath);
        }
    }


    private function doSyncOsc($postId, $oscSyncOptions = [])
    {
        $this->logger('info', 'start sync ' . $postId);

        //default sync options
        $postArr = array(
            'id' => '', //osc的文章id;
            'user_code' => "i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z",
            'title' => "Title",
            'content' => "Content",
            'content_type' => "4",
            'catalog' => "0",
            'classification' => "430381", //其他类型
            'type' => "1",
            'origin_url' => "",
            'privacy' => "0",
            'deny_comment' => "0",
            'as_top' => "0",
            'downloadImg' => "0",
            'isRecommend' => "0",
        );


        if (empty($oscSyncOptions)) {
            $syncOptions = PostMeta::where(['post_id' => $postId, 'meta_key' => 'osc_sync_options'])->first();

            if ($syncOptions === NULL) {
                throw new Exception('No OSC Sync Options');
            }
            $oscSyncOptions = unserialize($syncOptions->meta_value);
        }

        $postArr = array_merge($postArr, $oscSyncOptions);

        if (!$postArr['classification']) {
            throw new Exception('classification empty');
        }
        if (!$postArr['catalog']) {
            throw new Exception('catalog empty');
        }

        //self::init( $request, $response , $args) ;
        $postDbData = Post::where('post_id', $postId)->first();

        $postArr['title'] = $postDbData->post_title;
        $postArr['content'] = $postDbData->post_content;


        //$this->data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $postDbData->post_author)->where('meta_key', 'osc_userinfo')->first();
        if (!$oscer) {
            throw new Exception("user did not connected to osc yet");
        }
        $oscer = unserialize($oscer->meta_value);

        $blogWriteUrl = $oscer['homepage'] . '/blog/write';
        $blogSaveUrl = $oscer['homepage'] . '/blog/save';

        $cookieField = UserMeta::where('user_id', $postDbData->post_author)->where('meta_key', 'osc_cookie')->first();
        $cookies = unserialize($cookieField->meta_value);


        $conf = $this->settings['guzzle'];
        if (!is_null($cookies)) {
            $conf['cookies'] = $cookies;
        }
        $conf['headers']['Referer'] = $blogWriteUrl;

        $client = new Client($conf);
        //确认分类字段是否存在，获取user_code
        //<input type="hidden" name="user_code" value="i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z">

        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string) $oscResponse->getBody();

        $dom = new \PHPHtmlParser\Dom;
        $dom->load($body, ['whitespaceTextNode' => false]);


        //get userCode
        $userCodeNode = $dom->find('input[name="user_code"]');
        if (!count($userCodeNode)) {
            throw new Exception('userCodeNode empty');
        }
        $userCode = $userCodeNode[0]->getAttribute('value');

        //check catalog
        $catalogNode = $dom->find('#catalogDropdown option[value=' . $postArr['catalog'] . ']');
        if (!count($catalogNode)) {
            throw new Exception('catalog not exists');
        }
        $postArr['user_code'] = $userCode;
        //当文章为更新时
        if ($oscId = getOscPostId($postId)) {

            $oscOldlink = getOscPostLink($postId, $postDbData->post_author); //检测旧文章是否被移除
            try {
                $oscOldPostResponse = $client->request('HEAD', $oscOldlink);

                if ($oscOldPostResponse->getStatusCode() == 200) {
                    $postArr['id'] = $oscId;
                    $blogSaveUrl = $oscer['homepage'] . '/blog/edit';
                }
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                if ($e->getCode() != 404) { //ignore  404
                    throw $e;
                }
            }
        }



        $oldStatus = $postDbData->post_status;
        $postDbData = Post::where('post_id', $postId)->first(); // reload
        if ($postDbData->post_status != $oldStatus) {
            throw new Exception('post not need to sync,having different status');
        }
        // $postDbData->post_status = 'syncing';
        // $postDbData->save();

        // try {
        //     $oscResponse = $client->request('POST', $blogSaveUrl,[
        //         'form_params' => $postArr,
        //     ]);
        // }catch(Exception $e){

        //     $postDbData->post_status = 'future';
        //     $postDbData->save();
        //     throw $e;
        // }
        $this->logger->debug('SyncArgs', ['postId' => $postId, 'oscId' => $oscId, 'postArr' => $postArr, 'blogSaveUrl' => $blogSaveUrl]);
        $oscResponse = $client->request('POST', $blogSaveUrl, [
            'form_params' => $postArr,
        ]);


        $body = (string) $oscResponse->getBody();

        $this->logger->debug('OscReturnMessage', ['OscReturnMessage' => $body]);

        $jData = json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg(), json_last_error());
        }
        $syncResult = PostMeta::firstOrNew(['post_id' => $postId, 'meta_key' => 'osc_sync_result']);
        //$jData->result->content=null; //移除文章内容，减少空间
        @$jData->result->content = mb_substr($jData->result->content, 0, 100, 'UTF-8') . '...'; //移除文
        $syncResult->meta_value = maybe_serialize($jData);
        $syncResult->save();

        $postDbData->post_status = "publish";
        $utcTimestamp = time();
        $postDbData->post_date = date('Y-m-d H:i:s', $utcTimestamp);
        $postDbData->post_date_local = $this->dateTolocal('Y-m-d H:i:s', $postDbData->post_date);
        $postDbData->save();

        return $jData;
    }
}
