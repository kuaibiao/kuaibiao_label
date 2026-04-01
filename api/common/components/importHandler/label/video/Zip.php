<?php 
namespace common\components\importHandler\label\video;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\ZipHelper;


/**
 * 
 * 输入为:视频切割成图片后的图片打包文件夹(.zip)
 * 支持图片包
 * 
 *
 */

class Zip extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['filePath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' filePath empty '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            $listMessage = [
                    'content' => 'label_unpack_error_filePath_empty',
            ];
            $errmessages = json_encode($listMessage);

            return FormatHelper::result('', 'error', $errmessages);
        }
        $filePath = $args['filePath'];
        $defaultField = isset($args['defaultField']) ? $args['defaultField'] : null;
        $extensions = isset($args['extensions']) ? $args['extensions'] : null;
        $batchConfigs = isset($args['batchConfigs']) ? $args['batchConfigs'] : null;
        $video_as_frame = isset($args['video_as_frame']) ? $args['video_as_frame'] : null;
        
        //------------------------------------------
        
        $frameNumber = 100;
        if (!empty($batchConfigs['frame_number']) && $batchConfigs['frame_number'] > 0)
        {
            $frameNumber = $batchConfigs['frame_number'];
        }
        
        //文件的相对路径
        $filePathRelative = ltrim(str_replace(Setting::getUploadRootPath(), '', $filePath), '/');
        
        //解压的目录, 格式为userid/dataid/filename/date/ + file
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
        
        //------------------------------------------
        
        //解压缩包
        $unzipPath = $filePath.'.unzip';
        FileHelper::touchPath($unzipPath);
        
        //压缩文件解压为文件夹
        ZipHelper::unzip_linux($filePath, $unzipPath);
        
        //------------------------------------------
        
        //读取文件夹的文件列表
        $unzipFileList = FileHelper::get_all_files_by_scan_dir($unzipPath, '', Yii::$app->params['task_source_ignorefiles']);
        if (!$unzipFileList)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            $listMessage = [
                    'content' => 'label_unpack_error_file_list_empty',
            ];
            $errmessages = json_encode($listMessage);

            return FormatHelper::result('', 'error', $errmessages);
        }
        
        //判断是否图片包还是视频包
        $isImageZip = false;
        foreach ($unzipFileList as $i => $filepath_)
        {
            $sourceFilePath = $unzipPath .'/'.ltrim($filepath_, '/');
            $_logs['$sourceFilePath'] = $sourceFilePath;
            
            if (FileHelper::is_image($sourceFilePath))
            {
                $isImageZip = true;
                break;
            }
        }
        
        //是图片包
        if ($isImageZip)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $isImageZip '.json_encode($_logs));
            
            //图片分组
            $frameGroupPath = $filePath.'.framegroups';
            FileHelper::touchPath($frameGroupPath);
            
            //图片分组
            $frameGroupZipPath = $filePath.'.framegroupzips';
            FileHelper::touchPath($frameGroupZipPath);
            
            //读取文件夹的文件列表
            $dirList = FileHelper::dir_get_all_childdirs($unzipPath, '', Yii::$app->params['task_source_ignorefiles']);
            if ($dirList)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' has dirs '.json_encode($_logs));
                
                $_logs['$dirList'] = $dirList;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileList '.json_encode($_logs));
                
                //图片名都是数字, 按图片名进行排序
                natsort($dirList);
                
                foreach ($dirList as $i => $dirpath)
                {
                    $_logs['$dirpath'] = $dirpath;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each dirs '.json_encode($_logs));
                    
                    //目录的图片数
                    $childDirFileList = FileHelper::get_all_files_by_scan_dir($unzipPath.'/'.$dirpath, '', Yii::$app->params['task_source_ignorefiles']);
                    if (!$childDirFileList)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
                        // return FormatHelper::result('', 'error', 'error');
                        $listMessage = [
                                'content' => 'label_unpack_error_file_list_empty',
                        ];
                        $errmessages = json_encode($listMessage);

                        return FormatHelper::result('', 'error', $errmessages);
                    }
                
                    $childDirBatches = [];
                    foreach ($childDirFileList as $ii => $childDirFile_)
                    {
                        $_logs['$childDirFile_'] = $childDirFile_;
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each file '.json_encode($_logs));
                        
                        $batch_ = floor($ii/$frameNumber);
                
                        $frameGroupPath_ = $frameGroupPath.'/'.$i.'.'.$batch_;
                        FileHelper::touchPath($frameGroupPath_);
                
                        if (empty($childDirBatches[$batch_]))
                        {
                            $childDirBatches[$batch_] = $frameGroupPath_.'/'.$dirpath;
                        }
                
                        FileHelper::copyFileToDir($unzipPath.'/'.$dirpath.'/'.$childDirFile_, $frameGroupPath_.'/'.$dirpath);
                    }
                    
                    if ($childDirBatches)
                    {
                        foreach ($childDirBatches as $batch_ => $childDirPath_)
                        {
                            ZipHelper::zip($childDirPath_, $frameGroupZipPath.'/'.$i.'.'.$batch_.'.zip');
                        }
                    }
                    
                }
            }
            //没有目录的情况
            else
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' hasno dirs '.json_encode($_logs));
                
                //目录的图片数
                $childDirFileList = FileHelper::get_all_files_by_scan_dir($unzipPath, '', Yii::$app->params['task_source_ignorefiles']);
                if (!$childDirFileList)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
                    // return FormatHelper::result('', 'error', 'error');
                    $listMessage = [
                            'content' => 'label_unpack_error_file_list_empty',
                    ];
                    $errmessages = json_encode($listMessage);

                    return FormatHelper::result('', 'error', $errmessages);
                }
            
                $childDirBatches = [];
                foreach ($childDirFileList as $ii => $childDirFile_)
                {
                    $_logs['$childDirFile_'] = $childDirFile_;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each file '.json_encode($_logs));
                    
                    $batch_ = floor($ii/$frameNumber);
                    
                    $frameGroupPath_ = $frameGroupPath.'/'.$batch_;
                    FileHelper::touchPath($frameGroupPath_);
            
                    if (empty($childDirBatches[$batch_]))
                    {
                        $childDirBatches[$batch_] = $frameGroupPath_;
                    }
                    
                    FileHelper::copyFileToDir($unzipPath.'/'.$childDirFile_, $frameGroupPath_);
                }
                
                if ($childDirBatches)
                {
                    foreach ($childDirBatches as $batch_ => $childDirPath_)
                    {
                        ZipHelper::zip($childDirPath_, $frameGroupZipPath.'/'.$batch_.'.zip');
                    }
                }
            }
            
            //复制文件
            FileHelper::copydir($frameGroupZipPath, $extractpath);
        }
        //是视频包
        else 
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $isVideoZip '.json_encode($_logs));
            
            FileHelper::copydir($unzipPath, $extractpath);
        }
        
        
        //-----------------------------------------
        
        //读取文件夹的文件列表
        $fileList = FileHelper::get_all_files_by_scan_dir($extractpath, '', Yii::$app->params['task_source_ignorefiles']);
        if (!$fileList)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
            // return FormatHelper::result('', 'error', 'error');
            $listMessage = [
                    'content' => 'label_unpack_error_file_list_empty',
            ];
            $errmessages = json_encode($listMessage);

            return FormatHelper::result('', 'error', $errmessages);
        }
        $_logs['$fileList'] = $fileList;
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileList '.json_encode($_logs));
        //var_dump($fileList);
        
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
            
            if ($isImageZip)
            {
                //此处必须是zip
                if (!in_array(strtolower($sourceFileExt), ['zip']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' extension no match '.json_encode($_logs));
                    $checkFailCount ++;
                    
                    if (empty($checkMessages['label_unpack_succ_tip_format_error']) || 
                        (is_array($checkMessages['label_unpack_succ_tip_format_error']) && !in_array($sourceFileExt, $checkMessages['label_unpack_succ_tip_format_error'])))
                    {
                        $checkMessages['label_unpack_succ_tip_format_error'] = [$sourceFileExt];
                    }
                    continue;
                }
            }
            else
            {
                if ($extensions && !in_array(strtolower($sourceFileExt), $extensions))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' extension no match '.json_encode($_logs));
                    $checkFailCount ++;
                    
                    if (empty($checkMessages['label_unpack_succ_tip_format_error']) || 
                        (is_array($checkMessages['label_unpack_succ_tip_format_error']) && !in_array($sourceFileExt, $checkMessages['label_unpack_succ_tip_format_error'])))
                    {
                        $checkMessages['label_unpack_succ_tip_format_error'] = [$sourceFileExt];
                    }
                    continue;
                }
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