<?php
namespace common\helpers;

use Yii;

/**
 * ZipHelper provides zip functionality that you can use in your
 * application.
 */
class ZipHelper
{
    public static function unzip($zipfile, $extractpath)
    {
        $_logs = ['$extractpath' => $extractpath, '$zipfile' => $zipfile];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        ini_set('memory_limit','5120M');
    
        $zip = new \ZipArchive();
        $openres = $zip->open($zipfile);
        //var_dump($openres);
    
        $_logs['$openres'] = $openres;
        if ($openres === TRUE)
        {
            $zip->extractTo($extractpath);
    
            //             for($i = 0; $i < $zip->numFiles; $i++)
            //             {
            //                 $filename = $zip->getNameIndex($i);
            //                 if(!is_dir($filename)){
            //                     $zip->extractTo($extractpath, $zip->getNameIndex($i));
            //                 }
            //                 else
            //                 {
            //                     @mkdir($extractpath.'/'.$filename, 0777);
            //                 }
    
            //                 // here you can run a custom function for the particular extracted file
            //             }
    
            $zip->close();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode($_logs));
            return false;
        }
    }
    
    public static function unzip_linux($zipfile, $extractpath)
    {
        $_logs = ['$extractpath' => $extractpath, '$zipfile' => $zipfile];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        ini_set('memory_limit','5120M');
    
        //$_logs['locale'] = exec('locale charmap');
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
    
        //设置环境变量解决中文编码问题
//         $locale = 'zh_CN.UTF-8';
//         @setlocale(LC_ALL, $locale);
//         @putenv('LC_ALL='.$locale);
        //-------------------------
        
        //检测是否有-O选项
        $cmd = 'unzip -h';
        @exec($cmd, $output, $return_var);
        
        if (preg_match('/-O/', serialize($output)))
        {
            $cmd = sprintf('unzip -O cp936 -d %s %s', escapeshellarg($extractpath), escapeshellarg($zipfile));
            @exec($cmd, $output, $return_var);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' unzip has -O '.json_encode($_logs));
            
        }
        else
        {
            $cmd = sprintf('unzip -d %s %s', escapeshellarg($extractpath), escapeshellarg($zipfile));
            @exec($cmd, $output, $return_var);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' unzip hasnot -O '.json_encode($_logs));
        }
    
        $_logs['$cmd'] = $cmd;
        $_logs['$return_var'] = $return_var;
    
        if (!is_array($output))
        {
            $_logs['$output'] = $output;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' unzip '.json_encode($_logs));
            return false;
        }
    
        //调整权限
        $cmd = sprintf('chmod -R 0777 %s', escapeshellarg($extractpath));
        @exec($cmd, $output, $return_var);
    
        $_logs['$cmd'] = $cmd;
        $_logs['$return_var'] = $return_var;
    
        if (!is_array($output))
        {
            $_logs['$output'] = $output;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' chmod error '.json_encode($_logs));
            return false;
        }
    
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    /**
     * 压缩文件夹
     * 递归添加文件夹下的文件
     *
     * @param string $dir
     */
    public static function zip($path, $zipfile, &$zip = null, $childpath = '')
    {
        $_logs = [];
    
        if ($zip === null)
        {
            $_logs['$path'] = $path;
            $_logs['$zipfile'] = $zipfile;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
            if (!file_exists($path))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' path notexist '.json_encode($_logs));
                return false;
            }
    
            $zipfilepath = FileHelper::filepath($zipfile);
            $_logs['$zipfilepath'] = $zipfilepath;
            if (!file_exists($zipfilepath))
            {
                FileHelper::mkdir($zipfilepath, 0777);

                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode($_logs));
    
                if (!file_exists($zipfilepath))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir error '.json_encode($_logs));
                    trigger_error("Error : mkdir ".$zipfilepath);
                }
            }
    
            $zip = new \ZipArchive();
            if (!file_exists($zipfile))
            {
                $openres = $zip->open($zipfile, \ZipArchive::CREATE);
            }
            else
            {
                $openres = $zip->open($zipfile, \ZipArchive::OVERWRITE);
            }
            $_logs['$openres'] = $openres;
            if ($openres === TRUE)
            {
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' open '.json_encode($_logs));
                self::zip($path, $zipfile, $zip, $childpath);
                $zip->close();
            }
            else
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' open fail '.json_encode($_logs));
                return false;
            }
        }
        elseif (is_dir($path .'/'. $childpath))
        {
            $_logs['$path'] = $path;
            $_logs['$childpath'] = $childpath;
            if ($childpath)
            {
                $zip->addEmptyDir($childpath);
            }
    
            $handler = opendir($path.'/'.$childpath); //打开当前文件夹由$path指定
            while (($filename = readdir($handler)) !== false)
            {
                $_logs['$filename'] = $filename;
    
                //文件夹文件名字为'.'和'..'，不要对他们进行操作
                if (in_array($filename, ['.', '..']))
                {
                    continue;
                }
    
                self::zip($path, $zipfile, $zip, ($childpath ? trim($childpath, '/'). '/' : ''). $filename);
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' zip isdir '.json_encode($_logs));
            }
            //@closedir($path.'/'.$childpath);
        }
        else
        {
            $_logs['$path'] = $path;
            $_logs['$childpath'] = $childpath;
            $addFileResult = $zip->addFile($path.'/'.$childpath, $childpath);
            $_logs['$addFileResult'] = $addFileResult;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' addFile '.json_encode($_logs));
        }
    
        return true;
    }
    
    public static function zip_linux($zipfile, $dir, $ignore = ['.', '..'], $include = [])
    {
        $_logs = ['$dir' => $dir, '$zipfile' => $zipfile, '$include' => $include];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        ini_set('memory_limit','5120M');
    
        //$_logs['locale'] = exec('locale charmap');
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
    
        //设置环境变量解决中文编码问题
//         $locale = 'zh_CN.UTF-8';
//         setlocale(LC_ALL, $locale);
//         putenv('LC_ALL='.$locale);
        //-------------------------
    
        //zip -r backup.20181019.160203.zip //www/.com/api -x . .. .htaccess -x .. -x vendor*
        $cmd = 'cd '.escapeshellarg($dir).' && ';
        $cmd .= 'zip -rq '.escapeshellarg($zipfile);
        if ($include && is_array($include))
        {
            foreach ($include as $v)
            {
                $cmd .= ' '.escapeshellarg($v).' ';
            }
        }
        else
        {
            $cmd .= ' '.escapeshellarg($dir).' ';
        }
    
        if ($ignore && is_array($ignore))
        {
            foreach ($ignore as $v)
            {
                $cmd .= ' -x '.$v.'* ';
            }
        }
        @exec($cmd, $output, $return_var);
    
        $_logs['$cmd'] = $cmd;
        $_logs['$return_var'] = $return_var;
    
        if (!is_array($output))
        {
            $_logs['$output'] = $output;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' zip error '.json_encode($_logs));
            return false;
        }
    
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    /**
     * 压缩文件, pcl
     *
     * @param string $path
     * @param string $zipfile
     */
    public static function zip_pcl($path, $zipfile)
    {
        $_logs = [];
        $_logs['$path'] = $path;
        $_logs['$zipfile'] = $zipfile;
    
        if (!file_exists($path))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' path notexist '.json_encode($_logs));
            return false;
        }
    
        //目标文件所在的文件夹不存在, 则主动创建
        $zipfilepath = FileHelper::filepath($zipfile);
        $_logs['$zipfilepath'] = $zipfilepath;
        if (!file_exists($zipfilepath))
        {
            FileHelper::mkdir($zipfilepath, 0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir '.json_encode($_logs));
    
            if (!file_exists($zipfilepath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir error '.json_encode($_logs));
                trigger_error("Error : mkdir ".$zipfilepath);
            }
        }
    
        include_once __DIR__.'/../../common/libraries/pclzip/pclzip.lib.php';
        $zip = new \PclZip($zipfile);
    
        //打包在path目录, 且去掉
        $error = $zip->create($path, PCLZIP_OPT_REMOVE_PATH, $path);
        #$error = $zip->create($file, PCLZIP_OPT_REMOVE_ALL_PATH);
        if ($error == 0)
        {
            $_logs['ziperror'] = $zip->errorInfo(true);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '.json_encode($_logs));
            trigger_error("Error : ".$zip->errorInfo(true));
        }
        return $zipfile;
    }
    
    public static function unzip_pcl($zipfile, $extractpath)
    {
        $_logs = ['$extractpath' => $extractpath, '$zipfile' => $zipfile];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        include_once __DIR__.'/../../common/libraries/pclzip/pclzip.lib.php';
        $zip = new \PclZip($zipfile);
        $error = $zip->extractTo($extractpath);
        if ($error == 0)
        {
            $_logs['ziperror'] = $zip->errorInfo(true);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode($_logs));
            trigger_error("Error : ".$zip->errorInfo(true));
        }
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    /**
     * 返回zip包内的文件内容
     * @param $zipfile
     * @param $filename
     * @return string
     */
    public static function zip_get_file_content($zipfile,$filename){
        $zip = zip_open($zipfile);
        if ($zip)
        {
            while ($zip_entry = zip_read($zip))
            {
    
                $path = zip_entry_name($zip_entry);
                if($path == $filename){
                    $size = zip_entry_filesize($zip_entry);
                    $entry_content = zip_entry_read($zip_entry,$size);
                    return $entry_content;
                }
            }
            zip_close($zip);
        }
    }
    /**
     * 读取zip包的目录结构及文件信息
     *
     * @param unknown $zipfile
     * @return array
     *
     *
     *
     *
     */
    public static function zip_get_all_files($zipfile, $removed = ['.', '..', '.svn'], $includeExtensions = [])
    {
        $_logs = ['$zipfile' => $zipfile, '$removed' => $removed, '$includeExtensions' => $includeExtensions];
    
        $zip = zip_open($zipfile);
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test time0 '.json_encode($_logs));
    
        //保存zip目录结构
        $zipDirTree = [];
        if ($zip)
        {
            while ($zip_entry = zip_read($zip))
            {
                $path = zip_entry_name($zip_entry);
                $size = zip_entry_filesize($zip_entry);
    
                //文件夹
                if ($size < 1)
                {
                    continue;
                }
    
                $pathArr = explode('/', $path);
                //var_dump($pathArr);
    
                $zipDirTreeParent = &$zipDirTree;
                foreach ($pathArr as $p)
                {
                    $ext = FileHelper::fileextension($p);
                    if ($includeExtensions && !in_array($ext, $includeExtensions))
                    {
                        continue;
                    }
    
    
                    if (isset($zipDirTreeParent[$p]))
                    {
                        $zipDirTreeParent[$p]['size'] += $size;
                        $zipDirTreeParent[$p]['count'] += 1;
                    }
                    else
                    {
                        $zipDirTreeParent[$p]['size'] = $size;
                        $zipDirTreeParent[$p]['count'] = 1;
                    }
    
                    $zipDirTreeParent = &$zipDirTreeParent[$p];
                }
    
                //                 echo "Name: " . zip_entry_name($zip_entry) . "\n";
                //                 echo "Actual Filesize: " . zip_entry_filesize($zip_entry) . "\n";
                //                 echo "Compressed Size: " . zip_entry_compressedsize($zip_entry) . "\n";
                //                 echo "Compression Method: " . zip_entry_compressionmethod($zip_entry) . "\n";
                //                 if (zip_entry_open($zip, $zip_entry, "r")) {
                //                     echo "File Contents:n";
                //                     $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                //                     echo "$buf\n";
                //                     zip_entry_close($zip_entry);
                //                 }
                //echo "\n";
            }
            zip_close($zip);
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test time1 '.json_encode($_logs));
        return $zipDirTree;
    }
    
    /**
     * 获取zip包目录结构
     * @param   $zipPath 路径
     * @return  $array
     * @author  <王中艺>
     */
    static function get_zip_struct($zipPath, $prefix = '')
    {
        $_logs['$zipPath'] = $zipPath;
        $_logs['$prefix'] = $prefix;
    
        $zip = zip_open($zipPath);
        if (!is_resource($zip))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' zip_open error '.json_encode($_logs));
            return false;
        }
    
        $zipStruct  = [];
        while($zipHandle = zip_read($zip))
        {
            $path = zip_entry_name($zipHandle);
            $size = zip_entry_filesize($zipHandle);
    
            //是文件夹则跳过
            if ($size < 1)
            {
                continue;
            }
    
            $pathCuts   = explode('/', $path);
    
            $folder     = &$zipStruct;
            $folderPath = $prefix;
            foreach($pathCuts as $cut)
            {
                $_logs['$cut'] = $cut;
    
                if(!$cut)
                {
                    continue;
                }
    
                //若是文件则不统计
                $ext = FileHelper::fileextension($cut);
                if ($ext)
                {
                    continue;
                }
    
                //若文件路径包含中文等, 则转换
                //                     if (!preg_match('/^[\w\-\.\/]+$/', $cut))
                //                     {
                //                         $cut_ = @iconv('UTF-8','GBK', $cut);
                //                         $_logs['$cut_'] = $cut_;
                //                         if ($cut_)
                //                         {
                //                             $cut = $cut_;
                //                             Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' iconv succ '.json_encode($_logs));
                //                         }
                //                         else
                //                         {
                //                             Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' iconv fail '.json_encode($_logs));
                //                         }
                //                     }
    
                //处理path
                $folderPath .= ($folderPath && substr($folderPath, -1, 1) != '/' ? '/' : '').$cut.'/';
    
                if(!isset($folder[$cut]))
                {
                    $folder[$cut]   = [
                        'count' => 0,
                        'size'  => 0,
                        'sizeFormat'  => 0,
                        'name'  => $cut,
                        'path'  => $folderPath,
                        'children' => []
                    ];
                }
    
                if($size){
                    $folder[$cut]['count']++;
                    $folder[$cut]['size']   += $size;
                }
    
                $folder = &$folder[$cut]['children'];
    
            }
        }
    
        zip_close($zip);
    
        return $zipStruct;
    }
    
    public static function get_zip_struct_linux($zipPath, $prefix = '', $removed = ['.', '..', '.svn'])
    {
        $_logs = ['$zipPath' => $zipPath, '$prefix' => $prefix];
    
        //$_logs['locale'] = exec('locale charmap');
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
    
        //设置环境变量解决中文编码问题
//         $locale = 'zh_CN.UTF-8';
//         @setlocale(LC_ALL, $locale);
//         @putenv('LC_ALL='.$locale);
        //-------------------------
        
        $cmd = 'unzip -h';
        @exec($cmd, $output, $return_var);
        
        if (preg_match('/-O/', serialize($output)))
        {
            $cmd = sprintf('unzip -l -O cp936 %s', escapeshellarg($zipPath));
            @exec($cmd, $output, $return_var);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' unzip has -O '.json_encode($_logs));
            
        }
        else
        {
            $cmd = sprintf('unzip -l %s', escapeshellarg($zipPath));
            @exec($cmd, $output, $return_var);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' unzip hasnot -O '.json_encode($_logs));
        }
    
        $_logs['$cmd'] = $cmd;
        //$_logs['$output'] = $output;
        $_logs['$return_var'] = $return_var;
    
        //         if ($return_var)
        //         {
        //             Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
        //             return false;
        //         }
    
        if (!is_array($output))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
            return false;
        }
    
        if (count($output) <= 5)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locale '.json_encode($_logs));
            return null;
        }
    
        $lines = array_slice($output, 3, -2);
        $structs = [];
        foreach ($lines as $i => $line)
        {
            $_logs['$line'] = $line;
    
            //229684  07-16-2017 03:10   2341191农夫山泉NFC果汁17.5100鲜榨苹果汁330ml瓶（2件起售）/596b1f6aN1eaabbc3.jpg
            preg_match('/(\d+)\s+([\d\-:]+)\s+([\d:]+)\s+(.*)/', $line, $matches);
            $_logs['$matches'] = $matches;
            if (!$matches)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $matches '.json_encode($_logs));
                continue;
            }
            list($all, $size, $date, $time, $path) = $matches;
            $path = basename($zipPath).'/'.$path;
            $pathCuts = explode('/', $path);
            $_logs['$pathCuts'] = $pathCuts;
    
            if ($removed && array_intersect($pathCuts, $removed))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $removed '.json_encode($_logs));
                continue;
            }
    
            $folder = &$structs;
            $folderPath = $prefix;
            foreach ($pathCuts as $k => $cut)
            {
                $_logs['$cut'] = $cut;
    
                if (count($pathCuts) == $k+1)
                {
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' last '.json_encode($_logs));
                    continue;
                }
    
                //$cut = StringHelper::toutf8($cut);
                //$_logs['$cut1'] = $cut;
    
                //处理path
                $folderPath .= ($folderPath && substr($folderPath, -1, 1) != '/' ? '/' : '') . $cut;
                //$_logs['$folderPath'] = $folderPath;
    
                if(!isset($folder[$cut]))
                {
                    $folder[$cut] = [
                        'count' => 0,
                        'size_count'  => 0,
                        'size'  => 0,
                        'name'  => $cut,
                        'path'  => $folderPath,
                        //'path'  => StringHelper::base64_encode($folderPath),
                        'children' => []
                    ];
                }
    
                if($size > 0)
                {
                    $folder[$cut]['count']++;
                    $folder[$cut]['size'] += $size;
                    //$folder[$cut]['size'] = FormatHelper::filesize_format($folder[$cut]['size_count']);
    
                }
    
                $folder = &$folder[$cut]['children'];
            }
    
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' line succ '.json_encode($_logs));
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $structs;
    }
    
    
}