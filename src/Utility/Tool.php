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
    public static function centToYuan(int $val): float
    {
        return sprintf("%.2f", $val / 100);
    }
    /**
     * @desc 元转分
     * @param float $val
     * @return float|int
     */
    public static function fn_yuanToCent(float $val)
    {
        return $val * 100;
    }

}