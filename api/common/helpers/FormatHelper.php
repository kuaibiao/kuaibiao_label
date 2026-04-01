<?php 
namespace common\helpers;

use yii\helpers\BaseFormatConverter;
use Yii;

/**
 * FormatHelper provides additional format functionality that you can use in your
 * application.
 */
class FormatHelper extends BaseFormatConverter
{
    /**
     * 格式化返回值
     *
     * @param int|string|array $data
     * @param string $error
     * @param string $message
     */
    public static function result($data, $error = '', $message = '')
    {
        return array('error' => $error, 'message' => $message, 'data' => $data ? $data : []);
    }
    
    /**
     * 格式化数据
     * 强转类型
     *
     * @param unknown $data
     * @param string $error
     * @param string $message
     * @return string[]|\stdClass[]|var[]
     */
    public static function resultStrongType($data, $error = '', $message = '')
    {
        $data = $data ? self::convertToStrongType($data) : (object)[];
        return array('error' => $error, 'message' => $message, 'data' => $data);
    }
    
    /**
     * 返回接口结果, 支持json/jsonp格式
     *
     * return resultJsonp($data)
     *
     * @param unknown $data
     * @param string $error
     * @param string $message
     * @return string data string
     */
    public static function responseJson($data, $error = '', $message = '')
    {
        $dataArr = array('error' => empty($error) ? '' : $error, 'message' => $message ? $message : '', 'data' => $data);
    
        //?callback=jQuery213007410891919373785_1419918006860&
        //header("Content-type:text/html;charset=utf-8");
        $callback = (string)Yii::$app->request->get('callback', '');
        $dataStr = json_encode($dataArr);
        if ($callback)
        {
            $dataStr = $callback.'('.$dataStr.');';
        }
    
        header('Content-type: application/json');
        echo $dataStr;
        //Yii::$app->end();
    }
    
    /**
     * 数值或数组的值转化为字符串类型
     * 只会返回对象或字符串
     *
     * @param var $data
     */
    public static function convertToStrongType($data)
    {
        if (is_array($data))
        {
            if ($data)
            {
                $dataNew = new \stdClass();
                foreach ($data as $k => $v)
                {
                    if ($k === '')
                    {
                        continue;
                    }
                    $dataNew->$k = self::convertToStrongType($v);
                }
            }
            else
            {
                $dataNew = $data;
            }
        }
        elseif (is_object($data))
        {
            $dataNew = $data;
        }
        else
        {
            $dataNew = (string)$data;
        }
    
        return $dataNew;
    }
    
    public static function convertToStrongType1($data, $key='')
    {
        if (is_array($data))
        {
            if ($data)
            {
                $dataNew = [];
                foreach ($data as $k => $v)
                {
                    if ($k === '')
                    {
                        continue;
                    }
                    $dataNew[$k] = self::convertToStrongType($v, $k);
                }
            }
            else
            {
                $dataNew = $data;
            }
        }
        elseif (is_object($data))
        {
            $dataNew = $data;
        }
        elseif (is_null($data) && $key !== '' && !in_array($key, self::fieldDbText()))
        {
            //with关联的空模型返回为null时不再强转为string,同时避免数据库中text类型字段为null时被误处理
            //20191203！！！后期会把数据库中text类型的字段统一设置默认值'',并对老数据进行处理，这样就不需要在此进行过滤数据库text字段验证
            //            $dataNew = new \stdClass();
            $dataNew = null;
        }
        elseif(is_bool($data))
        {
            $dataNew = $data;
        }
        else
        {
            $dataNew = (string)$data;
        }
        
        return $dataNew;
    }
    
    /**
     * notes: 强制转化为objec对象
     */
    public static function toObject($params){
        return (object)$params;
    }
    
    public static function toObject1($params)
    {
        $dataNew = new \stdClass();
        if (is_array($params))
        {
            foreach ($params as $k => $v)
            {
                $dataNew->$k = $v;
            }
        }
        return $dataNew;
    }
    
    public static function toString($params){
        if(is_array($params) || is_object($params)){
            $result = [];
            foreach ($params as $key=>$val){
                $result[$key] = (string)$val;
            }
        }else{
            $result = (string)$params;
        }
        return $result;
    }
    
    public static function toInt($params){
        if(is_array($params) || is_object($params)){
            $result = [];
            foreach ($params as $key=>$val){
                $result[$key] = (int)$val;
            }
        }else{
            $result = (int)$params;
        }
        return $result;
    }
    
    public static function toArray($params){
        if(is_array($params) || is_object($params)){
            $result = (array)$params;
        }else{
            $result = [$params];
        }
        return $result;
    }
    
    /**
     * 格式化文件大小
     * @param unknown $bytes
     * @param number $prec
     * @return string
     */
    public static function filesize_format($bytes,$prec=2){
        $rank=0;
        $size=(int)$bytes;
        $unit="B";
        while($size>1024){
            $size=$size/1024;
            $rank++;
        }
        $size=round($size,$prec);
        switch ($rank){
            case "1":
                $unit="KB";
                break;
            case "2":
                $unit="MB";
                break;
            case "3":
                $unit="GB";
                break;
            case "4":
                $unit="TB";
                break;
            default :
    
        }
        return $size." ".$unit;
    }
    
    /**
     * 将文件大小转为MB格式
     * @param unknown $bytes
     * @param number $prec
     * @param string $unit
     */
    public static function filesize_format_mb($bytes,$prec=3, $unit = false){
        $size = $bytes/1024/1024;
        $size = round($size,$prec);
    
        if ($unit)
        {
            $size.="MB";
        }
    
        return $size;
    }
    
    /**
     * 将文件大小转为int
     * @param unknown $var
     * @return mixed
     */
    public static function file_size_to_int($var)
    {
        if (strpos($var, 'k'))
        {
            $var = str_replace('k', '000', $var);
        }
        elseif (strpos($var, 'K'))
        {
            $var = str_replace('K', '000', $var);
        }
        elseif (strpos($var, 'm'))
        {
            $var = str_replace('m', '000000', $var);
        }
        elseif (strpos($var, 'M'))
        {
            $var = str_replace('M', '000000', $var);
        }
        elseif (strpos($var, 'g'))
        {
            $var = str_replace('g', '000000000', $var);
        }
        elseif (strpos($var, 'G'))
        {
            $var = str_replace('G', '000000000', $var);
        }
        elseif (strpos($var, 't'))
        {
            $var = str_replace('t', '000000000000', $var);
        }
        elseif (strpos($var, 'T'))
        {
            $var = str_replace('T', '000000000000', $var);
        }
        return str_replace(' ', '', $var);
    }
    
    /**
     * 带着微秒的日期转化为带微秒的int
     *
     * 如 04:51:35.643
     *
     * @param unknown $date
     */
    public static function strtomicrotime($date)
    {
        if (preg_match('/([\d:]+)\.(\d+)/', $date, $matches))
        {
            //var_dump($matches);
    
            $time_ = 0;
            if (strpos($matches[1], ':'))
            {
                $time_brfore = explode(':', $matches[1]);
                $time_brfore_ = array_reverse($time_brfore);
                foreach ($time_brfore_ as $k => $v)
                {
                    if ($k == 0)
                    {
                        $time_ += $v;
                    }
                    elseif ($k == 1)
                    {
                        $time_ += $v * 60;
                    }
                    elseif ($k == 2)
                    {
                        $time_ += $v * 60 * 60;
                    }
                }
            }
            //var_dump(strtotime($matches[1]));
            //var_dump($matches[2]);
            $time = $time_ . '.' . $matches[2];
            return $time;
    
        }
        return false;
    }
    
    /**
     * 带微秒的int型时间转化为日期格式
     *
     * @param Timestamp $time
     * @return String Time Elapsed
     * @author Shelley Shyan
     * @copyright http://phparch.cn (Professional PHP Architecture)
     */
    public static function microtimetodate($time)
    {
        $hour   = floor($time / 60 / 60);
        if ($hour)
        {
            $time  -= $hour * 60 * 60;
        }
    
        $minute = floor($time / 60);
        if ($minute)
        {
            $time  -= $minute * 60;
        }
        $second = $time;
    
        $elapse = sprintf('%s:%s:%s', $hour, $minute, $second);
    
        return $elapse;
    }
    
    /**
     * 将日期格式根据以下规律修改为不同显示样式
     * 小于1分钟 则显示多少秒前
     * 小于1小时，显示多少分钟前
     * 一天内，显示多少小时前
     * 3天内，显示前天22:23或昨天:12:23。
     * 超过3天，则显示完整日期。
     * @static
     * @param  $sorce_date 数据源日期 unix时间戳
     * @return void
     */
    public static function timeLongStyle($time)
    {
        if (!is_numeric($time))
        {
            return false;
        }
    
        $nowTime = time();  //获取今天时间戳
        $dur_time = abs($nowTime - $time);
    
        $timeHtml = '';
        if($nowTime > $time)
        {
            if ($dur_time <= 60)
            {
                $timeHtml = $dur_time .'秒前';
            }
            elseif ($dur_time <= 3600)
            {
                $timeHtml = floor($dur_time/60) . '分钟前';
            }
            elseif ($dur_time < 86400)
            {
                $timeHtml = floor($dur_time/3600) . '小时前';
            }
            elseif ($dur_time <= 86400 * 2)
            {
                $temp_time = date('H:i',$time);
                $timeHtml = '昨天'.$temp_time ;
            }
            elseif ($dur_time <= 86400 * 3)
            {
                $temp_time  = date('H:i',$time);
                $timeHtml = '前天'.$temp_time ;
            }
            elseif ($dur_time <= 86400 * 4)
            {
                $timeHtml = '3天前';
            }
            else
            {
                $timeHtml = date('Y-m-d',$time);
            }
        }
        else
        {
            //一分钟后
            if ($dur_time <= 60)
            {
                $timeHtml = $dur_time .'秒后';
            }
            elseif ($dur_time <= 3600)
            {
                $timeHtml = floor($dur_time/60) . '分钟后';
            }
            elseif ($dur_time < 86400)
            {
                $timeHtml = floor($dur_time/3600) . '小时后';
            }
            elseif ($dur_time <= 86400 * 2)
            {
                $temp_time = date('H:i',$time);
                $timeHtml = '明天'.$temp_time ;
            }
            elseif ($dur_time <= 86400 * 3)
            {
                $temp_time  = date('H:i',$time);
                $timeHtml = '后天'.$temp_time ;
            }
            elseif ($dur_time <= 86400 * 4)
            {
                $timeHtml = '3天后';
            }
            else
            {
                $timeHtml = date('Y-m-d',$time);
            }
        }
    
        return $timeHtml;
    }

    public static function money_format($money, $decimal_digit = 3)
    {
        if (function_exists('money_format'))
        {
            return money_format('%.'.$decimal_digit.'n', $money);
        }
        else
        {
            return sprintf('%.'.$decimal_digit.'f', $money);
        }
    }

    
    
    /**
     * 格式化xml文件
     * @param   $xml 文件路径
     * @return  string
     */
    static function format_xml($xml){
        if(!preg_match('/^[\s\S]+?<(\w+)>[\s\S]+$/',$xml, $match)){
            return '';
        }
        $root   = $match[1];
    
        //xml头获取
        $header     = '';
        preg_match('/^(<\?.+\?>)[\s\S]+$/',$xml, $match);
        if($match){
            $header     = $match[1] . "\n";
        }
    
        $xml    =simplexml_load_string($xml);
        $data   = json_decode(json_encode($xml),TRUE);
        $xml    = $header . ArrayHelper::array_to_xml($data, $root);
    
        return $xml;
    }

    static function format_xml_with_filter($xml){
        if(!preg_match('/^[\s\S]+?<(\w+)>[\s\S]+$/',$xml, $match)){
            return '';
        }
        $root   = $match[1];

        //xml头获取
        $header     = '';
        preg_match('/^(<\?.+\?>)[\s\S]+$/',$xml, $match);
        if($match){
            $header     = $match[1] . "\n";
        }

        if (strpos($xml,'&'))
        {
           $xml = str_replace('&', '&amp;',$xml);
        }

        $xml    =simplexml_load_string($xml);

        $data   = json_decode(json_encode($xml),TRUE);
        $xml    = $header . ArrayHelper::array_to_xml($data, $root);

        return $xml;
    }
    
    /**
     * zip包目录结构格式化
     * @param   $array 目录结构
     * @return  $array
     * @author  <王中艺>
     */
    public static function format_zip_struct($data)
    {
        if(isset($data['children'])){
            $children = [];
            foreach($data['children'] as $v){
                $children[] = self::format_zip_struct($v);
            }
            $data['children'] = $children;
        }
    
        $data = [
            'path'  => StringHelper::base64_encode($data['path']),
            'name'  => $data['name'],
            'children'  => $data['children'],
            'count' => $data['count'],
            'size_number' => $data['size'],
            'size' => self::filesize_format($data['size']),
        ];
    
        return $data;
    }
    
    /**
     * int类的参数转化为数组
     * 处理参数接收
     *
     * @param string|int|array $var
     */
    public static function param_int_to_array($var)
    {
        return self::splitToArray($var, ',');
    }
    
    public static function splitToArray($var, $split = ',')
    {
        $vars_ = [];

        if (is_numeric($var))
        {
            if (strpos($var, $split))
            {
                $vars_ = explode($split, $var);
            }
            else
            {
                $vars_ = [$var];
            }
        }
        elseif (is_string($var))
        {

           if (strpos($var, $split))
           {
               $vars_ = explode($split, $var);
           }
           elseif ($var)
           {
               $vars_ = [$var];
           }
        }
        elseif (is_array($var) || is_object($var))
        {
            $vars_ = $var;
        }
        elseif ($var)
        {
            $vars_ = [$var];
        }
        
        return $vars_;
    }
    
}