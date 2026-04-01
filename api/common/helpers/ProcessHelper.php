<?php
namespace common\helpers;

use Yii;

/**
 * ProcessHelper provides process functionality that you can use in your
 * application.
 */
class ProcessHelper
{
    /**
     * 判断进程是否在执行
     * @param str|array $cmd_keys
     * @return boolean : 单位秒, 0为无超时
     */
    public static function processIsRunning($cmd_keys, $timeout = 600, $allowProcessCount = 1)
    {
        $_logs = ['$cmd_keys' => $cmd_keys, '$timeout' => $timeout, '$allowProcessCount' => $allowProcessCount];
    
        if (strtolower(PHP_OS) != 'linux')
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' os not linux '.json_encode($_logs));
            return false;
        }

        //组合cmd查询命令
        $cmd = "ps -o pid,etimes,command ax ";
        if (is_array($cmd_keys))
        {
            foreach ($cmd_keys as $item)
            {
                $cmd .= "| grep '{$item}' ";
            }
        }
        else
        {
            $cmd .= "| grep '{$cmd_keys}' ";
        }
        $cmd .= "| awk '!/awk/ && !/grep/'";
        $_logs['$cmd'] = $cmd;
        Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cmd '.json_encode($_logs));
    
        //判断是否有任务在执行
        @exec($cmd, $return, $error);
        $_logs['$return'] = $return;
        $_logs['$error'] = $error;
        Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exec '.json_encode($_logs));
    
        //有错误
        if ($error !== 0)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ps error '.json_encode($_logs));
            return FALSE;
        }
        //正常情况
        elseif ($return && is_array($return))
        {
            $runningProcess = 0;
            $selfpid = getmypid();
            $_logs['$selfpid'] = $selfpid;
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' self pid '.json_encode($_logs));
             
            //43070 18:37:00 php \/opt\/www\/cms\/tools\/cms_html_sync_monitor.php
            foreach ($return as $key => $item)
            {
                preg_match('/\s*([\d]+)\s+([\d]+)\s+(.+)/', $item, $matches);
                $_logs['$key'] = $key;
                $_logs['$matches'] = $matches;
                //array(4) {string(42) "37009 00:00:00 tail -f search_rsync.py.log"[1]=>string(5) "37009"[2]=>string(8) "00:00:00"[3]=>string(27) "tail -f search_rsync.py.log"}
                if (!$matches)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' thread item not preg_match '.json_encode($_logs));
                    continue;
                }
    
                //匹配的情况
                list($str, $pid, $etimes, $cmd) = $matches;
                $_logs['$str'] = $str;
                $_logs['$pid'] = $pid;
                $_logs['$etimes'] = $etimes;
                $_logs['$cmd'] = $cmd;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' thread item info '.json_encode($_logs));

                $time_length = $etimes;
                $_logs['$time_length'] = $time_length;
                
                //自身则跳过
                if (intval($pid) == $selfpid)
                {
                    Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' thread item isself '.json_encode($_logs));
                    continue;
                }
    
                //去除特殊命令
                if (strpos($cmd, 'tail') !== false)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' thread item istail '.json_encode($_logs));
                    continue;
                }
    
                if ($timeout && $time_length > $timeout)
                {
                    //kill 进程
                    $kill_cmd = "kill -9 ".$pid;
                    $_logs['$kill_cmd'] = $kill_cmd;
    
                    @exec($kill_cmd, $kill_return, $kill_error);
                    $_logs['$kill_return'] = $kill_return;
                    $_logs['$kill_error'] = $kill_error;
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' thread item kill thread '.json_encode($_logs));
                     
                    continue;
                }
                else
                {
                    Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' thread item is running '.json_encode($_logs));
                    $runningProcess += 1;
                }
            }
            $_logs['$runningProcess'] = $runningProcess;
    
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' check $isRunning '.json_encode($_logs));
            return $runningProcess >= $allowProcessCount;
        }
        else
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ps result empty '.json_encode($_logs));
            return false;
        }
    
        return FALSE;
    }
    
    public static function crontabQueue($cmds, $phpbin, $rootPath)
    {
        //存入队列
        $_logs['file'] = __FILE__;
        $_logs['pid'] = getmypid();
        
        $dayNow = (int)date('d');
        $hourNow = (int)date('H');
        $hourMinute = (int)date('i');
        $outputfile = Yii::getAlias('@app/../logfile/'.date('ymd').'/output.'.date('H').'.log');
        
        $_logs['$dayNow'] = $dayNow;
        $_logs['$hourNow'] = $hourNow;
        $_logs['$hourMinute'] = $hourMinute;
        
        $tasks = [];
        if ($cmds) {
            foreach ($cmds as $cmd)
            {
                $_logs['$cmd'] = $cmd;
                
                if (empty($cmd['timelong']) || empty($cmd['script']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config error '. json_encode($_logs));
                    continue;
                }
                
                if (isset($cmd['start_day']) && is_numeric($cmd['start_day']))
                {
                    if ($dayNow != $cmd['start_day'])
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' day_error '. json_encode($_logs));
                        continue;
                    }
                }
                
                if (isset($cmd['start_hour']) && is_numeric($cmd['start_hour']))
                {
                    if ($hourNow != $cmd['start_hour'])
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' hour_error '. json_encode($_logs));
                        continue;
                    }
                }
                
                if (isset($cmd['start_minute']) && is_numeric($cmd['start_minute']))
                {
                    if ($hourMinute != $cmd['start_minute'])
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' minute_error '. json_encode($_logs));
                        continue;
                    }
                }
                
                $phase = floor(time() / $cmd['timelong']);
                $scriptKey = $phase .'.'. $cmd['script'].'.'.$cmd['timelong'];
                $_logs['$scriptKey'] = $scriptKey;
                
                $tasks[] = $scriptKey;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cmd succ '. json_encode($_logs));
            }
        }
        
        $_logs['$tasks'] = $tasks;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cmd test '. json_encode($_logs));
        //-------------------------------------------
        
        if ($tasks)
        {
            foreach ($tasks as $cacheCmd)
            {
                $_logs['$cacheCmd'] = $cacheCmd;
                
                list($phase, $script, $timelong) = explode('.', $cacheCmd);
                $_logs['$phase'] = $phase;
                $_logs['$script'] = $script;
                
                //-------------------------------------------------
                //并发锁
                if (!Yii::$app->redis->lock($cacheCmd, $timelong))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' lock error '. json_encode($_logs));
                    continue;
                }
                
                //-------------------------------------------------
                
                //$command = sprintf('nohup %s %s %s > /dev/null 2>&1 &', $phpbin, $rootPath, $script);
                $command = sprintf('nohup %s %s %s >> %s 2>&1 &', $phpbin, $rootPath, $script, $outputfile);
                
                @exec($command, $output, $return);
                $_logs['exec.$command'] = $command;
                $_logs['exec.$output'] = $output;
                $_logs['exec.$return'] = $return;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' nohup exec '. json_encode($_logs));
                
                //间隔1秒, 防止并发
                sleep(1);
            }
        }
        
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return true;
    }
    
    public static function crontabQueue1($cmds, $phpbin, $rootPath)
    {
        //存入队列
        $cacheKey = Yii::$app->redis->buildKey('crontab-queue.'.date('Ymd'));
        $_logs['$cacheKey'] = $cacheKey;
        $_logs['file'] = __FILE__;
        $_logs['pid'] = getmypid();
        
        foreach ($cmds as $cmd)
        {
            $_logs['$cmd'] = $cmd;
            
            if (empty($cmd['timelong']) || empty($cmd['script']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config error '. json_encode($_logs));
                continue;
            }
            
            if (isset($cmd['start_day']) && is_numeric($cmd['start_day']))
            {
            	$dayNow = (int)date('d');
                $_logs['$dayNow'] = $dayNow;
                if ($dayNow != $cmd['start_day'])
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' day_error '. json_encode($_logs));
                    continue;
                }
            }
            
            if (isset($cmd['start_hour']) && is_numeric($cmd['start_hour']))
            {
                $hourNow = (int)date('H');
                $_logs['$hourNow'] = $hourNow;
                if ($hourNow != $cmd['start_hour'])
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' hour_error '. json_encode($_logs));
                    continue;
                }
            }
            
            if (isset($cmd['start_minute']) && is_numeric($cmd['start_minute']))
            {
            	$hourMinute = (int)date('i');
                $_logs['$hourMinute'] = $hourMinute;
                if ($hourMinute != $cmd['start_minute'])
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' minute_error '. json_encode($_logs));
                    continue;
                }
            }
            
            $phase = floor(time() / $cmd['timelong']);
            $scriptKey = $phase .'.'. $cmd['script'];
            $_logs['$scriptKey'] = $scriptKey;
            
            //判断是否存在
            $isExistScore = Yii::$app->redis->zscore($cacheKey, $scriptKey);
            $_logs['$isExistScore'] = $isExistScore;
            if (is_numeric($isExistScore))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $isExistScore '. json_encode($_logs));
                continue;
            }
            
            //添加到队列, 已存在会忽略
            $isAdd = Yii::$app->redis->zadd($cacheKey, $phase, $scriptKey);
            $_logs['$isAdd'] = $isAdd;
            if (!$isAdd)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' add error '. json_encode($_logs));
                continue;
            }
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cmd succ '. json_encode($_logs));
        }
        
        //生命期为一天
        Yii::$app->redis->expire($cacheKey, 86400);
        
        //-------------------------------------------
        
        //执行队列
        $cacheCmds = Yii::$app->redis->ZRANGEBYSCORE($cacheKey, 1, time());//, ['LIMIT' =>  [1, 1]]
        $_logs['$cacheCmds'] = $cacheCmds;
        if ($cacheCmds)
        {
            foreach ($cacheCmds as $cacheCmd)
            {
                $_logs['$cacheCmd'] = $cacheCmd;
                //-------------------------------------------------
                //并发锁
                $cacheKeyCmd = Yii::$app->redis->buildKey('crontab-queue.cmd.'.$cacheCmd);
                $_logs['$cacheKeyCmd'] = $cacheKeyCmd;
                if (!Yii::$app->redis->setnx($cacheKeyCmd, 1))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setnx error '. json_encode($_logs));
                    continue;
                }
                Yii::$app->redis->expire($cacheKeyCmd, 30);
                
                //-------------------------------------------------
                //更新此项score为0
                Yii::$app->redis->zadd($cacheKey, 0, $cacheCmd);
                
                //-------------------------------------------------
                
                list($phase, $script) = explode('.', $cacheCmd);
                $_logs['$phase'] = $phase;
                $_logs['$script'] = $script;
                
                $command = sprintf('nohup %s %s %s > /dev/null 2>&1 &', $phpbin, $rootPath, $script);
                @exec($command, $output, $return);
                $_logs['exec.$command'] = $command;
                $_logs['exec.$output'] = $output;
                $_logs['exec.$return'] = $return;
                Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' nohup exec '. json_encode($_logs));
                
                //间隔1秒, 防止并发
                sleep(1);
                
            }
        }
        
        //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return true;
    }
    
    public static function ps($cmd_keys)
    {
        $_logs = ['$cmd_keys' => $cmd_keys];
        
        //组合cmd查询命令
        $cmd = "ps -o pid,time,command ax ";
        if (is_array($cmd_keys))
        {
            foreach ($cmd_keys as $item)
            {
                $cmd .= "| grep '{$item}' ";
            }
        }
        else
        {
            $cmd .= "| grep '{$cmd_keys}' ";
        }
        $cmd .= "| awk '!/awk/ && !/grep/'";
        $_logs['$cmd'] = $cmd;
        Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cmd '.json_encode($_logs));
        
        //判断是否有任务在执行
        @exec($cmd, $return, $error);
        $_logs['$return'] = $return;
        $_logs['$error'] = $error;
        Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exec '.json_encode($_logs));
        
        //有错误
        if ($error !== 0)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ps error '.json_encode($_logs));
            return FALSE;
        }
        //正常情况
        elseif ($return && is_array($return))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ps succ '.json_encode($_logs));
            return true;
        }
    }

    private static $timeStart;
    
    /**
     * 给运行的程序打断点计时
     * @param $breakPoint 程序中断点位置
     *
     * @return $timeLong 两个断点之间的时长
     */
    public static function runTimeLong($breakPoint = '')
    {
        $_logs['$breakPoint'] = $breakPoint;

        $timeLong = 0;
        if(self::$timeStart)
        {
            $timeLong = time() - self::$timeStart;
            $_logs['$timeLong'] = $timeLong;
            if($timeLong > 10)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' time too long '.json_encode($_logs));
            }
            else
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' time normal '.json_encode($_logs));
            }
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' time start '.json_encode($_logs));
        }
        self::$timeStart = time();

        return $timeLong;
    }
}