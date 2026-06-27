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
 * AuthMiddleware 认证中间件
 */
namespace BoxPHP\Server\Middleware;

use BoxPHP\Server\Http\Message\HttpResponse;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var callable|null 验证回调
     */
    protected $validator = null;

    /**
     * @param callable|null $validator 验证函数，接收 token 返回 bool
     */
    public function __construct(?callable $validator = null)
    {
        $this->validator = $validator;
    }

    public function handle(mixed $request, callable $next): mixed
    {
        $headers = $request['headers'] ?? [];

        // 从 header 或 cookie 获取 token
        $token = $headers['authorization']
            ?? $headers['x-auth-token']
            ?? $request['cookie']['token']
            ?? null;

        if ($token === null) {
            return HttpResponse::error(401, 'Missing token');
        }

        // 去掉 Bearer 前缀
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        // 自定义验证
        if ($this->validator !== null) {
            $result = ($this->validator)($token);
            if ($result === false) {
                return HttpResponse::error(401, 'Invalid token');
            }
            $request['auth_user'] = $result;
        } else {
            // 默认验证：非空即通过（实际项目应替换为 JWT 验证）
            $request['auth_user'] = ['token' => $token];
        }

        $request['token'] = $token;

        return $next($request);
    }
}
