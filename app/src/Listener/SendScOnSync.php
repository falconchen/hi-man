<?php
namespace App\Listener;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use League\HTMLToMarkdown\HtmlConverter;


/**
 * 同步成功时 用 Server 酱发送一条通知
 * 
 */
class SendScOnSync extends AbstractListener
{
    use \App\Helper\HelperTrait;
    /**
     * 回调函数
     * https://github.com/thephpleague/event/issues/65
     * 
     * @param EventInterface $event
     * @param mixed $param | 参数必须有默认值 ref: https://event.thephpleague.com/2.0/events/arguments/ 文档有错误，正确用法使用https://github.com/thephpleague/event/issues/65 
     * 
     * @return void
     */
    public function handle(EventInterface $event, $c = null ,$post = null,$oscSyncOptions=null,$syncResult=null)
    { 
        $this->logger = $c->get('logger');
        $this->logger->info('now send sync sc post_id '.$post->post_id);
        $this->setContainer($c);
        $notifyTitle =  '文章 《' . $post->post_title . '》 同步到osc: ' . $syncResult->message;
        $tweetSyncResultText  = '';

        if ($syncResult->code == 1) {

            if( property_exists($syncResult,'tweetPub') && $syncResult->tweetPub['code'] == 1 ) {
                
                $tweetUrl = getOscTweetLink($post->post_author,$syncResult->tweetPub['result']['log']);
                $tweetSyncResultText = sprintf(" ;动弹发送成功, [查看动弹](%s)",$tweetUrl);
                
            }
        }
        $notifyBody = sprintf(
            "<ul>                
                <li><a href='%s'>%s</a></li>
                <li><a href='%s'>OSC博客</a>%s</li>                
            </ul>",
            $this->getPostLink($post,true),          
            $post->post_title,            
            $post->getOscLink(),
            $tweetSyncResultText
        );
        $this->logger->info('base notify info',[$notifyBody]);
        $converter = new HtmlConverter();
        //$content = preg_replace('#<blockquote class="hn\-blockquote">.*</blockquote>#iUs','',$post->post_content); 
        $content = $post->post_content;       
        $notifyBody = $converter($notifyBody . $content);
        $this->scNofify($notifyTitle, $notifyBody);
        

    }
} 
