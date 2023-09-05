<?php
declare(strict_types=1);
/**
 * Author: Sgenmi
 * Date: 2023/7/3 18:41 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Contract;

/**
 * @method mixed get(string $key, mixed $default='')
 * @method bool set(string $key,mixed $val)
 */

interface ConfigInterface
{
    /**
     * @param string $key
     * @param mixed $val
     * @return bool
     * @author Sgenmi
     */
    public static function set(string $key,mixed $val):bool;

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @author Sgenmi
     */
    public static function get(string $key, mixed $default=''):mixed;
}