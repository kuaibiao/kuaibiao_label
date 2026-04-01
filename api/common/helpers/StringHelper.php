<?php
namespace common\helpers;

use yii\helpers\BaseStringHelper;
use Yii;

/**
 * StringHelper provides string functionality that you can use in your
 * application.
 */
class StringHelper extends BaseStringHelper
{
    /**
     * 判断字符串是否是url
     * @param unknown $value
     */
    public static function is_url($value)
    {
        $_logs['$value'] = $value;
    
        if (!is_string($value))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_string fail '.json_encode($_logs));
            return false;
        }
    
        $pattern = '/^(http|https|git|gs|ws|tcp|udp|ftp|soap|telnet|rlogin):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i';
        if (!preg_match($pattern, $value))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' valid fail '.json_encode($_logs));
            return false;
        }
    
        return true;
    }
    
    /**
     * 判断是否相对路径
     *
     * @param string $value
     */
    public static function is_relativepath($value)
    {
        $_logs['$value'] = $value;
    
        if (!is_string($value))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_string fail '.json_encode($_logs));
            return false;
        }
    
        $mimes = FileHelper::get_mimes();
        $extentions = array_keys($mimes);
    
        $pattern = '/(.*\\/)+(.*)\.('.implode('|', $extentions).')$/i';
        if (!preg_match($pattern, $value))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' valid fail '.json_encode($_logs));
            return false;
        }
    
        return true;
    }
    
    /**
     * 判断字符串是否符合/^[\w\-\.]+$/
     * @param string $value
     */
    public static function is_string_normal($value)
    {
        $_logs['$value'] = $value;
    
        if (!is_string($value))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_string fail '.json_encode($_logs));
            return false;
        }
    
        $pattern = '/^[\w\-\.]+$/';
        if (!preg_match($pattern, $value))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' valid fail '.json_encode($_logs));
            return false;
        }
    
        return true;
    }
    
    /**
     * 判断字符串是乱码
     *
     * @param unknown $string
     * @return boolean
     */
    public static function is_messy_code($string)
    {
        return json_encode( $string) === 'null' ? true : false;
    }
    
    /**
     * 判断给定参数是否是json格式
     * @param unknown $json
     * @return boolean
     */
    public static function is_json($json)
    {
        $_logs = ['$json' => $json];
        
        if (!is_string($json))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_string fail '.json_encode($_logs));
            return false;
        }
        
        if (!json_decode($json))
        {
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' json_decode fail '.json_encode($_logs));
            return false;
        }
        
        return true;
    }
    
    /**
     * 判断给定参数是否是xml格式
     * @param unknown $str
     * @return boolean
     */
    public static function is_xml_format($str)
    {
        $xml_parser = xml_parser_create();
        if(!xml_parse($xml_parser,$str,true))
        {
            xml_parser_free($xml_parser);
            return false;
        }else {
            return true;
        }
    }
    
    /**
     * 给定参数是否是正确的域名
     * @description 匹配
     *              t.cn 正确
     *              t-.cn 错误
     *              tt.cn正确
     *              -t.cn 错误
     *              t-t.cn 正确
     *              tst-test-tst.cn 正确
     *              tst--tt.cn -- 错误
     *
     *
     *
     * @param $domain
     *
     * @return bool
     */
    public static function isDomain($domain)
    {
        return !empty($domain) && strpos($domain, '--') === false &&
        preg_match('/^([a-z0-9]+([a-z0-9-]*(?:[a-z0-9]+))?\.)?[a-z0-9]+([a-z0-9-]*(?:[a-z0-9]+))?(\.us|\.tv|\.org\.cn|\.org|\.net\.cn|\.net|\.mobi|\.me|\.la|\.info|\.hk|\.gov\.cn|\.edu|\.com\.cn|\.com|\.co\.jp|\.co|\.cn|\.cc|\.biz)$/i', $domain) ? true : false;
    }
    
    public static function html_encode($content, $doubleEncode = false)
    {
        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, Yii::$app ? Yii::$app->charset : 'UTF-8', $doubleEncode);
    }
    
    public static function html_decode($content)
    {
        return htmlspecialchars_decode($content, ENT_QUOTES);
    }
    
    public static function base64_encode($data)
    {
        return str_replace(array('/','+','='), array('_a','_b','_c'), base64_encode($data));
    }
    
    public static function base64_decode($data)
    {
        return base64_decode(str_replace(array('_a','_b','_c'), array('/','+','='), $data));
    }
    
    /**
     * 转义字符串
     * 用于在sql中使用正则查询
     *
     * @param unknown $str
     */
    public static function regexg_encode($str)
    {
        $words = ['\\', '.', '(', ')', '|', '[', ']',  '*', '+', '?', '{', '}', '^', '$', '-', '/'];
        foreach ($words as $w)
        {
            if (strpos($str, $w) !== false)
            {
                $str = str_replace($w, '\\'.$w, $str);
            }
        }
    
        return $str;
    }
    
    /**
     * 对字符串追加后缀
     *
     * @param string $str
     * @param string $suffix
     * @param string $flag
     * @return string
     */
    public static function str_add_suffix($str, $suffix, $flag = '_')
    {
        if (strrpos($str, $flag))
        {
            $suffixOld = substr(strrchr($str, $flag), 1);
            if ($suffixOld && is_numeric($suffixOld))
            {
                $str = substr($str, 0, strrpos($str, $flag));
            }
        }
    
        return $str . $flag . $suffix;
    }
    
    //获取手机尾号
    public static function mobileLastNum($mobile)
    {
        if (!$mobile){
            return '';
        }
        return substr($mobile, -4);
    }
    
    public static function hidePhone($mobile){
        if (!$mobile){
            return '';
        }
        return substr_replace($mobile, '****', 3, 4);
    }
    
    public static function emailName($email){
        if (!$email){
            return '';
        }
        
        return strstr($email, '@', true);
    }
    
    public static function hideEmail($email){
        if (!$email){
            return '';
        }
    
        $emailName = strstr($email, '@', true);
        $emailSuffix = substr(strrchr($email, '@'), 0);
    
        $emailNameLen = strlen($emailName);
        if ($emailNameLen > 7){
            // > 7
            $emailName = substr_replace($emailName, '****', 3, 4);
        } else if ($emailNameLen > 1){
            // >1 < 7
            $emailName = substr_replace($emailName, str_pad('*', $emailNameLen - 1, '*'), 1);
        } else {
            // 1
            $emailName = '***';
        }
    
        return $emailName . $emailSuffix;
    }
    
    /**
     * 判断字符串是否电话号码
     * @param string $string
     * @return boolean
     */
    public static function is_mobile($str)
    {
        if(!preg_match('/^1[3456789]{1}\d{9}$/', trim($str)))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode([$str]));
            return false;
        }
        
        return true;
    }

    public static function valid_mobile($str)
    {
        return self::is_mobile($str);
    }

    public static function valid_phone($str)
    {
        if(!preg_match('/^1[345789]{1}\d{9}$/', trim($str)))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode([$str]));
            return false;
        }

        return true;
    }
    
    public static function is_email($str)
    {
        if (function_exists('idn_to_ascii') && $atpos = strpos($str, '@'))
        {
            $str = substr($str, 0, ++$atpos).idn_to_ascii(substr($str, $atpos));
        }
        
        return (bool) filter_var($str, FILTER_VALIDATE_EMAIL);
    }
    
    public static function valid_email($str)
    {
        return self::is_email($str);
    }
    
    public static function get_email_name($email)
    {
        return strstr($email, '@', true);
    }

    /**
     * 数字位数补全
     * $number 数字
     * $digit 补全后的位数
     * $complemented 用于补全的字符
     */
    public static function number_complement($number,$digit,$complemented){
        $number = (string)$number;
        $complemented = (string)$complemented;
        $len = mb_strlen($number);
        $ctLen = $digit - $len;
        if($ctLen){
            for($i = 0;$i < $ctLen;$i++){
                $number = $complemented.$number;
            }
        }
        return $number;
    }
    
    /**
     * 生成随机数
     * @param number $minLength
     * @param number $maxLength
     */
    public static function generate_random($minLength = 10, $maxLength = 10)
    {
        return self::random($minLength, $maxLength);
    }
    
    public static function random($minLength = 10, $maxLength = 10)
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
    
        $letters = 'bcdfghjklmnpqrstvwxyz';
        $vowels = 'aeiou';
        $code = '';
        for ($i = 0; $i < $length; ++$i) {
            if ($i % 2 && mt_rand(0, 10) > 2 || !($i % 2) && mt_rand(0, 10) > 9) {
                $code .= $vowels[mt_rand(0, 4)];
            } else {
                $code .= $letters[mt_rand(0, 20)];
            }
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.json_encode([$length, $code]));
        return $code;
    }
    
    public static function generate_uniqueid()
    {
        return self::uniqueid();
    }
    
    public static function uniqueid()
    {
        return uniqid();
    }
    
    public static function toutf8($var)
    {
        $_logs = ['$var' => serialize($var)];
    
        if (is_array($var) || is_object($var))
        {
            $var_ = array();
            foreach ($var as $key => $item)
            {
                $var_[$key] = self::toutf8($item);
            }
            return $var_;
        }
        elseif (is_numeric($var))
        {
            return $var;
        }
        elseif (is_string($var))
        {
            //简单字符不作处理
            if (self::is_string_normal($var))
            {
                return $var;
            }
            elseif (self::is_url($var))
			{

			}
    
            $encoding = self::get_encoding($var);
            $_logs['$encoding.check'] = $encoding;
            
            if (!in_array($encoding, ['ASCII', 'UTF-8']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' check fail 0 '.serialize($_logs));
                $var_ = @iconv($encoding, 'UTF-8', $var);
                
                $encoding = self::get_encoding($var_);
                $_logs['$encoding.check0'] = $encoding;
                if (!in_array($encoding, ['ASCII', 'UTF-8']) || $var_ === false)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' check fail 1 '.serialize($_logs));
                    
                    //指定编码列表
                    $var_ = @mb_convert_encoding($var, 'UTF-8', $encoding);
                    
                    $encoding = self::get_encoding($var_);
                    $_logs['$encoding.check1'] = $encoding;
                    if (!in_array($encoding, ['ASCII', 'UTF-8', 'CP936']))//$encoding 注释:此处为cp936直接返回前端却可以正常显示.
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' check fail 2 '.serialize($_logs));
                        
                        //使用内部编码列表
                        $var_ = @mb_convert_encoding($var, 'UTF-8');
                        
                        $encoding = self::get_encoding($var_);
                        $_logs['$encoding.check2'] = $encoding;
                        if (!in_array($encoding, ['ASCII', 'UTF-8', 'CP936']))
                        {
                            $var = $var_;
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' check fail 3 '.serialize($_logs));
                        }
                        else
                        {
                            $var = $var_;
                        }
                    }
                    else
                    {
                        $var = $var_;
                    }
                }
                else 
                {
                    $var = $var_;
                }
            }
            $_logs['$var.new'] = serialize($var);
    
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.serialize($_logs));
            return $var;
        }
        else
        {
            return $var;
        }
    }
    
    public static function togbk($var)
    {
        if (is_array($var) || is_object($var))
        {
            $var_ = array();
            foreach ($var as $key => $item)
            {
                $var_[$key] = self::togbk($item);
            }
            return $var_;
        }
        else
        {
            $encode = mb_detect_encoding($var, array('ASCII','UTF-8','ASCII','GBK','GB2312','CP936'));
            if ($encode != 'GBK')
            {
                //log_message('error', __CLASS__.' '.__FUNCTION__.' mb_convert_encoding '.json_encode(array($var)));
                $var = mb_convert_encoding($var, "GBK", $encode);
                //log_message('error', __CLASS__.' '.__FUNCTION__.' mb_convert_encoding '.json_encode(array($var)));
            }
    
            return $var;
        }
    }
    
    /**
     * 检测字符编码
     * @param string $file 文件路径
     * @return string|null 返回 编码名 或 null
     * 
     * //"$encoding":"ASCII","$encoding1":"GBK"
        //"$encoding":"EUC-CN","$encoding1":"GBK"
        //"$encoding":"CP936","$encoding1":"GBK"
        //"$encoding":CP936","$encoding1":"CP936"
     */
    public static function get_encoding($str)
    {
        $encodings = self::encodings();
        
        $encoding = mb_detect_encoding($str, $encodings, true);
        $_logs['$encoding'] = $encoding;
        
        if (!mb_check_encoding($str, $encoding))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' check fail '.serialize($_logs));
        }
        
        return $encoding;
    }
    
    public static function encodings()
    {
        //'CP936', 
        return array('ASCII', 'UTF-8', 'Unicode', 'GB2312', 'GBK', 'UTF-16LE', 'UTF-16BE', 'UTF-16', 'ISO-8859-1', 'UCS-2','BIG5');
    }
    
    /**
     * 获取指定字符串在目标字符串中最后一次出现的后面的字符串
     * @param string $param
     */
    public static function strrbehind($string, $find_string)
    {
        return substr($string, strrpos($string, $find_string) + strlen($find_string));
    }
    
    public static function substr($str, $start, $length)
    {
        return mb_substr($str, $start, $length, 'utf-8');
    }
    
    // 过滤掉emoji表情
    /**
     * 
     * unicode定义的emoji是四个字符，根据这个原理进行过滤
     * 
     * @param unknown $str
     */
    public static function filter_emoji($str)
    {
        $str = preg_replace_callback(    //执行一个正则表达式搜索并且使用一个回调进行替换
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
    
        return $str;
    }
    
    public static function strToArray($var)
    {
        return FormatHelper::param_int_to_array($var);
    }
}