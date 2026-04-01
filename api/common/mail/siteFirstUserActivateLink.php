<?php
/**
 * 创建租户 用户激活账户发送邮件模板
 */

use yii\helpers\Html;
use common\models\User;
use common\models\Setting;
use common\helpers\ImageHelper;
use common\helpers\FileHelper;

$_logs = [];

if(empty($link) || empty($expire))
{
    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user notexist ' . json_encode($_logs));
    exit();
}

Yii::$app->language = empty($language) ? User::getLanguageKey(User::LANGUAGE_ZH_CN) : $language;

$platform = Setting::getSetting('platform');

$str = Yii::t('app', 'email_site_first_user_activate_content');
//ai平台,且语言为英文
if(trim($platform) == 'ai' && Yii::$app->language = 'en')
{
    $str = Yii::t('app', 'email_site_first_user_activate_content_ai');
}



?>
<div class="container">
    <div class="main-content">
        <?php
        //echo sprintf($str, $link, Html::a(Html::encode($link), $link), round($expire / 3600));
        if(trim($platform) == 'ai' && Yii::$app->language = 'en')
        {
            echo sprintf($str, $link, Html::a(Html::encode($link), $link), round($expire / 3600));

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
            echo sprintf($str, $link, Html::a(Html::encode($link), $link), round($expire / 3600));

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
        width: 800px;
        height: inherit;
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
    .resource {
        width: 490px;
        padding: 10px 20px;
        box-shadow: 0px 6px 30px rgba(164, 172, 179, 0.18);
        border-radius: 8px;
    }
    .resource div {
        font-size: 15px;
        margin-bottom: 10px;
    }
    .resource .title {
        text-align: center;
    }
</style>