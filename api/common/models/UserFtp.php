<?php

namespace common\models;

use Yii;

/**
 * user_ftp 表数据模型
 *
 */
class UserFtp extends \yii\db\ActiveRecord
{
    const STATUS_ENABLE = 0;
    const STATUS_DISABLE = 1;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_ftp';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id','created_at', 'updated_at'], 'integer'],
            [['ftp_host','ftp_username', 'ftp_password'], 'string', 'max' => 64],
            [['ftp_home'], 'string', 'max' => 120],
            ['ftp_username', 'unique']
        ];
    }

    
    
}
