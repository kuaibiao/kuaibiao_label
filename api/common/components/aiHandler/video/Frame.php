<?php 
namespace common\components\aiHandler\video;

use Yii;
use yii\base\Component;
use common\models\Setting;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;

/**
 * 
 * 视频切割成图片
 * 需要系统安装ffmpeg
 * 
 */

class Frame extends Component
{
    public static function run($aimodel, $item)
    {
        $url = $aimodel['url'];
        $requestConfig = $aimodel['request_config'];
        $responseConfig = $aimodel['response_config'];
        
        $dataArr = JsonHelper::json_decode_all($item['dataResult']['data']);
        
        $resultArr = [];
        if ($dataArr)
        {
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                $resourceRoot = Setting::getResourceRootPath();
                
                if (strpos($resourceUrl, $resourceRoot) === false)
                {
                    $resourceUrl = $resourceRoot . '/'.$resourceUrl;
                }
                $_logs['$resourceUrl'] = $resourceUrl;
                
                if (!file_exists($resourceUrl))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_sourcenotfound '.json_encode($_logs));
                    exit();
                }
                
                if (FileHelper::is_video($resourceUrl))
                {
                    
                    
                    
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $mime not audio '.json_encode($_logs));
                    exit();
                }
            }
        }
        
        return ['data' => $resultArr];
//         {"data":[
//         {"id":"ab2e32f4-9ef3-4262-975e-2010edcca492","note":{"text":"\u6211\u4eec\u7684","attr":[
//         {"id":"d8700b94-d6cd-425a-8141-24e7ad75ac2c","type":"form-radio","value":"\u65e0","header":"\u566a\u97f3"},
//         {"id":"f3be5006-6f4a-459e-b638-85ed5ce0cdf3","type":"form-radio","value":"\u6709\u6548","header":"\u6709\u6548\u6027"},
//         {"id":"f58dc2c9-7091-47b3-8877-364626656adf","type":"form-select","value":"\u4e2d\u6587","header":"\u8bed\u8a00"}]},
//         "start":"26.55","end":"28.40","cBy":"53","cTime":1529391466}
    }
    
}