<?php

/**
 * Author: Sgenmi
 * Date: 2023/6/30 19:02 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf;

use Swoole\Coroutine;

class Context
{
    private static array $context = [];

    public static function set(string $id, mixed $value, ?int $coroutineId = null): mixed
    {
        if (self::isSwooleCo()) {
            Coroutine::getContext($coroutineId)[$id] = $value;
        } else {
            static::$context[$id] = $value;
        }
        return $value;
    }

    public static function get(string $id, mixed $default = null, ?int $coroutineId = null): mixed
    {
        if (self::isSwooleCo()) {
            return Coroutine::getContext($coroutineId)[$id] ?? $default;
        }
        return static::$context[$id] ?? $default;
    }

    public static function has(string $id, ?int $coroutineId = null): bool
    {
        if (self::isSwooleCo()) {
            return isset(Coroutine::getContext($coroutineId)[$id]);
        }
        return isset(static::$context[$id]);
    }


    public static function destroy(string $id, ?int $coroutineId = null): void
    {
        if (self::isSwooleCo()) {
            unset(Coroutine::getContext($coroutineId)[$id]);
        }
        unset(static::$context[$id]);
    }


    public static function getContainer(?int $coroutineId = null)
    {
        if (self::isSwooleCo()) {
            if(is_null($coroutineId)){
                return Coroutine::getContext();
            }
            return Coroutine::getContext($coroutineId);
        }
        return static::$context;
    }

    private static function isSwooleCo():bool{
        return extension_loaded('swoole') && (Coroutine::getCid()>0);
    }

}