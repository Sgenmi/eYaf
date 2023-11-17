<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2022/1/17 7:00 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Model;

use Medoo\Medoo as catfanMedoo;
use PDOStatement;

class Medoo extends catfanMedoo
{

    const LOCK_FOR_UPDATE = ' FOR UPDATE';
    const LOCK_SHARE = ' LOCK IN SHARE MODE';

    /**
     * @param string $statement
     * @param array $map
     * @param callable|null $callback
     * @return PDOStatement|null
     */
    public function exec(string $statement, array $map = [], callable $callback = null): ?PDOStatement
    {
        try {
            $res =  parent::exec($statement,$map,$callback);
        }catch (\PDOException $e) {
            // 超时重链
            if($e->getCode()=='HY000'){
                //协程下，删除保存原链接
                if(extension_loaded('swoole')){
                    $coId = \Swoole\Coroutine::getCid();
                    if($coId>0){
                        Coroutine::delCon($coId);
                    }
                }
                $config = Model::getDBConfig(true);
                parent::__construct($config);
                $res =  parent::exec($statement,$map,$callback);
            }else{
                throw $e;
            }
        }
        return $res;
    }

    /**
     * @param string $table
     * @param array $values
     * @param string|null $primaryKey
     * @return PDOStatement|null
     */
    public function insertUpdate(string $table, array $values, string $primaryKey = null): ?PDOStatement
    {
        $this->beginDebug();
        $this->insert($table,$values,$primaryKey);
        $logArr = $this->debugLog();
        $columns =[];
        foreach ($values as $v){
            $columns = array_keys($v);
            if($columns){
                break;
            }
        }
        $columnArr=[];
        foreach ($columns as $v){
            $columnArr[] = "`{$v}`=values(`{$v}`)";
        }
        $sql = end($logArr). ' ON DUPLICATE KEY UPDATE ' .implode(',',$columnArr);
        return  $this->exec($sql);
    }

}