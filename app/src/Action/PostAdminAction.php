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
use Whoops\Handler\JsonResponseHandler;

final class PostAdminAction extends \App\Helper\LoggedAction
{

    use \App\Helper\OscTrait;

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
        $this->init($request, $response, $args);



        $postAuthor = $this->userId;
        $currentPostStatus = Input::get('post_status') ? Input::get('post_status') : 'publish';

        $conditions = [
            'post_author' => $postAuthor,
            'post_type'=>'post'
        ];
        if ($currentPostStatus != 'any') {
            $conditions['post_status'] = $currentPostStatus;
        }

        $posts = Post::where($conditions)
            ->orderBy('post_date', 'DESC')->get();



        $postsCurrentPage = Post::where($conditions)
            ->orderBy('post_date', 'DESC')->paginate(20);

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
                    'post_type' => 'post',
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

        $this->init($request, $response, $args);

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

        $this->init($request, $response, $args);
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

        $post->osc_link = getOscPostLink($post->post_id);

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
            $this->doSyncPostOsc(19);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }

    


    public function save(Request $request, Response $response, $args)
    {

        $this->init($request, $response, $args);


        if ($request->getAttribute('csrf_status') === false) {
            $flash = array('[error] CSRF faiure');
            //$this->view->render($response, 'login.twig', ['errors' => $v->errors(), 'flash' => $flash, 'request' => $request]);

        }

        if (Input::post('post_id') && intval(Input::post('post_id')) > 0) {
            $postId = intval(Input::post('post_id'));
            $postQuery = Post::where('post_id', $postId);
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

        $post->post_modified = date('Y-m-d H:i:s', $currentTimestamp);


        if (!isset($postId) || !in_array($post->post_status, ['publish', 'future'])) {
            // only update publish time on new post or old status in daft/trash
            $post->post_date = $post->post_modified;
            $post->post_date_local = date('Y-m-d H:i:s', $this->localTimestamp($currentTimestamp));
        }

        //@todo validate post status
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
                // if ($syncResult->count()) {
                //     $syncResult->delete();
                // }
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
                

                if($post->post_status == 'future'){
                    $message = sprintf('文章保存成功，将定时发布于：<code class="w3-grey w3-padding-small">%s</code>，',$post->post_date_local);
                }else{
                    $message = '文章发布成功， ';
                }
                $message  .= sprintf(
                    ' <a class="w3-text-green" href="%s" target="_blank">%s</a>',
                    $this->router->pathFor(
                        'post',
                        ['name' => $post->post_name]
                    ),
                    '查看'

                );
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

                    $syncResult = $this->doSyncPostOsc($postId, $sync['osc']);
                    $this->logger->info('syncResult',[var_export($syncResult,true)]);
                    if ($syncResult->code == 1) {
                        
                        $tweetSyncResult  = '';
                        if( property_exists($syncResult,'tweetPub')) {
                            $tweetSyncResult = $syncResult->tweetPub->code ? '动弹发布成功':'动弹发布出错';
                        }

                        $location =  $this->data['oscer']['homepage'] . '/blog/' . $syncResult->result->id;

                        $this->flash->addMessage('flash', "[info] 同步到OSC：" . $syncResult->message . sprintf(" <a class='w3-text-blue' target='_blank' href='%s'>在osc中查看</a>", $location,',<br/>'.$tweetSyncResult ));

                        
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


    /**
     * 保存预览的session
     */
    public function savePreview(Request $request, Response $response, $args)
    {
        //@todo : check csrf

        if (!empty(Input::post()) && is_array(Input::post())) {
            $post = new Post;
            foreach (Input::post()  as $field => $value) {
                if (strpos($field, 'post') === 0) {
                    $post->$field = $value;
                }
            }
            $post->post_author = $this->userId;

            $this->flash->addMessage('preview_post', maybe_serialize($post));
            $data = [
                'url' => add_query_arg('preview', 'true', $this->router->pathFor('post', ['name' => 'burn-after-reading']))
            ];
            $this->JsonRender->render($response, 200, $data);
        }
    }
}
