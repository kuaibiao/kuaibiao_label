<?php 
namespace common\components\importHandler\label\threed;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\ZipHelper;;
use common\helpers\StringHelper;
use common\helpers\JsonHelper;

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
        
        //------------------------------------------
        
        //文件的相对路径
        $filePathRelative = ltrim(str_replace(Setting::getUploadRootPath(), '', $filePath), '/');
        $_logs['$filePathRelative'] = $filePathRelative;
        
        //------------------------------------------
        
        //解压的目录, 格式为userid/dataid/filename/date/ + file
        $resourceRootPath = Setting::getResourceRootPath();
        $extractpath = $resourceRootPath .'/'.$filePathRelative;
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
                
                $checkMessages = ['label_unpack_error_filePath_empty' => []];
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
            
            $checkMessages = ['label_unpack_error_filePath_empty' => []];
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
            
            $checkMessages = ['label_unpack_error_filePath_empty' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }


        $path3d = FileHelper::findFilePath($extractpath, $defaultField);
        $_logs['$path3d'] = $path3d;

        if($path3d) //带有2d映射
        {
            $fileCount = 0;
            $checkFailCount = 0;
            $checkMessages = ['label_unpack_succ_tip' => []];
            $fileData = [];
            foreach($path3d as $path)
            {
                $result = FileHelper::rule3d($path, $defaultField, $resourceRootPath);
                if($result === false)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$path.' not match rule3d '.json_encode($_logs));
                    continue;
                }

                list($keys, $values) = $result;
                if($keys && $values)
                {
                    $fileCount += count($values);
                    foreach($values as $value)
                    {
                        $dataArr = [];
                        foreach($keys as $index => $key)
                        {
                            if(!empty($value[$index]))
                            {
                                $dataArr[$key] = $value[$index];
                            }
                        }
                        if(empty($dataArr[$defaultField]))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' default field not found '.json_encode($_logs));
                            $checkFailCount++;

                            $checkMessages['label_unpack_succ_tip_default_field_not_found'] = [];
                            continue;
                        }
                        $sourceFileExt = FileHelper::fileextension($dataArr[$defaultField]);
                        $_logs['$sourceFileExt'] = $sourceFileExt;
                        if ($extensions && !in_array(strtolower($sourceFileExt), $extensions))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' extension no match '.json_encode($_logs));
                            $checkFailCount++;
                            
                            if (empty($checkMessages['label_unpack_succ_tip_format_error']) ||
                                (is_array($checkMessages['label_unpack_succ_tip_format_error']) && !in_array($sourceFileExt, $checkMessages['label_unpack_succ_tip_format_error'])))
                            {
                                $checkMessages['label_unpack_succ_tip_format_error'] = [$sourceFileExt];
                            }
                            continue;
                        }

                        //自带结果格式
                        if (count($dataArr) > 1 && !empty($dataArr['ai_result']))
                        {
                            $_logs['$ai_result'] = $dataArr['ai_result'];
                        
                            if (StringHelper::is_url($dataArr['ai_result']))
                            {
                                $source_result_json = FileHelper::netfile_getcontent($dataArr['ai_result']);
                            }
                            //相对路径
                            elseif (StringHelper::is_relativepath($dataArr['ai_result']))
                            {
                                $resultUrl = rtrim(Setting::getResourceRootPath(), '/').'/'.ltrim($dataArr['ai_result'], '/');
                                $source_result_json = file_get_contents($resultUrl);
                            }
                            else
                            {
                                $source_result_json = $dataArr['ai_result'];
                            }
                            //$_logs['$source_result_json'] = $source_result_json;
                        
                            if (empty($source_result_json))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $source_result_json empty '.json_encode($_logs));
                                continue;
                            }
                            if (!StringHelper::is_json($source_result_json))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $source_result_json not json '.json_encode($_logs));
                                continue;
                            }
                        
                            //如果是完整结果, 则需只取result的值
                            $source_result_arr = JsonHelper::json_decode_all($source_result_json);
                            if (isset($source_result_arr['data']) && isset($source_result_arr['result']))
                            {
                                $source_result_json = json_encode($source_result_arr['result']);
                            }
                        
                            $dataArr['ai_result'] = $source_result_json;
                            $_logs['$dataArr'] = $dataArr;
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ai_result succ '.json_encode($_logs));
                        }

                        array_push($fileData, $dataArr);
                    }
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$path.' not match rule3d '.json_encode($_logs));
                    continue;
                }
            }
        }
        else //没有2d映射
        {
            //读取文件夹的文件列表
            $fileList = FileHelper::get_all_files_by_scan_dir($extractpath, '', Yii::$app->params['task_source_ignorefiles']);
            if (!$fileList)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file list empty '.json_encode($_logs));
                // return FormatHelper::result('', 'error', '包内没有文件');
                
                $checkMessages = ['label_unpack_error_filePath_empty' => []];
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
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' extension no match '.json_encode($_logs));
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

        
        
        //提示成功和失败数量
        if ($checkFailCount)
        {
            $checkMessages['label_unpack_succ_tip'] = [$fileCount - $checkFailCount, $checkFailCount];
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($fileData, '', $checkMessages);
    }
}