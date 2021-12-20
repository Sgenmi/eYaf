<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 23:05
 */

namespace Sgenmi\eYaf\Model;
use Medoo\Medoo;

interface ModelInface
{
    public function getDb():Medoo;

    public function setDb( Medoo $db);

    // 统一判断用户提交数据,省去重复判断
    public function checkField(array $data,array $field=[]);

    public function select($join, $columns = null, $where = null, $is_slave = false);

    public function insert(array $datas);

    public function update(array $datas, $where = null);

    public function delete($where);

    public function replace($columns, $where = null);

    public function get($join = null, $columns = null, $where = null, $is_slave = false);

    public function has(array $join, $where = null);

    public function rand($join = null, $columns = null, $where = null, $is_slave = false);

    public function count($join = null, $column = null, $where = null, $is_slave = false);

    public function sum(array $join, $column = null, $where = null, $is_slave = false);

    public function avg(array $join, $column = null, $where = null);

    public function max(array $join, $column = null, $where = null);

    public function min(array $join, $column = null, $where = null);

    public function action(callable $actions);

    public function debug();

    public function error();

    public function last();

    public function log();

}
