<?php

namespace App\Action;

use App\Helper\Hash;
use App\Helper\Menu;
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
use Route;
use stdClass;
use Symfony\Component\Config\Definition\Exception\Exception;
use Illuminate\Database\Capsule\Manager as DB;

use Violin\Violin;

final class PostAdminAction extends \App\Helper\LoggedAction
{


    private $data;


    private function init(Request $request, Response $response, $args)
    {
        $userId = $this->userId;
        //$this->data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_userinfo')->first();
        if ($oscer) {
            $this->data['oscer'] = unserialize($oscer->meta_value);
            $this->data['avatar'] = $this->data['oscer']['avatar'];
        }
        $this->data['flash'] = $this->flash->getMessage('flash');

        $route = $request->getAttribute('route');
        $menu = new Menu($route, $this->user);
        $this->data['menu'] = $menu;

        // $name = $route->getName();
        // $groups = $route->getGroups();
        // $methods = $route->getMethods();
        // $arguments = $route->getArguments();
    }

    public function index(Request $request, Response $response, $args)
    { //list all posts
        self::init($request, $response, $args);



        $postAuthor = $this->userId;
        $currentPostStatus = Input::get('post_status') ? Input::get('post_status') : 'publish';

        $conditions = [
            'post_author' => $postAuthor,
        ];
        if ($currentPostStatus != 'any') {
            $conditions['post_status'] = $currentPostStatus;
        }

        $posts = Post::where($conditions)
            ->orderBy('post_date', 'DESC')->get();



        $postsCurrentPage = Post::where($conditions)
            ->orderBy('post_date', 'DESC')->paginate(12);

        $postsCurrentPage->withPath(remove_query_arg('page'));


        //$postsCurrentPage->appends('b=3');



        if ($postsCurrentPage->count() > 0) {
            foreach ($postsCurrentPage as &$post) {
                $post->post_modified = $this->dateTolocal('Y-m-d H:i:s', $post->post_modified);
                $post->post_author_name = User::where('id', $postAuthor)->first()->username;
            }
        }
        $this->data['postsCurrentPage'] =  $postsCurrentPage;
        $this->data['posts'] = $posts;

        $postStatuses = ['publish', 'future', 'trash', 'draft', 'any'];
        $postStatusesNames = ['已发表', '定时', '回收站', '草稿', '全部'];
        foreach ($postStatuses as $k => $postStatus) {

            $this->data['postStatuses'][$postStatus] = [];
            $this->data['postStatuses'][$postStatus]['name'] = $postStatusesNames[$k];
            if ($currentPostStatus == $postStatus) {
                $this->data['postStatuses'][$postStatus]['current'] = true;
                $count = $posts->count();
            } else {
                $conditions = [
                    'post_author' => $postAuthor,
                ];
                if ($postStatus != 'any') {
                    $conditions['post_status'] = $postStatus;
                }
                $count = Post::where($conditions)->count();
            }

            $this->data['postStatuses'][$postStatus]['count'] = $count;
            $this->data['postStatuses'][$postStatus]['url'] = add_query_arg(
                'post_status',
                $postStatus,
                $this->router->pathFor('post-admin')
            );
        }


        $this->view->render($response, 'post-admin/index.twig', $this->data);
    }

    public function postNew(Request $request, Response $response, $args)
    {

        self::init($request, $response, $args);

        if (isset($this->data['oscer'])) {

            try {
                $blogWriteUrl = $this->data['oscer']['homepage'] . '/blog/write';
                $cookieField = UserMeta::where('user_id', $this->userId)->where('meta_key', 'osc_cookie')->first();
                $cookies = unserialize($cookieField->meta_value);
                $html = $this->getOscPostOptions($blogWriteUrl, $cookies);
                $this->data['oscOptions'] = $html;
                $this->data['storeOptions'] = $this->getStoreSyncOptions();
            } catch (ClientException $e) { //40x
                $this->logger->log(Psr7\str($e->getRequest()));
                $this->logger->log(Psr7\str($e->getResponse()));
            } catch (Exception $e) { //others

            }
        }
        $this->data['publishDate'] = $this->localTimeArr();

        $this->view->render($response, 'post-admin/post.twig', $this->data);
    }

    public function postEdit(Request $request, Response $response, $args)
    {

        self::init($request, $response, $args);
        $postQuery = Post::where('post_name', $args['name']);
        if ($this->user->group_id >= 3) {
            $postQuery = $postQuery->where('post_author', $this->userId);
        }
        $post = $postQuery->first();
        if (is_null($post)) {
            $flash = ['[error] 没有找到文章 '];
            $this->data['flash'] = $flash;
            return $this->view->render($response, 'post-admin/post.twig', $this->data);
        }

        $this->data['post'] = $post;

        if (isset($this->data['oscer'])) {

            try {
                $blogWriteUrl = $this->data['oscer']['homepage'] . '/blog/write';
                $cookieField = UserMeta::where('user_id', $this->userId)->where('meta_key', 'osc_cookie')->first();
                $cookies = unserialize($cookieField->meta_value);
                $html = $this->getOscPostOptions($blogWriteUrl, $cookies);
                $this->data['oscOptions'] = $html;
                $this->data['storeOptions'] = $this->getStoreSyncOptions($post->post_id);
                //var_dump($this->data['storeOptions'] );exit;
            } catch (ClientException $e) { //40x
                $this->logger->log(Psr7\str($e->getRequest()));
                $this->logger->log(Psr7\str($e->getResponse()));
            } catch (Exception $e) { //others

            }
        }


        $this->data['publishDate'] = $this->localTimeArr(strtotime($post->post_date_local));
        $this->view->render($response, 'post-admin/post.twig', $this->data);
    }



    /**
     * 同步到osc
     */
    public function syncOsc(Request $request, Response $response, $args)
    {
        try {
            $this->doSyncOsc(19);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    private function doSyncOsc($postId, $oscSyncOptions = [])
    {

        //default sync options
        $postArr = array(
            'id' => '', //osc的文章id;
            'user_code' => "i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z",
            'title' => "Title",
            'content' => "Content",
            'content_type' => "4",
            'catalog' => "0",
            'classification' => "430381", //其他类型
            'type' => "1",
            'origin_url' => "",
            'privacy' => "0",
            'deny_comment' => "0",
            'as_top' => "0",
            'downloadImg' => "0",
            'isRecommend' => "0",
        );


        if (empty($oscSyncOptions)) {
            $syncOptions = PostMeta::where(['post_id' => $postId, 'meta_key' => 'osc_sync_options'])->first();
            if ($syncOptions === NULL) {
                throw new Exception('No OSC Sync Options');
            }
            $oscSyncOptions = unserialize($syncOptions->meta_value);
        }

        $postArr = array_merge($postArr, $oscSyncOptions);

        if (!$postArr['classification']) {
            throw new Exception('classification empty');
        }
        if (!$postArr['catalog']) {
            throw new Exception('catalog empty');
        }

        //self::init( $request, $response , $args) ;
        $postDbData = Post::where('post_id', $postId)->first();
        $postArr['title'] = $postDbData->post_title;
        $postArr['content'] = $postDbData->post_content;


        //$this->data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $postDbData->post_author)->where('meta_key', 'osc_userinfo')->first();
        if (!$oscer) {
            throw new Exception("user did not connected to osc yet");
        }
        $oscer = unserialize($oscer->meta_value);

        $blogWriteUrl = $oscer['homepage'] . '/blog/write';
        $blogSaveUrl = $oscer['homepage'] . '/blog/save';

        $cookieField = UserMeta::where('user_id', $postDbData->post_author)->where('meta_key', 'osc_cookie')->first();
        $cookies = unserialize($cookieField->meta_value);


        $conf = $this->settings['guzzle'];
        if (!is_null($cookies)) {
            $conf['cookies'] = $cookies;
        }
        $conf['headers']['Referer'] = $blogWriteUrl;

        $client = new Client($conf);
        //确认分类字段是否存在，获取user_code
        //<input type="hidden" name="user_code" value="i17sGbMlA2FhAI5hwcVZCOlzoXkjZ5TT0hGJUN9z">

        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string) $oscResponse->getBody();

        $dom = new \PHPHtmlParser\Dom;
        $dom->load($body, ['whitespaceTextNode' => false]);


        //get userCode
        $userCodeNode = $dom->find('input[name="user_code"]');
        if (!count($userCodeNode)) {
            throw new Exception('userCodeNode empty');
        }
        $userCode = $userCodeNode[0]->getAttribute('value');

        //check catalog
        $catalogNode = $dom->find('#catalogDropdown option[value=' . $postArr['catalog'] . ']');
        if (!count($catalogNode)) {
            throw new Exception('catalog not exists');
        }


        $postArr['user_code'] = $userCode;

        $oscResponse = $client->request('POST', $blogSaveUrl, [
            'form_params' => $postArr,
        ]);
        $body = (string) $oscResponse->getBody();

        $jData = json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg(), json_last_error());
        }
        $syncResult = PostMeta::firstOrNew(['post_id' => $postId, 'meta_key' => 'osc_sync_result']);
        $jData->result->content = mb_substr($jData->result->content, 0, 100, 'UTF-8') . '...'; //移除文章内容，减少空间
        $syncResult->meta_value = maybe_serialize($jData);
        $syncResult->save();

        $postDbData->post_status = "publish";
        $postDbData->save();
        return $jData;
    }


    public function save(Request $request, Response $response, $args)
    {

        self::init($request, $response, $args);


        if ($request->getAttribute('csrf_status') === false) {
            $flash = array('[error] CSRF faiure');
            //$this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);

        }

        if (Input::post('post_id')) {
            $post_id = intval(Input::post('post_id'));
            $postQuery = Post::where('post_id', $post_id);
            if ($this->user->group_id >= 3) {
                $postQuery = $postQuery->where('post_author', $this->userId);
            }
            $post = $postQuery->first();
            if (is_null($post)) {
                $flash = ['[error] 没有找到文章 '];
                $this->data['flash'] = $flash;
                return $this->view->render($response, 'post-admin/post.twig', $this->data);
            }
        } else {
            $post = new Post();
        }

        $post->post_author = $this->userId;
        $post->post_title = Input::post('post_title');
        $post->post_content = Input::post('post_content');

        $currentTimestamp = time();
        $post->post_date = date('Y-m-d H:i:s', $currentTimestamp);
        $post->post_modified = $post->post_date;
        $post->post_date_local = date('Y-m-d H:i:s', $this->localTimestamp($currentTimestamp));
        $post->post_status = Input::post('post_status');


        if (Input::post('post_future') == 'yes') {

            //@todo 校验日期有效性

            $future =  sprintf(
                '%d-%d-%d %d:%d',
                Input::post('y'),
                Input::post('m'),
                Input::post('d'),
                Input::post('h'),
                Input::post('i')
            );

            $post->post_date_local = $future;
            $utc = $this->dateToUtc('Y-m-d H:i', $post->post_date_local);
            if (strtotime($utc)  > $currentTimestamp) {

                //$post->post_status = 'future';

                $post->post_date = $utc;
                $syncResult = PostMeta::where(['post_id' => $post->post_id, 'meta_key' => 'osc_sync_result']);
                if ($syncResult->count()) {
                    $syncResult->delete();
                }
            }
        }

        if (!$post->post_id) {
            $post->post_name = hi_random();
        }


        $post->post_visibility = trim(Input::post('post_visibility'));
        $post->post_password = trim(Input::post('post_password'));
        $result = $post->save();

        //var_dump($post->post_status);

        if ($result) {

            //$post->refresh();
            $postId = $post->post_id;
            if ($post->post_status == 'trash') {
                $message = '文章已放入回收站';
            } else {
                $message = '文章保存成功。';
            }
            $this->flash->addMessage('flash', "[success] " . $message);
            $sync =  Input::post('sync');


            if ($sync && isset($sync['osc']) && !empty($sync['osc'])) {

                if (isset($sync['osc']['save_as_default']) && !empty($sync['osc']['save_as_default'])) {
                    unset($sync['osc']['save_as_default']);
                    $oscSyncOptions = maybe_serialize($sync['osc']);
                    $options = UserMeta::firstOrNew(['user_id' => $this->userId, 'meta_key' => 'osc_sync_default']);
                    $options->meta_value = $oscSyncOptions;
                    $options->save();
                }

                $postMeta = PostMeta::firstOrNew(['post_id' => $postId, 'meta_key' => 'osc_sync_options']);
                $postMeta->meta_value = maybe_serialize($sync['osc']);
                $postMeta->save();

                if ($post->post_status == 'publish') { //此时提交

                    $syncResult = self::doSyncOsc($postId, $sync['osc']);
                    if ($syncResult->code == 1) {
                        $location =  $this->data['oscer']['homepage'] . '/blog/' . $syncResult->result->id;
                        $this->flash->addMessage('flash', "[info] 同步到OSC：" . $syncResult->message);
                        $this->flash->addMessage('flash', sprintf("[info] <a target='_blank' href='%s'>osc链接</a>", $location));
                    } else { //其他code未测试
                        $this->flash->addMessage('flash', "[error] 同步到OSC出错：code: " . $syncResult->code);
                    }
                }
            }

            $redirectUrl = ($post->post_status != 'trash') ?
                $this->router->pathFor(
                    'post-admin.edit',
                    ['name' => $post->post_name]
                ) : add_query_arg('post_status', 'trash', $this->route->pathFor('post-admin'));

            return $response->withRedirect($redirectUrl);
        }
    }

    private function getOscPostOptions($blogWriteUrl, $cookies)
    {

        $conf = $this->settings['guzzle'];
        if (!is_null($cookies)) {
            $conf['cookies'] = $cookies;
        }

        $client = new Client($conf);

        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string) $oscResponse->getBody();
        $dom = new \PHPHtmlParser\Dom;
        $dom->load($body, ['whitespaceTextNode' => false]);
        $catalogDropdownNode = $dom->find('#catalogDropdown');
        $classificationNode = $dom->find('[name=classification]');

        $html = new stdClass;

        $html->catalogDropdown = $catalogDropdownNode[0]->innerHtml;
        $html->classification = $classificationNode[0]->innerHtml;

        $catalogDropdownNodes = $dom->find('#catalogDropdown option');
        $classificationNodes = $dom->find('[name=classification] option');
        $html->catalogDropdowns = [];
        $html->classifications = [];
        foreach ($catalogDropdownNodes as $node) {
            $html->catalogDropdowns[] = ['text' => $node->text, 'value' => $node->getAttribute('value')];
        }
        foreach ($classificationNodes as $node) {
            $html->classifications[] = ['text' => $node->text, 'value' => $node->getAttribute('value')];
        }

        return $html;
    }


    private function getStoreSyncOptions($postId = null, $userId = null)
    {

        $options = null;

        if ($postId) {
            $options = PostMeta::where('post_id', $postId)->where('meta_key', 'osc_sync_options')->first();
        }

        if (is_null($options)) {
            $userId = is_null($userId) ? $this->userId : $userId;
            $options = UserMeta::where('user_id', $userId)->where('meta_key', 'osc_sync_default')->first();
        }

        if (!is_null($options)) {
            $options = unserialize($options->meta_value);
        } else {
            return [
                "catalog" => "304044",
                "classification" => "430381",
                "type" => "1"
            ];
        }




        return $options;
    }

    protected function localTimeArr($localStamp = null)
    {
        $localStamp = is_null($localStamp) ? $this->localTimestamp() : $localStamp;
        return [
            'y' => date('Y', $localStamp), 'm' => date('m', $localStamp), 'd' => date('d', $localStamp),
            'h' => date('H', $localStamp), 'i' => date('i', $localStamp),
        ];
    }
}
