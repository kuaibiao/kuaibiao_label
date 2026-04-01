<?php 
namespace common\components\importHandler\label\audio;

use Yii;
use yii\base\Component;
// use common\components\Functions;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
/**
 * 
 * excel处理
 * 
 *
 */

class Excel extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['filePath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' filePath empty '.json_encode($_logs));
            
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
        
        //读取文件内容
        $fileList = FileHelper::file_readcontent($filePath);
        if (!$fileList)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data_datafile_fileempty '.json_encode($_logs));
            
            $checkMessages = ['label_unpack_error_data_datafile_fileempty' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fileList '.json_encode($fileList));
        
        $fileCount = count($fileList);
        $fileData = [];
        $checkFailCount = 0;
        $checkMessages = ['label_unpack_succ_tip' => []];
        foreach ($fileList as $source_urls)
        {
            $_logs['$source_urls'] = $source_urls;
            
            //自带结果格式
            if (count($source_urls) > 1 && !empty($source_urls['ai_result']))
            {
                $_logs['$ai_result'] = $source_urls['ai_result'];
                
                if (StringHelper::is_url($source_urls['ai_result']))
                {
                    $source_result_json = FileHelper::netfile_getcontent($source_urls['ai_result']);
                }
                //相对路径
                elseif (StringHelper::is_relativepath($source_urls['ai_result']))
                {
                    $source_result_json = file_get_contents($source_urls['ai_result']);
                }
                else
                {
                    $source_result_json = $source_urls['ai_result'];
                }
                $source_result_json = StringHelper::toutf8($source_result_json);
                //$_logs['$source_result_json'] = $source_result_json;
                
                if (empty($source_result_json))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $source_result_json empty '.json_encode($_logs));
                    continue;
                }
                if (!JsonHelper::is_json($source_result_json))
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
                
                $source_urls['ai_result'] = $source_result_json;
                $_logs['$source_urls'] = $source_urls;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ai_result succ '.json_encode($_logs));
            }
            //针对科大讯飞结果格式
            elseif (count($source_urls) > 1 && !empty($source_urls['ai_result_ifly']))
            {
                $_logs['$ai_result_ifly'] = $source_urls['ai_result_ifly'];
                
                if (StringHelper::is_url($source_urls['ai_result_ifly']))
                {
                    $source_result_json = FileHelper::netfile_getcontent($source_urls['ai_result_ifly']);
                }
                //相对路径
                elseif (StringHelper::is_relativepath($source_urls['ai_result_ifly']))
                {
                    $source_result_json = file_get_contents($source_urls['ai_result_ifly']);
                }
                else 
                {
                    $source_result_json = $source_urls['ai_result_ifly'];
                }
                $source_result_json = StringHelper::toutf8($source_result_json);
                //$_logs['$source_result_json'] = $source_result_json;
                
                if (empty($source_result_json))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $source_result_json empty '.json_encode($_logs));
                    continue;
                }
                if (!JsonHelper::is_json($source_result_json))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $source_result_json not json '.json_encode($_logs));
                    continue;
                }
                $source_result_arr = JsonHelper::json_decode_all($source_result_json);
                //$_logs['$source_result_arr'] = $source_result_arr;
                
                if (!empty($source_result_arr['result']['content']['orderResult']))
                {
                    $audio_result_arr = $source_result_arr['result']['content']['orderResult'];
                }
                elseif (!empty($source_result_arr['content']['orderResult']))
                {
                    $audio_result_arr = $source_result_arr['content']['orderResult'];
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $source_result_arr format fail '.json_encode($_logs));
                    continue;
                }
                
                
                $audio_result_arr_ = [];
                foreach ($audio_result_arr as $audio_result_item)
                {
                    $audio_result_item_word = '';
                    if (!empty($audio_result_item['ws']))
                    {
                        foreach ($audio_result_item['ws'] as $audio_result_item_part)
                        {
                            if (empty($audio_result_item_part['cw']))
                            {
                                continue;
                            }
                            
                            if (!empty($audio_result_item_part['cw']['w']))
                            {
                                $audio_result_item_word .= $audio_result_item_part['cw']['w'];
                            }
                            elseif (!empty($audio_result_item_part['cw'][0]['w']))
                            {
                                $audio_result_item_word .= $audio_result_item_part['cw'][0]['w'];
                            }
                        }
                    }
                    
                    $data_item = [];
                    $data_item['type'] = 'voice_transcription';
                    $data_item['id'] = StringHelper::uniqueid();
                    $data_item['note'] = ['text' => $audio_result_item_word, 'attr' => []];
                    $data_item['start'] = $audio_result_item['bg']/1000;
                    $data_item['end'] = $audio_result_item['ed']/1000;
                    $data_item['cBy'] = '0';
                    $data_item['cTime'] = '0';
                    $data_item['mBy'] = '0';
                    $data_item['mTime'] = '0';
                    $audio_result_arr_[] = $data_item;
                }
                
                $source_urls['ai_result_ifly'] = '';
                unset($source_urls['ai_result_ifly']);
                
                $source_urls['ai_result'] = json_encode(['data' => $audio_result_arr_]);
                $_logs['$source_urls'] = $source_urls;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
            }
            
            $fileData[] = $source_urls;
        }
        $_logs['$fileData.count'] = count($fileData);
        
        //提示成功和失败数量
        if ($checkFailCount)
        {
            $checkMessages['label_unpack_succ_tip'] = [$fileCount - $checkFailCount, $checkFailCount];
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($fileData, '', $checkMessages);
    }
}