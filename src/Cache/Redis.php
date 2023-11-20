<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/27 23:56
 */

namespace Sgenmi\eYaf\Cache;

use RedisException;
use Sgenmi\eYaf\Context;
use Sgenmi\eYaf\Di\Container;
use Sgenmi\eYaf\Pool\PoolFactory;
use Sgenmi\eYaf\Pool\RedisConnection;
use Swoole\Coroutine;
use Throwable;
use Yaf\Exception;

/**
 * @mixin \Redis
 */
class Redis
{
    protected string $name='app';
    private array $config=[];
    private static array $connect=[];

    private int $database=0;

    public function __construct(string $name="app"){
        $this->name = $name;
        $this->setConfig();
    }

    /**
     * @param string $name
     * @return $this|Redis
     * @throws Exception
     * @author Sgenmi
     */
    public function getPool(string $name='app'){
        if($this->name==$name){
            return $this;
        }
        $self = clone $this;
        $self->name = $name;
        $self->setConfig();
        return $self;
    }

//    private function connect(){
//        $coId=0;
//        if(Tool::isSwooleCo()){
//            $coId = \Swoole\Coroutine::getCid();
//        }
//        if(!isset(self::$connect[$coId][$this->configDefault])){
//            try {
//                $this->reconnect();
//                self::$connect[$coId][$this->configDefault] = $this->redis;
//                if($coId>0){
//                    \Swoole\Coroutine\defer(function ()use($coId){
//                        unset(self::$connect[$coId]);
//                    });
//                }
//            }catch (\Throwable $e){
//                unset(self::$connect[$coId][$this->configDefault]);
//            }
//        }else{
//            $this->redis = self::$connect[$coId][$this->configDefault];
//        }
//    }

    /**
     * @return void
     * @throws Exception
     * @author Sgenmi
     */
    private function setConfig(){
        $gConfig = \Yaf\Registry::get('_config');
        $name = $this->name;
        $config = $gConfig['redis'][$name]??[];
        if(!$config) {
            throw new Exception("Can't find $name configuration with redis");
        }
        if(!isset($config['host']) || !isset($config['port'])) {
            throw new Exception("Can't find $name's host|port configuration with redis");
        }
        $config['db']= intval($config['db']??0);
        $this->database = $config['db'];
        $this->config = $config;
    }

    /**
     * @throws RedisException
     */
    private function reconnect():\Redis {
        $redis = new \Redis();
        $redis->connect($this->config['host'],intval($this->config['port']));
        if(!empty($this->config['auth'])){
            $redis->auth($this->config['auth']);
        }
        if(!empty($this->config['db'])){
            $redis->select($this->config['db']);
        }
        return $redis;
    }

    /**
     * @param int $db
     * @return $this
     * @author Sgenmi
     */
    public function select(int $db): static
    {
        $this->database=$db;
        return $this;
    }

    public function __call($name, $arguments){
        $contextKey = $this->getContextKey();
        //如果是非swoole协程，则直接走Context保存，不走连接池
        $hasContextConnection = Context::has($contextKey);
        $connection = $this->getConnection($hasContextConnection);
        $coId = $this->getCoroutineId();
        try {
            if($coId && !$this->isDisablePool()){
                $connection = $connection->getConnection();
            }
            if($this->database !=$this->config['db']){
                $connection->select($this->database );
            }
            $result = $connection->{$name}(...$arguments);
            if($this->database !=$this->config['db']){
                $connection->select($this->config['db']);
                $this->database = $this->config['db'];
            }
        } finally {
            if (! $hasContextConnection && $coId) {
                //禁用连接池，则在协程中，不需要回收池；
                $isDisablePool = $this->isDisablePool();
                if ( in_array($name, [ 'multi', 'pipeline', 'select',])) {
                    Context::set($contextKey, $connection);
                    defer(function () use ($connection,$isDisablePool,$contextKey) {
                        Context::set($contextKey, null);
                        if(!$isDisablePool){
                            $connection->release();
                        }
                    });
                } else {
                    if(!$isDisablePool){
                        $connection->release();
                    }
                    //使用了swoole自带的上下文，协程结束，自动结束，不用defer
                }
            }

        }
        return $result;
    }

    /**
     * @return int
     * @author Sgenmi
     */
    private function getCoroutineId(): int
    {
        if( extension_loaded('swoole') && (($coId = Coroutine::getCid())>0)){
            return intval($coId);
        }
        return 0;
    }

    /**
     * @param bool $hasContextConnection
     * @return mixed|\Redis|RedisConnection|null
     * @throws Throwable
     * @author Sgenmi
     */
    private function getConnection(bool $hasContextConnection){
        $connection = null;
        if ($hasContextConnection) {
            $connection = Context::get($this->getContextKey());
        }
        $coId = $this->getCoroutineId();
        //有时协程下，就是用独立连接，不用连接池
        if($coId && !$this->isDisablePool()){
            $factory = Container::getInstance()->get(PoolFactory::class);
            if (! $connection instanceof RedisConnection) {
                $pool = $factory->getPool($this->name,'redis');
                $connection = $pool->get()->getConnection();
            }
            if (! $connection instanceof RedisConnection) {
                throw new RedisException('The connection is not a valid RedisConnection.');
            }
        }else{ //非协程下,实际返回是redis实例
            if(!$connection){
                $connection = $this->reconnect();
                Context::set($this->getContextKey(),$connection);
            }
        }
        return $connection;
    }

    private function getContextKey(): string {
        return 'redis.connection.'.$this->name;
    }

    /**
     * @return bool
     * @author Sgenmi
     */
    private function isDisablePool():bool{
        return defined("IS_DISABLE_REDIS_POOL") && IS_DISABLE_REDIS_POOL;
    }

}
