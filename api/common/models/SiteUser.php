<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;
use yii\behaviors\TimestampBehavior;

/**
 * site_user 表数据模型
 *
 */
class SiteUser extends ModelComponent
{
    const STATUS_ENABLE = 0;
    const STATUS_DISABLE = 1;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'site_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['site_id', 'integer'],
            ['site_id', 'default', 'value' => 0],

            ['user_id', 'integer'],
            ['user_id', 'default', 'value' => 0],

            ['created_by', 'integer'],
            ['created_by', 'default', 'value' => 0],

            [['created_at', 'updated_at'], 'integer'],
        ];
    }
    
    public function behaviors()
    {
        return [
            'timeStamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => time()
            ]
        ];
    }

    //关联用户信息
    function getUser(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])
            ->select(User::privateFields())->with(['roles', 'tags']);
    }

    //关联创建者信息
    function getCreater(){
        return $this->hasOne(User::className(), ['id' => 'created_by'])
            ->select(User::publicFields());
    }

    //关联团队
    function getSite(){
        return $this->hasOne(Site::className(), ['id' => 'site_id']);
    }
    
    /**
     * 批量插入
     * @param   $fieldList [<description>]
     * @param   $dataList [<description>]
     * @return  boolean
     */
    public static function batchInsert($fieldList, $dataList)
    {
        return Yii::$app->db->createCommand()
        ->batchInsert(self::tableName(), $fieldList, $dataList)
        ->execute() ? true : false;
    }
}