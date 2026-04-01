<?php
namespace common\models;

use Yii;
use common\helpers\ZipHelper;
use common\helpers\FileHelper;
use common\models\Setting;

/**
 * 数据部署数据模型
 */
class Deployment extends \yii\db\ActiveRecord
{
    const STATUS_WAIT = 0; //待部署
    const STATUS_RUNNING = 1; //部署中
    const STATUS_SUCCESS = 2; //部署成功
    const STATUS_FAIL = 3; //部署失败
    const STATUS_DELETED = 4; //已删除
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'deployment';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'start_time', 'user_id', 'site_id', 'file_count'], 'integer'],
            ['status', 'integer'],
            ['status', 'default', 'value' => self::STATUS_WAIT],
            ['status', 'in', 'range' => [self::STATUS_DELETED, self::STATUS_FAIL, self::STATUS_RUNNING, self::STATUS_SUCCESS, self::STATUS_WAIT]],
            ['name', 'required'],
            ['name', 'string', 'max' => 30, 'min' => 1],
            ['host', 'required'],
            ['upload_path', 'required'],
            ['file_list_path', 'string'],
        ];
    }
    
    /**
     * 数据部署状态
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_WAIT => Yii::t('app', 'data_deployment_status_wait'),
            self::STATUS_RUNNING => Yii::t('app', 'data_deployment_status_running'),
            self::STATUS_SUCCESS => Yii::t('app', 'data_deployment_status_success'),
            self::STATUS_FAIL => Yii::t('app', 'data_deployment_status_fail'),
            self::STATUS_DELETED => Yii::t('app', 'data_deployment_status_deleted')
        ];
    }
    
    public static function unzip($zipfile)
    {
        //文件的相对路径
        $_logs['$zipfile'] = $zipfile;
        
        //绝对路径
        $zipfileReal = rtrim(Setting::getUploadRootPath(), '/').'/'.ltrim($zipfile, '/');
        
        $filePathRelative = ltrim(str_replace('/deployment', '', $zipfile), '/');
        $_logs['$filePathRelative'] = $filePathRelative;
        
        $extractpath = rtrim(Setting::getDeployRootPath(), '/').'/'.$filePathRelative;
        $_logs['$extractpath'] = $extractpath;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath '.json_encode($_logs));
        
        //校验解压目录是否存在且不为空
        if (file_exists($extractpath))
        {
            //删除解包文件夹
            FileHelper::rmdir($extractpath);
        
            if (file_exists($extractpath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath exist, donot remove '.json_encode($_logs));
                return false;
            }
        }
        
        //创建解压目录
        @mkdir($extractpath, 0777, true);
        @chmod($extractpath, 0777);
        if (!file_exists($extractpath))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir fail '.json_encode($_logs));
            return false;
        }
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath mkdir succ '.json_encode($_logs));
        
        //解压包
        $isUnzip = ZipHelper::unzip_linux($zipfileReal, $extractpath);
        if (!$isUnzip)
        {
            //删除解包文件夹
            FileHelper::rmdir($extractpath);
        
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' zip extractTo fail '.json_encode($_logs));
            return false;
        }
        
        return $extractpath;
    }
    
    /**
     * 根据相同basename匹配成同一条作业
     * 按照文件类型，每个类型一列，每列之间文件对应关系为文件名相同在一行，例如：abc.jpg和abc.json在同一行。
     * 表头根据文件类型确定，可能是image_url、ai_result、voice_url、video_url、3d_url、unknown
     * 示例1: 
     * 1.jpg,1.txt
     * 示例2:
     * 1.pcd,1.jpg
     */
    public static function ruleSameBasename($fileList, $baseUrl)
    {
        $_logs = [];
        
        //根据文件类型进行分组
        //文件类型有,图片,文本,语音,视频,3d
        $groups = [];
        $ExtRecord_ = [];//记录文件的后缀，给客户提示用

        natsort($fileList); //对文件列表自然排序

        foreach ($fileList as $file)
        {

            if (FileHelper::is_image($file))
            {
                $groups[0][$file] = (string)FileHelper::filefilename($file);
            }
            elseif (FileHelper::is_text($file) || strrchr($file, '.') == '.json')
            {
                $groups[1][$file] = (string)FileHelper::filefilename($file);
            }
            elseif (FileHelper::is_audio($file))
            {
                $groups[2][$file] = (string)FileHelper::filefilename($file);
            }
            elseif (FileHelper::is_video($file))
            {
                $groups[3][$file] = (string)FileHelper::filefilename($file);
            }
            elseif (FileHelper::is_3d($file))
            {
                $groups[4][$file] = (string)FileHelper::filefilename($file);
            }
            else
            {
                $groups[5][$file] = (string)FileHelper::filefilename($file);
            }

            //记录扩展名
            $strExt_ = FileHelper::fileextension($file);
            if(!in_array($strExt_, $ExtRecord_)){
                array_push($ExtRecord_, $strExt_);
            }
        }
        $titles = ['image_url', 'ai_result', 'voice_url', 'video_url', '3d_url', 'unknown'];
        
        $baseNames = [];
        $list_titles = [];
        foreach($groups as $index => $group)
        {
            $list_titles[$index] = isset($titles[$index]) ? $titles[$index] : '';
            $groupBaseNames = array_values($group);
            $baseNames = array_merge($baseNames, $groupBaseNames);
        }
//         $baseNames = array_unique($baseNames);
        $_logs['count($baseNames)'] = count($baseNames);
        $groupCount = count($groups);
        $_logs['$groupCount'] = $groupCount;
        if($groupCount == 1 && isset($list_titles[1])) //如果只有文本，则表头为text_url
        {
            $list_titles[1] = 'text_url';
        }
        ksort($list_titles);
        ksort($groups);
        $list_titles = array_values($list_titles);
        $groups = array_values($groups);
        $list = [];
        foreach($groups as $index => $group)
        {
            foreach($baseNames as $i => $baseName)
            {
                if(in_array($baseName, $group))
                {
                    $file = array_search($baseName, $group);
                    $list[$i][$index] = $baseUrl.'/'.ltrim($file, '/');
                    unset($group[$file]); //兼容basename重复
                }
                else 
                {
                    $list[$i][$index] = '';
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
        
        if(count($list) == count($fileList) && $groupCount != 1)
        {
            return false;
        }
        
        array_unshift($list, $list_titles);
        $_logs['count($list)'] = count($list);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return [$list, $ExtRecord_];
    }
    
    
    /**
     * 根据相同basename匹配成同一条作业
     * 按照文件类型，每个类型一列，每列之间文件对应关系为文件名相似在一行，例如：abc.jpg和abc.jpg.json在同一行。
     * 表头根据文件类型确定，可能是image_url、ai_result、voice_url、video_url、3d_url、unknown
     * 示例1:
     * 1.jpg,1.jpg.txt
     */
    public static function ruleLikeBasename($fileList, $baseUrl)
    {
        $_logs = [];
        
        //根据文件类型进行分组
        //文件类型有,图片,文本,语音,视频,3d
        $fileNames = [];
        $fileBaseNames = [];
        $groups = [];
        $ExtRecord_ = [];//记录文件的后缀，给客户提示用

        natsort($fileList); //对文件列表自然排序

        foreach ($fileList as $file)
        {
            $sepPos = strrpos($file, '/');
            $temp = $sepPos === false ? $file : substr($file, $sepPos + 1);
            if (FileHelper::is_image($file))
            {
                $groups[0][$file] = (string)str_replace(strstr($temp, '.'), '', $temp);
            }
            elseif (FileHelper::is_text($file) || strrchr($file, '.') == '.json')
            {
                $groups[1][$file] = (string)str_replace(strstr($temp, '.'), '', $temp);
            }
            elseif (FileHelper::is_audio($file))
            {
                $groups[2][$file] = (string)str_replace(strstr($temp, '.'), '', $temp);
            }
            elseif (FileHelper::is_video($file))
            {
                $groups[3][$file] = (string)str_replace(strstr($temp, '.'), '', $temp);
            }
            elseif (FileHelper::is_3d($file))
            {
                $groups[4][$file] = (string)str_replace(strstr($temp, '.'), '', $temp);
            }
            else 
            {
                $groups[5][$file] = (string)str_replace(strstr($temp, '.'), '', $temp);
            }

            //记录扩展名
            $strExt_ = FileHelper::fileextension($file);
            if(!in_array($strExt_, $ExtRecord_)){
                array_push($ExtRecord_, $strExt_);
            }
        }
        $titles = ['image_url', 'ai_result', 'voice_url', 'video_url', '3d_url', 'unknown'];
        
        $baseNames = [];
        $list_titles = [];
        foreach($groups as $index => $group)
        {
            $list_titles[$index] = isset($titles[$index]) ? $titles[$index] : '';
            $groupBaseNames = array_values($group);
            $baseNames = array_merge($baseNames, $groupBaseNames);
        }
//         $baseNames = array_unique($baseNames);
        $_logs['count($baseNames)'] = count($baseNames);
        $groupCount = count($groups);
        $_logs['$groupCount'] = $groupCount;
        if($groupCount == 1 && isset($list_titles[1])) //如果只有文本，则表头为text_url
        {
            $list_titles[1] = 'text_url';
        }
        ksort($list_titles);
        ksort($groups);
        $list_titles = array_values($list_titles);
        $groups = array_values($groups);
        $list = [];
        foreach($groups as $index => $group)
        {
            foreach($baseNames as $i => $baseName)
            {
                if(in_array($baseName, $group))
                {
                    $file = array_search($baseName, $group);
                    $list[$i][$index] = $baseUrl.'/'.ltrim($file, '/');
                    unset($group[$file]); //兼容basename重复
                }
                else 
                {
                    $list[$i][$index] = '';
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
        if(count($list) == count($fileList) && $groupCount != 1)
        {
            return false;
        }
        
        array_unshift($list, $list_titles);
        $_logs['count($list)'] = count($list);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return [$list, $ExtRecord_];
    }
    
    /**
     * 匹配数据集的规则：
     * 提取数据集中data文件夹（数据文件夹）和annotations文件夹（结果文件夹）的文件，按照文件类型，
     * 每个类型一列，每列之间文件对应关系为文件名相似在一行：例如abc.jpg和abc.jpg.json在同一行。
     * 表头根据文件类型确定，可能是image_url、ai_result、voice_url、video_url、3d_url、unknown
     * @param unknown $fileList
     * @param unknown $baseUrl
     */
    public static function ruleBfDataset($fileList, $baseUrl)
    {
        $_logs = ['count($fileList)' => count($fileList), '$baseUrl' => $baseUrl];
    
        if(in_array('list.txt', $fileList) && in_array('info.json', $fileList))
        {
            $files = [];
            foreach($fileList as $file)
            {
                if(strpos($file, '/data/') === 0 || strpos($file, '/annotations/') === 0 || strpos($file, 'data/') === 0 || strpos($file, 'annotations/') === 0)
                {
                    $files[] = $file;
                }
            }
            $_logs['count($files)'] = count($files);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $files '.json_encode($_logs));
            return self::ruleLikeBasename($files, $baseUrl);
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
    private static function rule3d($dataPath = '', $baseUrl = '')
    {
        $_logs = ['$dataPath' => $dataPath, '$baseUrl' => $baseUrl];
        if(empty($dataPath) || empty($baseUrl))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' method param error '.json_encode($_logs));
            return false;
        }
    
        $dirs = FileHelper::scan_dir($dataPath, '', 0, Yii::$app->params['task_source_ignorefiles']); //列出路径下的文件夹
        // if(count($dirs) == 1) //兼容外面包一层文件夹的情况
        // {
        //     $baseUrl .= '/'.$dirs[0];
        //     $dataPath .= '/'.$dirs[0];
        //     if(is_dir($dataPath))
        //     {
        //         $dirs = FileHelper::scan_dir($dataPath, '', 0, Yii::$app->params['task_source_ignorefiles']);
        //     }
        // }
        $_logs['$dirs'] = $dirs;
        if(in_array('3d_url', $dirs))
        {
            $groups = [];
            $files = [];
            $list = [];
    
            $files3d = FileHelper::get_all_files_by_scan_dir($dataPath, '3d_url', Yii::$app->params['task_source_ignorefiles']);

            natsort($files3d); //对文件列表自然排序

            foreach($files3d as $index => $file)
            {
                $files3d[$index] = (string)FileHelper::filefilename($file);
            }

            foreach($dirs as $dir)
            {
                $dirFiles = FileHelper::get_all_files_by_scan_dir($dataPath, $dir, Yii::$app->params['task_source_ignorefiles']);
                $files = array_merge($files, $dirFiles);
            }

            $_logs['count($files)'] = count($files);
            $ExtRecord_ = [];//记录文件的后缀，给客户提示用
            foreach($files as $index => $file) //将所有文件按照文件夹分组
            {
                preg_match('|(.+)/.+|', $file, $matches);
                if(isset($matches[1]))
                {
                    $groups[$matches[1]][$file] = (string)FileHelper::filefilename($file);
                }

                //记录扩展名
                $strExt_ = FileHelper::fileextension($file);
                if(!in_array($strExt_, $ExtRecord_)){
                    array_push($ExtRecord_, $strExt_);
                }
            }

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
                        $list[$row][$column] = $baseUrl.'/'.ltrim($fileName, '/');
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
    
            array_unshift($list, $dirs); //加表头
            $_logs['count($list)'] = count($list);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return [$list, $ExtRecord_];
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pattern not match '.json_encode($_logs));
    
        return false;
    }

    /**
     * 匹配3d的新规则：
     * 以3d_url的外层目录（例如目录A）整体作为一个作业，目录A下面的结构如下：
     * 3d_url目录，图片目录（image0，image1，image2……，图片目录的数量根据实际情况），
     * 3d_url目录和图片目录下面的文件名字要对应，
     * 例如：
     * a/3d_url/1.pcd,a/image0/1.jpg,a/image1/1.jpg……对应，
     * a/3d_url/2.pcd,a/image0/2.jpg,a/image1/2.jpg……对应，
     * b/3d_url/1.pcd,b/image0/1.jpg,b/image1/1.jpg……对应
     * 
     * @param string $dataPath
     * @param string $baseUrl
     */
    public static function rule3dNew($dataPath = '', $baseUrl = '')
    {
        $_logs = ['$dataPath' => $dataPath, '$baseUrl' => $baseUrl];

        $path3d = FileHelper::findFilePath($dataPath, '3d_url');
        $_logs['$path3d'] = $path3d;
        if($path3d === false)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' 3d_url not found '.json_encode($_logs));
            return false;
        }

        $list = [];
        $ext = [];
        foreach($path3d as $path)
        {
            // $dataPath = $dataPath.'/'.$path;
            $baseUrlNew = $baseUrl.'/'.ltrim(str_replace($dataPath, '', $path), '/');
            $_logs['$baseUrlNew'] = $baseUrlNew;
            $result = self::rule3d($path, $baseUrlNew);
            if($result === false)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$path.' not match rule3d '.json_encode($_logs));
                continue;
            }

            list($resultList, $resultExt) = $result;
            if($resultList && is_array($resultList))
            {
                if($list && $resultList)
                {
                    array_shift($resultList); //去除后面组的表头，只有第一组有表头
                }
                $list = array_merge($list, $resultList);
            }
            if($resultExt && is_array($resultExt))
            {
                $ext = array_merge($ext, $resultExt);
            }
        }
        $_logs['count($list)'] = count($list);

        if(empty($list))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' list empty '.json_encode($_logs));
            return false;
        }
        $ext = array_unique($ext);
        $_logs['$ext'] = $ext;

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rule 3d success '.json_encode($_logs));
        
        return [$list, $ext];
    }
    
    /**
     * 没有规则匹配的时候
     * 所有文件在同一列，根据文件类型确定表头，哪个类型文件最多，就用哪个类型的表头，
     * 表头可能是image_url、ai_result、voice_url、video_url、3d_url、unknown
     * @param unknown $fileList 文件列表
     * @param unknown $baseUrl url
     * @return Ambigous <multitype:, string>
     */
    public static function ruleNo($fileList, $baseUrl)
    {
        $list = [];

        $baseNameCount_ = [];//扩展名计数，用于判断多文件类型产生的问题
        $ExtRecord_ = [];//记录文件的后缀，给客户提示用

        natsort($fileList); //对文件列表自然排序

        foreach($fileList as $index => $file)
        {
            $list[$index][0] = $baseUrl.'/'.ltrim($file, '/');

            //文件类型计数
            $strFileType_ = '';
            
            if (FileHelper::is_image($file))
            {
                $strFileType_ = 'image_url';
            }
            elseif (FileHelper::is_text($file) || strrchr($file, '.') == '.json')
            {
                $strFileType_ = 'text_url';
            }
            elseif (FileHelper::is_audio($file))
            {
                $strFileType_ = 'voice_url';
            }
            elseif (FileHelper::is_video($file))
            {
                $strFileType_ = 'video_url';
            }
            elseif (FileHelper::is_3d($file))
            {
                $strFileType_ = '3d_url';
            }
            else
            {
                $strFileType_ = 'unknown';
            }

            //记录扩展名
            $strExt_ = FileHelper::fileextension($file);
            if(!in_array($strExt_, $ExtRecord_)){
                array_push($ExtRecord_, $strExt_);
            }

            //数据类型计数
            if(isset($baseNameCount_[$strFileType_])){
                $baseNameCount_[$strFileType_] = $baseNameCount_[$strFileType_] +1;
            }else{
                $baseNameCount_[$strFileType_] = 1;
            }
        }

        //获取最多的类型为文件的表头
        $list_titles = [array_search(max($baseNameCount_),$baseNameCount_)];

        array_unshift($list, $list_titles);

        return [$list, $ExtRecord_];

    }
    
    public function getFtp()
    {
        return $this->hasOne(UserFtp::className(), ['user_id' => 'user_id']);
    }
    
    public function getFtpPathKey($deploymentId = 0)
    {
        return 'deploy:'.':ftp:path:'.Yii::$app->user->id;
    }
}