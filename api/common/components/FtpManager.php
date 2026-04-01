<?php
/**
 * ftp用户管理
*
*/

namespace common\components;

use Yii;
use yii\base\Component;
use common\models\UserFtp;
use common\models\Setting;

class FtpManager extends Component
{
    public static function open($userId)
    {
        $_logs = ['$userId' => $userId];
        
        $host = self::getHost($userId);
        $homeWeb = self::getHomeAtWeb($userId);
        $homeFtp = self::getHomeAtFtp($userId);
        $username = self::getUsername($userId);
        $password = self::generatePasswd();
        
        $_logs['$homeWeb'] = $homeWeb;
        $_logs['$homeFtp'] = $homeFtp;
        $_logs['$username'] = $username;
        $_logs['$password'] = $password;
        
        if (!$host || !$homeWeb || !$homeFtp || !$username || !$password)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' params error '.json_encode($_logs));
            return false;
        }
        
        //创建用户根目录
        $result = self::createUserHome($homeWeb);
        if (!$result)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' createUserHome error '.json_encode($_logs));
            return false;
        }
        
        //创建用户配置文件
        $result = self::createUserConfigFile($username, $homeFtp);
        if (!$result)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' createUserConfigFile error '.json_encode($_logs));
            return false;
        }
        
        //开通用户账号
        $result = self::createUserRecord($userId, $host, $username, $password, $homeFtp);
        if (!$result)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' createUserConfigFile error '.json_encode($_logs));
            return false;
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    public static function close($userId)
    {
    
    }
    
    public static function check($userId)
    {
        $_logs = ['$userId' => $userId];
        
        $userFtp = UserFtp::find()->where(['user_id' => $userId])->asArray()->limit(1)->one();
        
        $host = self::getHost($userId);
        $homeWeb = self::getHomeAtWeb($userId);
        $homeFtp = self::getHomeAtFtp($userId);
        $username = $userFtp && !empty($userFtp['ftp_username']) ? $userFtp['ftp_username'] : self::getUsername($userId);
        $password = $userFtp && !empty($userFtp['ftp_password']) ? $userFtp['ftp_password'] : self::generatePasswd();
        
        $_logs['$homeWeb'] = $homeWeb;
        $_logs['$homeFtp'] = $homeFtp;
        $_logs['$username'] = $username;
        $_logs['$password'] = $password;
        
        if (!$host || !$homeWeb || !$homeFtp || !$username || !$password)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' params error '.json_encode($_logs));
            return false;
        }
        
        //创建用户根目录
        $result = self::createUserHome($homeWeb);
        if (!$result)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' createUserHome error '.json_encode($_logs));
            return false;
        }
        
        //创建用户配置文件
        $result = self::createUserConfigFile($username, $homeFtp);
        if (!$result)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' createUserConfigFile error '.json_encode($_logs));
            return false;
        }
        
        //开通用户账号
        $result = self::createUserRecord($userId, $host, $username, $password, $homeFtp);
        if (!$result)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' createUserConfigFile error '.json_encode($_logs));
            return false;
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    public static function createUserHome($home)
    {
        if (!file_exists($home))
        {
            //创建用户目录
            @mkdir($home, 0777, true);
            @chmod($home, 0777);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $home '.json_encode([$home]));
        }
        
        return file_exists($home);
    }
    
    public static function getHost($userId)
    {
        $host = Yii::$app->params['ftp.host'];
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode([$host, $userId]));
        return $host;
    }
    
    public static function getUsername($userId)
    {
        return 'ftpuser'.$userId;
    }
    
    public static function createUserConfigFile($username, $home)
    {
        if (!file_exists(Yii::$app->params['ftp.user_conf']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ftp userconf path notexist '.json_encode([$username, $home]));
            return false;
        }
        
        $contents = [
            'local_root='.$home,
            'write_enable=YES',
            'anon_mkdir_write_enable=YES',
            'anon_upload_enable=YES',
            'anon_other_write_enable=YES',
            'download_enable=YES',
            'cmds_denied='
        ];
        $contents = implode("\n", $contents);//unix文本格式
        
        $file = Yii::$app->params['ftp.user_conf'] . '/'.$username;
        if (!file_exists($file))
        {
            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_notexists create '.json_encode([$file, $username, $home]));
            //写入配置文件
            $fp = fopen($file, 'w');
            fwrite($fp, $contents);
            fclose($fp);
            
            @chmod($file, 0777);
        }
        else
        {
            $contents_old = file_get_contents($file);
            if ($contents_old != $contents)
            {
                //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_notexists create '.json_encode([$file, $username, $home]));
                //覆盖写入配置文件
                $fp = fopen($file, 'w');
                fwrite($fp, $contents);
                fclose($fp);
                
                @chmod($file, 0777);
            }
        }
        
        if (!file_exists($file))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_notexists create '.json_encode([$file, $username, $home]));
            return false;
        }
        
        //更改文件所属
        $cmd = 'sudo /bin/chown root:root ' . $file;
        @exec($cmd, $return, $error);
        if ($error)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' chown root error '.json_encode([$cmd, $return, $error, $file, $username, $home]));
            return false;
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode([$file, $username, $home]));
        return true;
    }
    
    public static function createUserRecord($userId, $host, $username, $password, $home)
    {
        $userFtp = UserFtp::find()->where(['user_id' => $userId])->asArray()->limit(1)->one();
        if (!$userFtp)
        {
            $userFtp = new UserFtp();
            $userFtp->user_id = $userId;
            $userFtp->ftp_host = $host;
            $userFtp->ftp_username = $username;
            $userFtp->ftp_password = $password;
            $userFtp->ftp_home = $home;
            $userFtp->created_at = time();
            $userFtp->save();
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $userFtp create succ '.json_encode([$username, $home, $userId]));
        }
        elseif ($userFtp)
        {
            if (array_diff([$host, $username, $password, $home], [$userFtp['ftp_host'], $userFtp['ftp_username'], $userFtp['ftp_password'], $userFtp['ftp_home']]))
            {
                $attributes = [
                    'ftp_host' => $host,
                    'ftp_username' => $username,
                    'ftp_password' => $password,
                    'ftp_home' => $home
                ];
                UserFtp::updateAll($attributes, ['user_id' => $userId]);
            }
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ ');
        return $userFtp;
    }
    
    public static function resetPasswd($userId)
    {
        $passwd = self::generatePasswd();
        
        $attributes = array(
            'ftp_password' => $passwd,
        );
        return UserFtp::updateAll($attributes, ['user_id' => $userId]);
    }
    
    public static function getHomeAtWeb($userId)
    {
        $homeRoot = Setting::getUploadRootPath();
        return $homeRoot . '/'.$userId;
    }
    
    public static function getHomeAtFtp($userId)
    {
        $homeRoot = Yii::$app->params['ftp.user_home'] . '/' . $userId;
        return $homeRoot;
    }
    
    public static function generatePasswd($minLength = 6, $maxLength = 20)
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
        
        return $code;
    }
    
}