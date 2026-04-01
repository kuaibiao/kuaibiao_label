<?php

//【忘记密码】
/**
 * 适用于已登录用户
 *
 */
use common\models\User;
use common\models\Setting;
use common\helpers\ImageHelper;
use common\helpers\FileHelper;

$expire = Yii::$app->params['emailcode_timeout'];

Yii::$app->language = empty($language) ? User::getLanguageKey(User::LANGUAGE_ZH_CN) : $language;

echo sprintf(Yii::t('app', 'email_reset_password_code_content'), $code, round($expire / 3600));

?>


