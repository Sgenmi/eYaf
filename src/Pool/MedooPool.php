<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/4 3:00 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;

class MedooPool extends Pool
{
    /**
     * @return PDOConnection
     * @author Sgenmi
     */
    protected function createConnection(): PDOConnection
    {
        return new PDOConnection($this->container,$this->getConfig(),$this);
    }
}