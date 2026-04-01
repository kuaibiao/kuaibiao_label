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
use common\components\AliyunAiHandler;
use common\components\ProjectHandler;

/**
 * 根据图片自动识别其中的矩形框
 * 
 */

class AliAudio extends Component
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
        $client = new AliyunAiHandler();
        
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
                    
                    if (!FileHelper::is_audio($resourceUrl)){
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                        return FormatHelper::result('', 'error', '文件不存在');
                    }
                    
                    $resourceUrl = ProjectHandler::showUrl($resourceUrl);
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                    return FormatHelper::result('', 'error', '文件路径错误');
                }
                
                
                $_logs['$resourceUrl1'] = $resourceUrl;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                
                // 识别本地文件
                $cacheKey = 'aliaudio.'.md5($resourceUrl);
                $cacheVal = Yii::$app->cache->get($cacheKey);
                if (!$cacheVal) {
                    $cacheVal = $client->longAudio($resourceUrl);
                    
                    Yii::$app->cache->set($cacheKey, $cacheVal, 600);
                }
                $aiResult = $cacheVal;
                
                $_logs['$aiResult0'] = $aiResult;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                
                //$aiResult = '{"errormsg": "", "data": {"result": "\u641e\u9519\u73b0\u5728\u54ea\u91cc"}, "errcode": 0}';
                if (JsonHelper::is_json($aiResult))
                {
                    $aiResult = JsonHelper::json_decode_all($aiResult);
                }
                //$_logs['$aiResult'] = ArrayHelper::var_desc($aiResult);
                $_logs['$aiResult1'] = $aiResult;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.json_encode($_logs));
                
                
                
                //$_logs['$data'] = $data;
                //var_dump($_logs);
                //exit();
                
                if (empty($aiResult))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $data empty '.json_encode($_logs));
                    return FormatHelper::result('', 'error', '文件不存在');
                }
                else 
                {
                    foreach ($aiResult as $dataItem) {
                        
                        /**
                         * {
				"EndTime": 9658,
				"SilenceDuration": 0,
				"SpeakerId": "1",
				"BeginTime": 0,
				"Text": "我国的国防科技工业也在不断发展壮大，包括无人机、人工智能、网络安全等新兴领域都取得了重要突破。",
				"ChannelId": 0,
				"SpeechRate": 267,
				"EmotionValue": 6.5
			},
                         */
                        $resultArr[] = [
                            'id' => StringHelper::uniqueid(),
                            'type' => 'voice_transcription',
                            'note' => [
                                'text' => $dataItem['Text'],
                                'attr' => [],
                            ],
                            'start' => $dataItem['BeginTime'],
                            'end' => $dataItem['EndTime'],
                            'code' => [],
                            'cBy' => '0',
                            'cTime' => time(),
                            'score' => 0,
                            'is_ai' => 1
                        ];
                    }
                    
                }
                    
            }
        }
        
        $_logs['$resultArr'] = $resultArr;
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        //return ['data' => $resultArr];
        return FormatHelper::result(['data' => $resultArr]);
    }
    
}