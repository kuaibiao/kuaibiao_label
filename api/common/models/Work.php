<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;

/**
 * work 表数据模型
 *
 */
class Work extends ModelComponent
{
    const STATUS_NEW = 0;//待领取
    const STATUS_RECEIVED = 1;//已领取
    const STATUS_EXECUTING = 2;//执行中
    const STATUS_SUBMITED = 3;//已提交
    const STATUS_FINISH = 4;//已完成
    const STATUS_DELETED = 5;//已失效
    const STATUS_REFUSED = 6;//驳回作业
    const STATUS_DIFFICULT = 7;//疑难作业
    const STATUS_REFUSEDSUBMIT = 8;//返工作业, 被驳回再提交的
    
    const STATUS_RESETED = 9; //未通过，数据库中使用status和type两个字段来标识
    const STATUS_GIVE_UP = 10; //已放弃，数据库中使用status和type两个字段来标识
    const STATUS_TIME_OUT = 11; //已超时，数据库中使用status和type两个字段来标识
    const STATUS_TO_AUDIT = 12; //待审核，数据库中使用status和type两个字段来标识
    const STATUS_TO_ACCEPT = 13; //待验收，数据库中使用status和type两个字段来标识
    const STATUS_AUDIT_REFUSE = 14; //已驳回，审核驳回作业员，数据库中使用status和type两个字段来标识
    const STATUS_AUDIT_RESET = 15; //已重置，审核重置作业员，数据库中使用status和type两个字段来标识
    const STATUS_PRODUCE_EXECUTING = 16; //作业中，作业员执行作业，数据库中使用status和type两个字段来标识
    const STATUS_AUDIT_EXECUTING = 17; //审核中，审核人员领取作业即变为作业中，数据库中使用status和type两个字段来标识
    const STATUS_ACCEPTANCE_EXECUTING = 18; //验收中，验收人员领取作业即变为作业中，数据库中使用status和type两个字段来标识
    const STATUS_ACCEPTANCE_FINISH = 19; //已完成，验收通过，数据库中使用status和type两个字段来标识

    const AUDIT_REFUSE = 0;//审核驳回
    const AUDIT_ALLOW = 1;//审核通过
    const AUDIT_RESET = 2;//审核重置
    
    //对应is_refused
    const OPTION_DEFAULT = 0;
    const OPTION_REFUSED = 1;//被驳回
    const OPTION_REFUSESUBMIT = 2;//返工作业
    
    const IS_CORRECT_NO = 0;
    const IS_CORRECT_YES = 1;
    
    const IS_SUBMITTED_NO = 0; //作业未提交过
    const IS_SUBMITTED_YES = 1; //作业提交过
    
    const IS_ALLOWED_NO = 0; //作业未被通过
    const IS_ALLOWED_YES = 1; //作业被通过
    
    const TYPE_NORMAL = 0;
    const TYPE_EXECUTE = 1;
    const TYPE_SUBMIT = 2;
    const TYPE_GIVEUP = 6;
    const TYPE_TIMEOUT = 7;
    const TYPE_DIFFICULT = 9;
    const TYPE_AUDITALLOW = 10;
    const TYPE_AUDITREFUSE = 11;
    const TYPE_AUDITRESET = 12;
    const TYPE_AUDITIGNORE = 18;
    const TYPE_AUDITALLOWED = 13;
    const TYPE_AUDITREFUSED = 14;
    const TYPE_AUDITRESETED = 15;
    const TYPE_AUDITIGNORED = 19;
    const TYPE_PARENTREFUSED = 16;
    const TYPE_PARENTRESETED = 17;
    const TYPE_REFUSEREVISE = 20;
    const TYPE_REFUSERESET = 21;
    const TYPE_REFUSESUBMITREVISE = 22; //返工作业审核
    const TYPE_REFUSESUBMITRESET = 23; //返工作业重置
    const TYPE_REFUSESUBMIT = 24; //已返工
    const TYPE_CANCELED = 25; //作废
    const TYPE_REDO = 40;
    const TYPE_PARENTREDO = 41;
    const TYPE_FORCEREFUSE = 50;
    const TYPE_FORCERESET = 51;
    const TYPE_PARENTFORCEREFUSE = 52;
    const TYPE_PARENTFORCERESET = 53;
    const TYPE_PARENTTIMEOUT = 54;
    const TYPE_DIFFICULTREVISE = 60;
    const TYPE_DIFFICULTRESET = 61;
    const TYPE_PARENTBATCHREFUSE = 70;
    const TYPE_PARENTBATCHRESET = 71;
    const TYPE_ROOTBATCHREFUSE = 72;
    const TYPE_ADMINBATCHREFUSE = 73;
    const TYPE_ROOTBATCHRESET = 74;
    const TYPE_ADMINBATCHRESET = 75;
    
    public static $tableName = 'work';
    
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
            ['project_id', 'integer' ],
            ['project_id', 'default', 'value' => 0],

            ['batch_id', 'integer' ],
            ['batch_id', 'default', 'value' => 0],

            ['step_id', 'integer' ],
            ['step_id', 'default', 'value' => 0],

            ['data_id', 'integer' ],
            ['data_id', 'default', 'value' => 0],

            ['status', 'integer' ],
            ['status', 'default', 'value' => self::STATUS_NEW],
            ['status', 'in', 'range' => array_keys(self::getStatuses())],

            ['user_id', 'integer' ],
            ['user_id', 'default', 'value' => 0],

            ['is_refused', 'integer' ],
            ['is_refused', 'default', 'value' => 0],

            ['start_time', 'integer' ],
            ['start_time', 'default', 'value' => time()],

            ['end_time', 'integer' ],
            ['end_time', 'default', 'value' => time()],

            [['created_at', 'updated_at'], 'integer'],

            //准确率;0-99
            ['correct_rate', 'integer'],
            ['correct_rate', 'default', 'value' => 0],

            //是否正确, 决定是否记录绩效
            ['is_correct', 'integer'],
            ['is_correct', 'default', 'value' => 0],
        ];
    }
    
    public static function batchInsert($project_id, $batch_id, $step_id ,$data_ids)
    {
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchInsert='.json_encode([$project_id, $batch_id, $step_id, $data_ids]));
    
        $fileds = ['project_id','batch_id','step_id','data_id','status', 'created_at', 'updated_at'];
        $created_at = $updated_at = time();
    
        $arr = array();
        foreach ($data_ids as $data_id)
        {
            $arr[] = array(
                $project_id,
                $batch_id,
                $step_id,
                $data_id,
                self::STATUS_NEW,
                $created_at,
                $updated_at
            );
    
            if (count($arr) >= 30 )
            {
                Yii::$app->db->createCommand()->batchInsert(self::tableName(), $fileds, $arr)->execute();
                $arr = array();
            }
        }
    
        if ($arr)
        {
            Yii::$app->db->createCommand()->batchInsert(self::tableName(), $fileds, $arr)->execute();
        }
    
    }
    
    public static function batchDelete($project_id, $batch_id)
    {
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchDelete='.json_encode([$project_id]));
    
        $attr = array();
        $attr['project_id'] = $project_id;
        $attr['batch_id'] = $batch_id;
        
        Yii::$app->db->createCommand()->delete(self::tableName(), $attr)->execute();
    }
    
    public static function getStatus($status)
    {
        $statuses = self::getStatuses();
        
        return isset($statuses[$status]) ? $statuses[$status] : '';
    }
    
    /**
     * @return array
     * 待发布,审核中,执行中,已完成
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_NEW => Yii::t('app', 'work_status_new'),
            self::STATUS_RECEIVED => Yii::t('app', 'work_status_received'),
            self::STATUS_EXECUTING => Yii::t('app', 'work_status_executing'),
            self::STATUS_SUBMITED => Yii::t('app', 'work_status_submited'),
            self::STATUS_FINISH => Yii::t('app', 'work_status_finished'),
            self::STATUS_DELETED => Yii::t('app', 'work_status_deleted'),
            self::STATUS_REFUSED => Yii::t('app', 'work_status_refused'),
            self::STATUS_REFUSEDSUBMIT => Yii::t('app', 'work_status_refusedsubmit'),
            self::STATUS_DIFFICULT => Yii::t('app', 'work_status_difficult'),
        ];
    }

    /**
     * @return array
     * 获取列表状态信息
     */
    public static function getListStatuses()
    {
        return [
            self::STATUS_NEW => Yii::t('app', 'work_status_new'),
            self::STATUS_RECEIVED => Yii::t('app', 'work_status_received'),
            self::STATUS_EXECUTING => Yii::t('app', 'work_status_executing'),
            self::STATUS_SUBMITED => Yii::t('app', 'work_status_submited'),
            self::STATUS_FINISH => Yii::t('app', 'work_status_finished'),
            self::STATUS_DELETED => Yii::t('app', 'work_status_deleted'),
            self::STATUS_REFUSED => Yii::t('app', 'work_status_refused'),
            self::STATUS_REFUSEDSUBMIT => Yii::t('app', 'work_status_refusedsubmit'),
            self::STATUS_DIFFICULT => Yii::t('app', 'work_status_difficult'),
            self::STATUS_RESETED => Yii::t('app', 'work_status_reseted'),
            self::STATUS_GIVE_UP => Yii::t('app', 'work_status_give_up'),
            self::STATUS_TIME_OUT => Yii::t('app', 'work_status_time_out'),
            self::STATUS_TO_AUDIT => Yii::t('app', 'work_status_to_audit'),
            self::STATUS_TO_ACCEPT => Yii::t('app', 'work_status_to_accept'),
            self::STATUS_AUDIT_REFUSE => Yii::t('app', 'work_status_audit_refuse'),
            self::STATUS_AUDIT_RESET => Yii::t('app', 'work_status_audit_reset'),
            self::STATUS_PRODUCE_EXECUTING => Yii::t('app', 'work_status_produce_executing'),
            self::STATUS_AUDIT_EXECUTING => Yii::t('app', 'work_status_audit_executing'),
            self::STATUS_ACCEPTANCE_EXECUTING => Yii::t('app', 'work_status_acceptance_executing'),
            self::STATUS_ACCEPTANCE_FINISH => Yii::t('app', 'work_status_acceptance_finish'),
        ];
    }

    /**
     * @return array
     * 已提交,已完成,驳回作业,未通过
     */
    public static function getProduceStatStatuses()
    {
        return [
            self::STATUS_SUBMITED => Yii::t('app', 'work_status_to_audit'),
            self::STATUS_FINISH => Yii::t('app', 'work_status_finished'),
            self::STATUS_REFUSED => Yii::t('app', 'work_status_refused'),
            self::STATUS_RESETED => Yii::t('app', 'work_status_reseted'),
        ];
    }

    /**
     * @return array
     * 已提交,已完成,驳回作业,未通过
     */
    public static function getAuditStatStatuses()
    {
        return [
            self::STATUS_SUBMITED => Yii::t('app', 'work_status_audit_submited'),
            self::STATUS_FINISH => Yii::t('app', 'work_status_finished'),
            self::STATUS_AUDIT_REFUSE => Yii::t('app', 'work_status_audit_refuse'),
            self::STATUS_REFUSED => Yii::t('app', 'work_status_refused'),
            self::STATUS_AUDIT_RESET => Yii::t('app', 'work_status_audit_reset'),
            self::STATUS_RESETED => Yii::t('app', 'work_status_reseted'),
        ];
    }

    /**
     * @return array
     * 已放弃,已超时,未通过
     */
    public static function getInvalidStatuses()
    {
        return [
            self::STATUS_GIVE_UP => Yii::t('app', 'work_status_give_up'),
            self::STATUS_TIME_OUT => Yii::t('app', 'work_status_time_out'),
            self::STATUS_RESETED => Yii::t('app', 'work_status_reseted'),
        ];
    }

    /**
     * @return array
     * 待质检,待验收,已驳回,已重置
     */
    public static function getAuditStatuses()
    {
        return [
            // self::STATUS_TO_CHECK => Yii::t('app', 'work_status_to_check'),
            self::STATUS_TO_ACCEPT => Yii::t('app', 'work_status_to_accept'),
            self::STATUS_AUDIT_REFUSE => Yii::t('app', 'work_status_audit_refuse'),
            self::STATUS_AUDIT_RESET => Yii::t('app', 'work_status_audit_reset'),
        ];
    }
    
    public static function getType($type)
    {
        $types = self::getTypes();
        
        return isset($types[$type]) ? $types[$type] : '';
    }
    
    /**
     * @return array
     * 待发布,审核中,执行中,已完成
     */
    public static function getTypes()
    {
        return [
            self::TYPE_NORMAL => Yii::t('app', 'work_type_normal'),
            self::TYPE_ALLOW => Yii::t('app', 'work_type_allow'),
            self::TYPE_REFUSE => Yii::t('app', 'work_type_refuse'),
            self::TYPE_RESET => Yii::t('app', 'work_type_reset'),
            self::TYPE_TIMEOUT => Yii::t('app', 'work_type_timeout'),
            self::TYPE_REFUSED => Yii::t('app', 'work_type_refused'),
            self::TYPE_PARENTREFUSED => Yii::t('app', 'work_type_parentrefused'),
            self::TYPE_RESETED => Yii::t('app', 'work_type_reseted'),
            self::TYPE_PARENTRESETED => Yii::t('app', 'work_type_parentreseted'),
            self::TYPE_REDO => Yii::t('app', 'work_type_redo'),
            //self::TYPE_REDONE => Yii::t('app', 'work_type_redone'),
            //self::TYPE_PARENTREDO => Yii::t('app', 'work_type_parentredo'),
            self::TYPE_FORCERESET => Yii::t('app', 'work_type_forcereset'),
            self::TYPE_PARENTFORCERESET => Yii::t('app', 'work_type_parentforcereset'),
            self::TYPE_REFUSERESET => Yii::t('app', 'work_type_refusereset'),
            self::TYPE_REFUSEDSUBMIT => Yii::t('app', 'work_type_refused_submit'),
            //self::TYPE_TIMEOUT_REFUSED => Yii::t('app', 'work_type_timeout_refused'),
            self::TYPE_DIFFICULT => Yii::t('app', 'work_type_difficult'),
            self::TYPE_GIVEUP => Yii::t('app', 'work_type_giveup'),
        ];
    }
    
    /**
     * @return array
     * 待发布,审核中,执行中,已完成
     */
    public static function getDeletedTypes()
    {
        return [
            self::TYPE_AUDITREFUSE => Yii::t('app', 'work_type_auditrefuse'),
            self::TYPE_AUDITRESET => Yii::t('app', 'work_type_auditreset'),
            self::TYPE_TIMEOUT => Yii::t('app', 'work_type_timeout'),
            self::TYPE_AUDITREFUSED => Yii::t('app', 'work_type_auditrefused'),
            //self::TYPE_PARENTREFUSED => Yii::t('app', 'work_type_parentrefused'),
            self::TYPE_AUDITRESETED => Yii::t('app', 'work_type_auditreseted'),
            //self::TYPE_PARENTRESETED => Yii::t('app', 'work_type_parentreseted'),
            //self::TYPE_REDO => Yii::t('app', 'work_type_redo'),
            //self::TYPE_REDONE => Yii::t('app', 'work_type_redone'),
            self::TYPE_PARENTREDO => Yii::t('app', 'work_type_parentredo'),
            self::TYPE_FORCEREFUSE => Yii::t('app', 'work_type_forcerefuse'),
            self::TYPE_FORCERESET => Yii::t('app', 'work_type_forcereset'),
            self::TYPE_PARENTFORCERESET => Yii::t('app', 'work_type_parentforcereset'),
            self::TYPE_REFUSERESET => Yii::t('app', 'work_type_refusereset'),
            self::TYPE_REFUSESUBMITRESET => Yii::t('app', 'work_type_refusesubmitreset'),
            self::TYPE_DIFFICULT => Yii::t('app', 'work_type_difficult'),
            self::TYPE_GIVEUP => Yii::t('app', 'work_type_giveup'),
            self::TYPE_DIFFICULTREVISE => Yii::t('app', 'work_type_difficultreverse'),
            self::TYPE_DIFFICULTRESET => Yii::t('app', 'work_type_difficultreset'),
        ];
    }
    
    function getWorkResult(){
        return $this->hasOne(WorkResult::className(), ['work_id' => 'id']);
    }
    
    function getData(){
        return $this->hasOne(Data::className(), ['id' => 'data_id'])->select(['id', 'name', 'sort']);
    }
    
    public function getDataResult(){
        return $this->hasOne(DataResult::className(), ['data_id' => 'data_id']);
    }
    
    //关联批次
    function getUser(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])->select(['id', 'email', 'nickname']);
    }
    
    //关联批次
    function getStep(){
        return $this->hasOne(Step::className(), ['id' => 'step_id'])->select(['id', 'name', 'type']);
    }
    
    
    public static function mergeCorrectRate($projectId, $batchId, $stepChildId, $dataId, $correctRate, $isRefuseSubmit = false)
    {
        $_logs = ['$projectId' => $projectId, '$batchId' => $batchId, '$stepChildId' => $stepChildId, '$dataId' => $dataId, '$correctRate' => $correctRate, '$isRefuseSubmit' => $isRefuseSubmit];
        
        //判断是否有子分步工作
        $stepChildWorkInfo = Work::find()
        ->select(['id'])
        ->where(['project_id' => $projectId,'batch_id' => $batchId, 'step_id' => $stepChildId, 'data_id' => $dataId])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->asArray()->limit(1)->one();
        
        if (!$stepChildWorkInfo)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $stepChildWorkInfo '.json_encode($_logs));
            
            //查询最后一次审核人员
            $lastAuditWork = [];
            if ($isRefuseSubmit)
            {
                $lastAuditWork = Work::find()
                ->where(['data_id' => $dataId, 'step_id' => $stepChildId, 'status' => Work::STATUS_DELETED])
                ->orderBy(['id' => SORT_DESC])->asArray()->limit(1)->one();
                $_logs['$lastAuditWork'] = $lastAuditWork;
                
                if (empty($lastAuditWork['user_id']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $lastAuditWork.userid empty '.json_encode($_logs));
                    $lastAuditWork = [];
                }
            }
            
            //重新生成记录
            $workNew = new Work();
            $workNew->project_id = $projectId;
            $workNew->batch_id = $batchId;
            $workNew->step_id = $stepChildId;
            $workNew->data_id = $dataId;
            $workNew->user_id = $lastAuditWork ? $lastAuditWork['user_id'] : 0;
            $workNew->start_time = 0;
            $workNew->delay_time = 0;
            $workNew->status = $lastAuditWork ? Work::STATUS_REFUSEDSUBMIT : Work::STATUS_NEW;
            $workNew->type = Work::TYPE_NORMAL;
            $workNew->correct_rate = $correctRate;
            $workNew->created_at = time();
            $workNew->updated_at = time();
            $workNew->save();
        
            //保存驳回理由
            $workResultNew = new WorkResult();
            $workResultNew->work_id = $workNew->id;
            $workResultNew->result = '';
            $workResultNew->feedback = '';
            $workResultNew->save();
        
            //增加任务总量
            $stepChildTask = Task::find()
            ->where(['project_id' => $projectId, 'batch_id' => $batchId, 'step_id' => $stepChildId,'status' => Task::STATUS_NORMAL])
            ->asArray()->limit(1)->one();
        
            if (!$stepChildTask)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $stepChildTask not exist '.json_encode($_logs));
                return false;
            }
        
            $attributes = [
                'amount' => 1,
            ];
            Task::updateAllCounters($attributes, ['id' => $stepChildTask['id']]);
        
            //更新此分步的总数
            $stepChildStat = Stat::find()
            ->where(['project_id' => $projectId, 'batch_id' => $batchId, 'step_id' => $stepChildId])
            ->asArray()->limit(1)->one();
            if (!$stepChildStat)
            {
                $stepChildStat = new Stat();
                $stepChildStat->project_id = $projectId;
                $stepChildStat->batch_id = $batchId;
                $stepChildStat->step_id = $stepChildId;
                $stepChildStat->task_id = $stepChildTask['id'];
                $stepChildStat->created_at = time();
                $stepChildStat->save();
            }
            $attributes = [
                'amount' => 1,
                'refuse_revised_count' => $lastAuditWork ? 1 : 0
            ];
            Stat::updateAllCounters($attributes, ['id' => $stepChildStat['id']]);
            
            if ($lastAuditWork)
            {
                $counters = [
                    'refuse_revised_count' => 1,
                ];
                StatUser::updateCounter($projectId, $batchId, $lastAuditWork['step_id'], $lastAuditWork['user_id'], $counters);
                //StatUserDay::updateCounter($projectId, $batchId, $lastAuditWork['step_id'], $lastAuditWork['user_id'], $counters);
            }
        }
        else
        {
            $attributes = [
                'correct_rate' => $correctRate
            ];
            Work::updateAll($attributes, ['id' => $stepChildWorkInfo['id']]);
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
}
