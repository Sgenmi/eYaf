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
    protected $isAjax = false;
    protected $isGet = false;
    protected $isPost = false;
    protected $isHead = false;
    protected $isPut = false;
    protected $isDel = false;
    protected $isWeiXin = false;

    public function init()
    {
        $this->_module = strtolower($this->getRequest()->getModuleName());
        $this->_controller = strtolower($this->getRequest()->getControllerName());
        $this->_action = strtolower($this->getRequest()->getActionName());
        !defined('IS_DISABLE_VIEW') && define('IS_DISABLE_VIEW', true);
        //禁用渲染模板
        if (IS_DISABLE_VIEW) {
            \Yaf\Dispatcher::getInstance()->disableView();
        }
        $this->initMethod();
    }

    private function initMethod()
    {
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

    /**
     * @param int $code
     * @param array $message
     * @param array $data
     * @param int $count
     */
    protected function Json($code = 0, $message = [], $data = [], $count = 0)
    {
        $r_data = [
            'code' => $code,
            'msg' => $message,
            'data' => $data,
            'count' => $count,
        ];
        header('Content-type:text/json');
        exit(json_encode($r_data));
    }

    /**
     * @content 获取单个uri中参数或get中参数 优先uri中，yaf中并不提倡开启全局get,
     * @param string $name
     * @param string $dafault
     * @return bool|string|array
     */
    protected function getParam(string $name = '', $dafault = '')
    {
        if (!empty($name)) {
            $val = $this->getRequest()->getParam($name);
            if (!empty($val)) {
                return $val;
            }
            $val = $this->getRequest()->getQuery($name);
            if (!empty($val)) {
                return $val;
            }
        } else {
            $getQuery = $this->getRequest()->getQuery();
            $getParams = $this->getRequest()->getParams();
            return array_merge($getParams, $getQuery);
        }
        return $dafault;
    }

    /**
     * @param string $name
     * @param string $dafault
     * @return mixed
     */
    protected function getPost(string $name = '', $dafault = '')
    {
        if (!empty($name)) {
            $val = $this->getRequest()->getPost($name);
            if (!empty($val)) {
                return $val;
            }
        } else {
            $val = $this->getRequest()->getPost();
            return $val;
        }
        return $dafault;

    }

    /**
     * @content 获取body里内容
     * @return mixed
     */
    protected function getRaw()
    {
        return $this->getRequest()->getRaw();
    }

    /**
     * @content 获取环境变量
     * @param string $name
     * @param string $dafault
     * @return mixed
     */
    protected function getEnv(string $name = '', string $dafault = '')
    {
        return $this->getRequest()->getEnv($name, $dafault);
    }

    /**
     * @content 获取$_SERVER内容
     * @param string $name
     * @param string $dafault
     * @return string
     */
    protected function getServer(string $name = '', $dafault = null)
    {

        if (!empty($name)) {
            $val = $this->getRequest()->getServer($name, $dafault);
        } else {
            $val = $this->getRequest()->getServer();
        }
        return $val;
    }

    /**
     * @content 获取cookie
     * @param string $name
     * @param string $dafault
     * @return mixed
     */
    protected function getCookie(string $name = '', string $dafault = '')
    {
        if (!empty($name)) {
            $val = $this->getRequest()->getCookie($name, $dafault);
        } else {
            $val = $this->getRequest()->getCookie();
        }
        return $val;
    }

    /**
     * @content 获取文件$_FILE
     * @param string $name
     * @return mixed
     */
    protected function getFiles(string $name)
    {
        return $this->getRequest()->getFiles($name);
    }


}