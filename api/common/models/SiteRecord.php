<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * site_record 表数据模型
 *
 */
class SiteRecord extends ActiveRecord
{
    const TYPE_SIGNUP = 1;
    const TYPE_CREATE = 2;
    const TYPE_VERIFIED = 3;
    const TYPE_EDIT = 4;
    const TYPE_DELETE = 5;
    const TYPE_CLOSE = 6;
    const TYPE_OPEN = 7;
    const TYPE_AUDIT = 8;


    public static $tableName = 'site_record';

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
            ['site_id', 'integer'],
            ['site_id', 'default', 'value' => 0],

            ['created_by', 'integer'],
            ['created_by', 'default', 'value' => 0],

            [['created_at', 'updated_at'], 'integer'],

            ['type', 'integer'],
            ['type', 'in', 'range' => array_keys(self::getTypes())],
            ['type', 'default', 'value' => self::TYPE_SIGNUP],

            ['ip', 'string', 'max' => 15],
            ['ip', 'default', 'value' => ''],

            ['message', 'string', 'max' => 65535],
            ['message', 'default', 'value' => ''],
        ];
    }


    /**
     *    注册租户 日志
     */

    public static function signupRecord($siteId)
    {
        return self::saveRecord(self::TYPE_SIGNUP, $siteId, self::getType(self::TYPE_SIGNUP));
    }

    /**
     *    创建租户
     */

    public static function createRecord($siteId)
    {
        return self::saveRecord(self::TYPE_CREATE, $siteId, self::getType(self::TYPE_CREATE));
    }

    /**
     *    租户认证
     */

    public static function verifiedRecord($siteId)
    {
        return self::saveRecord(self::TYPE_VERIFIED, $siteId, self::getType(self::TYPE_VERIFIED));
    }

    /**
     *    编辑租户
     */

    public static function editRecord($siteId)
    {
        return self::saveRecord(self::TYPE_EDIT, $siteId, self::getType(self::TYPE_EDIT));
    }

    /**
     *    删除租户
     */

    public static function deleteRecord($siteId)
    {
        return self::saveRecord(self::TYPE_DELETE, $siteId, self::getType(self::TYPE_DELETE));
    }

    /**
     *    禁用租户
     */

    public static function closeRecord($siteId)
    {
        return self::saveRecord(self::TYPE_CLOSE, $siteId, self::getType(self::TYPE_CLOSE));
    }

    /**
     *    重启租户
     */

    public static function openRecord($siteId)
    {
        return self::saveRecord(self::TYPE_OPEN, $siteId, self::getType(self::TYPE_OPEN));
    }

    /**
     *    审核租户
     */

    public static function auditRecord($siteId)
    {
        return self::saveRecord(self::TYPE_AUDIT, $siteId, self::getType(self::TYPE_AUDIT));
    }


    //记录日志
    public static function saveRecord($type, $siteId, $message)
    {
        $_logs = [];

        if(empty($siteId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' site_record_siteId_not_found ' . json_encode($_logs));
            return false;
        }
        if(empty(Yii::$app->user) || empty(Yii::$app->user->id))
        {
            $created_by = 0;
        }
        else
        {
            $created_by = Yii::$app->user->id;
        }

        $message = $_POST;
        unset($message['access_token'], $message['id']);

        $siteRecord = new SiteRecord();
        $siteRecord->site_id = $siteId;
        $siteRecord->type = $type;
        $message ? $siteRecord->message = json_encode($message) : '';
        $siteRecord->ip = Yii::$app->request->getUserIP();
        $siteRecord->created_by = $created_by;
        $siteRecord->created_at = time();
        $siteRecord->updated_at = time();
        if(!$siteRecord->validate() || !$siteRecord->save())
        {
            $_logs['$siteRecord'] = $siteRecord;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' siteRecord_fail ' . json_encode($_logs));
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
            self::TYPE_SIGNUP => yii::t('app', 'site_record_type_signup'),
            self::TYPE_CREATE => yii::t('app', 'site_record_type_create'),
            self::TYPE_VERIFIED => yii::t('app', 'site_record_type_verified'),
            self::TYPE_EDIT => yii::t('app', 'site_record_type_edit'),
            self::TYPE_DELETE => yii::t('app', 'site_record_type_delete'),
            self::TYPE_CLOSE => yii::t('app', 'site_record_type_close'),
            self::TYPE_OPEN => yii::t('app', 'site_record_type_open'),
            self::TYPE_AUDIT => yii::t('app', 'site_record_type_audit'),
        ];
    }

    public function getOperateUser()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by'])->select(['id', 'nickname']);
    }
}
