<?php
namespace common\helpers;

use Yii;
use yii\helpers\BaseFileHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * FileHelper provides file functionality that you can use in your
 * application.
 */
class FileHelper extends BaseFileHelper
{
    /**
     * 判断文件是否是图片
     * @param string $file
     */
    public static function is_image($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //根据mime判断
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && strpos($mime, 'image') !== false)
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'image') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_image_by_ext($file)
    {
        $_logs = ['$file' => $file];
        
        if (StringHelper::is_url($file))
        {
            $file = @parse_url($file, PHP_URL_PATH);
        }
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'image') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    /**
     * 判断文件是否是音频
     * @param string $file
     */
    public static function is_audio($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //根据mime判断
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && strpos($mime, 'audio') !== false)
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'audio') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_audio_by_ext($file)
    {
        $_logs = [];
        
        if (StringHelper::is_url($file))
        {
            $file = @parse_url($file, PHP_URL_PATH);
        }
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'audio') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    /**
     * 判断文件是否是视频
     * @param string $file
     */
    public static function is_video($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //根据mime判断
        $mime = self::get_file_mime($file);
    
        if (in_array($mime, $mimes) && strpos($mime, 'video') !== false)
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'video') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_video_by_ext($file)
    {
        $_logs = ['$file' => $file];
        
        if (StringHelper::is_url($file))
        {
            $file = @parse_url($file, PHP_URL_PATH);
        }
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'video') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    /**
     * 判断文件是否是文本
     * @param string $file
     */
    public static function is_text($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //获取mime
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && strpos($mime, 'text') !== false)
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'text') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_text_by_ext($file)
    {
        $_logs = ['$file' => $file];
        
        if (StringHelper::is_url($file))
        {
            $file = @parse_url($file, PHP_URL_PATH);
        }
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'text') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    public static function is_pdf($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //获取mime
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && strpos($mime, 'pdf') !== false)
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'pdf') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_pdf_by_ext($file)
    {
        $_logs = [];
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'pdf') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    /**
     * 判断文件是否是zip压缩包
     * @param string $file
     */
    public static function is_zip($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //根据mime判断
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && (strpos($mime, 'zip') !== false || strpos($mime, 'octet-stream') !== false))
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'zip') !== false || strpos($mime, 'octet-stream') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_zip_by_ext($file)
    {
        $_logs = ['$file' => $file];
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'zip') !== false || strpos($mime, 'octet-stream') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    /**
     * 判断文件是否是xls文件
     * @param string $file
     */
    public static function is_xls($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //根据mime判断
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && (
            strpos($mime, 'xls') !== false || strpos($mime, 'officedocument') !== false ||
            strpos($mime, 'excel') !== false || strpos($mime, 'office') !== false))
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'xls') !== false || strpos($mime, 'officedocument') !== false ||
            strpos($mime, 'excel') !== false || strpos($mime, 'office') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_xls_by_ext($file)
    {
        $_logs = [];
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'xls') !== false || strpos($mime, 'officedocument') !== false ||
            strpos($mime, 'excel') !== false || strpos($mime, 'office') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    
    /**
     * 判断文件是否是csv文件
     * @param string $file
     */
    public static function is_csv($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //根据mime判断
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && (strpos($mime, 'csv') !== false || strpos($mime, 'comma') !== false))
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, 'csv') !== false || strpos($mime, 'comma') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_csv_by_ext($file)
    {
        $_logs = [];
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, 'csv') !== false || strpos($mime, 'comma') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    
    /**
     * 判断文件是否是3d文件
     * @param string $file
     */
    public static function is_3d($file)
    {
        //根据后缀判断
        $extension = self::fileextension($file);
        $mimes = self::get_mimes_by_fileextension($extension);
    
        if (!$mimes)
        {
            return false;
        }
    
        //根据mime判断
        $mime = self::get_file_mime($file);
    
        //mime和后缀mimes匹配
        if (in_array($mime, $mimes) && (strpos($mime, '3d') !== false || strpos($mime, 'comma') !== false))
        {
            return $mime;
        }
    
        //判断第一项mime
        $mime = reset($mimes);
        if (strpos($mime, '3d') !== false || strpos($mime, 'comma') !== false)
        {
            return $mime;
        }
    
        return false;
    }
    
    public static function is_3d_by_ext($file)
    {
        $_logs = ['$file' => $file];
        
        if (StringHelper::is_url($file))
        {
            $file = @parse_url($file, PHP_URL_PATH);
        }
        
        //根据后缀判断
        $mime = self::getMimeTypeByExtension($file);
        
        if (!$mime)
        {
            $mimes = self::get_mimes_by_fileextension($file);
            if (!$mimes)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mimetype not found '.json_encode($_logs));
                return false;
            }
            $mime = reset($mimes);
        }
        
        //mime和后缀mimes匹配
        if (strpos($mime, '3d') !== false || strpos($mime, 'comma') !== false)
        {
            return $mime;
        }
        
        return false;
    }
    
    /**
     * 递归删除文件夹及其下文件和文件夹
     * @param string $path
     * @return boolean
     */
    public static function rmdir($path)
    {
        $_logs = ['$path' => $path];
    
        if (!file_exists($path))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' path notexist '.json_encode($_logs));
            return false;
        }
    
        $op = dir($path);
        while(false !== ($item = $op->read())) {
            if($item == '.' || $item == '..') {
                continue;
            }
            if(is_dir($op->path.'/'.$item)) {
                self::rmdir($op->path.'/'.$item);
            } else {
                unlink($op->path.'/'.$item);
            }
        }
        @rmdir($path);
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rmdir '.json_encode($_logs));
        return true;
    }
    
    /**
     * 删除文件
     * @param string $filepath
     */
    public static function rmfile($filepath)
    {
        @unlink($filepath);
    }
    
    /**
     * 重命名文件或目录
     * @param string $oldname
     * @param string $newname
     */
    public static function rename($oldname, $newname)
    {
        @rename($oldname, $newname);
    }
    
    /**
     *
     * 如果目标文件已存在，将会被覆盖
     *
     * @param string $source 源文件路径
     * @param string $dest 目标路径。如果 dest 是一个 URL，则如果封装协议不支持覆盖已有的文件时拷贝操作会失败
     */
    public static function copy($source, $dest)
    {
        @copy($source, $dest);
    }
    
    /**
     * 将文件复制到目录
     * @param string $source
     * @param string $dir
     * @return boolean
     */
    public static function copyFileToDir($source, $dir)
    {
        $_logs = ['$source' => $source, '$dir' => $dir];
    
        if(!file_exists($dir))
        {
            self::mkdir($dir, 0777);

            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode($_logs));
    
            if (!file_exists($dir))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir error '.json_encode($_logs));
                return false;
            }
        }
    
        $filename = self::filename($source);
        return @copy($source, $dir .'/'. $filename);
    }
    
    /**
     * 复制目录
     * @param string $dirSrc
     * @param string $dirTo
     * @return boolean
     */
    public static function copydir($dirSrc, $dirTo)
    {
        $_logs = ['$dirSrc' => $dirSrc, '$dirTo' => $dirTo];
    
        if(!file_exists($dirSrc))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_notexists '.json_encode($_logs));
            return false;
        }
    
        if(!file_exists($dirTo))
        {
            self::mkdir($dirTo, 0777);

            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode($_logs));
    
            if (!file_exists($dirTo))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir error '.json_encode($_logs));
                return false;
            }
        }
    
        if($handle = opendir($dirSrc))
        {
            while($filename = readdir($handle))
            {
                $_logs['$filename'] = $filename;
    
                if($filename!='.' && $filename!='..')
                {
                    $subsrcfile = $dirSrc . '/' . $filename;
                    $subtofile = $dirTo . '/' . $filename;
                    if(is_dir($subsrcfile))
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' parent '.json_encode($_logs));
                        self::copydir($subsrcfile,$subtofile);//再次递归调用copydir
                    }
                    if(is_file($subsrcfile))
                    {
                        if (!@copy($subsrcfile,$subtofile))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' copy error '.json_encode($_logs));
                            return false;
                        }
                    }
                }
            }
            closedir($handle);
        }
    
        return true;
    }
    
    /**
     * 获取文件扩展名
     * @param string $urlorpath
     */
    public static function get_extension($urlorpath)
    {
        return self::fileextension($urlorpath);
    }
    
    /**
     * 获取文件扩展名
     * @param string $urlorpath
     */
    public static function fileextension($urlorpath)
    {
        return @pathinfo($urlorpath, PATHINFO_EXTENSION);
        //return substr(strrchr($urlorpath, '.'), 1);
    }
    
    /**
     * 获取文件所在路径
     * @param string $urlorpath
     */
    public static function get_filepath($urlorpath)
    {
        return self::filepath($urlorpath);
    }
    
    /**
     * 获取文件所在路径
     * @param string $urlorpath
     */
    public static function filepath($urlorpath)
    {
        return substr($urlorpath, 0, strrpos($urlorpath, '/'));
    }
    
    /**
     * 获取文件名
     * @param string $urlorpath
     */
    public static function get_filename($urlorpath)
    {
        return self::filename($urlorpath);
    }
    
    /**
     * 获取文件名
     * @param string $urlorpath
     */
    public static function filename($urlorpath)
    {
        if (strpos($urlorpath, '/') !== false)
        {
            return substr(strrchr($urlorpath, '/'), 1);
        }
    
        if (strpos($urlorpath, '\\') !== false)
        {
            return substr(strrchr($urlorpath, '\\'), 1);
        }
        return $urlorpath;
    }
    
    /**
     * 获取文件名
     * @param string $urlorpath
     */
    public static function filebasename($urlorpath)
    {
        if (strpos($urlorpath, '/') !== false)
        {
            return substr(strrchr($urlorpath, '/'), 1);
        }
        return $urlorpath;
    }
    
    /**
     * 获取文件所在路径
     * @param string $urlorpath
     * @return string
     */
    public static function filedirname($urlorpath)
    {
        if (strpos($urlorpath, '/') !== false)
        {
            return substr($urlorpath, 0, strrpos($urlorpath, '/'));
        }
        return $urlorpath;
    }
    
    /**
     * 获取文件名（不包含扩展名）
     * @param string $urlorpath
     */
    public static function filefilename($urlorpath)
    {
        $ext = self::fileextension($urlorpath);
        $extLen = strlen($ext);
        if (strpos($urlorpath, '/') !== false)
        {
            $basename = substr(strrchr($urlorpath, '/'), 1);
            return substr($basename, 0, - ($extLen + 1));
        }
        return substr($urlorpath, 0, - ($extLen + 1));
    }
    
    /**
     * 获取文件大小
     * @param string $file
     * @param string $format
     */
    public static function file_size($file, $format = false)
    {
        return self::filesize($file, $format);
    }
    
    /**
     * 获取文件大小
     * @param string $file
     * @param string $format
     */
    public static function filesize($file, $format = false)
    {
        if (!file_exists($file))
        {
            return false;
        }
    
        $size = @filesize($file);
        if ($format)
        {
            return FormatHelper::filesize_format($size, 1);
        }
        return $size;
    }

    /**
     * notes: 获取远程文件大小
     * @param string $url 文件地址
     * return string
     */
    public static function remote_filesize($url, $format = false, $user = "", $pw = "")
    {
        ob_start();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);

        if(!empty($user) && !empty($pw))
        {
            $headers = array('Authorization: Basic ' .  base64_encode("$user:$pw"));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $ok = curl_exec($ch);
        curl_close($ch);
        $head = ob_get_contents();
        ob_end_clean();

        $regex = '/Content-Length:\s([0-9].+?)\s/';
        $count = preg_match($regex, $head, $matches);
        $size  = isset($matches[1]) ? $matches[1] : 0;

        $_logs = ['$url' => $url,'$size' => $size];
        if(!$size){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' size not find '.json_encode($_logs));
        }

        if ($format)
        {
            return FormatHelper::filesize_format($size, 1);
        }
        return $size;
    }
    
    
    /**
     * 判断文件是否存在
     * @param string $path
     */
    public static function file_exists($path)
    {
        return file_exists($path);//file_exists(escapeshellarg($path));
    }
    
    /**
     * 判断url指定的网络文件是否存在
     * @param string $url
     */
    public static function netfile_exists($url)
    {
        $hander = curl_init();
        curl_setopt($hander, CURLOPT_URL, $url);
        curl_setopt($hander, CURLOPT_NOBODY, true); // 不下载
        curl_setopt($hander, CURLOPT_FAILONERROR, 1);
        curl_setopt($hander, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($hander, CURLOPT_TIMEOUT, 3);
    
        $ret = curl_exec($hander);
        $httpCode = curl_getinfo($hander, CURLINFO_HTTP_CODE);
        $httpTime = curl_getinfo($hander, CURLINFO_TOTAL_TIME);
        curl_close($hander);
    
        //仅当返回404时才不存在, 其他状态值都代表有效
        if ($httpCode != 404)
        {
            return true;
        }
    
        return false;
    }
    
    /**
     * 获取url指定的网络文件内容
     * @param string $url
     */
    public static function netfile_getcontent($url)
    {
        $hander = curl_init();
        curl_setopt($hander, CURLOPT_URL, $url);
        curl_setopt($hander, CURLOPT_HEADER, 0);
        curl_setopt($hander, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($hander, CURLOPT_TIMEOUT, 60);
    
        $ret = curl_exec($hander);
        $httpCode = curl_getinfo($hander, CURLINFO_HTTP_CODE);
        $httpTime = curl_getinfo($hander, CURLINFO_TOTAL_TIME);
        curl_close($hander);
    
        if ($httpCode === 200)
        {
            return $ret;
        }
    
        return null;
    }
    
    /**
     * 获取文件内容
     * @param string $url_or_path
     */
    public static function file_getcontent($url_or_path)
    {
        $_logs = ['$url_or_path' => $url_or_path];
    
        //如果是http
        if (StringHelper::is_url($url_or_path))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ishttp '.json_encode($_logs));
    
            if (!self::netfile_exists($url_or_path))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url not exist '.json_encode($_logs));
                return false;
            }
    
            $binary = self::netfile_getcontent($url_or_path);
    
        }
        //如果是本地硬盘相对路径
        elseif (StringHelper::is_relativepath($url_or_path))
        {
            if (!file_exists($url_or_path))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                return false;
            }
    
            $binary = @file_get_contents($url_or_path);
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
            return false;
        }
    
        return $binary;
    }
    
    
    
    /**
     * 获取目录名字
     * @param string $urlorpath
     * @return string
     */
    public static function get_dirname($urlorpath)
    {
        return self::dirname($urlorpath);
    }
    
    /**
     * 获取文件路径
     * @param string $urlorpath
     */
    public static function dirpath($urlorpath)
    {
        return substr($urlorpath, 0, strrpos($urlorpath, '/'));
    }
    
    /**
     * 获取目录名字
     * @param string $urlorpath
     * @return string
     */
    public static function dirname($urlorpath)
    {
        $fullpath = substr($urlorpath, 0, strrpos($urlorpath, '/'));
        $dirname = substr(strrchr($fullpath, '/'), 1);
        return $dirname;
    }
    
    /**
     * 获取目录大小
     * @param string $dir
     */
    public static function dirsize($dir)
    {
        @$dh = opendir($dir);
        $size = 0;
        while ($file = @readdir($dh)) {
            if ($file != "." and $file != "..") {
                $path = $dir."/".$file;
                if (is_dir($path)) {
                    $size += self::dirsize($path);
                }
                elseif (is_file($path)) {
                    $size += filesize($path);
                }
            }
        }
        @closedir($dh);
        return $size;
    }
    
    /**
     * 通过Linux命令获取目录大小
     * @param string $dir
     */
    public static function dirsize_linux($dir)
    {
        $_logs = ['$dir' => $dir];
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        ini_set('memory_limit','5120M');
    
        $_logs['locale'] = exec('locale charmap');
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
    
        //设置环境变量解决中文编码问题
        $locale = 'zh_CN.UTF-8';
        @setlocale(LC_ALL, $locale);
        @putenv('LC_ALL='.$locale);
        //-------------------------
    
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exec0 '.json_encode($_logs));
    
        $cmd = sprintf('du -bs %s', escapeshellarg($dir));
        @exec($cmd, $output, $return_var);
    
        $_logs['$cmd'] = $cmd;
        $_logs['$output'] = $output;
        $_logs['$return_var'] = $return_var;
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exec1 '.json_encode($_logs));
    
        if (!is_array($output))
        {
            $_logs['$output'] = $output;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
            return false;
        }
    
        $line = $output[0];
        preg_match('/(\w+)\s+(.*)/', $line, $matches);
        $_logs['$matches'] = $matches;
        if (!$matches)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $matches '.json_encode($_logs));
            return false;
        }
        list($all, $size, $fpath) = $matches;
        $_logs['$all'] = $all;
        $_logs['$size'] = $size;
        $_logs['$fpath'] = $fpath;
    
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $size;
    }
    
    /**
     * 给目录拼接子目录
     * @param string $dir
     * @param string $childpath
     */
    public static function diraddchildpath($dir, $childpath)
    {
        return sprintf('%s/%s', rtrim($dir, '/'), ltrim($childpath, '/'));
    }
    
    /**
     * 获取文件mime
     * @param string $filename
     */
    public static function get_file_mime($filename)
    {
        $_logs['$filename'] = $filename;
    
        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($filename);
        }
        $_logs['$mime'] = $mime;
    
        if (!$mime)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode($_logs));
            $mimes = self::get_mimes_by_fileextension($filename);
            if ($mimes)
            {
                $mime = reset($mimes);
            }
        }
        elseif ($mime == 'inode/x-empty')
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' empty '.json_encode($_logs));
            return false;
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $mime;
    }
    
    /**
     * 通过扩展名获取文件mime
     * @param string $filename
     */
    public static function get_mimes_by_fileextension($filename)
    {
        $_logs['$filename'] = $filename;
    
        $mimes = self::get_mimes();
    
        if (strpos($filename, '.') !== false)
        {
            $extension = strtolower(substr(strrchr($filename, '.'), 1));
        }
        else
        {
            $extension = strtolower($filename);
        }
        $_logs['$extension'] = $extension;
    
        if (isset($mimes[$extension]))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return is_array($mimes[$extension]) ? $mimes[$extension] : [$mimes[$extension]];
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode($_logs));
        return FALSE;
    }
    
    /**
     * 获取mime类型
     * @return multitype:string multitype:string
     */
    public static function get_mimes()
    {
        $mimes = array(
            'hqx'   =>  array('application/mac-binhex40', 'application/mac-binhex', 'application/x-binhex40', 'application/x-mac-binhex40'),
            'cpt'   =>  'application/mac-compactpro',
            'csv'   =>  array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
            'cvs'   =>  array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
            'bin'   =>  array('3d/cloud-point', 'application/macbinary', 'application/mac-binary', 'application/octet-stream', 'application/x-binary', 'application/x-macbinary'), //3d/cloud-point 匹配的是3d点云
            'dms'   =>  'application/octet-stream',
            'lha'   =>  'application/octet-stream',
            'lzh'   =>  'application/octet-stream',
            'exe'   =>  array('application/octet-stream', 'application/x-msdownload'),
            'class' =>  'application/octet-stream',
            'psd'   =>  array('application/x-photoshop', 'image/vnd.adobe.photoshop'),
            'so'    =>  'application/octet-stream',
            'sea'   =>  'application/octet-stream',
            'dll'   =>  'application/octet-stream',
            'oda'   =>  'application/oda',
            'pdf'   =>  array('application/pdf', 'application/force-download', 'application/x-download', 'binary/octet-stream'),
            'ai'    =>  array('application/pdf', 'application/postscript'),
            'eps'   =>  'application/postscript',
            'ps'    =>  'application/postscript',
            'smi'   =>  'application/smil',
            'smil'  =>  'application/smil',
            'mif'   =>  'application/vnd.mif',
            'xls'   =>  array('application/vnd.ms-excel', 'application/msexcel', 'application/x-msexcel', 'application/x-ms-excel', 'application/x-excel', 'application/x-dos_ms_excel', 'application/xls', 'application/x-xls', 'application/excel', 'application/download', 'application/vnd.ms-office', 'application/msword'),
            'ppt'   =>  array('application/powerpoint', 'application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/msword'),
            'pptx'  =>  array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/x-zip', 'application/zip'),
            'wbxml' =>  'application/wbxml',
            'wmlc'  =>  'application/wmlc',
            'dcr'   =>  'application/x-director',
            'dir'   =>  'application/x-director',
            'dxr'   =>  'application/x-director',
            'dvi'   =>  'application/x-dvi',
            'gtar'  =>  'application/x-gtar',
            'gz'    =>  'application/x-gzip',
            'gzip'  =>  'application/x-gzip',
            'php'   =>  array('application/x-httpd-php', 'application/php', 'application/x-php', 'text/php', 'text/x-php', 'application/x-httpd-php-source'),
            'php4'  =>  'application/x-httpd-php',
            'php3'  =>  'application/x-httpd-php',
            'phtml' =>  'application/x-httpd-php',
            'phps'  =>  'application/x-httpd-php-source',
            'js'    =>  array('application/x-javascript', 'text/plain'),
            'swf'   =>  'application/x-shockwave-flash',
            'sit'   =>  'application/x-stuffit',
            'tar'   =>  'application/x-tar',
            'tgz'   =>  array('application/x-tar', 'application/x-gzip-compressed'),
            'z' =>  'application/x-compress',
            'xhtml' =>  'application/xhtml+xml',
            'xht'   =>  'application/xhtml+xml',
            'zip'   =>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed', 'application/s-compressed', 'multipart/x-zip'),
            'rar'   =>  array('application/x-rar', 'application/rar', 'application/x-rar-compressed'),
            'mid'   =>  'audio/midi',
            'midi'  =>  'audio/midi',
            'mpga'  =>  'audio/mpeg',
            'mp2'   =>  'audio/mpeg',
            'mp3'   =>  array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
            'aif'   =>  array('audio/x-aiff', 'audio/aiff'),
            'aiff'  =>  array('audio/x-aiff', 'audio/aiff'),
            'aifc'  =>  'audio/x-aiff',
            'ram'   =>  'audio/x-pn-realaudio',
            'rm'    =>  'audio/x-pn-realaudio',
            'rpm'   =>  'audio/x-pn-realaudio-plugin',
            'ra'    =>  'audio/x-realaudio',
            'rv'    =>  'video/vnd.rn-realvideo',
            'wav'   =>  array('audio/x-wav', 'audio/wave', 'audio/wav'),
            'bmp'   =>  array('image/bmp', 'image/x-bmp', 'image/x-bitmap', 'image/x-xbitmap', 'image/x-win-bitmap', 'image/x-windows-bmp', 'image/ms-bmp', 'image/x-ms-bmp', 'application/bmp', 'application/x-bmp', 'application/x-win-bitmap'),
            'gif'   =>  'image/gif',
            'jpeg'  =>  array('image/jpeg', 'image/pjpeg'),
            'jpg'   =>  array('image/jpeg', 'image/pjpeg'),
            'jpe'   =>  array('image/jpeg', 'image/pjpeg'),
            'png'   =>  array('image/png',  'image/x-png'),
            'tiff'  =>  'image/tiff',
            'tif'   =>  'image/tiff',
            'css'   =>  array('text/css', 'text/plain'),
            'html'  =>  array('text/html', 'text/plain'),
            'htm'   =>  array('text/html', 'text/plain'),
            'shtml' =>  array('text/html', 'text/plain'),
            'txt'   =>  'text/plain',
            'text'  =>  'text/plain',
            'log'   =>  array('text/plain', 'text/x-log'),
            'rtx'   =>  'text/richtext',
            'rtf'   =>  'text/rtf',
            'xml'   =>  array('application/xml', 'text/xml', 'text/plain'),
            'xsl'   =>  array('application/xml', 'text/xsl', 'text/xml'),
            'mpeg'  =>  'video/mpeg',
            'mpg'   =>  'video/mpeg',
            'mpe'   =>  'video/mpeg',
            'qt'    =>  'video/quicktime',
            'mov'   =>  'video/quicktime',
            'avi'   =>  array('video/x-msvideo', 'video/msvideo', 'video/avi', 'application/x-troff-msvideo'),
            'movie' =>  'video/x-sgi-movie',
            'doc'   =>  array('application/msword', 'application/vnd.ms-office'),
            'docx'  =>  array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'),
            'dot'   =>  array('application/msword', 'application/vnd.ms-office'),
            'dotx'  =>  array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'),
            'xlsx'  =>  array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'application/msword'),
            'word'  =>  array('application/msword', 'application/octet-stream'),
            'xl'    =>  'application/excel',
            'eml'   =>  'message/rfc822',
            'json'  =>  array('application/json', 'text/json'),
            'pem'   =>  array('application/x-x509-user-cert', 'application/x-pem-file', 'application/octet-stream'),
            'p10'   =>  array('application/x-pkcs10', 'application/pkcs10'),
            'p12'   =>  'application/x-pkcs12',
            'p7a'   =>  'application/x-pkcs7-signature',
            'p7c'   =>  array('application/pkcs7-mime', 'application/x-pkcs7-mime'),
            'p7m'   =>  array('application/pkcs7-mime', 'application/x-pkcs7-mime'),
            'p7r'   =>  'application/x-pkcs7-certreqresp',
            'p7s'   =>  'application/pkcs7-signature',
            'crt'   =>  array('application/x-x509-ca-cert', 'application/x-x509-user-cert', 'application/pkix-cert'),
            'crl'   =>  array('application/pkix-crl', 'application/pkcs-crl'),
            'der'   =>  'application/x-x509-ca-cert',
            'kdb'   =>  'application/octet-stream',
            'pgp'   =>  'application/pgp',
            'gpg'   =>  'application/gpg-keys',
            'sst'   =>  'application/octet-stream',
            'csr'   =>  'application/octet-stream',
            'rsa'   =>  'application/x-pkcs7',
            'cer'   =>  array('application/pkix-cert', 'application/x-x509-ca-cert'),
            '3g2'   =>  'video/3gpp2',
            '3gp'   =>  array('video/3gp', 'video/3gpp'),
            'mp4'   =>  'video/mp4',
            'm4a'   =>  'audio/x-m4a',
            'f4v'   =>  'video/mp4',
            'webm'  =>  'video/webm',
            'aac'   =>  'audio/x-acc',
            'm4u'   =>  'application/vnd.mpegurl',
            'm3u'   =>  'text/plain',
            'xspf'  =>  'application/xspf+xml',
            'vlc'   =>  'application/videolan',
            'wmv'   =>  array('video/x-ms-wmv', 'video/x-ms-asf'),
            'au'    =>  'audio/x-au',
            'ac3'   =>  'audio/ac3',
            'flac'  =>  'audio/x-flac',
            'ogg'   =>  'audio/ogg',
            'kmz'   =>  array('application/vnd.google-earth.kmz', 'application/zip', 'application/x-zip'),
            'kml'   =>  array('application/vnd.google-earth.kml+xml', 'application/xml', 'text/xml'),
            'ics'   =>  'text/calendar',
            'ical'  =>  'text/calendar',
            'zsh'   =>  'text/x-scriptzsh',
            '7zip'  =>  array('application/x-compressed', 'application/x-zip-compressed', 'application/zip', 'multipart/x-zip'),
            'cdr'   =>  array('application/cdr', 'application/coreldraw', 'application/x-cdr', 'application/x-coreldraw', 'image/cdr', 'image/x-cdr', 'zz-application/zz-winassoc-cdr'),
            'wma'   =>  array('audio/x-ms-wma', 'video/x-ms-asf'),
            'jar'   =>  array('application/java-archive', 'application/x-java-application', 'application/x-jar', 'application/x-compressed'),
            'svg'   =>  array('image/svg+xml', 'application/xml', 'text/xml'),
            'vcf'   =>  'text/x-vcard',
            'srt'   =>  array('text/srt', 'text/plain'),
            'vtt'   =>  array('text/vtt', 'text/plain'),
            'ico'   =>  array('image/x-icon', 'image/x-ico', 'image/vnd.microsoft.icon'),
            'v3'    =>  'audio/v3',
            'mkv'   =>  'video/mkv',
            'pcd'   =>  ['3d/cloud-point', 'image/x-photo-cd'],//自定义mime, 为了匹配是3d点云文件
        );
    
        return $mimes;
    }
    
    /**
     * 通过Linux命令获取tar包结构
     * @param string $file
     * @param string $prefix
     * @param string $removed
     * @throws \Exception
     * @return multitype:
     */
    public static function get_tar_struct_linux($file, $prefix = '', $removed = ['.', '..', '.svn'])
    {
        if(!file_exists($file)){
            throw new \Exception('文件不存在');
        }
    
        //设置环境变量解决中文编码问题
        $locale = 'zh_CN.UTF-8';
        @setlocale(LC_ALL, $locale);
        @putenv('LC_ALL='.$locale);
    
        $cmd    = sprintf('tar -tvf %s', escapeshellarg($file));
        @exec($cmd, $lines, $code);
        if($code){
            throw new \Exception('shell出错, code:'.$code);
        }
    
        $files  = [];
        foreach($lines as $line){
            if(preg_match('/^([\w-]+)\s+([\w|\/]+)\s+(\d+)\s+([\d-]+)\s+([\d:]+)\s+(.+)/', $line, $matchs)){
                $files[]    = [
                    'name'  => $matchs[6],
                    'size'  => (int)$matchs[3]
                ];
            }
        }
    
        return self::list_file_tree_struct($files, $prefix);
    }
    
    /**
     * 文件列表接结构转化
     * @param   string $list 文件列表 [['name' => 'name.jpg', 'size' => 100]...]
     * @param   string $pathPrefix 路径前缀
     * @return  array [<description>]
     */
    public static function list_file_tree_struct($list, $pathPrefix = ''){
        $struct     = [];
        foreach($list as $file){
            if(!isset($file['name'])){
                continue;
            }
    
            $fileCuts   = array_filter(explode('/', $file['name']));
            $folder     = &$struct;
            $folderPath = $pathPrefix;
            foreach($fileCuts as $key => $cut){
                if(!$cut || $key == count($fileCuts) - 1){
                    continue;
                }
    
                //$cut = StringHelper::toutf8($cut);
                $folderPath     .= $cut.'/';
    
                if(!isset($folder[$cut])){
                    $folder[$cut]   = [
                        'count' => 0,
                        'size_count'  => 0,
                        'size'  => 0,
                        'name'  => $cut,
                        'path'  => $folderPath,
                        //'path'  => StringHelper::base64_encode($folderPath),
                        'children' => []
                    ];
                }
    
                if(isset($file['size']) && $file['size']){
                    $folder[$cut]['count']++;
                    $folder[$cut]['size']   += (int)$file['size'];
                }
    
                $folder     = &$folder[$cut]['children'];
            }
        }
    
        return $struct;
    }
    
    /**
     * 获取文件夹目录结构
     * @param   string $folder 文件夹路径
     * @return  array
     */
    static function get_dir_struct($folder, $prefix = '', $removed = ['.', '..', '.svn'])
    {
        $_logs = ['$folder' => $folder, '$prefix' => $prefix, '$removed' => $removed];
    
        if(!is_dir($folder))
        {
            return [];
        }
    
        $dirname = self::filebasename($folder);
        $_logs['$dirname'] = $dirname;
        
        $tmpPath = $prefix.$dirname.'/';
        $dirStruct = [
            'count' => 0,
            'size' => 0,
            'size_number' => 0,
            'name' => $dirname,
            'path' => base64_encode($tmpPath),
            'children' => [],
        ];
        foreach(scandir($folder) as $file)
        {
            if($removed && in_array($file, $removed))
            {
                continue;
            }
            else if ($removed)
            {
                $isRemoved = false;
                foreach ($removed as $removed_)
                {
                    if (fnmatch($removed_, $file))
                    {
                        $isRemoved = true;
                        break;
                    }
                }
    
                if ($isRemoved)
                {
                    continue;
                }
            }
    
            $file = $folder . '/' . $file;
            if(is_dir($file)){
                $childDirStruct = self::get_dir_struct($file, $tmpPath);
                $dirStruct['children'][] = $childDirStruct;
                $dirStruct['count'] += $childDirStruct['count'];
                $dirStruct['size_number'] += $childDirStruct['size_number'];
                $dirStruct['size'] = FormatHelper::filesize_format($dirStruct['size_number']);
            }else{
                $dirStruct['count']++;
                $dirStruct['size_number'] += filesize($file);
                $dirStruct['size'] = FormatHelper::filesize_format($dirStruct['size_number']);
            }
        }
    
        $_logs['$dirStruct'] = $dirStruct;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $dirStruct;
    }
    
    public static function file_write($file, $contents, $mode = 'w')
    {
        $pathinfo = pathinfo($file);
        if (!file_exists($pathinfo['dirname']))
        {
            //创建用户目录
            self::mkdir($pathinfo['dirname'], 0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode([$pathinfo, $file]));
        }
    
        //写入配置文件
        $fp = fopen($file, $mode);
        if (is_array($contents))
        {
            foreach ($contents as $row)
            {
                fwrite($fp, $row);
            }
        }
        else
        {
            fwrite($fp, $contents);
        }
    
        fclose($fp);
    
        @chmod($file, 0777);
    
        return true;
    }
    
    /**
     * 生成excel/csv文档
     * @param string $file 文件名包含路径
     * @param array $contents 文件内容，一维数组写一行，二维数组写一个sheet，三维数组写多个sheet
     * @param array $mergeCells 合并单元格
     * @param array $sheetName 设置工作表名称
     * @return bool
     */
    public static function file_write_excel($file = '', $content = [], $mergeCells = [], $sheetName = [])
    {
        $_log = [ '$file' => $file, '$content' => json_encode($content)];
        $pathinfo = pathinfo($file);
        if(!in_array($pathinfo['extension'], ['xls', 'xlsx', 'csv']))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' excel filename error '.json_encode($_log));
            return false;
        }
        if(empty($content) && is_array($content))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' excel data formate error '.json_encode($_log));
            return false;
        }
    
        if(!is_dir($pathinfo['dirname']))
        {
            if(!self::mkdir($pathinfo['dirname'], 0755))
            {
                $_log['$pathinfo'] = $pathinfo;
    
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir error '.json_encode($_log));
                return false;
            }
        }
    
        $spreadsheet = new Spreadsheet();
        if(is_array($content[0]) &&  !is_array($content[0][0]))
        {
            $content = [$content];
        }
        else if(!is_array($content[0]))
        {
            $content = [[$content]];
        }

        foreach($content as $sheetIndex => $value)
        {
            if($sheetIndex > 0)
            {
                $workSheet = $spreadsheet->createSheet();
            }
            else
            {
                $workSheet = $spreadsheet->getActiveSheet();
            }
    
            if($sheetName && is_array($sheetName))
            {
                $workSheet->setTitle($sheetName[$sheetIndex]);
            }
    
            $value = array_values($value);
            foreach($value as $i => $row)
            {
                if(is_array($row))
                {
                    $row = array_values($row);
                    foreach ($row as $j => $column)
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' write excel column '.($i+1).' '.json_encode($column));
                        $workSheet->setCellValueByColumnAndRow($j+1, $i+1, $column);
                        $column_ = $workSheet->getCellByColumnAndRow($j+1, $i+1)->getValue();
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' write excel get column '.($i+1).' '.json_encode($column_));
                    }
                }
                else
                {
                    $workSheet->setCellValueByColumnAndRow($i+1, 1, $row);
                }
            }
            
            if(isset($mergeCells[$sheetIndex]) && is_array($mergeCells[$sheetIndex]))//合并单元格
            {
                foreach($mergeCells[$sheetIndex] as $mergeCell)
                {
                    if(count($mergeCell) != 4)
                    {
                        continue;
                    }
    
                    call_user_func_array([$workSheet, 'mergeCellsByColumnAndRow'], $mergeCell);//合并单元格
                    call_user_func_array([$workSheet, 'getStyleByColumnAndRow'], $mergeCell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);//居中
                }
            }
        }
        
        if($pathinfo['extension'] == 'xls')
        {
            $writer = new Xls($spreadsheet);
        }
        else if($pathinfo['extension'] == 'xlsx')
        {
            $writer = new Xlsx($spreadsheet);
        }
        else if($pathinfo['extension'] == 'csv')
        {
            $writer = new Csv($spreadsheet);
        }
        $writer->save($file);
    
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    
        return true;
    }
    
    /**
     * 读excel文件
     * @param string $file
     */
    public static function file_read_excel($file = '')
    {
        $data = [];
    
        \PhpOffice\PhpSpreadsheet\Settings::setLibXmlLoaderOptions(LIBXML_COMPACT | LIBXML_PARSEHUGE);//LIBXML_PARSEHUGE：解除文本节点大小限制；LIBXML_COMPACT：提高应用性能
        $spreadsheet = IOFactory::load($file);
        $workSheets = $spreadsheet->getAllSheets();
        foreach($workSheets as $index => $sheet)
        {
            $maxRow = $sheet->getHighestRow();
            $maxColumn = $sheet->getHighestColumn();
            $maxColumnIndex = Coordinate::columnIndexFromString($maxColumn);//将列字母转为列索引
            for($i = 1; $i <= $maxRow; $i ++)
            {
                for($j = 1; $j <= $maxColumnIndex; $j ++)
                {
                    $cell = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                    
                    // 富文本转换字符串
                    if($cell instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText)
                    {
                        $cell = $cell->__toString();
                    }
                    
                    if(is_string($cell) || is_numeric($cell))
                    {
                        $data[$index][$i - 1][$j - 1] = $cell;
                    }
                    else
                    {
                        $data[$index][$i - 1][$j - 1] = '';
                    }
                }
            }
        }
    
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    
        return $data;
    }
    
    /**
     * 生成excel文档
     * @param $file 文件名包含路径
     * @param $contents 文件内容
     * @return bool
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    //     public static function file_write_excel($file, $contents)
    //     {
    
    //         $_log = [ '$file' => $file, '$contents' => json_encode($contents)];
    //         $objectPHPExcel = new \PHPExcel();
    //         $linenumber = 1;
    //         $words = range('A','Z');
    //         if(isset($contents['headers'])){
    //             $headers = $contents['headers'];
    //             //如果设置了表头，这里要求是二维数组
    //             foreach($headers as $header){
    //                 $k = 0;
    //                 foreach($header as $v){
    // /*                    var_dump($k,$linenumber,$words[$k]);
    //                     echo '<br/>';*/
    //                     $objectPHPExcel->getActiveSheet()->getStyle(($words[$k]).($linenumber))->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    //                     $objectPHPExcel->setActiveSheetIndex()->setCellValue(($words[$k]).($linenumber), $v);
    //                     $k++;
    //                 }
    //                 $linenumber ++;
    //             }
    //            // die;
    //         }
    //         if(isset($contents['body'])){
    //             //主体
    //             $bodys = $contents['body'];
    //             foreach($bodys as $items){
    //                 $k = 0;
    //                 foreach($items as $item){
    //                     $objectPHPExcel->getActiveSheet()->getStyle(($words[$k]).($linenumber))->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
    //                     $objectPHPExcel->getActiveSheet()->setCellValue(($words[$k]).($linenumber) ,$item);
    //                     $k++;
    //                 }
    //                 $linenumber++;
    //             }
    //         }
    //         $filepathinfo = pathinfo($file);
    
    //         if (!is_dir($filepathinfo['dirname']))
        //         {
        //             mkdir($filepathinfo['dirname'], 0755, true);
        //             chmod($filepathinfo['dirname'], 0755);
        //             if (!is_dir($filepathinfo['dirname']))
            //             {
            //                 $_log['$filepathinfo'] = $filepathinfo;
    
            //                 Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir error '.json_encode($_log));
            //                 return false;
            //             }
        //         }
    
    //         $objWriter = \PHPExcel_IOFactory::createWriter($objectPHPExcel, 'Excel2007');
    //         $objWriter->save($file);
    
    //     }
    
    /**
     * 将列表写入csv
     * @param string $filepath
     * @param string $list
     * @return boolean
     */
    public static function file_put_csv_list($filepath, $list)
    {
        $_log = ['$filepath' => $filepath, '$list' => count($list)];
    
        $filepathinfo = pathinfo($filepath);
        if (!is_dir($filepathinfo['dirname']))
        {
            self::mkdir($filepathinfo['dirname'], 0755);

            if (!is_dir($filepathinfo['dirname']))
            {
                $_log['$filepathinfo'] = $filepathinfo;
    
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir error '.json_encode($_log));
                return false;
            }
        }
    
        $fp = fopen($filepath, 'w');
        //Windows下使用BOM来标记文本文件的编码方式
        fwrite($fp,chr(0xEF).chr(0xBB).chr(0xBF));
    
        foreach ($list as $fields)
        {
            fputcsv($fp, $fields);
        }
        fclose($fp);
    }
    
    function file_get_csv()
    {
    
    }
    
    /**
     * 读取文件内容
     * 支持对txt,xls,csv,txt
     *
     * @var string $path 文件路径
     * @var boolean $withEncode : 是否转码
     *
     */
    public static function file_readcontent($path, $withEncode = true)
    {
        $_logs = ['$path' => $path];
    
        if (!file_exists($path))
        {
            var_dump('file_exists error '.$path);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_not_exists '.json_encode($_logs));
            return false;
        }
    
        $fileinfo = pathinfo($path);
        $list = array();
    
        $_logs['$fileinfo'] = $fileinfo;
        $_logs['$list'] = $list;
    
        if (in_array($fileinfo['extension'], array('xls','xlsx')))
        {
            $sheets = self::file_read_excel($path);
            
            //$_logs['$sheets.count'] = count($sheets);
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $sheets '.json_encode($_logs));
    
            //所有栏目
            if ($sheets && is_array($sheets))
            {
                //循环读取数据
                foreach ($sheets as $sheetNum => $sheetData)
                {
                    $_logs['$sheets$sheetNum'] = $sheetNum;
                    $fields = array();
                    foreach ($sheetData as $k => $line)
                    {
                        $_logs['$sheetData$k'] = $k;
                        $_logs['$sheetData$line'] = $line;
    
                        if (empty($line))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line empty '.json_encode($_logs));
                            continue;
                        }
    
                        if (!is_array($line))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line isnot array '.json_encode($_logs));
                            continue;
                        }

                        if ($withEncode)
                        {
                            foreach ($line as $line_k => $line_str)
                            {
                                $line[$line_k] = StringHelper::toutf8($line_str);
                            }
                        }
                        $_logs['$line'] = $line;
    
                        //去除所有都是空值的栏
                        $line_vals = array_values($line);
                        $_logs['$line_vals'] = $line_vals;
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line_val_str empty '.json_encode($_logs));
    
                        $line_str = trim(implode('', $line_vals));
                        $_logs['$line_str'] = $line_str;
    
                        if (empty($line_str))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line_val_str empty '.json_encode($_logs));
                            continue;
                        }
    
                        if (empty($fields) && empty($k))
                        {
                            $isField = true;
                            foreach ($line_vals as $line_val_)
                            {
                                if (!StringHelper::is_string_normal($line_val_))
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' isnot header '.json_encode($_logs));
                                    $isField = false;
                                    break;
                                }
                            }
    
                            if ($isField)
                            {
                                $fields = array_values($line_vals);
                                $_logs['$fields'] = $fields;
                                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set header '.json_encode($_logs));
                                continue;
                            }
                        }
    
                        if ($fields)
                        {
                            if (count($fields) != count($line_vals))
                            {
                                if (count($fields) < count($line_vals))
                                {
                                    $line_vals = array_slice($line_vals, 0, count($fields));
                                }
                                else
                                {
                                    $fields = array_slice($fields, 0, count($line_vals));
                                }
                            }
    
                            $line_vals = array_combine($fields, $line_vals);
                            
                            //过滤掉空键名的情况
                            $line_vals_ = [];
                            if ($line_vals && is_array($line_vals))
                            {
                                foreach ($line_vals as $line_k => $line_v)
                                {
                                    if (!empty($line_k))
                                    {
                                        $line_vals_[$line_k] = $line_v;
                                    }
                                }
                            }
                            $line_vals = $line_vals_;
                        }
    
                        $list[] = $line_vals;
                    }
                }
            }
    
            // Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' xls,xlsx '.json_encode($_logs));
        }
        elseif (in_array($fileinfo['extension'], array('csv')))
        {
            $fields = array();
            $handle = fopen($path,"r");
            if ($handle !== FALSE)
            {
                while (($line = fgetcsv($handle, 1000, ",")) !== FALSE)
                {
                    if (empty($line))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line empty '.json_encode($_logs));
                        continue;
                    }
    
                    if (!is_array($line))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line isnot array '.json_encode($_logs));
                        continue;
                    }

                    //仅针对文件头做处理
                    if(empty($fields)){
                        $line = self::remove_utf8_bom($line);
                    }

                    if ($withEncode)
                    {
                        foreach ($line as $line_k => $line_str)
                        {
                            $line[$line_k] = StringHelper::toutf8($line_str);
                        }
                    }
                    
    
                    //去除所有都是空值的栏
                    $line_vals = array_values($line);
                    $_logs['$line_vals'] = $line_vals;
    
                    $line_str = trim(implode('', $line_vals));
                    $_logs['$line_str'] = $line_str;
    
                    if (empty($line_str))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line_val_str empty '.json_encode($_logs));
                        continue;
                    }
    
                    if (empty($fields) && empty($k))
                    {
                        $isField = true;
                        foreach ($line_vals as $line_val_)
                        {
                            if (!empty($line_val_) && !StringHelper::is_string_normal($line_val_))
                            {
                                $isField = false;
                                break;
                            }
                        }
    
                        if ($isField)
                        {
                            $fields = $line_vals;
                            continue;
                        }
                    }
    
                    if ($fields)
                    {
                        if (count($fields) != count($line_vals))
                        {
                            if (count($fields) < count($line_vals))
                            {
                                $line_vals = array_slice($line_vals, 0, count($fields));
                            }
                            else
                            {
                                $fields = array_slice($fields, 0, count($line_vals));
                            }
                        }
    
                        $line_vals = array_combine($fields, $line_vals);
                    }

                    $list[] = $line_vals;
                }
                fclose($handle);
            }
    
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' csv '.json_encode([$fileinfo, count($list)]));
        }
        elseif (in_array($fileinfo['extension'], array('tsv')))
        {
    
        }
        elseif (in_array($fileinfo['extension'], array('txt')))
        {
            $f = fopen($path, "r");
            $ln= 0;
            while (!feof($f))
            {
                $line = trim(fgets($f));
                $_logs['$line'] = $line;
    
                if (empty($line))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $line empty '.json_encode($_logs));
                    continue;
                }

                if ($withEncode)
                {
                    $line = StringHelper::toutf8($line);
                }
                
    
                $list[] = $line;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' txt '.json_encode($_logs));
            }
            fclose ($f);
    
            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
    
        }
        elseif (in_array($fileinfo['extension'], array('xml')))
        {
//             $f = fopen ($path, "r");
//             $ln= 0;
//             while (! feof ($f))
//             {
//                 $lineStr = fgets($f);
//                 $_logs['$lineStr'] = $lineStr;

//                 //if (substr($str_, 0, 1) == '<' && substr($str_, -1, 1) == '>')
//                 if ($lineStr)
//                 {
//                     $list[] = $lineStr;
//                 }
//                 //$_logs['$lineArr'] = $lineArr;
//                 //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' txt '.json_encode($_logs));
//             }
//             fclose ($f);
    
            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        }
    
        return $list;
    }

    /**
     * notes: 移除utf8_bom头
     */
    public static function remove_utf8_bom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);
        return $text;
    }
    
    /**
     * 获取文件夹下所有文件及文件夹
     *
     * @param string $dir 文件夹路径
     * @param number $level 文件夹深度
     * @return multitype:string
     * array(4) {
     ["0610"]=>
     array(6) {
     ["aaaaa"]=>
     array(1) {
     [0]=>
     string(10) "333333.txt"
     }
     [0]=>
     string(29) "lys1-0610_1m60d_918382747.txt"
     [1]=>
     string(29) "lys1-0610_3m60d_918382744.txt"
     [2]=>
     string(28) "lys1-0610_5m0d_918382774.txt"
     }}
     */
    public static function scan_dir($dir, $childDir = '',$level = 99, $removed = ['.', '..', '.svn'])
    {
        $files = array();
        foreach(scandir($dir . '/'.$childDir) as $file)
        {
            if($removed && in_array($file, $removed))
            {
                continue;
            }
            else if ($removed)
            {
                $isRemoved = false;
                foreach ($removed as $removed_)
                {
                    if (fnmatch($removed_, $file))
                    {
                        $isRemoved = true;
                        break;
                    }
                }
                    
                if ($isRemoved)
                {
                    continue;
                }
            }
                
            if(is_dir($dir . '/'.$childDir.'/'.$file))
            {
                if ($level > 0)
                {
                    $files_ = self::scan_dir($dir . '/'.$childDir, $file, $level - 1);
                    if ($files_)
                    {
                        if ($childDir)
                        {
                            if (isset($files[$childDir]))
                            {
                                $files[$childDir] = array_merge($files[$childDir], $files_);
                            }
                            else
                            {
                                $files[$childDir] = $files_;
                            }
                        }
                        else
                        {
                            $files = array_merge($files, $files_);
                        }
                    }
                }
                else
                {
                    if ($childDir)
                    {
                        $files[$childDir][] = $file;
                    }
                    else
                    {
                        $files[] = $file;
                    }
                }
            } else {
                 
                if ($childDir)
                {
                    $files[$childDir][] = $file;
                }
                else
                {
                    $files[] = $file;
                }
            }
        }
         
        return $files;
    }
    
    /**
     * 创建路径
     * @param string $path
     */
    public static function touchPath($path)
    {
        if (!file_exists($path))
        {
            //创建用户目录
            self::mkdir($path, 0777);

        }
    
        if (file_exists($path))
        {
            return true;
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir false '.json_encode($path));
            return false;
        }
    }
    
    /**
     * 
     * 
     * -----------------------------------------
     * 以前的方式
     * @mkdir($extractpath, 0777, true);
       @chmod($extractpath, 0777);
     * 结果
     * 创建了32354 755
     * 创建了12526 755
     * 创建了uploadfile 755
     * 创建了3\u5f20\u56fe.zip 777
     * ----------------------------------------
     * 其实我们要的结果是
     * 32354 需要是 777
     * 12526 需要是 777
     * uploadfile 需要是 777
     * 
     * 
     * @param string $path
     * @param string $mode
     */
    public static function mkdir($path, $mode = 0777)
    {
        $pathArr = explode('/',$path);
//        $pathArr = array_filter($pathArr);
        $newPath = '';
        if(!empty($pathArr)){
            foreach ($pathArr as $value){
                if($value === ''){
                    continue;
                }
                $newPath .= '/'.$value;
                if(!file_exists($newPath)){
                    @mkdir($newPath,$mode);
                    @chmod($newPath,$mode);
                }
            }
        }

        if (file_exists($path))
        {
            return true;
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir false '.json_encode($path));
            return false;
        }
    }
    
    /**
     * 获取文件夹下的所有文件
     *
     * @param string $dir
     * @param array $removed
     * @param array $includeExtensions
     * @param string $prefix
     * @return array
     */
    public static function dir_get_files($dir, $removed = ['.', '..', '.svn'], $includeExtensions = [], $prefix = '')
    {
        return self::get_dir_files($dir, $removed, $includeExtensions, $prefix);
    }
    
    public static function get_dir_stat($dir, $removed = ['.', '..', '.svn'], $includeExtensions = [], $prefix = '')
    {
        return self::get_dir_files($dir, $removed, $includeExtensions, $prefix);
    }
    
    /**
     * 获取文件夹下的文件列表信息
     * 不包含子文件夹的文件
     * 非递归
     *
     * @param string $dir
     */
    public static function get_dir_files($dir, $removed = ['.', '..', '.svn'], $includeExtensions = [], $prefix = '')
    {
        $_logs = ['$dir' => $dir, '$removed' => $removed, '$includeExtensions' => $includeExtensions, '$prefix' => $prefix];
    
        if (!file_exists($dir))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file not exist '.json_encode($dir));
            return null;
        }
    
        $files = scandir($dir);
        $list = [];
    
        if ($files)
        {
            $notAllowExts = [];
            foreach ($files as $child)
            {
                if($removed && in_array($child, $removed))
                {
                    continue;
                }
                else if ($removed)
                {
                    $isRemoved = false;
                    foreach ($removed as $removed_)
                    {
                        if (fnmatch($removed_, $child))
                        {
                            $isRemoved = true;
                            break;
                        }
                    }
    
                    if ($isRemoved)
                    {
                        continue;
                    }
                }
    
                //$child = StringHelper::toutf8($child);
    
                if (is_dir($dir . '/'.$child))
                {
                    $filesize = '-';//self::dirsize_linux($dir . '/'.$child);
                }
                else
                {
                    //校验是否是有效文件
                    if ($includeExtensions)
                    {
                        $ext = substr(strrchr($child, '.'), 1);
                        if (!in_array(strtolower($ext), $includeExtensions))
                        {
                            $notAllowExts[] = $ext;
                            continue;
                        }
                    }
    
                    $filesize = filesize($dir . '/'.$child);
                }
    
                //获取文件信息
                $filectime = filectime($dir . '/'.$child);
    
                $file_info = [
                    'fullpath' => $dir .'/'. $prefix . $child,
                    'path' => $prefix . $child,
                    'dir' => $dir,
                    'name' => $child,
                    'ctime' => $filectime,
                    'size' => $filesize,
                    'size_format' => FormatHelper::filesize_format($filesize, 2),
                    'children' => []
                ];
                $list[] = $file_info;
            }
    
            if ($notAllowExts)
            {
                $_logs['$notAllowExts'] = $notAllowExts;
                //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $notAllowExts '.json_encode($_logs));
            }
    
        }
    
        //根据创建时间排序
        if ($list)
        {
            $list = ArrayHelper::array_sort($list, 'ctime', 'asc');
        }
    
        return $list;
    }
    
    /**
     * 获取文件夹下的文件及其文件夹下的子文件
     *
     * @param string $dir
     * @param string $childDir
     * @param array $removed
     */
    public static function dir_get_all_files($dir, $childDir = '', $removed = ['.', '..'], $includeExtensions = [])
    {
        return self::get_all_files_by_scan_dir($dir, $childDir, $removed, $includeExtensions);
    }
    
    /**
     * 获取文件夹所有的文件
     *
     * @param string $dir
     * @param string $childDir
     * @param array $removed
     */
    public static function get_all_files_by_scan_dir($dir, $childDir = '', $removed = ['.', '..', '.svn'], $includeExtensions = [])
    {
        $files = array();
        $dirpath = $dir . '/'.$childDir;
    
        if(is_dir($dirpath))
        {
            $dirfiles_ = scandir($dirpath);
            foreach($dirfiles_ as $file)
            {
        
                if($removed && in_array($file, $removed))
                {
                    continue;
                }
                else if ($removed)
                {
                    $isRemoved = false;
                    foreach ($removed as $removed_)
                    {
                        if (fnmatch($removed_, $file))
                        {
                            $isRemoved = true;
                            break;
                        }
                    }
        
                    if ($isRemoved)
                    {
                        continue;
                    }
                }
        
                $file_ = $dir . '/'.$childDir.'/'.$file;
                if(is_dir($file_))
                {
                    $files_ = self::get_all_files_by_scan_dir($dir . '/'.$childDir, $file, $removed, $includeExtensions);
                    if ($files_)
                    {
                        foreach ($files_ as $f_)
                        {
                            $files[] = $childDir .'/'. $f_;
                        }
                    }
                } else {
        
                    //$file = StringHelper::toutf8($file);
                    //校验是否是有效文件
                    if ($includeExtensions)
                    {
                        $ext = substr(strrchr($file, '.'), 1);
                        if (!in_array(strtolower($ext), $includeExtensions))
                        {
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file ext notallow '.json_encode([$ext, $includeExtensions]));
                            continue;
                        }
                    }
                     
                    if ($childDir !== '')
                    {
                        $files[] = $childDir .'/'. $file;
                    }
                    else
                    {
                        $files[] = $file;
                    }
                }
            }
        }
        
    
        return $files;
    }
    
    /**
     * 获取子目录结构
     * @param string $dir
     * @param string $childDir
     * @param string $removed
     */
    public static function dir_get_all_childdirs($dir, $childDir = '', $removed = ['.', '..', '.svn'])
    {
        $files = array();
        $dirpath = $dir . '/'.$childDir;
    
        $dirfiles_ = scandir($dirpath);
        foreach($dirfiles_ as $file)
        {
    
            if($removed && in_array($file, $removed))
            {
                continue;
            }
            else if ($removed)
            {
                $isRemoved = false;
                foreach ($removed as $removed_)
                {
                    if (fnmatch($removed_, $file))
                    {
                        $isRemoved = true;
                        break;
                    }
                }
    
                if ($isRemoved)
                {
                    continue;
                }
            }
    
            $file_ = $dir . '/'.$childDir.'/'.$file;
            if(is_dir($file_))
            {
                $files_ = self::dir_get_all_childdirs($dir . '/'.$childDir, $file, $removed);
                if ($files_)
                {
                    foreach ($files_ as $f_)
                    {
                        $files[] = $childDir .'/'. $f_;
                    }
                }
                else
                {
                    $files[] = $childDir .'/'. $file;
                }
            } else {
    
            }
        }
    
        return $files;
    }
    
    /**
     * 转移文件
     *
     * @param string $oldname
     * @param string $newname
     */
    public static function mv_file($oldname, $newname)
    {
        $filepath = self::filepath($newname);
    
        self::touchPath($filepath);
    
        return rename($oldname, $newname);
    }

    public static function sendfile_nginx($file, $rate = 512000)
    {
        $_logs = ['$file' => $file, '$rate' => $rate];
    
        if (!file_exists($file))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_exists false '. json_encode($_logs));
            return false;
        }
    
        header('Content-type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'. basename($file) .'"');
        header('X-Accel-Redirect: '.$file);
        header('X-Accel-Limit-Rate: '.$rate);
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return true;
    }
    
    public static function uploadComplete($dir, $time = 0)
    {
        if(self::dirsize($dir))
        {
            return !@shell_exec('find '.$dir.' -type f -cmin -'.$time);
        }
    
        return false;
    }
    
    /**
     * 判断目录写操作是否完成
     *
     * $var $dir 指定目录
     * $var $time 单位为分钟, 文件夹在此分钟内是否有更改的文件或目录
     * @return boolean
     */
    public static function dir_write_completed($dir, $time = 1)
    {
        $_logs = ['$dir' => $dir, '$time' => $time];
    
        $hasFile = self::dir_get_files($dir);
        $_logs['$hasFile'] = $hasFile;
        if (!$hasFile)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '. json_encode($_logs));
            return false;
        }
    
        //查询目录中更新时间<=$time的文件列表
        $cmd = 'find '.$dir.' -type f -cmin -'.$time;
        $_logs['$cmd'] = $cmd;
    
        @exec($cmd, $output, $return_var);
        $_logs['$output'] = $output;
        $_logs['$return_var'] = $return_var;
    
        if (!empty($output))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '. json_encode($_logs));
            return false;
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return true;
    }
    
    /**
     * 查找文件夹下面文件的路径
     * @param string $path 待查找的路径
     * @param string $needle 待查找的目标
     * @return array 查找的目标所在的路径，找不到返回false
     */
    public static function findFilePath($path = '', $needle = '')
    {
        $_logs = ['$path' => $path, '$needle' => $needle];
    
        if(empty($path) || empty($needle))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path or $needle empty '.json_encode($_logs));
            return false;
        }
        if(!is_dir($path))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path is not a directory '.json_encode($_logs));
            return false;
        }
    
        $dirs = self::scan_dir($path, '', 0, Yii::$app->params['task_source_ignorefiles']);
        $_logs['$dirs'] = $dirs;
        if(empty($dirs))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dirs is empty '.json_encode($_logs));
            return false;
        }
    
        if(in_array($needle, $dirs))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $needle is found '.json_encode($_logs));
            return [$path];
        }
    
        $needlePath = [];
        foreach($dirs as $dir)
        {
            $dirPath = $path.'/'.$dir;
            $_logs['$dirPath'] = $dirPath;
    
            if(!is_dir($dirPath))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dirPath is not a directory '.json_encode($_logs));
                continue;
            }
    
            if(in_array($dirPath, $needlePath))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dirPath exists '.json_encode($_logs));
                continue;
            }
    
            $destPath = self::findFilePath($dirPath, $needle);
            $_logs['$destPath'] = $destPath;
            if($destPath === false)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $needle is not found in $dir '.json_encode($_logs));
                continue;
            }
    
            $needlePath = array_merge($needlePath, $destPath);
            $_logs['$needlePath'] = $needlePath;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $needlePath '.json_encode($_logs));
        }
    
        // $result = $needlePath;
        if($needlePath)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' find $needle success '.json_encode($_logs));
            return $needlePath;
        }
    
        return false;
    }

    /**
     * 匹配3d的规则：
     * zip包中有3d_url文件夹（必须），和其他文件夹，每个文件夹一列，文件夹的名字作为该列的第一行（表头），
     * 各列之间的关系如下：以3d_url文件夹中文件为准，文件名相同的放在同一行，
     * 如果其他文件夹中有3d_url文件夹下不存在的文件名，则忽略其他文件夹中的这个文件
     * @param string $dataPath
     * @param string $baseUrl
     */
    public static function rule3d($dataPath, $url3d, $replacePrefix = '')
    {
        $_logs = ['$dataPath' => $dataPath, '$url3d' => $url3d, '$replacePrefix' => $replacePrefix];
        if(empty($dataPath) || empty($url3d))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' method param error '.json_encode($_logs));
            return false;
        }
        
        $dirs = FileHelper::scan_dir($dataPath, '', 0, Yii::$app->params['task_source_ignorefiles']); //列出路径下的文件夹
        $_logs['$dirs'] = $dirs;
        if(in_array($url3d, $dirs))
        {
            $groups = [];
            $files = [];
            $list = [];
            
            $files3d = FileHelper::get_all_files_by_scan_dir($dataPath, $url3d, Yii::$app->params['task_source_ignorefiles']);
            foreach($files3d as $index => $file)
            {
                $files3d[$index] = (string)FileHelper::filefilename($file);
            }
            
            foreach($dirs as $dir)
            {
                $dirFiles = FileHelper::get_all_files_by_scan_dir($dataPath, $dir, Yii::$app->params['task_source_ignorefiles']);
                if(empty($dirFiles))
                {
                    $groups[$dir] = [];
                }
                else
                {
                    foreach($dirFiles as $file)
                    {
                        $groups[$dir][$file] = (string)FileHelper::filefilename($file);
                    }
                }
            }
            $_logs['count($groups)'] = count($groups);
            
            rsort($dirs);
            krsort($groups);
            
            $groups = array_values($groups);
            $_logs['count($groups)'] = count($groups);
            foreach($groups as $column => $group)
            {
                foreach($files3d as $row => $file) //保留组中与3d_url文件夹中文件同名文件
                {
                    if(in_array($file, $group))
                    {
                        $fileName = array_search($file, $group, true);
                        $list[$row][$column] = rtrim($dataPath, '/').'/'.ltrim($fileName, '/');
                        if($replacePrefix)
                        {
                            $list[$row][$column] = str_replace($replacePrefix, '', $list[$row][$column]);
                        }
                        unset($group[$fileName]); //兼容basename重复
                    }
                    else
                    {
                        $list[$row][$column] = '';
                    }
                }
            }
            
            $list = array_filter($list, function($var){
                foreach($var as $value)
                {
                    if($value)
                    {
                        return true;
                    }
                }
                return false;
            }); //去除数组空行
            
            // array_unshift($list, $dirs); //加表头
            $_logs['count($list)'] = count($list);
                
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return [$dirs, $list];
        }
    }
}