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
 * Worker 工作进程
 */
namespace BoxPHP\Server\Worker;

use Workerman\Worker as Workerman;

class Worker implements WorkerInterface
{
    protected string $name;
    protected string $listen;
    protected int $count;
    protected bool $running = false;

    /** @var callable|null */
    protected $messageHandler = null;
    protected $startHandler = null;
    protected $stopHandler = null;
    protected $connectHandler = null;
    protected $closeHandler = null;

    protected ?Workerman $workerman = null;

    public function __construct(string $listen, int $count = 1, string $name = '')
    {
        $this->listen = $listen;
        $this->count = PHP_OS_FAMILY === 'Windows' ? 1 : $count;
        $this->name = $name ?: 'Worker-' . $this->getProtocol();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getListen(): string
    {
        return $this->listen;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
        $this->workerman = new \Workerman\Worker($this->listen);
        $this->workerman->name = $this->name;
        $this->workerman->count = $this->count;

        // 设置回调
        if ($this->startHandler) {
            $this->workerman->onWorkerStart = $this->startHandler;
        }

        if ($this->messageHandler) {
            $this->workerman->onMessage = $this->messageHandler;
        }

        if ($this->connectHandler) {
            $this->workerman->onConnect = $this->connectHandler;
        }

        if ($this->closeHandler) {
            $this->workerman->onClose = $this->closeHandler;
        }

        if ($this->stopHandler) {
            $this->workerman->onWorkerStop = $this->stopHandler;
        }

        $this->running = true;
        \Workerman\Worker::runAll();
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
        if ($this->workerman) {
            $this->workerman->stop();
            $this->running = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restart(): void
    {
        $this->stop();
        sleep(1);
        $this->start();
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(callable $handler): static
    {
        $this->messageHandler = $handler;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(callable $handler): static
    {
        $this->startHandler = $handler;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onStop(callable $handler): static
    {
        $this->stopHandler = $handler;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onConnect(callable $handler): static
    {
        $this->connectHandler = $handler;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(callable $handler): static
    {
        $this->closeHandler = $handler;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        return [
            'name'      => $this->name,
            'listen'    => $this->listen,
            'count'     => $this->count,
            'running'   => $this->running,
            'protocol'  => $this->getProtocol(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * 获取协议类型
     */
    protected function getProtocol(): string
    {
        if (str_contains($this->listen, '://')) {
            $parts = explode('://', $this->listen);
            return $parts[0];
        }
        return 'tcp';
    }

    /**
     * 获取底层 Worker 实例
     */
    public function getWorkerman(): ?\Workerman\Worker
    {
        return $this->workerman;
    }
}