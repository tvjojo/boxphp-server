# boxphp/server

BoxPHP 服务器包 - HTTP/TCP/WebSocket 服务器及中间件

## 安装

```bash
composer require boxphp/server
```

## 组件

### HttpServer HTTP 服务器

```php
use BoxPHP\Server\Http\Server\HttpServer;
use BoxPHP\Server\Http\Message\HttpResponse;

$server = new HttpServer('http://0.0.0.0:8080');

$server->get('/', function ($request) {
    return HttpResponse::json(['hello' => 'world']);
});

$server->get('/users/{id}', function ($request) {
    $id = $request['params']['id'];
    return HttpResponse::json(['id' => $id]);
});

$server->post('/users', function ($request) {
    return HttpResponse::json(['created' => true], 201);
});

$server->start();
```

### 路由分组

```php
$server->group('/api', function ($server) {
    $server->get('/users', fn() => HttpResponse::json(['users' => []]));
    $server->get('/posts', fn() => HttpResponse::json(['posts' => []]));
});
```

### 中间件

```php
use BoxPHP\Server\Middleware\AuthMiddleware;
use BoxPHP\Server\Middleware\CorsMiddleware;
use BoxPHP\Server\Middleware\RateLimitMiddleware;

// 认证中间件
$server->get('/profile', function ($request) {
    return HttpResponse::json($request['auth_user']);
})->middleware(new AuthMiddleware());

// CORS 中间件
$server->options('/api/{path}', function ($request) {
    return HttpResponse::json(['ok' => true]);
})->middleware(new CorsMiddleware());

// 限流中间件
$server->post('/submit', function ($request) {
    return HttpResponse::json(['submitted' => true]);
})->middleware(new RateLimitMiddleware(60, 60));
```

### TcpServer TCP 服务器

```php
use BoxPHP\Server\Tcp\TcpServer;

$server = new TcpServer('0.0.0.0:9000');

$server->onMessage(function ($connection, $data) {
    $connection->send("Echo: {$data}");
});

$server->start();
```

### WebSocketServer

```php
use BoxPHP\Server\WebSocket\WebSocketServer;

$server = new WebSocketServer('0.0.0.0:8080');

$server->onOpen(function ($connection) {
    echo "New connection\n";
});

$server->onMessage(function ($connection, $data) {
    $connection->send($data); // Echo
});

$server->start();
```

## 依赖

- PHP >= 8.1
- boxphp/core ^1.0
- workerman/workerman ^4.0
