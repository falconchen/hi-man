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
    ],
    'auth' => [
        'session'   => 'user_id',
        'group'     => 'group_id',
        'remember'  => 'user_r'
    ],
    'settings' => [
        'email.verify' => false, //关闭用户邮箱验证
        'locked_dir' => __DIR__ . '/../cache/locked', // 判断队列锁定的文件
        'sync' => [
            'email.notify' => false, //同步到osc时是否发送用户邮件通知
        ],

        'admin' => [ //管理员信息设置
            'sckey' => 'SCUxxxxx', //Server酱密钥 http://sc.ftqq.com/?c=code            

        ],

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


    ],
];
