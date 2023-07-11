<?php
declare(strict_types=1);
/**
 * Author: sgenmi
 * Date: 2023/7/11 23:52
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;

use Sgenmi\eYaf\Di\Container;

/**
 * @mixin \Redis
 */
class Redis
{
    protected $name="app";
    public function __construct()
    {
    }

    public function __call($name, $arguments)
    {

    }

    private function getConnection():RedisConnection{
       $connect =  Container::getInstance()->get(PoolFactory::class)->getPool($this->name,'redis')->get()->getConnection();
       return $connect;
    }

}
