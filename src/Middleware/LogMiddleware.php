<?php
/**
 * LogMiddleware 请求日志中间件
 */
namespace BoxPHP\Server\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use BoxPHP\Server\Http\Message\HttpResponse;

class LogMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected LoggerInterface $logger = new NullLogger(),
        protected string $level = 'info'
    ) {}

    public function handle(mixed $request, callable $next): mixed
    {
        $start = microtime(true);
        $method = $request['method'] ?? 'GET';
        $uri = $request['path'] ?? '/';

        $this->logger->log($this->level, ">>> {$method} {$uri}");

        $response = $next($request);

        $elapsed = round((microtime(true) - $start) * 1000, 2);
        $status = $response instanceof HttpResponse ? $response->getStatus() : ($response['status'] ?? 200);
        $this->logger->log($this->level, "<<< {$method} {$uri} [{$status}] {$elapsed}ms");

        return $response;
    }
}
