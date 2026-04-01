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
 * 根据超像素算法获取图片的区域划分
 * 返回mask图
 * 
 *
 */

class Superpixel extends Component
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
        
        if (!empty($args['item']))
        {
            if (!empty($args['item']['dataResult']['data']))
            {
                $dataArr = JsonHelper::json_decode_all($args['item']['dataResult']['data']);
            }
            elseif (!empty($args['item']['result']['data']))
            {
                $dataArr = JsonHelper::json_decode_all($args['item']['result']['data']);
            }
            
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                $args['imageUrlOrPath'] = $resourceUrl;
                break;
            }
        }
        
        if (empty($args['imageUrlOrPath']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $imageUrlOrPath = $args['imageUrlOrPath'];
        
        //色差值
        $tolerance = isset($args['tolerance']) ? $args['tolerance'] : 20;
        
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
        $requestFields = ['$binary', '$type', '$filename', '$tolerance'];
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
            $requestParams = ArrayHelper::array_keys_replace($requestParams, $requestFieldKeys['$tolerance'], $tolerance);
            //$_logs['$requestParams'] = $requestParams;
            
            if (empty($requestParams))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestConfigs empty '.json_encode($_logs));
                return FormatHelper::result('', 'error', '$requestConfigs empty');
            }
            
            //替换占位符
            $requestParamJson = json_encode($requestParams);
            $_logs['$requestParamJson'] = ArrayHelper::var_desc($requestParamJson);
            
            if (!JsonHelper::is_json($requestParamJson))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' isnot json '.serialize($_logs));
                return FormatHelper::result('', 'error', '$requestParamJson isnot json');
            }
            
            $result = Functions::request($url, $requestParamJson, 'post');
            $_logs['request.$result'] = ArrayHelper::var_desc($result);
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
            $_logs['$aiResult'] = ArrayHelper::var_desc($aiResult);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.json_encode($_logs));
            
            $error = ArrayHelper::array_keys_value($aiResult, $responseFieldKeys['$error']);
            $message = ArrayHelper::array_keys_value($aiResult, $responseFieldKeys['$message']);
            $data = ArrayHelper::array_keys_value($aiResult, $responseFieldKeys['$data']);
            $_logs['$aiResult.$error'] = $error;
            $_logs['$aiResult.$message'] = $message;
            //$_logs['$aiResult.$data'] = ArrayHelper::var_desc($data);
            
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
                    	"data": [
                    	{"type":"polygon","editable":false,"points":[{"x":0,"y":0},{"x":0,"y":0.02819237112999},{"x":0,"y":0.026533996686339},{"x":0.0080482894554734,"y":0.026533996686339},{"x":0.010060362517834,"y":0.024875622242689},{"x":0.016096578910947,"y":0.024875622242689},{"x":0.018108651041985,"y":0.023217247799039},{"x":0.024144869297743,"y":0.023217247799039},{"x":0.028169013559818,"y":0.021558871492743},{"x":0.028169013559818,"y":0.0099502485245466},{"x":0.026156941428781,"y":0.0082918740808964},{"x":0.026156941428781,"y":0}],"color":"#000000"},{"type":"polygon","editable":false,"points":[{"x":0.028169013559818,"y":0},{"x":0.028169013559818,"y":0.0082918740808964},{"x":0.030181085690856,"y":0.0099502485245466},{"x":0.030181085690856,"y":0.023217247799039},{"x":0.032193157821894,"y":0.023217247799039},{"x":0.038229376077652,"y":0.02819237112999},{"x":0.058350101113319,"y":0.019900497049093},{"x":0.058350101113319,"y":0}],"color":"#000000"}
                    	]
                    }*/
                    
                    $dataItemPoints = ArrayHelper::array_keys_value($dataItem, $responseFieldKeys['$points']);
                    //$_logs['$dataItemPoints'] = ArrayHelper::var_desc($dataItemPoints);
                    
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
                    //$_logs['$resultArr'] = ArrayHelper::var_desc($resultArr);
                }
            }
            
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));
        }
        
        $_logs['$resultArr'] = ArrayHelper::var_desc($resultArr);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return ['data' => $resultArr];
    }
    
}