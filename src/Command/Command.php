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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class Command extends \Symfony\Component\Console\Command\Command
{
    protected $config;
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var array
     */
    protected $descInfo;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    protected function configure()
    {
        $this->config = \Yaf\Registry::get('_config');
        $arr = $this->descInfo[$this->getName()] ?? [];
        $this->setDescription($this->descInfo[$this->getName()]['desc'] ?? '');
        if (!$arr) {
            return;
        }
        if (isset($arr['argument']) && $arr['argument']) {
            foreach ($arr['argument'] as $k => $v) {
                $this->addArgument($k, $v['mode'] ?? InputArgument::OPTIONAL, $v['desc'] ?? '',$v['default']??null);
            }
        }
        if (isset($arr['option']) && $arr['option']) {
            foreach ($arr['option'] as $k => $v) {
                $this->addOption($k, $v['short_alias']??null, $v['mode'] ?? InputOption::VALUE_OPTIONAL, $v['desc'] ?? '',$v['default']??null);
            }
        }
    }

    //信号的定义
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = explode(':', $this->getName());
        $action= end($action);
        $this->$action();
        return 0;
    }
    public function start(){
       $application = new Application();
       $allCommand = $this->getAllCommand();
       foreach ($allCommand as $v){
           $application->add($v);
       }
       $application->run();
   }

    /**
     * 命令行统一加载
     * @return array
     */
    private function getAllCommand():array{
        $commands=[
            new Create('create:controller'),
            new Create('create:model'),
            new Create('create:module'),
            new Create('create:plugin'),
            new Create('create:service'),
            new Create('create:repository'),
            new Create('create:command'),
        ];
        if(defined('APP_PATH')){
            $cmdConfFile =  APP_PATH.'/conf/command.php';
            if(is_file($cmdConfFile)){
                $cmdConfArr = require $cmdConfFile;
                $commands = array_merge($commands,$cmdConfArr);
            }
        }

        $modules= \Yaf\Registry::get('_modules');
        if($modules && is_array($modules)){
            foreach ($modules as $v){
                $cmdConfFile =  APP_PATH."/modules/{$v}/commands/command.php";
                if(is_file($cmdConfFile)){
                    $cmdConfArr = require $cmdConfFile;
                    $commands = array_merge($commands,$cmdConfArr);
                }
            }
        }
        return $commands;
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

    /**
     * @param mixed $val
     * @return void
     */
    protected function log($val){
        echo date("Y-m-d H:i:s").'==>'. (is_array($val)?json_encode($val,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):$val).PHP_EOL;
    }

}
