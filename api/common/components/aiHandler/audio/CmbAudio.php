<?php 
namespace common\components\aiHandler\audio;

use Yii;
use yii\base\Component;
use common\components\Functions;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;
use common\helpers\ArrayHelper;
use common\helpers\StringHelper;
/**
 * 招行语音对接脚本
 * 
 */

class CmbAudio extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['aimodel']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $aimodel not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $aimodel = $args['aimodel'];
        
        if (empty($args['item']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $item = $args['item'];
        
        //----------------------------------------------------------
        $url = $aimodel['url'];
        $requestFields = $aimodel['category']['request_fields'];
        $responseFields = $aimodel['category']['response_fields'];
        $requestConfig = $aimodel['request_config'];
        $responseConfig = $aimodel['response_config'];
        $requestFieldsRelation = $aimodel['request_fields_relation'];
        $responseFieldsRelation = $aimodel['response_fields_relation'];
        
        $_logs['$url'] = $url;
        $_logs['$requestConfig'] = $requestConfig;
        $_logs['$responseConfig'] = $responseConfig;
        
        
        if (!JsonHelper::is_json($requestFields))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestFields isnot json '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        if (!JsonHelper::is_json($responseFields))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $responseFields isnot json '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        if (!JsonHelper::is_json($requestConfig))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestConfig isnot json '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        if (!JsonHelper::is_json($responseConfig))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $responseConfig isnot json '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        if (!JsonHelper::is_json($requestFieldsRelation))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestFieldsRelation isnot json '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        if (!JsonHelper::is_json($responseFieldsRelation))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $responseFieldsRelation isnot json '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        
        $requestFields = JsonHelper::json_decode_all($requestFields);
        if (!$requestFields)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestFields json_decode_all fail '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $responseFields = JsonHelper::json_decode_all($responseFields);
        if (!$responseFields)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestFields json_decode_all fail '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $requestConfigs = JsonHelper::json_decode_all($requestConfig);
        if (!$requestConfigs)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestConfig json_decode_all fail '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $responseConfigs = JsonHelper::json_decode_all($responseConfig);
        if (!$responseConfigs)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $responseConfig json_decode_all fail '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $requestFieldsRelations = JsonHelper::json_decode_all($requestFieldsRelation);
        if (!$requestFieldsRelations)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestFieldsRelation json_decode_all fail '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $responseFieldsRelations = JsonHelper::json_decode_all($responseFieldsRelation);
        if (!$responseFieldsRelations)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $responseFieldsRelation json_decode_all fail '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $_logs['$requestConfigs'] = $requestConfigs;
        $_logs['$responseConfigs'] = $responseConfigs;
        $_logs['$requestFieldsRelations'] = $requestFieldsRelations;
        $_logs['$responseFieldsRelations'] = $responseFieldsRelations;
        //----------------------------------------------------------
        $requestFields = ['$binary', '$type', '$filename'];
        $requestFieldKeys = [];
        foreach ($requestFields as $field)
        {
            $_logs['$field'] = $field;
        
            //查找配置中的占位符对应的所有key的索引, 可能是多维数组
            if (empty($requestFieldsRelations[$field]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $field empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'error');
            }
            $fieldRelation = $requestFieldsRelations[$field];
        
            $fieldRelation = trim($fieldRelation, '>');
            if (strpos($fieldRelation, '>'))
            {
                $requestFieldKeys[$field] = explode('>', $fieldRelation);
            }
            else
            {
                $requestFieldKeys[$field] = [$fieldRelation];
            }
        }
        $_logs['$requestFieldKeys'] = $requestFieldKeys;
        //------------------------------------------
        
        $responseFields = ['$error','$message','$data', '$text', '$start_time', '$end_time'];
        $responseFieldKeys = [];
        foreach ($responseFields as $field)
        {
            $_logs['$field'] = $field;
        
            //查找配置中的占位符对应的所有key的索引, 可能是多维数组
            if (empty($responseFieldsRelations[$field]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $field empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'error');
            }
            $fieldRelation = $responseFieldsRelations[$field];
        
            $fieldRelation = trim($fieldRelation, '>');
            if (strpos($fieldRelation, '>'))
            {
                $responseFieldKeys[$field] = explode('>', $fieldRelation);
            }
            else
            {
                $responseFieldKeys[$field] = [$fieldRelation];
            }
        }
        $_logs['$responseFieldKeys'] = $responseFieldKeys;
        //------------------------------------------
//         $responseItemFields = ['$label', '$score', '$left', '$top', '$right', '$bottom'];
//         foreach ($responseItemFields as $field)
//         {
//             $_logs['$field'] = $field;

//             //查找配置中的占位符对应的所有key的索引, 可能是多维数组
//             if (empty($responseFieldKeys[$field]))
//             {
//                 Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $field empty '.json_encode($_logs));
//                 return FormatHelper::result('', 'error', 'error');
//             }
//             $dataKeysCount = count($responseFieldKeys['$data']);
//             //必须多销毁一个{{0}} //result>{{0}}>class
//             for ($i=1;$i<=$dataKeysCount;$i++)
//             {
//                 $responseFieldKeys[$field][$i] = '';
//                 unset($responseFieldKeys[$field][$i]);
//             }
//         }
//         $_logs['$responseFieldKeys'] = $responseFieldKeys;
        //------------------------------------------
        
        $resourceRoot = Setting::getResourceRootPath();
        
        $dataArr = JsonHelper::json_decode_all($item['dataResult']['data']);
        
        
        $resultArr = [];
        if ($dataArr)
        {
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                $_logs['$resourceUrl'] = $resourceUrl;
                
                //如果是http
                if (StringHelper::is_url($resourceUrl))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ishttp '.json_encode($_logs));
                    
                    if (!FileHelper::netfile_exists($resourceUrl))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url not exist '.json_encode($_logs));
                        return FormatHelper::result('', 'error', '远程文件不存在');
                    }
                    
                    $binary = FileHelper::netfile_getcontent($resourceUrl);
                }
                //如果是本地硬盘相对路径
                elseif (StringHelper::is_relativepath($resourceUrl))
                {
                    if (strpos($resourceUrl, $resourceRoot) === false)
                    {
                        $resourceUrl = $resourceRoot . '/'.$resourceUrl;
                    }
                    $_logs['$resourceUrl'] = $resourceUrl;
                    
                    if (!file_exists($resourceUrl))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件不存在');
                    }
                    
                    $binary = file_get_contents($resourceUrl);
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                    return FormatHelper::result('', 'error', '文件路径错误');
                }
                
                if (FileHelper::is_audio($resourceUrl))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_image '.json_encode($_logs));
                    
                    
                    //组装请求数据
                    $binary = base64_encode(file_get_contents($resourceUrl));
                    $type = FileHelper::fileextension($resourceUrl);
                    $filename = FileHelper::filename($resourceUrl);
                    
                    $requestParams = $requestConfigs;
                    $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$binary'], $binary);
                    $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$type'], $type);
                    $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$filename'], $filename);
                    //$_logs['$requestParams'] = $requestParams;
                    
                    if (empty($requestParams))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestConfigs empty '.json_encode($_logs));
                        continue;
                    }
                    
                    //替换占位符
                    $requestParamJson = json_encode($requestParams);
                    $_logs['$requestParamJson'] = substr($requestParamJson, 0, 50);
                    
                    if (!JsonHelper::is_json($requestParamJson))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' isnot json '.serialize($_logs));
                        continue;
                    }
                    
                    $result = Functions::request($url, $requestParamJson, 'post');
                    $_logs['$result'] = $result;
                    if ($result['error'])
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                        continue;
                    }
                    elseif (empty($result['data']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result empty '.json_encode($_logs));
                        continue;
                    }
                    $aiResult = $result['data'];
                    //$aiResult = '{"errormsg": "", "data": {"result": "\u641e\u9519\u73b0\u5728\u54ea\u91cc"}, "errcode": 0}';
                    if (JsonHelper::is_json($aiResult))
                    {
                        $aiResult = JsonHelper::json_decode_all($aiResult);
                    }
                    $_logs['$aiResult'] = $aiResult;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.json_encode($_logs));
                    
                    $error = ArrayHelper::array_keys_value($aiResult, 'err_no');
                    $message = ArrayHelper::array_keys_value($aiResult, 'err_msg');
                    $data = ArrayHelper::array_keys_value($aiResult, 'result');
                    $_logs['$error'] = $error;
                    $_logs['$message'] = $message;
                    //$_logs['$data'] = $data;
                    //var_dump($_logs);
                    //exit();
                    
                    if ($error)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件不存在');
                    }
                    elseif (empty($data) || !is_array($data))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件不存在');
                    }
                    else 
                    {
                        if (!isset($data['content']) || !isset($data['contentN0'])|| !isset($data['contentN1'])|| !isset($data['timePosition'])|| !isset($data['voiceId']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                            return FormatHelper::result('', 'error', '结果格式错误');
                        }
                        
                        $content = $data['content'];
                        $contentN0 = $data['contentN0'];
                        $contentN1 = $data['contentN1'];
                        $timePosition = $data['timePosition'];
                        $voiceId = $data['voiceId'];
                        
                        $words = explode(' ', $timePosition);
                        
                        foreach ($words as $word)
                        {
                            /* $word = 730,1250|0,1 */
                            if (!strpos($word, '|'))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                                return FormatHelper::result('', 'error', '结果格式错误');
                            }
                            
                            if (substr_count($word, ',') != 2)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                                return FormatHelper::result('', 'error', '结果格式错误');
                            }
                            
                            
                            list($time_, $position_) = explode('|', $word);
                            list($startTime_, $endTime_) = explode(',', $time_);
                            list($startPosition_, $endPosition_) = explode(',', $position_);
                            
                            $text = mb_substr($content, $startPosition_, $endPosition_);
                            /*{
                            	"data": [{
                            		"type": "voice_transcription",
                            		"id": "590e8023-c965-4d99-a995-eeb559e98f22",
                            		"note": {
                            			"text": "",
                            			"attr": []
                            		},
                            		"start": "10.67",
                            		"end": "17.70",
                            		"cBy": "14084",
                            		"cTime": 1547706154,
                            		"mBy": "14084",
                            		"mTime": 1547706154
                            	}],
                            	"is_difficult": 0,
                            }*/
                            $resultArr[] = [
                                'id' => StringHelper::uniqueid(),
                                'type' => 'voice_transcription',
                                'note' => [
                                    'text' => $text,
                                    'attr' => [],
                                ],
                                'start' => $startTime_/1000,
                                'end' => $endTime_/1000,
                                'code' => [],
                                'cBy' => '0',
                                'cTime' => time(),
                                'score' => 0,
                                'is_ai' => 1,
                                'voiceId' => $voiceId //附加字段
                            ];
                        }
                    }
                    
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));
                    continue;
                }
            }
        }
        
        $_logs['$resultArr'] = $resultArr;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return ['data' => $resultArr];
    }
    
}