<?php

namespace App\Task;

use \GuzzleHttp\Exception\TransferException;
use \PHPHtmlParser\Dom;
use App\Model\Post;
use App\Model\PostMeta;

/**
 * 发布HackerNews每日播报
 */
class PubHackerNewsTask extends BaseTaskAbstract
{

    use \App\Helper\OscTrait;

    public function command($args)
    {

        

        try {
            
            $inputs = $this->initInputs($args);            
            $defaultInputs = [
                't'=>'sogou',// 翻译器 sogou/baidu
                'p'=>2,//hackerNews 页数
            ];
            $this->inputs = array_merge($defaultInputs,$inputs);

            
            $newsArr = [];
            $hackerNewsHomePageUrl = 'https://hk.phpfun.xyz/hn/';
            
            for ($i=1;$i<=$this->inputs['p'];$i++) {
                $url = rtrim($hackerNewsHomePageUrl,'/') . '/news?p='.$i;
                $newsArr = array_merge_recursive($newsArr,$this->fetchNews($url));                
            }
            $consumeTime = time() - $this->startTime;
            $contentHtml = $this->c->view->fetch('hacker-news/list.twig', [
                'newsArr' => $newsArr,
                'title' => sprintf('更新时间: %s ', date('Y-m-d H:i', $this->localTimestamp())),
                'hackerNewsHomePageUrl' => $hackerNewsHomePageUrl,
                'consumeTime' => $consumeTime,//获取和翻译消耗时间
            ]);
            $this->logger->info('fetch and translated hackerNews time',[ 'consumeTime' => $consumeTime,]);
            $this->logger->info('start save hackerNews to Database');
            $this->saveToDb($contentHtml);

            
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    function fetchNews($url) {
            
            $this->logger->info('start clawer url ' . $url);
            $hnResponse = $this->c->guzzle->request('GET', $url);
            $body = (string) $hnResponse->getBody();

            $this->logger->info('start parse html');
            $dom = new Dom;
            $dom->load($body, ['whitespaceTextNode' => false]);

            $storyLinkNodes = $dom->find('.storylink');
            $siteStrNodes = $dom->find('.sitestr');
            $scoreNodes = $dom->find('.score');
            $ageNodes = $dom->find('.age');
            $subTextNodes = $dom->find('.subtext');

            $storyTextArr = [];
            foreach ($storyLinkNodes as $node) {
                $storyTextArr[] = $node->innerHtml;
            }
            if($this->inputs['t'] == 'baidu'){
                $storyTextCNArr = $this->baiduTransArray($storyTextArr);
            }else {
                $storyTextCNArr = $this->sogouTransArray($storyTextArr);                
            }
            

            $newsArr = [];
            foreach ($storyLinkNodes as $k => $node) {

                $newsArr[]  = [
                    'title' => $node->innerHtml,
                    'site' => isset($siteStrNodes[$k]) ? $siteStrNodes[$k]->innerHtml : '',
                    'titleCN' => $storyTextCNArr[$k],
                    'href' => $node->getAttribute('href'),
                    'score' =>  isset($scoreNodes[$k]) ? intval($scoreNodes[$k]->innerHtml) : 0,
                    'age' => strip_tags($ageNodes[$k]->innerHtml),
                    'comments' => intval($subTextNodes[$k]->lastChild()->innerHtml),
                    'commentsLink' => $subTextNodes[$k]->lastChild()->getAttribute('href'),
                ];
            }
            return $newsArr;
    }

    
    /**
     * save HackerNews content to db
     */
    private function saveToDb($contentHtml)
    {
        $localTimeStamp = $this->localTimestamp();
        $currentTimestamp = time();
        $postName = 'HN-' . date('Ymd', $localTimeStamp);

        $post = Post::where(['post_name' => $postName,'post_status'=>'publish'])->first();
        $isCreate = false;
        if ($post == null) {
            $isCreate = true;
            $post = Post::firstOrNew(['post_name' => $postName]); //新建,如果是trash或draft直接覆盖
            $post->post_author = 19;//HackerNews  
            $post->post_title = 'Hacker News 简讯 ' . date('Y-m-d', $localTimeStamp);
            $post->post_date = date('Y-m-d H:i:s', $currentTimestamp);            
            $post->post_date_local = date('Y-m-d H:i:s', $localTimeStamp);
            $post->post_status = 'publish';
        }
        $post->post_type = 'post';
        $post->post_modified = date('Y-m-d H:i:s', $currentTimestamp);
        $post->post_content = $contentHtml;

        $post->save();
        
        $post->post_content = str_replace(
            ['香港', '民主'],
            ['HK' , '*主'],
            $post->post_content 
        );

        $hackerNewsOscSyncOptions = [
            'catalog' => 7027796, //Hacker News
            'classification' => 430381, //其他类型
            'type' => 1,
            'as_top' => 1,
            'privacy' => 0,
            'deny_comment' => 0,
            'downloadImg' => 1,
            'send_tweet' =>is_null($post->getPostMeta('last_send_tweet')) ? 1 : 0,
            'email_me' =>$isCreate ? 1 : 0,
            'tweet_tmpl' => "看看老外在搞啥【:文章标题:】:OSC链接:"
        ];
                
        $this->syncOsc($post, $hackerNewsOscSyncOptions);
        return $post;
    }

    /**
     * Sync to osc
     *
     * @param Post $post
     * @return object
     */
    protected function syncOsc(Post $post, $syncOptions)
    {

        $postId = $post->post_id;
        $postMeta = PostMeta::firstOrNew(['post_id' => $postId, 'meta_key' => 'osc_sync_options']);

        $postMeta->meta_value = maybe_serialize($syncOptions);
        $postMeta->save();
        return $this->doSyncPostOsc($postId, $syncOptions);
    }
}
