<?php
namespace api\models;

use yii;
use yii\base\Model;
use common\models\User;
use common\models\UserAttribute;
use common\models\UserStat;
use common\models\UserRecord;
use common\models\Message;
use common\models\AuthItem;
use common\models\Site;
use common\models\SiteUser;
use common\helpers\StringHelper;

/**
 * Signup form
 */
class RegisterForm extends Model
{
    public $company;
    public $email;
    public $mobile;
    //public $type;
    public $password;
    public $verifyCodeKey;
    public $verifyCode;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['company', 'string', 'max' => 32],
            ['company', 'default', 'value' => ''],
            
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'validateEmail'],
            
            //['type', 'required'],
            //['type', 'in', 'range' => [User::TYPE_ADMIN, User::TYPE_WORKER]],
            
            ['mobile', 'required'],
            ['mobile', 'match', 'pattern'=>User::getMobileRegex(),'message'=> Yii::t('app', 'phone_format_error')],
            //['mobile', 'checkPhone'],
            
            ['password', 'required'],
            ['password', 'string', 'min' => 6, 'max' => 32],
            ['password', 'match', 'pattern' => User::getPasswordRegex(), 'message'=> Yii::t('app', 'password_format_error')],

            ['verifyCodeKey', 'required'],
            ['verifyCode', 'required'],
            ['verifyCode', 'checkCaptcha'],
            
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'phone' => '手机号码',
            'phoneCode' => '短信验证码',
            'password' => '密码',
            'verifyCode'=> '图片验证码',
        ];
    }
    
    public function validateEmail($attribute, $params)
    {
        $_logs = ['email' => $this->email];
    
        if (!$this->hasErrors())
        {
            $user_ = User::find()->select(['id', 'email'])->where(['email' => $this->email])->asArray()->limit(1)->one();
            if ($user_)
            {
                $this->addError($attribute, Yii::t('app', 'email_existed'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_existed '.json_encode($_logs));
                return false;
            }
    
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
    }
    
    public function checkCaptcha($attribute, $params)
    {
        $_logs = ['verifyCodeKey' => $this->verifyCodeKey, 'verifyCode' => $this->verifyCode];
    
        if (!$this->hasErrors())
        {
            $cackeyKey = $this->verifyCodeKey;
            if (!Yii::$app->cache->exists($cackeyKey))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone timeout '.json_encode($_logs));
                $this->addError($attribute,Yii::t('app', 'verifcode_invalid'));
                return false;
            }
    
            $captchaCode = Yii::$app->cache->get($cackeyKey);
            if ($captchaCode != $this->verifyCode)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' verifcode_incorrect '.json_encode($_logs));
                $this->addError($attribute,Yii::t('app', 'verifcode_incorrect'));
                return false;
            }
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
    }

    /**
     * Signs user up.
     * 必须先进行 $this->validate() 校验
     *
     * @return User|null the saved model or null if saving fails
     */
    public function signup()
    {
        $_logs = [];
        
        if (!$this->validate())
        {
            $_logs['Errors'] = $this->getErrors();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' validate error '.json_encode($_logs));
            return false;
        }
        
        $trans  = Yii::$app->db->beginTransaction();
        try {
        
            $user = new User();
            $user->nickname = StringHelper::get_email_name($this->email);
            $user->type = User::TYPE_ADMIN;
            $user->status = User::STATUS_ACTIVE;
            $this->password && $user->setPassword($this->password);
            $user->email = $this->email;
            $user->mobile = $this->mobile;
            $user->company = $this->company;
            $user->generateAuthKey();
            $user->created_at = time();
            $user->updated_at = time();
            if (!$user->validate() || !$user->save())
            {
                $_logs['$user$Errors'] = $user->getErrors();
                $this->addErrors($user->getErrors());
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' save fail '.json_encode($_logs));
                return false;
            }
            $_logs['$userId'] = $user->id;
            
            //生产access_token
            $user->access_token = $user->generateAccessToken();
            
            $attributes = [
                'access_token' => $user->access_token,
                'updated_at' => time()
            ];
            User::updateAll($attributes, ['id' => $user->id]);
            
            $userAttr = new UserAttribute();
            $userAttr->user_id = $user->id;
            $userAttr->register_description = '';
            $userAttr->register_files = '';
            $userAttr->save();
            
            $userStat = new UserStat();
            $userStat->user_id = $user->id;
            $userStat->new_message_count = 0;
            $userStat->login_last_time = time();
            $userStat->login_last_ip = (string)Yii::$app->request->getUserIP();
            $userStat->save();
            
            $userRecord = new UserRecord();
            $result = $userRecord->saveRecord('signup', $user->id, '');
            
            $site = new Site();
            $site->name = $this->company;
            $site->status = Site::STATUS_ACTIVED;
            $site->created_by = $user->id;
            if (!$site->validate() || !$site->save())
            {
                $_logs['$site$Errors'] = $site->getErrors();
                $this->addErrors($site->getErrors());
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' save fail '.json_encode($_logs));
                return false;
            }
            $_logs['$siteId'] = $site->id;
            
            $siteUser = new SiteUser();
            $siteUser->site_id = $site->id;
            $siteUser->user_id = $user->id;
            $siteUser->created_by = 0;
            $siteUser->save();
            
            
            //给用户分配角色
            $rolename = AuthItem::ROLE_MANAGER;
            $auth = Yii::$app->authManager;
            $role = $auth->getRole($rolename);
            if ($role)
            {
                $auth->assign($role, $user->id);
            }
        
            //发送通知
            Message::sendUserSignupSucc($user->id);
        
            $trans->commit();
        }
        catch (\Exception $e)
        {
            $trans->rollback();
            
            $_logs['$e.message'] = $e->getMessage();
            $_logs['$e.trace'] = $e->getTraceAsString();
            
            $this->addError('company', $e->getMessage());
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' save fail '.json_encode($_logs));
            return false;
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $user;
    }
    
}
