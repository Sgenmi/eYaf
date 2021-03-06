<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/27 23:56
 */

namespace Sgenmi\eYaf\Cache;

use Yaf\Exception;

/**
 */
class Redis implements CacheIface
{
    private $configDefault='app';
    private $config=[];
    /**
     * @var \Redis
     */
    private $redis;
    public function __construct($configDefault="app"){
        $this->configDefault = $configDefault;
        $this->connect();
    }
    private function connect() {
        $gCofnig = \Yaf\Registry::get('_config');
        $name = $this->configDefault;
        $config = $gCofnig->redis->$name;
        if(!$config) {
            throw new Exception("Can't find $name configuration with redis");
        }
        if(!isset($config['host']) || !isset($config['port'])) {
            throw new Exception("Can't find $name's host|port configuration with redis");
        }
        $this->config = $config;

        $redis = new \Redis();
        $redis->connect($config['host'],$config['port']);
        if(isset($config['auth']) && $config['auth']){
            $redis->auth($config['auth']);
        }
        $this->redis = $redis;
        return ;
    }

    /**
     * @return \Redis
     */
    public function getRedis():\Redis{
        return  $this->redis;
    }

    /**
     * @return array
     */
    public function getConfig():array{
        return $this->config;
    }

    public function __call($name, $arguments){
        return $this->redis->{$name}(...$arguments);
    }

    /**
     * @param string $key
     * @return bool|mixed|string
     */
    public function get(string $key){
        return $this->redis->get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $timeout
     * @return bool
     */
    public function set(string $key, $value, int $timeout = 0){
        return $this->redis->set($key,$value,$timeout);
    }

    /**
     * @param string $key
     * @param mixed ...$otherKeys
     * @return int
     */
    public function del(string $key, ...$otherKeys){
        return $this->redis->del($key,...$otherKeys);
    }

    /**
     * @param string $key
     * @return bool|int
     */
    public function exists(string $key){
        return $this->redis->exists($key);
    }

    /**
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl=86400){
        return $this->redis->expire($key,$ttl);
    }


}
