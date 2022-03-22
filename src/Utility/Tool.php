<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/29 14:20
 */

namespace Sgenmi\eYaf\Utility;


class Tool
{
    /**
     * @desc 获取客户端IP
     * @return string
     */
    public static function getClientIp(): string
    {
        $ser = (array)((new \Yaf\Request\Http())->getServer());
        if (isset($ser["HTTP_CLIENT_IP"])) {
            $ip = $ser["HTTP_CLIENT_IP"];
        } else if (isset($ser["HTTP_X_FORWARDED_FOR"])) {
            $ip = $ser["HTTP_X_FORWARDED_FOR"];
        } else if (isset($ser["REMOTE_ADDR"])) {
            $ip = $ser["REMOTE_ADDR"];
        } else {
            $ip = "Unknow";
        }
        return $ip;
    }

    /**
     * @param string $arg
     * @return array|mixed
     */
    public static function getConfig(string $arg = '')
    {
        $_config = \Yaf\Registry::get('_config');
        if (empty($arg)) {
            return $_config;
        } else {
            return isset($_config->$arg) ? $_config->$arg : [];
        }
    }
    /**
     * @desc 分转元
     * @param int $val
     * @return float
     */
    public static function centToYuan(int $val):float
    {
        return sprintf("%.2f", $val / 100);
    }
    /**
     * @desc 元转分
     * @param float $val
     * @return int
     */
    public static function yuanToCent(float $val)
    {
        return (int)($val * 100);
    }

    public static function  parseUrl($url){
        $urlArr = parse_url(urldecode(htmlspecialchars_decode($url)));
        $queryStr = $urlArr['query']??'';
        if(isset($urlArr['fragment'])){
            $url_fr = 'http://127.0.0.1/';
            if(strpos($urlArr['fragment'],'?')===false){
                $url_fr.= '?'.$urlArr['fragment'];
            }else{
                $url_fr.=$urlArr['fragment'];
            }
            $urlFrArr = parse_url(urldecode(htmlspecialchars_decode($url_fr)));
            if(isset($urlFrArr['query'])){
                $queryStr.="&".$urlFrArr['query'];
            }
        }
        $params=[];
        if($queryStr){
            $queryArr = explode('&',$queryStr);
            foreach ($queryArr as $v){
                $vArr = explode('=',$v);
                $params[$vArr[0]] = $vArr[1]??'';
            }
        }
        $urlArr['params'] = $params;
        return $urlArr;
    }

    /**
     * 检测是否 swoole环境下,协程运行
     * @return bool
     */
    public static function isSwooleCo():bool{
        return extension_loaded('swoole') && (\Swoole\Coroutine::getCid()>-1);
    }

}
