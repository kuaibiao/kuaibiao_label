<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;
use common\helpers\HttpHelper;
/**
 * user_record 表数据模型
 *
 */
class UserRecord extends ModelComponent
{
    const TYPE_SIGNUP = 1;
    const TYPE_SIGNUPED = 2;
    const TYPE_LOGIN = 3;
    const TYPE_EDIT = 4;
    const TYPE_DELETE = 5;
    const TYPE_UPDATE_PASSWORD = 6;
    const TYPE_UPDATE_EMAIL = 7;
    const TYPE_UPDATE_MOBILE = 8;
    const TYPE_IMPORT = 9;
    const TYPE_MODIFY_USER = 10;
    const TYPE_DELETE_USER = 11;
    const TYPE_SEND_EMAIL_SUCC = 12;
    const TYPE_SEND_EMAIL_FAIL = 13;
    const TYPE_SEND_SMS = 14;


    public static $tableName = 'user_record';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return self::$tableName;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['user_id', 'integer' ],
            ['user_id', 'default', 'value' => 0],

//            ['created_by', 'integer' ],
//            ['created_by', 'default', 'value' => 0],

            [['created_at', 'updated_at'], 'integer'],

//            ['type', 'integer' ],
//            ['type', 'in', 'range' => array_keys(self::getTypes())],
//            ['type', 'default', 'value' => self::TYPE_SIGNUP],

            ['ip', 'string', 'max' => 15],
            ['ip', 'default', 'value' => ''],

            ['message', 'string', 'max' => 65535],
            ['message', 'default', 'value' => ''],
        ];
    }

    /**
     *    用户删除日志
     */

    public static function updatePasswordRecord($userId)
    {
        return self::saveRecord(self::TYPE_UPDATE_PASSWORD, $userId, self::getType(self::TYPE_UPDATE_PASSWORD));
    }

    //记录日志
    public static function saveRecord($event, $userId, $message)
    {
        $_logs=[];

        if(empty($userId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_record_userId_not_found '.json_encode($_logs));
            return false;
        }
        if(empty(Yii::$app->user) || empty(Yii::$app->user->id))
        {
            $created_by = $userId;
        }
        else
        {
            $created_by = Yii::$app->user->id;
        }

        $suffix = UserRecord::generateSuffix();
        UserRecord::setTable($suffix);

        $userRecord = new UserRecord();
        $userRecord->user_id = $userId;
        $userRecord->event = $event;
        $userRecord->message = $message;
        $userRecord->ip = HttpHelper::get_ip();
        $userRecord->created_by = $created_by;
        $userRecord->created_at = time();
        $userRecord->updated_at = time();
        if (!$userRecord->validate() || !$userRecord->save())
        {
            $_logs['$userRecord'] = $userRecord;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' projectRecord_fail '. json_encode($_logs));
            return false;
        }
    }

    public static function getType($var)
    {
        $vars = self::getTypes();
        return isset($vars[$var]) ? $vars[$var] : null;
    }

    //获取 event 对应类型
    public static function getTypes()
    {
        return [
            self::TYPE_SIGNUP => yii::t('app','user_record_type_signup'),
            self::TYPE_SIGNUPED => yii::t('app','user_record_type_signuped'),
            self::TYPE_LOGIN => yii::t('app','user_record_type_login'),
            self::TYPE_EDIT => yii::t('app','user_record_type_edit'),
            self::TYPE_DELETE => yii::t('app','user_record_type_delete'),
            self::TYPE_UPDATE_PASSWORD => yii::t('app','user_record_type_update_password'),
            self::TYPE_UPDATE_EMAIL => yii::t('app','user_record_type_update_email'),
            self::TYPE_UPDATE_MOBILE => yii::t('app','user_record_type_update_mobile'),
            self::TYPE_IMPORT => yii::t('app','user_record_type_import'),
            self::TYPE_MODIFY_USER => yii::t('app','user_record_type_modify_user'),
            self::TYPE_DELETE_USER => yii::t('app','user_record_type_delete_user'),
            self::TYPE_SEND_EMAIL_SUCC => yii::t('app', 'user_record_type_send_email_succ'),
            self::TYPE_SEND_EMAIL_FAIL => yii::t('app', 'user_record_type_send_email_fail'),
            self::TYPE_SEND_SMS => yii::t('app','user_record_type_send_sms'),
        ];
    }
}
