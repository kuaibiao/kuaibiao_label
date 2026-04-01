<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;

/**
 * 消息(私信)表数据模型
 *
 */
class MessageToUser extends ModelComponent
{
    const ISREAD_NO = 0;
    const ISREAD_YES = 1;
    
    const STATUS_ENABLE = 0;
    const STATUS_DISABLED = 1;
    const STATUS_DELETED = 2;
    
    const TYPE_SERVER = 0;//服务类
    const TYPE_USER = 1;//用户类
    const TYPE_PROJECT = 2;//项目类
    const TYPE_TASK = 3;//任务类
    const TYPE_ACTIVITY = 4;//活动类
    
    /**
     * 数据库表名
     *
     * @var string
     */
    public static $tableName = 'message_to_user';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return self::$tableName;
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['message_id', 'user_id', 'type', 'is_read', 'status'], 'integer'],
        ];
    }
    
    public static function getType($var)
    {
        $vars = self::getTypes();
        return isset($vars[$var]) ? $vars[$var] : null;
    }
    
    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_SERVER => yii::t('app', 'messagetouser_type_server'),
            self::TYPE_USER => yii::t('app', 'messagetouser_type_user'),
            self::TYPE_PROJECT => yii::t('app', 'messagetouser_type_project'),
            self::TYPE_TASK => yii::t('app', 'messagetouser_type_task'),
            self::TYPE_ACTIVITY => yii::t('app', 'messagetouser_type_activity'),
        ];
    }

    //关联消息模型
    function getMessage(){
        return $this->hasOne(Message::className(), ['id' => 'message_id'])->select(['id', 'read_count', 'content', 'link_word', 'link_type', 'link_attribute']);
    }
    
    function getUser(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])->select(['id', 'nickname', 'email', 'avatar']);
    }
    
    
}
