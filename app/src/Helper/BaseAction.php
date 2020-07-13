<?php

namespace App\Helper;

use TH\Lock\FileLock;
use App\Model\User;

class BaseAction
{

    use HelperTrait;
    
    
    protected $container;
    protected $c;//别名
    protected $route;
    protected $user = null;
    protected $userId = 0;
    protected $allowPostTypes = ['post','tweet','gallery'];

    protected function setupUser()
    {
        if ($this->session->get($this->auth['session'])) {
            $this->userId = $this->session->get($this->auth['session']);
            $this->user = User::where('id', $this->userId)->first();
        }
    }

    //Constructor
    public function __construct(\Slim\Container $c)
    {
        $this->jsonRequest = new JsonRequest();
        $this->JsonRender = new JsonRenderer();
        $this->setContainer($c);
        $this->route = $this->c->get('router'); //alias
        
        $this->setupUser();
    }

    public function __get($arg)
    {

        if ($this->c->has($arg)) {
            return $this->c->get($arg);
        }
    }

    public function __call($funcname, $args = [])
    {
        if (!method_exists($this, $funcname) && method_exists($this->c, $funcname)) {
            return call_user_func_array([$this->c, $funcname], $args);
        }
    }

    
}
