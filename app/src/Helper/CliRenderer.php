<?php
namespace App\Helper;

use \Psr\Http\Message\ResponseInterface;

/**
 * CliRenderer
 *
 * 
 */
  class CliRenderer
{
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
    $newResponse = $response->withHeader('Content-Type', 'text');    
    $errorMsg = '';
    foreach($data as $k=> $v) {
      if(is_array($v)) {
        $v = implode(PHP_EOL,$v);
      }
      $errorMsg .= sprintf("%s: %s".PHP_EOL,$k,$v);
    }
    $newResponse->getBody()->write($errorMsg);

    return $newResponse;
  }
}