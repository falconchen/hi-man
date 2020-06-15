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



//abstraclass BackupDongDanTask {
abstract class BackupDongDanAbstract {

     use \App\Helper\ActionHelper;      

      /** @var ContainerInterface */
      protected $container;
      protected $startTime;

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
          $this->startTime = time();     
      }

      /**
       * get current class ShortName
       *
       * @return string
       */
      protected function getShortName(){
        return (new \ReflectionClass($this))->getShortName();
      }

      /**
       * setup GuzzleHttp Client base on userId
       *
       * @param [int] $userId
       * @return Client|false
       */
      protected function setupClient($userId){

        $cookieField = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_cookie')->first();
        if(is_null($cookieField)) {
            $this->logError( "Cookie not exists for user_id ".$userId );     
            return false;    
        }
        
        $cookies = unserialize($cookieField->meta_value);
        $guzzleConf = $this->settings['guzzle'];
        $guzzleConf['cookies'] = $cookies;        
        $guzzleConf['headers']['Referer'] = 'https://www.oschina.net/tweets';

        
        return new Client($guzzleConf);  

      }

      protected function getOSCUserInfo($userId) {

        $oscUserInfo = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_userinfo')->first();
        
        if ( is_null($oscUserInfo )) {            
            $this->logError( "Osc User Info failed for user_id ".$userId );    
            return false;      
        }
        return unserialize($oscUserInfo->meta_value) ;  

      }
       /**
        * 
        *本地时间转换到utc
        * @param [string] $format
        * @param [string] $dateStr
        * @return string
        */
        protected function dateToUtc($format, $dateStr)
        {
            return date($format, (strtotime($dateStr) - $this->settings['UTC'] * 3600));
        }


        /**
         * Log errror 
         *
         * @param [type] $msg
         * @return void
         */
        protected function logError($msg)
        {

            $this->logger->error($msg);
            throw new RuntimeException($msg);
        }


        abstract public function command($args) ;

}