<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/4 3:00 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;

class RedisPool extends Pool
{
    /**
     * @return RedisConnection
     * @author Sgenmi
     */
    protected function createConnection():RedisConnection
    {
        return new RedisConnection($this->container,$this->getConfig(),$this);
    }
}
