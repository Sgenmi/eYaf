<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 22:02
 */

namespace Sgenmi\eYaf;
use Sgenmi\eYaf\Contract\ConfigInterface;
use Sgenmi\eYaf\Di\Container;
use Sgenmi\eYaf\Plugin\SysInitPlugin;
use Sgenmi\eYaf\Pool\PoolFactory;
use Yaf\Bootstrap_Abstract;
use Yaf\Loader;
use Yaf\Registry;

require (defined('BASE_PATH') && BASE_PATH ? BASE_PATH: dirname(getcwd())).'/vendor/autoload.php';
class Bootstrap extends Bootstrap_Abstract
{
    //全部设置配置文件
    public function _initBootstrap()
    {
        $config = \Yaf\Application::app()->getConfig();
        Registry::set('_config', $config->toArray());
        $con = Container::getInstance();
        $con->set(ConfigInterface::class,new Config());
        $con->set(PoolFactory::class,new PoolFactory());
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
        Loader::import('Funs.php');
        $loader = Loader::getInstance(APP_PATH.'/library');
        $loader->registerNamespace("\Service", APP_PATH."/services/");
        $loader->registerNamespace("\Repository",APP_PATH."/repositorys/"); //repositories
        $loader->registerNamespace("\Command",APP_PATH."/commands/");
    }

    public function _initRegisterNameSpace(){
        //注册模块名命名空间
        $loader = Loader::getInstance(APP_PATH.'/library');
        $modules= Registry::get('_modules');
        if($modules && is_array($modules)){
            foreach ($modules as $v){
                $loader->registerNamespace(sprintf("\%s\Model",$v), APP_PATH."/modules/{$v}/models");
                $loader->registerNamespace(sprintf("\%s\Service",$v), APP_PATH."/modules/{$v}/services");
                $loader->registerNamespace(sprintf("\%s\Command",$v), APP_PATH."/modules/{$v}/commands");
                $loader->registerNamespace(sprintf("\%s\Repository",$v), APP_PATH."/modules/{$v}/repositorys");
            }
        }
    }

    //system plugin
    public function _initSysPlugins(\Yaf\Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new SysInitPlugin());
    }

}
