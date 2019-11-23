<?php
// Routes
$app->get('/','App\Action\HomeAction:dispatch')->setName('homepage');
$app->get('/login', 'App\Action\HomeAction:login')->setName('login');
$app->get('/logout', 'App\Action\HomeAction:logout')->setName('logout');
$app->get('/register', 'App\Action\HomeAction:register')->setName('register');
$app->get('/dashboard', 'App\Action\HomeAction:dashboard')->setName('dashboard');
$app->post('/login','App\Action\HomeAction:loginPost')->setName('login.post');
//$app->post('/login','App\Action\HomeAction:testJson')->setName('login.post');
$app->post('/register','App\Action\HomeAction:registerPost')->setName('register.post');



$app->get('/verify/{user}/{code}', 'App\Action\HomeAction:verifyEmail')->setName('verify.email');
$app->group('/note/',function(){
	$this->get('','App\Action\NoteAction:index');
	$this->get('new','App\Action\NoteAction:new');

});

$app->get('/oscer','App\Action\OscerAction:index')->setName('oscer');
$app->post('/oscer/bind-oscer','App\Action\OscerAction:bindOscerPost')->setName('bind-oscer.post');

$app->get('/post-admin','App\Action\PostAdminAction:index')->setName('post-admin');
$app->get('/post-admin/new','App\Action\PostAdminAction:new')->setName('post-admin.new');
$app->post('/post-admin/save','App\Action\PostAdminAction:save')->setName('post-admin.save');


$route = App\Model\Route::all();
$app->get('/hi-admin/','App\Action\Admin:index');

foreach ($route as $rt) {

    //$app->get('/'.'groupedit','App\Action\Admin:groupEdit')->setName('groupedit');
	$app->get('/hi-admin/'.$rt->route,$rt->address)->setName($rt->route);
}