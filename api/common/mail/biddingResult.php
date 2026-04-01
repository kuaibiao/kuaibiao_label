<?php
/**
 * 中标通知邮件
 *
 */


use yii\helpers\Html;
use common\models\User;
use common\models\Setting;
use common\helpers\ImageHelper;
use common\helpers\FileHelper;

//Html::encode($user->email)


Yii::$app->language = empty($language) ? User::getLanguageKey(User::LANGUAGE_EN) : $language;

$platform = Setting::getSetting('platform');

//中标
if ($type == 0)
{

    //ai平台,且语言为英文
    if(trim($platform) == 'ai' && Yii::$app->language = 'en')
    //if(Yii::$app->language = 'en')
    {
        $message = Yii::t('app', 'email_win_bidding_content_ai');
        echo sprintf($message, $project_name);
    }
    else
    {
        $message = Yii::t('app', 'email_win_bidding_content');
        echo sprintf($message, $project_name);
    }

}
//未中标
elseif ($type == 1)
{
    //ai平台,且语言为英文
    if(trim($platform) == 'ai' && Yii::$app->language = 'en')
    {
        $message = Yii::t('app', 'email_fail_bidding_content_ai');
        echo sprintf($message, $project_name);
    }
    else
    {
        $message = Yii::t('app', 'email_fail_bidding_content');
        echo sprintf($message, $project_name);
    }
}
//待沟通
elseif ($type == 2)
{
    //ai平台,且语言为英文
    if(trim($platform) == 'ai' && Yii::$app->language = 'en')
    {
        $message = Yii::t('app', 'email_wait_contact_content_ai');
        echo sprintf($message);

    }
    else
    {
        $message = Yii::t('app', 'email_wait_contact_content');
        echo sprintf($message, $project_name);
    }

}

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

<style>
    .verification_code{
        color: rgb(21, 125, 241);
        font-size: 16pt;
    }
</style>

