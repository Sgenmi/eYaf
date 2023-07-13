<?php
declare(strict_types=1);
/**
 * Author: sgenmi
 * Date: 2023/7/11 23:08
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;

use Sgenmi\eYaf\Contract\ConnectionInterface;
use Sgenmi\eYaf\Contract\StdoutLoggerInterface;
use Sgenmi\eYaf\Di\Container;
use Throwable;

class RedisConnection implements ConnectionInterface
{
    private Container $container;
    private array $config = [];
    protected \Redis $connection = null;
    protected float $lastUseTime = 0.0;

    protected Pool $pool;

    public function __construct(Container $container, array $config, Pool $pool)
    {
        $this->container = $container;
        $this->config = $config;
        $this->pool = $pool;
        $this->reconnect();
    }

    public function getConnection(): static
    {
        try {
            return $this->getActiveConnection();
        } catch (Throwable $e) {
            if ($this->container->has(StdoutLoggerInterface::class) && $logger = $this->container->get(StdoutLoggerInterface::class)) {
                $logger->warning('Get connection failed, try again. ' . $e);
            }
        }
        return $this->getActiveConnection();
    }

    private function getActiveConnection(): static
    {
        if ($this->check()) {
            return $this;
        }
        $this->reconnect();
        return $this;
    }

    public function reconnect(): bool
    {
        try {
            $redis = new \Redis();
            $redis->connect($this->config['host'],intval($this->config['port']));
            if(!empty($this->config['auth'])){
                $redis->auth($this->config['auth']);
            }
            if(!empty($this->config['db'])){
                $redis->select(intval($this->config['db']));
            }
        } catch (Throwable $e) {
            throw new \RedisException('Connection reconnect failed.:' . $e->getMessage());
        }
        $this->connection = $redis;
        return true;
    }

    public function check(): bool
    {
        $maxIdleTime = $this->config['pool']['max_idle_time'] ?? 10;
        $now = microtime(true);
        if ($now > $maxIdleTime + $this->lastUseTime) {
            return false;
        }
        $this->lastUseTime = $now;
        return true;
    }

    public function close(): bool
    {
        unset($this->connection);
        return true;
    }

    public function release(): void
    {
        $this->pool->release($this);
    }

}
