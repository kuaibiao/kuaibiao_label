<?php

namespace api\models;

use Yii;
use yii\base\Model;
use common\helpers\NumberHelper;

/**
 * 验证密码表单
 */
class VerifyMobileForm extends Model
{
    public $mobile;
    public $mobileCode;
    const MAX_ERROR_TIMES = 3;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['mobile', 'required'],
            ['mobile', 'match', 'pattern'=>'/^1[345789]{1}\d{9}$/', 'message'=> Yii::t('app', 'mobile_format_error')],
            
            ['mobileCode', 'required'],
            ['mobileCode', 'string', 'min' => 6, 'max' => 6],
            ['mobileCode', 'checkMobileCode'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'mobile' => Yii::t('app', 'verifyMobileForm_field_mobile'),
            'mobileCode' => Yii::t('app', 'verifyMobileForm_field_mobileCode'),
        ];
    }
    
    public static function getTimesErrorCacheKey($mobile)
    {
        return Yii::$app->redis->buildKey('user:mobileCodeErrTimes:'.$mobile);
    }
    
    public static function getCacheKey($mobile)
    {
        return Yii::$app->redis->buildKey('user:mobilecode:'.$mobile);
    }
    
    public function checkMobileCode($attribute, $params)
    {
        $_logs = [];
        
        if (!$this->hasErrors())
        {
            if (!$this->mobile)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mobile empty '.json_encode($_logs));
                $this->addError($attribute, Yii::t('app', 'mobile_not_given'));
                return false;
            }
            
            //校验错误次数
            $cacheKey = self::getTimesErrorCacheKey($this->mobile);
            $_logs['$cacheKey'] = $cacheKey;
            if (Yii::$app->redis->exists($cacheKey))
            {
                $errorTimes = Yii::$app->redis->get($cacheKey);
                $_logs['$errorTimes'] = $errorTimes;
            
                if ($errorTimes > 5)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $errorTimes '.json_encode($_logs));
                    $this->addError($attribute, Yii::t('app', 'user_code_error_excessive'));
                    return false;
                }
            }

            $cacheKey = self::getCacheKey($this->mobile);
            if (!Yii::$app->cache->exists($cacheKey))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' verifycode_timeout '.json_encode($_logs));
                $this->addError($attribute,Yii::t('app', 'user_verifycode_timeout'));
                return false;
            }
            
            $mobileCode = Yii::$app->cache->get($cacheKey);
            if ($mobileCode != $this->mobileCode)
            {
                //增加错误数
                Yii::$app->redis->incr($cacheKey);
                Yii::$app->redis->expire($cacheKey, 600);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' verifycode_incorrect '.json_encode($_logs));
                $this->addError($attribute,Yii::t('app', 'user_verifycode_incorrect'));
                return false;
            }
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
    }

    public static function generateCode($mobile)
    {
        $cacheKey = self::getCacheKey($mobile);
        $mobileCode = NumberHelper::generate_random_number(6, 6);
        $_logs['$mobileCode'] = $mobileCode;
        
        Yii::$app->cache->set($cacheKey, $mobileCode, Yii::$app->params['mobilecode_timeout']);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $mobileCode;
    }
    
}