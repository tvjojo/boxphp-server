<?php
/**
 * BoxPHP Framework
 *
 * Copyright 2026 BoxPHP
 * By tvjojo, asterhuang, 黄波涛; 5viv.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * HttpServer HTTP 服务器
 */
namespace BoxPHP\Server\Http\Server;

use BoxPHP\Core\Config\ConfigRepository;
use BoxPHP\Core\Container\Container;
use BoxPHP\Core\Event\EventDispatcher;
use BoxPHP\Core\Middleware\Pipeline;
use BoxPHP\Server\Middleware\LogMiddleware;
use BoxPHP\Server\Middleware\CorsMiddleware;
use BoxPHP\Server\Http\Message\HttpRequest;
use BoxPHP\Server\Http\Message\HttpResponse;
use Workerman\Worker;

class HttpServer
{
    protected Worker $worker;
    protected Pipeline $pipeline;
    protected ConfigRepository $config;
    protected Container $container;
    protected EventDispatcher $events;
    protected array $routes = [];

    public function __construct(
        string $listen = 'http://0.0.0.0:8080',
        int $count = 1
    ) {
        $this->config = new ConfigRepository();
        $this->container = new Container();
        $this->events = new EventDispatcher();
        $this->pipeline = new Pipeline();

        $count = PHP_OS_FAMILY === 'Windows' ? 1 : $count;
        $this->worker = new Worker($listen);
        $this->worker->count = $count;
        $this->worker->name = 'BoxHTTP';

        $this->registerDefaultMiddleware();
        $this->registerWorkerCallbacks();
    }

    protected function registerDefaultMiddleware(): void
    {
        $this->pipeline->pipe(new CorsMiddleware());
    }

    protected function registerWorkerCallbacks(): void
    {
        $server = $this;

        $this->worker->onMessage = function ($connection, $request) use ($server) {
            $server->handleRequest($connection, $request);
        };

        $this->worker->onStart = function ($worker) use ($server) {
            echo "BoxHTTP server started at {$worker->listen}\n";
            $server->events->emit('server.start', $worker);
        };
    }

    public function handleRequest($connection, $workerRequest): void
    {
        $httpRequest = HttpRequest::fromWorkerman($workerRequest);

        $result = $this->pipeline->run($httpRequest->toArray(), function (array $request) use ($connection) {
            return $this->dispatch($request);
        });

        if ($result instanceof HttpResponse) {
            $result->send($connection);
        } else {
            HttpResponse::json($result)->send($connection);
        }
    }

    public function dispatch(array $request): HttpResponse
    {
        $method = $request['method'];
        $path = $request['path'];

        // 精确匹配
        $key = $method . ' ' . $path;
        if (isset($this->routes[$key])) {
            return ($this->routes[$key])($request);
        }

        // 模式匹配 /users/{id}
        foreach ($this->routes as $routeKey => $handler) {
            $spacePos = strpos($routeKey, ' ');
            if ($spacePos === false) continue;
            $routeMethod = substr($routeKey, 0, $spacePos);
            $routePath = substr($routeKey, $spacePos + 1);

            if ($routeMethod !== $method) continue;

            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                $request['params'] = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $handler($request);
            }
        }

        return HttpResponse::error(404, "Route not found: {$method} {$path}");
    }

    /**
     * 通过管道执行请求（测试用）
     */
    public function dispatchWithPipeline(array $request): HttpResponse
    {
        return $this->pipeline->run($request, function (array $request) {
            return $this->dispatch($request);
        });
    }

    public function get(string $path, callable $handler): static
    {
        $this->routes['GET ' . $path] = $handler;
        return $this;
    }

    public function post(string $path, callable $handler): static
    {
        $this->routes['POST ' . $path] = $handler;
        return $this;
    }

    public function put(string $path, callable $handler): static
    {
        $this->routes['PUT ' . $path] = $handler;
        return $this;
    }

    public function delete(string $path, callable $handler): static
    {
        $this->routes['DELETE ' . $path] = $handler;
        return $this;
    }

    public function any(string $path, callable $handler): static
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $this->routes[$method . ' ' . $path] = $handler;
        }
        return $this;
    }

    public function group(string $prefix, callable $callback): static
    {
        $previousRoutes = $this->routes;
        $this->routes = [];
        $callback($this);
        $groupedRoutes = $this->routes;
        $this->routes = $previousRoutes;

        foreach ($groupedRoutes as $key => $handler) {
            // 路由键格式: "METHOD /path" -> "METHOD /prefix/path"
            $spacePos = strpos($key, ' ');
            if ($spacePos !== false) {
                $method = substr($key, 0, $spacePos);
                $path = substr($key, $spacePos + 1);
                $this->routes[$method . ' ' . $prefix . $path] = $handler;
            } else {
                $this->routes[$prefix . $key] = $handler;
            }
        }
        return $this;
    }

    public function pipe(\BoxPHP\Core\Middleware\MiddlewareInterface $middleware): static
    {
        $this->pipeline->pipe($middleware);
        return $this;
    }

    public function config(): ConfigRepository { return $this->config; }
    public function container(): Container { return $this->container; }
    public function events(): EventDispatcher { return $this->events; }

    public function start(): void
    {
        Worker::runAll();
    }
}
