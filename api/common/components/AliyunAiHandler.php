<?php
namespace common\components;

use Yii;
use yii\base\Component;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use common\helpers\HttpHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use PHPUnit\Framework\SelfDescribing;

class AliyunAiHandler extends Component
{
    
    // https://help.aliyun.com/zh/isi/developer-reference/restful-api-2?spm=a2c4g.11186623.0.0.189746bfwBoeTu
    public function audioAsr($filecontent) {
        $_logs = ['$file' => strlen($filecontent)];
        
        
        AlibabaCloud::accessKeyClient(Yii::$app->params['aliyunConfig']['accessKeyId'], Yii::$app->params['aliyunConfig']['accessKeySecret'])
        ->regionId("cn-shanghai")->asDefaultClient();
        
        $tokenRes = AlibabaCloud::nlsCloudMeta()
        ->v20180518()
        ->createToken()
        ->request();
        
        $_logs['$tokenRes'] = $tokenRes;
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.serialize($_logs));
        
        if (empty($tokenRes)) {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' gettoken error '.json_encode($_logs));
            return false;
        }
        if (empty($tokenRes["Token"]['Id'])) {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' gettoken error '.json_encode($_logs));
            return false;
        }
        $token = $tokenRes["Token"]['Id'];
        
        //-------------------------------
        
        
        $host = "https://nls-gateway-cn-beijing.aliyuncs.com";
        $path = '/stream/v1/asr';
        $querys = [
            'appkey' => '72Yk5v0scjwofkuX',
        ];
        $method = "POST";
        $headers = array(
            'X-NLS-Token' => $token,
            'Content-type' => 'application/octet-stream',
            'Content-Length' => strlen($filecontent),
            'Host' => 'nls-gateway-cn-shanghai.aliyuncs.com',
        );
        $bodys = $filecontent;
        //$bodys = "{//文件数据：base64编码，要求base64编码后大小不超过100M，页数不超过20页，和url参数只能同时存在一个\"fileBase64\":\"\",//文件url地址：完整URL，URL长度不超过1024字节，URL对应的文件base64编码后大小不超过100M，页数不超过20页，和img参数只能同时存在一个\"url\":\"\",//是否需要识别结果中每一行的置信度，默认不需要。true：需要false：不需要\"prob\":false,//是否需要单字识别功能，默认不需要。true：需要false：不需要\"charInfo\":false,//是否需要自动旋转功能，默认不需要。true：需要false：不需要\"rotate\":false,//是否需要表格识别功能，默认不需要。true：需要false：不需要\"table\":false,//转文件类型，word\"fileType\":\"word\"}";
        $url = $host . $path . '?'.http_build_query($querys);
        
        $httpRes = HttpHelper::request($url, $bodys, $method, $headers);
        $_logs['$httpRes'] = $httpRes;
        if ($httpRes['error'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request error '.json_encode($_logs));
            return false;
        }
        elseif (empty($httpRes['data']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result empty '.json_encode($_logs));
            return false;
        }
        $aiResult = $httpRes['data'];
        //$aiResult = '{"errormsg": "", "data": {"result": "\u641e\u9519\u73b0\u5728\u54ea\u91cc"}, "errcode": 0}';
        if (StringHelper::is_json($aiResult))
        {
            $aiResult = JsonHelper::json_decode_all($aiResult);
        }
        $_logs['$aiResult'] = $aiResult;
        
        $contents = '';
        if (!empty($aiResult['result'])) {
            $contents = $aiResult['result'];
        }
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $contents;
    }
    
    
    // 请求参数
    private const KEY_APP_KEY = "appkey";
    private const KEY_FILE_LINK = "file_link";
    private const KEY_VERSION = "version";
    private const KEY_ENABLE_WORDS = "enable_words";
    // 响应参数
    private const KEY_TASK_ID = "TaskId";
    private const KEY_STATUS_TEXT = "StatusText";
    private const KEY_RESULT = "Result";
    // 状态值
    private const STATUS_SUCCESS = "SUCCESS";
    private const STATUS_RUNNING = "RUNNING";
    private const STATUS_QUEUEING = "QUEUEING";
    
    public function longAudio($fileLink) {
        
        $_logs = ['$fileLink' => $fileLink];
        
        $accessKeyId = Yii::$app->params['aliyunConfig']['accessKeyId'];
        $accessKeySecret = Yii::$app->params['aliyunConfig']['accessKeySecret'];
        $appKey = '72Yk5v0scjwofkuX';
        
        /**
         * 第一步：设置一个全局客户端。
         * 使用阿里云RAM账号的AccessKey ID和AccessKey Secret进行鉴权。
         */
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
        ->regionId("cn-shanghai")
        ->asGlobalClient();
           
        // 获取task JSON字符串，包含appkey和file_link参数等。
        // 新接入请使用4.0版本，已接入（默认2.0）如需维持现状，请注释掉该参数设置。
        // 设置是否输出词信息，默认为false，开启时需要设置version为4.0。
        $taskArr = array(self::KEY_APP_KEY => $appKey, self::KEY_FILE_LINK => $fileLink, self::KEY_VERSION => "4.0", self::KEY_ENABLE_WORDS => FALSE);
        $task = json_encode($taskArr);
        //print $task . "\n";
        
        try {
            // 提交请求，返回服务端的响应。
            $submitTaskResponse = AlibabaCloud::nlsFiletrans()
            ->v20180817()
            ->submitTask()
            ->withTask($task)
            ->request();
            //print $submitTaskResponse . "\n";
            
            // 获取录音文件识别请求任务的ID，以供识别结果查询使用。
            $taskId = NULL;
            $statusText = $submitTaskResponse[self::KEY_STATUS_TEXT];
            if (strcmp(self::STATUS_SUCCESS, $statusText) == 0) {
                $taskId = $submitTaskResponse[self::KEY_TASK_ID];
            }
            //return $taskId;
            if (empty($taskId)) {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '.json_encode($_logs));
                return false;
            }
        } catch (ClientException $exception) {
            // 获取错误消息
            print_r($exception->getErrorMessage());
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '.json_encode($_logs));
            return false;
        } catch (ServerException $exception) {
            // 获取错误消息
            print_r($exception->getErrorMessage());
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '.json_encode($_logs));
            return false;
        }
        
        $_logs['$taskId'] = $taskId;
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '.json_encode($_logs));
        //-------------------------------------
            
        $result = NULL;
        while (TRUE) {
            try {
                $getResultResponse = AlibabaCloud::nlsFiletrans()
                ->v20180817()
                ->getTaskResult()
                ->withTaskId($taskId)
                ->request();
                
                //var_dump($getResultResponse);
                //var_dump($getResultResponse);
                $_logs['$getResultResponse'] = $getResultResponse;
                
                //print "识别查询结果: " . $getResultResponse . "\n";
                $statusText = $getResultResponse[self::KEY_STATUS_TEXT];
                $_logs['$statusText'] = $statusText;
                
                if (strcmp(self::STATUS_RUNNING, $statusText) == 0 || strcmp(self::STATUS_QUEUEING, $statusText) == 0) {
                    // 继续轮询
                    sleep(1);
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                } else if (strcmp(self::STATUS_SUCCESS, $statusText) == 0) {
                    
                    if (!empty($getResultResponse[self::KEY_RESULT])) {
                        /**
                         * 
                         * "Sentences": [
			{
				"EndTime": 9658,
				"SilenceDuration": 0,
				"SpeakerId": "1",
				"BeginTime": 0,
				"Text": "我国的国防科技工业也在不断发展壮大，包括无人机、人工智能、网络安全等新兴领域都取得了重要突破。",
				"ChannelId": 0,
				"SpeechRate": 267,
				"EmotionValue": 6.5
			},
                         * 
                         */
                        $result = $getResultResponse[self::KEY_RESULT]['Sentences'];
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ok '.json_encode($_logs));
                    }else{
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no result '.json_encode($_logs));
                    }
                    
                    // 退出轮询
                    break;
                } else {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '.json_encode($_logs));
                    
                    // 退出轮询
                    break;
                }
                    
                
            } catch (ClientException $exception) {
                // 获取错误消息
                $_logs['msg'] = $exception->getErrorMessage();
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                // 退出轮询
                break;
            } catch (ServerException $exception) {
                // 获取错误消息
                $_logs['msg'] = $exception->getErrorMessage();
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
                // 退出轮询
                break;
            }
        }
        
        $_logs['$result'] = $result;
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $result;
        
    }
    
}