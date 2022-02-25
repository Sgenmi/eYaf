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

    public function _initCommon()
    {
        \Yaf\Loader::import('Funs.php');
        $loader = \Yaf\Loader::getInstance(APP_PATH.'/library');
        $loader->registerNamespace("\Service", APP_PATH."/services/");
        $loader->registerNamespace("\Repository",APP_PATH."/repositorys/");
        $loader->registerNamespace("\Command",APP_PATH."/commands/");
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
}
