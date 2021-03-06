<?php

namespace App\Helper;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Helper\Git;
use App\Model\MediaMap;
use MediaMap as GlobalMediaMap;
use App\Model\User;

class ProjectTwigExtension extends AbstractExtension implements GlobalsInterface
{
    private $container;
    private $uri;

    public static $units = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    public function __construct($c)
    {
        $this->container = $c;
        $this->uri = $c->get('request')->getUri();
        $this->translator = $c->translator;

    }
    public function getTests(){
        return [
            new \Twig_SimpleTest('array', function ($value) {
                return is_array($value);
            })
        ];
    }
    //注入全局变量
    public function getGlobals()
    {
        return array(
            'container' => $this->container,
            'SERVER' => $_SERVER,
            'SESSIOIN'   => $_SESSION,
            'REQUEST'=>$_REQUEST,
            'GET'=>$_GET,
            'POST'=>$_POST,
            't'=>$this->container->translator,
            'c'=>$this->container,
        );
    }

    public function getFilters()
    {
        return [
            new TwigFilter('truncate', [$this, 'twig_truncate_filter'], ['needs_environment' => true]),
            new TwigFilter('wordwrap', [$this, 'twig_wordwrap_filter'], ['needs_environment' => true]),
            new TwigFilter('flash_fmt', [$this, 'flash_fmt'], ['is_safe' => ['html'],]),
            new TwigFilter('time_diff', [$this, 'diff'], ['needs_environment' => true]),

            new TwigFilter('ucfirst', 'twig_capitalize_string_filter', ['needs_environment' => true]),
            
            new TwigFilter('makeLinks','makeLinks',['is_safe' => ['html'],]),
            new TwigFilter('replaceTweetTopic','replaceTweetTopic',['is_safe' => ['html'],]),
            new TwigFilter('imgMap', [$this,'imgMap'], ['needs_environment' => true]),

            new TwigFilter('pageTranslateUrl',[$this,'pageTranslateUrl'],['is_safe' => ['html'],]),

            new TwigFilter('maybeCDN',[$this,'maybeCDN'],['needs_environment' => true]),

        ];
    }

    //demo
    public function getFunctions()
    {
        return [
            new TwigFunction('red', [$this, 'red'], ['is_safe' => ['html'],]),
            new TwigFunction('checked', [$this, 'checked'], ['is_safe' => ['html'],]),
            new TwigFunction('is_null', [$this, 'is_null']),
            new TwigFunction('static_url', [$this, 'staticUrl']),
            new TwigFunction('git_latest', [$this, 'gitLatest']),

            new TwigFunction('get_user_by_id', [$this, 'getUserByID']),
            new TwigFunction('get_username_by_id', [$this, 'getUserNameByID']),
            new TwigFunction('trim_http', [$this, 'trimHttp']),



        ];
    }


    function red($string)
    {

        return "<div class='w3-red'>" . $string . '</div>';
    }

    function checked($val1, $val2)
    {
        return ($val1 === $val2) ? ' checked="checked" ' : '';
    }

    function is_null($val)
    {
        return is_null($val);
    }
    /**
     * @param $value
     * @param string $type msg/type
     * @return string
     */
    function flash_fmt($value, $type = 'msg')
    {

        if ($type == 'msg') {
            return trim(preg_replace('#\[.*\]#iUs', '', $value));
        }


        $error_type = 'info';
        if (preg_match('#\[(.*)\]#iUs', $value, $match)) {
            $error_type = trim($match[1]);
        }
        if ($type == 'type') {
            return $error_type;
        }


        if ($type == 'class') {
            //
            //            {{ "[warning] Lorem ipsum dolor sit amet, consectetur adipiscing"|flash_fmt('msg') }}
            //            {{ "[warning] Lorem ipsum dolor sit amet, consectetur adipiscing"|flash_fmt('class') }}


            switch ($error_type) {
                case 'success':
                    return 'w3-white w3-border-green w3-leftbar';
                case 'error':
                    return 'w3-white w3-border-red w3-leftbar';
                case 'warning':
                    return 'w3-white w3-border-yellow w3-leftbar';
                case 'debug':
                    return 'w3-border-indigo w3-leftbar';
                case 'info':
                    return 'w3-white w3-leftbar w3-border-blue';
            }
        }
    }



    //参考：https://twig-extensions.readthedocs.io/en/latest/text.html
    function twig_truncate_filter(Environment $env, $value, $length = 30, $preserve = false, $separator = '...')
    {

        /**另一种但并不在服务器奏效
         * setlocale(LC_ALL, "zh_CN.UTF-8");
        
        
        if ( str_word_count( $value,0 ) > $length) {
       
            $words = str_word_count($value, 2);
            
            $pos = array_keys($words);   
            $sub_pos = $pos[$length];
            
            
            $value = substr($value, 0, $sub_pos) . $separator;
            
        }
        

        return $value;

        或者安装intl扩展
         * 
         */
        if (mb_strlen($value, $env->getCharset()) > $length) {
            if ($preserve) {
                // If breakpoint is on the last word, return the value without separator.
                if (false === ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
                    return $value;
                }
                $length = $breakpoint;
            }
            return rtrim(mb_substr($value, 0, $length, $env->getCharset())) . $separator;
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

    /**
     * Filters for converting dates to a time ago string like Facebook and Twitter has.
     *
     * @param string|DateTime $date a string or DateTime object to convert
     * @param string|DateTime $now  A string or DateTime object to compare with. If none given, the current time will be used.
     *
     * @return string the converted time
     */
    public function diff(Environment $env, $date, $now = null)
    {
        // Convert both dates to DateTime instances.
        $date = twig_date_converter($env, $date);
        $now = twig_date_converter($env, $now);
        // Get the difference between the two DateTime objects.
        
        $diffFromNow = $now->diff($date);
        

        if( 
            $diffFromNow->y == 0 &&
            $diffFromNow->m == 0 &&
            $diffFromNow->d == 0 &&
            $diffFromNow->h < 4 
        ) {

            foreach (self::$units as $attribute => $unit) {
                $count = $diffFromNow->$attribute;
                if (0 !== $count) {
                    return $this->getPluralizedInterval($count, $diffFromNow->invert, $unit);
                }
            }

            
        }else{

            $corExt = $env->getExtension(\Twig\Extension\CoreExtension::class);
            $date0clock = new \DateTime($date->format('Y-m-d'),$corExt->getTimeZone());
            $diffFrom0 = $date0clock->diff($now);//从0点到现在的diff
            //var_dump($diffFrom0);
            //@todo 
            //先硬编码时间格式
            $timeFormat = 'H:i';
            
            if( $diffFrom0->y == 0 && $diffFrom0->m == 0 && $diffFrom0->d <= 2 ) {

                return $this->translator->transChoice(
                                                'Recent DayFormat',
                                                intval($diffFrom0->d),
                                                ['%timeStr%'=>$date->format($timeFormat)]
                );

            }else{

                $dateFormat = $corExt->getDateFormat()[0];
                if($now->format('Y') == $date->format("Y")){ // @todo,假设为Y-m-d H:is
                    $dateFormat = str_replace( "Y-", "", $dateFormat);                
                }                
                return $date->format($dateFormat);
            }        
            
        }

        return '';
        
        
    }
    private function getPluralizedInterval($count, $invert, $unit)
    {
        if ($this->translator) {
            $id = sprintf('diff.%s.%s', $invert ? 'in' : 'ago', $unit);
            return $this->translator->transChoice($id, $count, ['%count%' => $count], 'date');
        }
        $id = sprintf('diff.%s.%s', $invert ? 'in' : 'ago', $unit);
        if (1 !== $count) {
            $unit .= 's';
        }
        return $invert ? "in $count $unit" : "$count $unit ago";
    }

    public function maybeCDN(Environment $env,$url)
    {

        //$url = '//hi.local.cellmean.com/c/@Falcon/testing';
        
        $arr = parse_url($url);
        if( count($arr) == 1 && isset($arr['path'])) {
            //  $url= '/media/a/b/'
            return $this->staticUrl() . $url;
        }elseif(count($arr) == 2 && isset($arr['host']) && isset($arr['path'])) {
            //$url = '//hi.local.cellmean.com/c/@Falcon/testing';
            return str_replace('//'.$arr['host'] , rtrim($this->staticUrl(),'/'),$url);   
        }else{
            $arr['scheme'] = $arr['scheme'] ?? '';
            $arr['host'] = $arr['host'] ?? '';        
            return str_replace($arr['scheme'].'://'.$arr['host'],rtrim($this->staticUrl(),'/'),$url);
        }
        
        
    }

    public function staticUrl()
    {
        $appSettings = $this->container->get('app');
        if (
            isset($appSettings['cdn'])
            && $appSettings['cdn']['allow']
            && isset($appSettings['cdn']['url'])
        ) {
            return $appSettings['cdn']['url'];
        }

        return $this->baseUrl();
    }

    private function baseUrl()
    {

        if (method_exists($this->uri, 'getBaseUrl')) {
            return $this->uri->getBaseUrl();
        }
    }

    public function gitLatest()
    {
        return Git::latestLog();
    }

    public function imgMap(Environment $env,$originUrl,$width=null,$height=null){
        $img = MediaMap::where('origin_url', $originUrl)->first();
        if( is_null($img) ) {
            return $originUrl;
        }

        $app = $this->container->get('app');
        $settings = $this->container->get('settings');        
        if( $app['cdn']['allow'] == false &&
         !file_exists($settings['media']['image']['dir'] . $img->local_path)) {
             
             return $this->trimHttp($originUrl);
         }
                
        $url = $settings['media']['image']['uri'].$img->local_path;
        
        
        if( $settings['media']['image']['images.weserv.nl'] ){            
            $args = [];
            !is_null($width) && $args['w'] = $width;
            !is_null($height) && $args['h'] = $height;
            $tails = !empty($args) ? '&'.http_build_query($args) :'';
            $url = 'https://images.weserv.nl/?url='.$url .$tails;
        }
        return $url;

    }

    public function getUserByID($uid){
        return $uid>0 ? User::find($uid) :null;
    }

    public function getUserNameByID($uid){
        $user = $this->getUserByID($uid);
        return !is_null($user) ? $user->username : null;
    }

    public function trimHttp($val) {
        return preg_replace('#^http\://#','//',$val);
    }

    public function pageTranslateUrl($url,$to='zh-Hans',$from="en") {
        // return sprintf('https://www.translatetheweb.com/?from=%s&to=%s&dl=%s&ref=trb&a=%s',
        //                 $from,$to,$from,urlencode($url)
        // );
        
        return sprintf('https://translate.google.com/translate?sl=%s&tl=%s&u=%s&prev=search',
                        $from, ($to === 'zh-Hans') ? 'zh-CN' : $to,urlencode($url)
        );
        
    }
}
