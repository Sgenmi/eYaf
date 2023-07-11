<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/4 4:58 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;

use PDOException;
use Sgenmi\eYaf\Contract\ConnectionInterface;
use Sgenmi\eYaf\Contract\StdoutLoggerInterface;
use Sgenmi\eYaf\Di\Container;
use Sgenmi\eYaf\Model\Medoo;
use Throwable;

class PDOConnection implements ConnectionInterface
{
    private Container $container;
    private array $config=[];
    protected ?Medoo $connection = null;
    protected float $lastUseTime = 0.0;

    protected Pool $pool;

    public function __construct(Container $container,  array $config, Pool $pool)
    {
        $this->container = $container;
        $this->config = $config;
        $this->pool = $pool;
        $this->reconnect();
    }

    public function getConnection():static
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
            $medoo = new Medoo($this->config);
        } catch (Throwable $e) {
            throw new PDOException('Connection reconnect failed.:' . $e->getMessage());
        }
        $this->connection = $medoo;
        return true;
    }

    public function check(): bool
    {
        $maxIdleTime = $this->config['pool']['max_idle_time']??10;
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

    /**
     * @return Medoo
     * @author Sgenmi
     */
    public function getMedoo():Medoo{
        return $this->connection;
    }

}