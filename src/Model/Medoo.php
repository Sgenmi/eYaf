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

    private array $config=[];

    public function __construct(array $options)
    {
        $this->config = $options;
        parent::__construct($options);
    }

    /**
     * @param string $table
     * @param array $map
     * @param $join
     * @param $columns
     * @param $where
     * @param $columnFn
     * @return string
     * @author Sgenmi
     */
    protected function selectContext(string $table, array &$map, $join, &$columns = null, $where = null, $columnFn = null): string
    {
        preg_match('/(?<table>[\p{L}_][\p{L}\p{N}@$#\-_]*)\s*\((?<alias>[\p{L}_][\p{L}\p{N}@$#\-_]*)\)/u', $table, $tableMatch);

        if (isset($tableMatch['table'], $tableMatch['alias'])) {
            $table = $this->tableQuote($tableMatch['table']);
            $tableAlias = $this->tableQuote($tableMatch['alias']);
            $tableQuery = "{$table} AS {$tableAlias}";
        } else {
            $table = $this->tableQuote($table);
            $tableQuery = $table;
        }

        $isJoin = $this->isJoin($join);

        if ($isJoin) {
            $tableQuery .= ' ' . $this->buildJoin($tableAlias ?? $table, $join, $map);
        } else {
            if (is_null($columns)) {
                if (
                    !is_null($where) ||
                    (is_array($join) && isset($columnFn))
                ) {
                    $where = $join;
                    $columns = null;
                } else {
                    $where = null;
                    $columns = $join;
                }
            } else {
                $where = $columns;
                $columns = $join;
            }
        }

        if (isset($columnFn)) {
            if ($columnFn === 1) {
                $column = '1';

                if (is_null($where)) {
                    $where = $columns;
                }
            } elseif ($raw = $this->buildRaw($columnFn, $map)) {
                $column = $raw;
            } else {
                if (empty($columns) || $this->isRaw($columns)) {
                    $columns = '*';
                    $where = $join;
                }

                $column = $columnFn . '(' . $this->columnPush($columns, $map, true) . ')';
            }
        } else {
            $column = $this->columnPush($columns, $map, true, $isJoin);
        }
        //force index
        if(!empty($where['INDEX']) && $this->type=='mysql'){
            if($this->isRaw($where['INDEX'])){
                $forceIndex= $this->buildRaw($where['INDEX'],$map);
            }else{
                $forceIndex = $where['INDEX'];
            }
            if($isJoin){
                $tableQuery = str_replace(sprintf('%s ',$table),sprintf('%s FORCE INDEX(`%s`) ',$table,$forceIndex),$tableQuery);
            }else{
                $tableQuery .=sprintf(' FORCE INDEX(%s)',$forceIndex);
            }
            unset($where['INDEX']);
        }
        //lock
        $lock = $where['LOCK']??'';
        if($lock){
            unset($where['LOCK']);
        }
        $whereClause = $this->whereClause($where, $map);
        if(!empty($lock) && $this->type=='mysql'){
            $whereClause.= " ". $lock;
        }
        return 'SELECT ' . $column . ' FROM ' . $tableQuery . $whereClause;
    }


    /**
     * @param string $statement
     * @param array $map
     * @param callable|null $callback
     * @return PDOStatement|null
     */
    public function exec(string $statement, array $map = [], ?callable $callback = null): ?PDOStatement
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
                parent::__construct($this->config);
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
    public function insertUpdate(string $table, array $values, ?string $primaryKey = null): ?PDOStatement
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