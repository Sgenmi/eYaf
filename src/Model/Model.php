<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 23:04
 */

namespace Sgenmi\eYaf\Model;
use Medoo\Raw;
use Yaf\Exception;

abstract class Model implements ModelInface
{
    /**
     * @var Medoo|mixed
     */
    protected $db;
    /**
     * @var Medoo
     */
    private $readDB;

    /**
     * @var Medoo
     */
    private $writeDB;
    /**
     * @var array
     */
    protected $check_rule;

    /**
     * @var string
     */
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

    /**
     * @var array
     */
    protected $check_field;

    /**
     * @var \PDOStatement;
     */
    protected $statement;

    // public function checkLogin($d)
    // {
    // $this->rData = $d;
    // $this->check_field = [
    // 'user_name', 'password'
    // ];
    // return $this->check_data($d);
    // }

    // 这里可以设置读写分离
    // 如果未设置从库，则readDB也是调用主库
    /**
     * @throws Exception
     */
    public function __construct()
    {
        if(extension_loaded('swoole')){
            $coId = \Swoole\Coroutine::getCid();
            if($coId>0){
                $this->co_db($coId);
            }else{
                $this->fpm_db();
            }
        }else{
            $this->fpm_db();
        }
    }

    /**
     *  FPM , Swoole Coroutine=false
     * @return void
     * @throws Exception
     */
    private function fpm_db(){
        $writeDB = \Yaf\Registry::get('_masterDB');
        if(!$writeDB || !($writeDB instanceof Medoo)){
            $masterConfig = $this->getDBConfig(true);
            $writeDB = $this->getMedoo($masterConfig);
            if(!$writeDB){
                throw new Exception("master db connect fail");
            }
            \Yaf\Registry::set('_masterDB',$writeDB);
        }
        $readDB = \Yaf\Registry::get('_slaveDB');
        if(!$readDB || !($readDB instanceof Medoo)){
            $slaveConfig = $this->getDBConfig(false);
            if($slaveConfig){
                $readDB = $this->getMedoo($slaveConfig);
            }else{
                $readDB =  $writeDB;
            }
            if(!$readDB){
                throw new Exception("slave db connect fail");
            }
            \Yaf\Registry::set('_slaveDB',$readDB);
        }
        $this->writeDB = $writeDB;
        $this->readDB = $readDB;
        $this->db= $this->writeDB;
    }

    /**
     *  Swoole, Coroutine
     * @param $coId
     * @return void
     * @throws Exception
     */
    private function co_db($coId){
        $writeDB = Coroutine::getCon($coId);
        //重新链接
        if(!$writeDB || !($writeDB instanceof Medoo)){
            $masterConfig = $this->getDBConfig(true);
            $writeDB = $this->getMedoo($masterConfig);
            if(!$writeDB){
                throw new Exception("master db connect fail in coroutine");
            }
            Coroutine::setCon($coId,$writeDB);
            \Swoole\Coroutine::defer(function () use($coId,$writeDB){
                $writeDB->pdo = null;
                Coroutine::delCon($coId);
            });
        }
        $this->writeDB = $writeDB;
        $this->readDB = $this->writeDB;
        $this->db= $this->writeDB;
    }

    /**
     * @param $config
     * @return Medoo|null
     */
    private function getMedoo($config):?Medoo
    {
        try {
            return new Medoo($config);
        }catch (\PDOException $e){
            echo '_masterDB:'.$e->getMessage().PHP_EOL;
        }
        return null;
    }

    /**
     * 获取链接db,兼容升级
     * @param bool $isMaster
     * @return array
     */
    public static function getDBConfig(bool $isMaster = false):array
    {
        $_config = \Yaf\Registry::get('_config');
        $options=[];
        if ($isMaster) {
            if(is_object($_config)){
                if(!empty($_config->database->params->master)){
                    $options = $_config->database->params->master->toArray();
                }
            }else{
                $options = $_config['database']['params']['master']??[];
            }

        } else {
            // 如果没有设置从库，就直接选主库
            if(is_object($_config)){
                $slaveArr = $_config->database->params->slave->toArray();
            }else{
                $slaveArr = $_config['database']['params']['slave']??[];
            }
            if (!empty($slaveArr)) {
                if(isset($slaveArr['host'])){
                    $options = $slaveArr;
                }else{
                    $randKey = array_rand($slaveArr, 1);
                    $options = $slaveArr[$randKey];
                }
            }
        }
        //兼容medoo 1.x 升级 2.x
        if($options && isset($options['database_name'])){
            $options['type'] = $options['database_type'];
            $options['database'] = $options['database_name'];
            $options['host'] = $options['server'];
        }
        return $options;
    }


    /**
     * @param array $data
     * @param array $field
     * @return bool|mixed|string
     */
    public function checkField(array $data, array $field = [])
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

    /**
     * @return Medoo
     */
    public function getDb():Medoo
    {
        return $this->db;
    }

    /**
     * @param Medoo $db
     * @return $this
     * @throws \Exception
     */
    public function setDb(Medoo $db):Model
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
    private function check_data(array $d)
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

    /**
     * @param array|string $join
     * @param array|string $columns
     * @param array $where
     * @param bool $is_slave
     * @return array|null
     */
    public function select($join, $columns = null, $where = null, $is_slave = false):?array
    {
        if ($is_slave) {
            return $this->readDB->select($this->table, $join, $columns, $where);
        } else {
            return $this->writeDB->select($this->table, $join, $columns, $where);
        }
    }

    /**
     * @param array $datas
     * @param string|null $primaryKey
     * @return bool|int
     */
    public function insert(array $datas,string $primaryKey = null)
    {
        $statement = $this->writeDB->insert($this->table, $datas);
        $this->statement = $statement;
        if (!empty($statement)) {
            if ($statement->rowCount() > 0) {
                return (int)($this->writeDB->id());
            }
        }
        return false;
    }

    /**
     * @param array $datas
     * @param array $where
     * @return bool
     */
    public function update(array $datas, $where = null):bool
    {
        $statement = $this->writeDB->update($this->table, $datas, $where);
        $this->statement = $statement;
        if (!empty($statement)) {
            return true;
        }
        return false;
    }

    /**
     * @param array|Raw $where
     * @return bool
     */
    public function delete($where):bool
    {
        $statement =  $this->writeDB->delete($this->table, $where);
        $this->statement = $statement;
        if (!empty($statement)) {
            return true;
        }
        return false;
    }

    /**
     * @param array $columns
     * @param array $where
     * @return bool
     */
    public function replace($columns, $where = null):bool
    {
        $statement =  $this->writeDB->replace($this->table, $columns, $where);
        if (!empty($statement)) {
            return true;
        }
        return false;
    }

    /**
     * @param array|string $join
     * @param array|string $columns
     * @param array $where
     * @param bool $is_slave
     * @return mixed
     */
    public function get($join = null, $columns = null, $where = null, $is_slave = false)
    {
        if ($is_slave) {
            return $this->readDB->get($this->table, $join, $columns, $where);
        } else {
            return $this->writeDB->get($this->table, $join, $columns, $where);
        }
    }

    /**
     * @param array $join
     * @param array $where
     * @return bool
     */
    public function has(array $join, $where = null):bool
    {
        return $this->writeDB->has($this->table, $join, $where);
    }

    /**
     * @param array|string $join
     * @param array|string $columns
     * @param array $where
     * @param bool $is_slave
     * @return array
     */
    public function rand($join = null, $columns = null, $where = null, $is_slave = false):?array
    {
        if ($is_slave) {
            return $this->readDB->rand($this->table, $join, $columns, $where);
        } else {
            return $this->writeDB->rand($this->table, $join, $columns, $where);
        }
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @param bool $is_slave
     * @return int|null
     */
    public function count($join = null, $column = null, $where = null, $is_slave = false):?int
    {
        if ($is_slave) {
            return $this->readDB->count($this->table, $join, $column, $where) + 0;
        } else {
            return $this->writeDB->count($this->table, $join, $column, $where) + 0;
        }
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @param bool $is_slave
     * @return string|null
     */
    public function sum($join, $column = null, $where = null, $is_slave = false):?string
    {
        if ($is_slave) {
            return $this->readDB->sum($this->table, $join, $column, $where);
        } else {
            return $this->writeDB->sum($this->table, $join, $column, $where);
        }
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public function avg($join, $column = null, $where = null): ?string
    {
        return $this->writeDB->avg($this->table, $join, $column, $where);
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public function max($join, $column = null, $where = null): ?string
    {
        return $this->writeDB->max($this->table, $join, $column, $where);
    }

    /**
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     */
    public function min($join, $column = null, $where = null): ?string
    {
        return $this->writeDB->min($this->table, $join, $column, $where);
    }


    /**
     * @param callable $actions
     * @return bool
     */
    public function action(callable $actions):bool
    {
        $ret = true;
        try {
            $this->writeDB->action($actions);
        }catch (\Throwable $e){
            $this->writeDB->error = $e->getMessage();
            $ret =  false;
        }
        return $ret;
    }

    /**
     * @param array $columns
     * @param array|null $options
     * @return bool
     */
    public function create(array $columns, array $options = null):bool {
        $this->statement = $this->writeDB->create($this->table,$columns,$options);
        if(!empty($this->statement)){
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function drop():bool {
        $this->statement = $this->writeDB->drop($this->table);
        if(!empty($this->statement)){
            return true;
        }
        return false;
    }

    /**
     * @return $this
     */
    public function debug():Model
    {
        $this->writeDB->debug();
        return $this;
    }

    /**
     * @return string|null
     */
    public function error():?string
    {
        return $this->writeDB->error;
    }

    /**
     * @return string|null
     */
    public function last():?string
    {
        return $this->writeDB->last();
    }

    /**
     * @return array
     */
    public function log():array
    {
        return $this->writeDB->log();
    }

    /**
     * @param string $sql
     * @param int $pdo_fetch
     * @return array|bool|null
     */
    public function query(string $sql, int $pdo_fetch = \PDO::FETCH_ASSOC):?array
    {
        $this->statement =  $this->writeDB->query($sql);
        if(!empty($this->statement)){
            return $this->statement->fetchAll($pdo_fetch);
        }
        return false;
    }

    /**
     * @param string $string
     * @return string
     */
    public function quote(string $string):?string {
       return $this->writeDB->quote($string);
    }

    /**
     * @return string
     */
    public function tableQuote():string {
        return $this->writeDB->tableQuote($this->table);
    }

    /**
     * @param string $string
     * @param array $map
     * @return Medoo|Raw
     */
    public function raw(string $string, array $map = []):?Raw
    {
        return $this->writeDB->raw($string, $map);
    }

    /**
     *
     */
    public function beginDebug():void
    {
        $this->writeDB->beginDebug();
    }

    /**
     * @return array
     */
    public function debugLog():array {
        return $this->writeDB->debugLog();
    }

    /**
     * @return array|null
     */
    public function errorInfo():?array
    {
        return $this->writeDB->errorInfo;
    }

    /**
     * @return array
     */
    public function info():array {
        return $this->writeDB->info();
    }

    /**
     * @param array $values
     * @param string|null $primaryKey
     * @return bool
     */
    public function insertUpdate(array $values, string $primaryKey = null):bool{
        try {
            $this->writeDB->insertUpdate($this->table,$values,$primaryKey);
            $ret = true;
        }catch (\Exception $e){
            $ret = false;
            throw $e;
        }
        return $ret;
    }

}
