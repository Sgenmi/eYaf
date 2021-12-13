<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2021/12/13 下午4:04
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eyaf;


class Config
{
    const TOKEN_USER_KEY="web:user";  //用户登录token 前辍
    const TOKEN_ADMIN_KEY="admin:user";  //管理后台登录token前辍

    /**
     * @param string $key
     * @param $val
     * @return bool
     */
    public static function set(string $key,$val):bool {
        if(!$key){
            return false;
        }
        $config = \Yaf\Registry::get('_config');
        if(strpos($key,'.')!==false){
            $keys = explode('.',$key,2);
            $config[$keys[0]?:0][$keys[1]?:0] = $val;
        }else{
            $config[$key] = $val;
        }
        \Yaf\Registry::set('_config',$config);
        return  true;
    }

    /**
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public static function get(string $key,$default=''){
        $config = \Yaf\Registry::get('_config');
        if(!$key){
            return $config;
        }
        if(strpos($key,'.')!==false){
            $keys = explode('.',$key,2);
            $ret = $config[$keys[0]?:0][$keys[1]?:0] ?? $default;
        }else{
            $ret = $config[$key]?? $default;
        }
        return $ret;
    }

}
