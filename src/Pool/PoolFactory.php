<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/4 6:55 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;

use RuntimeException;
use Sgenmi\eYaf\Contract\ConfigInterface;
use Sgenmi\eYaf\Di\Container;

class PoolFactory
{
    protected array $pools = [];

    protected Container $container;
    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    public function getPool(string $name,string $type="database" ): Pool
    {
        $_name = $type.'.'.$name;
        if (isset($this->pools[$_name])) {
            return $this->pools[$_name];
        }
        $configObj = $this->container->get(ConfigInterface::class);
        $driver="";
        $config=[];
        if(empty($type) || $type=='database'){
            $config = $configObj->get('database.params')[$name]??[];
            $driver = $config['type']??'';
        }elseif($type=='redis'){
            $config = $configObj->get('redis')[$name]??[];
            $driver = !empty($config)?$name:'';
        }
        $class = $this->getPoolClass($driver);
        //这里可以完成fpm获取实例
        $pool = new $class($config,$name);

        if (! $pool instanceof Pool) {
            throw new RuntimeException(sprintf('Driver %s is not invalid.', $driver));
        }
        return $this->pools[$_name] = $pool;
    }

    protected function getPoolClass(string $driver): string
    {
        return match (strtolower($driver)) {
            'mysql', 'pgsql', 'pdo' ,'sybase','oracle'=> MedooPool::class,
            'redis'=>RedisPool::class,
            default => class_exists($driver) ? $driver : throw new RuntimeException(sprintf('Driver %s is not found.', $driver)),
        };
    }

}