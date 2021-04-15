<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/27 23:44
 */

namespace Sgenmi\eYaf\Auth;

use Sgenmi\eYaf\Cache\Redis;

class Token
{
    const USER_TOKEN ='k:u:token:';
    const USER_ID_SET = 'z:uid:token:';

    /**
     * @param string $key
     * @return array
     * @throws \Yaf\Exception
     */
    public static function getTokenInfo( string $key='admin'):array
    {
        $token = self::getToken();
        if (! $token) {
            return [];
        }
        $_key = self::USER_TOKEN .$key.':'. $token;
        $redis = new Redis();
        $tokenInfo = $redis->get($_key);
        $info = [];
        if ($tokenInfo) {
            $info=json_decode($tokenInfo, true);
            $score = $redis->zScore(self::USER_ID_SET.$key.':'.$info['id'],$token);
            if(!$score){
                return [];
            }
        }
        return $info;
    }

    /**
     * @return mixed
     */
    public static function getToken()
    {
        自定义名称
        $headerToken= "TOKEN";
        if(defined('HEADER_TOKEN') && HEADER_TOKEN ){
            $headerToken = HEADER_TOKEN;
        }
        return (new \Yaf\Request\Http())->getServer("HTTP_".strtoupper($headerToken), '');
    }

    /**
     * @param string $key
     * @return int
     * @throws \Yaf\Exception
     */
    public static function getUserId(string $key='admin'):int
    {
        $info = self::getTokenInfo($key);
        return $info['id']??0;
    }

    /**
     * @param string $key
     * @return int
     * @throws \Yaf\Exception
     */
    public static function getGroupId(string $key='admin'):int
    {
        $info = self::getTokenInfo($key);
        return $info['group_id']??0;
    }


    /**
     * @param string $token
     * @param array $data
     * @param int $expire
     * @param string $key
     * @return bool
     */
    public static function setToken(string $token, array $data, int $expire=864000, string $key='admin'):bool {

        if(isset($data['id'])){
            throw new \Yaf\Exception('setToken: No id field was found in the data array');
        }
        if(empty($data['id'])){
            throw new \Yaf\Exception('setToken: Id field cannot be empty');
        }
        $redis = new Redis();
        $redis->set(self::USER_TOKEN .$key.':'.$token, json_encode($data), $expire);
        $redis->zAdd(self::USER_ID_SET.$key.':'.$data['id'],1,$token);
        return true;
    }

    /**
     * @param int $uid
     * @param string $key
     * @return bool
     * @throws \Yaf\Exception
     */
    public static function del(int $uid=0, string $key='admin'):bool {

        $redis = new Redis();
        if($uid>0){
            $delKey = self::USER_ID_SET.$key.':'.$uid;
            if(!$redis->exists($delKey)){
                return true;
            }
            return $redis->del($delKey);
        }else{
            $token = self::getToken();
            if (! $token) {
                return true;
            }
            $delKey =self::USER_TOKEN .$key.':'. $token;
            if(!$redis->exists($delKey)){
                return true;
            }
            return $redis->del($key);
        }

    }

}
