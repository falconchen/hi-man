<?php
return [
    'app' => [
            'url' => 'http://slim.dev',
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
    ],
];
