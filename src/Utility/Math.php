<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2022/9/14 9:25 AM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Utility;

use Exception;

class Math
{

    private static string  $error="";
    private static ?int $scale=2;

    /**
     * @param int|null $scale
     * @return Math
     */
    public function setScale(?int $scale):Math {
        self::$scale = $scale;
        return $this;
    }

    /**
     * @return int
     */
    public static function getScale():?int {
        return self::$scale;
    }

    /**
     * 加法
     * @param $num1
     * @param $num2
     * @param ...$num3
     * @return string
     */
    public static function add($num1,$num2,...$num3): string {
        $arr = func_get_args();
        $ret="0";
        foreach ($arr as $v){
            if(is_numeric($v)){
                $ret= bcadd(strval($ret),strval($v),self::$scale);
            }
        }
        return $ret;
    }

    /**
     * 减法
     * @param $num1
     * @param $num2
     * @param ...$num3
     * @return string
     */
    public static function sub($num1,$num2,...$num3):string{
        $arr = func_get_args();
        $ret=$arr[0];
        unset($arr[0]);
        foreach ($arr as $v){
            if(is_numeric($v)){
                $ret= bcsub(strval($ret),strval($v),self::$scale);
            }
        }
        return $ret;
    }

    /**
     * 任意精度数字乘法计算
     * @param $num1
     * @param $num2
     * @param ...$num3
     * @return string
     */
    public static function mul($num1,$num2,...$num3):string{
        $arr = func_get_args();
        $ret='1';
        foreach ($arr as $v){
            if(is_numeric($v)){
                $ret= bcmul(strval($ret),strval($v),self::$scale);
            }
        }
        return $ret;
    }

    /**
     * 任意精度的数字除法计算
     * @param $num1
     * @param $num2
     * @return string
     * @throws Exception
     */
    public static function div($num1,$num2):string {
        if(!is_numeric($num1)){
            throw new Exception("num1 不是数字类型");
        }
        if(!is_numeric($num2)){
            throw new Exception("num2 不是数字类型");
        }
        try {
            $ret =  bcdiv(strval($num1),strval($num2),self::$scale);
        }catch (\Throwable $e){
            self::$error = $e->getMessage();
            $ret ='0';
        }
        return is_null($ret)?'0':$ret;
    }

    /**
     * @return string
     */
    public static function getError():string{
        return self::$error;
    }
}




