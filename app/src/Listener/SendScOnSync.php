<?php
namespace App\Listener;
use League\Event\AbstractListener;
use League\Event\EventInterface;


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
        $notifyBody = sprintf(
            '网站文章ID: %d , OSC链接 [%s](%s)',
            $post->post_id,
            $post->post_title,
            $post->getOscLink()
        );
        $this->scNofify($notifyTitle, $notifyBody);
        

    }
} 
