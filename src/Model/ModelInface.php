<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 23:05
 */

namespace Sgenmi\eYaf\Model;

interface ModelInface
{
    // 统一判断用户提交数据,省去重复判断
    public function checkField(array $data,array $field=[]);

    public function select($join, $columns = null, $where = null, $is_slave = false);

    public function insert(array $datas,?string $primaryKey = null);

    public function update(array $datas, $where = null);

    public function delete($where);

    public function replace($columns, $where = null);

    public function get($join = null, $columns = null, $where = null, $is_slave = false);

    public function has(array $join, $where = null);

    public function rand($join = null, $columns = null, $where = null, $is_slave = false);

    public function count($join = null, $column = null, $where = null, $is_slave = false);

    public function sum($join, $column = null, $where = null, $is_slave = false);

    public function avg($join, $column = null, $where = null);

    public function max($join, $column = null, $where = null);

    public function min($join, $column = null, $where = null);

    public function action(callable $actions);

    public function debug();

    public function error();

    public function last();

    public function log();

}
