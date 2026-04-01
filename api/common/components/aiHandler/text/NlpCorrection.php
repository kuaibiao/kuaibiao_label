<?php 
namespace common\components\aiHandler\text;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\StringHelper;
use common\helpers\JsonHelper;
use common\helpers\FileHelper;
use common\helpers\ArrayHelper;
use common\helpers\HttpHelper;
use common\components\ImageHandler;
use yii\helpers\Url;

/**
 * Nlp 对语句进行纠错
 * 
 *
 */

class NlpCorrection extends Component
{
    public static function run($args)
    {
        $_logs['$args'] = ArrayHelper::desc($args);
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

        $work = empty($args['work']) ? [] : $args['work'];
        $_logs['$work'] = $work;
        
        $url = $aimodel['url'];
        if (!FileHelper::netfile_exists($url))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' url notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        
        //------------------------------------------
        
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
                    
                    if (!FileHelper::netfile_exists($resourceUrl))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $field empty '.json_encode($_logs));
                        return FormatHelper::result('', 'error', 'error');
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
                        $resourceUrl = str_replace($resourceRoot, $uploadRoot, $resourceUrl);
                        if (!file_exists($resourceUrl))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                            continue;
                        }
                    }
                }
                else
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                    continue;
                }
                
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_image pre '.json_encode($_logs));
                if (FileHelper::is_text_by_ext($resourceUrl))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is_image '.json_encode($_logs));
                    
                    $requestParams = [];
                    $requestParams['type'] = 'str';
                    $requestParams['sentence'] = file_get_contents($resourceUrl);
                    $_logs['$requestParams'] = ArrayHelper::desc($requestParams);
                    
                    if (empty($requestParams))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $requestConfigs empty '.json_encode($_logs));
                        continue;
                    }
                    
                    //替换占位符
                    $requestParamJson = json_encode($requestParams);
                    $_logs['$requestParamJson'] = substr($requestParamJson, 0, 50);
                    
                    if (!StringHelper::is_json($requestParamJson))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' isnot json '.serialize($_logs));
                        continue;
                    }
                    
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request pre '.json_encode($_logs));
                    
                    $result = HttpHelper::request($url, $requestParamJson, 'post');
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
                    if (StringHelper::is_json($aiResult))
                    {
                        $aiResult = JsonHelper::json_decode_all($aiResult);
                    }
                    $_logs['$aiResult'] = ArrayHelper::desc($aiResult);
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $aiResult '.json_encode($_logs));
                    
                    if (!isset($aiResult['ret']) || !isset($aiResult['errmsg']) || !isset($aiResult['result']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result empty '.json_encode($_logs));
                        continue;
                    }
                    
                    $error = $aiResult['ret'];
                    $message = $aiResult['errmsg'];
                    $data = $aiResult['result'];
                    $_logs['$error'] = $error;
                    $_logs['$message'] = $message;
                    $_logs['$data'] = ArrayHelper::desc($data);
                    
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
/**
{
	'ret': 0,
	'errmsg': '',
	'result': {
		'keywords': ['美国', '贪官', '中国', '法律', '做', '洗钱', '可能', '犯罪', '转移', '余振东', '美国司法部', '追赃', '加拿大', '司法', '赃款', '人', '财产', '民事诉讼', '行为', '法院'],
		'phrases': ['美国法律', '中国贪官', '民事诉讼追赃']
	}
}
*/
                        $data['cBy'] = empty($work['user_id']) ? '0' : $work['user_id'];
                        $data['cStep'] = empty($work['step_id']) ? '0' : $work['step_id'];
                        $data['cTime'] = time();
                        $resultArr[] = $data;
                        
                    }
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));
                    continue;
                }
                
                
            }
        }
        
        $_logs['$resultArr'] = ArrayHelper::desc($resultArr);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result(['data' => $resultArr]);
//         {"data":[
//         {"id":"ab2e32f4-9ef3-4262-975e-2010edcca492","note":{"text":"\u6211\u4eec\u7684","attr":[
//         {"id":"d8700b94-d6cd-425a-8141-24e7ad75ac2c","type":"form-radio","value":"\u65e0","header":"\u566a\u97f3"},
//         {"id":"f3be5006-6f4a-459e-b638-85ed5ce0cdf3","type":"form-radio","value":"\u6709\u6548","header":"\u6709\u6548\u6027"},
//         {"id":"f58dc2c9-7091-47b3-8877-364626656adf","type":"form-select","value":"\u4e2d\u6587","header":"\u8bed\u8a00"}]},
//         "start":"26.55","end":"28.40","cBy":"53","cTime":1529391466}
    }
    
}