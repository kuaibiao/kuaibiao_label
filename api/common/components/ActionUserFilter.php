<?php
namespace common\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;
use common\models\User;
use common\models\UserStat;
use common\helpers\SecurityHelper;
use common\helpers\ArrayHelper;
use yii\web\UnauthorizedHttpException;
use common\models\Site;

/**
 * 用户监控过滤器, 针对已登录用户, 处理用户语言, 限制用户相关的内容
 * 
 * 
 */
class ActionUserFilter extends ActionFilter
{
    public function beforeAction($action)
    {
        $_logs = [];
        
        //关闭站点
        if (!empty(Yii::$app->catchAll))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' catchAll '.json_encode($_logs));
            return parent::beforeAction($action);
        }
        
        //未登录用户则跳过
        if (!Yii::$app->user->id)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notlogin '.json_encode($_logs));
            return parent::beforeAction($action);
        }
        $_logs['user_id'] = Yii::$app->user->id;
        
        //用户语言
        if (is_numeric(Yii::$app->user->identity->language) && User::getLanguageKey(Yii::$app->user->identity->language))
        {
            Yii::$app->language = User::getLanguageKey(Yii::$app->user->identity->language);
            $_logs['language'] = Yii::$app->language;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set language '.json_encode($_logs));
        }
        
        //检测用户登录状态
        $this->checkLoginStatus(Yii::$app->user->id);
        
        //校验请求频率, 各控制器无需根据ip校验频率!!
        $isFrequencyAndCount = SecurityHelper::checkFrequency('request:'.Yii::$app->user->id, 6000, 300);
        if ($isFrequencyAndCount)
        {
            //在白名单里
            if (!in_array(Yii::$app->user->id, Yii::$app->params['user.whitelist']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' userid checkFrequency error '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'system_ip_request_frequently'));
            }
        }
        /*
        //用户请求拦截器
        $requestdata = ArrayHelper::desc(array_merge(Yii::$app->request->get(), Yii::$app->request->post()));
        $requestlockKey = 'requestlock:'.Yii::$app->user->id.':'.$action->uniqueId.':'.Yii::$app->request->getMethod().':'.md5(serialize($requestdata));
        $isLock = SecurityHelper::lock($requestlockKey, 1);
        if($isLock)
        {
            $_logs['$requestdata'] = $requestdata;
            $_logs['$requestlockKey'] = $requestlockKey;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' requestlock $isLock '.json_encode($_logs));
            
            //post请求, 更新数据库的操作做限制
            $requestActionFrequentLimited = ['create', 'update', 'edit', 'delete'];//, 'execute'
            if (Yii::$app->request->isPost && in_array($action->id, $requestActionFrequentLimited))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' requestlock $isLock ForbiddenHttpException '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'system_ip_request_frequently'));
            }*/
            /*
             $requestActionExceptions = ['list', 'detail', 'view', 'stat', 'upload-private-file', 'upload-resource-file', 'upload-public-file', 'projects'];
             if (Yii::$app->request->isPost && !in_array($action->id, $requestActionExceptions))
             {
             Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' requestlock $isLock ForbiddenHttpException '.json_encode($_logs));
             throw new ForbiddenHttpException(Yii::t('app', 'system_ip_request_frequently'));
             }*/
        //}
        
        //判断状态
        if (!in_array(Yii::$app->user->identity->status, [User::STATUS_ACTIVE]))
        {
            $_logs['identity.status'] = Yii::$app->user->identity->status;
            
            //用户被禁用
            if(Yii::$app->user->identity->status == User::STATUS_DISABLE){
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_status_disable '.json_encode($_logs));
                throw new UnauthorizedHttpException(Yii::t('app', 'user_status_disable'), User::EXCEPTION_STATUS_DISABLED);
            }
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_exist error '.json_encode($_logs));
            throw new UnauthorizedHttpException(Yii::t('app', 'user_not_exist'));
        }

        //----------------------------
        
        //判断用户类型
        if (Yii::$app->user->identity->type == User::TYPE_ROOT)
        {
            
        }
        elseif (in_array(Yii::$app->user->identity->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
        {
            if (!Yii::$app->user->identity->site)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'user_no_site'));
            }
            $siteId = Yii::$app->user->identity->site->id;
            if (!$siteId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'user_no_site'));
            }
            
            $siteInfo = Site::find()->where(['id' => $siteId])->andWhere(['not', ['status' => Site::STATUS_DELETED]])->asArray()->limit(1)->one();
            if (empty($siteInfo))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_not_exists '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'site_not_exists'));
            }
            if($siteInfo['status'] == Site::STATUS_NOTACTIVE)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_status_not_active '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'site_status_not_active'));
            }
            if($siteInfo['status'] == Site::STATUS_DISABLED)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_status_disabled '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'site_status_disabled'),Site::EXCEPTION_STATUS_DISABLED);
            }
            if($siteInfo['status'] == Site::STATUS_EXPIRED)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_status_expired_notice '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'site_status_expired_notice'));
            }
//             if (empty($siteInfo['team_count_limit']))
//             {
//                 Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_site_no_team '.json_encode($_logs));
//                 throw new ForbiddenHttpException(Yii::t('app', 'user_site_no_team'));
//             }
            
            //0826 admin进入team 再进入passport 取消关联团队后返回
//             if (Yii::$app->user->identity->type == User::TYPE_WORKER)
//             {
//                 if (empty(Yii::$app->user->identity->teamUser))
//                 {
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_team '.json_encode($_logs));
//                     throw new ForbiddenHttpException(Yii::t('app', 'user_no_team'));
//                 }
                
//                 if (empty(Yii::$app->user->identity->team))
//                 {
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_team '.json_encode($_logs));
//                     throw new ForbiddenHttpException(Yii::t('app', 'user_no_team'));
//                 }
//             }
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'user_no_permission'));
        }
        
        //检测用户登录状态
        self::refreshLoginStatus(Yii::$app->user->id);
        
        //----------------------------
        
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return parent::beforeAction($action);
    }
    
    //检测并保持用户登录状态
    public function checkLoginStatus($userId)
    {
        $_logs = [];
        
        $cacheKey = self::getLoginKey($userId);
        $_logs['$cacheKey'] = $cacheKey;
        
        $cacheVal = Yii::$app->cache->get($cacheKey);
        $_logs['$cacheVal'] = $cacheVal;
        
        if ($cacheVal)
        {
            $loginInfoArr = explode('___', $cacheVal);
            
            if (count($loginInfoArr) != 2)
            {
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' check login status fail ' . json_encode($_logs));
                return false;
            }
        }
        else
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' check login status fail ' . json_encode($_logs));
            return false;
        }
        
        return true;
    }
    
    /**
     * 刷新用户登录状态
     */
    public static function refreshLoginStatus($userId)
    {
        $_logs = [];
        
        $ip = Yii::$app->request->getUserIP();
        $_logs['$ip'] = $ip;
        
        $useragent = Yii::$app->request->getUserAgent();
        $_logs['$useragent'] = $useragent;
        
        //大于半小时会更新用户最后更新时间
        if (!empty(Yii::$app->user->identity) && time() - Yii::$app->user->identity->updated_at > 1800)
        {
            //更新登录时间和ip
            $attributes = [
                'login_last_time' => time(),
                'login_last_ip' => $ip
            ];
            UserStat::updateAll($attributes, ['user_id' => $userId]);
            
            //更新用户更新时间
            $attributes = [
                'updated_at' => time()
            ];
            User::updateAll($attributes, ['id' => $userId]);
        }
        
        //更新用户登录缓存
        $cacheKey = self::getLoginKey($userId);
        $_logs['$cacheKey'] = $cacheKey;
        
        $cacheVal = $ip.'___'.$useragent;
        $_logs['$cacheVal'] = $cacheVal;
        
        Yii::$app->cache->set($cacheKey, $cacheVal, 1800);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
    }
    
    public static function getLoginKey($userId = '')
    {
        $cacheKey = Yii::$app->redis->buildKey('user.online.'.$userId);
    
        return $cacheKey;
    }
    
    public static function getOnlineUsers()
    {
        $cacheKeyPre = self::getLoginKey();
        $_logs['$cacheKeyPre'] = $cacheKeyPre;
        $keys = Yii::$app->redis->keys('*'.$cacheKeyPre.'*');
        $_logs['$keys.count'] = count($keys);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' keys '.json_encode($_logs));
    
        $userIds = [];
        if ($keys)
        {
            foreach ($keys as $key)
            {
                $userIds[] = substr($key, strrpos($key, $cacheKeyPre) + strlen($cacheKeyPre));
            }
        }
        $_logs['$userIds'] = $userIds;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return array_unique($userIds);
    }
}