<?php
namespace App\Action;

use App\Helper\Hash;
use App\Helper\BaseAction;
use App\Helper\JsonRenderer;
use App\Helper\JsonRequest;
use App\Model\User;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

final class NoteAction extends BaseAction
{

    public function index(Request $request, Response $response, $args)
    {
        
        $this->logger->info("Home page action dispatched");
        $this->view->render($response, '/note/index.twig', [
            'date'=>date('Y-m-d')
        ]);
        return $response;
    }

    public function dashboard(Request $request, Response $response, $args)
    {
        $flash = $this->session->get('flash');
        return $this->view->render($response, 'dashboard.twig', ['flash' => $flash]);
    }

}