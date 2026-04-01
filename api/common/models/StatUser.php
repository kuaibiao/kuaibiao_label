<?php

namespace common\models;

use Yii;
use yii\web\ServerErrorHttpException;

/**
 * stat_user 表数据模型
 * 
 * 用户任务的驳回待修正数=refused_count-refused_revise_count
 *
 */
class StatUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'stat_user';
    }
    
    /**
     * 更新统计量
     *
     * @param int $userId
     * @param int $projectId
     * @param int $batchId
     * @param int $stepId
     * @param int $counters
     */
    public static function updateCounter($projectId, $batchId, $stepId, $userId, $counters, $lockTimes = 0)
    {
        $_logs = ['$projectId' => $projectId, '$batchId' => $batchId, '$stepId' => $stepId, '$userId' => $userId, '$counters' => $counters, '$lockTimes' => $lockTimes];
        
        if (empty($userId) || !is_numeric($userId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $userId empty '.json_encode($_logs));
            return false;
        }
        
        $statUser = StatUser::find()
        ->where(['user_id' => $userId, 'project_id' => $projectId, 'batch_id' => $batchId, 'step_id' => $stepId])
        ->asArray()->limit(1)->one();
        if (!$statUser)
        {
            //并发锁, 防止N多人同时第一次点击执行任务
            $cacheKey = sprintf('statUser.updateCounter.%s.%s.%s.%s.lock', $projectId, $batchId, $stepId, $userId);
            $lockKey = Yii::$app->redis->buildKey($cacheKey);
            $_logs['$lockKey'] = $lockKey;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $lockKey '.json_encode($_logs));
            
            //成功返回 1,失败返回0
            if (!Yii::$app->redis->setnx($lockKey, 1))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locked '.json_encode($_logs));
                if ($lockTimes > 5)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locked, locktimes > 5 '.json_encode($_logs));
                    return false;
                }
                sleep(1);
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sleep 1 '.json_encode($_logs));
            
                return self::updateCounter($projectId, $batchId, $stepId, $userId, $counters, $lockTimes + 1);
            }
            Yii::$app->redis->expire($lockKey, 30);
            
            
            $task = Task::find()
            ->select(['id'])
            ->where(['project_id' => $projectId, 'batch_id' => $batchId, 'step_id' => $stepId,'status' => Task::STATUS_NORMAL])
            ->asArray()->limit(1)->one();
            if (!$task)
            {
                $_logs = [];
                $_logs['$projectId'] = $projectId;
                $_logs['$userId'] = $userId;
                $_logs['$batchId'] = $batchId;
                $_logs['$stepId'] = $stepId;
                
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
                throw new ServerErrorHttpException(Yii::t('app', 'task_not_found'));
            }
            
            $statUser = new StatUser();
            $statUser->project_id = $projectId;
            $statUser->batch_id = $batchId;
            $statUser->step_id = $stepId;
            $statUser->task_id = $task['id'];
            $statUser->user_id = $userId;
            $statUser->created_at = time();
            $statUser->updated_at = time();
            $statUser->save();
            
            $statUser = $statUser->getAttributes();
            
            //删除lockkey
            Yii::$app->redis->del($lockKey);
        }
        
        //检测产生负数的情况
        foreach ($counters as $k => $v)
        {
            if ($v < 0 && isset($statUser[$k]) && $statUser[$k] + $v < 0)
            {
                $_logs['$counters.$k'] = $k;
                $_logs['$counters.$v'] = $v;
                $_logs['$statUser.$v'] = $statUser[$k];
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $statUser value <0 '.json_encode($_logs));
                
                //删除此项
                $counters[$k] = '';
                unset($counters[$k]);
            }
        }
        
        $counters['updated_at'] = (time() - $statUser['updated_at']);
        if ($counters['updated_at'] > time())
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' updated_at > time() '.json_encode($_logs));
            $counters['updated_at'] = 0;
        }
        StatUser::updateAllCounters($counters, ['id' => $statUser['id']]);
        
        return true;
    }
    
    //关联项目
    function getProject(){
        return $this->hasOne(Project::className(), ['id' => 'project_id'])->select(['id', 'name', 'amount', 'category_id'])->with(['category']);
    }

    //关联批次
    function getBatch(){
        return $this->hasOne(Batch::className(), ['id' => 'batch_id'])->select(['id', 'amount']);
    }
    
    //关联步骤信息
    function getStep(){
        return $this->hasOne(Step::className(), ['id' => 'step_id'])->select(['id', 'name', 'type']);
    }
    
    //关联任务
    function getTask(){
        return $this->hasOne(Task::className(), ['id' => 'task_id'])->select(['id', 'name', 'status']);
    }
    
    //关联用户
    function getUser(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])->select(['id', 'nickname', 'email'])->with(['roles','group']);
    }
}
