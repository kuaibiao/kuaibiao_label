<?php 
namespace common\components\aiHandler\image;

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
 * 根据色差获取区域划分
 * 
 */

class Colordifference extends Component
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
        
        if (empty($args['imageUrlOrPath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $imageUrlOrPath = $args['imageUrlOrPath'];
        
        if (empty($args['point']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $point not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $point = $args['point'];
        
        $tolerance = isset($args['tolerance']) ? $args['tolerance'] : 20;
        $mode = isset($args['mode']) ? $args['mode'] : '';
        $smooth = isset($args['smooth']) ? $args['smooth'] : '';
        
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
        
        //判断接口是否存在
        //if (!FileHelper::netfile_exists($url))
        //{
            
        //}
        
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
        $requestFields = ['$binary', '$type', '$filename', '$point', '$tolerance', '$mode', '$smooth'];
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
        
        $responseFields = ['$error','$message','$data', '$points'];
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
        
//         $responseItemFields = ['$points'];
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
//             for ($i=0;$i<=$dataKeysCount;$i++)
//             {
//                 $responseFieldKeys[$field][$i] = '';
//                 unset($responseFieldKeys[$field][$i]);
//             }
//         }
//         $_logs['$responseFieldKeys'] = $responseFieldKeys;
        
        //------------------------------------------
        
        $resourceRoot = Setting::getResourceRootPath();
        
        //如果是http
        if (StringHelper::is_url($imageUrlOrPath))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ishttp '.json_encode($_logs));
        }
        //如果是本地硬盘相对路径
        elseif (StringHelper::is_relativepath($imageUrlOrPath))
        {
            if (strpos($imageUrlOrPath, $resourceRoot) === false)
            {
                $imageUrlOrPath = $resourceRoot . '/'.$imageUrlOrPath;
            }
            $_logs['$imageUrlOrPath'] = $imageUrlOrPath;
            
            if (!file_exists($imageUrlOrPath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                return FormatHelper::result('', 'error', '$imageUrlOrPath not exist');
            }
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
            return FormatHelper::result('', 'error', '$imageUrlOrPath not exist');
        }
        
        if (FileHelper::is_image($imageUrlOrPath))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_image '.json_encode($_logs));
            
            //组装请求数据
            $binary = base64_encode(file_get_contents($imageUrlOrPath));
            $type = FileHelper::fileextension($imageUrlOrPath);
            $filename = FileHelper::filename($imageUrlOrPath);
            
            $requestParams = $requestConfigs;
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$binary'], $binary);
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$type'], $type);
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$filename'], $filename);
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$point'], $point);
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$tolerance'], $tolerance);
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$mode'], $mode);
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$smooth'], $smooth);
            //$_logs['$requestParams'] = $requestParams;
            
            if (empty($requestParams))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestConfigs empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', '$requestConfigs empty');
            }
            
            //替换占位符
            $requestParamJson = json_encode($requestParams);
            $_logs['$requestParamJson'] = substr($requestParamJson, 0, 50);
            
            if (!JsonHelper::is_json($requestParamJson))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' isnot json '.serialize($_logs));
                return FormatHelper::result('', 'error', '$requestParamJson isnot json');
            }
            
            $result = Functions::request($url, $requestParamJson, 'post');
            $_logs['$result'] = $result;
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'request error');
            }
            elseif (empty($result['data']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'result empty');
            }
            $aiResult = $result['data'];
            //$aiResult = '{"errormsg": "", "data": {"result": "\u641e\u9519\u73b0\u5728\u54ea\u91cc"}, "errcode": 0}';
            if (JsonHelper::is_json($aiResult))
            {
                $aiResult = JsonHelper::json_decode_all($aiResult);
            }
            $_logs['$aiResult'] = $aiResult;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.json_encode($_logs));
            
            $error = ArrayHelper::array_keys_value($aiResult, $responseFieldKeys['$error']);
            $message = ArrayHelper::array_keys_value($aiResult, $responseFieldKeys['$message']);
            $data = ArrayHelper::array_keys_value($aiResult, $responseFieldKeys['$data']);
            $_logs['$error'] = $error;
            $_logs['$message'] = $message;
            $_logs['$data'] = $data;
            
            if ($error)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                return FormatHelper::result('', 'error', 'request error');
            }
            elseif (empty($data) || !is_array($data))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', '$data empty');
            }
            else 
            {
                foreach ($data as $dataItem)
                {
                    /*{
                    	"error": "",
                    	"message": "",
                    	"data": [{
                    		"points": [{
                    				"x": "0.68236746361746",
                    				"y": "0.3783783783783784"
                    			},
                    			{
                    				"x": "0.68236746361746",
                    				"y": "0.5783783783783784"
                    			},
                    			{
                    				"x": "0.88236746361746",
                    				"y": "0.3783783783783784"
                    			}
                    		]
                    	}]
                    }*/
                    
                    $dataItemPoints = ArrayHelper::array_keys_value($dataItem, $responseFieldKeys['$points']);
                    $_logs['$dataItemPoints'] = $dataItemPoints;
                    
                    /*{
                    	"type": "polygon",
                    	"points": [{
                    		"x": 0.47592017208413007,
                    		"y": 0.17973231357552583
                    	}, {
                    		"x": 0.35868785850860424,
                    		"y": 0.3441682600382409
                    	}, {
                    		"x": 0.7232911089866157,
                    		"y": 0.5334608030592735
                    	}],
                    	"left": 0,
                    	"top": 0,
                    	"fill": "green",
                    	"opacity": 0.4,
                    	"label": "freespace"
                    }*/
                    $resultArr[] = [
                        'id' => StringHelper::uniqueid(),
                        'type' => 'polygon',
                        'points' => $dataItemPoints,
                        'label' => [],
                        'code' => [],
                        'cBy' => '0',
                        'cTime' => time(),
                        'score' => 100,
                        'is_ai' => 1
                    ];
                    $_logs['$resultArr'] = $resultArr;
                    //var_dump($_logs);
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                    
                }
            }
            
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));
        }
        
        $_logs['$resultArr'] = $resultArr;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return ['data' => $resultArr];
//         {"data":[
//         {"id":"ab2e32f4-9ef3-4262-975e-2010edcca492","note":{"text":"\u6211\u4eec\u7684","attr":[
//         {"id":"d8700b94-d6cd-425a-8141-24e7ad75ac2c","type":"form-radio","value":"\u65e0","header":"\u566a\u97f3"},
//         {"id":"f3be5006-6f4a-459e-b638-85ed5ce0cdf3","type":"form-radio","value":"\u6709\u6548","header":"\u6709\u6548\u6027"},
//         {"id":"f58dc2c9-7091-47b3-8877-364626656adf","type":"form-select","value":"\u4e2d\u6587","header":"\u8bed\u8a00"}]},
//         "start":"26.55","end":"28.40","cBy":"53","cTime":1529391466}
    }
    
}