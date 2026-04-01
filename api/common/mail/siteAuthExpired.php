<?php
/**
 * @note 租户授权有效期过期
 */

use yii\helpers\Html;
use common\models\User;
use common\models\Setting;
use common\helpers\ImageHelper;
use common\helpers\FileHelper;

//Html::encode($user->email)


Yii::$app->language = empty($language) ? User::getLanguageKey(User::LANGUAGE_EN) : $language;

$message = Yii::t('app', 'email_site_auth_expired');

$platform = Setting::getSetting('platform');
//ai平台,且语言为英文
if(trim($platform) == 'ai' && Yii::$app->language = 'en')
{
    $message = Yii::t('app', 'email_site_auth_expired_ai');
}

echo $message;

//邮箱签名
if(trim($platform) == 'ai' && Yii::$app->language = 'en')
{
    $logoImg = dirname(Yii::getAlias('@app')) .'/publicfile/images/email/ai_logo.png';
    $logobase64 = '';
    if (FileHelper::file_exists($logoImg))
    {
        $logobase64 = ImageHelper::base64_encode_image(file_get_contents($logoImg), 'png');
    }

    echo sprintf(Yii::t('app', 'email_company_sign_ai'), $logobase64);

}
else
{
    $logoImg = dirname(Yii::getAlias('@app')) .'/publicfile/images/email/logo.png';
    $publicImg = dirname(Yii::getAlias('@app')) .'/publicfile/images/email/public.png';
    $xiaobeiImg = dirname(Yii::getAlias('@app')) .'/publicfile/images/email/xiaobei.jpeg';

    $logobase64 = '';
    $publicbase64 = '';
    $xiaobeibase64 = '';
    if (FileHelper::file_exists($logoImg))
    {
        $logobase64 = ImageHelper::base64_encode_image(file_get_contents($logoImg), 'png');
    }

    if (FileHelper::file_exists($publicImg))
    {
        $publicbase64 = ImageHelper::base64_encode_image(file_get_contents($publicImg), 'png');
    }

    if (FileHelper::file_exists($xiaobeiImg))
    {
        $xiaobeibase64 = ImageHelper::base64_encode_image(file_get_contents($xiaobeiImg), 'jpeg');
    }

    echo sprintf(Yii::t('app', 'email_company_sign'), $logobase64, $publicbase64, $xiaobeibase64);
}


?>
