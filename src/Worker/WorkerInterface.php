<?php
/**
 * Worker 接口
 */
namespace BoxPHP\Server\Worker;

interface WorkerInterface
{
    /**
     * 获取 Worker 名称
     */
    public function getName(): string;

    /**
     * 获取监听地址
     */
    public function getListen(): string;

    /**
     * 获取进程数
     */
    public function getCount(): int;

    /**
     * 启动 Worker
     */
    public function start(): void;

    /**
     * 停止 Worker
     */
    public function stop(): void;

    /**
     * 重启 Worker
     */
    public function restart(): void;

    /**
     * 注册消息处理器
     */
    public function onMessage(callable $handler): static;

    /**
     * 注册启动回调
     */
    public function onStart(callable $handler): static;

    /**
     * 注册停止回调
     */
    public function onStop(callable $handler): static;

    /**
     * 注册连接回调
     */
    public function onConnect(callable $handler): static;

    /**
     * 注册关闭回调
     */
    public function onClose(callable $handler): static;

    /**
     * 获取统计信息
     */
    public function getStats(): array;

    /**
     * 是否正在运行
     */
    public function isRunning(): bool;
}