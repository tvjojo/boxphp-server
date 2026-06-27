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
 * WorkerManager 管理器
 */
namespace BoxPHP\Server\Worker;

class WorkerManager
{
    /** @var array<string, WorkerInterface> */
    protected array $workers = [];

    /**
     * 添加 Worker
     */
    public function add(WorkerInterface $worker): void
    {
        $this->workers[$worker->getName()] = $worker;
    }

    /**
     * 移除 Worker
     */
    public function remove(string $name): void
    {
        unset($this->workers[$name]);
    }

    /**
     * 获取 Worker
     */
    public function get(string $name): ?WorkerInterface
    {
        return $this->workers[$name] ?? null;
    }

    /**
     * 获取所有 Worker
     */
    public function all(): array
    {
        return $this->workers;
    }

    /**
     * 启动所有 Worker
     */
    public function startAll(): void
    {
        foreach ($this->workers as $worker) {
            $worker->start();
        }
    }

    /**
     * 停止所有 Worker
     */
    public function stopAll(): void
    {
        foreach ($this->workers as $worker) {
            $worker->stop();
        }
    }

    /**
     * 获取统计信息
     */
    public function getStats(): array
    {
        $stats = [];
        foreach ($this->workers as $name => $worker) {
            $stats[$name] = $worker->getStats();
        }
        return $stats;
    }

    /**
     * Worker 数量
     */
    public function count(): int
    {
        return count($this->workers);
    }
}