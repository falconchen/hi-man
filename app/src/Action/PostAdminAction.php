<?php
namespace App\Action;

use App\Helper\Hash;
use App\Helper\Session;
use App\Model\Group;
use App\Model\User;
use App\Model\UserMeta;
use App\Validation\Validator;
use Carlosocarvalho\SimpleInput\Input\Input;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Helper\JsonRenderer;

use GuzzleHttp\Psr7;
use GuzzleHttp\Client; // http://docs.guzzlephp.org/en/stable/index.html
use GuzzleHttp\Exception\ClientException;
use stdClass;
use Symfony\Component\Config\Definition\Exception\Exception;

use Violin\Violin;

final class PostAdminAction extends \App\Helper\LoggedAction
{


    private $data;


    private function init()
    {
        $userId = $this->userId;
        $this->data = ['menu'=>$this->menu];
        $oscer = UserMeta::where('user_id', $userId)->where('meta_key','osc_userinfo')->first();
        if( $oscer ){
            $this->data['oscer'] = unserialize($oscer->meta_value);
        }

    }

    public function index(Request $request, Response $response, $args)
    {//list all posts
        self::init();
        $this->view->render($response, 'post-admin/index.twig',$this->data);

    }

    public function new(Request $request, Response $response, $args)
    {

        self::init();

        if( isset($this->data['oscer']) ) {

            try {
                $blogWriteUrl = $this->data['oscer']['homepage'] .'/blog/write';
                $cookieField= UserMeta::where('user_id', $this->userId)->where('meta_key','osc_cookie')->first();
                $cookies = unserialize($cookieField->meta_value);
                $html = $this->getOscPostOptions($blogWriteUrl,$cookies);
                $this->data['oscOptions'] = $html;
            }catch (ClientException $e) { //40x
                $this->logger->log( Psr7\str($e->getRequest()) );
                $this->logger->log(Psr7\str($e->getResponse()));

            } catch (Exception $e){ //others

            }

        }


        $this->view->render($response, 'post-admin/new.twig',$this->data);

    }

    public function save(Request $request, Response $response, $args){
        echo "hello";
        self::init();

        var_dump($_POST);
    }

    private function getOscPostOptions($blogWriteUrl,$cookies)
    {

        $conf = $this->settings['guzzle'];
        if( !is_null($cookies) ) {
            $conf['cookies'] = $cookies;
        }
        
        $client = new Client($conf);

        $oscResponse = $client->request('GET', $blogWriteUrl);
        $body = (string)$oscResponse->getBody();
        $dom = new \PHPHtmlParser\Dom;
        $dom->load($body,['whitespaceTextNode' => false]);
        $catalogDropdownNode = $dom->find('#catalogDropdown');
        $classificationNode = $dom->find('[name=classification]');

        $html = new stdClass;
        $html->catalogDropdown = $catalogDropdownNode[0]->innerHtml;
        $html->classification = $classificationNode[0]->innerHtml;
        return $html;

    }


}
