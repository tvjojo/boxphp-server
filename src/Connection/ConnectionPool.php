<?php
/**
 * ConnectionPool 连接池
 */
namespace BoxPHP\Server\Connection;

class ConnectionPool
{
    /** @var array<int, ConnectionInterface> 所有连接 */
    protected array $connections = [];

    /** @var array<int, array<int, ConnectionInterface>> 用户ID => 连接列表 (多设备) */
    protected array $userConnections = [];

    /** @var array<int, int> 连接ID => 用户ID */
    protected array $connectionToUser = [];

    /**
     * 添加连接
     */
    public function add(ConnectionInterface $conn): void
    {
        $this->connections[$conn->getId()] = $conn;
    }

    /**
     * 移除连接
     */
    public function remove(ConnectionInterface $conn): void
    {
        $connId = $conn->getId();
        $userId = $this->connectionToUser[$connId] ?? null;

        unset($this->connections[$connId]);

        if ($userId !== null) {
            unset($this->userConnections[$userId][$connId]);
            if (empty($this->userConnections[$userId])) {
                unset($this->userConnections[$userId]);
            }
            unset($this->connectionToUser[$connId]);
        }
    }

    /**
     * 绑定用户
     */
    public function bindUser(int $userId, ConnectionInterface $conn): void
    {
        $this->add($conn);
        $this->userConnections[$userId][$conn->getId()] = $conn;
        $this->connectionToUser[$conn->getId()] = $userId;
    }

    /**
     * 获取连接
     */
    public function get(int $connId): ?ConnectionInterface
    {
        return $this->connections[$connId] ?? null;
    }

    /**
     * 获取用户的所有连接
     */
    public function getByUserId(int $userId): array
    {
        return $this->userConnections[$userId] ?? [];
    }

    /**
     * 获取用户 ID
     */
    public function getUserId(int $connId): ?int
    {
        return $this->connectionToUser[$connId] ?? null;
    }

    /**
     * 检查用户是否在线
     */
    public function isOnline(int $userId): bool
    {
        return !empty($this->userConnections[$userId]);
    }

    /**
     * 向用户发送 (所有设备)
     */
    public function sendToUser(int $userId, mixed $data): int
    {
        $sent = 0;
        foreach ($this->userConnections[$userId] ?? [] as $conn) {
            if ($conn->send($data)) {
                $sent++;
            }
        }
        return $sent;
    }

    /**
     * 广播给所有连接
     */
    public function broadcast(mixed $data, ?int $excludeUserId = null): int
    {
        $sent = 0;
        foreach ($this->userConnections as $userId => $connections) {
            if ($userId === $excludeUserId) {
                continue;
            }
            foreach ($connections as $conn) {
                if ($conn->send($data)) {
                    $sent++;
                }
            }
        }
        return $sent;
    }

    /**
     * 获取在线用户数
     */
    public function getOnlineCount(): int
    {
        return count($this->userConnections);
    }

    /**
     * 获取总连接数
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
        return array_keys($this->userConnections);
    }

    /**
     * 清理无效连接
     */
    public function gc(int $timeout = 60): int
    {
        $removed = 0;
        $now = time();

        foreach ($this->connections as $connId => $conn) {
            if (!$conn->isValid() || ($now - $conn->getLastActivityTime()) > $timeout) {
                $this->remove($conn);
                $removed++;
            }
        }

        return $removed;
    }
}