<?php
namespace common\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;
use common\models\User;
use common\models\Setting;
use common\helpers\SecurityHelper;
use common\helpers\ArrayHelper;

/**
 * 程序监控过滤器,记录每次请求的时间和内存
 * 
 * 
 */
class ActionMonitorFilter extends ActionFilter
{
    private $_startTime;

    public function beforeAction($action)
    {
        $_logs = ['$action' => $action->getUniqueId()];
        
        //记录本次起始时间
        $this->_startTime = microtime(true);
        $_logs['$_startTime'] = $this->_startTime;
        
        //记录用户ip
        $ip = Yii::$app->request->getUserIP();
        $_logs['$ip'] = $ip;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        //关闭站点
        if (!empty(Yii::$app->catchAll))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' catchAll '.json_encode($_logs));
            return parent::beforeAction($action);
        }
        
        //测试,临时添加
        //if (Yii::$app->request->get('access_token'))
        //{
        //    Yii::$app->request->getBodyParam('access_token', Yii::$app->request->get('access_token'));
        //    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test access_token '.json_encode($_logs));
        //}
        
        //校验请求频率, 各控制器无需根据ip校验频率!!
        $isFrequencyAndCount = SecurityHelper::checkFrequency('request:'.$ip, 300000, 300);
        if ($isFrequencyAndCount)
        {
            //在白名单里
            if (!in_array($ip, Yii::$app->params['ip.whitelist']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ip checkFrequency error '.json_encode($_logs));
                throw new ForbiddenHttpException(Yii::t('app', 'system_ip_request_frequently'));
            }
        }
        
        //--------------------------------------------------------------------------
        //系统安全
        
        //有效期
        if (empty(Yii::$app->params['system.expiry_date']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' expiry_date notfound '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'system_expiry_date_passed'));
        }
        
        if (empty(Yii::$app->params['system.install_time']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' install_time notfound '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'system_expiry_date_passed'));
        }
        
        if (empty(Yii::$app->params['system.last_running_time']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' last_running_time notfound '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'system_expiry_date_passed'));
        }
        
        $current_time = time();
        $expiry_time = strtotime(Yii::$app->params['system.expiry_date']);
        $install_time = strtotime(Yii::$app->params['system.install_time']);
        $last_running_time = strtotime(Yii::$app->params['system.last_running_time']);
        $_logs['$current_time'] = $current_time;
        $_logs['$expiry_time'] = $expiry_time;
        $_logs['$install_time'] = $install_time;
        $_logs['$last_running_time'] = $last_running_time;
        
        //从setting中读取最后运行时间
        if (Setting::getSetting('last_running_time'))
        {
            $last_running_time = Setting::getSetting('last_running_time');
        }
        
        //判断时间格式
        if ($install_time < 1)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $last_running_time < $install_time error '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'system_expiry_date_passed'));
        }
        
        //最后运行时间必须大于安装时间
        if ($last_running_time < $install_time)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $last_running_time < $install_time error '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'system_expiry_date_passed'));
        }
        
        //当前时间必须大于等于最后运行时间
        if ($current_time < $last_running_time)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $current_time < $last_running_time error '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'system_expiry_date_passed'));
        }
        
        //当前时间必须小于有效期
        if ($current_time > $expiry_time)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' expiry_date error '.json_encode($_logs));
            throw new ForbiddenHttpException(Yii::t('app', 'system_expiry_date_passed'));
        }
        
        //当前时间大于最后运行时间一个小时以上,更新最后运行时间
        if ($current_time > $last_running_time + 3600)
        {
            Setting::updateSetting('last_running_time', $current_time);
        }
        
        //--------------------------------------------------------------------------
        
        //页面语言
        $lanuageStr = Setting::getSetting('open_languages');
        if ($lanuageStr)
        {
            $lanuageArr = ArrayHelper::strToArray($lanuageStr);
            Yii::$app->language = array_shift($lanuageArr);
            $_logs['language'] = Yii::$app->language;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set language '.json_encode($_logs));
        }
        if (is_numeric(Yii::$app->request->post('language')) && User::getLanguageKey(Yii::$app->request->post('language')))
        {
            Yii::$app->language = User::getLanguageKey(Yii::$app->request->post('language'));
            $_logs['language'] = Yii::$app->language;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set language '.json_encode($_logs));
        }
        
        //--------------------------------------------------------------------------
        
        //统一处理options请求
        if (Yii::$app->request->isOptions)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request_is_options '.json_encode($_logs));
            return Yii::$app->end();
        }
        //----------------------------
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return parent::beforeAction($action);
    }

    public function afterAction($action, $result)
    {
        $_logs = [];
        $_logs['$ip'] = Yii::$app->request->getUserIP();
        $_logs['$action'] = $action->uniqueId;
        $_logs['$action.start'] = $this->_startTime;
        
        $endTime = microtime(true);
        $_logs['$action.end'] = $endTime;
        
        $usedtime = round($endTime - $this->_startTime, 4);
        $_logs['$usedtime'] = $usedtime;
        
        $memory	= round(memory_get_usage() / 1024 / 1024, 2).'MB';
        $_logs['$memory'] = $memory;
        
        //时长大于3秒的请求, 记录下来
        if ($usedtime > 3)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $usedtime > 3 '.json_encode($_logs));
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $usedtime '.json_encode($_logs));
        }
        
        $_logs['$result'] = ArrayHelper::var_desc($result);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' end '.json_encode($_logs));
        return parent::afterAction($action, $result);
    }
    
}