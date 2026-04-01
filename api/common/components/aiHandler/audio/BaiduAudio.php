<?php 
namespace common\components\aiHandler\audio;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;
use common\helpers\ArrayHelper;
use common\helpers\StringHelper;
use common\components\BaiduAiHandler;

/**
 * 根据图片自动识别其中的矩形框
 * 
 */

class BaiduAudio extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = $args;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($args['item']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', '参数错误');
        }
        $item = $args['item'];
        
        $_logs['$item'] = $item;
        //----------------------------------------------------------
        
        
        $resourceRoot = Setting::getResourceRootPath();
        
        $dataArr = JsonHelper::json_decode_all($item['dataResult']['data']);
        
        //实例化百度ai
        $client = new BaiduAiHandler();
        
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
                    
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                    return FormatHelper::result('', 'error', '文件路径错误');
                }
                
                if (FileHelper::is_audio($resourceUrl))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_image '.json_encode($_logs));
                    
                    $filecontent = FileHelper::file_getcontent($resourceUrl);
                    if ($filecontent === false)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件内容获取失败');
                    }
                    
                    // 识别本地文件
                    $aiResult = $client->audioAsr($filecontent);
                    //var_dump($aiResult);
                    //exit();
                    
                    /**
                    // 成功返回
                    {
                        "err_no": 0,
                        "err_msg": "success.",
                        "corpus_no": "15984125203285346378",
                        "sn": "481D633F-73BA-726F-49EF-8659ACCC2F3D",
                        "result": ["北京天气"]
                    }
                    
                    // 失败返回
                    {
                        "err_no": 2000,
                        "err_msg": "data empty.",
                        "sn": null
                    }
                    
                    */
                    
                    //$aiResult = '{"errormsg": "", "data": {"result": "\u641e\u9519\u73b0\u5728\u54ea\u91cc"}, "errcode": 0}';
                    if (JsonHelper::is_json($aiResult))
                    {
                        $aiResult = JsonHelper::json_decode_all($aiResult);
                    }
                    //$_logs['$aiResult'] = ArrayHelper::var_desc($aiResult);
                    $_logs['$aiResult'] = $aiResult;
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.json_encode($_logs));
                    
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
                        
                        foreach ($data as $dataItem)
                        {
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
                                'type' => 'voice_transcription',
                                'note' => [
                                    'text' => $dataItem,
                                    'attr' => [],
                                ],
                                'start' => 0,
                                'end' => 0,
                                'code' => [],
                                'cBy' => '0',
                                'cTime' => time(),
                                'score' => 0,
                                'is_ai' => 1
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
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        //return ['data' => $resultArr];
        return FormatHelper::result(['data' => $resultArr]);
    }
    
}