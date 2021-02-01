<?php
namespace App\Api;

use Ramsey\Uuid\Uuid;
use App\Model\MediaMap;
use App\Model\User;
use Firebase\JWT\JWT;
use Slim\Http\Response;
use Slim\Http\Request;
use Slim\Http\UploadedFile;
use App\Helper\JsonRenderer;

final class Images extends \App\Helper\ApiAction
{
    public function read(Request $request, Response $response, $args)
    {
        $data = MediaMap::where('media_id',$args['id'])->get()->toArray();        
        return JsonRenderer::success($response,200,null,$data);

    }
    public function create(Request $request, Response $response, $args)
    {                
        
        $uploadedFiles = $request->getUploadedFiles();        
        $uploadedFile = $uploadedFiles['image'];
        
        $directory =$this->settings['media']['image']['dir'];
        
        if ( $uploadedFile->getError() === UPLOAD_ERR_OK ) {
            
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
            
            $data['filename'] = $filename;
            
            return JsonRenderer::success($response,200,null,$data);
        }
        
        return JsonRenderer::error($response,500,'failed:error code:'.$uploadedFile->getError());
        //var_dump($uploadedFile );exit;

        //var_dump($_FILES);
        //exit('hello');
    }   
    
    function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        return $filename;
    }

}