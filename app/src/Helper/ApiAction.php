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
use Slim\Http\Response;
use Slim\Http\Request;

class ApiAction extends BaseAction
{


    public function read(Request $request, Response $response, $args)
    {
        exit("read");
    }
    public function create(Request $request, Response $response, $args)
    {
        exit("create");
    }
    public function update(Request $request, Response $response, $args)
    {
        exit("update");
    }
    public function delete(Request $request, Response $response, $args)
    {
        exit("delete");
    }

}
