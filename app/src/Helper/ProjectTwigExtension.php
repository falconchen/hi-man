<?php
namespace App\Helper;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;



class ProjectTwigExtension extends AbstractExtension implements GlobalsInterface
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

    public function getFilters()
    {
        return [
            new TwigFilter('truncate', [$this,'twig_truncate_filter'], ['needs_environment' => true]),
            new TwigFilter('wordwrap', [$this,'twig_wordwrap_filter'], ['needs_environment' => true]),
            new TwigFilter('flash_fmt', [$this, 'flash_fmt'], [ 'is_safe' => ['html'], ])
        ];
    }

    //demo
    public function getFunctions(){
        return [
            new TwigFunction('red', [$this, 'red'], [ 'is_safe' => ['html'], ])
        ];
    }


    function red($string){

        return "<div class='w3-red'>" .$string .'</div>';
    }


    /**
     * @param $value
     * @param string $type msg/type
     * @return string
     */
    function flash_fmt($value, $type='msg'){

        if($type == 'msg') {
            return trim(preg_replace('#\[.*\]#iUs','',$value));
        }


        $error_type = 'info';
        if(preg_match('#\[(.*)\]#iUs',$value,$match)){
            $error_type = trim($match[1]);
        }
        if($type == 'type'){
            return $error_type;
        }


        if($type == 'class'){
//
//            {{ "[warning] Lorem ipsum dolor sit amet, consectetur adipiscing"|flash_fmt('msg') }}
//            {{ "[warning] Lorem ipsum dolor sit amet, consectetur adipiscing"|flash_fmt('class') }}


            switch($error_type){
                case 'success':
                    return 'w3-pale-green w3-border-green w3-leftbar';
                case 'error':
                    return 'w3-pale-red w3-border-red w3-leftbar';
                case 'warning':
                    return 'w3-pale-yellow w3-border-yellow w3-leftbar';
                case 'debug':
                    return 'w3-border-indigo w3-leftbar';
                case 'info':
                    return 'w3-pale-blue w3-leftbar w3-border-blue';
            }
        }



    }

    //参考：https://twig-extensions.readthedocs.io/en/latest/text.html
    function twig_truncate_filter(Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {

        if (mb_strlen($value, $env->getCharset()) > $length) {
            if ($preserve) {
                // If breakpoint is on the last word, return the value without separator.
                if (false === ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
                    return $value;
                }
                $length = $breakpoint;
            }
            return rtrim(mb_substr($value, 0, $length, $env->getCharset())).$separator;
        }
        return $value;
    }


    //参考：https://twig-extensions.readthedocs.io/en/latest/text.html
    function twig_wordwrap_filter(Environment $env, $value, $length = 80, $separator = "\n", $preserve = false)
    {
        $sentences = [];
        $previous = mb_regex_encoding();
        mb_regex_encoding($env->getCharset());
        $pieces = mb_split($separator, $value);
        mb_regex_encoding($previous);
        foreach ($pieces as $piece) {
            while (!$preserve && mb_strlen($piece, $env->getCharset()) > $length) {
                $sentences[] = mb_substr($piece, 0, $length, $env->getCharset());
                $piece = mb_substr($piece, $length, 2048, $env->getCharset());
            }
            $sentences[] = $piece;
        }
        return implode($separator, $sentences);
    }



}