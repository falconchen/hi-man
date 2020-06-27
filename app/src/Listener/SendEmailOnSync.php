<?php
namespace App\Listener;

use App\Model\User;
use League\Event\AbstractListener;
use League\Event\EventInterface;

/**
 * 同步成功时发送一封邮件
 */
class SendEmailOnSync extends AbstractListener
{
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
        // Handle the event.
        
        $this->logger = $c->get('logger');        
        $this->mailer = $c->get('mailer');
        
        if ( $c->get('settings')['sync']['email.notify']) {
            
            $this->logger->info('now send sync post email for post_id '.$post->post_id);
            $notifyTitle =  '文章 《' . $post->post_title . '》 同步到osc: ' . $syncResult->message;
            $tweetSyncResultText  = '';

            if ($syncResult->code == 1) {
    
                if( property_exists($syncResult,'tweetPub') && $syncResult->tweetPub['code'] == 1 ) {
                    
                    $tweetUrl = getOscTweetLink($post->post_author,$syncResult->tweetPub['result']['log']);
                    $tweetSyncResultText = sprintf(" ;动弹发送成功, 查看动弹 %s",$tweetUrl);
                    
                }
            }
            $notifyBody = sprintf(
                '网站文章ID: %d , OSC链接 [%s] %s%s',
                $post->post_id,
                $post->post_title,
                $post->getOscLink(),
                $tweetSyncResultText
            );

            try {            

                $user = User::find($post->post_author);
                $this->logger->info("sending mail to " . $user->email);
                $sendAddress = $user->email;
                $this->mailer->Subject = $notifyTitle;
                $this->mailer->Body = $notifyBody;
                $this->mailer->AddAddress($sendAddress);

                if (!$this->mailer->send()) {
                    $this->logger->info("failed to send mail to " . $user->email);
                } else {
                    $this->logger->info("success send mail to " . $user->email);
                }
            }catch (\Exception $e) {
                $this->logger->info("failed to send mail to " . $user->email);
                $this->logger->error( $this->mailer->ErrorInfo );                        
            }
        }

    }
} 
