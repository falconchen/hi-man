<?php
return [
    'app' => [
        'url' => 'http://hi.dev',
        'hash' => [
            'algo' => PASSWORD_BCRYPT,
            'cost' => 10
        ],
        'cdn' => [
            'allow' => true,
            'url' => 'http://cdn.hi.dev',
        ],
        'salt'=>'pipi', 
    ],
    'auth' => [
        'session'   => 'user_id',
        'group'     => 'group_id',
        'remember'  => 'user_r'
    ],
    'settings' => [

        'jwt'=>[
            'secret'=>'hicms',
            'timeout'=>'+2 hours', //strtotime 时间格式
        ],
        
        'determineRouteBeforeAppMiddleware'=>true, // 必须，为注入路由名称r全局变量到模板
        'media'=>[
            
            'image'=>[
                'dir'=> __DIR__ . '/../public/media/image',
                'uri'=>'/media/image',
                'images.weserv.nl'=>false, //是否使用 https://images.weserv.nl 图片处理及cdn
            ],
            
        ],
        'email.verify' => false, //关闭用户邮箱验证
        'locked_dir' => __DIR__ . '/../cache/locked', // 判断队列锁定的文件
        'sync' => [
            'email.notify' => true, //同步到osc时是否给管理员发送邮件通知
            'email.notify.skip'=>[],//对特定用户id的文章，不给管理员发邮件通知，比如 hacknews

            'sc.notify' => true, //同步到osc时是否给管理员发送Server酱通知            
            'sc.notify.skip'=>[],//对特定用户id的文章，不给管理员发sc通知，如hacknews
        ],

        'admin' => [ //管理员信息设置
            'sckey' => 'SCUxxxxx', //Server酱密钥 http://sc.ftqq.com/?c=code            

        ],
        'UTC' => 8, //UTC时间偏移量(小时），+/- 整数
        'timezone' => 'PRC', //服务器时区

        'debug'         => true,
        'whoops.editor' => 'sublime',
        // View settings
        'view' => [
            'template_path' => __DIR__ . '/templates',
            'twig' => [
                'cache' => __DIR__ . '/../cache/twig',
                'debug' => true,
                'auto_reload' => true,
            ],
        ],


        // monolog settings
        'logger' => [
            'name' => 'app',
            'path' => __DIR__ . '/../log/app.log',
        ],
        
        // cli monolog settings
        'cli-logger' => [
            'name' => 'cli',
            'path' => __DIR__ . '/../log/cli.log',
        ],

        //error
        'displayErrorDetails' => true,

        //database
        'database' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'database'  => 'slim_project',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],

        'mailer' => [

            'Host' => 'smtp.host',
            'SMTPAuth' => true,
            'Username' => 'mail.username',
            'Password' => 'mail.password',
            'SMTPSecure' => 'tls',
            'CharSet' => 'UTF-8',
            'Port' => '465',
            'From' => 'your@mailbox',
            'FromName' => 'Hi-cms',
            'isHTML' => true,
        ],


        'guzzle' => [
            'allow_redirects' => true,
            'connect_timeout' => 10,
            'read_timeout' => 20,
            'timeout' => 40,
            'cookies' => true,
            //'proxy' => 'http://127.0.0.1:8123',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36 DongDangClub', //
            ],
        ],


        'language' => [
            'html' => 'zh-CN',
            'locale' => 'zh_CN',
            'dir' => __DIR__ . '/../langs',
        ],
        'deepi.sogou'=>[ //搜狗机器翻译
            'url'=>'https://fanyi.sogou.com/reventondc/api/sogouTranslate',
            'pid'=>'xxxxxxxxxx',
            'key'=>'xxxxxxxxxx',
            'salt'=>'xxxxxxxxx',
        ],
        'osc'=>[
            'cookie_keep_alive_days'=>30, //osc cookie有效时间天数
        ],

    ],
    

    'commands' => [       
        /**
         * php public/index.php BackupDongDan "userId=12&pageToken=DBA816934CD0AA59&forceUpdate=0"
         * forceUpdate 强制更新所有动弹，否则只更新未入库的动弹
         */
        'BackupDongDan' => App\Task\BackupDongDanTask::class, 
        /**
         * 完整参数
         * php public/index.php BackupDongDanComments "userId=12&fromPostId=1234&orderBy=post_date&order=desc&take=10"
         * 特定动弹id
         * php public/index.php BackupDongDanComments "tweetId=123456"
         */
        'BackupDongDanComments'=>App\Task\BackupDongDanCommentsTask::class,      
         /**
         * 完整参数
         * php public/index.php BackupDongDanImages "userId=12&fromPostId=1234&orderBy=post_date&order=desc&take=10"
         * 特定动弹id
         * php public/index.php BackupDongDanImages "tweetId=123456"
         */
        'BackupDongDanImages'=>App\Task\BackupDongDanImagesTask::class,
        /**
         * 更新旧osc静态文件服务器50x50的头像为200x200的，部分404
         */
        'UpdateDongDanOldImages'=>App\Task\UpdateDongDanOldImagesTask::class,
        /**
         * 发布hackerNews
         * 
         */
        'PubHackerNews'=>App\Task\PubHackerNewsTask::class,
    ],



    
];
