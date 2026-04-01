<?php

namespace common\models;

use Yii;

/**
 * team 表数据模型
 *
 */
class Group extends \yii\db\ActiveRecord
{
    const STATUS_NORMAL = 1;//正常
    const STATUS_DISABLE = 2;//删除
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 30],
            ['name', 'validateName'],
        ];
    }


    public function validateName($attribute, $params)
    {
        $_logs = ['name' => $this->name];

        if (!$this->hasErrors())
        {
            $group_ = Group::find()->select(['id', 'name'])->where(['name' => $this->name])->orderBy(['id' => SORT_ASC])->limit(1)->asArray()->one();
            if ($group_)
            {
                if ($group_['id'] != $this->id)
                {
                    $this->addError($attribute, Yii::t('app', 'team_name_existed'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team_name_existed '.json_encode($_logs));
                    return false;
                }
            }
            return true;
        }

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
            self::STATUS_NORMAL => Yii::t('app', 'group_status_normal'),
            self::STATUS_DISABLE => Yii::t('app', 'group_status_disabled'),
        ];
    }

    public static function getGroup()
    {
        return Group::find()->select('id,name')->where([ 'status' => Group::STATUS_NORMAL])->asArray()->all();
    }

    public static function getUserGroup($userId)
    {
        $groupUser = GroupUser::find()->where(['user_id' => $userId])->asArray()->limit(1)->one();
        if (!$groupUser)
        {
            return [];
        }
        else
        {
            $groupUser = Group::find()->select('id,name')->where(['id' => $groupUser['group_id']])->asArray()->limit(1)->one();
            return $groupUser;
        }
    }

    //关联创建者
    public function getCreater()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id'])
            ->select(User::publicFields());
    }

}