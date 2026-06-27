<?php
/**
 * Connection 接口
 */
namespace BoxPHP\Server\Connection;

interface ConnectionInterface
{
    /**
     * 获取连接 ID
     */
    public function getId(): int;

    /**
     * 发送数据
     */
    public function send(mixed $data): bool;

    /**
     * 关闭连接
     */
    public function close(): void;

    /**
     * 获取远端地址
     */
    public function getRemoteAddress(): string;

    /**
     * 获取连接属性
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 设置连接属性
     */
    public function set(string $key, mixed $value): void;

    /**
     * 获取所有属性
     */
    public function all(): array;

    /**
     * 连接是否有效
     */
    public function isValid(): bool;

    /**
     * 获取最后活跃时间
     */
    public function getLastActivityTime(): int;
}