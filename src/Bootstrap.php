<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 22:02
 */

namespace Sgenmi\eYaf;
require (defined('BASE_PATH') && BASE_PATH ? BASE_PATH: dirname(getcwd())).'/vendor/autoload.php';
class Bootstrap extends \Yaf\Bootstrap_Abstract
{
    //全部设置配置文件
    public function _initBootstrap()
    {
        $config = \Yaf\Application::app()->getConfig();
        \Yaf\Registry::set('_config', $config->toArray());
    }

    //关闭错误
    public function _initErrors()
    {
        if (defined('DEVELOPMENT') && DEVELOPMENT) {
            ini_set('display_errors', 'On');
            error_reporting(E_ALL);
        } else {
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
    }

    public function _initRegisterNameSpace(){
        //注册模块名命名空间
        $loader = \Yaf\Loader::getInstance(APP_PATH.'/library');
        $modules= \Yaf\Registry::get('_modules');
        if($modules && is_array($modules)){
            foreach ($modules as $v){
                $loader->registerNamespace(sprintf("\%s\Model",$v), APP_PATH."/modules/{$v}/models");
                $loader->registerNamespace(sprintf("\%s\Service",$v), APP_PATH."/modules/{$v}/services");
                $loader->registerNamespace(sprintf("\%s\Command",$v), APP_PATH."/modules/{$v}/command");
            }
        }
    }

    /**
     * 获取链接db,兼容升级
     * @param bool $isMaster
     * @return array
     */
    private function getDBConfig(bool $isMaster = false)
    {
        $_config = \Yaf\Registry::get('_config');
        $options=[];
        if ($isMaster) {
            if(is_object($_config)){
                if(!empty($_config->database->params->master)){
                    $options = $_config->database->params->master->toArray();
                }
            }else{
                $options = $_config['database']['params']['master']??[];
            }

        } else {
            // 如果没有设置从库，就直接选主库
            if(is_object($_config)){
                if (empty($_config->database->params->slave)) {
                    if(!empty($_config->database->params->master)){
                        $options = $_config->database->params->master->toArray();
                    }
                } else {
                    $slaveArr = $_config->database->params->slave->toArray();
                    if(isset($slaveArr['host'])){
                        $options = $slaveArr;
                    }else{
                        $randKey = array_rand($slaveArr, 1);
                        $options = $slaveArr[$randKey];
                    }
                }
            }else{
                $slaveArr = $_config['database']['params']['slave']??[];
                if (empty($saves)) {
                    $options = $_config['database']['params']['master']??[];
                } else {
                    if(isset($slaveArr['host'])){
                        $options = $slaveArr;
                    }else{
                        $randKey = array_rand($slaveArr, 1);
                        $options = $slaveArr[$randKey];
                    }
                }
            }
        }
        //兼容medoo 1.x 升级 2.x
        if($options && isset($options['database_name'])){
            $options['type'] = $options['database_type'];
            $options['database'] = $options['database_name'];
            $options['host'] = $options['server'];
        }
        return $options;
    }
    //连接数据库
    public function _initDB()
    {
        try {
            $config = $this->getDBConfig(true);
            if($config){
                \Yaf\Registry::set('_masterDB', new \Medoo\Medoo($config));
            }
        }catch (\PDOException $e){
            echo '_masterDB:'.$e->getMessage().PHP_EOL;
            exit;
        }
        try {
            $config = $this->getDBConfig(false);
            if($config){
                \Yaf\Registry::set('_slaveDB', new \Medoo\Medoo($config));
            }
        }catch (\PDOException $e){
            echo '_slaveDB:'.$e->getMessage().PHP_EOL;
            exit;
        }
    }

}
