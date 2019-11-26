<?php
return [
    'app' => [
            'url' =>  'http://hi.local.cellmean.com',
            'hash' => [
                'algo' => PASSWORD_BCRYPT,
                'cost' => 10
            ]
    ],
    'auth' => [
        'session'   => 'user_id',
        'group'     => 'group_id',
        'remember'  => 'user_r'
    ],
    
    'settings' => [

        'UTC'=>8, //UTC时间偏移量(小时），+/- 整数
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

        //error
        'displayErrorDetails' => true,

        //database
        'database' => [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'database'  => 'hi_man',
            'username'  => 'dev',
            'password'  => 'dev',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ],
        
        'mailer'=>[

            'Host'=>'smtp.mxhichina.com',
            'SMTPAuth'=>true,
            'Username'=>'me@cellmean.com',
            'Password'=>'7340985@gdufs',
            'SMTPSecure'=>'tls',
            'CharSet'=>'UTF-8',
            'Port'=>'465',
            'From'=>'me@cellmean.com',
            'FromName'=>'Me',
            'isHTML'=>true,
        ],


        'guzzle'=>[
            'allow_redirects' => true,
            'read_timeout' => 10,
            'cookies' => true,
            //'proxy' => 'http://127.0.0.1:8123',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36 DongDangClub',//
            ],
        ]

    ],
];
