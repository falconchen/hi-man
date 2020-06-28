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

    public function command($args) {
        
        

        try {
            
            $hackerNewsHomePageUrl = 'https://hk.phpfun.xyz/hn/';
            $this->logger->info('start clawer url '.$hackerNewsHomePageUrl);
            $hnResponse = $this->c->guzzle->request('GET',$hackerNewsHomePageUrl);
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
            foreach($storyLinkNodes as $node) {
                $storyTextArr[] = $node->innerHtml;
            }            
            $storyTextCNArr = $this->sogouTransArray($storyTextArr);
            $newsArr = [];
            foreach($storyLinkNodes as $k=>$node) {
                
                $newsArr[]  = [
                    'title'=>$node->innerHtml,
                    'site'=>isset($siteStrNodes[$k]) ? $siteStrNodes[$k]->innerHtml : '',
                    'titleCN'=>$storyTextCNArr[$k],
                    'href'=>$node->getAttribute('href'),
                    'score'=>intval($scoreNodes[$k]->innerHtml),
                    'age'=>strip_tags($ageNodes[$k]->innerHtml),
                    'comments'=>intval($subTextNodes[$k]->lastChild()->innerHtml),
                    'commentsLink'=>$subTextNodes[$k]->lastChild()->getAttribute('href'),
                ];

            }    
            
            $contentHtml = $this->c->view->fetch('hacker-news/list.twig', [
                'newsArr' => $newsArr,
                'title'=>sprintf('最后更新时间: %s ',date('Y-m-d H:i',$this->localTimestamp()))
            ]);
            $this->saveToDb($contentHtml);

            
        }catch (\Exception $e) {

            $this->logError($e->getMessage());

        }
        
        



    }



    private function saveToDb($contentHtml) {

        
        $localTimeStamp = $this->localTimestamp();
        $postName = 'hn-'.date('Ymd',$localTimeStamp);
        $post = Post::firstOrnew(['post_name'=>$postName]);
        $post->post_author = 12;
        $post->post_title = 'Hacker News 中文简讯 '. date('Y-m-d',$localTimeStamp);
        $post->post_content = $contentHtml;
        $post->post_type='post';
        $currentTimestamp = time();
        $post->post_modified = date('Y-m-d H:i:s', $currentTimestamp);
        $post->post_date = $post->post_modified;
        $post->post_date_local = date('Y-m-d H:i:s', $localTimeStamp);
        $post->post_status = 'publish';        
        $post->save();

        return $post;
    }

}
