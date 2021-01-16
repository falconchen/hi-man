<?php
// namespace Slim\Csrf;
namespace FalconChen\SlimCsrf;


use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * CSRF protection middleware based
 * on the OWASP example linked below.
 *
 * @link https://www.owasp.org/index.php/PHP_CSRF_Guard
 */
class Guard extends \Slim\Csrf\Guard
{
    
    /**
     * Invoke middleware
     *
     * @param  RequestInterface  $request  PSR7 request object
     * @param  ResponseInterface $response PSR7 response object
     * @param  callable          $next     Next middleware callable
     *
     * @return ResponseInterface PSR7 response object
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        // Validate POST, PUT, DELETE, PATCH requests
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            //var_dump($request->getHeaders());exit;
            $body = $request->getParsedBody();
            $body = $body ? (array)$body : [];
            $name = isset($body[$this->prefix . '_name']) ? $body[$this->prefix . '_name'] : false;
            $value = isset($body[$this->prefix . '_value']) ? $body[$this->prefix . '_value'] : false;
            
            if (!$name || !$value || !$this->validateToken($name, $value)) {
                // Need to regenerate a new token, as the validateToken removed the current one.

                //$request = $this->generateNewToken($request); // ajax处理会变得非常麻烦，停止

                $failureCallable = $this->getFailureCallable();
                return $failureCallable($request, $response, $next);
            }
        }
        // Generate new CSRF token
        $request = $this->generateNewToken($request);

        // Enforce the storage limit
        $this->enforceStorageLimit();

        return $next($request, $response);
    }

    

    /**
     * Validate CSRF token from current request
     * against token value stored in $_SESSION
     *
     * @param  string $name  CSRF name
     * @param  string $value CSRF token value
     *
     * @return bool
     */
    protected function validateToken($name, $value)
    {
        $token = $this->getFromStorage($name);
        if (function_exists('hash_equals')) {
            $result = ($token !== false && hash_equals($token, $value));
        } else {
            $result = ($token !== false && $token === $value);
        }
        //$this->removeFromStorage($name); // ajax处理会变得非常麻烦，停止

        return $result;
    }



}
