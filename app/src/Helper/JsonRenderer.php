<?php
namespace App\Helper;

use \Psr\Http\Message\ResponseInterface;

/**
 * JsonRenderer
 *
 * Render JSON view into a PSR-7 Response object
 */
class JsonRenderer
{

  // public  function __construct($c)
  // {

  //   $this->c = $c; 

  // }
  /**
   *
   * @param ResponseInterface $response
   * @param int $statusCode
   * @param array $data
   *
   * @return ResponseInterface
   *
   * @throws \InvalidArgumentException
   * @throws \RuntimeException
   */

  public static function render(ResponseInterface $response, $statusCode = 200, array $data = [])
  {
    $newResponse = $response->withHeader('Content-Type', 'application/json');
    $newResponse = $newResponse->withStatus(intval($statusCode));
    $newResponse->getBody()->write(json_encode($data,JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return $newResponse;
  }

  public static function success(ResponseInterface $response, $statusCode = 200,$message=null, array $data = [], $code=null)
  {
    
    /**
     * 使用以下模板
    {
      ""success": true,
      "code": 200,// 包含一个整数类型的 HTTP 响应状态码，也可以是业务描述操作码，比如 200001 表示注册成功
      "message": "操作成功",// 多语言的响应描述
      "data": {// 实际的响应数据
          "nickname": "Joaquin Ondricka",
          "email": "lowe.chaim@example.org"
      },
      "error": {}// 异常时的调试信息
  }
   */
    $dataWrap = [
      'success'=> true,
      'code'=> !is_null($code) ? $code : $statusCode,
      'message'=>!is_null($message) ? $message : "success",
      'data'=>$data,
      'error'=>[],
    ];
    return self::render($response,$statusCode,$dataWrap);
  }

  public static function error(ResponseInterface $response, $statusCode = 500, $message=null, array $error = [], $code=null)
  {
    
    /**
     * 使用以下模板
    {
      "success": false,
      "code": 200,// 包含一个整数类型的 HTTP 响应状态码，也可以是业务描述操作码，比如 200001 表示注册成功
      "message": "操作成功",// 多语言的响应描述
      "data": {// 实际的响应数据
          "nickname": "Joaquin Ondricka",
          "email": "lowe.chaim@example.org"
      },
      "error": {}// 异常时的调试信息
  }

   */
  
    $dataWrap = [
      'success'=> false,
      'code'=> !is_null($code) ? $code : $statusCode,
      'message'=> !is_null($message) ? $message : "error",
      'data'=>[],
      'error'=>$error,
    ];

    return self::render($response,$statusCode,$dataWrap);
  }

  
}