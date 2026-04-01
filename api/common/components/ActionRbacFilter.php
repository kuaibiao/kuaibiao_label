<?php
namespace common\components;

use Yii;
use yii\base\ActionFilter;
use common\models\User;
use yii\web\ForbiddenHttpException;
use common\components\SystemInfo;
use common\models\Setting;

/**
 * rbac过滤器,判断是否有执行的权限
 *
 *
 */
class ActionRbacFilter extends ActionFilter
{
    public function beforeAction($action)
    {
        $_logs = [];
        $_logs['actionid'] = $action->getUniqueId();
        $_logs['userid'] = Yii::$app->user->id;
        
        if (!parent::beforeAction($action))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' beforeAction '.json_encode($_logs));
            return false;
        }
        
        //组成权限
        $app = Yii::$app->id;
        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;
        $permission = $controller.'/'.$action;
        $_logs['$permission'] = $permission;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $permission '.json_encode($_logs));
        
        //rbac校验
        if(!Yii::$app->user->can($permission))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no permission '.json_encode($_logs));
            $this->denyAccess();
            return false;
        }
        
        
        //--------------------------------
        //判断mac address
        $systeminfo = new SystemInfo();
        if (!empty(Yii::$app->params['macAddress']) && !in_array(strtolower(Yii::$app->params['macAddress']), $systeminfo->getMacAddress()))
        {
            $_logs['macAddress.param'] = Yii::$app->params['macAddress'];
            $_logs['macAddress'] = $systeminfo->getMacAddress();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mac address error params.macAddress '.json_encode($_logs));
            $this->denyAccess();
            return false;
        }
        //判断mac address
        if (!empty(Setting::getSetting('macAddress')) && !in_array(strtolower(Setting::getSetting('macAddress')), $systeminfo->getMacAddress()))
        {
            $_logs['macAddress.setting'] = Setting::getSetting('macAddress');
            $_logs['macAddress'] = $systeminfo->getMacAddress();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mac address error setting.macAddress '.json_encode($_logs));
            $this->denyAccess();
            return false;
        }
        //--------------------------------
        
        return true;
    }

    /**
     * Denies the access of the user.
     * The default implementation will redirect the user to the login page if he is a guest;
     * if the user is already logged, a 403 HTTP exception will be thrown.
     * @param User $user the current user
     * @throws ForbiddenHttpException if the user is already logged in.
     */
    protected function denyAccess()
    {
        $_logs = [];
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '.json_encode($_logs));
        throw new ForbiddenHttpException(Yii::t('app', 'user_no_permission'));
    }
}