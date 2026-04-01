<?php
namespace common\helpers;

use Yii;

/**
 * NumberHelper provides number functionality that you can use in your
 * application.
 */
class NumberHelper
{
    public static function intToArray($var)
    {
        return FormatHelper::param_int_to_array($var);
    }
    
    
    /**
     * 奇偶进位
     * 四舍六入五成双
     * 982.49 = 982，（4舍）
     * 982.671 = 983，（6入）
     * 983.50 = 984，（5后无有效数字，前面3奇数，舍5进1）
     * 983.51 = 984，（5后面有有效数字，舍5进1）
     * 982.50 = 982，（5后面无有效数字，前面2偶数，舍5不进）
     * 982.501 = 983，（5后面有有效数字，舍5进1）
     */
    public static function parity_carry($number){
        if(is_int($number)){
            return $number;
        }elseif(is_float($number)) {
            $numbers = explode('.',$number);
            $int_part = $numbers[0];
            if(!isset($numbers[1])){
                return $number;
            }
            $decimals_part = $numbers[1];
            $decimals_parts = str_split($decimals_part);
            $decimals_one = $decimals_parts[0];
            //var_dump($decimals_one);
            unset($decimals_parts[0]);
            $decimals_other = array_sum($decimals_parts);
            //var_dump($decimals_other);
            if($decimals_one > 5){
                return $int_part + 1;
            }elseif($decimals_one < 5){
                return $int_part;
            }else{
                $oddeven = $int_part % 2;
                if($decimals_other){
                    return $int_part + 1;
                }elseif($oddeven == 1){
                    return $int_part + 1;
                }else{
                    return $int_part;
                }
            }
        }else{
            return 0;
        }
    }
    
    /**
     * ip转int
     * @param string $ip
     * @return number
     */
    public static function iptoint($ip)
    {
        list($a, $b, $c, $d) = explode('.', $ip);
        return $a *255 *255*255 + $b *255*255+$c*255+$d;
    }
    
    /**
     * 两个时间戳之间的天数
     * @param unknown $time
     * @param string $time1
     */
    public static function timelong_day($time, $time1 = null)
    {
        if (!is_numeric($time))
        {
            return false;
        }
    
        if ($time1 === null)
        {
            $time1 = time();
        }
    
        $timelong = $time1 - $time;
    
        return ceil($timelong / 86400);
    }
    
    /**
     * 返回当前的毫秒时间戳
     * @return number
     */
    public static function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        return $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
    
    public static function generate_random_number($minLength = 4, $maxLength = 10)
    {
        if ($minLength > $maxLength) {
            $maxLength = $minLength;
        }
        if ($minLength < 3) {
            $minLength = 3;
        }
        if ($maxLength > 20) {
            $maxLength = 20;
        }
        $length = mt_rand($minLength, $maxLength);
    
        $letters = '1234579';
        $vowels = '0689';
        $code = '';
        for ($i = 0; $i < $length; ++$i) {
            if ($i % 2 && mt_rand(0, 10) > 2 || !($i % 2) && mt_rand(0, 10) > 9) {
                $code .= $vowels[mt_rand(0, 3)];
            } else {
                $code .= $letters[mt_rand(0, 6)];
            }
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.json_encode([$length, $code]));
        return $code;
    }
}