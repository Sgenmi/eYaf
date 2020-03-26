<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/23 下午6:03
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Http;

abstract class Controller extends \Yaf\Controller_Abstract
{
    protected $_module;
    protected $_controller;
    protected $_action;
    protected $isAjax=false;
    protected $isGet = false;
    protected $isPost = false;
    protected $isHead = false;
    protected $isPut =false;
    protected $isDel=false;
    protected $isWeiXin =false;

    public function init()
    {
        $this->_module = strtolower($this->getRequest()->getModuleName());
        $this->_controller= strtolower($this->getRequest()->getControllerName());
        $this->_action = strtolower($this->getRequest()->getActionName());
        !defined('IS_DISABLE_VIEW') && define('IS_DISABLE_VIEW',true);
        //禁用渲染模板
        if(IS_DISABLE_VIEW){
            \Yaf\Dispatcher::getInstance()->disableView();
        }
        $this->initMethod();
    }

    private function initMethod(){
        $request = $this->getRequest();
        switch (true) {
            case $request->isXmlHttpRequest():
                $this->isAjax = true;
            case $request->isGet():
                $this->isGet = true;
            case $request->isPost():
                $this->isPost = true;
            case true: // 结束，下面头没有用到，但保留功能
                break;
            case $request->isHead():
                $this->isHead = true;
                break;
            case $request->isPut():
                $this->isPut = true;
                break;
            case $request->isDelete():
                $this->isDel = true;
                break;
        }
        // 判断是否是wx
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        if (strpos($ua, 'micromessenger') !== false) {
            $this->isWeiXin = true;
        }
    }

    protected function Json($code=0,$message=[],$data=[],$count=0){
        $r_data = [
            'code' => $code,
            'msg' => $message,
            'data' => $data,
            'count' => $count,
        ];
        header('Content-type:text/json');
        exit(json_encode($r_data));
    }




}