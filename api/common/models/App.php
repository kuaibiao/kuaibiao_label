<?php

namespace common\models;

use Yii;

/**
 * app 表数据模型
 *
 */
class App extends \yii\db\ActiveRecord
{
    const STATUS_DEFAULT = 0;//未审核
    const STATUS_ENABLE = 1;//通过
    const STATUS_DISABLE = 2;//禁用
    
    const PLATFORM_PC_ADMIN = 0;
    const PLATFORM_PC_TEAM = 1;
    const PLATFORM_WIN = 2;
    const PLATFORM_IOS = 4;
    const PLATFORM_ADR = 5;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app';
    }
    
    public static function primaryKey()
    {
        return 'id';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform','user_type','status','created_at', 'updated_at'], 'integer'],
            [['name','appkey', 'app_version'], 'string', 'max' => 64],
        ];
    }
    
    
    
}
