<?php 

namespace common\helpers;

use yii\helpers\BaseArrayHelper;

/**
 * ArrayHelper provides additional array functionality that you can use in your
 * application.
 *
 * For more details and usage information on ArrayHelper, see the [guide article on array helpers](guide:helper-array).
 */
class ArrayHelper extends BaseArrayHelper
{
    /**
     *
     * 对一个给定的二维数组按照指定的键值进行排序
     * array_sort($arr, 'ctime', 'asc')
     *
     * @param unknown $arr
     * @param unknown $keys
     * @param string $type
     */
    public static function array_sort($arr,$key,$type='asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k=>$v){
            if (!isset($v[$key]))
            {
                $keysvalue[$k] = $k;
            }
            else
            {
                $keysvalue[$k] = $v[$key];
            }
        }
        if($type == 'asc'){
            asort($keysvalue);
        }else{
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k=>$v){
            $new_array[$k] = $arr[$k];
        }
        return array_values($new_array);
    }
    
    /**
     * 数组相减
     * @param   $arr1
     * @param   $arr2
     * @return  $arr
     */
    public static function array_minus($arr1, $arr2){
        $arr    = [];
        foreach($arr1 as $val){
            if(!in_array($val, $arr2)){
                $arr[]  = $val;
            }
        }
    
        return $arr;
    }
    
    /**
     * 扩展array_search, 使支持多维数组
     *
     * @param array $arr
     * @param string $val
     * @return array array(1) { [0]=> string(3) "ret" }
     */
    public static function array_search_all($arr, $val)
    {
        if (is_array($arr))
        {
            $key = array_search($val, $arr);
            if ($key)
            {
                return [$key];
            }
            else
            {
                foreach ($arr as $k => $v)
                {
                    $childvals_ = self::array_search_all($v, $val);
                    if ($childvals_ !== null)
                    {
                        return array_merge([$k], $childvals_);
                    }
                }
            }
        }
    
        return null;
    }

    /**
     * 获取多维数组中的值,
     *
     * @param string|int|array $var
     */
    public static function get_value_in_array($arr)
    {
        if(isset($arr[0]) && is_array($arr[0]))
        {
            return self::get_value_in_array($arr[0]);
        }

        return $arr[0];
    }

    public static function strToArray($var)
    {
        return self::splitToArray($var);
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
    /**
     * 从数组中根据keys查找值
     *
     * @param array $arr
     * @param array $keys
     */
    public static function array_keys_value($arr, $keys)
    {
        if (empty($arr) || empty($keys))
        {
            return null;
        }
    
        if (!is_array($keys))
        {
            $keys = [$keys];
        }
    
        if (is_array($arr) && is_array($keys))
        {
            foreach ($keys as $k)
            {
                if (isset($arr[$k]))
                {
                    $arr = $arr[$k];
                }
                else
                {
                    $arr = null;
                    break;
                }
            }
    
            return $arr;
        }
        else
        {
            return null;
        }
    
    }
    
    
    /**
     * 从数组中根据keys查找值
     *
     * @param array $arr
     * @param array $keys
     */
    public static function array_keys_replace($arr, $keys, $val)
    {
        if (empty($arr) || empty($keys))
        {
            return null;
        }
    
        if (!is_array($keys))
        {
            $keys = [$keys];
        }
    
        if (is_array($arr) && is_array($keys))
        {
            $arr_ = [];
            foreach ($arr as $k => $v)
            {
                if (is_array($v))
                {
                    $v = self::array_keys_replace($v, $keys, $val);
                }
                else
                {
                    if (in_array($k, $keys) && $k == end($keys))
                    {
                        $v = $val;
                    }
                }
    
                $arr_[$k] = $v;
            }
    
            return $arr_;
        }
        else
        {
            return null;
        }
    
    }
    
    /**
     * 把一个数组的所有字段合并到另一个数组, 若存在则不变, 若不存在则置为0
     *
     * @param array $array_new
     * @param array $array_old
     */
    public static function array_merge_fields_int($array_new, $array_old)
    {
        if (empty($array_old) && empty($array_new))
        {
            return [];
        }
    
        foreach ($array_old as $k => $v)
        {
            if (is_array($v))
            {
                $array_new[$k] = self::array_merge_fields_int(isset($array_new[$k]) ? $array_new[$k] : [], $v);
            }
            else if (!isset($array_new[$k]))
            {
                $array_new[$k] = 0;
            }
        }
    
        return $array_new;
    }
    
    /**
     * 计算两个数组的各数值的差值
     *
     * @param array $array_old
     * @param array $array_new
     */
    public static function array_value_diff_int($array_old, $array_new)
    {
        if (empty($array_old) && empty($array_new))
        {
            return [];
        }
    
        $array_new = self::array_merge_fields_int($array_new, $array_old);
    
        $diff_new = [];
        foreach ($array_new as $k => $v)
        {
            if (is_array($v))
            {
                $diff_new[$k] = self::array_value_diff_int(isset($array_old[$k]) ? $array_old[$k] : [], $v);
            }
            elseif (isset($array_old[$k]))
            {
                $diff_new[$k] = (int)$v - $array_old[$k];
            }
            else
            {
                $diff_new[$k] = (int)$v;
            }
        }
    
        return $diff_new;
    }
    
    public static function desc($var, $length = 50, $maxLoop = 2, $_nowLoop = 0)
    {
        return self::var_desc($var, $length, $maxLoop, $_nowLoop);
    }
    
    /**
     * 描述数组的主要信息
     * desc 为 describe 简写
     *
     * @param var $var 字符串/数组/其他
     * @param number $length 截取的长度
     * @param number $maxLoop 数组遍历最大层数
     */
    public static function var_desc($var, $length = 50, $maxLoop = 2, $_nowLoop = 0)
    {
        $var_ = [];
    
        if (is_object($var) || is_array($var))
        {
            foreach ($var as $k => $v)
            {
                if ($_nowLoop < $maxLoop)
                {
                    $var_[$k] = self::var_desc($v, $length, $maxLoop, $_nowLoop+1);
                }
                else
                {
                    if (is_object($v) || is_array($v))
                    {
                        $v = json_encode($v);
                    }
    
                    $l = mb_strlen($v);
                    $var_[$k] = $l > $length ? (mb_substr($v, 0, $length, 'utf-8') . '###object.length:'.$l) : $v;
                }
    
                if ($k > 9)
                {
                    $var_[] = '###other_ignore';
                    break;
                }
            }
        }
        elseif (is_string($var))
        {
            if (json_decode($var))
            {
                $var = json_decode($var, true);
                if (is_object($var) || is_array($var))
                {
                    foreach ($var as $k => $v)
                    {
                        if ($_nowLoop < $maxLoop - 1)
                        {
                            $var_[$k] = self::var_desc($v, $length, $maxLoop, $_nowLoop+1);
                        }
                        else
                        {
                            if (is_object($v) || is_array($v))
                            {
                                $v = json_encode($v);
                            }
    
                            $l = mb_strlen($v);
                            $var_[$k] = $l > $length ? (mb_substr($v, 0, $length, 'utf-8') . '###string.json.length:'.$l) : $v;
                        }
    
                        if ($k > 9)
                        {
                            $var_[] = '###other_ignore';
                            break;
                        }
                    }
                }
                else
                {
                    $l = mb_strlen($var);
                    $var_ = $l > $length ? (mb_substr($var, 0, $length, 'utf-8') . '###string.other.length:'.$l) : $var;
                }
            }
            else
            {
                $l = mb_strlen($var);
                $var_ = $l > $length ? (mb_substr($var, 0, $length, 'utf-8') . '###other.length:'.$l) : $var;
            }
        }
        else
        {
            $var_ = $var;
        }
    
        return $var_;
    }
    
    /**
     * @method 多维数组转字符串
     * @param type $array
     * @return type $srting
     * @author yanhuixian
     */
    public static function arrayToString($arr) {
        if (is_array($arr)){
            return implode('|||', array_map('arrayToString', $arr));
        }
        return $arr;
    }
    
    /**
     * 数组转为xml
     * @param   $data
     * @return  string
     */
    public static function array_to_xml($data, $key, $level = 0){
        $xml    = '';
        $withKey    = true;
        foreach($data as $k => $v){
            if(is_numeric($k) && is_array($v)){
                $withKey    = false;
                $xml    .= self::array_to_xml($v, $key, $level);
            }else{
                if(is_array($v)){
                    $xml    .= self::array_to_xml($v, $k, $level + 1);
                }else{
                    $gap    = str_pad('', ($level+1) * 4, ' ');
                    $xml    .= sprintf("%s<%s>%s</%s>\n", $gap, $k, $v, $k);
                }
            }
        }
    
        $gap    = str_pad('', $level * 4, ' ');
        if($withKey){
            return sprintf("%s<%s>\n%s%s</%s>\n", $gap, $key, $xml, $gap, $key);
        }else{
            return $xml;
        }
    }
    
    public static function arrayToXml($arr, $children = false)
    {
        $xml = '';
        if (!$children)
        {
            $xml .= "<xml>\n";
        }
    
        foreach ($arr as $key=>$val)
        {
            if (is_array($val))
            {
                //子数组的key是否数字自增的
                $childKeyIsNum = false;
                foreach ($val as $key_ => $val_)
                {
                    if (is_numeric($key_))
                    {
                        $childKeyIsNum = true;
                        break;
                    }
                }
    
                //是数字自增的情况
                if ($childKeyIsNum)
                {
                    foreach ($val as $key_ => $val_)
                    {
                        $val__=self::arrayToXml($val_, true);
                        $xml.="<".$key.">\n".$val__."</".$key.">\n";
                    }
                }
                else
                {
                    $val_=self::arrayToXml($val, true);
                    $xml.="<".$key.">\n".$val_."</".$key.">\n";
                }
            }
            elseif (is_numeric($val))
            {
                $xml.="<".$key.">".trim($val)."</".$key.">\n";
            }else{
                $xml.="<".$key.">".trim($val)."</".$key.">\n";
                //$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
    
        if (!$children)
        {
            $xml.="</xml>\n";
        }
    
        return $xml;
    }
    
    /**
     * 将XML转为array
     * @param unknown $xml
     * @return mixed
     */
    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
}