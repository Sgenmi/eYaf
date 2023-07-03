<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/23 下午6:03
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Http;

use Nyholm\Psr7\Stream;
use Sgenmi\eYaf\Contract\ContainerInterface;
use Sgenmi\eYaf\Contract\RequestInterface;
use Sgenmi\eYaf\Contract\ResponseInterface;
use Sgenmi\eYaf\Di\Container;
use Sgenmi\eYaf\Utility\Tool;

abstract class Controller extends \Yaf\Controller_Abstract
{
    protected bool $isAjax = false;
    protected bool $isGet = false;
    protected bool $isPost = false;
    protected bool $isHead = false;
    protected bool $isPut = false;
    protected bool $isDel = false;
    protected bool $isWeiXin = false;
    protected ContainerInterface $container;

    public function init()
    {
        $this->container = Container::getInstance();
        !defined('IS_DISABLE_VIEW') && define('IS_DISABLE_VIEW', true);
        //禁用渲染模板
        if (IS_DISABLE_VIEW) {
            \Yaf\Dispatcher::getInstance()->disableView();
        }
        $this->initMethod();
    }

    private function initMethod()
    {
        $request = $this->container->get(RequestInterface::class);
        $method = strtoupper($request->getMethod());
        switch (true) {
            case strtoupper($request->getHeader('HTTP_X_REQUESTED_WITH')[0]??'')==='XMLHttpRequest':
                $this->isAjax = true;
            case $method==='GET':
                $this->isGet = true;
                break;
            case $method==="POST":
                $this->isPost = true;
                break;
            case $method==="HEAD":
                $this->isHead = true;
                break;
            case $method==='PUT':
                $this->isPut = true;
                break;
            case $method==='DELETE':
                $this->isDel = true;
                break;
        }
        // 判断是否是wx
        $ua = $request->getHeader('HTTP_USER_AGENT')[0]??'';
        if ($ua && strpos(strtolower($ua), 'micromessenger') !== false) {
            $this->isWeiXin = true;
        }
    }

    /**
     * 返回false 因为yaf在控制器中返回false时，不渲染模板
     * @param int $code
     * @param string $message
     * @param mixed $data
     * @return bool
     */
    protected function Json( int $code = 0, string $message = '', mixed $data =[])
    {
        $r_data = [
            'code' => $code,
            'msg' => $message,
            'data' => $data?:new \stdClass()
        ];
        $json = json_encode($r_data);
//        if(Tool::isSwooleCo() && ($responseSw = \Co::getContext()['response'])){
//            $responseSw->setHeader('Content-Type','application/json; charset=utf-8');
//            echo $json;
//        }else{
//            $response = $this->getResponse();
//            $response->setHeader( 'Content-Type', 'application/json; charset=utf-8' );
//            $response->setBody($json);
//        }
//        $this->container->get(ResponseInterface::class)

        $resp =  $this->container->get(ResponseInterface::class)
            ->withBody(Stream::create($json))
            ->withHeader('Content-Type','application/json; charset=utf-8');
        $this->container->set(ResponseInterface::class,$resp);
        return false;
    }

    /**
     * @content 获取单个uri中参数或get中参数 优先uri中，yaf中并不提倡开启全局get,
     * @param string $name
     * @param mixed $default
     * @return bool|string|array
     */
    protected function getParam(string $name = '', $default = '')
    {
        if (!empty($name)) {
            $val = $this->getRequest()->getParam($name);
            if (!empty($val) || (is_numeric($val) && $val==0) ) {
                return $val;
            }
            if(Tool::isSwooleCo()){
                $val = \Co::getContext()['get'][$name]??'';
            }else{
                $val = $this->getRequest()->getQuery($name);
            }
            if (!empty($val) || (is_numeric($val) && $val==0) ) {
                return $val;
            }
        } else {
            if(Tool::isSwooleCo()){
                $getQuery = \Co::getContext()['get']??[];
            }else{
                $getQuery = $this->getRequest()->getQuery();
            }
            $getParams = $this->getRequest()->getParams();
            return array_merge($getParams, $getQuery);
        }
        return $default;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getPost(string $name = '', $default = '')
    {
        if (!empty($name)) {
            if(Tool::isSwooleCo()){
                $val = \Co::getContext()['post'][$name]??'';
            }else{
                $val = $this->getRequest()->getPost($name);
            }
            if (!empty($val)) {
                return $val;
            }
        } else {
            if(Tool::isSwooleCo()){
                $val = \Co::getContext()['post']??[];
            }else{
                $val = $this->getRequest()->getPost();
            }
            return $val;
        }
        return $default;

    }

    /**
     * @content 获取body里内容
     * @return mixed
     */
    protected function getRaw()
    {
        if(Tool::isSwooleCo()){
            $str = \Co::getContext()['body']??'';
        }else{
            $str =  $this->getRequest()->getRaw();
        }
        return $str;
    }

    /**
     * @content 获取环境变量
     * @param string $name
     * @param string $default
     * @return mixed
     */
    protected function getEnv(string $name = '', string $default = '')
    {
        return $this->getRequest()->getEnv($name, $default);
    }

    /**
     * @content 获取$_SERVER内容
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getServer(string $name = '', $default = null)
    {

        if (!empty($name)) {
            $val = $this->getRequest()->getServer($name, $default);
        } else {
            $val = $this->getRequest()->getServer();
        }
        return $val;
    }

    /**
     * @content 获取cookie
     * @param string $name
     * @param string $default
     * @return mixed
     */
    protected function getCookie(string $name = '', string $default = '')
    {
        if (!empty($name)) {
            if(Tool::isSwooleCo()){
                $val = \Co::getContext()['cookie'][$name]??$default;
            }else{
                $val = $this->getRequest()->getCookie($name,$default);
            }
        } else {
            if(Tool::isSwooleCo()){
                $val = \Co::getContext()['cookie']??[];
            }else{
                $val = $this->getRequest()->getCookie();
            }
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
        if(Tool::isSwooleCo()){
            $val = \Co::getContext()['files'][$name]??[];
        }else{
            $val = $this->getRequest()->getFiles($name);
        }
        return $val;
    }


}
