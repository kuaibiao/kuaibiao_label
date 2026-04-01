<?php
namespace common\helpers;

use Yii;

/**
 * SecurityHelper provides security functionality that you can use in your
 * application.
 */
class SecurityHelper
{
    /**
     * 校验某元素频率
     *
     * @param string $key
     * @param int $times
     * @param int $time
     * @return boolean
     */
    public static function checkFrequency1($key, $times = 100, $time = 300, $backlist = false)
    {
        $_logs = ['$key' => $key, '$times' => $times, '$time' => $time];
    
        //cache key
        $frequencyKey = Yii::$app->redis->buildKey('frequency:'.$key);
        $_logs['$frequencyKey'] = $frequencyKey;
    
        //黑名单列表
        $blacklistKey = Yii::$app->redis->buildKey('frequency:blacklist');
        $_logs['$blacklistKey'] = $blacklistKey;
    
        //判断是否在黑名单
        if (Yii::$app->redis->sismember($blacklistKey, $key))
        {
            $_logs['$blackip.sismember'] = $key;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is blackip.sismember '.json_encode($_logs));
            return false;
        }
    
        //统计数+1, 返回新统计数
        $count = Yii::$app->redis->incr($frequencyKey);
        $_logs['incr.count'] = $count;
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' incr.count  '.json_encode($_logs));
    
        //获取过期时间
        $ttl = Yii::$app->redis->ttl($frequencyKey);
        if (!$ttl)
        {
            Yii::$app->redis->expire($frequencyKey, $time);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' count = 1 '.json_encode($_logs));
            return true;
        }
    
        // 3600秒内频率大于3600次, 加入黑名单
        if ($count > $times * 2)
        {
            //加入黑名单
            Yii::$app->redis->sadd($blacklistKey, $key);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $count > $times * 2, add blackIp '.json_encode($_logs));
            return false;
        }
        elseif ($count > $times)
        {
            $_logs['$count'] = $count;
            Yii::$app->redis->expire($frequencyKey, $time * 2);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' count > $times '.json_encode($_logs));
            return false;
        }
        //当60秒内频率大于300次, 延时至3600
        elseif ($count == $times)
        {
            $_logs['$count'] = $count;
            Yii::$app->redis->expire($frequencyKey, $time * 2);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' count = $times '.json_encode($_logs));
            return false;
        }
        //初始化时间为60秒
        elseif ($count == 1)
        {
            Yii::$app->redis->expire($frequencyKey, $time);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' count = 1 '.json_encode($_logs));
            return true;
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' count=other '.json_encode($_logs));
            return true;
        }
    }
    
    /**
     * 校验某元素频率
     *
     * @param string $key
     * @param int $times
     * @param int $time
     * @return boolean
     */
    public static function checkFrequency($key, $times = 100, $time = 300)
    {
        $_logs = ['$key' => $key, '$times' => $times, '$time' => $time];
    
        //cache key
        $frequencyKey = Yii::$app->redis->buildKey('frequency:'.$key);
        $_logs['$frequencyKey'] = $frequencyKey;
    
        //统计数+1, 返回新统计数
        $count = Yii::$app->redis->incr($frequencyKey);
        $_logs['incr.count'] = $count;
    
        //获取过期时间
        $ttl = Yii::$app->redis->ttl($frequencyKey);
        $_logs['$ttl'] = $ttl;
        if ($ttl < 0)
        {
            Yii::$app->redis->expire($frequencyKey, $time);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' expire '.json_encode($_logs));
        }
    
        //校验次数
        if ($count > $times)
        {
            //若一直请求将一直存在; 中断请求则会消失
            Yii::$app->redis->expire($frequencyKey, $time);
    
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' count > $times '.json_encode($_logs));
            return $count;
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' count<$times '.json_encode($_logs));
        return false;
    }
    
    /**
     * 并发锁
     * 一段时间内禁止重复执行
     *
     * @param string $key
     * @param number $sec
     * @return boolean
     */
    public static function lock($key, $sec = 3)
    {
        $cacheKey = Yii::$app->redis->buildKey('smalllock:'.$key);
    
        //key不存在, 返回1, 未锁
        if (Yii::$app->redis->setnx($cacheKey, 1))
        {
            Yii::$app->redis->expire($cacheKey, $sec);
            return false;
        }
        //key已存在, 返回0, 已锁
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' redis_lock error '.json_encode([$cacheKey, $sec]));
            return true;
        }
    
        return false;
    }
    
    /**
     * 获取密码的严密性
     *
     * @param unknown $param
     */
    public static function password_power($password, $min_score = 10) {
        $score = 0;
        if(preg_match("/[0-9]+/",$password))
        {
            $score ++;
        }
        if(preg_match("/[0-9]{3,}/",$password))
        {
            $score ++;
        }
        if(preg_match("/[a-z]+/",$password))
        {
            $score ++;
        }
        if(preg_match("/[a-z]{3,}/",$password))
        {
            $score ++;
        }
        if(preg_match("/[A-Z]+/",$password))
        {
            $score ++;
        }
        if(preg_match("/[A-Z]{3,}/",$password))
        {
            $score ++;
        }
        if(preg_match('/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]+/',$password))
        {
            $score += 2;
        }
        if(preg_match('/[_|\-|+|=|*|!|@|#|$|%|^|&|(|)]{3,}/',$password))
        {
            $score ++ ;
        }
        if(strlen($password) >= 10)
        {
            $score ++;
        }
    
        if ($score < $min_score)
        {
            return false;
        }
    
        return true;
    }
    
    public static function encrypt_encode($string)
    {
        return StringHelper::base64_encode(self::authcode($string,"ENCODE"));
    }
    
    public static function encrypt_decode($string)
    {
        return self::authcode(StringHelper::base64_decode($string),"DECODE");
    }
    
    /**
     * 加密解密字符串
     * @param unknown $string
     * @param string $operation
     * @param string $key
     * @param number $expiry
     * @return string
     */
    public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        $ckey_length = 4;
        // 密匙
        $key = md5($key ? $key : 'maikesi.org.2015');
    
        // 密匙a会参与加解密
        $keya = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证
        $keyb = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
        // 参与运算的密匙
        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，
        //解密时会通过这个密匙验证数据完整性
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :  sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);
        $result = '';
        $box = range(0, 255);
        $rndkey = array();
        // 产生密匙簿
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分
        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if($operation == 'DECODE') {
            // 验证数据有效性，请看未加密明文的格式
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&
                substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                    return substr($result, 26);
                } else {
                    return '';
                }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }
    
    /**
     *散列函数
     *根据ID得到0～m-1之间的值
     *
     * @param string $id
     * @param int $m
     * @return int
     */
    public static function hashid($id, $m=10)
    {
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for($i=0;$i<$l;$i++)
        {
            //相加模式HASH
            $h += substr($b,$i*2,2);
        }
        $hash = ($h*1)%$m;
        return $hash;
    }
    
    public static function generateValidationHash($code)
    {
        for ($h = 0, $i = strlen($code) - 1; $i >= 0; --$i) {
            $h += ord($code[$i]);
        }
    
        return $h;
    }
}