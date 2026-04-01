<?php

namespace diszz\phpredis;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * redis queue
 *
 * [
 *     'components' => [
 *         'queue' => [
 *             'class' => 'diszz\phpredis\Queue',
 *             'keyPrefix' => 'saas.queue:',
 *             'maxRunTimelong' => 299,//进程最大运行时间,单位秒
 *             'phpbin' => 'php',
 *             'consoleStartAction' => 'mq/start',//把此命令加到crontab里
 *             'consoleListenAction' => 'mq/listen',
 *             'channels' => [
 *                 'mq/test-func' => 2,//启动的最大进程数,最少2个
 *             ],
 *         ],
 *     ],
 * ]
 *
 * @property boolean $useCustomStorage Whether to use custom storage. This property is read-only.
 *
 */
class Queue extends Component
{
    /**
     * @var Connection|string|array the Redis [[Connection]] object or the application component ID of the Redis [[Connection]].
     * This can also be an array that is used to create a redis [[Connection]] instance in case you do not want do configure
     * redis connection as an application component.
     * After the Queue object is created, if you want to change this property, you should only assign it
     * with a Redis [[Connection]] object.
     */
    public $redis = 'redis';

    /**
     * @var string a string prefixed to every cache key so that it is unique. If not set,
     * it will use a prefix generated from [[Application::id]]. You may set this property to be an empty string
     * if you don't want to use key prefix. It is recommended that you explicitly set this property to some
     * static value if the cached data needs to be shared among multiple applications.
     */
    public $keyPrefix = 'queue';
    
    //最大等待时间=60的整数倍-1
    public $maxRunTimelong = 179;
    
    /**
     * 执行方法
     * 注意必须是static方法
     * 
     * console\controllers\MqController
     * public static function actionTestFunc($param){
     * }
     * 
     */
    public $channels = [
        'mq/test-func',
    ];
    
    public $phpbin = 'php';

    public $consoleStartAction = 'site/start';
    
    public $consoleListenAction = 'site/listen';
    
    //无任务是休息0.1秒, 1秒=1000000微秒
    public $usleepTime = 100000;
    
    /**
     * Initializes the redis Queue component.
     * This method will initialize the [[redis]] property to make sure it refers to a valid redis connection.
     * @throws InvalidConfigException if [[redis]] is invalid.
     */
    public function init()
    {
        \Yii::trace("Queue init ", __CLASS__);
        
        if (is_string($this->redis)) {
            $this->redis = Yii::$app->get($this->redis);
        } elseif (is_array($this->redis)) {
            if (!isset($this->redis['class'])) {
                $this->redis['class'] = Connection::className();
            }
            $this->redis = Yii::createObject($this->redis);
        }
        if (!$this->redis instanceof Connection) {
            throw new InvalidConfigException("Queue::redis must be either a Redis connection instance or the application component ID of a Redis connection.");
        }
        if ($this->keyPrefix === null) {
            $this->keyPrefix = substr(md5(Yii::$app->id), 0, 5);
        }
        $this->redis->open();
        
        \Yii::trace("Queue init end ", __CLASS__);
        parent::init();
    }
    
    public function getChannelKey($channel) {
        return $this->keyPrefix.$channel;
    }
    public function getDeamonKey($channel) {
        return $this->keyPrefix.'.deamon.'.$channel;
    }
    
    /**
     * 发布者
     *
     */
    public function publish(string $channel, array $message){
        $_logs = ['$channel' => $channel, '$message' => $message];
        
        $cacheKey = $this->getChannelKey($channel);
        $_logs['$cacheKey'] = $cacheKey;
        
        $ret = $this->redis->lpush($cacheKey, json_encode($message));
        $_logs['$ret'] = $ret;
        
        Yii::debug(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
        return $ret;
    }
    
    public function getMessageCount($channel) {
        
        return $this->redis->llen($this->getChannelKey($channel));
    }
    
    public function getListenKey($channel) {
        return $this->keyPrefix.'.listen.'.$channel;
    }
    
    public function getListenCount($channel) {
        return $this->redis->get($this->getListenKey($channel));
    }
    
    public function setListenCount($channel, $count) {
        return $this->redis->setex($this->getListenKey($channel), $this->maxRunTimelong, $count);
    }
    
    public function getDeamonCount($channel) {
        return $this->redis->llen($this->getDeamonKey($channel));
    }
    
    
    /**
     * 监听,消费者
     * 
     * @param bool $channel
     */
    public function listen($channel) {
        
        $_logs = ['$channel' => $channel];
        
        if (!strpos($channel, '/')) {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $channel error ' . json_encode($_logs));
            return false;
        }
        list($class, $action) = explode('/', $channel);
        
        $class = ucfirst($class) . 'Controller';
        
        $actions = explode('-', $action);
        $action = 'action';
        foreach ($actions as $str) {
            $action .= ucfirst($str);
        }
        
        $timeNow = time();
        
        $_logs['$class'] = $class;
        $_logs['$action'] = $action;
        $_logs['usleepTime'] = $this->usleepTime;
        $_logs['$timeNow'] = $timeNow;
        $_logs['maxRunTimelong'] = $this->maxRunTimelong;
        
        
        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' start ' . json_encode($_logs));
        
        while (((time() - $timeNow) < $this->maxRunTimelong)) {
            
            try {
                
                $channelKey = $this->getChannelKey($channel);
                $message = $this->redis->lpop($channelKey);
                $_logs['$channel'] = $channel;
                $_logs['$message'] = $message;
                
                if ($message) {
                    //守护队列key
                    $channelDeamonKey = $this->getDeamonKey($channel);
                    //加入到守护队列
                    $this->redis->lpush($channelDeamonKey, $message);
                    
                    $_logs['$channelDeamonKey'] = $channelDeamonKey;
                    
                    $callResult = call_user_func_array(['console\\controllers\\' . $class, $action], [json_decode($message, true)]);
                    $_logs['$callResult'] = $callResult;
                    
                    if ($callResult === false) {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' runException '.serialize($_logs));
                    } else {
                        $this->redis->lrem($channelDeamonKey, $message);
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' remove_from_deamon '.serialize($_logs));
                    }
                    //$message->ack();
                }else{
                    //暂停10000微秒, 1秒=1000000微秒
                    usleep($this->usleepTime);
                }
                
            } catch(\Exception $e) {
                $eMessage = $e->getMessage();
                $_logs['$e.file'] = $e->getFile();
                $_logs['$e.line'] = $e->getLine();
                $_logs['$e.code'] = $e->getCode();
                $_logs['$e.message'] = $eMessage;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exception '.serialize($_logs));
                
                exit;
            }
        }
        
        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' end ' . json_encode($_logs));
        return true;
    }
}
