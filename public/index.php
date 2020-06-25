<?php

define('HI_ROOT',__DIR__);

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require HI_ROOT . '/../vendor/autoload.php';

//session life time setting
ini_set('session.cookie_lifetime', '99999999');
ini_set('session.gc_maxlifetime', '99999999');

function hiGetSettings($key=null) {
    
    if (PHP_SAPI == 'cli') {
        $settingsFile = HI_ROOT . '/../app/settings.php';
    }else{
        $settingsFile = HI_ROOT . '/../app/settings.' . $_SERVER['HTTP_HOST'] . '.php';;
        $settingsFile = (file_exists($settingsFile)) ? $settingsFile : HI_ROOT . '/../app/settings.php';
    }
        
    $settings = require $settingsFile;
    return ($key===null) ? $settings : $settings[$key];    
}

// Instantiate the app
$settings = hiGetSettings();

session_start();
$app = new \Slim\App($settings);

// Set up dependencies
require __DIR__ . '/../app/dependencies.php';

// Register routes
require __DIR__ . '/../app/routes.php';

// Register middleware
require __DIR__ . '/../app/middleware.php';


// Run!
$app->run();
