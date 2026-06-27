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
 * WebSocketServer WebSocket 服务器
 */
namespace BoxPHP\Server\WebSocket;

use BoxPHP\Core\Config\ConfigRepository;
use BoxPHP\Core\Container\Container;
use BoxPHP\Core\Event\EventDispatcher;
use Workerman\Worker;
use Workerman\Protocols\Http\Request;

class WebSocketServer
{
    protected Worker $worker;
    protected ConfigRepository $config;
    protected Container $container;
    protected EventDispatcher $events;

    /** @var callable|null */
    protected $messageHandler = null;
    protected $connectHandler = null;
    protected $closeHandler = null;

    /** @var array<int, array> 连接上下文 */
    protected array $connections = [];

    public function __construct(
        string $listen = '0.0.0.0:23001',
        int $count = 1
    ) {
        $this->config = new ConfigRepository();
        $this->container = new Container();
        $this->events = new EventDispatcher();

        $count = PHP_OS_FAMILY === 'Windows' ? 1 : $count;
        $this->worker = new Worker($listen);
        $this->worker->count = $count;
        $this->worker->name = 'BoxWS';
        $this->worker->transport = 'websocket';

        $this->registerWorkerCallbacks();
    }

    protected function registerWorkerCallbacks(): void
    {
        $server = $this;

        $this->worker->onMessage = function ($connection, $data) use ($server) {
            $connId = (int)$connection->id;
            $context = $server->connections[$connId] ?? [];

            // 尝试 JSON 解码
            $message = json_decode($data, true);
            if ($message === null) {
                $message = ['type' => 'text', 'content' => $data];
            }

            if ($server->messageHandler) {
                $result = ($server->messageHandler)($connection, $message, $context);
                if ($result !== null) {
                    $connection->send(is_string($result) ? $result : json_encode($result));
                }
            }

            $server->events->emit('ws.message', [
                'connection' => $connection,
                'message' => $message,
                'context' => $context,
            ]);
        };

        $this->worker->onConnect = function ($connection) use ($server) {
            $connId = (int)$connection->id;
            $server->connections[$connId] = [
                'connected_at' => time(),
                'ip' => $connection->getRemoteIp(),
                'port' => $connection->getRemotePort(),
                'user_id' => null,
            ];

            if ($server->connectHandler) {
                ($server->connectHandler)($connection);
            }

            $server->events->emit('ws.connect', [
                'connection' => $connection,
                'context' => $server->connections[$connId],
            ]);
        };

        $this->worker->onClose = function ($connection) use ($server) {
            $connId = (int)$connection->id;
            $context = $server->connections[$connId] ?? [];
            unset($server->connections[$connId]);

            if ($server->closeHandler) {
                ($server->closeHandler)($connection);
            }

            $server->events->emit('ws.close', [
                'connection' => $connection,
                'context' => $context,
            ]);
        };

        $this->worker->onWorkerStart = function ($worker) use ($server) {
            echo "BoxWS server started at {$worker->listen}\n";
            $server->events->emit('server.start', $worker);
        };
    }

    public function onMessage(callable $handler): static
    {
        $this->messageHandler = $handler;
        return $this;
    }

    public function onConnect(callable $handler): static
    {
        $this->connectHandler = $handler;
        return $this;
    }

    public function onClose(callable $handler): static
    {
        $this->closeHandler = $handler;
        return $this;
    }

    /**
     * 广播消息给所有连接
     */
    public function broadcast(mixed $data): void
    {
        $message = is_string($data) ? $data : json_encode($data);
        foreach ($this->worker->connections as $connection) {
            $connection->send($message);
        }
    }

    /**
     * 发送给指定用户
     */
    public function sendTo(string $userId, mixed $data): bool
    {
        foreach ($this->connections as $connId => $context) {
            if (($context['user_id'] ?? null) === $userId) {
                $connection = $this->worker->connections[$connId] ?? null;
                if ($connection) {
                    $connection->send(is_string($data) ? $data : json_encode($data));
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 设置用户 ID
     */
    public function setUserId($connection, string $userId): void
    {
        $connId = (int)$connection->id;
        if (isset($this->connections[$connId])) {
            $this->connections[$connId]['user_id'] = $userId;
        }
    }

    /**
     * 获取在线连接数
     */
    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    /**
     * 获取所有在线用户 ID
     */
    public function getOnlineUserIds(): array
    {
        return array_unique(array_filter(array_column($this->connections, 'user_id')));
    }

    public function config(): ConfigRepository { return $this->config; }
    public function container(): Container { return $this->container; }
    public function events(): EventDispatcher { return $this->events; }

    public function start(): void
    {
        Worker::runAll();
    }
}
