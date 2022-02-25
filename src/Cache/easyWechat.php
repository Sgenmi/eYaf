<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2022/1/21 10:08 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Cache;

use Psr\SimpleCache\CacheInterface;
use InvalidArgumentException;

class easyWechat implements CacheInterface
{
    private $configDefault = 'app';
    /**
     * @var \Redis;
     */
    private $redis = null;

    public function __construct($configDefault = 'app' ){
        $this->configDefault = $configDefault;
    }
    /**
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        $redis = $this->getRedis();
        $val = $redis->get($key);
        return $val ?unserialize($val):$default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $redis = $this->getRedis();
        $redis->sadd('s:all:wechat:cache', $key);
        $value = serialize($value);
        return $redis->set($key,$value,$ttl);
    }

    /**
     * @param string $key
     * @return bool|int
     */
    public function delete($key)
    {
        $redis = $this->getRedis();
        return $redis->del($key);
    }

    /**
     * @return bool
     */
    public function clear()
    {
        $redis = $this->getRedis();
        $keys = $redis->sMembers('s:all:wechat:cache');
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @param null $default
     * @return iterable|null
     */
    public function getMultiple($keys, $default = null)
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!\is_array($keys)) {
            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given.', get_debug_type($keys)));
        }
        $redis = $this->getRedis();
        $val = $redis->mget($keys);
        $ret=[];
        foreach ($keys as $k=>$v){
            $_v = $default;
            if(isset($val[$k])){
                $_v = unserialize($val[$k]);
            }
            $ret[$v]=$_v;
        }
        return $ret;
    }

    /**
     * @param iterable $values
     * @param null $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new InvalidArgumentException(sprintf('Cache values must be array or Traversable, "%s" given.', get_debug_type($values)));
        }
        foreach ($values as $key => $value) {
            $this->set($key, $values, $ttl);
        }
        return true;
    }

    /**
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple($keys)
    {
        if ($keys instanceof \Traversable) {
            $keys = iterator_to_array($keys, false);
        } elseif (!is_array($keys)) {
            throw new InvalidArgumentException(sprintf('Cache keys must be array or Traversable, "%s" given.', get_debug_type($keys)));
        }
        foreach ($keys as $key => $value) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * @param string $key
     * @return bool|int
     */
    public function has($key)
    {
        $redis = $this->getRedis();
        return $redis->exists($key);
    }

    public function getRedis():\Redis{
        if(!is_null($this->redis)){
            return $this->redis;
        }
        $this->redis = (new Redis($this->configDefault))->getRedis();
        return $this->redis;

    }

}