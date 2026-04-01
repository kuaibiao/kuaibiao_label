<?php

namespace common\models;

use Yii;

/**
 * user_stat 表数据模型
 *
 */
class UserStat extends \yii\db\ActiveRecord
{
    const PLATFORM_PC = 0;
    const PLATFORM_IOS = 1;
    const PLATFORM_ADR = 2;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_stat';
    }
    
    public static function primaryKey()
    {
        return ['user_id'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            ['login_last_time', 'integer'],
            ['login_last_ip', 'string', 'max' => 15],
            ['login_last_useragent', 'default', 'value' => ''],
            ['login_last_useragent', 'filter', 'filter' => function($value) {
                return strlen($value) > 225 ? substr($value, 0, 225) : $value;
            }],
            ['login_last_useragent', 'string', 'max' => 225],
        ];
    }
    
    public static function fetch($userId, $field = '')
    {
        $userStat = UserStat::find()->where(['user_id' => $userId])->asArray()->limit(1)->one();
        if (!$userStat)
        {
            $userStat = new UserStat();
            $userStat->user_id = $userId;
            $userStat->save();
        }
        
        if (!empty($field))
        {
            return isset($userStat[$field]) ? $userStat[$field] : '';
        }
        else
        {
            return $userStat;
        }
    }
    
    /**
     * 增加用户的通知数
     * 不需要判断userstat记录是否存在
     * 
     * @param int|0 $userId
     * @param number $num
     */
    public static function incMessageCount($userId, $num = 1)
    {
        if (!$userId)
        {
            return null;
        }
        
        $counters = [
            'new_message_count' => $num
        ];
        UserStat::updateAllCounters($counters, ['user_id' => $userId]);
    }
    
    /**
     * 清除新通知数
     * 
     * @param unknown $userId
     */
    public static function clearMessageCount($userId)
    {
        if (!$userId)
        {
            return null;
        }
        
        $counters = [
            'new_message_count' => 0
        ];
        UserStat::updateAll($counters, ['user_id' => $userId]);
    }
    
    /**
     * 减少新通知数
     * 
     * @param unknown $userId
     */
    public static function decrMessageCount($userId)
    {
        if (!$userId)
        {
            return null;
        }
        
        
        $counters = [
            'new_message_count' => -1
        ];
        UserStat::updateAllCounters($counters, 'user_id='. $userId. ' and new_message_count>0');
    }
    
    /**
     * 转化平台
     */
    public static function transPlatform($pt)
    {
        $platforms = ['pc' => self::PLATFORM_PC, 'ios' => self::PLATFORM_IOS, 'adr' => self::PLATFORM_ADR];
        if (isset($platforms[$pt]))
        {
            return $platforms[$pt];
        }
        else
        {
            return self::PLATFORM_PC;
        }
    }
    
}
