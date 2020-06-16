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

class BackupDongDanCommentsTask extends BackupDongDanAbstract 
{

    private $items;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->logger->info("=== Running Task :". $this->getShortName());
    }

    /**
     * php public/index.php BackupDongDanComments "userId=12&fromPostId=1234&orderBy=post_date&order=desc&take=10"
     * php public/index.php BackupDongDanComments "tweetId=123456"
     * 
     * Backup DongDan comments/hot comments/likes
     *      
     * @param array $args
     * @return void
     */
    public function command($args)
    {
        
        
        $this->logger->info("Start backup dongdan comments with args: " . var_export($args,true));

        //使用userId=12&fromPostId=1234&orderBy=post_date&order=desc&take=10&tweetId=123456
        $inputs = $this->initInputs($args);

        $userId = isset($inputs['userId']) ? $inputs['userId'] : 12;
        $fromPostId = isset($inputs['fromPostId']) ? intval($inputs['fromPostId']) : 0;
        $orderBy = isset($inputs['orderBy']) ? $inputs['orderBy'] : 'post_date';
        $order = isset($inputs['order']) ? $inputs['order'] : 'desc';
        $take = isset($inputs['take']) ? intval($inputs['take']) : 0;
        $tweetId = isset($inputs['tweetId']) ? intval($inputs['tweetId']) : 0;


        $postsBuilder = Post::select('post_id', 'post_name', 'post_content')
            ->where('post_type', 'tweet')            
            ->where('post_id', '>=', $fromPostId);
            
        if( $tweetId > 0 ) {
            $postsBuilder->where('post_title',$tweetId);
        }
        $postsBuilder->orderBy($orderBy, $order); 
        if( $take > 0 ){
            $postsBuilder->take($take);
        }
        
        $posts = $postsBuilder->get();


        if ($posts->count() == 0) {
            $this->logger->info('No tweet with Comments needs to backup');
            return;
        }
        $this->logger->info('tweet with comments total: ' . $posts->count());

        $client = $this->setupClient($userId);


        $totalTweets = count($posts);

        foreach ($posts as $k => $post) {


            $this->logger->info(sprintf(
                "backup comments/likes for %s | %s | %s ",
                $post->post_id,
                $post->post_name,
                $post->post_content
            ));

            $tweetId = $post->post_name;


            $urls = [
                //正常评论
                'tweet_comments' => 'https://www.oschina.net/action/apiv2/tweet_comments?pageToken=%s&sourceId=%d',
                //精彩评论
                'tweet_hot_comments' => 'https://www.oschina.net/action/apiv2/tweet_comments?pageToken=%s&order=1&sourceId=%d',
                //点赞数据
                'tweet_likes' => 'https://www.oschina.net/action/apiv2/tweet_likes?pageToken=%s&sourceId=%d',
            ];

            foreach ($urls as $key => $urlTemplate) {

                $items = $this->fetchItems($client, $urlTemplate, $tweetId);
                if ($items === false) {
                    $this->logger->info('skip fetching error for tweetId:%d', $tweetId);
                    continue;
                }
                $total = count($items);
                if ($total > 0) {
                    $info = sprintf(
                        'store data for %s, total:%d , tweetId:%d ,postId:%d',
                        $key,
                        $total,
                        $tweetId,
                        $post->post_id
                    );
                    $this->logger->info($info);
                    $postMeta = PostMeta::firstOrNew(['post_id' => $post->post_id, 'meta_key' => $key]);
                    $postMeta->meta_value = maybe_serialize($items);
                    $postMeta->save();
                }
            }

            $this->logger->info(sprintf('Progress: %d/%d - %.2f%%', $k+1, $totalTweets, ($k+1) / $totalTweets * 100));
        }
        
        $this->logger->info(sprintf('finish comments/likes backups, time consumed: %d (s)', (time() - $this->startTime)));
        return ;
    }


    protected function fetchItems(\GuzzleHttp\Client $client,  $urlTemplate = "", $tweetId, $pageToken = '')
    {

        try {

            $this->items = ($pageToken == '') ? [] : $this->items;

            $url = sprintf($urlTemplate, $pageToken, $tweetId);
            $this->logger->info('crawling url : ' . $url);
            $oscResponse = $client->request('GET', $url);
            $body = (string) $oscResponse->getBody();
            $dataArr = json_decode($body, true);

            if ($dataArr == null) {
                throw new \Exception('json parse error:' .  json_last_error());
            }
            if ($dataArr['message'] !== 'SUCCESS' || !isset($dataArr['result'])) {
                throw new \Exception('data message is ' . $dataArr['message']);
            }

            if (isset($dataArr['result']['items']) && count($dataArr['result']['items']) === 0) {

                $this->logger->info('End of fetching data for ' . $url);
                return $this->items;
            }


            foreach ($dataArr['result']['items'] as $item) {
                $this->items[] = $item;
            }
            if ($dataArr['result']['responseCount'] < $dataArr['result']['requestCount']) {

                return $this->items;
            }

            $this->logger->info('start next page with pageToken: ' . $dataArr['result']['nextPageToken']);
            return $this->fetchItems($client, $urlTemplate, $tweetId, $dataArr['result']['nextPageToken']);
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            $this->logError($msg);
            return false;
        }
    }






}
