<?php
namespace App\Task;

use App\Model\Post;
use App\Model\PostMeta;
use App\Model\MediaMap;
// use Illuminate\Database\Capsule\Manager as DB;
//use Illuminate\Database\Query\Builder as DB;
use Illuminate\Database\Connection as DB;
use Illuminate\Database\Eloquent\Collection;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;


class BackupDongDanImagesTask extends BackupDongDanAbstract{

    protected $images = [];
    protected $forceUpdate;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->logger->info("=== Running Task :". $this->getShortName());
    }

    /**
     * 
     * php public/index.php BackupDongDanImages "userId=12&fromPostId=1234&orderBy=post_date&order=desc&take=10"
     * php public/index.php BackupDongDanImages "tweetId=123456"
     * 
     * Backup DongDan images inlude tweets images/ author portaits
     *
     * @param array $args
     * @return void
     */
    public function command($args)
    {           
        $this->logger->info("Start backup images with args: ". var_export($args,true));

        $inputs = $this->initInputs($args);

        $userId = isset($inputs['userId']) ? $inputs['userId'] : 12;
        $fromPostId = isset($inputs['fromPostId']) ? intval($inputs['fromPostId']) : 0;
        $orderBy = isset($inputs['orderBy']) ? $inputs['orderBy'] : 'post_date';
        $order = isset($inputs['order']) ? $inputs['order'] : 'desc';
        $take = isset($inputs['take']) ? intval($inputs['take']) : 0;
        $tweetId = isset($inputs['tweetId']) ? intval($inputs['tweetId']) : 0;
        $this->forceUpdate = isset($inputs['forceUpdate']) ? boolval($inputs['forceUpdate']) : false;
        
        $client = $this->setupClient($userId);
        $postsBuilder = Post::select(['*'])
            ->where('post_type', 'tweet')            
            ->where('post_id', '>=', $fromPostId);
            
        if( $tweetId > 0 ) {
            $postsBuilder->where('post_title',$tweetId);
        }
        $postsBuilder->orderBy($orderBy, $order); 
        if( $take > 0 ){
            $postsBuilder->take($take);
        }
        
        $tweets = $postsBuilder->get();

        if($tweets->count() > 0 ){

            foreach($tweets as $tweet) {
                $this->logger->info( sprintf("Start backup images for post_id:%d tweet : %s",$tweet->post_id, $tweet->post_content) );                
                $this->backupImage4Tweet($tweet,$client);                
            }    

        }    
        
        
        $this->logger->info(sprintf('finish images backups, time consumed: %d (s)', (time() - $this->startTime)));
        return ;
        
    }

    protected function backupImage4Tweet(Post $tweet ,Client $client) {

        try {

            $this->images = [];
            
            $tweetMetas = $tweet->metas('meta_key','like','tweet_%')->orderBy('meta_id','asc')->get();
            foreach($tweetMetas as $metaItem) {                
                $this->saveOrigin($metaItem,$tweet);
            }
            if( count($this->images) == 0 ) {
                $this->logger->info('no image need to backup in this tweet');
                return ;
            }
            $requests = function () use ($client) {

                foreach ($this->images as $key => $image) {

                    $uri = $image->origin_url;
                    $tmpArr = explode('!/both',$uri);
                    $uri = $tmpArr[0];
                    $this->logger->info(sprintf('index:%d,request:%s,origin: %s',$key,$uri,$image->origin_url));
                    yield function() use ($client, $uri) {
                        return $client->getAsync($uri);
                    };
                }
            };
            
            $pool = new Pool($client, $requests(), [
                'concurrency' => 10,
                'fulfilled'   => function ($response, $index) {

                    if( $response->getStatusCode() !== 200 ){
                        $this->logError(
                                sprintf('Failed Request url:%s,status code:%d ',$this->images[$index]->origin_url,$response->getStatusCode() )
                        );
                    }
                    
                    if(!empty( $response->getHeader('Content-Type') )) {
                        $contentType = $response->getHeader('Content-Type'); 
                        $contentType = $contentType[0];                       
                    }else{
                        $contentType = 'image/jpeg';
                    }

                    
                    if($contentType == 'image/jpeg'){
                        $extName = '.jpg';
                    }else{
                        $tmpArr = explode('/',$contentType);
                        $extName = '.'.$tmpArr[1];
                    }

                    $extName = empty($extName) ? '.jpg' : $extName;
                    $locaPathDB = $this->setLocalPathDB($this->images[$index],$extName);

                    $realPath = $this->getRealPath($locaPathDB);

                    $dir = dirname($realPath);
                    !is_dir($dir) && mkdir($dir,0755,true);

                    $this->logger->info(sprintf('media_id:%d , write file path:%s',$this->images[$index]->media_id,$realPath));

                    $content = $response->getBody()->getContents();
                    if( $content  && strlen($content) > 0 ) {
                        if(!file_put_contents($realPath, $content )){
                            $this->logger->error('error in writing file, path:'.$realPath);
                        }                
                    }else{
                        $this->logger->error('not content get '. $this->images[$index]->origin_url);
                    }
                    
                    $this->images[$index]->content_type = $contentType;
                    $this->images[$index]->local_path = $locaPathDB;
                    $this->images[$index]->save();
                                                            
                    $this->logger->info("current index: " .$index);
                        
                },
                'rejected' => function ($reason, $index){                    
                    
                    $this->logger->error(
                        sprintf('Failed to Request media_id:%d',$this->images[$index]->media_id)
                    );          
                    $this->logger->error("rejected reason: " . $reason->getMessage() );              
                },

            ]);

            // 开始发送请求
            $promise = $pool->promise();
            $promise->wait();

        }catch (\Exception $e) {

            $msg = $e->getMessage();
            $this->logger->error($msg);              
            //$this->logError($msg);            
        }

    
    }

    protected function getRealPath($locaPathDB) {

        return $this->settings['media']['image']['dir'] . $locaPathDB;
        
    }

    protected function saveOrigin($metaItem,Post $tweet)
    {

        
        $metaValue = maybe_unserialize($metaItem->meta_value);
        $this->logger->info('save the images in '. $metaItem->meta_key) ;        
        switch ($metaItem->meta_key) {

            case 'tweet_info':

                if( isset($metaValue['images'] )) {

                    foreach( $metaValue['images'] as $image) {
                        
                        $mediaMap = MediaMap::firstOrNew(['origin_url'=>$image['href']]);
                        if( $this->forceUpdate || !$this->dbPathIsValid($mediaMap->local_path) ) {
                            $mediaMap->title = $image['name'];
                            $mediaMap->post_id = $tweet->post_id;
                            $mediaMap->media_author = $tweet->post_author;
                            $mediaMap->meta_info = maybe_serialize($image);
                            $mediaMap->tags = 'tweet_image';
                            $images['tweet_image'][$image['href']] = $mediaMap;
                            
                            $mediaMap->save();
                            $this->images[] = $mediaMap;
                        }
                        

                        $mediaMapThumb = MediaMap::firstOrNew(['origin_url'=>$image['thumb']]);
                        if( $this->forceUpdate || !$this->dbPathIsValid($mediaMapThumb->local_path) ) {

                            $mediaMapThumb->title = $image['name'];
                            $mediaMapThumb->post_id = $tweet->post_id;
                            $mediaMapThumb->meta_info = maybe_serialize($image);
                            $mediaMapThumb->origin_url = $image['thumb'];
                            $mediaMapThumb->tags = 'tweet_image_thumb';                        

                            $mediaMapThumb->save();
                            $this->images[] = $mediaMapThumb;
                        }
                    }                        
                }
                
                if(isset($metaValue['author']['portrait'])) {

                    $mediaPortrait = $this->savePortaitMedia($metaValue);
                    if($mediaPortrait !== false) {
                        $this->images[] = $mediaPortrait;
                    }                    

                }  

            break;
            
            case 'tweet_likes':                    
            case 'tweet_comments':    
            case 'tweet_hot_comments':
                foreach($metaValue as $v) {

                    $mediaPortrait = $this->savePortaitMedia($v);
                    if($mediaPortrait !== false) {
                        $this->images[] = $mediaPortrait;
                    } 
                    
                }                        
            break;

            default:
                $this->logError('Not allow key: '.$metaItem->meta_key);
                return false;
            break;            

        }

        return true;
    }

    protected function savePortaitMedia($metaValue) {

        $mediaPortrait = MediaMap::firstOrNew(['origin_url'=>$metaValue['author']['portrait']]);

        if( $this->forceUpdate || !$this->dbPathIsValid($mediaPortrait->local_path) ) {
            $mediaPortrait->title = $metaValue['author']['name'];
            $mediaPortrait->tags = 'tweet_portrait';
            $mediaPortrait->save();
            return $mediaPortrait;            
        }
        return false;

    }
    protected function dbPathIsValid($path) {

        return !is_null($path) && file_exists($this->getRealPath($path));
                            
    }

    protected function setLocalPathDB(MediaMap $media,$extensionName) {
        
        $realPath = sprintf("/%d/%d/%s", $media->media_id % 1024, $media->media_id % 512,  $media->media_id . $extensionName);                
        return $realPath;
    }

}