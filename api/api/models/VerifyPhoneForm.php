<?php

namespace api\models;

use Yii;
use yii\base\Model;
/**
 * 验证密码表单
 */
class VerifyPhoneForm extends Model
{
    public $phone;
    public $phoneCode;
    const MAX_ERROR_TIMES = 3;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['phone', 'required'],
            ['phone', 'match', 'pattern'=>'/^1[345789]{1}\d{9}$/', 'message'=> Yii::t('app', 'phone_format_error')],
            
            ['phoneCode', 'required'],
            ['phoneCode', 'string', 'min' => 6, 'max' => 6],
            ['phoneCode', 'checkPhoneCode'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'phoneCode' => '短信验证码',
        ];
    }
    
    public function checkPhoneCode($attribute, $params)
    {
        $_logs = [];
        
        if (!$this->hasErrors())
        {
            //------------------
            //校验错误数
            //------------------
            
            if (!$this->phone)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone empty '.json_encode($_logs));
                $this->addError($attribute, Yii::t('app', 'phone_not_given'));
                return false;
            }
            
            $cackeyKey = 'phonecode:'.$this->phone;
            if (!Yii::$app->cache->exists($cackeyKey))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone timeout '.json_encode($_logs));
                $this->addError($attribute,Yii::t('app', 'verifcode_invalid'));
                return false;
            }
            
            $phoneCode = Yii::$app->cache->get($cackeyKey);
            if ($phoneCode != $this->phoneCode)
            {
                //----------------
                //增加错误数
                //---------------
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' verifcode_incorrect '.json_encode($_logs));
                $this->addError($attribute,Yii::t('app', 'verifcode_incorrect'));
                return false;
            }
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
    }
    
}