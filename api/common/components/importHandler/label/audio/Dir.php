<?php 
namespace common\components\importHandler\label\audio;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;

/**
 * 
 * 文件夹处理
 * 
 */

class Dir extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['filePath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' filePath empty '.json_encode($_logs));
            
            $checkMessages = ['label_unpack_error_filePath_empty' => []];
            return FormatHelper::result('', 'error', $checkMessages);
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
        
        //复制文件
        $extractpath = Setting::getResourceRootPath() .'/'.$filePathRelative;
        $_logs['$extractpath'] = $extractpath;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath '.json_encode($_logs));
        
        //校验目录是否存在且不为空
        if (file_exists($extractpath))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath exist '.json_encode($_logs));
        
            //删除解包文件夹
            FileHelper::rmdir($extractpath);
        
            if (file_exists($extractpath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $extractpath exist, donot remove '.json_encode($_logs));
                // return FormatHelper::result('', 'error', 'error');
                $listMessage = [
                        'content' => 'label_unpack_error_extractpath_exist',
                ];
                $errmessages = json_encode($listMessage);

                return FormatHelper::result('', 'error', $errmessages);
            }
        }
        
        //复制文件
        FileHelper::copydir($filePath, $extractpath);
        
        //读取文件夹的文件列表
        $fileList = FileHelper::get_all_files_by_scan_dir($extractpath, '', Yii::$app->params['task_source_ignorefiles']);
        if (!$fileList)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
            
            $checkMessages = ['label_unpack_error_file_list_empty' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        
        //包名都是数字, 按包名进行排序
        natsort($fileList);
        
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
                $checkFailCount++;
                
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
        return FormatHelper::result($fileData, '', $errmessages);
    }
}