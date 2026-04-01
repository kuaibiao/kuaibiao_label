<?php
/**
 * 数据导入成功通知
 *
 */

use common\models\User;
use common\models\Setting;

//Html::encode($user->email)


Yii::$app->language = empty($language) ? User::getLanguageKey(User::LANGUAGE_EN) : $language;

$platform = Setting::getSetting('platform');

$message = "</div><p>已成功导入数据:</p><p>数据来源:%s</p><p>数据管理ID:%s</p><p>数据类型:%s</p><p>请检查无误后,确认提交对应数据管理。
</p></div><div>邮件来源: %s</div>";

echo sprintf($message, $name, $data_manage_id, $type, $base_url);

?>

<style>
    .verification_code{
        color: rgb(21, 125, 241);
        font-size: 16pt;
    }
</style>

