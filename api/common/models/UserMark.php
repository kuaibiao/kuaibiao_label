<?php

namespace common\models;

use Yii;

/**
 * user_mark 表数据模型
 *
 */
class UserMark extends \yii\db\ActiveRecord
{
    const TYPE_0 = 0;
    const TYPE_1 = 1;
    const TYPE_2 = 2;
    const TYPE_3 = 3;
    const TYPE_4 = 4;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_mark';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id','type','task_id','mark_user_id','mark_time','created_at', 'updated_at'], 'integer'],
            ['mark_ip', 'string', 'max' => 15],
            ['content', 'string', 'max' => 65535],
            
            ['content', 'required'],
            
            ['task_id', 'default', 'value' => 0]
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'task_id' => '任务ID',
            'content' => '跟进内容',
        ];
    }

    public static function gettType($key)
    {
        $vals = self::getTypes();
    
        return isset($vals[$key]) ? $vals[$key] : '';
    }
    
    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_0 => Yii::t('app', 'usermark_type_0'),
            self::TYPE_1 => Yii::t('app', 'usermark_type_1'),
            self::TYPE_2 => Yii::t('app', 'usermark_type_2'),
            self::TYPE_3 => Yii::t('app', 'usermark_type_3'),
            self::TYPE_4 => Yii::t('app', 'usermark_type_4'),
        ];
    }
    
}
