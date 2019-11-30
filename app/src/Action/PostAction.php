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

final class PostAction extends \App\Helper\BaseAction
{

    public function index(Request $request, Response $response, $args) {
        echo "hello";
        
    }
    /**
     * 同步到osc
     */
    public function syncOsc(Request $request, Response $response, $args) {



        try{
            $currentTime = date('Y-m-d H:i');            
            $posts = Post::where('post_status','future')->where('post_date','<=', $currentTime)->get();         
            if( $posts->count() > 0 ){     
                   // 过滤未绑定osc帐户的      
                foreach( $posts as $k=>$post ){
                    $hasOscBind = UserMeta::where('user_id', $post->post_author)
                    ->where('meta_key','osc_cookie')->count();
                    if(!$hasOscBind) {
                        unset($posts[$k]);
                    }
                }
            }
            
            if($posts->count() == 0) {  //再次检查                           
                exit('None To Sync Post');
            }                                                
            foreach($posts as $postDbData) {
                
                if(!$hasOscBind) {
                    continue;
                }
                $lock = $this->fileLock('sync_post_'.$postDbData->post_id,false);
                $lock->acquire();
                $result = $this->doSyncOsc( $postDbData->post_id );
                $lock->release();
                
                $this->logger->info('sync post result',['post_id'=>$postDbData->post_id, 'post_title'=>$postDbData->post_title, 'result'=>$result]);

                $notifyTitle = '同步文章到osc结果:' . $result->message;
                $notifyBody = sprintf('post_id:%d-[%s], https://my.oschina.net/u/%s/blog/%s',
                    $postDbData->post_id,
                    $postDbData->post_title,
                    $result->result->space,
                    $result->result->id); 
                    
                if( @$this->settings['sync']['email.notify'] ) {
                    
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
                
                if( @isset($this->settings['admin']['sckey']) ){
                    //$body =  
                    $this->scNofify($notifyTitle, $notifyBody);
                }

                sleep(1);
            }
            
        }catch(Exception $e){
            $this->logger->error('Sync post error', ['error'=> $e->getMessage() ,'detail'=>$e->getTraceAsString()]);

        }finally{
            //is_file($lockedFilePath) && unlink($lockedFilePath);
        }

    }


    private function doSyncOsc($postId,$oscSyncOptions=[]) {

        //default sync options
        $postArr = array(
            'id'=>'', //osc的文章id;
            'user_code'=> "i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z",
            'title'=>"Title",
            'content'=>"Content",
            'content_type'=>"4",
            'catalog'=>"0",
            'classification'=>"430381",//其他类型
            'type'=>"1",
            'origin_url'=>"",
            'privacy'=>"0",
            'deny_comment'=>"0",
            'as_top'=>"0",
            'downloadImg'=>"0",
            'isRecommend'=>"0",
        );


        if(empty($oscSyncOptions)) {
            $syncOptions = PostMeta::where(['post_id'=>$postId,'meta_key'=>'osc_sync_options'])->first();
            if($syncOptions === NULL){
                throw new Exception('No OSC Sync Options');
            }
            $oscSyncOptions = unserialize($syncOptions->meta_value);
        }

        $postArr = array_merge($postArr,$oscSyncOptions);

        if( !$postArr['classification'] ) {
            throw new Exception('classification empty');
        }
        if( !$postArr['catalog'] ) {
            throw new Exception('catalog empty');
        }

        //self::init( $request, $response , $args) ;
        $postDbData = Post::where('post_id',$postId)->first();
        
        $postArr['title'] = $postDbData->post_title;
        $postArr['content'] = $postDbData->post_content;


        //$this->data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $postDbData->post_author)->where('meta_key','osc_userinfo')->first();
        if( !$oscer ){
            throw new Exception("user did not connected to osc yet");
        }
        $oscer = unserialize($oscer->meta_value);

        $blogWriteUrl = $oscer['homepage'] .'/blog/write';
        $blogSaveUrl = $oscer['homepage'] .'/blog/save';

        $cookieField= UserMeta::where('user_id', $postDbData->post_author)->where('meta_key','osc_cookie')->first();
        $cookies = unserialize($cookieField->meta_value);


        $conf = $this->settings['guzzle'];
        if( !is_null($cookies) ) {
            $conf['cookies'] = $cookies;
        }
        $conf['headers']['Referer'] = $blogWriteUrl;

        $client = new Client($conf);
        //确认分类字段是否存在，获取user_code
        //<input type="hidden" name="user_code" value="i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z">

        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string)$oscResponse->getBody();

        $dom = new \PHPHtmlParser\Dom;
        $dom->load($body,['whitespaceTextNode' => false]);


        //get userCode
        $userCodeNode = $dom->find('input[name="user_code"]');
        if(!count($userCodeNode)) {
            throw new Exception('userCodeNode empty');
        }
        $userCode = $userCodeNode[0]->getAttribute('value');

        //check catalog
        $catalogNode = $dom->find('#catalogDropdown option[value='. $postArr['catalog'] .']');
        if(!count($catalogNode)) {
            throw new Exception('catalog not exists');
        }
        $postArr['user_code'] = $userCode;
        
        
        $oldStatus = $postDbData->post_status;
        $postDbData = Post::where('post_id',$postId)->first(); // reload
        if($postDbData->post_status != $oldStatus) {
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
        $oscResponse = $client->request('POST', $blogSaveUrl,[
            'form_params' => $postArr,
        ]);
        
        
        $body = (string)$oscResponse->getBody();

        $jData = json_decode($body);
        if(json_last_error() !== JSON_ERROR_NONE){
            throw new Exception(json_last_error_msg(),json_last_error());
        }
        $syncResult = PostMeta::firstOrNew(['post_id'=>$postId,'meta_key'=>'osc_sync_result']);
        //$jData->result->content=null; //移除文章内容，减少空间
        $syncResult->meta_value = maybe_serialize($jData);
        $syncResult->save();

        $postDbData->post_status="publish";
        $utcTimestamp = time();
        $postDbData->post_date=date('Y-m-d H:i:s',$utcTimestamp);
        $postDbData->post_date_local=$this->dateTolocal('Y-m-d H:i:s',$postDbData->post_date);
        $postDbData->save();
        
        return $jData;


    }
}