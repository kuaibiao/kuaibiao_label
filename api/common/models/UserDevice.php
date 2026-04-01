<?php

namespace common\models;

use Yii;

/**
 * user_device 表数据模型
 */
class UserDevice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_device';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['user_id', 'integer'],
            ['user_id', 'default', 'value' => 0],
            
            //['status', 'integer'],
            //['status', 'default', 'value' => self::STATUS_OFF_LINE],
            //['status', 'in', 'range' => [self::STATUS_OFF_LINE, self::STATUS_ON_LINE]],
            
            //['device_type', 'integer'],
            //['device_type', 'default', 'value' => self::DEVICE_TYPE_PC],
            //['device_type', 'in', 'range' => [self::DEVICE_TYPE_PC, self::DEVICE_TYPE_MOBILE]],
            
            //[['app_id'], 'integer'],
            
            [['device_name'], 'string', 'max' => 60],
            [['device_name'], 'default', 'value' => ''],
            
            //['access_token', 'string', 'max' => 250],
            //['access_token', 'default', 'value' => ''],
            
//             ['push_token', 'string', 'max' => 250],
//             ['push_token', 'default', 'value' => ''],
            
//             ['screen_rp', 'string', 'max' => 127],
//             ['screen_rp', 'default', 'value' => ''],
            
//             ['browser_version', 'string', 'max' => 127],
//             ['browser_version', 'default', 'value' => ''],
            
//             ['os_version', 'string', 'max' => 127],
//             ['os_version', 'default', 'value' => ''],
            
//             ['reply_status', 'integer'],
//             ['reply_status', 'default', 'value' => self::REPLY_STATUS_DEFAULT],
//             [
//                 'reply_status', 'in', 'range' => [
//                     self::REPLY_STATUS_DEFAULT,
//                     self::REPLY_STATUS_ALLOW,
//                     self::REPLY_STATUS_REFUSE,
//                     self::REPLY_STATUS_APPLY
//                 ]
//             ],
            
//             ['reply_begin_time', 'integer'],
//             ['reply_begin_time', 'default', 'value' => 0],
            
//             ['reply_device_number', 'string', 'max' => 64],
//             ['reply_device_number', 'default', 'value' => ''],
            
//             ['user_ip', 'string'],
//             ['user_ip', 'default', 'value' => ''],
        ];
    }
}
