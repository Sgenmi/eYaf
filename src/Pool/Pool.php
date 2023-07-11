<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/4 19:15 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;

use RuntimeException;
use Sgenmi\eYaf\Contract\ConnectionInterface;
use Sgenmi\eYaf\Di\Container;
use Throwable;

abstract class Pool
{
    protected Container $container;
    private array $config=[];
    private Channel $channel;
    private int $currentConnections=0;

    private string $name='';
    /**
     * @throws \Exception
     */
    public function __construct(array $config,string $name="master")
    {
        $this->config = $config;
        $this->name = $name;
        $this->container = Container::getInstance();
        $this->channel =   new Channel($this->getMaxConnections());
    }

    public function get():ConnectionInterface{
        $connection = $this->getConnection();
        return $connection;
    }

    /**
     * @return int
     * @author Sgenmi
     */
    public function getCurrentConnections():int{
        return $this->currentConnections;
    }

    /**
     * @return int
     * @author Sgenmi
     */
    public function getChannelLength(): int
    {
        return $this->channel->length();
    }

    public function release(ConnectionInterface $connection): void
    {
        $this->channel->push($connection);
    }

    private function getConnection():ConnectionInterface{
        $num = $this->getChannelLength();
        try {
            if ($num === 0 && $this->currentConnections < $this->getMaxConnections()) {
                ++$this->currentConnections;
                return $this->createConnection();
            }
        } catch (Throwable $e) {
            --$this->currentConnections;
            throw $e;
        }

        $connection = $this->channel->pop($this->getWaitTimeout());
        if (! $connection instanceof ConnectionInterface) {
            throw new RuntimeException('Connection pool exhausted. Cannot establish new connection before wait_timeout.');
        }
        return $connection;
    }

    public function getConfig():array{
        return $this->config;
    }

    private function getMaxConnections():int{
       return intval($this->config['pool']['max_connections']??10);
    }

    private function getWaitTimeout():float{
        return floatval($this->config['pool']['wait_timeout']??3);
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract protected function createConnection();
}