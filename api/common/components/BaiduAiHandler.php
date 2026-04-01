<?php

namespace common\components;

use Yii;
use yii\base\Exception;
use yii\base\Component;

class BaiduAiHandler extends Component
{
    private $appId;
    private $apiKey;
    private $secretKey;

    public function __construct()
    {
        parent::__construct(array());
    }
    
    public function init($app = 'nlp')
    {
        if (empty(Yii::$app->params['baiduAi'][$app]))
        {
            return false;
        }
        
        $this->appId = Yii::$app->params['baiduAi'][$app]['APP_ID'];
        $this->apiKey = Yii::$app->params['baiduAi'][$app]['API_KEY'];
        $this->secretKey = Yii::$app->params['baiduAi'][$app]['SECRET_KEY'];
    }
    

    /**
     * 图像多主体检测
     * @param $fileContent
     * @return array
     */
    public function multiObjectDetect($fileContent)
    {
        self::init('image');
        require_once(Yii::getAlias('@vendor/baidu/aip-php-sdk/AipImageClassify.php'));
        $aipImageClassify = new \AipImageClassify($this->appId, $this->apiKey, $this->secretKey);
        return $aipImageClassify->multiObjectDetect($fileContent);
    }

    /**
     * 图像主体检测
     * @param $fileContent
     * @return array
     */
    public function objectDetect($fileContent)
    {
        self::init('image');
        require_once(Yii::getAlias('@vendor/baidu/aip-php-sdk/AipImageClassify.php'));
        $aipImageClassify = new \AipImageClassify($this->appId, $this->apiKey, $this->secretKey);
        return $aipImageClassify->objectDetect($fileContent);
    }
    
    /**
     * 文本纠错接口
     *
     * @param string $text - 待纠错文本，输入限制511字节
     * @param array $options - 可选参数对象，key: value都为string类型
     * @description options列表:
     * @return array
     */
    public function ecnet($text, $options = [])
    {
        self::init('nlp');
        require_once(Yii::getAlias('@vendor/baidu/aip-php-sdk/AipNlp.php'));
        $api = new \AipNlp($this->appId, $this->apiKey, $this->secretKey);
        return $api->ecnet($text, $options);
    }
    
    public function simnet($text1, $text2, $options = [])
    {
        self::init('nlp');
        require_once(Yii::getAlias('@vendor/baidu/aip-php-sdk/AipNlp.php'));
        $api = new \AipNlp($this->appId, $this->apiKey, $this->secretKey);
        
        sleep(1);
        
        return $api->simnet(trim($text1), trim($text2), $options);
    }
    
    public function audioAsr($filecontent) {
        self::init('audio');
        require_once(Yii::getAlias('@vendor/baidu/aip-php-sdk/AipSpeech.php'));
        $client = new \AipSpeech($this->appId, $this->apiKey, $this->secretKey);
        
        return $client->asr($filecontent, 'pcm', 16000);
    }
}