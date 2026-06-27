<?php
/**
 * RateLimitMiddleware 限流中间件
 */
namespace BoxPHP\Server\Middleware;

use BoxPHP\Server\Http\Message\HttpResponse;

class RateLimitMiddleware implements MiddlewareInterface
{
    /** @var array<int, array{count: int, reset_at: int}> */
    protected array $counters = [];

    /**
     * @param int $maxRequests 最大请求数
     * @param int $windowSeconds 时间窗口(秒)
     */
    public function __construct(
        protected int $maxRequests = 60,
        protected int $windowSeconds = 60
    ) {}

    public function handle(mixed $request, callable $next): mixed
    {
        $key = $this->getKey($request);
        $now = time();

        // 初始化或重置计数器
        if (!isset($this->counters[$key]) || $this->counters[$key]['reset_at'] < $now) {
            $this->counters[$key] = [
                'count' => 0,
                'reset_at' => $now + $this->windowSeconds,
            ];
        }

        $this->counters[$key]['count']++;

        $remaining = $this->maxRequests - $this->counters[$key]['count'];

        if ($remaining < 0) {
            $retryAfter = $this->counters[$key]['reset_at'] - $now;
            return HttpResponse::error(429, "Too Many Requests, retry after {$retryAfter}s")
                ->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
                ->withHeader('X-RateLimit-Remaining', '0')
                ->withHeader('X-RateLimit-Reset', (string)$this->counters[$key]['reset_at'])
                ->withHeader('Retry-After', (string)$retryAfter);
        }

        $response = $next($request);

        // 添加限流头
        if ($response instanceof HttpResponse) {
            return $response->withHeader('X-RateLimit-Limit', (string)$this->maxRequests)
                ->withHeader('X-RateLimit-Remaining', (string)max(0, $remaining))
                ->withHeader('X-RateLimit-Reset', (string)$this->counters[$key]['reset_at']);
        }

        // 原始数组响应
        if (!isset($response['headers'])) {
            $response['headers'] = [];
        }
        $response['headers']['X-RateLimit-Limit'] = $this->maxRequests;
        $response['headers']['X-RateLimit-Remaining'] = max(0, $remaining);
        $response['headers']['X-RateLimit-Reset'] = $this->counters[$key]['reset_at'];

        return $response;
    }

    protected function getKey(mixed $request): string
    {
        $ip = $request['ip'] ?? '127.0.0.1';
        $uri = $request['path'] ?? '/';
        return $ip . ':' . $uri;
    }
}
