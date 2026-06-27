<?php
/**
 * Connection 连接实现
 */
namespace BoxPHP\Server\Connection;

class Connection implements ConnectionInterface
{
    protected int $id;
    protected string $remoteAddress;
    protected array $attributes = [];
    protected int $lastActivityTime;
    protected bool $valid = true;

    /** @var \Workerman\Connection\TcpConnection|null */
    protected ?\Workerman\Connection\TcpConnection $workermanConnection = null;

    public function __construct(int $id, string $remoteAddress = '', ?\Workerman\Connection\TcpConnection $conn = null)
    {
        $this->id = $id;
        $this->remoteAddress = $remoteAddress;
        $this->workermanConnection = $conn;
        $this->lastActivityTime = time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function send(mixed $data): bool
    {
        if (!$this->valid) {
            return false;
        }

        if ($this->workermanConnection) {
            $this->workermanConnection->send($data);
            $this->lastActivityTime = time();
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        $this->valid = false;

        if ($this->workermanConnection) {
            $this->workermanConnection->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRemoteAddress(): string
    {
        return $this->remoteAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return $this->valid && $this->workermanConnection !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastActivityTime(): int
    {
        return $this->lastActivityTime;
    }

    /**
     * 设置 Workerman 连接
     */
    public function setWorkermanConnection(\Workerman\Connection\TcpConnection $conn): void
    {
        $this->workermanConnection = $conn;
    }

    /**
     * 获取 Workerman 连接
     */
    public function getWorkermanConnection(): ?\Workerman\Connection\TcpConnection
    {
        return $this->workermanConnection;
    }

    /**
     * 更新活跃时间
     */
    public function touch(): void
    {
        $this->lastActivityTime = time();
    }
}