<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;

/**
 * 第三方用户表模型
 *
 */
class ThirdpartyUser extends ModelComponent
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'thirdparty_user';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

            [['thirdparty_id'], 'required'],
            [['thirdparty_id', 'user_id','site_id', 'created_at', 'updated_at', 'deleted_at','last_update_time'], 'integer'],
            [['thirdparty_id', 'user_id','site_id', 'created_at', 'updated_at', 'deleted_at','last_update_time'], 'default', 'value' => 0],

            [['thirdparty_openid', 'thirdparty_unionid'],'string'],
            [['thirdparty_openid', 'thirdparty_unionid'], 'default', 'value' => ''],
        ];
    }
    
    public function getThirdparty()
    {
        return $this->hasOne(Thirdparty::className(), ['id' => 'thirdparty_id']);
    }
}
