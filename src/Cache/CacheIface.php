<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/27 23:58
 */

namespace Sgenmi\eYaf\Cache;


interface CacheIface
{
    public function set(string $key,  $value = NULL, int $time = 0);
    public function get(string $key);
    public function del(string $key);
    public function exists(string $key);
    public function expire(string $key, int $time = 86400);

}