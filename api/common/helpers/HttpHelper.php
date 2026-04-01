<?php
namespace common\helpers;

use Yii;

/**
 * HttpHelper provides additional http functionality that you can use in your
 * application.
 */
class HttpHelper
{
    public static function request($url,$params=array(),$requestMethod='GET',$headers=array())
    {
        $_logs = ['$url' => $url, '$requestMethod' => $requestMethod, '$headers' => $headers];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_USERAGENT, '1001 Magazine v1');
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE );
        
        $requestMethod = strtoupper($requestMethod);
        switch ($requestMethod) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, TRUE);
                if ($params) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                }
                else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, ''); // Don't know why: if not set,  413 Request Entity Too Large
                }
                
                if (is_array($params))
                {
                    $params_ = [];
                    foreach ($params as $k => $v)
                    {
                        $l = strlen($v);
                        $params_[$k] = $l > 50 ? substr($v, 0, 50) . '###length:'.$l : $v;
                    }
                    $_logs['$params'] = $params;
                }
                else
                {
                    $l = strlen($params);
                    $_logs['$params'] = $l > 50 ? substr($params, 0, 50) . '###length:'.$l : $params;
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($params) {
                    $url = "{$url}?{$params}";
                }
                
                $_logs['$url'] = $url;
                $_logs['$params'] = $params;
                break;
            case 'GET':
                if($params) {
                    // http_build_query($params)
                    foreach ($params as $k => $v)
                    {
                        $url .= (false === strpos($url,'?') ? '?' : '&') . $k.'='.$v;
                    }
                }
                
                $_logs['$url'] = $url;
                $_logs['$params'] = $params;
                break;
            case 'PUT':
                if($params) {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                }
                
                $_logs['$url'] = $url;
                $_logs['$params'] = $params;
                break;
        }
        
        //$headers[] = "APIWWW: " . $_SERVER['REMOTE_ADDR'];
        curl_setopt($ch, CURLOPT_URL, $url );
        
        if ($headers)
        {
            if (is_array($headers))
            {
                foreach ($headers as $k => $v)
                {
                    if (!is_numeric($k))
                    {
                        $headers[$k] = $k.':'.$v;
                    }
                }
                $headers = array_values($headers);
            }
            $_logs['$headers.real'] = $headers;
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $httpHeader = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $curl_errno = curl_errno($ch);
        $curl_error = curl_error($ch);
        curl_close ($ch);
        
        if ($curl_errno > 0)
        {
            $return = array(
                'time' => $httpTime,
                'error' => $curl_errno,
                'data' => $response,
                'message' => $curl_error
            );
        }
        else
        {
            $return = array(
                'time' => $httpTime,
                'error' => 0,
                'data' => $response,
                'message' => ''
            );
        }
        
        //$httpInfo = curl_getinfo($ch);
        $_logs['$curl_errno'] = $curl_errno;
        $_logs['$curl_error'] = $curl_error;
        $_logs['$httpTime'] = $httpTime;
        $_logs['$httpCode'] = $httpCode;
        $_logs['$httpHeader'] = $httpHeader;
        $_logs['$response'] = ArrayHelper::var_desc($response);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $return;
    }
    
    public static function get_user_agent()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
    
        return $agent;
    }
    
    public static function is_phone()
    {
        $clientkeywords = array(
            'nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','phone'
        );
        //从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (isset($_SERVER['HTTP_USER_AGENT']) &&
            preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
        {
            return true;
        }
        return false;
    }
    
    /**
     * 获取客户端浏览器信息 添加win10 edge浏览器判断
     * @param  null
     * @author  Jea杨
     * @return string
     */
    public static function get_broswer(){
        $sys = $_SERVER['HTTP_USER_AGENT'];  //获取用户代理字符串
        if (stripos($sys, "Firefox/") > 0) {
            preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
            $exp[0] = "Firefox";
            $exp[1] = $b[1];  //获取火狐浏览器的版本号
        } elseif (stripos($sys, "Maxthon") > 0) {
            preg_match('/Maxthon\/([\d\.]+)/', $sys, $aoyou);
            $exp[0] = "傲游";
            $exp[1] = $aoyou[1];
        } elseif (stripos($sys, "MSIE") > 0) {
            preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
            $exp[0] = "IE";
            $exp[1] = $ie[1];  //获取IE的版本号
        } elseif (stripos($sys, "OPR") > 0) {
            preg_match('/OPR\/([\d\.]+)/', $sys, $opera);
            $exp[0] = "Opera";
            $exp[1] = $opera[1];
        } elseif(stripos($sys, "Edge") > 0) {
            //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
            preg_match('/Edge\/([\d\.]+)/', $sys, $Edge);
            $exp[0] = "Edge";
            $exp[1] = $Edge[1];
        } elseif (stripos($sys, "Chrome") > 0) {
            preg_match('/Chrome\/([\d\.]+)/', $sys, $google);
            $exp[0] = "Chrome";
            $exp[1] = $google[1];  //获取google chrome的版本号
        } elseif(stripos($sys,'rv:')>0 && stripos($sys,'Gecko')>0){
            preg_match('/rv:([\d\.]+)/', $sys, $IE);
            $exp[0] = "IE";
            $exp[1] = $IE[1];
        }else {
            $exp[0] = "未知浏览器";
            $exp[1] = "";
        }
        return $exp[0].'('.$exp[1].')';
    }
    
    /**
     * 获取客户端操作系统信息包括win10
     * @param  null
     * @author  Jea杨
     * @return string
     */
    public static function get_os(){
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $os = false;
    
        if (preg_match('/win/i', $agent) && strpos($agent, '95'))
        {
            $os = 'Windows 95';
        }
        else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90'))
        {
            $os = 'Windows ME';
        }
        else if (preg_match('/win/i', $agent) && preg_match('/98/i', $agent))
        {
            $os = 'Windows 98';
        }
        else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent))
        {
            $os = 'Windows Vista';
        }
        else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent))
        {
            $os = 'Windows 7';
        }
        else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent))
        {
            $os = 'Windows 8';
        }else if(preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent))
        {
            $os = 'Windows 10';#添加win10判断
        }else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent))
        {
            $os = 'Windows XP';
        }
        else if (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent))
        {
            $os = 'Windows 2000';
        }
        else if (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent))
        {
            $os = 'Windows NT';
        }
        else if (preg_match('/win/i', $agent) && preg_match('/32/i', $agent))
        {
            $os = 'Windows 32';
        }
        else if (preg_match('/linux/i', $agent))
        {
            $os = 'Linux';
        }
        else if (preg_match('/unix/i', $agent))
        {
            $os = 'Unix';
        }
        else if (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent))
        {
            $os = 'SunOS';
        }
        else if (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent))
        {
            $os = 'IBM OS/2';
        }
        else if (preg_match('/Mac/i', $agent) && preg_match('/PC/i', $agent))
        {
            $os = 'Macintosh';
        }
        else if (preg_match('/PowerPC/i', $agent))
        {
            $os = 'PowerPC';
        }
        else if (preg_match('/AIX/i', $agent))
        {
            $os = 'AIX';
        }
        else if (preg_match('/HPUX/i', $agent))
        {
            $os = 'HPUX';
        }
        else if (preg_match('/NetBSD/i', $agent))
        {
            $os = 'NetBSD';
        }
        else if (preg_match('/BSD/i', $agent))
        {
            $os = 'BSD';
        }
        else if (preg_match('/OSF1/i', $agent))
        {
            $os = 'OSF1';
        }
        else if (preg_match('/IRIX/i', $agent))
        {
            $os = 'IRIX';
        }
        else if (preg_match('/FreeBSD/i', $agent))
        {
            $os = 'FreeBSD';
        }
        else if (preg_match('/teleport/i', $agent))
        {
            $os = 'teleport';
        }
        else if (preg_match('/flashget/i', $agent))
        {
            $os = 'flashget';
        }
        else if (preg_match('/webzip/i', $agent))
        {
            $os = 'webzip';
        }
        else if (preg_match('/offline/i', $agent))
        {
            $os = 'offline';
        }
        else
        {
            $os = '未知操作系统';
        }
        return $os;
    }
    
    /**
     * 获取客户端操作系统平台
     *
     *
     */
    public static function get_platform()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $platform = '';
    
        if(preg_match('/iPhone/i', $agent) || preg_match('/iPad/i', $agent))
        {
            $platform = 'ios';
        }
        else if(preg_match('/Android/i', $agent))
        {
            $platform = 'adr';
        }
        else
        {
            $platform = 'pc';
        }
        return $platform;
    }
    
    public static function get_ip()
    {
        $onlineip = '';
        
        if(method_exists(Yii::$app->request, 'getUserIP'))
        {
            $onlineip = Yii::$app->request->getUserIP();
        }
//         if(getenv('HTTP_CLIENT_IP')){
//             $onlineip = getenv('HTTP_CLIENT_IP');
//         }
//         elseif(getenv('HTTP_X_FORWARDED_FOR')){
//             $onlineip = getenv('HTTP_X_FORWARDED_FOR');
//         }
//         elseif(getenv('REMOTE_ADDR')){
//             $onlineip = getenv('REMOTE_ADDR');
//         }
//         else{
//             $onlineip = $_SERVER['REMOTE_ADDR'];
//         }
    
        return $onlineip;
    }
    public static function getHeaders()
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            
        } elseif (function_exists('http_get_request_headers')) {
            $headers = http_get_request_headers();
            
        } else {
            foreach ($_SERVER as $name => $value) {
                if (strncmp($name, 'HTTP_', 5) === 0) {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $headers[$name] = $value;
                }
            }
        }
        return $headers;
    }
}