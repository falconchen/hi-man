<?php
namespace App\Task;

use App\Model\Post;
use App\Model\PostMeta;


class BackupDongDanTask extends BackupDongDanAbstract{


    public function __construct($container)
    {
        parent::__construct($container);
        $this->logger->info("=== Running Task :". $this->getShortName());
    }

    /**
     * command
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
                $this->logger->info(sprintf('finish tweets backups, time consumed: %d (s)', (time() - $this->startTime)));
                return true;
                exit;
            }

            foreach($tweetsArr['result']['items'] as $item) {

                $this->logger->info('backup tweet to Post : '. $item['href']);
                $this->logger->info( sprintf("%d: %s", $item['id'], $item['content'] ) );
                $post = Post::firstOrNew(['post_name' => $item['id']]);                
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

}