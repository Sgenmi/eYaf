<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/26 23:04
 */

namespace Sgenmi\eYaf\Model;
use Medoo\Raw;
use RuntimeException;
use Sgenmi\eYaf\Context;
use Sgenmi\eYaf\Di\Container;
use Sgenmi\eYaf\Pool\PoolFactory;
use Swoole\Coroutine;
use Throwable;
use Yaf\Exception;

abstract class Model implements ModelInface
{
    /**
     * @var Medoo|mixed
     * @deprecated
     */
    protected $db;
    /**
     * @var Medoo
     * @deprecated
     */
    private $readDB;

    /**
     * @var Medoo
     * @deprecated
     */
    private $writeDB;
    /**
     * @var array
     * @deprecated
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
     * @deprecated
     */
    protected $statement;

    private ?array $errorInfo=null;
    private ?string $error=null;
    private ?string $lastSql="";
    private array $logs=[];

    protected string $connect="master";



    public function __construct()
    {
//        if(extension_loaded('swoole') && ($coId= Coroutine::getCid())>0){
//            $this->co_db($coId);
//        }else{
//            $this->fpm_db();
//        }
    }

    /**
     *  FPM
     * @param bool $isSlave
     * @return Medoo
     * @throws Exception
     */
    private function fpm_db(bool $isSlave=false):Medoo{
        $contextKey = $this->getContextKey($isSlave);
        $medoo = \Yaf\Registry::get($contextKey);
        if(!$medoo || !($medoo instanceof Medoo)){
            $masterConfig = $this->getDBConfig($this->connect,$isSlave);
            $medoo = $this->getMedoo($masterConfig);
            if(!$medoo){
                throw new Exception($this->connect." db connect fail");
            }
            \Yaf\Registry::set($contextKey,$medoo);
        }
//        $this->writeDB = $writeDB;
//        $this->readDB = $readDB;
//        $this->db= $this->writeDB;
        return $medoo;
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
            Coroutine::defer(function () use($coId,$writeDB){
                $writeDB->pdo = null;
                Coroutine::delCon($coId);
            });
        }
//        $this->writeDB = $writeDB;
//        $this->readDB = $this->writeDB;
//        $this->db= $this->writeDB;
        return $writeDB;
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
     * @param string $connect
     * @param bool $isSlave
     * @return string
     * @author Sgenmi
     */
    private static function getConnectName(string $connect,bool $isSlave = false): string
    {
       return $isSlave? ($connect=='master'?"slave":$connect."_slave"): $connect;
    }

    /**
     * 获取链接db,兼容升级
     * @param string $connect
     * @param bool $isSlave
     * @return array
     */
    public static function getDBConfig(string $connect,bool $isSlave = false):array
    {
        $_config = \Yaf\Registry::get('_config');
        $options=[];
        if (!$isSlave) {
            if(is_object($_config)){
                if(!empty($_config->database->params->$connect)){
                    $options = $_config->database->params->$connect->toArray();
                }
            }else{
                $options = $_config['database']['params'][$connect]??[];
            }
        } else {
            $connect = self::getConnectName($connect,$isSlave);
            // 如果没有设置从库，就直接选主库
            if(is_object($_config)){
                $slaveArr = $_config->database->params->$connect->toArray();
            }else{
                $slaveArr = $_config['database']['params'][$connect]??[];
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

        if(!$options){
            throw new RuntimeException($connect." db config not fund");
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
     */
    public function setDb(Medoo $db):Model
    {
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
     * @param bool $isSlave
     * @return array
     * @throws Throwable
     * @author Sgenmi
     */
    private function _getMedoo(bool $isSlave=false):array{
        $coId = $this->getCoroutineId();
        $poolConnection=null;
        $contextKey = $this->getContextKey($isSlave);
        if($coId){
            if(defined("IS_DISABLE_POOL") && IS_DISABLE_POOL){
                $medoo = $this->co_db($coId);
            }else{
                $hasContextConnection = Context::get($contextKey);
                if($hasContextConnection){
                    $poolConnection =$hasContextConnection;
                }else{
                    $con = Container::getInstance();
                    $poolConnection = $con->get(PoolFactory::class)->getPool(self::getConnectName($this->connect,$isSlave))->get()->getConnection();
                }
                $medoo = $poolConnection->getMedoo();
            }
        }else{
            $medoo = $this->fpm_db($isSlave);
        }
        return [
            'name'=>$contextKey,
            'medoo'=>$medoo,
            'coroutine_id'=>$coId,
            'pool_connection'=>$poolConnection
        ];
    }

    /**
     * @return int
     * @author Sgenmi
     */
    private function getCoroutineId(): int
    {
        if( extension_loaded('swoole') && (($coId = Coroutine::getCid())>0)){
            return intval($coId);
        }
        return 0;
    }

    /**
     * @param bool $isSlave
     * @return string
     * @author Sgenmi
     */
    private function getContextKey(bool $isSlave=false):string {
        return  $isSlave ? sprintf('database:%s:slave',$this->connect): 'database:'.$this->connect;
    }


    /**
     * @param array $medooInfo
     * @param string $action
     * @return void
     * @author Sgenmi
     */
    private function _finally(array $medooInfo,string $action=''): void
    {
        $this->error = $medooInfo['medoo']->error;
        $this->errorInfo = $medooInfo['medoo']->errorInfo;
        $this->lastSql = $medooInfo['medoo']->last();
        if($medooInfo['coroutine_id']<=0){
            return;
        }
        $contextKey=$medooInfo['name'];
        $hasContextConnection = Context::has($contextKey);
        if (! $hasContextConnection) {
            $connection = $medooInfo['pool_connection'];
            if (in_array($action,['action','debug','beginDebug','debugLog','lock'])) {
                Context::set($contextKey,$connection);
                defer(function () use ($connection, $contextKey) {
                    Context::set($contextKey, null);
                    $connection->release();
                });
            } else {
                $connection->release();
            }
        }
    }

    /**
     * @param array|string $join
     * @param array|string $columns
     * @param array $where
     * @param bool $is_slave
     * @return array|null
     * @throws Throwable
     */
    public function select($join, $columns = null, $where = null, $is_slave = false):?array
    {
        $medooInfo = $this->_getMedoo($is_slave);
        try {
            $res = $medooInfo['medoo']->select($this->table, $join, $columns, $where);
            return $res;
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return [];
    }

    /**
     * @param array $datas
     * @param string|null $primaryKey
     * @return bool|int
     * @throws Throwable
     */
    public function insert(array $datas, ?string $primaryKey = null)
    {
        $medooInfo = $this->_getMedoo();
        try {
            $statement = $medooInfo['medoo']->insert($this->table, $datas);
            if (!empty($statement)) {
                if ($statement->rowCount() > 0) {
                    return (int)($medooInfo['medoo']->id());
                }
            }
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return false;
    }

    /**
     * @param array $datas
     * @param array $where
     * @return bool
     * @throws Throwable
     */
    public function update(array $datas, $where = null):bool
    {
        $medooInfo = $this->_getMedoo();
        try {
            $statement = $medooInfo['medoo']->update($this->table, $datas, $where);
            if (!empty($statement)) {
                return true;
            }
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return false;
    }

    /**
     * @param array|Raw $where
     * @return bool
     * @throws Throwable
     */
    public function delete($where):bool
    {
        $medooInfo = $this->_getMedoo();
        try {
            $statement =  $medooInfo['medoo']->delete($this->table, $where);
            if (!empty($statement)) {
                return true;
            }
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return false;
    }

    /**
     * @param array $columns
     * @param array $where
     * @return bool
     * @throws Throwable
     */
    public function replace($columns, $where = null):bool
    {
        $medooInfo = $this->_getMedoo();
        try {
            $statement =  $medooInfo['medoo']->replace($this->table, $columns, $where);
            if (!empty($statement)) {
                return true;
            }
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return false;
    }

    /**
     * @param array|string $join
     * @param array|string $columns
     * @param array $where
     * @param bool $is_slave
     * @return mixed
     * @throws Throwable
     */
    public function get($join = null, $columns = null, $where = null, $is_slave = false)
    {
        $medooInfo = $this->_getMedoo($is_slave);
        try {
            return $medooInfo['medoo']->get($this->table, $join, $columns, $where);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
    }

    /**
     * @param array $join
     * @param array $where
     * @return bool
     * @throws Throwable
     */
    public function has(array $join, $where = null):bool
    {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->has($this->table, $join, $where);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally  {
            $this->_finally($medooInfo);
        }
        return false;
    }

    /**
     * @param array|null $join
     * @param array|string|null $columns
     * @param string|array|null $where
     * @param bool $is_slave
     * @return array|null
     * @throws Throwable
     */
    public function rand($join = null, $columns = null, $where = null, $is_slave = false):?array
    {
        $medooInfo = $this->_getMedoo($is_slave);
        try {
            return $medooInfo['medoo']->rand($this->table, $join, $columns, $where);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return [];
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @param bool $is_slave
     * @return int|null
     * @throws Throwable
     */
    public function count($join = null, $column = null, $where = null, $is_slave = false):?int
    {
        $medooInfo = $this->_getMedoo($is_slave);
        try {
            return $medooInfo['medoo']->count($this->table, $join, $column, $where) + 0;
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return null;
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @param bool $is_slave
     * @return string|null
     * @throws Throwable
     */
    public function sum($join, $column = null, $where = null, $is_slave = false):?string
    {
        $medooInfo = $this->_getMedoo($is_slave);
        try {
            return $medooInfo['medoo']->sum($this->table, $join, $column, $where);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return null;
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @return string|null
     * @throws Throwable
     */
    public function avg($join, $column = null, $where = null): ?string
    {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->avg($this->table, $join, $column, $where);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return null;
    }

    /**
     * @param array|string $join
     * @param string $column
     * @param array $where
     * @return string|null
     * @throws Throwable
     */
    public function max($join, $column = null, $where = null): ?string
    {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->max($this->table, $join, $column, $where);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return null;
    }

    /**
     * @param array $join
     * @param string $column
     * @param array $where
     * @return string|null
     * @throws Throwable
     */
    public function min($join, $column = null, $where = null): ?string
    {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->min($this->table, $join, $column, $where);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return null;
    }


    /**
     * @param callable $actions
     * @return bool
     * @throws Throwable
     */
    public function action(callable $actions):bool
    {
        $medooInfo = $this->_getMedoo();
        $ret = true;
        try {
            $medooInfo['medoo']->action($actions);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
            $ret =  false;
        } finally {
            $this->_finally($medooInfo,'action');
        }
        return $ret;
    }

    /**
     * @param array $columns
     * @param array|null $options
     * @return bool
     * @throws Throwable
     */
    public function create(array $columns, array $options = null):bool {
        $medooInfo = $this->_getMedoo();
        try {
            $statement =$medooInfo['medoo']->create($this->table,$columns,$options);
            if(!empty($statement)){
                return true;
            }
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return false;
    }

    /**
     * @return bool
     * @throws Throwable
     */
    public function drop():bool {
        $medooInfo = $this->_getMedoo();
        try {
            $statement =$medooInfo['medoo']->drop($this->table);
            if(!empty($statement)){
                return true;
            }
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return false;
    }

    /**
     * @return $this
     * @throws Throwable
     */
    public function debug():Model
    {
        $medooInfo = $this->_getMedoo();
        try {
            $medooInfo['medoo']->debug();
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo,'debug');
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function error():?string{
        $error = $this->error;
        $this->error=null;
        return $error;
    }

    /**
     * @return string|null
     */
    public function last():?string
    {
        return $this->lastSql;
    }

    /**
     * @return array
     * @throws Throwable
     */
    public function log():array
    {
        $medooInfo = $this->_getMedoo();
        $coId = $this->getCoroutineId();
        if($coId){
            return $this->logs;
        }else{
            return $medooInfo['medoo']->log();
        }
    }

    /**
     * @param string $sql
     * @param int $pdo_fetch
     * @return array|null
     * @throws Throwable
     */
    public function query(string $sql, int $pdo_fetch = \PDO::FETCH_ASSOC):?array
    {
        $medooInfo = $this->_getMedoo();
        try {
            $statement =  $medooInfo['medoo']->query($sql);
            if(!empty($statement)){
                return $statement->fetchAll($pdo_fetch);
            }
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return null;
    }

    /**
     * @param string $string
     * @return string|null
     * @throws Throwable
     */
    public function quote(string $string):?string {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->quote($string);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return null;
    }

    /**
     * @return string
     * @throws Throwable
     */
    public function tableQuote():string {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->tableQuote($this->table);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return '';
    }

    /**
     * @param string $string
     * @param array $map
     * @return Medoo|Raw
     */
    public function raw(string $string, array $map = []):?Raw
    {
        return Medoo::raw($string,$map);
    }

    /**
     *
     */
    public function beginDebug():void
    {
        $medooInfo = $this->_getMedoo();
        try {
            $medooInfo['medoo']->beginDebug();
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo,'beginDebug');
        }
    }

    /**
     * @return array
     * @throws Throwable
     */
    public function debugLog():array {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->debugLog();
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo,'debugLog');
        }
        return [] ;
    }

    /**
     * @return array|null
     */
    public function errorInfo():?array
    {
        $errorInfo = $this->errorInfo;
        $this->errorInfo=null;
        return $errorInfo;
    }



    /**
     * @return array
     * @throws Throwable
     */
    public function info():array {
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->info();
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return [] ;
    }

    /**
     * @param string $type
     * @return Model
     * @throws Throwable
     */
    public function lock(string $type = Medoo::LOCK_FOR_UPDATE):Model{
        $medooInfo = $this->_getMedoo();
        try {
            return $medooInfo['medoo']->lock($type);
        }catch (Throwable $e){
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo,'lock');
        }
        return $this;
    }

    /**
     * @param array $values
     * @param string|null $primaryKey
     * @return bool
     * @throws Throwable
     */
    public function insertUpdate(array $values, string $primaryKey = null):bool{
        $medooInfo = $this->_getMedoo();
        try {
            $medooInfo['medoo']->insertUpdate($this->table,$values,$primaryKey);
            $ret = true;
        }catch (\Exception $e){
            $ret =false;
            if($medooInfo['coroutine_id']<=0){
                throw $e;
            }
        } finally {
            $this->_finally($medooInfo);
        }
        return $ret;
    }

}
