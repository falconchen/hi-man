<?php

namespace App\Action;

use Pheanstalk\Pheanstalk;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;

use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response; // http://docs.guzzlephp.org/en/stable/index.html
use Psr\Http\Message\ServerRequestInterface as Request;
//use App\Helper\Paginator;
// use Illuminate\Pagination;

// use Illuminate\Pagination\Paginator;


//use Illuminate\Support\Facades\DB;

final class TaskAction extends \App\Helper\BaseAction
{

    public function __construct(\Slim\Container $c)
    {
        parent::__construct($c);
        $this->pheanstalk = Pheanstalk::create('127.0.0.1');
    }

    public function producer(Request $request, Response $response, $args)
    {
        $this->pheanstalk
            ->useTube('testtube')
            ->put("hello world2\n");
    }
    public function worker(Request $request, Response $response, $args)
    {
        $job = $this->pheanstalk
            ->watch('testtube')
            ->ignore('default')
            ->reserve();

        echo $job->getData();

        $this->pheanstalk->delete($job);
    }

    public function list(Request $request, Response $response, $args)
    {

        // $listTubes = $this->pheanstalk->listTubes();
        // var_dump($listTubes);

        //查看管道的详细信息
        // $statsTube = $this->pheanstalk->statsTube('testtube');
        // var_dump($statsTube);

        // $stats = $this->pheanstalk->stats('testtube');
        // var_dump($stats);

        $job =  $this->pheanstalk->watch('testtube')->reserve();
        $job_stats = $this->pheanstalk->statsJob($job);
        var_dump($job->getData());
    }
}
