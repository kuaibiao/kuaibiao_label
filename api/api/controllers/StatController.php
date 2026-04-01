<?php
namespace api\controllers;
use common\models\Setting;
use common\models\Data;
use common\models\Step;
use common\models\Work;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\models\Project;
use common\models\Stat;
use common\models\StatUser;
use common\models\StatResult;
use common\models\StatResultUser;
use common\models\StatResultWork;
use common\models\Task;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\SecurityHelper;
use common\helpers\StringHelper;

/**
 * Work controller
 */
class StatController extends Controller
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
                'optional' => ['worklist', 'workdetail'],
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
     * 统计列表
     * @return \yii\web\Response
     */
    public function actionList()
    {
        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 99);
        $keyword = Yii::$app->request->post('keyword', null);
        $status = Yii::$app->request->post('status', null);
        $orderby = Yii::$app->request->post('orderby', '');
        $sort = Yii::$app->request->post('sort', null);
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 99;
        $offset = ($page-1)*$limit;
         
        //校验参数
        if ($status !== null)
        {
            if (!Project::getStatus($status))
            {
                $status == null;
            }
        }
        if (!in_array($orderby, ['id', 'user_id']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
         
        $query = Stat::find()->where(['project_id' => $projectId]);
        if ($keyword)
        {
            $keyword = trim($keyword);
            $query->andFilterWhere([
                'or',
                ['like', 'id', $keyword],
            ]);
        }
         
        //执行查询
        $count = $query->count();
        $list = [];
        if ($count > 0)
        {
            $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
            $list = $query->offset($offset)->limit($limit)->asArray()->all();
        }
        
        $responseData = [
            'keyword' => $keyword,
            'status' => $status,
            'orderby' => $orderby,
            'sort' => $sort,
            'list' => $list,
			'count' => $count,
        ];
         
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    // public function actionTask()
    // {
    //     //处理分页
    //     $page = (int)Yii::$app->request->post('page', 1);
    //     $limit = (int)Yii::$app->request->post('limit', 99);
    //     $keyword = Yii::$app->request->post('keyword', null);
    //     $orderby = Yii::$app->request->post('orderby', '');
    //     $sort = Yii::$app->request->post('sort', null);
    //     $taskId = (int)Yii::$app->request->post('task_id', 0);
        
    //     $page < 1 && $page = 1;
    //     $limit < 1 && $limit = 99;
    //     $offset = ($page-1)*$limit;
        
    //     //校验参数
    //     if (!in_array($orderby, ['id', 'task_id', 'work_time', 'work_count', 'join_count', 'label_count', 'point_count','allowed_count', 'refused_count', 'reseted_count']))
    //     {
    //         $orderby = 'id';
    //     }
    //     if (!in_array($sort, ['asc', 'desc']))
    //     {
    //         $sort = 'desc';
    //     }
        
    //     $query = StatUser::find()->andWhere(['task_id' => $taskId])->andWhere(['not', ['user_id' => 0]]);
    //     if ($keyword)
    //     {
    //         $keyword = trim($keyword);
    //         $userIds = User::find()->select(['id'])->where([
    //             'or',
    //             ['like', 'email', $keyword],
    //             ['like', 'nickname', $keyword],
    //             ['like', 'id', $keyword],
    //         ])->andWhere(['status' => User::STATUS_ACTIVE])->asArray()->column();
    //         $query->andFilterWhere(['in', 'user_id', $userIds]);
    //     }
        
    //     //执行查询
    //     $count = $query->count();
    //     $list = [];
    //     $total = [];
    //     if ($count > 0)
    //     {
    //         $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
    //         $list = $query->offset($offset)->limit($limit)->with(['user'])->asArray()->all();
    //     }
        
    //     $responseData = [
    //         'count' => $count,
    //         'list' => $list,
    //         'roles' => AuthItem::getRoles()
    //     ];
        
    //     return $this->asJson(FormatHelper::resultStrongType($responseData));
    // }

    /**
     * 获取用户在每个任务的绩效列表
     *
     * @return \yii\web\Response
     */
    public function actionTask()
    {
        $_logs = ['$postData' => Yii::$app->request->post()];
        
        //处理分页
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 99);
        $keyword = Yii::$app->request->post('keyword', null);
        $orderby = Yii::$app->request->post('orderby', '');
        $sort = Yii::$app->request->post('sort', null);
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $stepId = (int)Yii::$app->request->post('step_id', 0);
        $taskId = (int)Yii::$app->request->post('task_id', 0);
        
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 99;
        $offset = ($page-1)*$limit;
    
        //校验参数
        if (!in_array($orderby, ['id', 'task_id', 'work_time', 'work_count', 'join_count','allowed_count', 'refused_count', 'reseted_count']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

        if(empty($projectId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat project_id not given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_project_id_not_given', Yii::t('app', 'stat_project_id_not_given')));
        }
        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if(empty($project))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat user project not found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_project_not_found', Yii::t('app', 'stat_project_not_found')));
        }

        //----------------------------------------------
        
        //$query = StatUser::find()->andWhere(['not', ['user_id' => 0]]);
        $query = Stat::find();
        $stepId && $query->andWhere(['step_id' => $stepId]);
        $taskId && $query->andWhere(['task_id' => $taskId]);
        if ($keyword)
        {
            $keyword = trim($keyword);
            
            $query_ = Task::find()
            ->select(['id'])
            ->where(['not', ['status' => Task::STATUS_DELETED]])
            ->andWhere(['like', 'name', $keyword]);
            $stepId && $query_->andWhere(['step_id' => $stepId]);
            $taskId && $query_->andWhere(['id' => $taskId]);
            $taskIds_ = $query_->asArray()->column();
            
            $conditions = ['or',
                ['like', 'project_id', $keyword],
                // ['like', 'batch_id', $keyword],
                ['like', 'step_id', $keyword],
                ['like', 'task_id', $keyword],
                ['like', 'user_id', $keyword],
                ['in', 'task_id', $taskIds_],
            ];
            
            $query->andWhere($conditions);
        }
    
        //执行查询
        $count = $query->count();
        $list = [];
        $statResult = [];
        if ($count > 0)
        {
            $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
            $list = $query->offset($offset)->limit($limit)->with(['task', 'project', 'batch'])->asArray()->all();

            StatResult::setTable($project['table_suffix']);
            $params = ['project_id' => $projectId];
            $taskId && $params['task_id'] = $taskId;
            $statResult = StatResult::fetchData($params);
            if($statResult['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat result error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $statResult['error'], $statResult['message']));
            }
            $statResult = $statResult['data'];
            $templateLabelTypes = Stat::getLabelsByTemplate($project['template_id']);

            foreach($list as $key => $val)
            {
                $allowed_count = $val['allowed_count'];
                $refused_count = $val['refused_count'];
                $reseted_count = $val['reseted_count'];
                $other_count = $val['other_operated_count'];
                $accuracy = 0;
                $accuracy_total = $allowed_count + $refused_count + $reseted_count+$other_count;
                if ($accuracy_total > 0) {
                    $accuracy = round($allowed_count / $accuracy_total, 4) * 100;
                    if ($accuracy > 100) {
                        $accuracy = 100;
                    } elseif ($accuracy < 0) {
                        $accuracy = 0;
                    }
                }

                $list[$key]['accuracy'] = $accuracy;

                $list[$key]['project_label_stat'] = [];
                if(isset($statResult[$val['task']['id']]))
                {
                    $list[$key]['project_label_stat'] = $statResult[$val['task']['id']];
                }
                $list[$key]['invalid_data_effective_count'] = isset($statResult[$val['task']['id']]['label_no']['effective_count']) ? $statResult[$val['task']['id']]['label_no']['effective_count'] : 0;
            }
        }
        
        //查询统计数
        $queryTotal = StatUser::find();
        $projectId && $queryTotal->andWhere(['project_id' => $projectId]);
        $stepId && $queryTotal->andWhere(['step_id' => $stepId]);
        $taskId && $queryTotal->andWhere(['task_id' => $taskId]);
        $queryTotal->select([
            'count(user_id) as users',
            'sum(work_time) as work_time',
            'sum(work_count) as work_count',
            'sum(submit_count) as submit_count',
            'sum(timeout_count) as timeout_count',
            'sum(audited_count) as audited_count',
            'sum(allowed_count) as allowed_count',
            'sum(refuse_count) as refuse_count',
            'sum(refused_count) as refused_count',
            'sum(reset_count) as reset_count',
            'sum(reseted_count) as reseted_count',
            'sum(other_operated_count) as other_operated_count',
        ]);
        $groupby = '';
        $stepId && $groupby = 'step_id';
        $taskId && $groupby = 'task_id';

        $total = [];
        if ($groupby)
        {
            $total = $queryTotal->groupBy([$groupby])->asArray()->limit(1)->one();
            if(!empty($total))
            {
                $total['pass_rate'] = $total['audited_count'] > 0 ? $total['allowed_count'] / $total['audited_count'] * 100 : 0;
                $total['pass_rate'] = $total['pass_rate'] > 0 ? round($total['pass_rate'], 2) : '0.00';
                $total['invalid_data_effective_count'] = 0;
                $total['invalid_data_allowed_count'] = 0;
                $total['invalid_data_toaudit_count'] = 0;
                // $total['invalid_data_delete_count'] = 0;
                $total['valid_data_effective_count'] = 0;
                $total['valid_data_allowed_count'] = 0;
                $total['valid_data_toaudit_count'] = 0;
                // $total['valid_data_delete_count'] = 0;

                $labelNames = StatResult::getLabelNames();
                foreach($statResult as $statTaskId => $typeStat)
                {
                    if(isset($typeStat[$labelNames[StatResult::TYPE_LABEL_NO_COUNT]]))
                    {
                        //无效数据总数
                        $total['invalid_data_effective_count'] += $typeStat[$labelNames[StatResult::TYPE_LABEL_NO_COUNT]]['effective_count'];
                        $total['invalid_data_effective_count'] = $total['invalid_data_effective_count'] > 0 ? $total['invalid_data_effective_count'] : 0;
                        //待审核无效数据
                        $total['invalid_data_toaudit_count'] += $typeStat[$labelNames[StatResult::TYPE_LABEL_NO_COUNT]]['toaudit_count'];
                        $total['invalid_data_toaudit_count'] = $total['invalid_data_toaudit_count'] > 0 ? $total['invalid_data_toaudit_count'] : 0;
                        //已通过无效数据
                        $total['invalid_data_allowed_count'] += $typeStat[$labelNames[StatResult::TYPE_LABEL_NO_COUNT]]['allowed_count'];
                    }
                    if(isset($typeStat[$labelNames[StatResult::TYPE_LABEL_YES_COUNT]]))
                    {
                        //有效数据总数
                        $total['valid_data_effective_count'] += $typeStat[$labelNames[StatResult::TYPE_LABEL_YES_COUNT]]['effective_count'];
                        $total['valid_data_effective_count'] = $total['valid_data_effective_count'] > 0 ? $total['valid_data_effective_count'] : 0;
                        //待审核有效数据
                        $total['valid_data_toaudit_count'] += $typeStat[$labelNames[StatResult::TYPE_LABEL_YES_COUNT]]['toaudit_count'];
                        $total['valid_data_toaudit_count'] = $total['valid_data_toaudit_count'] > 0 ? $total['valid_data_toaudit_count'] : 0;
                        //已通过有效数据
                        $total['valid_data_allowed_count'] += $typeStat[$labelNames[StatResult::TYPE_LABEL_YES_COUNT]]['allowed_count'];
                    }
                }
            }
            else
            {
                $total = (object)[];
            }
        }

        $responseData = [
            'count' => $count,
            'list' => $list,
            'total' => $total
        ];
        // if($taskId) //此时统计这个用户的绩效
        // {
        //     $responseData['label_stat'] = empty($statResult[$taskId]) ? [] : $statResult[$taskId];
        // }
        // else
        // {
        //     $responseData['label_stat'] = $statResult;
        // }
        $responseData['template_label_types'] = isset($templateLabelTypes) ? $templateLabelTypes : [];
        $responseData['label_types'] = array_combine(StatResult::getLabelNames(), StatResult::getTypes()); //模板标注工具和标注的对应翻译

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    /**
     * 获取用户在每个任务的绩效列表
     *
     * @return \yii\web\Response
     */
    public function actionUser()
    {
        $_logs = ['$postData' => Yii::$app->request->post()];
        
        //处理分页
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 99);
        $keyword = Yii::$app->request->post('keyword', null);
        $orderby = Yii::$app->request->post('orderby', '');
        $sort = Yii::$app->request->post('sort', null);
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $stepId = (int)Yii::$app->request->post('step_id', 0);
        $taskId = (int)Yii::$app->request->post('task_id', 0);
        $userId = (int)Yii::$app->request->post('user_id', 0);
        
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 99;
        $offset = ($page-1)*$limit;
    
        //校验参数
        if (!in_array($orderby, ['id', 'task_id', 'work_time', 'work_count', 'join_count','allowed_count', 'refused_count', 'reseted_count']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

        if(empty($projectId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat project_id not given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_project_id_not_given', Yii::t('app', 'stat_project_id_not_given')));
        }
        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if(empty($project))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat user project not found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_project_not_found', Yii::t('app', 'stat_project_not_found')));
        }

        //----------------------------------------------
        
        //$query = StatUser::find()->andWhere(['not', ['user_id' => 0]]);
        $query = StatUser::find();
        $stepId && $query->andWhere(['step_id' => $stepId]);
        $taskId && $query->andWhere(['task_id' => $taskId]);
        $userId && $query->andWhere(['user_id' => $userId]);
        if ($keyword)
        {
            $keyword = trim($keyword);
            
            $query_ = Task::find()
            ->select(['id'])
            ->where(['not', ['status' => Task::STATUS_DELETED]])
            ->andWhere(['like', 'name', $keyword]);
            $stepId && $query_->andWhere(['step_id' => $stepId]);
            $taskId && $query_->andWhere(['id' => $taskId]);
            $taskIds_ = $query_->asArray()->column();
            
            $conditions = ['or',
                ['like', 'project_id', $keyword],
                // ['like', 'batch_id', $keyword],
                ['like', 'step_id', $keyword],
                ['like', 'task_id', $keyword],
                ['like', 'user_id', $keyword],
                ['in', 'task_id', $taskIds_],
            ];
            
            $query->andWhere($conditions);
        }
    
        //执行查询
        $count = $query->count();
        $list = [];
        $statUserResult = [];
        if ($count > 0)
        {
            $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
            $list = $query->offset($offset)->limit($limit)->with(['task', 'user', 'project'])->asArray()->all();

            StatResultUser::setTable($project['table_suffix']);
            $params = ['project_id' => $projectId];
            $taskId && $params['task_id'] = $taskId;
            $userId && $params['user_id'] = $userId;
            $statUserResult = StatResultUser::fetchData($params);
            if($statUserResult['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat user result error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $statUserResult['error'], $statUserResult['message']));
            }
            $statUserResult = $statUserResult['data'];
            $templateLabelTypes = Stat::getLabelsByTemplate($project['template_id']);

            foreach($list as $key => $val)
            {
                $allowed_count = $val['allowed_count'];
                $refused_count = $val['refused_count'];
                $reseted_count = $val['reseted_count'];
                $other_count = $val['other_operated_count'];
                $accuracy = 0;
                $accuracy_total = $allowed_count + $refused_count + $reseted_count+$other_count;
                if ($accuracy_total > 0) {
                    $accuracy = round($allowed_count / $accuracy_total, 4) * 100;
                    if ($accuracy > 100) {
                        $accuracy = 100;
                    } elseif ($accuracy < 0) {
                        $accuracy = 0;
                    }
                }

                $list[$key]['accuracy'] = $accuracy;

                if(isset($statUserResult[$val['user']['id']]))
                {
                    $list[$key]['project_label_stat'] = $statUserResult[$val['user']['id']];
                }
            }
        }
        
        //查询统计数
        $queryTotal = StatUser::find();
        $projectId && $queryTotal->andWhere(['project_id' => $projectId]);
        $stepId && $queryTotal->andWhere(['step_id' => $stepId]);
        $taskId && $queryTotal->andWhere(['task_id' => $taskId]);
        $userId && $queryTotal->andWhere(['user_id' => $userId]);
        $queryTotal->select([
            'count(user_id) as users',
            'sum(work_time) as work_time',
            'sum(work_count) as work_count',
            'sum(submit_count) as submit_count',
            'sum(timeout_count) as timeout_count',
            'sum(audited_count) as audited_count',
            'sum(allowed_count) as allowed_count',
            'sum(refuse_count) as refuse_count',
            'sum(refused_count) as refused_count',
            'sum(reset_count) as reset_count',
            'sum(reseted_count) as reseted_count',
            'sum(other_operated_count) as other_operated_count',
        ]);
        $groupby = '';
        $stepId && $groupby = 'step_id';
        $taskId && $groupby = 'task_id';
        $userId && $groupby = 'user_id';

        $total = [];
        if ($groupby)
        {
            $total = $queryTotal->groupBy([$groupby])->asArray()->limit(1)->one();
            if(!empty($total))
            {
                $total['pass_rate'] = $total['audited_count'] > 0 ? $total['allowed_count'] / $total['audited_count'] * 100 : 0;
                $total['pass_rate'] = $total['pass_rate'] > 0 ? round($total['pass_rate'], 2) : '0.00';
                $total['invalid_data_effective_count'] = 0;
                $total['invalid_data_allowed_count'] = 0;
                $total['invalid_data_toaudit_count'] = 0;
                // $total['invalid_data_delete_count'] = 0;
                $total['valid_data_effective_count'] = 0;
                $total['valid_data_allowed_count'] = 0;
                $total['valid_data_toaudit_count'] = 0;
                // $total['valid_data_delete_count'] = 0;

                $labelNames = StatResultUser::getLabelNames();
                foreach($statUserResult as $statUserId => $typeStat)
                {
                    if(isset($typeStat[$labelNames[StatResultUser::TYPE_LABEL_NO_COUNT]]))
                    {
                        //无效数据总数
                        $total['invalid_data_effective_count'] += $typeStat[$labelNames[StatResultUser::TYPE_LABEL_NO_COUNT]]['effective_count'];
                        $total['invalid_data_effective_count'] = $total['invalid_data_effective_count'] > 0 ? $total['invalid_data_effective_count'] : 0;
                        //待审核无效数据
                        $total['invalid_data_toaudit_count'] += $typeStat[$labelNames[StatResultUser::TYPE_LABEL_NO_COUNT]]['toaudit_count'];
                        $total['invalid_data_toaudit_count'] = $total['invalid_data_toaudit_count'] > 0 ? $total['invalid_data_toaudit_count'] : 0;
                        //已通过无效数据
                        $total['invalid_data_allowed_count'] += $typeStat[$labelNames[StatResultUser::TYPE_LABEL_NO_COUNT]]['allowed_count'];
                    }
                    if(isset($typeStat[$labelNames[StatResultUser::TYPE_LABEL_YES_COUNT]]))
                    {
                        //有效数据总数
                        $total['valid_data_effective_count'] += $typeStat[$labelNames[StatResultUser::TYPE_LABEL_YES_COUNT]]['effective_count'];
                        $total['valid_data_effective_count'] = $total['valid_data_effective_count'] > 0 ? $total['valid_data_effective_count'] : 0;
                        //待审核有效数据
                        $total['valid_data_toaudit_count'] += $typeStat[$labelNames[StatResultUser::TYPE_LABEL_YES_COUNT]]['toaudit_count'];
                        $total['valid_data_toaudit_count'] = $total['valid_data_toaudit_count'] > 0 ? $total['valid_data_toaudit_count'] : 0;
                        //已通过有效数据
                        $total['valid_data_allowed_count'] += $typeStat[$labelNames[StatResultUser::TYPE_LABEL_YES_COUNT]]['allowed_count'];
                    }
                }
            }
            else
            {
                $total = (object)[];
            }
        }

        $responseData = [
            'count' => $count,
            'list' => $list,
            'total' => $total
        ];
        if($userId) //此时统计这个用户的绩效
        {
            $responseData['label_stat'] = empty($statUserResult[$userId]) ? [] : $statUserResult[$userId];
        }
        else
        {
            $responseData['label_stat'] = $statUserResult;
        }
        $responseData['template_label_types'] = isset($templateLabelTypes) ? $templateLabelTypes : [];
        $responseData['label_types'] = array_combine(StatResultUser::getLabelNames(), StatResultUser::getTypes()); //模板标注工具和标注的对应翻译

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

    public function actionUserStatList()
    {
        $_logs = [];
        
        //处理分页
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 99);
        $keyword = Yii::$app->request->post('keyword', null);
        $orderby = Yii::$app->request->post('orderby', '');
        $sort = Yii::$app->request->post('sort', null);
        $stepId = (int)Yii::$app->request->post('step_id', 0);
        $taskId = (int)Yii::$app->request->post('task_id', 0);
        $userId = (int)Yii::$app->request->post('user_id', 0);
        
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 99;
        $offset = ($page-1)*$limit;
    
        //校验参数
        if (!in_array($orderby, ['id', 'task_id', 'work_time', 'work_count', 'join_count', 'allowed_count', 'refused_count', 'reseted_count']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        //----------------------------------------------

        $query = StatUser::find();
        $stepId && $query->andWhere(['step_id' => $stepId]);
        $taskId && $query->andWhere(['task_id' => $taskId]);
        $userId && $query->andWhere(['user_id' => $userId]);
        
        if ($keyword)
        {
            $keyword = trim($keyword);
            
            $query_ = Task::find()
            ->select(['id'])
            ->where(['not', ['status' => Task::STATUS_DELETED]])
            ->andWhere(['like', 'name', $keyword]);
            
            $stepId && $query_->andWhere(['step_id' => $stepId]);
            $taskId && $query_->andWhere(['id' => $taskId]);
            $taskIds_ = $query_->asArray()->column();
            
            $conditions = ['or',
                ['like', 'project_id', $keyword],
                ['like', 'batch_id', $keyword],
                ['like', 'step_id', $keyword],
                ['like', 'task_id', $keyword],
                ['like', 'user_id', $keyword],
                ['in', 'task_id', $taskIds_],
            ];
            
            $query->andWhere($conditions);
        }
    
        //执行查询
        $count = $query->count();
        $list = [];
        if ($count > 0)
        {
            $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
            $list = $query->offset($offset)->limit($limit)->with(['task', 'user', 'project'])->asArray()->all();

            foreach($list as $key => $val)
            {
                $allowed_count = $val['allowed_count'];
                $refused_count = $val['refused_count'];
                $reseted_count = $val['reseted_count'];
                $other_count = $val['other_operated_count'];
                $accuracy = 0;
                $accuracy_total = $allowed_count + $refused_count + $reseted_count+$other_count;
                if ($accuracy_total > 0) {
                    $accuracy = round($allowed_count / $accuracy_total, 4) * 100;
                    if ($accuracy > 100) {
                        $accuracy = 100;
                    } elseif ($accuracy < 0) {
                        $accuracy = 0;
                    }
                }
                $list[$key]['accuracy'] = $accuracy;

                $project = Project::find()->where(['id' => $val['project_id']])->asArray()->limit(1)->one();
                if(empty($project))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat user project not found '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'stat_project_not_found', Yii::t('app', 'stat_project_not_found')));
                }
                StatResultUser::setTable($project['table_suffix']);

                $statResultUser = StatResultUser::fetchData(['project_id' => $val['project_id'], 'task_id' => $val['task_id'], 'user_id' => $val['user_id']]);
                if(!empty($statResultUser['error']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat result user error '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', $statResultUser['error'], $statResultUser['message']));
                }
                $statResultUser = $statResultUser['data'];
                $list[$key]['label_count'] = 0;
                if(!empty($statResultUser[$val['user_id']]))
                {
                    foreach($statResultUser[$val['user_id']] as $type => $stat)
                    {
                        $list[$key]['label_count'] += $stat['effective_count'];
                    } 
                }
            }
        }
        
        $responseData = [
            'count' => $count,
            'list' => $list
        ];

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionWork()
    {
        $_logs = ['$postData' => Yii::$app->request->post()];
        
        //处理分页
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 99);
        $orderby = Yii::$app->request->post('orderby', '');
        $sort = Yii::$app->request->post('sort', null);
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $keyword = Yii::$app->request->post('keyword', null);
        $workStatus = Yii::$app->request->post('status', 0);
        $workStartTime = Yii::$app->request->post('work_start_time', '');
        $workEndTime = Yii::$app->request->post('work_end_time', '');
        $updateStartTime = Yii::$app->request->post('update_start_time', '');
        $updateEndTime = Yii::$app->request->post('update_end_time', '');
        $userId = (int)Yii::$app->request->post('user_id', 0);
        $taskId = (int)Yii::$app->request->post('task_id', 0);

        if(empty($projectId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat project_id not given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_project_id_not_given', Yii::t('app', 'stat_project_id_not_given')));
        }
        if(empty($taskId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat task_id not given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_task_id_not_given', Yii::t('app', 'stat_task_id_not_given')));
        }
        
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 99;
        $offset = ($page-1)*$limit;

        //校验参数
        if (!in_array($orderby, ['id', 'work_time']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

        //----------------------------------------------

        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if(empty($project))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat project not found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_project_not_found', Yii::t('app', 'stat_project_not_found')));
        }
        Work::setTable($project['table_suffix']);
        StatResultWork::setTable($project['table_suffix']);

        $task = Task::find()->where(['id' => $taskId])->andWhere(['!=', 'status', Task::STATUS_DELETED])->with(['step'])->asArray()->limit(1)->one();
        if(empty($task))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat task not found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_task_not_found', Yii::t('app', 'stat_task_not_found')));
        }

        $query = Work::find()->where(['project_id' => $projectId]);
        if(isset($task['step']['id']))
        {
            $query->andWhere(['step_id' => $task['step']['id']]);
        }
        $userId && $query->andWhere(['user_id' => $userId]);
        if($workStatus)
        {
            $workStatuses = explode(',', trim($workStatus, ','));
            $specialStatuses = [Work::STATUS_RESETED, Work::STATUS_AUDIT_REFUSE, Work::STATUS_AUDIT_RESET];
            $specialStatuses = array_intersect($specialStatuses, $workStatuses);
            if($specialStatuses)
            {
                if(in_array(Work::STATUS_RESETED, $specialStatuses)) //处理未通过（被重置）状态
                {
                    $statuses = array_diff($workStatuses, $specialStatuses); //正常状态
                    if($statuses)
                    {
                        $query->andWhere(['or', ['and', ['status' => Work::STATUS_DELETED], ['in', 'type', [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]]], ['in', 'status', $statuses]]);
                    }
                    else
                    {
                        $query->andWhere(['status' => Work::STATUS_DELETED])->andWhere(['in', 'type', [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]]);
                    }
                }
                if(in_array(Work::STATUS_AUDIT_REFUSE, $specialStatuses))
                {
                    $statuses = array_diff($workStatuses, $specialStatuses);
                    if($statuses)
                    {
                        $query->andWhere(['or', ['in', 'status', $statuses], ['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITREFUSE]]);
                    }
                    else
                    {
                        $query->andWhere(['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITREFUSE]);
                    }
                }
                if(in_array(Work::STATUS_AUDIT_RESET, $specialStatuses))
                {
                    $statuses = array_diff($workStatuses, $specialStatuses);
                    if($statuses)
                    {
                        $query->andWhere(['or', ['in', 'status', $statuses], ['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITRESET]]);
                    }
                    else
                    {
                        $query->andWhere(['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITRESET]);
                    }
                }
            }
            else
            {
                $query->andWhere(['in', 'status', $workStatuses]);
            }
        }
        else
        {
            if(isset($task['step']))
            {
                if($task['step']['type'] == Step::TYPE_PRODUCE)
                {
                    $query->andWhere(['or', ['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH, Work::STATUS_REFUSED]], ['and', ['status' => Work::STATUS_DELETED], ['in', 'type', [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]]]]);
                }
                else if($task['step']['type'] == Step::TYPE_AUDIT)
                {
                    $query->andWhere(['or', ['in', 'status', [Work::STATUS_SUBMITED, Work::STATUS_FINISH, Work::STATUS_REFUSED]], ['and', ['status' => Work::STATUS_DELETED], ['in', 'type', [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]]], ['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITREFUSE], ['status' => Work::STATUS_DELETED, 'type' => Work::TYPE_AUDITRESET]]);
                }
            }
        }
        $workStartTime && $query->andWhere(['>=', 'start_time', strtotime($workStartTime)]);
        $workEndTime && $query->andWhere(['<=', 'start_time', strtotime($workEndTime)]);
        $updateStartTime && $query->andWhere(['>=', 'updated_at', strtotime($updateStartTime)]);
        $updateEndTime && $query->andWhere(['<=', 'updated_at', strtotime($updateEndTime)]);

        if($keyword !== null)
        {
            $keyword = trim($keyword);
            $query->andFilterWhere([
                'or',
                ['like', 'id', $keyword],
            ]);
        }

        //执行查询
        $count = $query->count();
        $list = [];
        if ($count > 0)
        {
            $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
            $list = $query->offset($offset)->limit($limit)->asArray()->all();
        }

        $workIds = array_column($list, 'id');
        $statResultWork = StatResultWork::fetchData(['project_id' => $projectId, 'work_id' => $workIds]);
        if($statResultWork['error'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat result work error '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $statResultWork['error'], $statResultWork['message']));
        }
        $statResultWork = $statResultWork['data'];
        $labelNames = StatResultWork::getLabelNames();
        foreach($list as $index => $work)
        {
            if(empty($statResultWork[$work['id']]))
            {
                $list[$index]['invalid_data_count'] = 0;
                $list[$index]['label_stat'] = (object)[];
            }
            else
            {
                $list[$index]['invalid_data_count'] = isset($statResultWork[$work['id']][$labelNames[StatResultWork::TYPE_LABEL_NO_COUNT]]['effective_count']) ? $statResultWork[$work['id']][$labelNames[StatResultWork::TYPE_LABEL_NO_COUNT]]['effective_count'] : 0;
                $list[$index]['label_stat'] = empty($statResultWork[$work['id']]) ? (object)[] : $statResultWork[$work['id']];
            }

            //处理未通过（被重置）状态
            if($work['status'] == Work::STATUS_DELETED && in_array($work['type'], [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]))
            {
                $list[$index]['status'] = Work::STATUS_RESETED;
            }
            else if($work['status'] == Work::STATUS_DELETED && $work['type'] == Work::TYPE_AUDITREFUSE)
            {
                $list[$index]['status'] = Work::STATUS_AUDIT_REFUSE;
            }
            else if($work['status'] == Work::STATUS_DELETED && $work['type'] == Work::TYPE_AUDITRESET)
            {
                $list[$index]['status'] = Work::STATUS_AUDIT_RESET;
            }
        }

        //获取模板标注工具
        $templateLabelTypes = Stat::getLabelsByTemplate($project['template_id']);

        $labelTypes = array_combine($labelNames, StatResultWork::getTypes()); //模板标注工具和标注的对应翻译

        $responseData = [
            'list' => $list,
            'count' => $count,
            'produce_statuses' => Work::getProduceStatStatuses(),
            'audit_statuses' => Work::getAuditStatStatuses(),
            'label_types' => $labelTypes,
            'template_label_types' => $templateLabelTypes
        ];

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

    public function actionWorkForm()
    {
        $_logs = [];
        $statStatuses = [];

        $stepId = Yii::$app->request->post('step_id');
        $_logs['$stepId'] = $stepId;
        
        if($stepId)
        {
            $step = Step::find()->where(['id' => $stepId, 'status' => Step::STATUS_NORMAL])->asArray()->limit(1)->one();
            $_logs['$step'] = $step;
            if(empty($step))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' work form step not found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'project_step_not_found', Yii::t('app','project_step_not_found')));
            }
            if($step['type'] == Step::TYPE_PRODUCE)
            {
                $statStatuses = Work::getProduceStatStatuses();
            }
            else if($step['type'] == Step::TYPE_AUDIT)
            {
                $statStatuses = Work::getAuditStatStatuses();
            }
        }
        else
        {
            $statStatuses = Work::getStatuses();
        }
        
        return $this->asJson(FormatHelper::resultStrongType($statStatuses));
    }

    /**
     * 项目绩效导出
     *
     * @return \yii\web\Response
     */
    /**
     * 项目绩效导出
     *
     * @return \yii\web\Response
     */
    public function actionExport(){

        $_logs = [];

        $stepId = (int)Yii::$app->request->post('step_id', 0);
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        // $taskId = (int)Yii::$app->request->post('task_id', 0);
        $userId = (int)Yii::$app->request->post('user_id', 0);

        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app','project_id_not_given')));
        }

        //频率限制
        $isFrequency = SecurityHelper::checkFrequency('statExport:'.$projectId.'-'.$stepId, 100, 300);
        if ($isFrequency)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat_export_ipRequestFrequently '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_export_ipRequestFrequently', Yii::t('app', 'project_stat_export_excessive')));
        }

        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app','project_not_found')));
        }
        if(in_array($project['status'], [Project::STATUS_RELEASING, Project::STATUS_SETTING, Project::STATUS_PREPARING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app','project_status_not_allow')));
        }

        // if($taskId)
        // {
        //     $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
        //     if (!$task)
        //     {
        //         Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
        //         return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
        //     }
        // }

        $steps = Step::find()->select(['id','name','type'])->where(['project_id' => $projectId])->asArray()->all();
        if(empty($steps))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_not_found', Yii::t('app','project_step_not_found')));
        }

        $stepsInfo = [];
        $stepIds = [];
        foreach($steps as $key => $val)
        {
            $stepIds[] = $val['id'];
            $stepsInfo[$val['id']] = [
                'name' => $val['name'],
                'type' => $val['type']
            ];
        }
        if($stepId)
        {
            if (!in_array($stepId, $stepIds))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'project_step_not_found', Yii::t('app','project_step_not_found')));
            }
            $stepIds = [];
            $stepIds[] = $stepId;
        }


        set_time_limit(0);//设置时间不超时
        ini_set('memory_limit','5120M');//设置执行内存

        //设置分表
        Data::setTable($project['table_suffix']);
        $writeContents = [];
        $stepNames = [];

        foreach ($stepIds as $key => $stepId_)
        {
            $stepName = $stepsInfo[$stepId_]['name'];
            $stepType = $stepsInfo[$stepId_]['type'];

            $task = Task::find()->where(['project_id' => $projectId, 'step_id' => $stepId_])->asArray()->limit(1)->one();
            if (!$task)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
            }
            $taskId = $task['id'];

            //查询分步信息
            $step_query = Step::find();
            $step_query ->where(['=','id',$stepId_]);

            $project_query = Data::find()->where(['project_id' => $project['id']]);
            $total_work_count = $project_query->count();//作业总张数
            $query = StatUser::find();
            $stepId_ && $query->andWhere(['step_id' => $stepId_]);
            $projectId && $query->andWhere(['project_id' => $projectId]);
            $taskId && $query->andWhere(['task_id' => $taskId]);
            if($userId){
                $query->andWhere(['user_id' => $userId]);
            }else{
                $query->andWhere(['>','user_id',0]);
            }

            //执行查询
            $count = $query->count();
            $list = [];
            $total = [];
            if ($count > 0)
            {
                $query->orderBy(['id' => SORT_ASC ]);
                $list = $query->with(['task', 'user', 'project'])->asArray()->all();
            }
            $queryTotal = StatUser::find();
            $stepId_ && $queryTotal->andWhere(['step_id' => $stepId_]);
            $taskId && $queryTotal->andWhere(['task_id' => $taskId]);
            $queryTotal->select([
                'count(user_id) as users',
                'sum(work_time) as work_time',
                'sum(work_count) as work_count',
                'sum(submit_count) as submit_count',
                // 'sum(label_count) as label_count',
                // 'sum(label_time) as label_time',
                // 'sum(point_count) as point_count',
                // 'sum(line_count) as line_count',
                // 'sum(rect_count) as rect_count',
                // 'sum(polygon_count) as polygon_count',
                // 'sum(other_count) as other_count',
                'sum(timeout_count) as timeout_count',
                'sum(allow_count) as allow_count',
                'sum(allowed_count) as allowed_count',
                'sum(audited_count) as audited_count',
                'sum(refuse_count) as refuse_count',
                'sum(refused_count) as refused_count',
                'sum(reset_count) as reset_count',
                'sum(reseted_count) as reseted_count',
                'sum(other_operated_count) as other_operated_count',
            ]);
            $total = $queryTotal->groupBy(['step_id'])->asArray()->limit(1)->one();
            
            if (empty($total)) {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
            }
            
            $total_allowed_count = $total['allowed_count'];
            $total_audited_count = $total['audited_count'];
            $total_refused_count = $total['refused_count'];
            $total_reseted_count = $total['reseted_count'];
            $other_operated_count = $total['other_operated_count'];

            $work_time = $total['work_time'];
            //$work_time = $total['work_time']/60;
            //$work_time = $work_time > 1 ?  number_format($work_time) : $work_time;
            $downloadfileRoot = Setting::getDownloadPath($project['user_id'], $projectId);
            $uploadfileRoot = Setting::getUploadRootPath();

            //$total_count = 被通过+被审核驳回+被审核重置+管理员驳回和重置
            // $total_count = $total_allowed_count + $total_refused_count + $total_reseted_count+$other_operated_count;
            if($total_audited_count == 0)
            {
                $accuracy_total = 0;
            }
            else
            {
                $accuracy_total = round($total_allowed_count / $total_audited_count, 4) * 100;
                if($accuracy_total > 100)
                {
                    $accuracy_total = 100;
                }
                else if($accuracy_total < 0)
                {
                    $accuracy_total = 0;
                }
            }

            StatResult::setTable($project['table_suffix']);
            $where = ['project_id' => $projectId];
            if($taskId)
            {
                $where['task_id'] = $taskId;
            }
            $statResult = StatResult::fetchData($where);
            if($statResult['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat export error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $statResult['error'], $statResult['message']));
            }
            $statResult = $statResult['data'];
            // $projectStat = [];
            foreach($statResult as $taskId => $stat)
            {
                foreach($stat as $type => $values)
                {
                    if(empty($total['label_count']))
                    {
                        $total['label_count'] = 0;
                    }
                    if(empty($total[$type]))
                    {
                        $total[$type] = 0;
                    }

                    $total['label_count'] += $values['effective_count'];
                    $total[$type] += $values['effective_count'];
                }
            }

            StatResultUser::setTable($project['table_suffix']);
            $where = ['project_id' => $projectId];
            if($taskId)
            {
                $where['task_id'] = $taskId;
            }
            $statUserResult = StatResultUser::fetchData($where);
            if($statUserResult['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat export error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $statUserResult['error'], $statUserResult['message']));
            }
            $statUserResult = $statUserResult['data'];
            $userStat = [];
            foreach($statUserResult as $userId => $result)
            {
                foreach($result as $type => $values)
                {
                    if(empty($userStat[$userId]['label_count']))
                    {
                        $userStat[$userId]['label_count'] = 0;
                    }
                    if(empty($userStat[$userId][$type]))
                    {
                        $userStat[$userId][$type] = 0;
                    }

                    $userStat[$userId]['label_count'] += $values['effective_count'];
                    $userStat[$userId][$type] += $values['effective_count'];
                }
            }

            $templateLabelTypes = Stat::getLabelsByTemplate($project['template_id']);

            $labelTypes = array_combine(StatResultUser::getLabelNames(), StatResultUser::getTypes()); //模板标注工具和标注的对应翻译

            $accuracy_total .= '%';
            $contents = [];
            //设置表格头的输出
            // if (isset($task['name'])) {
            //     $contents[] = ['任务名称:', $task['name']];
            // }
            $contents[] = [Yii::t('app', 'stat_controller_step_name'), $stepName];
            $contents[] = [Yii::t('app', 'stat_controller_step_type'), Step::getType($stepType)];
            // $contents[] = ['工人总数：', isset($total['users']) ? $total['users'] : 0];
            $contents[] = [Yii::t('app', 'stat_controller_task_total_time'), $work_time];
            $contents[] = [Yii::t('app', 'stat_controller_allow_count_total'), isset($total_allowed_count) ? $total_allowed_count : 0];
            $contents[] = [Yii::t('app', 'stat_controller_submit_times_total'), isset($total['submit_count']) ? $total['submit_count'] : 0];
            $contents[] = [Yii::t('app', 'stat_controller_invalid_count_total'), 0];
            $contents[] = [Yii::t('app', 'stat_controller_refused_count_total'), isset($total_refused_count) ? $total_refused_count : 0];
            $contents[] = [Yii::t('app', 'stat_controller_reseted_count_total'), isset($total_reseted_count) ? $total_reseted_count : 0];
            $contents[] = [Yii::t('app', 'stat_controller_correct_rate_colon'), $accuracy_total];
            $contents[] = [Yii::t('app', 'stat_controller_submit_count_total'), isset($total['work_count']) ? $total['work_count'] : 0];
            // $contents[] = ['作业总张数：', $total_work_count];
            // $contents[] = ['作业执行张数：', isset($total['work_count']) ? $total['work_count'] : 0];
            $contents[] = [Yii::t('app', 'stat_controller_work_result_count_total'), isset($total['label_count']) ? $total['label_count'] : 0];

            foreach($templateLabelTypes as $labelType)
            {
                if(isset($labelTypes[$labelType]))
                {
                    array_push($contents, [$labelTypes[$labelType].'：', isset($total[$labelType]) ? $total[$labelType] : 0]);
                }
            }



            // $contents[] = ['作业总点数：', isset($total['point_count']) ? $total['point_count'] : 0];
            // $contents[] = ['作业总线数：', isset($total['line_count']) ? $total['line_count'] : 0];
            // $contents[] = ['作业总矩形数：', isset($total['rect_count']) ? $total['rect_count'] : 0];
            // $contents[] = ['作业总多边形数：', isset($total['polygon_count']) ? $total['polygon_count'] : 0];
            // $contents[] = ['其他总数：', isset($total['other_count']) ? $total['other_count'] : 0];
            // $contents[] = ['作业中时长（语音）', isset($total['label_time']) ? $total['label_time'] : 0];
            $contents[] = [];

            $contents[] = [Yii::t('app', 'stat_controller_correct_rate_format'), Yii::t('app', 'stat_controller_correct_rate_format_math')];
            // $contents[] = ['注1：', '为已经提交的作业张数'];
            // $contents[] = [];
            //设置表格头的输出
            // $contents[] = ['批次ID','昵称','账号', '所属团队', '张数(注1)', '标注数', '点数', '线数', '框数', '多边形数', '其他图形数', '通过数', '驳回数', '重置数', '正确率', '标注时长（s）', '作业平均时长（s）'];
            $contents[] = [];
            $titleRow = count($contents) + 1;

            if($stepType == Step::TYPE_PRODUCE)
            {
                $content = [
                    Yii::t('app', 'stat_controller_batch_id'),
                    Yii::t('app', 'stat_controller_nickname'),
                    Yii::t('app', 'stat_controller_account'), 
                    Yii::t('app', 'stat_controller_submit_pic_count'), 
                    Yii::t('app', 'stat_controller_allow_pic_count'), 
                    Yii::t('app', 'stat_controller_refused_count'), 
                    Yii::t('app', 'stat_controller_reseted_count'), 
                    Yii::t('app', 'stat_controller_correct_rate'), 
                    Yii::t('app', 'stat_controller_invalid_pic_count'), 
                    Yii::t('app', 'stat_controller_valid_pic_count'), 
                    Yii::t('app', 'stat_controller_valid_data')
                ];
                // array_push($content, '');
                foreach($templateLabelTypes as $labelType)
                {
                    array_push($content, '');
                }
                array_push($content, Yii::t('app', 'stat_controller_accumulation_work_time'));
                array_push($content, Yii::t('app', 'stat_controller_average_work_time'));
                $contents[] = $content;

                $content = ['','','', '', '', '', '', '', '', '', Yii::t('app', 'stat_controller_label_count')];
                foreach($templateLabelTypes as $labelType)
                {
                    $labelName = isset($labelTypes[$labelType]) ? $labelTypes[$labelType] : '';
                    array_push($content, $labelName);
                }
                array_push($content, '');
                array_push($content, '');
                $contents[] = $content;

                if (is_array($list) && count($list)) {
                    foreach ($list as $k => $v) {
                        $allowed_count = $v['allowed_count'];
                        $refused_count = $v['refused_count'];
                        $reseted_count = $v['reseted_count'];
                        $accuracy = 0;
                        $accuracy_total = $allowed_count + $refused_count + $reseted_count;
                        if ($accuracy_total > 0) {
                            $accuracy = round($allowed_count / $accuracy_total, 4) * 100;
                            if ($accuracy > 100) {
                                $accuracy = 100;
                            } elseif ($accuracy < 0) {
                                $accuracy = 0;
                            }
                            $accuracy .= '%';
                        }
                        $averageTime = 0;
                        if ($v['work_count']) {
                            $averageTime = $v['work_time'] / $v['work_count'];
                        }
                        $content = [
                            $v['batch_id'],
                            $v['user']['nickname'],
                            $v['user']['email'],
                            $v['submit_count'],
                            $allowed_count,
                            $refused_count,
                            $reseted_count,
                            $accuracy,
                            0,
                            $v['work_count'],
                            isset($userStat[$v['user_id']]['label_count']) ? $userStat[$v['user_id']]['label_count'] : 0,

                            // $v['point_count'],
                            // $v['line_count'],
                            // $v['rect_count'],
                            // $v['polygon_count'],
                            // $v['other_count'],
                            // $v['label_time'],
                            // $v['work_time'],
                            // round($averageTime)
                        ];
                        foreach($templateLabelTypes as $labelType)
                        {
                            if(isset($labelTypes[$labelType]))
                            {
                                // $contents[] = [$labelTypes[$labelType].'：', isset($userStat[$v['user_id']][$labelType]) ? $userStat[$v['user_id']][$labelType] : 0];
                                array_push($content, isset($userStat[$v['user_id']][$labelType]) ? $userStat[$v['user_id']][$labelType] : 0);
                            }
                        }
                        array_push($content, $v['work_time']);
                        array_push($content, round($averageTime));
                        // $contents[] = $v['work_time'];
                        // $contents[] = round($averageTime);
                        $contents[] = $content;
                    }
                }
                $writeContents[] = $contents;
                $mergeCells[] = [
                    [1, $titleRow, 1, $titleRow + 1],//从1列$titleRow行到1列$titleRow + 1行合并
                    [2, $titleRow, 2, $titleRow + 1],
                    [3, $titleRow, 3, $titleRow + 1],
                    [4, $titleRow, 4, $titleRow + 1],
                    [5, $titleRow, 5, $titleRow + 1],
                    [6, $titleRow, 6, $titleRow + 1],
                    [7, $titleRow, 7, $titleRow + 1],
                    [8, $titleRow, 8, $titleRow + 1],
                    [9, $titleRow, 9, $titleRow + 1],
                    [10, $titleRow, 10, $titleRow + 1],
                    [11, $titleRow, 11+count($templateLabelTypes), $titleRow],
                    [12+count($templateLabelTypes), $titleRow, 12+count($templateLabelTypes), $titleRow + 1],
                    [13+count($templateLabelTypes), $titleRow, 13+count($templateLabelTypes), $titleRow + 1]
                ];
            }
            else if($stepType == Step::TYPE_AUDIT)
            {
                $contents[] = [
                    Yii::t('app', 'stat_controller_batch_id'),
                    Yii::t('app', 'stat_controller_nickname'),
                    Yii::t('app', 'stat_controller_account'), 
                    Yii::t('app', 'stat_controller_submit_pic_count'), 
                    Yii::t('app', 'stat_controller_allow_pic_count'), 
                    Yii::t('app', 'stat_controller_refused_count'), 
                    Yii::t('app', 'stat_controller_reseted_count'), 
                    Yii::t('app', 'stat_controller_correct_rate'), 
                    Yii::t('app', 'stat_controller_valid_pic_count'), 
                    Yii::t('app', 'stat_controller_valid_data'), 
                    '', 
                    '', 
                    Yii::t('app', 'stat_controller_accumulation_work_time'), 
                    Yii::t('app', 'stat_controller_average_work_time')
                ];
                $contents[] = ['', '', '', '', '', '', '', '', '', Yii::t('app', 'stat_controller_allow_count'), Yii::t('app', 'stat_controller_refuse_count'), Yii::t('app', 'stat_controller_reset_count'), '', ''];

                if (is_array($list) && count($list)) {
                    foreach ($list as $k => $v) {
                        $allowed_count = $v['allowed_count'];
                        $refused_count = $v['refused_count'];
                        $reseted_count = $v['reseted_count'];
                        $other_count = $v['other_operated_count'];
                        $accuracy = 0;
                        $accuracy_total = $allowed_count + $refused_count + $reseted_count+$other_count;
                        if ($accuracy_total > 0) {
                            $accuracy = round($allowed_count / $accuracy_total, 4) * 100;
                            if ($accuracy > 100) {
                                $accuracy = 100;
                            } elseif ($accuracy < 0) {
                                $accuracy = 0;
                            }
                            $accuracy .= '%';
                        }
                        $averageTime = 0;
                        if ($v['work_count']) {
                            $averageTime = $v['work_time'] / $v['work_count'];
                        }
                        $contents[] = [
                            $v['batch_id'],
                            $v['user']['nickname'],
                            $v['user']['email'],
                            $v['submit_count'],
                            $allowed_count,
                            $refused_count,
                            $reseted_count,
                            $accuracy,
                            $v['work_count'],
                            $v['allow_count'],
                            $v['refuse_count'],
                            $v['reset_count'],
                            $v['work_time'],
                            round($averageTime)
                        ];
                    }
                }
                $writeContents[] = $contents;
                $mergeCells[] = [
                    [1, $titleRow, 1, $titleRow + 1],//从1列$titleRow行到1列$titleRow + 1行合并
                    [2, $titleRow, 2, $titleRow + 1],
                    [3, $titleRow, 3, $titleRow + 1],
                    [4, $titleRow, 4, $titleRow + 1],
                    [5, $titleRow, 5, $titleRow + 1],
                    [6, $titleRow, 6, $titleRow + 1],
                    [7, $titleRow, 7, $titleRow + 1],
                    [8, $titleRow, 8, $titleRow + 1],
                    [9, $titleRow, 9, $titleRow + 1],
                    [10, $titleRow, 12, $titleRow],
                    [13, $titleRow, 13, $titleRow + 1],
                    [14, $titleRow, 14, $titleRow + 1]
                ];
            }
            else if(in_array($stepType, [Step::TYPE_CHECK, Step::TYPE_ACCEPTANCE]))
            {
                $contents[] = [
                    Yii::t('app', 'stat_controller_batch_id'),
                    Yii::t('app', 'stat_controller_nickname'),
                    Yii::t('app', 'stat_controller_account'), 
                    Yii::t('app', 'stat_controller_submit_pic_count'), 
                    Yii::t('app', 'stat_controller_refused_count'), 
                    Yii::t('app', 'stat_controller_reseted_count'), 
                    Yii::t('app', 'stat_controller_valid_pic_count'), 
                    Yii::t('app', 'stat_controller_valid_data'), 
                    '', 
                    '', 
                    Yii::t('app', 'stat_controller_accumulation_work_time'), 
                    Yii::t('app', 'stat_controller_average_work_time')
                ];
                $contents[] = ['','', '', '', '', '', '', Yii::t('app', 'stat_controller_allow_count'), Yii::t('app', 'stat_controller_refuse_count'), Yii::t('app', 'stat_controller_reset_count'), '', ''];

                if (is_array($list) && count($list)) {

                    foreach ($list as $k => $v) {
                        $refused_count = $v['refused_count'];
                        $reseted_count = $v['reseted_count'];
                        $averageTime = 0;
                        if ($v['work_count']) {
                            $averageTime = $v['work_time'] / $v['work_count'];
                        }
                        $contents[] = [
                            $v['batch_id'],
                            $v['user']['nickname'],
                            $v['user']['email'],
                            $v['submit_count'],
                            $refused_count,
                            $reseted_count,
                            $v['work_count'],
                            $v['allow_count'],
                            $v['refuse_count'],
                            $v['reset_count'],
                            $v['work_time'],
                            round($averageTime)
                        ];
                    }
                }
                $writeContents[] = $contents;
                $mergeCells[] = [
                    [1, $titleRow, 1, $titleRow + 1],//从1列$titleRow行到1列$titleRow + 1行合并
                    [2, $titleRow, 2, $titleRow + 1],
                    [3, $titleRow, 3, $titleRow + 1],
                    [4, $titleRow, 4, $titleRow + 1],
                    [5, $titleRow, 5, $titleRow + 1],
                    [6, $titleRow, 6, $titleRow + 1],
                    [7, $titleRow, 7, $titleRow + 1],
                    [8, $titleRow, 10, $titleRow],
                    [11, $titleRow, 11, $titleRow + 1],
                    [12, $titleRow, 12, $titleRow + 1]
                ];
            }

            $stepNames[] = Step::getType($stepType);
        }

        $filename = $projectId . '_' . $stepId . '_stat_' . date("YmdHis") . '.xls';
        $Path = $downloadfileRoot .'/'.$filename;
        FileHelper::file_write_excel($Path, $writeContents, $mergeCells, $stepNames);


        return $this->asJson(FormatHelper::result([
            'download' => StringHelper::base64_encode(str_replace($uploadfileRoot, '', $Path))
        ]));

    }



    /**
     * 操作记录导出
     *
     * @return \yii\web\Response
     */
    public function actionOperationExport()
    {
        $_logs = [];

        $stepId = (int)Yii::$app->request->post('step_id', 0);
        $batchId = (int)Yii::$app->request->post('batch_id', 0);
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $userId = (int)Yii::$app->request->post('user_id', 0);
        $taskId = (int)Yii::$app->request->post('task_id', 0);

        $_logs['$projectId'] = $projectId;
        $_logs['$stepId'] = $stepId;
        $_logs['$batchId'] = $batchId;
        $_logs['$userId'] = $userId;
        $_logs['$taskId'] = $taskId;
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app','project_id_not_given')));
        }

        //频率限制
        $isFrequency = SecurityHelper::checkFrequency('statExport:'.$projectId.'-'.$stepId, 100, 300);
        if ($isFrequency)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' stat_export_ipRequestFrequently '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'stat_export_ipRequestFrequently', Yii::t('app', 'project_stat_export_excessive')));
        }

        if($taskId)
        {
            $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
            if (!$task)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
            }
        }

        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app','project_not_found')));
        }
        if(in_array($project['status'], [Project::STATUS_RELEASING, Project::STATUS_SETTING, Project::STATUS_PREPARING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app','project_status_not_allow')));
        }

        $steps = Step::find()->select(['id','name','type'])->where(['project_id' => $projectId])->orderBy(['type' => SORT_ASC])->asArray()->all();
        if(empty($steps))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_not_found', Yii::t('app','project_step_not_found')));
        }

        $stepsInfo = [];
        $stepIds = [];
        foreach($steps as $key => $val)
        {
            $stepIds[] = $val['id'];
            $stepsInfo[$val['id']] = [
                'name' => $val['name'],
                'type' => $val['type']
            ];
        }

        if($stepId)
        {
            if (!in_array($stepId, $stepIds))
            {
                $_logs['$stepIds'] = $stepIds;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'project_step_not_found', Yii::t('app','project_step_not_found')));
            }
        }

        //---------------------------------------------
        Data::setTable($project['table_suffix']);
        Work::setTable($project['table_suffix']);

        //--------------------------------------
        $downloadfileRoot = Setting::getDownloadPath($project['user_id'], $projectId);
        $uploadfileRoot = Setting::getUploadRootPath();

        //获取项目的作业数
        $dataQuery = Data::find()->select(['id'])->where(['project_id' => $projectId]);
        $batchId && $dataQuery->andWhere(['batch_id' => $batchId]);
        $dataCounts = $dataQuery->count();

        //每次获取1000个作业id
        $limit = 1000;
        $pages = ceil($dataCounts/$limit);

        $contents = [];
        $contents[] = ['项目id：', $projectId];
        $contents[] = ['项目名称：', $project['name']];
        $contents[] = [];
        $titleRow = count($contents) + 1;
        if($stepId)
        {
            $contents[] = ['批次id', '作业id', '作业名称', $stepsInfo[$stepId]['name']];
            $contents[] = ['','','','最终作业员','作业员id','提交时间',];

            $stepName = $stepsInfo[$stepId]['name'];
            $stepType = $stepsInfo[$stepId]['type'];

            for($i = 0;$i<$pages;$i++)
            {
                $offset = $i * $limit;
                $dataIds = $dataQuery->offset($offset)->limit($limit)->asArray()->column();


                //分组处理
                $groups = array_chunk($dataIds, 100);
                foreach($groups as $key => $dataIds_)
                {
                    $workQuery = Work::find()->select(['id', 'status', 'start_time', 'end_time', 'data_id', 'user_id', 'batch_id'])
                        ->where(['in', 'data_id', $dataIds_])->andWhere(['step_id' => $stepId]);
                    if($stepType == Step::TYPE_PRODUCE)
                    {
                        $workQuery->andWhere(['status' => Work::STATUS_FINISH]);
                    }
                    elseif($stepType == Step::TYPE_AUDIT)
                    {
                        $workQuery->andWhere(['status' => Work::STATUS_FINISH]);
                    }
                    elseif($stepType == Step::TYPE_CHECK)
                    {
                        $workQuery->andWhere(['status' => Work::STATUS_SUBMITED]);
                    }
                    $userId && $workQuery->andWhere(['user_id' => $userId]);
                    $list = $workQuery->with(['data', 'user'])->asArray()->all();
                    if(empty($list))
                    {
                        continue;
                    }

                    foreach($list as $key => $val)
                    {
                        $contents[] = [
                            $val['batch_id'],
                            $val['data_id'],
                            $val['data']['name'],
                            $val['user']['email'],
                            $val['user_id'],
                            date('Y-m-d H:i:s', $val['end_time'])
                        ];
                    }
                }
            }




            $writeContents[] = $contents;
            $mergeCells[] = [
                [1, $titleRow, 1, $titleRow + 1],//从1列$titleRow行到1列$titleRow + 1行合并
                [2, $titleRow, 2, $titleRow + 1],
                [3, $titleRow, 3, $titleRow + 1],
                [4, $titleRow, 6, $titleRow]
            ];
            $filename = $projectId . '_' . $stepId . '_stat_' . date("YmdHis") . '.xls';
        }
        else
        {
            $contents[] = ['批次id', '作业id', '作业名称', '执行', '', '', '审核','','','验收','',''];
            $contents[] = ['','','','最终作业员','作业员id','提交时间','最终作业员','作业员id','提交时间','最终作业员','作业员id','提交时间',];

            $data = [];
            for($i = 0; $i<$pages;$i++)
            {
                $offset = $i * $limit;
                $dataIds = $dataQuery->offset($offset)->limit($limit)->asArray()->column();
                //分组处理
                $groups = array_chunk($dataIds, 100);
                foreach($groups as $key => $dataIds_)
                {
                    foreach($stepIds as $key => $stepId_)
                    {


                        $stepName = $stepsInfo[$stepId_]['name'];
                        $stepType = $stepsInfo[$stepId_]['type'];

                        if($stepType == Step::TYPE_PRODUCE)
                        {
                            $workQuery = Work::find()->select(['id', 'status', 'start_time', 'end_time', 'data_id', 'user_id', 'batch_id'])
                                ->where(['in', 'data_id', $dataIds_])->andWhere(['step_id' => $stepId_])
                                ->andWhere(['status' => Work::STATUS_FINISH]);
                            $userId && $workQuery->andWhere(['user_id' => $userId]);
                            $batchId && $workQuery->andWhere(['batch_id' => $batchId]);
                            $list = $workQuery->with(['data', 'user'])->asArray()->all();
                            if(is_array($list) && count($list))
                            {
                                foreach ($list as $key => $val) {
                                    $data[$val['data_id']] = [
                                        $val['batch_id'],
                                        $val['data_id'],
                                        $val['data']['name'],
                                        $val['user']['email'],
                                        $val['user_id'],
                                        date('Y-m-d H:i:s', $val['end_time'])
                                    ];
                                }
                            }
                        }
                        elseif ($stepType == Step::TYPE_AUDIT)
                        {
                            $workQuery = Work::find()->select(['id', 'status', 'start_time', 'end_time', 'data_id', 'user_id', 'batch_id'])
                                ->where(['in', 'data_id', $dataIds_])->andWhere(['step_id' => $stepId_])
                                ->andWhere(['status' => Work::STATUS_FINISH]);
                            $userId && $workQuery->andWhere(['user_id' => $userId]);
                            $batchId && $workQuery->andWhere(['batch_id' => $batchId]);
                            $list = $workQuery->with(['data', 'user'])->asArray()->all();
                            if(is_array($list) && count($list))
                            {
                                foreach($list as $key => $val)
                                {
                                    if(isset($data[$val['data_id']]))
                                    {
                                        $data[$val['data_id']][] = $val['user']['email'];
                                        $data[$val['data_id']][] = $val['user_id'];
                                        $data[$val['data_id']][] = date('Y-m-d H:i:s', $val['end_time']);
                                    }
                                    else
                                    {
                                        $data[$val['data_id']] = [
                                            $val['batch_id'],
                                            $val['data_id'],
                                            $val['data']['name'],
                                            '',
                                            '',
                                            '',
                                            '',
                                            $val['user']['email'],
                                            $val['user_id'],
                                            date('Y-m-d H:i:s', $val['end_time'])
                                        ];
                                    }

                                }
                            }

                        }
                        elseif ($stepType == Step::TYPE_ACCEPTANCE)
                        {
                            $workQuery = Work::find()->select(['id', 'status', 'start_time', 'end_time', 'data_id', 'user_id', 'batch_id'])
                                ->where(['in', 'data_id', $dataIds_])->andWhere(['step_id' => $stepId_])
                                ->andWhere(['status' => Work::STATUS_SUBMITED]);
                            $userId && $workQuery->andWhere(['user_id' => $userId]);
                            $batchId && $workQuery->andWhere(['batch_id' => $batchId]);
                            $list = $workQuery->with(['data', 'user'])->asArray()->all();
                            if(is_array($list) && count($list))
                            {
                                foreach($list as $key => $val)
                                {
                                    if(isset($data[$val['data_id']]))
                                    {
                                        $data[$val['data_id']][] = $val['user']['email'];
                                        $data[$val['data_id']][] = $val['user_id'];
                                        $data[$val['data_id']][] = date('Y-m-d H:i:s', $val['end_time']);
                                    }
                                    else
                                    {
                                        $data[$val['data_id']] = [
                                            $val['batch_id'],
                                            $val['data_id'],
                                            $val['data']['name'],
                                            '',
                                            '',
                                            '',
                                            '',
                                            '',
                                            '',
                                            '',
                                            '',
                                            $val['user']['email'],
                                            $val['user_id'],
                                            date('Y-m-d H:i:s', $val['end_time'])
                                        ];
                                    }
                                }
                            }

                        }
                    }
                }
                if($data)
                {
                    foreach($data as $key => $val){
                        $contents[] = $val;
                    }
                    unset($data);
                }
            }

            $writeContents[] = $contents;
            $mergeCells[] = [
                [1, $titleRow, 1, $titleRow + 1],//从1列$titleRow行到1列$titleRow + 1行合并
                [2, $titleRow, 2, $titleRow + 1],
                [3, $titleRow, 3, $titleRow + 1],
                [4, $titleRow, 6, $titleRow],
                [7, $titleRow, 9, $titleRow],
                [10, $titleRow, 12, $titleRow]
            ];
            $filename = $projectId . '_' . '_stat_' . date("YmdHis") . '.xls';
        }
        $Path = $downloadfileRoot .'/'.$filename;

        FileHelper::file_write_excel($Path, $writeContents, $mergeCells);


        return $this->asJson(FormatHelper::result([
            'download' => StringHelper::base64_encode(str_replace($uploadfileRoot, '', $Path))
        ]));
    }
}