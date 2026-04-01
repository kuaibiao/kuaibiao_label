<?php
/**
 * 任务助手
 * 
 * 
 */

namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use common\models\Project;
use common\models\Batch;
use common\models\Data;
use common\models\DataResult;
use common\models\Work;
use common\models\WorkResult;
use common\models\WorkRecord;
use common\models\Step;
use common\models\StepGroup;
use common\models\Category;
use common\models\Stat;
use common\models\StatUser;
use common\models\Task;
use common\models\TaskUser;
use common\models\Template;
use common\models\User;
use common\models\Message;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use common\helpers\ArrayHelper;
use common\models\StatResultWork;
use common\models\StatResultUser;
use common\models\StatResultData;
use common\models\StatResult;
use common\components\aiHandler\AiHandler;

class TaskHandler
{
    var $projectId = null;
    var $batchId = null;
    var $stepId = null;
    var $userId = null;
    
    var $project = null;
    var $batch = null;
    var $step = null;
    var $stepGroup = [];
    var $task = null;
    var $stat = null;
    var $category = null;
    var $stepParentIds = [];
    var $stepChildIds = [];
    var $stepBrotherIds = [];
    
    //不可删除
    public function __construct()
    {
    }
    
    /**
     * 初始化数据
     * @param int $projectId
     * @param int $userId
     */
    public function init($projectId = 0, $batchId = 0, $stepId = 0, $userId = 0)
    {
        $_logs = [];
        
        //--------------------------------
        //判断mac address
        $systeminfo = new SystemInfo();
        if (!empty(Yii::$app->params['macAddress']) && !in_array(strtolower(Yii::$app->params['macAddress']), $systeminfo->getMacAddress()))
        {
            $_logs['macaddr.param'] = Yii::$app->params['macAddress'];
            $_logs['macaddrs'] = $systeminfo->getMacAddress();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_error mac address error '.json_encode($_logs));
            $this->denyAccess();
            return false;
        }
        //判断mac address
        if (!empty(Setting::getSetting('macAddress')) && !in_array(strtolower(Setting::getSetting('macAddress')), $systeminfo->getMacAddress()))
        {
            $_logs['macaddr.setting'] = Setting::getSetting('macAddress');
            $_logs['macaddrs'] = $systeminfo->getMacAddress();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_error mac address error '.json_encode($_logs));
            $this->denyAccess();
            return false;
        }
        //--------------------------------
        
        $this->projectId = $projectId;
        $this->batchId = $batchId;
        $this->stepId = $stepId;
        $this->userId = $userId;

        $_logs['$projectId'] = $projectId;
        $_logs['$batchId'] = $batchId;
        $_logs['$stepId'] = $stepId;
        $_logs['$userId'] = $userId;

        $this->getProject();
        $this->getBatch();
        $this->getStep();
        $this->getTask();
        $this->getStat();
        $this->getCategory();//需要在getTask之后
        $this->getStepParentIds();
        $this->getStepChildIds();
        $this->getStepBrotherIds();//需要在getStepChildIds之后

        $_logs['project'] = $this->project;
        $_logs['batch'] = $this->batch;
        $_logs['step'] = $this->step;
        $_logs['task'] = $this->task;
        $_logs['stat'] = $this->stat;

        if (!$this->userId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_error userId not exist'.json_encode($_logs));
            return false;
        }
        
        if (!$this->project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_error project not exist '.json_encode($_logs));
            return false;
        }
        
        if (!$this->batch)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_error batch not exist '.json_encode($_logs));
            return false;
        }
        
        if (!$this->step)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_error step not exist '.json_encode($_logs));
            return false;
        }
        
        if (!$this->task)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_error task not exist '.json_encode($_logs));
            return false;
        }
        
        if (!$this->stat)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat_init_error task not exist '.json_encode($_logs));
            return false;
        }

        //设置分表
        Data::setTable($this->project['table_suffix']);
        DataResult::setTable($this->project['table_suffix']);
        Work::setTable($this->project['table_suffix']);
        WorkResult::setTable($this->project['table_suffix']);
        WorkRecord::setTable($this->project['table_suffix']);
        StatResult::setTable($this->project['table_suffix']);
        StatResultData::setTable($this->project['table_suffix']);
        StatResultUser::setTable($this->project['table_suffix']);
        StatResultWork::setTable($this->project['table_suffix']);

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' init '.json_encode($_logs));

        return true;
    }
    
    //获取项目
    public function getProject()
    {
        if (!$this->project)
        {
            $this->project = Project::find()
            ->where(['id' => $this->projectId])
            ->andWhere(['in', 'status', [Project::STATUS_WORKING, Project::STATUS_PAUSED, Project::STATUS_STOPPED, Project::STATUS_FINISH]])
            ->asArray()->limit(1)->one();
        }
    
        return $this->project;
    }
    
    //获取批次
    public function getBatch()
    {
        if (!$this->batch)
        {
            $this->batch = Batch::find()
            ->where(['project_id' => $this->projectId, 'id' => $this->batchId, 'status' => Batch::STATUS_ENABLE])
            ->asArray()->limit(1)->one();
        }
        
        return $this->batch;
    }
    
    //获取分步
    public function getStep()
    {
        if (!$this->step)
        {
            $this->step = Step::find()
            ->where(['project_id' => $this->projectId, 'id' => $this->stepId])
            ->asArray()->limit(1)->one();
        }
    
        return $this->step;
    }
    
    public function getStepGroup()
    {
        if (!$this->stepGroup)
        {
            $this->stepGroup = StepGroup::find()
            ->where(['project_id' => $this->projectId, 'id' => $this->step['step_group_id']])
            ->asArray()->limit(1)->one();
        }
    
        return $this->stepGroup;
    }
    
    public function getStepParentIds()
    {
        if (!$this->stepParentIds)
        {
            $this->stepParentIds = Step::getParentStepIds($this->stepId);
        }
    
        return $this->stepParentIds;
    }
    
    public function getStepChildIds()
    {
        if (!$this->stepChildIds)
        {
            $this->stepChildIds = Step::getChildStepIds($this->stepId);
        }
    
        return $this->stepChildIds;
    }
    
    public function getStepBrotherIds()
    {
        if (!$this->stepBrotherIds)
        {
            $this->stepBrotherIds = Step::getBrotherStepIds($this->stepId);
        }
    
        return $this->stepBrotherIds;
    }
    
    //获取分类
    public function getCategory()
    {
        if (!$this->category)
        {
            $categoryId = $this->project['category_id'];
            if (!empty($this->step['category_id']))
            {
                $categoryId = $this->step['category_id'];
            }
            $this->category = Category::find()->where(['id' => $categoryId])->asArray()->limit(1)->one();
        }
    
        return $this->category;
    }
    
    public function getTask()
    {
        if (!$this->task)
        {
            $this->task = Task::find()
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId, 'status' => Task::STATUS_NORMAL])
            ->asArray()->limit(1)->one();
        }
        
        return $this->task;
    }
    
    public function getStat()
    {
        if (!$this->stat)
        {
            $this->stat = Stat::find()
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
            ->asArray()->limit(1)->one();
            
            if (empty($this->stat)) {
                
                $stat = new Stat();
                $stat->project_id = $this->projectId;
                $stat->batch_id = $this->batchId;
                $stat->step_id = $this->stepId;
                $stat->task_id = $this->task['id'];
                $stat->created_at = time();
                $stat->save();
                
                $this->stat = $stat->getAttributes();
                
            }
        }
        
        return $this->stat;
    }
    
    //-----------------------------------------------------
    
    /**
     * 获取任务池的key
     *
     * @param string $other
     * @return string
     */
    public function getDataKey($other = 0)
    {
        $cacheKey = sprintf('projectdata:%s:%s:%s', $this->projectId, $this->batchId, $this->stepId);
        if ($other)
        {
            $cacheKey .= ':'.$other;
        }
        //$_logs['$cacheKey'] = $cacheKey;
    
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKey '.json_encode($_logs));
        return Yii::$app->redis->buildKey($cacheKey);
    }
    
    /**
     * 初始化数据
     *
     * @param int $lockTimes
     * @return boolean
     */
    public function initData($lockTimes = 0)
    {
        $_logs = [];
        $_logs['$lockTimes'] = $lockTimes;
        
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        //获取任务池的key
        $cacheKey = $this->getDataKey();
        $_logs['$cacheKey'] = $cacheKey;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKey '.json_encode($_logs));
        
        //key不存在的情况, 要生成数据并保存到key
        if (!Yii::$app->redis->exists($cacheKey))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data cache not exists '.json_encode($_logs));
            //并发锁, 防止N多人同时第一次点击执行任务
            $lockKey = $cacheKey.'lock';
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
                return $this->initData($lockTimes + 1);
            }
            Yii::$app->redis->expire($lockKey, 30);
            
            //--------------------------------------
            
            $_logs['stepParentIds'] = $this->stepParentIds;
            
            //获取所有可执行的作业集
            $isLoad = false;
            $allowIds = [];
            if ($this->stepParentIds)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' has parent  '.json_encode($_logs));
                
                foreach ($this->stepParentIds as $stepId_)
                {
                    $_logs['$stepId_'] = $stepId_;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' load start '.json_encode($_logs));
                
                    //查询初始数据
                    $parentDoneIds = Work::find()
                    ->select(['data_id'])
                    ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_])
                    ->andWhere(['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH]])
                    ->andWhere(['<', 'updated_at', time() - 1])
                    //->orderBy(['id' => SORT_ASC])
                    ->asArray()->column();
                    $_logs['$parentDoneIds.count'] = count($parentDoneIds);
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds '.json_encode($_logs));
            
                    //是否有条件
                    if (!empty($this->step['condition']) && StringHelper::is_json($this->step['condition']))
                    {
                        $_logs['condition'] = $this->step['condition'];
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' has condition '.json_encode($_logs));
            
                        $stepConditionArr = JsonHelper::json_decode_all($this->step['condition']);
                        $_logs['$stepConditionArr'] = $stepConditionArr;
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
            
                        $parentWorkIds = Work::find()
                        ->select(['id'])
                        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_])
                        ->andWhere(['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH]])
                        ->andWhere(['<', 'updated_at', time() - 1])
                        //->orderBy(['id' => SORT_ASC])
                        ->asArray()->column();
                        $_logs['$parentWorkIds.count'] = count($parentWorkIds);
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $parentWorkIds '.json_encode($_logs));
            
                        $parentDoneConditionIds = [];
                        foreach ($parentWorkIds as $workId_)
                        {
                            if (!Step::checkCondition($this->stepId, $workId_))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition not allow '.json_encode($_logs));
                                continue;
                            }
            
                            $parentDoneConditionIds[] = $workId_;
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition count ok '.json_encode($_logs));
                        }
            
                        //通过条件的列表
                        if ($parentDoneConditionIds)
                        {
                            $parentDoneDataIds = Work::find()->select(['data_id'])->where(['in', 'id', $parentDoneConditionIds])->asArray()->column();
                            $parentDoneIds = $parentDoneDataIds;
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition set value $parentDoneDataIds '.json_encode($_logs));
                        }
                        else
                        {
                            $parentDoneIds = [];
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition set value empty '.json_encode($_logs));
                        }
                    }
            
                    //--------------------------------------
            
                    if (!$isLoad)
                    {
                        $isLoad = true;
                        $allowIds = $parentDoneIds;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds $isLoad '.json_encode($_logs));
                    }
                    else
                    {
                        $allowIds = array_intersect($allowIds, $parentDoneIds);
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds array_intersect  '.json_encode($_logs));
                    }
                }
            }
            else
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' hasnot parent  '.json_encode($_logs));
                
                //查询所有的可执行数据
                $allIds = Data::find()
                ->select(['id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId])
                ->orderBy(['sort' => SORT_ASC])->asArray()->column();
                $_logs['$allowIds.count'] = count($allowIds);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $allowIds '.json_encode($_logs));
                
                if (!$isLoad)
                {
                    $isLoad = true;
                    $allowIds = $allIds;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds $isLoad '.json_encode($_logs));
                }
                else
                {
                    $allowIds = array_intersect($allowIds, $allIds);
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds array_intersect  '.json_encode($_logs));
                }
            }
            
            $_logs['$allowIds.count'] = count($allowIds);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $allowIds '.json_encode($_logs));
            
            //查询初始数据
            $doneIds = Work::find()
            ->select(['data_id'])
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->asArray()->column();
            $_logs['$doneIds.count'] = count($doneIds);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds '.json_encode($_logs));
            
            $newIds = array_diff($allowIds, $doneIds);
            $totalCount = count($newIds);
            $_logs['$totalCount'] = $totalCount;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $newIds  '.json_encode($_logs));
            
            //有剩余项, 存储id
            if ($totalCount > 0)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' has $newIds '.json_encode($_logs));
                
                //批量增加
                Work::batchInsert($this->projectId, $this->batchId, $this->stepId, $newIds);
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchInsert '.json_encode($_logs));
                
                $attributes = [
                    'amount' => $totalCount,
                ];
                Task::updateAllCounters($attributes, ['id' => $this->task['id']]);
                
                //更新此分步的总数
                $stat = Stat::find()
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
                ->asArray()->limit(1)->one();
                if (!$stat)
                {
                    $stat = new Stat();
                    $stat->project_id = $this->projectId;
                    $stat->batch_id = $this->batchId;
                    $stat->step_id = $this->stepId;
                    $stat->task_id = $this->task['id'];
                    $stat->created_at = time();
                    $stat->save();
                    
                    $stat = $stat->getAttributes();
                }
                $attributes = [
                    'amount' => $totalCount,
                ];
                Stat::updateAllCounters($attributes, ['id' => $stat['id']]);
            }
            //没有剩余项, 置一个标志位, string类型
            else
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $newIds '.json_encode($_logs));
            }
            
            //查询初始数据
            $notStartIds = Work::find()
            ->select(['data_id'])
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
            ->andWhere(['in', 'status', [Work::STATUS_NEW]]) //, Work::STATUS_EXECUTING
            ->asArray()->column();
            $_logs['$notStartIds.count'] = count($notStartIds);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $notStartIds '.json_encode($_logs));
            
            //计算并集,因以下使用集合, 故不需要排除重复
            $newIds = array_merge($newIds, $notStartIds);
            $_logs['$newIds.count'] = count($newIds);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $newIds '.json_encode($_logs));
            
            //--------------------------------
            // 缓存数据
            //--------------------------------
            
            //有剩余项, 存储id
            if ($newIds)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' import cache  '.json_encode($_logs));
                
                //Yii::$app->redis->MULTI();
                foreach ($newIds as $id)
                {
                    //有优化空间, 可一次性push多个
                    Yii::$app->redis->sadd($cacheKey, $id);
                }
                //Yii::$app->redis->EXEC();
                
                Yii::$app->redis->expire($cacheKey, 3600);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' import cache succ '.json_encode($_logs));
            }
            //没有剩余项, 置一个标志位, string类型
            else
            {
                //Yii::$app->redis->set($cacheKey, 1);
                
                //设定缓存失效时间
                //Yii::$app->redis->expire($cacheKey, 1);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no data remain, change cacheType to string '.json_encode($_logs));
            }
            
            //删除lockkey
            Yii::$app->redis->del($lockKey);
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data cache exist '.json_encode($_logs));
        }
    
        return true;
    }
    
    public function initDataForUser($userId = 0, $lockTimes = 0)
    {
        $_logs = ['$userId' => $userId, '$lockTimes' => $lockTimes];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        //获取任务池的key
        $cacheKey = $this->getDataKey($userId);
        $_logs['$cacheKey'] = $cacheKey;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKey '.json_encode($_logs));
        
        //key不存在的情况, 要生成数据并保存到key
        if (!Yii::$app->redis->exists($cacheKey))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data cache not exists '.json_encode($_logs));
            
            //并发锁, 防止N多人同时第一次点击执行任务
            $lockKey = $cacheKey.'lock';
            $_logs['$lockKey'] = $lockKey;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $lockKey '.json_encode($_logs));
            
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
                
                return $this->initDataForUser($userId, $lockTimes + 1);
            }
            Yii::$app->redis->expire($lockKey, 30);
            
            //--------------------------------------
            
            $allowIds = [];
            if ($this->stepParentIds)
            {
                foreach ($this->stepParentIds as $stepId_)
                {
                    //查询初始数据
                    $doneIds = Work::find()
                    ->select(['data_id'])
                    ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_])
                    ->andWhere(['user_id' => $userId])
                    ->andWhere(['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH]])
                    //->orderBy(['id' => SORT_ASC])
                    ->asArray()->column();
                    $_logs['$doneIds'] = count($doneIds);
                    //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds '.json_encode($_logs));
                
                    $allowIds = $allowIds ? array_intersect($allowIds, $doneIds) : $doneIds;
                }
            }
            $_logs['$allowIds'] = count($allowIds);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $allowIds '.json_encode($_logs));
            
            
            //查询初始数据
            $doneIds = Work::find()
            ->select(['data_id'])
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->asArray()->column();
            $_logs['$doneIds'] = count($doneIds);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $doneIds '.json_encode($_logs));
            
            $newIds = array_diff($allowIds, $doneIds);
            $totalCount = count($newIds);
            $_logs['$totalCount'] = $totalCount;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $newIds  '.json_encode($_logs));
            
            
            //查询初始数据
            $notStartIds = Work::find()
            ->select(['data_id'])
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
            ->andWhere(['in', 'data_id', $allowIds])
            ->andWhere(['in', 'status', [Work::STATUS_NEW]])
            ->asArray()->column();
            $_logs['$notStartIds'] = $notStartIds;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $notStartIds '.json_encode($_logs));
            
            //计算并集,因以下使用集合, 故不需要排除重复
            $newIds = array_merge($newIds, $notStartIds);
            $_logs['$newIds'] = $newIds;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $allIds '.json_encode($_logs));
            
            //--------------------------------
            // 缓存数据
            //--------------------------------
            
            //有剩余项, 存储id
            if ($newIds)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' import cache '.json_encode($_logs));
                $time1 = microtime(true);
                
                //Yii::$app->redis->MULTI();
                foreach ($newIds as $id)
                {
                    //有优化空间, 可一次性push多个
                    Yii::$app->redis->sadd($cacheKey, $id);
                }
                //Yii::$app->redis->EXEC();
                
                Yii::$app->redis->expire($cacheKey, 300);
                
                $time111 = microtime(true) - $time1;
                $_logs['$time111'] = $time111;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' import cache succ '.json_encode($_logs));
            }
            //没有剩余项, 置一个标志位, string类型
            else
            {
                //Yii::$app->redis->set($cacheKey, 1);
                
                //设定缓存失效时间
                //Yii::$app->redis->expire($cacheKey, 5);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no data remain, change cacheType to string '.json_encode($_logs));
            }
            
            //删除lockkey
            Yii::$app->redis->del($lockKey);
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data cache exist '.json_encode($_logs));
        }
        
        return true;
    }
    
    /**
     * 获取指定任务指定分步的可执行的itemId
     * 存储于redis list中
     * 采用递归方式
     *
     * @param int $stepId
     * @param int $dataId 可选, 指定item
     * @param int $lockTimes 防止死循环
     */
    public function checkData($dataId = 0, $userId = 0)
    {
        $_logs['$dataId'] = $dataId;
        $_logs['$userId'] = $userId;

        //获取任务池的key
        $cacheKey = $this->getDataKey();
        $_logs['$cacheKey'] = $cacheKey;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getDataKey '.json_encode($_logs));
    
        //生成数据并保存到key
        $isInit = $this->initData();
        $_logs['$isInit'] = $isInit;
        if (!$isInit)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData '.json_encode($_logs));
            return false;
        }
    
        //获取key的类型
        $cacheKeyType = (string)Yii::$app->redis->type($cacheKey);
        $_logs['$cacheKeyType'] = $cacheKeyType;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyType '.json_encode($_logs));
        
        //如果是list
        if (in_array($cacheKeyType, ['set', '2', 2]))
        {
            //若指定某item
            if ($dataId)
            {
                //抛出目标元素
                $ismember = Yii::$app->redis->sismember($cacheKey, $dataId);
                $_logs['$ismember'] = $ismember;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $ismember '.json_encode($_logs));
                if ($ismember)
                {
                    return true;
                }
                return 0;
            }
            elseif ($userId)
            {
                //获取任务池的key
                $cacheKeyProduceUser = $this->getDataKey($userId);
                $_logs['$cacheKeyProduceUser'] = $cacheKeyProduceUser;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyProduceUser '.json_encode($_logs));
            
                //生成数据并保存到key
                $isInit = $this->initDataForUser($userId);
                $_logs['$isInit'] = $isInit;
                if (!$isInit)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData '.json_encode($_logs));
                    return false;
                }
            
                //获取key的类型
                $cacheKeyTypeProduceUser = Yii::$app->redis->type($cacheKeyProduceUser);
                $_logs['$cacheKeyTypeProduceUser'] = $cacheKeyTypeProduceUser;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyTypeProduceUser '.json_encode($_logs));
                if (in_array($cacheKeyTypeProduceUser, ['set', '2', 2]))
                {
                    //计算交集, 并存储到新的key
                    $cacheKeyProduceUserInterset = $this->getDataKey($userId.'_interset');
                    $membserCount = Yii::$app->redis->SINTERSTORE($cacheKeyProduceUserInterset, $cacheKey, $cacheKeyProduceUser);
                    Yii::$app->redis->expire($cacheKeyProduceUserInterset, 300);
            
                    if ($membserCount)
                    {
                        return true;
                    }
                    else
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no data remain '.json_encode($_logs));
                        return 0;
                    }
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no data remain '.json_encode($_logs));
                    return 0;
                }
            }
            else 
            {
                //余量为0, key转型, 保持一直有cache
                $lastLen = Yii::$app->redis->scard($cacheKey);
                $_logs['$lastLen'] = $lastLen;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $lastLen '.json_encode($_logs));
                if ($lastLen > 0)
                {
                    return true;
                }
                return 0;
            }
        }
        //如果是string, 即结果为空, 表全部以做完
        elseif (in_array($cacheKeyType, ['string', '1', 1]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no data remain '.json_encode($_logs));
            return 0;
        }
        //其他类型, 程序设计不存在此类型, 程序异常
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cacheKeyType error '.json_encode($_logs));
            return 0;
        }
    }
    
    /**
     * 获取指定任务指定分步的可执行的itemId
     * 存储于redis list中
     * 采用递归方式
     *
     * @param int $stepId 必有值
     * @param int $dataId 可无值
     * @param int $dataCount 每次获取的数据量
     * @param int $dataSort 获取顺序, 0顺序,1随机
     */
    public function getData($dataId = 0, $userId = 0, $dataCount = 1, $dataSort = 0)
    {
        $_logs = ['$userId' => $userId, '$dataId' => $dataId, '$dataCount' => $dataCount];
    
        //获取任务池的key
        $cacheKey = $this->getDataKey();
        $_logs['$cacheKey'] = $cacheKey;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getDataKey '.json_encode($_logs));
    
        //生成数据并保存到key
        $isInit = $this->initData();
        $_logs['$isInit'] = $isInit;
        if (!$isInit)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData '.json_encode($_logs));
            return false;
        }
    
        //获取key的类型
        $cacheKeyType = (string)Yii::$app->redis->type($cacheKey);
        $_logs['$cacheKeyType'] = $cacheKeyType;
        $_logs['$cacheKeyTtl'] = Yii::$app->redis->ttl($cacheKey);;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyType '.json_encode($_logs));
    
        //如果是list
        if (in_array($cacheKeyType, ['set', '2', 2]))
        {
            //若指定某item
            if ($dataId)
            {
                if (Yii::$app->redis->sismember($cacheKey, $dataId))
                {
                    //抛出目标元素
                    $count = Yii::$app->redis->srem($cacheKey, $dataId);
                    $_logs['$count'] = $count;
                    
                    $itemId = $dataId;
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataId not exist '.json_encode($_logs));
                    return 0;
                }
            }
            elseif ($userId)
            {
                //获取任务池的key
                $cacheKeyProduceUser = $this->getDataKey($userId);
                $_logs['$cacheKeyProduceUser'] = $cacheKeyProduceUser;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyProduceUser '.json_encode($_logs));
                
                //生成数据并保存到key
                $isInit = $this->initDataForUser($userId);
                $_logs['$isInit'] = $isInit;
                if (!$isInit)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData '.json_encode($_logs));
                    return false;
                }
                
                //获取key的类型
                $cacheKeyTypeProduceUser = Yii::$app->redis->type($cacheKeyProduceUser);
                $_logs['$cacheKeyTypeProduceUser'] = $cacheKeyTypeProduceUser;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyTypeProduceUser '.json_encode($_logs));
                if (in_array($cacheKeyTypeProduceUser, ['set', '2', 2]))
                {
                    //计算交集, 并存储到新的key
                    $cacheKeyProduceUserInterset = $this->getDataKey($userId.'_interset');
                    $membserCount = Yii::$app->redis->SINTERSTORE($cacheKeyProduceUserInterset, $cacheKey, $cacheKeyProduceUser);
                    Yii::$app->redis->expire($cacheKeyProduceUserInterset, 300);
                    
                    $_logs['$cacheKeyProduceUserInterset'] = $cacheKeyProduceUserInterset;
                    $_logs['$membserCount'] = $membserCount;
                    
                    if ($membserCount)
                    {
                        $itemIds = Yii::$app->redis->sort($cacheKeyProduceUserInterset);
                        $_logs['$itemIds.count'] = count($itemIds);
                        
                        //删除交集缓存
                        Yii::$app->redis->del($cacheKeyProduceUserInterset);
                        
                        if (!$itemIds)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' not items '.json_encode($_logs));
                            return 0;
                        }
                        
                        if ($dataSort)
                        {
                            //打乱数组顺序
                            shuffle($itemIds);
                        }
                        
                        $itemId = [];
                        foreach ($itemIds as $itemId_)
                        {
                            $_logs['$itemId_'] = $itemId_;
                            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sort '.json_encode($_logs));
                            
                            //抛出目标元素
                            $isRemove = Yii::$app->redis->srem($cacheKey, $itemId_);
                            $_logs['$isRemove'] = $isRemove;
                            if (!$isRemove)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $isRemove error, $cacheKey '.json_encode($_logs));
                                return 0;
                            }
                            
                            $isRemove = Yii::$app->redis->srem($cacheKeyProduceUser, $itemId_);
                            $_logs['$isRemove'] = $isRemove;
                            if (!$isRemove)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $isRemove error, $cacheKeyProduceUser '.json_encode($_logs));
                                return 0;
                            }
                            
                            $itemId[] = $itemId_;
                            if (count($itemId) >= $dataCount)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get full '.json_encode($_logs));
                                break;
                            }
                        }
                        
                        $_logs['$itemId'] = $itemId;
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
                    }
                    else
                    {
                        //删除交集缓存
                        //Yii::$app->redis->del($cacheKey);
                        
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user has data, intersect no data remain '.json_encode($_logs));
                        return 0;
                    }
                }
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user no data remain '.json_encode($_logs));
                    return 0;
                }
            }
            //没指定, 则顺序抛出
            elseif (in_array($this->step['type'], [Step::TYPE_PRODUCE]))
            {
                $_logs['getdata.type'] = 'sort';
                
                //顺序抛出一个元素
                $itemIds = Yii::$app->redis->sort($cacheKey);
                $_logs['$itemIds.count'] = count($itemIds);
                
                if (!$itemIds)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' not items '.json_encode($_logs));
                    return 0;
                }
                
                if ($dataSort)
                {
                    //打乱数组顺序
                    shuffle($itemIds);
                }
                
                $itemId = [];
                foreach ($itemIds as $itemId_)
                {
                    $_logs['$itemId_'] = $itemId_;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sort '.json_encode($_logs));
                    
                    //抛出目标元素
                    $count = Yii::$app->redis->srem($cacheKey, $itemId_);
                    $_logs['srem$count'] = $count;
                    
                    //若删除失败, 则说明出现并发, 重新获取
                    if ($count < 1)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' srem empty, continue '.json_encode($_logs));
                        continue;
                    }
                    
                    $itemId[] = $itemId_;
                    if (count($itemId) >= $dataCount)
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get full '.json_encode($_logs));
                        break;
                    }
                }
                
                $_logs['$itemId'] = $itemId;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' srem.succ '.json_encode($_logs));
            }
            //审核或质检, 打乱顺序
            elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $_logs['getdata.type'] = 'random';
                
                $_logs['getdata.type'] = 'sort';
                
                //顺序抛出一个元素
                $itemIds = Yii::$app->redis->sort($cacheKey);
                $_logs['$itemIds.count'] = count($itemIds);
                
                if (!$itemIds)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' not items '.json_encode($_logs));
                    return 0;
                }
                
                if ($dataSort)
                {
                    //打乱数组顺序
                    shuffle($itemIds);
                }
                
                $itemId = [];
                foreach ($itemIds as $itemId_)
                {
                    $_logs['$itemId_'] = $itemId_;
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sort '.json_encode($_logs));
                    
                    //抛出目标元素
                    $count = Yii::$app->redis->srem($cacheKey, $itemId_);
                    $_logs['srem$count'] = $count;
                    
                    //若删除失败, 则说明出现并发, 重新获取
                    if ($count < 1)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' srem empty, continue '.json_encode($_logs));
                        continue;
                    }
                    
                    $itemId[] = $itemId_;
                    if (count($itemId) >= $dataCount)
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get full '.json_encode($_logs));
                        break;
                    }
                }
                
                $_logs['$itemId'] = $itemId;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' spop.succ '.json_encode($_logs));
            }
            
            
            //余量为0, key转型, 保持一直有cache
            $lastLen = Yii::$app->redis->scard($cacheKey);
            $_logs['$lastLen'] = $lastLen;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $lastLen '.json_encode($_logs));
            if (!$lastLen)
            {
                //Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no data remain, change cacheType to string '.json_encode($_logs));
                //Yii::$app->redis->set($cacheKey, 1);
    
                //设定缓存失效时间
                //Yii::$app->redis->expire($cacheKey, 5);
            }
            
            $_logs['$itemId.last'] = $itemId;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $itemId;
        }
        //如果是string, 即结果为空, 表全部以做完
        elseif (in_array($cacheKeyType, ['string', '1', 1]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no data remain '.json_encode($_logs));
            return 0;
        }
        //其他类型, 程序设计不存在此类型, 程序异常
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cacheKeyType error '.json_encode($_logs));
            return 0;
        }
    }
    
    public function getDataCount()
    {
        $_logs = [];
        
        //获取任务池的key
        $cacheKey = $this->getDataKey();
        $_logs['$cacheKey'] = $cacheKey;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getDataKey '.json_encode($_logs));
        
        //生成数据并保存到key
        $isInit = $this->initData();
        $_logs['$isInit'] = $isInit;
        if (!$isInit)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData '.json_encode($_logs));
            return false;
        }
        
        //获取key的类型
        $cacheKeyType = (string)Yii::$app->redis->type($cacheKey);
        $_logs['$cacheKeyType'] = $cacheKeyType;
        $_logs['$cacheKeyTtl'] = Yii::$app->redis->ttl($cacheKey);;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyType '.json_encode($_logs));
        
        //如果是list
        if (in_array($cacheKeyType, ['set', '2', 2]))
        {
            $count = Yii::$app->redis->scard($cacheKey);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set '.json_encode($_logs));
            return $count;
        }
        //如果是string, 即结果为空, 表全部以做完
        elseif (in_array($cacheKeyType, ['string', '1', 1]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' string '.json_encode($_logs));
            return 0;
        }
        //其他类型, 程序设计不存在此类型, 程序异常
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other '.json_encode($_logs));
            return 0;
        }
    }
    
    /**
     * 把作业追加到任务池
     *
     * @param int $dataId
     */
    public function appendData($dataId)
    {
        $_logs = ['$dataId' => $dataId];
    
        //获取任务池的key
        $cacheKey = $this->getDataKey();
        $_logs['$cacheKey'] = $cacheKey;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getDataKey '.json_encode($_logs));
    
        //生成数据并保存到key (屏蔽此处, 为了缓解压力, 也不是必须要执行)
        //$result = $this->initData($stepId);
        //$_logs['initData$result'] = $result;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData '.json_encode($_logs));
    
        //获取key的类型
        $cacheKeyType = (string)Yii::$app->redis->type($cacheKey);
        $_logs['$cacheKeyType'] = $cacheKeyType;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheKeyType '.json_encode($_logs));
    
        //---------------------------
        //有并发的问题, 此时同时getData, 若只剩最后一个, 类型转变为string, 将异常
        //---------------------------
        //如果是list
        if (in_array($cacheKeyType, ['set', '2', 2]))
        {
            //判断id是否存在
            $ismember = Yii::$app->redis->sismember($cacheKey, $dataId);
            $_logs['$ismember'] = $ismember;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $ismember '.json_encode($_logs));
            if ($ismember)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $ismember '.json_encode($_logs));
                return true;
            }
    
            //并发情况, 类型转变
            try {
                Yii::$app->redis->sadd($cacheKey, $dataId);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sadd '.json_encode($_logs));
            }catch (Exception $e){
                //说明类型已转变
                $_logs['$e.getMessage'] = $e->getMessage();
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Exception '.json_encode($_logs));
    
                Yii::$app->redis->del($cacheKey);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' del $cacheKey '.json_encode($_logs));
            };
    
            return true;
        }
        //如果是string, 即结果为空, 删除cache, itemid添加到set
        elseif (in_array($cacheKeyType, ['string', '1', 1]))
        {
            Yii::$app->redis->del($cacheKey);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' del $cacheKey '.json_encode($_logs));
            
            //有优化空间, 可一次性push多个
            Yii::$app->redis->sadd($cacheKey, $dataId);
            Yii::$app->redis->expire($cacheKey, 3600);
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' add cache succ '.json_encode($_logs));
            return true;
        }
        //其他类型, 程序设计不存在此类型, 程序异常
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cacheKeyType error '.json_encode($_logs));
            return false;
        }
    }
    
    //把追加的item放到所有的数据池里
    public function clearData()
    {
        $cacheKeyPre = $this->getDataKey(0, 0);
        $_logs['$cacheKeyPre'] = $cacheKeyPre;
        $keys = Yii::$app->redis->keys($cacheKeyPre.'*');
        $_logs['$keys'] = $keys;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' keys0 '.json_encode($_logs));
        if ($keys)
        {
            foreach ($keys as $key)
            {
                Yii::$app->redis->del($key);
            }
        }
        
        $cacheKeyPre = $this->getUserCacheKey();
        $_logs['$cacheKeyPre'] = $cacheKeyPre;
        $keys = Yii::$app->redis->keys($cacheKeyPre.'*');
        $_logs['$keys'] = $keys;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' keys2 '.json_encode($_logs));
        if ($keys)
        {
            foreach ($keys as $key)
            {
                Yii::$app->redis->del($key);
            }
        }
    
        return true;
    }
    
    /**
     * 校验用户是否对某任务有执行权限
     * 
     * @param int $dataId
     * @return boolean is or not allow
     */
    public function checkPermission()
    {
        //-------------------------------------------------
        $_logs = [];

        $taskUser = TaskUser::find()->where(['task_id' => $this->task['id'], 'user_id' => $this->userId])->asArray()->limit(1)->one();
        if (!$taskUser)
        {
            $taskUser = new TaskUser();
            $taskUser->project_id = $this->projectId;
            $taskUser->task_id = $this->task['id'];
            $taskUser->user_id = $this->userId;
            $taskUser->status = 0;
            $taskUser->created_at = time();
            $taskUser->updated_at = time();
            $taskUser->save();

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' add to task_user '.json_encode($_logs));
        }
        
        //有源数据的情况, 比如:标注类, 
        if ($this->category['type'] == Category::TYPE_LABEL)
        {
            //------------------------------
            //判断是否有分步的执行权限
            //-------------------------------
            
            
        }
        //没有数据源的情况, 比如:采集类, 
        elseif ($this->category['type'] == Category::TYPE_COLLECTION)
        {
            //------------------------------
            //判断是否有分步的执行权限
            //-------------------------------
            
            
			//---------------------------------
        }
        elseif ($this->category['type'] == Category::TYPE_EXTERNAL)
        {
            //------------------------------
            //判断是否有分步的执行权限
            //-------------------------------
            
        }
        
        //------------------------------------------
        
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result(true);
    }
    
    /**
     *
     * 指定某一item中, 指定用户可执行的step
     *
     * @param int $projectId
     * @param int $orderId
     * @param int $userId
     * @param int $dataId
     */
    public function fetchData($dataId = 0, $userId = 0, $dataCount = 2, $dataSort = 0)
    {
        $_logs = ['$dataId' => $dataId, '$userId' => $userId];
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        //-------------------------------------------------
        
        //判断是否有可执行作业
        $statInfo = Stat::find()
        ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
        ->asArray()->limit(1)->one();
        if (!$statInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_stat_notfound error '.json_encode($_logs));
            return FormatHelper::result('', 'task_stat_notfound', yii::t('app', 'task_stat_notfound'));
        }
        
        /*
        if ($this->batch['amount'] <= $statInfo['work_count'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_received_finished error '.json_encode($_logs));
            return FormatHelper::result('', 'task_received_finished', yii::t('app', 'task_received_finished'));
        }
        
        if ($statInfo['amount'] && $statInfo['amount'] <= $statInfo['work_count'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_waitting error '.json_encode($_logs));
            return FormatHelper::result('', 'task_data_waitting', yii::t('app', 'task_data_waitting'));
        }*/
        
        //-----------------------------------
        
        if ($this->step['type'] == Step::TYPE_PRODUCE){
            
            //抢单模式, #有源数据的情况, 比如:标注类,
            if ((int)$this->project['assign_type'] == Project::ASSIGN_TYPE_NORMAL)
            {
                //---------------------------------------
                //找到可执行的分步
                //并检查该分步是否有数据
                //---------------------------------------
    
                //判断该批次的分步是否有数据
                $hasData = $this->checkData($dataId, $userId);
                //数据初始化中
                if ($hasData === false)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_initializing '.json_encode($_logs));
                    return FormatHelper::result('', 'task_data_initializing', yii::t('app', 'task_data_initializing'));
                }
                //没有获取到数据, 继续循环
                elseif (!$hasData)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_waitting error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_data_waitting', yii::t('app', 'task_data_waitting'));
                }
                //该分步有数据
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId hasData '.json_encode($_logs));
                }
                //获取数据
                $nextItemId = $this->getData($dataId, $userId, $dataCount, $dataSort);
                $_logs['$nextItemId'] = $nextItemId;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' the step in nowsteps, getData '.json_encode($_logs));
                
                //发生了异常
                if ($nextItemId === false)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_get_error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_data_get_error', yii::t('app', 'task_data_get_error'));
                }
                //没有获取到数据, 继续循环
                elseif (!$nextItemId)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_waitting '.json_encode($_logs));
                    return FormatHelper::result('', 'task_data_waitting', yii::t('app', 'task_data_waitting'));
                }
                //获取到有效的数据,跳出循环
                else
                {
                    //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' the step in nowsteps, succ '.json_encode($_logs));
                    return FormatHelper::result($nextItemId);
                }
            }
            //非抢单模式, #没有数据源的情况, 比如:采集类,
            elseif ((int)$this->project['assign_type'] == Project::ASSIGN_TYPE_STUDY)
            {
                //查询已初始化的数据
                $oriDataIds = Data::find()
                ->select(['id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'data_key' => ''])
                ->asArray()->column();
                $_logs['$oriDataIds'] = $oriDataIds;
                
                //查询已初始化的数据
                $hasDataIds = Data::find()
                ->select(['data_parent_id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'data_key' => $this->userId])
                ->asArray()->column();
                $_logs['$hasDataIds'] = $hasDataIds;
                
                //找到未执行的数据
                $newDataIds = array_diff($oriDataIds, $hasDataIds);
                $_logs['$newDataIds'] = $newDataIds;
                
                if ($newDataIds)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataInfo notexist '.json_encode($_logs));
                    
                    /*
                    //判断作业总量
                    $didTotal = Data::find()
                    ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId])
                    ->asArray()->count();
                    $_logs['$didTotal'] = $didTotal;
                    
                    if ($didTotal >= $this->batch['amount'])
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_received_finished '.json_encode($_logs));
                        return FormatHelper::result('', 'task_received_finished', yii::t('app', 'task_received_finished'));
                    }*/
                    
                    foreach ($newDataIds as $newDataId){
                        
                        $oriData = Data::find()->where(['id' => $newDataId])->asArray()->limit(1)->one();
                        $oriDataResult = DataResult::find()->where(['data_id' => $newDataId])->asArray()->limit(1)->one();
                        
                        
                        $data = new Data();
                        $data->load($oriData, '');
                        $data->project_id = $this->projectId;
                        $data->batch_id = $this->batchId;
                        $data->data_key = (string)$this->userId;
                        $data->data_parent_id = (string)$newDataId;
                        $data->created_at = time();
                        $data->updated_at = time();
                        if (!$data->validate() || !$data->save())
                        {
                            $errors = $data->getFirstErrors();
                            $error = key($errors);
                            $message = current($errors);
                            $_logs['$model.$errors'] = $errors;
                            $_logs['$model.$error'] = $error;
                            $_logs['$model.$message'] = $message;
                            
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                            return FormatHelper::result('', $error, $message);
                        }
                        
                        $dataResult = new DataResult();
                        $dataResult->load($oriDataResult, '');
                        $dataResult->project_id = $this->projectId;
                        $dataResult->batch_id = $this->batchId;
                        $dataResult->data_id = $data->id;
                        //$dataResult->data = json_encode(['user_id' => $this->userId]);
                        if (!$dataResult->validate() || !$dataResult->save())
                        {
                            $errors = $dataResult->getFirstErrors();
                            $error = key($errors);
                            $message = current($errors);
                            $_logs['$model.$errors'] = $errors;
                            $_logs['$model.$error'] = $error;
                            $_logs['$model.$message'] = $message;
                            
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                            return FormatHelper::result('', $error, $message);
                        }
                        
                        //追加到分发数据池
                        //$this->appendData($data->id);
                    }
                    
                    //增加统计数
                    $counters = ['amount' => count($newDataIds)];
                    Batch::updateAllCounters($counters, ['id' => $this->batchId]);
                    
                    $counters = ['data_count' => count($newDataIds), 'amount' => count($newDataIds)];
                    Project::updateAllCounters($counters, ['id' => $this->projectId]);
                    //$_logs['$nextItemId'] = $nextItemId;
                    
                    $attributes = ['amount' => count($newDataIds)];
                    Task::updateAllCounters($attributes, ['id' => $this->task['id']]);
                    
                    $attributes = ['amount' => count($newDataIds)];
                    Stat::updateAllCounters($attributes, ['id' => $this->stat['id']]);
                    
                    //教学模式,最大执行张数等于批次初试张数
                    if ($this->task['max_times'] != count($newDataIds)) {
                        $attributes = ['max_times' => count($newDataIds)];
                        Task::updateAll($attributes, ['id' => $this->task['id']]);
                    }
                }
                
                /*
                //查询作业的状态
                $work = Work::find()
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $nextItemId])
                ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
                ->orderBy(['id' => SORT_DESC])
                ->asArray()->limit(1)->one();
                $_logs['$work'] = $work;
                
                if (!$work)
                {
                    Work::batchInsert($this->projectId, $this->batchId, $this->stepId, [$nextItemId]);
                }*/
                
                /*
                //采集类只可能有一步
                $nextItemId = $this->getData($dataInfo['id'], $userId);
                if ($nextItemId === false)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_get_error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_data_get_error', yii::t('app', 'task_data_get_error'));
                }
                elseif (!$nextItemId)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_fetchData_noDataRemain error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_data_notremain', yii::t('app', 'task_data_notremain'));
                }
                //该分步有数据
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId hasData '.json_encode($_logs));
                }*/
                
                $allDataIds = Data::find()
                ->select(['id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'data_key' => $this->userId])
                ->asArray()->column();
                $_logs['$allDataIds'] = $allDataIds;
                
                //查询用户已执行的作业
                $didDataIds = Work::find()
                ->select(['data_id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
                ->andWhere(['user_id' => $this->userId])
                ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
                ->asArray()->column();
                $_logs['$didDataIds'] = $didDataIds;
                
                /*
                //判断用户的执行数是否已达到任务的最大执行数
                if ($this->task['max_times'] && count($didDataIds) >= $this->task['max_times'])
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_exe_max_times '.json_encode($_logs));
                    return FormatHelper::result('', 'task_exe_max_times', sprintf(yii::t('app', 'task_exe_max_times'), count($didDataIds)));
                }*/
                
                $notStartDataIds = array_diff($allDataIds, $didDataIds);
                $_logs['$notStartDataIds'] = $notStartDataIds;
                if ($notStartDataIds) {
                    //Work::batchInsert($this->projectId, $this->batchId, $this->stepId, $notStartDataIds);
                    $batchInsertFields = [
                        'project_id','batch_id','step_id','data_id','status','user_id', 'created_at', 'updated_at'
                    ];
                    $batchInsertValues = [];
                    foreach ($notStartDataIds as $notStartDataId) {
                        $batchInsertValues[] = [
                            $this->projectId, $this->batchId, $this->stepId, $notStartDataId, Work::STATUS_NEW, $this->userId, time(), time()
                        ];
                    }
                    
                    Yii::$app->db->createCommand()->batchInsert(Work::tableName(), $batchInsertFields, $batchInsertValues)->execute();
                }
                
                $allowDataIds = Work::find()
                ->select(['data_id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
                ->andWhere(['user_id' => $this->userId])
                ->andWhere(['in', 'status', [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]])
                ->asArray()->column();
                $_logs['$allowDataIds'] = $allowDataIds;
                
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
                return FormatHelper::result($allowDataIds);
            }
        } else {
            #审核和质检的正常操作
            
            //判断该批次的分步是否有数据
            $hasData = $this->checkData($dataId, $userId);
            //数据初始化中
            if ($hasData === false)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_initializing '.json_encode($_logs));
                return FormatHelper::result('', 'task_data_initializing', yii::t('app', 'task_data_initializing'));
            }
            //没有获取到数据, 继续循环
            elseif (!$hasData)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_waitting error '.json_encode($_logs));
                return FormatHelper::result('', 'task_data_waitting', yii::t('app', 'task_data_waitting'));
            }
            //该分步有数据
            else
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId hasData '.json_encode($_logs));
            }
            //获取数据
            $nextItemId = $this->getData($dataId, $userId, $dataCount, $dataSort);
            $_logs['$nextItemId'] = $nextItemId;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' the step in nowsteps, getData '.json_encode($_logs));
            
            //发生了异常
            if ($nextItemId === false)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_get_error '.json_encode($_logs));
                return FormatHelper::result('', 'task_data_get_error', yii::t('app', 'task_data_get_error'));
            }
            //没有获取到数据, 继续循环
            elseif (!$nextItemId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_waitting '.json_encode($_logs));
                return FormatHelper::result('', 'task_data_waitting', yii::t('app', 'task_data_waitting'));
            }
            //获取到有效的数据,跳出循环
            else
            {
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' the step in nowsteps, succ '.json_encode($_logs));
                return FormatHelper::result($nextItemId);
            }
        }
        
    }
    
    public function getUserScene($dataId = 0, $userId = 0)
    {
        $scene = sprintf('%s.%s.%s', $this->projectId, $this->batchId, $this->stepId);
        $dataId && ($scene .= '.'.$dataId);
        $userId && ($scene .= '.'.$userId);
        
        return $scene;
    }
    
    public function getUserScenes()
    {
        //获取用户本任务缓存池
        $cacheKey = sprintf('projectuser:%s:%s:%s:%s:%s', $this->projectId, $this->batchId, $this->stepId, $this->userId, '*');
        $cacheKey = Yii::$app->redis->buildKey($cacheKey);
        $_logs['$cacheKey'] = $cacheKey;
        
        $keys = Yii::$app->redis->keys($cacheKey);
        $_logs['$keys'] = $keys;
    
        $scenes = [];
        if ($keys)
        {
            foreach ($keys as $key)
            {
                // v2redis:projectuser:10174:1201:1596:13:10174.1201.1596.13
                $scene = substr(strrchr($key, ':'), 1);// 10174.1201.1596.13
    
                ///只统计正常执行作业的情况, 驳回等情况不限制
                ///if (substr_count($scene, '.') == 1)
                ///{
                    $scenes[] = $scene;
                ///}
            }
        }
        $_logs['$scenes'] = $scenes;
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $scenes;
    }
    
    public function getUserAllScenes()
    {
        //获取用户所有缓存池
        $cacheKey = sprintf('projectuser:%s:%s:%s:%s:%s', '*', '*', '*', $this->userId, '*');
        
        $cacheKey = Yii::$app->redis->buildKey($cacheKey);
        $keys = Yii::$app->redis->keys($cacheKey);
        $_logs['$keys'] = $keys;
        
        $scenes = [];
        if ($keys)
        {
            foreach ($keys as $key)
            {
                $scene = substr(strrchr($key, ':'), 1);
                
                ///只统计正常执行作业的情况, 驳回等情况不限制
                ///if (substr_count($scene, '.') == 1)
                ///{
                    $scenes[] = $scene;
                ///}
            }
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $scenes;
    }
    
    //用户执行任务的临时缓存key
    public function getUserCachePrefixKey($scene = '')
    {
        $cacheKey = sprintf('projectuser:%s:%s:%s', $this->projectId, $this->batchId, $this->stepId);
    
        return Yii::$app->redis->buildKey($cacheKey);
    }
    
    //用户执行任务的临时缓存key
    public function getUserCacheKey($scene = '')
    {
        $cacheKey = sprintf('projectuser:%s:%s:%s:%s', $this->projectId, $this->batchId, $this->stepId, $this->userId);
        if ($scene)
        {
            $cacheKey .= ':'.$scene;
        }
        
        return Yii::$app->redis->buildKey($cacheKey);
    }
    
    public function getUserCacheTime($isLong = false)
    {
        //缓存时间
        if ($isLong)
        {
            $cacheTime = Yii::$app->params['task_receive_expire_long'];
            $_logs = ['$cacheTime' => $cacheTime];
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCacheKey1 '.json_encode($_logs));
        }
        elseif (!empty($this->task['receive_expire']) && $this->task['receive_expire'] > 0)
        {
            $cacheTime = $this->task['receive_expire'];
            $_logs = ['$cacheTime' => $cacheTime];
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCacheKey0 '.json_encode($_logs));
        }
        else
        {
            $cacheTime = Yii::$app->params['task_receive_expire'];
            $_logs = ['$cacheTime' => $cacheTime];
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCacheKey2 '.json_encode($_logs));
        }
        
        return $cacheTime;
    }
    
    //用户执行任务的临时缓存key
    public function getUserCacheTtl($scene = '')
    {
        $_logs = ['$scene' => $scene];
        
        $cacheKey = $this->getUserCacheKey($scene);
        $_logs['$cacheKey'] = $cacheKey;
        
        $cacheTime = Yii::$app->redis->ttl($cacheKey);
        $_logs['$cacheTime'] = $cacheTime;
        if ($cacheTime < 0)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheTime < 0 '.json_encode($_logs));
        }
        
        return $cacheTime;
    }
    
    public function refreshUserCacheTtl($scene, $isLong = false)
    {
        $_logs = ['$scene' => $scene, '$isLong' => $isLong];
        
        $cacheKey = $this->getUserCacheKey($scene);
        $_logs['$cacheKey'] = $cacheKey;
        
        //缓存时间
        $cacheTime = $this->getUserCacheTime($isLong);
        $_logs['$cacheTime'] = $cacheTime;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' expire '.json_encode($_logs));
        Yii::$app->redis->expire($cacheKey, $cacheTime);
        
        return true;
    }
    
    //设置用户执行任务时的临时缓存
    public function setUserCache($scene, $dataId, $isKeepOld = false)
    {
        $_logs['$dataId'] = $dataId;
        $_logs['$scene'] = $scene;
        
        //任务锁, 一段时间范围内将获取统一任务项, 防止刷新
        //任务锁的key
        $cacheKey = $this->getUserCacheKey($scene);
        $_logs['$cacheKey'] = $cacheKey;
        
        //检查是否已有缓存
        $hadCacheVal = Yii::$app->redis->get($cacheKey);
        $_logs['$hadCacheVal'] = $hadCacheVal;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $hadCacheVal '.json_encode($_logs));
        if ($hadCacheVal)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCacheKey '.json_encode($_logs));
        
            //保留老数据
            if ($isKeepOld)
            {
                //提取数据
                list($cacheScene, $cacheDataId) = explode('_', $hadCacheVal);
                $_logs['$cacheDataId'] = $cacheDataId;
                $_logs['$cacheScene_'] = $cacheScene;
            
                if (is_numeric($cacheDataId))
                {
                    $dataIds_ = [$cacheDataId];
                }
                else
                {
                    $dataIds_ = unserialize($cacheDataId);
                }
                $_logs['$dataIds_'] = $dataIds_;
                //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataIds_ '.json_encode($_logs));
            
                $dataIds = is_array($dataId) ? $dataId : [$dataId];
                $_logs['$dataIds'] = $dataIds;
                $dataId = array_merge($dataIds, $dataIds_);
                $_logs['$dataId.new'] = $dataId;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataId.new '.json_encode($_logs));
            }
            //else 
            //{
                ////Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' clear $hadCacheVal '.json_encode($_logs));
                //$this->clearUserCache(0, true);
            //}
        }
        
        $cacheVal = sprintf('%s_%s', $scene, is_numeric($dataId) ? $dataId : serialize($dataId));
        $_logs['$cacheVal'] = $cacheVal;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheVal '.json_encode($_logs));
        
        //缓存时间
        $cacheTime = $this->getUserCacheTime($isKeepOld);
        $_logs['$cacheTime'] = $cacheTime;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheTime '.json_encode($_logs));
        
        //缓存标志
        Yii::$app->redis->setex($cacheKey, $cacheTime, $cacheVal);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set '.json_encode($_logs));
        
        return true;
    }
    
    //获取用户执行任务时的临时缓存
    public function getUserCache($scene)
    {
        $_logs['$scene'] = $scene;
        
        //任务锁, 一段时间范围内将获取统一任务项, 防止刷新
        //任务锁的key
        $cacheKey = $this->getUserCacheKey($scene);
        $_logs['$cacheKey'] = $cacheKey;
        
        $cacheVal = Yii::$app->redis->get($cacheKey);
        $_logs['$cacheVal'] = $cacheVal;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCacheKey '.json_encode($_logs));
        
        //判断是否存在
        if (!$cacheVal)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheVal notexist '.json_encode($_logs));
            return null;
        }
        
        //判断有效性
        if (strpos($cacheVal, '_') === false)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheVal format error '.json_encode($_logs));
            $this->clearUserCache($scene);
            return false;
        }
        
        //统计_出现的次数
        $count = substr_count($cacheVal, '_');
        if ($count != 1)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheVal format error '.json_encode($_logs));
            $this->clearUserCache($scene);
            return false;
        }
        
        //提取数据
        list($cacheScene, $dataId) = explode('_', $cacheVal);
        $_logs['$dataId'] = $dataId;
        $_logs['$cacheScene'] = $cacheScene;
        
        
        //判断场景
        if ($scene != $cacheScene)
        {
            $this->clearUserCache($scene, 0, true);
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' scene != $cacheScene '.json_encode($_logs));
            return false;
        }
        
        if (!is_numeric($dataId))
        {
            $dataId = unserialize($dataId);
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return is_array($dataId) ? array_unique($dataId) : $dataId;
    }
    
    public function clearUserCache($scene, $clearDataId = 0, $isAppendBack = false)
    {
        $_logs['$scene'] = $scene;
        $_logs['$clearDataId'] = $clearDataId;
        $_logs['$isAppendBack'] = $isAppendBack;
        
        //任务锁的key
        $cacheKey = $this->getUserCacheKey($scene);
        $_logs['$cacheKey'] = $cacheKey;
        
        if (Yii::$app->redis->exists($cacheKey))
        {
            $cacheVal = Yii::$app->redis->get($cacheKey);
            $_logs['$cacheVal'] = $cacheVal;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheVal '.json_encode($_logs));
            
            //统计_出现的次数
            $count = substr_count($cacheVal, '_');
            if ($count != 1)
            {
                Yii::$app->redis->del($cacheKey);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheVal format error '.json_encode($_logs));
                return false;
            }
            
            //提取数据
            list($cacheScene, $dataId) = explode('_', $cacheVal);
            $_logs['$dataId'] = $dataId;
            $_logs['$cacheScene'] = $cacheScene;
            
            if (is_numeric($dataId))
            {
                $dataIds = [$dataId];
            }
            else
            {
                $dataIds = unserialize($dataId);
            }
            $_logs['$dataIds'] = $dataIds;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataIds '.json_encode($_logs));
            
            //指定某一元素
            if ($clearDataId)
            {
                if (in_array($clearDataId, $dataIds))
                {
                    //除去$clearDataId的剩下元素保留
                    $otherElements = array_diff($dataIds, [$clearDataId]);
                    $_logs['$otherElements'] = $otherElements;
                    if ($otherElements)
                    {
                        $cacheValNew = sprintf('%s_%s', $scene, is_numeric($otherElements) ? $otherElements : serialize($otherElements));
                        $_logs['$cacheValNew'] = $cacheValNew;
                        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cacheValNew '.json_encode($_logs));
                        
                        $cacheTime = Yii::$app->redis->ttl($cacheKey);
                        $_logs['$cacheTime'] = $cacheTime;
                        
                        Yii::$app->redis->setex($cacheKey, $cacheTime, $cacheValNew);
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' reset '.json_encode($_logs));
                    }
                    else 
                    {
                        Yii::$app->redis->del($cacheKey);
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' del all '.json_encode($_logs));
                    }
                    
                    if ($isAppendBack)
                    {
                        $this->appendData($clearDataId);
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' appendData succ '.json_encode($_logs));
                    }
                }
                else
                {
                    //无需操作
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' not in usercache '.json_encode($_logs));
                }
            }
            //全部清空
            else 
            {
                Yii::$app->redis->del($cacheKey);
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' del all '.json_encode($_logs));
                
                if ($isAppendBack)
                {
                    foreach ($dataIds as $dataId_)
                    {
                        $this->appendData($dataId_);
                    }
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' appendData succ '.json_encode($_logs));
                }
            }
        }
        
        Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    public function getUserHistoryKey()
    {
        $cacheKey = sprintf('projectuserhistory:%s:%s:%s:%s', $this->projectId, $this->batchId, $this->stepId, $this->userId);
    
        return Yii::$app->redis->buildKey($cacheKey);
    }
    
    public function addUserHistory($dataId, $dataStat)
    {
        $cacheKey = $this->getUserHistoryKey();
        $_logs['$cacheKey'] = $cacheKey;
    
        //缓存标志
        Yii::$app->redis->zadd($cacheKey, time(), serialize([$dataId => $dataStat]));
        Yii::$app->redis->expire($cacheKey, 43200);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' set '.json_encode($_logs));
    
        //移除老数据
        Yii::$app->redis->ZREMRANGEBYSCORE($cacheKey, 0, time() - 43200);
    
        return true;
    }
    
    public function getUserHistory()
    {
        $cacheKey = $this->getUserHistoryKey();
        $_logs['$cacheKey'] = $cacheKey;
    
        //取倒数20个
        $cacheVal = Yii::$app->redis->ZREVRANGE($cacheKey, -50, -1);
        $_logs['$cacheVal'] = $cacheVal;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get '.json_encode($_logs));
    
        return $cacheVal;
    }
    
    //统计结果的id, 防止出现结果偏移(本作业结果是其他作业结果)的情况
    public function checkHistoryRepeat($dataId, $dataStat)
    {
        $_logs = ['$dataId' => $dataId, '$dataStat' => $dataStat];
    
        $data = $this->getUserHistory();
        $_logs['$data'] = $data;
    
        $isRepeat = false;
        if ($data)
        {
            foreach ($data as $dataItem)
            {
                $dataItem_ = unserialize($dataItem);
                if (empty($dataItem_) || !is_array($dataItem_))
                {
                    continue;
                }
                $dataId_ = key($dataItem_);
                $dataStat_ = reset($dataItem_);
                $_logs['$dataId_'] = $dataId_;
                $_logs['$dataStat_'] = $dataStat_;
                
                if (empty($dataStat_) || !is_array($dataStat_))
                {
                    continue;
                }
    
                if ($dataId_ != $dataId)
                {
                    $repeatIds = array_intersect($dataStat_, $dataStat);
                    $_logs['$repeatIds'] = $repeatIds;
    
                    if ($repeatIds)
                    {
                        $isRepeat = true;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $repeatIds '.json_encode($_logs));
                        break;
                    }
                }
            }
        }
    
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $isRepeat;
    }
    
    public function fetch($dataId, $userId, $dataCount, $dataSort = 0, $clearReceived = true)
    {
        $_logs = ['$dataId' => $dataId, '$userId' => $userId, '$dataCount' => $dataCount];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        //校验是否有权限
        $checkPermission = $this->checkPermission();
        $_logs['$checkPermission'] = $checkPermission;
        if ($checkPermission['error'])
        {
            $_logs['$checkPermission'] = $checkPermission;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkPermission error '.json_encode($_logs));
            return FormatHelper::result('', 'error', $checkPermission['message']);
        }
        
        //场景
        $scene = $this->getUserScene($dataId, $userId);
        $_logs['$scene'] = $scene;
        
        //获取用户作业数据
        $userCache = $this->getUserCache($scene);
        $_logs['$userCache'] = $userCache;
        if (!$userCache)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache empty '.json_encode($_logs));
            
            //尝试清除已领取但并未过期的作业
            //清除已领取
            if ($clearReceived) {
                $result = $this->clearReceived();
                if ($result['error'])
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fetch error '.json_encode($_logs));
                    return FormatHelper::result('', $result['error'], $result['message']);
                }
            }else{
                //尝试清除已领取但并未过期的作业
                $result = $this->clearReceived(true);
                if ($result['error'])
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fetch error '.json_encode($_logs));
                    return FormatHelper::result('', $result['error'], $result['message']);
                }
                //若有已领取scene则提示
                if (!empty($result['data']['has_scene'])) {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_received_remain error '.json_encode($_logs));
                    return FormatHelper::result([
                        'op' => 'clear_received',
                        'msg' => yii::t('app', 'task_received_remain')
                    ]);
                }
            }
            
            //获取数据
            $result = $this->fetchData($dataId, $userId, $dataCount, $dataSort);
            $_logs['fetchData$result'] = $result;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fetchData '.json_encode($_logs));
            if ($result['error'])
            {
                $_logs['fetchData$result'] = $result;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fetchData error '.json_encode($_logs));
                return FormatHelper::result('', $result['error'], $result['message']);
            }
            $cacheDataId = $result['data'];
            
            //设置缓存
            $this->setUserCache($scene, $cacheDataId);
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setUserCache '.json_encode($_logs));
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache succ '.json_encode($_logs));
            $this->refreshUserCacheTtl($scene);
            
            $cacheDataId = $userCache;
        }
        $_logs['$cacheDataId'] = $cacheDataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache data '.json_encode($_logs));
        
        $dataIds = is_array($cacheDataId) ? $cacheDataId : [$cacheDataId];
        if (count($dataIds) < 1)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_received_finished '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_received_finished'));
        }
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataIds '.json_encode($_logs));
        
        //判断批次是否执行中
        if ($this->batch['status'] != Batch::STATUS_ENABLE)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_batch_status_not_allow '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_batch_status_not_allow'));
        }
        
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime();
        $_logs['$workMaxTime'] = $workMaxTime;
        
        //查询分发分步
        $statInfo = Stat::find()
        ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
        ->asArray()->limit(1)->one();
        if (!$statInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_fetch_statNotExist '.json_encode($_logs));
            
            $stat = new Stat();
            $stat->project_id = $this->projectId;
            $stat->batch_id = $this->batchId;
            $stat->step_id = $this->stepId;
            $stat->task_id = $this->task['id'];
            $stat->created_at = time();
            $stat->save();
            
            $statInfo = $stat->getAttributes();
        }
        
        //查询模板信息
        //兼容v3方式
        if ($this->step['template_id'])
        {
            $templateId = $this->step['template_id'];
        }
        elseif ($this->project['template_id'])
        {
            $templateId = $this->project['template_id'];
        }
        else
        {
            $templateId = Category::getTemplateIdById($this->project['category_id']);
        }
        $template = Template::find()
        ->select(['id', 'config', 'status'])
        ->where(['id' => $templateId])
        ->asArray()->limit(1)->one();
        if (!$template)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_is_deleted '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'template_is_deleted'));
        }
        if (!empty($template['config']))
        {
            $template['config'] = JsonHelper::json_decode_all($template['config']);
        }
        
        $list = [];
        foreach ($dataIds as $dataId)
        {
            $_logs['for.$dataId'] = $dataId;
            
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_fetch_batchNotEffect '.json_encode($_logs));
                continue;
            }
            
            $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
            
            if ($data['batch_id'] != $this->batchId)
            {
                //清除用户执行的缓存, 不回退数据, 销毁数据
                $this->clearUserCache($scene, $dataId);
                
                $_logs['$data.batch_id'] = $data['batch_id'];
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_fetch_batchNotAllow '.json_encode($_logs));
                continue;
            }
            
            //查询作业的状态
            $work = Work::find()
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
            $_logs['$work'] = $work;
            
            if (!$work)
            {
                //清除用户执行的缓存, 不回退数据, 销毁数据
                $this->clearUserCache($scene, $dataId);
                
                $_logs['$data.batch_id'] = $data['batch_id'];
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_fetch_workNotAllow '.json_encode($_logs));
                continue;
            }
            
            //保证当前item当前分步有效状态的一定是当前用户
            if ($work['user_id'] && $work['user_id'] != $this->userId)
            {
            	//清除用户执行的缓存, 不回退数据, 销毁数据
            	$this->clearUserCache($scene, $dataId);
            	
            	Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Work not current user '.json_encode($_logs));
            	continue;
            }
            
            if (!in_array($work['status'], [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
            {
                //清除用户执行的缓存, 不回退数据, 销毁数据
                $this->clearUserCache($scene, $dataId);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Work status error '.json_encode($_logs));
                continue;
            }
            
            //--------------------------
            
            //查询结果, 用于回显
            $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
            if (!$dataResult)
            {
            	//清除用户执行的缓存, 不回退数据, 销毁数据
            	$this->clearUserCache($scene, $dataId);
            	
            	Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' DataResult miss '.json_encode($_logs));
            	continue;
            }
            
            //解析data
            $dataResult['data'] = JsonHelper::json_decode_all($dataResult['data']);
            if (!empty($dataResult['data']) && is_array($dataResult['data']))
            {
                $dataResultDdata = array();
                foreach ($dataResult['data'] as $k => $v)
                {
                    $dataResultDdata[strtolower($k)] = $v;
                }
                $dataResult['data'] = $dataResultDdata;
            }
            
            //解析result
            if (!empty($dataResult['result']))
            {
                $dataResult['result'] = JsonHelper::json_decode_all($dataResult['result']);
            }
            
            //ai辅助
            if (!empty($dataResult['ai_result']))
            {
                $dataResult['ai_result'] = JsonHelper::json_decode_all($dataResult['ai_result']);
            }
            
            if (!empty($dataResult['ai_result']) && empty($dataResult['result']))
            {
                $dataResult['result'] = $dataResult['ai_result'];
            }
            
            //更新开始时间
            $attributes = array(
                'user_id' => $this->userId,
                'status' => Work::STATUS_RECEIVED,
                //'type' => Work::TYPE_EXECUTE,
                'start_time' => time(),
                'delay_time' => $workMaxTime,
                'updated_at' => time()
            );
            Work::updateAll($attributes, ['id' => $work['id']]);
            
            $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
            if (!$workResult)
            {
                $workResult = new WorkResult();
                $workResult->work_id = $work['id'];
                $workResult->result = '';
                $workResult->feedback = '';
                $workResult->save();
                
                $workResult = $workResult->getAttributes();
            }
            
            //解析result
            if (!empty($workResult['result']))
            {
                $workResult['result'] = JsonHelper::json_decode_all($workResult['result']);
            }
            
            //因暂存的情况, workresult的版本比dataresult的版本高, 需要用workresult覆盖dataresult
            if (!empty($workResult['result']['version']) && !empty($dataResult['result']['version']) && $workResult['result']['version'] > $dataResult['result']['version'])
            {
                $dataResult['result'] = DataResult::mergeResult($dataResult['result'], $workResult['result']);
            }
            elseif (!empty($workResult['result']['version']) && empty($dataResult['result']['version']))
            {
                $dataResult['result'] = DataResult::mergeResult($dataResult['result'], $workResult['result']);
            }
            
            //查询是否有多个父分步
            $parentWorks = [];
            $parentWorkResults = [];
            $parentWorkUserIds = [];//父分步的作业员
            if ($this->stepParentIds)
            {
                $parentWorkIds = Work::find()
                ->select(['id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'data_id' => $dataId])
                ->andWhere(['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH]])
                ->andWhere(['in', 'step_id', $this->stepParentIds])
                ->asArray()->column();
            
                if (!$parentWorkIds)
                {
                    //清除用户执行的缓存, 回退数据
                    $this->clearUserCache($scene, $dataId);
                    
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' parent_work_empty cleardata '.json_encode($_logs));
                    continue;
                }
                
                $parentWorks = Work::find()
                ->andWhere(['in', 'id', $parentWorkIds])
                ->orderBy(['id' => SORT_ASC])
                ->with(['user'])
                ->asArray()->all();
                
                $parentWorkResults = WorkResult::find()
                ->where(['in', 'work_id', $parentWorkIds])
                ->asArray()->all();
                
                foreach ($parentWorks as $v)
                {
                    $parentWorkUserIds[] = $v['user_id'];
                }
            }
            $_logs['userId'] = $this->userId;
            $_logs['$parentWorkUserIds'] = $parentWorkUserIds;
            
            //是否启用不可审核自己作业的功能
            if (!empty(Setting::getSetting('open_audit_self')))
            {
                if (in_array($this->userId, $parentWorkUserIds))
                {
                    //清除用户执行的缓存, 回退数据
                    $this->clearUserCache($scene, $dataId, true);
                     
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' open_audit_self cleardata '.json_encode($_logs));
                    continue;
                }
            }
            
            //查询同任务上一次的作业结果
            $lastWorks = [];
            $lastWorkResults = [];
            if (in_array($this->step['type'], [Step::TYPE_PRODUCE]))
            {
                
            }
            elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                //是否开启审核自己作业的功能
                if (!empty(Setting::getSetting('open_audit_self')))
                {
                    if (in_array($this->userId, $parentWorkUserIds))
                    {
                        //清除用户执行的缓存, 不回退数据, 销毁数据
                        $this->clearUserCache($scene, $dataId, true);
                         
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' open_audit_self cleardata '.json_encode($_logs));
                        continue;
                    }
                }
                
                $lastWorkIds = Work::find()
                ->select(['id'])
                ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'data_id' => $dataId])
                ->andWhere(['status' => Work::STATUS_DELETED, 'step_id' => $this->stepId])
                ->andWhere(['type' => Work::TYPE_AUDITREFUSE])
                ->orderBy(['id' => SORT_DESC])->limit(1)
                ->asArray()->column();
            
                if ($lastWorkIds)
                {
                    $lastWorks = Work::find()
                    ->andWhere(['in', 'id', $lastWorkIds])
                    ->orderBy(['id' => SORT_ASC])
                    ->with(['user'])
                    ->asArray()->all();
            
                    $lastWorkResults = WorkResult::find()
                    ->where(['in', 'work_id', $lastWorkIds])
                    ->asArray()->all();
                }
            }
            
            //添加作业记录, 排除主动领取的情况,如redo等
            if ($work['status'] != Work::STATUS_RECEIVED)
            {
                $workRecord = new WorkRecord();
                $workRecord->project_id = $this->projectId;
                $workRecord->work_id = $work['id'];
                $workRecord->data_id = $dataId;
                $workRecord->batch_id = $work['batch_id'];
                $workRecord->step_id = $work['step_id'];
                $workRecord->task_id = $this->task['id'];
                $workRecord->type = WorkRecord::TYPE_FETCH;
                $workRecord->after_user_id = $this->userId;
                $workRecord->after_work_status = Work::STATUS_RECEIVED;
                $workRecord->before_user_id = $parentWorkUserIds ? reset($parentWorkUserIds) : 0;
                $workRecord->before_work_status = $work['status'];
                $workRecord->created_at = time();
                $workRecord->updated_at = time();
                $workRecord->save();
            }
            
            //------------------------------
            
            //查询模板
            $list[] = [
                'data' => $data,
                'dataResult' => $dataResult,
                'work' => $work,
                'workResult' => $workResult,
                'parentWorks' => $parentWorks,
                'parentWorkResults' => $parentWorkResults,
                'lastWorks' => $lastWorks,
                'lastWorkResults' => $lastWorkResults
            ];
        }

        //父分步的作业者及统计列表
        $parentWorkUsers = [];
        if ($this->stepParentIds)
        {
            $parentWorkUsers = Work::find()
            ->select(['user_id', 'count(user_id) as count'])
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId])
            ->andWhere(['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH]])
            ->andWhere(['in', 'step_id', $this->stepParentIds])
            ->groupBy(['user_id'])
            ->asArray()->all();
            
            $parentUserIds = [];
            if ($parentWorkUsers)
            {
                foreach ($parentWorkUsers as $parentUser_)
                {
                    $parentUserIds[] = $parentUser_['user_id'];
                }
            }

            $parentUsers = [];
            if ($parentUserIds)
            {
                $parentUserList_ = User::find()->select(['id', 'email'])->where(['in', 'id', $parentUserIds])->asArray()->all();
                foreach ($parentUserList_ as $user_)
                {
                    $parentUsers[$user_['id']] = $user_;
                }
            }
            
            if ($parentWorkUsers)
            {
                foreach ($parentWorkUsers as $k => $parentUser_)
                {
                    $parentWorkUsers[$k]['user'] = $parentUsers && !empty($parentUsers[$parentUser_['user_id']]) ? $parentUsers[$parentUser_['user_id']] : '';
                }
            }
        }
        
        //加入view
        $resultData = [];
        $resultData['count'] = count($list);
        $resultData['list'] = $list;
        $resultData['timeout'] = $this->getUserCacheTtl($scene);
        $resultData['category'] = $this->category;
        $resultData['template'] = $template;
        $resultData['batch'] = $this->batch;
        $resultData['step'] = $this->step;
        $resultData['task'] = $this->task;
        $resultData['stat'] = $statInfo;
        $resultData['time'] = time();
        $resultData['parentWorkUsers'] = $parentWorkUsers;
        if (!empty($this->step['condition']))
        {
            $resultData['audit_rate'] = ($statInfo['amount'] > 0 ? floor(($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count']) / $statInfo['amount']*100) : 0).'%';
            $resultData['pass_rate'] = ($statInfo['work_count'] > 0 ? floor($statInfo['allow_count'] / ($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count'])*100) : 0).'%';
        }
        else
        {
            $resultData['audit_rate'] = ($this->batch['amount'] > 0 ? floor(($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count']) / $this->batch['amount']*100) : 0).'%';
            $resultData['pass_rate'] = ($statInfo['work_count'] > 0 ? floor($statInfo['allow_count'] / ($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count'])*100) : 0).'%';
        }
        
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($resultData);
    }
    
    /**
     * 作业员提交作业结果
     * 
     * 
     * @param int $this->batchId
     * @param int $dataId
     * @param int $postData
     * @return string[]|array[]|int[][]|string[][]|array[][]|string[]
     */
    public function submit($dataId, $userId, $postData)
    {
        $_logs = ['$dataId' => $dataId, '$userId' => $userId];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        if (empty($postData) || !is_array($postData))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
            return FormatHelper::result('', 'data_do_datanotexist', yii::t('app', 'task_submit_param_error'));
        }
        
        //-------------------------------------------------
        
        $checkPermission = $this->checkPermission();
        $_logs['$checkPermission'] = $checkPermission;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkPermission '.json_encode($_logs));
        if ($checkPermission['error'])
        {
            $_logs['$checkPermission'] = $checkPermission;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkPermission fail '.json_encode($_logs));
            return FormatHelper::result('', $checkPermission['error'], $checkPermission['message']);
        }

        //场景
        $scene = $this->getUserScene($dataId, $userId);
        $_logs['$scene'] = $scene;
        
        //获取用户临时作业数据
        $userCache = $this->getUserCache($scene);
        $_logs['$userCache'] = $userCache;
        if (!$userCache)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_usercache_empty error '.json_encode($_logs));
            return FormatHelper::result('', 'task_usercache_empty', yii::t('app', Yii::t('app', 'task_usercache_empty')));
        }
        $cacheDataId = $userCache;
        $_logs['$cacheDataId'] = $cacheDataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache '.json_encode($_logs));
        
        //----------------------------------------------
        
        $dataIds = is_array($cacheDataId) ? $cacheDataId : [$cacheDataId];
        $_logs['$dataIds'] = $dataIds;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataIds '.json_encode($_logs));
        
        //------------------------------------------------

        $resultData = [];
        foreach ($postData['result'] as $dataId => $result_)
        {
            $_logs['loop.$dataId'] = $dataId;
            //$_logs['loop.$result_'] = $result_;
            
            //结果值
            $resultData[$dataId] = [];
            
            //判断是否在已领取作业池
            if (!in_array($dataId, $dataIds))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_received '.json_encode($_logs));
                //return FormatHelper::result('', 'task_data_not_received', yii::t('app', 'task_data_not_received'));
                continue;
            }
            
            //有item的情况
            $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
            if (!$data)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_exist '.json_encode($_logs));
                return FormatHelper::result('', 'task_data_not_exist', yii::t('app', 'task_data_not_exist'));
            }
            
            if ($data['project_id'] != $this->project['id'])
            {
                //清除用户执行的缓存, 回退数据
                $this->clearUserCache($scene, $dataId);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_data_invalid '.json_encode($_logs));
                return FormatHelper::result('', 'task_submit_data_invalid', yii::t('app', 'task_submit_data_invalid'));
            }
            
            //查询数据结果
            $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
            if (!$dataResult)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_exist '.json_encode($_logs));
                return FormatHelper::result('', 'task_data_not_exist', yii::t('app', 'task_data_not_exist'));
            }
            
            //查询分发作业
            $work = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
            $_logs['$work'] = $work;
            
            if (!$work)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_work_not_found '.json_encode($_logs));
                return FormatHelper::result('', 'task_work_not_found', yii::t('app', 'task_work_not_found'));
            }
            
            //允许已领取的或者执行中的作业提交
            if (!in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_work_status_not_allow '.json_encode($_logs));
                return FormatHelper::result('', 'task_work_status_not_allow', yii::t('app', 'task_work_status_not_allow'));
            }
            
            if ($work['user_id'] != $this->userId)
            {
                $_logs['user_id'] = $work['user_id'];
                
                //清除用户执行的缓存, 不回退数据, 销毁数据
                $this->clearUserCache($scene, $dataId);
                
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_user_not_receive '.json_encode($_logs));
                return FormatHelper::result('', 'task_user_not_receive', Yii::t('app', 'task_user_not_receive'));
            }
            
            //查询分发结果
            $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
            //没有建立分发结果, 则创建
            if (!$workResult)
            {
                $workResult = new WorkResult();
                $workResult->work_id = $work['id'];
                $workResult->result = '';
                $workResult->feedback = '';
                $workResult->save();
            }
            
            $workTime = 0;
            if ($work['start_time'] > 0)
            {
                $workTime = (time() - $work['start_time']);
            }
            
            //-----------------------------------
            //业务开始
            //-----------------------------------
            
            if ($this->step['type'] == Step::TYPE_PRODUCE)
            {
                //有源数据的情况,疑难作业,不作为有效提交
                if ($this->category['type'] == Category::TYPE_LABEL && !empty($result_['is_difficult']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type label,is_difficult '.json_encode($_logs));
                    
                    //清除用户执行的缓存, 不回退数据, 销毁数据
                    $this->clearUserCache($scene, $dataId);
                    
                    //统计结果
                    list($stat_, $statUser_, $statIds_) = ProjectHandler::statResult($result_);
                    
                    //校验作业结果偏移的问题
                    if ($statIds_)
                    {
                        if ($this->checkHistoryRepeat($dataId, $statIds_))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkHistoryRepeat Yes!!! '.json_encode($_logs));
                            return FormatHelper::result('', 'error', yii::t('app', 'task_result_repeat'));
                        }
                    
                        $this->addUserHistory($dataId, $statIds_);
                    }
                    
                    //更新分发作业状态为已执行
                    $attributes = [
                        'status' => Work::STATUS_DIFFICULT,
                        'type' => Work::TYPE_DIFFICULT,
                        'end_time' => time(),
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work['id']]);

                    //更新作业记录
                    $attributes = [
                        'result' => json_encode($result_)
                    ];
                    WorkResult::updateAll($attributes, ['id' => $workResult['id']]);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work['batch_id'];
                    $workRecord->step_id = $work['step_id'];
                    $workRecord->task_id = $this->task['id'];
                    $workRecord->type = WorkRecord::TYPE_DIFFICULT;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_DIFFICULT;
                    $workRecord->before_user_id = $work['user_id'];
                    $workRecord->before_work_status = $work['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    //--------------------------------
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 0,
                        'difficult_count' => 1,
                    ];
                    //挂起作业,增加挂起作业已修正数
                    if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                    {
                        //$counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
                    {
                        $counters['refuse_revise_count'] = 1;
                        $counters['refuse_sumited_count'] = -1;
                    }
                    Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 0,
                        'join_count' => 0,
                        'difficult_count' => 1,
                    ];
                    //挂起作业,增加挂起作业已修正数
                    if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                    {
                        //$counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
                    
                    $resultData[$dataId] = [
                        'work_count' => 0,
                        'label_count' => 0,
                        'point_count' => 0,
                        'sharepoint_count' => 0,
                        'label_time' => 0
                    ];
                }
                //有源数据的情况, 比如:标注类,
                elseif ($this->category['type'] == Category::TYPE_LABEL)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type label '.json_encode($_logs));
                    
                    //清除用户执行的缓存, 不回退数据, 销毁数据
                    $this->clearUserCache($scene, $dataId);
                    
                    //统计结果
                    list($stat_, $statUser_, $statIds_) = ProjectHandler::statResult($result_);
                    
                    //校验作业结果偏移的问题
                    if ($statIds_)
                    {
                        if ($this->checkHistoryRepeat($dataId, $statIds_))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkHistoryRepeat Yes!!! '.json_encode($_logs));
                            return FormatHelper::result('', 'error', yii::t('app', 'task_result_repeat'));
                        }
                    
                        $this->addUserHistory($dataId, $statIds_);
                    }
                    
                    //更新分发作业状态为已执行
                    $attributes = [
                        'submit_count' => $work['submit_count'] + 1,
                        'status' => Work::STATUS_SUBMITED,
                        'end_time' => time(),
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work['id']]);

                    //更新作业记录
                    $attributes = [
                        'result' => json_encode($result_)
                    ];
                    WorkResult::updateAll($attributes, ['id' => $workResult['id']]);

                    //标注绩效统计
                    if(!empty($statUser_))
                    {
                        foreach($statUser_ as $statUserId_ => $statUserResult_)
                        {
                            foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                            {
                                foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                {
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'data_id' => $dataId,
                                        'work_id' => $work['id'],
                                        'user_id' => $statUserId_,
                                        'type' => $statUserResultType__,
                                        'action' => $statUserResultAction_
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultWork::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'user_id' => $statUserId_,
                                        'type' => $statUserResultType__,
                                        'action' => $statUserResultAction_
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultUser::updateCounter($where, $statCounters);
                                }
                            }
                        }
                    }
                    if(!empty($stat_))
                    {
                        foreach($stat_ as $statResultAction_ => $statResult__)
                        {
                            foreach ($statResult__ as $statResultType__ => $statResultVal__)
                            {
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'type' => $statResultType__,
                                    'action' => $statResultAction_
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResultData::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'type' => $statResultType__,
                                    'action' => $statResultAction_
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResult::updateCounter($where, $statCounters);
                            }
                        }
                    }
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work['batch_id'];
                    $workRecord->step_id = $work['step_id'];
                    $workRecord->task_id = $this->task['id'];
                    $workRecord->type = WorkRecord::TYPE_SUBMIT;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_SUBMITED;
                    $workRecord->before_user_id = $work['user_id'];
                    $workRecord->before_work_status = $work['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 1,
                        'submit_count' => 1
                    ];
                    //挂起作业,增加挂起作业已修正数
                    if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                    {
                        //$counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 1,
                        'submit_count' => 1,
                        'join_count' => 1
                    ];
                    //挂起作业,增加挂起作业已修正数
                    if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                    {
                        //$counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
                    //--------------------------------
                    
                    $resultData[$dataId] = [
                        'work_count' => 1
                    ];
                }
                //没有数据源的情况, 比如采集类
                elseif ($this->category['type'] == Category::TYPE_COLLECTION)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type collection '.json_encode($_logs));
                    
                    //清除用户执行的缓存, 不回退数据, 销毁数据
                    $this->clearUserCache($scene, $dataId);
                    
                    //更新分发作业状态为已执行
                    $attributes = [
                        'submit_count' => $work['submit_count'] + 1,
                        'status' => Work::STATUS_SUBMITED,
                        'end_time' => time(),
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work['id']]);
                    
                    //更新作业记录
                    $attributes = [
                        'result' => json_encode($result_),
                    ];
                    WorkResult::updateAll($attributes, ['id' => $workResult['id']]);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work['batch_id'];
                    $workRecord->step_id = $work['step_id'];
                    $workRecord->task_id = $this->task['id'];
                    $workRecord->type = WorkRecord::TYPE_SUBMIT;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_SUBMITED;
                    $workRecord->before_user_id = $work['user_id'];
                    $workRecord->before_work_status = $work['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    //--------------------------------
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 1,
                        'submit_count' => 1,
                        'label_count' => 0
                    ];
                    //挂起作业,增加挂起作业已修正数
                    if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                    {
                        //$counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 1,
                        'submit_count' => 1,
                        'join_count' => 1,
                    ];
                    //挂起作业,增加挂起作业已修正数
                    if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                    {
                        //$counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
                    
                    //--------------------------------
                    
                    $resultData[$dataId] = [
                        'work_count' => 1,
                        'label_count' => 0,
                        'point_count' => 0,
                        'sharepoint_count' => 0,
                    ];
                }
                elseif ($this->category['type'] == Category::TYPE_EXTERNAL)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type external '.json_encode($_logs));
                    
                    //清除用户执行的缓存, 不回退数据, 销毁数据
                    $this->clearUserCache($scene, $dataId);
                    
                    //更新分发作业状态为已执行
                    $attributes = [
                        'submit_count' => $work['submit_count'] + 1,
                        'status' => Work::STATUS_SUBMITED,
                        'end_time' => time(),
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work['id']]);
                    
                    //更新作业记录
                    $attributes = [
                        'result' => json_encode($result_),
                    ];
                    WorkResult::updateAll($attributes, ['id' => $workResult['id']]);
                    
                    //合并数据结果
                    DataResult::mergeResult($dataId, $result_);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work['batch_id'];
                    $workRecord->step_id = $work['step_id'];
                    $workRecord->task_id = $this->task['id'];
                    $workRecord->type = WorkRecord::TYPE_SUBMIT;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_SUBMITED;
                    $workRecord->before_user_id = $work['user_id'];
                    $workRecord->before_work_status = $work['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    //--------------------------------
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 1,
                        'submit_count' => 1,
                        'label_count' => 0
                    ];
                    Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
                    
                    $counters = [
                        'work_time' => $workTime,
                        'work_count' => 1,
                        'submit_count' => 1,
                        'join_count' => 1,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
                    
                    //--------------------------------
                    
                    $resultData[$dataId] = [
                        'work_count' => 1,
                    ];
                }
                else
                {
                    //清除用户执行的缓存, 不回退数据, 销毁数据
                    $this->clearUserCache($scene, $dataId);
                    
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_categoryTypeNotExist '.json_encode($_logs));
                    return FormatHelper::result('', 'task_submit_categoryTypeNotExist', Yii::t('app', 'task_submit_categoryTypeNotExist'));
                }
                
            }
            //审核和质检的疑难作业
            elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]) && !empty($result_['is_difficult']))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type label,is_difficult '.json_encode($_logs));
            
                //清除用户执行的缓存, 不回退数据, 销毁数据
                $this->clearUserCache($scene, $dataId);
            
                //更新分发作业状态为已执行
                $attributes = [
                    'status' => Work::STATUS_DIFFICULT,
                    'type' => Work::TYPE_DIFFICULT,
                    'end_time' => time(),
                    'updated_at' => time(),
                ];
                Work::updateAll($attributes, ['id' => $work['id']]);
            
                //更新作业记录
                $attributes = [
                    'result' => json_encode($result_),
                ];
                WorkResult::updateAll($attributes, ['id' => $workResult['id']]);
            
                //添加作业记录
                $workRecord = new WorkRecord();
                $workRecord->project_id = $this->projectId;
                $workRecord->work_id = $work['id'];
                $workRecord->data_id = $dataId;
                $workRecord->batch_id = $work['batch_id'];
                $workRecord->step_id = $work['step_id'];
                $workRecord->task_id = $this->task['id'];
                $workRecord->type = WorkRecord::TYPE_DIFFICULT;
                $workRecord->after_user_id = $this->userId;
                $workRecord->after_work_status = Work::TYPE_DIFFICULT;
                $workRecord->before_user_id = $work['user_id'];
                $workRecord->before_work_status = $work['status'];
                $workRecord->created_at = time();
                $workRecord->updated_at = time();
                $workRecord->save();
                
                //--------------------------------
            
                $counters = [
                    'work_time' => 0,
                    'work_count' => 0,
                    'difficult_count' => 1,
                ];
                //挂起作业,增加挂起作业已修正数
                if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                {
                    //$counters['difficult_revise_count'] = 1;
                }
                if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                {
                    $counters['refused_revise_count'] = 1;
                }
                if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                {
                    $counters['refuse_revise_count'] = 1;
                }
                if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
                {
                    $counters['refuse_revise_count'] = 1;
                }
                Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
            
                $counters = [
                    'work_time' => 0,
                    'work_count' => 0,
                    'join_count' => 0,
                    'difficult_count' => 1,
                ];
                //挂起作业,增加挂起作业已修正数
                if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
                {
                    //$counters['difficult_revise_count'] = 1;
                }
                if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                {
                    $counters['refused_revise_count'] = 1;
                }
                if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
                {
                    $counters['refuse_revise_count'] = 1;
                }
                if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
                {
                    $counters['refuse_revise_count'] = 1;
                }
                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
            
                $resultData[$dataId] = [
                    'work_count' => 0
                ];
            }
            //审核和质检的正常操作
            elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is type audit '.json_encode($_logs));
                
                if (empty($result_['verify']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_submit_param_error', yii::t('app', 'task_submit_param_error'));
                }
                if (!isset($result_['verify']['verify']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_submit_param_error', yii::t('app', 'task_submit_param_error'));
                }
                
                $verify = (int)$result_['verify']['verify'];
                $feedback = (string)(isset($result_['verify']['feedback']) ? $result_['verify']['feedback'] : '');
                $correctWorkId = (string)(isset($result_['verify']['correct_work_id']) ? $result_['verify']['correct_work_id'] : '');
                $correctWorkIds = FormatHelper::param_int_to_array($correctWorkId);
                
                //驳回原因不能超过1000字符
                if (strlen($feedback) > 1000)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_feedback_toolong '.json_encode($_logs));
                    return FormatHelper::result('', 'task_submit_feedback_toolong', yii::t('app', 'task_submit_feedback_toolong'));
                }
                
                //必须是驳回, 通过, 重置
                if (!in_array($verify, [Work::AUDIT_REFUSE, Work::AUDIT_ALLOW, Work::AUDIT_RESET]))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_submit_param_error', yii::t('app', 'task_submit_param_error'));
                }
                
                //通过时, 必须指定通过的工作id
                if ($verify === Work::AUDIT_ALLOW && empty($correctWorkIds))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
                    return FormatHelper::result('', 'task_submit_audit_error', yii::t('app', 'task_submit_param_error'));
                }
                
                //更新分发作业状态为已执行
                $attributes = [
                    'submit_count' => $work['submit_count'] + 1,
                    'status' => Work::STATUS_SUBMITED,
                    'end_time' => time(),
                    'updated_at' => time()
                ];
                Work::updateAll($attributes, ['id' => $work['id']]);
                
                //更新作业记录
                $attributes = [
                    'result' => json_encode($result_),
                    'feedback' => $feedback
                ];
                WorkResult::updateAll($attributes, ['id' => $workResult['id']]);
                
                //添加作业记录
                $workRecord = new WorkRecord();
                $workRecord->project_id = $this->projectId;
                $workRecord->work_id = $work['id'];
                $workRecord->data_id = $dataId;
                $workRecord->batch_id = $work['batch_id'];
                $workRecord->step_id = $work['step_id'];
                $workRecord->task_id = $this->task['id'];
                $workRecord->type = WorkRecord::TYPE_SUBMIT;
                $workRecord->after_user_id = $this->userId;
                $workRecord->after_work_status = Work::STATUS_SUBMITED;
                $workRecord->before_user_id = $work['user_id'];
                $workRecord->before_work_status = $work['status'];
                $workRecord->created_at = time();
                $workRecord->updated_at = time();
                $workRecord->save();
                
                //--------------------------------------------------
                
                $resultData[$dataId] = [
                    'work_count' => 1,
                ];
            }
            
            //-------------------------------------------------------------------------------
            
            //裁决:所有兄弟工作都完成, 则执行结算, 若为执行作业, 计算正确率, 子工序初始化
            $stepParentIds = $this->getStepParentIds();
            $stepBrotherIds = $this->getStepBrotherIds();
            $stepChildIds = $this->getStepChildIds();
            $_logs['$stepParentIds'] = $stepParentIds;
            $_logs['$stepBrotherIds'] = $stepBrotherIds;
            $_logs['$stepChildIds'] = $stepChildIds;
            
            //查询已提交或已完成兄弟工作
            $stepBrotherWorkIds = Work::find()
            ->select(['id'])
            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'data_id' => $dataId])
            ->andWhere(['in', 'step_id', $stepBrotherIds])
            ->andWhere(['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH]])
            ->asArray()->column();
            $_logs['$stepBrotherWorkIds'] = count($stepBrotherWorkIds);
        
            //判断所有兄弟工序都已提交或已完成数
            if (count($stepBrotherWorkIds) >= count($stepBrotherIds))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' step brother check ok '.json_encode($_logs));
                
                //查询兄弟工作的结果, 合并结果, 计算正确率
                $stepBrotherWorkResults = WorkResult::find()->select(['result'])->where(['in', 'work_id', $stepBrotherWorkIds])->asArray()->column();
                $_logs['$stepBrotherWorkResults'] = ArrayHelper::var_desc($stepBrotherWorkResults);
                
                //计算执行最终结果和正确率
                list($correctRate, $correctResult) = ProjectHandler::correctRate($stepBrotherWorkResults);
                $_logs['$correctRate'] = $correctRate;
                $_logs['$correctResult'] = ArrayHelper::var_desc($correctResult);
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' correctRate '.json_encode($_logs));

                //合并数据结果
                DataResult::mergeResult($dataId, $correctResult);
                
                //若为执行
                if ($this->step['type'] == Step::TYPE_PRODUCE)
                {
                    //处理子工作
                    foreach ($stepChildIds as $stepChildId)
                    {
                        if (!Step::checkCondition($stepChildId, $work['id']))
                        {
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition not allow '.json_encode($_logs));
                        }
                        else
                        {
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition count match succ '.json_encode($_logs));
                    
                            //合并正确率, 初始化子工序
                            $isRefuseSubmit = !empty($work['is_refused']) ? true : false;
                            Work::mergeCorrectRate($this->projectId, $this->batchId, $stepChildId, $dataId, $correctRate, $isRefuseSubmit);
                        }
                    }
                    
                }
                //审核和质检的正常操作
                elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
                {
                    if (StringHelper::is_json($correctResult))
                    {
                        $correctResult = JsonHelper::json_decode_all($correctResult);
                    }
                    if (!empty($correctResult['result']))
                    {
                        $correctResult = $correctResult['result'];
                    }
                    $_logs['$correctResult.new'] = ArrayHelper::var_desc($correctResult);
                    
                    if (!isset($correctResult['verify']['verify']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
                        return FormatHelper::result('', 'task_submit_audit_error', yii::t('app', 'task_submit_param_error'));
                    }
                    $verify = (int)$correctResult['verify']['verify'];
                    $_logs['$verify'] = $verify;
                    
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' audit '.json_encode($_logs));
                    
                    //驳回
                    if ($verify == Work::AUDIT_REFUSE)
                    {
                        //清除用户执行的缓存, 回退数据
                        $this->clearUserCache($scene, $dataId);
                    
                        //拒绝审核
                        $auditResult = $this->auditRefuse($dataId, $feedback);
                        if (!empty($auditResult['error']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auditRefuse error '.json_encode($_logs));
                            return $auditResult;
                        }
                    }
                    //通过
                    elseif ($verify == Work::AUDIT_ALLOW)
                    {
                        if (empty($correctResult['verify']['correct_work_id']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
                            return FormatHelper::result('', 'task_submit_audit_error', yii::t('app', 'task_submit_param_error'));
                        }
                        $correctWorkIds = FormatHelper::param_int_to_array($correctResult['verify']['correct_work_id']);
                        $_logs['$correctWorkIds'] = $correctWorkIds;
                        
                        //清除用户执行的缓存, 回退数据
                        $this->clearUserCache($scene, $dataId);
                    
                        //通过审核
                        $auditResult = $this->auditAllow($dataId, $correctWorkIds);
                        if (!empty($auditResult['error']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auditAllow error '.json_encode($_logs));
                            return $auditResult;
                        }
                        
                        //处理子工作
                        foreach ($stepChildIds as $stepChildId)
                        {
                            if (!Step::checkCondition($stepChildId, $work['id']))
                            {
                                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition not allow '.json_encode($_logs));
                            }
                            else
                            {
                                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition count match succ '.json_encode($_logs));
                        
                                //合并正确率, 初始化子工序
                                $isRefuseSubmit = !empty($work['is_refused']) ? true : false;
                                Work::mergeCorrectRate($this->projectId, $this->batchId, $stepChildId, $dataId, $correctRate, $isRefuseSubmit);
                            }
                        }
                        
                    }
                    //重置
                    elseif ($verify == Work::AUDIT_RESET)
                    {
                        //清除用户执行的缓存, 回退数据
                        $this->clearUserCache($scene, $dataId);
                    
                        //重置作业
                        $auditResult = $this->auditReset($dataId, $feedback);
                        if (!empty($auditResult['error']))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auditReset error '.json_encode($_logs));
                            return $auditResult;
                        }
                    }
                    else
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_param_error '.json_encode($_logs));
                        return FormatHelper::result('', 'task_submit_param_error', yii::t('app', 'task_submit_param_error'));
                    }
                }
                
            }
        }
        
        //查询分发分步
        $statInfo = Stat::find()
        ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
        ->asArray()->limit(1)->one();
        if (!$statInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat not exist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_stat_not_found'));
        }
        
        if (!empty($this->step['condition']))
        {
            $resultData['audit_rate'] = ($statInfo['amount'] > 0 ? floor(($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count']) / $statInfo['amount']*100) : 0).'%';
            $resultData['pass_rate'] = ($statInfo['work_count'] > 0 ? floor($statInfo['allow_count'] / ($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count'])*100) : 0).'%';
        }
        else
        {
            $resultData['audit_rate'] = ($this->batch['amount'] > 0 ? floor(($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count']) / $this->batch['amount']*100) : 0).'%';
            $resultData['pass_rate'] = ($statInfo['work_count'] > 0 ? floor($statInfo['allow_count'] / ($statInfo['work_count']+$statInfo['refuse_count']+$statInfo['reset_count'])*100) : 0).'%';
        }
        
        //------------------------------------------------
        $_logs['$resultData'] = $resultData;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($resultData);
        
    }
    
    public function execute($dataId, $userId, $executeDataId)
    {
        $_logs = ['$dataId' => $dataId, '$executeDataId' => $executeDataId];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        //判断批次是否执行中
        if ($this->batch['status'] != Batch::STATUS_ENABLE)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_batch_status_not_allow '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_batch_status_not_allow'));
        }
        
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime();
        $_logs['$workMaxTime'] = $workMaxTime;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workMaxTime '.json_encode($_logs));
        
        //-----------------------------------------
        
        //场景
        $scene = $this->getUserScene($dataId, $userId);
        $_logs['$scene'] = $scene;
        
        //获取用户作业数据
        $userCache = $this->getUserCache($scene);
        $_logs['$userCache'] = $userCache;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache '.json_encode($_logs));
        if (!$userCache)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_cache_data_not_found '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_cache_data_not_found'));
        }
        $cacheDataId = $userCache;
        $_logs['$cacheDataId'] = $cacheDataId;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache data '.json_encode($_logs));
        
        $dataIds = is_array($cacheDataId) ? $cacheDataId : [$cacheDataId];
        
        //判断是否存在
        if (!in_array($executeDataId, $dataIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' executeDataId not in cache '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_execute_data_notAllow'));
        }
        
        //设置分发作业的状态
        $workList = Work::find()
        ->where(['batch_id' => $this->batchId, 'step_id' => $this->stepId])
        ->andWhere(['in', 'data_id', $dataIds])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->all();
        
        $effectiveDataIds = [];
        $receiveWorkIds = [];
        if ($workList)
        {
           foreach ($workList as $work)
           {
               $_logs['$work'] = $work;
               Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work '.json_encode($_logs));
               
               //排除因并发问题出现多条记录的情况
               if (in_array($work['data_id'], $effectiveDataIds))
               {
                   Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_work_repeat '.json_encode($_logs));
                   continue;
               }
               
               if (!in_array($work['status'], [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
               {
                   //清除用户执行的缓存, 不回退数据, 销毁数据
                   $this->clearUserCache($scene, $work['data_id']);
               
                   Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_workNotAllow '.json_encode($_logs));
                   continue;
               }
               
               //执行中的作业
               if ($work['data_id'] == $executeDataId)
               {
                   $attributes = [
                       'status' => Work::STATUS_EXECUTING,
                       //'type' => Work::TYPE_NORMAL,
                       'user_id' => $this->userId,
                       'start_time' => time(),
                       'delay_time' => $workMaxTime,
                       'updated_at' => time()
                   ];
                   Work::updateAll($attributes, ['id' => $work['id']]);
                   
                   //添加作业记录
                   $workRecord = new WorkRecord();
                   $workRecord->project_id = $this->projectId;
                   $workRecord->work_id = $work['id'];
                   $workRecord->data_id = $work['data_id'];
                   $workRecord->batch_id = $work['batch_id'];
                   $workRecord->step_id = $work['step_id'];
                   $workRecord->task_id = $this->task['id'];
                   $workRecord->type = WorkRecord::TYPE_EXECUTE;
                   $workRecord->after_user_id = $this->userId;
                   $workRecord->after_work_status = Work::STATUS_EXECUTING;
                   $workRecord->before_user_id = $work['user_id'];
                   $workRecord->before_work_status = $work['status'];
                   $workRecord->created_at = time();
                   $workRecord->updated_at = time();
                   $workRecord->save();
               }
               //已领取的作业
               else
               {
                   $receiveWorkIds[] = $work['id'];
               }
               
               $effectiveDataIds[] = $work['data_id'];
           }
        }
        
        //已领取的作业
        if ($receiveWorkIds)
        {
            $attributes = [
                'status' => Work::STATUS_RECEIVED,
                //'type' => Work::TYPE_NORMAL,
                'user_id' => $this->userId,
                'start_time' => time(),
                'delay_time' => $workMaxTime,
                'updated_at' => time()
            ];
            Work::updateAll($attributes, ['in', 'id', $receiveWorkIds]);
        }
        
        //处理无效的作业
        $notExistDataIds = array_diff($dataIds, $effectiveDataIds);
        $_logs['$notExistDataIds'] = $notExistDataIds;
        if ($notExistDataIds)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $notExistDataIds '.json_encode($_logs));
            foreach ($notExistDataIds as $dataId_)
            {
                //清除用户执行的缓存, 不回退数据, 销毁数据
                $this->clearUserCache($scene, $dataId_);
            }
        }
        
        //-----------------------------
        
        //给缓存续期
        $this->refreshUserCacheTtl($scene);
        
        //返回值
        $resultData = [];
        $resultData['timeout'] = $this->getUserCacheTtl($scene);
        $resultData['allowDataIds'] = $effectiveDataIds;
        $resultData['dataIds'] = $effectiveDataIds;
        
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($resultData);
    }
    
    /**
     * 退回已领取的作业
     * 
     */
    public function clearReceived($justCheck = false)
    {
        $_logs = [];
        $hasExistSceneCount = 0;
        $clearReceivedCount = 0;
        
        //获取本人本项目本批次本分步的所有场景
        $scenes = $this->getUserScenes();
        $_logs['$scenes'] = $scenes;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserScenes '.json_encode($_logs));
        
        if ($scenes)
        {
            foreach ($scenes as $scene)
            {
                $userCache = $this->getUserCache($scene);
                $_logs['$userCache'] = $userCache;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache '.json_encode($_logs));
                
                if (!$userCache)
                {
                    $this->clearUserCache($scene);
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $userCache empty '.json_encode($_logs));
                    continue;
                } elseif ($justCheck){
                    $hasExistSceneCount++;
                    continue;
                }
                
                $cacheDataId = $userCache;
                $_logs['$cacheDataId'] = $cacheDataId;
                
                $dataIds = is_array($cacheDataId) ? $cacheDataId : [$cacheDataId];
                
                //------------------------------------------------
                
                //清退作业
                foreach ($dataIds as $dataId_)
                {
                    $_logs['$dataId_'] = $dataId_;
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' clean dataId  '.json_encode($_logs));
                    
                    //清除用户执行的缓存, 回退数据
                    $work_ = Work::find()
                    ->where(['batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $dataId_])
                    ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
                    ->orderBy(['id' => SORT_DESC])
                    ->asArray()->limit(1)->one();
                    $_logs['$work_'] = $work_;
                
                    if (!$work_)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work not exist '.json_encode($_logs));
                        continue;
                    }
                    
                    if (!in_array($work_['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ status not allow '.json_encode($_logs));
                        continue;
                    }
                    
                    $workTime = 0;
                    $activeWorkTime = 0;
                    if ($work_['is_refused'] == Work::OPTION_REFUSED)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work  '.json_encode($_logs));
                        
                        $attributes = [
                            'status' => Work::STATUS_REFUSED,
                            'type' => Work::TYPE_AUDITREFUSED,
                            'updated_at' => time()
                        ];
                        //如果既是驳回作业又是返工作业, 按照返工作业来执行
                        if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE]))
                        {
                            $attributes['status'] = Work::STATUS_REFUSEDSUBMIT;
                            $attributes['type'] = Work::TYPE_REFUSESUBMITREVISE;
                        }
                        Work::updateAll($attributes, ['id' => $work_['id']]);
                        
                        //添加作业记录
                        $workRecord = new WorkRecord();
                        $workRecord->project_id = $this->projectId;
                        $workRecord->work_id = $work_['id'];
                        $workRecord->data_id = $work_['data_id'];
                        $workRecord->batch_id = $work_['batch_id'];
                        $workRecord->step_id = $work_['step_id'];
                        $workRecord->task_id = $this->task['id'];
                        $workRecord->type = WorkRecord::TYPE_BACKTOREFUSE;
                        $workRecord->after_user_id = $this->userId;
                        $workRecord->after_work_status = Work::STATUS_REFUSED;
                        $workRecord->before_user_id = $work_['user_id'];
                        $workRecord->before_work_status = $work_['status'];
                        $workRecord->created_at = time();
                        $workRecord->updated_at = time();
                        $workRecord->save();
                        
                        $counters = [
                            'work_time' => $workTime,
                            'active_work_time' => $activeWorkTime
                        ];
                        //挂起作业,增加挂起作业已修正数
                        if (in_array($work_['type'], [Work::TYPE_DIFFICULT, Work::TYPE_DIFFICULTREVISE]))
                        {
                            $counters['refused_count'] = 1;
                            //$counters['difficult_count'] = 1;
                            //$counters['difficult_revise_count'] = 1;
                        }
                        if (in_array($work_['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                        {
                            //$counters['refused_count'] = 1;
                            //$counters['refused_revise_count'] = 1;
                        }
                        if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
                        {
                            //$counters['refuse_count'] = 1;
                            //$counters['refuse_revise_count'] = 1;
                        }
                        
                        Stat::updateCounter($work_['project_id'], $work_['batch_id'], $work_['step_id'], $counters);
                        
                        StatUser::updateCounter($this->projectId, $work_['batch_id'], $work_['step_id'], $this->userId, $counters);
                        
                    }
                    elseif ($work_['is_refused'] == Work::OPTION_REFUSESUBMIT)
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work  '.json_encode($_logs));
                        
                        $this->clearUserCache($scene, $dataId_, false);
                        
                        $attributes = [
                            'status' => Work::STATUS_REFUSEDSUBMIT,
                            'type' => Work::TYPE_REFUSESUBMIT,
                            'work_time' => $work_['work_time'] + $workTime,
                            'active_work_time' => $work_['active_work_time'] + $activeWorkTime,
                            'updated_at' => time()
                        ];
                        //如果既是驳回作业又是返工作业, 按照返工作业来执行
                        if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
                        {
                            $attributes['status'] = Work::STATUS_REFUSEDSUBMIT;
                            $attributes['type'] = Work::TYPE_REFUSESUBMIT;
                        }
                        Work::updateAll($attributes, ['id' => $work_['id']]);
                        
                        //添加作业记录
                        $workRecord = new WorkRecord();
                        $workRecord->project_id = $this->projectId;
                        $workRecord->work_id = $work_['id'];
                        $workRecord->data_id = $work_['data_id'];
                        $workRecord->batch_id = $work_['batch_id'];
                        $workRecord->step_id = $work_['step_id'];
                        $workRecord->task_id = $this->task['id'];
                        $workRecord->type = WorkRecord::TYPE_BACKTOREFUSESUBMIT;
                        $workRecord->after_user_id = $this->userId;
                        $workRecord->after_work_status = Work::STATUS_REFUSEDSUBMIT;
                        $workRecord->before_user_id = $work_['user_id'];
                        $workRecord->before_work_status = $work_['status'];
                        $workRecord->created_at = time();
                        $workRecord->updated_at = time();
                        $workRecord->save();
                        
                        //增加被驳回数
                        $counters = [
                            'work_time' => $workTime,
                            'active_work_time' => $activeWorkTime
                        ];
                        //挂起作业,增加挂起作业已修正数
                        if (in_array($work_['type'], [Work::TYPE_DIFFICULT, Work::TYPE_DIFFICULTREVISE]))
                        {
                            $counters['refuse_count'] = 1;
                            $counters['refuse_submited_count'] = 1;
                            //$counters['difficult_count'] = 1;
                            //$counters['difficult_revise_count'] = 1;
                        }
                        if (in_array($work_['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                        {
                            //$counters['refused_count'] = 1;
                            //$counters['refused_revise_count'] = 1;
                        }
                        if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
                        {
                            //$counters['refuse_count'] = 1;
                            //$counters['refuse_revise_count'] = 1;
                        }
                        /*
                        StatUserDay::updateCounter($this->projectId, $work_['batch_id'], $work_['step_id'], $this->userId, $counters);
                        StatDayUser::updateCounter($this->projectId, $work_['batch_id'], $work_['step_id'], $this->userId, $counters);
                        if($work_['status'] == Work::STATUS_RECEIVED)
                        {
                            $counters['received_clear_count'] = 1;
                        }
                        if($work_['status'] == Work::STATUS_EXECUTING)
                        {
                            $counters['executing_count'] = -1;
                        }
                        */
                        if(!($work_['status'] == Work::STATUS_REFUSEDSUBMIT && $work_['type'] == Work::TYPE_REFUSESUBMIT))
                        {
                            $counters['refuse_submited_count'] = 1;
                        }
                        Stat::updateCounter($this->projectId, $work_['batch_id'], $work_['step_id'], $counters);
                        if(isset($counters['refuse_submited_count']))
                        {
                            unset($counters['refuse_submited_count']);
                        }
                        StatUser::updateCounter($this->projectId, $work_['batch_id'], $work_['step_id'], $this->userId, $counters);
                    }
                    else
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work  '.json_encode($_logs));
                        
                        $this->clearUserCache($scene, $dataId_, false);
                        
                        //统计框数, 矫正框数
                        $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                        
                        //----------------------------------------------------
                        $transaction = Yii::$app->db->beginTransaction();
                        
                        $attributes = [
                            'status' => Work::STATUS_DELETED,
                            'type' => Work::TYPE_GIVEUP,
                            'updated_at' => time(),
                            'work_time' => $work_['work_time'] + $workTime,
                            'active_work_time' => $work_['active_work_time'] + $activeWorkTime,
                        ];
                        $updateCount = Work::updateAll($attributes, ['id' => $work_['id']]);
                        if (!$updateCount)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $updateCount error '.json_encode($_logs));
                        }
                        
                        //重新生成记录
                        $workNew = new Work();
                        $workNew->project_id = $work_['project_id'];
                        $workNew->batch_id = $work_['batch_id'];
                        $workNew->step_id = $work_['step_id'];
                        $workNew->data_id = $work_['data_id'];
                        $workNew->status = Work::STATUS_NEW;
                        $workNew->type = Work::TYPE_NORMAL;
                        $workNew->submit_count = $work_['submit_count'];
                        $workNew->is_submitted = $work_['is_submitted'];
                        $workNew->created_at = time();
                        $workNew->updated_at = time();
                        $workNew->save();
                        
                        $transaction->commit();
                        //------------------------------------------------------
                        
                        //保存驳回理由
                        $workResultNew = new WorkResult();
                        $workResultNew->work_id = $workNew->id;
                        $workResultNew->result = $workResult_['result'];
                        $workResultNew->feedback = $workResult_['feedback'];
                        $workResultNew->save();
                        
                        //追加作业到数据池
                        $this->appendData($dataId_);
                        
                        
                        $counters = [
                            'work_time' => $workTime,
                            'active_work_time' => $activeWorkTime
                        ];
                        /*
                        StatUserDay::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
                        StatDayUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
                        if($work_['status'] == Work::STATUS_RECEIVED)
                        {
                            $counters['received_clear_count'] = 1;
                        }
                        if($work_['status'] == Work::STATUS_EXECUTING)
                        {
                            $counters['executing_count'] = -1;
                        }*/
                        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
                        StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
                        
                        //添加作业记录
                        $workRecord = new WorkRecord();
                        $workRecord->project_id = $this->projectId;
                        $workRecord->work_id = $work_['id'];
                        $workRecord->data_id = $work_['data_id'];
                        $workRecord->batch_id = $work_['batch_id'];
                        $workRecord->step_id = $work_['step_id'];
                        $workRecord->task_id = $this->task['id'];
                        $workRecord->type = WorkRecord::TYPE_GIVEUP;
                        $workRecord->after_user_id = $this->userId;
                        $workRecord->after_work_status = Work::STATUS_NEW;
                        $workRecord->before_user_id = $work_['user_id'];
                        $workRecord->before_work_status = $work_['status'];
                        $workRecord->created_at = time();
                        $workRecord->updated_at = time();
                        $workRecord->save();
                    }
                }
            
                $clearReceivedCount += count($dataIds);
            }
        }
        $_logs['$clearReceivedCount'] = $clearReceivedCount;
        
        //可能是领取未过期, 但是缓存丢失
        /*$receivedWorks = Work::find()
        ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId])
        ->andWhere(['user_id' => $this->userId])
        ->andWhere(['in', 'status', [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]])
        ->asArray()->all();
        $_logs['$receivedWorks'] = $receivedWorks;
        
        if ($receivedWorks)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $receivedWorks '.json_encode($_logs));
            
            foreach ($receivedWorks as $work_)
            {
                $_logs['$work_'] = $work_;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ '.json_encode($_logs));
                
                if (!empty($work_['is_refused']))
                {
                    $attributes = [
                        'status' => Work::STATUS_REFUSED,
                        'type' => Work::TYPE_AUDITREFUSED,
                        'updated_at' => time()
                    ];
                    //如果既是驳回作业又是返工作业, 按照返工作业来执行
                    if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE]))
                    {
                        $attributes['status'] = Work::STATUS_REFUSEDSUBMIT;
                        $attributes['type'] = Work::TYPE_REFUSESUBMITREVISE;
                    }
                    Work::updateAll($attributes, ['id' => $work_['id']]);
                
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $work_['data_id'];
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work_['step_id'];
                    $workRecord->task_id = $this->task['id'];
                    $workRecord->type = WorkRecord::TYPE_BACKTOREFUSE;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_REFUSED;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                }
                else
                {
                    //统计框数, 矫正框数
                    $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                    
                    //---------------------------------------------------
                    $transaction = Yii::$app->db->beginTransaction();
                    
                    $attributes = [
                        'status' => Work::STATUS_DELETED,
                        'type' => Work::TYPE_GIVEUP,
                        'updated_at' => time()
                    ];
                    $updateCount = Work::updateAll($attributes, ['id' => $work_['id']]);
                    if (!$updateCount)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $updateCount error '.json_encode($_logs));
                    }
                    
                    //重新生成记录
                    $workNew = new Work();
                    $workNew->project_id = $work_['project_id'];
                    $workNew->batch_id = $work_['batch_id'];
                    $workNew->step_id = $work_['step_id'];
                    $workNew->data_id = $work_['data_id'];
                    $workNew->status = Work::STATUS_NEW;
                    $workNew->type = Work::TYPE_NORMAL;
                    $workNew->created_at = time();
                    $workNew->updated_at = time();
                    $workNew->save();
                    
                    $transaction->commit();
                    //------------------------------------------------------
                    
                    //保存驳回理由
                    $workResultNew = new WorkResult();
                    $workResultNew->work_id = $workNew->id;
                    $workResultNew->result = $workResult_['result'];
                    $workResultNew->feedback = $workResult_['feedback'];
                    $workResultNew->save();
                    
                    //追加作业到数据池
                    $this->appendData($work_['data_id']);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $work_['data_id'];
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work_['step_id'];
                    $workRecord->task_id = $this->task['id'];
                    $workRecord->type = WorkRecord::TYPE_GIVEUP;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_NEW;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                }
            }
            
            $clearReceivedCount += count($receivedWorks);
        }
        $_logs['$clearReceivedCount.1'] = $clearReceivedCount;
        */
        
        //返回值
        $resultData = [];
        $resultData['count'] = $clearReceivedCount;
        $resultData['has_scene'] = $hasExistSceneCount;
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return FormatHelper::result($resultData);
    }
    
    /**
     * 作业超时处理, 将对作业重新分发
     * 不对其父分步做任何处理
     * 作业数据保留!!!!
     *
     * @param int $this->batchId
     * @param int $dataId
     */
    public function timeout($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'task_data_not_exist', yii::t('app', 'task_data_not_exist'));
        }
        
        //---------------------------
        
        $work = Work::find()
        ->where(['batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->andWhere(['user_id' => $this->userId])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'task_work_not_exist', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING, Work::STATUS_REFUSED, Work::STATUS_DIFFICULT]))
        {
            if($work['status'] == Work::STATUS_NEW)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status new '.json_encode($_logs));
                return FormatHelper::result('', 'task_work_status_new', yii::t('app', 'task_work_status_new'));
            }
            else if($work['status'] == Work::STATUS_SUBMITED)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status submited '.json_encode($_logs));
                return FormatHelper::result('', 'task_work_status_submited', yii::t('app', 'task_work_status_submited'));
            }
            else
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status error '.json_encode($_logs));
                return FormatHelper::result('', 'task_work_status_not_allow', yii::t('app', 'task_work_status_not_allow'));
            }
        }
        
        //驳回作业置为驳回作业
        if ($work['is_refused'] == Work::OPTION_REFUSED)
        {
            $attributes = [
                'status' => Work::STATUS_REFUSED,
                'type' => Work::TYPE_AUDITREFUSED,
                'updated_at' => time()
            ];
            //如果既是驳回作业又是返工作业, 按照返工作业来执行
            if (in_array($work['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
            {
                $attributes['status'] = Work::STATUS_REFUSEDSUBMIT;
                $attributes['type'] = Work::TYPE_REFUSESUBMIT;
            }
            
            /*if(in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
             {
             if($work['updated_at']) //计算放弃作业的工时
             {
             $attributes['work_time'] = $work['work_time'] + (time() - $work['updated_at']);
             }
             }*/
            Work::updateAll($attributes, ['id' => $work['id']]);
            
            $counters = [];
            //挂起作业,增加挂起作业已修正数
            if (in_array($work['type'], [Work::TYPE_DIFFICULT, Work::TYPE_DIFFICULTREVISE]))
            {
                $counters['refused_count'] = 1;
                //$counters['difficult_count'] = 1;
                //$counters['difficult_revise_count'] = 1;
            }
            if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
            {
                //$counters['refused_count'] = 1;
                //$counters['refused_revise_count'] = 1;
            }
            if (in_array($work['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
            {
                //$counters['refuse_count'] = 1;
                //$counters['refuse_revise_count'] = 1;
            }
            
            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
            StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
        }
        elseif ($work['is_refused'] == Work::OPTION_REFUSESUBMIT)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work  '.json_encode($_logs));
            
            $attributes = [
                'status' => Work::STATUS_REFUSEDSUBMIT,
                'type' => Work::TYPE_REFUSESUBMIT,
                'updated_at' => time()
            ];
            //如果既是驳回作业又是返工作业, 按照返工作业来执行
            if (in_array($work['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
            {
                $attributes['status'] = Work::STATUS_REFUSEDSUBMIT;
                $attributes['type'] = Work::TYPE_REFUSESUBMIT;
            }
            
            Work::updateAll($attributes, ['id' => $work['id']]);
            
            //添加作业记录
            $workRecord = new WorkRecord();
            $workRecord->project_id = $this->projectId;
            $workRecord->work_id = $work['id'];
            $workRecord->data_id = $work['data_id'];
            $workRecord->batch_id = $work['batch_id'];
            $workRecord->step_id = $work['step_id'];
            $workRecord->task_id = $this->task['id'];
            $workRecord->type = WorkRecord::TYPE_BACKTOREFUSESUBMIT;
            $workRecord->after_user_id = $this->userId;
            $workRecord->after_work_status = Work::STATUS_REFUSEDSUBMIT;
            $workRecord->before_user_id = $work['user_id'];
            $workRecord->before_work_status = $work['status'];
            $workRecord->created_at = time();
            $workRecord->updated_at = time();
            $workRecord->save();
            
            //增加被驳回数
            $counters = [];
            //挂起作业,增加挂起作业已修正数
            if (in_array($work['type'], [Work::TYPE_DIFFICULT, Work::TYPE_DIFFICULTREVISE]))
            {
                $counters['refuse_count'] = 1;
                $counters['refuse_submited_count'] = 1;
                //$counters['difficult_count'] = 1;
                //$counters['difficult_revise_count'] = 1;
            }
            if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
            {
                //$counters['refused_count'] = 1;
                //$counters['refused_revise_count'] = 1;
            }
            if (in_array($work['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE, Work::TYPE_REFUSESUBMIT]))
            {
                //$counters['refuse_count'] = 1;
                //$counters['refuse_revise_count'] = 1;
            }
            
            if(!($work['status'] == Work::STATUS_REFUSEDSUBMIT && $work['type'] == Work::TYPE_REFUSESUBMIT))
            {
                $counters['refuse_submited_count'] = 1;
            }
            Stat::updateCounter($this->projectId, $work['batch_id'], $work['step_id'], $counters);
            if(isset($counters['refuse_submited_count']))
            {
                unset($counters['refuse_submited_count']);
            }
            StatUser::updateCounter($this->projectId, $work['batch_id'], $work['step_id'], $this->userId, $counters);
        }
        else
        {
            //统计框数, 矫正框数
            $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
            
            //---------------------------------------------------
            $transaction = Yii::$app->db->beginTransaction();
            
            $attributes = [
                'status' => Work::STATUS_DELETED,
                'type' => Work::TYPE_TIMEOUT,
                'end_time' => time(),
                'updated_at' => time()
            ];
            $updateCount = Work::updateAll($attributes, ['id' => $work['id']]);
            if (!$updateCount)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $updateCount error '.json_encode($_logs));
            }
            
            //重新生成记录
            $workNew = new Work();
            $workNew->project_id = $work['project_id'];
            $workNew->batch_id = $work['batch_id'];
            $workNew->step_id = $work['step_id'];
            $workNew->data_id = $work['data_id'];
            $workNew->is_refused = $work['is_refused'];
            $workNew->status = Work::STATUS_NEW;
            $workNew->type = Work::TYPE_TIMEOUT;
            $workNew->submit_count = $work['submit_count'];
            $workNew->is_submitted = $work['is_submitted'];
            $workNew->created_at = time();
            $workNew->updated_at = time();
            $workNew->save();
            
            $transaction->commit();
            //------------------------------------------------------
            
            //保存驳回理由
            $workResultNew = new WorkResult();
            $workResultNew->work_id = $workNew->id;
            $workResultNew->result = $workResult['result'];
            $workResultNew->feedback = $workResult['feedback'];
            $workResultNew->save();
            
            //追加作业到数据池
            $this->appendData($dataId);
            
            $counters = [
                'timeout_count' => 1
            ];
            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
            StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
            
            //添加作业记录
            $workRecord = new WorkRecord();
            $workRecord->project_id = $this->projectId;
            $workRecord->work_id = $work['id'];
            $workRecord->data_id = $dataId;
            $workRecord->batch_id = $work['batch_id'];
            $workRecord->step_id = $work['step_id'];
            $workRecord->task_id = $this->task['id'];
            $workRecord->type = WorkRecord::TYPE_TIMEOUT;
            $workRecord->after_user_id = $this->userId;
            $workRecord->after_work_status = Work::STATUS_NEW;
            $workRecord->before_user_id = $work['user_id'];
            $workRecord->before_work_status = $work['status'];
            $workRecord->created_at = time();
            $workRecord->updated_at = time();
            $workRecord->save();
            
            //获取作业人的语言
            $oldLanguage = Yii::$app->language;
            $user = User::findOne($this->userId);
            if(empty($user) || !isset($user->language))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_info_not_found '.json_encode($_logs));
                $language = User::LANGUAGE_ZH_CN;
            }
            else
            {
                $language = $user->language;
            }
            Yii::$app->language = User::getLanguageKey($language);
            
            Message::sendTaskTimeout($work['user_id'], $this->projectId, $this->task['id'], $dataId);
            
            Yii::$app->language = $oldLanguage;
            
            
            //检测被操作人是否被移除
            $checkPermission = $this->checkPermission($work['batch_id'], $work['step_id'], $work['user_id']);
            $_logs['$checkPermission'] = $checkPermission;
            if (!empty($checkPermission['error'])) {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' taskuser_removed '.json_encode($_logs));
                
                //实例化执行类
                $taskHandler = new TaskHandler();
                $isinit = $taskHandler->init($work['project_id'], $work['batch_id'], $work['step_id'], $work['user_id']);
                if (!$isinit)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                    return FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail'));
                }
                
                $result = $taskHandler->forceReset($work['data_id']);
                //$_logs['$result'] = $result;
                
                if (!empty($result['error']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_forceReset_notAllow '.json_encode($_logs));
                    return FormatHelper::resultStrongType('', $result['error'], $result['message']);
                }
            }
        }
        
        //-----------------------
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 执行结果暂存
     * @param int/array $dataId
     * @param int $userId
     * @param int $dataId_
     * @param array $workResult_
     */
    public function temporaryStorage($dataId, $userId, $dataId_, $workResult_)
    {
        $_logs = ['$dataId' => $dataId, '$userId' => $userId, '$dataId_' => $dataId_];
        
        //场景
        $scene = $this->getUserScene($dataId, $userId);
        $_logs['$scene'] = $scene;
        
        //获取用户缓存池中作业数据
        $userCache = $this->getUserCache($scene);
        $_logs['$userCache'] = $userCache;
        if (!$userCache)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_usercache_empty error '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_usercache_empty'));
        }
        $cacheDataId = $userCache;
        $_logs['$cacheDataId'] = $cacheDataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache '.json_encode($_logs));
        
        //----------------------------------------------
        
        $dataIds = is_array($cacheDataId) ? $cacheDataId : [$cacheDataId];
        $_logs['$dataIds'] = $dataIds;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataIds '.json_encode($_logs));
        
        //------------------------------------------------
        
        if (!in_array($dataId_, $dataIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_usercache_empty error '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_dataid_notin_usercache'));
        }
        
        
        $data = Data::find()->where(['id' => $dataId_])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_exist '.json_encode($_logs));
            return FormatHelper::result('', 'task_data_not_exist', yii::t('app', 'task_data_not_exist'));
        }
        
        $work = Work::find()
        ->where(['batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $dataId_])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        //此处需要多个状态, 因批量审核,无法确定状态
        if (!in_array($work['status'], [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING, Work::STATUS_SUBMITED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'task_work_status_not_allow', yii::t('app', 'task_work_status_not_allow'));
        }
        
        $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        if (empty($workResult)){
            $workResult = new WorkResult();
            $workResult->work_id = $work['id'];
            $workResult->result = '';
            $workResult->feedback = '';
            $workResult->save();
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult insert '.json_encode($_logs));
            
            $workResult = $workResult->getAttributes();
        }
        
        //统计本次作业的结果
        list($stat_, $statUser_, $statIds_) = ProjectHandler::statResult($workResult_);
        
        //校验作业结果偏移的问题
        if ($statIds_)
        {
            if ($this->checkHistoryRepeat($dataId_, $statIds_))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkHistoryRepeat Yes!!! '.json_encode($_logs));
                return FormatHelper::result('', 'error', yii::t('app', 'task_work_result_repeat'));
            }
        
            $this->addUserHistory($dataId_, $statIds_);
        }
        
        //更新作业记录
        WorkResult::mergeResult($workResult['id'], $workResult_);
        
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId_;
        $workRecord->batch_id = $this->batchId;
        $workRecord->step_id = $this->stepId;
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_TEMPORARYSTORAGE;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_EXECUTING;
        $workRecord->before_user_id = 0;
        $workRecord->before_work_status = 0;
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 审核修改
     * 
     * @param int $dataId 用户缓存池指定数据
     * @param int $userId 用户缓存池指定用户
     * @param int $dataId_ 需要修改的数据id
     */
    public function auditEditGet($dataId, $userId, $workId_, $dataId_)
    {
        $_logs = [];
        
        if (!in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_type_not_allow '.json_encode($_logs));
            return FormatHelper::result('', 'task_type_not_allow', yii::t('app', 'task_type_not_allow'));
        }
        
        //场景
        $scene = $this->getUserScene($dataId, $userId);
        $_logs['$scene'] = $scene;
        
        //获取用户临时作业数据
        $userCache = $this->getUserCache($scene);
        $_logs['$userCache'] = $userCache;
        if (!$userCache)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_usercache_empty error '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_usercache_empty'));
        }
        $cacheDataId = $userCache;
        $_logs['$cacheDataId'] = $cacheDataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache '.json_encode($_logs));
        
        //----------------------------------------------
        
        $dataIds = is_array($cacheDataId) ? $cacheDataId : [$cacheDataId];
        $_logs['$dataIds'] = $dataIds;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataIds '.json_encode($_logs));
        
        //------------------------------------------------
        
        if (!in_array($dataId_, $dataIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_usercache_empty error '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_usercache_empty'));
        }
        
        $data = Data::find()->where(['id' => $dataId_])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_usercache_empty'));
        }
        
        //查询结果, 用于回显
        $dataResult = DataResult::find()->where(['data_id' => $dataId_])->asArray()->limit(1)->one();
        
        //解析data
        $dataResult['data'] = json_decode($dataResult['data'], true);
        if (!empty($dataResult['data']))
        {
            $dataResultDdata = array();
            foreach ($dataResult['data'] as $k => $v)
            {
                $dataResultDdata[strtolower($k)] = $v;
            }
            //$dataResult['data'] = json_encode($dataResultDdata);
        }
        
        //解析result
        if (!empty($dataResult['result']))
        {
            $dataResult['result'] = json_decode($dataResult['result'], true);
            //$dataResult['data'] = json_encode($dataResultDdata);
        }
        
        $resultData = [
            'dataInfo' => $data,
            'dataResultInfo' => $dataResult,
        ];
        
        return FormatHelper::result($resultData);
    }
    
    public function auditEditSubmit($dataId, $userId, $workId_, $dataId_, $dataResult_)
    {
        $_logs = [];
        
        if (!in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_type_not_allow '.json_encode($_logs));
            return FormatHelper::result('', 'task_type_not_allow', yii::t('app', 'task_type_not_allow'));
        }
        
        //场景
        $scene = $this->getUserScene($dataId, $userId);
        $_logs['$scene'] = $scene;
        
        //获取用户临时作业数据
        $userCache = $this->getUserCache($scene);
        $_logs['$userCache'] = $userCache;
        if (!$userCache)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_usercache_empty error '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_usercache_empty'));
        }
        $cacheDataId = $userCache;
        $_logs['$cacheDataId'] = $cacheDataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserCache '.json_encode($_logs));
        
        //----------------------------------------------
        
        $dataIds = is_array($cacheDataId) ? $cacheDataId : [$cacheDataId];
        $_logs['$dataIds'] = $dataIds;
        //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataIds '.json_encode($_logs));
        
        //------------------------------------------------
        
        if (!in_array($dataId_, $dataIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_usercache_empty error '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_dataid_notin_usercache'));
        }
        
        
        $data = Data::find()->where(['id' => $dataId_])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_exist '.json_encode($_logs));
            return FormatHelper::result('', 'task_data_not_exist', yii::t('app', 'task_data_not_exist'));
        }
        
        //查询数据结果
        $dataResult = DataResult::find()->where(['data_id' => $dataId_])->asArray()->limit(1)->one();
        
        $work = Work::find()
        ->where(['batch_id' => $this->batchId, 'id' => $workId_, 'data_id' => $dataId_])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        //此处需要多个状态, 因批量审核,无法确定状态
        if (!in_array($work['status'], [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING, Work::STATUS_SUBMITED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'task_work_status_not_allow', yii::t('app', 'task_work_status_not_allow'));
        }
        
        $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        if (empty($workResult)){
            $workResult = new WorkResult();
            $workResult->work_id = $work['id'];
            $workResult->result = '';
            $workResult->feedback = '';
            $workResult->save();
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult insert '.json_encode($_logs));
            
            $workResult = $workResult->getAttributes();
        }
        
        //统计本次作业的结果
        list($stat_, $statUser_, $statIds_) = ProjectHandler::statResult($dataResult_);
        
        //校验作业结果偏移的问题
        if ($statIds_)
        {
            if ($this->checkHistoryRepeat($dataId_, $statIds_))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' checkHistoryRepeat Yes!!! '.json_encode($_logs));
                return FormatHelper::result('', 'error', yii::t('app', 'task_result_repeat'));
            }
        
            $this->addUserHistory($dataId_, $statIds_);
        }
        
        //保存最终结果
        DataResult::mergeResult($dataId_, $dataResult_);
        
        //更新作业记录
        WorkResult::mergeResult($workResult['id'], $dataResult_);
        
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId_;
        $workRecord->batch_id = $this->batchId;
        $workRecord->step_id = $this->stepId;
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_AUDITEDITSUBMIT;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_EXECUTING;
        $workRecord->before_user_id = 0;
        $workRecord->before_work_status = 0;
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        /*
         * 
        
        //更新为新结果统计
        $counters = [
            'label_count' => !empty($stat_['label_count']) ? $stat_['label_count'] : 0,
            'point_count' => !empty($stat_['point_count']) ? $stat_['point_count'] : 0,
            'line_count' => !empty($stat_['line_count']) ? $stat_['line_count'] : 0,
            'rect_count' => !empty($stat_['rect_count']) ? $stat_['rect_count'] : 0,
            'polygon_count' => !empty($stat_['polygon_count']) ? $stat_['polygon_count'] : 0,
            'sharepoint_count' => !empty($stat_['sharepoint_count']) ? $stat_['sharepoint_count'] : 0,
            'other_count' => !empty($stat_['other_count']) ? $stat_['other_count'] : 0,
            'label_time' => !empty($stat_['label_time']) ? $stat_['label_time'] : 0
        ];
        Work::updateAll($counters, ['id' => $work['id']]);
        
        //统计本次作业的结果
        $counters = [
            'label_count' => isset($stat_['label_count']) ? $stat_['label_count'] - $work['label_count'] : 0,
            'point_count' => isset($stat_['point_count']) ? $stat_['point_count'] - $work['point_count'] : 0,
            'line_count' => isset($stat_['line_count']) ? $stat_['line_count'] - $work['line_count'] : 0,
            'rect_count' => isset($stat_['rect_count']) ? $stat_['rect_count'] - $work['rect_count'] : 0,
            'polygon_count' => isset($stat_['polygon_count']) ? $stat_['polygon_count'] - $work['polygon_count'] : 0,
            'sharepoint_count' => isset($stat_['sharepoint_count']) ? $stat_['sharepoint_count'] - $work['sharepoint_count'] : 0,
            'other_count' => isset($stat_['other_count']) ? $stat_['other_count'] - $work['other_count'] : 0,
            'label_time' => isset($stat_['label_time']) ? $stat_['label_time'] - $work['label_time'] : 0
        ];
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
        $oldStatUser = !empty($workResult['stat']) ? json_decode($workResult['stat']) : [];
        
        $statUserIds = array_merge(array_keys($oldStatUser), array_keys($statUser_));
        
        if ($statUserIds)
        {
            foreach ($statUserIds as $statUserId_)
            {
                $counters = [
                    'label_count' => isset($statUser_[$statUserId_]['label_count']) ? $statUser_[$statUserId_]['label_count'] - : 0,
                    'point_count' => isset($userstat_[$statUserId_]['point_count']) ? $statUser_[$statUserId_]['point_count'] : 0,
                    'line_count' => isset($userstat_[$statUserId_]['line_count']) ? $statUser_[$statUserId_]['line_count'] : 0,
                    'rect_count' => isset($userstat_[$statUserId_]['rect_count']) ? $statUser_[$statUserId_]['rect_count'] : 0,
                    'polygon_count' => isset($userstat_[$statUserId_]['polygon_count']) ? $statUser_[$statUserId_]['polygon_count'] : 0,
                    'sharepoint_count' => isset($userstat_[$statUserId_]['sharepoint_count']) ? $statUser_[$statUserId_]['sharepoint_count'] : 0,
                    'other_count' => isset($userstat_[$statUserId_]['other_count']) ? $statUser_[$statUserId_]['other_count'] : 0,
                    'label_time' => isset($userstat_[$statUserId_]['label_time']) ? $statUser_[$statUserId_]['label_time'] : 0
                ];
                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $statUserId_, $counters);
            }
        }
        
        */
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 审核某分步的作业, 执行通过
     * 将对其父分步的作业更改为通过完成状态
     * 
     * @param int $dataId
     * @param string $correctWorkIds [123,124]
     * 
     * @return boolean
     */
    public function auditAllow($dataId, $correctWorkIds = [])
    {
        $_logs = ['$dataId' => $dataId, '$correctWorkIds' => $correctWorkIds];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
        
        $work = Work::find()
        ->where(['batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_work_not_exist notexist '.json_encode($_logs));
            return FormatHelper::result('', 'task_work_not_exist', yii::t('app', 'task_work_not_exist'));
        }
        
        //此处需要多个状态, 因批量审核,无法确定状态
        if (!in_array($work['status'], [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING, Work::STATUS_SUBMITED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_work_status_not_allow '.json_encode($_logs));
            return FormatHelper::result('', 'task_work_status_not_allow', yii::t('app', 'task_work_status_not_allow'));
        }
        
        if (!in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_type_not_allow '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_type_not_allow'));
        }
        
        //判断最优结果
        $correctWorkIds = Work::find()->select(['id'])->where(['data_id' => $dataId])->andWhere(['in', 'id', $correctWorkIds])->asArray()->column();
        if (!$correctWorkIds)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $correctWork notexist '.json_encode($_logs));
            return FormatHelper::result('', 'task_currect_workids_not_found', yii::t('app', 'task_currect_workids_not_found'));
        }
        
        
        //统计本次作业的结果
        //$dataResult_ = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
        //list($stat_, $statUser_) = ProjectHandler::statResult($dataResult_['result']);
        
        $workTime = 0;
        if ($work['start_time'] > 0)
        {
            $workTime = (time() - $work['start_time']);
        }
        
        $attributes = [
            'user_id' => $this->userId,
            'status' => Work::STATUS_SUBMITED,
            'type' => Work::TYPE_AUDITALLOW,
            'updated_at' => time(),
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
        
        //-----------------------------
        
        $counters = [
            'work_time' => $workTime,
            'work_count' => 1,//增加有效提交数
            'submit_count' => 1,//增加提交数
            'allow_count' => 1,//增加通过数
        ];
        //挂起作业,增加挂起作业已修正数
        if (in_array($work['type'], [Work::TYPE_DIFFICULTREVISE]))
        {
            //$counters['difficult_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
        {
            $counters['refused_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
        
        //统计标注绩效
        
        
        //Message::sendTaskAllow($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        
        
        //-----------------------------
        
        //更改分发
        foreach ($this->stepParentIds as $stepId_)
        {
            $_logs['$stepId_'] = $stepId_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
            
            if (!$stepId_)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ empty '.json_encode($_logs));
                continue;
            }
            
            //分发作业的结果
            $work_ = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
            $_logs['$work_'] = $work_;
            
            //---------------------------------
            if (!$work_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ not exist '.json_encode($_logs));
                continue;
            }
            
            if (!in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                continue;
            }
            
            $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
            if (!$task_)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                continue;
            }
            
            $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
            if (!$step_)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                continue;
            }
            
            if ($step_['type'] == Step::TYPE_PRODUCE)
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $step_ is TYPE_PRODUCE '.json_encode($_logs));
                
                //处理正确答案
                if (in_array($work_['id'], $correctWorkIds))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' in $correctWorkIds '.json_encode($_logs));
                    
                    $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                    $result_ = JsonHelper::json_decode_all($workResult_['result']);
            
                    //合并数据结果
                    //DataResult::mergeResult($dataId, $result_);
                    
                    list($stat_, $statUser_) = ProjectHandler::statResult($result_);
                    
                    //标注绩效统计
                    if(!empty($statUser_))
                    {
                        foreach($statUser_ as $statUserId_ => $statUserResult_)
                        {
                            foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                            {
                                foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                {
                                    if(empty($work['is_refused']))
                                    {
                                        //统计被通过标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'work_id' => $work_['id'],
                                            'user_id' => $statUserId_,
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_ALLOWED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultWork::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'user_id' => $statUserId_,
                                            'type' => $statUserResultType__,
                                            'action' => StatResultUser::ACTION_ALLOWED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultUser::updateCounter($where, $statCounters);
                                    }


                                    //统计通过标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'data_id' => $dataId,
                                        'work_id' => $work['id'],
                                        'user_id' => $this->userId,
                                        'type' => $statUserResultType__,
                                        'action' => StatResultWork::ACTION_ALLOW
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultWork::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'user_id' => $this->userId,
                                        'type' => $statUserResultType__,
                                        'action' => StatResultUser::ACTION_ALLOW
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultUser::updateCounter($where, $statCounters);
                                }
                            }
                        }
                    }
                    if(!empty($stat_))
                    {
                        foreach($stat_ as $statResultAction_ => $statResult__)
                        {
                            foreach ($statResult__ as $statResultType__ => $statResultVal__)
                            {
                                if(empty($work['is_refused']))
                                {
                                    //统计被通过标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'data_id' => $dataId,
                                        'type' => $statResultType__,
                                        'action' => StatResultData::ACTION_ALLOWED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResultData::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'type' => $statResultType__,
                                        'action' => StatResult::ACTION_ALLOWED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResult::updateCounter($where, $statCounters);
                                }

                                //统计通过标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'type' => $statResultType__,
                                    'action' => StatResultData::ACTION_ALLOW
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResultData::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'type' => $statResultType__,
                                    'action' => StatResult::ACTION_ALLOW
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResult::updateCounter($where, $statCounters);
                            }
                        }
                    }
                    
                    $counters = [];
                    $counters['work_count'] = 0;//执行不增加提交数
                    $counters['allowed_count'] = 1;//执行增加通过数
                    if(empty($work_['is_refused']))
                    {
                        $counters['audited_count'] = 1;//被审核张数
                    }
                    Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
                    
                    if (!empty($work_['user_id']))
                    {
                        $counters = [];
                        $counters['work_count'] = 0;//执行不增加提交数
                        $counters['allowed_count'] = 1;//执行增加通过数
                        if(empty($work_['is_refused']))
                        {
                            $counters['audited_count'] = 1;//被审核张数
                        }
                        StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                    }
                    
                    //更改每一步结果为结束
                    $attributes = [
                        'is_correct' => 1,//结果正确
                        'status' => Work::STATUS_FINISH,
                        'type' => Work::TYPE_AUDITALLOW,
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work_['id']]);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_AUDITALLOWED;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_FINISH;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskAllow($work_['user_id'], $this->projectId, $task_['id'], $dataId);
                    
                }
                //处理错误答案
                else
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notin $correctWorkIds '.json_encode($_logs));


                    $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                    $result_ = JsonHelper::json_decode_all($workResult_['result']);
            
                    //合并数据结果
                    //DataResult::mergeResult($dataId, $result_);
                    
                    list($stat_, $statUser_) = ProjectHandler::statResult($result_);
                    
                    //标注绩效统计
                    if(!empty($statUser_))
                    {
                        foreach($statUser_ as $statUserId_ => $statUserResult_)
                        {
                            foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                            {
                                foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                {
                                    //统计通过标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'data_id' => $dataId,
                                        'work_id' => $work['id'],
                                        'user_id' => $this->userId,
                                        'type' => $statUserResultType__,
                                        'action' => StatResultWork::ACTION_ALLOW
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultWork::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'user_id' => $this->userId,
                                        'type' => $statUserResultType__,
                                        'action' => StatResultUser::ACTION_ALLOW
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultUser::updateCounter($where, $statCounters);
                                }
                            }
                        }
                    }
                    if(!empty($stat_))
                    {
                        foreach($stat_ as $statResultAction_ => $statResult__)
                        {
                            foreach ($statResult__ as $statResultType__ => $statResultVal__)
                            {
                                //统计通过标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'type' => $statResultType__,
                                    'action' => StatResultData::ACTION_ALLOW
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResultData::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'type' => $statResultType__,
                                    'action' => StatResult::ACTION_ALLOW
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResult::updateCounter($where, $statCounters);
                            }
                        }
                    }

                    
                    //更改每一步结果为结束
                    $attributes = [
                        'is_correct' => 0,//结果不正确
                        'status' => Work::STATUS_FINISH,
                        'type' => Work::TYPE_AUDITIGNORED,
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work_['id']]);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_AUDITIGNORED;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_FINISH;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskAllow($work_['user_id'], $this->projectId, $task_['id'], $dataId);
                }
            }
            //处理父分步是审核的情况
            elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $step_ is TYPE_AUDIT '.json_encode($_logs));
                
                //处理正确答案
                if (in_array($work_['id'], $correctWorkIds))
                {
                    $counters = [];
                    $counters['work_count'] = 0;//不增加有效提交数
                    $counters['allowed_count'] = 1;//增加通过数
                    if(empty($work_['is_refused']))
                    {
                        $counters['audited_count'] = 1;//被审核张数
                    }
                    Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
                    
                    if (!empty($work_['user_id']))
                    {
                        $counters = [];
                        $counters['work_count'] = 0;//不增加有效提交数
                        $counters['allowed_count'] = 1;//增加通过数
                        if(empty($work_['is_refused']))
                        {
                            $counters['audited_count'] = 1;//被审核张数
                        }
                        StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                    }



                    //连带其上一步操作
                    $stepParentIds_ = Step::getParentStepIds($stepId_);
                    if ($stepParentIds_)
                    {
                        foreach ($stepParentIds_ as $stepId__)
                        {
                            $_logs['$stepId__'] = $stepId__;
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
                        
                            if (!$stepId__)
                            {
                                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId__ 0 '.json_encode($_logs));
                                continue;
                            }
                        
                            //分发作业的结果
                            $work__ = Work::find()
                            ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $stepId__, 'data_id' => $dataId])
                            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
                            ->orderBy(['id' => SORT_DESC])
                            ->asArray()->limit(1)->one();
                            $_logs['$work__'] = $work__;
                        
                            if (!$work__)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work__ not exist '.json_encode($_logs));
                                continue;
                            }
                        
                            if (!in_array($work__['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                                continue;
                            }
                            
                            //查询分发结果
                            $workResult__ = WorkResult::find()->where(['work_id' => $work__['id']])->asArray()->limit(1)->one();
                            
                            if (!$workResult__)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult__ not exist '.json_encode($_logs));
                                continue;
                            }
                            
                            //统计本次作业的结果
                            list($stat__, $statUser__) = ProjectHandler::statResult($workResult__['result']);
                        
                            $task__ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId__,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
                            if (!$task__)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task__ record '.json_encode($_logs));
                                continue;
                            }
                        
                            $step__ = Step::find()->where(['id' => $stepId__])->asArray()->limit(1)->one();
                            if (!$step__)
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step__ record '.json_encode($_logs));
                                continue;
                            }

                            if ($step__['type'] == Step::TYPE_PRODUCE)
                            {
                                $counters = [
                                    'work_count' => 0,
                                    'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                                ];
                                Stat::updateCounter($this->projectId, $this->batchId, $stepId__, $counters);
                                
                                if ($statUser__)
                                {
                                    foreach($statUser__ as $statUserId__ => $statUserResult__)
                                    {
                                        foreach ($statUserResult__ as $statUserResultAction__ => $statUserResults__)
                                        {
                                            foreach ($statUserResults__ as $statUserResultType__ => $statUserResultVal__)
                                            {
                                                //统计被通过标注绩效
                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $task_['id'],
                                                    'data_id' => $dataId,
                                                    'work_id' => $work_['id'],
                                                    'user_id' => $work_['user_id'],
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultWork::ACTION_ALLOWED //审核 被通过 有效
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultWork::updateCounter($where, $statCounters);

                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $task_['id'],
                                                    'user_id' => $work_['user_id'],
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultUser::ACTION_ALLOWED
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultUser::updateCounter($where, $statCounters);

                                                


                                                //统计通过标注绩效
                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $this->task['id'],
                                                    'data_id' => $dataId,
                                                    'work_id' => $work['id'],
                                                    'user_id' => $this->userId,
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultWork::ACTION_ALLOW //审核 通过
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultWork::updateCounter($where, $statCounters);

                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $this->task['id'],
                                                    'user_id' => $this->userId,
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultUser::ACTION_ALLOW
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultUser::updateCounter($where, $statCounters);

                                                
                                            }
                                        }
                                    }

                                }
                                if($stat__)
                                {
                                    foreach ($stat__ as $statResultAction__ => $statResults__)
                                    {
                                        foreach ($statResults__ as $statResultType__ => $statResultVal__)
                                        {
                                            //统计被通过标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'data_id' => $dataId,
                                                'type' => $statResultType__,
                                                'action' => StatResultData::ACTION_ALLOWED
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResultData::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'type' => $statResultType__,
                                                'action' => StatResult::ACTION_ALLOWED
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResult::updateCounter($where, $statCounters);

                                            //统计通过标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'data_id' => $dataId,
                                                'type' => $statResultType__,
                                                'action' => StatResultData::ACTION_ALLOW
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResultData::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'type' => $statResultType__,
                                                'action' => StatResult::ACTION_ALLOW
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResult::updateCounter($where, $statCounters);
                                        }
                                    }
                                }
                            }
                            else if(in_array($step__['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
                            {
                                $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
                                list($stat__, $statUser__) = ProjectHandler::statResult($dataResult['result']);

                                if ($statUser__)
                                {
                                    foreach($statUser__ as $statUserId__ => $statUserResult__)
                                    {
                                        foreach ($statUserResult__ as $statUserResultAction__ => $statUserResults__)
                                        {
                                            foreach ($statUserResults__ as $statUserResultType__ => $statUserResultVal__)
                                            {
                                                //统计被通过标注绩效
                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $task_['id'],
                                                    'data_id' => $dataId,
                                                    'work_id' => $work_['id'],
                                                    'user_id' => $work_['user_id'],
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultWork::ACTION_ALLOWED //审核 被通过 有效
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultWork::updateCounter($where, $statCounters);

                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $task_['id'],
                                                    'user_id' => $work_['user_id'],
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultUser::ACTION_ALLOWED
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultUser::updateCounter($where, $statCounters);

                                                //统计通过标注绩效
                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $this->task['id'],
                                                    'data_id' => $dataId,
                                                    'work_id' => $work['id'],
                                                    'user_id' => $this->userId,
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultWork::ACTION_ALLOW //审核 通过
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultWork::updateCounter($where, $statCounters);

                                                $where = [
                                                    'project_id' => $this->projectId,
                                                    'task_id' => $this->task['id'],
                                                    'user_id' => $this->userId,
                                                    'type' => $statUserResultType__,
                                                    'action' => StatResultUser::ACTION_ALLOW
                                                ];
                                                $statCounters = [
                                                    'value' => $statUserResultVal__
                                                ];
                                                StatResultUser::updateCounter($where, $statCounters);
                                            }
                                        }
                                    }

                                }
                                if(!empty($stat__))
                                {
                                    foreach ($stat__ as $statResultAction__ => $statResults__)
                                    {
                                        foreach ($statResults__ as $statResultType__ => $statResultVal__)
                                        {
                                            //统计被通过标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'data_id' => $dataId,
                                                'type' => $statResultType__,
                                                'action' => StatResultData::ACTION_ALLOWED
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResultData::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'type' => $statResultType__,
                                                'action' => StatResult::ACTION_ALLOWED
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResult::updateCounter($where, $statCounters);

                                            //统计通过标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'data_id' => $dataId,
                                                'type' => $statResultType__,
                                                'action' => StatResultData::ACTION_ALLOW
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResultData::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'type' => $statResultType__,
                                                'action' => StatResult::ACTION_ALLOW
                                            ];
                                            $statCounters = [
                                                'value' => $statResultVal__
                                            ];
                                            StatResult::updateCounter($where, $statCounters);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //更改每一步结果为结束
                    $attributes = [
                        'is_correct' => 1,//结果正确
                        'status' => Work::STATUS_FINISH,
                        'type' => Work::TYPE_AUDITALLOW,
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work_['id']]);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_AUDITALLOWED;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_FINISH;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskAllow($work_['user_id'], $this->projectId, $task_['id'], $dataId);
                }
                //处理错误答案
                else
                {
                    //查询分发结果
                    $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                    
                    if (!$workResult_)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult__ not exist '.json_encode($_logs));
                        continue;
                    }
                    
                    //统计本次作业的结果
                    list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
                            
                    if ($statUser_)
                    {
                        foreach($statUser_ as $statUserId_ => $statUserResult_)
                        {
                            foreach ($statUserResult_ as $statUserResultAction__ => $statUserResults__)
                            {
                                foreach ($statUserResults__ as $statUserResultType__ => $statUserResultVal__)
                                {
                                    //统计驳回标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'data_id' => $dataId,
                                        'work_id' => $work['id'],
                                        'user_id' => $this->userId,
                                        'type' => $statUserResultType__,
                                        'action' => StatResultWork::ACTION_ALLOW
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultWork::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'user_id' => $this->userId,
                                        'type' => $statUserResultType__,
                                        'action' => StatResultUser::ACTION_ALLOW
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultUser::updateCounter($where, $statCounters);
                                }
                            }
                        }

                    }
                    if($stat_)
                    {
                        foreach ($stat_ as $statResultAction__ => $statResults__)
                        {
                            foreach ($statResults__ as $statResultType__ => $statResultVal__)
                            {
                                //统计驳回标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'type' => $statResultType__,
                                    'action' => StatResultData::ACTION_ALLOW
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResultData::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'type' => $statResultType__,
                                    'action' => StatResult::ACTION_ALLOW
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResult::updateCounter($where, $statCounters);
                            }
                        }
                    }


                    //更改每一步结果为结束
                    $attributes = [
                        'is_correct' => 0,//结果不正确
                        'status' => Work::STATUS_FINISH,
                        'type' => Work::TYPE_AUDITIGNORED,
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work_['id']]);
                    
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_AUDITIGNORED;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_FINISH;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskAllow($work_['user_id'], $this->projectId, $task_['id'], $dataId);
                }
            }
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 审核某分步的作业, 执行驳回
     * 将对其父分步的作业打回原作业者
     * 作业数据保留!!!!
     *
     * @param int $dataId
     * @param string $feedback
     * @return boolean
     */
    public function auditRefuse($dataId, $feedback = '')
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
        
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        //此处需要多个状态, 因批量审核,无法确定状态
        if (!in_array($work['status'], [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING, Work::STATUS_SUBMITED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'task_type_not_allow', yii::t('app', 'task_type_not_allow'));
        }
        
        $attributes = [
            'user_id' => $this->userId,
            'status' => Work::STATUS_DELETED,//只有通过才算有效审核
            'type' => Work::TYPE_AUDITREFUSE,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
        
        //-----------------------------
        
        $workTime = 0;
        if ($work['start_time'] > 0)
        {
            $workTime = (time() - $work['start_time']);
        }
        
        $counters = [
            'work_time' => $workTime,
            'amount' => -1,
            'work_count' => 0,//此时不增加提交量, 审核只有通过时才算有效数
            'submit_count' => 1,
            'refuse_count' => 1,
        ];
        //挂起作业,增加挂起作业已修正数
        if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
        {
            //$counters['difficult_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
        {
            $counters['refused_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        $counters = [
            'work_time' => $workTime,
            'work_count' => 0,//此时不增加提交量, 审核只有通过时才算有效数
            'submit_count' => 1,
            'refuse_count' => 1
        ];
        //挂起作业,增加挂起作业已修正数
        if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
        {
            //$counters['difficult_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
        {
            $counters['refused_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
        
        //Message::sendTaskRefuse($work['user_id'], $this->projectId, $this->task['id'], $dataId, $feedback);
        
        //-----------------------------
        
        foreach ($this->stepParentIds as $stepId_)
        {
            $_logs['$stepId_'] = $stepId_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
            
            if (!$stepId_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
                continue;
            }
            
            //分发作业的结果
            $work_ = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
            $_logs['$work_'] = $work_;
            
            if (!$work_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ not exist '.json_encode($_logs));
                continue;
            }
            
            if (!in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                continue;
            }
            
            //统计框数, 矫正框数
            $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
            
            $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
            if (!$task_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                continue;
            }
            
            $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
            if (!$step_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                continue;
            }
            
            if ($step_['type'] == Step::TYPE_PRODUCE)
            {
                $counters = [
                    'work_count' => -1,
                    'refused_count' => 1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,

                ];
                if(empty($work_['is_refused']))
                {
                    $counters['audited_count'] = 1;//被审核张数
                }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
                
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => -1,
                        'join_count' => 0,
                        'refused_count' => 1,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    if(empty($work_['is_refused']))
                    {
                        $counters['audited_count'] = 1;//被审核张数
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
                

                $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                $result_ = JsonHelper::json_decode_all($workResult_['result']);

                list($stat_, $statUser_) = ProjectHandler::statResult($result_);
                
                //标注绩效统计
                if(!empty($statUser_))
                {
                    if($work_['status'] == Work::STATUS_FINISH)
                    {
                        $produceAction = StatResultWork::ACTION_REFUSED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $produceAction = StatResultWork::ACTION_REFUSED;
                    }
                    foreach($statUser_ as $statUserId_ => $statUserResult_)
                    {
                        foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                        {
                            foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                            {
                                //统计执行被驳回标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $task_['id'],
                                    'data_id' => $dataId,
                                    'work_id' => $work_['id'],
                                    'user_id' => $statUserId_,
                                    'type' => $statUserResultType__,
                                    'action' => $produceAction //执行 被驳回 有效或无效
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultWork::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $task_['id'],
                                    'user_id' => $statUserId_,
                                    'type' => $statUserResultType__,
                                    'action' => $produceAction
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultUser::updateCounter($where, $statCounters);

                                //统计驳回标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'work_id' => $work['id'],
                                    'user_id' => $this->userId,
                                    'type' => $statUserResultType__,
                                    'action' => StatResultWork::ACTION_REFUSE //审核 驳回
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultWork::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'user_id' => $this->userId,
                                    'type' => $statUserResultType__,
                                    'action' => StatResultWork::ACTION_REFUSE
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultUser::updateCounter($where, $statCounters);
                            }
                        }
                    }
                }
                if(!empty($stat_))
                {
                    if($work_['status'] == Work::STATUS_FINISH)
                    {
                        $produceAction = StatResultWork::ACTION_REFUSED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $produceAction = StatResultWork::ACTION_REFUSED;
                    }

                    foreach ($stat_ as $statResultAction_ => $statResult__)
                    {
                        foreach ($statResult__ as $statResultType__ => $statResultVal__)
                        {
                            //统计执行被驳回标注绩效
                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $task_['id'],
                                'data_id' => $dataId,
                                'type' => $statResultType__,
                                'action' => $produceAction
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResultData::updateCounter($where, $statCounters);

                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $task_['id'],
                                'type' => $statResultType__,
                                'action' => $produceAction
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResult::updateCounter($where, $statCounters);

                            //统计驳回标注绩效
                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'data_id' => $dataId,
                                'type' => $statResultType__,
                                'action' => StatResultData::ACTION_REFUSE
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResultData::updateCounter($where, $statCounters);

                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'type' => $statResultType__,
                                'action' => StatResult::ACTION_REFUSE
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResult::updateCounter($where, $statCounters);
                        }
                    }
                }
            }
            elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $counters = [
                    'work_count'=> -1,
                    'refused_count' => 1,
                    'allow_count' => -1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                if(empty($work_['is_refused']))
                {
                    $counters['audited_count'] = 1;//被审核张数
                }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
                
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_count' => -1,
                        'refused_count' => 1,
                        'allow_count' => -1,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    if(empty($work_['is_refused']))
                    {
                        $counters['audited_count'] = 1;//被审核张数
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
                
                //---grand father start------------------------------------
                
                //连带其上一步操作
                $stepParentIds_ = Step::getParentStepIds($stepId_);
                if ($stepParentIds_)
                {
                    foreach ($stepParentIds_ as $stepId__)
                    {
                        $_logs['$stepId__'] = $stepId__;
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
                    
                        if (!$stepId__)
                        {
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId__ 0 '.json_encode($_logs));
                            continue;
                        }
                    
                        //分发作业的结果
                        $work__ = Work::find()
                        ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $stepId__, 'data_id' => $dataId])
                        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
                        ->orderBy(['id' => SORT_DESC])
                        ->asArray()->limit(1)->one();
                        $_logs['$work__'] = $work__;
                    
                        if (!$work__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work__ not exist '.json_encode($_logs));
                            continue;
                        }
                    
                        if (!in_array($work__['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                            continue;
                        }
                        
                        //查询分发结果
                        $workResult__ = WorkResult::find()->where(['work_id' => $work__['id']])->asArray()->limit(1)->one();
                        
                        if (!$workResult__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult__ not exist '.json_encode($_logs));
                            continue;
                        }
                        
                        //统计本次作业的结果
                        list($stat__, $statUser__) = ProjectHandler::statResult($workResult__['result']);
                    
                        $task__ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId__,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
                        if (!$task__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task__ record '.json_encode($_logs));
                            continue;
                        }
                    
                        $step__ = Step::find()->where(['id' => $stepId__])->asArray()->limit(1)->one();
                        if (!$step__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step__ record '.json_encode($_logs));
                            continue;
                        }
                    
                        if ($step__['type'] == Step::TYPE_PRODUCE)
                        {
                            $counters = [
                                'work_count' => 0,
                                'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                            ];
                            Stat::updateCounter($this->projectId, $this->batchId, $stepId__, $counters);
                            
                            if ($statUser__)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED;
                                }
                                foreach($statUser__ as $statUserId__ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultAction__ => $statUserResults__)
                                    {
                                        foreach ($statUserResults__ as $statUserResultType__ => $statUserResultVal__)
                                        {
                                            //统计被驳回标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_ //审核 被驳回 有效或无效
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_ //审核 被驳回 有效或无效
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);

                                            //统计驳回标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultWork::ACTION_REFUSE
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultUser::ACTION_REFUSE
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);
                                        }
                                    }

                                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId__, $statUserId__, $counters);
                                }

                            }
                            if($stat__)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED;
                                }
                                foreach ($stat__ as $statResultAction__ => $statResults__)
                                {
                                    foreach ($statResults__ as $statResultType__ => $statResultVal__)
                                    {
                                        //统计被驳回标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => $auditAction_ //审核 被驳回 有效或无效
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'type' => $statResultType__,
                                            'action' => $auditAction_ //审核 被驳回 有效或无效
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);

                                        //统计驳回标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => StatResultData::ACTION_REFUSE
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'type' => $statResultType__,
                                            'action' => StatResult::ACTION_REFUSE
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);
                                    }
                                }
                            }
                            
                            if (!empty($work__['user_id']))
                            {
                                $counters = [
                                    'work_count' => 0,
                                    'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                                ];
                                StatUser::updateCounter($this->projectId, $this->batchId, $stepId__, $work__['user_id'], $counters);
                            }
                    
                        }
                        elseif (in_array($step__['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
                        {
                            $counters = [
                                'work_count' => 0,
                                'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                            ];
                            Stat::updateCounter($this->projectId, $this->batchId, $stepId__, $counters);
                    
                            if (!empty($work__['user_id']))
                            {
                                $counters = [
                                    'work_count' => 0,
                                    'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                                ];
                                StatUser::updateCounter($this->projectId, $this->batchId, $stepId__, $work__['user_id'], $counters);
                            }


                            $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
                            list($stat, $statUser) = ProjectHandler::statResult($dataResult['result']);

                            if ($statUser)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED;
                                }
                                foreach($statUser as $statUserId__ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultAction__ => $statUserResults__)
                                    {
                                        foreach ($statUserResults__ as $statUserResultType__ => $statUserResultVal__)
                                        {
                                            //统计被驳回标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_ //审核 被驳回 有效或无效
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_ //审核 被驳回 有效或无效
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);

                                            //统计驳回标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultWork::ACTION_REFUSE
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultUser::ACTION_REFUSE
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);
                                        }
                                    }

                                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId__, $statUserId__, $counters);
                                }

                            }
                            if($stat)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_REFUSED;
                                }
                                foreach ($stat as $statResultAction__ => $statResults__)
                                {
                                    foreach ($statResults__ as $statResultType__ => $statResultVal__)
                                    {
                                        //统计被驳回标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => $auditAction_ //审核 被驳回 有效或无效
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'type' => $statResultType__,
                                            'action' => $auditAction_ //审核 被驳回 有效或无效
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);

                                        //统计驳回标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => StatResultData::ACTION_REFUSE
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'type' => $statResultType__,
                                            'action' => StatResult::ACTION_REFUSE
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);
                                    }
                                }
                            }
                        }
                        
                        // //更改为已提交
                        // $attributes = [
                        //     'status' => Work::STATUS_SUBMITED,
                        //     //'type' => Work::TYPE_AUDITREFUSED,
                        //     'updated_at' => time()
                        // ];
                        // Work::updateAll($attributes, ['id' => $work__['id']]);
                        
                        // //添加作业记录
                        // $workRecord = new WorkRecord();
                        // $workRecord->project_id = $this->projectId;
                        // $workRecord->work_id = $work__['id'];
                        // $workRecord->data_id = $dataId;
                        // $workRecord->batch_id = $work__['batch_id'];
                        // $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
                        // $workRecord->task_id = $task__['id'];
                        // $workRecord->type = WorkRecord::TYPE_BACKTOSUBMIT;
                        // $workRecord->after_user_id = $this->userId;
                        // $workRecord->after_work_status = Work::STATUS_SUBMITED;
                        // $workRecord->before_user_id = $work__['user_id'];
                        // $workRecord->before_work_status = $work__['status'];
                        // $workRecord->created_at = time();
                        // $workRecord->updated_at = time();
                        // $workRecord->save();
                        
                    }
                }
                
                //----grand father end-----------------------------------
            }
            
            //----------------------------------------------------
            $transaction = Yii::$app->db->beginTransaction();
            
            //分发作业重新开启
            $attributes = [
                'status' => Work::STATUS_DELETED,
                'type' => Work::TYPE_AUDITREFUSED,
                'updated_at' => time()
            ];
            Work::updateAll($attributes, ['id' => $work_['id']]);
            
            //重新生成记录
            $workNew = new Work();
            $workNew->project_id = $work_['project_id'];
            $workNew->batch_id = $work_['batch_id'];
            $workNew->step_id = $work_['step_id'];
            $workNew->data_id = $work_['data_id'];
            $workNew->user_id = $work_['user_id'];
            $workNew->status = Work::STATUS_REFUSED;
            $workNew->type = Work::TYPE_AUDITREFUSED;
            $workNew->is_refused = 1;
            $workNew->created_at = time();
            $workNew->updated_at = time();
            $workNew->save();
            
            $transaction->commit();
            //------------------------------------------------------
            
            $attributes = [
                'feedback' => $feedback
            ];
            WorkResult::updateAll($attributes, ['work_id' => $work_['id']]);
            
            //保存驳回理由
            $workResultNew = new WorkResult();
            $workResultNew->work_id = $workNew->id;
            $workResultNew->result = $workResult_['result'];
            $workResultNew->feedback = $feedback;
            $workResultNew->save();
            
            //添加作业记录
            $workRecord = new WorkRecord();
            $workRecord->project_id = $this->projectId;
            $workRecord->work_id = $work_['id'];
            $workRecord->data_id = $dataId;
            $workRecord->batch_id = $work_['batch_id'];
            $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
            $workRecord->task_id = $task_['id'];
            $workRecord->type = WorkRecord::TYPE_AUDITREFUSED;
            $workRecord->after_user_id = $this->userId;
            $workRecord->after_work_status = Work::STATUS_REFUSED;
            $workRecord->before_user_id = $work_['user_id'];
            $workRecord->before_work_status = $work_['status'];
            $workRecord->created_at = time();
            $workRecord->updated_at = time();
            $workRecord->save();
            
            //====================================
            
            Message::sendTaskRefuse($work_['user_id'], $work_['project_id'], $task_['id'], $dataId, $feedback);
        }
        
        
        //---------------------------------------
        
        // foreach ($this->stepChildIds as $stepId_) //什么意思？？？
        // {
        //     $_logs['$stepId_'] = $stepId_;
        //     Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
        
        //     if (!$stepId_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
        //         continue;
        //     }
        
        //     //判断是否可执行
        //     if (!Step::checkCondition($stepId_, $work['id']))
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition not allow '.json_encode($_logs));
        //         continue;
        //     }
        
        //     //分发作业的结果
        //     $work_ = Work::find()
        //     ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
        //     ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        //     ->orderBy(['id' => SORT_DESC])
        //     ->asArray()->limit(1)->one();
        //     $_logs['$work'] = $work_;
        
        //     if (!$work_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ not exist '.json_encode($_logs));
        //         continue;
        //     }
        
        //     $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
        //     if (!$task_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
        //         continue;
        //     }
            
        //     $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
        //     if (!$step_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
        //         continue;
        //     }
            
        //     //-------------------------------
        
        //     //分发作业重新开启
        //     $attributes = [
        //         'status' => Work::STATUS_DELETED,
        //         'type' => Work::TYPE_PARENTREFUSED,
        //         'updated_at' => time()
        //     ];
        //     Work::updateAll($attributes, ['id' => $work_['id']]);
            
        //     //添加作业记录
        //     $workRecord = new WorkRecord();
        //     $workRecord->project_id = $this->projectId;
        //     $workRecord->work_id = $work_['id'];
        //     $workRecord->data_id = $dataId;
        //     $workRecord->batch_id = $work_['batch_id'];
        //     $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
        //     $workRecord->task_id = $task_['id'];
        //     $workRecord->type = WorkRecord::TYPE_PARENTREFUSED;
        //     $workRecord->after_user_id = $this->userId;
        //     $workRecord->after_work_status = Work::STATUS_DELETED;
        //     $workRecord->before_user_id = $work_['user_id'];
        //     $workRecord->before_work_status = $work_['status'];
        //     $workRecord->created_at = time();
        //     $workRecord->updated_at = time();
        //     $workRecord->save();
            
        //     Message::sendTaskRefuse($work_['user_id'], $work_['project_id'], $task_['id'], $dataId, $feedback);
            
        
        //     //====================================
            
        //     if ($step_['type'] == Step::TYPE_PRODUCE)
        //     {
        //         $counters = [
        //             'amount' => -1,
        //             'work_count' => in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
        //             'refused_count' => 1,
        //             'allowed_count' => in_array($work_['status'], [Work::STATUS_FINISH]) ? -1 : 0,
        //         ];
        //         Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
        
        //         if (!empty($work_['user_id']))
        //         {
        //             $counters = [
        //                 'work_time' => 0,
        //                 'work_count' => -1,
        //                 'join_count' => 0,
        //                 'refused_count' => 1,
        //                 'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
        //             ];
        //             StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
        //         }
        //     }
        //     elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        //     {
        //         $counters = [];
        //         $counters['amount'] = -1;
        //         $counters['work_count'] = in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0;
        //         $counters['refused_count'] = 1;
        //         $counters['allowed_count'] = ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0;
        //         Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
        
        //         if (!empty($work_['user_id']))
        //         {
        //             $counters = [];
        //             $counters['work_count'] = in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0;
        //             $counters['refused_count'] = 1;
        //             $counters['allowed_count'] = ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0;
        //             StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
        //         }
        //     }
        // }
        
        //---------------------------
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 审核某分步的作业, 执行驳回
     * 将对其父分步的作业不打回原作业者, 打回任务池
     * 作业数据保留!!!!
     *
     * @param int $dataId
     */
    public function auditReset($dataId, $feedback = '')
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
    
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        //此处需要多个状态, 因批量审核,无法确定状态; 领取未提交也可以重置
        if (!in_array($work['status'], [Work::STATUS_NEW, Work::STATUS_RECEIVED, Work::STATUS_EXECUTING, Work::STATUS_SUBMITED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_work_status_not_allow '.json_encode($_logs));
            return FormatHelper::result('', 'task_work_status_not_allow', yii::t('app', 'task_work_status_not_allow'));
        }
        
        if (!in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'task_type_not_allow', yii::t('app', 'task_type_not_allow'));
        }
        
        //---------------------------
    
        $attributes = [
            'user_id' => $this->userId,
            'status' => Work::STATUS_DELETED,//只有通过才算有效审核
            'type' => Work::TYPE_AUDITRESET,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
        
        $workTime = 0;
        if ($work['start_time'] > 0)
        {
            $workTime = (time() - $work['start_time']);
        }
    
        $counters = [
            'work_time' => $workTime,
            'amount' => -1,
            'work_count' => 0,//此时不增加作业量, 只有通过时才算有效数
            'submit_count' => 1,
            'reset_count' => 1
        ];
        //挂起作业,增加挂起作业已修正数
        if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
        {
            //$counters['difficult_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
        {
            $counters['refused_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        $counters = [
            'work_time' => $workTime,
            'work_count' => 0,//此时不增加作业量, 只有通过时才算有效数
            'submit_count' => 1,
            'reset_count' => 1
        ];
        //挂起作业,增加挂起作业已修正数
        if (in_array($work['type'], [Work::TYPE_DIFFICULT]))
        {
            //$counters['difficult_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
        {
            $counters['refused_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        if (in_array($work['type'], [Work::TYPE_REFUSESUBMITREVISE]))
        {
            $counters['refuse_revise_count'] = 1;
        }
        StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $this->userId, $counters);
    
        //Message::sendTaskReset($work['user_id'], $this->projectId, $this->task['id'], $dataId, $feedback);
        //-----------------------------
    
        foreach ($this->stepParentIds as $stepId_)
        {
            $_logs['$stepId_'] = $stepId_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
    
            if (!$stepId_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
                continue;
            }
    
            //分发作业的结果
            $work_ = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
    
            if (!$work_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ not exist '.json_encode($_logs));
                continue;
            }
            
            if (!in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                continue;
            }
            
            $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
            
            $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
            if (!$task_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                continue;
            }
            
            $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
            if (!$step_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                continue;
            }

            //---------------------------------
            
            if ($step_['type'] == Step::TYPE_PRODUCE)
            {
                $result_ = JsonHelper::json_decode_all($workResult_['result']);
                list($stat_, $statUser_) = ProjectHandler::statResult($result_);
                $_logs['$stat_'] = $stat_;
                $_logs['$statUser_'] = $statUser_;
                //标注绩效统计
                if(!empty($statUser_))
                {
                    if(in_array($work_['status'], [Work::STATUS_FINISH]))
                    {
                        $produceAction = StatResultWork::ACTION_RESETED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $produceAction = StatResultWork::ACTION_RESETED;
                    }
                    foreach($statUser_ as $statUserId_ => $statUserResult_)
                    {
                        foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                        {
                            foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                            {
                                //统计被重置标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $task_['id'],
                                    'data_id' => $dataId,
                                    'work_id' => $work_['id'],
                                    'user_id' => $statUserId_,
                                    'type' => $statUserResultType__,
                                    'action' => $produceAction //执行 被重置 有效或无效
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultWork::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $task_['id'],
                                    'user_id' => $statUserId_,
                                    'type' => $statUserResultType__,
                                    'action' => $produceAction
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultUser::updateCounter($where, $statCounters);

                                //统计重置标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'work_id' => $work['id'],
                                    'user_id' => $this->userId,
                                    'type' => $statUserResultType__,
                                    'action' => StatResultWork::ACTION_RESET
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultWork::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'user_id' => $this->userId,
                                    'type' => $statUserResultType__,
                                    'action' => StatResultUser::ACTION_RESET
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultUser::updateCounter($where, $statCounters);
                            }
                        }
                    }
                }
                if(!empty($stat_))
                {
                    if(in_array($work_['status'], [Work::STATUS_FINISH]))
                    {
                        $produceAction = StatResultWork::ACTION_RESETED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $produceAction = StatResultWork::ACTION_RESETED;
                    }
                    foreach ($stat_ as $statResultAction_ => $statResult__)
                    {
                        foreach ($statResult__ as $statResultType__ => $statResultVal__)
                        {
                            //统计被重置标注绩效
                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $task_['id'],
                                'data_id' => $dataId,
                                'type' => $statResultType__,
                                'action' => $produceAction
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResultData::updateCounter($where, $statCounters);

                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $task_['id'],
                                'type' => $statResultType__,
                                'action' => $produceAction
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResult::updateCounter($where, $statCounters);

                            //统计重置标注绩效
                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'data_id' => $dataId,
                                'type' => $statResultType__,
                                'action' => StatResultData::ACTION_RESET
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResultData::updateCounter($where, $statCounters);

                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'type' => $statResultType__,
                                'action' => StatResult::ACTION_RESET
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResult::updateCounter($where, $statCounters);
                        }
                    }
                }


                //一定是已提交, 不是已通过, 所以不需要减绩效
                $counters = [
                    'work_count' => -1,
                    'reseted_count' => 1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                if(empty($work_['is_refused']))
                {
                    $counters['audited_count'] = 1;//被审核张数
                }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
                
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => -1,
                        'join_count' => 0,
                        'reseted_count' => 1,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    if(empty($work_['is_refused']))
                    {
                        $counters['audited_count'] = 1;//被审核张数
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
            elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $counters = [
                    'work_count' => -1,
                    'reseted_count' => 1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                if(empty($work_['is_refused']))
                {
                    $counters['audited_count'] = 1;//被审核张数
                }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
                
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_count' => -1,
                        'reseted_count' => 1,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    if(empty($work_['is_refused']))
                    {
                        $counters['audited_count'] = 1;//被审核张数
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
                
                //---grand father start------------------------------------
                
                //连带其上一步操作
                $stepParentIds_ = Step::getParentStepIds($stepId_);
                if ($stepParentIds_)
                {
                    foreach ($stepParentIds_ as $stepId__)
                    {
                        $_logs['$stepId__'] = $stepId__;
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
                
                        if (!$stepId__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId__ 0 '.json_encode($_logs));
                            continue;
                        }
                
                        //分发作业的结果
                        $work__ = Work::find()
                        ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $stepId__, 'data_id' => $dataId])
                        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
                        ->orderBy(['id' => SORT_DESC])
                        ->asArray()->limit(1)->one();
                        $_logs['$work__'] = $work__;
                
                        if (!$work__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work__ not exist '.json_encode($_logs));
                            continue;
                        }
                
                        if (!in_array($work__['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                            continue;
                        }
                
                        //查询分发结果
                        $workResult__ = WorkResult::find()->where(['work_id' => $work__['id']])->asArray()->limit(1)->one();
                
                        if (!$workResult__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult__ not exist '.json_encode($_logs));
                            continue;
                        }
                
                        //统计本次作业的结果
                        list($stat__, $statUser__) = ProjectHandler::statResult($workResult__['result']);
                
                        $task__ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId__,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
                        if (!$task__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task__ record '.json_encode($_logs));
                            continue;
                        }
                
                        $step__ = Step::find()->where(['id' => $stepId__])->asArray()->limit(1)->one();
                        if (!$step__)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step__ record '.json_encode($_logs));
                            continue;
                        }
                
                        if ($step__['type'] == Step::TYPE_PRODUCE)
                        {
                            $counters = [
                                'work_count' => 0,
                                'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                            ];
                            Stat::updateCounter($this->projectId, $this->batchId, $stepId__, $counters);
                
                            if ($statUser__)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED;
                                }
                                foreach ($statUser__ as $statUserId__ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultAction__ => $statUserResults__)
                                    {
                                        foreach ($statUserResults__ as $statUserResultType__ => $statUserResultVal__)
                                        {
                                            //统计被重置标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);

                                            //统计重置标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultWork::ACTION_RESET
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultUser::ACTION_RESET
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);

                                            
                                        }
                                    }
                                }
                            }
                            if($stat__)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED;
                                }
                                foreach ($stat__ as $statResultAction__ => $statResults__)
                                {
                                    foreach ($statResults__ as $statResultType__ => $statResultVal__)
                                    {
                                        //统计被重置标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => $auditAction_
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'type' => $statResultType__,
                                            'action' => $auditAction_
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);

                                        //统计重置标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => StatResultData::ACTION_RESET
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'type' => $statResultType__,
                                            'action' => StatResult::ACTION_RESET
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);
                                    }
                                }
                            }
                
                            if (!empty($work__['user_id']))
                            {
                                $counters = [
                                    'work_count' => 0,
                                    'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                                ];
                                StatUser::updateCounter($this->projectId, $this->batchId, $stepId__, $work__['user_id'], $counters);
                            }

                        }
                        elseif (in_array($step__['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
                        {
                            $counters = [
                                'work_count' => 0,
                                'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                            ];
                            Stat::updateCounter($this->projectId, $this->batchId, $stepId__, $counters);
                
                            if (!empty($work__['user_id']))
                            {
                                $counters = [
                                    'work_count' => 0,
                                    'allowed_count' => ($work__['status'] == Work::STATUS_FINISH) ? -1 : 0,
                                ];
                                StatUser::updateCounter($this->projectId, $this->batchId, $stepId__, $work__['user_id'], $counters);
                            }


                            $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
                            list($stat, $statUser) = ProjectHandler::statResult($dataResult['result']);

                            if ($statUser)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED;
                                }
                                foreach ($statUser as $statUserId__ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultAction__ => $statUserResults__)
                                    {
                                        foreach ($statUserResults__ as $statUserResultType__ => $statUserResultVal__)
                                        {
                                            //统计被重置标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $task_['id'],
                                                'user_id' => $work_['user_id'],
                                                'type' => $statUserResultType__,
                                                'action' => $auditAction_
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);

                                            //统计重置标注绩效
                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'data_id' => $dataId,
                                                'work_id' => $work['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultWork::ACTION_RESET
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultWork::updateCounter($where, $statCounters);

                                            $where = [
                                                'project_id' => $this->projectId,
                                                'task_id' => $this->task['id'],
                                                'user_id' => $this->userId,
                                                'type' => $statUserResultType__,
                                                'action' => StatResultUser::ACTION_RESET
                                            ];
                                            $statCounters = [
                                                'value' => $statUserResultVal__
                                            ];
                                            StatResultUser::updateCounter($where, $statCounters);
                                        }
                                    }
                                }
                            }
                            if($stat)
                            {
                                if(in_array($work_['status'], [Work::STATUS_FINISH]))
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED_AFTER_ALLOWED;
                                }
                                else
                                {
                                    $auditAction_ = StatResultWork::ACTION_RESETED;
                                }
                                foreach ($stat as $statResultAction__ => $statResults__)
                                {
                                    foreach ($statResults__ as $statResultType__ => $statResultVal__)
                                    {
                                        //统计被重置标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => $auditAction_
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'type' => $statResultType__,
                                            'action' => $auditAction_
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);

                                        //统计重置标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'data_id' => $dataId,
                                            'type' => $statResultType__,
                                            'action' => StatResultData::ACTION_RESET
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResultData::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $this->task['id'],
                                            'type' => $statResultType__,
                                            'action' => StatResult::ACTION_RESET
                                        ];
                                        $statCounters = [
                                            'value' => $statResultVal__
                                        ];
                                        StatResult::updateCounter($where, $statCounters);
                                    }
                                }
                            }
                        }
                
                        // //更改为已提交
                        // $attributes = [
                        //     'status' => Work::STATUS_SUBMITED,
                        //     //'type' => Work::TYPE_AUDITREFUSED,
                        //     'updated_at' => time()
                        // ];
                        // Work::updateAll($attributes, ['id' => $work__['id']]);
                
                        // //添加作业记录
                        // $workRecord = new WorkRecord();
                        // $workRecord->project_id = $this->projectId;
                        // $workRecord->work_id = $work__['id'];
                        // $workRecord->data_id = $dataId;
                        // $workRecord->batch_id = $work__['batch_id'];
                        // $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
                        // $workRecord->task_id = $task__['id'];
                        // $workRecord->type = WorkRecord::TYPE_BACKTOSUBMIT;
                        // $workRecord->after_user_id = $this->userId;
                        // $workRecord->after_work_status = Work::STATUS_SUBMITED;
                        // $workRecord->before_user_id = $work__['user_id'];
                        // $workRecord->before_work_status = $work__['status'];
                        // $workRecord->created_at = time();
                        // $workRecord->updated_at = time();
                        // $workRecord->save();
                
                    }
                }
                
                //----grand father end-----------------------------------
            }
    
            //----------------------------------------------------
            $transaction = Yii::$app->db->beginTransaction();
    
            //分发作业重新开启
            $attributes = [
                'status' => Work::STATUS_DELETED,
                'type' => Work::TYPE_AUDITRESETED,
                'updated_at' => time()
            ];
            Work::updateAll($attributes, ['id' => $work_['id']]);
    
            //重新生成记录
            $workNew = new Work();
            $workNew->project_id = $work_['project_id'];
            $workNew->batch_id = $work_['batch_id'];
            $workNew->step_id = $work_['step_id'];
            $workNew->data_id = $work_['data_id'];
            $workNew->status = Work::STATUS_NEW;
            $workNew->type = Work::TYPE_NORMAL;
            $workNew->created_at = time();
            $workNew->updated_at = time();
            $workNew->save();
            
            $transaction->commit();
            //------------------------------------------------------
            
            //保存驳回理由
            $workResultNew = new WorkResult();
            $workResultNew->work_id = $workNew->id;
            $workResultNew->result = $workResult_['result'];
            $workResultNew->feedback = $feedback;
            $workResultNew->save();
    
            //追加作业到数据池
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($work_['project_id'], $work_['batch_id'], $stepId_, $this->userId);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_userNoPermission '.json_encode($_logs));
                continue;
            }
            $taskHandler->appendData($dataId);
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setUserCache '. json_encode($_logs));
    
            //添加作业记录
            $workRecord = new WorkRecord();
            $workRecord->project_id = $this->projectId;
            $workRecord->work_id = $work_['id'];
            $workRecord->data_id = $dataId;
            $workRecord->batch_id = $work_['batch_id'];
            $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
            $workRecord->task_id = $task_['id'];
            $workRecord->type = WorkRecord::TYPE_AUDITRESETED;
            $workRecord->after_user_id = $this->userId;
            $workRecord->after_work_status = Work::STATUS_NEW;
            $workRecord->before_user_id = $work_['user_id'];
            $workRecord->before_work_status = $work_['status'];
            $workRecord->created_at = time();
            $workRecord->updated_at = time();
            $workRecord->save();
            
            //====================================
            
            Message::sendTaskReset($work_['user_id'], $work_['project_id'], $task_['id'], $dataId);
        }
        
        
        //---------------------------------------
        
        // foreach ($this->stepChildIds as $stepId_)
        // {
        //     $_logs['$stepId_'] = $stepId_;
        //     Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
        
        //     if (!$stepId_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
        //         continue;
        //     }
        
        //     //判断是否可执行
        //     if (!Step::checkCondition($stepId_, $work['id']))
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition not allow '.json_encode($_logs));
        //         continue;
        //     }
        
        //     //分发作业的结果
        //     $work_ = Work::find()
        //     ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
        //     ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        //     ->orderBy(['id' => SORT_DESC])
        //     ->asArray()->limit(1)->one();
        //     $_logs['$work'] = $work_;
        
        //     if (!$work_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $work record '.json_encode($_logs));
        //         continue;
        //     }
        
        //     $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
        //     if (!$task_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
        //         continue;
        //     }
            
        //     $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
        //     if (!$step_)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
        //         continue;
        //     }
        //     //-------------------------------
        
        //     //分发作业重新开启
        //     $attributes = [
        //         'status' => Work::STATUS_DELETED,
        //         'type' => Work::TYPE_PARENTRESETED,
        //         'updated_at' => time()
        //     ];
        //     Work::updateAll($attributes, ['id' => $work_['id']]);
            
        //     //添加作业记录
        //     $workRecord = new WorkRecord();
        //     $workRecord->project_id = $this->projectId;
        //     $workRecord->work_id = $work_['id'];
        //     $workRecord->data_id = $dataId;
        //     $workRecord->batch_id = $work_['batch_id'];
        //     $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
        //     $workRecord->task_id = $task_['id'];
        //     $workRecord->type = WorkRecord::TYPE_PARENTRESETED;
        //     $workRecord->after_user_id = $this->userId;
        //     $workRecord->after_work_status = Work::STATUS_DELETED;
        //     $workRecord->before_user_id = $work_['user_id'];
        //     $workRecord->before_work_status = $work_['status'];
        //     $workRecord->created_at = time();
        //     $workRecord->updated_at = time();
        //     $workRecord->save();
        
        //     //====================================
            
        //     if ($step_['type'] == Step::TYPE_PRODUCE)
        //     {
        //         //统计框数, 矫正框数
        //         $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
        //         list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
        
        //         $counters = [
        //             'amount' => -1,
        //             'work_count' => in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
        //             'reseted_count' => 1,
        //             'allowed_count' => in_array($work_['status'], [Work::STATUS_FINISH]) ? -1 : 0,
        //         ];
        //         Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
        
        //         if (!empty($work_['user_id']))
        //         {
        //             $counters = [
        //                 'work_time' => 0,
        //                 'work_count' => -1,
        //                 'join_count' => 0,
        //                 'reseted_count' => 1,
        //                 'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
        //             ];
        //             StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
        //         }
        //     }
        //     elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        //     {
        //         $counters = [];
        //         $counters['amount'] = -1;
        //         $counters['work_count'] = in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0;
        //         $counters['reseted_count'] = 1;
        //         $counters['allowed_count'] = ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0;
        //         Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
        
        //         if (!empty($work_['user_id']))
        //         {
        //             $counters = [];
        //             $counters['work_count'] = in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0;
        //             $counters['reseted_count'] = 1;
        //             $counters['allowed_count'] = ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0;
        //             StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
        //         }
        //     }
            
        //     Message::sendTaskReset($work_['user_id'], $work_['project_id'], $task_['id'], $dataId);
        // }
        
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对已提交的作业强制驳回作业
     * 管理员或作业员驳回作业,
     * 将清除其所有的子分步
     * 
     */
    public function redo($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
        
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_EXECUTING, Work::STATUS_SUBMITED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
        
        //统计框数, 矫正框数
        $workResult_ = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
        
        //是执行
        if ($this->step['type'] == Step::TYPE_PRODUCE)
        {
            $counters = [
                'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                'refused_count' => 0,
                'allow_count' => 0,
                'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
            ];

            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
            
            if ($statUser_)
            {
                foreach ($statUser_ as $statUserId_ => $statUserResult_)
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => 0,
                        'join_count' => -1,
                        'refused_count' => 0,
                        'allow_count' => 0,
                        'allowed_count' => 0,
                    ];

                    StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $statUserId_, $counters);

                    //统计标注绩效
                    foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                    {
                        foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                        {
                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'data_id' => $dataId,
                                'work_id' => $work['id'],
                                'user_id' => $statUserId_,
                                'type' => $statUserResultType__,
                                'action' => StatResultWork::ACTION_REDO //执行 重做
                            ];
                            $statCounters = [
                                'value' => $statUserResultVal__
                            ];
                            StatResultWork::updateCounter($where, $statCounters);

                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'user_id' => $statUserId_,
                                'type' => $statUserResultType__,
                                'action' => StatResultUser::ACTION_REDO
                            ];
                            $statCounters = [
                                'value' => $statUserResultVal__
                            ];
                            StatResultUser::updateCounter($where, $statCounters);
                        }
                    }
                }
            }
            if($stat_)
            {
                foreach ($stat_ as $statResultAction_ => $statResult__)
                {
                    foreach ($statResult__ as $statResultType__ => $statResultVal__)
                    {
                        $where = [
                            'project_id' => $this->projectId,
                            'task_id' => $this->task['id'],
                            'data_id' => $dataId,
                            'type' => $statResultType__,
                            'action' => StatResultData::ACTION_REDO
                        ];
                        $statCounters = [
                            'value' => $statResultVal__
                        ];
                        StatResultData::updateCounter($where, $statCounters);

                        $where = [
                            'project_id' => $this->projectId,
                            'task_id' => $this->task['id'],
                            'type' => $statResultType__,
                            'action' => StatResult::ACTION_REDO
                        ];
                        $statCounters = [
                            'value' => $statResultVal__
                        ];
                        StatResult::updateCounter($where, $statCounters);
                    }
                }
            }


            if (!empty($work['user_id']))
            {
                $counters = [
                    'work_time' => 0,
                    'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                    'join_count' => 0,
                    'refused_count' => 0,
                    'allow_count' => 0,
                    'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
            }
        }
        elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            $counters = [
                'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                'refused_count' => 0,
                'allow_count' => -1,
                'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
            ];

            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
            
            if (!empty($work['user_id']))
            {
                $counters = [
                    'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                    'refused_count' => 0,
                    'allow_count' => -1,
                    'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];

                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
            }
            
        }
        
        
        foreach ($this->stepParentIds as $stepId_)
        {
            $_logs['$stepId_'] = $stepId_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
        
            if (!$stepId_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
                continue;
            }
        
            //分发作业的结果
            $work_ = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
        
            if (!$work_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ not exist '.json_encode($_logs));
                continue;
            }
        
            if (!in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                continue;
            }
        
            $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
            
            if (!$workResult_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult__ not exist '.json_encode($_logs));
                continue;
            }
            
            $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
            if (!$task_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                continue;
            }
            
            $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
            if (!$step_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                continue;
            }
            
            //统计本次作业的结果
            list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
            
            //---------------------------------
            
            if ($step_['type'] == Step::TYPE_PRODUCE)
            {
                $counters = [
                    'work_count' => 0,
                    //'reseted_count' => 1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                if (in_array($work_['status'], [Work::STATUS_FINISH]) )//&& $work_['is_correct']
                {
                    $counters = array_merge($counters, [
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ]);
                }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
            
                if ($statUser_)
                {
                    foreach ($statUser_ as $userId_ => $statUserResult_)
                    {
                        $counters = [
                            'work_time' => 0,
                            'work_count' => 0,
                            'join_count' => -1,
                            'refused_count' => 0,
                            'allow_count' => 0,
                            'allowed_count' => 0,
                        ];
                        
                        StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $userId_, $counters);

                    }
                }
            
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => 0,
                        'join_count' => 0,
                        'reseted_count' => 0,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
            elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $counters = [
                    'work_count' => 0,
                    //'reseted_count' => 1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
            
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_count' => 0,
                        'reseted_count' => 0,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
            
            //---------------------------------
        
            //分发作业重新开启
            $attributes = [
                'status' => Work::STATUS_SUBMITED,
                //'type' => Work::TYPE_REDO,
                'updated_at' => time()
            ];
            Work::updateAll($attributes, ['id' => $work_['id']]);
        
            //添加作业记录
            $workRecord = new WorkRecord();
            $workRecord->project_id = $this->projectId;
            $workRecord->work_id = $work_['id'];
            $workRecord->data_id = $dataId;
            $workRecord->batch_id = $work_['batch_id'];
            $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
            $workRecord->task_id = $task_['id'];
            $workRecord->type = WorkRecord::TYPE_REDO;
            $workRecord->after_user_id = $this->userId;
            $workRecord->after_work_status = Work::STATUS_EXECUTING;
            $workRecord->before_user_id = $work_['user_id'];
            $workRecord->before_work_status = $work_['status'];
            $workRecord->created_at = time();
            $workRecord->updated_at = time();
            $workRecord->save();
            //====================================
        }
        
        
        //-------------------------------
        
        //获得生产的场景, 务必是projectId,batchId,stepId
        $scene = $this->getUserScene();
        $_logs['$scene'] = $scene;
        
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime(true);
        $_logs['$workMaxTime'] = $workMaxTime;
        
        //作业回归用户任务池
        $this->setUserCache($scene, $dataId, true);
        $this->refreshUserCacheTtl($scene, true);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setUserCache '. json_encode($_logs));
        
        //-----------------------------
    
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_EXECUTING,
            //'type' => Work::TYPE_REDO,
            'start_time' => time(),
            'delay_time' => $workMaxTime,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
        
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_REDO;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_EXECUTING;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
    
        //-----------------------------
        
        //遍历删除所有子分步的已作业数据
        $this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTREDO);
        
        //---------------------------
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对已提交的作业强制驳回作业
     * 管理员或作业员驳回作业,
     * 将清除其所有的子分步
     * 
     */
    public function forceRefuse($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
        
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
        
        //统计框数, 矫正框数
        $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        list($stat, $statUser) = ProjectHandler::statResult($workResult['result']);
        
        //是执行
        if ($this->step['type'] == Step::TYPE_PRODUCE)
        {
            $counters = [
                'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                'refused_count' => 1,
                'allow_count' => 0,
                'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
            ];
            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
            
            if ($statUser)
            {
                if(in_array($work['status'], [Work::STATUS_FINISH]))
                {
                    $action = StatResultWork::ACTION_FORCEREFUSED_AFTER_ALLOWED;
                }
                else
                {
                    $action = StatResultWork::ACTION_FORCEREFUSED;
                }
                foreach ($statUser as $statUserId_ => $statUserResult_)
                {
                    foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                    {
                        foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                        {
                            //统计被驳回标注绩效
                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'data_id' => $dataId,
                                'work_id' => $work['id'],
                                'user_id' => $statUserId_,
                                'type' => $statUserResultType__,
                                'action' => $action
                            ];
                            $statCounters = [
                                'value' => $statUserResultVal__
                            ];
                            StatResultWork::updateCounter($where, $statCounters);

                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'user_id' => $statUserId_,
                                'type' => $statUserResultType__,
                                'action' => $action
                            ];
                            $statCounters = [
                                'value' => $statUserResultVal__
                            ];
                            StatResultUser::updateCounter($where, $statCounters);
                        }
                    }


                    $counters = [
                        'work_time' => 0,
                        'work_count' => 0,
                        'join_count' => -1,
                        'refused_count' => 0,
                        'allow_count' => 0,
                        'allowed_count' => 0,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $statUserId_, $counters);
                }
            }
            if($stat)
            {
                if(in_array($work['status'], [Work::STATUS_FINISH]))
                {
                    $action = StatResultWork::ACTION_FORCEREFUSED_AFTER_ALLOWED;
                }
                else
                {
                    $action = StatResultWork::ACTION_FORCEREFUSED;
                }
                foreach ($stat as $statResultAction_ => $statResult__)
                {
                    foreach ($statResult__ as $statResultType__ => $statResultVal__)
                    {
                        //统计被驳回标注绩效
                        $where = [
                            'project_id' => $this->projectId,
                            'task_id' => $this->task['id'],
                            'data_id' => $dataId,
                            'type' => $statResultType__,
                            'action' => $action
                        ];
                        $statCounters = [
                            'value' => $statResultVal__
                        ];
                        StatResultData::updateCounter($where, $statCounters);

                        $where = [
                            'project_id' => $this->projectId,
                            'task_id' => $this->task['id'],
                            'type' => $statResultType__,
                            'action' => $action
                        ];
                        $statCounters = [
                            'value' => $statResultVal__
                        ];
                        StatResult::updateCounter($where, $statCounters);
                    }
                }
            }


            if (!empty($work['user_id']))
            {
                $counters = [
                    'work_time' => 0,
                    'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                    'join_count' => 0,
                    'refused_count' => 1,
                    'allow_count' => 0,
                    'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
            }
        }
        elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            
            $counters = [
                'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                'refused_count' => 1,
                'allow_count' => -1,
                'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
            ];
            // if (in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            // {
            //     $counters = array_merge($counters, [
            //         'label_count' => !empty($work['label_count']) ? -$work['label_count'] : 0,
            //         'point_count' => !empty($work['point_count']) ? -$work['point_count'] : 0,
            //         'line_count' => !empty($work['line_count']) ? -$work['line_count'] : 0,
            //         'rect_count' => !empty($work['rect_count']) ? -$work['rect_count'] : 0,
            //         'polygon_count' => !empty($work['polygon_count']) ? -$work['polygon_count'] : 0,
            //         'sharepoint_count' => !empty($work['sharepoint_count']) ? -$work['sharepoint_count'] : 0,
            //         'other_count' => !empty($work['other_count']) ? -$work['other_count'] : 0,
            //         'label_time' => !empty($work['label_time']) ? -$work['label_time'] : 0
            //     ]);
            // }
            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
            
            if (!empty($work['user_id']))
            {
                $counters = [
                    'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                    'refused_count' => 1,
                    'allow_count' => -1,
                    'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                // if (in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                // {
                //     $counters = array_merge($counters, [
                //         'label_count' => !empty($work['label_count']) ? -$work['label_count'] : 0,
                //         'point_count' => !empty($work['point_count']) ? -$work['point_count'] : 0,
                //         'line_count' => !empty($work['line_count']) ? -$work['line_count'] : 0,
                //         'rect_count' => !empty($work['rect_count']) ? -$work['rect_count'] : 0,
                //         'polygon_count' => !empty($work['polygon_count']) ? -$work['polygon_count'] : 0,
                //         'sharepoint_count' => !empty($work['sharepoint_count']) ? -$work['sharepoint_count'] : 0,
                //         'other_count' => !empty($work['other_count']) ? -$work['other_count'] : 0,
                //         'label_time' => !empty($work['label_time']) ? -$work['label_time'] : 0
                //     ]);
                // }
                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
            }
        }
        
        
        foreach ($this->stepParentIds as $stepId_)
        {
            $_logs['$stepId_'] = $stepId_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
        
            if (!$stepId_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
                continue;
            }
        
            //分发作业的结果
            $work_ = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
        
            if (!$work_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ not exist '.json_encode($_logs));
                continue;
            }
        
            if (!in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                continue;
            }
        
            $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
            
            if (!$workResult_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult__ not exist '.json_encode($_logs));
                continue;
            }
            
            $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
            if (!$task_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                continue;
            }
            
            $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
            if (!$step_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                continue;
            }
            
            //统计本次作业的结果
            list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
            
            //---------------------------------
            
            if ($step_['type'] == Step::TYPE_PRODUCE)
            {
                $counters = [
                    'work_count' => 0,
                    //'reseted_count' => 1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                if (in_array($work_['status'], [Work::STATUS_FINISH]) )//&& $work_['is_correct']
                {
                    $counters = array_merge($counters, [
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ]);
                }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
            
                if ($statUser_)
                {
                    if(in_array($work['status'], Work::STATUS_FINISH))
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED;
                    }
                    foreach ($statUser_ as $userId_ => $statUserResult_)
                    {
                        if(in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]) && in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                        {
                            foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                            {
                                foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                {
                                    //统计被驳回标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'data_id' => $dataId,
                                        'work_id' => $work['id'],
                                        'user_id' => $work['user_id'],
                                        'type' => $statUserResultType__,
                                        'action' => $action
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultWork::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'user_id' => $work['user_id'],
                                        'type' => $statUserResultType__,
                                        'action' => $action
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultUser::updateCounter($where, $statCounters);
                                }
                            }
                        }

                        $counters = [
                            'work_time' => 0,
                            'work_count' => 0,
                            'join_count' => -1,
                            'refused_count' => 0,
                            'allow_count' => 0,
                            'allowed_count' => 0,
                        ];
                        if (in_array($work_['status'], [Work::STATUS_FINISH]) )//&& $work['is_correct']
                        {
                            // $counters = array_merge($counters, [
                            //     'label_count' => !empty($userstat_['label_count']) ? -$userstat_['label_count'] : 0,
                            //     'point_count' => !empty($userstat_['point_count']) ? -$userstat_['point_count'] : 0,
                            //     'line_count' => !empty($userstat_['line_count']) ? -$userstat_['line_count'] : 0,
                            //     'rect_count' => !empty($userstat_['rect_count']) ? -$userstat_['rect_count'] : 0,
                            //     'polygon_count' => !empty($userstat_['polygon_count']) ? -$userstat_['polygon_count'] : 0,
                            //     'sharepoint_count' => !empty($userstat_['sharepoint_count']) ? -$userstat_['sharepoint_count'] : 0,
                            //     'other_count' => !empty($userstat_['other_count']) ? -$userstat_['other_count'] : 0,
                            //     'label_time' => !empty($userstat_['label_time']) ? -$userstat_['label_time'] : 0
                            // ]);
                        }
                        StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $userId_, $counters);
                    }
                }
                if($stat_)
                {
                    if(in_array($work['status'], Work::STATUS_FINISH))
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED;
                    }
                    if(in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]) && in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                    {
                        foreach ($stat_ as $statResultAction_ => $statResult__)
                        {
                            foreach ($statResult__ as $statResultType__ => $statResultVal__)
                            {
                                //统计被驳回标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'type' => $statResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResultData::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'type' => $statResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResult::updateCounter($where, $statCounters);
                            }
                        }
                    }
                }
                
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => 0,
                        'join_count' => 0,
                        'reseted_count' => 0,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
            elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $counters = [
                    'work_count' => 0,
                    //'reseted_count' => 1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
            
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_count' => 0,
                        'reseted_count' => 0,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }


                $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
                list($stat, $statUser) = ProjectHandler::statResult($dataResult['result']);

                if ($statUser)
                {
                    if(in_array($work['status'], Work::STATUS_FINISH))
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED;
                    }
                    foreach ($statUser as $userId_ => $statUserResult_)
                    {
                        if(in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]) && in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                        {
                            foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                            {
                                foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                {
                                    //统计被驳回标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'data_id' => $dataId,
                                        'work_id' => $work['id'],
                                        'user_id' => $work['user_id'],
                                        'type' => $statUserResultType__,
                                        'action' => $action
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultWork::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'user_id' => $work['user_id'],
                                        'type' => $statUserResultType__,
                                        'action' => $action
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultUser::updateCounter($where, $statCounters);
                                }
                            }
                        }

                        $counters = [
                            'work_time' => 0,
                            'work_count' => 0,
                            'join_count' => -1,
                            'refused_count' => 0,
                            'allow_count' => 0,
                            'allowed_count' => 0,
                        ];
                        if (in_array($work_['status'], [Work::STATUS_FINISH]) )//&& $work['is_correct']
                        {
                            // $counters = array_merge($counters, [
                            //     'label_count' => !empty($userstat_['label_count']) ? -$userstat_['label_count'] : 0,
                            //     'point_count' => !empty($userstat_['point_count']) ? -$userstat_['point_count'] : 0,
                            //     'line_count' => !empty($userstat_['line_count']) ? -$userstat_['line_count'] : 0,
                            //     'rect_count' => !empty($userstat_['rect_count']) ? -$userstat_['rect_count'] : 0,
                            //     'polygon_count' => !empty($userstat_['polygon_count']) ? -$userstat_['polygon_count'] : 0,
                            //     'sharepoint_count' => !empty($userstat_['sharepoint_count']) ? -$userstat_['sharepoint_count'] : 0,
                            //     'other_count' => !empty($userstat_['other_count']) ? -$userstat_['other_count'] : 0,
                            //     'label_time' => !empty($userstat_['label_time']) ? -$userstat_['label_time'] : 0
                            // ]);
                        }
                        StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $userId_, $counters);
                    }
                }
                if($stat)
                {
                    if(in_array($work['status'], Work::STATUS_FINISH))
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCEREFUSED;
                    }
                    if(in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]) && in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                    {
                        foreach ($stat as $statResultAction_ => $statResult__)
                        {
                            foreach ($statResult__ as $statResultType__ => $statResultVal__)
                            {
                                //统计被驳回标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'type' => $statResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResultData::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'type' => $statResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResult::updateCounter($where, $statCounters);
                            }
                        }
                    }
                }
            }
                
            //----------------------------
        
            //分发作业重新开启
            $attributes = [
                'status' => Work::STATUS_SUBMITED,
                //'type' => Work::TYPE_RESETED,
                'updated_at' => time()
            ];
            Work::updateAll($attributes, ['id' => $work_['id']]);
        
            //添加作业记录
            $workRecord = new WorkRecord();
            $workRecord->project_id = $this->projectId;
            $workRecord->work_id = $work_['id'];
            $workRecord->data_id = $dataId;
            $workRecord->batch_id = $work_['batch_id'];
            $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
            $workRecord->task_id = $task_['id'];
            $workRecord->type = WorkRecord::TYPE_BACKTOSUBMIT;
            $workRecord->after_user_id = $this->userId;
            $workRecord->after_work_status = Work::STATUS_SUBMITED;
            $workRecord->before_user_id = $work_['user_id'];
            $workRecord->before_work_status = $work_['status'];
            $workRecord->created_at = time();
            $workRecord->updated_at = time();
            $workRecord->save();
            
            //====================================
        }
        
        
        //-------------------------------
        
        //已领取时,要清除清空掉缓存
        if (in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
        {
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($work['project_id'], $work['batch_id'], $work['step_id'], $work['user_id']);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
            }
            else
            {
                //获取本项目本批次本分步的所有场景
                $scenes = $taskHandler->getUserScenes();
                $_logs['$scenes'] = $scenes;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserScenes '.json_encode($_logs));
        
                if ($scenes)
                {
                    foreach ($scenes as $scene)
                    {
                        $taskHandler->clearUserCache($scene, $dataId, true);
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' clearUserCache '.json_encode($_logs));
                    }
                }
            }
        }
        
        //----------------------------------------------------
        $transaction = Yii::$app->db->beginTransaction();
        
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_DELETED,
            'type' => Work::TYPE_FORCEREFUSE,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
        
        //重新生成记录
        $workNew = new Work();
        $workNew->project_id = $work['project_id'];
        $workNew->batch_id = $work['batch_id'];
        $workNew->step_id = $work['step_id'];
        $workNew->data_id = $work['data_id'];
        $workNew->user_id = $work['user_id'];
        $workNew->is_refused = $work['is_refused'];
        $workNew->start_time = time();
        $workNew->delay_time = 0;
        $workNew->status = Work::STATUS_REFUSED;
        $workNew->type = Work::TYPE_FORCEREFUSE;
        $workNew->created_at = time();
        $workNew->updated_at = time();
        $workNew->save();
        
        $transaction->commit();
        //------------------------------------------------------
        
        //保存驳回理由
        $workResultNew = new WorkResult();
        $workResultNew->work_id = $workNew->id;
        $workResultNew->result = $workResult['result'];
        $workResultNew->feedback = $workResult['feedback'];
        $workResultNew->save();
        
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_FORCEREFUSE;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_REFUSED;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        Message::sendTaskForceRefuse($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        
        //-----------------------------
        
        //遍历删除所有子分步的已作业数据
        $this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCEREFUSE);
        
        //---------------------------
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对已提交的作业强制重置作业
     * 管理员驳回作业,
     * 将清除其所有的子分步
     *
     */
    public function forceReset($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
        
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING, Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
        
        //被驳回的作业不得强制驳回或重置, 因后续任务的返工作业数会错
        if (in_array($work['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_AUDITREFUSED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work type TYPE_AUDITREFUSED '.json_encode($_logs));
            return false;
        }
        //查看结果, 统计框数, 矫正框数
        $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        list($stat, $statUser) = ProjectHandler::statResult($workResult['result']);
        
        if ($this->step['type'] == Step::TYPE_PRODUCE)
        {
            $counters = [
                'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                'reseted_count' => 1,
                'allow_count' => 0,
                'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
            ];
            if (in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))//&& $work['is_correct']
            {
                if ($statUser)
                {
                    if(in_array($work['status'], [Work::STATUS_FINISH]))
                    {
                        $action = StatResultWork::ACTION_FORCERESETED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCERESETED;
                    }
                    foreach ($statUser as $statUserId_ => $statUserResult_)
                    {
                        foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                        {
                            foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                            {
                                //统计被驳回标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'work_id' => $work['id'],
                                    'user_id' => $statUserId_,
                                    'type' => $statUserResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultWork::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'user_id' => $statUserId_,
                                    'type' => $statUserResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statUserResultVal__
                                ];
                                StatResultUser::updateCounter($where, $statCounters);
                            }
                        }

                    }
                }
                if($stat)
                {
                    if(in_array($work['status'], [Work::STATUS_FINISH]))
                    {
                        $action = StatResultWork::ACTION_FORCERESETED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCERESETED;
                    }
                    foreach ($stat as $statResultAction_ => $statResult__)
                    {
                        foreach ($statResult__ as $statResultType__ => $statResultVal__)
                        {
                            //统计被驳回标注绩效
                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'data_id' => $dataId,
                                'type' => $statResultType__,
                                'action' => $action
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResultData::updateCounter($where, $statCounters);

                            $where = [
                                'project_id' => $this->projectId,
                                'task_id' => $this->task['id'],
                                'type' => $statResultType__,
                                'action' => $action
                            ];
                            $statCounters = [
                                'value' => $statResultVal__
                            ];
                            StatResult::updateCounter($where, $statCounters);
                        }
                    }
                }
            }
            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
            if ($statUser)
            {
                foreach ($statUser as $userId_ => $userstat_)
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => 0,
                        'join_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                        'reseted_count' => 0,
                        'allow_count' => 0,
                        'allowed_count' => 0,
                    ];
                    // if (in_array($work['status'], [Work::STATUS_FINISH]))// && $work['is_correct']
                    // {
                    //     $counters = array_merge($counters, [
                    //         'label_count' => !empty($userstat_['label_count']) ? -$userstat_['label_count'] : 0,
                    //         'point_count' => !empty($userstat_['point_count']) ? -$userstat_['point_count'] : 0,
                    //         'line_count' => !empty($userstat_['line_count']) ? -$userstat_['line_count'] : 0,
                    //         'rect_count' => !empty($userstat_['rect_count']) ? -$userstat_['rect_count'] : 0,
                    //         'polygon_count' => !empty($userstat_['polygon_count']) ? -$userstat_['polygon_count'] : 0,
                    //         'sharepoint_count' => !empty($userstat_['sharepoint_count']) ? -$userstat_['sharepoint_count'] : 0,
                    //         'other_count' => !empty($userstat_['other_count']) ? -$userstat_['other_count'] : 0,
                    //         'label_time' => !empty($userstat_['label_time']) ? -$userstat_['label_time'] : 0
                    //     ]);
                    // }
                    StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $userId_, $counters);
                }
            }
            if (!empty($work['user_id']))
            {
                $counters = [
                    'work_time' => 0,
                    'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                    'join_count' => 0,
                    'reseted_count' => 1,
                    'allow_count' => 0,
                    'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
            }
        }
        elseif (in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
        {
            //统计本次作业的结果
            $dataResult_ = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
            list($stat_, $statUser_) = ProjectHandler::statResult($dataResult_['result']);
            $counters = [
                'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                'reseted_count' => 1,
                'allow_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
            ];
            if (in_array($work['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE]))
            {
                $counters['refuse_revise_count'] = 1;
            }
            // if (in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            // {
            //     $counters = array_merge($counters, [
            //         'label_count' => !empty($work['label_count']) ? -$work['label_count'] : 0,
            //         'point_count' => !empty($work['point_count']) ? -$work['point_count'] : 0,
            //         'line_count' => !empty($work['line_count']) ? -$work['line_count'] : 0,
            //         'rect_count' => !empty($work['rect_count']) ? -$work['rect_count'] : 0,
            //         'polygon_count' => !empty($work['polygon_count']) ? -$work['polygon_count'] : 0,
            //         'sharepoint_count' => !empty($work['sharepoint_count']) ? -$work['sharepoint_count'] : 0,
            //         'other_count' => !empty($work['other_count']) ? -$work['other_count'] : 0,
            //         'label_time' => !empty($work['label_time']) ? -$work['label_time'] : 0
            //     ]);
            // }
            Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
            if (!empty($work['user_id']))
            {
                $counters = [
                    'work_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                    'reseted_count' => 1,
                    'allow_count' => in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                    'allowed_count' => ($work['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];

                // if (in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                // {
                //     $counters = array_merge($counters, [
                //         'label_count' => !empty($work['label_count']) ? -$work['label_count'] : 0,
                //         'point_count' => !empty($work['point_count']) ? -$work['point_count'] : 0,
                //         'line_count' => !empty($work['line_count']) ? -$work['line_count'] : 0,
                //         'rect_count' => !empty($work['rect_count']) ? -$work['rect_count'] : 0,
                //         'polygon_count' => !empty($work['polygon_count']) ? -$work['polygon_count'] : 0,
                //         'sharepoint_count' => !empty($work['sharepoint_count']) ? -$work['sharepoint_count'] : 0,
                //         'other_count' => !empty($work['other_count']) ? -$work['other_count'] : 0,
                //         'label_time' => !empty($work['label_time']) ? -$work['label_time'] : 0
                //     ]);
                // }
                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
            }
        
        }
        
        foreach ($this->stepParentIds as $stepId_)
        {
            $_logs['$stepId_'] = $stepId_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
        
            if (!$stepId_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
                continue;
            }
        
            //分发作业的结果
            $work_ = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
            $_logs['$work_'] = $work_;
            
            if (!$work_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $work_ not exist '.json_encode($_logs));
                continue;
            }
        
            if (!in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                continue;
            }
        
            //---------------------------------
        
            $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
            
            if (!$workResult_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $workResult__ not exist '.json_encode($_logs));
                continue;
            }
            
            $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
            if (!$task_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                continue;
            }
            
            $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
            if (!$step_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                continue;
            }
            
            //统计本次作业的结果
            list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
            
            //---------------------------------
            
            if ($step_['type'] == Step::TYPE_PRODUCE)
            {
                $counters = [
                    'work_count' => 0,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                // if (in_array($work_['status'], [Work::STATUS_FINISH]))// && $work_['is_correct']
                // {
                //     $counters = array_merge($counters, [
                //         'label_count' => !empty($stat_['label_count']) ? -$stat_['label_count'] : 0,
                //         'point_count' => !empty($stat_['point_count']) ? -$stat_['point_count'] : 0,
                //         'line_count' => !empty($stat_['line_count']) ? -$stat_['line_count'] : 0,
                //         'rect_count' => !empty($stat_['rect_count']) ? -$stat_['rect_count'] : 0,
                //         'polygon_count' => !empty($stat_['polygon_count']) ? -$stat_['polygon_count'] : 0,
                //         'sharepoint_count' => !empty($stat_['sharepoint_count']) ? -$stat_['sharepoint_count'] : 0,
                //         'other_count' => !empty($stat_['other_count']) ? -$stat_['other_count'] : 0,
                //         'label_time' => !empty($stat_['label_time']) ? -$stat_['label_time'] : 0
                //     ]);
                // }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
            
                if ($statUser_)
                {
                    if(in_array($work['status'], [Work::STATUS_FINISH]))
                    {
                        $action = StatResultWork::ACTION_FORCERESETED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCERESETED;
                    }
                    foreach ($statUser_ as $userId_ => $userstat_)
                    {
                        if(in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]) && in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                        {
                            foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                            {
                                foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                {
                                    //统计被重置标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'data_id' => $dataId,
                                        'work_id' => $work['id'],
                                        'user_id' => $work['user_id'],
                                        'type' => $statUserResultType__,
                                        'action' => $action
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultWork::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $this->task['id'],
                                        'user_id' => $work['user_id'],
                                        'type' => $statUserResultType__,
                                        'action' => $action
                                    ];
                                    $statCounters = [
                                        'value' => $statUserResultVal__
                                    ];
                                    StatResultUser::updateCounter($where, $statCounters);
                                }
                            }
                        }

                        $counters = [
                            'work_time' => 0,
                            'work_count' => 0,
                            'join_count' => -1,
                            'refused_count' => 0,
                            'allow_count' => 0,
                            'allowed_count' => 0,
                        ];
                        // if (in_array($work_['status'], [Work::STATUS_FINISH]))// && $work['is_correct']
                        // {
                        //     $counters = array_merge($counters, [
                        //         'label_count' => !empty($userstat_['label_count']) ? -$userstat_['label_count'] : 0,
                        //         'point_count' => !empty($userstat_['point_count']) ? -$userstat_['point_count'] : 0,
                        //         'line_count' => !empty($userstat_['line_count']) ? -$userstat_['line_count'] : 0,
                        //         'rect_count' => !empty($userstat_['rect_count']) ? -$userstat_['rect_count'] : 0,
                        //         'polygon_count' => !empty($userstat_['polygon_count']) ? -$userstat_['polygon_count'] : 0,
                        //         'sharepoint_count' => !empty($userstat_['sharepoint_count']) ? -$userstat_['sharepoint_count'] : 0,
                        //         'other_count' => !empty($userstat_['other_count']) ? -$userstat_['other_count'] : 0,
                        //         'label_time' => !empty($userstat_['label_time']) ? -$userstat_['label_time'] : 0
                        //     ]);
                        // }
                        StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $userId_, $counters);
                    }
                }
                if($stat_)
                {
                   if(in_array($work['status'], [Work::STATUS_FINISH]))
                    {
                        $action = StatResultWork::ACTION_FORCERESETED_AFTER_ALLOWED;
                    }
                    else
                    {
                        $action = StatResultWork::ACTION_FORCERESETED;
                    } 
                    if(in_array($this->step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]) && in_array($work['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                    {
                        foreach ($stat_ as $statResultAction_ => $statResult__)
                        {
                            foreach ($statResult__ as $statResultType__ => $statResultVal__)
                            {
                                //统计被重置标注绩效
                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'data_id' => $dataId,
                                    'type' => $statResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResultData::updateCounter($where, $statCounters);

                                $where = [
                                    'project_id' => $this->projectId,
                                    'task_id' => $this->task['id'],
                                    'type' => $statResultType__,
                                    'action' => $action
                                ];
                                $statCounters = [
                                    'value' => $statResultVal__
                                ];
                                StatResult::updateCounter($where, $statCounters);
                            }
                        }
                    }
                }
                
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => 0,
                        'join_count' => 0,
                        'reseted_count' => 0,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
            elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $counters = [
                    'work_count' => 0,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
            
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_count' => 0,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
                
            //----------------------------
        
            //分发作业重新开启
            $attributes = [
                'status' => Work::STATUS_SUBMITED,
                //'type' => Work::TYPE_RESETED,
                'updated_at' => time()
            ];
            Work::updateAll($attributes, ['id' => $work_['id']]);
        
            //添加作业记录
            $workRecord = new WorkRecord();
            $workRecord->project_id = $this->projectId;
            $workRecord->work_id = $work_['id'];
            $workRecord->data_id = $dataId;
            $workRecord->batch_id = $work_['batch_id'];
            $workRecord->step_id = $work['step_id'];//注意此处:在审核时候的操作
            $workRecord->task_id = $task_['id'];
            $workRecord->type = WorkRecord::TYPE_BACKTOSUBMIT;
            $workRecord->after_user_id = $this->userId;
            $workRecord->after_work_status = Work::STATUS_SUBMITED;
            $workRecord->before_user_id = $work_['user_id'];
            $workRecord->before_work_status = $work_['status'];
            $workRecord->created_at = time();
            $workRecord->updated_at = time();
            $workRecord->save();
        }
        
        //已领取时,要清除清空掉缓存
        if (in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
        {
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($work['project_id'], $work['batch_id'], $work['step_id'], $work['user_id']);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
            }
            else
            {
                //获取本项目本批次本分步的所有场景
                $scenes = $taskHandler->getUserScenes();
                $_logs['$scenes'] = $scenes;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserScenes '.json_encode($_logs));
        
                if ($scenes)
                {
                    foreach ($scenes as $scene)
                    {
                        $taskHandler->clearUserCache($scene, $dataId, true);
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' clearUserCache '.json_encode($_logs));
                    }
                }
            }
        }
        
        //----------------------------------------------------
        $transaction = Yii::$app->db->beginTransaction();
        
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_DELETED,
            'type' => Work::TYPE_FORCERESET,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
        
        //重新生成记录
        $workNew = new Work();
        $workNew->project_id = $work['project_id'];
        $workNew->batch_id = $work['batch_id'];
        $workNew->step_id = $work['step_id'];
        $workNew->data_id = $work['data_id'];
        $workNew->is_refused = $work['is_refused'];
        $workNew->user_id = 0;
        $workNew->start_time = time();
        $workNew->delay_time = 0;
        $workNew->status = Work::STATUS_NEW;
        $workNew->type = Work::TYPE_NORMAL;
        $workNew->created_at = time();
        $workNew->updated_at = time();
        $workNew->save();
        
        $transaction->commit();
        //------------------------------------------------------
        
        //保存驳回理由
        $workResultNew = new WorkResult();
        $workResultNew->work_id = $workNew->id;
        $workResultNew->result = $workResult['result'];
        $workResultNew->feedback = $workResult['feedback'];
        $workResultNew->save();
        
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_FORCERESET;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_NEW;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        Message::sendTaskForceReset($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        //-----------------------------
        
        //遍历删除所有子分步的已作业数据
        $this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCERESET);
    
        //---------------------------
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对被驳回的作业进行修改操作
     * 
     * @return boolean
     */
    public function refusedRevise($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
        
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_REFUSED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
        
        //统计框数, 矫正框数
        //$workResult_ = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        //list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
        
        //-------------------------------
        
        //获得生产的场景, 务必是projectId,batchId,stepId
        $scene = $this->getUserScene();
        $_logs['$scene'] = $scene;
        
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime(true);
        $_logs['$workMaxTime'] = $workMaxTime;
        
        //作业回归用户任务池
        $this->setUserCache($scene, $dataId, true);
        $this->refreshUserCacheTtl($scene, true);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setUserCache '. json_encode($_logs));
        
        //-----------------------------
        
//         $counters = [];
//         $counters['refused_revise_count'] = 1;
//         Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
//         if (!empty($work['user_id']))
//         {
//             $counters = [];
//             $counters['refused_revise_count'] = 1;
//             StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
//         }
        
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_RECEIVED,
            'type' => Work::TYPE_REFUSEREVISE,
            'start_time' => time(),
            'delay_time' => $workMaxTime,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
        
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_REFUSEREVISE;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_RECEIVED;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
//         //重新生成记录
//         $workNew = new Work();
//         $workNew->project_id = $work['project_id'];
//         $workNew->batch_id = $work['batch_id'];
//         $workNew->step_id = $work['step_id'];
//         $workNew->data_id = $work['data_id'];
//         $workNew->user_id = $work['user_id'];
//         $workNew->start_time = time();
//         $workNew->delay_time = $workMaxTime;
//         $workNew->status = Work::STATUS_EXECUTING;
//         $workNew->type = Work::TYPE_REDONE;
//         $workNew->created_at = time();
//         $workNew->updated_at = time();
//         $workNew->save();
        
//         //保存驳回理由
//         $workResultNew = new WorkResult();
//         $workResultNew->work_id = $workNew->id;
//         $workResultNew->result = $workResult_['result'];
//         $workResultNew->feedback = $workResult_['feedback'];
//         $workResultNew->save();
        
        //Message::sendTaskForceReset($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        //-----------------------------
        
        //遍历删除所有子分步的已作业数据
        //$this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCERESET);
        
        //---------------------------
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对被驳回的作业进行重置操作
     *
     * @return boolean
     */
    public function refusedReset($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
    
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_REFUSED]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
    
        //统计框数, 矫正框数
        $workResult = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        //list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
    
        //-------------------------------
    
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime(true);
        $_logs['$workMaxTime'] = $workMaxTime;
    
        //-----------------------------
        
        //增加修正数,保证驳回数平衡
        $counters = [];
        $counters['refused_revise_count'] = 1;
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
        if (!empty($work['user_id']))
        {
            $counters = [];
            $counters['refused_revise_count'] = 1;
            StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
        }
    
        //----------------------------------------------------
        $transaction = Yii::$app->db->beginTransaction();
        
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_DELETED,
            'type' => Work::TYPE_REFUSERESET,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
    
        //重新生成记录
        $workNew = new Work();
        $workNew->project_id = $work['project_id'];
        $workNew->batch_id = $work['batch_id'];
        $workNew->step_id = $work['step_id'];
        $workNew->data_id = $work['data_id'];
        $workNew->is_refused = $work['is_refused'];
        $workNew->start_time = time();
        $workNew->delay_time = $workMaxTime;
        $workNew->status = Work::STATUS_NEW;
        $workNew->type = Work::TYPE_NORMAL;
        $workNew->created_at = time();
        $workNew->updated_at = time();
        $workNew->save();
    
        $transaction->commit();
        //------------------------------------------------------
        
        //保存驳回理由
        $workResultNew = new WorkResult();
        $workResultNew->work_id = $workNew->id;
        $workResultNew->result = $workResult['result'];
        $workResultNew->feedback = $workResult['feedback'];
        $workResultNew->save();
        
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_REFUSERESET;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_NEW;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
    
        //Message::sendTaskForceReset($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        //-----------------------------
    
        //遍历删除所有子分步的已作业数据
        //$this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCERESET);
    
        //---------------------------
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对返工作业进行修改操作
     *
     * @param int $dataId
     * @return boolean
     */
    public function refuseSubmitReceive($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
    
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_REFUSEDSUBMIT]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
    
        //统计框数, 矫正框数
        $workResult_ = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        //list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
    
        //-------------------------------
    
        //获得生产的场景, 务必是projectId,batchId,stepId
        $scene = $this->getUserScene();
        $_logs['$scene'] = $scene;
    
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime(true);
        $_logs['$workMaxTime'] = $workMaxTime;
    
        //作业回归用户任务池
        $this->setUserCache($scene, $dataId, true);
        $this->refreshUserCacheTtl($scene, true);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setUserCache '. json_encode($_logs));
    
        //-----------------------------
    
        $counters = [];
        $counters['refuse_receive_count'] = 1;
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
        if (!empty($work['user_id']))
        {
            $counters = [];
            $counters['refuse_receive_count'] = 1;
            StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
        }
    
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_RECEIVED,
            'type' => Work::TYPE_REFUSESUBMITREVISE,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
    
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_REFUSEREVISE;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_RECEIVED;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        //Message::sendTaskForceReset($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        //-----------------------------
    
        //遍历删除所有子分步的已作业数据
        //$this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCERESET);
    
        //---------------------------
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对返工作业进行重置操作
     *
     * @param int $dataId
     * @return boolean
     */
    public function refuseSubmitReset($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
    
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_REFUSEDSUBMIT]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
    
        //统计框数, 矫正框数
        $workResult_ = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        //list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
    
        //-------------------------------
    
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime(true);
        $_logs['$workMaxTime'] = $workMaxTime;
    
        //-----------------------------
    
        $counters = [];
        $counters['refused_revise_count'] = 1;
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
        if (!empty($work['user_id']))
        {
            $counters = [];
            $counters['refused_revise_count'] = 1;
            StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
        }
    
        //----------------------------------------------------
        $transaction = Yii::$app->db->beginTransaction();
        
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_DELETED,
            'type' => Work::TYPE_REFUSESUBMITRESET,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
    
        //重新生成记录
        $workNew = new Work();
        $workNew->project_id = $work['project_id'];
        $workNew->batch_id = $work['batch_id'];
        $workNew->step_id = $work['step_id'];
        $workNew->data_id = $work['data_id'];
        $workNew->is_refused = $work['is_refused'];
        $workNew->start_time = time();
        $workNew->delay_time = $workMaxTime;
        $workNew->status = Work::STATUS_NEW;
        $workNew->type = Work::TYPE_NORMAL;
        $workNew->created_at = time();
        $workNew->updated_at = time();
        $workNew->save();
        
        $transaction->commit();
        //------------------------------------------------------
    
        //保存驳回理由
        $workResultNew = new WorkResult();
        $workResultNew->work_id = $workNew->id;
        $workResultNew->result = $workResult_['result'];
        $workResultNew->feedback = $workResult_['feedback'];
        $workResultNew->save();
    
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_REFUSERESET;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_NEW;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        //Message::sendTaskForceReset($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        //-----------------------------
    
        //遍历删除所有子分步的已作业数据
        $this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCERESET);
    
        //---------------------------
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    public function difficultRevise($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
    
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
        
        if (!in_array($work['status'], [Work::STATUS_DIFFICULT]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
    
        //统计框数, 矫正框数
        $workResult_ = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        //list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
    
        //-------------------------------
    
        //获得生产的场景, 务必是projectId,batchId,stepId
        $scene = $this->getUserScene();
        $_logs['$scene'] = $scene;
    
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime(true);
        $_logs['$workMaxTime'] = $workMaxTime;
    
        //作业回归用户任务池
        $this->setUserCache($scene, $dataId, true);
        $this->refreshUserCacheTtl($scene, true);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setUserCache '. json_encode($_logs));
    
        //-----------------------------
    
        $counters = [];
        $counters['difficult_revise_count'] = 1;
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
        if (!empty($work['user_id']))
        {
            $counters = [];
            $counters['difficult_revise_count'] = 1;
            StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
        }
        
        //----------------------------------------------------
        $transaction = Yii::$app->db->beginTransaction();
        
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_DELETED,
            'type' => Work::TYPE_DIFFICULTREVISE,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
    
        //重新生成记录
        $workNew = new Work();
        $workNew->project_id = $work['project_id'];
        $workNew->batch_id = $work['batch_id'];
        $workNew->step_id = $work['step_id'];
        $workNew->data_id = $work['data_id'];
        $workNew->user_id = $work['user_id'];
        $workNew->is_refused = $work['is_refused'];
        $workNew->start_time = time();
        $workNew->delay_time = $workMaxTime;
        $workNew->status = Work::STATUS_RECEIVED;
        $workNew->type = Work::TYPE_DIFFICULT;
        $workNew->created_at = time();
        $workNew->updated_at = time();
        $workNew->save();
        
        $transaction->commit();
        //------------------------------------------------------
    
        //保存驳回理由
        $workResultNew = new WorkResult();
        $workResultNew->work_id = $workNew->id;
        $workResultNew->result = $workResult_['result'];
        $workResultNew->feedback = $workResult_['feedback'];
        $workResultNew->save();
    
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_DIFFICULTREVISE;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_RECEIVED;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        //Message::sendTaskForceReset($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        //-----------------------------
    
        //遍历删除所有子分步的已作业数据
        //$this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCERESET);
    
        //---------------------------
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 对被驳回的作业进行重置操作
     *
     * @return boolean
     */
    public function difficultReset($dataId)
    {
        $_logs['$dataId'] = $dataId;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_data_not_exist'));
        }
    
        $work = Work::find()
        ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()->limit(1)->one();
        $_logs['$work'] = $work;
        
        if (!$work)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_not_exist'));
        }
    
        if (!in_array($work['status'], [Work::STATUS_DIFFICULT]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
            return FormatHelper::result('', 'error', yii::t('app', 'task_work_status_not_allow'));
        }
        
        //统计框数, 矫正框数
        $workResult_ = WorkResult::find()->where(['work_id' => $work['id']])->asArray()->limit(1)->one();
        //list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
    
        //-------------------------------
    
        //最晚提交时间
        $workMaxTime = $this->getUserCacheTime(true);
        $_logs['$workMaxTime'] = $workMaxTime;
    
        //-----------------------------
    
        $counters = [];
        $counters['difficult_revise_count'] = 1;
        Stat::updateCounter($this->projectId, $this->batchId, $this->stepId, $counters);
        
        if (!empty($work['user_id']))
        {
            $counters = [];
            $counters['difficult_revise_count'] = 1;
            StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $work['user_id'], $counters);
        }
    
        //----------------------------------------------------
        $transaction = Yii::$app->db->beginTransaction();
        
        //分发作业重新开启
        $attributes = [
            'status' => Work::STATUS_DELETED,
            'type' => Work::TYPE_DIFFICULTRESET,
            'updated_at' => time()
        ];
        Work::updateAll($attributes, ['id' => $work['id']]);
    
        //重新生成记录
        $workNew = new Work();
        $workNew->project_id = $work['project_id'];
        $workNew->batch_id = $work['batch_id'];
        $workNew->step_id = $work['step_id'];
        $workNew->data_id = $work['data_id'];
        $workNew->is_refused = $work['is_refused'];
        $workNew->start_time = time();
        $workNew->delay_time = $workMaxTime;
        $workNew->status = Work::STATUS_NEW;
        $workNew->type = Work::TYPE_NORMAL;
        $workNew->created_at = time();
        $workNew->updated_at = time();
        $workNew->save();
    
        $transaction->commit();
        //------------------------------------------------------
        
        //保存驳回理由
        $workResultNew = new WorkResult();
        $workResultNew->work_id = $workNew->id;
        $workResultNew->result = $workResult_['result'];
        $workResultNew->feedback = $workResult_['feedback'];
        $workResultNew->save();
    
        //添加作业记录
        $workRecord = new WorkRecord();
        $workRecord->project_id = $this->projectId;
        $workRecord->work_id = $work['id'];
        $workRecord->data_id = $dataId;
        $workRecord->batch_id = $work['batch_id'];
        $workRecord->step_id = $work['step_id'];
        $workRecord->task_id = $this->task['id'];
        $workRecord->type = WorkRecord::TYPE_DIFFICULTRESET;
        $workRecord->after_user_id = $this->userId;
        $workRecord->after_work_status = Work::STATUS_NEW;
        $workRecord->before_user_id = $work['user_id'];
        $workRecord->before_work_status = $work['status'];
        $workRecord->created_at = time();
        $workRecord->updated_at = time();
        $workRecord->save();
        
        //Message::sendTaskForceReset($work['user_id'], $this->projectId, $this->task['id'], $dataId);
        //-----------------------------
    
        //遍历删除所有子分步的已作业数据
        //$this->deleteAllChildren($this->stepId, $dataId, Work::TYPE_PARENTFORCERESET);
    
        //---------------------------
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return FormatHelper::result(1);
    }
    
    /**
     * 遍历删除所有子分步的已作业数据
     * 
     * @param int $stepId
     * @param int $dataId
     * @param int $workType
     * @return NULL
     */
    public function deleteAllChildren($stepId, $dataId, $workType)
    {
        $_logs = ['$stepId' => $stepId, '$dataId' => $dataId];
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '.json_encode($_logs));
        
        $stepChildIds = Step::getChildStepIds($stepId);
        $_logs['$stepChildIds'] = $stepChildIds;
        
        if (empty($stepChildIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepChildIds empty '.json_encode($_logs));
            return null;
        }
        
        foreach ($stepChildIds as $stepId_)
        {
            $_logs['$stepId_'] = $stepId_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
        
            if (!$stepId_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepId_ 0 '.json_encode($_logs));
                continue;
            }
            
            //分发作业的结果
            $work_ = Work::find()
            ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_, 'data_id' => $dataId])
            ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->limit(1)->one();
            $_logs['$work_'] = $work_;
        
            if (!$work_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $work_ record '.json_encode($_logs));
                continue;
            }
            
            $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $stepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
            if (!$task_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                continue;
            }
            
            $step_ = Step::find()->where(['id' => $stepId_])->asArray()->limit(1)->one();
            if (!$step_)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                continue;
            }
            
            //-------------------------------
            
            if ($step_['type'] == Step::TYPE_PRODUCE)
            {
                //统计框数, 矫正框数
                $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
            
                $counters = [
                    'amount' => -1,
                    'allowed_count' => in_array($work_['status'], [Work::STATUS_FINISH]) ? -1 : 0,
                    'refused_revise_count' => ($work_['status'] == Work::STATUS_REFUSED) ? 1 : 0,
                    'difficult_revise_count' => ($work_['status'] == Work::STATUS_DIFFICULT) ? 1 : 0,
                    'refuse_receive_count' => ($work_['status'] == Work::STATUS_REFUSEDSUBMIT) ? 1 : 0,
                ];
		//返工作业在前一步未提交的情况下不减少总数, 但前一步已提交的情况下, 需要减少总数
                if (in_array($work_['status'], [Work::STATUS_REFUSEDSUBMIT]) && in_array($work_['type'], [Work::TYPE_AUDITREFUSE]))
                {
                    $counters['amount'] = 0;

                    if ($statUser_)
                    {
                        foreach ($statUser_ as $statUserId_ => $statUserResult_)
                        {
                            $counters = [
                                'work_time' => 0,
                                'work_count' => 0,
                                'join_count' => -1,
                                'reseted_count' => 0,
                                'allowed_count' => 0,
                            ];
                            StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $statUserId_, $counters);

                            if($workType == Work::TYPE_PARENTFORCEREFUSE)
                            {
                                foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                    {
                                        //统计被驳回标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'work_id' => $work_['id'],
                                            'user_id' => $statUserId_,
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCEREFUSED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultWork::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'user_id' => $statUserId_,
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCEREFUSED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultUser::updateCounter($where, $statCounters);
                                    }
                                }
                            }
                            else if($workType == Work::TYPE_PARENTFORCERESET)
                            {
                                foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                    {
                                        //统计被重置标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'work_id' => $work_['id'],
                                            'user_id' => $statUserId_,
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCERESETED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultWork::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'user_id' => $statUserId_,
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCERESETED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultUser::updateCounter($where, $statCounters);
                                    }
                                }
                            }
                        }
                    }
                    if($stat_)
                    {
                        if($workType == Work::TYPE_PARENTFORCEREFUSE)
                        {
                            foreach ($stat_ as $statResultAction_ => $statResult__)
                            {
                                foreach ($statResult__ as $statResultType__ => $statResultVal__)
                                {
                                    //统计被驳回标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'data_id' => $dataId,
                                        'type' => $statResultType__,
                                        'action' => StatResultData::ACTION_PARENTFORCEREFUSED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResultData::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'type' => $statResultType__,
                                        'action' => StatResult::ACTION_PARENTFORCEREFUSED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResult::updateCounter($where, $statCounters);
                                }
                            }
                        }
                        else if($workType == Work::TYPE_PARENTFORCERESET)
                        {
                            foreach ($stat_ as $statResultAction_ => $statResult__)
                            {
                                foreach ($statResult__ as $statResultType__ => $statResultVal__)
                                {
                                    //统计被重置标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'data_id' => $dataId,
                                        'type' => $statResultType__,
                                        'action' => StatResultData::ACTION_PARENTFORCERESETED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResultData::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'type' => $statResultType__,
                                        'action' => StatResult::ACTION_PARENTFORCERESETED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResult::updateCounter($where, $statCounters);
                                }
                            }
                        }
                    }
                }

                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'work_time' => 0,
                        'work_count' => in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                        'join_count' => 0,
                        'other_operated_count' => in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]) ? -1 : 0,
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    if (in_array($work_['type'], [Work::TYPE_DIFFICULT, Work::TYPE_DIFFICULTREVISE]))
                    {
                        $counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work_['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work_['type'], [Work::TYPE_FORCEREFUSE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
            elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                //统计本次作业的结果
                $dataResult_ = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
                list($stat_, $statUser_) = ProjectHandler::statResult($dataResult_['result']);
                $counters = [
                    'amount' => -1,
                    'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                ];
                //返工作业在前一步未提交的情况下不减少总数, 但前一步已提交的情况下, 需要减少总数
                if (in_array($work_['status'], [Work::STATUS_REFUSEDSUBMIT]) && in_array($work_['type'], [Work::TYPE_AUDITREFUSE]))
                {
                    $counters['amount'] = 0;

                    if($workType == Work::TYPE_PARENTFORCEREFUSE)
                    {
                        if ($statUser_)
                        {
                            foreach ($statUser_ as $statUserId_ => $statUserResult_)
                            {
                                foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                    {
                                        //统计被驳回标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'work_id' => $work_['id'],
                                            'user_id' => $work_['user_id'],
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCEREFUSED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultWork::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'user_id' => $work_['user_id'],
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCEREFUSED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultUser::updateCounter($where, $statCounters);
                                    }
                                }


                                $counters = [
                                    'work_time' => 0,
                                    'work_count' => 0,
                                    'join_count' => -1,
                                    'refused_count' => 0,
                                    'allow_count' => 0,
                                    'allowed_count' => 0,
                                ];
                                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $statUserId_, $counters);
                            }
                        }
                        if($stat_)
                        {
                            foreach ($stat_ as $statResultAction_ => $statResult__)
                            {
                                foreach ($statResult__ as $statResultType__ => $statResultVal__)
                                {
                                    //统计被驳回标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'data_id' => $dataId,
                                        'type' => $statResultType__,
                                        'action' => StatResultData::ACTION_PARENTFORCEREFUSED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResultData::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'type' => $statResultType__,
                                        'action' => StatResult::ACTION_PARENTFORCEREFUSED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResult::updateCounter($where, $statCounters);
                                }
                            }
                        }
                    }
                    else if($workType == Work::TYPE_PARENTFORCERESET)
                    {
                        if ($statUser_)
                        {
                            foreach ($statUser_ as $statUserId_ => $statUserResult_)
                            {
                                foreach ($statUserResult_ as $statUserResultAction_ => $statUserResult__)
                                {
                                    foreach ($statUserResult__ as $statUserResultType__ => $statUserResultVal__)
                                    {
                                        //统计被驳回标注绩效
                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'data_id' => $dataId,
                                            'work_id' => $work_['id'],
                                            'user_id' => $work_['user_id'],
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCERESETED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultWork::updateCounter($where, $statCounters);

                                        $where = [
                                            'project_id' => $this->projectId,
                                            'task_id' => $task_['id'],
                                            'user_id' => $work_['user_id'],
                                            'type' => $statUserResultType__,
                                            'action' => StatResultWork::ACTION_PARENTFORCERESETED
                                        ];
                                        $statCounters = [
                                            'value' => $statUserResultVal__
                                        ];
                                        StatResultUser::updateCounter($where, $statCounters);
                                    }
                                }


                                $counters = [
                                    'work_time' => 0,
                                    'work_count' => 0,
                                    'join_count' => -1,
                                    'refused_count' => 0,
                                    'allow_count' => 0,
                                    'allowed_count' => 0,
                                ];
                                StatUser::updateCounter($this->projectId, $this->batchId, $this->stepId, $statUserId_, $counters);
                            }
                        }
                        if($stat_)
                        {
                            foreach ($stat_ as $statResultAction_ => $statResult__)
                            {
                                foreach ($statResult__ as $statResultType__ => $statResultVal__)
                                {
                                    //统计被驳回标注绩效
                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'data_id' => $dataId,
                                        'type' => $statResultType__,
                                        'action' => StatResultData::ACTION_PARENTFORCERESETED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResultData::updateCounter($where, $statCounters);

                                    $where = [
                                        'project_id' => $this->projectId,
                                        'task_id' => $task_['id'],
                                        'type' => $statResultType__,
                                        'action' => StatResult::ACTION_PARENTFORCERESETED
                                    ];
                                    $statCounters = [
                                        'value' => $statResultVal__
                                    ];
                                    StatResult::updateCounter($where, $statCounters);
                                }
                            }
                        }
                    }
                }
                // if (in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                // {
                //     $counters = array_merge($counters, [
                //         'work_count' => -1,
                //         'allow_count' => -1,
                //         'other_operated_count' => 1,
                //         'label_count' => !empty($work_['label_count']) ? -$work_['label_count'] : 0,
                //         'point_count' => !empty($work_['point_count']) ? -$work_['point_count'] : 0,
                //         'line_count' => !empty($work_['line_count']) ? -$work_['line_count'] : 0,
                //         'rect_count' => !empty($work_['rect_count']) ? -$work_['rect_count'] : 0,
                //         'polygon_count' => !empty($work_['polygon_count']) ? -$work_['polygon_count'] : 0,
                //         'sharepoint_count' => !empty($work_['sharepoint_count']) ? -$work_['sharepoint_count'] : 0,
                //         'other_count' => !empty($work_['other_count']) ? -$work_['other_count'] : 0,
                //         'label_time' => !empty($work_['label_time']) ? -$work_['label_time'] : 0
                //     ]);
                // }
                if (in_array($work_['type'], [Work::TYPE_DIFFICULT, Work::TYPE_DIFFICULTREVISE]))
                {
                    $counters['difficult_revise_count'] = 1;
                }
                if (in_array($work_['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                {
                    $counters['refused_revise_count'] = 1;
                }
                if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE]))
                {
                    $counters['refuse_revise_count'] = 1;
                }
                if (in_array($work_['type'], [Work::TYPE_FORCEREFUSE]))
                {
                    $counters['refused_revise_count'] = 1;
                }
                Stat::updateCounter($this->projectId, $this->batchId, $stepId_, $counters);
            
                if (!empty($work_['user_id']))
                {
                    $counters = [
                        'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                    ];
                    if (in_array($work_['status'], [Work::STATUS_SUBMITED, Work::STATUS_FINISH]))
                    {
                        $counters = array_merge($counters, [
                            'work_count' => -1,
                            'allow_count' => -1,
                            'other_operated_count' => 1,
                            // 'label_count' => !empty($work_['label_count']) ? -$work_['label_count'] : 0,
                            // 'point_count' => !empty($work_['point_count']) ? -$work_['point_count'] : 0,
                            // 'line_count' => !empty($work_['line_count']) ? -$work_['line_count'] : 0,
                            // 'rect_count' => !empty($work_['rect_count']) ? -$work_['rect_count'] : 0,
                            // 'polygon_count' => !empty($work_['polygon_count']) ? -$work_['polygon_count'] : 0,
                            // 'sharepoint_count' => !empty($work_['sharepoint_count']) ? -$work_['sharepoint_count'] : 0,
                            // 'other_count' => !empty($work_['other_count']) ? -$work_['other_count'] : 0,
                            // 'label_time' => !empty($work_['label_time']) ? -$work_['label_time'] : 0
                        ]);
                    }
                    if (in_array($work_['type'], [Work::TYPE_DIFFICULT, Work::TYPE_DIFFICULTREVISE]))
                    {
                        $counters['difficult_revise_count'] = 1;
                    }
                    if (in_array($work_['type'], [Work::TYPE_AUDITREFUSED, Work::TYPE_REFUSEREVISE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    if (in_array($work_['type'], [Work::TYPE_AUDITREFUSE, Work::TYPE_REFUSESUBMITREVISE]))
                    {
                        $counters['refuse_revise_count'] = 1;
                    }
                    if (in_array($work_['type'], [Work::TYPE_FORCEREFUSE]))
                    {
                        $counters['refused_revise_count'] = 1;
                    }
                    StatUser::updateCounter($this->projectId, $this->batchId, $stepId_, $work_['user_id'], $counters);
                }
            }
            
            //已领取时,要清除清空掉缓存
            if (in_array($work_['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]))
            {
                //实例化执行类
                $taskHandler = new TaskHandler();
                $isinit = $taskHandler->init($work_['project_id'], $work_['batch_id'], $work_['step_id'], $work_['user_id']);
                if (!$isinit)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                }
                else 
                {
                    //获取本项目本批次本分步的所有场景
                    $scenes = $taskHandler->getUserScenes();
                    $_logs['$scenes'] = $scenes;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' getUserScenes '.json_encode($_logs));
                    
                    if ($scenes)
                    {
                        foreach ($scenes as $scene)
                        {
                            $taskHandler->clearUserCache($scene, $dataId, true);
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' clearUserCache '.json_encode($_logs));
                        }
                    }
                }
            }
            
            //-------------------------------
            
            if ($workType == Work::TYPE_PARENTREFUSED)
            {
                //分发作业重新开启
                $attributes = [
                    'status' => Work::STATUS_DELETED,
                    'type' => Work::TYPE_PARENTREFUSED,
                    'updated_at' => time()
                ];
                Work::updateAll($attributes, ['id' => $work_['id']]);
                
                //未领取时,不需要操作记录
                if (!in_array($work_['status'], [Work::STATUS_NEW]))
                {
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work_['step_id'];
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_PARENTREFUSED;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_DELETED;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskRefuse($work_['user_id'], $work_['project_id'], $task_['id'], $dataId);
                }
            }
            elseif ($workType == Work::TYPE_PARENTRESETED)
            {
                //分发作业重新开启
                $attributes = [
                    'status' => Work::STATUS_DELETED,
                    'type' => Work::TYPE_PARENTRESETED,
                    'updated_at' => time()
                ];
                Work::updateAll($attributes, ['id' => $work_['id']]);
                
                //未领取时,不需要操作记录
                if (!in_array($work_['status'], [Work::STATUS_NEW]))
                {
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work_['step_id'];
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_PARENTRESETED;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_DELETED;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskReset($work_['user_id'], $work_['project_id'], $task_['id'], $dataId);
                }
            }
            elseif ($workType == Work::TYPE_PARENTREDO)
            {
                //分发作业重新开启
                $attributes = [
                    'status' => Work::STATUS_DELETED,
                    'type' => Work::TYPE_PARENTREDO,
                    'updated_at' => time()
                ];
                Work::updateAll($attributes, ['id' => $work_['id']]);
                
                //未领取时,不需要操作记录
                if (!in_array($work_['status'], [Work::STATUS_NEW]))
                {
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work_['step_id'];
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_PARENTREDO;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_DELETED;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskForceRefuse($work_['user_id'], $work_['project_id'], $task_['id'], $dataId);
                }
            }
            elseif ($workType == Work::TYPE_PARENTFORCEREFUSE)
            {
                //分发作业重新开启
                $attributes = [
                    'status' => Work::STATUS_DELETED,
                    'type' => Work::TYPE_PARENTFORCEREFUSE,
                    'updated_at' => time()
                ];
                Work::updateAll($attributes, ['id' => $work_['id']]);
            
                //未领取时,不需要操作记录
                if (!in_array($work_['status'], [Work::STATUS_NEW]))
                {
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = 0;
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_PARENTFORCEREFUSE;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_DELETED;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    Message::sendTaskForceRefuse($work_['user_id'], $work_['project_id'], $task_['id'], $dataId);
                }
            }
            elseif ($workType == Work::TYPE_PARENTFORCERESET)
            {
                //分发作业重新开启
                $attributes = [
                    'status' => Work::STATUS_DELETED,
                    'type' => Work::TYPE_PARENTFORCERESET,
                    'updated_at' => time()
                ];
                Work::updateAll($attributes, ['id' => $work_['id']]);
                
                //未领取时,不需要操作记录
                if (!in_array($work_['status'], [Work::STATUS_NEW]))
                {
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work_['step_id'];
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_PARENTFORCERESET;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_DELETED;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Message::sendTaskForceReset($work_['user_id'], $work_['project_id'], $task_['id'], $dataId);
                }
            }
        
            //反向删除分步的父分步
            $childStepParentIds = Step::getParentStepIds($stepId_);
            if (!empty($childStepParentIds))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepChildIds '.json_encode($_logs));
            
                foreach ($childStepParentIds as $cpstepId_)
                {
                    $_logs['$cpstepId_'] = $cpstepId_;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop step '.json_encode($_logs));
            
                    if (!$cpstepId_)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cpstepId_ 0 '.json_encode($_logs));
                        continue;
                    }
            
                    if ($cpstepId_ == $stepId)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $cpstepId_ == $stepId, continue '.json_encode($_logs));
                        continue;
                    }
            
                    //分发作业的结果
                    $work_ = Work::find()
                    ->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $cpstepId_, 'data_id' => $dataId])
                    ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
                    ->orderBy(['id' => SORT_DESC])
                    ->asArray()->limit(1)->one();
                    $_logs['$work_'] = $work_;
            
                    if (!$work_)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $work_ record '.json_encode($_logs));
                        continue;
                    }
                    
                    if (!in_array($work_['status'], [Work::STATUS_FINISH]))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work status other '.json_encode($_logs));
                        return false;
                    }
            
                    $task_ = Task::find()->where(['project_id' => $this->projectId,'batch_id' => $this->batchId,'step_id' => $cpstepId_,'status' => Task::STATUS_NORMAL])->asArray()->limit(1)->one();
                    if (!$task_)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $task_ record '.json_encode($_logs));
                        continue;
                    }
            
                    $step_ = Step::find()->where(['id' => $cpstepId_])->asArray()->limit(1)->one();
                    if (!$step_)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $step_ record '.json_encode($_logs));
                        continue;
                    }
            
                    //-------------------------------
            
                    if ($step_['type'] == Step::TYPE_PRODUCE)
                    {
                        //统计框数, 矫正框数
                        $workResult_ = WorkResult::find()->where(['work_id' => $work_['id']])->asArray()->limit(1)->one();
                        list($stat_, $statUser_) = ProjectHandler::statResult($workResult_['result']);
            
                        $counters = [];
                        //$counters['amount'] = -1;
                        //$counters['reseted_count'] = 1;
                        $counters['allowed_count'] = in_array($work_['status'], [Work::STATUS_FINISH]) ? -1 : 0;
                        Stat::updateCounter($this->projectId, $this->batchId, $cpstepId_, $counters);
            
                        if ($statUser_)
                        {
                            foreach ($statUser_ as $userId_ => $userstat_)
                            {
                                $counters = [];
                                //$counters['work_time'] = 0;
                                //$counters['work_count'] = 0;
                                //$counters['join_count'] = -1;
                                //$counters['reseted_count'] = 1;
                                $counters['allowed_count'] = 0;
                                StatUser::updateCounter($this->projectId, $this->batchId, $cpstepId_, $userId_, $counters);
                            }
                        }
                        if (!empty($work_['user_id']))
                        {
                            $counters = [
                                //'work_time' => 0,
                                //'work_count' => -1,
                                //'join_count' => 0,
                                //'reseted_count' => 1,
                                'allowed_count' => ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0,
                            ];
                            StatUser::updateCounter($this->projectId, $this->batchId, $cpstepId_, $work_['user_id'], $counters);
                        }
                    }
                    elseif (in_array($step_['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
                    {
                        //统计本次作业的结果
                        $dataResult_ = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
                        list($stat_, $statUser_) = ProjectHandler::statResult($dataResult_['result']);
                        $counters = [];
                        //$counters['amount'] = -1;
                        //$counters['reseted_count'] = 1;
                        $counters['allowed_count'] = ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0;
                        Stat::updateCounter($this->projectId, $this->batchId, $cpstepId_, $counters);
            
                        if (!empty($work_['user_id']))
                        {
                            $counters = [];
                            //$counters['reseted_count'] = 1;
                            $counters['allowed_count'] = ($work_['status'] == Work::STATUS_FINISH) ? -1 : 0;
                            StatUser::updateCounter($this->projectId, $this->batchId, $cpstepId_, $work_['user_id'], $counters);
                        }
                    }
            
                    //分发作业重新开启
                    $attributes = [
                        'status' => Work::STATUS_SUBMITED,
                        //'type' => Work::TYPE_PARENTRESETED,
                        'updated_at' => time()
                    ];
                    Work::updateAll($attributes, ['id' => $work_['id']]);
            
                    //添加作业记录
                    $workRecord = new WorkRecord();
                    $workRecord->project_id = $this->projectId;
                    $workRecord->work_id = $work_['id'];
                    $workRecord->data_id = $dataId;
                    $workRecord->batch_id = $work_['batch_id'];
                    $workRecord->step_id = $work_['step_id'];
                    $workRecord->task_id = $task_['id'];
                    $workRecord->type = WorkRecord::TYPE_BACKTOSUBMIT;
                    $workRecord->after_user_id = $this->userId;
                    $workRecord->after_work_status = Work::STATUS_SUBMITED;
                    $workRecord->before_user_id = $work_['user_id'];
                    $workRecord->before_work_status = $work_['status'];
                    $workRecord->created_at = time();
                    $workRecord->updated_at = time();
                    $workRecord->save();
                    
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' delete parent '.json_encode($_logs));
                }
            }
            //====================================
            
            //删除子分步
            $this->deleteAllChildren($stepId_, $dataId, $workType);
            
        }
    }
    
    
    /**
     * AI模型辅助处理
     *
     * @param string $class
     * @param string $action
     * @param string $dataId
     * @return array
     */
    public function aimodel($class, $dataId, $args = [])
    {
        $_logs['$dataId'] = $dataId;
        
        /*
        //并发锁
        if($this->lock($dataId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $isLock '.json_encode($_logs));
            return FormatHelper::result('', 'task_oprate_excessive', yii::t('app', 'task_oprate_excessive'));
        }*/
        /*
        $aimodel = AiModel::find()->where(['id' => $aimodelId])->asArray()->with(['group'])->limit(1)->one();
        if (empty($aimodel['group']['script']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' script empty '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $class = $aimodel['group']['script'];
        
        if($aimodel['status'] != AiModel::STATUS_ENABLE)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' aimodel status not enabled '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }*/
        
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        
        $dataResult = DataResult::find()->where(['data_id' => $dataId])->asArray()->limit(1)->one();
        if (!$dataResult)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataResult notexist '.json_encode($_logs));
            return FormatHelper::result('', 'error', 'error');
        }
        $item = $data;
        $item['dataResult'] = $dataResult;
        
        $params = (array)$args;
        $params['item'] = $item;
        //$params['aimodel'] = $aimodel;
        
        //获取当前有效的work，ai绩效统计会用到
        $work = Work::find()
        ->where(['project_id' => $this->projectId, 'batch_id' => $this->batchId, 'step_id' => $this->stepId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->asArray()->limit(1)->one();
        if($work)
        {
            $work['user_id'] = $this->userId;
            $params['work'] = $work;
        }
        else
        {
            $params['work'] = [];
        }
        /*
        $template = Template::find()->select(['id', 'config'])->where(['id' => $this->project['template_id']])->asArray()->limit(1)->one();
        if(!empty($template)){
            $params['template_config'] = $template['config'];
        }*/
        
        //80种常见物品识别
        if ($class == 'image/BaiduMultiObjectDetect')
        {
            //$data = '{"data":[{"type":"rect","id":"de90d949-e06d-4817-b2e8-19d8c52770be","points":[{"x":0.584394,"y":0.657549},{"x":0.597283,"y":0.657549},{"x":0.597283,"y":0.710227},{"x":0.584394,"y":0.710227}],"strokeWidth":2,"label":["ignore+no","Pedestrian","严重遮挡30-70%"],"code":["","直立行走的行人",""],"category":["ignore","类别","遮挡选择"],"color":"#ffff00","cBy":"10089","cTime":1539581307,"mBy":"","mTime":"","minWidth":3,"minHeight":3,"maxWidth":0,"maxHeight":0,"angle":0,"editable":true}]}';
            //return FormatHelper::result($data);
        }
        elseif ($class == 'audio/BaiduAudio')
        {
            
        }
        elseif ($class == 'text/tokenize')
        {
            $labels = ProjectHandler::fetchLabelFromTemplate($template['config']);
            $dicts = ProjectHandler::fetchDictFromTemplate($template['config']);
            
            $params['labels'] = $labels;
            $params['dicts'] = $dicts;
        }
        elseif ($class == 'audio/KedaXunfeiV3')
        {
            
        }
        //超像素功能
        elseif ($class == 'image/superpixel')
        {
            $data = '{"data":[{"type":"rect","id":"de90d949-e06d-4817-b2e8-19d8c52770be","points":[{"x":0.584394,"y":0.657549},{"x":0.597283,"y":0.657549},{"x":0.597283,"y":0.710227},{"x":0.584394,"y":0.710227}],"strokeWidth":2,"label":["ignore+no","Pedestrian","严重遮挡30-70%"],"code":["","直立行走的行人",""],"category":["ignore","类别","遮挡选择"],"color":"#ffff00","cBy":"10089","cTime":1539581307,"mBy":"","mTime":"","minWidth":3,"minHeight":3,"maxWidth":0,"maxHeight":0,"angle":0,"editable":true}]}';
            return FormatHelper::result($data);
        }
        
        $action = !empty($args['action']) ? $args['action'] : 'run';
        
        $_logs['$class'] = $class;
        $_logs['$action'] = $action;
        $_logs['$params'] = $params;//ArrayHelper::var_desc($params);
        
        $aiResult = AiHandler::run($class, $action, $params);
        $_logs['$aiResult'] = ArrayHelper::var_desc($aiResult);
        if (!empty($aiResult['error']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' AiHandler error '.json_encode($_logs));
            return FormatHelper::result('', $aiResult['error'], $aiResult['message']);
        }
        elseif (isset($aiResult['data']))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return FormatHelper::result($aiResult['data']);
        }
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode($_logs));
        return FormatHelper::result('', 'error', 'error');
    }
}