<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/4 18:34 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Pool;


use RuntimeException;
use Sgenmi\eYaf\Contract\ConnectionInterface;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel as CoChannel;
class Channel
{
    protected CoChannel $channel;

    /**
     * @throws \Exception
     */
    public function __construct(int $size)
    {
        $this->isCoroutine();
        $this->channel = new CoChannel($size);
    }

    public function pop(float $timeout): ConnectionInterface|false
    {
        return $this->channel->pop($timeout);
    }

    public function push(ConnectionInterface $obj): bool
    {
        return $this->channel->push($obj);
    }

    public function length(): int
    {
        return $this->channel->length();
    }

    protected function isCoroutine(): bool
    {
        if( extension_loaded('swoole') && (Coroutine::getCid()>0)){
            return true;
        }
        throw new RuntimeException("run only in coroutines");
    }

}