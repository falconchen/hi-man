<?php
namespace App\Helper;

use Illuminate\Database\Eloquent\Builder;
use TH\Lock\FileLock;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use App\Model\User;


trait HelperTrait {


    use BaseTrait;
        

    /**
     * print SQL
     *
     * @param Builder $builder
     * @return string
     */
    public function getSQL(Builder $builder) {
        $sql = $builder->toSql();
        foreach ( $builder->getBindings() as $binding ) {
          $value = is_numeric($binding) ? $binding : "'".$binding."'";
          $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        return $sql;
    }
    

    protected function addPannelMessage($content, $status = "default", $title = NULL)
    {

        $this->c->flash->addMessage(
            'pannel',
            json_encode(
                ["title" => $title, "body" => $content, "status" => $status]
            )
        );
    }
    protected function getPannelMessage()
    {

        $raw_message = $this->c->flash->getMessage('pannel');
        if (is_array($raw_message) && !empty($raw_message[0])) {
            return json_decode($raw_message[0]);
        }
        return null;
    }

    protected function scNofify($title, $description = null)
    {

        if (isset($this->c->settings['admin']) && isset($this->c->settings['admin']['sckey'])) {
            $sckey = $this->c->settings['admin']['sckey'];
            $scUrl = 'https://sc.ftqq.com/' . $sckey . '.send';
            $description = is_null($description) ? $title : $description;
            $scResponse = $this->c->guzzle->request('POST', $scUrl, [
                'form_params' => [
                    'text' => str_replace(' ', '_', $title),
                    'desp' => $description,
                ],
            ]);
            $body = (string) $scResponse->getBody();
            $jsonArr = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(json_last_error_msg(), json_last_error());
            }
            if (isset($jsonArr['errmsg']) && $jsonArr['errmsg'] == 'success') {
                return true;
            }
            return false;
        } else {
            throw new \Exception('admin sckey Not set');
        }
    }

    //utc timestamp 转当地
    protected function localTimestamp($utcTimestamp = null)
    {
        $utcTimestamp = is_null($utcTimestamp) ? time() : $utcTimestamp;
        return $utcTimestamp + $this->c->get('settings')['UTC'] * 3600;
    }

    //当地 timestamp 转UTC
    protected function utcTimestamp($localTimestamp)
    {
        return $localTimestamp - $this->c->get('settings')['UTC'] * 3600;
    }
    //utc时间格式转到当地时间
    protected function dateTolocal($format, $dateStr)
    {
        return date($format, $this->localTimestamp(strtotime($dateStr)));
    }

    //本地时间转换到utc

    protected function dateToUtc($format, $dateStr)
    {
        return date($format, (strtotime($dateStr) - $this->c->get('settings')['UTC'] * 3600));
    }


    //创建文件锁
    protected function fileLock($lockName, $removeOnRelease = true)
    {
        return  new FileLock(
            $this->c->settings['locked_dir'] . '/' . $lockName . '.lock',
            FileLock::EXCLUSIVE,
            FileLock::NON_BLOCKING,
            $removeOnRelease,
            $this->logger
        );
    }

    protected function sogouTrans($text) {

        $transApiArr =  $this->c->settings['deepi.sogou'];

        $response = $this->c->guzzle->request('POST',$transApiArr['url'],[
            'form_params' => [
                'q' => $text,
                'from' => 'en',
                'to' => 'zh-CHS',
                'pid'=>$transApiArr['pid'],
                'salt'=>$transApiArr['salt'],
                //签名md5(pid+q+salt+用户密钥)
                'sign'=>md5($transApiArr['pid'].$text.$transApiArr['salt'].$transApiArr['key']),
                'dict'=>false,

            ]
        ]);
        
        $body = (string) $response->getBody();
        return json_decode($body,true)['translation'];

    }

    /**
     * 并发请求翻译api
     *
     * @param array $textArr
     * @param integer $concurrency
     * @return array
     */
    protected function sogouTransArray(array $textArr, $concurrency=10) {

        $client = $this->c->guzzle;
        $transApiArr =  $this->c->settings['deepi.sogou'];
        $this->textTranArr = [];

        $requests = function () use ($client, $textArr,$transApiArr ){

            foreach($textArr as $text) {

                yield function() use ($client, $text,$transApiArr ){
                    return $client->postAsync($transApiArr['url'],[
                        'form_params' => [
                            'q' => $text,
                            'from' => 'en',
                            'to' => 'zh-CHS',
                            'pid'=>$transApiArr['pid'],
                            'salt'=>$transApiArr['salt'],
                            //签名md5(pid+q+salt+用户密钥)
                            'sign'=>md5($transApiArr['pid'].$text.$transApiArr['salt'].$transApiArr['key']),
                            'dict'=>false,
            
                        ]
                    ]);
                };
                
            }
        };


        $pool = new Pool($client, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) {
                // this is delivered each successful response
                
                $body = (string) $response->getBody();                
                $this->textTranArr[$index] = json_decode($body,true)['translation'];
                
            },
            'rejected' => function ($reason, $index) {
                // this is delivered each failed request                                
                $textTranArr[$index] = null;                
            },
        ]);        
        $promise = $pool->promise();
        
        $promise->wait();

        return $this->textTranArr;
    }
}