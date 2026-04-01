<?php 
namespace common\components\importHandler\label\video;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\components\Ffmpeg;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\ZipHelper;

/**
 * 
 * 视频切割成图片
 * 需要系统安装ffmpeg
 * 
 *
 */

class Video extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
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
        $video_as_frame = isset($args['video_as_frame']) ? $args['video_as_frame'] : null;
        
        //------------------------------------------
        
        //文件的相对路径
        $filePathRelative = ltrim(str_replace(Setting::getUploadRootPath(), '', $filePath), '/');
        $_logs['$filePathRelative'] = $filePathRelative;
        
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
                $checkMessages = ['label_unpack_error_filePath_empty' => []];
                return FormatHelper::result('', 'error', json_encode($checkMessages));
            }
        }
        
        //------------------------------------------
        
        //进行抽帧处理
        if (!empty($batchConfigs['frame_number']))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' as_frame '.json_encode($_logs));
            
            $frameNumber = 100;
            if (!empty($batchConfigs['frame_number']) && $batchConfigs['frame_number'] > 0)
            {
                $frameNumber = $batchConfigs['frame_number'];
            }
            
            //------------------------------------------
            //视频切帧
            $framePath = $filePath.'.frames';
            //$framePath = $filePath.'.videos';
            FileHelper::touchPath($framePath);
            
            //切割视频为图片
            $ffmpeg = new Ffmpeg();
            $ffmpeg->setFfmpeg(Yii::$app->params['ffmpeg_bin']);
            $ffmpeg->setSource($filePath);
            //$ffmpeg->setStartTime(0);
            //$ffmpeg->setDuration(1);
            //$ffmpeg->videoCutToImages($framePath, 1, 1);
            $ffmpeg->videoCutToImagesQuick($framePath, 1);
            //$ffmpeg->videoCutToVideosFormatMp4($framePath, 60);
            
            
            //把切出来的图片转移到resource目录
            FileHelper::copydir($framePath, $extractpath);
            
            //读取文件夹的文件列表
            $fileList = FileHelper::get_all_files_by_scan_dir($extractpath, '', Yii::$app->params['task_source_ignorefiles']);
            if (!$fileList)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
                // return Functions::result('', 'error', 'error');
                $checkMessages = ['label_unpack_error_file_list_empty'=>[]];
                return FormatHelper::result('', 'error', $checkMessages);
            }
            
            $_logs['$fileList'] = $fileList;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileList '.json_encode($_logs));
            
            //图片名都是数字, 按图片名进行排序
            natsort($fileList);
            
            
            //图片分组
            $fileGroups = array_chunk($fileList, $frameNumber);
            
            $fileCount = count($fileGroups);
            $fileData = [];
            $checkFailCount = 0;
            $checkMessages = ['label_unpack_succ_tip' => []];
            foreach ($fileGroups as $fileGroup)
            {
                $fileGroupItems = [];
                foreach ($fileGroup as $source_path)
                {
                    $sourceFilePath = $filePathRelative .'/'.ltrim($source_path, '/');
                    $fileGroupItems[] = $sourceFilePath;
                }
                
                $fileData[] = [$defaultField => $fileGroupItems];
            }
        }
        else 
        {
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' not as_frame '.json_encode($_logs));
            FileHelper::copyFileToDir($filePath, $extractpath);
            
            //读取文件夹的文件列表
            $fileList = FileHelper::get_all_files_by_scan_dir($extractpath, '', Yii::$app->params['task_source_ignorefiles']);
            if (!$fileList)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
                // return FormatHelper::result('', 'error', 'error');
            
                $checkMessages = ['label_unpack_error_file_list_empty' => []];
                return FormatHelper::result('', 'error', json_encode($checkMessages));
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
            
                //此处必须是zip
                if (!in_array(strtolower($sourceFileExt), ['zip']))
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
        }
        
        //-----------------------------------------
        
        //提示成功和失败数量
        if ($checkFailCount)
        {
            $checkMessages['label_unpack_succ_tip'] = [$fileCount - $checkFailCount, $checkFailCount];
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($fileData, '', $checkMessages);
    }
}