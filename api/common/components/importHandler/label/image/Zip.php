<?php 
namespace common\components\importHandler\label\image;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\ZipHelper;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\ArrayHelper;
/**
 * 
 * zip文件处理
 * 
 *
 */

class Zip extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = ArrayHelper::desc($args);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['filePath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' filePath empty '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            $checkMessages = ['label_unpack_error_filePath_empty' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        $filePath = $args['filePath'];
        $defaultField = isset($args['defaultField']) ? $args['defaultField'] : null;
        $extensions = isset($args['extensions']) ? $args['extensions'] : null;
        $batchConfigs = isset($args['batchConfigs']) ? $args['batchConfigs'] : null;
        
        //------------------------------------------
        
        //文件的相对路径
        $filePathRelative = ltrim(str_replace(Setting::getUploadRootPath(), '', $filePath), '/');
        $_logs['$filePathRelative'] = $filePathRelative;
        
        //------------------------------------------
        
        //解压的目录, 格式为userid/dataid/filename/date/ + file
        $extractpath = Setting::getResourceRootPath() .'/'.$filePathRelative;
        $_logs['$extractpath'] = $extractpath;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath '.json_encode($_logs));
        
        //校验目录是否存在且不为空
        if (file_exists($extractpath))
        {
            //删除解包文件夹
            FileHelper::rmdir($extractpath);
        
            if (file_exists($extractpath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath exist, donot remove '.json_encode($_logs));
                // return FormatHelper::result('', 'error', 'error');
                
                $checkMessages = ['label_unpack_error_extractpath_exist' => []];
                return FormatHelper::result('', 'error', json_encode($checkMessages));
            }
        }
        
        //-----------------------------
        
        //创建用户目录
        @mkdir($extractpath, 0777, true);
        @chmod($extractpath, 0777);
        if (!file_exists($extractpath))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir fail '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            
            $checkMessages = ['label_unpack_error_mkdir_fail' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath mkdir succ '.json_encode($_logs));
        
        //解压包
        $isUnzip = ZipHelper::unzip_linux($filePath, $extractpath);
        if (!$isUnzip)
        {
            //删除解包文件夹
            FileHelper::rmdir($extractpath);
        
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' zip extractTo fail '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            
            $checkMessages = ['label_unpack_error_zip_extractTo_fail' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        
        //读取文件夹的文件列表
        $fileList = FileHelper::get_all_files_by_scan_dir($extractpath, '', Yii::$app->params['task_source_ignorefiles']);
        if (!$fileList)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
            // return FormatHelper::result('', 'error', '包内没有文件');
            
            $checkMessages = ['label_unpack_error_file_list_empty' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        $_logs['$fileList.count'] = count($fileList);
        
        //包名都是数字, 按包名进行排序
        natsort($fileList);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' natsort '.json_encode($_logs));
        
        $fileCount = count($fileList);
        $fileData = [];
        $checkFailCount = 0;
        $checkMessages = ['label_unpack_succ_tip' => []];
        foreach ($fileList as $source_path)
        {
            $sourceFilePath = $filePathRelative .'/'.ltrim($source_path, '/');
            $_logs['$sourceFilePath'] = $sourceFilePath;
        
            $sourceFileExt = FileHelper::fileextension($sourceFilePath);
            $_logs['$sourceFileExt'] = $sourceFileExt;
        
            if ($extensions && !in_array(strtolower($sourceFileExt), $extensions))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' extension no match '.json_encode($_logs));
                
                $checkFailCount ++;
                
                if (empty($checkMessages['label_unpack_succ_tip_format_error']) || 
                    (is_array($checkMessages['label_unpack_succ_tip_format_error']) && !in_array($sourceFileExt, $checkMessages['label_unpack_succ_tip_format_error'])))
                {
                    $checkMessages['label_unpack_succ_tip_format_error'] = [$sourceFileExt];
                }
                
                continue;
            }
            
            $fileData[] = [$defaultField => $sourceFilePath];
        }
        
        //提示成功和失败数量
        if ($checkFailCount)
        {
            $checkMessages['label_unpack_succ_tip'] = [$fileCount - $checkFailCount, $checkFailCount];
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($fileData, '', $checkMessages);
    }
}