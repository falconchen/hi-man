<?php 

namespace App\Helper;

use App\Model\User;
use App\Model\Post;
use App\Model\PostMeta;
use App\Model\UserMeta;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client; // http://docs.guzzlephp.org/en/stable/index.html
use GuzzleHttp\Exception\ClientException;

use Symfony\Component\Config\Definition\Exception\Exception;

trait OscTrait {

    use BaseTrait;

    /**
     * Sync post to osc blog
     *
     * @param [type] $postId
     * @param array $oscSyncOptions
     * @return object
     */
    protected function doSyncPostOsc($postId, $oscSyncOptions = [])
    {

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
            'email_me'=>"1",
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

        $client = $this->setUpClient($postDbData->post_author,['Referer'=> $blogWriteUrl]);
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


        $oscResponse = $client->request('POST', $blogSaveUrl, [
            'form_params' => $postArr,
        ]);
        $body = (string) $oscResponse->getBody();

        $jData = json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg(), json_last_error());
        }
        $syncResult = PostMeta::firstOrNew(['post_id' => $postId, 'meta_key' => 'osc_sync_result']);
        @$jData->result->content = mb_substr($jData->result->content, 0, 100, 'UTF-8') . '...'; //移除文章内容，减少空间
        $syncResult->meta_value = maybe_serialize($jData);
        $syncResult->save();

        $postDbData->post_status = "publish";
        $postDbData->save();

        //发布动弹
        //$this->c->logger->debug('jData',[var_export($jData,true)]);

        if( $oscSyncOptions['send_tweet'] ) {

            if( strpos($jData->message,'审核') !== false ) {
                $this->c->logger->info('stop publish tweet as the aritecle is in review status',[$jData->message]);
                
            }else{
                $this->c->logger->info('start publish tweet for article');
                $tmpl = $oscSyncOptions['tweet_tmpl'];
                $localTimeStamp = $this->localTimestamp();
                $tmplVars = [
                            ':当前日期:'=>date('Y/m/d',$localTimeStamp),
                            ':当前时间:'=>date('H:i:s',$localTimeStamp),
                            ':文章标题:'=>$postDbData->post_title,
                            ':OSC链接:'=>$postDbData->getOscLink()
                            ];
                
                $tweetContent = str_replace(
                    array_keys($tmplVars),
                    array_values($tmplVars),
                    $tmpl
                );
                
                $tweetData = [
                    'userId'=>$oscer['userId'],
                    'user_code'=>$userCode,
                    'content'=>$tweetContent,
                    'code_snippet'=>'',
                    'code_brush'=>'', 
                    'attachment'=> 0,
                ];
                $tweetPubUrl = 'https://www.oschina.net/tweet/pubForwardTweet';

                $oscTweetResponse = $client->request('POST', $tweetPubUrl, ['form_params'=>$tweetData]);
                $tweePubResult  = (string) $oscTweetResponse->getBody();
                $this->c->logger->debug('pub tweet arg ',$tweetData);
                $this->c->logger->info('pub tweet result ',[var_export($tweePubResult,true)]);
                $jData->tweetPub = json_decode($tweePubResult,true);
            }
            
        }

        $this->c->logger->debug('start event post.sync2osc');
        $this->c->get('eventManager')->emit('post.sync2osc', $this->c, $postDbData,$oscSyncOptions,$jData);

        return $jData;
    }
/**
 * Client with cookie
 *
 * @param int $userId
 * @param array $headers
 * @return Client
 */
    protected function setUpClient($userId,$headers=['Referer'=>'https://www.oschina.net']) 
    {
       
        $cookieField = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_cookie')->first();
        if(is_null($cookieField)) {
            throw new Exception( "Cookie not exists for user_id ".$userId );               
        }
        
        $cookies = unserialize($cookieField->meta_value);
        $guzzleConf = $this->c->settings['guzzle'];
        $guzzleConf['cookies'] = $cookies;        
        $conf['headers'] = $headers;

        
        return new Client($guzzleConf);  
    }

}