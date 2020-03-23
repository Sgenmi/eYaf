<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/23 下午6:03
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Http;

class Controller extends \Yaf\Controller_Abstract
{
    protected $_module;
    protected $_controller;
    protected $_action;

    public function init()
    {
        $this->_module = strtolower($this->getRequest()->getModuleName());
        $this->_controller= strtolower($this->getRequest()->getControllerName());
        $this->_action = strtolower($this->getRequest()->getActionName());
        defined(IS_DISABLE_VIEW) && defined('IS_DISABLE_VIEW',true);
        //禁用渲染模板
        if(IS_DISABLE_VIEW){
            \Yaf\Dispatcher::getInstance()->disableView();
        }

    }

}