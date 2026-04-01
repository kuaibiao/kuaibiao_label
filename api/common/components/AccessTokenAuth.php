<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\components;
use Yii;
use yii\filters\auth\AuthMethod;
/**
 * QueryParamAuth is an action filter that supports the authentication based on the access token passed through a query parameter.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AccessTokenAuth extends AuthMethod
{
    /**
     * @var string the parameter name for passing the access token
     */
    public $tokenParam = 'access_token';


    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        $_logs = [];
        $_logs['tokenParam'] = $this->tokenParam;
        
        $accessToken = $request->post($this->tokenParam);
        $_logs['$accessToken'] = $accessToken;
        if (empty($accessToken))
        {
            $accessToken = $request->get($this->tokenParam);
            $_logs['$accessToken'] = $accessToken;
        }
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $accessToken '. json_encode($_logs));
        
        if (is_string($accessToken))
        {
            $identity = $user->loginByAccessToken($accessToken, get_class($this));
            if ($identity !== null) {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
                return $identity;
            }
        }
        if ($accessToken !== null) {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' error '. json_encode($_logs));
            return null;
        }
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '. json_encode($_logs));
        return null;
    }
    
}
