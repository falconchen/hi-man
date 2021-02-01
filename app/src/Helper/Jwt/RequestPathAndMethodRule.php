<?php
namespace  App\Helper\Jwt;

use Psr\Http\Message\ServerRequestInterface;
use Tuupola\Middleware\JwtAuthentication\RuleInterface;
/**
 * Rule to decide by HTTP verb whether the request should be authenticated or not.
 */
final class RequestPathAndMethodRule implements RuleInterface
{

    /**
     * Stores all the options passed to the rule.
     * @var mixed[]
     */
    private $options = [
        "path" => ["/"],
        "ignore" => []
    ];

    /**
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function __invoke(ServerRequestInterface $request): bool
    {
        $uri = "/" . $request->getUri()->getPath();
        $uri = preg_replace("#/+#", "/", $uri);
        
        foreach ((array)$this->options["path"] as $path) {
            $path = rtrim($path, "/");
            if (!!preg_match("@^{$path}(/.*)?$@", (string) $uri)) {

                // 如果请求方法也符合ignore，则返回假，不需要验证
                if(in_array($request->getMethod(), $this->options["ignore"])){
                    return false;
                }                
            }
        }
        return true;
    }
}
