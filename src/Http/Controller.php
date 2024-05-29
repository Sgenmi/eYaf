<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/23 下午6:03
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Http;

use GuzzleHttp\Psr7\Utils;
use Psr\Container\ContainerInterface;
use Sgenmi\eYaf\Contract\RequestInterface;
use Sgenmi\eYaf\Contract\ResponseInterface;
use Sgenmi\eYaf\Di\Container;

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
    protected bool $isEmptyReturnArray=false;

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
    protected function Json( int $code = 0, string $message = '', mixed $data =[]):bool
    {
        $r_data = [
            'code' => $code,
            'msg' => $message,
            'data' => $data?:new \stdClass()
        ];
        if($this->isEmptyReturnArray && !$data){
            $r_data['data'] = [];
        }
        $json = json_encode($r_data);
        $resp =  $this->container->get(ResponseInterface::class)
            ->withBody(Utils::streamFor($json))
            ->withHeader('Content-Type','application/json; charset=utf-8');
        $this->container->set(ResponseInterface::class,$resp);
        return false;
    }

    /**
     * @content 获取单个uri中参数或get中参数 优先uri中，yaf中并不提倡开启全局get,
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getParam(string $name = '',mixed $default = '')
    {
        $request = $this->container->get(RequestInterface::class);
        if(!empty($name)){
            $_params = $request->getParams();
            if(isset($_params[$name])){
                return $_params[$name];
            }
            $_params = $request->getQueryParams();
            if(isset($_params[$name])){
                return $_params[$name];
            }
            return $default;
        }else{
            return array_merge($request->getParams(), $request->getQueryParams());
        }

    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getPost(string $name = '', mixed $default = null)
    {
        $request = $this->container->get(RequestInterface::class);
        if (!empty($name)) {
            $val =  $request->getPost($name,$default);
        } else {
            $val = $request->getPost();
        }
        return $val;
    }

    /**
     * @content 获取body里内容
     * @return mixed
     */
    protected function getRaw()
    {
        $request = $this->container->get(RequestInterface::class);
        $str = $request->getBody()->getContents();
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
     * @param mixed|null $default
     * @return mixed
     */
    protected function getServer(string $name = '', mixed $default = null)
    {
        $request = $this->container->get(RequestInterface::class);
        if (!empty($name)) {
            $val = $request->getServerParams()[$name]??$default;
        } else {
            $val = $request->getServerParams();
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
        $request = $this->container->get(RequestInterface::class);
        if (!empty($name)) {
            $val = $request->getCookieParams()[$name]??$default;
        } else {
            $val = $request->getCookieParams();
        }
        return $val;
    }

    /**
     * @content 获取文件$_FILE
     * @param string $name
     * @return mixed
     */
    protected function getFiles(string $name='')
    {
        $request = $this->container->get(RequestInterface::class);
        if (!empty($name)) {
            $val = $request->getUploadedFiles()[$name];
        } else {
            $val = $request->getUploadedFiles();
        }
        return $val;
    }

}
