<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2021/11/20 下午1:07
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Command;

use Medoo\Medoo;
use Sgenmi\eYaf\Command\Action\Create;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Command extends \Symfony\Component\Console\Command\Command
{
    protected $config;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->config = \Yaf\Registry::get('_config');
    }

    public function start(){
       $application = new Application();
       $allCommand = $this->getAllCommand();
       foreach ($allCommand as $v){
           $application->add($v);
       }
       $application->run();
   }

   private function getAllCommand():array{
        return [
            new Create('create:controller'),
            new Create('create:model'),
            new Create('create:module'),
            new Create('create:plugin'),
            new Create('create:service'),
            new Create('create:repository'),
        ];
   }


    /**
     * 获取Redis实际，主要命令行中兼容协程不共享实例
     * @author Sgenmi
     * @param string $redisName
     * @return \Redis
     * @throws \Exception
     */
    protected function getRedis(string $redisName = 'app'):\Redis
    {
        $redis = new \Redis();
        if(empty($this->config['redis'][$redisName])){
            throw new \Exception('The '.$redisName.' redis configuration does not exist');
        }
        $_redis_config = $this->config['redis'][$redisName];
        $isConn = $redis->connect($_redis_config["host"]??'', $_redis_config["port"]??'');
        if(!$isConn){
            throw new \Exception('The '.$redisName.' redis configuration connection failed');
        }
        if (isset($_redis_config["auth"]) && $_redis_config["auth"]) {
            $isOk = $redis->auth($_redis_config["auth"]);
            if(!$isOk){
                throw new \Exception('The '.$redisName.' redis configuration auth failed');
            }
        }
        if (isset($_redis_config["db"]) && $_redis_config["db"] >=0 && $_redis_config["db"]<=16) {
            $redis->select((int) $_redis_config["db"] );
        }
        return $redis;
    }

    /**
     * 获取Medoo实例，主要命令行中兼容协程不共享实例
     * @author Sgenmi
     * @param string $dbName
     * @return Medoo
     * @throws \Exception
     */
    protected function getMedoo(string $dbName='master'):Medoo{

        if(empty($this->config['database']['params'][$dbName])){
            throw new \Exception('The '.$dbName.' database configuration does not exist or incorrectly configured');
        }
        if($dbName=='slave'){
            throw new \Exception('The command line can not used slave database, it may affect the results');
        }
        $options =$this->config['database']['params'][$dbName];
        return new Medoo($options);
    }


}
