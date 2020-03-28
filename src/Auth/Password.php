<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/27 23:43
 */

namespace Sgenmi\eYaf\Auth;

class Password
{
    /**
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verify(string $password, string $hash):bool {
        return password_verify($password,$hash);
    }

    /**
     * @desc 官方建议数据库字段255个字符
     * @param string $password
     * @return string
     */
    public static function hash(string $password):string {
       return password_hash($password, PASSWORD_DEFAULT);
    }
}