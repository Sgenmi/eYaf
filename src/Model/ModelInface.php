<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 23:05
 */

namespace Sgenmi\eYaf\Model;


interface ModelInface
{
    public function getDb();

    // 统一判断用户提交数据,省去重复判断
    public function checkField($data,$field=[]);

    public function select($join, $columns = null, $where = null, $is_slave = false);

    public function insert($datas);

    public function update($datas, $where = null);

    public function delete($where);

    public function delete_virtual($where);

    public function replace($columns, $where = null);

    public function get($join = null, $columns = null, $where = null, $is_slave = false);

    public function has($join, $where = null);

    public function rand($join = null, $columns = null, $where = null, $is_slave = false);

    public function count($join = null, $column = null, $where = null, $is_slave = false);

    public function sum($join, $column = null, $where = null, $is_slave = false);

    public function avg($join, $column = null, $where = null);

    public function max($join, $column = null, $where = null);

    public function min($join, $column = null, $where = null);

    public function action($actions);

    public function debug();

    public function error();

    public function last();

    public function log();

}