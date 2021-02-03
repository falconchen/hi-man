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
        $token = $request->getAttribute("token");
        $userId = $token['uid'];        
        $uploadedFiles = $request->getUploadedFiles();        
        $uploadedFile = $uploadedFiles['image'];        
        //$dateDir = date('Y/m/d',$this->localTimestamp());
        $userSourceDir = $userId .'/source';
        $directory = $this->settings['media']['uploads']['dir'] .'/'. $userSourceDir ;



        if( !is_dir($directory) ) {
            mkdir($directory,0744,true);
        }
        
        if ( $uploadedFile->getError() === UPLOAD_ERR_OK ) {
            
            $filename = $this->moveUploadedFile($directory, $uploadedFile);
                        
            $data = [];            
            $media = new MediaMap();
            $media->content_type = 'image/' .$this->getExtension($uploadedFile);
            $media->media_author = $userId;
            $media->title = strip_tags( pathinfo($uploadedFile->getClientFilename(),PATHINFO_FILENAME) );
            $media->origin_url = $this->settings['media']['uploads']['uri'] .'/'. $userSourceDir  .'/'.$filename;
            $media->tags = 'upload,collection-cover';
            $media->save();
            $data = $media->toArray();          
            return JsonRenderer::success($response,200,null,$data);
        }
        
        return JsonRenderer::error($response,500,'failed with error code:'.$uploadedFile->getError());

    }   
    
    function moveUploadedFile($directory, UploadedFile $uploadedFile)
    {
        $extension = $this->getExtension($uploadedFile);
        $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
        $filename = sprintf('%s.%0.8s', $basename, $extension);
        $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);
        return $filename;
    }

    private function getExtension(UploadedFile $uploadedFile) {
        return strtolower(pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
    }

}