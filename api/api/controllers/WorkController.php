<?php
namespace api\controllers;

use common\models\TaskUser;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\components\TaskHandler;
use common\models\Project;
use common\models\DataResult;
use common\models\Data;
use common\models\Category;
use common\models\Work;
use common\models\WorkResult;
use common\models\Task;
use common\models\User;
use common\models\Step;
use common\models\WorkRecord;
use common\models\StatResultWork;
use common\helpers\FormatHelper;
/**
 * Work controller
 */
class WorkController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            //程序监控过滤器,记录每次请求的时间和内存
            'monitor' => [
                'class' => 'common\components\ActionMonitorFilter',
            ],
            //请求方式过滤器,检查用户是否是post提交
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'downloadfile' => ['get'],
                    '*' => ['POST'],
                ],
            ],
            //accesstoken身份验证
            'authenticator' => [
                'class' => AccessTokenAuth::className(),
                'optional' => ['works', 'detail'],
            ],
            //用户行为过滤器
            'userfilter' => [
                'class' => 'common\components\ActionUserFilter',
            ],
            //rbac过滤器,判断是否有执行的权限
            'rbac' => [
                'class' => 'common\components\ActionRbacFilter',
            ],
        ];
    }

    /**
     * 获取作业列表
     */
    public function actionList()
    {
        $_logs = [];
        
        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $taskId = Yii::$app->request->post('task_id', null);
        $userId = (int)Yii::$app->request->post('user_id', 0);
        $op = Yii::$app->request->post('op', null);
        $status = (int)Yii::$app->request->post('status', 0);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $type = Yii::$app->request->post('type', null);
        $_logs['$taskId'] = $taskId;
        
        //处理参数
        ($page < 1 || $page > 1000) && $page = 1;
        ($limit < 1 || $limit > 1000) && $limit = 5;
        $offset = ($page-1)*$limit;

        //排序
        if (!in_array($orderby, ['id', 'data_id', 'start_time', 'end_time', 'created_at', 'updated_at']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        //--------------------------------------
        
        //校验参数
        if (!$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }
        
        //查询任务信息
        $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
        if (!$task)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found',  Yii::t('app', 'task_not_found')));
        }
        $projectId = $task['project_id'];
        
        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->with(['category'])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found')));
        }
        if (!in_array($project['status'], array(Project::STATUS_WORKING)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow')));
        }
        
        //---------------------------------------------
        Data::setTable($project['table_suffix']);
        DataResult::setTable($project['table_suffix']);
        Work::setTable($project['table_suffix']);
        WorkResult::setTable($project['table_suffix']);
        StatResultWork::setTable($project['table_suffix']);
        
        //--------------------------------------
        
        //是否初始化数据
        if (in_array($project['category']['type'], [Category::TYPE_LABEL, Category::TYPE_EXTERNAL]))
        {
            $count = Work::find()->where(['project_id' => $projectId, 'batch_id' => $task['batch_id'], 'step_id' => $task['step_id']])->count();
            if ($count < 1)
            {
                //实例化执行类
                $taskHandler = new TaskHandler();
                $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
                if (!$isinit)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
                }
                //获取数据
                $result = $taskHandler->initData();
            }
        }
        
        //--------------------------------------
        
        $query = Work::find()->where(['project_id' => $projectId, 'batch_id' => $task['batch_id'], 'step_id' => $task['step_id']]);
        $userId && $query->andWhere(['user_id' => $userId]);
        if (is_numeric($op) && $op == 0)
        {
            $query->andWhere(['in', 'status', [Work::STATUS_NEW]]);
        }
        elseif (is_numeric($op) && $op == 1)
        {
            $step = Step::find()->where(['id' => $task['step_id'], 'status' => Step::STATUS_NORMAL])->asArray()->limit(1)->one();
            if($step['type'] == Step::TYPE_PRODUCE)
            {
                $query->andWhere(['in', 'status', [Work::STATUS_EXECUTING]]);
            }
            else if(in_array($step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $query->andWhere(['in', 'status', [Work::STATUS_RECEIVED]]);
            }
        }
        elseif (is_numeric($op) && $op == 2)
        {
            $step = Step::find()->where(['id' => $task['step_id'], 'status' => Step::STATUS_NORMAL])->asArray()->limit(1)->one();
            if($step['type'] == Step::TYPE_PRODUCE)
            {
                $query->andWhere(['in', 'status', [Work::STATUS_SUBMITED]]);
            }
            else if(in_array($step['type'], [Step::TYPE_AUDIT, Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $dataIdsQuery = clone $query;
                if($status == Work::STATUS_TO_ACCEPT) //待验收
                {
                    $dataIdsQuery->andWhere(['in', 'status', [Work::STATUS_SUBMITED]])->joinWith(['step' => function($query){
                        $query->where(['type' => Step::TYPE_CHECK]);
                    }]);
                }
                else if($status == Work::STATUS_AUDIT_REFUSE) //已驳回
                {
                    $dataIdsQuery->andWhere(['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITREFUSE]);
                }
                else if($status == Work::STATUS_AUDIT_RESET) //已重置
                {
                    $dataIdsQuery->andWhere(['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITRESET]);
                }
                else
                {
                    $dataIdsQuery->andWhere(['or', ['and', ['in', 'status', [Work::STATUS_SUBMITED]], ['type' => Work::TYPE_AUDITALLOW]], ['and', ['status' => Work::STATUS_DELETED], ['in', 'type', [Work::TYPE_AUDITREFUSE, Work::TYPE_AUDITRESET]]]]);
                }

                //获取最后一次重置或驳回的work_id
                $dataIds = $dataIdsQuery->select(['id', 'data_id'])->orderBy(['id' => SORT_DESC])->asArray()->all();
                $dataIds = array_column($dataIds, 'data_id', 'id');
                $dataIds = array_unique($dataIds);
                $workIds = array_keys($dataIds);
                $query->andWhere(['in', 'id', $workIds]);
            }
        }
        elseif (is_numeric($op) && $op == 3)
        {
            $query->andWhere(['in', 'status', [Work::STATUS_FINISH]]);
        }
        elseif (is_numeric($op) && $op == 4)
        {
            $query->andWhere(['in', 'status', [Work::STATUS_DELETED]]);
            if($status == Work::STATUS_RESETED)
            {
                $query->andWhere(['in', 'type', [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]]);
            }
            else if($status == Work::STATUS_GIVE_UP)
            {
                $query->andWhere(['type' => Work::TYPE_GIVEUP]);
            }
            else if($status == Work::STATUS_TIME_OUT)
            {
                $query->andWhere(['type' => Work::TYPE_TIMEOUT]);
            }
            else
            {
                $query->andWhere(['in', 'type', [Work::TYPE_GIVEUP, Work::TYPE_TIMEOUT, Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]]);
            }
        }
        elseif (is_numeric($op) && $op == 6)
        {
            $query->andWhere(['in', 'status', [Work::STATUS_REFUSED]]);
            $refused_count = $query->count();
            
        }
        elseif (is_numeric($op) && $op == 7)
        {
            $query->andWhere(['in', 'status', [Work::STATUS_DIFFICULT]]);
            $difficult_count = $query->count();
            
        }
        //返工作业
        elseif (is_numeric($op) && $op == 8)
        {
            //20190706已调整为只有Work::STATUS_REFUSEDSUBMIT, 去除STATUS_DELETED的情况, 此处看情况调整
            $query_ = clone $query;
            $dataIds_ = $query_->andWhere([
                'or',
                ['status' => Work::STATUS_REFUSEDSUBMIT],
                // ['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITREFUSE]
            ])->select(['data_id'])->groupBy('data_id')->asArray()->column();
            
            $workIds_ = [];
            if ($dataIds_)
            {
                foreach ($dataIds_ as $dataId_)
                {
                    $query_ = clone $query;
                    $work_ = $query_->select(['id', 'status', 'type'])->andWhere(['data_id' => $dataId_])->orderBy(['id' => SORT_DESC])->limit(1)->asArray()->one();
                    /*if ($work_['status'] == Work::STATUS_DELETED && $work_['type'] == Work::TYPE_AUDITREFUSE)
                    {
                        $workIds_[] = $work_['id'];
                    }
                    else*/if (in_array($work_['status'], [Work::STATUS_REFUSEDSUBMIT]))
                    {
                        $workIds_[] = $work_['id'];
                    }
                }
            }
            $query->andWhere(['in', 'id', $workIds_]);
            $refuseSubmit_count = count($workIds_);

        }
        else 
        {
            $query->andWhere(['or', 
                ['not', ['status' => Work::STATUS_DELETED]], 
                ['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITREFUSE]
            ]);
        }
        
        if ($keyword)
        {
            $dataIds = Data::find()->select(['id'])->where(['project_id' => $projectId])->andWhere([
                'or',
                ['like', 'id', $keyword],
                ['like', 'name', $keyword]
            ])->asArray()->column();
            
            $query->andWhere(['in', 'data_id', $dataIds]);
        }
        if($type !== null)
        {
            $types_ = FormatHelper::param_int_to_array($type);
            if ($types_)
            {
                $query->andWhere(['in', 'type', $types_]);
            }
        }
        
		$count = $query->count();
		$list = [];
		if ($count > 0)
		{
		    $list = $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
		    ->limit($limit)->offset($offset)->with(['workResult', 'data', 'dataResult', 'user', 'step'])->asArray()->all();
		}

        // $workIds = array_column($list, 'id');
        // $statResultWork = StatResultWork::fetchData(['project_id' => $projectId, 'work_id' => $workIds]);
        // if($statResultWork['error'])
        // {
        //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat result work error '.json_encode($_logs));
        //     return $this->asJson(FormatHelper::resultStrongType('', $statResultWork['error'], $statResultWork['message']));
        // }
        // $statResultWork = $statResultWork['data'];

        // $labelCount = [];
        // foreach($statResultWork as $workId => $statResult)
        // {
        //     $labelCount[$workId] = 0;
        //     foreach($statResult as $type => $result)
        //     {
        //         $labelCount[$workId] += $result['effective_count'];
        //     }
        // }

        foreach($list as $index => $work)
        {
            // $pointTypeName = StatResultWork::getLabelName(StatResultWork::TYPE_POINT_COUNT);
            // $list[$index]['label_count'] = isset($labelCount[$work['id']]) ? $labelCount[$work['id']] : 0;
            // $list[$index]['point_count'] = isset($statResultWork[$work['id']][$pointTypeName]['effective_count']) ? $statResultWork[$work['id']][$pointTypeName]['effective_count'] : 0;
            if($work['status'] == Work::STATUS_DELETED)
            {
                if(in_array($work['type'], [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET])) //处理未通过状态
                {
                    $list[$index]['status'] = Work::STATUS_RESETED;
                }
                else if($work['type'] == Work::TYPE_GIVEUP) //处理已放弃状态
                {
                    $list[$index]['status'] = Work::STATUS_GIVE_UP;
                }
                else if($work['type'] == Work::TYPE_TIMEOUT) //处理已超时状态
                {
                    $list[$index]['status'] = Work::STATUS_TIME_OUT;
                }
                else if($work['type'] == Work::TYPE_AUDITREFUSE)
                {
                    $list[$index]['status'] = Work::STATUS_AUDIT_REFUSE;
                }
                else if($work['type'] == Work::TYPE_AUDITRESET)
                {
                    $list[$index]['status'] = Work::STATUS_AUDIT_RESET;
                }
            }
            else if($work['status'] == Work::STATUS_SUBMITED)
            {
//                 if($work['type'] == Work::TYPE_AUDITALLOW)
//                 {
                    if(isset($work['step']['type']) && $work['step']['type'] == Step::TYPE_PRODUCE)
                    {
                        $list[$index]['status'] = Work::STATUS_TO_AUDIT;
                    }
                    else if(isset($work['step']['type']) && $work['step']['type'] == Step::TYPE_AUDIT)
                    {
                        $list[$index]['status'] = Work::STATUS_TO_ACCEPT;
                    }
                    else if(isset($work['step']['type']) && $work['step']['type'] == Step::TYPE_ACCEPTANCE)
                    {
                        $list[$index]['status'] = Work::STATUS_ACCEPTANCE_FINISH;
                    }
//                 }
                // else if($work['type'] == Work::TYPE_AUDITREFUSE)
                // {
                //     $list[$index]['status'] = Work::STATUS_AUDIT_REFUSE;
                // }
                // else if($work['type'] == Work::TYPE_AUDITRESET)
                // {
                //     $list[$index]['status'] = Work::STATUS_AUDIT_RESET;
                // }
                // else if($work['type'] == Work::TYPE_AUDITREFUSED)
                // {
                //     $list[$index]['status'] = Work::STATUS_TO_REAUDIT;
                // }
            }
            else if($work['status'] == Work::STATUS_EXECUTING)
            {
                if($work['step']['type'] == Step::TYPE_PRODUCE)
                {
                    $list[$index]['status'] = Work::STATUS_PRODUCE_EXECUTING;
                }
            }
            else if($work['status'] == Work::STATUS_RECEIVED)
            {
                if($work['step']['type'] == Step::TYPE_AUDIT) //审核领取就是审核中
                {
                    $list[$index]['status'] = Work::STATUS_AUDIT_EXECUTING;
                }
                else if($work['step']['type'] == Step::TYPE_ACCEPTANCE)
                {
                    $list[$index]['status'] = Work::STATUS_ACCEPTANCE_EXECUTING;
                }
            }
        }

		//获取任务操作员
        $userIds = TaskUser::find()->select(['user_id'])->where(['task_id' => $taskId])->asArray()->column();
        $users = [];
		if($userIds)
		{
            $userIds = array_unique($userIds);
            $users = User::find()->select(['id', 'email', 'nickname'])->where(['in', 'id', $userIds])->asArray()->all();
        }


        $responseData = [
            'count' => $count,
            'list' => $list,
            'statuses' => Work::getListStatuses(),
            'invalid_statuses' => Work::getInvalidStatuses(),
            'audit_statuses' => Work::getAuditStatuses(),
            'types' => Work::getDeletedTypes(),
            'users' => $users,
            'time' => time()
        ];
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionRecords()
    {
        $_logs = [];
        
        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 99);//一次性返回
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $projectId = Yii::$app->request->post('project_id', 0);
        $taskId = Yii::$app->request->post('task_id', 0);
        $dataId = (int)Yii::$app->request->post('data_id', 0);
        $orderby = 'id';
        $sort = 'desc';
        
        //处理参数
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 5;
        $offset = ($page-1)*$limit;
        
        //--------------------------------------
        
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
        
        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->with(['category'])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found')));
        }
        if (!in_array($project['status'], array(Project::STATUS_WORKING)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow')));
        }
        
        //---------------------------------------------
        //Data::setTable($project['table_suffix']);
        //DataResult::setTable($project['table_suffix']);
        //Work::setTable($project['table_suffix']);
        //WorkResult::setTable($project['table_suffix']);
        WorkRecord::setTable($project['table_suffix']);
        
        //--------------------------------------
        
        $query = WorkRecord::find()->where(['project_id' => $projectId]);
        if ($dataId)
        {
            $query->andWhere(['data_id' => $dataId]);
        }
//         if ($taskId)
//         {
//             $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
//             if (!$task)
//             {
//                 Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
//                 return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
//             }
//             $query->andWhere(['batch_id' => $task['batch_id'], 'step_id' => $task['step_id']]);
//         }
        
        $count = $query->count();
        $list = [];
        if ($count > 0)
        {
            $list = $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
            ->limit($limit)->offset($offset)->asArray()->with(['beforeUser', 'afterUser', 'step'])->all();
        }
        
        $responseData = [
            'count' => $count,
            'list' => $list,
            'types' => WorkRecord::getTypes(),
            'step_types' => Step::getTypes(),
            'work_status' => Work::getStatuses()
        ];
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

}