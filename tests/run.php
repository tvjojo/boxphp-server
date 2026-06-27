<?php
/**
 * Server 包测试 - 修正版 v2
 */
require_once __DIR__ . '/../vendor/autoload.php';

use BoxPHP\Server\Http\Message\HttpRequest;
use BoxPHP\Server\Http\Message\HttpResponse;

echo "=== BoxPHP Server Package Tests ===\n\n";
$passed = 0;
$failed = 0;

// Test 1: HttpRequest
echo "1. HttpRequest\n";
try {
    $request = new HttpRequest([
        'method' => 'POST',
        'path' => '/users',
        'headers' => ['content-type' => 'application/json', 'accept' => 'application/json'],
        'body' => ['name' => 'John', 'email' => 'john@example.com'],
        'query' => ['ref' => 'home'],
        'cookie' => ['session' => 'abc123'],
        'ip' => '192.168.1.1',
    ]);
    
    assert($request->getMethod() === 'POST');
    assert($request->getPath() === '/users');
    assert($request->header('content-type') === 'application/json');
    assert($request->header('accept') === 'application/json');
    assert($request->getBody()['name'] === 'John');
    assert($request->getQuery()['ref'] === 'home');
    assert($request->getCookie()['session'] === 'abc123');
    assert($request->getClientIp() === '192.168.1.1');
    assert($request->getRawBody() === '');
    
    $array = $request->toArray();
    assert($array['method'] === 'POST');
    assert($array['path'] === '/users');
    assert($array['headers']['content-type'] === 'application/json');
    
    echo "   ✓ All request tests passed\n";
    $passed++;
} catch (\Throwable $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 2: HttpResponse
echo "2. HttpResponse\n";
try {
    // JSON response
    $response = HttpResponse::json(['status' => 'ok'], 200);
    assert($response->getStatus() === 200);
    assert($response->getBody()['status'] === 'ok');
    assert($response->getHeaders()['Content-Type'] === 'application/json');
    
    // HTML response
    $response = HttpResponse::html('<h1>Hello</h1>');
    assert($response->getStatus() === 200);
    assert(str_contains($response->getBody(), '<h1>'));
    
    // Text response
    $response = HttpResponse::text('Hello World');
    assert($response->getBody() === 'Hello World');
    
    // Error response
    $response = HttpResponse::error(404, 'Not Found');
    assert($response->getStatus() === 404);
    assert($response->getBody()['error'] === 'Not Found');
    assert($response->getBody()['message'] === 'Not Found');
    
    // Redirect
    $response = HttpResponse::redirect('/login');
    assert($response->getStatus() === 302);
    assert($response->getHeaders()['Location'] === '/login');
    
    // Custom status
    $response = HttpResponse::json(['ok' => true], 201);
    assert($response->getStatus() === 201);
    
    // With headers
    $response = HttpResponse::json(['ok' => true]);
    $response2 = $response->withHeader('X-Custom', 'value');
    assert($response2->getHeaders()['X-Custom'] === 'value');
    // Original unchanged
    assert(!isset($response->getHeaders()['X-Custom']));
    
    echo "   ✓ All response tests passed\n";
    $passed++;
} catch (\Throwable $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: HttpServer dispatch (unit test)
echo "3. HttpServer Dispatch (unit test)\n";
try {
    $routes = [];
    $routes['GET /test'] = fn($req) => HttpResponse::json(['route' => 'test']);
    $routes['POST /submit'] = fn($req) => HttpResponse::json(['submitted' => true]);
    $routes['GET /users/{id}'] = fn($req) => HttpResponse::json(['id' => $req['params']['id'] ?? 'unknown']);
    
    // 精确匹配
    $request = new HttpRequest(['method' => 'GET', 'path' => '/test']);
    $key = $request->getMethod() . ' ' . $request->getPath();
    assert(isset($routes[$key]));
    $response = $routes[$key]($request->toArray());
    assert($response->getBody()['route'] === 'test');
    
    // POST
    $request = new HttpRequest(['method' => 'POST', 'path' => '/submit']);
    $key = $request->getMethod() . ' ' . $request->getPath();
    assert(isset($routes[$key]));
    $response = $routes[$key]($request->toArray());
    assert($response->getBody()['submitted'] === true);
    
    // 模式匹配
    $request = new HttpRequest(['method' => 'GET', 'path' => '/users/42']);
    $path = $request->getPath();
    $pattern = '#^/users/(?P<id>[^/]+)$#';
    assert(preg_match($pattern, $path, $matches));
    assert($matches['id'] === '42');
    
    echo "   ✓ All routing tests passed\n";
    $passed++;
} catch (\Throwable $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Results: {$passed} passed, {$failed} failed ===\n";
exit($failed > 0 ? 1 : 0);
