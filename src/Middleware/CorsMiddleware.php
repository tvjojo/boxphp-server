<?php
/**
 * CorsMiddleware 跨域中间件
 */
namespace BoxPHP\Server\Middleware;

use BoxPHP\Server\Http\Message\HttpResponse;

class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param array $options CORS 配置
     */
    public function __construct(
        protected array $options = []
    ) {
        $this->options = array_merge([
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'allowedHeaders' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
            'exposedHeaders' => [],
            'maxAge' => 86400,
            'supportsCredentials' => false,
        ], $options);
    }

    public function handle(mixed $request, callable $next): mixed
    {
        $method = $request['method'] ?? 'GET';
        $origin = $request['headers']['origin'] ?? $request['headers']['referer'] ?? '*';

        $headers = [];

        // 允许的来源
        if (in_array('*', $this->options['allowedOrigins'])) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        } elseif (in_array($origin, $this->options['allowedOrigins'])) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        // OPTIONS 预检请求
        if ($method === 'OPTIONS') {
            $headers['Access-Control-Allow-Methods'] = implode(', ', $this->options['allowedMethods']);
            $headers['Access-Control-Allow-Headers'] = implode(', ', $this->options['allowedHeaders']);
            $headers['Access-Control-Max-Age'] = $this->options['maxAge'];

            if (!empty($this->options['exposedHeaders'])) {
                $headers['Access-Control-Expose-Headers'] = implode(', ', $this->options['exposedHeaders']);
            }

            if ($this->options['supportsCredentials']) {
                $headers['Access-Control-Allow-Credentials'] = 'true';
            }

            $response = HttpResponse::json(null, 204);
            foreach ($headers as $key => $value) {
                $response = $response->withHeader($key, $value);
            }
            return $response;
        }

        // 正常请求
        if (!empty($this->options['exposedHeaders'])) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->options['exposedHeaders']);
        }

        if ($this->options['supportsCredentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        $response = $next($request);

        if ($response instanceof HttpResponse) {
            foreach ($headers as $key => $value) {
                $response = $response->withHeader($key, $value);
            }
            return $response;
        }

        // 原始数组响应
        if (!isset($response['headers'])) {
            $response['headers'] = [];
        }
        $response['headers'] = array_merge($headers, $response['headers']);

        return $response;
    }
}
