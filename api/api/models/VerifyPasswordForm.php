<?php

namespace api\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * 验证密码表单
 */
class VerifyPasswordForm extends Model
{
    public $password;
    private $_user;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['password', 'required'],
            ['password', 'string', 'min' => 6, 'max' => 30],
            ['password', 'match', 'pattern'=>User::getPasswordRegex(),'message'=> yii::t('app', 'password_format_error')],
            ['password', 'validatePassword'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'password' => '密码'
        ];
    }
    
    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        $_logs = [];
        
        if (!$this->hasErrors())
        {
            //校验错误次数
            $cackeyKey = Yii::$app->redis->buildKey('user:verifyErrorTimes:'.Yii::$app->user->id);
            $_logs['$cackeyKey'] = $cackeyKey;
            
            if (Yii::$app->redis->exists($cackeyKey))
            {
                $errorTimes = Yii::$app->redis->get($cackeyKey);
                $_logs['$errorTimes'] = $errorTimes;
                
                if ($errorTimes > 3)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $errorTimes '.json_encode($_logs));
                    $this->addError($attribute,  yii::t('app', 'password_fail_excessive'));
                    return false;
                }
            }
            
            //-------------------------------
            //判断密码正确性
            //-------------------------------
            $user = $this->getUser();
            
            //密码的加密算法使用了600ms多
            if (!$user || !$user->validatePassword($this->password))
            {
                Yii::$app->redis->incr($cackeyKey);
                Yii::$app->redis->expire($cackeyKey, 300);
                
                $this->addError($attribute, yii::t('app', 'password_incorrect'));
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' password_incorrect '.json_encode($_logs));
                return false;
            }
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findIdentity(Yii::$app->user->id);
        }
    
        return $this->_user;
    }
}
