<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 23:04
 */

namespace Sgenmi\eYaf\Model;

use Medoo\Medoo;

abstract class Model implements ModelInface
{
    protected $db;
    private $readDB;
    private $writeDB;
    protected $check_rule;
    public $table;
    // protected $check_rule = [
    // 'user_name' => [
    // 'required' => [true, "用户名不能为空"], //or 'required' => "用户名不能为空"
    // 'range' => [[6, 30], "用户名只能6到30个字符"]
    // ],
    // 'password' => [
    // 'required' => [true, "用户名密码不能为空"],
    // 'range' => [[6, 32], "用户名密码只能6到32个字符"]
    // ]
    // ];
    protected $check_field;

    // public function checkLogin($d)
    // {
    // $this->rData = $d;
    // $this->check_field = [
    // 'user_name', 'password'
    // ];
    // return $this->check_data($d);
    // }
    public function __construct()
    {
        // 这里可以设置读写分离
        // 如果未设置从库，则readDB也是调用主库
        $this->writeDB = \Yaf\Registry::get('_masterDB');
        $this->readDB = \Yaf\Registry::get('_slaveDB');
        $this->db = $this->writeDB;
        // $this->db = new Medoo\Medoo();
    }

    public function checkField($data, $field = [])
    {
        if(!$data){
            return "请求参数为空";
        }
        if (! $field) {
            $field = array_keys($data);
        }
        $this->check_field = $field;
        return $this->check_data($data);
    }

    public function getDb()
    {
        return $this->db;
    }

    public function setDb($db)
    {
        if(empty($db)){
            return $this;
        }
        if(!($db instanceof Medoo)){
            $className =  get_class($db);
            throw new \Exception($className .' not Medoo instance, please inject Medoo instance' );
        }
        $this->writeDB = $db;
        $this->readDB = $db;
        $this->db = $db;
        return $this;
    }

    // 统一判断用户提交数据,省去重复判断
    private function check_data($d)
    {
        $ret = TRUE;

        if (!$this->check_field) {
            return $ret;
        }
        $check_field = array_flip($this->check_field);

        foreach ($d as $k => $v) {

            // 判断是否需要验证字段
            if (!isset($check_field[$k])) {
                continue;
            }
            unset($check_field[$k]);
            if (!isset($this->check_rule[$k])) {
                continue;
            }
            foreach ($this->check_rule[$k] as $_k => $fv) {

                switch ($_k) {
                    case 'required': // 必填

                        if (is_string($fv)) {
                            $fv = [
                                true,
                                $fv
                            ];
                        }
                        if ($fv[0]) {

                            if (!is_numeric($v) && $v !== 0 && empty($v) && !isset($this->check_rule[$k]['in'])) { // 注意有0情况
                                $ret = $fv[1];
                            }
                        }
                        break;
                    case 'range': // 长度范围

                        if ($v) {
                            $len = mb_strlen($v, 'utf-8');
                            if (is_array($fv)) {
                                if ($len < $fv[0][0] || $len > $fv[0][1]) {
                                    $ret = $fv[1];
                                }
                            } else {
                                if ($len != $fv[0]) {
                                    $ret = $fv[1];
                                }
                            }
                        }
                        break;
                    case 'pattern':
                        if ($v && !preg_match($fv[0], $v)) {
                            $ret = $fv[1];
                        }
                        break;
                    case 'in':
                        if ($v && !in_array($v, $fv[0])) {
                            $ret = $fv[1];
                        }
                        break;
                    case 'integer':
                        if ($v && is_string($fv)) {
                            $fv = [
                                true,
                                $fv
                            ];
                        }
                        if ($v && $fv[0]) {
                            if (!is_numeric($v) || strpos($v, ".") !== false) {
                                $ret = $fv[1];
                            }
                        }
                        break;
                    case 'numeric':
                        if ($v && !is_numeric($v)) {
                            $ret = $fv[1];
                        }
                        break;
                    case 'date':
                        if (!strtotime($v)) {
                            $ret = $fv[1];
                        }
                        break;
                    case 'array':
                        if (is_string($fv)) {
                            $fv = [
                                true,
                                $fv
                            ];
                        }
                        if($fv[0]){
                            if(!is_array($v)){
                                $ret = $fv[1];
                            }
                        }
                        break;

                }
                if ($ret !== true) {
                    return $ret;
                }
            }
        }

        if ($ret == true && $check_field) {
            $ret = "参数不全";
        }

        return $ret;
    }

    public function select($join, $columns = null, $where = null, $is_slave = false)
    {
        if ($is_slave) {
            return $this->readDB->select($this->table, $join, $columns, $where);
        } else {
            return $this->writeDB->select($this->table, $join, $columns, $where);
        }
    }

    public function insert($datas)
    {
        $smt = $this->writeDB->insert($this->table, $datas);
        if (!empty($smt)) {
            if ($smt->rowCount() > 0) {
                return $this->writeDB->id();
            }
        }
        return false;
    }

    public function update($datas, $where = null)
    {
        $updateObj = $this->writeDB->update($this->table, $datas, $where);
        if (!empty($updateObj)) {
            return $updateObj->rowCount()?true:false;
        }
        return false;
    }

    public function delete($where)
    {
        return $this->writeDB->delete($this->table, $where);
    }

    public function replace($columns, $where = null)
    {
        return $this->writeDB->replace($this->table, $columns, $where);
    }

    public function get($join = null, $columns = null, $where = null, $is_slave = false)
    {
        if ($is_slave) {
            return $this->readDB->get($this->table, $join, $columns, $where);
        } else {
            return $this->writeDB->get($this->table, $join, $columns, $where);
        }
    }

    public function has($join, $where = null)
    {
        return $this->writeDB->has($this->table, $join, $where);
    }

    public function rand($join = null, $columns = null, $where = null, $is_slave = false)
    {
        if ($is_slave) {
            return $this->readDB->rand($this->table, $join, $columns, $where);
        } else {
            return $this->writeDB->rand($this->table, $join, $columns, $where);
        }
    }

    public function count($join = null, $column = null, $where = null, $is_slave = false)
    {
        if ($is_slave) {
            return $this->readDB->count($this->table, $join, $column, $where) + 0;
        } else {
            return $this->writeDB->count($this->table, $join, $column, $where) + 0;
        }
    }

    public function sum($join, $column = null, $where = null, $is_slave = false)
    {
        if ($is_slave) {
            return $this->readDB->sum($this->table, $join, $column, $where) + 0;
        } else {
            return $this->writeDB->sum($this->table, $join, $column, $where) + 0;
        }
    }

    public function avg($join, $column = null, $where = null)
    {
        return $this->writeDB->avg($this->table, $join, $column, $where);
    }

    public function max($join, $column = null, $where = null)
    {
        return $this->writeDB->max($this->table, $join, $column, $where);
    }

    public function min($join, $column = null, $where = null)
    {
        return $this->writeDB->min($this->table, $join, $column, $where);
    }

    public function action($actions)
    {
        return $this->writeDB->action($actions);
    }

    public function debug()
    {
        $this->writeDB->debug();
        return $this;
    }

    public function error()
    {
        return $this->writeDB->error;
    }

    public function last()
    {
        return $this->writeDB->last();
    }

    public function log()
    {
        return $this->writeDB->log();
    }

    public function query($sql, $pdo)
    {
        return $this->writeDB->query($sql)->fetchAll($pdo);
    }

    public function raw($string, $map = [])
    {
        return $this->writeDB->raw($string, $map);
    }

    public function beginDebug(){
        return $this->writeDB->beginDebug();
    }

    public function debugLog(){
        return $this->writeDB->debugLog();
    }

    public function errorInfo()
    {
        return $this->writeDB->errorInfo;
    }

}
