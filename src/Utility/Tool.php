<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/29 14:20
 */

namespace Sgenmi\eYaf\Utility;


class Tool
{
    // 获取IP
   public static function getClientIp()
    {
        $ser =(array) ((new \Yaf\Request\Http())->getServer());
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

}