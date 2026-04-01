<?php

namespace common\models;

use Yii;

/**
 * 站点属性表数据模型
 *
 */
class SiteAttribute extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'site_attribute';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['site_id', 'integer'],
            ['site_id', 'default', 'value' => 0],

            [['created_at', 'updated_at'], 'integer'],
            
            ['notice', 'default', 'value' => ''],
            ['notice', 'string', 'max' => 20000],//max 16777215
        ];
    }
}
