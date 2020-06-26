<?php
namespace App\Helper;

trait BaseTrait {

    protected $c;
    protected $container;

    public function setContainer(\Slim\Container $c){
        $this->c = $c;
        $this->container = $this->c;
    }

    public function getContainer() {

      if (! $this->c) {
          throw new \Exception('Container not set');
      }
      return $this->c;
    }
}