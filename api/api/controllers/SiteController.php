<?php
namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UnauthorizedHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\components\ActionUserFilter;
use common\models\Setting;
use common\models\User;
use common\models\Category;
use common\models\Project;
use common\models\AppStat;
use api\models\InitForm;
use api\models\LoginForm;
use api\models\LoginQuickForm;
use api\models\RegisterForm;
use api\models\VerifyPhoneForm;
use api\models\VerifyEmailForm;
use api\models\ResetPasswordForm;
use common\models\StepGroup;
use common\models\PackScript;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\ImageHelper;
use common\helpers\SecurityHelper;
use common\helpers\ZipHelper;
use common\helpers\StringHelper;
use common\components\ImageHandler;
use common\components\SystemInfo;
use common\models\Site;
use common\models\SiteUser;
use common\helpers\HttpHelper;
use common\helpers\JsonHelper;
use api\models\ThirdpartyLoginForm;
/**
 * 默认控制器
 * 包含的方法允许未登录访问
 * 
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        //例外路由, 为保护客户信息, 严格禁止客户上传的数据不登录访问.
        $exceptActions = [
            'error','init','captcha','login','login-quick','register','forget-password',
            'download-private-file','download-public-file','forward', 'thirdparty-login'
        ];
        return [
            //程序监控过滤器,记录每次请求的时间和内存
            'monitor' => [
                'class' => 'common\components\ActionMonitorFilter',
                'except' => ['error']
            ],
            //请求方式过滤器,检查用户是否是post提交
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'error' => ['get', 'post'],
                    'download-log-file' => ['get'],
                    'download-private-file' => ['get'],
                    'download-public-file' => ['get'],
                    '*' => ['POST', 'OPTIONS'],//OPTIONS用于ajax跨域前获取支持的请求方式
                ],
            ],
            //accesstoken身份验证
            'authenticator' => [
                'class' => AccessTokenAuth::className(),
                'except' => $exceptActions
            ],
            //用户行为过滤器
            'userfilter' => [
                'class' => 'common\components\ActionUserFilter',
                'except' => $exceptActions
            ],
            // rbac过滤器,判断是否有执行的权限
            'rbac' => [
                'class' => 'common\components\ActionRbacFilter',
                'except' => $exceptActions
            ],
            //下载文件浏览器缓存http-cache
            [
                'class' => 'yii\filters\HttpCache',
                'only' => ['download-private-file'],
                'lastModified' => function ($action, $params) {
                    return time();
                },
                'etagSeed' => function ($action, $params) {
                    return Yii::$app->request->get('file');// generate ETag seed here
                },
            ],
        ];
    }
    
    /**
     * 错误处理
     */
    public function actionError()
    {
        $_logs = [];
    
        $exception = Yii::$app->getErrorHandler()->exception;
        $_logs['$exception'] = $exception;
    
        if ($exception)
        {
            Yii::$app->response->statusCode = 200;
            
            //认证失败的异常
            if ($exception instanceof UnauthorizedHttpException)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_auth_fail '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'site_auth_fail', Yii::t('app', 'site_auth_fail')));
            }
            //请求方式错误
            elseif ($exception instanceof MethodNotAllowedHttpException)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_error_request_method_error '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'site_error_request_method_error', $exception->getMessage()));
            }
            //禁止访问
            elseif ($exception instanceof ForbiddenHttpException)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_error_user_Forbidden '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'site_error_user_Forbidden', $exception->getMessage()));
            }
            else
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_error_exception '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'site_error_exception', $exception->getCode()));
            }
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_error_site_page_not_found, no exception ');
            return $this->asJson(FormatHelper::resultStrongType('', 'site_page_not_found', Yii::t('app', 'site_page_not_found')));
        }
    }
    
    public function actionIndex()
    {
        var_dump(time());
        
        exit();
    }
    
    public function actionOffline()
    {
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' system_offline ');
        return $this->asJson(FormatHelper::resultStrongType('', 'system_offline', Yii::t('app', 'system_offline')));
    }
    
    /**
     * 初始化配置
     * 
     * @return \yii\web\Response
     */
    public function actionInit()
    {
        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;
        
        $model = new InitForm();
        $model->load($postData, '');
        
        if (!$model->validate())
        {
            $errors = $model->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;

            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_init_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $model->save();
        
        //-----------------------------
        
        //返回值
        $responseData = [
            'ip' => Yii::$app->request->getUserIP(),
            'settings' => Setting::getSettings()
        ];
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionCaptcha()
    {
        //因官网需要https的,故此处做了兼容处理
        $params = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        
        $key = 'site/captcha_'.uniqid();
        $code = Yii::$app->cache->get($key);
        if (empty($code) || !empty($params['regenerate']))
        {
            $code = StringHelper::generate_random(4, 4);
            Yii::$app->cache->set($key, $code, 3600);
            //Yii::$app->cache->set($key . '_count', 1, 3600);
        }
        
        $bin = ImageHelper::renderImageByGD($code);
        
        //返回值
        $responseData = [
            'key' => $key,
            'bin' => ImageHelper::base64_encode_image($bin, 'png'),
            'hash' => SecurityHelper::generateValidationHash(strtolower($code)),
        ];
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

    /**
     * Login action
     * 
     * access_token	"aeUetAcjdH8T27o_Iq7-2L9CS6oOuZzR_5a53114e54b71_1515393358"
     *
     * @return string
     */
    public function actionLogin()
    {
        $_logs = [];

        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;
        
        //前段对密码做了base64加密
        if (isset($postData['password']))
        {
            $password = StringHelper::base64_decode($postData['password']);
            if ($password)
            {
                $postData['password'] = $password;
            }
        }

        $model = new LoginForm();
        $model->load($postData, '');
        
        //频率限制
        $isFrequency = SecurityHelper::checkFrequency('login:'.$model->username, 100, 300);
        if ($isFrequency)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' login_excessive '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'login_excessive', Yii::t('app', 'login_excessive')));
        }

        if (!$model->validate())
        {
            $errors = $model->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;
            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $user = $model->login();
        if (!$user)
        {
            $errors = $model->getFirstErrors();
            $error = key($errors);
            $message = current($errors);
            
            $_logs['$model.getFirstErrors'] = $errors;
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        
        //----------------------------------

        //返回值
        $responseData = [
            'id' => $user->id,
            'access_token' => $user->access_token,
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_login_succ '. json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionLoginQuick()
    {
        $_logs = [];
    
        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;
    
        $model = new LoginQuickForm();
        $model->load($postData, '');
    
        if (!$model->validate())
        {
            $errors = $model->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;
    
            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
    
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $user = $model->login();
        if (!$user)
        {
            $errors = $model->getFirstErrors();
            $error = key($errors);
            $message = current($errors);
            
            $_logs['$model.getFirstErrors'] = $errors;
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
    
        //----------------------------------
    
        //返回值
        $responseData = [
            'id' => $user->id,
            'access_token' => $user->access_token,
        ];
        $_logs['$responseData'] = $responseData;
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_login_succ '. json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    

    /**
     * 客户提交注册申请
     * 
     * @return mixed
     */
    public function actionRegister()
    {
        $_logs = [];
        
        //因官网需要https的,故此处做了兼容处理
        $postData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        //$postData = Yii::$app->request->get();
        $_logs['$postData'] = $postData;
        
        //前段对密码做了base64加密
        if (isset($postData['password']))
        {
            $password = StringHelper::base64_decode($postData['password']);
            if ($password)
            {
                $postData['password'] = $password;
            }
        }

        
        $registerForm = new RegisterForm();
        $registerForm->load($postData, '');//导入数据, ''字符串表不使用场景, 强制导入
        if (!$registerForm->validate())
        {
            $errors = $registerForm->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;

            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }

        $user = $registerForm->signup();
        if (!$user)
        {
            $errors = $registerForm->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;

            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $userId = $user->id;
        $_logs['$userId'] = $userId;
        
        //----------------------------------
        
        //返回值
        $responseData = [
            'id' => $userId,
            'access_token' => $user->access_token
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_signup_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    /**
     * 忘记密码
     * @return \yii\web\Response
     */
    public function actionForgetPassword()
    {
        $_logs = [];
        
        $verifyPhoneForm = new VerifyPhoneForm();
        $verifyEmailForm = new VerifyEmailForm();
        
        $postData = Yii::$app->request->post();
        $op = Yii::$app->request->post('op');
        
        if ($op == 'sendEmailCode')
        {
            $email = Yii::$app->request->post('email', '');
            
            if (!StringHelper::valid_email($email))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_format_error', Yii::t('app', 'email_format_error')));
            }
            
            //频率限制
            $isFrequency = SecurityHelper::checkFrequency('sendemail:'.$email, 10, 600);
            if ($isFrequency)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' send_email_code_excessive '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'send_email_code_excessive', Yii::t('app', 'send_email_code_excessive')));
            }
            
            $userInfo = User::find()->where(['email' => $email])->asArray()->limit(1)->one();
            if (!$userInfo)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_not_exist '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_not_exist', Yii::t('app', 'email_not_exist')));
            }
            
            $verifyEmailForm = new VerifyEmailForm();
            $verifyEmailForm->email = $email;
            $emailCodeHash = $verifyEmailForm->send('resetPasswordCode');
            
            //返回值
            $responseData = [
                'email' => $email,
                'emailCodeHash' => $emailCodeHash
            ];
            $_logs['$responseData'] = $responseData;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_sendEmailCode_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType($responseData));
        }
        elseif ($op == 'verifyEmail')
        {
            $verifyEmailForm->load($postData, '');
            
            //频率限制
            $isFrequency = SecurityHelper::checkFrequency('forget:'.$verifyEmailForm->email, 60, 600);
            if ($isFrequency)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_verify_excessive '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_verify_excessive', Yii::t('app', 'email_verify_excessive')));
            }
            
            if (!$verifyEmailForm->validate())
            {
                $errors = $verifyEmailForm->getFirstErrors();
                $_logs['$model.getFirstErrors'] = $errors;

                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;

                $error = sprintf('user_forgetPassword_%sError', $key);

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
            
            $user = User::find()->where(['email' => $verifyEmailForm->email])->asArray()->limit(1)->one();
            if (!$user)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_not_exist'.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_not_exist', Yii::t('app', 'email_not_exist')));
            }
        }
        elseif ($op == 'verifyPhone')
        {
            $verifyPhoneForm->load($postData, '');
            
            //频率限制
            $isFrequency = SecurityHelper::checkFrequency('forget:'.$verifyPhoneForm->phone, 60, 600);
            if ($isFrequency)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_verify_excessive '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_verify_excessive', Yii::t('app', 'phone_verify_excessive')));
            }
            
            if (!$verifyPhoneForm->validate())
            {
                $errors = $verifyPhoneForm->getFirstErrors();
                $_logs['$model.getFirstErrors'] = $errors;
                
                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;

                $error = sprintf('user_forgetPassword_%sError', $key);

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
            
            $user = User::find()->where(['phone' => $verifyPhoneForm->phone])->asArray()->limit(1)->one();
            if (!$user)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_not_exist '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_not_exist', Yii::t('app', 'phone_not_exist')));
            }
        }
        elseif ($op == 'submit')
        {
            $postData = Yii::$app->request->post();
            
            //前段对密码做了base64加密
            if (isset($postData['newpassword']))
            {
                $password = StringHelper::base64_decode($postData['newpassword']);
                if ($password)
                {
                    $postData['newpassword'] = $password;
                }
            }
            
            $model = new ResetPasswordForm();
            $model->load($postData, '');
            if (!$model->validate())
            {
                $errors = $model->getFirstErrors();
                $_logs['$model.getFirstErrors'] = $errors;
                
                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;
                
                $error = sprintf('user_forgetPasswordNew_%sError', $key);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
            if (!$model->resetPassword())
            {
                $errors = $model->getFirstErrors();
                $_logs['$model.getFirstErrors'] = $errors;
                
                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;
                
                $error = sprintf('user_forgetPasswordNew_%sError', $key);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
            
            //返回值
            $responseData = [
                'ret' => 1,
            ];
            $_logs['$responseData'] = $responseData;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_forgetPasswordNew_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType($responseData));
        }
        
        
    }
    
    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     */
    private function actionForgetPasswordNew()
    {
        $_logs = [];
        
        $model = new ResetPasswordForm();
        
        $postData = Yii::$app->request->post();
        
        if (!isset($postData['key']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' key_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'key_not_given', Yii::t('app', 'key_not_given')));
        }
        
        //判断是否验证
        $cacheKey = 'user:forgetPassword:'.$postData['key'];
        if (!Yii::$app->cache->exists($cacheKey))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cachekey_not_exist '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'cachekey_not_exist', Yii::t('app', 'cachekey_not_exist')));
        }
        
        //获取用户id
        $userId = Yii::$app->cache->get($cacheKey);
        if (!$userId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_not_found', Yii::t('app', 'user_not_found')));
        }
        
        //指定用户id
        $model->userid = $userId;
        $model->load($postData, '');
        if (!$model->validate())
        {
            $errors = $model->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;

            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_forgetPasswordNew_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        if (!$model->resetPassword())
        {
            $errors = $model->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;

            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_forgetPasswordNew_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        
        //返回值
        $responseData = [
            'user_id' => $userId,
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_forgetPasswordNew_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    /**
     * 发送短信验证码
     *
     * @return \yii\web\Response
     */
    private function actionSendPhoneCode()
    {
        $_logs = [];
        
        $op = Yii::$app->request->post('op', 0);
        $_logs['$op'] = $op;
        
        if ($op == 'signup')
        {
            $phone = Yii::$app->request->post('phone', 0);
            $_logs['$phone'] = $phone;
            
            if (!StringHelper::valid_phone($phone))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_format_error', Yii::t('app', 'phone_format_error')));
            }
            
            //频率限制
            $isFrequency = SecurityHelper::checkFrequency('sendphone:'.$phone, 10, 600);
            if ($isFrequency)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' send_phone_code_excessive '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'send_phone_code_excessive', Yii::t('app', 'send_phone_code_excessive')));
            }
            
            $isExist = User::find()->where(['phone' => $phone])->exists();
            if ($isExist)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_existed '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_existed', Yii::t('app', 'phone_existed')));
            }
            
            $sms_type = 1;
        }
        elseif ($op == 'forget_password')
        {
            $phone = Yii::$app->request->post('phone', 0);
            $_logs['$phone'] = $phone;
            
            if (!StringHelper::valid_phone($phone))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_format_error', Yii::t('app', 'phone_format_error')));
            }
            
            //频率限制
            $isFrequency = SecurityHelper::checkFrequency('sendphone:'.$phone, 10, 600);
            if ($isFrequency)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' send_phone_code_excessive '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'send_phone_code_excessive', Yii::t('app', 'send_phone_code_excessive')));
            }
            
            $userInfo = User::find()->where(['phone' => $phone])->asArray()->limit(1)->one();
            if (!$userInfo)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_not_exist '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_not_exist', Yii::t('app', 'phone_not_exist')));
            }
            
            $sms_type = 7;
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
        
        $verifyPhoneForm = new VerifyPhoneForm();
        $verifyPhoneForm->phone = $phone;
        $phoneCodeHash = $verifyPhoneForm->send($sms_type);
        
        //返回值
        $responseData = [
            'phone' => $phone,
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_sendPhoneCode_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }


    /**
     * 发送邮箱验证码
     * @return \yii\web\Response
     */
    private function actionSendEmailCode()
    {
        $_logs = [];
        
        $op = Yii::$app->request->post('op', '');
        $_logs['$op'] = $op;
        
        if ($op == 'forget_password')
        {
            $email = Yii::$app->request->post('email', '');
            
            if (!StringHelper::valid_email($email))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_format_error', Yii::t('app', 'email_format_error')));
            }
            
            //频率限制
            $isFrequency = SecurityHelper::checkFrequency('sendemail:'.$email, 10, 600);
            if ($isFrequency)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' send_email_code_excessive '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'send_email_code_excessive', Yii::t('app', 'send_email_code_excessive')));
            }
            
            $userInfo = User::find()->where(['email' => $email])->asArray()->limit(1)->one();
            if (!$userInfo)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_not_exist '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_not_exist', Yii::t('app', 'email_not_exist')));
            }
            
            $verifyEmailForm = new VerifyEmailForm();
            $verifyEmailForm->email = $email;
            $emailCodeHash = $verifyEmailForm->send('forgetPassword');
            
            //返回值
            $responseData = [
                'email' => $email,
            ];
            $_logs['$responseData'] = $responseData;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_sendEmailCode_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType($responseData));
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
    }
    
    /**
     * 上传数据
     */
    public function actionUploadPublicFile()
    {
        $_logs = [];
    
        if(Yii::$app->request->isOptions)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request_is_options ');
            return $this->asJson(FormatHelper::resultStrongType('', 'request_is_options', Yii::t('app', 'request_is_options')));
        }
    
        //判断配置信息
        /*$postMaxSize = ini_get('post_max_size');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $_logs['$postMaxSize'] = $postMaxSize;
        $_logs['$uploadMaxFilesize'] = $uploadMaxFilesize;
        if(FormatHelper::file_size_to_int($postMaxSize) < Yii::$app->params['post_max_size'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config_post_max_size_error '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'config_post_max_size_error', Yii::t('app', 'config_post_max_size_error')));
        }
        if(FormatHelper::file_size_to_int($uploadMaxFilesize) < Yii::$app->params['upload_max_filesize'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config_upload_max_filesize_error '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'config_upload_max_filesize_error', Yii::t('app', 'config_upload_max_filesize_error')));
        }*/
    
        $uploadfile = UploadedFile::getInstanceByName('file');
        if (!$uploadfile)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_not_given', Yii::t('app', 'site_upload_file_not_given')));
        }
        $post_max_file_name = Yii::$app->params['post_max_file_name'];
        $originalName = strlen($uploadfile->name);
        $_logs['$post_max_file_name'] = $post_max_file_name;
        $_logs['$originalName'] = $originalName;
        // Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file name length debug '.json_encode($_logs));
        if($originalName > $post_max_file_name )
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_name_too_long '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_name_too_long', Yii::t('app', 'site_upload_file_name_too_long')));
        }
    
        $save_root = Setting::getPublicRootPath();
        $save_path = '/upload/'.date('Y/m/d');
        $id = uniqid() .'_'. time();
    
        $new_name = '/'.$id.'.'.$uploadfile->getExtension();
        $new_path = $save_root.$save_path;
        $new_file = $save_root.$save_path.$new_name;
        $new_url = $save_path.$new_name;
         
        if (!is_dir($new_path))
        {
            @mkdir($new_path, 0777, true);
            @chmod($new_path, 0777);
        }
         
        //保存到硬盘
        if (!$uploadfile->saveAs($new_file))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_save_fail '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_save_fail', Yii::t('app', 'site_upload_file_save_fail')));
        }
    
        //----------------------------------------------------
         
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' deploy_upload_success '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType([
            'upload_path' => $new_url,
        ]));
    }
    
    /**
     * 下载文件
     */
    public function actionDownloadPublicFile()
    {
        $_logs = [];
         
        $file = Yii::$app->request->get('file', null);
        $_logs['$file'] = $file;
         
        if (!$file)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_file_not_given '. json_encode($_logs));
            return false;
        }
        
        //频率限制
        $isFrequency = SecurityHelper::checkFrequency('downloadpublicfile:'.$file, 100, 10);
        if ($isFrequency)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' download_excessive '.json_encode($_logs));
            return false;
        }
        
        //对文件转码
        $filepath = rtrim(Setting::getPublicRootPath(), '/').'/'.ltrim(StringHelper::base64_decode($file), '/');
        $_logs['$filepath'] = $filepath;
        if (!$filepath)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' base64_decode error '. json_encode($_logs));
            return false;
        }
         
        //判断文件是否存在
        if (!file_exists($filepath))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' download file '.$filepath.' not exists '. json_encode($_logs));
            return false;
        }
         
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' download file success '. json_encode($_logs));
    
        return Yii::$app->response->sendFile($filepath);
    }
    
    /**
     * 上传可对外访问的图片
     * 可对外访问
     * 
     * 注意:
     * 文件上传会使用options请求, 所以要免验证
     * 
     * @return \yii\web\Response
     */
    public function actionUploadPublicImage()
    {
    	$_logs = [];
    	
    	if (Yii::$app->request->isOptions)
    	{
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request_is_options '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'request_is_options', Yii::t('app', 'request_is_options')));
    	}
    	
    	//判断配置信息
    	/*$postMaxSize = ini_get('post_max_size');
    	$uploadMaxFilesize = ini_get('upload_max_filesize');
    	$_logs['$postMaxSize'] = $postMaxSize;
    	$_logs['$uploadMaxFilesize'] = $uploadMaxFilesize;
    	if (FormatHelper::file_size_to_int($postMaxSize) < Yii::$app->params['post_max_size'])
    	{
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config_post_max_size_error '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'config_post_max_size_error', Yii::t('app', 'config_post_max_size_error')));
    	}
    	 
    	if (FormatHelper::file_size_to_int($uploadMaxFilesize) < Yii::$app->params['upload_max_filesize'])
    	{
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config_upload_max_filesize_error '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'config_upload_max_filesize_error', Yii::t('app', 'config_upload_max_filesize_error')));
    	}*/
    	
    	$uploadfile = UploadedFile::getInstanceByName('image');
    	if (!$uploadfile)
    	{
    		Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_imgage_not_given '.json_encode($_logs));
    		return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_imgage_not_given', Yii::t('app', 'site_upload_imgage_not_given')));
    	}
    	if ($uploadfile->error)
    	{
    	    $_logs['$uploadfile.error'] = $uploadfile->error;
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_not_given '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_not_given', Yii::t('app', 'site_upload_file_not_given')));
    	}
    	if ($uploadfile->size > Yii::$app->params['upload_max_filesize'])
    	{
    	    $_logs['$uploadfile.error'] = $uploadfile->error;
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_too_large '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_too_large', Yii::t('app', 'site_upload_file_too_large')));
    	}
        $post_max_file_name = Yii::$app->params['post_max_file_name'];
        $originalName = strlen($uploadfile->name);
        $_logs['$post_max_file_name'] = $post_max_file_name;
        $_logs['$originalName'] = $originalName;
        // Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file name length debug '.json_encode($_logs));
        if($originalName > $post_max_file_name )
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_name_too_long '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_name_too_long', Yii::t('app', 'site_upload_file_name_too_long')));
        }
    	
    	$save_root = Setting::getPublicRootPath();
    	$save_path = '/upload/'.date('Y/m/d');
    	$id = uniqid() .'_'. time();
    	
    	$new_name = '/'.$id.'.'.$uploadfile->getExtension();
    	$new_path = $save_root.$save_path;
    	$new_file = $save_root.$save_path.$new_name;
    	$new_url = $save_path.$new_name;
    	
    	if (!is_dir($new_path))
    	{
    		@mkdir($new_path, 0777, true);
    		@chmod($new_path, 0777);
    	}
    	
    	//保存到硬盘
    	if (!$uploadfile->saveAs($new_file))
    	{
    		Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_save_fail '.json_encode($_logs));
    		return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_save_fail', Yii::t('app', 'site_upload_file_save_fail')));
    	}
    	
    	//----------------------------------------------------
    	
//     	$file_size = filesize($new_file);
    	
//     	$type = '';
//     	if ($file_size > 200000)
//     	{
//     		$type = 'thumb';
//     	}
//     	else if ($file_size > 20000)
//     	{
//     		$type = 'resize';
//     	}
//     	$thumb_width = 200;
//     	$thumb_height = 200;
    	
//     	if ($type == 'thumb')
//     	{
//     		//-------------------------
//     		//缩略图
//     		//-------------------------
//     		$thumb_name = '/'.$id.'_s.'.$uploadfile->getExtension();
//     		$thumb_path = $save_root.$save_path;
//     		$thumb_file = $save_root.$save_path.$thumb_name;
//     		$thumb_url = Yii::$app->params['publicfile_url'].$save_path.$thumb_name;
    		
//     		if (!is_dir($thumb_path))
//     		{
//     			@mkdir($thumb_path, 0777, true);
//     			@chmod($thumb_path, 0777);
//     		}
    		
//     		//printf("4.1 total run: %.2f s<br>"."memory usage: %.2f M<br> ",microtime(true)-$HeaderTime,memory_get_usage() / 1024 / 1024 );
//     		Image::thumbnail($new_file, $thumb_width, $thumb_height)->save($thumb_file);
//     		//printf("4.3 total run: %.2f s<br>"."memory usage: %.2f M<br> ",microtime(true)-$HeaderTime,memory_get_usage() / 1024 / 1024 );
//     	}
//     	elseif ($type == 'resize')
//     	{
//     		//-------------------------
//     		//缩略图
//     		//-------------------------
//     		$thumb_name = '/'.$id.'_m.'.$uploadfile->getExtension();
//     		$thumb_path = $save_root.$save_path;
//     		$thumb_file = $save_root.$save_path.$thumb_name;
//     		$thumb_url = Yii::$app->params['publicfile_url'].$save_path.$thumb_name;
    		
//     		if (!is_dir($thumb_path))
//     		{
//     			@mkdir($thumb_path, 0777, true);
//     			@chmod($thumb_path, 0777);
//     		}
    		
//     		//printf("5.1 total run: %.2f s<br>"."memory usage: %.2f M<br> ",microtime(true)-$HeaderTime,memory_get_usage() / 1024 / 1024 );
//     		Image::resize($new_file, $thumb_width, $thumb_height)->save($thumb_file);
//     		//printf("5.2 total run: %.2f s<br>"."memory usage: %.2f M<br> ",microtime(true)-$HeaderTime,memory_get_usage() / 1024 / 1024 );
//     	}
//     	else
//     	{
//     		$thumb_url = $new_url;
//     	}
    	
    	Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_uploadPublicImage_succ '.json_encode($_logs));
    	return $this->asJson(FormatHelper::resultStrongType([
    	    'url' => $new_url,
    	]));
    }
    
    /**
     * 上传私有文件
     * 不可对外访问
     * 
     * 注意:
     * 文件上传会使用options请求, 所以要免验证
     *
     * @return boolean
     */
    public function actionUploadPrivateFile()
    {
    	$_logs = [];
    	
    	if (Yii::$app->request->isOptions)
    	{
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' request_is_options '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'request_is_options', Yii::t('app', 'request_is_options')));
    	}
    	
    	//判断配置信息
    	/*$postMaxSize = FormatHelper::file_size_to_int(ini_get('post_max_size'));
    	$uploadMaxFilesize = FormatHelper::file_size_to_int(ini_get('upload_max_filesize'));
    	$_logs['$postMaxSize'] = $postMaxSize;
    	$_logs['$uploadMaxFilesize'] = $uploadMaxFilesize;
    	if ($postMaxSize < FormatHelper::file_size_to_int(Yii::$app->params['post_max_size']))
    	{
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config_post_max_size_error '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'config_post_max_size_error', Yii::t('app', 'config_post_max_size_error')));
    	}
    	
    	if ($uploadMaxFilesize < FormatHelper::file_size_to_int(Yii::$app->params['upload_max_filesize']))
    	{
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' config_upload_max_filesize_error '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'config_upload_max_filesize_error', Yii::t('app', 'config_upload_max_filesize_error')));
    	}*/
    	
    	$postData = Yii::$app->request->post();
    	if (!$postData)
    	{
    		Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' param_not_given '.json_encode($_logs));
    		return $this->asJson(FormatHelper::resultStrongType('', 'param_not_given', Yii::t('app', 'param_not_given')));
    	}
    	
    	if (!empty($postData['project_id']))
    	{
    		$project = Project::find()->where(['id' => $postData['project_id']])->asArray()->limit(1)->one();
    		if (!$project)
    		{
    			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
    			return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
    		}
            //不是发布中的项目不允许上传数据包，点云分割执行作业也需要上传文件
            if(!in_array($project['status'], [Project::STATUS_RELEASING, Project::STATUS_SETTING, Project::STATUS_WORKING]))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_file_project_fail '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('','site_file_project_fail',Yii::t('app', 'site_file_project_fail')));
            }

    		//类型分为:上传附件和上传数据文件
    		$types = [
    		    'attachment' => Yii::$app->params['attachment_dirname'],
                'uploadfile' => Yii::$app->params['uploadfile_dirname'],
    		    'temporaryStorage' => Yii::$app->params['temporaryStorage_dirname']
    		];
    		if (empty($postData['type']) || !isset($types[$postData['type']]))
    		{
    		    $postData['type'] = 'attachment';
    		}
    		$childDirname = $types[$postData['type']];
    		
    		$save_root = Setting::getUploadRootPath();
    		$save_path = rtrim('/'.$project['user_id']. '/'.$postData['project_id'].'/'.$childDirname, '/');
    	}
    	elseif (!empty($postData['team_user_import']))
    	{
    	    $save_root = Setting::getUploadRootPath();
    		$save_path = '/team_user_import/'.time();
    	}
        elseif (!empty($postData['user_import']))
        {
            $save_root = Setting::getUploadRootPath();
            $save_path = '/user_import/'.time();
        }
        elseif (!empty($postData['deploy_path']))
        {
            $deploy_path = $postData['deploy_path'];
            $save_root = Setting::getUploadRootPath();
            $save_path = '/'.ltrim($deploy_path, '/');
            $_logs['$save_path'] = $save_path;
            $_logs['$save_root'] = $save_root;
        }
    	else
    	{
    		Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' param_error '.json_encode($_logs));
    		return $this->asJson(FormatHelper::resultStrongType('', 'param_error', Yii::t('app', 'param_error')));
    	}
    	
    	$uploadfile = UploadedFile::getInstanceByName('file');
    	if (!$uploadfile)
    	{
    		Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_not_given '.json_encode($_logs));
    		return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_not_given', Yii::t('app', 'site_upload_file_not_given')));
    	}
    	if ($uploadfile->error)
    	{
    	    $_logs['$uploadfile.error'] = $uploadfile->error;
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_not_given '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_not_given', sprintf(Yii::t('app', 'site_upload_file_not_given'), Yii::$app->params['upload_max_filesize'])));
    	}
    	if ($uploadfile->size > FormatHelper::file_size_to_int(Yii::$app->params['upload_max_filesize']))
    	{
    	    $_logs['$uploadfile.error'] = $uploadfile->error;
    	    $_logs['upload_max_filesize.f'] = FormatHelper::file_size_to_int(Yii::$app->params['upload_max_filesize']);
    	    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_too_large '.json_encode($_logs));
    	    return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_too_large', sprintf(Yii::t('app', 'site_upload_file_too_large'), FormatHelper::filesize_format($uploadfile->size), Yii::$app->params['upload_max_filesize'])));
    	}
        $post_max_file_name = Yii::$app->params['post_max_file_name'];
        $originalName = strlen($uploadfile->name);
        $_logs['$post_max_file_name'] = $post_max_file_name;
        $_logs['$originalName'] = $originalName;
        // Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file name length debug '.json_encode($_logs));
        if($originalName > $post_max_file_name )
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_name_too_long '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_name_too_long', Yii::t('app', 'site_upload_file_name_too_long')));
        }
    	
    	$id = trim($uploadfile->getBaseName(), '/');
    	$new_name = '/'.$id.'.'.$uploadfile->getExtension();
    	$new_path = $save_root.$save_path;
    	$new_file = $save_root.$save_path.$new_name;
    	$new_urlpath = $save_path.$new_name;
    	
    	
    	if (!file_exists($new_path))
    	{
    		$_logs['$new_path'] = $new_path;
    		@mkdir($new_path, 0777, true);
    		@chmod($new_path, 0777);
    		
    		if (!file_exists($new_path))
    		{
    			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_save_path_not_exist '.json_encode($_logs));
    			return $this->asJson(FormatHelper::resultStrongType('', 'site_save_path_not_exist', Yii::t('app', 'site_save_path_not_exist')));
    		}
    	}
    	
    	//保存到硬盘, 文件存在,则覆盖
    	if (!$uploadfile->saveAs($new_file))
    	{
    	    $_logs['$uploadfile-error'] = $uploadfile->error;
    		Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_upload_file_save_fail '.json_encode($_logs));
    		return $this->asJson(FormatHelper::resultStrongType('', 'site_upload_file_save_fail', Yii::t('app', 'site_upload_file_save_fail')));
    	}
    	
    	//返回值
    	$responseData = [
    		'urlpath' => $new_urlpath,
    		'md5' => md5_file($new_file),
    	    'key' => StringHelper::base64_encode($save_path.$new_name),
    	    'url' => 'site/download-private-file?file='.StringHelper::base64_encode($save_path.$new_name)
    	];
    	$_logs['$responseData'] = $responseData;
    	
    	Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_uploadPrivateFile_succ '.json_encode($_logs));
    	return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    /**
     * 删除文件
     *
     * @return boolean
     */
    public function actionDeletePrivateFile()
    {
        $_logs = [];
         
        $file = Yii::$app->request->post('file', null);
        $projectId = Yii::$app->request->post('project_id', null);
         
        if (!$file)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_file_not_given '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('','site_file_not_given',Yii::t('app', 'site_file_not_given')));
        }
        $_logs['$file'] = $file;

        if ($projectId)
        {
            $_logs['$projectId'] = $file;
            $project = Project::find()->where(['id'=>$projectId])->asArray()->limit(1)->one();
            if(!in_array($project['status'], [Project::STATUS_RELEASING, Project::STATUS_SETTING]))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_file_project_fail '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('','site_file_project_fail',Yii::t('app', 'site_file_project_fail')));
            }
        }

        //对文件转码
        $filepath = StringHelper::base64_decode($file);
        $_logs['$filepath'] = $filepath;
        if (!$filepath)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'site_file_path_decode_fail '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('','site_file_path_decode_fail',Yii::t('app', 'site_file_path_decode_fail')));
        }
         
        //获取真是路径
        $filerealpath = Setting::getUploadRootPath() . '/'. trim($filepath, '/');
        $_logs['$filerealpath'] = $filerealpath;
         
        //判断文件是否存在
        if (!file_exists($filerealpath))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' file_not_exist '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('','file_not_exist', Yii::t('app', 'file_not_exist')));
        }
    
        FileHelper::rmfile($filerealpath);
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rmfile '. json_encode($_logs));
         
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_deletePrivateFile_succ '. json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType(1));
    }
    
    /**
     * 下载文件
     *
     * @return boolean
     */
    public function actionDownloadPrivateFile()
    {
    	$_logs = [];
    	
    	$file = Yii::$app->request->get('file', null);
    	$_logs['$file'] = $file;
    	
    	if (!$file)
    	{
    		Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_downloadPrivateFile_paramError '. json_encode($_logs));
    		return false;
    	}
    	
        //频率限制
        $isFrequency = SecurityHelper::checkFrequency('downloadprivatefile:'.$file, 100, 10);
        if ($isFrequency)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' download_excessive '.json_encode($_logs));
            return false;
        }

    	//对文件转码
    	$filepath = StringHelper::base64_decode($file);
    	$_logs['$filepath'] = $filepath;
    	if (!$filepath)
    	{
    		Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'site_downloadPrivateFile_fileFormatError '. json_encode($_logs));
    		return false;
    	}
    	
    	//判断文件是否存在
    	if (!file_exists($filepath))
    	{
    	    //获取真实路径
    	    $filePath_ = Setting::getUploadRootPath() . '/'. trim($filepath, '/');
    	    $_logs['$filePath1'] = $filePath_;
    	
    	    //判断文件是否存在
    	    if (!file_exists($filePath_))
    	    {
    	        $filePath_ = Setting::getResourceRootPath() . '/'. trim($filepath, '/');
    	        $_logs['$filePath2'] = $filePath_;
    	
    	        if (!file_exists($filePath_))
    	        {
    	            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $filePath not exist '. json_encode($_logs));
    	            return false;
    	        }
    	    }
    	    	
    	    $filepath = $filePath_;
    	}
    	$_logs['$filepath.new'] = $filepath;
    	
    	//根据大小选择下载方式
    	$fileSize = FileHelper::filesize($filepath);
    	$_logs['$fileSize'] = $fileSize;
    	
    	//因内存有限, 使用file_getcontents 必须限制文件大小
    	if ($fileSize < 10000000) //10M
    	{
    	    $fileName = FileHelper::filename($filepath);
    	    
    	    //单独处理图片, 解决图片旋转问题
    	    if (FileHelper::is_image($filepath))
    	    {
    	        if (StringHelper::is_url($filepath))
    	        {
    	            $content = FileHelper::netfile_getcontent($filepath);
    	        }
    	        elseif (StringHelper::is_relativepath($filepath))
    	        {
    	            $content = ImageHelper::image_get_content($filepath);
    	        }
    	        else
    	        {
    	            $content = ImageHelper::image_get_content($filepath);
    	        }
    	        
    	        //tif格式转化
    	        if (!in_array(@pathinfo($filepath, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif']))
    	        {
    	            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' convert image '. json_encode($_logs));
    	            $content = ImageHelper::image_convert($content, 'jpeg');
    	        }
    	        // >300kb 进行压缩
    	        elseif ($fileSize > 300000)
    	        {
    	            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' compress image '. json_encode($_logs));
    	             
    	            $image_type = FileHelper::fileextension($filepath);
    	            $content = ImageHelper::image_compress($content, $image_type);
    	        }
    	    }
    	    elseif (FileHelper::is_pdf($filepath))
    	    {
    	        if (StringHelper::is_url($filepath))
    	        {
    	            $content = FileHelper::netfile_getcontent($filepath);
    	        }
    	        elseif (StringHelper::is_relativepath($filepath))
    	        {
    	            $content = FileHelper::file_getcontent($filepath);
    	        }
    	        else
    	        {
    	            $content = FileHelper::file_getcontent($filepath);
    	        }
    	    
    	        $imageHandler = new ImageHandler();
    	        $imageHandler->setResolution(120, 120);
    	        $imageHandler->loadBlob($content);
    	        if ($imageHandler->getError())
    	        {
    	            var_dump($imageHandler->getError());
    	            exit();
    	        }
    	        $imageHandler->setFormat('png');
    	        $content = $imageHandler->getBlob();
    	        $fileName .= '.png';
    	    }
    	    else
    	    {
    	        $content = FileHelper::file_getcontent($filepath);
    	    }
    	    
    	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sendContentAsFile '. json_encode($_logs));
    	    return Yii::$app->response->sendContentAsFile($content, $fileName)->send();
    	}
        //较大文件, 例如:打包下载的文件
    	else
    	{
    	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sendFile '. json_encode($_logs));
    	    return Yii::$app->response->sendFile($filepath)->send();
    	}
    }
    
    /**
     * 分批下载文件
     *
     * @return string
     */
    public function actionFetchPrivateFile()
    {
        $_logs = [];
        
        $key = Yii::$app->request->post('key', null);
        $batch = Yii::$app->request->post('batch', null);
        $size = Yii::$app->request->post('size', 1024 * 1024);
        $_logs['$key'] = $key;
        $_logs['$batch'] = $batch;
        $_logs['$size'] = $size;

        if (!$key || !$batch || !$size)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_fetchPrivateFile_paramError '. json_encode($_logs));
            return false;
        }
        
        //对文件转码
        $filepath = StringHelper::base64_decode($key);
        $_logs['$filepath'] = $filepath;
        if (!$filepath)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'site_fetchPrivateFile_fileFormatError '. json_encode($_logs));
            return false;
        }
        
        //是url
        if (StringHelper::is_url($filepath))
        {
            if (!FileHelper::netfile_exists($filepath))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataArr image_url not exist '.json_encode($_logs));
                return false;
            }
            
            $binary = FileHelper::netfile_getcontent($filepath);
            $base64Batch = substr(base64_encode($binary), ($batch - 1) * $size, $size);
            
            return $base64Batch;
        }
        //如果是本地硬盘相对路径
        elseif (StringHelper::is_relativepath($filepath))
        {
            //获取真实路径
            $filerealpath = Setting::getUploadRootPath() . '/'. trim($filepath, '/');
            $_logs['$filerealpath'] = $filerealpath;
            
            //判断文件是否存在
            if (!file_exists($filerealpath))
            {
                $filerealpath = Setting::getResourceRootPath() . '/'. trim($filepath, '/');
                $_logs['$filerealpath'] = $filerealpath;
            
                if (!file_exists($filerealpath))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_fetchPrivateFile_fileExistsFalse '. json_encode($_logs));
                    return false;
                }
            }
            
            // $mime = FileHelper::get_file_mime($filerealpath);
            $binary = file_get_contents($filerealpath);
            $base64Batch = substr(base64_encode($binary), ($batch - 1) * $size, $size);
            
            return $base64Batch;
        }
    }
    
    public function actionGetOnlineUsers()
    {
        $_logs = [];
        
        $userIds = ActionUserFilter::getOnlineUsers();
        $_logs['$userIds'] = $userIds;
        
        $userStatsByType = [];
        $users = [];
        if ($userIds)
        {
            $userStatsByType = User::find()
            ->select(['type', 'count(*) as count'])
            ->where(['in', 'id', $userIds])
            ->groupBy('type')
            ->asArray()->all();
        }
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        
        return $this->asJson(FormatHelper::resultStrongType([
            'userStatsByType' => $userStatsByType,
            'count' => count($userIds),
            'users' => $users,
        ]));
    }
    
    public function actionStat()
    {
        
        //各平台访问量统计
        $appStatList = AppStat::find()->orderBy(['date' => SORT_DESC])->limit(30)->asArray()->with(['app'])->all();
        $appStats = [];
        if ($appStatList)
        {
            foreach ($appStatList as $v)
            {
                $appStats[$v['date']][$v['app']['name']] = $v['count'];
            }
            $appStats = array_reverse($appStats);
        }
        
        $responseData = [];
        $responseData['appStats'] = $appStats;
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));;
    }
    
    public function actionList()
    {
        $_logs = [];
        
        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $keyword = trim(Yii::$app->request->post('keyword', null));
        $status = Yii::$app->request->post('status', null);
        $siteType = Yii::$app->request->post('type', null);
        $serviceType = Yii::$app->request->post('service_type', null);
        $siteId = Yii::$app->request->post('id', '');
        $siteName = Yii::$app->request->post('name', '');
        $startTimeFrom = Yii::$app->request->post('start_time_from', 0);
        $startTimeTo = Yii::$app->request->post('start_time_to', 0);
        $endTimeFrom = Yii::$app->request->post('end_time_from', 0);
        $endTimeTo = Yii::$app->request->post('end_time_to', 0);
        $createdBy = Yii::$app->request->post('created_by', 0);
        $createdFrom = Yii::$app->request->post('created_from', '');
        
        //翻页
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 10;
        $offset = ($page-1)*$limit;
        
        //排序
        if (!in_array($orderby, ['id', 'created_at', 'start_time', 'end_time', 'last_login_time']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        //-------------------------------------
        
        //root权限可操作所有租户信息
        if (Yii::$app->user->identity->type == User::TYPE_ROOT)
        {
        }
        //其他用户可以看到本租户人员
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
        }
        
        //---------------------------------------
        
        $query = Site::find();
        $query->andWhere(['not', ['status' => Site::STATUS_DELETED]]);
        $siteId !== '' && $query->andWhere(['id' => $siteId]);
        $siteName !== '' && $query->andWhere(['like', 'name', $siteName]);
        $createdBy && $query->andWhere(['created_by' => $createdBy]);
        $createdFrom !== '' &&  $query->andWhere(['created_from' => $createdFrom]);
        if($keyword || $keyword == '0')
        {
            $query->andFilterWhere([
                'or',
                ['like', 'id', $keyword],
                ['like', 'name', $keyword],
            ]);
        }
        if ($status != null)
        {
            $statuses = FormatHelper::param_int_to_array($status);
            if ($statuses)
            {
                $query->andWhere(['in', 'status', $statuses]);
            }
        }
        if($startTimeFrom)
        {
            $query->andWhere(['>=', 'start_time', $startTimeFrom]);
        }
        if($startTimeTo)
        {
            $query->andWhere(['<=', 'start_time', $startTimeTo]);
        }
        if($endTimeFrom)
        {
            $query->andWhere(['>=', 'end_time', $endTimeFrom]);
        }
        if($endTimeTo)
        {
            $query->andWhere(['<=', 'end_time', $endTimeTo]);
        }
        $count = $query->count();
        
        $list = [];
        if($count > 0)
        {
            $list = $query->select(['*'])
            ->with(['creator'])
            ->orderBy([$orderby => $sort == 'asc'? SORT_ASC: SORT_DESC])
            ->offset($offset)->limit($limit)
            ->asArray()->all();
        }
        
        if ($list)
        {
            foreach ($list as $k => $v)
            {
                //$userCount = User::find()->where(['site_id' => $v['id']])->andWhere(['not', ['status' => User::STATUS_DELETED]])->count();
                $userCount = SiteUser::find()->where(['site_id' => $v['id']])->andWhere(['status' => SiteUser::STATUS_ENABLE])->count();
                $v['user_count'] = $userCount;
                $list[$k] = $v;
            }
        }
        
        $creatorIds = Site::find()->select(['created_by'])->Where(['not', ['status' => Site::STATUS_DELETED]])->distinct()->asArray()->column();
        $creators = User::find()->select(['id', 'nickname'])->where(['in', 'id', $creatorIds])->asArray()->all();
        
        $data = [
            'list' => $list,
            'count' => $count,
            'page' => $page,
            'limit' => $limit,
            'statuses' => Site::getStatuses(),
            //'types' => Site::getTypes(),
            // 'service_types' => Site::getBriefServiceType(),
            'creators' => $creators,
            'created_from' => Site::getCreatedFrom()
        ];
    
        return $this->asJson(FormatHelper::resultStrongType($data));
    }
    
    public function actionForm()
    {
    
        $categories = Category::find()
        ->select(['id', 'type', 'file_type'])
        ->where(['status' => Category::STATUS_ENABLE])
        ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
        ->with(['desc'])
        ->asArray()->all();
        
        $stepGroups = StepGroup::find()
        ->select(['id', 'name', 'sort'])
        ->where(['status' => StepGroup::STATUS_ENABLE])
        ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
        ->asArray()->all();
        
        $packScripts = PackScript::find()
        ->select(['id', 'name'])
        ->where(['status' => PackScript::STATUS_ENABLE])
        ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
        ->asArray()->all();
        
        //打包脚本翻译
        foreach ($packScripts as $k => $v)
        {
        	if(!empty($v['key']) && is_string($v['key']))
        	{
        		$packScripts[$k]['name'] = Yii::t('app', $v['key']);
        	}
        }
        
        $data = [
            'categories' => $categories,
            'stepGroups' => $stepGroups,
            'packScripts' => $packScripts,
        ];
    
        return $this->asJson(FormatHelper::resultStrongType($data));
    }
    
    public function actionCustomerService()
    {
        $_logs = [];
        
        //因官网需要https的,故此处做了兼容处理
        $postData = array_merge(Yii::$app->request->get(), Yii::$app->request->post());
        //$postData = Yii::$app->request->get();
        $_logs['$postData'] = $postData;
        
        //前段对密码做了base64加密
        if (isset($postData['password']))
        {
            $password = StringHelper::base64_decode($postData['password']);
            if ($password)
            {
                $postData['password'] = $password;
            }
        }
        
        
        $registerForm = new RegisterForm();
        $registerForm->load($postData, '');//导入数据, ''字符串表不使用场景, 强制导入
        if (!$registerForm->validate())
        {
            $errors = $registerForm->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;
            
            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        
        $user = $registerForm->signup();
        if (!$user)
        {
            $errors = $registerForm->getFirstErrors();
            $_logs['$model.getFirstErrors'] = $errors;
            
            $error = key($errors);
            $message = current($errors);
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $userId = $user->id;
        $_logs['$userId'] = $userId;
        
        //----------------------------------
        
        //返回值
        $responseData = [
            'id' => $userId,
            'access_token' => $user->access_token
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_signup_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    /**
     * Login action
     *
     * access_token "aeUetAcjdH8T27o_Iq7-2L9CS6oOuZzR_5a53114e54b71_1515393358"
     *
     * @return string
     */
    public function actionThirdpartyLogin()
    {
        $_logs = [];
        
        $postData = Yii::$app->request->getBodyParams();
        $_logs['$postData'] = $postData;
        
        Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' Login submit ' . json_encode($_logs));
        
        //用户登录
        $model = new ThirdpartyLoginForm();
        $model->load($postData, '');
        $loginResult = $model->login();
        if (empty($loginResult)) {
            $errors = $model->getFirstErrors();
            $error = key($errors);
            $message = current($errors);
            
            $_logs['$model.$errors'] = $errors;
            $_logs['$model.$error'] = $error;
            $_logs['$model.$message'] = $message;
            
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' ' . $error . ' ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType([], $error, $message));
        }
        
        //返回值
        $responseData = [
            'id' => $loginResult['id'],
            'access_token' => $loginResult['access_token'],
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' site_login_succ ' . json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
}
