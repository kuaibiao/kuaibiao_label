<?php
namespace api\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * Password reset request form
 */
class ResetPasswordForm extends Model
{
    public $email;
    public $verify_code;
    public $password;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'required'],
            ['verify_code', 'required'],
            
            ['password', 'required'],
            ['password', 'string', 'min' => 6, 'max' => 32],
            ['password', 'match', 'pattern' => User::getPasswordRegex(), 'message'=> Yii::t('app', 'password_format_error')],
            
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'email'=> Yii::t('app', 'resetPasswordForm_field_confirmpassword'),
            'password' => Yii::t('app', 'resetPasswordForm_field_password'),
        ];
    }
    
    /**
     *  update password.
     *
     * @return bool if password was update.
     */
    public function resetPassword()
    {
        $_logs = [];
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        $cacheKey = 'emailcode:'.$this->email;
        $emailCode = Yii::$app->cache->get($cacheKey);
        if (!$emailCode)
        {
            $this->addError(['email' => Yii::t('app', 'resetPasswordForm_verify_code_notexist')]);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' validate fail '.json_encode($_logs));
            return false;
        }
        
        if ($emailCode != $this->verify_code)
        {
            $this->addError(['email' => Yii::t('app', 'resetPasswordForm_verify_code_error')]);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' validate fail '.json_encode($_logs));
            return false;
        }
        
        $user = User::find()->where(['email' => $this->email])->limit(1)->one();
        if (!$user)
        {
            $this->addError(['email' => Yii::t('app', 'email_not_exist')]);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' validate fail '.json_encode($_logs));
            return false;
        }
        
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->created_at = time();
        $user->updated_at = time();
        
        if (!$user->validate())
        {
            $_logs['$user$Errors'] = $user->getErrors();
            $this->addErrors($user->getErrors());
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' validate fail '.json_encode($_logs));
            return false;
        }
        if (!$user->save())
        {
            $_logs['$user$Errors'] = $user->getErrors();
            $this->addErrors($user->getErrors());
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' save fail '.json_encode($_logs));
            return false;
        }
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
}
