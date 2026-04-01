<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use common\components\ForbiddenWord;
use common\models\AuthAssignment;
use yii\web\ServerErrorHttpException;
use common\models\Group;
use common\models\Setting;
use common\models\GroupUser;
use common\helpers\ArrayHelper;
use common\helpers\StringHelper;

/**
 * User model
 *
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_NOTACTIVE = 0;//未激活
    const STATUS_ACTIVE = 1;//已激活
    const STATUS_DISABLE = 2;//已禁用
    const STATUS_DELETED = 3;//已删除
    
    //const TYPE_DEFAULT = 0;//默认,申请注册的
    const TYPE_ADMIN = 1;//租户管理员
    const TYPE_WORKER = 2;//作业员
    const TYPE_ROOT = 3;//超级管理员
    //const TYPE_CUSTOMER = 4;//客户(弃用)
    //const TYPE_CROWDSOURCING = 5;//众包
    
    const AUDIT_STATUS_NOVERIFY = 0;//待审核
    const AUDIT_STATUS_NOCONTACT = 1;//待跟进
    const AUDIT_STATUS_ALLOWLOGIN = 2;//待登录
    const AUDIT_STATUS_LOGGEDIN = 3;//已登录
    const AUDIT_STATUS_DISABLE = 4;//无效信息

    const VERIFY_TYPE_PASSWORD = 0;
    const VERIFY_TYPE_EMAIL = 1;
    
    const LANGUAGE_ZH_CN = 0;
    const LANGUAGE_EN = 1;

    const LANGUAGE_KEY_ZH_CN = 'zh-CN';
    const LANGUAGE_KEY_EN = 'en';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'integer'],
            
            ['type', 'integer'],
            ['type', 'default', 'value' => 0],
            
            ['status', 'integer'],
            ['status', 'default', 'value' => self::STATUS_NOTACTIVE],
            ['status', 'in', 'range' => [self::STATUS_NOTACTIVE, self::STATUS_ACTIVE, self::STATUS_DELETED, self::STATUS_DISABLE]],
            
            ['realname', 'trim'],
            ['realname', 'string', 'max' => 32],
            
            ['nickname', 'trim'],
            ['nickname', 'string', 'max' => 32],
            ['nickname', 'match', 'pattern'=> User::getNickNameRegex(), 'message'=> yii::t('app', 'user_nickname_format_error')],
            ['nickname', 'validateNickname'],
            
            ['username', 'trim'],
            ['username', 'string', 'max' => 32],
            
            ['company', 'trim'],
            ['company', 'string', 'max' => 32],
            
            ['email', 'trim'],
            ['email', 'email'],
            ['email', 'string', 'max' => 64],
            ['email', 'validateEmail'],
            
            ['mobile', 'default', 'value' => ''],
            ['mobile', 'string', 'max' => 11],
            ['mobile', 'validateMobile'],

            ['phone', 'default', 'value' => ''],
            ['phone', 'string', 'max' => 32],
            //['phone', 'match', 'pattern'=>'/^1[345789]{1}\d{9}$/', 'message'=> '手机号码不正确'],
            ['phone', 'match', 'pattern'=> User::getPhoneRegex(), 'message'=> yii::t('app', 'user_phone_format_error')],
//            ['phone', 'unique', 'targetClass' => '\common\models\User', 'message' => yii::t('app', 'user_phone_existed')],
            
            [['city', 'province', 'country'], 'string', 'max' => 30],
            [['position'], 'string', 'max' => 254],
            
            ['sex', 'default', 'value' => 0],
            ['sex','in', 'range' => [0,1,2]],
            
            ['access_token', 'string', 'max' => 254],
            ['access_token', 'default', 'value' => ''],
            
            ['verify_token', 'string', 'max' => 254],
            ['verify_token', 'default', 'value' => ''],
            
            ['language', 'integer'],

            [['avatar'], 'string', 'max' => 254],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nickname' => '昵称',
            'start_time' => '开始时间',
            'end_time' => '截止时间',
        ];
    }
    
    public function validateUsername($attribute, $params)
    {
        $_logs = ['username' => $this->username];
        
        if (!$this->hasErrors()) {
            if (empty($this->username) && empty($this->email) && empty($this->mobile)) {
                $this->addError($attribute, Yii::t('app', 'user_username_notexist'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' email_existed ' . json_encode($_logs));
                return false;
            }
            
            if (empty($this->username))
            {
                return true;
            }
            
            $user_ = User::find()
            ->select(['id', 'username'])
            ->where(['username' => $this->username, 'type' => $this->type])
            ->andWhere(['not', ['status' => User::STATUS_DELETED]])
            ->asArray()->limit(1)->one();
            if ($user_ && $this->id) {
                if ($user_['id'] != $this->id) {
                    $this->addError($attribute, Yii::t('app', 'user_username_existed'));
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' email_existed ' . json_encode($_logs));
                    return false;
                }
            } elseif ($user_ && !$this->id) {
                $this->addError($attribute, Yii::t('app', 'user_username_existed'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_username_existed ' . json_encode($_logs));
                return false;
            }
            
            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
            return true;
        }
    }
    
    public function validateEmail($attribute, $params)
    {
        $_logs = ['email' => $this->email];
        
        if (!$this->hasErrors()) {
            if (empty($this->username) && empty($this->email) && empty($this->mobile))
            {
                $this->addError($attribute, Yii::t('app', 'user_email_notexist'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' email_existed ' . json_encode($_logs));
                return false;
            }
            
            if (empty($this->email))
            {
                return true;
            }
            
            //          $user_ = User::find()
            //              ->select(['id', 'email'])
            //              ->where(['email' => $this->email, 'type' => $this->type])
            //              ->andWhere(['not', ['status' => User::STATUS_DELETED]])
            //              ->asArray()->limit(1)->one();
            
            $query_ = User::find()
            ->select(['id', 'email'])
            ->where(['email' => $this->email])
            ->andWhere(['not', ['status' => User::STATUS_DELETED]]);
            
            //20210524 模快化二期
            /*if(in_array($this->type, [self::TYPE_ADMIN, self::TYPE_TEAM]))
             {
             //admin,team不允许出现同账号问题
             $query_->andWhere(['in', 'type', [self::TYPE_ADMIN, self::TYPE_TEAM]]);
             }
             else
             {
             $query_->andWhere(['type' => $this->type]);
             }*/
            $user_ = $query_->asArray()->limit(1)->one();
            
            if ($user_ && $this->id) {
                if ($user_['id'] != $this->id)
                {
                    $this->addError($attribute, Yii::t('app', 'user_email_existed'));
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' email_existed ' . json_encode($_logs));
                    return false;
                }
            }
            elseif ($user_ && !$this->id)
            {
                $this->addError($attribute, Yii::t('app', 'user_email_existed'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' email_existed ' . json_encode($_logs));
                return false;
            }
            
            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
            return true;
        }
    }
    
    public function validateMobile($attribute, $params)
    {
        $_logs = ['phone' => $this->mobile, 'id' => $this->id];
        
        if (!$this->hasErrors()) {
            if (empty($this->username) && empty($this->email) && empty($this->mobile))
            {
                $this->addError($attribute, Yii::t('app', 'user_mobile_notexist'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' mobile_existed ' . json_encode($_logs));
                return false;
            }
            
            if (empty($this->mobile))
            {
                return true;
            }
            
            $query_ = User::find()->select(['id', 'mobile']);
            
            //20210524 模快化二期
            //$query_->where(['mobile' => $this->mobile, 'type' => $this->type]);
            $query_->where(['mobile' => $this->mobile]);
            $user_ = $query_->andWhere(['not', ['status' => User::STATUS_DELETED]])
            ->asArray()->limit(1)->one();
            
            if ($user_ && $this->id)
            {
                if ($user_['id'] != $this->id) {
                    $this->addError($attribute, Yii::t('app', 'user_mobile_existed'));
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' phone_existed ' . json_encode($_logs));
                    return false;
                }
            }
            elseif ($user_ && !$this->id)
            {
                $this->addError($attribute, Yii::t('app', 'user_mobile_existed'));
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' phone_existed ' . json_encode($_logs));
                return false;
            }
            
            Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' succ ' . json_encode($_logs));
            return true;
        }
    }
    
    public static function publicFields()
    {
        return ['id','nickname', 'email','avatar', 'type', 'status', 'created_at'];
    }
    
    public static function privateFields()
    {
        return ['id','nickname', 'email','phone','avatar', 'type', 'status', 'access_token', 'company','language', 'created_by', 'created_at'];
    }
    
    public static function getUsernameRegex()
    {
        return '/^[a-zA-Z0-9_\-\.]{4,32}$/';
    }
    
    public static function getMobileRegex()
    {
        return '/^1[3456789]{1}\d{9}$/';
    }
    
    public static function getPhoneRegex()
    {
        return '/^[0-9\-\+\s]{5,20}$/';
    }
    
    public static function getNickNameRegex()
    {
        return '/^[\x{4e00}-\x{9fa5}\w\.]{2,16}$/u';
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }
    
    public static function getUsernameById($userId)
    {
        $user = User::find()->select(['username'])->where(['id' => $userId])->asArray()->limit(1)->one();
        return isset($user['username']) ? $user['username'] : '';
    }
    
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        if (empty($this->password_hash))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $user password_hash empty ');
            return false;
        }
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
    
    public static function getPasswordRegex()
    {
        return '/^(?![0-9_]+$)(?![a-zA-Z_]+$)(?=[a-zA-Z0-9_]+$)/';
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {   
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
        return $this->password_hash;
    }

    public function setPayPassword($password)
    {
        $this->payment_password = Yii::$app->security->generatePasswordHash($password);
        return $this->payment_password;
    }
    
    //-----------------------------------------------------------------------
    
    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }
    
    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
        return $this->auth_key;
    }
    
    //-----------------------------------------------------------------------
    
    public static function accessTokenString()
    {
        return '|||';
    }
    
    /**
     * Finds out if access token is valid
     *
     * @param string $token access token
     * @return bool
     */
    public static function isAccessTokenValid($token)
    {
        $_logs = ['$token' => $token];
        
        if (empty($token))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $token empty '.json_encode($_logs));
            return false;
        }
        
        $accessTokenInfo = self::parseAccessToken($token);
        if (!$accessTokenInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '. json_encode($_logs));
            return false;
        }
        list($random, $userId, $ip, $time) = $accessTokenInfo;
        
        //判断ip是否一致, 因ip会变动, 暂时不判断ip
        //$_logs['userip'] = Yii::$app->request->getUserIP();
        //if ($ip != Yii::$app->request->getUserIP())
        //{
            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_login_ip_changed '.json_encode($_logs));
            //return false;
        //}
        
        //判断时间是否有效
        if (!empty(Setting::getSetting('login_everyday')))
        {
            $expire = 3600*24;
        }
        else
        {
            $expire = Yii::$app->params['user.accessTokenExpire'];
        }
        $_logs['$expire'] = $expire;
        if ($time + $expire < time())
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' expire fail '.json_encode($_logs));
            return false;
        }
        
        //获取用户信息
        $cacheKey = 'user:accesstoken:'.$userId;
        $_logs['$cacheKey'] = $cacheKey;
        if (Yii::$app->cache->exists($cacheKey))
        {
            $user = Yii::$app->cache->get($cacheKey);
        
            //兼容旧格式
            if (is_numeric($user))
            {
                $user = static::findIdentity($user);
            }
        
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get from cache '.json_encode($_logs));
        }
        else
        {
            $user = self::findIdentity($userId);
            if (!$user)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '. json_encode($_logs));
                return false;
            }
        
            Yii::$app->cache->set($cacheKey, $user, 300);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set '.json_encode($_logs));
        }
        
        //判断access_token
        if (empty($user['access_token']) || $user['access_token'] !== $token)
        {
            $_logs['$user.access_token'] = $user['access_token'];
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user access_token isold '.json_encode($_logs));
            return false;
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $user;
    }
    
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $_logs = ['$token' => $token, '$type' => $type];
        
        $user = static::isAccessTokenValid($token);
        if (!$user)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' isAccessTokenValid fail '.json_encode($_logs));
            return null;
        }
        
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $user;
    }
    
    /**
     * 生成访问令牌
     * 
     */
    public static function generateAccessToken($userId = null, $ip = null, $appKey = null, $appVersion = null)
    {
        $_logs = ['$userId' => $userId, '$ip' => $ip, '$appKey' => '$appKey', '$appVersion' => $appVersion];
        
        $access_token = sprintf('%s%s%s%s%s%s%s', 
            Yii::$app->security->generateRandomString(), 
            self::accessTokenString(),
            $userId, 
            self::accessTokenString(),
            $ip,
            self::accessTokenString(),
            time());
        
        //格式转化
        $access_token = StringHelper::base64_encode($access_token);
        
        $isExist = User::find()->where(['access_token' => $access_token])->asArray()->exists();
        if ($isExist)
        {
            $_logs['$access_token'] = $access_token;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_access_token_existed '.json_encode($_logs));
            throw new ServerErrorHttpException(yii::t('app', 'user_access_token_existed'));
        }
        
        //清除用户缓存
        self::clearCache($userId);
    
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return $access_token;
    }
    
    /**
     * 解析$accessToken
     * 
     */
    public static function parseAccessToken($accessToken)
    {
        $_logs = ['$accessToken' => $accessToken];
        
        //格式转化
        $accessToken = StringHelper::base64_decode($accessToken);
        if (!$accessToken)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' format error '.json_encode($_logs));
            return false;
        }
        
        if (strpos($accessToken, self::accessTokenString()) === false)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' format error '.json_encode($_logs));
            return false;
        }
        
        $count = substr_count($accessToken, self::accessTokenString());
        $_logs['$count'] = $count;
        if ($count != 3)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' format error, $count!=5  '.json_encode($_logs));
            return false;
        }
        
        list($random, $userId, $ip, $time) = explode(self::accessTokenString(), $accessToken);
        $_logs['$random'] = $random;
        $_logs['$userId'] = $userId;
        $_logs['$ip'] = $ip;
        $_logs['$time'] = $time;
        
        if (!is_numeric($userId) || !is_numeric($time))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '. json_encode($_logs));
            return false;
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return [$random, $userId, $ip, $time];
    }
    
    public static function clearCache($userId)
    {
        //清空缓存
        $cacheKey = 'user:accesstoken:'.$userId;
        $_logs['$cacheKey'] = $cacheKey;
        if (Yii::$app->cache->exists($cacheKey))
        {
            Yii::$app->cache->delete($cacheKey);
        }
        
        //清空缓存
        $cacheKey = Yii::$app->redis->buildKey('user:online_accesstoken:' . $userId);
        $_logs['$cacheKey2'] = $cacheKey;
        if (Yii::$app->redis->exists($cacheKey)) {
            Yii::$app->redis->del($cacheKey);
        }
        
        //清除权限cache
        $auth = Yii::$app->authManager;
        $auth->clearAssignmentsCache($userId);
    }
    
    //-----------------------------------------------------------------------
    
    
    public static function findByMobile($mobile, $type = null)
    {
        $_logs = ['$mobile' => $mobile, '$type' => $type];
        
        $query = User::find()->where(['mobile' => $mobile]);
        
        if(is_array($type))
        {
            $query->andWhere(['in', 'type', $type]);
        }
        elseif (is_numeric($type))
        {
            $query->andWhere(['type' => $type]);
        }
        $user = $query->andWhere(['not', ['status' => User::STATUS_DELETED]])->one();
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $user;
    }
    
    public static function findByEmail($email, $type = null)
    {
        $_logs = ['$email' => $email, '$type' => $type];
        
        $query = User::find()->where(['email' => $email]);
        
        if(is_array($type))
        {
            $query->andWhere(['in', 'type', $type]);
        }
        elseif (is_numeric($type))
        {
            $query->andWhere(['type' => $type]);
        }
        $user = $query->andWhere(['not', ['status' => User::STATUS_DELETED]])->one();
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $user;
    }
    
    public static function findByUsername($username, $type = null)
    {
        $_logs = ['$username' => $username, '$type' => $type];
        
        $query = User::find()->where(['username' => $username]);
        
        if(is_array($type))
        {
            $query->andWhere(['in', 'type', $type]);
        }
        elseif (is_numeric($type))
        {
            $query->andWhere(['type' => $type]);
        }
        $user = $query->andWhere(['not', ['status' => User::STATUS_DELETED]])->one();
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $user;
    }
    
    //-----------------------------------------------------------------------
    
    public static function getType($type)
    {
        $types = self::getTypes();
        return isset($types[$type]) ? $types[$type] : null;
    }
    
    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            //self::TYPE_DEFAULT => yii::t('app', 'user_type_default'),
            //self::TYPE_CUSTOMER => yii::t('app', 'user_type_customer'),
            self::TYPE_ADMIN => yii::t('app', 'user_type_admin'),
            self::TYPE_WORKER => yii::t('app', 'user_type_worker'),
            //self::TYPE_CROWDSOURCING => yii::t('app', 'user_type_crowdsourcing'),
            self::TYPE_ROOT => yii::t('app', 'user_type_root'),
        ];
    }
    
    public static function getAdminTypes()
    {
        return array_intersect_key(self::getTypes(), array_flip([
        //            self::ROLE_GUEST,
            self::TYPE_ADMIN,
            self::TYPE_WORKER,
        ]));
    }

    public static function getStatus($status)
    {
        $statuses = self::getStatuses();
        return isset($statuses[$status]) ? $statuses[$status] : null;
    }
    
    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_NOTACTIVE => yii::t('app', 'user_status_not_active'),
            self::STATUS_ACTIVE => yii::t('app', 'user_status_active'),
            self::STATUS_DISABLE => yii::t('app', 'user_status_disabled'),
        ];
    }
    
    //绑定team_user模型
    public function getSiteUser(){
        return $this->hasOne(SiteUser::className(), ['user_id' => 'id'])
        ->select(['id', 'site_id', 'user_id', 'created_by'])
        ->where(['status' => SiteUser::STATUS_ENABLE])
        ->orderBy(['id' => SORT_DESC]);
    }
    
    public function getSite(){
        return $this->hasOne(Site::className(), ['id' => 'site_id'])
        ->select(['id', 'name', 'logo', 'status'])
        ->via('siteUser');
    }
    
    public static function getLanguage($language)
    {
        $vals = self::getLanguages();
        return isset($vals[$language]) ? $vals[$language] : null;
    }
    
    /**
     * @return array
     */
    public static function getLanguages()
    {
        $lanuageStr = Setting::getSetting('open_languages');
    
        $lanuageArr = [];
        if ($lanuageStr)
        {
            $lanuageArr = ArrayHelper::strToArray($lanuageStr);
        }
    
        $lanuages = [];
        in_array(self::LANGUAGE_KEY_ZH_CN, $lanuageArr) && ($lanuages[self::LANGUAGE_ZH_CN] = yii::t('app', 'language_zh_cn'));
        in_array(self::LANGUAGE_KEY_EN, $lanuageArr) && ($lanuages[self::LANGUAGE_EN] = yii::t('app', 'language_en'));
        return $lanuages;
    }
    
    public static function getLanguageKey($language)
    {
        $vals = self::getLanguageKeys();
        return isset($vals[$language]) ? $vals[$language] : null;
    }
    
    /**
     * 对应common.messages下的翻译文本
     * @return array
     */
    public static function getLanguageKeys()
    {
        $lanuageStr = Setting::getSetting('open_languages');
    
        $lanuageArr = [];
        if ($lanuageStr)
        {
            $lanuageArr = ArrayHelper::strToArray($lanuageStr);
        }
    
        $lanuages = [];
        in_array(self::LANGUAGE_KEY_ZH_CN, $lanuageArr) && ($lanuages[self::LANGUAGE_ZH_CN] = self::LANGUAGE_KEY_ZH_CN);//切勿翻译
        in_array(self::LANGUAGE_KEY_EN, $lanuageArr) && ($lanuages[self::LANGUAGE_EN] = self::LANGUAGE_KEY_EN);//切勿翻译
        return $lanuages;
    }
    
    /**
     * 验证用户名
     * 
     * @param string $attribute
     * @param array $params
     * @return boolean
     */
    public function validateNickname($attribute, $params)
    {
        $_logs = ['nickname' => $this->nickname];
        
        if (!$this->hasErrors())
        {
            $isForbidden = ForbiddenWord::checkForbiddenWord($this->nickname);
            if ($isForbidden)
            {
                $this->addError($attribute, Yii::t('app', 'nickname_forbidden'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkForbiddenWord '.json_encode(['nickname' => $this->nickname]));
                return false;
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
        
    }

    //获取所有商务
    public static function getBusinesses(){
        $userIds = AuthAssignment::find()->select(['user_id'])->where(['item_name' => 'business'])->asArray()->column();
        if(is_array($userIds)&&count($userIds)){
            $users = User::find()->select(['id','nickname'])->where(['in','id',$userIds])->asArray()->all();
            $keys = array_column($users,'id');
            $values = array_column($users,'nickname');
            return array_combine($keys,$values);
        }else{
            return [];
        }
    }
    
    public function getCreatedByUser(){
        return $this->hasOne(User::className(), ['id' => 'created_by'])->select(User::publicFields());
    }
    
    //获取用户的角色
    public function getRoles(){
        return $this->hasMany(AuthAssignment::className(), ['user_id' => 'id']);
    }
    
    public function getRoleKeys()
    {
        return AuthAssignment::find()->select(['item_name'])->where(['user_id' => $this->getId()])->asArray()->column();
    }
    
    public function getUserStat()
    {
        return $this->hasOne(UserStat::className(), ['user_id' => 'id']);
    }
    public function  getGroupUser()
    {
        return $this->hasOne(GroupUser::className(), ['user_id' => 'id'])->select('group_id,user_id');
    }

    public function getGroup()
    {
        return $this->hasOne(Group::className(), ['id' => 'group_id'])->via('groupUser')->select('id,name,count');
    }
    /**
     * 更新用户的小组
     *
     */
    public function updateUserGroup($groupId)
    {
        $_logs = [];
        //查询用户旧标签
        $oldUserGroup = GroupUser::find()
            ->select(['group_id'])
            ->where(['user_id' => $this->getId()])
            ->asArray()->one();
        $_log['$oldUserGroup'] = $oldUserGroup;

        if($oldUserGroup)
        {
            $group = Group::findOne($oldUserGroup['group_id']);
            if($groupId !== $oldUserGroup['group_id'] && $group)
            {
                $group->count -= 1;
                $group->updated_at = time();
                $group->save();

                $group = Group::findOne($groupId);
                if($group) {
                    $group->count += 1;
                    $group->updated_at = time();
                    $group->save();
                }
            }
            $condition = ['user_id'=>$this->getId()];
            $attributes = ['group_id'=>$groupId,'updated_at'=>time()];

            GroupUser::updateAll($attributes, $condition);

        }else{
            //已有小组，给用户修改小组
            $group = Group::findOne($groupId);
            if($group){
                $group->count += 1;
                $group->created_at = time();
                $group->save();

                $groupUser = new GroupUser();
                $groupUser->user_id = $this->getId();
                $groupUser->group_id = $groupId;
                $groupUser->created_at = time();
                $groupUser->save();
            }
            else
            {
                $this->addErrors($group->getErrors());
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' group_save_error '.json_encode($_logs));
                return false;
            }
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_log));
        return true;
    }
    
    /**
     * 更新用户的角色
     * 用法:
     * User::updateUserRole($userId, $roles);
     * @param int $userId 用户id
     * @param array $roles 例如:['team_manager','team_worker']
     * @param int $type 用户类型，若传值，则仅更新该类型下的角色，其他类型角色将会继续保留
     * return boolean
     */
    public static function updateUserRole($userId, $roles, $userType = null)
    {
        $_log = ['$roles' => $roles, '$userType' => $userType];
        
        $user = User::find()->where(['id' => $userId])->with('roles')->one();
        if (empty($user))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user not found '.json_encode($_log));
            return false;
        }
        
        //默认，该类型的可修改的角色
        $allowRoles = array_keys(AuthItem::getRolesByType($user->type));
        $roles = array_unique(array_intersect($allowRoles, $roles));
        
        if($userType !== null)
        {
            $roleOtherType = [];
            //当前类型下所有角色
            $typeRoles = array_keys(AuthItem::getRolesByType($userType));
            if($user->roles){
                //当前用户现在的所有角色
                $userCurrentRoles = array_column($user->roles, 'item_name');
                
                //非指定类型的角色
                $roleOtherType = array_diff($userCurrentRoles, $typeRoles);
            }
            
            //保留其他类型下的角色
            $roles = array_merge($roleOtherType, $roles);
        }
        $_log['$allowRoles'] = $allowRoles;
        $_log['$roles'] = $roles;
        
        if (!$roles)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no effect roles '.json_encode($_log));
            return false;
        }
        $_log['$roles'] = $roles;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' roles '.json_encode($_log));
        
        $auth = Yii::$app->authManager;
        $auth->removeUserAssignments($userId);
        
        foreach($roles as $role)
        {
            $role = $auth->getRole($role);
            if (!$role)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_update_roleNotExist '.json_encode($_log));
                continue;
            }
            $auth->assign($role, $userId);
        }
        
        //清除权限cache
        $auth->clearAssignmentsCache($userId);
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_log));
        return true;
    }
    
}
