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
            //$posts = Post::where('post_date','<=', $currentTime)->get();
            if($posts->count() == 0) {
                exit('None To Sync Post');
            }

            foreach($posts as $postDbData) {

                $hasOscBind = UserMeta::where('user_id', $postDbData->post_author)
                    ->where('meta_key','osc_cookie')->count();
                if(!$hasOscBind) {
                    continue;
                }

                $result = $this->doSyncOsc( $postDbData->post_id );
                $info = sprintf(
                    'Synced Post Id:%d , title:《%s》',
                            $postDbData->post_id,
                            $postDbData->post_title
                );
                $info .= "\n=======\n" .var_export($result,true);

                $this->logger->info($info);

                $mailBody = $result->message . sprintf( 'https://my.oschina.net/u/%s/blog/%s',
                        $result->result->space,$result->result->id);


                $user = User::find($postDbData->post_author);

                $sendAddress = $user->email;
                $this->mailer->Subject = '发布文章到osc';
                $this->mailer->Body = $mailBody;
                $this->mailer->AddAddress($sendAddress);

                if (!$this->mailer->send()) {
                    $this->logger->info("failed to send mail to " . $user->email);
                } else {
                    $this->logger->info("send mail to " . $user->email);
                    //$response = $response->withRedirect($this->router->pathFor('thanks'));
                }

                sleep(1);

            }



        }catch(Exception $e){
            $this->logger->error($e->getMessage() . "\n". $e->getTraceAsString());
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

        $oscResponse = $client->request('POST', $blogSaveUrl,[
            'form_params' => $postArr,
        ]);
        $body = (string)$oscResponse->getBody();

        $jData = json_decode($body);
        if(json_last_error() !== JSON_ERROR_NONE){
            throw new Exception(json_last_error_msg(),json_last_error());
        }
        $syncResult = PostMeta::firstOrNew(['post_id'=>$postId,'meta_key'=>'osc_sync_result']);
        $jData->content=null; //移除文章内容，减少空间
        $syncResult->meta_value = maybe_serialize($jData);
        $syncResult->save();

        $postDbData->post_status="publish";
        $postDbData->save();
        return $jData;


    }
}
