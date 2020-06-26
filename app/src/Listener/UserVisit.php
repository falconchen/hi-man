<?php
namespace App\Listener;
//use League\Event\AbstractEvent;
use League\Event\AbstractListener;
use League\Event\EventInterface;


class UserVisit extends AbstractListener
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
    public function handle(EventInterface $event, $param = null)
    { 
        // Handle the event.
        echo 'user visit ';
        var_dump($param);
                
    }
} 
