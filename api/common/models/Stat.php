<?php

namespace common\models;

use Yii;
use yii\web\ServerErrorHttpException;
use common\models\Category;
use common\components\ProjectHandler;

/**
 * stat 表数据模型
 * 
 * 
 * 任务的驳回待修正数=refused_count-refused_revise_count
 * 
 * 
 *
 */
class Stat extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'stat';
    }
    
    /**
     * 更新统计量
     * 
     * @param int $projectId
     * @param int $batchId
     * @param int $stepId
     * @param int $counters
     */
    public static function updateCounter($projectId, $batchId, $stepId, $counters, $lockTimes = 0)
    {
        $_logs = ['$projectId' => $projectId, '$batchId' => $batchId, '$stepId' => $stepId, '$counters' => $counters, '$lockTimes' => $lockTimes];
        
        $stat = Stat::find()
        ->where(['project_id' => $projectId, 'batch_id' => $batchId, 'step_id' => $stepId])
        ->asArray()->limit(1)->one();
        if (!$stat)
        {
            //并发锁, 防止N多人同时第一次点击执行任务
            $cacheKey = sprintf('stat.updateCounter.%s.%s.%s.lock', $projectId, $batchId, $stepId);
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
            
                return self::updateCounter($projectId, $batchId, $stepId, $counters, $lockTimes + 1);
            }
            Yii::$app->redis->expire($lockKey, 30);
            
            //-----------------------------------
            
            $task = Task::find()
            ->select(['id'])
            ->where(['project_id' => $projectId, 'batch_id' => $batchId, 'step_id' => $stepId,'status' => Task::STATUS_NORMAL])
            ->asArray()->limit(1)->one();
            if (!$task)
            {
                $_logs = [];
                $_logs['$projectId'] = $projectId;
                $_logs['$batchId'] = $batchId;
                $_logs['$stepId'] = $stepId;
            
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
                throw new ServerErrorHttpException(Yii::t('app', 'task_not_found'));
            }
            
            $stat = new Stat();
            $stat->project_id = $projectId;
            $stat->batch_id = $batchId;
            $stat->step_id = $stepId;
            $stat->task_id = $task['id'];
            $stat->created_at = time();
            $stat->updated_at = time();
            $stat->save();
            
            $stat = $stat->getAttributes();
            
            //删除lockkey
            Yii::$app->redis->del($lockKey);
        }
        
        if ($counters)
        {
            //检测产生负数的情况
            foreach ($counters as $k => $v)
            {
                if ($v < 0 && isset($stat[$k]) && $stat[$k] + $v < 0)
                {
                    $_logs['$stat.$k'] = $k;
                    $_logs['$stat.$v'] = $stat[$k];
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stat value <0 '.json_encode($_logs));

                    //删除此项
                    $counters[$k] = '';
                    unset($counters[$k]);
                }
            }
            
            $counters['updated_at'] = (time() - $stat['updated_at']);
            if ($counters['updated_at'] > time())
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' updated_at > time() '.json_encode($_logs));
                $counters['updated_at'] = 0;
            }
            Stat::updateAllCounters($counters, ['id' => $stat['id']]);
        }
        
        return true;
    }

    public static function getLabelsByTemplate($templateId = 0)
    {
        $labels = [];

        $template = Template::find()->where(['id' => $templateId])->asArray()->limit(1)->one();
        if(isset($template['category_id']))
        {
            $category = Category::find()->where(['id' => $template['category_id']])->asArray()->limit(1)->one();
            if($category['file_type'] == Category::FILE_TYPE_IMAGE)
            {
                $labels = ProjectHandler::fetchLabelTypeFromTemplate($template['config']);
                if(ProjectHandler::templateHasForm($template['config']))
                {
                    array_push($labels, 'form');
                }
            }
            else if($category['file_type'] == Category::FILE_TYPE_3D)
            {
                $labels = ['3d_cloudpoint', '2d_cloudpoint', '2d_object', '3d_object'];
                if(ProjectHandler::templateHasForm($template['config']))
                {
                    array_push($labels, 'form');
                }
            }
            else if($category['file_type'] == Category::FILE_TYPE_VIDEO)
            {
                $labels = ['media_duration', 'effective_duration', 'video'];
                if(ProjectHandler::templateHasForm($template['config']))
                {
                    array_push($labels, 'form');
                }
            }
            else if($category['file_type'] == Category::FILE_TYPE_AUDIO)
            {
                $labels = ['media_duration', 'effective_duration', 'audio'];
                if(ProjectHandler::templateHasForm($template['config']))
                {
                    array_push($labels, 'form');
                }
            }
            else if($category['file_type'] == Category::FILE_TYPE_TEXT)
            {
                $labels = ['file_text_word', 'text_word', 'text'];
                if(ProjectHandler::templateHasForm($template['config']))
                {
                    array_push($labels, 'form');
                }
            }
        }

        return $labels;
    }
    
    //关联项目
    function getProject(){
        return $this->hasOne(Project::className(), ['id' => 'project_id'])->select(['id', 'name', 'amount']);
    }

    //关联批次
    function getBatch(){
        return $this->hasOne(Batch::className(), ['id' => 'batch_id'])->select(['id', 'amount']);
    }
    
    //关联步骤信息
    function getStep(){
        return $this->hasOne(Step::className(), ['id' => 'step_id'])->select(['id', 'name', 'type']);
    }
    
    //关联批次
    function getTask(){
        //return $this->hasOne(Task::className(), ['id' => 'task_id'])->select(['id', 'name']);
        return $this->hasOne(Task::className(), ['batch_id' => 'batch_id', 'step_id' => 'step_id'])->select(['id', 'batch_id','step_id', 'name']);
    }
}