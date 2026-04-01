<?php

namespace common\components\aiHandler\audio;

use common\components\KedaxunfeiHandler;
use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\StringHelper;
use common\helpers\JsonHelper;
use common\helpers\FileHelper;
use common\helpers\ArrayHelper;
use common\helpers\HttpHelper;
use common\helpers\LabelHelper;
use common\helpers\ProcessHelper;

/**
 * 科大讯飞语音转写
 * https://www.xfyun.cn/doc/asr/lfasr/API.html
 *
 */
class KedaXunfeiV3 extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = ArrayHelper::desc($args);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));

        if (empty($args['item']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' args $imageUrlOrPath not found '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $item = $args['item'];
        
        $work = empty($args['work']) ? [] : $args['work'];
        $_logs['$work'] = $work;
        
        //调用模型接口逻辑
        $resourceRoot = Setting::getResourceRootPath();
        $uploadRoot = Setting::getUploadRootPath();
        
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

                if (FileHelper::is_audio_by_ext($resourceUrl))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_video '.json_encode($_logs));
                    ProcessHelper::runTimeLong(__CLASS__.':'.__FUNCTION__.':'.__LINE__);

                    $kedaxunfei = new KedaxunfeiHandler($resourceUrl);
                    $result = $kedaxunfei->run();
                    
                    ProcessHelper::runTimeLong(__CLASS__.':'.__FUNCTION__.':'.__LINE__);

                    $_logs['$result'] = $result;
                    if (empty($result) || empty($result['data']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' keda_result_empty '.json_encode($_logs));
                        continue;
                    }
                    if (!empty($result['err_no']))
                    {
                        $message = empty($result['failed']) ? 'keda_response_error' : $result['failed'];
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__." $message ".json_encode($_logs));
                        continue;
                    }
                    
                    $aiResult = $result['data'];
                    if (StringHelper::is_json($aiResult))
                    {
                        $aiResult = JsonHelper::json_decode_all($aiResult);
                    }

                    //todo:总时长

                    foreach ($aiResult as $dataItem)
                    {
                        $dataItemText = empty($dataItem['text']) ? '' : $dataItem['text'];
                        $dataItemStartTime = $dataItem['start_time'];
                        $dataItemEndTime = $dataItem['end_time'];
                        $_logs['$dataItemText'] = $dataItemText;
                        $_logs['$dataItemStartTime'] = $dataItemStartTime;
                        $_logs['$dataItemEndTime'] = $dataItemEndTime;

                        $resultArr[] = [
                            'id' => StringHelper::uniqueid(),
                            'type' => 'voice_transcription',
                            'note' => [
                                'text' => $dataItemText,
                                'attr' => [],
                            ],
                            'start' => $dataItemStartTime/1000,
                            'end' => $dataItemEndTime/1000,
                            'cBy' => empty($work['user_id']) ? '0' : $work['user_id'],
                            'cTime' => time(),
                            'cStep' => empty($work['step_id']) ? '0' : $work['step_id'],
                            //'score' => $dataItemScore,
                            'is_ai' => 1,
                            'isVaild' => 1,
                            //'audio_duration' => 0, //总时长
                        ];
                        $_logs['$resultArr'] = $resultArr;
                        //var_dump($_logs);
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                    }
                }
            }
        }

        $_logs['$resultArr'] = ArrayHelper::desc($resultArr);

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result(['data' => $resultArr]);
    }
}