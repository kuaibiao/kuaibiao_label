<?php

namespace common\models;

use Yii;

/**
 * user_attribute 表数据模型
 *
 */
class UserAttribute extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_attribute';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['register_description'], 'string', 'max' => 65535],
            [['register_files'], 'string', 'max' => 65535],
        ];
    } 
    
}
