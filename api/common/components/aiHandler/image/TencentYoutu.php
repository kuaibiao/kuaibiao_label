<?php 
namespace common\components\aiHandler\image;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;
use common\helpers\ArrayHelper;
use common\helpers\StringHelper;

require_once(Yii::getAlias('@common/libraries/tencent_youtu/include.php'));
use TencentYoutuyun\Conf;
use TencentYoutuyun\YouTu;

/**
 * 根据图片自动识别其中的矩形框
 * 
 *
 */

class TencentYoutu extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['item']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $item = $args['item'];
        
        $_logs['$item'] = $item;
        //----------------------------------------------------------
        
        
        $resourceRoot = Setting::getResourceRootPath();
        
        $dataArr = JsonHelper::json_decode_all($item['dataResult']['data']);
        
        $appid=Yii::$app->params['tencent_youtu']['AppID'];
        $secretId=Yii::$app->params['tencent_youtu']['SecretID'];
        $secretKey=Yii::$app->params['tencent_youtu']['SecretKey'];
        $userid=Yii::$app->params['tencent_youtu']['UserId'];
        
        
        Conf::setAppInfo($appid, $secretId, $secretKey, $userid,conf::API_YOUTU_END_POINT );
        
        
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
                        exit('$resourceUrl not exist');
                    }
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                    exit('$resourceUrl not exist');
                }
                
                if (FileHelper::is_image($resourceUrl))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_image '.json_encode($_logs));
                    
                    $filecontent = FileHelper::file_getcontent($resourceUrl);
                    if ($filecontent === false)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件内容获取失败');
                    }
                    
                    $aiResult = YouTu::arithmeticocr('', base64_encode($filecontent));
                    //var_dump($aiResult);
                    //exit();
                    
                    //$aiResult = '{"errormsg": "", "data": {"result": "\u641e\u9519\u73b0\u5728\u54ea\u91cc"}, "errcode": 0}';
                    if (JsonHelper::is_json($aiResult))
                    {
                        $aiResult = JsonHelper::json_decode_all($aiResult);
                    }
                    $_logs['$aiResult'] = ArrayHelper::var_desc($aiResult);
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.json_encode($_logs));
                    
                    $error = ArrayHelper::array_keys_value($aiResult, 'errorcode');
                    $message = ArrayHelper::array_keys_value($aiResult, 'errormsg');
                    $data = ArrayHelper::array_keys_value($aiResult, 'items');
                    $_logs['$error'] = $error;
                    $_logs['$message'] = $message;
                    //$_logs['$data'] = $data;
                    //var_dump($_logs);
                    //exit();
                    
                    if ($error)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
                        continue;
                    }
                    elseif (empty($data) || !is_array($data))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                        continue;
                    }
                    else 
                    {
                        list($width_, $height_, $type_, $attr_) = getimagesize($resourceUrl);
                        
                        foreach ($data as $dataItem)
                        {
                            $dataItemLeft = round($dataItem['itemcoord']['x'] / $width_, 6);
                            $dataItemRight = round(($dataItem['itemcoord']['x'] + $dataItem['itemcoord']['width'])/$width_, 6);
                            $dataItemTop = round($dataItem['itemcoord']['y']/$height_, 6);
                            $dataItemBottom = round(($dataItem['itemcoord']['y'] + $dataItem['itemcoord']['height'])/$height_, 6);
                            
                            /*{
                        		"type": "rect",
                        		"id": "a5e810f4-4837-4adf-aeb1-c31a07ce41a8",
                        		"points": [{
                        			"x": 0.362084,
                        			"y": 0.268254
                        		}, {
                        			"x": 0.676752,
                        			"y": 0.268254
                        		}, {
                        			"x": 0.676752,
                        			"y": 0.520635
                        		}, {
                        			"x": 0.362084,
                        			"y": 0.520635
                        		}],
                        		"strokeWidth": 2,
                        		"label": ["有效"],
                        		"code": [""],
                        		"category": ["有效"],
                        		"color": "#ffff00",
                        		"cBy": "96",
                        		"cTime": 1541157802,
                        		"minWidth": 3,
                        		"minHeight": 3,
                        		"maxWidth": 0,
                        		"maxHeight": 0,
                        		"angle": 0,
                        		"editable": true
                        	}*/
                            $resultArr[] = [
                                'id' => StringHelper::uniqueid(),
                                'type' => 'rect',
                                'points' => [
                                    ['x' => $dataItemLeft, 'y' => $dataItemTop],
                                    ['x' => $dataItemRight, 'y' => $dataItemTop],
                                    ['x' => $dataItemRight, 'y' => $dataItemBottom],
                                    ['x' => $dataItemLeft, 'y' => $dataItemBottom],
                                ],
                                'label' => [$dataItem['item']],
                                'category' => [$dataItem['item']],
                                'code' => [''],
                                'text' => $dataItem['itemstring'],
                                'cBy' => '0',
                                'cTime' => time(),
                                'score' => 0,
                                'is_ai' => 1
                            ];
                            //$_logs['$resultArr'] = $resultArr;
                            //var_dump($_logs);
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                            
                        }
                        
                        sleep(1);
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
//         {"data":[
//         {"id":"ab2e32f4-9ef3-4262-975e-2010edcca492","note":{"text":"\u6211\u4eec\u7684","attr":[
//         {"id":"d8700b94-d6cd-425a-8141-24e7ad75ac2c","type":"form-radio","value":"\u65e0","header":"\u566a\u97f3"},
//         {"id":"f3be5006-6f4a-459e-b638-85ed5ce0cdf3","type":"form-radio","value":"\u6709\u6548","header":"\u6709\u6548\u6027"},
//         {"id":"f58dc2c9-7091-47b3-8877-364626656adf","type":"form-select","value":"\u4e2d\u6587","header":"\u8bed\u8a00"}]},
//         "start":"26.55","end":"28.40","cBy":"53","cTime":1529391466}
    }
    
}