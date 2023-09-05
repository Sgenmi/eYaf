<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2021/12/13 下午4:04
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf;

use Sgenmi\eYaf\Contract\ConfigInterface;

class Config implements ConfigInterface {

    /**
     * @param string $key
     * @param mixed $val
     * @return bool
     */
    public static function set(string $key,mixed $val):bool {
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
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default=''): mixed
    {
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
