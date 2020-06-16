<?php

namespace App\Task;


use App\Model\MediaMap;
use GuzzleHttp\Pool;


class UpdateDongDanOldImagesTask extends BackupDongDanImagesTask
{

    protected $images = [];
    private $uris =[];
    private $count = 0;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->logger->info("=== Running Task :" . $this->getShortName());
    }

    public function command($args)
    {
        $this->logger->info("Start update old images with args: " . var_export($args, true));

        $inputs = $this->initInputs($args);

        $userId = isset($inputs['userId']) ? $inputs['userId'] : 12;

        $client = $this->setupClient($userId);

        $this->images = MediaMap::where(
            function($query) {
                $query->where('origin_url', 'like', '%//static.oschina.net/uploads/%\_50.jpg%')
                ->orWhere('origin_url','like','%//static.oschina.net/uploads/%\_50.jpeg%');
            })->where('tags','not like','%tweet_portrait_200x200%')->get();

            //echo $this->getSQL($this->images);exit;
        
        if( count($this->images) == 0 ){
            $this->logger->info('No image need to update');
            return;
        }

        try {

            $requests = function () use ($client) {

                foreach ($this->images as $key => $image) {
                    
                    $uri = str_replace(['_50.jpeg','_50.jpg'],['_200.jpeg','_200.jpg'],$image->origin_url);
                    $this->uris[$key] = $uri;
                    $this->logger->info(sprintf('index:%d,request:%s,origin: %s',$key,$uri,$image->origin_url));
                    yield function() use ($client, $uri) {
                        return $client->getAsync($uri);
                    };
                }
            };

            $pool = new Pool($client, $requests(), [
                'concurrency' => 10,
                'fulfilled'   => function ($response, $index) {

                    $this->logger->info(
                        sprintf('Request url:%s,status code:%d ',$this->uris[$index],$response->getStatusCode() )
                    );
                                    
                    if( $response->getStatusCode() !== 200 ){
                        $this->logError(
                                sprintf('Failed to Request url:%s,status code:%d ',$this->uris[$index],$response->getStatusCode() )
                        );
                    }

                    
                    $localPathDB = $this->images[$index]->local_path;
                    $realPath = $this->getRealPath($localPathDB);

                    $dir = dirname($realPath);
                    !is_dir($dir) && mkdir($dir,0755,true);

                    $this->logger->info(sprintf('media_id:%d , write file path:%s',$this->images[$index]->media_id,$realPath));

                    $content = $response->getBody()->getContents();
                    
                    if( $content  && strlen($content) > 0 ) {
                        if(!file_put_contents($realPath, $content )){
                            $this->logger->error('error in writing file, path:'.$realPath);
                        }                
                    }else{
                        $this->logger->error('not content for '. $this->uris[$index]);
                    }
                                                                                                                
                    $this->logger->info("current index: " .$index);
                    $this->updateTags($this->images[$index]);
                    $this->checkAndEnd();
                        
                },
                'rejected' => function ($reason, $index){   
                           
                    $this->logger->error(
                        sprintf('Failed to Request url:%s',$this->uris[$index])
                    );          
                    $this->logger->error("rejected reason: " . $reason->getMessage() );
                    $this->updateTags($this->images[$index]);
                    $this->checkAndEnd();
                },

            ]);

            // 开始发送请求
            $promise = $pool->promise();
            $promise->wait();

        }catch (\Exception $e) {

            $msg = $e->getMessage();
            //$this->logError($msg);            
        }

    }

    protected function updateTags(MediaMap $imageObj) 
    {
        $this->logger->info('update tags for media_id '.$imageObj->media_id);
        $imageObj->tags = rtrim($imageObj->tags,',') . ',tweet_portrait_200x200';
        $imageObj->save();
    }


    protected function checkAndEnd() 
    {
        $this->count++;
        $total = count($this->images);
        $this->logger->info(sprintf('Progress: %d/%d - %.2f%%', $this->count, $total, ($this->count) / $total * 100));

        if($this->count == $total) {
           $this->logger->info('End of updated images');
        }
    }


}
