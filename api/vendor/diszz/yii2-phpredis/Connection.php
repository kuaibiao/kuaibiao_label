<?php

namespace diszz\phpredis;

use Yii;
use yii\base\Component;
use yii\db\Exception;
use RedisException;
use yii\helpers\StringHelper;
use yii\di\Instance;
use diszz\phpyac\Connection as yacConnection;

/**
 * 
 * 基于yii2的redis扩展类
 * 
 * 普通模式配置方法:
 * 'components' => [
        'redis' => [
            'class' => 'diszz\phpredis\Connection',
            'hostname' => '127.0.0.1',
            'password' => null,//qweasdzxc123456
            'port' => 6379,
            'database' => 0,
            'keyPrefix' => 'v3redis:',
        ],
 *
 *
 * 哨兵模式配置方法:
 * 'components' => [
        'redis' => [
            'class' => 'diszz\phpredis\Connection',
            'hostname' => '127.0.0.1',
            'password' => null,//qweasdzxc123456
            'port' => 6379,
            'database' => 0,
            'keyPrefix' => 'v3redis:',
            'sentinel' => 1,
        ],
 *
 * 集群模式配置方法:
 * 'components' => [
        'redis' => [
            'class' => 'diszz\phpredis\Connection',
            'hostname' => '127.0.0.1',
            'password' => null,//qweasdzxc123456
            'port' => 6379,
            'database' => 0,
            'keyPrefix' => 'v3redis:',
            'sentinel' => 0,
            'cluster' => 1,
            'servers' => ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7002']
        ],
 *
 *
 * Class Connection
 * @package diszz\phpredis
 */
class Connection extends Component
{
    /**
     * @var string the hostname or ip address to use for connecting to the redis server. Defaults to 'localhost'.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $hostname = 'localhost';
    /**
     * @var integer the port to use for connecting to the redis server. Default port is 6379.
     * If [[unixSocket]] is specified, hostname and port will be ignored.
     */
    public $port = 6379;
    /**
     * @var string the unix socket path (e.g. `/var/run/redis/redis.sock`) to use for connecting to the redis server.
     * This can be used instead of [[hostname]] and [[port]] to connect to the server using a unix socket.
     * If a unix socket path is specified, [[hostname]] and [[port]] will be ignored.
     */
    public $unixSocket;
    /**
     * @var string the password for establishing DB connection. Defaults to null meaning no AUTH command is send.
     * See http://redis.io/commands/auth
     */
    public $password;
    /**
     * @var integer the redis database to use. This is an integer value starting from 0. Defaults to 0.
     */
    public $database = 0;
    /**
     * @var float value in seconds (optional, default is 0.0 meaning unlimited)
     */
    public $connectionTimeout = 1.5;
    
    /**
     * 缓存前缀
     * 需手动添加
     * $cackeyKey = Yii::$app->redis->buildKey($key);
     * Yii::$app->redis->exists($cackeyKey);
     * 
     * @var string
     */
    public $keyPrefix = '';
    
    /**
     * Redis connection
     */
    protected $_redis;
    
    /**
     * 是否开启哨兵模式
     * @var int
     */
    public $sentinel = 0;
    
    /**
     * 是否开启集群模式
     * @var int
     */
    public $cluster = 0;
    
    /**
     * 集群服务器配置
     * 
     * 'servers' => ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7002']
     * 
     * @var array
     */
    public $servers = [];
    
    //连接是否复用, 复用时用pcconnect
    public $persistent = 0;
    
    //长连接的连接标识
    public $persistent_id = null;
    
    //集群会用到
    public $readTime = 1.5;
    
    public $yac = '';//yac
    
    /**
     * Initializes the redis Session component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     */
    public function init()
    {
        $this->open();
        
        parent::init();
    }
    
    /**
     * Returns the fully qualified name of this class.
     * @return string the fully qualified name of this class.
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * Establishes a DB connection.
     * It does nothing if a DB connection has already been established.
     * @throws RedisException if connection fails
     */
    public function open()
    {
        if ($this->_redis !== null) {
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis had open', __CLASS__);
            return;
        }
        
        //-----------------------------------
        /*
        if ($this->yac) {
            $this->yac = Instance::ensure($this->yac, yacConnection::className());
            
            $connectionString = $this->hostname . $this->port;
            
            $localCacheKey = 'phpredis.Connection.'.$connectionString;
            $_logs['$localCacheKey'] = $localCacheKey;
            
            $localCacheData = $this->yac->get($localCacheKey);
            $_logs['$localCacheData.len'] = strlen($localCacheData);
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' local '.json_encode($_logs));
            
            if ($localCacheData) {
                $this->_redis = unserialize($localCacheData);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' local 1 '.json_encode($_logs));
                
                if (is_object($this->_redis)) {
                    $_logs['clientId'] = $this->clientId();
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' at local '.json_encode($_logs));
                    return ;
                }else{
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' local exception '.json_encode($_logs));
                }
                
            }
        }*/
        
        //-----------------------------------
        
        //使用哨兵模式
        if ($this->sentinel)
        {
            $this->_redis = new \Redis();
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis new class', __CLASS__);
            
            if ($this->unixSocket !== null) {
                $isConnected = $this->_redis->connect($this->unixSocket);
            } else {
                $isConnected = $this->_redis->connect($this->hostname, $this->port, $this->connectionTimeout);
            }
            
            if ($isConnected === false) {
                throw new RedisException('Connect to redis server error.');
            }
            \Yii::trace('_redis $isConnected', __CLASS__);
            
            if ($this->password !== null) {
                $this->_redis->auth($this->password);
            }
            
            if ($this->database !== null) {
                $this->_redis->select($this->database);
                \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
            }
            
            //获取主库列表及其状态信息
            $sentinelInfo = $this->_redis->rawCommand('SENTINEL', 'masters');
            //var_dump($sentinelInfo);
            //\Yii::trace('_redis select '. $this->database, __CLASS__);
            
            $masterInfo = $this->parseArrayResult($sentinelInfo);
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis $masterInfo '. json_encode($masterInfo), __CLASS__);
            //var_dump($masterInfo);
            
            if (empty($masterInfo['ip']) || empty($masterInfo['port']))
            {
                $masterInfo = current($masterInfo);
                if (empty($masterInfo['ip']) || empty($masterInfo['port']))
                {
                    \Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
                    throw new RedisException('redis conf error');
                }
            }
            
            $this->hostname = $masterInfo['ip'];
            $this->port = $masterInfo['port'];
            
            \Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis $masterInfo '. json_encode([$this->hostname, $this->port]), __CLASS__);
            
            if ($this->unixSocket !== null) {
                $isConnected = $this->_redis->connect($this->unixSocket);
            } else {
                $isConnected = $this->_redis->connect($this->hostname, $this->port, $this->connectionTimeout);
            }
            
            if ($isConnected === false) {
                throw new RedisException('Connect to redis server error.');
            }
            
            if ($this->password !== null) {
                $this->_redis->auth($this->password);
            }
            
            if ($this->database !== null) {
                $this->_redis->select($this->database);
                \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
            }
            
            \Yii::trace('_redis $isConnected', __CLASS__);
        }
        //使用集群模式
        else if ($this->cluster)
        {
            if (empty($this->servers))
            {
                \Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->servers, __CLASS__);
                throw new RedisException('redisCluster servers error');
            }
            
            $this->_redis = new \RedisCluster(
                null, 
                $this->servers, 
                $this->connectionTimeout ? $this->connectionTimeout : 1.5,
                $this->readTime ? $this->readTime : 1.5,
                true,//$this->persistent ? true : false,
                $this->password
            );
            
            //主从节点 读取分配策略
            //$this->_redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES);
            
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redisCluster $isConnected', __CLASS__);
        }
        //长链接模式
        elseif ($this->persistent)
        {
            $this->_redis = new \Redis();
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__." _redis new class", __CLASS__);
            
//             $connectOptions = [];
//             if ($this->password !== null) {
//                 $connectOptions['auth'] = $this->password;
//             }
            
            $isConnected = $this->_redis->pconnect(
                $this->hostname,//host
                $this->port,//port
                $this->connectionTimeout,//timeout
                $this->persistent_id,//persistent_id
                0,//retry_interval
                0,//read_timeout
                //$connectOptions
            );
            
            if ($isConnected === false) {
                throw new RedisException('Connect to redis server error.');
            }
            
            if ($this->password !== null) {
                $this->_redis->auth($this->password);
            }
            
            if ($this->database !== null) {
                $this->_redis->select($this->database);
                \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
            }
            
            //$clientId = $this->clientId();
            //$info = $this->info();
            
            //\Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis $isConnected '.$isConnected.', $clientId:'.$clientId, __CLASS__);
        }
        //普通模式
        else 
        {
            $this->_redis = new \Redis();
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__." _redis new class", __CLASS__);
            
            if ($this->unixSocket !== null) {
                $isConnected = $this->_redis->connect($this->unixSocket);
            } else {
                $isConnected = $this->_redis->connect($this->hostname, $this->port, $this->connectionTimeout);
            }
            
            if ($isConnected === false) {
                throw new RedisException('Connect to redis server error.');
            }
            
            if ($this->password !== null) {
                $this->_redis->auth($this->password);
            }
            
            if ($this->database !== null) {
                $this->_redis->select($this->database);
                \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis select '. $this->database, __CLASS__);
            }
            
            \Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis $isConnected', __CLASS__);
        }

        
        //------------------------------------------
        /*
        if ($this->yac) {
            
            $this->yac->set($localCacheKey, serialize($this->_redis), 30);
            
            $_logs['_redis.len'] = strlen(serialize($this->_redis));
            $_logs['clientId'] = $this->clientId();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setlocalcache '.json_encode($_logs));
        }*/
    }
    
    //这个方法可以将以上sentinel返回的信息解析为数组
    public function parseArrayResult(array $data)
    {
        $result = array();
        $count = count($data);
        for ($i = 0; $i < $count;) {
            $record = $data[$i];
            if (is_array($record)) {
                $result[] = $this->parseArrayResult($record);
                $i++;
            } else {
                $result[$record] = $data[$i + 1];
                $i += 2;
            }
        }
        return $result;
    }
    
    public function buildKey($key)
    {
        /*
         if (is_string($key)) {
         $key = ctype_alnum($key) && StringHelper::byteLength($key) <= 64 ? $key : md5($key);
         } else {
         $serializedKey = serialize($key);
         $key = md5($serializedKey);
         }*/
        
        return $this->keyPrefix . $key;
    }
    
    public function exists($key)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->_redis->exists($this->buildKey($key));
    }
    
    public function get($key)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->_redis->get($this->buildKey($key));
    }
    
    public function mget($keys)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($keys), __CLASS__);
        
        foreach ($keys as $k => $v) {
            $keys[$k] = $this->buildKey($v);
        }
        return $this->_redis->mget($keys);
    }
    
    public function set($key, $val)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->_redis->set($this->buildKey($key), $val);
    }
    
    public function setex($key, $ttl, $val) {
        return $this->_redis->setex($this->buildKey($key), $ttl, $val);
    }
    
    //设置成功:返回1;设置失败:返回 0
    public function setnx($key, $val, $ttl = 0) {
        $bool = $this->_redis->setnx($this->buildKey($key), $val);
        if ($ttl > 0) {
            $this->_redis->expire($this->buildKey($key), $ttl);
        }
        
        return $bool;
    }
    
    /**
     * Yac::set($key, $value[, $ttl = 0])
     * Yac::set(array $kvs[, $ttl = 0])
     *
     * $yac->set("foo", "bar");
     * $yac->set(array("dummy" => "foo","dummy2" => "foo"));
     *
     * @param array $vals
     * @param int $expire
     */
    public function msetex($kvs, $expire)
    {
        //$_logs = ['$kvs' => $kvs, '$expire' => $expire];
        //$_logs[__LINE__] = microtime(true);
        
        $kvs_ = [];
        foreach ($kvs as $k => $v) {
            $kvs_[$this->buildKey($k)] = $v;
        }
        
        $this->_redis->mset($kvs_);
        
        //$_logs[__LINE__] = microtime(true);
        
        if ($expire > 0) {
            //$index = [];
            $this->_redis->multi();
            foreach ($kvs_ as $key => $value) {
                $this->_redis->expire($key, $expire);
                //$index[] = $key;
            }
            $result = $this->_redis->exec();
            /*
            array_shift($result);
            foreach ($result as $i => $r) {
                if ($r != 1) {
                    $failedKeys[] = $index[$i];
                }
            }*/
        }
        
        //$_logs[__LINE__] = microtime(true);
        
        //\Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs), __CLASS__);
        return true;
    }
    
    /**
     *
     * bool, true on success, false on failure
     *
     * @param string $key
     * @param string $val
     * @param int $ttl
     */
    public function add($key, $val, $ttl)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->_redis->setex($this->buildKey($key), $val, $ttl);
    }
    
    public function del($key)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->_redis->del($this->buildKey($key));
    }
    
    public function delete($key)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->del($key);
    }
    
    public function incr($key, $step = 1, $ttl = 30)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->incrby($key, 1, $ttl);
    }
    
    public function incrby($key, $step = 1, $ttl = 30)
    {
        $value = $this->_redis->incrBy($this->buildKey($key), $step);
        if ($ttl > 0) {
            $this->_redis->expire($this->buildKey($key), $ttl);
        }
        
        return $value;
    }
    
    public function increase($key, $step = 1, $ttl = 30)
    {
        return $this->incrby($key, $step, $ttl);
    }
    
    public function decr($key = '', $step = 1, $ttl = 30)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->decrby($key, 1, $ttl);
    }
    
    public function decrby($key, $step = 1, $ttl = 30)
    {
        $value = $this->_redis->decrBy($this->buildKey($key), $step);
        if ($ttl > 0) {
            $this->_redis->expire($this->buildKey($key), $ttl);
        }
        
        return $value;
    }
    
    public function decrease($key, $step = 1, $ttl = 30)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        return $this->decrby($key, $step, $ttl);
    }

    public function keys($pattern)
    {
        return $this->_redis->keys($this->buildKey($pattern));
    }

    public function hget($key, $hashKey)
    {
        return $this->_redis->hget($this->buildKey($key), $hashKey);
    }

    public function hgetall($key)
    {
        return $this->_redis->hgetall($this->buildKey($key));
    }

    public function hset($key, $hashKey, $value)
    {
        return $this->_redis->hset($this->buildKey($key), $hashKey, $value);
    }

    public function hdel($key, $hashKey1, ...$otherHashKeys)
    {
        return $this->_redis->hdel($this->buildKey($key), $hashKey1, ...$otherHashKeys);
    }
    
    public function sadd($key, $val){
        return $this->_redis->sadd($this->buildKey($key), $val);
    }
    
    public function smembers($key){
        return $this->_redis->smembers($this->buildKey($key));
    }
    
    public function sismember($key, $val) {
        return $this->_redis->sismember($this->buildKey($key), $val);
    }

    public function srem($key, ...$member1)
    {
        return $this->_redis->srem($this->buildKey($key), ...$member1);
    }
    
    public function spop($key)
    {
        return $this->_redis->spop($this->buildKey($key));
    }
    
    public function srandmember($key)
    {
        return $this->_redis->srandmember($this->buildKey($key));
    }
    public function sinter($key, $key1)
    {
        return $this->_redis->sinter($this->buildKey($key), $key1);
    }
    public function sunion($key, $key1)
    {
        return $this->_redis->sunion($this->buildKey($key), $key1);
    }
    public function sdiff($key, $key1)
    {
        return $this->_redis->sdiff($this->buildKey($key), $key1);
    }

    public function scard($key){
        return $this->_redis->scard($this->buildKey($key));
    }

    public function sinterstore($storekey, $key, $key1){
        return $this->_redis->sinterstore($this->buildKey($storekey), $this->buildKey($key), $this->buildKey($key1));
    }

    public function lpush($key, $value)
    {
        return $this->_redis->lpush($this->buildKey($key), $value);
    }
    
    public function lpop($key) {
        return $this->_redis->lpop($this->buildKey($key));
    }

    public function llen($key)
    {
        return $this->_redis->llen($this->buildKey($key));
    }
    
    public function lrem($key, $val)
    {
        return $this->_redis->lrem($this->buildKey($key), $val, 0);
    }
    
    public function ltrim($key, $start, $end)
    {
        return $this->_redis->ltrim($this->buildKey($key), $start, $end);
    }
    
    public function lset($key, $index, $val)
    {
        return $this->_redis->lset($this->buildKey($key), $index, $val);
    }
    
    public function lindex($key, $index)
    {
        return $this->_redis->lindex($this->buildKey($key), $index);
    }
    
    public function lrange($key, $start, $end)
    {
        return $this->_redis->lrange($this->buildKey($key), $start, $end);
    }
    
    public function zadd($key, $score, $value)
    {
        return $this->_redis->zadd($this->buildKey($key), $score, $value);
    }

    public function zcard($key)
    {
        return $this->_redis->zcard($this->buildKey($key));
    }

    public function zrange($key, $start, $end, $withscore = null)
    {
        return $this->_redis->zrange($this->buildKey($key), $start, $end, $withscore);
    }
    
    public function zrevrank($key, $member)
    {
        return $this->_redis->zrevrank($this->buildKey($key), $member);
    }

    public function zrevrange($key, $start, $stop, $withscore = null)
    {
        return $this->_redis->zrevrange($this->buildKey($key), $start, $stop, $withscore);
    }

    public function zrevrangebyscore($key, $max, $min, $options = [])
    {
        return $this->_redis->zrevrangebyscore($this->buildKey($key), $max, $min, $options);
    }

    public function zscore($key, $member)
    {
        return $this->_redis->zscore($this->buildKey($key), $member);
    }
    
    public function zrem($key, ...$member)
    {
        return $this->_redis->zrem($this->buildKey($key), ...$member);
    }

    public function zremrangebyscore($key, $scoremin, $scoremax)
    {
        return $this->_redis->zremrangebyscore($this->buildKey($key), $scoremin, $scoremax);
    }
    
    public function zremrangebyrank($key, $start, $stop)
    {
        return $this->_redis->zremrangebyrank($this->buildKey($key), $start, $stop);
    }
    
    public function expire($key, $ttl){
        return $this->_redis->expire($this->buildKey($key), $ttl);
    }
    
    public function ttl($key){
        return $this->_redis->ttl($this->buildKey($key));
    }

    public function type($key){
        return $this->_redis->type($this->buildKey($key));
    }

    public function sort($key){
        return $this->_redis->sort($this->buildKey($key));
    }
    
    public function lock($key, $ttl = 1)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.$key, __CLASS__);
        
        $cacheKey = $this->buildKey('slock:'.$key);
        
        //bool, true on success, false on failure
        if ($this->_redis->setnx($cacheKey, 1)) {
            $this->_redis->expire($cacheKey, $ttl);
            return true;
        } else {
            return false;
        }
    }
    
    public function unLock($key)
    {
        $cacheKey = $this->buildKey('slock:'.$key);
        
        return $this->_redis->del($cacheKey);
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return $this->_redis->ping() === '+PONG';
    }

    public function flushdb()
    {
        return $this->_redis->flushDB();
    }
    
    public function clientId()
    {
        return $this->_redis->client('ID');
    }
    
    public function info()
    {
        return $this->_redis->info();
    }
    
    /**
     * 
     * demo:
     * 
     * public static function getMatchTwoJoinCount() {
        return Yii::$app->redis->getOrSet('matchTwoJoinCount', function() use ($type){
            
            $where = ['status' => DatasetMatchUser::STATUS_OVER, 'type' => $type];
            list($matchTwoJoinCount) = DatasetMatchUser::getCount($where);
            return $matchTwoJoinCount;
            
        }, 1800);
    }
     * 
     * @param string $key
     * @param $callable
     * @param number $expire
     * @param number $lockedTimes
     * @return boolean|boolean|mixed|mixed
     */
    public function getOrSet($key, $callable, $expire = 1800, $resetCache = false, $lockedTimes = 0)
    {
        $_logs = ['$key' => $key, '$expire' => $expire, '$resetCache' => $resetCache, '$lockedTimes' => $lockedTimes];
        $_logs['starttime'] = microtime(true);
        
        //$cacheKey = $this->buildKey('getOrSet:'.$key);
        $cacheKey = $key;
        //$_logs['$cacheKey'] = $cacheKey;
        
        $cacheData = $this->get($cacheKey);
        //$_logs[__LINE__] = microtime(true);
        
        //没有找到缓存, 则去查询
        if($cacheData === false || $resetCache){
            
            //并发锁, 防止缓存击穿. 返回1:未锁; 返回0:已锁
            $keySetnx = $cacheKey.':setnx';
            //$_logs['$keySetnx'] = $keySetnx;
            
            if ($this->setnx($keySetnx, 1, 2)){
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' lock succ '. json_encode($_logs));
            } else {
                //避免死循环, 被锁3次, 立即返回
                if ($lockedTimes > 3) {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locked, locktimes > 3 '.json_encode($_logs));
                    return false;
                }
                
                //延迟0.3秒
                usleep(300000);
                
                //第一次并发不记录
                if ($lockedTimes > 0) {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' usleep '. json_encode($_logs));
                }
                return $this->getOrSet($key, $callable, $expire, $resetCache, $lockedTimes+1);
            }
            
            //----------------------
            //$_logs[__LINE__] = microtime(true);
            
            $cacheData = call_user_func($callable, $this);
            
            //$_logs[__LINE__] = microtime(true);
            
            $this->setex($cacheKey, $expire, serialize($cacheData));
            
            //$_logs[__LINE__] = microtime(true);
            
            $this->del($keySetnx);
            
            $_logs['endtime'] = microtime(true);
            if ($_logs['endtime'] - $_logs['starttime'] > 0.1) {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' timeout110 '. json_encode($_logs));
            }
            
        } else {
            $cacheData = unserialize($cacheData);
            
            $_logs['endtime'] = microtime(true);
            if ($_logs['endtime'] - $_logs['starttime'] > 0.1) {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' timeout110 '. json_encode($_logs));
            }
        }
        
        //$_logs['$cacheData'] = $cacheData;
        //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return $cacheData;
    }
    
    /**
     * 添加到列表的key
     */
    public function addtoListKey($listKey, $itemKey){
        
        if (!$this->sismember($listKey, $itemKey)) {
            //$_logs[$fieldCacheKey.'sadd'] = 1;
            $this->sadd($listKey, $itemKey);
        }
        
        //key不存在返回-2, 用不过期返回-1, 其他返回正常值
        $ttl = $this->ttl($listKey);
        if (!$ttl || $ttl < 7200) {
            //$_logs[$fieldCacheKey.'expire'] = 1;
            $this->expire($listKey, 72000);
        }
        
    }
    
    /**
     * 清空列表的key
     */
    public function cleanListKey($listKey){
        
        $keyMembers = $this->smembers($listKey);
        if ($keyMembers && is_array($keyMembers)) {
            
            foreach($keyMembers as $keyMember){
                $this->del($keyMember);
            }
            $this->del($listKey);
        }
        
    }

    
    /**
     * Allows issuing all supported commands via magic methods.
     *
     * ```php
     * $redis->set('key1', 'val1')
     * ```
     *
     * @param string $name name of the missing method to execute
     * @param array $params method call arguments
     * @return mixed
     */
    public function __call($name, $params)
    {
        //\Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis call '. $name .' '. json_encode($params), __CLASS__);
        \Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis call '. $name .' '. json_encode($params), __CLASS__);
        throw new RedisException('redis exception, method not exist: '.$name);
        
        /*
        if (!method_exists($this->_redis, $name))
        {
            \Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis call '. $name .' '. json_encode($params), __CLASS__);
            throw new RedisException('redis error, method not exist: '.$name);
        }
        
        \Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' _redis call '. $name .' '. json_encode($params), __CLASS__);
        return call_user_func_array([$this->_redis, $name], $params);
        */
    }
}
