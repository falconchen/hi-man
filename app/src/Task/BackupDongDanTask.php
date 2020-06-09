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

class BackupDongDanTask {

    /** @var ContainerInterface */
    protected $container;

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
        $this->logger->info("Start backup dongdan args: ". implode(' ',$args));

        $userId =  empty($args) ? 12 : $args[0];
        $pageToken = isset($args[1]) ? $args[1] : '';

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

        $myTweetsUrl = sprintf('https://www.oschina.net/action/apiv2/tweets?authorId=%d&pageToken=%s',$authorId,$pageToken);
        try{

            $this->logger->info('crawling url : '. $myTweetsUrl);
            $oscResponse = $client->request('GET', $myTweetsUrl);
            $body = (string) $oscResponse->getBody();
            $tweetsArr = json_decode($body,true);

            if( $tweetsArr == null ){
                throw new \Exception('json parse error:'.  json_last_error() );
            }
            //var_dump($tweetsArr);
            if( $tweetsArr['message'] !== 'SUCCESS' ){
                throw new \Exception('data message is '.$tweetsArr['message']);
            }


            if( isset($tweetsArr['result']['items']) && count($tweetsArr['result']['items']) === 0 ) {
                if( $tweetsArr['result']['totalResults'] > 0 ) {                    
                    $this->logger->info('End of backup' );
                }else{
                    $this->logger->info('No tweet to backup' );
                }
                exit;
            }

            foreach($tweetsArr['result']['items'] as $item) {

                $this->logger->info('backup tweet to Post : '. $item['href']);
                $this->logger->info( sprintf("%d: %s", $item['id'], $item['content'] ) );
                $post = Post::firstOrNew(['post_name' => sprintf('%012d',$userId.$item['id'])]);                
                $post->post_author = $userId ;
                $post->post_content = $item['content'];
                $post->post_date = $this->dateToUtc('Y-m-d H:i:s',$item['pubDate']);
                $post->post_date_local = $item['pubDate'];                
                $post->post_modified = date('Y-m-d H:i:s');
                $post->post_type = 'tweet';
                $post->post_title = '';
                $post->comment_count = $item['statistics']['comment'];                
                if( $post->save() ){
                    $this->logger->info('backup tweet to PostMeta : '. $item['href']);
                    unset($item['content']); //减小体积
                    $postMeta = PostMeta::firstOrNew(['post_id' =>$post->post_id, 'meta_key' => 'tweet_info']);
                    $postMeta->meta_value = maybe_serialize($item);
                    $postMeta->save();
                }                                                               

            }

            
            $argsNext = [$userId, $tweetsArr['result']['nextPageToken']];
            $this->logger->info('start next page with args: '.implode(' ',$argsNext));
            $this->command($argsNext);


        }catch (\Exception $e) {
            
            $msg = $e->getMessage();
            $this->logError($msg);     
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