<?php
/**
 *
 * Description:
 * Author: falcon
 * Date: 2019/11/16
 * Time: 12:18 PM
 *
 */

namespace App\Helper;

use App\Model\User;

class LoggedAction extends BaseAction
{
    protected $userId;
    protected $user;

    public function __construct(\Slim\Container $c){
        parent::__construct($c);
        $this->userId = $this->session->get($this->auth['session']);                
        $this->user = User::where('id', $this->userId)->first();
       // $this->menu = new Menu($this->router,$this->user);

    }

    //protected function init(Request $request, Response $response, $args)

}