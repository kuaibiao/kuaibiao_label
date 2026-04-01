<?php
namespace api\models;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\UserRecord;
use common\models\UserStat;
use common\models\UserDevice;
use common\models\App;
use common\models\AppStat;
use common\components\ActionUserFilter;


/**
 * Login form
 */
class LoginQuickForm extends Model
{
    public $access_token;
    public $device_name;
    public $device_number;
    public $device_token;
    public $app_key;
    public $app_version;
    
    private $_user;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['access_token', 'required'],
            ['access_token', 'string', 'min' => 6, 'max' => 254],
            ['access_token', 'filter', 'filter' => 'trim'],
            ['access_token', 'checkAccessToken'],
            
            //['device_name', 'required'],
            ['device_name', 'string', 'max' => 30],
            
            //['device_number', 'required'],
            ['device_number', 'string', 'max' => 64],
            
            ['device_token', 'string', 'max' => 64],
            
            ['app_key', 'required'],
            ['app_key', 'string', 'max' => 64],
            ['app_key', 'checkApp'],
            
            ['app_version', 'required'],
            ['app_version', 'string', 'max' => 64],
        ];
    }
    
    public function checkApp($attribute, $params)
    {
        $_logs = ['appKey' => $this->app_key];
    
        if (!$this->hasErrors())
        {
            $app = App::find()->where(['app_key' => $this->app_key, 'app_version' => $this->app_version])->asArray()->limit(1)->one();
            if (!$app)
            {
                $this->addError($attribute, Yii::t('app', 'app_check_fail'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' app_check_fail '.json_encode($_logs));
                return false;
            }
            
            $user = $this->getUser();
            if (!$user)
            {
                $this->addError($attribute, Yii::t('app', 'user_not_exist'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_exist '.json_encode($_logs));
                return false;
            }
    
            if ((int)$user->type !== (int)$app['user_type'])
            {
                $this->addError($attribute, Yii::t('app', 'user_no_permission'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
                return false;
            }
            else
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
                return true;
            }
        }
    }
    
    public function checkAccessToken($attribute, $params)
    {
        $_logs = ['access_token' => $this->access_token, 'startTime' => microtime(true)];
        
        if (!$this->hasErrors())
        {
            $user = $this->getUser();
            if (!$user)
            {
                $this->addError($attribute, Yii::t('app', 'user_not_exist'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_exist '.json_encode($_logs));
                return false;
            }
            
            if ($user->status == User::STATUS_NOTACTIVE)
            {
                $this->addError($attribute, Yii::t('app', 'user_auditing'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_auditing '.json_encode($_logs));
                return false;
            }
            elseif($user->status != User::STATUS_ACTIVE)
            {
                $this->addError($attribute, Yii::t('app', 'user_unavailable'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_unavailable '.json_encode($_logs));
                return false;
            }
            else
            {
                $_logs['usedTime'] = microtime(true) - $_logs['startTime'];
                
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
                return true;
            }
        }
    }
    
    /**
     * Logs in a user using the provided access_token and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        $_logs =  [];
        
        $user = $this->getUser();
        $_logs['$user'] = $user;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get user '.json_encode($_logs));
        
        if (!$user)
        {
            $this->addError('access_token', Yii::t('app', 'user_not_exist'));
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user not exist '.json_encode($_logs));
            return false;
        }
        
        //---------------------------------------
        
        $app = App::find()->where(['app_key' => $this->app_key, 'app_version' => $this->app_version])->asArray()->limit(1)->one();
        if (!$app)
        {
            $this->addError('app_key', Yii::t('app', 'app_check_fail'));
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' app_check_fail '.json_encode($_logs));
            return false;
        }
        
        //app请求记录
        $appStat = AppStat::find()->where(['app_id' => $app['id'], 'date' => date('Y-m-d')])->asArray()->limit(1)->one();
        if ($appStat)
        {
            $counters = ['count' => 1];
            AppStat::updateAllCounters($counters, ['id' => $appStat['id']]);
        }
        else
        {
            $appStat = new AppStat();
            $appStat->app_id = $app['id'];
            $appStat->date = date('Y-m-d');
            $appStat->count = 1;
            $appStat->save();
        }
        
        //检测账户信息是否完整
        if (empty($user->roleKeys))
        {
            $this->addError('access_token', Yii::t('app', 'user_not_assign_role'));
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_assign_role '.json_encode($_logs));
            return false;
        }
        
        //---------------------------------------
        
        //更新用户设备
        $userDevice = UserDevice::find()->where(['user_id' => $user->id, 'device_number' => $this->device_number])->asArray()->limit(1)->one();
        if ($userDevice)
        {
            $counters = [
                'request_count' => 1,
                'updated_at' => (time() - $userDevice['updated_at'])
            ];
            UserDevice::updateAllCounters($counters, ['id' => $userDevice['id']]);
            
            //设备token变更
            if ($userDevice['device_token'] != $this->device_token || $userDevice['app_key'] != $this->app_key || $userDevice['app_version'] != $this->app_version)
            {
                $attributes = [
                    'device_token' => (string)$this->device_token,
                    'app_key' => $this->app_key,
                    'app_version' => $this->app_version,
                    'updated_at' => time()
                ];
                UserDevice::updateAll($attributes, ['id' => $userDevice['id']]);
            }
        }
        else
        {
            $userDevice = new UserDevice();
            $userDevice->user_id = $user->id;
            $userDevice->device_name = $this->device_name;
            $userDevice->device_number = $this->device_number;
            $userDevice->device_token = (string)$this->device_token;
            $userDevice->app_key = (string)$this->app_key;
            $userDevice->app_version = $this->app_version;
            $userDevice->request_count = 1;
            $userDevice->created_at = time();
            $userDevice->save();
        }
        
        //更新登录时间和ip
        $userStat = UserStat::find()->where(['user_id' => $user->id])->asArray()->limit(1)->one();
        if (!$userStat)
        {
            $userStat = new UserStat();
            $userStat->user_id = $user->id;
            $userStat->save();
        }
        $attributes = [
            'login_last_platform' => $app['platform'],
            'login_last_device_id' => $userDevice['id'],
            'login_last_time' => time(),
            'login_last_ip' => (string)Yii::$app->request->getUserIP(),
            'login_last_useragent' => (string)Yii::$app->request->getUserAgent(),
        ];
        UserStat::updateAll($attributes, ['user_id' => $user->id]);
        
        //校验access token
        if (!User::isAccessTokenValid($user->access_token))
        {
            $user->access_token = User::generateAccessToken($user->id, Yii::$app->request->getUserIP(), $this->app_key, $this->app_version);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' generate new '.json_encode($_logs));
            
            $attributes = [
                'access_token' => $user->access_token,
                'updated_at' => time()
            ];
            User::updateAll($attributes, ['id' => $user->id]);
        }
        
        //执行登录
        Yii::$app->user->loginByAccessToken($user->access_token);
        
        //刷新用户登录凭证
        ActionUserFilter::refreshLoginStatus();
        
        //保存用户操作记录
        $userRecord = new UserRecord();
        $result = $userRecord->saveRecord('login', $user->id, '');
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $user;
    }

    /**
     * Finds user by [[access_token]]
     *
     * @return User|null
     */
    function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::find()->where(['access_token' => $this->access_token])->limit(1)->one();
        }
    
        return $this->_user;
    }

}