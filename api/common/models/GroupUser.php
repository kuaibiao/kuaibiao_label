<?php

namespace common\models;

use Yii;

/**
 * team 表数据模型
 *
 */
class GroupUser extends \yii\db\ActiveRecord
{
    const STATUS_NORMAL = 1;//正常
    const STATUS_DISABLE = 2;//删除

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'group_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['user_id', 'required'],
            ['group_id', 'required'],
        ];
    }


    public static function getStatus($status)
    {
        $statuses = self::getStatuses();

        return isset($statuses[$status]) ? $statuses[$status] : null;
    }

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_DEFAULT => Yii::t('app', 'team_status_default'),
            self::STATUS_NORMAL => Yii::t('app', 'team_status_normal'),
            self::STATUS_DISABLE => Yii::t('app', 'team_status_disabled'),
        ];
    }

    public static function getOpenPayments(){
        return [
            self::OPEN_PAYMENT_NO   => Yii::t('app', 'team_open_payment_no'),
            self::OPEN_PAYMENT_YES  => Yii::t('app', 'team_open_payment_yes')
        ];
    }

    public static function getOpenPayment($openPayment){
        $openPayments   = self::getOpenPayments();
        if(isset($openPayments[$openPayment]))
            return $openPayments[$openPayment];

        return null;
    }

    public static function getGroup(){
        return Group::find()->select('id,name')->where([ 'status' => Group::STATUS_NORMAL])->asArray()->all();

    }
    

    //关联用户列表
    public function getUsers(){
        return $this->hasMany(User::className(), ['group_id' => 'id'])
            ->select('id,username,nickname,group_id');
    }

    public function getTagUsers(){
        return $this->hasMany(GroupUser::className(), ['user_id' => 'user_id'])
            ->via('teamUsers');
    }

    public function getTags(){
        return $this->hasMany(Group::className(), ['id' => 'tag_id'])
            ->via('tagUsers')
            ->select(['id', 'name', 'count']);
    }

    //关联创建者
    public function getCreater(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])
            ->select(User::publicFields());
    }
}