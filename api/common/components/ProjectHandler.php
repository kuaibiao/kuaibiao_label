<?php
/**
 * 项目处理类
 * 
 * 
 */

namespace common\components;

use Yii;
use yii\base\Component;
use common\models\Project;
use common\models\Batch;
use common\models\StatResult;
use common\models\Task;
use common\models\Message;
use yii\helpers\Url;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;
use common\helpers\ArrayHelper;
use common\models\Setting;
use common\helpers\StringHelper;
use common\helpers\ImageHelper;
use common\helpers\LabelHelper;
use common\helpers\ZipHelper;
use common\helpers\FormatHelper;
use common\models\ProjectAttribute;

class ProjectHandler extends Component
{
    public static function showUrl($url_or_path) {
        
        return Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($url_or_path)], true);
        
    }
    
    /**
     *
     * 根据图片/语音地址返回数据
     * 若是http地址,直接返回http地址
     * 若是本地路径,返回图片/语音的二进制流并base64
     *
     * @param string $url_or_path
     * @param int $image_auto_rotate 自动调整旋转, 临时参数, 后期可去除
     */
    public static function getResource($url_or_path, $image_auto_rotate = true)
    {
        $_logs = ['$url_or_path' => $url_or_path];

        //如果是http
        if (StringHelper::is_url($url_or_path))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ishttp '.json_encode($_logs));

            $url = $url_or_path;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr ishttp '.json_encode($_logs));

            //URL链接中出现空格，则用%20替换，便于前端可正常加载显示
            if(strpos($url,' ') !== false){
                $url = str_replace(' ','%20',$url);
            }
            
            if (!FileHelper::netfile_exists($url))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url not exist '.json_encode($_logs));
                return [
                    'type' => 'text',
                    'content' => $url_or_path
                ];
            }

            //外部地址音频, web端无法读取波峰波谷(nginx设置跨域解决了此问题, 但是其他服务器外部链接, 前端还是无法获取波峰波谷)
            if (FileHelper::is_audio($url))
            {
//                 if (!FileHelper::netfile_exists($url))
//                 {
//                     Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_not_exist'), $url));

//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url not exist '.json_encode($_logs));
//                     return [
//                         'type' => 'error',
//                         'url' => Yii::$app->params['task_sourcenotfound']
//                     ];
//                 }

//                 //生成波形数据
//                 $audioWaveform = new AudioWaveform();
//                 $audioWaveform->setAudioWaveform(Yii::$app->params['audiowaveform_bin']);
//                 $audioWaveform->setInputFile($url);
//                 $waveformJsonData = $audioWaveform->getJsonData();
//                 if (!$waveformJsonData)
//                 {
//                     $_logs['$audioWaveform.error'] = $audioWaveform->getError();
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $audioWaveform.error '.json_encode($_logs));
//                 }

                //$base64 = 'data:' . $mime . ';base64,' . base64_encode($binary);
//                 return ['type' => 'audio','url' => $url,'waveform' => $waveformJsonData];
                return [
                    'type' => 'audio',
                    'url' => $url,
                ];
            }
            elseif (FileHelper::is_text($url))
            {
                $content = FileHelper::netfile_getcontent($url);
                $content = StringHelper::toutf8($content);

                return [
                    'type' => 'text',
                    //'url' => $url,
                    'content' => $content
                ];
            }
            elseif (FileHelper::is_image($url))
            {
                //获取图片旋转信息
                $rotate_orientation = ImageHelper::rotate_orientation($url);

                $size = FileHelper::remote_filesize($url);
                $_logs['$size'] = $size;

                return [
                    'type' 	=> 'image',
                    'url' 	=> $url,
                    'size' => $size,
                    'rotate_orientation'=>$rotate_orientation
                ];
            }
            else
            {
                //为支持vpn方式,不在服务器端判断远程文件是否存在
                return [
                    'type' => 'other',
                    'content' => $url,
                ];
            }
        }
        //如果是本地硬盘相对路径
        elseif (StringHelper::is_relativepath($url_or_path))
        {
            $resourceRoot = Setting::getResourceRootPath();

            //判断文件路径是否包含uploadfile根路径
            if (strpos($url_or_path, $resourceRoot) === false)
            {
                $realpath = $resourceRoot . '/'.$url_or_path;
            }
            else
            {
                $realpath = $url_or_path;
            }
            $_logs['$realpath'] = $realpath;

            //判断文件是否存在
            if (!FileHelper::file_exists($realpath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' path notexist '.json_encode($_logs));
                return [
                    'type' => 'text',
                    'content' => $url_or_path
                ];
            }

            //是图片
            if (FileHelper::is_image($realpath))
            {
                //图片容量大于设置值, 防止浏览器超时, 交给浏览器自行下载
                $size = FileHelper::filesize($realpath);
                $_logs['$size'] = $size;

                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' >1M '.json_encode($_logs));

                //获取图片旋转信息
                $rotate_orientation = ImageHelper::rotate_orientation($realpath);
                return [
                    'type' => 'image',
                    'url' => Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($url_or_path)], true),
                    'size' => $size,
                    'rotate_orientation'=>$rotate_orientation
                ];
            }
            else if (FileHelper::is_text($realpath))
            {
                $content = file_get_contents($realpath);
                $content = StringHelper::toutf8($content);

                return [
                    'type' => 'text',
                    'content' => $content
                ];
            }
            else if (FileHelper::is_audio($realpath))
            {
                //生成波形数据
//                 $audioWaveform = new AudioWaveform();
//                 $audioWaveform->setAudioWaveform(Yii::$app->params['audiowaveform_bin']);
//                 $audioWaveform->setInputFile($realpath);
//                 $waveformJsonData = $audioWaveform->getJsonData();
//                 if (!$waveformJsonData)
//                 {
//                     $_logs['$audioWaveform.error'] = $audioWaveform->getError();
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $audioWaveform.error '.json_encode($_logs));
//                 }

                //$base64 = 'data:' . $mime . ';base64,' . base64_encode($binary);
//                 return [
//                     'type' => 'audio',
//                     'url' => Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($realpath)], true),
//                     'waveform' => $waveformJsonData
//                 ];
                return [
                    'type' => 'video',
                    'url' => Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($url_or_path)], true),
                ];
            }
            else if (FileHelper::is_video($realpath))
            {
                return [
                    'type' => 'video',
                    'url' => Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($url_or_path)], true),
                ];
            }
            else if (FileHelper::is_zip($realpath))
            {
                return [
                    'type' => 'zip',
                    'url' => Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($url_or_path)], true),
                ];
            }
            else if (FileHelper::is_3d($realpath))
            {
                return [
                    'type' => 'zip',
                    'url' => Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($url_or_path)], true),
                ];
            }
            else
            {
                return [
                    'type' => 'other',
                    'url' => Url::toRoute(['site/download-private-file', 'file' => StringHelper::base64_encode($url_or_path)], true),
                ];
            }
        }
        else if (is_array($url_or_path))
        {
            //如果是3d点云切分
//                 foreach ($url_or_path as $item_)
//                 {
//                     if ($item_)
//                     {
//                         foreach ($item_ as $itemKey_ => $itemVal_)
//                         {
//                             if (StringHelper::is_url($value))
//                         }
//                     }
//                 }
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' 3d '.serialize($_logs));
            return [
                'type' => '3d',
                'urls' => $url_or_path,
            ];
        }
        else
        {
            //一个文本, 每行一个作业的情况
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' text '.serialize($_logs));
            return [
                'type' => 'text',
                'content' => $url_or_path,
            ];
        }
    }
    
    /**
     * 生成mark图
     * 
     * @param string $url_or_path
     * @param array $result
     * @param string $isShowLabel
     * @param bool $image_auto_rotate 自动调整旋转, 临时参数, 后期可去除
     */
    public static function showMark($url_or_path, $result, $isShowLabel = true, $isFilled = false, $image_auto_rotate = true)
    {
        $_logs = [];
        $_logs['$url_or_path'] = $url_or_path;
        $_logs['$result'] = $result;
        $_logs['$isShowLabel'] = $isShowLabel;
        $_logs['$isFilled'] = $isFilled;
        
        $resourceRoot = Setting::getResourceRootPath();
        
        $resultArr = JsonHelper::json_decode_all($result);
        
        $resultData = [];
        if (!empty($resultArr['data']))
        {
            $resultData = $resultArr['data'];
        }
        elseif (isset($resultArr['result']) && isset($resultArr['result']['data']))
        {
            $resultData = $resultArr['result']['data'];
        }
        else
        {
            $resultData = $resultArr;
        }
        
        //如果是http
        if (StringHelper::is_url($url_or_path))
        {
            $url = $url_or_path;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr ishttp '.json_encode($_logs));
        
            if (!FileHelper::netfile_exists($url))
            {
                Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_not_exist'), $url_or_path));
        
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url not exist '.json_encode($_logs));
                return Yii::$app->params['task_sourcenotfound'];
            }
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr ishttp0 '.json_encode($_logs));
        
            //是图片
            if (FileHelper::is_image($url))
            {
                $imageName = FileHelper::filebasename($url);
                $imageBinary = FileHelper::netfile_getcontent($url);
        
                //生成mark图
                $base64_image = ImageProcessor::image_mark_from_string($imageName, $imageBinary, $resultData, null, $isShowLabel, $isFilled);
                if (!$base64_image)
                {
                    Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_parse_fail'), $url_or_path));
        
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' image_mark_from_string fail '.json_encode($_logs));
                    return Yii::$app->params['task_sourcenotfound'];
                }
        
                //生成mark图
                return $base64_image;
            }
        }
        //如果是本地硬盘相对路径
        elseif (StringHelper::is_relativepath($url_or_path))
        {
        
            //判断文件路径是否包含uploadfile根路径
            if (strpos($url_or_path, $resourceRoot) === false)
            {
                $realpath = $resourceRoot . '/'.$url_or_path;
            }
            else
            {
                $realpath = $url_or_path;
            }
            $_logs['$realpath'] = $realpath;
        
            //判断文件是否存在
            if (!FileHelper::file_exists($realpath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_not_exist'), $url_or_path));
                return Yii::$app->params['task_sourcenotfound'];
            }
        
            //是图片
            if (FileHelper::is_image($realpath))
            {
                $imageName = FileHelper::filebasename($realpath);
                $imageBinary = ImageHelper::image_get_content($realpath, $image_auto_rotate);
        
                $base64_image = ImageProcessor::image_mark_from_string($imageName, $imageBinary, $resultData, null, $isShowLabel, $isFilled);
                if (!$base64_image)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' image_mark_from_string fail '.json_encode($_logs));
                    Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_parse_fail'), $url_or_path));
                    return Yii::$app->params['task_sourcenotfound'];
                }
        
                //生成mark图
                return $base64_image;
            }
        }
        
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other '.json_encode($_logs));
        return $url_or_path;
    }
    
    /**
     * 生成mask图
     * 
     * @param string $url_or_path
     * @param array $result
     * @param bool $image_auto_rotate 自动调整旋转, 临时参数, 后期可去除
     */
    public static function showMask($url_or_path, $result, $image_auto_rotate = true)
    {
        $_logs = [];
        $_logs['$url_or_path'] = $url_or_path;
        $_logs['$result'] = $result;
    
        $resourceRoot = Setting::getResourceRootPath();
        
        $resultArr = JsonHelper::json_decode_all($result);
        
        $resultData = [];
        if (!empty($resultArr['data']))
        {
            $resultData = $resultArr['data'];
        }
        elseif (isset($resultArr['result']) && isset($resultArr['result']['data']))
        {
            $resultData = $resultArr['result']['data'];
        }
        else
        {
            $resultData = $resultArr;
        }
    
        //如果是http
        if (StringHelper::is_url($url_or_path))
        {
            $url = $url_or_path;
            $_logs['$url'] = $url;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr ishttp '.json_encode($_logs));
        
            if (!FileHelper::netfile_exists($url))
            {
                Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_not_exist'), $url_or_path));
        
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url not exist '.json_encode($_logs));
                return Yii::$app->params['task_sourcenotfound'];
            }
        
            //是图片
            if (FileHelper::is_image($url))
            {
                $imageName = FileHelper::filebasename($url);
                $imageBinary = FileHelper::netfile_getcontent($url);
        
                //生成mark图
                $base64_image = ImageProcessor::image_mask_from_string($imageName, $imageBinary, $resultData);
                if (!$base64_image)
                {
                    Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_parse_fail'), $url_or_path));
        
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' image_mask_from_string fail '.json_encode($_logs));
                    return Yii::$app->params['task_sourcenotfound'];
                }
        
                //生成mark图
                return $base64_image;
            }
        }
        //如果是本地硬盘相对路径
        elseif (StringHelper::is_relativepath($url_or_path))
        {
            
            //判断文件路径是否包含uploadfile根路径
            if (strpos($url_or_path, $resourceRoot) === false)
            {
                $realpath = $resourceRoot . '/'.$url_or_path;
            }
            else
            {
                $realpath = $url_or_path;
            }
            $_logs['$realpath'] = $realpath;
        
            //判断文件是否存在
            if (!FileHelper::file_exists($realpath))
            {
                Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_not_exist'), $url_or_path));
        
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' path notexist '.json_encode($_logs));
                return Yii::$app->params['task_sourcenotfound'];
            }
        
            //是图片
            if (FileHelper::is_image($realpath))
            {
                $imageName = FileHelper::filebasename($realpath);
                $imageBinary = ImageHelper::image_get_content($realpath, $image_auto_rotate);
        
                //生成mark图
                $base64_image = ImageProcessor::image_mask_from_string($imageName, $imageBinary, $resultData);
                if (!$base64_image)
                {
                    Message::sendProjectResourceFail(sprintf(Yii::t('app', 'resource_parse_fail'), $url_or_path));
        
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' image_mask_from_string fail '.json_encode($_logs));
                    return Yii::$app->params['task_sourcenotfound'];
                }
        
                //生成mark图
                return $base64_image;
            }
        }
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other '.json_encode($_logs));
        return $url_or_path;
    }
    
    public static function formatLabelValue($val)
    {
        if (is_string($val))
        {
            return $val;
        }
    
        if (is_array($val))
        {
            $val_ = '';
            foreach ($val as $k => $v)
            {
                $val_ .= (empty($val_) ? '' : '_') . self::formatLabelValue($v);
            }
            $val = $val_;
        }
    
        return $val;
    }
    /**
     * 由于$vak数组中可能存在“_”,所有使用特殊的分隔符
     * @param $val
     * @return string
     */
    public static function formatLabelValueNew($val)
    {
        if (is_string($val))
        {
            return $val;
        }

        if (is_array($val))
        {
            $val_ = '';
            foreach ($val as $k => $v)
            {
                $val_ .= (empty($val_) ? '' : '@~@') . self::formatLabelValue($v);
            }
            $val = $val_;
        }

        return $val;
    }

    /**
     * 多维数组转化为一维数组
     * @param $val
     * @return array
     */
    public static function formatLabelValueToArray($val)
    {

        if(!is_array($val)){
            return [$val];
        }

        $result = [];

        array_map(function ($value) use (&$result) {
            if(is_array($value)){
                $result = array_merge($result, array_values($value));
            }else{
                $result[] = $value;
            }
        }, $val);

        return $result;

    }


    /**
     * 获取标注结果
     * @param array $result
     * @return array $resultArr
     */
    public static function fetchResult($result)
    {
        $resultArr = [];
        
        if (is_string($result))
        {
            $resultArr = JsonHelper::json_decode_all($result);
        }
        elseif (is_array($result))
        {
            $resultArr = $result;
        }
        
        //兼容大文件存储为文件的方式
        if (isset($resultArr['result']) && StringHelper::is_url($resultArr['result']))
        {
            $resultFileContent = FileHelper::netfile_getcontent($resultArr['result']);
            if ($resultFileContent)
            {
                $resultArr = JsonHelper::json_decode_all($resultFileContent);
            }
        }
        
        //处理3d点云索引文件
        elseif($resultArr && is_array($resultArr))
        {
            foreach($resultArr as $rkey => $rvalue)
            {
                if(isset($rvalue['indexs']) && StringHelper::is_url($rvalue['indexs']))
                {
                    $resultDataFileContent = FileHelper::netfile_getcontent($rvalue['indexs']);
                    if ($resultDataFileContent)
                    {
                        $resultArr[$rkey]['indexs'] = JsonHelper::json_decode_all($resultDataFileContent);
                    }
                }
        
            }
        }

        return $resultArr;
    }



    /**
     * 分析标注结果
     * 格式为
     * [
     *  {rect_count : 0, point_count : 1},
     *  {userid0 : {'add' : {'rect_count' : 1, 'point_count' : 3}}}
     * ]
     * 
     * @param array $resultInfo
     */
    public static function statResult($result)
    {
    	$_logs = [];

        $resultArr = self::fetchResult($result);

    	// $_logs['$resultArr'] = $resultArr;
    	$resultsToStat = [];
    	if (isset($resultArr['result']))
    	{
            if(isset($resultArr['result']['data']) && isset($resultArr['result']['info']) && is_array($resultArr['result']['data']) && is_array($resultArr['result']['info']))
            {
                $resultsToStat = array_merge($resultArr['result']['data'], $resultArr['result']['info']);
            }
            else if(isset($resultArr['result']['data']))
            {
        		$resultsToStat = $resultArr['result']['data'];
            }
            else if(isset($resultArr['result']['info']))
            {
                $resultsToStat = $resultArr['result']['info'];
            }
    	}
        else if(isset($resultArr['data']) && isset($resultArr['info']) && is_array($resultArr['data']) && is_array($resultArr['info']))
        {
            $resultsToStat = array_merge($resultArr['data'], $resultArr['info']);
        }
    	elseif (isset($resultArr['data']))
    	{
    		$resultsToStat = $resultArr['data'];
    	}
        elseif (isset($resultArr['info']))
        {
            $resultsToStat = $resultArr['info'];
        }
    	elseif ($resultArr)
    	{
    		$resultsToStat = $resultArr;
    	}
        // $_logs['$resultsToStat'] = $resultsToStat;
    	$_logs['$resultsToStat'] = ArrayHelper::var_desc($resultsToStat);

    	$statTask = [];
        $statUsers = [];
        $statIds = [];
        
        if ($resultsToStat && is_array($resultsToStat))
        {
            foreach ($resultsToStat as $i => $label)
            {
                $_logs['$i'] = $i;
                
                if (empty($label))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label empty '.json_encode($_logs));
                    continue;
                }
                
                $userId_ = 0;
                if (!empty($label['modifiedBy']))
                {
                    $userId_ = $label['modifiedBy'];
                    $action = StatResult::ACTION_EDIT;
                }
                elseif (!empty($label['mBy']))
                {
                    $userId_ = $label['mBy'];
                    $action = StatResult::ACTION_EDIT;
                }
                elseif (!empty($label['createBy']))
                {
                    $userId_ = $label['createBy'];
                    $action = StatResult::ACTION_ADD;
                }
                elseif (!empty($label['cBy']))
                {
                    $userId_ = $label['cBy'];
                    $action = StatResult::ACTION_ADD;
                }
                
                //骨骼点
                if (!empty($label['type']) && $label['type'] == 'bonepoint')
                {
                    if (!empty($label['nodes'][0]['mBy']))
                    {
                        $userId_ = $label['nodes'][0]['mBy'];
                        $action = StatResult::ACTION_EDIT;
                    }
                    elseif (!empty($label['nodes'][0]['cBy']))
                    {
                        $userId_ = $label['nodes'][0]['cBy'];
                        $action = StatResult::ACTION_ADD;
                    }
                }
                
                if (!$userId_)
                {
                    $_logs['$label'] = $label;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' label no user '.json_encode($_logs));
                    continue;
                }
                
                //只统计标注类结果; 因标注类结果id唯一, 审核填空判断类结果id不唯一
                if (!empty($label['id']) && !empty($resultArr['data']) && empty($resultArr['info']))
                {
                    $statIds[] = $label['id'];
                }
                
                //根据标注类型统计
                if(!empty($label['type']))
                {
                    switch($label['type'])
                    {
                        case 'voice_transcription':
                            if (empty($statTask[$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION]))
                            {
                                $statTask[$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] = 0;
                            }

                            if (isset($label['start']) && isset($label['end']))
                            {
                                $statTask[$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] += ($label['end'] - $label['start']);
                                $statUsers[$userId_][$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] += ($label['end'] - $label['start']);
                            }

                            //to do 语音总时长

                            if (empty($statTask[$action][StatResult::TYPE_AUDIO_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_AUDIO_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_AUDIO_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_AUDIO_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_AUDIO_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_AUDIO_COUNT] += 1;
                            break;
                        case 'video-segmentation':
                            if (empty($statTask[$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION]))
                            {
                                $statTask[$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] = 0;
                            }
                            if (empty($statTask[$action][StatResult::TYPE_MEDIA_DURATION]))
                            {
                                $statTask[$action][StatResult::TYPE_MEDIA_DURATION] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_MEDIA_DURATION]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_MEDIA_DURATION] = 0;
                            }

                            if (isset($label['length']))
                            {
                                $statTask[$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] += $label['length'];
                                $statUsers[$userId_][$action][StatResult::TYPE_MEDIA_EFFECTIVE_DURATION] += $label['length'];
                            }
                            if (isset($label['duration'])) //总时长不做累加
                            {
                                $statTask[$action][StatResult::TYPE_MEDIA_DURATION] = $label['duration'];
                                $statUsers[$userId_][$action][StatResult::TYPE_MEDIA_DURATION] = $label['duration'];
                            }

                            if (empty($statTask[$action][StatResult::TYPE_VIDEO_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_VIDEO_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_VIDEO_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_VIDEO_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_VIDEO_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_VIDEO_COUNT] += 1;
                            break;
                        case 'point':
                            if (empty($statTask[$action][StatResult::TYPE_POINT_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_POINT_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_POINT_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_POINT_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_POINT_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_POINT_COUNT] += 1;
                            break;
                        case 'line':
                            if (empty($statTask[$action][StatResult::TYPE_LINE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_LINE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_LINE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_LINE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_LINE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_LINE_COUNT] += 1;
                            break;
                        case 'rect':
                            if (empty($statTask[$action][StatResult::TYPE_RECT_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_RECT_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_RECT_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_RECT_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_RECT_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_RECT_COUNT] += 1;
                            break;
                        case 'bonepoint':
                            if (empty($statTask[$action][StatResult::TYPE_BONEPOINT_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_BONEPOINT_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_BONEPOINT_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_BONEPOINT_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_BONEPOINT_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_BONEPOINT_COUNT] += 1;
                            break;
                        case 'ellipse':
                            if (empty($statTask[$action][StatResult::TYPE_ELLIPSE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_ELLIPSE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_ELLIPSE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_ELLIPSE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_ELLIPSE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_ELLIPSE_COUNT] += 1;
                            break;
                        case 'polygon':
                            if (empty($statTask[$action][StatResult::TYPE_POLYGON_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_POLYGON_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_POLYGON_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_POLYGON_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_POLYGON_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_POLYGON_COUNT] += 1;
                            break;
                        case 'circler':
                            if (empty($statTask[$action][StatResult::TYPE_CIRCLE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_CIRCLE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_CIRCLE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_CIRCLE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_CIRCLE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_CIRCLE_COUNT] += 1;
                            break;
                        case 'closedcurve':
                            if (empty($statTask[$action][StatResult::TYPE_CLOSEDCURVE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_CLOSEDCURVE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_CLOSEDCURVE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_CLOSEDCURVE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_CLOSEDCURVE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_CLOSEDCURVE_COUNT] += 1;
                            break;
                        case 'cuboid':
                            if (empty($statTask[$action][StatResult::TYPE_CUBOID_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_CUBOID_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_CUBOID_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_CUBOID_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_CUBOID_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_CUBOID_COUNT] += 1;
                            break;
                        case 'pencilline':
                            if (empty($statTask[$action][StatResult::TYPE_PENLINE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_PENLINE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_PENLINE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_PENLINE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_PENLINE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_PENLINE_COUNT] += 1;
                            break;
                        case 'quadrangle':
                            if (empty($statTask[$action][StatResult::TYPE_QUADRANGLE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_QUADRANGLE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_QUADRANGLE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_QUADRANGLE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_QUADRANGLE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_QUADRANGLE_COUNT] += 1;
                            break;
                        case 'rectP':
                            if (empty($statTask[$action][StatResult::TYPE_RECT_POINT_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_RECT_POINT_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_RECT_POINT_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_RECT_POINT_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_RECT_POINT_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_RECT_POINT_COUNT] += 1;
                            break;
                        case 'rectS':
                            if (empty($statTask[$action][StatResult::TYPE_RECT_SEAL_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_RECT_SEAL_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_RECT_SEAL_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_RECT_SEAL_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_RECT_SEAL_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_RECT_SEAL_COUNT] += 1;
                            break;
                        case 'splinecurve':
                            if (empty($statTask[$action][StatResult::TYPE_SPLINECURVE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_SPLINECURVE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_SPLINECURVE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_SPLINECURVE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_SPLINECURVE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_SPLINECURVE_COUNT] += 1;
                            break;
                        case 'trapezoid':
                            if (empty($statTask[$action][StatResult::TYPE_TRAPEZOID_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_TRAPEZOID_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_TRAPEZOID_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_TRAPEZOID_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_TRAPEZOID_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_TRAPEZOID_COUNT] += 1;
                            break;
                        case 'triangle':
                            if (empty($statTask[$action][StatResult::TYPE_TRIANGLE_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_TRIANGLE_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_TRIANGLE_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_TRIANGLE_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_TRIANGLE_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_TRIANGLE_COUNT] += 1;
                            break;
                        case 'unclosedpolygon':
                            if (empty($statTask[$action][StatResult::TYPE_UNCLOSEDPOLYGON_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_UNCLOSEDPOLYGON_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_UNCLOSEDPOLYGON_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_UNCLOSEDPOLYGON_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_UNCLOSEDPOLYGON_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_UNCLOSEDPOLYGON_COUNT] += 1;
                            break;
                        case 'd3d_cube':
                        // case 'pcl_segment':
                            if (empty($statTask[$action][StatResult::TYPE_3D_CLOUDPOINT_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_3D_CLOUDPOINT_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_3D_CLOUDPOINT_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_3D_CLOUDPOINT_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_3D_CLOUDPOINT_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_3D_CLOUDPOINT_COUNT] += 1;

                            //2D点云框数
                            if (empty($statTask[$action][StatResult::TYPE_2D_CLOUDPOINT_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_2D_CLOUDPOINT_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_2D_CLOUDPOINT_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_2D_CLOUDPOINT_COUNT] = 0;
                            }

                            if(isset($label['cubeMap']))
                            {
                                $cubepointsCount = count(array_column($label['cubeMap'], 'cubePoints'));
                                $bbox = count(array_column($label['cubeMap'], 'bbox'));
                                $statTask[$action][StatResult::TYPE_2D_CLOUDPOINT_COUNT] += ($cubepointsCount + $bbox);
                                $statUsers[$userId_][$action][StatResult::TYPE_2D_CLOUDPOINT_COUNT] += ($cubepointsCount + $bbox);
                            }
                            break;
                        case 'text-annotation':
                            if (empty($statTask[$action][StatResult::TYPE_TEXT_WORD_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_TEXT_WORD_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_TEXT_WORD_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_TEXT_WORD_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_TEXT_WORD_COUNT] += ($label['end'] - $label['start']);
                            $statUsers[$userId_][$action][StatResult::TYPE_TEXT_WORD_COUNT] += ($label['end'] - $label['start']);

                            //to do 原文本字符数

                            if (empty($statTask[$action][StatResult::TYPE_TEXT_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_TEXT_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_TEXT_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_TEXT_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_TEXT_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_TEXT_COUNT] += 1;
                            break;

                        case 'data-is-valid':
                            if($label['value'] == 'Yes')
                            {
                                if (empty($statTask[$action][StatResult::TYPE_LABEL_YES_COUNT]))
                                {
                                    $statTask[$action][StatResult::TYPE_LABEL_YES_COUNT] = 0;
                                }
                                if (empty($statUsers[$userId_][$action][StatResult::TYPE_LABEL_YES_COUNT]))
                                {
                                    $statUsers[$userId_][$action][StatResult::TYPE_LABEL_YES_COUNT] = 0;
                                }

                                $statTask[$action][StatResult::TYPE_LABEL_YES_COUNT] += 1;
                                $statUsers[$userId_][$action][StatResult::TYPE_LABEL_YES_COUNT] += 1;
                            }
                            else if($label['value'] == 'No')
                            {
                                if (empty($statTask[$action][StatResult::TYPE_LABEL_NO_COUNT]))
                                {
                                    $statTask[$action][StatResult::TYPE_LABEL_NO_COUNT] = 0;
                                }
                                if (empty($statUsers[$userId_][$action][StatResult::TYPE_LABEL_NO_COUNT]))
                                {
                                    $statUsers[$userId_][$action][StatResult::TYPE_LABEL_NO_COUNT] = 0;
                                }

                                $statTask[$action][StatResult::TYPE_LABEL_NO_COUNT] += 1;
                                $statUsers[$userId_][$action][StatResult::TYPE_LABEL_NO_COUNT] += 1;
                            }
                            else if($label['value'] == 'Unknown')
                            {
                                if (empty($statTask[$action][StatResult::TYPE_LABEL_UNKNOWN_COUNT]))
                                {
                                    $statTask[$action][StatResult::TYPE_LABEL_UNKNOWN_COUNT] = 0;
                                }
                                if (empty($statUsers[$userId_][$action][StatResult::TYPE_LABEL_UNKNOWN_COUNT]))
                                {
                                    $statUsers[$userId_][$action][StatResult::TYPE_LABEL_UNKNOWN_COUNT] = 0;
                                }

                                $statTask[$action][StatResult::TYPE_LABEL_UNKNOWN_COUNT] += 1;
                                $statUsers[$userId_][$action][StatResult::TYPE_LABEL_UNKNOWN_COUNT] += 1;
                            }

                            if (empty($statTask[$action][StatResult::TYPE_FORM_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_FORM_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_FORM_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_FORM_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_FORM_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_FORM_COUNT] += 1;
                            break;

                        // case 'data-is-valid':
                        case 'form-checkbox':
                        case 'form-radio':
                        case 'form-select':
                        case 'multi-input':
                        case 'single-input':
                            if (empty($statTask[$action][StatResult::TYPE_FORM_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_FORM_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_FORM_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_FORM_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_FORM_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_FORM_COUNT] += 1;
                            break;
                        
                        default:
                            if (empty($statTask[$action][StatResult::TYPE_UNKNOWN_COUNT]))
                            {
                                $statTask[$action][StatResult::TYPE_UNKNOWN_COUNT] = 0;
                            }
                            if (empty($statUsers[$userId_][$action][StatResult::TYPE_UNKNOWN_COUNT]))
                            {
                                $statUsers[$userId_][$action][StatResult::TYPE_UNKNOWN_COUNT] = 0;
                            }

                            $statTask[$action][StatResult::TYPE_UNKNOWN_COUNT] += 1;
                            $statUsers[$userId_][$action][StatResult::TYPE_UNKNOWN_COUNT] += 1;
                    }
                }

            }
        }
    	
    	$_logs['$statTask'] = $statTask;
    	$_logs['$statUser'] = $statUsers;
    	$_logs['$statIds'] = $statIds;
    	
    	Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
    	return [$statTask, $statUsers, $statIds];
    }
    
    public static function fetchLabelFromTemplate($configs)
    {
        $labels = [];
        
        if (JsonHelper::is_json($configs))
        {
            $configs = JsonHelper::json_decode_all($configs);
        }
        
        if ($configs && is_array($configs))
        {
            foreach ($configs as $k => $v)
            {
                if (is_array($v))
                {
                    $labels_ = self::fetchLabelFromTemplate($v);
                    if ($labels_)
                    {
                        $labels = array_merge($labels, $labels_);
                    }
                }
                else if ($k == 'type' && $v == 'tag')
                {
                    if (!empty($configs['data']) && is_array($configs['data']))
                    {
                        foreach ($configs['data'] as $k1 => $v1)
                        {
                            $labels[] = $v1['text'];
                        }
                    }
                }
            }
        }
        
        return $labels;
    }
    
    public static function fetchLabelTypeFromTemplate($configs)
    {
        $labels = [];

        if (JsonHelper::is_json($configs))
        {
            $configs = JsonHelper::json_decode_all($configs);
        }
    
        if ($configs && is_array($configs))
        {
            foreach ($configs as $k => $v)
            {
                if ($k === 'supportShapeType')
                {
                    $labels = array_merge($labels, $v);
                }
                else if (is_array($v))
                {
                    $labels_ = self::fetchLabelTypeFromTemplate($v);
                    if ($labels_)
                    {
                        $labels = array_merge($labels, $labels_);
                    }
                }
            }
        }

        $labels = array_unique($labels);

        return $labels;
    }

    public static function templateHasForm($configs)
    {
        $hasForm = false;

        if (JsonHelper::is_json($configs))
        {
            $configs = JsonHelper::json_decode_all($configs);
        }

        if ($configs && is_array($configs))
        {
            foreach ($configs as $k => $v)
            {
                if (is_array($v))
                {
                    $hasForm = self::templateHasForm($v);
                    if($hasForm)
                    {
                        break;
                    }
                }
                else if(is_string($v) && in_array($v, ['data-is-valid', 'form-checkbox', 'form-radio', 'form-select', 'multi-input', 'single-input']))
                {
                    $hasForm = true;
                    break;
                }
            }
        }

        return $hasForm;
    }
    
    public static function correctRate($results)
    {
        $_logs = [];
        $correctRate = 0;
        $correctResult = null;
        
        if (count($results) > 1)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $results.count > 1 '.json_encode($_logs));
            
            //所有的组合
            $groups = [];
            $resultKeys = array_keys($results);
            foreach ($resultKeys as $resultKey0)
            {
                foreach ($resultKeys as $resultKey1)
                {
                    if ($resultKey0 != $resultKey1)
                    {
                        if (!in_array([$resultKey0, $resultKey1], $groups) && !in_array([$resultKey1, $resultKey0], $groups))
                        {
                            $groups[] = [$resultKey1, $resultKey0];
                        }
                    }
                }
            }
            $_logs['$groups'] = $groups;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $groups '.json_encode($_logs));
            
            $groupRates = [];
            foreach ($groups as $groupKey => $group)
            {
                $workKeys = [];
                $workRates = [];
                
                //-------------------------------------
                
                $result0 = $results[$group[0]];
                
                $resultArr0 = JsonHelper::json_decode_all($result0);
                if (!empty($resultArr0['data']))
                {
                    $resultData0 = $resultArr0['data'];
                }
                elseif (!empty($resultArr0['info']))
                {
                    $resultData0 = $resultArr0['info'];
                }
                else 
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $resultArr0.data empty '.json_encode($_logs));
                    $resultData0 = [];
                }
                
                //----------------------------------
                
                $result1 = $results[$group[1]];
                
                $resultArr1 = JsonHelper::json_decode_all($result1);
                if (!empty($resultArr1['data']))
                {
                    $resultData1 = $resultArr1['data'];
                }
                elseif (!empty($resultArr1['info']))
                {
                    $resultData1 = $resultArr1['info'];
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $resultArr1.data empty '.json_encode($_logs));
                    $resultData1 = [];
                }
                
                
                //----------------------------------
                
                
                
                //规则:元素数量比较
                if (count($resultData0) != count($resultData1))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' resultdata count error '.json_encode($_logs));
                    if (count($resultData0) < count($resultData1))
                    {
                        $tmp = $resultData0;
                        $resultData0 = $resultData1;
                        $resultData1 = $tmp;
                    }
                }
                
                //----------------------------------
                
                if (empty($resultData0) && empty($resultData1))
                {
                    $groupRates[$groupKey] = 100;
                }
                else
                {
                    //遍历数据中的每一项
                    foreach ($resultData0 as $key0 => $item0)
                    {
                        $_logs['$item0'] = $item0;
                    
                        if (empty($item0['type']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result no data '.json_encode($_logs));
                    
                            continue;
                        }
                    
                        if (in_array($item0['type'], ['text_analysis', 'text-annotation']))
                        {
                            $itemRates = [];
                    
                            //遍历数据中的每一项
                            foreach ($resultData1 as $key1 => $item1)
                            {
                                $_logs['$item1'] = $item1;
                                $itemRate = 0;
                                if (empty($item1['type']))
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result no data '.json_encode($_logs));
                                    continue;
                                }
                    
                                if (in_array($item1['type'], ['text_analysis', 'text-annotation']))
                                {
                                    if (!isset($item0['start']) || !isset($item1['start']))
                                    {
                                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no start '.json_encode($_logs));
                                        continue;
                                    }
                    
                                    if (!isset($item0['end']) || !isset($item1['end']))
                                    {
                                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no end '.json_encode($_logs));
                                        continue;
                                    }
                    
                                    if ($item0['start'] != $item1['start'] || $item0['end'] != $item1['end'])
                                    {
                                        continue;
                                    }
                                    
                                    $itemRate += 25;
                    
                                    //挨个比较值
                                    if (isset($item0['text']) && isset($item1['text']) && $item0['text'] == $item1['text'])
                                    {
                                        $itemRate += 25;
                                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' match text '.json_encode($_logs));
                                    }
                    
                                    if (isset($item0['label']) && isset($item1['label']) && json_encode($item0['label']) == json_encode($item1['label']))
                                    {
                                        $itemRate += 25;
                                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' match label '.json_encode($_logs));
                                    }
                    
                                    if (isset($item0['attr']) && isset($item1['attr']) && json_encode($item0['attr']) == json_encode($item1['attr']))
                                    {
                                        $itemRate += 25;
                                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' match attr '.json_encode($_logs));
                                    }
                                }
                    
                                $itemRates[$key1] = $itemRate;
                            }
                    
                            $_logs['$itemRates'] = $itemRates;
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $itemRates '.json_encode($_logs));
                    
                            //按照得分倒序
                            arsort($itemRates);
                            $workKeys[$key0] = key($itemRates);
                            $workRates[$key0] = current($itemRates);
                            $_logs['$itemRates'] = $itemRates;
                            $_logs['$workKeys'] = $workKeys;
                            $_logs['$workRates'] = $workRates;
                    
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $itemRates1 '.json_encode($_logs));
                        }
                        elseif (in_array($item0['type'], ['single-input', 'multi-input', 'form-radio', 'form-checkbox', 'form-select']))
                        {
                            $itemRates = [];
                            foreach ($resultData1 as $key1 => $item1)
                            {
                                $itemRate = 0;
                                if (empty($item1['type']))
                                {
                                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result no data '.json_encode($_logs));
                                    continue;
                                }
                    
                                if (in_array($item1['type'], ['single-input', 'multi-input', 'form-radio', 'form-checkbox', 'form-select']))
                                {
                                    //挨个比较值
                                    if (!isset($item0['header']) || !isset($item1['header']))
                                    {
                                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no header '.json_encode($_logs));
                                        continue;
                                    }
                    
                                    if ($item0['header'] != $item1['header'])
                                    {
                                        continue;
                                    }
                    
                                    if (isset($item0['value']) && isset($item1['value']) && json_encode($item0['value']) == json_encode($item1['value']))
                                    {
                                        $itemRate += 100;
                                    }
                                }
                    
                                $itemRates[$key1] = $itemRate;
                            }
                    
                            $_logs['$itemRates'] = $itemRates;
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $itemRates '.json_encode($_logs));
                    
                            //按照得分倒序
                            arsort($itemRates);
                            $workKeys[$key0] = key($itemRates);
                            $workRates[$key0] = current($itemRates);
                        }
                        else
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result no data '.json_encode($_logs));
                            continue;
                        }
                    }
                    
                    //-------------------------------------
                    $_logs['$workRates'] = $workRates;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workRates '.json_encode($_logs));
                    
                    if ($workRates)
                    {
                        $workRateSum = array_sum($workRates);
                        $workRateCount = count($workRates);
                        $groupRates[$groupKey] = floor( $workRateSum / $workRateCount);
                        $_logs['$workRateSum'] = $workRateSum;
                        $_logs['$workRateCount'] = $workRateCount;
                        $_logs['$groupRates.$groupKey'] = $groupRates[$groupKey];
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workRates '.json_encode($_logs));
                    }
                    else
                    {
                        $groupRates[$groupKey] = 0;
                    }
                }
            }
            
            $_logs['$groupRates'] = $groupRates;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $groupRates '.json_encode($_logs));
            
            if ($groupRates)
            {
                //按照得分倒序
                arsort($groupRates);
                $_logs['$groupRates'] = $groupRates;
                
                $sumRate = array_sum($groupRates);
                $countRate = count($groupRates);
                $correctRate = floor( $sumRate / $countRate);
                $_logs['$sumRate'] = $sumRate;
                $_logs['$countRate'] = $countRate;
                $_logs['$correctRate'] = $correctRate;
                
                $isLoad = false;
                
                $tmpGroup = [];
                foreach ($groupRates as $groupKey => $rate_)
                {
                    $_logs['$groupKey'] = $groupKey;
                    
                    $group_ = [$groups[$groupKey][0], $groups[$groupKey][1]];
                    $_logs['$group_'] = $group_;
                    if (!$isLoad)
                    {
                        $isLoad = true;
                        $tmpGroup = $group_;
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no correctresult,array_merge '.json_encode($_logs));
                    }
                    else 
                    {
                        $correctKeys_ = array_intersect($tmpGroup, $group_);
                        $_logs['$correctKeys_'] = $correctKeys_;
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' has correctresult '.json_encode($_logs));
                        if ($correctKeys_)
                        {
                            $correctKey = array_shift($correctKeys_);
                            $_logs['$correctKey'] = $correctKey;
                            $correctResult = $results[$correctKey];//current($correctResults_);
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' has correctresult '.json_encode($_logs));
                            break;
                        }
                    }
                }
                $_logs['$tmpGroup'] = $tmpGroup;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $tmpGroup '.json_encode($_logs));
                if (!$correctResult)
                {
                    if ($tmpGroup)
                    {
                        $correctKey = array_shift($tmpGroup);
                        $correctResult = $results[$correctKey];
                    }
                    else 
                    {
                        $correctResult = current($results);
                    }
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $correctResults current '.json_encode($_logs));
                }
            }
            else
            {
                $correctRate = 0;
                reset($results);
                $correctResult = current($results);
            }
        }
        else 
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $results.count = 1 '.json_encode($_logs));
            
            $correctRate = 100;
            reset($results);
            $correctResult = current($results);
        }
        
        //为了区分开是否已执行, 必须不能返回0
        if ($correctRate < 1)
        {
            $correctRate = 1;
        }
        
        $_logs['$correctRate'] = $correctRate;
        //$_logs['$correctResult'] = $correctResult;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return [$correctRate, $correctResult];
    }
    
    /**
     * 
     * 对标注的图形按照面积进行排序
     * 
     * @param array $graphData
     * @return multitype:
     */
    
    public static function sortLabelGraph($graphData)
    {
        //对图形按照面积大小排序
        $graphDataSort = [];
        foreach ($graphData as $k => $v)
        {
            if (isset($v['type']) && in_array($v['type'], ['polygon', 'rect', 'triangle']))
            {
                $sort = LabelHelper::polygon_area($v['points']);
            }
            else
            {
                $sort = - count($graphDataSort);
            }
        
            $graphDataSort[] = $sort;
        }
        //$_logs['$graphDataSort'] = $graphDataSort;
        $graphData = array_combine($graphDataSort, $graphData);
        krsort($graphData);
        //$_logs['$graphData.1'] = $graphData;
        
        return $graphData;
    }

    public static function getDataStruct($userId = 0, $projectId = 0)
    {
        $_logs = ['$userId' => $userId, '$projectId' => $projectId];

        //获取项目文件目录
        $uploadFilePath = Setting::getUploadfilePath($userId, $projectId);
        $uploadfileRelativePath = Setting::getUploadfileRelativePath($userId, $projectId);
        $_logs['$uploadFilePath'] = $uploadFilePath;

        //上传的数据文件
        $uploadFiles = FileHelper::get_dir_stat($uploadFilePath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
        $_logs['$uploadFiles'] = $uploadFiles;

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $uploadFiles '. json_encode($_logs));

        $datafiles = [];
        if ($uploadFiles)
        {
            foreach ($uploadFiles as $_file)
            {
                $_logs['$_file'] = $_file;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each file '.json_encode($_logs));

                $_fileBasename = FileHelper::filebasename(rtrim($_file['path'], '/'));
                $_filePath = $uploadFilePath . '/'. $_file['path'];
                $_filePathRelative = $uploadfileRelativePath . '/'. $_file['path'];//文件的相对路径

                $_logs['$_fileBasename'] = $_fileBasename;
                $_logs['$_filePath'] = $_filePath;
                $_logs['$_filePathRelative'] = $_filePathRelative;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each file '.json_encode($_logs));

                if (is_dir($_filePath))
                {
                    $_logs['filetype'] = 'is_dir';
                    
                    $datafiles[] = FileHelper::get_dir_struct($_filePath, $uploadfileRelativePath, Yii::$app->params['task_source_ignorefiles']);
                }
                elseif (FileHelper::is_zip($_filePath))
                {
                    $_logs['filetype'] = 'is_zip';

                    $zipStructs = ZipHelper::get_zip_struct_linux($_filePath, $uploadfileRelativePath, Yii::$app->params['task_source_ignorefiles']);
                    if (!$zipStructs)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get_zip_struct error '.json_encode($_logs));
                        break;
                    }

                    foreach ($zipStructs as $struct)
                    {
                        $datafiles[] = FormatHelper::format_zip_struct($struct);
                    }
                }
                else if (FileHelper::is_text($_filePath))
                {
                    $_logs['filetype'] = 'is_text';

                    $list = FileHelper::file_readcontent($_filePath);
                    $size = FileHelper::file_size($_filePath);
                    $sizeFormat = FileHelper::file_size($_filePath, true);

                    $datafiles[] = [
                        'count' => count($list),
                        'size'  => $sizeFormat,
                        'size_number'  => $size,
                        'name'  => $_fileBasename,
                        'path'  => StringHelper::base64_encode($_filePathRelative),
                        'children' => [],
                    ];
                }
                elseif (FileHelper::is_csv($_filePath))
                {
                    $_logs['filetype'] = 'is_csv';

                    $list = FileHelper::file_readcontent($_filePath);
                    $size = FileHelper::file_size($_filePath);
                    $sizeFormat = FileHelper::file_size($_filePath, true);

                    $datafiles[] = [
                        'count' => count($list),
                        'size'  => $sizeFormat,
                        'size_number'  => $size,
                        'name'  => $_fileBasename,
                        'path'  => StringHelper::base64_encode($_filePathRelative),
                        'children' => [],
                    ];
                }
                elseif (FileHelper::is_xls($_filePath))
                {
                    $_logs['filetype'] = 'is_xls';

                    $list = FileHelper::file_readcontent($_filePath);
                    $size = FileHelper::file_size($_filePath);
                    $sizeFormat = FileHelper::file_size($_filePath, true);

                    $datafiles[] = [
                        'count' => count($list),
                        'size'  => $sizeFormat,
                        'size_number'  => $size,
                        'name'  => $_fileBasename,
                        'path'  => StringHelper::base64_encode($_filePathRelative),
                        'children' => [],
                    ];
                }
                elseif (FileHelper::is_video($_filePath))
                {
                    $_logs['filetype'] = 'is_video';

                    //$datafiles[] = FileHelper::get_dir_struct($_filePath, $project['user_id'].'/'.$projectId.'/', Yii::$app->params['task_source_ignorefiles']);
                    $size = FileHelper::file_size($_filePath);
                    $sizeFormat = FileHelper::file_size($_filePath, true);

                    $datafiles[] = [
                        'count' => 1,
                        'size'  => $sizeFormat,
                        'size_number'  => $size,
                        'name'  => $_fileBasename,
                        'path'  => StringHelper::base64_encode($_filePathRelative),
                        'children' => [],
                    ];
                }
                else
                {
                    $_logs['filetype'] = 'other';
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));

                    $size = FileHelper::file_size($_filePath);
                    $sizeFormat = FileHelper::file_size($_filePath, true);

                    $datafiles[] = [
                        'count' => 1,
                        'size'  => $sizeFormat,
                        'size_number'  => $size,
                        'name'  => $_fileBasename,
                        'path'  => StringHelper::base64_encode($_filePathRelative),
                        'children' => [],
                    ];

                }
            }
        }

        return $datafiles;
    }
    
    
    /**
     * 采用队列的形式, 解决排序的问题, 每60秒更新一次
     * 
     * @param number $teamId 指定团队
     * @param number $categoryIds 筛选分类
     * @param number $lastUpdateTime 用于翻页, 配合$lastBatchId使用
     * @param number $lastBatchId 用于翻页,配合$lastUpdateTime使用
     * @param number $page  用于翻页, 表第几页;在未传$lastUpdateTime时生效, 配合limit使用
     * @param number $limit 返回多少行数据
     * @param number $keyword 关键词搜索
     * @param number $lockTimes
     * @return boolean|boolean
     */
    private static function tasklist($teamId, $categoryIds = 0, $lastUpdateTime = 0, $lastBatchId = 0, $page = 1, $limit = 5, $keyword = '', $lockTimes = 0)
    {
        $cacheKey = 'project:tasklist:'.$teamId;
        $categoryIds && ($cacheKey .= '.'. implode(',', $categoryIds));
        $lastUpdateTime && ($cacheKey .= '.'. $lastUpdateTime);
        $lastBatchId && ($cacheKey .= '.'. $lastBatchId);
        $page && $cacheKey .= '.'.$page;
        $limit && $cacheKey .= '.'.$limit;
        $keyword && $cacheKey .= '.'.$keyword;
        
        $cacheKey = Yii::$app->redis->buildKey($cacheKey);
        $_logs['$cacheKey'] = $cacheKey;
        
        if (!Yii::$app->redis->exists($cacheKey))
        {
            //并发锁, 防止N多人同时第一次点击执行任务
            $lockKey = $cacheKey.'lock';
            $_logs['$lockKey'] = $lockKey;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $lockKey '.json_encode($_logs));
            
            //成功返回 1,失败返回0
            if (!Yii::$app->redis->setnx($lockKey, 1))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locked '.json_encode($_logs));
                if ($lockTimes > 5)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locked, locktimes > 5 '.json_encode($_logs));
                    return false;
                }
                sleep(1);
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sleep 1 '.json_encode($_logs));
                
                return self::tasklist($teamId, $categoryIds, $lastUpdateTime, $lastBatchId, $page, $limit, $keyword, $lockTimes + 1);
            }
            Yii::$app->redis->expire($lockKey, 5);
            
            $query = Task::find()->select(['id'])->where(['team_id' => $teamId, 'status' => Task::STATUS_NORMAL]);
            if($keyword){
                $query->andWhere(['or', 
                    ['like', 'id', $keyword],
                    ['like', 'name', $keyword]
                ]);
            }
            $effectiveTaskIds = $query->asArray()->column();
            
            $categoryIds && $query->andWhere(['in', 'category_id', $categoryIds]);
            
            $query = Project::find()
            ->select(['id'])
            ->where(['team_id' => $teamId, 'status' => Project::STATUS_WORKING]);
            
            $categoryIds && $query->andWhere(['in', 'category_id', $categoryIds]);
            $effectiveProjectIds = $query->asArray()->column();
            
            //获取批次列表
            $query = Batch::find()
            ->select(['id', 'updated_at'])
            ->where(['in', 'project_id', $effectiveProjectIds])
            ->andWhere(['status' => Batch::STATUS_ENABLE]);
            $list = $query->orderBy(['updated_at' => SORT_DESC, 'id' => SORT_DESC])->asArray()->all();
            if ($list)
            {
                foreach ($list as $v)
                {
                    $score = $v['updated_at'] .'.'. $v['id'];
                    Yii::$app->redis->zadd($cacheKey, $score, $v['id']);
                }
            }
            
            //设定缓存失效时间
            Yii::$app->redis->expire($cacheKey, Yii::$app->params['team_worklist_cachetime']);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' init succ '.json_encode($_logs));
        }
        
        $count = Yii::$app->redis->zcard($cacheKey);
        $batchIds = [];
        if ($count > 1)
        {
            if ($lastUpdateTime)
            {
                //获取后$limit个
                $score = $lastUpdateTime .'.'. $lastBatchId;
                $batchIds = Yii::$app->redis->zrevrangebyscore($cacheKey, $score, 0, 'LIMIT', 1, $limit);
                $_logs['$score'] = $score;
                $_logs['$limit'] = $limit;
                $_logs['$batchIds'] = $batchIds;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' zrevrange '.json_encode($_logs));
            }
            else
            {
                $start = ($page - 1) * $limit;
                $stop = $start + $limit;
                $batchIds = Yii::$app->redis->zrevrange($cacheKey, $start, $stop);
                $_logs['$start'] = $start;
                $_logs['$stop'] = $stop;
                $_logs['$batchIds'] = $batchIds;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' zrevrange '.json_encode($_logs));
            }
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return [$count, $batchIds];
    }
    
    
}