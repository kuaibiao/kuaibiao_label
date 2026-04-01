<?php

namespace common\models;

use Yii;
use common\helpers\StringHelper;
use common\models\Data;
use common\models\DataResult;
use common\models\Work;
use common\models\WorkResult;
use common\components\ModelComponent;
use common\helpers\FormatHelper;
use common\helpers\ArrayHelper;
/**
 * Project 表数据模型
 *
 */
class Project extends ModelComponent
{
    const STATUS_RELEASING = 0;//发布中
    const STATUS_SETTING = 1;//配置中
    const STATUS_PREPARING = 2;//准备中
    const STATUS_WORKING = 3;//作业中
    const STATUS_PAUSED = 4;//暂停
    const STATUS_STOPPED = 5;//已停止
    const STATUS_FINISH = 6;//结束
    const STATUS_DELETE = 7;//删除

    //暂不使用
    const RELEASE_STATUS_CATEGORY = 0;//选择分类
    const RELEASE_STATUS_FORM = 1;//填写表单
    const RELEASE_STATUS_TEMPORARYSTORAGE = 2;//暂存发布
    const RELEASE_STATUS_FINISH = 5;//发布完成
    
    //暂不使用
    const AUDIT_STATUS_WAITING = 0;//等待审核
    const AUDIT_STATUS_SUCCESS = 1;//审核通过
    const AUDIT_STATUS_FAILURE = 2;//审核失败
    const AUDIT_STATUS_PAUSING = 3;//暂停

    //暂不使用
    const WORK_STATUS_SETTING = 0;//配置中
    const WORK_STATUS_TODATALOAD = 1;//等待数据初始化
    const WORK_STATUS_DATALOADING = 2;//数据初始化中//解包并存储到数据库
    const WORK_STATUS_DATALOADFAIL = 6;//数据初始化错误
    const WORK_STATUS_EXECUTING = 3;//作业中
    const WORK_STATUS_FINISH = 4;//已交付
    const WORK_STATUS_CANCEL = 5;//取消

    //暂不使用
    const UNIT_PRICE_TYPE_0 = '0';
    const UNIT_PRICE_TYPE_1 = '1';
    const UNIT_PRICE_TYPE_2 = '2';
    
    const ASSIGN_TYPE_NORMAL = 0;//抢单模式
    const ASSIGN_TYPE_STUDY = 1;//学习模式,教学和评测
    
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'project';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id','category_id','template_id','status','amount','data_count','upload_data_count','assign_type', 'created_at', 'updated_at'], 'default', 'value' => 0],
            [['user_id','category_id','template_id','status','amount','data_count','upload_data_count','assign_type', 'created_at', 'updated_at'], 'integer'],

            ['name', 'required'],
            ['name', 'validateName'],
            ['name', 'string', 'min' => 1, 'max' => 254],

            ['start_time', 'filter', 'filter' => function($value) {
                //return is_numeric($value) ? strtotime(date('Y-m-d', $value)) : strtotime(date('Y-m-d', strtotime($value)));
                return is_numeric($value) ? $value : strtotime(date('Y-m-d', strtotime($value)));
            }],
            ['end_time', 'filter', 'filter' => function($value) {
                //return is_numeric($value) ? strtotime(date('Y-m-d', $value)) + 24 * 3600 - 1 :  strtotime(date('Y-m-d', strtotime($value))) + 24 * 3600 - 1;
                return is_numeric($value) ? $value : strtotime(date('Y-m-d', strtotime($value))) + 24 * 3600 - 1;
            }],
            [['start_time', 'end_time'], 'integer'],
            ['start_time', 'compare', 'compareAttribute' => 'end_time', 'operator' => '<='],

            //数据表后缀, 自动生成, 请勿赋值
            ['table_suffix', 'default', 'value' => self::getTableSuffix()],
            ['table_suffix', 'string', 'min' => 1, 'max' => 30],
            
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '项目名称',
            'user_id' => 'user_id',
            'category_id' => '分类',
            'description' => 'description',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'start_time' => '开始时间',
            'end_time' => '截止时间',
        ];
    }
    
    //判断租户中的项目名称唯一
    public function validateName($attribute, $params)
    {
        $_logs = ['$projectName' => $this->name, '$userId' => $this->user_id];
        
        if (!$this->hasErrors())
        {
            $project = Project::find()->select('id')->andWhere(['name' => $this->name])->andWhere(['user_id' => $this->user_id])->asArray()->limit(1)->one();
            if($project && $project['id'] != $this->id)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'project_name_existed', Yii::t('app', 'project_name_existed'));
                return false;
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return true;
        }
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
            self::STATUS_RELEASING => Yii::t('app', 'project_status_releasing'),
            self::STATUS_SETTING => Yii::t('app', 'project_status_setting'),
            self::STATUS_PREPARING => Yii::t('app', 'project_status_preparing'),
            self::STATUS_WORKING => Yii::t('app', 'project_status_working'),
            self::STATUS_PAUSED => Yii::t('app', 'project_status_paused'),
            //self::STATUS_STOPPED => Yii::t('app', 'project_status_stopped'),
            self::STATUS_FINISH => Yii::t('app', 'project_status_finished'),
            //self::STATUS_DELETE => Yii::t('app', 'project_status_deleted'),
        ];
    }
    
    public static function getReleaseStatus($status)
    {
        $statuses = self::getReleaseStatuses();
        
        return isset($statuses[$status]) ? $statuses[$status] : '';
    }
    
    /**
     * @return array
     */
    public static function getReleaseStatuses()
    {
        return [
            self::RELEASE_STATUS_CATEGORY => Yii::t('app', 'project_release_status_category_select'),
            self::RELEASE_STATUS_FORM => Yii::t('app', 'project_release_status_form_fill'),
            self::RELEASE_STATUS_TEMPORARYSTORAGE => Yii::t('app', 'project_release_status_temp_store'),
            self::RELEASE_STATUS_FINISH => Yii::t('app', 'project_release_status_finished'),
        ];
    }
    
    public static function getWorkStatus($status)
    {
        $statuses = self::getWorkStatuses();
        
        return isset($statuses[$status]) ? $statuses[$status] : '';
    }
    
    /**
     * @return array
     */
    public static function getWorkStatuses()
    {
        return [
            self::WORK_STATUS_SETTING => Yii::t('app', 'project_work_status_setting'),
            self::WORK_STATUS_TODATALOAD => Yii::t('app', 'project_work_status_data_to_load'),
            self::WORK_STATUS_DATALOADING => Yii::t('app', 'project_work_status_data_loading'),
            self::WORK_STATUS_DATALOADFAIL => Yii::t('app', 'project_work_status_data_load_fail'),
            self::WORK_STATUS_EXECUTING => Yii::t('app', 'project_work_status_executing'),
            self::WORK_STATUS_FINISH => Yii::t('app', 'project_work_status_finished'),
            self::WORK_STATUS_CANCEL => Yii::t('app', 'project_work_status_canceled'),
        ];
    }
    
    public static function getAssignType($var)
    {
        $vars = self::getAssignTypes();
        
        return isset($vars[$var]) ? $vars[$var] : '';
    }
    
    /**
     * @return array
     */
    public static function getAssignTypes()
    {
        return [
            self::ASSIGN_TYPE_NORMAL => Yii::t('app', 'project_assign_type_normal'),
            self::ASSIGN_TYPE_STUDY => Yii::t('app', 'project_assign_type_study'),
        ];
    }
    
    //生成默认名称, 分类名+(1)
    public static function defaultName($prefix, $userId = 0)
    {
        $_logs = ['$prefix' => $prefix, '$userId' => $userId];
        
        if (preg_match('/\(([0-9]+)\)$/', $prefix, $matches))
        {
            if (is_numeric($matches[1]))
            {
                $currentId = $matches[1];
                $prefix = rtrim($prefix, '('.$currentId.')');
            }
        }
        
        //查询名称是否存在
        $query = Project::find()->select(['id', 'name'])->andWhere(['name' => $prefix]);
        $query->andWhere(['not', ['status' => Project::STATUS_DELETE]]);
        $userId && $query->andWhere(['user_id' => $userId]);
        $info = $query->orderBy(['id' => SORT_DESC])->limit(1)->asArray()->one();
        
        //存在的情况
        if ($info)
        {
            //查询名称匹配.*(\d)的最后的记录
            $regexpName = '^'.StringHelper::regexg_encode($prefix) .'\\([0-9]+\\)$';
            $_logs['$regexpName'] = $regexpName;
            
            $lastQuery = Project::find()->select(['id', 'name'])->andWhere(['regexp', 'name', $regexpName]);
            $lastQuery->andWhere(['not', ['status' => Project::STATUS_DELETE]]);
            $userId && $lastQuery->andWhere(['user_id' => $userId]);
            
            $lastList = $lastQuery->orderBy(['id' => SORT_DESC])->limit(11)->asArray()->all();
            
            $lastId = 0;
            if ($lastList)
            {
                foreach ($lastList as $v)
                {
                    if (preg_match('/^'.StringHelper::regexg_encode($prefix).'\(([0-9]+)\)$/i', $v['name'], $matches))
                    {
                        if (is_numeric($matches[1]) && $matches[1] > $lastId)
                        {
                            $lastId = $matches[1];
                        }
                    }
                }
            }
            
            $lastId++;
            $newName = $prefix . '('.$lastId.')';
            $_logs['$newName'] = $newName;
        }
        else 
        {
            $newName = $prefix;
            $_logs['$newName'] = $newName;
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $newName;
    }
    
    //生成数据表名后缀
    public static function getTableSuffix()
    {
        $suffix = self::generateSuffix();
        
        Data::setTable($suffix);
        DataResult::setTable($suffix);
        Work::setTable($suffix);
        WorkResult::setTable($suffix);
        WorkRecord::setTable($suffix);
        
        return $suffix; 
    }

    public static function getUnitPriceType($val)
    {
        $vals = self::getUnitPriceTypes();
        return isset($vals[$val]) ? $vals[$val] : null;
    }
    
    /**
     * @return array
     */
    public static function getUnitPriceTypes()
    {
        return [
            self::UNIT_PRICE_TYPE_0 => Yii::t('app', 'project_price_per_piece'),
            self::UNIT_PRICE_TYPE_1 => Yii::t('app', 'project_price_per_pic'),
            self::UNIT_PRICE_TYPE_2 => Yii::t('app', 'project_price_per_point'),
        ];
    }
    
    //绑定属性模型
    public function getAttr(){
        return $this->hasOne(ProjectAttribute::className(), ['project_id' => 'id'])
        ->select(['description', 'uploadfile_type', 'uploadfile_account']);
    }
    
    //绑定分类模型
    public function getCategory(){
        return $this->hasOne(Category::className(), ['id' => 'category_id'])
        ->select(['id','name', 'key', 'type', 'status', 'file_type', 'view', 'draw_type', 'file_extensions','upload_file_extensions', 'icon', 'thumbnail', 'video_as_frame']);
    }
    
    //绑定模板模型
    public function getTemplate(){
        return $this->hasOne(Template::className(), ['id' => 'template_id'])
        ->select(['id', 'name', 'config']);
    }

    //绑定批次模型
    public function getBatches(){
        return $this->hasMany(Batch::className(), ['project_id' => 'id'])
        ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]);
    }

    //绑定分步模型
    public function getSteps(){
        return $this->hasMany(Step::className(), ['project_id' => 'id'])
        ->andWhere(['status' => Step::STATUS_NORMAL])
        ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC]);
    }

    public function getStepGroups($projectId = [])
    {
        $_logs = ['$projectId' => $projectId];

        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found'));
        }
        if (in_array($project['status'], array(Project::STATUS_DELETE)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow'));
        }

        $stepTypes = Step::getTypes();
        
        //--------------------
        
        $stepGroupList = StepGroup::find()
        ->select(['id', 'project_id', 'status', 'sort', 'parent_id'])
        ->where(['project_id' => $projectId, 'status' => StepGroup::STATUS_NORMAL])
        ->with(['steps'])
        ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
        ->asArray()->all();
        
        //如果没有记录, 则自动创建
        if (!$stepGroupList)
        {
            $produceStepGroup = new StepGroup();
            $produceStepGroup->project_id = $projectId;
            $produceStepGroup->status = StepGroup::STATUS_NORMAL;
            $produceStepGroup->parent_id = 0;
            $produceStepGroup->sort = 0;
            $produceStepGroup->save();
            
            $produceStep = new Step();
            $produceStep->project_id = $projectId;
            $produceStep->step_group_id = $produceStepGroup->id;
            $produceStep->type = Step::TYPE_PRODUCE;
            $produceStep->status = Step::STATUS_NORMAL;
            $produceStep->sort = 0;
            $produceStep->save();
            
            $auditStepGroup = new StepGroup();
            $auditStepGroup->project_id = $projectId;
            $auditStepGroup->status = StepGroup::STATUS_NORMAL;
            $auditStepGroup->parent_id = $produceStepGroup->id;
            $auditStepGroup->sort = 0;
            $auditStepGroup->save();
            
            $auditStep = new Step();
            $auditStep->project_id = $projectId;
            $auditStep->step_group_id = $auditStepGroup->id;
            $auditStep->type = Step::TYPE_AUDIT;
            $auditStep->status = Step::STATUS_NORMAL;
            $auditStep->sort = 0;
            $auditStep->save();
            
            $acceptanceStepGroup = new StepGroup();
            $acceptanceStepGroup->project_id = $projectId;
            $acceptanceStepGroup->status = StepGroup::STATUS_NORMAL;
            $acceptanceStepGroup->parent_id = $auditStepGroup->id;
            $acceptanceStepGroup->sort = 0;
            $acceptanceStepGroup->save();
            
            $acceptanceStep = new Step();
            $acceptanceStep->project_id = $projectId;
            $acceptanceStep->step_group_id = $acceptanceStepGroup->id;
            $acceptanceStep->type = Step::TYPE_ACCEPTANCE;
            $acceptanceStep->status = Step::STATUS_NORMAL;
            $acceptanceStep->sort = 0;
            $acceptanceStep->save();
            
            $stepGroupList = StepGroup::find()
            ->select(['id', 'project_id', 'status', 'sort', 'parent_id'])
            ->where(['project_id' => $projectId, 'status' => StepGroup::STATUS_NORMAL])
            ->with(['steps'])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
            ->asArray()->all();
        }
        
        return FormatHelper::resultStrongType([
            'stepGroupList' => $stepGroupList,
            'step_types' => $stepTypes,
        ]);
    }
    
    //绑定任务模型
    public function getTasks(){
        return $this->hasMany(Task::className(), ['project_id' => 'id'])
        ->andWhere(['not', ['status' => Task::STATUS_DELETED]])
        ->with(['batch', 'step', 'stat'])
        ->orderBy('task.sort asc');
    }

	//绑定用户模型
	public function getUser(){
		return $this->hasOne(User::className(), ['id' => 'user_id'])
		->select(['id', 'email', 'nickname','language']);
	}
	
	public function getUnpack(){
	    return $this->hasOne(Unpack::className(), ['project_id' => 'id'])
	    ->andWhere(['status' => Unpack::STATUS_ENABLE])
	    ->select(['project_id', 'status', 'unpack_status', 'unpack_message', 'unpack_start_time', 'unpack_end_time', 'unpack_progress']);
	}
	
	public static function getTaskStats($projectId) {
	    
	    $_logs = ['$projectId' => $projectId];
	    
	    //查询任务信息
	    $project = Project::find()->where(['id' => $projectId])->with(['tasks'])->asArray()->limit(1)->one();
	    if (!$project)
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
	        return false;
	    }
	    
	    Work::setTable($project['table_suffix']);
	    
	    $workList = Work::find()
	    ->where(['project_id' => $projectId])
	    ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
	    ->asArray()->all();
	    
	    $workArr = [];
	    if ($workList) {
	        foreach ($workList as $work) {
	            $workArr[$work['batch_id']][$work['step_id']][] = $work;
	        }
	    }
	    
	    
	    $taskStats = [];
	    $taskUserStats = [];
	    foreach ($project['tasks'] as $task) {
	        
	        $new = 0;
	        $working = 0;
	        $submit = 0;
	        $finish = 0;
	        $refused = 0;
	        $difficult = 0;
	        $refuse = 0;
	        $refuseSubmited = 0;
	        $refuseWait = 0;
	        
	        $workingUsers = [];
	        $submitUsers = [];
	        $refusedUsers = [];
	        $difficultUsers = [];
	        $refuseUsers = [];
	        $refuseSubmitedUsers = [];
	        $refuseWaitUsers = [];
	        
	        $refuseDataIds = [];
	        $joinUserIds = [];
	        
	        if (!empty($workArr[$task['batch_id']][$task['step_id']])) {
	            foreach ($workArr[$task['batch_id']][$task['step_id']] as $work) {
	                
	                if ($work['user_id'] && !in_array($work['user_id'], $joinUserIds)) {
	                    $joinUserIds[] = $work['user_id'];
	                }
	                
	                if (empty($workingUsers[$work['user_id']])) {
	                    $workingUsers[$work['user_id']] = 0;
	                }
	                if (empty($submitUsers[$work['user_id']])) {
	                    $submitUsers[$work['user_id']] = 0;
	                }
	                if (empty($refusedUsers[$work['user_id']])) {
	                    $refusedUsers[$work['user_id']] = 0;
	                }
	                if (empty($difficultUsers[$work['user_id']])) {
	                    $difficultUsers[$work['user_id']] = 0;
	                }
	                if (empty($refuseUsers[$work['user_id']])) {
	                    $refuseUsers[$work['user_id']] = 0;
	                }
	                if (empty($refuseSubmitedUsers[$work['user_id']])) {
	                    $refuseSubmitedUsers[$work['user_id']] = 0;
	                }
	                if (empty($refuseWaitUsers[$work['user_id']])) {
	                    $refuseWaitUsers[$work['user_id']] = 0;
	                }
	                
	                
	                if (in_array($work['status'], [Work::STATUS_NEW])) {
	                    $new += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING])) {
	                    $working += 1;
	                    $workingUsers[$work['user_id']] += 1;
	                    
	                    if ($work['type'] == Work::TYPE_REFUSESUBMITREVISE){
	                        $refuseSubmited += 1;
	                        $refuseSubmitedUsers[$work['user_id']] += 1;
	                    }
	                    
	                    if ($work['type'] == Work::TYPE_REFUSEREVISE){
	                        $refused += 1;
	                        $refusedUsers[$work['user_id']] += 1;
	                    }
	                } elseif (in_array($work['status'], [Work::STATUS_SUBMITED])) {
	                    $submit += 1;
	                    $submitUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_FINISH])) {
	                    $finish += 1;
	                    $submitUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_REFUSED])){
	                    $refused += 1;
	                    $refusedUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_DIFFICULT])){
	                    $difficult += 1;
	                    $difficultUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_REFUSEDSUBMIT])){
	                    $refuse += 1;
	                    $refuseUsers[$work['user_id']] += 1;
	                    
	                    if ($work['type'] == Work::TYPE_REFUSESUBMIT){
	                        $refuseSubmited += 1;
	                        $refuseSubmitedUsers[$work['user_id']] += 1;
	                    } elseif ($work['type'] == Work::TYPE_AUDITREFUSE) {
	                        $refuseWait += 1;
	                        $refuseWaitUsers[$work['user_id']] += 1;
	                    }
	                }
	                
	                if (empty($refuseDataIds[$work['data_id']])) {
	                    $refuseDataIds[$work['data_id']] = 0;
	                }
	                if ($work['is_refused'] == Work::OPTION_REFUSED) {
	                    $refuseDataIds[$work['data_id']] = 1;
	                }
	            }
	        }
	        
	        $taskStats[$task['batch_id']][$task['step_id']] = [
	            'has_new_count' => $new,
	            'has_working_count' => $working,
	            'has_execute_count' => $new + $working,
	            'has_refuse_count' => $refuse,
	            'has_refuse_submited_count' => $refuseSubmited,
	            'has_refuse_wait_count' => $refuseWait,
	            'has_refused_count' => $refused,
	            'has_submit_count' => $submit,
	            'has_finish_count' => $finish,
	            'has_difficult_count' => $difficult,
	            'has_refused_count_byitems' => array_sum($refuseDataIds),
	            'has_submit_count_byitems' => count($refuseDataIds),
	            'has_refused_rate_byitems' => count($refuseDataIds) > 0 ? round(array_sum($refuseDataIds) / count($refuseDataIds)).'%' : 0,
	        ];
	        
	        if ($joinUserIds) {
	            foreach ($joinUserIds as $joinUserId) {
	                $taskUserStats[$task['batch_id']][$task['step_id']][$joinUserId] = [
	                    'has_execute_count' => $new + (isset($workingUsers[$joinUserId]) ? $workingUsers[$joinUserId] : 0),
	                    'has_refuse_count' => isset($refuseUsers[$joinUserId]) ? $refuseUsers[$joinUserId] : 0,
	                    'has_refuse_submited_count' => isset($refuseSubmitedUsers[$joinUserId]) ? $refuseSubmitedUsers[$joinUserId] : 0,
	                    'has_refuse_wait_count' => isset($refuseWaitUsers[$joinUserId]) ? $refuseWaitUsers[$joinUserId] : 0,
	                    'has_refused_count' => isset($refusedUsers[$joinUserId]) ? $refusedUsers[$joinUserId] : 0,
	                    'has_submit_count' => isset($submitUsers[$joinUserId]) ? $submitUsers[$joinUserId] : 0,
	                    'has_difficult_count' => isset($difficultUsers[$joinUserId]) ? $difficultUsers[$joinUserId] : 0,
	                    //'has_refused_count_byitems' => array_sum($refuseDataIds),
	                    //'has_submit_count_byitems' => count($refuseDataIds),
	                    //'has_refused_rate_byitems' => count($refuseDataIds) > 0 ? round(array_sum($refuseDataIds) / count($refuseDataIds)).'%' : 0,
	                ];
	            }
	        }
	    }
	    
	    $_logs['$taskStats'] = ArrayHelper::desc($taskStats);
	    $_logs['$taskUserStats'] = ArrayHelper::desc($taskUserStats);
	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
	    return [$taskStats, $taskUserStats];
	}
	
	public static function getTaskStat($projectId, $taskId) {
	    
	    $_logs = ['$projectId' => $projectId];
	    
	    //查询任务信息
	    $project = Project::find()->where(['id' => $projectId])->with(['tasks'])->asArray()->limit(1)->one();
	    if (!$project)
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
	        return false;
	    }
	    
	    Work::setTable($project['table_suffix']);
	    
	    $workList = Work::find()
	    ->where(['project_id' => $projectId])
	    ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
	    ->asArray()->all();
	    
	    $workArr = [];
	    if ($workList) {
	        foreach ($workList as $work) {
	            $workArr[$work['batch_id']][$work['step_id']][] = $work;
	        }
	    }
	    
	    
	    $taskStats = [];
	    $taskUserStats = [];
	    foreach ($project['tasks'] as $task) {
	        
	        $new = 0;
	        $working = 0;
	        $submit = 0;
	        $refused = 0;
	        $difficult = 0;
	        $refuse = 0;
	        $refuseSubmited = 0;
	        $refuseWait = 0;
	        
	        $workingUsers = [];
	        $submitUsers = [];
	        $refusedUsers = [];
	        $difficultUsers = [];
	        $refuseUsers = [];
	        $refuseSubmitedUsers = [];
	        $refuseWaitUsers = [];
	        
	        $refuseDataIds = [];
	        $joinUserIds = [];
	        
	        if (!empty($workArr[$task['batch_id']][$task['step_id']])) {
	            foreach ($workArr[$task['batch_id']][$task['step_id']] as $work) {
	                
	                if ($work['user_id'] && !in_array($work['user_id'], $joinUserIds)) {
	                    $joinUserIds[] = $work['user_id'];
	                }
	                
	                if (empty($workingUsers[$work['user_id']])) {
	                    $workingUsers[$work['user_id']] = 0;
	                }
	                if (empty($submitUsers[$work['user_id']])) {
	                    $submitUsers[$work['user_id']] = 0;
	                }
	                if (empty($refusedUsers[$work['user_id']])) {
	                    $refusedUsers[$work['user_id']] = 0;
	                }
	                if (empty($difficultUsers[$work['user_id']])) {
	                    $difficultUsers[$work['user_id']] = 0;
	                }
	                if (empty($refuseUsers[$work['user_id']])) {
	                    $refuseUsers[$work['user_id']] = 0;
	                }
	                if (empty($refuseSubmitedUsers[$work['user_id']])) {
	                    $refuseSubmitedUsers[$work['user_id']] = 0;
	                }
	                if (empty($refuseWaitUsers[$work['user_id']])) {
	                    $refuseWaitUsers[$work['user_id']] = 0;
	                }
	                
	                
	                if (in_array($work['status'], [Work::STATUS_NEW])) {
	                    $new += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING])) {
	                    $working += 1;
	                    $workingUsers[$work['user_id']] += 1;
	                    
	                    if ($work['type'] == Work::TYPE_REFUSESUBMITREVISE){
	                        $refuseSubmited += 1;
	                        $refuseSubmitedUsers[$work['user_id']] += 1;
	                    }
	                    
	                    if ($work['type'] == Work::TYPE_REFUSEREVISE){
	                        $refused += 1;
	                        $refusedUsers[$work['user_id']] += 1;
	                    }
	                } elseif (in_array($work['status'], [Work::STATUS_SUBMITED])) {
	                    $submit += 1;
	                    $submitUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_FINISH])) {
	                    $submit += 1;
	                    $submitUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_REFUSED])){
	                    $refused += 1;
	                    $refusedUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_DIFFICULT])){
	                    $difficult += 1;
	                    $difficultUsers[$work['user_id']] += 1;
	                } elseif (in_array($work['status'], [Work::STATUS_REFUSEDSUBMIT])){
	                    $refuse += 1;
	                    $refuseUsers[$work['user_id']] += 1;
	                    
	                    if ($work['type'] == Work::TYPE_REFUSESUBMIT){
	                        $refuseSubmited += 1;
	                        $refuseSubmitedUsers[$work['user_id']] += 1;
	                    } elseif ($work['type'] == Work::TYPE_AUDITREFUSE) {
	                        $refuseWait += 1;
	                        $refuseWaitUsers[$work['user_id']] += 1;
	                    }
	                }
	                
	                if (empty($refuseDataIds[$work['data_id']])) {
	                    $refuseDataIds[$work['data_id']] = 0;
	                }
	                if ($work['is_refused'] == Work::OPTION_REFUSED) {
	                    $refuseDataIds[$work['data_id']] = 1;
	                }
	            }
	        }
	        
	        $taskStats[$task['batch_id']][$task['step_id']] = [
	            'has_execute_count' => $new + $working,
	            'has_refuse_count' => $refuse,
	            'has_refuse_submited_count' => $refuseSubmited,
	            'has_refuse_wait_count' => $refuseWait,
	            'has_refused_count' => $refused,
	            'has_submit_count' => $submit,
	            'has_difficult_count' => $difficult,
	            'has_refused_count_byitems' => array_sum($refuseDataIds),
	            'has_submit_count_byitems' => count($refuseDataIds),
	            'has_refused_rate_byitems' => count($refuseDataIds) > 0 ? round(array_sum($refuseDataIds) / count($refuseDataIds)).'%' : 0,
	        ];
	        
	        if ($joinUserIds) {
	            foreach ($joinUserIds as $joinUserId) {
	                $taskUserStats[$task['batch_id']][$task['step_id']][$joinUserId] = [
	                    'has_execute_count' => $new + (isset($workingUsers[$joinUserId]) ? $workingUsers[$joinUserId] : 0),
	                    'has_refuse_count' => isset($refuseUsers[$joinUserId]) ? $refuseUsers[$joinUserId] : 0,
	                    'has_refuse_submited_count' => isset($refuseSubmitedUsers[$joinUserId]) ? $refuseSubmitedUsers[$joinUserId] : 0,
	                    'has_refuse_wait_count' => isset($refuseWaitUsers[$joinUserId]) ? $refuseWaitUsers[$joinUserId] : 0,
	                    'has_refused_count' => isset($refusedUsers[$joinUserId]) ? $refusedUsers[$joinUserId] : 0,
	                    'has_submit_count' => isset($submitUsers[$joinUserId]) ? $submitUsers[$joinUserId] : 0,
	                    'has_difficult_count' => isset($difficultUsers[$joinUserId]) ? $difficultUsers[$joinUserId] : 0,
	                    //'has_refused_count_byitems' => array_sum($refuseDataIds),
	                    //'has_submit_count_byitems' => count($refuseDataIds),
	                    //'has_refused_rate_byitems' => count($refuseDataIds) > 0 ? round(array_sum($refuseDataIds) / count($refuseDataIds)).'%' : 0,
	                ];
	            }
	        }
	    }
	    
	    $_logs['$taskStats'] = ArrayHelper::desc($taskStats);
	    $_logs['$taskUserStats'] = ArrayHelper::desc($taskUserStats);
	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
	    return [$taskStats, $taskUserStats];
	}
}
