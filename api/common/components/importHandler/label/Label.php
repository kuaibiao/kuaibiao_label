<?php 
namespace common\components\importHandler\label;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\models\ProjectAttribute;
use common\models\Category;
use common\models\Data;
use common\models\Batch;
use common\models\Message;
use common\helpers\FormatHelper;
use common\models\Project;
use common\helpers\FileHelper;
use common\helpers\StringHelper;
use common\helpers\ArrayHelper;
/**
 * 
 * 标注类
 * 
 *
 */

class Label extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = ArrayHelper::desc($args);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['unpack']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $unpack not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $unpack = $args['unpack'];
        
        if (empty($args['project']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $project not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $project = $args['project'];
        
        if (empty($args['batchConfigs']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $batchConfigs not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $batchConfigs = $args['batchConfigs'];
        
        //------------------------------------------------------------
        
        $categoryInfo = Category::find()->where(['id' => $project['category_id']])->asArray()->limit(1)->one();
        
        //分类的必须输入字段
        $categoryFields = array();
        if ($categoryInfo['required_input_field'])
        {
            $categoryInfo['required_input_field'] = trim($categoryInfo['required_input_field'], ',');
            if (strpos($categoryInfo['required_input_field'], ','))
            {
                $categoryFields = explode(',', $categoryInfo['required_input_field']);
            }
            else
            {
                $categoryFields = array($categoryInfo['required_input_field']);
            }
        
            //清除空项
            $categoryFields = array_filter($categoryFields);
        }
        $_logs['$categoryFields'] = $categoryFields;
        
        if (count($categoryFields) > 1)
        {
            //发站内通知
            Message::sendProjectUnpackFail($project['user_id'], $project['id'], 'message_project_unpack_fail_reason_wrongtype');//数据文件解析失败, 类型配置错误
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data_datafile_unpacked_failure_fielddiff $path='.json_encode($_logs));
            return FormatHelper::result('', 'error',json_encode(['content'=>'message_project_unpack_fail_reason_wrongpath']));//数据文件解析失败, 类型配置错误
        }
        if (count($categoryFields) < 1)
        {
            //发站内通知
            Message::sendProjectUnpackFail($project['user_id'], $project['id'], 'message_project_unpack_fail_reason_wrongtype');//数据文件解析失败, 类型配置错误
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data_datafile_unpacked_failure_fielddiff $path='.json_encode($_logs));
            return FormatHelper::result('', 'error', json_encode(['content'=>'message_project_unpack_fail_reason_wrongpath']));
        }
        
        $defaultField = $categoryFields[0];
        $_logs['$defaultField'] = $defaultField;
        
        //-------------------------------------
        
        //获取项目文件目录
        $uploadFilePath = Setting::getUploadfilePath($project['user_id'], $project['id']);
        $_logs['$uploadFilePath'] = $uploadFilePath;
        
        //上传的数据文件
        $uploadFiles = FileHelper::get_dir_stat($uploadFilePath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
        //var_dump($uploadFiles);
        
        if (!$uploadFiles)
        {
            //发站内通知
            Message::sendProjectUnpackFail($project['user_id'], $project['id'], 'message_project_unpack_fail_reason_invaildfile');//解包失败，没有找到有效的文件
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $uploadFiles noexist '.serialize($_logs));
            return FormatHelper::result('', 'error', json_encode(['content'=>'site_unpack_fail_vallid_file_not_found']));
        }
        
        //更新进度
        $unpack->unpack_progress = 10;
        $unpack->updated_at = time();
        $unpack->save();
        
        
        $batchData = array();
        $progressValue = 40;
        $itemProgressValue = round($progressValue / count($uploadFiles));
        
        //遍历处理上传的文件
        foreach ($uploadFiles as $fileIndex => $_file)
        {
            $_filePath = $uploadFilePath . '/'. $_file['path'];
            $_filePathRelative = ltrim(str_replace(Setting::getUploadRootPath(), '', $_filePath), '/');//文件的相对路径
        
            $_logs['$_file'] = $_file;
            $_logs['$_filePath'] = $_filePath;
            $_logs['$_filePathRelative'] = $_filePathRelative;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each file '.json_encode($_logs));
        
            
            $script = '';
            if ($categoryInfo['file_type'] == Category::FILE_TYPE_IMAGE)
            {
                if (is_dir($_filePath))
                {
                    $script = 'image\Dir';
                }
                elseif (FileHelper::is_zip($_filePath))
                {
                    $script = 'image\Zip';
                }
                elseif (FileHelper::is_xls($_filePath))
                {
                    $script = 'image\Excel';
                }
                elseif (FileHelper::is_csv($_filePath))
                {
                    $script = 'image\Excel';
                }
                elseif (FileHelper::is_video($_filePath))
                {
                    //$script = 'image\Video';
                }
                else if (FileHelper::is_text($_filePath))
                {
                    $script = 'image\Txt';
                }
            }
            elseif ($categoryInfo['file_type'] == Category::FILE_TYPE_AUDIO)
            {
                if (is_dir($_filePath))
                {
                    $script = 'audio\Dir';
                }
                elseif (FileHelper::is_zip($_filePath))
                {
                    $script = 'audio\Zip';
                }
                elseif (FileHelper::is_xls($_filePath))
                {
                    $script = 'audio\Excel';
                }
                elseif (FileHelper::is_csv($_filePath))
                {
                    $script = 'audio\Excel';
                }
                elseif (FileHelper::is_audio($_filePath))
                {
                    //$script = 'audio\Video';
                }
                else if (FileHelper::is_text($_filePath))
                {
                    //$script = 'audio\Txt';
                }
            }
            elseif ($categoryInfo['file_type'] == Category::FILE_TYPE_TEXT)
            {
                if (is_dir($_filePath))
                {
                    $script = 'text\Dir';
                }
                elseif (FileHelper::is_zip($_filePath))
                {
                    $script = 'text\Zip';
                }
                elseif (FileHelper::is_xls($_filePath))
                {
                    $script = 'text\Excel';
                }
                elseif (FileHelper::is_csv($_filePath))
                {
                    $script = 'text\Excel';
                }
                elseif (FileHelper::is_video($_filePath))
                {
                    //$script = 'text\Video';
                }
                else if (FileHelper::is_text($_filePath))
                {
                    $script = 'text\Txt';
                }
            }
            elseif ($categoryInfo['file_type'] == Category::FILE_TYPE_VIDEO)
            {
                if (is_dir($_filePath))
                {
                    //$script = 'video\Dir';
                }
                elseif (FileHelper::is_zip($_filePath))
                {
                    $script = 'video\Zip';
                }
                else if (FileHelper::is_text($_filePath))
                {
                    //$script = 'video\Txt';
                }
                elseif (FileHelper::is_xls($_filePath))
                {
                    //$script = 'video\Excel';
                }
                elseif (FileHelper::is_csv($_filePath))
                {
                    //$script = 'video\Excel';
                }
                elseif (FileHelper::is_video($_filePath))
                {
                    $script = 'video\Video';
                }
            }
            elseif ($categoryInfo['file_type'] == Category::FILE_TYPE_3D)
            {
                if (is_dir($_filePath))
                {
                    //$script = 'video\Dir';
                }
                elseif (FileHelper::is_zip($_filePath))
                {
                    $script = 'threed\Zip';
                }
                else if (FileHelper::is_text($_filePath))
                {
                    $script = 'threed\Txt';
                }
                elseif (FileHelper::is_xls($_filePath))
                {
                    $script = 'threed\Excel';
                }
                elseif (FileHelper::is_csv($_filePath))
                {
                    $script = 'threed\Excel';
                }
                elseif (FileHelper::is_video($_filePath))
                {
                    //$script = 'video\Video';
                }
            }
            $_logs['$script'] = $script;
            
            if (!$script)
            {
                //发站内通知
                Message::sendProjectUnpackFail($project['user_id'], $project['id'], 'message_project_unpack_fail_reason_unsupport');//数据文件解析失败, 暂不支持此类型的数据文件
                
                //反馈信息
                $unpackMessages = [];
                if (StringHelper::is_json($unpack->unpack_message))
                {
                    $unpackMessages = json_decode($unpack->unpack_message, true);
                }
                $unpackMessages[] = ['unpack_fail_type_not_supported' => []];
                
                $unpack->unpack_message = json_encode($unpackMessages);
                $unpack->updated_at = time();
                $unpack->save();
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' nothis type '.json_encode($_logs));
                continue;
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $parseStart '.json_encode($_logs));
            
            //分类允许执行的文件类型
            $fileExtensionString = trim($categoryInfo['file_extensions']);
            if (strpos($fileExtensionString, ','))
            {
                $fileExtensions = explode(',', $fileExtensionString);
            }
            else
            {
                $fileExtensions = [$fileExtensionString];
            }
            
            $params = [
                'filePath' => $_filePath,
                'defaultField' => $defaultField,
                'extensions' => $fileExtensions,
                'batchConfigs' => $batchConfigs,
                'video_as_frame' => $categoryInfo['video_as_frame']
            ];
            $result = call_user_func_array([__NAMESPACE__.'\\'.$script, 'run'], [$params]);
            
            $_logs['$result'] = $result;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' call_user_func_array ret '.json_encode($_logs));
            
            
            //反馈信息
            $unpackMessages = [];
            if (StringHelper::is_json($unpack->unpack_message))
            {
                $unpackMessages = json_decode($unpack->unpack_message, true);
            }
            if (!empty($result['message']) && is_array($result['message']))
            {
                $unpackMessages = array_merge($unpackMessages, $result['message']);
            }
            
            $unpack->unpack_message = json_encode($unpackMessages);
            $unpack->updated_at = time();
            $unpack->save();
            
            if ($result['error'])
            {
                //发站内通知
                Message::sendProjectUnpackFail($project['user_id'], $project['id'], $result['message']);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' call_user_func_array error '.json_encode($_logs));
                continue;
            }
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $parseEnd '.json_encode($_logs));
            
            $fileData = $result['data'];
            
            //按照目录
            if ($batchConfigs['assign_type'] == 1)
            {
                foreach ($fileData as $fileSource)
                {
                    if (empty($fileSource) || !is_array($fileSource))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileSource isnot array '.json_encode($_logs));
                        continue;
                    }
                    
                    $_logs['$fileSource'] = $fileSource;
                    $isAssign = false;
                    foreach ($batchConfigs['paths'] as $batchConfig)
                    {
                        //csv方式
                        if ($batchConfig == $_filePathRelative)
                        {
                            $batchData[$batchConfig][] = $fileSource;
                            $isAssign = true;
                            break;
                        }
                        //zip方式, 当$sourceFilePath前缀包含$batchConfig时, 说明属于一个批次
                        elseif (isset($fileSource[$defaultField]) && is_string($fileSource[$defaultField]) &&
                            strpos($fileSource[$defaultField], $batchConfig) !== false)
                        {
                            $batchData[$batchConfig][] = $fileSource;
                            $isAssign = true;
                            break;
                        }
                        //视频跟踪方式,值为一个数组
                        else if (isset($fileSource[$defaultField]) && is_array($fileSource[$defaultField]))
                        {
                            $batchData[$batchConfig][] = $fileSource;
                            $isAssign = true;
                            break;
                        }
                    }
                    
                    if (!$isAssign)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileSource not match '.json_encode($_logs));
                    }
                }
            }
            //按照数量
            elseif ($batchConfigs['assign_type'] == 2)
            {
                foreach ($fileData as $fileSource)
                {
                    if (empty($fileSource) || !is_array($fileSource))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileSource isnot array '.json_encode($_logs));
                        continue;
                    }
                    
                    foreach ($batchConfigs['batches'] as $i => $batch_)
                    {
                        if (empty($batch_['name']) || empty($batch_['count']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_assignData_batchesParamError '.json_encode($_logs));
                            continue;
                        }
                    
                        $batchKey = md5($batch_['name'].$batch_['count'].$i);
                        if (!isset($batchData[$batchKey]))
                        {
                            $batchData[$batchKey] = [];
                        }
                    
                        if (count($batchData[$batchKey]) < $batch_['count'])
                        {
                            $batchData[$batchKey][] = $fileSource;
                            break;
                        }
                    }
                }
            
            }
            //按照数量均分
            else
            {
                foreach ($fileData as $fileSource)
                {
                    if (empty($fileSource) || !is_array($fileSource))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileSource isnot array '.json_encode($_logs));
                        continue;
                    }
                    
                    $batchKey = $batchData ? count($batchData) : 1;
                    if (!isset($batchData[$batchKey]))
                    {
                        $batchData[$batchKey] = [];
                    }
                    
                    if (count($batchData[$batchKey]) < $batchConfigs['count'])
                    {
                        $batchData[$batchKey][] = $fileSource;
                    }
                    else
                    {
                        $batchData[$batchKey+1] = [];
                        $batchData[$batchKey+1][] = $fileSource;
                    }
                }
                
            }
            
            //更新进度
            $unpack->updateCounters(['unpack_progress' => $itemProgressValue]);
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file end '.json_encode($_logs));
        }
        
        //-----------------------------------------------------
        $_logs['$batchData.count'] = count($batchData);
        
        //更新进度
        $unpack->unpack_progress = 50;
        $unpack->updated_at = time();
        $unpack->save();
        
        //存储入库
        if ($batchData)
        {
            $progressValue = 40;
            $itemProgressValue = round($progressValue / count($batchData));
            
            foreach ($batchData as $batchPath => $data)
            {
                $_logs['$batchPath'] = $batchPath;
                $batch = Batch::find()->where(['project_id' => $project['id'], 'path' => StringHelper::html_encode($batchPath)])->limit(1)->asArray()->one();
                if (!$batch)
                {
                    //发站内通知
                    Message::sendProjectUnpackFail($project['user_id'], $project['id'], 'message_project_unpack_fail_reason_wrongpath');//数据文件解析失败, 批次路径无效
                    
                    //反馈信息
                    $unpackMessages = [];
                    if (StringHelper::is_json($unpack->unpack_message))
                    {
                        $unpackMessages = json_decode($unpack->unpack_message, true);
                    }
                    $unpackMessages[] = ['unpack_fail_batch_notfound' => []];
                    
                    $unpack->unpack_message = json_encode($unpackMessages);
                    $unpack->updated_at = time();
                    $unpack->save();
        
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batch notexist '.json_encode($_logs));
                    continue;
                }
        
                //批量导入任务子项表
                Data::batchInsert($project['id'], $batch['id'], $data);
        
                //更新任务数据量
                $addCount = count($data);
                if ($addCount)
                {
                    //更新统计数
                    $counters = ['upload_data_count' => $addCount, 'amount' => $addCount];
                    Batch::updateAllCounters($counters, ['id' => $batch['id']]);
                    
                    $counters = ['upload_data_count' => $addCount, 'data_count' => $addCount, 'amount' => $addCount];
                    Project::updateAllCounters($counters, ['id' => $project['id']]);
        
                    //设置开始时间
                    $attributes = [
                        'status' => Batch::STATUS_ENABLE,
                        'updated_at' => time()
                    ];
                    Batch::updateAll($attributes, ['id' => $batch['id']]);
                }
                
                //更新进度
                $unpack->updateCounters(['unpack_progress' => $itemProgressValue]);
            }
        }
        else
        {
            //发站内通知
            Message::sendProjectUnpackFail($project['user_id'], $project['id'], 'message_project_unpack_fail_reason_invaildfile');//解包失败，没有找到有效的文件
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $batchData empty '.serialize($_logs));
            return FormatHelper::result('', 'error', json_encode(['content'=>'site_unpack_fail_vallid_file_not_found']));
        }
        
        //更新进度
        $unpack->unpack_progress = 90;
        $unpack->updated_at = time();
        $unpack->save();
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result(1);
    }
}