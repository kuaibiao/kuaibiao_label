<?php
namespace common\helpers;

use yii\helpers\BaseJson;

/**
 * JsonHelper provides json functionality that you can use in your
 * application.
 */
class JsonHelper extends BaseJson
{
    public static function is_json($var)
    {
        return StringHelper::is_json($var);
    }
    
    /**
     * 递归解析json
     *
     * @param string $json
     * @param bool $assoc : 默认为 true; 当true时,将返回 array 而非 object
     */
    public static function json_decode_all($json, $assoc = true)
    {
        if (empty($json))
        {
            $ret = $json;
        }
        elseif (is_array($json))
        {
            $ret = [];
            foreach ($json as $k => $v)
            {
                $ret[$k] = self::json_decode_all($v, $assoc);
            }
        }
        elseif (is_object($json))
        {
            if ($assoc)
            {
                $ret = [];
                foreach ($json as $k => $v)
                {
                    $ret[$k] = self::json_decode_all($v, $assoc);
                }
            }
            else
            {
                $ret = new \stdClass();
                foreach ($json as $k => $v)
                {
                    $ret->$k = self::json_decode_all($v, $assoc);
                }
            }
        }
        elseif (self::is_json($json))
        {
            $arr_ = json_decode($json, $assoc);
            if (is_array($arr_))
            {
                $ret = [];
                foreach ($arr_ as $k => $v)
                {
                    $ret[$k] = self::json_decode_all($v, $assoc);
                }
            }
            elseif (is_object($arr_))
            {
                if ($assoc)
                {
                    $ret = [];
                    foreach ($arr_ as $k => $v)
                    {
                        $ret[$k] = self::json_decode_all($v, $assoc);
                    }
                }
                else
                {
                    $ret = new \stdClass();
                    foreach ($arr_ as $k => $v)
                    {
                        $ret->$k = self::json_decode_all($v, $assoc);
                    }
                }
    
            }
            else
            {
                $ret = $json;
            }
        }
        else
        {
            $ret = $json;
        }
    
        return $ret;
    }
    
    public static function json_decode_all1($json)
    {
        $arr = [];
        if ($json && is_array($json))
        {
            foreach ($json as $k => $v)
            {
                $arr[$k] = self::json_decode_all($v);
            }
        }
        elseif ($json && is_string($json))
        {
            if (json_decode($json))
            {
                $arr_ = json_decode($json, true);
                if ($arr_ && is_array($arr_))
                {
                    foreach ($arr_ as $k => $v)
                    {
                        $arr[$k] = self::json_decode_all($v);
                    }
                }
                else
                {
                    $arr = $json;
                }
    
            }
            else
            {
                $arr = $json;
            }
        }
        else
        {
            $arr = $json;
        }
    
        return $arr;
    }
    
    
    /**
     * json转码中文版
     *
     * @param int/string/array $var
     * @param bool $each : 内部使用变量, 外部无需传值
     * @return string
     */
    public static function json_encode_cn($var, $each = false)
    {
        $var_ = array();
        if (is_array($var))
        {
            $var_ = array();
            foreach ($var as $key => $val)
            {
                if (!is_numeric($key))
                {
                    $key = self::json_encode_cn($key, true);
                }
                $var_[$key] = self::json_encode_cn($val, true);
            }
        }
        elseif ($var)
        {
            !mb_check_encoding($var, 'UTF-8') && $var = mb_convert_encoding($var,'UTF-8');
            $var_ = urlencode($var);
        }
    
        //内循环
        if ($each)
        {
            return $var_;
        }
    
        //统一转码
        return urldecode(json_encode($var_));
    }
}