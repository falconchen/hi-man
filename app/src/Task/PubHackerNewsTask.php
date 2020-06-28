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

            $hackerNewsHomePageUrl = 'https://hk.phpfun.xyz/hn/';
            $this->logger->info('start clawer url ' . $hackerNewsHomePageUrl);
            $hnResponse = $this->c->guzzle->request('GET', $hackerNewsHomePageUrl);
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
            $storyTextCNArr = $this->sogouTransArray($storyTextArr);
            $newsArr = [];
            foreach ($storyLinkNodes as $k => $node) {

                $newsArr[]  = [
                    'title' => $node->innerHtml,
                    'site' => isset($siteStrNodes[$k]) ? $siteStrNodes[$k]->innerHtml : '',
                    'titleCN' => $storyTextCNArr[$k],
                    'href' => $node->getAttribute('href'),
                    'score' => intval($scoreNodes[$k]->innerHtml),
                    'age' => strip_tags($ageNodes[$k]->innerHtml),
                    'comments' => intval($subTextNodes[$k]->lastChild()->innerHtml),
                    'commentsLink' => $subTextNodes[$k]->lastChild()->getAttribute('href'),
                ];
            }

            $contentHtml = $this->c->view->fetch('hacker-news/list.twig', [
                'newsArr' => $newsArr,
                'title' => sprintf('最后更新时间: %s ', date('Y-m-d H:i', $this->localTimestamp())),
                'hackerNewsHomePageUrl' => $hackerNewsHomePageUrl,
            ]);
            $this->logger->info('start save hackerNews to Database');
            $this->saveToDb($contentHtml);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
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
            $post = new Post(['post_name' => $postName,'post_status'=>'publish']); //新建,如果是trash或draft直接覆盖
            $post->post_author = 12;
            $post->post_title = 'Hacker News 中文简讯 ' . date('Y-m-d', $localTimeStamp);
            $post->post_date = $post->post_modified;
            $post->post_date_local = date('Y-m-d H:i:s', $localTimeStamp);
            $post->post_status = 'publish';
        }
        $post->post_type = 'post';
        $post->post_modified = date('Y-m-d H:i:s', $currentTimestamp);
        $post->post_content = $contentHtml;

        $post->save();

        if ($isCreate) {
            $hackerNewsOscSyncOptions = [
                'catalog' => 7027796, //Hacker News
                'classification' => 430381, //其他类型
                'type' => 1,
                'as_top' => 1,
                'privacy' => 0,
                'deny_comment' => 0,
                'downloadImg' => 1,
                'send_tweet' => 1,
                'tweet_tmpl' => "看看老外在搞啥【:文章标题:】:OSC链接:"
            ];
            $this->syncOsc($post, $hackerNewsOscSyncOptions);
        }
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
