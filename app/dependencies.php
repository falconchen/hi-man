<?php
// DIC configuration

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;


$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------

// Twig
$container['view'] = function ($c) {
    $settings = $c->get('settings');
    $view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);

    // Add extensions
    $view->addExtension(new Slim\Views\TwigExtension($c->get('router'), $c->get('request')->getUri()));
    $view->addExtension(new App\Helper\ProjectTwigExtension($c));
    $view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new FalconChen\Slim\Views\TwigExtension\CsrfInputs($c->csrf)); // csrf

    return $view;
};

$container['jsonRender'] = function ($c) {
    $view = new App\Helper\JsonRenderer();

    return $view;
};

$container['jsonRequest'] = function ($c) {
    $jsonRequest = new App\Helper\JsonRequest();

    return $jsonRequest;
};

$container['notAllowedHandler'] = function ($c) {
    return function ($request, $response, $methods) use ($c) {

        $view = new App\Helper\JsonRenderer();
        return $view->render(
            $response,
            405,
            ['error_code' => 'not_allowed', 'error_message' => 'Method must be one of: ' . implode(', ', $methods)]
        );
    };
};

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        $view = new App\Helper\JsonRenderer();

        return $view->render($response, 404, ['error_code' => 'not_found', 'error_message' => 'Not Found']);
    };
};

$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {

        $settings = $c->settings;
        $view = new App\Helper\JsonRenderer();

        $errorCode = 500;
        if (is_numeric($exception->getCode()) && $exception->getCode() > 300 && $exception->getCode() < 600) {
            $errorCode = $exception->getCode();
        }

        if ($settings['displayErrorDetails'] == true) {
            $data = [
                'error_code' => $errorCode,
                'error_message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        } else {
            $data = [
                'error_code' => $errorCode,
                'error_message' => $exception->getMessage(),
            ];
        }

        return $view->render($response, $errorCode, $data);
    };
};

$container['csrf'] = function ($c) {
    $guard = new \Slim\Csrf\Guard();
    $guard->setFailureCallable(function ($request, $response, $next) {
        $request = $request->withAttribute("csrf_status", false);
        return $next($request, $response);
    });
    return $guard;
};

// Flash messages
$container['flash'] = function ($c) {
    return new \Slim\Flash\Messages;
};

// database
use Illuminate\Database\Capsule\Manager as Capsule;

$setting = include 'settings.php';
$capsule = new Capsule;
$capsule->addConnection($setting['settings']['database']);
$capsule->setAsGlobal();
// 注册分页类
// Capsule::setPaginator(function () use ($app, $c) {
//     $settings = $c->get('settings');
//     return new App\Helper\Paginator($app->request, 4);
// });
$capsule->bootEloquent();

// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------

// monolog
$container['logger'] = function ($c) {
    //$settings = $c->settings;
    $settings = $c->get('settings');
    $logger = new \Monolog\Logger($settings['logger']['name']);
    $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
    $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['logger']['path'], \Monolog\Logger::DEBUG));
    return $logger;
};

$container['hash'] = function ($c) {
    return new App\Helper\Hash($c->get('app'));
};

//session
$container['session'] = function ($c) {
    return new App\Helper\Session;
};

// -----------------------------------------------------------------------------
// Action factories
// -----------------------------------------------------------------------------

// $container['App\Action\HomeAction'] = function ($c) use ($app) {

//     return new App\Action\HomeAction($c->get('jsonRequest'),$c->get('view'), $c->get('logger'),$c->get('hash'),$c->get('auth'));
// };

// $container['App\Action\Admin'] = function ($c) {
//     return new App\Action\Admin($c->get('view'), $c->get('logger'), $c->get('session'));
// };

//mailer
$container['mailer'] = function ($c) {

    $settings = $c->get('settings');
    $settings = $settings['mailer'];
    $mailer = new PHPMailer();
    $mailer->IsSMTP(); //设置使用SMTP服务器发送
    $mailer->SMTPAuth = true; //开启SMTP认证
    $mailer->Host = $settings['Host']; //设置 SMTP 服务器,自己注册邮箱服务器地址
    $mailer->Username = $settings['Username']; //发信人的邮箱用户名
    $mailer->Password = $settings['Password']; //发信人的邮箱密码
    $mailer->Port = $settings['Port'];
    /*内容信息*/
    $mailer->IsHTML($settings['isHTML']); //指定邮件内容格式为：html
    $mailer->CharSet = $settings['CharSet']; //编码
    $mailer->From = $settings['From']; //发件人完整的邮箱名称
    $mailer->FromName = $settings['FromName']; //发信人署名
    //$mailer->SMTPSecure = $settings['SMTPSecure'];

    return $mailer;
};

$container['guzzle'] = function ($c) {
    $settings = $c->get('settings');
    $client = new GuzzleHttp\Client($settings['guzzle']);
    return $client;
};

$container['translator'] = function ($c) use ($setting) {
    $langArr = $setting['settings']['language'];
    $translator = new Translator($langArr['locale']);
    $translator->addLoader('yaml', new YamlFileLoader());
    $path = sprintf($langArr['dir'] . '/messages.%s.yaml', $langArr['locale']);
    $translator->addResource('yaml', $path, $langArr['locale']);
    return $translator;
};
