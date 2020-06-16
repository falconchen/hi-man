<?php
namespace App\Task;

use App\Model\Post;
use App\Model\PostMeta;

use function GuzzleHttp\Psr7\build_query;

class BackupDongDanTask extends BackupDongDanAbstract{


    public function __construct($container)
    {
        parent::__construct($container);
        $this->logger->info("=== Running Task :". $this->getShortName());
    }

    /**
     * BackupDongDan
     * php public/index.php BackupDongDan "userId=12&pageToken=DBA816934CD0AA59&forceUpdate=0"
     * @param array $args
     * @return void
     */
    public function command($args)
    {   
        
        $this->logger->info("Start backup dongdan with args: ". var_export($args,true));

        $inputs = $this->initInputs($args);
        
        $userId = isset($inputs['userId']) ? $inputs['userId'] : 12;
        $pageToken = isset($inputs['pageToken']) ? $inputs['pageToken'] : '';
        $forceUpdate = isset($inputs['forceUpdate']) ? boolval($inputs['forceUpdate']) : false;
        

        $client = $this->setupClient($userId);

        $oscUserInfoArr = $this->getOSCUserInfo($userId);
        $authorId = $oscUserInfoArr['userId'];

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
                    $this->logger->info('End of tweets backup' );
                }else{
                    $this->logger->info('No tweet to backup' );
                }
                $this->logger->info(sprintf('finish tweets backup, time consumed: %d (s)', (time() - $this->startTime)));
                return ;                
            }

            foreach($tweetsArr['result']['items'] as $item) {

                $this->logger->info('backup tweet to Post : '. $item['href']);
                $this->logger->info( sprintf("%d: %s", $item['id'], $item['content'] ) );
                
                if ( !$forceUpdate && ($lastTweet = Post::where(['post_name'=>$item['id'],'post_type'=>'tweet'])->first()) ) {

                    $this->logger->info( sprintf('reach the Tweet last backuped (tweetId:%d) %s ',$lastTweet->post_name ,$lastTweet->post_content));

                    $this->logger->info(sprintf('finish tweets backup, time consumed: %d (s)', (time() - $this->startTime)));
                    return;
                    
                }                

                $post = Post::firstOrNew(['post_name'=>$item['id']]);                
                $post->post_name =  $item['id'];
                $post->post_author = $userId ;
                $post->post_content = $item['content'];                
                $post->post_date = $this->dateToUtc('Y-m-d H:i:s',$item['pubDate']);
                $post->post_date_local = $item['pubDate'];                
                $post->post_modified = date('Y-m-d H:i:s');
                $post->post_type = 'tweet';
                $post->post_title = '';
                $post->like_count = $item['statistics']['like'];
                $post->comment_count = $item['statistics']['comment'];                
                if( $post->save() ){
                    $this->logger->info('backup tweet to PostMeta : '. $item['href']);
                    unset($item['content']); //减小体积
                    $postMeta = PostMeta::firstOrNew(['post_id' =>$post->post_id, 'meta_key' => 'tweet_info']);
                    $postMeta->meta_value = maybe_serialize($item);
                    $postMeta->save();
                }                                                               

            }

            
            $argsNext = build_query( ['userId'=>$userId, 'pageToken'=>$tweetsArr['result']['nextPageToken'],'forceUpdate'=>$forceUpdate]);            
            $this->logger->info('start next page with args: '. $argsNext);
            return $this->command([$argsNext]); //注意参数取的是$args[0] ，需要传入数组并且第一个元素是查询参数


        }catch (\Exception $e) {
            
            $msg = $e->getMessage();
            $this->logError($msg);     
        }

    }

}