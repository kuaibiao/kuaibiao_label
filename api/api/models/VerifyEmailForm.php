<?php

namespace api\models;

use Yii;
use yii\base\Model;
use common\helpers\NumberHelper;
use common\helpers\SecurityHelper;

/**
 * 验证邮箱表单
 */
class VerifyEmailForm extends Model
{
    public $email;
    public $emailCode;
    
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'required'],
            ['email', 'email'],
            
            ['emailCode', 'required'],
            ['emailCode', 'string', 'min' => 6, 'max' => 6],
            ['emailCode', 'checkEmailCode'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'email' => Yii::t('app', 'verifyEmailForm_field_email'),
            'emailCode' => Yii::t('app', 'verifyEmailForm_field_emailCode'),
        ];
    }
    
    public function checkEmailCode($attribute, $params)
    {
        $_logs = [];
        
        if (!$this->hasErrors())
        {
            if (!$this->email)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email empty '.json_encode($_logs));
                $this->addError($attribute, Yii::t('app', 'email_not_given'));
                return false;
            }
            
            //校验错误次数
            $cacheKey = Yii::$app->redis->buildKey('user:emailCodeErrTimes:'.$this->email);
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
            
            $cacheKey = 'emailcode:'.$this->email;
            if (!Yii::$app->cache->exists($cacheKey))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' verifycode_timeout '.json_encode($_logs));
                $this->addError($attribute,Yii::t('app', 'user_verifycode_timeout'));
                return false;
            }
            
            $emailCode = Yii::$app->cache->get($cacheKey);
            if ($emailCode != $this->emailCode)
            {
                //增加错误数
                Yii::$app->redis->incr($cacheKey);
                Yii::$app->redis->expire($cacheKey, 600);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' verifycode_incorrect '.json_encode($_logs));
                $this->addError($attribute, Yii::t('app', 'user_verifycode_incorrect'));
                return false;
            }
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
    }
    
    /**
     * Sends an email to the specified email address using the information collected by this model.
     *
     * @param string $email the target email address
     * @return bool whether the email was sent
     */
    public function send($type)
    {
        $_logs = ['$type' => $type];
        $types = ['resetPasswordCode'];
        
        if (!in_array($type, $types))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mail type error '.json_encode($_logs));
            return false;
        }
        
        $cacheKey = 'emailcode:'.$this->email;
//         if (Yii::$app->cache->exists($cacheKey))
//         {
//             $emailCode = Yii::$app->cache->get($cacheKey);
//             $emailCodeHash = SecurityHelper::generateValidationHash($emailCode);
//             Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ from cache '.json_encode($_logs));
//             return $emailCodeHash;
//         }
        
        //生成验证码
        $emailCode = NumberHelper::generate_random_number(4, 4);
        $emailCodeHash = SecurityHelper::generateValidationHash($emailCode);
        $_logs['$emailCode'] = $emailCode;
        $_logs['$emailCodeHash'] = $emailCodeHash;
        
        Yii::$app->cache->set($cacheKey, $emailCode, Yii::$app->params['emailcode_timeout']);
        
        Yii::$app->mailer->setViewPath('@common/mail');

        $isSend = Yii::$app
        ->mailer
        ->compose(
            ['html' => $type],//'html' => 'sendEmailCode-html', 
            ['email' => $this->email, 'code' => $emailCode]
            )
            //->setFrom([Yii::$app->params['supportEmail'] => Yii::t('app', 'mail_from_name')])
        ->setTo($this->email)
        ->setSubject(Yii::t('app', 'mail_subject_'.$type))
        ->send();
        $_logs['$isSend'] = $isSend;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $emailCodeHash;
    }
    
    /**
     * 生成验证码
     * @param unknown $email
     * @return string
     */
    public function generateCode($email)
    {
        $cacheKey = 'emailcode:'.$email;
        $emailCode = NumberHelper::generate_random_number(6, 6);
        Yii::$app->cache->set($cacheKey, $emailCode, Yii::$app->params['emailcode_timeout']);
        
        return $emailCode;
    }
}
