<?php

namespace common\models;

use Yii;

/**
 * TaskUser 数据表模型
 *
 */
class TaskUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'task_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id','task_id','user_id','priority','created_at', 'updated_at'], 'integer'],
        ];
    }
    
    public static function batchDelete($projectId)
    {
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchDelete='.json_encode([$projectId]));
    
        $attr = array();
        $attr['project_id'] = $projectId;
        
        Yii::$app->db->createCommand()->delete(self::tableName(), $attr)->execute();
    }

    //绑定用户模型
    public function getUser(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])
            ->select(['id','email', 'nickname'])->with(['roles']);
    }

    //绑定用户统计模型
    public function getStatUser(){
        return $this->hasOne(StatUser::className(), ['user_id' => 'user_id', 'task_id' => 'task_id']);
    }
}
