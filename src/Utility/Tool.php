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
    public static function yuanToCent(float $val):int
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

    /**
     * 生成随机字符串，来自easyswoole
     * @param int $length
     * @param string $alphabet
     * @return string
     */
    public static function character(int $length = 6, string $alphabet = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz0123456789'):string
    {
        mt_srand();
        // 重复字母表以防止生成长度溢出字母表长度
        if ($length >= strlen($alphabet)) {
            $rate = intval($length / strlen($alphabet)) + 1;
            $alphabet = str_repeat($alphabet, $rate);
        }
        // 打乱顺序返回
        return strval(substr(str_shuffle($alphabet), 0, $length));
    }


    /**
     * @param mixed $val
     * @param string $type object|array|string|int
     * @param string $separator
     * @return mixed
     */
    public static function convertType(mixed $val, string $type = 'array', string $separator = ''): mixed
    {

        switch ($type) {
            case 'object':
                if (is_object($val)) {
                    return $val;
                } else {
                    if (is_string($val)) {
                        $val = json_decode($val);
                    } elseif (is_array($val)) {
                        $val = (object)$val;
                    }
                    return is_object($val) ? $val : new stdClass();
                }
            case 'array':
                if (is_array($val)) {
                    return $val;
                } else {
                    if (is_string($val)) {
                        if ($separator) {
                            $val = explode($separator, $val);
                        } else {
                            $val = json_decode($val, true);
                        }
                    } elseif (is_object($val)) {
                        $val = (array)$val;
                    }
                    return is_array($val) ? $val : [];
                }
            case 'string':
                if (is_string($val)) {
                    return $val;
                } else {
                    if (is_array($val)) {
                        if ($separator) {
                            $val = implode($separator, $val);
                        } else {
                            $val = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        }
                    } elseif (is_object($val)) {
                        $val = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    }
                    return is_string($val) ? $val : '';
                }
            case 'int':
                if (is_int($val)) {
                    return $val;
                } else {
                    if (is_string($val)) {
                        $val = intval($val);
                    }
                    return is_int($val) ? $val : 0;
                }
        }
        return null;
    }

}
