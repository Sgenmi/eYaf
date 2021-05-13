<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 22:02
 */

namespace Sgenmi\eyaf;
require dirname(getcwd()).'/vendor/autoload.php';
class Bootstrap extends \Yaf\Bootstrap_Abstract
{
    //全部设置配置文件
    public function _initBootstrap()
    {
        $config = \Yaf\Application::app()->getConfig();
        \Yaf\Registry::set('_config', $config);
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
    // 安全输入
    public function _initFilter()
    {
        \Sgenmi\eYaf\Utility\Filter::request();
    }

    private function getDBConfig($isMaster = false)
    {
        $_config = \Yaf\Registry::get('_config');
        if ($isMaster) {
            $options = $_config->database->params->master->toArray();
        } else {
            // 如果没有设置从库，就直接选主库
            if (! isset($_config->database->params->slave)) {
                $options = $_config->database->params->master->toArray();
            } else {
                $slaveArr = $_config->database->params->slave->toArray();
                $randKey = array_rand($slaveArr, 1);
                $options = $slaveArr[$randKey];
            }
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
