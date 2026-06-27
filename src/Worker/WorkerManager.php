<?php
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