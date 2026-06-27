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
 * TcpServer TCP 服务器
 * 基于 Workerman 的 TCP 长连接服务器
 */
namespace BoxPHP\Server\Tcp;

use BoxPHP\Core\Config\ConfigRepository;
use BoxPHP\Core\Container\Container;
use BoxPHP\Core\Event\EventDispatcher;
use BoxPHP\Core\Middleware\Pipeline;
use Workerman\Worker;

class TcpServer
{
    protected Worker $worker;
    protected ConfigRepository $config;
    protected Container $container;
    protected EventDispatcher $events;
    /** @var callable|null */
    protected $messageHandler = null;
    /** @var callable|null */
    protected $connectHandler = null;
    /** @var callable|null */
    protected $closeHandler = null;

    /** @var array<int, array> 连接上下文 */
    protected array $connections = [];

    public function __construct(
        string $listen = '0.0.0.0:23000',
        int $count = 1
    ) {
        $this->config = new ConfigRepository();
        $this->container = new Container();
        $this->events = new EventDispatcher();

        $count = PHP_OS_FAMILY === 'Windows' ? 1 : $count;
        $this->worker = new Worker($listen);
        $this->worker->count = $count;
        $this->worker->name = 'BoxTCP';

        $this->registerWorkerCallbacks();
    }

    protected function registerWorkerCallbacks(): void
    {
        $server = $this;

        $this->worker->onMessage = function ($connection, $data) use ($server) {
            $connId = (int)$connection->id;
            $context = $server->connections[$connId] ?? [];

            if ($server->messageHandler) {
                ($server->messageHandler)($connection, $data, $context);
            }

            $server->events->emit('tcp.message', [
                'connection' => $connection,
                'data' => $data,
                'context' => $context,
            ]);
        };

        $this->worker->onConnect = function ($connection) use ($server) {
            $connId = (int)$connection->id;
            $server->connections[$connId] = [
                'connected_at' => time(),
                'ip' => $connection->getRemoteIp(),
                'port' => $connection->getRemotePort(),
            ];

            if ($server->connectHandler) {
                ($server->connectHandler)($connection);
            }

            $server->events->emit('tcp.connect', [
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

            $server->events->emit('tcp.close', [
                'connection' => $connection,
                'context' => $context,
            ]);
        };

        $this->worker->onWorkerStart = function ($worker) use ($server) {
            echo "BoxTCP server started at {$worker->listen}\n";
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

    public function config(): ConfigRepository { return $this->config; }
    public function container(): Container { return $this->container; }
    public function events(): EventDispatcher { return $this->events; }

    /**
     * 获取连接上下文
     */
    public function getConnectionContext($connection): ?array
    {
        return $this->connections[(int)$connection->id] ?? null;
    }

    /**
     * 设置连接上下文
     */
    public function setConnectionContext($connection, array $data): void
    {
        $connId = (int)$connection->id;
        if (isset($this->connections[$connId])) {
            $this->connections[$connId] = array_merge($this->connections[$connId], $data);
        }
    }

    /**
     * 获取在线连接数
     */
    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    public function start(): void
    {
        Worker::runAll();
    }
}
