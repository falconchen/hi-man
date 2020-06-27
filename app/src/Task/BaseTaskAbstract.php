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
abstract class BaseTaskAbstract {

     use \App\Helper\HelperTrait;      


     /** @var ContainerInterface */
     protected $container;
     protected $settings;
     protected $startTime;
     protected $logger;

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
          //$this->container = $container;
          $this->setContainer($container);
          $this->settings = $this->container->get('settings');
          $this->logger = $this->container->get('logger');     
          $this->startTime = time();
          $this->logger->info("=== Running Task :". $this->getShortName());     
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

        protected function initInputs($args) {

            $inputs = [];
            if(isset($args[0])) {
                parse_str($args[0],$inputs);
            }
            return $inputs;
        }
        abstract public function command($args) ;

}