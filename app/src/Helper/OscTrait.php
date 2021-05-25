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
        /* $postArr = array(
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
         */
        $postArr  = self::getDefaultSyncOptions();
        

        if (empty($oscSyncOptions)) {
            $syncOptions = PostMeta::where(['post_id' => $postId, 'meta_key' => 'osc_sync_options'])->first();
            if ($syncOptions === NULL) {
                throw new Exception('No OSC Sync Options');
            }
            $oscSyncOptions = unserialize($syncOptions->meta_value);
        }

        $postArr = $oscSyncOptions = array_merge($postArr, $oscSyncOptions);

        /* if (!$postArr['classification']) {
            throw new Exception('classification empty');
        } */
        if (!$postArr['catalog']) {
            throw new Exception('catalog empty');
        }

        //self::init( $request, $response , $args) ;
        $post = Post::where('post_id', $postId)->first();
        $postArr['title'] = $post->post_title;
        $postArr['content'] = $post->post_content;
        
        

        //对原创且公开的内容特殊处理：
        if ( $oscSyncOptions['privacy'] == 0 && $oscSyncOptions['type'] == 1 ) {

            $postLink = rtrim(hiGetSettings('app')['url'],'/'). $this->c->router->pathFor('post',['name'=>$post->post_name]);

            // $postArr['content'] = '<blockquote style="margin-bottom:8px;background-color: cornsilk;border-left: 8px solid burlywood;">温馨提示：以下内容已加密，请自行解密后查看。</blockquote>';

            // $postArr['content'] .= base64_encode($post->post_content); // 用base64编码公开的内容

            //$postArr['content'] = $post->post_content;

            $postArr['content'] .= sprintf('<blockquote style="margin-top:8px;background-color: cornsilk;border-left: 8px solid burlywood;">
            
            <section>原文地址：%s</section>
            <section>本文在 <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-Hans">CC BY-NC-SA 4.0 许可</a>下发布</section>
            <ul>
                <li><strong>署名</strong> - 您可以复制、发行、展览、表演、放映、广播或通过信息网络传播本作品，但必须<strong>署名作者</strong>并添加链接到<a href="%s">原文地址</a>。</li>
                <li><strong>非商业性使用</strong> — 您不得将本作品用于商业目的。</li>
                <li><strong>相同方式共享</strong> — 如果您再混合、转换或者基于本作品进行创作，您必须基于与原先许可协议相同的许可协议 分发您贡献的作品。</li>
                <li><strong>没有附加限制</strong> — 您不得适用法律术语或者 技术措施 从而限制其他人做许可协议允许的事情</li>
            </ul>
                        
            </blockquote>',$postLink,$postLink);
            
        }
        

        

        //$this->data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $post->post_author)->where('meta_key', 'osc_userinfo')->first();
        if (!$oscer) {
            throw new Exception("user did not connected to osc yet");
        }
        $oscer = unserialize($oscer->meta_value);

        $blogWriteUrl = $oscer['homepage'] . '/blog/write';
        $blogSaveUrl = $oscer['homepage'] . '/blog/save';

        $client = $this->setUpClient($post->post_author,['Referer'=> $blogWriteUrl]);
        //确认分类字段是否存在，获取user_code
        //<input type="hidden" name="user_code" value="i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z">

        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string) $oscResponse->getBody();
        $this->c->logger->debug('blog write url',['body'=> $body]);
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

            $oscOldlink = getOscPostLink($postId, $post->post_author); //检测旧文章是否被移除
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

        $post->post_status = "publish";
        $post->save();
        $post->updatePostMeta('last_sync_osc',time());
        //发布动弹
        //$this->c->logger->debug('jData',[var_export($jData,true)]);

        if( isset($oscSyncOptions['send_tweet']) && $oscSyncOptions['send_tweet'] ) {

            if( strpos($jData->message,'审核') !== false ) {
                $this->c->logger->info('stop publish tweet as the aritecle is in review status',[$jData->message]);
                
            }else{
                $this->c->logger->info('start publish tweet for article');
                $tmpl = $oscSyncOptions['tweet_tmpl'];
                $localTimeStamp = $this->localTimestamp();
                $tmplVars = [
                            ':当前日期:'=>date('Y/m/d',$localTimeStamp),
                            ':当前时间:'=>date('H:i:s',$localTimeStamp),
                            ':文章标题:'=>$post->post_title,
                            ':OSC链接:'=>$post->getOscLink()
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
                $post->updatePostMeta('last_send_tweet',time());
            }
            
        }

        $this->c->logger->debug('start event post.sync2osc');
        $this->c->get('eventManager')->emit('post.sync2osc', $this->c, $post,$oscSyncOptions,$jData);

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

    /**
     * 默认同步到osc的文章设置,分类，分区，可见性
     *
     * @return array
     */
    public static function getDefaultSyncOptions()
    {
      /*   return [
            "stop_sync"=>0,
            "catalog" => "304044",
            //"classification" => "430381",
            "groups"=>14, // 14 程序人生
            "type" => "1",            
            'as_top' => 0,
            'privacy' => 0, 
            'deny_comment' => 0,
            'downloadImg' => 0,            
            'send_tweet' =>0, //不发送动弹
            'email_me' =>0,
        ];
 */
        return [   
            
            // 'id' => '', //osc的文章id;
            'user_code' => "",
            'title' => "",
            'content' => "",
            'content_type' => "4",
            'catalog' => "0",
            // 'classification' => "430381", //其他类型
            "groups"=>14, // 14 程序人生
            'type' => 1,
            'origin_url' => "",
            'privacy' => 0,
            'deny_comment' => 0,
            'as_top' => 0,
            'downloadImg' => 0,
            'isRecommend' => 0,
            'email_me'=>0,
            "stop_sync"=>0,
        ];
    }


    protected  function updateOscCookie($userId) {

        $oscLoginData = UserMeta::where('user_id', $userId)
                        ->where('meta_key', 'osc_login')                                                
                        ->first();

        $oscLogin = unserialize($oscLoginData->meta_value);                        
        

        $userMail = $oscLogin['userMail'];
        $userPassword = $oscLogin['userPassword'];

        $loginUrl = 'https://www.oschina.net/action/user/hash_login?from=';
            //$args = $this->settings['guzzle'];
            
            $client = new Client( $this->c->settings['guzzle'] );
            $oscResponse = $client->request('POST', $loginUrl,[
                'form_params' => [
                    'email' => $userMail,
                    'pwd' => $userPassword,
                    //'verifyCode'=>'',
                    'save_login'=>1,
                ]
            ]);
            $body = (string) $oscResponse->getBody();
            if($body == ''){ //登录成功返回空值

                //带cookie去获取osc用户名和头像
                $oscResponse = $client->request('GET', 'https://my.oschina.net/');
                $body = (string) $oscResponse->getBody();
                

                $dom = new \PHPHtmlParser\Dom;
                $dom->load($body,['whitespaceTextNode' => false]);
                $imgNode = $dom->find('.osc-avatar img');
                $homepageNode = $dom->find('.avatar-image__inner');
                $userIdNode = $dom->find('.current-user-avatar');
                $oscer = [];


                if( count($imgNode) && count($homepageNode) && count($userIdNode))  {
                    $oscer['userName'] = $imgNode[0]->getAttribute('title');
                    $oscer['avatar'] = $imgNode[0]->getAttribute('src');
                    $oscer['userId'] = $userIdNode[0]->getAttribute('data-user-id');
                    $oscer['homepage'] = $homepageNode[0]->getAttribute('href');
                    $oscer['signature'] = '';
                    $signature_node = $dom->find('.user-signature');
                    if( count($signature_node) ){
                        $oscer['signature'] = $signature_node[0]->text;
                    }
                    //var_dump($oscer);
                    //保存用户名密码
                    // $userId = $this->userId;

                    // $userMail = Input::post('userMail');
                    // $userPassword = Input::post('userPassword');
                    // $userMeta = new UserMeta();
                    // $userMeta->user_id = $userId;
                    // $userMeta->meta_key = 'osc_login';
                    // $userMeta->meta_value = maybe_serialize(
                    //     ['userMail'=>$userMail,'userPassword'=>$userPassword]
                    // );
                    
                    // $userMeta->save();


                    //获取cookie,保存到DB
                    $cookieJar = $client->getConfig('cookies');
                    //$cookieJar->toArray();
                    $userMeta = UserMeta::firstOrCreate(['user_id'=>$userId,'meta_key'=>'osc_cookie']);
                                        
                    $userMeta->meta_value = maybe_serialize(
                        $cookieJar
                    );
                    
                    $userMeta->save();

                    //保存osc用户信息
                    
                    $userMeta = UserMeta::firstOrCreate(['user_id'=>$userId,'meta_key'=>'osc_userinfo']);                                        
                    $userMeta->meta_value = maybe_serialize(
                        $oscer
                    );
                    $userMeta->save();



                }else{
                    throw new \Exception('fail to get OSCer info');
                }

                $this->c->logger->debug('updated user osc cookie ',['userId'=>$userId]);
                return true;



            }else{
                throw new \Exception('fail to update OSCer info');
            }
    }
    /**
     * 取出存在数据库的Osc用户信息
     */

    protected function initOscerMenuData($userId) {

        $data = [];
        $oscer = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_userinfo')->first();

        $oscCookieKeepAliveDays = isset( $this->settings['osc']['cookie_keep_alive_days'] ) ?  
        $this->settings['osc']['cookie_keep_alive_days']: 7; 
        

        if ($oscer) {
            $data['oscer'] = unserialize($oscer->meta_value);
            $data['avatar'] = $data['oscer']['avatar'];

            //$oscer = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_userinfo')->first();
            $cookieSafeTime = date('Y-m-d H:i:s' ,strtotime("-".abs($oscCookieKeepAliveDays)." days"));

            $oscCookie= UserMeta::where('user_id', $userId)
                        ->where('meta_key', 'osc_cookie')                        
                        ->where('updated_at','<=',$cookieSafeTime)
                        ->first();

            if($oscCookie != NULL) { // 更新过期的cookie
                
                try {
                    $this->updateOscCookie( $this->userId );      
                    $this->logger->info( 'updated osc cookie ', ['userId'=>$this->userId] );              
                }catch(Exception $e) {
                    $this->logger->error( 'failed to update osc cookie ', ['userId'=>$this->userId] );
                }                

            }
                        
        }
        return $data;
    }

}