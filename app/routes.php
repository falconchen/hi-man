<?php
// Routes
$app->get('/', 'App\Action\HomeAction:dispatch')->setName('u');
$app->get('/login', 'App\Action\HomeAction:login')->setName('login');
$app->get('/logout', 'App\Action\HomeAction:logout')->setName('logout');
$app->get('/register', 'App\Action\HomeAction:register')->setName('register');
$app->get('/dashboard', 'App\Action\HomeAction:dashboard')->setName('dashboard');
$app->post('/login', 'App\Action\HomeAction:loginPost')->setName('login.post');
//$app->post('/login','App\Action\HomeAction:testJson')->setName('login.post');
$app->post('/register', 'App\Action\HomeAction:registerPost')->setName('register.post');

$app->get('/sendmail', 'App\Action\HomeAction:sendmail')->setName('sendmail');
$app->get('/testing', 'App\Action\HomeAction:testing')->setName('testing');


$app->get('/verify/{user}/{code}', 'App\Action\HomeAction:verifyEmail')->setName('verify.email');
$app->group('/note/', function () {
    $this->get('', 'App\Action\NoteAction:index');
    $this->get('new', 'App\Action\NoteAction:new');
});

$app->get('/oscer', 'App\Action\OscerAction:index')->setName('oscer');
$app->post('/oscer/bind-oscer', 'App\Action\OscerAction:bindOscerPost')->setName('bind-oscer.post');

$app->get('/post-admin', 'App\Action\PostAdminAction:index')->setName('post-admin');


$app->get('/post-admin/new', 'App\Action\PostAdminAction:postNew')->setName('post-admin.new');
$app->get('/post-admin/edit/{name}', 'App\Action\PostAdminAction:postEdit')->setName('post-admin.edit');
$app->post('/post-admin/save', 'App\Action\PostAdminAction:save')->setName('post-admin.save');
$app->get('/post-admin/sync-osc', 'App\Action\PostAdminAction:syncOsc')->setName('post-admin.syncOsc');
$app->post('/post-admin/save-preivew', 'App\Action\PostAdminAction:savePreview')->setName('post-admin.savePreview');

$app->group(
    '/p',
    function () {

        $this->get('/sync-osc', 'App\Action\PostAction:syncOsc')->setName('post.syncOsc');
        $this->get('/{name}', 'App\Action\PostAction:index')->setName('post');
    }
);

$app->get('/search/','App\Action\SearchAction:index')->setName('search');

$app->group(
    '/u',
    function () {

        $this->get('/me/', 'App\Action\UserAction:index')->setName('myspace');
        $this->get('/{uid}/', 'App\Action\UserAction:index')->setName('user');
        $this->get('/{uid}/{postType}/', 'App\Action\UserAction:index')->setName('user.postType');
    }
);


$app->group(
    '/task',
    function () {
        $this->get('/list', 'App\Action\TaskAction:list')->setName('task.list');
        $this->get('/producer', 'App\Action\TaskAction:producer')->setName('task.producer');
        $this->get('/worker', 'App\Action\TaskAction:worker')->setName('task.worker');
    }
);


$route = App\Model\Route::all();
$app->get('/hi-admin/', 'App\Action\Admin:index');

foreach ($route as $rt) {

    //$app->get('/'.'groupedit','App\Action\Admin:groupEdit')->setName('groupedit');

    $app->get('/hi-admin/' . $rt->route, $rt->address)->setName($rt->route);
}
