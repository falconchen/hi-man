<?php

namespace App\Api;


use App\Helper\JsonRenderer;
use App\Model\Collection;
use Psr\Http\Message\ResponseInterface as Response; 
use Psr\Http\Message\ServerRequestInterface as Request;



final class Collections extends \App\Helper\ApiAction
{
    public function read(Request $request, Response $response, $args)
    {
        $token = $request->getAttribute("token");
        $userId = $token['uid'];
        $operateDefalut = [
            'offset' => 0,
            'limit' => 6,
            'order' => 'updated_at',
            'by' => 'desc',
        ];
        $params = $request->getQueryParams() ?? [];
        $operators = array_merge($operateDefalut, $params);
        $collections = Collection::where('author', $userId)
            ->offset($operators['offset'])
            ->limit($operators['limit'])
            ->orderBy($operators['order'], $operators['by'])
            //->orderBy('collection_id','desc')
            ->get()->toArray();
        return JsonRenderer::success($response, 200, null, $collections);
    }
    public function create(Request $request, Response $response, $args)
    {


        $token = $request->getAttribute("token");
        $userId = $token['uid'];
        $data = $request->getParsedBody();
        if (!isset($data['title']) || empty($data['title']) || strlen($data['title']) < 2) {
            return JsonRenderer::error($response, 400, $this->trans('invalid title'));
        }

        if( !isset($data['slug']) || strlen($data['slug']) == 0 ){
            $data['slug'] = hi_random();
        } 
        $data['slug'] = trimSlug($data['slug']);
        if (strlen($data['slug']) == 0) { //trim 掉非法字符后slug为0即非法
            return JsonRenderer::error($response, 400, $this->trans('invalid slug'));
        }
        

        $i = 1;
        $raw = $data['slug'];
        while (Collection::where('slug', $data['slug'])->where('author', $userId)->count()) {
            $i += 1;
            $data['slug'] = $raw . '-' . $i;
        }
        unset($i);
        unset($raw);

        $currentCollection = new Collection($data);
        $currentCollection->author = $userId;

        if (!$currentCollection->save()) {
            return JsonRenderer::error($response, 500);
        }
        //$data['collection_id'] = $currentCollection->collection_id;
        return JsonRenderer::success($response, 201, $this->trans('created successfully'), $currentCollection->toArray());
    }
    public function update(Request $request, Response $response, $args)
    {
        $token = $request->getAttribute("token");
        $userId = $token['uid'];
        $data = $request->getParsedBody();

        if (!isset($data['collection_id']) || empty($data['collection_id'])) {
            return JsonRenderer::error($response, 400, $this->trans('invalid collection_id'));
        }

        if (!isset($data['title']) || empty($data['title'])) {
            return JsonRenderer::error($response, 400, $this->trans('invalid title'));
        }

        $currentCollection = Collection::where(['collection_id' => $data['collection_id'], 'author' => $userId])->first();
        if (is_null($currentCollection)) {
            return JsonRenderer::error($response, 403, $this->trans('no permission to delete to update'));
        }

        if( !isset($data['slug']) || strlen($data['slug']) == 0 ){
            $data['slug'] = hi_random();
        } 
        $data['slug'] = trimSlug($data['slug']);
        if (strlen($data['slug']) == 0) { //trim 掉非法字符后slug为0即非法
            return JsonRenderer::error($response, 400, $this->trans('invalid slug'));
        }

        $i = 1;
        $raw = $data['slug'];
        while (Collection::where(['slug' => $data['slug'], 'author' => $userId])
            ->where('collection_id', '<>', $data['collection_id'])->count()
        ) {
            $i += 1;
            $data['slug'] = $raw . '-' . $i;
        }
        unset($i);
        unset($raw);


        if (!$currentCollection->update($data)) {
            return JsonRenderer::error($response, 500);
        }
        return JsonRenderer::success($response, 200, $this->trans('updated successful'), $currentCollection->toArray());
    }
    public function delete(Request $request, Response $response, $args)
    {
        $token = $request->getAttribute("token");
        $userId = $token['uid'];
        $data = $request->getParsedBody();

        if (!isset($data['collection_id']) || empty($data['collection_id'])) {
            return JsonRenderer::error($response, 400, $this->trans('invalid collection_id'));
        }

        $currentCollection = Collection::where(['collection_id' => $data['collection_id']])->first();

        if (is_null($currentCollection)) {
            return JsonRenderer::error($response, 404, $this->trans('collection is not exists'));
        }

        if ($currentCollection->author !== $userId) {
            return JsonRenderer::error($response, 403, $this->trans('no permission to delete'));
        }
        if (!$currentCollection->delete()) {
            return JsonRenderer::error($response, 500);
        }
        return JsonRenderer::success($response, 200, $this->trans('deleted successfully'), $data);
    }
}
