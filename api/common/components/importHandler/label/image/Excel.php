<?php 
namespace common\components\importHandler\label\image;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use common\helpers\ArrayHelper;
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
        
        //读取文件内容
        $fileList = FileHelper::file_readcontent($filePath);
        if (!$fileList)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data_datafile_fileempty '.json_encode($_logs));
            
            $checkMessages = ['label_unpack_error_extractpath_exist' => []];
            return FormatHelper::result('', 'error', json_encode($checkMessages));
        }
        
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
                
                $source_urls['ai_result'] = $source_result_json;
                $_logs['$source_urls'] = $source_urls;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ai_result succ '.json_encode($_logs));
            }
            else
            {
                $args__ = $source_urls;
                $sourceFileExt = FileHelper::fileextension(array_pop($args__));
                $_logs['$sourceFileExt'] = $sourceFileExt;
                
                if ($extensions && !in_array(strtolower($sourceFileExt), $extensions))
                {
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' extension no match '.json_encode($_logs));
                    $checkFailCount ++;
                    
                    if (empty($checkMessages['label_unpack_succ_tip_format_error']) || 
                        (is_array($checkMessages['label_unpack_succ_tip_format_error']) && !in_array($sourceFileExt, $checkMessages['label_unpack_succ_tip_format_error'])))
                    {
                        $checkMessages['label_unpack_succ_tip_format_error'] = [$sourceFileExt];
                    }
                    continue;
                }
            }

            $fileData[] = $source_urls;
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