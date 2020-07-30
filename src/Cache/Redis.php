<?php
/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2020/3/27 23:56
 */

namespace Sgenmi\eYaf\Cache;

use Yaf\Exception;

class Redis {

    /**
     * @var \Redis
     */
    private $redis;
    private $config=[];
    public function __construct(string $name='app')
    {
        $gCofnig = \Yaf\Registry::get('_config');
        $config = $gCofnig->redis->$name;
        if(!$config) {
            throw new Exception("Can't find $name configuration with redis");
        }
        if(!isset($config['host']) || !isset($config['port'])) {
            throw new Exception("Can't find $name's host|port configuration with redis");
        }
        $this->config = $config;
        $this->connect();
    }

    private function connect() {
        $redis = new \Redis();
        $timeout = $this->config['timeout']??0;
        $redis->connect($this->config['host'],$this->config['port'],$timeout);

        if(isset($this->config['auth']) && $this->config['auth'] ) {
            $redis->auth($this->config['auth']);
        }
        if(isset($this->config['select']) && $this->config['select']) {
            $redis->select((int) $this->config['select']);
        }
        $this->redis = $redis;
    }

    /**
     * @desc get Redis Object
     * @return \Redis
     */
    public function getRedis():\Redis
    {
        return $this->_redis;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getKey(string $key):string {
        $prefix = $this->config['prefix'] ?? '';
        if (isset( $this->config['encrypt']) && $this->config['encrypt']) {
            $key = $prefix.':'.md5($key);
        }
        return $key;
    }

    /**
     * @param int $key
     * @param $val
     * @return bool
     */
    public function setOption(int $key,$val):bool {
        return $this->redis->setOption($key,$val);
    }

    /**
     * @param int $key
     * @return mixed|null
     */
    public function getOption(int $key) {
        return $this->redis->getOption($key);
    }

    /**
     * @param string $str
     * @return mixed
     */
    public function ping ( string $str='' ){
      return $this->redis->ping($str);
    }

    /**
     * Append specified string to the string stored in specified key.
     *
     * @param string       $key
     * @param string|mixed $value
     *
     * @return int Size of the value after the append
     */
    public function append(string $key, $value = NULL)
    {
        $key = $this->getKey($key);
        return $this->redis->append($key, (string)$value);
    }

    /**
     * Decrement the number stored at key by one.
     *
     * @param string $key
     *
     * @return int the new value
     */
    public function decr(string $key){
        $key = $this->getKey($key);
        return $this->redis->decr();
    }

    /**
     * Decrement the number stored at key by one.
     * If the second argument is filled, it will be used as the integer value of the decrement.
     *
     * @param string $key
     * @param int    $value  that will be substracted to key (only for decrBy)
     *
     * @return int the new value
     */
    public function decrBy(string $key, int $value)
    {
        $key = $this->getKey($key);
        return $this->redis->decrBy($key,$value);
    }

    /**
     * Get the value related to the specified key
     *
     * @param string $key
     *
     * @return string|mixed|bool If key didn't exist, FALSE is returned.
     */

    public function get(string $key){
        $key = $this->getKey($key);
        return $this->redis->get($key);
    }

    /**
     * Return a single bit out of a larger string
     *
     * @param string $key
     * @param int    $offset
     *
     * @return int the bit value (0 or 1)
     */
    public function getBit(string $key,int $offset){
        $key = $this->getKey($key);
        return $this->redis->getBit($key,$offset);
    }

    /**
     * Return a substring of a larger string
     *
     * @param string $key
     * @param int    $start
     * @param int    $end
     *
     * @return string the substring
     */
     public function getRange(string $key, int $start, int $end):string {
         $key = $this->getKey($key);
         return $this->redis->getRange($key,$start,$end);
     }

    /**
     * Sets a value and returns the previous entry at that key.
     *
     * @param string       $key
     * @param string|mixed $value
     *
     * @return string|mixed A string (mixed, if used serializer), the previous value located at this key
     */
     public function getSet(string $key, $value) {
         $key = $this->getKey($key);
         return $this->redis->getSet($key,$value);
     }

    /**
     * Increment the number stored at key by one.
     *
     * @param   string $key
     *
     * @return int the new value
     */
     public function incr(string $key){
         $key = $this->getKey($key);
         return $this->redis->incr($key);
     }

    /**
     * Increment the number stored at key by one.
     * If the second argument is filled, it will be used as the integer value of the increment.
     *
     * @param string $key   key
     * @param int    $value value that will be added to key (only for incrBy)
     *
     * @return int the new value
     */
    public function incrBy(string $key, int $vallue){
        $key = $this->getKey($key);
        return $this->redis->incrBy($key,$vallue);
    }

    /**
     * Increment the float value of a key by the given amount
     *
     * @param string $key
     * @param float  $increment
     *
     * @return float
     */
    public function incrByFloat(string $key, float $value){
        $key = $this->getKey($key);
        return $this->redis->incrByFloat($key,$value);
    }

    /**
     * Returns the values of all specified keys.
     *
     * For every key that does not hold a string value or does not exist,
     * the special value false is returned. Because of this, the operation never fails.
     *
     * @param array $array
     *
     * @return array
     */
    public function mGet(array $array){
        return $this->redis->mGet($array);
    }

    /**
     * Sets multiple key-value pairs in one atomic command.
     * MSETNX only returns TRUE if all the keys were set (see SETNX).
     *
     * @param array $array Pairs: array(key => value, ...)
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     */
    public function mSet(array $array){
        $key = $this->getKey($key);
        $_arr=[];

        foreach ($array as $k=>$v){
            $key = $this->getKey($key);
            $_arr[$key] = $v;
        }
        return $this->redis->mSet($_arr);
    }

    /**
     * Set the string value in argument as value of the key.
     *
     * @since If you're using Redis >= 2.6.12, you can pass extended options as explained in example
     *
     * @param string       $key
     * @param string|mixed $value string if not used serializer
     * @param int|array    $timeout [optional] Calling setex() is preferred if you want a timeout.<br>
     *
     * @return bool TRUE if the command is successful
     */
    public function set(string $key, $value, $timeout = null){
        $key = $this->getKey($key);
        return $this->redis->set($key, $value, $timeout = null);
    }

    /**
     * Changes a single bit of a string.
     *
     * @param string   $key
     * @param int      $offset
     * @param bool|int $value  bool or int (1 or 0)
     *
     * @return int 0 or 1, the value of the bit before it was set
     */
    public function setBit(string $key, int $offset, $value ){
        $key = $this->getKey($key);
        return $this->redis->setBit($key, $offset, $value);
    }

    /**
     * Set the string value in argument as value of the key, with a time to live.
     *
     * @param string       $key
     * @param int          $ttl
     * @param string|mixed $value
     *
     * @return bool TRUE if the command is successful
     */
    public function setEx(string $key, int $ttl, $value ){
        $key = $this->getKey($key);
        return $this->redis->setEx($key,$ttl,$value);
    }

    /**
     * Set the value and expiration in milliseconds of a key.
     *
     * @see     setex()
     * @param   string       $key
     * @param   int          $ttl, in milliseconds.
     * @param   string|mixed $value
     *
     * @return bool TRUE if the command is successful
     */
    public function pSetEx(string $key, int $ttl, $value ){
        $key = $this->getKey($key);
        return $this->redis->pSetEx($key,$ttl,$value);
    }

    /**
     * Set the string value in argument as value of the key if the key doesn't already exist in the database.
     *
     * @param string       $key
     * @param string|mixed $value
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     */
    public function setNx(string $key, $value ){
        $key = $this->getKey($key);
        return $this->redis->setNx($key, $value);
    }
    /**
     * Changes a substring of a larger string.
     *
     * @param string $key
     * @param int    $offset
     * @param string $value
     *
     * @return int the length of the string after it was modified
     */
    public function setRange(string $key, int $offset, string $value ){
        $key = $this->getKey($key);
        return $this->redis->setRange($key, $offset, $value);
    }

    /**
     * Get the length of a string value.
     *
     * @param string $key
     * @return int
     */
    public function strLen(string $key){
        $key = $this->getKey($key);
        return $this->redis->strLen($key);
    }

    /**
     * Remove specified keys.
     *
     * @param   int|string|array $key1 An array of keys, or an undefined number of parameters, each a key: key1 key2 key3 ... keyN
     * @param   int|string       ...$otherKeys
     *
     * @return int Number of keys deleted
     */
    public function del( string $key ){
        $key = $this->getKey($key);
        return $this->redis->del($key);
    }

    /**
     * Verify if the specified key/keys exists
     *
     * This function took a single argument and returned TRUE or FALSE in phpredis versions < 4.0.0.
     *
     * @since >= 4.0 Returned int, if < 4.0 returned bool
     *
     * @param string|string[] $key
     *
     * @return int|bool The number of keys tested that do exist
     */
    public function exists( string $key ){
        $key = $this->getKey($key);
        return $this->redis->exists($key);
    }
    /**
     * Sets an expiration date (a timeout) on an item
     *
     * @param string $key The key that will disappear
     * @param int    $ttl The key's remaining Time To Live, in seconds
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     */
    public function expire( string $key, int $ttl){
        $key = $this->getKey($key);
        return $this->redis->expire($key,$ttl);
    }

    /**
     * Remove the expiration timer from a key.
     *
     * @param string $key
     *
     * @return bool TRUE if a timeout was removed, FALSE if the key didn’t exist or didn’t have an expiration timer.
     */
    public function persist( string $key){
        $key = $this->getKey($key);
        return $this->redis->persist($key);
    }
    /**
     * Returns a random key
     *
     * @return string an existing key in redis
     */
    public function randomKey(){
        return $this->redis->randomKeyu();
    }

    /**
     * Renames a key
     *
     * @param string $srcKey
     * @param string $dstKey
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     */
    public function rename(string $srcKey,string $dstKey){
        $str = $this->getKey($srcKey);
        $dst = $this->getKey($srcKey);
        return $this->redis->rename($str,$dst);
    }

    /**
     * Renames a key
     *
     * Same as rename, but will not replace a key if the destination already exists.
     * This is the same behaviour as setNx.
     *
     * @param string $srcKey
     * @param string $dstKey
     *
     * @return bool TRUE in case of success, FALSE in case of failure
     */
    public function renameNx(string $srcKey,string $dstKey){
        $str = $this->getKey($srcKey);
        $dst = $this->getKey($srcKey);
        return $this->redis->renameNx($str,$dst);
    }

    /**
     * Returns the type of data pointed by a given key.
     *
     * @param string $key
     *
     * @return int
     */
    public function type(string $key){
        $key = $this->getKey($key);
        return $this->redis->type($key);
    }


    /**
     * Returns the time to live left for a given key, in seconds. If the key doesn't exist, FALSE is returned.
     *
     * @param string $key
     *
     * @return int|bool the time left to live in seconds
     */
    public function ttl(string $key){
        $key = $this->getKey($key);
        return $this->redis->ttl($key);
    }

    /**
     * Removes a values from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey1
     * @param string ...$otherHashKeys
     *
     * @return int|bool Number of deleted fields
     */
    public function hDel(string $key,string $hashKey){
        $key = $this->getKey($key);
        return $this->redis->hDel($key,$hashKey);
    }

    /**
     * Verify if the specified member exists in a key.
     *
     * @param string $key
     * @param string $hashKey
     *
     * @return bool If the member exists in the hash table, return TRUE, otherwise return FALSE.
     */
    public function hExists(string $key,string $hashKey){
        $key = $this->getKey($key);
        return $this->redis->hExists($key,$hashKey);
    }

    /**
     * Gets a value from the hash stored at key.
     * If the hash table doesn't exist, or the key doesn't exist, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey
     *
     * @return string The value, if the command executed successfully BOOL FALSE in case of failure
     */
    public function hGet(string $key,string $hashKey){
        $key = $this->getKey($key);
        return $this->redis->hGet($key,$hashKey);
    }

    /**
     * Returns the whole hash, as an array of strings indexed by strings.
     *
     * @param string $key
     *
     * @return array An array of elements, the contents of the hash.
     */
    public function hGetAll(string $key){
        $key = $this->getKey($key);
        return $this->redis->hGetAll($key);
    }

    /**
     * Increments the value of a member from a hash by a given amount.
     *
     * @param string $key
     * @param string $hashKey
     * @param int    $value (integer) value that will be added to the member's value
     *
     * @return int the new value
     */
    public function hIncrBy(string $key,string $hashKey, int $value){
        $key = $this->getKey($key);
        return $this->redis->hIncrBy($key,$hashKey,$value);
    }

    /**
     * Increment the float value of a hash field by the given amount
     *
     * @param string $key
     * @param string $hashKey
     * @param float  $increment
     *
     * @return float
     */
    public function hIncrByFloat(string $key,string $hashKey, float $value){
        $key = $this->getKey($key);
        return $this->redis->hIncrByFloat($key,$hashKey,$value);
    }

    /**
     * @param string $key
     * @return array
     */
    public function hKeys(string $key){
        $key = $this->getKey($key);
        return $this->redis->hKeys($key);
    }

    /**
     * Returns the length of a hash, in number of items
     *
     * @param string $key
     *
     * @return int|bool the number of items in a hash, FALSE if the key doesn't exist or isn't a hash
     */
    public function hLen(string $key){
        $key = $this->getKey($key);
        return $this->redis->hLen($key);
    }

    /**
     * Retirieve the values associated to the specified fields in the hash.
     *
     * @param string $key
     * @param array  $hashKeys
     *
     * @return array Array An array of elements, the values of the specified fields in the hash,
     * with the hash keys as array keys.
     */
    public function hMGet(string $key,array $hashKeys){
        $key = $this->getKey($key);
        return $this->redis->hMGet($key,$hashKeys);
    }

    /**
     * Fills in a whole hash. Non-string values are converted to string, using the standard (string) cast.
     * NULL values are stored as empty strings
     *
     * @param string $key
     * @param array  $hashKeys key → value array
     *
     * @return bool
     */
    public function hMSet(string $key,array $hashKeys){
        $key = $this->getKey($key);
        return $this->redis->hMSet($key,$hashKeys);
    }

    /**
     * Adds a value to the hash stored at key. If this value is already in the hash, FALSE is returned.
     *
     * @param string $key
     * @param string $hashKey
     * @param string $value
     *
     * @return int|bool
     * - 1 if value didn't exist and was added successfully,
     * - 0 if the value was already present and was replaced, FALSE if there was an error.
     */

    public function hSet(string $key,string $hashKeys,string $value){
        $key = $this->getKey($key);
        return $this->redis->hSet($key,$hashKeys,$value);
    }

    /**
     * Adds a value to the hash stored at key only if this field isn't already in the hash.
     *
     * @param string $key
     * @param string $hashKey
     * @param string $value
     *
     * @return  bool TRUE if the field was set, FALSE if it was already present.
     */
    public function hSetNx(string $key,string $hashKeys,string $value){
        $key = $this->getKey($key);
        return $this->redis->hSetNx($key,$hashKeys,$value);
    }

    /**
     * Returns the values in a hash, as an array of strings.
     * @param string $key
     *
     * @return array An array of elements, the values of the hash. This works like PHP's array_values().
     */
    public function hVals(string $key,string $hashKeys,string $value){
        $key = $this->getKey($key);
        return $this->redis->hVals($key,$hashKeys,$value);
    }

    /**
     * Get the string length of the value associated with field in the hash stored at key
     *
     * @param string $key
     * @param string $field
     *
     * @return int the string length of the value associated with field, or zero when field is not present in the hash
     * or key does not exist at all.
     * @since >= 3.2
     */
    public function hStrLen(string $key,string $field){
        $key = $this->getKey($key);
        return $this->redis->hStrLen($key,$hashKeys);
    }

    /**
     * Is a blocking lPop primitive. If at least one of the lists contains at least one element,
     * the element will be popped from the head of the list and returned to the caller.
     * Il all the list identified by the keys passed in arguments are empty, blPop will block
     * during the specified timeout until an element is pushed to one of those lists. This element will be popped.
     *
     * @param string|string[] $keys    String array containing the keys of the lists OR variadic list of strings
     * @param int             $timeout Timeout is always the required final parameter
     *
     * @return array ['listName', 'element']
     */
    public function blPop(string $key,int $timeout=0){
        $key = $this->getKey($key);
        return $this->redis->blPop($key,$timeout);
    }
    public function brPop(string $key,int $timeout=0){
        $key = $this->getKey($key);
        return $this->redis->brPop($key,$timeout);
    }
    /**
     * A blocking version of rpoplpush, with an integral timeout in the third parameter.
     *
     * @param string $srcKey
     * @param string $dstKey
     * @param int    $timeout
     *
     * @return  string|mixed|bool  The element that was moved in case of success, FALSE in case of timeout
     */
    public function bRPopLPush(string $srcKey, string $dstKey ,int $timeout=0){
        $srcKey = $this->getKey($srcKey);
        $dstKey = $this->getKey($dstKey);
        return $this->redis->bRPopLPush($srcKey,$dstKey,$timeout);
    }
    /**
     * Return the specified element of the list stored at the specified key.
     * 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     * Return FALSE in case of a bad index or a key that doesn't point to a list.
     *
     * @param string $key
     * @param int    $index
     *
     * @return mixed|bool the element at this index
     */
    public function lIndex(string $key ,int $index){
        $key = $this->getKey($key);
        return $this->redis->lIndex($key,$index);
    }
    /**
     * Insert value in the list before or after the pivot value. the parameter options
     * specify the position of the insert (before or after). If the list didn't exists,
     * or the pivot didn't exists, the value is not inserted.
     *
     * @param string       $key
     * @param int          $position Redis::BEFORE | Redis::AFTER
     * @param string       $pivot
     * @param string|mixed $value
     *
     * @return int The number of the elements in the list, -1 if the pivot didn't exists.
     */
    public function lInsert(string $key ,int $position,string $pivot, $value){
        $key = $this->getKey($key);
        return $this->redis->lInsert($key,$position,$pivot,$value);
    }
    /**
     * Returns the size of a list identified by Key. If the list didn't exist or is empty,
     * the command returns 0. If the data type identified by Key is not a list, the command return FALSE.
     *
     * @param string $key
     *
     * @return int|bool The size of the list identified by Key exists.
     * bool FALSE if the data type identified by Key is not list
     */
    public function lLen(string $key ){
        $key = $this->getKey($key);
        return $this->redis->lLen($key);
    }
    /**
     * Returns and removes the first element of the list.
     *
     * @param   string $key
     *
     * @return  mixed|bool if command executed successfully BOOL FALSE in case of failure (empty list)
     */
    public function lPop(string $key ){
        $key = $this->getKey($key);
        return $this->redis->lPop($key);
    }

    /**
     * Adds the string values to the head (left) of the list.
     * Creates the list if the key didn't exist.
     * If the key exists and is not a list, FALSE is returned.
     *
     * @param string $key
     * @param string|mixed $value1... Variadic list of values to push in key, if dont used serialized, used string
     *
     * @return int|bool The new length of the list in case of success, FALSE in case of Failure
     */
    public function lPush(string $key,...$value ){
        $key = $this->getKey($key);
        return $this->redis->lPush($key,...$value);
    }

    /**
     * Adds the string value to the head (left) of the list if the list exists.
     *
     * @param string $key
     * @param string|mixed $value String, value to push in key
     *
     * @return int|bool The new length of the list in case of success, FALSE in case of Failure.
     */
    public function lPushx(string $key,$value ){
        $key = $this->getKey($key);
        return $this->redis->lPushx($key,$value);
    }

    /**
     * Returns the specified elements of the list stored at the specified key in
     * the range [start, end]. start and stop are interpretated as indices: 0 the first element,
     * 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @param string $key
     * @param int    $start
     * @param int    $end
     *
     * @return array containing the values in specified range.
     */
    public function lRange(string $key,int $start,int $end){
        $key = $this->getKey($key);
        return $this->redis->lRange($key,$start,$end);
    }
    /**
     * Removes the first count occurences of the value element from the list.
     * If count is zero, all the matching elements are removed. If count is negative,
     * elements are removed from tail to head.
     *
     * @param string $key
     * @param string $value
     * @param int    $count
     *
     * @return int|bool the number of elements to remove
     * bool FALSE if the value identified by key is not a list.
     */
    public function lRem(string $key,string $value,int $count){
        $key = $this->getKey($key);
        return $this->redis->lRem($key,$value,$count);
    }

    /**
     * Set the list at index with the new value.
     *
     * @param string $key
     * @param int    $index
     * @param string $value
     *
     * @return bool TRUE if the new value is setted.
     * FALSE if the index is out of range, or data type identified by key is not a list.
     */
    public function lSet(string $key,int $index,string $value){
        $key = $this->getKey($key);
        return $this->redis->lSet($key,$index,$value);
    }
    /**
     * Trims an existing list so that it will contain only a specified range of elements.
     *
     * @param string $key
     * @param int    $start
     * @param int    $stop
     *
     * @return array|bool Bool return FALSE if the key identify a non-list value
     */
    public function lTrim(string $key,int $start,int $stop){
        $key = $this->getKey($key);
        return $this->redis->lTrim($key,$start,$stop);
    }
    public function rPop(string $key){
        $key = $this->getKey($key);
        return $this->redis->rPop($key);
    }
    public function rPopLPush(string $srcKey, string $dstKey){
        $srcKey = $this->getKey($srcKey);
        $dstKey = $this->getKey($dstKey);
        return $this->redis->rPopLPush($srcKey, $dstKey);
    }
    public function rPush(string $key, ...$value1){
        $key = $this->getKey($key);
        return $this->redis->rPush($key, ...$value1);
    }
    public function rPushx(string $key, $value){
        $key = $this->getKey($key);
        return $this->redis->rPushx($key, $value);
    }

    /**
     * Adds a values to the set value stored at key.
     *
     * @param string       $key       Required key
     * @param string|mixed ...$value1 Variadic list of values
     *
     * @return int|bool The number of elements added to the set.
     * If this value is already in the set, FALSE is returned
     */
    public function sAdd(string $key, ...$value){
        $key = $this->getKey($key);
        return $this->redis->sAdd($key, ...$value);
    }
    /**
     * Returns the cardinality of the set identified by key.
     *
     * @param string $key
     *
     * @return int the cardinality of the set identified by key, 0 if the set doesn't exist.
     */
    public function sCard(string $key){
        $key = $this->getKey($key);
        return $this->redis->sCard($key);
    }


    /**
     * Performs the difference between N sets and returns it.
     *
     * @param string $key1         first key for diff
     * @param string ...$otherKeys variadic list of keys corresponding to sets in redis
     *
     * @return array string[] The difference of the first set will all the others
     */
    public function sDiff(string $key, ...$otherKeys){
        $key = $this->getKey($key);
        return $this->redis->sDiff($key,...$otherKeys);
    }

    /**
     * Performs the same action as sDiff, but stores the result in the first key
     *
     * @param string $dstKey       the key to store the diff into.
     * @param string $key1         first key for diff
     * @param string ...$otherKeys variadic list of keys corresponding to sets in redis
     *
     * @return int|bool The cardinality of the resulting set, or FALSE in case of a missing key

     */
    public function sDiffStore(string $dstKey, string $key1, ...$otherKeys){
        $dstKey = $this->getKey($dstKey);
        $key1 = $this->getKey($key1);
        return $this->redis->sDiffStore($dstKey, $key1, ...$otherKeys);
    }
    /**
     * Returns the members of a set resulting from the intersection of all the sets
     * held at the specified keys. If just a single key is specified, then this command
     * produces the members of this set. If one of the keys is missing, FALSE is returned.
     *
     * @param string $key1         keys identifying the different sets on which we will apply the intersection.
     * @param string ...$otherKeys variadic list of keys
     *
     * @return array contain the result of the intersection between those keys
     * If the intersection between the different sets is empty, the return value will be empty array.
     */
    public function sInter(string $key1, ...$otherKeys){
        $key1 = $this->getKey($key1);
        return $this->redis->sInter($key1, ...$otherKeys);
    }
    public function sInterStore(string $dstKey, string $key1, ...$otherKeys){
        $dstKey = $this->getKey($dstKey);
        $key1 = $this->getKey($key1);
        return $this->redis->sInterStore($dstKey, $key1, ...$otherKeys);
    }

    /**
     * Checks if value is a member of the set stored at the key key.
     *
     * @param string       $key
     * @param string|mixed $value
     *
     * @return bool TRUE if value is a member of the set at key key, FALSE otherwise
     */
    public function sIsMember(string $key, $value){
        $key = $this->getKey($key);
        return $this->redis->sIsMember($key, $value);
    }

    /**
     * Returns the contents of a set.
     *
     * @param string $key
     *
     * @return array An array of elements, the contents of the set
     */
    public function sMembers(string $keys){
        $key = $this->getKey($key);
        return $this->redis->sMembers($key);
    }

    /**
     * Moves the specified member from the set at srcKey to the set at dstKey.
     *
     * @param string       $srcKey
     * @param string       $dstKey
     * @param string|mixed $member
     *
     * @return bool If the operation is successful, return TRUE.
     * If the srcKey and/or dstKey didn't exist, and/or the member didn't exist in srcKey, FALSE is returned.
     */
    public function sMove(string $srcKey,string $dstKey, $member){
        $srcKey = $this->getKey($srcKey);
        $dstKey = $this->getKey($dstKey);
        return $this->redis->sMove($srcKey,$dstKey,$member);
    }
    /**
     * Removes and returns a random element from the set value at Key.
     *
     * @param string $key
     * @param int    $count [optional]
     *
     * @return string|mixed|array|bool "popped" values
     * bool FALSE if set identified by key is empty or doesn't exist.
     */
    public function sPop(string $key,int $count=1 ){
        $key = $this->getKey($key);
        return $this->redis->sPop($key,$count);
    }

    /**
     * Returns a random element(s) from the set value at Key, without removing it.
     *
     * @param string $key
     * @param int    $count [optional]
     *
     * @return string|mixed|array|bool value(s) from the set
     * bool FALSE if set identified by key is empty or doesn't exist and count argument isn't passed.
     */
    public function sRandMember(string $key,int $count=1 ){
        $key = $this->getKey($key);
        return $this->redis->sRandMember($key,$count);
    }
    /**
     * Removes the specified members from the set value stored at key.
     *
     * @param   string       $key
     * @param   string|mixed ...$member1 Variadic list of members
     *
     * @return int The number of elements removed from the set
     */
    public function sRem(string $key,...$member1){
        $key = $this->getKey($key);
        return $this->redis->sRem($key,...$member1);
    }

    /**
     * Performs the union between N sets and returns it.
     *
     * @param string $key1         first key for union
     * @param string ...$otherKeys variadic list of keys corresponding to sets in redis
     *
     * @return array string[] The union of all these sets
     */
    public function sUnion(string $key,...$otherKeys){
        $key = $this->getKey($key);
        return $this->redis->sUnion($key,...$otherKeys);
    }

    public function sUnionStore(string $dstKey, string $key1, ...$otherKeys){
        $dstKey = $this->getKey($dstKey);
        $key1 = $this->getKey($key1);
        return $this->redis->sUnionStore($dstKey, $key1, ...$otherKeys);
    }

    /**
     * Block until Redis can pop the highest or lowest scoring members from one or more ZSETs.
     * There are two commands (BZPOPMIN and BZPOPMAX for popping the lowest and highest scoring elements respectively.)
     *
     * @param string|array $key1
     * @param string|array $key2 ...
     * @param int $timeout
     *
     * @return array Either an array with the key member and score of the higest or lowest element or an empty array
     * if the timeout was reached without an element to pop.
     *
     * @since >= 5.0
     */
    public function bzPopMax($key1,$key2, int $timeout ){
        if(is_string($key1)){
            $key1 = $this->getKey($key1);
        }
        if(is_string($key2)){
            $key2 = $this->getKey($key2);
        }
        return $this->redis->bzPopMax($key1, $key2,$timeout);
    }
    public function bzPopMin($key1,$key2, int $timeout ){
        if(is_string($key1)){
            $key1 = $this->getKey($key1);
        }
        if(is_string($key2)){
            $key2 = $this->getKey($key2);
        }
        return $this->redis->bzPopMin($key1, $key2,$timeout);
    }

    /**
     * Adds the specified member with a given score to the sorted set stored at key
     *
     * @param string       $key     Required key
     * @param array        $options Options if needed
     * @param float        $score1  Required score
     * @param string|mixed $value1  Required value
     * @param float        $score2  Optional score
     * @param string|mixed $value2  Optional value
     * @param float        $scoreN  Optional score
     * @param string|mixed $valueN  Optional value
     *
     * @return int Number of values added
     */
    public function zAdd(string $key,  $score1, $value1)
    {
        $key = $this->getKey($key);
        return $this->redis->zAdd( $key, $score1, $value1);
    }
    /**
     * Returns the cardinality of an ordered set.
     *
     * @param string $key
     *
     * @return int the set's cardinality
     */
    public function zCard(string $key ){
        $key = $this->getKey($key);
        return $this->redis->zCard($key);
    }

    /**
     * Returns the number of elements of the sorted set stored at the specified key which have
     * scores in the range [start,end]. Adding a parenthesis before start or end excludes it
     * from the range. +inf and -inf are also valid limits.
     *
     * @param string $key
     * @param string $start
     * @param string $end
     *
     * @return int the size of a corresponding zRangeByScore
     */
    public function zCount(string $key ,string $start,string $end){
        $key = $this->getKey($key);
        return $this->redis->zCount($key,$start,$end);
    }


    /**
     * Increments the score of a member from a sorted set by a given amount.
     *
     * @param string $key
     * @param float  $value (double) value that will be added to the member's score
     * @param string $member
     *
     * @return float the new value
     */
    public function zIncrBy(string $key ,float $value,string $member){
        $key = $this->getKey($key);
        return $this->redis->zIncrBy($key,$value,$member);
    }

    /**
     * Creates an intersection of sorted sets given in second argument.
     * The result of the union will be stored in the sorted set defined by the first argument.
     * The third optional argument defines weights to apply to the sorted sets in input.
     * In this case, the weights will be multiplied by the score of each element in the sorted set
     * before applying the aggregation. The forth argument defines the AGGREGATE option which
     * specify how the results of the union are aggregated.
     *
     * @param string $output
     * @param array  $zSetKeys
     * @param array  $weights
     * @param string $aggregateFunction Either "SUM", "MIN", or "MAX":
     * defines the behaviour to use on duplicate entries during the zInterStore.
     *
     * @return int The number of values in the new sorted set.
     */
    public function zInterStore(string $output ,array $zSetKeys,array $weights=null,string $aggregateFunction='SUM'){
        $output = $this->getKey($output);
        return $this->redis->zInterStore($output,$zSetKeys,$weights,$aggregateFunction);
    }

    /**
     * Can pop the highest scoring members from one ZSET.
     *
     * @param string $key
     * @param int $count
     *
     * @return array Either an array with the key member and score of the highest element or an empty array
     * if there is no element to pop.
     *
     * @since >= 5.0
     */
    public function zPopMax(string $key ,int $count=1){
        $key = $this->getKey($key);
        return $this->redis->zPopMax($key,$count);
    }
    public function zPopMin(string $key ,int $count=1){
        $key = $this->getKey($key);
        return $this->redis->zPopMin($key,$count);
    }

    /**
     * Returns a range of elements from the ordered set stored at the specified key,
     * with values in the range [start, end]. start and stop are interpreted as zero-based indices:
     * 0 the first element,
     * 1 the second ...
     * -1 the last element,
     * -2 the penultimate ...
     *
     * @param string $key
     * @param int    $start
     * @param int    $end
     * @param bool   $withscores
     *
     * @return array Array containing the values in specified range.
     */
    public function zRange(string $key ,int $start,int $end, bool $withscores=null){
        $key = $this->getKey($key);
        return $this->redis->zRange($key , $start, $end, $withscores);
    }
    /**
     * Returns the elements of the sorted set stored at the specified key which have scores in the
     * range [start,end]. Adding a parenthesis before start or end excludes it from the range.
     * +inf and -inf are also valid limits.
     *
     * zRevRangeByScore returns the same items in reverse order, when the start and end parameters are swapped.
     *
     * @param string $key
     * @param int    $start
     * @param int    $end
     * @param array  $options Two options are available:
     *  - withscores => TRUE,
     *  - and limit => array($offset, $count)
     *
     * @return array Array containing the values in specified range.
     */
    public function zRangeByScore(string $key ,int $start,int $end, array $options=[]){
        $key = $this->getKey($key);
        return $this->redis->zRangeByScore($key , $start, $end, $options);
    }

    /**
     * Returns a lexigraphical range of members in a sorted set, assuming the members have the same score. The
     * min and max values are required to start with '(' (exclusive), '[' (inclusive), or be exactly the values
     * '-' (negative inf) or '+' (positive inf).  The command must be called with either three *or* five
     * arguments or will return FALSE.
     *
     * @param string $key    The ZSET you wish to run against.
     * @param int    $min    The minimum alphanumeric value you wish to get.
     * @param int    $max    The maximum alphanumeric value you wish to get.
     * @param int    $offset Optional argument if you wish to start somewhere other than the first element.
     * @param int    $limit  Optional argument if you wish to limit the number of elements returned.
     *
     * @return array|bool Array containing the values in the specified range.
     */
    public function zRangeByLex(string $key, $min, $max, $offset = null, $limit = null){
        $key = $this->getKey($key);
        return $this->redis->zRangeByLex($key, $min, $max, $offset, $limit);
    }

    /**
     * Returns the rank of a given member in the specified sorted set, starting at 0 for the item
     * with the smallest score. zRevRank starts at 0 for the item with the largest score.
     *
     * @param string       $key
     * @param string|mixed $member
     *
     * @return int|bool the item's score, or false if key or member is not exists
     *
     * @link    https://redis.io/commands/zrank
     * @example
     * <pre>
     * $redis->del('z');
     * $redis->zAdd('key', 1, 'one');
     * $redis->zAdd('key', 2, 'two');
     * $redis->zRank('key', 'one');     // 0
     * $redis->zRank('key', 'two');     // 1
     * $redis->zRevRank('key', 'one');  // 1
     * $redis->zRevRank('key', 'two');  // 0
     * </pre>
     */
    public function zRank(string $key, $member){
        $key = $this->getKey($key);
        return $this->redis->zRank($key, $membert);
    }
    public function zRevRank(string $key, $member){
        $key = $this->getKey($key);
        return $this->redis->zRevRank($key, $membert);
    }

    /**
     * Deletes a specified member from the ordered set.
     *
     * @param string       $key
     * @param string|mixed $member1
     * @param string|mixed ...$otherMembers
     *
     * @return int Number of deleted values
     */
    public function zRem(string $key, $member1, ...$otherMembers){
        $key = $this->getKey($key);
        return $this->redis->zRem($key, $member1, ...$otherMembers);
    }
    /**
     * Deletes the elements of the sorted set stored at the specified key which have rank in the range [start,end].
     *
     * @param string $key
     * @param int    $start
     * @param int    $end
     *
     * @return int The number of values deleted from the sorted set
     */
    public function zRemRangeByRank(string $key, int $start , int $end  ){
        $key = $this->getKey($key);
        return $this->redis->zRemRangeByRank($key, $start, $end);
    }

    /**
     * Deletes the elements of the sorted set stored at the specified key which have scores in the range [start,end].
     *
     * @param string       $key
     * @param float|string $start double or "+inf" or "-inf" string
     * @param float|string $end double or "+inf" or "-inf" string
     *
     * @return int The number of values deleted from the sorted set
     */
    public function zRemRangeByScore(string $key, $start , $end  ){
        $key = $this->getKey($key);
        return $this->redis->zRemRangeByScore($key, $start, $end);
    }

    /**
     * Returns the elements of the sorted set stored at the specified key in the range [start, end]
     * in reverse order. start and stop are interpretated as zero-based indices:
     * 0 the first element,
     * 1 the second ...
     * -1 the last element,
     * -2 the penultimate ...
     *
     * @param string $key
     * @param int    $start
     * @param int    $end
     * @param bool   $withscore
     *
     * @return array Array containing the values in specified range.
     */
    public function zRevRange(string $key, int $start , int $end, $withscore=null  ){
        $key = $this->getKey($key);
        return $this->redis->zRevRange($key, $start, $end,$withscore);
    }
    /**
     * Returns the score of a given member in the specified sorted set.
     *
     * @param string       $key
     * @param string|mixed $member
     *
     * @return float|bool false if member or key not exists
     */

    public function zScore(string $key,$member  ){
        $key = $this->getKey($key);
        return $this->redis->zScore($key, $member);
    }

    /**
     * Creates an union of sorted sets given in second argument.
     * The result of the union will be stored in the sorted set defined by the first argument.
     * The third optionnel argument defines weights to apply to the sorted sets in input.
     * In this case, the weights will be multiplied by the score of each element in the sorted set
     * before applying the aggregation. The forth argument defines the AGGREGATE option which
     * specify how the results of the union are aggregated.
     *
     * @param string $output
     * @param array  $zSetKeys
     * @param array  $weights
     * @param string $aggregateFunction  Either "SUM", "MIN", or "MAX": defines the behaviour to use on
     * duplicate entries during the zUnionStore
     *
     * @return int The number of values in the new sorted set
     */
    public function zUnionStore(string $output, $zSetKeys, array $weights = null, $aggregateFunction = 'SUM' ){
        $output = $this->getKey($output);
        return $this->redis->zUnionStore( $output, $zSetKeys, $weights, $aggregateFunction);
    }

}
