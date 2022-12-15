<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/27 23:56
 */

namespace Sgenmi\eYaf\Cache;

use Sgenmi\eYaf\Utility\Tool;
use Yaf\Exception;

/**
 */
class Redis implements CacheIface
{
    private $configDefault='app';
    private $config=[];
    private static $connect=[];
    /**
     * @var \Redis
     */
    private $redis;
    public function __construct(string $configDefault="app"){
        $this->configDefault = $configDefault;
        $this->connect();
    }

    private function connect(){
        $coId=0;
        if(Tool::isSwooleCo()){
            $coId = \Swoole\Coroutine::getCid();
            \Swoole\Coroutine\defer(function ()use($coId){
                unset(self::$connect[$coId]);
            });
        }
        if(!isset(self::$connect[$coId][$this->configDefault])){
            try {
                $this->reconnect();
                self::$connect[$coId][$this->configDefault] = $this->redis;
            }catch (\Throwable $e){
                unset(self::$connect[$coId][$this->configDefault]);
            }
        }else{
            $this->redis = self::$connect[$coId][$this->configDefault];
        }
    }
    private function reconnect():void {
        $gCofnig = \Yaf\Registry::get('_config');
        $name = $this->configDefault;
        $config = $gCofnig['redis'][$name]??[];
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
        if(isset($config['db']) && $config['db']){
            $redis->select($config['db']);
        }
        $this->redis = $redis;
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
