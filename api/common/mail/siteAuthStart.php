<?php
/**
 * 注册用户激活账户发送邮件模板
 */

use yii\helpers\Html;
use common\models\User;
use common\models\Setting;
use common\helpers\ImageHelper;
use common\helpers\FileHelper;

$_logs = [];

if(empty($link))
{
    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' email param lack ' . json_encode($_logs));
    exit();
}

Yii::$app->language = empty($language) ? User::getLanguageKey(User::LANGUAGE_ZH_CN) : $language;

$platform = Setting::getSetting('platform');

$str = Yii::t('app', 'email_site_auth_start_content');
//ai平台,且语言为英文
if(trim($platform) == 'ai' && Yii::$app->language = 'en')
{
    $str = Yii::t('app', 'email_site_auth_start_content_ai');
}



?>
<div class="container">
    <div class="main-header">
        <?php if(Yii::$app->language == USER::LANGUAGE_KEY_ZH_CN):?>
        <img src="/publicfile/images/email/logo.png" alt="">
        <?php elseif(Yii::$app->language == USER::LANGUAGE_KEY_EN):?>
        <img src="/publicfile/images/email/logo_full_en.png" alt="">
        <?php endif;?>
    </div>
    <div class="main-content">
        <?php
            echo sprintf($str, $account, $password, Html::a(Html::encode($link), $link));

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
    </div>
</div>
<style>
    html,body {
        width: 100%;
        height: 100%;
        background:rgba(240,242,234,1);
        overflow: hidden;
    }
    .container {
        width: 600px;
        height: 800px;
        /*position: absolute;*/
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        margin: auto
    }
    .main-header {
        text-align: center;
        margin-bottom: 10px;
    }
    .main-content {
        height: 600px;
        padding: 25px;
        background:rgba(255,255,255,1);
        border:1px solid rgba(217,217,217,1);
        box-shadow:0px 1px 10px 0px rgba(62,66,48,0.1);
    }
    .main-content .title{
        font-size:24px;
        font-family:MicrosoftYaHei-Bold;
        font-weight:bold;
        color:rgba(73,73,73,1);
        line-height:28px;
        display: inline-block;
        margin-bottom: 25px;
    }
    .main-content .text1{
        font-size:18px;
        font-family:MicrosoftYaHei;
        font-weight:400;
        color:rgba(51,51,51,1);
        line-height:28px;
        margin-bottom: 15px;
    }
    .main-content .text2{
        text-decoration: underline
    }
    .main-content .text3{
        margin-top: 20px;
    }

</style>