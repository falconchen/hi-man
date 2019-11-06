<?php
namespace App\Helper;

class BaseAction
{
    protected $c;
    protected $route;

    //Constructor
    public function __construct(\Slim\Container $c)
    {
        $this->c = $c;        
        $this->route = $this->c->get('router'); //alias
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

    protected function addPannelMessage($content,$status = "default",$title=NULL)
    {
        
        $this->c->flash->addMessage('pannel',
            json_encode(
                [ "title"=>$title,"body" => $content, "status" => $status]
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

}
