<?php

namespace common\models;

use Yii;

/**
 * app_stat 表数据模型
 *
 */
class AppStat extends \yii\db\ActiveRecord
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_stat';
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
            [['app_id','count'], 'integer'],
            [['date'], 'string'],
        ];
    }
    
    //绑定属性模型
    public function getApp(){
        return $this->hasOne(App::className(), ['id' => 'app_id'])
        ->select(['id', 'name']);
    }
}
