<?php
namespace App\Helper;
class ProjectTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    private $container;
    public function __construct($c)
    {
        $this->container = $c;
    }
    //注入全局变量
    public function getGlobals()
    {
        return array(      
            'container'=>$this->container ,      
            'server'=>$_SERVER,
            'session'   => $_SESSION,
        ) ;
    }

}