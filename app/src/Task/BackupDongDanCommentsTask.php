<?php
namespace App\Task;

use \Psr\Container\ContainerInterface;
use \RuntimeException;
use App\Model\Post;
use App\Model\User;
use App\Model\PostMeta;
use App\Model\UserMeta;
use Exception;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client; // http://docs.guzzlephp.org/en/stable/index.html
use GuzzleHttp\Exception\ClientException;

class BackupDongDanCommentsTask {

    /** @var ContainerInterface */
    protected $container;

    protected $items;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     * @return void
     */
    public function __construct($container)
    {
        // access container classes
        // eg $container->get('redis');
        $this->container = $container;
        $this->settings = $this->container->get('settings');
        $this->logger = $this->container->get('logger');        
    }

    /**
     * SampleTask command
     *
     * @param array $args
     * @return void
     */
    public function command($args)
    {   
        //$firstArg = $args[0];     
        // Throw if no arguments provided
        $start_time = time();
        $this->logger->info("Start backup dongdan comments args: ". implode(' ',$args));

        $userId =  empty($args) ? 12 : $args[0]; //HiCMS的用户id
        $fromPostId = isset($args[1]) ? intval($args[1]) : 0;
        $orderBy = isset($args[2]) ? $args[2] : 'post_date';
        $order = isset($args[3]) ? $args[3] : 'desc';
        
        $posts = Post::select('post_id','post_name','post_content')
                            ->where('post_type' , 'tweet')
                            //->where('comment_count' ,'>', 0)
                            ->where('post_id' ,'>=' , $fromPostId)                            
                            ->orderBy($orderBy, $order)
                            //->take(1)
                            ->get() ;

        
        if( $posts->count() == 0) {
            $this->logger->info('No tweet with Comments needs to backup');
            return;
        }
        $this->logger->info('tweet with comments total: '.$posts->count());
        
        $cookieField = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_cookie')->first();
        if(is_null($cookieField)) {
            $this->logError( "Cookie not exists for user_id ".$userId );          
        }
        
        $cookies = unserialize($cookieField->meta_value);
        $guzzleConf = $this->settings['guzzle'];
        $guzzleConf['cookies'] = $cookies;        
        $guzzleConf['headers']['Referer'] = 'https://www.oschina.net/tweets';

        $oscUserInfo = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_userinfo')->first();
        
        if ( is_null($oscUserInfo )) {            
            $this->logError( "Osc User Info failed for user_id ".$userId );          
        }
        $oscUserInfoArr = unserialize($oscUserInfo->meta_value) ;        
        $authorId = $oscUserInfoArr['userId'];
        $client = new Client($guzzleConf);       

        
        $totalTweets = count($posts);
        
        foreach($posts as $k=>$post) {


            $this->logger->info( sprintf("backup comments/likes for %s | %s | %s ",
                    $post->post_id,$post->post_name,$post->post_content));

            $tweetId = $post->post_name;
            

            $urls = [
                    //正常评论
                    'tweet_comments'=>'https://www.oschina.net/action/apiv2/tweet_comments?pageToken=%s&sourceId=%d',
                    //精彩评论
                    'tweet_hot_comments'=>'https://www.oschina.net/action/apiv2/tweet_comments?pageToken=%s&order=1&sourceId=%d', 
                    //点赞数据
                    'tweet_likes'=>'https://www.oschina.net/action/apiv2/tweet_likes?pageToken=%s&sourceId=%d', 
                    ];

            foreach( $urls as $key=> $urlTemplate ) {

                $items = $this->fetchItems($client,$urlTemplate,$tweetId);  
                if($items === false) {
                    $this->logger->info('skip fetching error for tweetId:%d',$tweetId);
                    continue;
                }
                $total = count($items);
                if( $total > 0) {
                    $info = sprintf('store data for %s, total:%d , tweetId:%d ,postId:%d',
                                    $key, $total,$tweetId,$post->post_id);                                    
                    $this->logger->info($info);
                    $postMeta = PostMeta::firstOrNew(['post_id' =>$post->post_id, 'meta_key' => $key]);
                    $postMeta->meta_value = maybe_serialize($items);
                    $postMeta->save();
                }
                
            }
            
            $this->logger->info(sprintf('Progress: %d/%d - %.2f%%',$k,$totalTweets,intval($k)/$totalTweets));

        }

        $end_time = time();        
        $this->logger->info('finish comments/likes backups, time consumed: %s (s)', ($end_time - $start_time) );
        return true;
                


    }


    protected function fetchItems(\GuzzleHttp\Client $client,  $urlTemplate="", $tweetId, $pageToken='') {

        try{

            $this->items = ($pageToken == '') ? [] : $this->items;

            $url = sprintf($urlTemplate,$pageToken,$tweetId);
            $this->logger->info('crawling url : '. $url);
            $oscResponse = $client->request('GET', $url);
            $body = (string) $oscResponse->getBody();
            $dataArr = json_decode($body,true);

            if( $dataArr == null ){
                throw new \Exception('json parse error:'.  json_last_error() );
            }
            if( $dataArr['message'] !== 'SUCCESS' || !isset($dataArr['result']) ){
                throw new \Exception('data message is '.$dataArr['message']);
            }

            if( isset($dataArr['result']['items']) && count($dataArr['result']['items']) === 0 ) {

                $this->logger->info('End of fetching data for '.$url );                
                return $this->items;
            }
            

            foreach($dataArr['result']['items'] as $item) {
                $this->items[] = $item;
            }
            if( $dataArr['result']['responseCount'] < $dataArr['result']['requestCount'] ) {

                return $this->items;
            }
                        
            $this->logger->info('start next page with pageToken: '. $dataArr['result']['nextPageToken']);
            return $this->fetchItems($client,$urlTemplate,$tweetId, $dataArr['result']['nextPageToken']);


        }catch (\Exception $e) {
            
            $msg = $e->getMessage();
            $this->logError($msg);  
            return false;
        }

    }

    
        
        

    //本地时间转换到utc

    protected function dateToUtc($format, $dateStr)
    {
        return date($format, (strtotime($dateStr) - $this->settings['UTC'] * 3600));
    }
        


    private function logError($msg) {
        
        $this->logger->error( $msg );
        throw new RuntimeException($msg);
    }
}