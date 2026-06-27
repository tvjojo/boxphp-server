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
