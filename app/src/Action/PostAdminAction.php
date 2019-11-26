<?php
namespace App\Action;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\User;
use App\Model\Post;
use App\Model\PostMeta;
use App\Model\UserMeta;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helper\JsonRenderer;

use App\Helper\Url;
use GuzzleHttp\Psr7;
use GuzzleHttp\Client; // http://docs.guzzlephp.org/en/stable/index.html
use GuzzleHttp\Exception\ClientException;
use Response as GlobalResponse;
use stdClass;
use Symfony\Component\Config\Definition\Exception\Exception;

use Violin\Violin;

final class PostAdminAction extends \App\Helper\LoggedAction
{


    private $data;


    private function init()
    {
        $userId = $this->userId;
        $this->data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $userId)->where('meta_key','osc_userinfo')->first();
        if( $oscer ){
            $this->data['oscer'] = unserialize($oscer->meta_value);
        }
        $this->data['flash'] = $this->flash->getMessage('flash');        

    }

    public function index(Request $request, Response $response, $args)
    {//list all posts
        self::init();
        
        $posts = Post::where('post_author',$this->userId)->orderBy('post_date','DESC')->get();
        $this->data['posts'] = $posts;
        $this->view->render($response, 'post-admin/index.twig',$this->data);

    }

    public function postNew(Request $request, Response $response, $args)
    {

        self::init();

        if( isset($this->data['oscer']) ) {

            try {
                $blogWriteUrl = $this->data['oscer']['homepage'] .'/blog/write';
                $cookieField= UserMeta::where('user_id', $this->userId)->where('meta_key','osc_cookie')->first();
                $cookies = unserialize($cookieField->meta_value);
                $html = $this->getOscPostOptions($blogWriteUrl,$cookies);
                $this->data['oscOptions'] = $html;
            }catch (ClientException $e) { //40x
                $this->logger->log( Psr7\str($e->getRequest()) );
                $this->logger->log(Psr7\str($e->getResponse()));

            } catch (Exception $e){ //others

            }

        }


        $this->view->render($response, 'post-admin/new.twig',$this->data);

    }

    public function postEdit(Request $request, Response $response, $args)
    {


        $route = $request->getAttribute('route');

        // return NotFound for non existent route
        if (empty($route)) {
            throw new NotFoundException($request, $response);
        }
    
        $name = $route->getName();
        $groups = $route->getGroups();
        $methods = $route->getMethods();
        $arguments = $route->getArguments();
        print($name);
        
        exit;

        self::init();

        
        $postQuery = Post::where('post_name',$args['name']);
        if( $this->user->group_id >= 3) {
            $postQuery = $postQuery->where('post_author',$this->userId);
        }
        $post = $postQuery->first();
        if( is_null( $post ) ){
            $flash = ['[error] 没有找到文章 '];
            $this->data['flash'] = $flash;
            return $this->view->render($response, 'post-admin/new.twig',$this->data);

        }

        $this->data['post'] = $post;
        
        

        if( isset($this->data['oscer']) ) {

            try {
                $blogWriteUrl = $this->data['oscer']['homepage'] .'/blog/write';
                $cookieField= UserMeta::where('user_id', $this->userId)->where('meta_key','osc_cookie')->first();
                $cookies = unserialize($cookieField->meta_value);
                $html = $this->getOscPostOptions($blogWriteUrl,$cookies);
                $this->data['oscOptions'] = $html;
            }catch (ClientException $e) { //40x
                $this->logger->log( Psr7\str($e->getRequest()) );
                $this->logger->log(Psr7\str($e->getResponse()));

            } catch (Exception $e){ //others

            }

        }


        $this->view->render($response, 'post-admin/new.twig',$this->data);

    }

    

    /**
     * 同步到osc
     */
    public function syncOsc(Request $request, Response $response, $args) {
        try{
            $this->doSyncOsc( 19 );
        }catch(Exception $e){
            $this->logger->error($e->getMessage() . "\n". $e->getTraceAsString());
        }
    }

    private function doSyncOsc($postId,$oscSyncOptions=[]) {
        
        //default sync options
        $postArr = array(               
            'id'=>'', //osc的文章id;
            'user_code'=> "i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z",
            'title'=>"Title",
            'content'=>"Content",
            'content_type'=>"4",
            'catalog'=>"0",
            'classification'=>"430381",//其他类型
            'type'=>"1", 
            'origin_url'=>"",
            'privacy'=>"0",
            'deny_comment'=>"0",
            'as_top'=>"0",
            'downloadImg'=>"0",
            'isRecommend'=>"0",
        );


        if(empty($oscSyncOptions)) {
            $syncOptions = PostMeta::where(['post_id'=>$postId,'meta_key'=>'osc_sync_options'])->first();
            if($syncOptions === NULL){
                throw new Exception('No OSC Sync Options');
            }            
            $oscSyncOptions = unserialize($syncOptions->meta_value);            
        }
        
        $postArr = array_merge($postArr,$oscSyncOptions);
        if( !$postArr['classification'] ) {
            throw new Exception('classification empty');
        }
        if( !$postArr['catalog'] ) {
            throw new Exception('catalog empty');
        }
        
        self::init();

        $blogWriteUrl = $this->data['oscer']['homepage'] .'/blog/write';        
        $blogSaveUrl = $this->data['oscer']['homepage'] .'/blog/save';
        
        $cookieField= UserMeta::where('user_id', $this->userId)->where('meta_key','osc_cookie')->first();
        $cookies = unserialize($cookieField->meta_value);

    
        $conf = $this->settings['guzzle'];
        if( !is_null($cookies) ) {
            $conf['cookies'] = $cookies;
        }
        $conf['headers']['Referer'] = $blogWriteUrl;
        
        $client = new Client($conf);        
        //确认分类字段是否存在，获取user_code
        //<input type="hidden" name="user_code" value="i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z">
        
        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string)$oscResponse->getBody();
        
        $dom = new \PHPHtmlParser\Dom;
        $dom->load($body,['whitespaceTextNode' => false]);
        

        //get userCode
        $userCodeNode = $dom->find('input[name="user_code"]');
        if(!count($userCodeNode)) {
            throw new Exception('userCodeNode empty');
        }
        $userCode = $userCodeNode[0]->getAttribute('value');

        //check catalog
        $catalogNode = $dom->find('#catalogDropdown option[value='. $postArr['catalog'] .']');
        if(!count($userCodeNode)) {
            throw new Exception('catalog not exists');
        }
        
        $postDbData = Post::where('post_id',$postId)->first();        
        $postArr['title'] = $postDbData->post_title;
        $postArr['content'] = $postDbData->post_content;
        $postArr['user_code'] = $userCode;

        

        $oscResponse = $client->request('POST', $blogSaveUrl,[
            'form_params' => $postArr,
        ]);
        $body = (string)$oscResponse->getBody();
        
        $jData = json_decode($body);
        if(json_last_error() !== JSON_ERROR_NONE){
            throw new Exception(json_last_error_msg(),json_last_error());
        }
        $syncResult = PostMeta::firstOrNew(['post_id'=>$postId,'meta_key'=>'osc_sync_result']); 
        $jData->content=null; //移除文章内容，减少空间        
        $syncResult->meta_value = maybe_serialize($jData);
        $syncResult->save();
        return $jData;


    }


    public function save(Request $request, Response $response, $args){

        self::init();

        
        if ($request->getAttribute('csrf_status') === false) {
            $flash = array('[error] CSRF faiure');
            //$this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);
            
        } 
        

        $post = new Post();

        $post->post_author = $this->userId;
        $post->post_title = Input::post('post_title');
        $post->post_content = Input::post('post_content');
        $currentTimestamp = time();
        $post->post_date = date('Y-m-d H:i:s',$currentTimestamp);
        $post->post_modified = $post->post_date;
        $post->post_date_local = date('Y-m-d H:i:s', $currentTimestamp + $this->get('settings')['UTC']*3600) ;

        $post->post_name = hi_random();
        $post->post_status = Input::post('post_status');
        $post->post_password = Input::post('post_password');
        
        $result = $post->save();
        
        
        if( $result ){

            //$post->refresh();
            $postId = $post->post_id;
            $this->flash->addMessage('flash', "[success] 文章保存成功。" );

            $sync =  Input::post('sync') ;
            
            if( $sync && isset($sync['osc']) && !empty($sync['osc'])){
                
                if( isset($sync['osc']['save_as_default']) && !empty($sync['osc']['save_as_default'])){
                    unset($sync['osc']['save_as_default']);
                    $oscSyncOptions = maybe_serialize($sync['osc']);
                    $options = UserMeta::firstOrNew(['user_id'=>$this->userId,'meta_key'=>'osc_sync_default']);
                    $options->meta_value = $oscSyncOptions;
                    $options->save();
                }

                $postMeta = new PostMeta();
                $postMeta->post_id = $postId ;
                $postMeta->meta_key = 'osc_sync_options';
                $postMeta->meta_value = maybe_serialize($sync['osc']);
                $postMeta->save();
                
                
                $syncResult = self::doSyncOsc($postId,$sync['osc']);
                if( $syncResult->code == 1 ){
                    $location =  $this->data['oscer']['homepage'] .'/blog/'. $syncResult->result->id;                $this->flash->addMessage('flash', "[info] 同步到OSC：".$syncResult->message );    
                }else{ //其他code未测试
                    $this->flash->addMessage('flash', "[error] 同步到OSC出错：code: ".$syncResult->code );    
                }
                
                
            }
            
            return $response->withRedirect($this->router->pathFor('post-admin.edit',['name'=>$post->post_name]));
        }



    }

    private function getOscPostOptions($blogWriteUrl,$cookies)
    {

        $conf = $this->settings['guzzle'];
        if( !is_null($cookies) ) {
            $conf['cookies'] = $cookies;
        }
        
        $client = new Client($conf);

        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string)$oscResponse->getBody();
        $dom = new \PHPHtmlParser\Dom;
        $dom->load($body,['whitespaceTextNode' => false]);
        $catalogDropdownNode = $dom->find('#catalogDropdown');
        $classificationNode = $dom->find('[name=classification]');

        $html = new stdClass;
        $html->catalogDropdown = $catalogDropdownNode[0]->innerHtml;
        $html->classification = $classificationNode[0]->innerHtml;
        return $html;

    }


}
