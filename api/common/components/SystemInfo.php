<?php 

namespace common\components;

use yii;
class SystemInfo
{
    //服务器参数
    public $S = array(
        'YourIP',//你的IP
        'DomainIP',//服务器域名和IP及进程用户名
        'Flag',//服务器标识
        'OS',//服务器操作系统具体
        'Language',//服务器语言
        'Name',//服务器主机名
        'Email',//服务器管理员邮箱
        'WebEngine',//服务器WEB服务引擎
        'WebPort',//web服务端口
        'WebPath',//web路径
        'ProbePath',//本脚本所在路径
        'sTime'//服务器时间
    );
    
    public $sysInfo; //系统信息，windows和linux
    public $CPU_Use;
    public $hd = array(
        't',//硬盘总量
        'f',//可用
        'u',//已用
        'PCT',//使用率
    );
    public $NetWork = array(
        'NetWorkName',//网卡名称
        'NetOut',//出网总量
        'NetInput',//入网总量
        'OutSpeed',//出网速度
        'InputSpeed'//入网速度
    );//网卡流量
    
    public function init()
    {
        $this->S['YourIP'] = @$_SERVER['REMOTE_ADDR'];
        $domain= $this->OS()?$_SERVER['SERVER_ADDR']:@gethostbyname($_SERVER['SERVER_NAME']);
        $this->S['DomainIP'] = @get_current_user().' - '.$_SERVER['SERVER_NAME'].'('.$domain.')';
        $this->S['Flag'] =empty($this->sysInfo['win_n'])?@php_uname():$this->sysInfo['win_n'];
        $os= explode(" ", php_uname());
        $oskernel= $this->OS()?$os[2]:$os[1];
        $this->S['OS'] =$os[0].Yii::t('app', 'system_info_Kernel_version').'：'.$oskernel;
        $this->S['Language'] =getenv("HTTP_ACCEPT_LANGUAGE");
        $this->S['Name'] =$this->OS()?$os[1]:$os[2];
        $this->S['Email'] =isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : '';
        $this->S['WebEngine'] =isset($_SERVER['SERVER_SOFTWARE']) ?  $_SERVER['SERVER_SOFTWARE'] : '';
        $this->S['WebPort'] =isset($_SERVER['SERVER_PORT']) ?  $_SERVER['SERVER_PORT'] : '';
        $this->S['WebPath'] =$_SERVER['DOCUMENT_ROOT']?str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']):str_replace('\\','/',dirname(__FILE__));
        $this->S['ProbePath'] =str_replace('\\','/',__FILE__)?str_replace('\\','/',__FILE__):$_SERVER['SCRIPT_FILENAME'];
        $this->S['sTime'] =date('Y-m-d H:i:s');
        
        $this->sysInfo =$this->GetsysInfo();
        //var_dump($this->sysInfo);
        
        $CPU1= $this->GetCPUUse();
        sleep(1);
        $CPU2= $this->GetCPUUse();
        $data= $this->GetCPUPercent($CPU1,$CPU2);
        $this->CPU_Use =$data['cpu0']['user']."%us,  ".$data['cpu0']['sys']."%sy,  ".$data['cpu0']['nice']."%ni, ".$data['cpu0']['idle']."%id,  ".$data['cpu0']['iowait']."%wa,  ".$data['cpu0']['irq']."%irq,  ".$data['cpu0']['softirq']."%softirq";
        if(!$this->OS())$this->CPU_Use ='目前只支持Linux系统';
        
        $this->hd =$this->GetDisk();
        $this->NetWork =$this->GetNetWork();
    }
    
    public function OS(){
        return DIRECTORY_SEPARATOR=='/'?true:false;
    }
    
    public function GetsysInfo(){
        switch(PHP_OS) {
            case'Linux':
                $sysInfo = $this->sys_linux();
                break;
            case'FreeBSD':
                $sysInfo = $this->sys_freebsd();
                break;
            default:
                # code...
                break;
        }
        return $sysInfo;
    }
    
    public function sys_linux(){ //linux系统探测
        $str = @file("/proc/cpuinfo");//获取CPU信息
        if(!$str) return false;
        $str = implode("",$str);
        @preg_match_all("/model\s+name\s{0,}\:+\s{0,}([\w\s\)\(\@.-]+)([\r\n]+)/s",$str, $model);//CPU 名称
        @preg_match_all("/cpu\s+MHz\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/",$str, $mhz);//CPU频率
        @preg_match_all("/cache\s+size\s{0,}\:+\s{0,}([\d\.]+\s{0,}[A-Z]+[\r\n]+)/",$str, $cache);//CPU缓存
        @preg_match_all("/bogomips\s{0,}\:+\s{0,}([\d\.]+)[\r\n]+/",$str, $bogomips);//
        if(isset($model[1]) && is_array($model[1])){
            $cpunum= count($model[1]);
            $x1= $cpunum>1?' ×'.$cpunum:'';
            $model[1][0] = isset($model[1][0]) ? $model[1][0]: '';
            $mhz[1][0] = isset($mhz[1][0]) ? ' | '.Yii::t('app', 'system_info_frequency').':'.$mhz[1][0] : '';
            $cache[1][0] =isset($cache[1][0]) ? ' | '.Yii::t('app', 'system_info_L2_cache').':'.$cache[1][0] : '';
            $bogomips[1][0] =isset($bogomips[1][0]) ? ' | Bogomips:'.$bogomips[1][0] : '';
            $res['cpu']['num'] = $cpunum;
            $res['cpu']['model'][] = $model[1][0].$mhz[1][0].$cache[1][0].$bogomips[1][0].$x1;
            if(isset($res['cpu']['model']) && is_array($res['cpu']['model']))$res['cpu']['model'] = implode("<br />", $res['cpu']['model']);
            if(isset($res['cpu']['mhz']) && is_array($res['cpu']['mhz']))$res['cpu']['mhz'] = implode("<br />", $res['cpu']['mhz']);
            if(isset($res['cpu']['cache']) && is_array($res['cpu']['cache']))$res['cpu']['cache'] = implode("<br />", $res['cpu']['cache']);
            if(isset($res['cpu']['bogomips']) && is_array($res['cpu']['bogomips']))$res['cpu']['bogomips'] = implode("<br />", $res['cpu']['bogomips']);
        }
        //服务器运行时间
        $str= @file("/proc/uptime");
        if(!$str) return false;
        $str= explode(" ", implode("",$str));
        $uptime= trim($str[0]);
        $min= $uptime/60;
        $hours= $min/60;
        $days= floor($hours/24);
        $hours= floor($hours-($days*24));
        $min= floor($min-($days*60*24)-($hours*60));
        $res['uptime'] =$days.Yii::t('app', 'day').$hours.Yii::t('app', 'hour').$min.Yii::t('app', 'minute');
        
        //系统空闲时间
        $freetime= trim($str[1]);
        $min= $freetime/60;
        $hours= $min/60;
        $days= floor($hours/24);
        $hours= floor($hours-($days*24));
        $min= floor($min-($days*60*24)-($hours*60));
        $res['freetime'] =$days.Yii::t('app', 'day').$hours.Yii::t('app', 'hour').$min.Yii::t('app', 'minute');
        //内存
        $str= @file("/proc/meminfo");
        if(!$str) return false;
        $str= implode("",$str);
        preg_match_all("/MemTotal\s{0,}\:+\s{0,}([\d\.]+).+?MemFree\s{0,}\:+\s{0,}([\d\.]+).+?Cached\s{0,}\:+\s{0,}([\d\.]+).+?SwapTotal\s{0,}\:+\s{0,}([\d\.]+).+?SwapFree\s{0,}\:+\s{0,}([\d\.]+)/s",$str, $buf);
        preg_match_all("/Buffers\s{0,}\:+\s{0,}([\d\.]+)/s",$str, $buffers);
        // $resmem['memTotal'] =round($buf[1][0]/1024, 2);
        // $resmem['memFree'] =round($buf[2][0]/1024, 2);
        // $resmem['memBuffers'] =round($buffers[1][0]/1024, 2);
        // $resmem['memCached'] =round($buf[3][0]/1024, 2);
        // $resmem['memUsed'] =$resmem['memTotal']-$resmem['memFree'];
        // $resmem['memPercent'] = (floatval($resmem['memTotal'])!=0)?round($resmem['memUsed']/$resmem['memTotal']*100,2):0;
        // $resmem['memRealUsed'] =$resmem['memTotal'] -$resmem['memFree'] -$resmem['memCached'] -$resmem['memBuffers'];//真实内存使用
        // $resmem['memRealFree'] =$resmem['memTotal'] -$resmem['memRealUsed'];//真实空闲
        // $resmem['memRealPercent'] = (floatval($resmem['memTotal'])!=0)?round($resmem['memRealUsed']/$resmem['memTotal']*100,2):0;//真实内存使用率
        // $resmem['memCachedPercent'] = (floatval($resmem['memCached'])!=0)?round($resmem['memCached']/$resmem['memTotal']*100,2):0;//Cached内存使用率
        // $resmem['swapTotal'] =round($buf[4][0]/1024, 2);
        // $resmem['swapFree'] =round($buf[5][0]/1024, 2);
        // $resmem['swapUsed'] =round($resmem['swapTotal']-$resmem['swapFree'], 2);
        // $resmem['swapPercent'] = (floatval($resmem['swapTotal'])!=0)?round($resmem['swapUsed']/$resmem['swapTotal']*100,2):0;
        $resmem= $this->linux_formatmem($buf, $buffers);//格式化内存显示单位
        $res= array_merge($res,$resmem);
        // LOAD AVG 系统负载
        $str= @file("/proc/loadavg");
        if(!$str)return false;
        $str= explode(" ", implode("",$str));
        $str= array_chunk($str, 4);
        $res['loadAvg'] = implode(" ", $str[0]);
        return $res;
    }
    
    public function sys_freebsd(){ //freeBSD系统探测
        $res['cpu']['num']   = do_command('sysctl','hw.ncpu');//CPU
        $res['cpu']['model'] = do_command('sysctl','hw.model');
        $res['loadAvg']      = do_command('sysctl','vm.loadavg');//Load AVG  系统负载
        //uptime
        $buf= do_command('sysctl','kern.boottime');
        $buf= explode(' ',$buf);
        $sys_ticks= time()-intval($buf[3]);
        $min= $sys_ticks/60;
        $hours= $min/60;
        $days= floor($hours/24);
        $hours= floor($hours-($days*24));
        $min= floor($min-($days*60*24)-($hours*60));
        $res['uptime'] =$days.'天'.$hours.'小时'.$min.'分钟';
        //内存
        $buf= do_command('sysctl','hw.physmem');
        $str= do_command('sysctl','vm.vmtotal');
        preg_match_all("/\nVirtual Memory[\:\s]*\(Total[\:\s]*([\d]+)K[\,\s]*Active[\:\s]*([\d]+)K\)\n/i",$str, $buff, PREG_SET_ORDER);
        preg_match_all("/\nReal Memory[\:\s]*\(Total[\:\s]*([\d]+)K[\,\s]*Active[\:\s]*([\d]+)K\)\n/i",$str, $buf, PREG_SET_ORDER);
        // $resmem['memTotal'] =round($buf/1024/1024, 2);
        // $resmem['memRealUsed'] =round($buf[0][2]/1024, 2);
        // $resmem['memCached'] =round($buff[0][2]/1024, 2);
        // $resmem['memUsed'] =round($buf[0][1]/1024, 2)+$resmem['memCached'];
        // $resmem['memFree'] =$resmem['memTotal']-$resmem['memUsed'];
        // $resmem['memPercent'] = (floatval($resmem['memTotal'])!=0)?round($resmem['memUsed']/$resmem['memTotal']*100,2):0;
        // $resmem['memRealPercent'] = (floatval($resmem['memTotal'])!=0)?round($resmem['memRealUsed']/$resmem['memTotal']*100,2):0;
        $resmem= $this->freebsd_formatmem($buf, $buff);
        $res= array_merge($res,$resmem);
        return $res;
    }
    
    public function do_command($cName,$args){ //执行系统命令FreeBSD
        $cName= empty($cName)?'sysctl':timr($cName);
        if(empty($args)) return false;
        $args= '-n '.$args;
        $buffers= '';
        $command= find_command($cName);
        if(!$command) return false;
        if($fp= @popen("$command $args",'r')){
            while(!@feof($fp)) {
                $buffers.= @fgets($fp, 4096);
            }
            pclose($fp);
            return trim($buffers);
        }
        return false;
    }
    
    public function find_command($cName){//确定shell位置
        $path= array('/bin','/sbin', '/usr/bin','/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
        foreach($path as $p) {
            if(@is_executable("$p/$commandName")) return "$p/$commandName";
        }
        return false;
    }
    
    public function GetCPUUse(){
        $data= @file('/proc/stat');
        $cores= array();
        foreach($data as $line) {
            if(preg_match('/^cpu[0-9]/',$line)){
                $info= explode(' ',$line);
                $cores[]=array('user'=>$info[1],'nice'=>$info[2],'sys'=> $info[3],'idle'=>$info[4],'iowait'=>$info[5],'irq'=> $info[6],'softirq'=> $info[7]);
            }
        }
        return $cores;
    }
    
    public function GetCPUPercent($CPU1,$CPU2){
        $num= count($CPU1);
        if($num!==count($CPU2)) return;
        $cups= array();
        for($i=0;$i < $num;$i++) {
            $dif= array();
            $dif['user']    =$CPU2[$i]['user'] -$CPU1[$i]['user'];
            $dif['nice']    =$CPU2[$i]['nice'] -$CPU1[$i]['nice'];
            $dif['sys']     =$CPU2[$i]['sys'] -$CPU1[$i]['sys'];
            $dif['idle']    =$CPU2[$i]['idle'] -$CPU1[$i]['idle'];
            $dif['iowait']  =$CPU2[$i]['iowait'] -$CPU1[$i]['iowait'];
            $dif['irq']     =$CPU2[$i]['irq'] -$CPU1[$i]['irq'];
            $dif['softirq'] =$CPU2[$i]['softirq'] -$CPU1[$i]['softirq'];
            $total= array_sum($dif);
            $cpu= array();
            foreach($dif as $x=>$y)
                $cpu[$x] =round($y/$total*100, 2);
                $cpus['cpu'.$i] = $cpu;
        }
        return $cpus;
    }
    
    public function GetDisk(){ //获取硬盘情况
        $totalSpace = disk_total_space(".");
        $freeSpace = disk_free_space(".");
        $d['t'] =round(@$totalSpace/(1024*1024*1024),3);
        $d['f'] =round(@$freeSpace/(1024*1024*1024),3);
        $d['u'] =round(@($totalSpace - $freeSpace)/(1024*1024*1024),3);
        $d['PCT'] = (floatval($d['t'])!=0)?round($d['u']/$d['t']*100,2):0;
        return $d;
    }
    
    // private function formatmem($mem){//格试化内存显示单位
    //     if(!is_array($mem)) return $mem;
    //     $tmp= array(
    //         'memTotal','memUsed', 'memFree', 'memPercent',
    //         'memCached','memRealPercent',
    //         'swapTotal','swapUsed', 'swapFree', 'swapPercent'
    //     );
    //     foreach($mem as $k=>$v) {
    //         if(!strpos($k,'Percent')){
    //             $v= $v<1024?$v.' M':round($v/1024, 2).' G';
    //         }
    //         $mem[$k] =$v;
    //     }
    //     foreach($tmp as $v) {
    //         $mem[$v] =$mem[$v]?$mem[$v]:0;
    //     }
    //     return $mem;
    // }
    
    private function freebsd_formatmem($buf, $buff){//格试化内存显示单位
        $tmp= array(
            'memTotal','memUsed', 'memFree', 'memPercent',
            'memCached','memRealPercent',
            'swapTotal','swapUsed', 'swapFree', 'swapPercent'
        );
        
        $memTotal = round($buf/1024/1024, 2);
        $resmem['memTotal'] =$memTotal < 1024 ? $memTotal.' M' : round($buf/1024/1024/1024, 2).' G';
        $memRealUsed = round($buf[0][2]/1024, 2);
        $resmem['memRealUsed'] =$memRealUsed < 1024 ? $memRealUsed.' M' : round($buf[0][2]/1024/1024, 2).' G';
        $memCached = round($buff[0][2]/1024, 2);
        $resmem['memCached'] =$memCached < 1024 ? $memCached.' M' : round($buff[0][2]/1024/1024, 2).' G';
        $memUsed = round(($buf[0][1] + $buff[0][2])/1024, 2);
        $resmem['memUsed'] =$memUsed < 1024 ? $memUsed.' M' : round(($buf[0][1] + $buff[0][2])/1024/1024, 2).' G';
        $memFree = round(($buf/1024/1024 - ($buf[0][1] + $buff[0][2]))/1024, 2);
        $resmem['memFree'] =$memFree < 1024 ? $memFree.' M' : round(($buf/1024/1024 - ($buf[0][1] + $buff[0][2]))/1024/1024, 2).' G';
        $resmem['memPercent'] = (floatval($memTotal)!=0)?round($memUsed/$memTotal*100,2):0;
        $resmem['memRealPercent'] = (floatval($memTotal)!=0)?round($memRealUsed/$memTotal*100,2):0;
        
        return $resmem;
    }
    
    private function linux_formatmem($buf, $buffers){//格试化内存显示单位
        $tmp= array(
            'memTotal','memUsed', 'memFree', 'memPercent',
            'memCached','memRealPercent',
            'swapTotal','swapUsed', 'swapFree', 'swapPercent'
        );
        
        $memTotal = round($buf[1][0]/1024, 2);
        $resmem['memTotal'] =$memTotal < 1024 ? $memTotal.' M' : round($buf[1][0]/1024/1024, 2).' G';
        $memFree = round($buf[2][0]/1024, 2);
        $resmem['memFree'] =$memFree < 1024 ? $memFree.' M' : round($buf[2][0]/1024/1024, 2).' G';
        $memBuffers = round($buffers[1][0]/1024, 2);
        $resmem['memBuffers'] =$memBuffers < 1024 ? $memBuffers.' M' : round($buffers[1][0]/1024/1024, 2).' G';
        $memCached = round($buf[3][0]/1024, 2);
        $resmem['memCached'] =$memCached < 1024 ? $memCached.' M' : round($buf[3][0]/1024/1024, 2).' G';
        $memUsed = round(($buf[1][0] - $buf[2][0])/1024, 2);
        $resmem['memUsed'] =$memUsed < 1024 ? $memUsed.' M' : round(($buf[1][0] - $buf[2][0])/1024/1024, 2).' G';
        $resmem['memPercent'] = (floatval($resmem['memTotal'])!=0)?round($memUsed/$memTotal*100,2):0;
        $memRealUsed = round(($buf[1][0] - $buf[2][0] - $buf[3][0] - $buffers[1][0])/1024, 2);
        $resmem['memRealUsed'] =$memRealUsed < 1024 ? $memRealUsed.' M' : round(($buf[1][0] - $buf[2][0] - $buf[3][0] - $buffers[1][0])/1024/1024, 2).' G';//真实内存使用
        $memRealFree = round(($buf[2][0] + $buf[3][0] + $buffers[1][0])/1024, 2);
        $resmem['memRealFree'] =$memRealFree < 1024 ? $memRealFree.' M' : round(($buf[2][0] + $buf[3][0] + $buffers[1][0])/1024/1024, 2).' G';//真实空闲
        $resmem['memRealPercent'] = (floatval($memTotal)!=0)?round($memRealUsed/$memTotal*100,2):0;//真实内存使用率
        $resmem['memCachedPercent'] = (floatval($memTotal)!=0)?round($memCached/$memTotal*100,2):0;//Cached内存使用率
        $swapTotal = round($buf[4][0]/1024, 2);
        $resmem['swapTotal'] =$swapTotal < 1024 ? $swapTotal.' M' : round($buf[4][0]/1024/1024, 2).' G';
        $swapFree = round($buf[5][0]/1024, 2);
        $resmem['swapFree'] =$swapFree < 1024 ? $swapFree.' M' : round($buf[5][0]/1024/1024, 2).' G';
        $swapUsed = round(($buf[4][0] - $buf[5][0])/1024, 2);
        $resmem['swapUsed'] =$swapUsed < 1024 ? $swapUsed.' M' : round(($buf[4][0] - $buf[5][0])/1024/1024, 2).' G';
        $resmem['swapPercent'] = (floatval($swapTotal)!=0)?round($swapUsed/$swapTotal*100,2):0;
        
        return $resmem;
    }
    
    // public function GetNetWork(){ //网卡流量
    //     $strs= @file("/proc/net/dev");
    //     $lines= count($strs);
    //     for($i=2;$i < $lines;$i++) {
    //         preg_match_all("/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/",$strs[$i],$info );
    //         $res['OutSpeed'][$i] = $info[10][0];
    //         $res['InputSpeed'][$i] = $info[2][0];
    //         $res['NetOut'][$i] = $this->formatsize($info[10][0]);
    //         $res['NetInput'][$i] = $this->formatsize($info[2][0]);
    //         $res['NetWorkName'][$i] = $info[1][0];
    //     }
    //     return $res;
    // }
    
    public function GetNetWorkInfo(){
        $strs= @file("/proc/net/dev");
        $lines= count($strs);
        for($i=2;$i < $lines;$i++) {
            preg_match_all("/([^\s]+):[\s]{0,}(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/",$strs[$i],$info );
            $res['OutTotal'][$i] = $info[10][0];
            $res['InputTotal'][$i] = $info[2][0];
            // $res['NetOut'][$i] = $this->formatsize($info[10][0]);
            // $res['NetInput'][$i] = $this->formatsize($info[2][0]);
            $res['NetWorkName'][$i] = $info[1][0];
        }
        return $res;
    }
    
    public function GetNetWork(){ //网卡流量
        $networkStart = $this->GetNetWorkInfo();
        sleep(1);
        $networkEnd = $this->GetNetWorkInfo();
        foreach($networkEnd['OutTotal'] as $index => $value)
        {
            $res['OutSpeed'][$index] = $this->ByteFormat($value - $networkStart['OutTotal'][$index]).'/s';
            $res['InputSpeed'][$index] = $this->ByteFormat($networkEnd['InputTotal'][$index] - $networkStart['InputTotal'][$index]).'/s';
            $res['NetOut'][$index] = $this->formatsize($value);
            $res['NetInput'][$index] = $this->formatsize($networkEnd['InputTotal'][$index]);
            $res['NetWorkName'][$index] = $networkEnd['NetWorkName'][$index];
        }
        
        return $res;
    }
    public function getMacAddress()
    {
        $addr = $this->getMacAddress1();
        if (!$addr)
        {
            $addr = $this->getMacAddress2();
        }
        
        return $addr;
    }
    public function getMacAddress1()
    {
        $temp_array = [];
        $mac_addrs = [];
        $files = scandir('/sys/class/net');
        foreach ($files as $f)
        {
            if(in_array($f, ['.', '..']))
            {
                continue;
            }
            
            $adr = '/sys/class/net/'.$f.'/address';
            if (file_exists($adr))
            {
                @exec('cat '.$adr, $return_array);
                foreach ( $return_array as $value )
                {
                    if ( preg_match( "/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i", $value, $temp_array ) )
                    {
                        $mac_addrs[] = strtolower($temp_array[0]);
                    }
                }
            }
        }
        unset($temp_array);
        return $mac_addrs;
    }
    
    public function getMacAddress2()
    {
        @exec("ifconfig -a", $return_array);
        $temp_array = array();
        $mac_addrs = [];
        foreach ( $return_array as $value )
        {
             if ( preg_match( "/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i", $value, $temp_array ) )
             {
                 $mac_addrs[] = strtolower($temp_array[0]);
                 break;
             }
        }
        unset($temp_array);
        return $mac_addrs;
    }
    
    public function ByteFormat($byte = 0)
    {
        if($byte < 1024)
        {
            return $byte.'B';
        }
        $k = round($byte/1024, 2);
        if($k < 1024)
        {
            return $k.'k';
        }
        $M = round($byte/1024/1024, 2);
        if($M < 1024)
        {
            return $M.'M';
        }
        $G = round($byte/1024/1024/1024, 2);
        if($G < 1024)
        {
            return $G.'G';
        }
        $T = round($byte/1024/1024/1024/1024, 2);
        return $T.'T';
    }
    
    public function formatsize($size) {//单位转换
        $danwei=array(' B ',' K ',' M ',' G ',' T ');
        $allsize=array();
        $i=0;
        for($i= 0; $i <5; $i++) {
            if(floor($size/pow(1024,$i))==0){break;}
        }
        for($l= $i-1;$l >=0; $l--) {
            $allsize1[$l]=floor($size/pow(1024,$l));
            $temp = isset($allsize1[$l+1]) ? $allsize1[$l+1] : 0;
            $allsize[$l]=$allsize1[$l]-$temp*1024;
        }
        $len=count($allsize);
        $fsize = '';
        for($j= $len-1;$j >=0; $j--) {
            $fsize=$fsize.$allsize[$j].$danwei[$j];
        }
        return $fsize ? $fsize : 0;
    }
    
    public function phpexts(){ //以编译模块
        $able= get_loaded_extensions();
        $str= '';
        foreach($able as $key => $value) {
            if($key!=0 &&$key%13==0) {
                $str.= '<br />';
            }
            $str.= "$value&nbsp;&nbsp;";
        }
        return $str;
    }
    
    public function show($varName){//检测PHP设置参数
        switch($result= get_cfg_var($varName)){
            case 0:
                return '<font color="red">×</font>';
                break;
            case 1:
                return '<font color="green">√</font>';
                break;
            default:
                return $result;
                break;
        }
    }
    
    public function GetDisFuns(){
        $disFuns=get_cfg_var("disable_functions");
        $str= '';
        if(empty($disFuns)){
            $str= '<font color=red>×</font>';
        }else{
            $disFunsarr=  explode(',',$disFuns);
            foreach($disFunsarr as $key=>$value) {
                if($key!=0 &&$key%8==0) {
                    $str.= '<br />';
                }
                $str.= "$value&nbsp;&nbsp;";
            }
        }
        return $str;
    }
    
    public function isfun($funName='',$j=0){// 检测函数支持
        if(!$funName || trim($funName) =='' || preg_match('~[^a-z0-9\_]+~i',$funName, $tmp)) return '错误';
        if(!$j){
            return (function_exists($funName) !== false) ?'<font color="green">√</font>' : '<font color="red">×</font>';
        }else{
            return (function_exists($funName) !== false) ?'√' : '×';
        }
    }
    
    public function GetGDVer(){
        $strgd= '<font color="red">×</font>';
        if(function_exists(gd_info)) {
            $gd_info= @gd_info();
            $strgd= $gd_info["GD Version"];
        }
        return $strgd;
    }
    
    public function GetZendInfo(){
        $zendInfo= array();
        $zendInfo['ver'] = zend_version()?zend_version():'<font color=red>×</font>';
        $phpv= substr(PHP_VERSION,2,1);
        $zendInfo['loader'] =$phpv>2?'ZendGuardLoader[启用]':'Zend Optimizer';
        if($phpv>2){
            $zendInfo['html'] = get_cfg_var("zend_loader.enable")?'<font color=green>√</font>':'<font color=red>×</font>';
        }elseif(function_exists('zend_optimizer_version')){
            $zendInfo['html'] = zend_optimizer_version();
        }else{
            $zendInfo['html']= (get_cfg_var("zend_optimizer.optimization_level") ||
                get_cfg_var("zend_extension_manager.optimizer_ts") ||
                get_cfg_var("zend.ze1_compatibility_mode") ||
                get_cfg_var("zend_extension_ts"))?'<font color=green>√</font>':'<font color=red>×</font>';
        }
        
        return $zendInfo;
    }
    
    public function GetIconcube(){
        $str= '<font color=red>×</font>';
        if(extension_loaded('ionCube Loader')){
            $ys= ionCube_Loader_version();
            $gm= '.'.(int)substr($ys, 3, 2);
            $str= $ys.$gm;
        }
        return $str;
    }
    
    public function CHKModule($cName){
        if(empty($cName)) return '错误';
        $str= phpversion($cName);
        return empty($str)?'<font color=red>×</font>':$str;
    }
    
    public function GetDBVer($dbname){
        if(empty($dbname)) return '错误';
        switch($dbname) {
            case 'mysql':
                if(function_exists("mysql_get_server_info")){
                    $s= @mysql_get_server_info();
                    $s= $s ? '&nbsp; mysql_server 版本：'.$s:'';
                    $c= @mysql_get_client_info();
                    $c= $c ? '&nbsp; mysql_client 版本：'.$c:'';
                    return $s.$c;
                }
                return '';
                break;
            case 'sqlite':
                if(extension_loaded('sqlite3')){
                    $sqliteVer= SQLite3::version();
                    $str= '<font color=green>√</font>';
                    $str.= 'SQLite3　Ver'.$sqliteVer['versionString'];
                }else{
                    $str= $this->isfun('sqlite_close');
                    if(strpos($str,'√')!==false){
                        $str.= '&nbsp; 版本：'.sqlite_libversion();
                    }
                }
                return $str;
                break;
                
            default:
                return '';
                break;
        }
    }
    
    public function getInfo()
    {
        $this->init();
        $netNum = count($this->NetWork);
        
        $arr=[
            'totalSpace'=>$this->hd['t'].' G',
            'useSpace'=>$this->hd['u'].' G',
            'freeSpace'=>$this->hd['f'].' G',
            'hdPercent'=>$this->hd['PCT'],
            'barhdPercent'=>$this->hd['PCT'].'%',
            'TotalMemory'=>$this->sysInfo['memTotal'],
            'UsedMemory'=>$this->sysInfo['memUsed'],
            'FreeMemory'=>$this->sysInfo['memFree'],
            'CachedMemory'=>$this->sysInfo['memCached'],
            'Buffers'=>$this->sysInfo['memBuffers'],
            'TotalSwap'=>$this->sysInfo['swapTotal'],
            'swapUsed'=>$this->sysInfo['swapUsed'],
            'swapFree'=>$this->sysInfo['swapFree'],
            'loadAvg'=>$this->sysInfo['loadAvg'],
            'uptime'=>$this->sysInfo['uptime'],
            'freetime'=>$this->sysInfo['freetime'],
            // 'bjtime'=>"$bjtime",
            'stime'=>$this->S['sTime'],
            'cpuuse'=>$this->CPU_Use,
            'memRealPercent'=>$this->sysInfo['memRealPercent'],
            'memRealUsed'=>$this->sysInfo['memRealUsed'],
            'memRealFree'=>$this->sysInfo['memRealFree'],
            'memPercent'=>$this->sysInfo['memPercent'],
            'barmemPercent'=>$this->sysInfo['memPercent'].'%',
            'memCachedPercent'=>$this->sysInfo['memCachedPercent'],
            'barmemCachedPercent'=>$this->sysInfo['memCachedPercent'].'%',
            'swapPercent'=>$this->sysInfo['swapPercent'],
            'barmemRealPercent'=>$this->sysInfo['memRealPercent'].'%',
            'barswapPercent'=>$this->sysInfo['swapPercent'].'%',
            'NetNum'=>$netNum,
            
            'YourIP' => $this->S['YourIP'],
            'DomainIP' => $this->S['DomainIP'],
            'Flag' => $this->S['Flag'],
            'OS' => $this->S['OS'],
            'Language' => $this->S['Language'],
            'Name' => $this->S['Name'],
            'Email' => $this->S['Email'],
            'WebEngine' => $this->S['WebEngine'],
            'WebPort' => $this->S['WebPort'],
            'WebPath' => $this->S['WebPath'],
            'ProbePath' => $this->S['ProbePath'],
            'cpu' => $this->sysInfo['cpu']['model'],
        ];
        for($i = 2; $i < $netNum + 2; $i ++)
        {
            $arr['NetWorkName'.($i - 2)] = isset($this->NetWork['NetWorkName'][$i]) ? $this->NetWork['NetWorkName'][$i] : '';
            $arr['NetOut'.($i - 2)] = isset($this->NetWork['NetOut'][$i]) ? $this->NetWork['NetOut'][$i] : '';
            $arr['OutSpeed'.($i - 2)] = isset($this->NetWork['OutSpeed'][$i]) ? $this->NetWork['OutSpeed'][$i] : '';
            $arr['NetInput'.($i - 2)] = isset($this->NetWork['NetInput'][$i]) ? $this->NetWork['NetInput'][$i] : '';
            $arr['InputSpeed'.($i - 2)] = isset($this->NetWork['InputSpeed'][$i]) ? $this->NetWork['InputSpeed'][$i] : '';
        }
        // foreach($this->sysInfo['cpu']['model'] as $index => $model)
        // {
        //     $arr['cpumodel'.($index + 1)] = $model;
        // }
        
        return $arr;
    }
}