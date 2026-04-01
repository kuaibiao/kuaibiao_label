<?php

namespace api\models;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\UserRecord;
use common\models\UserStat;
use common\models\UserDevice;
use common\models\App;
use common\components\ActionUserFilter;
use common\helpers\HttpHelper;
use common\helpers\FormatHelper;
use common\helpers\SecurityHelper;
use common\models\SiteUser;
use common\models\AuthItem;
use common\models\Thirdparty;
use common\models\ThirdpartyUser;

/**
 * Login form
 */
class ThirdpartyLoginForm extends Model
{
    public $appid;
    public $openid;
    public $site_id;
    public $nickname;
    public $nonce;
    public $timestamp;
    public $appVersion;
    public $deviceName;
    public $deviceNumber = 'thirdparty';
    public $deviceToken;
    public $systemVersion;
    public $verifyCodeKey;
    public $screenRp;
    public $osVersion;
    public $login_from; //登录来源
    public $site_info = []; //租户信息
    public $sign;
    public $reset;

    private $_user;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['sign', 'required'],
            ['sign', 'string', 'max' => 32],

            ['appid', 'required'],
            ['appid', 'string', 'max' => 32],
            ['appid','checkThirdparty'],

            //['site_id', 'required'],
            ['site_id', 'integer'],
            
            ['nickname', 'string', 'max' => 64],
            
            ['nonce', 'required'],
            ['nonce', 'string', 'max' => 32],
            
            ['timestamp', 'required'],
            ['timestamp', 'integer'],

            //获取用户信息
            ['openid', 'required'],
            ['openid', 'string', 'max' => 64],
            
            //['appVersion', 'required'],
            ['appVersion', 'string', 'max' => 64],
            ['appVersion', 'default', 'value' => '1.0.0'],
            
                     
            //['deviceName', 'required'],
            ['deviceName', 'string', 'max' => 64],

            //['deviceNumber', 'required'],
            ['deviceNumber', 'string', 'max' => 64],
            ['deviceNumber', 'default', 'value' => '1'],

            ['deviceToken', 'string', 'max' => 64],
            
            ['systemVersion', 'string', 'max' => 64],
            ['systemVersion', 'default', 'value' => ''],

            ['screenRp', 'trim'],
            ['screenRp', 'string', 'max' => 127],

            ['systemVersion', 'trim'],
            ['systemVersion', 'string', 'max' => 127],

            ['osVersion', 'trim'],
            ['osVersion', 'string', 'max' => 127],

            ['login_from', 'integer'],
            //['login_from', 'default', 'value' => self::LOGIN_FROM_DEFAULT],
            //['login_from', 'in', 'range' => [self::LOGIN_FROM_DEFAULT, self::LOGIN_FROM_FORGET_PW]],

            ['reset', 'integer'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'deviceName' => Yii::t('app', 'loginForm_field_device_name'),
            'device_number' => Yii::t('app', 'loginForm_field_device_number'),
            'device_token' => Yii::t('app', 'loginForm_field_device_token'),
            'appVersion' => Yii::t('app', 'loginForm_field_app_version'),
        ];
    }
    
    public function checkThirdparty($attribute, $params)
    {
        $_logs = ['$this.getAttributes' => $this->getAttributes()];

        if (!$this->hasErrors()) {
            if(!$this->sign){
                $this->addError($attribute, Yii::t('app', 'sign_check_fail'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' sign_check_fail ' . json_encode($_logs));
                return false;
            }
            
            if (!$this->openid) {
                $this->addError($attribute, Yii::t('app', 'openid_check_fail'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' openid_check_fail ' . json_encode($_logs));
                return false;
            }

            if(!$this->appid){
                $this->addError($attribute, Yii::t('app', 'appid_check_fail'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' appid_check_fail ' . json_encode($_logs));
                return false;
            }
            $thirdparty = Thirdparty::find()->where(['appid' => $this->appid])->asArray()->one();
            if (empty($thirdparty)){
                $this->addError($attribute, Yii::t('app', 'thirdparty_check_fail'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' thirdparty_check_fail ' . json_encode($_logs));
                return false;
            }
            if (!$thirdparty['site_id']) {
                $this->addError('appid', Yii::t('app', 'thirdparty_not_found'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' add_thirdpart_user_fail ' . json_encode($_logs));
                return false;
            }
            
            if (empty($this->site_id)) {
                $this->site_id = $thirdparty['site_id'];
            }
            
            /*
            if (strpos($this->openid, 'sz') === 0) {
                ;
            }else{
                $sign = SecurityHelper::hashHmacSign($this->appid . $this->openid . $this->nonce . $this->timestamp, $thirdparty['appsecret']);
                if($sign != $this->sign){
                    $_logs['$sign'] = $sign;
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' sign_check_fail ' . json_encode($_logs));
                    
                    $signStr = $this->appid . $this->openid . $this->nonce . $this->timestamp . $thirdparty['appsecret'];
                    $_logs['$signStr'] = $signStr;
                    
                    $sign = md5($signStr);
                    
                    if($sign != $this->sign){
                        $this->addError($attribute, Yii::t('app', 'sign_check_fail'));
                        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' sign_check_fail ' . json_encode($_logs));
                        return false;
                    }
                }
            }*/

            $thirdpartyUser = ThirdpartyUser::find()->where(['thirdparty_openid' => $this->openid])->asArray()->one();
            // print_r($thirdpartyUser);die;
            if(empty($thirdpartyUser)){
                
                if (empty($this->site_id)){
                    $this->addError($attribute, Yii::t('app', 'thirdparty_login_no_param_site_id'));
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' add_site_user_fail ' . json_encode($_logs));
                    return false;
                }
                
                $newUser = new User();
                $newUser->type = User::TYPE_WORKER;
                $newUser->nickname = ($this->nickname ? $this->nickname : $this->openid);
                $newUser->email = $this->openid.'-'.$thirdparty['site_id'].'@labeltool.com.cn';
                $newUser->created_at = time();
                $newUser->status = User::STATUS_ACTIVE;
                if (!$newUser->save()) {
                    $_logs['errors'] = $newUser->getErrors();
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' add_user_fail ' . json_encode($_logs));
                    return false;
                }

                $newSiteUser = new SiteUser();
                $newSiteUser->user_id = $newUser->id;
                $newSiteUser->site_id = $this->site_id;
                $newSiteUser->status = SiteUser::STATUS_ENABLE;
                $newSiteUser->created_at = time();
                if (!$newSiteUser->save()) {
                    $_logs['errors'] = $newUser->getErrors();
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' add_site_user_fail ' . json_encode($_logs));
                    return false;
                }

                $newThirdpartyUser = new ThirdpartyUser();
                $newThirdpartyUser->thirdparty_id = $thirdparty['id'];
                $newThirdpartyUser->thirdparty_openid = $this->openid;
                $newThirdpartyUser->created_at = time();
                $newThirdpartyUser->user_id = $newUser->id;
                if (!$newThirdpartyUser->save()) {
                    $_logs['errors'] = $newUser->getErrors();
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' add_thirdpart_user_fail ' . json_encode($_logs));
                    return false;
                }
            }
            
            $user = $this->getUser();
            //没有用记创建
            if (!$user) {
                $this->addError($attribute, Yii::t('app', 'user_not_exist'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_not_exist ' . json_encode($_logs));
                return false;
            }
            
            if (!empty($this->nickname) && $user->nickname != $this->nickname) {
                $user->nickname = $this->nickname;
                if (!$user->save()) {
                    $_logs['errors'] = $user->getErrors();
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' add_user_fail ' . json_encode($_logs));
                    return false;
                }
            }

            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
            return true;
        }
    }
    
    public function checkApp($attribute, $params)
    {
        $_logs = ['$this.getAttributes' => $this->getAttributes()];

        if (!$this->hasErrors()) {

            if (empty($this->appVersion)) {
                $this->appVersion = APP::APP_VERSION_DEFAULT;
            }

            $app = App::getApp($this->appKey, $this->appVersion);
            if (!$app) {
                $this->addError($attribute, Yii::t('app', 'app_check_fail'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' app_check_fail ' . json_encode($_logs));
                return false;
            }

            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
            return true;
        }
    }
        
    
    /**
     * Logs in a user using the provided mobile and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        $_logs =  [];
        //$_logs['$this.getAttributes'] = $this->getAttributes();

        if (!$this->validate()) {
            $errors = $this->getFirstErrors();
            $error = key($errors);
            $message = current($errors);

            $_logs['$model.$errors'] = $errors;
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;

            $this->addError('mobile', $message);
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' validate error ' . json_encode($_logs));
            return false;
        }
        
        //避免未输入任何密码，验证码跳过验证登录
        if (empty($this->appid)) {
            $this->addError('openid', Yii::t('app', 'login_appid_or_openid_fail'));
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' login_appid_or_openid_fail ' . json_encode($_logs));
            return false;
        }
        if (empty($this->openid)) {
            $this->addError('openid', Yii::t('app', 'login_appid_or_openid_fail'));
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' login_appid_or_openid_fail ' . json_encode($_logs));
            return false;
        }
        
        //---------------------------------------

        
        //---------------------------------------

        $user = $this->getUser();
        //不存在添加
        if (empty($user)) {
            $this->addError('mobile', Yii::t('app', 'login_username_or_password_fail'));
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' username_or_password_fail ' . json_encode($_logs));
            return false;
        }
        //$_logs['$user'] = $user->getAttributes();
        //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get user '.json_encode($_logs));

        //检测用户是否拥有角色, 老数据迁移的用户无角色, 需要自动补充.
        if (empty($user->roleKeys)) {
            
            //给用户分配角色
            $auth = Yii::$app->authManager;
            $rolenames = [AuthItem::ROLE_WORKER];
            foreach ($rolenames as $rolename)
            {
                $role = $auth->getRole($rolename);
                if (!$role)
                {
                    $role = $auth->createRole($rolename);
                    $role->description = $rolename;
                    $auth->add($role);
                }
                $auth->assign($role, $user->id);
            }
            
            $auth->clearAssignmentsCache($user->id);
            
            //$this->addError('mobile', Yii::t('app', 'user_not_assign_role'));
            //Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_not_assign_role ' . json_encode($_logs));
            //return false;
        }

        //---------------------------------------

        //app不支持同时多端登录,先将所有手机端登录下线
//         if (UserDevice::getDeviceTypeByPlatform($app['platform']) == UserDevice::DEVICE_TYPE_MOBILE) {
//             UserDevice::updateAll(['status' => UserDevice::STATUS_OFF_LINE], [
//                 'device_type' => UserDevice::DEVICE_TYPE_MOBILE,
//                 'user_id' => $user->id
//             ]);
//         }

        //该账号是否已在线
//         $userDeviceOnline = UserDevice::find()
//             ->select(UserDevice::privateFields())
//             ->where([
//                 'user_id' => $user->id,
//                 'device_type' => UserDevice::DEVICE_TYPE_PC,
//                 'status' => UserDevice::STATUS_ON_LINE
//             ])
//             ->orderBy(['id' => SORT_DESC])->limit(1)->asArray()->one();
//         $_logs['$userDeviceOnline'] = ArrayHelper::var_desc($userDeviceOnline);

//         //存在同账号有用户登录在线情况
//         if ($userDeviceOnline) {

            /*7.11注释
            $isCanLogin = UserDevice::isCanLogin($this->deviceNumber, $userDeviceOnline, $this->login_from);
            if ($isCanLogin == -1) {
                $this->addError('isCanLogin_error', Yii::t('app', 'isCanLogin_error'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' isCanLogin_error ' . json_encode($_logs));
                return false;
            }

            //不被允许登录
            if (empty($isCanLogin)) {

                //被主动拒绝
                if ($userDeviceOnline['reply_status'] == UserDevice::REPLY_STATUS_REFUSE) {
                    $this->addError('user_login_reply_refuse', Yii::t('app', 'user_login_reply_refuse'));
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_login_reply_refuse ' . json_encode($_logs));
                    return false;
                }

                $this->addError('user_login_reply_fail', Yii::t('app', 'user_login_reply_fail'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_login_reply_fail ' . json_encode($_logs));
                return false;
            }

            //加锁机制，避免多个用户同时登录
            $lockKey = Yii::$app->redis->buildKey('user:loginAllow:' . $user->id);
            //成功返回 1,失败返回0
            if (!Yii::$app->redis->setnx($lockKey, 1)) {
                $this->addError('user_login_reply_fail_lock', Yii::t('app', 'user_login_reply_fail'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_login_reply_fail ' . json_encode($_logs));
                return false;
            }
            Yii::$app->redis->expire($lockKey, 30);

            //该用户的其他同设备标记为下线
            UserDevice::updateAll(['status' => UserDevice::STATUS_OFF_LINE], [
                'device_type' => UserDevice::DEVICE_TYPE_PC,
                'user_id' => $user->id
            ]);*/
//         }

        //同设备号的其他用户，标记为下线,清除对应缓存
        /*
        $userDevicesOnline = UserDevice::find()
            ->select(['id', 'user_id'])
            ->where([
                'device_type' => UserDevice::DEVICE_TYPE_PC,
                'device_number' => $this->deviceNumber,
                'status' => UserDevice::STATUS_ON_LINE
            ])->asArray()->all();
        if ($userDevicesOnline) {
            $deviceIdsOnline = array_column($userDevicesOnline, 'id');
            UserDevice::updateAll(['status' => UserDevice::STATUS_OFF_LINE], ['in', 'id', $deviceIdsOnline]);

            $userIdsOnline = array_column($userDevicesOnline, 'user_id');
            foreach ($userIdsOnline as $userIdOnline) {
                //获取用户在线的access_token
                $cacheKeyAccessToken = Yii::$app->redis->buildKey('user:online_accesstoken:' . $userIdOnline);
                $_logs['$cacheKeyAccessToken'] = $cacheKeyAccessToken;
                if (Yii::$app->redis->exists($cacheKeyAccessToken)) {
                    Yii::$app->redis->del($cacheKeyAccessToken);
                }
            }
        }*/

        //---------------------------------------

        $userDevice = UserDevice::find()->where(['user_id' => $user->id, 'device_number' => $this->deviceNumber])->limit(1)->one();
        if (empty($userDevice))
        {
            $userDevice = new UserDevice();
            $userDevice->user_id = $user->id;
            $userDevice->device_number = $this->deviceNumber;
        }
        $userDevice->device_name = $this->deviceName;
        /*
        $userDevice->device_token = (string)$this->deviceToken;
        $userDevice->device_type = 0;
        $userDevice->system_version = $this->systemVersion;
        $userDevice->browser_version = !empty($this->systemVersion) ? HttpHelper::get_broswer() . '|' . $this->systemVersion : HttpHelper::get_broswer();
        $userDevice->os_version = !empty($this->osVersion) ? HttpHelper::get_os() . '|' . $this->osVersion : HttpHelper::get_os();
        $userDevice->reply_status = UserDevice::REPLY_STATUS_DEFAULT;
        $userDevice->reply_begin_time = 0;
        $userDevice->reply_device_number = '';
        $userDevice->request_count = intval($userDevice->request_count) + 1;
        $userDevice->status = UserDevice::STATUS_ON_LINE;
        $userDevice->screen_rp = isset($this->screenRp) ? $this->screenRp : '';
        */
        //$userDevice->user_ip = Yii::$app->request->getUserIP();
        $userDevice->updated_at = time();
        
        if (!$userDevice->validate() || !$userDevice->save()) {
            $errors = $userDevice->getFirstErrors();
            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$userDevice'] = $errors;
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' ' . $error . ' ' . json_encode($_logs));
            return false;
        }

        //更新登录时间和ip
        $userStat = UserStat::find()->where(['user_id' => $user->id])->one();
        if (empty($userStat))
        {
            $userStat = new UserStat();
            $userStat->user_id = $user->id;
            $userStat->save();
        }

        //标记为第一次登录
        /*
        if (empty($userStat->login_last_time)) {
            $this->is_first_login = 1;

            //用户为第一次登录，获取是否为租户下所有用户的第一次登录
            if (!empty($user->site->id)) {
                $userIds_ = SiteUser::find()->select(['user_id'])->where(['site_id' => $user->site->id])->asArray()->column();
                //没必要过多用户分到一组，大多数情况下，租户下用户都已登录过
                $userChunkIds = array_chunk($userIds_, 50);
                $this->is_site_first_login = 1;
                foreach ($userChunkIds as $userChunkId) {
                    $userStatCount_ = UserStat::find()
                        ->where(['in', 'user_id', $userChunkId])
                        ->andWhere(['>', 'login_last_time', 0])
                        ->andWhere(['!=', 'id', $userStat->id])
                        ->count('id');
                    $_logs['$userStatCount_'] = $userStatCount_;
                    if (!empty($userStatCount_)) {
                        $this->is_site_first_login = 0;
                        break;
                    }
                }
            }
        }*/

        // $userStat->login_last_platform = 0;
        //$userStat->login_last_device_id = $userDevice->id;
        $userStat->login_last_time = time();
        //$userStat->login_last_ip = (string)Yii::$app->request->getUserIP();
        //$userStat->login_last_useragent = (string)Yii::$app->request->getUserAgent();
        if (!$userStat->validate() || !$userStat->save()) {
            $errors = $userStat->getFirstErrors();
            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$userStat'] = $errors;
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' ' . $error . ' ' . json_encode($_logs));
        }
        // 更新用户所在团队的活跃时间
//         $teamUser = TeamUser::findOne(['user_id' => $user->id, 'status' => TeamUser::STATUS_ENABLE]);
//         if (!empty($teamUser)) {
//             Team::updateAll(['active_time' => time()], ['id' => $teamUser->team_id]);
//         }

        //校验access token
        if (!User::isAccessTokenValid($user->access_token))
        {
            $user->access_token = User::generateAccessToken($user->id, Yii::$app->request->getUserIP());
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' generate new '.json_encode($_logs));
            
            $attributes = [
                'access_token' => $user->access_token,
                'updated_at' => time()
            ];
            User::updateAll($attributes, ['id' => $user->id]);
        }

        //使用手机号码登录的判断是否激活
        /*if ($this->_loginBy == self::LOGIN_BY_MOBILE && $this->_safetyBy == self::SAFETY_BY_MOBILECODE) {
            if (!$user->is_verify_mobile) {
                $attributes = [
                    'is_verify_mobile' => 1,
                    'updated_at' => time()
                ];
                User::updateAll($attributes, ['id' => $user->id]);
            }
        }*/
        //使用邮箱登录判断是否激活
//         elseif ($this->_loginBy == self::LOGIN_BY_EMAIL && $this->_safetyBy == self::SAFETY_BY_EMAILCODE) {
//             if (!$user->is_verify_email) {
//                 $attributes = [
//                     'is_verify_email' => 1,
//                     'updated_at' => time()
//                 ];
//                 User::updateAll($attributes, ['id' => $user->id]);
//             }
//         }

        //兼容老系统, 处理返回值
//         $user->access_token = $userDevice['access_token'];
//         $user->save();

        //执行登录
        Yii::$app->user->loginByAccessToken($user->access_token);

        //刷新用户登录凭证
        ActionUserFilter::refreshLoginStatus($user->id);

        //保存用户操作记录
        //UserRecord::loginRecord($user->id);
        
        //初始化当天登录记录
        //UserStatDay::userStatDayInit($user->id);

        //删除lockkey
        //if (isset($lockKey)) {
            //Yii::$app->redis->del($lockKey);
        //}

        //登录成功，清空错误次数的缓存记录
        //LoginForm::clearErrorTimesCache($user->id);
        
        User::clearCache($user->id);

        Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
        return [
            'id' => $user->id, 
            'access_token' => $user->access_token
        ];
    }

    /**
     * Finds user by [[mobile]]
     *
     * @return User|null
     */
    function getUser()
    {
        if ($this->_user === null) {
            $thirdparty = ThirdpartyUser::find()->where(['thirdparty_openid' => $this->openid])->asArray()->one();
            if (empty($thirdparty)) {
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' thirdparty_check_fail ' . json_encode(['thirdparty_openid' => $this->openid]));
            }else{
                $this->_user = User::findIdentity($thirdparty['user_id']);
            }
            
        }

        return $this->_user;
    }
    
    public static function getErrorTimesCacheKey($mobile)
    {
        return Yii::$app->redis->buildKey('login:errorTimes:' . $mobile);
    }
    
    /**
     * 清除错误次数记录的缓存
     * Date: 2021-07-06 16:37:31
     * @param $mobile
     * @return bool
     */
    public static function clearErrorTimesCache($mobile)
    {
        $cacheKey = self::getErrorTimesCacheKey($mobile);
        
        if (Yii::$app->redis->exists($cacheKey)) {
            Yii::$app->redis->del($cacheKey);
        }
        return true;
    }


    public static function parseDeviceCache($cache)
    {
        $resultCheckVerify = SecurityHelper::encrypt_decode($cache);
        $_logs['$resultCheckVerify'] = $resultCheckVerify;
        if (empty($resultCheckVerify)) {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' verify_code_user_device_empty ' . json_encode($_logs));
            return FormatHelper::resultStrongType('', 'verify_code_user_device_empty', Yii::t('app', 'verify_code_user_device_empty'));
        }

        $verifyCodeKeys = explode(':', $resultCheckVerify);
        if (count($verifyCodeKeys) != 3) {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' system_error ' . json_encode($_logs));
            return FormatHelper::resultStrongType('', 'system_error', Yii::t('app', 'system_error'));
        }
        $userDeviceOnlineId = $verifyCodeKeys[1];

        $result = [
            'user_device_online_id' => $userDeviceOnlineId,
            'cache_key' => $resultCheckVerify,
        ];
        return FormatHelper::resultStrongType($result);
    }
}
