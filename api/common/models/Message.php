<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;
use common\helpers\JsonHelper;
/**
 * message表数据模型
 *
 */
class Message extends ModelComponent
{
    const STATUS_ENABLE = 0;
    const STATUS_DISABLED = 1;
    const STATUS_DELETED = 2;
    
    /**
     * 数据库表名
     *
     * @var string
     */
    public static $tableName = 'message';
    
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
            [['user_id','status','read_count','created_at', 'updated_at'], 'integer'],
            [['content'], 'string', 'min' => 1, 'max' => 65535],
        ];
    }
    
    /**
     * 获取所有的表
     */
    public static function getAllSuffixes()
    {
//         $arr = Message::find()
//         ->select(['table_suffix'])
//         ->groupBy(['table_suffix'])
//         ->orderBy(['table_suffix' => SORT_DESC])
//         ->asArray()->column();
        
//         return $arr;
        
        $tableName = self::getOriginalTable();
        $sql = sprintf("SHOW TABLES LIKE '%s';", $tableName.'%');
        $_logs['$sql'] = $sql;
        
        $tablenames = static::getDb()->createCommand($sql)->queryColumn();
        $_logs['$tablenames'] = $tablenames;
        
        $suffixes = [];
        if ($tablenames && is_array($tablenames))
        {
            foreach ($tablenames as $tablename)
            {
                if (preg_match('/'.$tableName.'_(\d+)/', $tablename, $matches))
                {
                    if ($matches && $matches[1])
                    {
                        $suffixes[] = $matches[1];
                    }
                }
            }
        }
        $_logs['$suffixes'] = $suffixes;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        
        return $suffixes;
    }
    
    
    //关联发送人信息
    function getSender(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])->select(['id', 'email', 'avatar', 'nickname']);
    }
    
    /**
     * 发送消息
     * 
     * @param int $user_id
     * @param string $message
     */
    public static function send($fromUserId, $toUserIds, $type, $contents)
    {
        $_logs = [];
        $_logs['$fromUserId'] = $fromUserId;
        $_logs['$toUserIds'] = $toUserIds;
        $_logs['$contents'] = $contents;
        
        //$fromUser = User::find()->select(User::publicFields())->where(['id' => $fromUserId])->asArray()->limit(1)->one();
        //if (!$fromUser)
        //{
            //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $fromUser notexist '.json_encode($_logs));
            //return false;
        //}
        
        $suffix = Message::generateSuffix();
        Message::setTable($suffix);
        $message = new Message();
        $message->user_id = $fromUserId;
        $message->status = Message::STATUS_ENABLE;
        $message->read_count = 0;
        $message->content = JsonHelper::json_encode_cn($contents);
        $message->table_suffix = $suffix;
        $message->created_at = time();
        if (!$message->validate())
        {
            $_logs['$message$Errors'] = $message->getErrors();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' save fail '.json_encode($_logs));
            return false;
        }
        $message->save();
        
        if (!is_array($toUserIds))
        {
            $toUserIds = [$toUserIds];
        }
        
        //*代表所有用户, 但存储的时候要转化为0, 即在数据库0表所有用户
        if (in_array('*', $toUserIds))
        {
            $toUserIds = [0];
        }
        
        MessageToUser::setTable($suffix);
        foreach ($toUserIds as $uid)
        {
            $messageToUser = new MessageToUser();
            $messageToUser->message_id = $message->id;
            $messageToUser->user_id = $uid;
            $messageToUser->type = $type;
            $messageToUser->is_read = MessageToUser::ISREAD_NO;
            $messageToUser->status = MessageToUser::STATUS_ENABLE;
			$messageToUser->created_at = time();
            $messageToUser->save();
            
            if ($uid)
            {
                UserStat::incMessageCount($uid, 1);
            }
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    /**
     * 撤销已发送的通知
     * @param int $message_id
     */
    public static function revoke($messageId)
    {
        $message = self::find()->where(['id' => $messageId])->andWhere(['status' => Message::STATUS_ENABLE])->limit(1)->one();
        if(!$message){
            return true;
        }
    
        $message->status = Message::STATUS_DISABLED;
        $message->updated_at = time();
        $message->save();
    
        // MessageToUser::setTable($message->table_suffix);
        MessageToUser::updateAll([
            'status' => MessageToUser::STATUS_DISABLED,
        ], [
            'message_id' => $messageId
        ]);
    
        $messageToUserIds = MessageToUser::find()->select(['user_id'])->where(['message_id' => $messageId, 'is_read' => MessageToUser::ISREAD_NO])->asArray()->column();
        if($messageToUserIds)
        {
            foreach($messageToUserIds as $uid)
            {
                if ($uid)
                {
                    UserStat::decrMessageCount($uid);
                }
            }
        }
    
    }
    
    /**
     * 用户对某通知更改为已读状态
     * 注意: 全站通知没有用户读取状态
     * 
     * @param int $message_id
     */
    public static function read($suffix, $messageId, $userId)
    {
        Message::setTable($suffix);
        $message = Message::findOne($messageId);
        if(!$message)
        {
            return null;
        }

        MessageToUser::setTable($message->table_suffix);
        $attributes = [
            'is_read' => MessageToUser::ISREAD_YES
        ];
        MessageToUser::updateAll($attributes, [
            'user_id'   => $userId,
            'message_id' => $messageId
        ]);
        
        $attributes = [
            'read_count' => 1
        ];
        Message::updateAllCounters($attributes, ['id' => $messageId]);
		UserStat::decrMessageCount($userId);
        return true;
    }
    
    /**
     * 用户删除某通知
     * @param unknown $user_id
     * @param unknown $message_id
     */
    public static function del($suffix, $messageId, $userId)
    {
        Message::setTable($suffix);
        $message = Message::findOne($messageId);
        if(!$message)
        {
            return null;
        }

        MessageToUser::setTable($message->table_suffix);
        $attributes = [
            'status' => MessageToUser::STATUS_DELETED
        ];
        MessageToUser::updateAll($attributes, ['message_id' => $messageId, 'user_id' => $userId]);

        return true;
    }
    
    //分配作业人员
    public static function sendTaskAssignedUser($toUserIds, $projectId, $taskId)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
            'action' => 'task_execute',
            'content' => 'message_task_assigned_user', 
            'params' => [
                'project_id' => $projectId,
                'task_id' => $taskId
            ]]);
    }
    
    public static function sendTaskTimeout($toUserIds, $projectId, $taskId, $dataId)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
            'action' => 'mytask_detail',
            'content' => 'message_task_timeout',
            'params' => [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'data_id' => $dataId,
                'type' => '4'
            ]]);
    }
    
    public static function sendTaskRefuse($toUserIds, $projectId, $taskId, $dataId, $reason = '')
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
            'action' => 'mytask_detail',
            'content' => 'message_task_refuse',
            'params' => [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'data_id' => $dataId,
                'reason' => $reason,
                'type' => '6'
            ]]);
    }
    
    public static function sendTaskAllow($toUserIds, $projectId, $taskId, $dataId)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
            'action' => 'mytask_detail',
            'content' => 'message_task_allow',
            'params' => [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'data_id' => $dataId,
                'type' => '3'
            ]]);
    }
    
    public static function sendTaskReset($toUserIds, $projectId, $taskId, $dataId, $reason = '')
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
            'action' => 'mytask_detail',
            'content' => 'message_task_reset',
            'params' => [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'data_id' => $dataId,
                'reason' => $reason,
                'type' => '4'
            ]]);
    }
    
    public static function sendTaskForceRefuse($toUserIds, $projectId, $taskId, $dataId)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
            'action' => 'mytask_detail',
            'content' => 'message_task_force_refuse',
            'params' => [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'data_id' => $dataId,
                'type' => '6'
            ]]);
    }
    
    public static function sendTaskForceReset($toUserIds, $projectId, $taskId, $dataId)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
            'action' => 'mytask_detail',
            'content' => 'message_task_force_reset',
            'params' => [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'data_id' => $dataId,
                'type' => '4'
            ]]);
    }
    
//     public static function sendTaskAuditConfirm($toUserIds, $projectId, $taskId, $dataId)
//     {
//         Message::send(0, $toUserIds, MessageToUser::TYPE_TASK, [
//             'action' => 'project_resource_fail',
//             'content' => Yii::t('app', 'message_task_audit_confirm'),
//             'params' => [
//                 'project_id' => $projectId,
//                 'task_id' => $taskId,
//                 'data_id' => $dataId,
//                 'type' => '14'
//             ]]);
//     }
    
    //更新用户邮箱
    public static function sendUserUpdateInfo($toUserIds)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_USER, [
            'action' => 'user_update_email',
            'content' => 'message_user_update_info',
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    //更新用户密码
    public static function sendUserUpdatePassword($toUserIds)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_USER, [
            'action' => 'user_update_password',
            'content' => 'message_user_update_password',
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    //更新用户邮箱
    public static function sendUserUpdateEmail($toUserIds)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_USER, [
            'action' => 'user_update_email',
            'content' => 'message_user_update_email',
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    //更新用户手机
    public static function sendUserUpdatePhone($toUserIds)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_USER, [
            'action' => 'user_update_phone',
            'content' => 'message_user_update_phone',
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    //用户注册成功
    public static function sendUserSignupSucc($toUserIds)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_USER, [
            'action' => 'user_signup_succ',
            'content' => 'message_user_signup_succ',
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    //解包失败
    public static function sendProjectUnpackFail($toUserIds, $projectId, $reason)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_PROJECT, [
            'action' => 'project_unpack_fail',
            'content' => 'message_project_unpack_fail',
            'trans_params' => ['project_id' => $projectId, 'reason' => $reason],
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    public static function sendProjectUnpackSucc($toUserIds, $projectId)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_PROJECT, [
            'action' => 'project_unpack_succ',
            'content' => 'message_project_unpack_succ',
            'trans_params' => ['project_id' => $projectId],
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    public static function sendProjectPackFail($toUserIds, $projectId, $reason)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_PROJECT, [
            'action' => 'project_pack_fail',
            'content' => 'message_project_pack_fail',
            'trans_params' => [$projectId, $reason],
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    public static function sendProjectPackSucc($toUserIds, $projectId)
    {
        Message::send(0, $toUserIds, MessageToUser::TYPE_PROJECT, [
            'action' => 'project_pack_succ',
            'content' => 'message_project_pack_succ',
            'trans_params' => [$projectId],
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }
    
    public static function sendProjectResourceFail($reason)
    {
        //获取所有admin用户, 并发通知
        $toUserIds = Yii::$app->authManager->getUserIdsByRole(AuthItem::ROLE_MANAGER);
        
        Message::send(0, $toUserIds, MessageToUser::TYPE_PROJECT, [
            'action' => 'project_resource_fail',
            'content' => $reason,
            'params' => [
                'user_id' => $toUserIds
            ]]);
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_ENABLE => yii::t('app', 'message_status_enable'),
            self::STATUS_DELETED => yii::t('app', 'message_status_deleted'),
            self::STATUS_DISABLED => yii::t('app', 'message_status_disabled')
        ];
    }
}
