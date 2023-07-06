<?php

/**
 * Author: Sgenmi
 * Date: 2023/7/4 2:54 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Contract;

interface ConnectionInterface
{
    public function getConnection();

    public function reconnect(): bool;

    public function check(): bool;

    public function close(): bool;

}