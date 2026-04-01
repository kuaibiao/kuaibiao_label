<?php
namespace api\controllers;

use common\models\Group;
use common\models\GroupUser;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\components\ProjectHandler;
use common\components\TaskHandler;
use common\models\Setting;
use common\models\Project;
use common\models\ProjectAttribute;
use common\models\Step;
use common\models\Task;
use common\models\Category;
use common\models\Data;
use common\models\DataResult;
use common\models\User;
use common\models\TaskUser;
use common\models\AuthItem;
use common\models\Template;
use common\models\Message;
use common\models\StatUser;
use common\models\AuthAssignment;
use Exception;
use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use common\models\SiteUser;
/**
 * 任务控制器
 */
class TaskController extends Controller
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
                    '*' => ['POST'],
                ],
            ],
            //accesstoken身份验证
            'authenticator' => [
                'class' => AccessTokenAuth::className(),
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
     * 获取项目的任务列表
     * 
     * @return \yii\web\Response
     */
    public function actionList()
    {
        $_logs = [];
        
        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

        //处理参数
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 5;
        $offset = ($page-1)*$limit;

        //排序
        if (!in_array($orderby, ['id', 'start_time', 'end_time']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        //-------------------------------------
        
        //root权限可操作所有租户信息
        if (Yii::$app->user->identity->type == User::TYPE_ROOT)
        {
        }
        //admin权限可操作本租户信息
        elseif (in_array(Yii::$app->user->identity->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
        {
            if (empty(Yii::$app->user->identity->site))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
            }
            $siteId = Yii::$app->user->identity->site->id;
            if (!$siteId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
            }
        }
        //其他用户可以看到本租户人员
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
        }
        
        //---------------------------------------

        $query = Task::find()->where(['not', ['status' => Task::STATUS_DELETED]]);
        $siteId && $query->andWhere(['platform_site_id' => $siteId]);
        $projectId && $query->andWhere(['project_id' => $projectId]);

        if ($keyword)
        {
            $query->andWhere(['or',
                ['like', 'id', $keyword],
                ['like', 'name', $keyword],
                ['like', 'project_id', $keyword],
                ['like', 'batch_id', $keyword]
            ]);
        }

        $count = $query->count();
        $list = [];
        $categories = [];
        if ($count > 0)
        {
            $list = $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
                ->offset($offset)->limit($limit)
                ->with(['project', 'batch', 'step', 'stat',])
                ->asArray()->all();

            if ($list)
            {
                $projectTaskStats = [];
                $projectIds = array_column($list, 'project_id');
                foreach ($projectIds as $projectId) {
                    list($taskStats, $taskUserStats) = Project::getTaskStats($projectId);
                    
                    $projectTaskStats[$projectId] = $taskStats;
                }
                
                foreach ($list as $k => $task_)
                {
                    /*
                    if (isset($list[$k]['stat']))
                    {
                        if (!empty($list[$k]['step']['condition']))
                        {
                            $list[$k]['audit_rate'] = ($list[$k]['stat']['amount'] > 0 ? floor(($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count']) / $list[$k]['stat']['amount']*100) : 0).'%';
                            $list[$k]['pass_rate'] = ($list[$k]['stat']['work_count'] > 0 ? floor($list[$k]['stat']['allow_count'] / ($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count'])*100) : 0).'%';
                        }
                        else
                        {
                            $list[$k]['audit_rate'] = ($list[$k]['batch']['amount'] > 0 ? floor(($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count']) / $list[$k]['batch']['amount']*100) : 0).'%';
                            $list[$k]['pass_rate'] = ($list[$k]['stat']['work_count'] > 0 ? floor($list[$k]['stat']['allow_count'] / ($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count'])*100) : 0).'%';
                        }

                        $list[$k]['refused_revise'] = $list[$k]['stat']['refused_count'] - $list[$k]['stat']['refused_revise_count'];
                        $list[$k]['difficult_revise'] = $list[$k]['stat']['difficult_count'] - $list[$k]['stat']['difficult_revise_count'];
                    }
                    else
                    {
                        $list[$k]['audit_rate'] = '0%';
                        $list[$k]['pass_rate'] = '0%';
                        $list[$k]['refused_revise'] = 0;
                        $list[$k]['difficult_revise'] = 0;
                        $list[$k]['refuse_revised'] = 0;
                    }*/
                    
                    if (!empty($projectTaskStats[$task_['project_id']]) && !empty($projectTaskStats[$task_['project_id']][$task_['batch_id']][$task_['step_id']])) {
                        $task_['stat'] = array_merge($task_['stat'], $projectTaskStats[$task_['project_id']][$task_['batch_id']][$task_['step_id']]);
                    }
                    
                    $list[$k] = $task_;
                }
            }

            $projectIds = $query->select(['project_id'])->asArray()->column();
            $categoryIds = Project::find()->select(['category_id'])->where(['in', 'id', $projectIds])->asArray()->column();
            $categories = Category::getNameByIds($categoryIds);
        }

        $responseData = [
            'list' => $list,
            'count' => $count,
            'categories' => $categories,
            'step_types' => Step::getTypes(),
            'statuses' => Task::getStatuses()
        ];

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionDetail()
    {
        $_logs = [];
        
        $taskId = Yii::$app->request->post('task_id', null);
        if (!$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }
        
        $task = Task::find()->where(['id' => $taskId])->asArray()->with(['project', 'step', 'stat'])->limit(1)->one();
        if (!$task)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
        }

        $projectId = $task['project']['id'];
        $userId = $task['project']['user_id'];
        
        //模板信息
        $templateId = 0;
        if ($task['step']['template_id'])
        {
            $templateId = $task['step']['template_id'];
        }
        elseif ($task['project']['template_id'])
        {
            $templateId = $task['project']['template_id'];
        }
        $template = Template::find()->select(['id', 'config'])->where(['id' => $templateId])->asArray()->limit(1)->one();
        if (!empty($template['config']))
        {
            $template['config'] = JsonHelper::json_decode_all($template['config']);
        }
        
		//项目空间路径
		$attachments = FileHelper::get_dir_files(Setting::getAttachmentPath($userId, $projectId), Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::ATTACHMENT_EXTS, $userId.'/'. $projectId.'/');

		$paths = [];
		if($attachments)
		{
			foreach ($attachments as $key => $val){
				$paths[] = [
					'path' => StringHelper::base64_encode($val['path']),
					'name' => $val['name'],
					'ctime' =>  $val['ctime'],
				];
			}
		}
        
        $responseData = [
            'info' => $task,
            'template' => $template,
			'attachments' => $paths,
            'step_types' => Step::getTypes()
        ];
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionTop()
    {
        $_logs = [];
        
        $taskId = Yii::$app->request->post('task_id', null);
        if (!$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }
        
        $taskIds = FormatHelper::param_int_to_array($taskId);
        if ($taskIds)
        {
            $timeNow = time();
            foreach ($taskIds as $taskId)
            {
                $task = Task::find()->where(['id' => $taskId])->with(['step'])->limit(1)->asArray()->one();
                if (!$task)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
                }
                
                if (!empty($task['is_top']))
                {
                    $attributes = [
                        'is_top' => 0,
                        'sort' => intval($task['project_id'] . $task['step']['sort']),
                        'updated_at' => $timeNow,
                    ];
                }
                else
                {
                    $attributes = [
                        'is_top' => 1,
                        'sort' => $timeNow + intval($task['project_id'] . $task['step']['sort']),
                        'updated_at' => $timeNow,
                    ];
                }
                Task::updateAll($attributes, ['id' => $taskId]);
            }
        }
        
        return $this->asJson(FormatHelper::resultStrongType(1));
    }
    
    /*
     * 获取当前用户可执行的任务列表
     * 未分配不会获取到
     *
     */
    public function actionTasks()
    {
        $_logs = [];

        $projectId = Yii::$app->request->post('project_id', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $lastTaskId = (int)Yii::$app->request->post('last_task_id', 0); //上一次获取的最后一个id
        $categoryId = (int)Yii::$app->request->post('category_id', 0);
        $categoryFileType = Yii::$app->request->post('category_file_type', null);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

        $stime = trim(Yii::$app->request->post('stime', ''));
        $etime = trim(Yii::$app->request->post('etime', ''));
        $timeField = trim(Yii::$app->request->post('timeField', ''));
        $status = Yii::$app->request->post('status', null);
        
        $isRoot = false;
        $siteId = 0;
        $teamId = 0;
        $crowdsourcingId = 0;

        //处理参数
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 5;
        $offset = ($page-1)*$limit;
        $lastTaskId < 1 && $lastTaskId = 0;

        $timeArr = ['start_time','end_time','created_at','updated_at'];
        if(!empty($timeField) && !in_array($timeField, $timeArr))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' time_type_not_correct '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'time_type_not_correct', Yii::t('app', 'time_type_not_correct')));
        }

        //排序
        if (!in_array($orderby, ['id', 'end_time', 'start_time']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        $statuses = [];
        if (is_numeric($status))
        {
            $statuses = FormatHelper::param_int_to_array($status);
        }
        elseif (is_array($status))
        {
            $statuses = $status;
        }
        else
        {
            $statuses = ['status' => Task::STATUS_NORMAL];
        }
        
        //-------------------------------------
        
        //root权限可操作所有租户信息
        if (Yii::$app->user->identity->type == User::TYPE_ROOT)
        {
        }
        //admin权限可操作本租户信息
        elseif (in_array(Yii::$app->user->identity->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
        {
            if (empty(Yii::$app->user->identity->site))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
            }
            $siteId = Yii::$app->user->identity->site->id;
            if (!$siteId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
            }
        }
        //其他用户可以看到本租户人员
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
        }
        
        //---------------------------------------


        //获取团队或组织的任务列表
        $query = Task::find()->select(['id']);
        $siteId && $query->andWhere(['platform_site_id' => $siteId]);
        $projectId && $query->andWhere(['project_id' => $projectId]);
        
        if($statuses)
        {
            $query->andWhere(['in', 'status', $statuses]);
        }

        //额外的搜索条件
        $stime && $timeField && $query->andWhere(['>=', $timeField, $stime]);
        $etime && $timeField && $query->andWhere(['<=', $timeField, $etime]);

        $allowTaskIds = $query->asArray()->column();

        $taskIds = TaskUser::find()
        ->select(['task_id'])
        ->where(['user_id' => Yii::$app->user->id])
        ->andWhere(['in', 'task_id', $allowTaskIds])
        ->asArray()->column();
        
        $publicTaskIds = Task::find()
        ->select(['id'])
        ->where(['is_public' => 1])
        ->andWhere(['in', 'id', $allowTaskIds])
        ->asArray()->column();
        
        $newPublicTaskIds = array_diff($publicTaskIds, $taskIds);
        if ($newPublicTaskIds) {
            foreach ($newPublicTaskIds as $newPublicTaskId) {
                $task = Task::find()->where(['id' => $newPublicTaskId])->asArray()->limit(1)->one();
                
                $taskUser = new TaskUser();
                $taskUser->project_id = $task['project_id'];
                $taskUser->task_id = $newPublicTaskId;
                $taskUser->user_id = Yii::$app->user->id;
                $taskUser->created_at = time();
                $taskUser->save();
                
                $attributes = [
                    'user_count' => 1
                ];
                Task::updateAllCounters($attributes, ['id' => $newPublicTaskId]);
            }
            $taskIds = array_merge($taskIds, $newPublicTaskIds);
        }

        $taskIds = array_unique($taskIds);
        
        $stepIds = Task::find()->select('step_id')->where(['in','id',$taskIds])->asArray()->column();

        if($projectId)
        {
            $stepIds = Step::find()->select('id')->where(['in','id',$stepIds])
                ->andWhere(['type'=>Step::TYPE_ACCEPTANCE])
                ->andWhere(['status'=>Step::STATUS_NORMAL])
                ->asArray()->column();
        }
        else
        {
            $stepIds = Step::find()->select('id')->where(['in','id',$stepIds])
                ->andWhere(['not in','type',[Step::TYPE_ACCEPTANCE]])
                ->andWhere(['status'=>Step::STATUS_NORMAL])
                ->asArray()->column();
        }

        //所有的项目
        $projectIds = Task::find()->select(['project_id'])->where(['in', 'id', $taskIds])->asArray()->column();
        $projectIds = array_unique($projectIds);

        //所有的分类
        $categoryIds = Project::find()->select(['category_id'])->where(['in', 'id', $projectIds])->asArray()->column();
        $categoryIds = array_unique($categoryIds);
        $categories = Category::getNameByIds($categoryIds);

        //查询列表
        $query = Task::find()->where(['in', 'id', $taskIds]);

        $query->andWhere(['in','step_id',$stepIds]);

        if ($categoryId)
        {
            $projectIds_ = Project::find()->select(['id'])->where(['in', 'id', $projectIds])->andWhere(['category_id' => $categoryId])->asArray()->column();
            $query->andWhere(['in', 'project_id', $projectIds_]);
        }
        if (isset($categoryFileType) && is_numeric($categoryFileType))
        {
            $categoryIds_ = Category::find()->where(['file_type' => $categoryFileType, 'status' => Category::STATUS_ENABLE])->asArray()->column();
            $projectIds_ = Project::find()->select(['id'])->where(['in', 'id', $projectIds])->andWhere(['in', 'category_id', $categoryIds_])->asArray()->column();
            $query->andWhere(['in', 'project_id', $projectIds_]);
        }
        if($keyword)
        {
            $query->andWhere(['or',
                ['like', 'id', $keyword],
                ['like', 'name', $keyword],
                ['like', 'project_id', $keyword],
                ['like', 'batch_id', $keyword]
            ]);
        }

        $count = $query->count();
        $list = [];
        if ($count > 0)
        {
            $query->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC]);

            if ($lastTaskId)
            {
                $list = $query->andWhere(['<', 'id', $lastTaskId])
                    ->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
                    ->offset(0)->limit($limit)
                    ->with(['project', 'batch', 'step', 'stat'])
                    ->asArray()->all();
            }
            else
            {
                $list = $query->select('*')->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
                    ->offset($offset)->limit($limit)
                    ->with(['project', 'batch', 'step', 'stat'])
                    ->asArray()->all();
            }
        }

        if ($list)
        {
            $taskIds = array_column($list, 'id');
            
            $statUserList = StatUser::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->andWhere(['in', 'task_id', $taskIds])
            ->asArray()->all();
            
            $statUserArr = [];
            if ($statUserList)
            {
                foreach ($statUserList as $v)
                {
                    $statUserArr[$v['task_id']] = $v;
                }
            }
            
            $projectTaskStats = [];
            $projectTaskUserStats = [];
            $projectIds = array_column($list, 'project_id');
            foreach ($projectIds as $projectId) {
                list($taskStats, $taskUserStats) = Project::getTaskStats($projectId);
                
                $projectTaskStats[$projectId] = $taskStats;
                $projectTaskUserStats[$projectId] = $taskUserStats;
            }

            foreach ($list as $k => $task_)
            {
                /*
                if (isset($list[$k]['stat']))
                {
                    if (!empty($list[$k]['step']['condition']))
                    {
                        $list[$k]['audit_rate'] = ($list[$k]['stat']['amount'] > 0 ? floor(($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count']) / $list[$k]['stat']['amount']*100) : 0).'%';
                        $list[$k]['pass_rate'] = ($list[$k]['stat']['work_count'] > 0 ? floor($list[$k]['stat']['allow_count'] / ($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count'])*100) : 0).'%';
                    }
                    else
                    {
                        $list[$k]['audit_rate'] = ($list[$k]['batch']['amount'] > 0 ? floor(($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count']) / $list[$k]['batch']['amount']*100) : 0).'%';
                        $list[$k]['pass_rate'] = ($list[$k]['stat']['work_count'] > 0 ? floor($list[$k]['stat']['allow_count'] / ($list[$k]['stat']['work_count']+$list[$k]['stat']['refuse_count']+$list[$k]['stat']['reset_count'])*100) : 0).'%';
                    }
                }
                else
                {
                    $list[$k]['audit_rate'] = '0%';
                    $list[$k]['pass_rate'] = '0%';
                }

                //查询我的绩效
                //$statUser = StatUser::find()->where(['user_id' => Yii::$app->user->id, 'task_id' => $v['id']])->asArray()->limit(1)->one();
                $list[$k]['refused_revise'] = isset($statUserArr[$v['id']]) ? ($statUserArr[$v['id']]['refused_count'] - $statUserArr[$v['id']]['refused_revise_count']) : 0;
                $list[$k]['difficult_revise'] = isset($statUserArr[$v['id']]) ? ($statUserArr[$v['id']]['difficult_count'] - $statUserArr[$v['id']]['difficult_revise_count']) : 0;
                $list[$k]['refuse_revised'] = isset($statUserArr[$v['id']]) ? ($statUserArr[$v['id']]['refuse_revised_count'] - $statUserArr[$v['id']]['refuse_receive_count']) : 0;
                */
                
                $task_['statUser'] = [];
                $task_['statUser']['has_refuse_count'] = '0';
                $task_['statUser']['has_refused_count'] = '0';
                $task_['statUser']['has_difficult_count'] = '0';
                $task_['statUser']['has_execute_count'] = '0';
                $task_['statUser']['has_refuse_submited_count'] = '0';
                $task_['statUser']['has_refuse_wait_count'] = '0';
                if (isset($statUserArr[$task_['id']]))
                {
                    $task_['statUser'] = $statUserArr[$task_['id']];
                }
                
                $task_['audit_rate'] = '0%';
                $task_['pass_rate'] = '0%';
                $task_['has_refuse_count'] = '0';
                $task_['has_refuse_submited_count'] = '0';
                $task_['has_refuse_wait_count'] = '0';
                $task_['has_refused_count'] = '0';
                $task_['has_difficult_count'] = '0';
                $task_['has_execute_count'] = '0';
                
                if (!empty($projectTaskStats[$task_['project_id']]) &&
                    !empty($projectTaskStats[$task_['project_id']][$task_['batch_id']][$task_['step_id']])) 
                {
                    $task_['stat'] = array_merge($task_['stat'], $projectTaskStats[$task_['project_id']][$task_['batch_id']][$task_['step_id']]);
                }
                
                if (!empty($projectTaskUserStats[$task_['project_id']]) &&
                    !empty($task_['statUser']['user_id']) &&
                    !empty($projectTaskUserStats[$task_['project_id']][$task_['batch_id']][$task_['step_id']][$task_['statUser']['user_id']])) 
                {
                    $task_['statUser'] = array_merge($task_['statUser'], $projectTaskUserStats[$task_['project_id']][$task_['batch_id']][$task_['step_id']][$task_['statUser']['user_id']]);
                }
                
                $list[$k] = $task_;
            }
        }

        $responseData = [
            'category_id' => $categoryId,
            'categories' => $categories,
            'list' => $list,
            'count' => $count,
            'step_types' => Step::getTypes(),
            'statuses' => Task::getStatuses(),
            'category_file_types' => Category::getFileTypes(),
            'assign_types' => Project::getAssignTypes()
        ];

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    /**
     * 执行任务
     * @return \yii\web\Response
     */
    public function actionExecute()
    {
        $_logs = [];
    
        //必传参数
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $taskId = (int)Yii::$app->request->post('task_id', 0);
        $op = Yii::$app->request->post('op', null);
    
        //非必传参数
        $dataIds = (int)Yii::$app->request->post('data_ids', 0);
        $userId = (int)Yii::$app->request->post('user_id', 0);
        $_logs['$projectId'] = $projectId;
        $_logs['$taskId'] = $taskId;
        $_logs['$dataIds'] = $dataIds;
        $_logs['$userId'] = $userId;
        $_logs['$op'] = $op;
    
        if (!$projectId || !$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_exe_param_error '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_exe_param_error', Yii::t('app', 'task_exe_param_error')));
        }
    
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        if (in_array($project['status'], [Project::STATUS_SETTING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow_setting '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow_setting', Yii::t('app', 'project_status_not_allow_setting')));
        }
        if (!in_array($project['status'], [Project::STATUS_WORKING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app', 'project_status_not_allow')));
        }
        if ($project['start_time'] > time())
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_time_not_start '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_time_not_start', Yii::t('app', 'project_time_not_start')));
        }
        if ($project['end_time'] < time())
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_time_ended '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_time_ended', Yii::t('app', 'project_time_ended')));
        }
        
    
        $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
        if (!$task)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
        }
    
        if ($task['project_id'] != $projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_project_not_match '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_project_not_match', Yii::t('app', 'task_project_not_match')));
        }
        if ($task['status'] != Task::STATUS_NORMAL)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_status_not_allow', Yii::t('app', 'task_status_not_allow')));
        }
        if ($task['start_time'] > time())
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_time_not_start '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_time_not_start', Yii::t('app', 'task_time_not_start')));
        }
        if ($task['end_time'] < time())
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_time_ended '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_time_ended', Yii::t('app', 'task_time_ended')));
        }
    
        //---------------------------------------------
    
        //查询分类
        $categoryInfo = Category::find()->where(['id' => $project['category_id']])->asArray()->limit(1)->one();
        if (!$categoryInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_category_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_category_not_found', Yii::t('app', 'task_category_not_found')));
        }
    
        //---------------------------------
        // 提交表单
        //---------------------------------
    
        if ($op == 'fetch')
        {
            //显示的作业条数
            $dataCount = Yii::$app->request->post('data_count', 5);
            //显示的顺序0顺序,1随机
            $dataSort = Yii::$app->request->post('data_sort', 0);
            $_logs['$dataCount'] = $dataCount;
            $_logs['$dataSort'] = $dataSort;
            
            if ($dataCount > 100)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_count_too_large '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_count_too_large', Yii::t('app', 'task_data_count_too_large')));
            }
            
            //设置领取数量
            if (!empty($task['receive_count']) && $task['receive_count'] > 0)
            {
                $dataCount = $task['receive_count'];
            }
    
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            //获取数据
            $result = $taskHandler->fetch($dataIds, $userId, $dataCount, $dataSort);
            //$_logs['$result'] = $result;
    
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_fetch_FetchError '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', $result['error'], $result['message']));
            }
    
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_fetch_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($result['data']));
        }
        else if ($op == 'fetchone')
        {
            
        }
        else if ($op == 'submit')
        {
            $postData = Yii::$app->request->post();
            //$_logs['$postData'] = $postData;
            //Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $postData '.json_encode($_logs));
    
            //没有填写任何内容, 打回重新做
            if (!$postData)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_submit_data_empty '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_submit_data_empty', Yii::t('app', 'task_submit_data_empty')));
            }
    
            //遍历转化数组里的json格式
            $postData = JsonHelper::json_decode_all($postData);

            //结果数据不能为空
            if (empty($postData['result']) || !is_array($postData['result']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_result_param_invalid '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_result_param_invalid', Yii::t('app', 'task_result_param_invalid')));
            }

            //若是驳回的作业是否重回审核员
            $postData['produce_redo_backtoauditor'] = 1;

            //执行任务类
            //---------------------------------
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            //提交数据
            $result = $taskHandler->submit($dataIds, $userId, $postData);
            //$_logs['$result'] = $result;
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_submit_FetchError '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $result['error'], $result['message']));
            }
    
            //------------------------------------------------
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_submit_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($result['data']));
        }
        else if ($op == 'execute')
        {
            //清空用户已领取的作业
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_execute_$op=execute '.json_encode($_logs));
    
            $executeDataId = Yii::$app->request->post('data_id', 0);
            if (!$executeDataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
    
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            $result = $taskHandler->execute($dataIds, $userId, $executeDataId);
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_execute_FetchError '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', $result['error'], $result['message']));
            }
    
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_execute_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($result['data']));
        }
        else if ($op == 'nearby')
        {
            //清空用户已领取的作业
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_execute_$op=execute '.json_encode($_logs));
        
            $dataId = Yii::$app->request->post('data_id', 0);
            $count = Yii::$app->request->post('count', 5);
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
        
            //设置分表
            Data::setTable($project['table_suffix']);
            DataResult::setTable($project['table_suffix']);
            
            
            $beforeList = Data::find()->where(['project_id' => $projectId])
            ->andWhere(['<', 'id', $dataId])->orderBy(['id' => SORT_DESC])
            ->offset(0)->limit($count)
            ->asArray()->all();
            
            
            $afterList = Data::find()->where(['project_id' => $projectId])
            ->andWhere(['>', 'id', $dataId])->orderBy(['id' => SORT_ASC])
            ->offset(0)->limit($count)
            ->asArray()->all();
            
        
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_execute_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result(['before' => $beforeList, 'after' => $afterList]));
        }
        elseif ($op == 'clear')
        {
            //清空当前用户的缓存, 并回退到缓存池
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_clear_$op=clear '.json_encode($_logs));
    
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            $result = $taskHandler->clearReceived();
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_clear_FetchError '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', $result['error'], $result['message']));
            }
    
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_clear_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($result['data']));
        }
        //暂存结果
        elseif ($op == 'temporary_storage')
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_edit_$op=temporary_storage '.json_encode($_logs));
        
            //设置分表
            Data::setTable($project['table_suffix']);
            DataResult::setTable($project['table_suffix']);
        
            //审核时, 编辑当前作业
            $dataId = Yii::$app->request->post('data_id');
            $workResult = Yii::$app->request->post('work_result');
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson('', 'task_data_id_not_given',  Yii::t('app', 'task_data_id_not_given'));
            }
            if (!JsonHelper::is_json($workResult))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_result_not_json '.json_encode($_logs));
                return $this->asJson('', 'task_data_result_not_json', Yii::t('app', 'task_data_result_not_json'));
            }
            $workResult = JsonHelper::json_decode_all($workResult);
            $_logs['$workResult'] = ArrayHelper::var_desc($workResult);//记录数据
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
        
            $result = $taskHandler->temporaryStorage($dataIds, $userId, $dataId, $workResult);
            //$_logs['$result'] = $result;
        
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' temporaryStorage error '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', $result['error'], $result['message']));
            }
        
        
            //------------------------------------------------
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' temporaryStorage succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('success'));
        }
        else if ($op == 'edit')
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_edit_$op=edit '.json_encode($_logs));
            
            //审核时, 编辑当前作业
            $dataId = Yii::$app->request->post('data_id');
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given'));
            }
    
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            $result = $taskHandler->auditEditGet($dataIds, $userId, $dataId);
            //$_logs['$result'] = $result;
            
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_edit_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', $result['error'], $result['message']));
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_edit_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($result['data']));
        }
        elseif ($op == 'edit_submit')
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_edit_$op=edit_submit '.json_encode($_logs));
            
            //设置分表
            Data::setTable($project['table_suffix']);
            DataResult::setTable($project['table_suffix']);
            
            //审核时, 编辑当前作业
            $workId = Yii::$app->request->post('work_id');
            $dataId = Yii::$app->request->post('data_id');
            $dataResult = Yii::$app->request->post('data_result');
    
            if (!$workId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson('', 'task_data_id_not_given',  Yii::t('app', 'task_data_id_not_given'));
            }
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson('', 'task_data_id_not_given',  Yii::t('app', 'task_data_id_not_given'));
            }
            if (!JsonHelper::is_json($dataResult))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_result_not_json '.json_encode($_logs));
                return $this->asJson('', 'task_data_result_not_json', Yii::t('app', 'task_data_result_not_json'));
            }
            $dataResult = JsonHelper::json_decode_all($dataResult);
            $_logs['$dataResult'] = ArrayHelper::var_desc($dataResult);//记录数据
            
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            $result = $taskHandler->auditEditSubmit($dataIds, $userId, $workId, $dataId, $dataResult);
            //$_logs['$result'] = $result;
            
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_edit_submit_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', $result['error'], $result['message']));
            }
            
    
            //------------------------------------------------
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_edit_submit_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('success'));
        }
        elseif ($op == 'redo')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
        
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
        
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->redo($dataId);
                //$_logs['$result'] = $result;
        
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_redo_notAllow '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_redo_notAllow', '此操作不可执行'));
                }
            }
        
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        elseif ($op == 'force_refuse')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
            
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
            
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->forceRefuse($dataId);
                //$_logs['$result'] = $result;
                
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_forceRefuse_notAllow '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_forceRefuse_notAllow', '此操作不可执行'));
                }
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        elseif ($op == 'force_reset')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
            
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
            
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->forceReset($dataId);
                //$_logs['$result'] = $result;
                
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_forceReset_notAllow '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_forceReset_notAllow', '此操作不可执行'));
                }
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        elseif ($op == 'refuse_revise')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->refusedRevise($dataId);
                //$_logs['$result'] = $result;
                
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_refuseRevise_notAllow '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_refuseRevise_notAllow', '此操作不可执行'));
                }
            }
        
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        //对驳回的作业进行重置操作(管理员)
        elseif ($op == 'refuse_reset')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->refusedReset($dataId);
                //$_logs['$result'] = $result;
            
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_refuseReset_notAllow '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_refuseReset_notAllow', '此操作不可执行'));
                }
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        //对返工作业进行领取
        elseif ($op == 'refusesubmit_receive')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
        
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
        
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->refuseSubmitReceive($dataId);
                //$_logs['$result'] = $result;
        
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_fetch_FetchError '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_forceRefuse_notAllow', '此操作不可执行'));
                }
            }
        
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        //对返工作业进行重置
        elseif ($op == 'refusesubmit_reset')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->refuseSubmitReset($dataId);
                //$_logs['$result'] = $result;
            
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_fetch_FetchError '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_forceRefuse_notAllow', '此操作不可执行'));
                }
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        elseif ($op == 'difficult_revise')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
        
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
            
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->difficultRevise($dataId);
                //$_logs['$result'] = $result;
            
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_fetch_FetchError '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_forceRefuse_notAllow', '此操作不可执行'));
                }
            }
        
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        elseif ($op == 'difficult_reset')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            $dataId = trim($dataId, ',');
            if (strpos($dataId, ','))
            {
                $dataIds = explode(',', $dataId);
            }
            else
            {
                $dataIds = [$dataId];
            }
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->difficultReset($dataId);
                //$_logs['$result'] = $result;
            
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_fetch_FetchError '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_forceRefuse_notAllow', '此操作不可执行'));
                }
            }
        
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result('1'));
        }
        //超像素功能
        elseif ($op == 'superpixel')
        {
            $dataId = Yii::$app->request->post('data_id', null);
            $_logs['$dataId'] = $dataId;
            
            $point = Yii::$app->request->post('point', null);
            $_logs['$point'] = $point;
        
            if (!$dataId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            if (!$point)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' point_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'point_not_given', Yii::t('app', 'point_not_given')));
            }
        
            $dataIds = FormatHelper::param_int_to_array($dataId);
            $points = FormatHelper::param_int_to_array($point);
            $points = array_combine(['x', 'y'], $points);
        
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
        
            $resultData = [];
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->superpixel($dataId, $points);
                $_logs['$result'] = $result;
        
                if (!$result)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_fetch_FetchError '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_forceRefuse_notAllow', '此操作不可执行'));
                }
                $resultData[$dataId] = $result;
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($resultData));
        }
        //ai辅助
        elseif ($op == 'aimodel')
        {
            $aimodelName = Yii::$app->request->post('aimodel_name', null);
            $dataId = Yii::$app->request->post('data_id', null);
            $args = Yii::$app->request->post('args', null);
            $_logs['$aimodelName'] = $aimodelName;
            $_logs['$dataId'] = $dataId;
            $_logs['$args'] = $args;
            
            if (empty($aimodelName))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            
            if ($aimodelName == 'audio/BaiduAudio') {
                $aimodelName = 'audio/AliAudio';
            }
            
            if (empty($dataId))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::result('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
            }
            $dataIds = FormatHelper::param_int_to_array($dataId);
            
            if ($args && JsonHelper::is_json($args))
            {
                $args = JsonHelper::json_decode_all($args);
            }
            
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            $resultData = [];
            foreach ($dataIds as $dataId)
            {
                $result = $taskHandler->aimodel($aimodelName, $dataId, $args);
                $_logs['$result'] = $result;
                // $taskHandler->unlock();
                
                if (!empty($result['error']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_textSegmentation_FetchError '.json_encode($_logs));
                    return $this->asJson(FormatHelper::result('', 'task_execute_textSegmentation_notAllow', '此操作不可执行'));
                }
                elseif (isset($result['data']))
                {
                    $resultData[$dataId] = $result['data'];
                }
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($resultData));
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
    }
    
    
    public function actionBatchExecute()
    {
        $_logs = [];
    
        //必传参数
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $taskId = (int)Yii::$app->request->post('task_id', 0);
        $op = Yii::$app->request->post('op', null);
    
        //非必传参数
        $dataIds = (int)Yii::$app->request->post('data_ids', 0);
        $userId = (int)Yii::$app->request->post('user_id', 0);
        $_logs['$projectId'] = $projectId;
        $_logs['$taskId'] = $taskId;
        $_logs['$dataIds'] = $dataIds;
        $_logs['$userId'] = $userId;
        $_logs['$op'] = $op;
    
        if (!$projectId || !$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_exe_param_error '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_exe_param_error', Yii::t('app', 'task_exe_param_error')));
        }
    
        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
    
        if (!in_array($project['status'], [Project::STATUS_WORKING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app', 'project_status_not_allow')));
        }
    
        $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
        if (!$task)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
        }
    
        if ($task['project_id'] != $projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_project_not_match '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_project_not_match', Yii::t('app', 'task_project_not_match')));
        }
        if ($task['status'] != Task::STATUS_NORMAL)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_status_not_allow', Yii::t('app', 'task_status_not_allow')));
        }
    
        //---------------------------------------------
    
        //查询分类
        $categoryInfo = Category::find()->where(['id' => $project['category_id']])->asArray()->limit(1)->one();
        if (!$categoryInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_category_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_category_not_found', Yii::t('app', 'task_category_not_found')));
        }
    
        //---------------------------------
        // 提交表单
        //---------------------------------
    
        if ($op == 'fetch')
        {
            $page = (int)Yii::$app->request->post('page', 1);
            $limit = (int)Yii::$app->request->post('limit', 10);
            $keyword = trim(Yii::$app->request->post('keyword', ''));
            $orderby = Yii::$app->request->post('orderby', null);
            $sort = Yii::$app->request->post('sort', null);
            $rate = Yii::$app->request->post('rate', null);
            $operator = Yii::$app->request->post('operator', null);
            
            $page < 1 && $page = 1;
            $limit < 1 && $limit = 10;
            $offset = ($page-1)*$limit;
            
            //排序
            if (!in_array($orderby, ['correct_rate', 'id']))
            {
                $orderby = 'correct_rate';
            }
            if (!in_array($sort, ['asc', 'desc']))
            {
                $sort = 'desc';
            }
            
            
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            //生成数据并保存到key
            $result = $taskHandler->batchFetch($orderby, $sort, $offset, $limit, $rate, $operator, $userId);
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $result['error'], $result['message']));
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($result['data']));
            
        }
        elseif ($op == 'submit')
        {
            $type = (int)Yii::$app->request->post('type', 0);
            $rate = (int)Yii::$app->request->post('rate', 100);
            $operator = Yii::$app->request->post('operator', null);
            
            //实例化执行类
            $taskHandler = new TaskHandler();
            $isinit = $taskHandler->init($projectId, $task['batch_id'], $task['step_id'], Yii::$app->user->id);
            if (!$isinit)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_init_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_init_fail', Yii::t('app', 'task_init_fail')));
            }
            
            //生成数据并保存到key
            $result = $taskHandler->batchSubmit($type, $rate, $operator, $userId);
            if ($result['error'])
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' initData error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $result['error'], $result['message']));
            }
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::result($result['data']));
        }
        
        
        
    }
    
    /**
     * 任务资源
     * @return \yii\web\Response
     */
    public function actionResource()
    {
        $_logs = [];
    
        $projectId = Yii::$app->request->post('project_id', null);
        $dataId = Yii::$app->request->post('data_id', null);
        $_logs['$dataId'] = $dataId;
    
        if (!$dataId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
        }
    
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
    
        $project = Project::find()->where(['id' => $projectId])->with(['category'])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        
        //---------------------------------------------
    
        Data::setTable($project['table_suffix']);
        DataResult::setTable($project['table_suffix']);
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_exist '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_data_not_exist', Yii::t('app', 'task_data_not_exist')));
        }

        $dataResult = DataResult::find()->select(['data','result'])->where(['data_id' => $dataId])->asArray()->limit(1)->one();
    
        $dataArr = JsonHelper::json_decode_all($dataResult['data']);
        $responseData = [];

        if ($dataArr && is_array($dataArr))
        {
            //临时方案, 后期可去掉
            $image_auto_rotate = true;
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                $result = [];
                
                //正常模式
                if (is_string($resourceUrl))
                {
                    $result = ProjectHandler::getResource($resourceUrl, $image_auto_rotate);
                }
                //视频跟踪标注的情况,一个key对应一个数组
                elseif (is_array($resourceUrl))
                {
                    foreach ($resourceUrl as $resourceKey_ => $resourceUrl_)
                    {
                        $result[$resourceKey_] = ProjectHandler::getResource($resourceUrl_, $image_auto_rotate);
                    }
                }
                
                if ($result)
                {
                    $responseData[$resourceKey] = $result;
                }
            }
        }
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  task_resource_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

    /**
     * 生成mark图
     * @return \yii\web\Response
     */
    public function actionMark()
    {
        $_logs = [];
    
        $projectId = Yii::$app->request->post('project_id', null);
        $dataId = Yii::$app->request->post('data_id', null);
        $isShowLabel = (int)Yii::$app->request->post('is_show_label', 1);
        $isFilled = (int)Yii::$app->request->post('is_filled', 0);
        $_logs['$dataId'] = $dataId;
    
        if (!$dataId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
        }
    
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
    
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
    
        //---------------------------------------------
    
        Data::setTable($project['table_suffix']);
        DataResult::setTable($project['table_suffix']);
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_exist '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_data_not_exist', Yii::t('app', 'task_data_not_exist')));
        }
    
        $dataResult = DataResult::find()
        ->select(['data','result'])
        ->where(['data_id' => $dataId])
        ->asArray()->limit(1)->one();
        
        $dataArr = JsonHelper::json_decode_all($dataResult['data']);
        
        $responseData = [];
        if ($dataArr && is_array($dataArr))
        {
            $image_auto_rotate = true;
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                $result = ProjectHandler::showMark($resourceUrl, $dataResult['result'], $isShowLabel, $isFilled, $image_auto_rotate);
                if ($result)
                {
                    $responseData[$resourceKey] = $result;
                }
            }
        }
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionMask()
    {
        $_logs = [];
    
        $projectId = Yii::$app->request->post('project_id', null);
        $dataId = Yii::$app->request->post('data_id', null);
        $_logs['$dataId'] = $dataId;
    
        if (!$dataId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_data_id_not_given', Yii::t('app', 'task_data_id_not_given')));
        }
    
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
    
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
    
        //---------------------------------------------
    
        Data::setTable($project['table_suffix']);
        DataResult::setTable($project['table_suffix']);
    
        $data = Data::find()->where(['id' => $dataId])->asArray()->limit(1)->one();
        if (!$data)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_data_not_exist '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_data_not_exist', Yii::t('app', 'task_data_not_exist')));
        }
    
        $dataResult = DataResult::find()
        ->select(['data','result'])
        ->where(['data_id' => $dataId])
        ->asArray()->limit(1)->one();
    
        $dataArr = JsonHelper::json_decode_all($dataResult['data']);
        
        $responseData = [];
        if ($dataArr && is_array($dataArr))
        {
            $image_auto_rotate = true;
            foreach ($dataArr as $resourceKey => $resourceUrl)
            {
                $result = ProjectHandler::showMask($resourceUrl, $dataResult['result'], $image_auto_rotate);
                if ($result)
                {
                    $responseData[$resourceKey] = $result;
                }
            }
        }
        
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }
    
    public function actionAssignedUserids()
    {
        $_logs = [];
    
        //--------------------------------------
    
        if (Yii::$app->user->identity->type == User::TYPE_ADMIN)
        {
    
        }
        elseif (Yii::$app->user->identity->type == User::TYPE_WORKER)
        {
            $teamId = Yii::$app->user->identity->teamUser->team_id;
            if (!$teamId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_list_userNotJoinTeam '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_list_userNotJoinTeam', Yii::t('app', 'task_list_userNotJoinTeam')));
            }
    
            if (!in_array(AuthItem::ROLE_TEAM_MANAGER, Yii::$app->user->identity->roleKeys))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_nopermission '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_nopermission', Yii::t('app', 'user_nopermission')));
            }
        }
        elseif (Yii::$app->user->identity->type == User::TYPE_CROWDSOURCING)
        {
            $crowdsourcingId = Yii::$app->user->identity->crowdsourcingUser->crowdsourcing_id;
            if (!$crowdsourcingId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_list_userNotJoinCrowdsourcing '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_list_userNotJoinCrowdsourcing', Yii::t('app', 'task_list_userNotJoinCrowdsourcing')));
            }
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_list_userNoPermission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_list_userNoPermission', Yii::t('app', 'task_list_userNoPermission')));
        }
    
        //---------------------------------------------
    
        $taskId = Yii::$app->request->post('task_id', null);
        $_logs['$taskId'] = $taskId;
    
        if (!$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_users_taskIdParamNotFound '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_users_taskIdParamNotFound', Yii::t('app', 'task_users_taskIdParamNotFound')));
        }
    
        $task = Task::find()->where(['id' => $taskId])->asArray()->one();
        if (!$task)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_users_taskNotFound '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_users_taskNotFound', Yii::t('app', 'task_users_taskNotFound')));
        }
    
    
        $userIds = TaskUser::find()->select(['user_id'])->where(['task_id' => $taskId])->asArray()->column();
    
        //推荐用户
        $recomend_userIds = [Yii::$app->user->id];
        $recomend_userList = User::find()->select(['id', 'email', 'nickname'])->where(['in', 'id', $recomend_userIds])->asArray()->all();
    
        $result = [
            'user_ids' => $userIds,
            'count' => count($userIds),
            'recomend_users' => $recomend_userList
        ];
        return $this->asJson(FormatHelper::resultStrongType($result));
    }
    
    public function actionAssignUserList()
    {
        $_logs = [];
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $groupId = Yii::$app->request->post('group_id', null);
        $roleId = Yii::$app->request->post('role_id', null);
        $taskId = Yii::$app->request->post('task_id', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $op = Yii::$app->request->post('op', 'list');
        $siteId = Yii::$app->request->post('site_id', null);
        $_logs['$taskId'] = $taskId;

        //排序
        if (!in_array($orderby, ['id', 'created_at', 'updated_at']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        if($groupId == '')
        {
            $groupId = null;
        }

        $page < 1 && $page = 1;
        $limit < 1 && $limit = 5;
        $offset = ($page-1)*$limit;
        
        //--------------------------------------
        //-------------------------------------
        
        //root权限可操作所有租户信息
        if (Yii::$app->user->identity->type == User::TYPE_ROOT)
        {
        }
        //admin权限可操作本租户信息
        elseif (in_array(Yii::$app->user->identity->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
        {
            if (empty(Yii::$app->user->identity->site))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
            }
            $siteId = Yii::$app->user->identity->site->id;
            if (!$siteId)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
            }
        }
        //其他用户可以看到本租户人员
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
        }
        
        //---------------------------------------
        
        //验证角色
        if (!in_array(AuthItem::ROLE_MANAGER, Yii::$app->user->identity->roleKeys))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
        }
        
        if (!$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }
        
        //int转array
        $taskIds = FormatHelper::param_int_to_array($taskId);
        if (!$taskIds)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }
        
        // 11,12,100001
        $taskCount = Task::find()->where(['in', 'id', $taskIds])->asArray()->count();
        if ($taskCount < count($taskIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_not_found', Yii::t('app', 'task_not_found')));
        }

        //查询所有的人员
        if ($siteId)
        {
            $userIds = SiteUser::find()->select(['user_id'])->where(['site_id' => $siteId])->column();
        }
        else
        {
            $userIds = User::find()->select(['id'])->where(['status' => User::STATUS_ACTIVE])->column();
        }
        
        //$userIds = [];
        //$workUserIds = AuthAssignment::find()->where(['item_name'=>AuthItem::ROLE_WORKER])->select('user_id')->asArray()->column();
        $query = User::find()->where(['status' => User::STATUS_ACTIVE]);
        //$workUserIds && $query->andWhere(['in','id',$workUserIds]);
        $query->andWhere(['in', 'id', $userIds]);
        $allUserIds = $query->orderBy([$orderby => ($sort == 'asc' ? SORT_ASC : SORT_DESC)])->asArray()->column();
        
        if(!empty($keyword))
        {
            $keyword = StringHelper::html_encode($keyword);
            $userQuery = User::find()->select(['id']);
            $userQuery->andFilterWhere([
                'or',
                ['like', 'id', $keyword],
                ['like', 'email', $keyword],
                ['like', 'nickname', $keyword],
            ]);
            $keywordUserIds = $userQuery->asArray()->andWhere(['in','id',$allUserIds])->column();
            $query->andWhere(['in', 'id', $keywordUserIds]);
            
        }
        if($groupId !== null)
        {
            $groupId = (int)$groupId;
            $groupUserIds = GroupUser::find()->select('user_id')->where(['group_id'=>$groupId])->asArray()->column();
            $query->andWhere(['in', 'id', $groupUserIds]);
        }
        if ($roleId)
        {
            if(strpos($roleId, ','))
            {
                $roleId = explode(',', $roleId);
            }
            else
            {
                $roleId = [$roleId];
            }
        }
        else
        {
            $roleId = [AuthItem::ROLE_WORKER, AuthItem::ROLE_MANAGER];
        }

        $roleUserIds = AuthAssignment::find()->select(['user_id'])->andWhere(['in', 'item_name', $roleId])->asArray()->column();
        $query->andWhere(['in', 'id', $roleUserIds]);
        $query->andWhere(['in', 'id', $userIds]);
        $queryUserIds = $query->orderBy([$orderby => ($sort == 'asc' ? SORT_ASC : SORT_DESC)])->asArray()->column();
        //---------------------------------------------
        
        //查询所有任务已分配人员
        $taskUserIds = [];
        if(count($taskIds) > 1)
        {
            foreach ($taskIds as $key => $taskId)
            {
                $oneTaskUserIds = TaskUser::find()->select(['user_id'])->where(['task_id' => $taskId])->asArray()->column();

                //更新任务的分配用户数
                $attributes = [
                    'user_count' => count($oneTaskUserIds)
                ];
                Task::updateAll($attributes, ['id' => $taskId]);

                //为空, 退出，不会有所有任务已分配给同一个人的情况
                if(empty($oneTaskUserIds))
                {
                    $taskUserIds = [];
                    break;
                }


                if(empty($taskUserIds))
                {
                    //第一次为空，赋值
                    $taskUserIds = $oneTaskUserIds;
                }
                else
                {
                    $taskUserIds = array_intersect($taskUserIds, $oneTaskUserIds);
                }

            }
        }
        else
        {
            $taskUserIds = TaskUser::find()->select(['user_id'])->where(['task_id' => $taskId])->asArray()->column();
        }

        $taskUserCount = count($taskUserIds);

        //找到没有分配任务的人员
        $queryUntaskUserIds = array_diff($queryUserIds, $taskUserIds);
        //已分配的人员放置最前边
        $queryTaskUserIds = array_intersect($queryUserIds, $taskUserIds);
        $queryUserIds = array_merge($queryTaskUserIds, $queryUntaskUserIds);

        // if(!empty($userIds))
        // {
        //     $queryUserIds = array_merge($userIds, $queryUserIds);
        // }
        //去除重复的人员
        $queryUserIds = array_unique($queryUserIds);
        //获取当前团队的所有小组
        $groups = Group::find()->select(['id', 'name'])->where(['status' => Group::STATUS_NORMAL])->asArray()->all();

        array_unshift($groups, ['id' => 0, 'name' => Yii::t('app', 'task_group_list_default')]);

        $count = count($queryUserIds);

        //分页
        $users = [];
        $list = [];
        $userIds = array_slice($queryUserIds, $offset, $limit);
        if($userIds)
        {
            //查询用户信息
            $userList = User::find()->where(['in', 'id', $userIds])->with(['roles','group'])->asArray()->all();
            foreach($userList as $key => $val)
            {
                $users[$val['id']] = $val;
                $users[$val['id']]['selected'] = 0;
                if(in_array($val['id'], $taskUserIds))
                {
                    $users[$val['id']]['selected'] = 1;
                }
            }
            
            //重新排序
            foreach($userIds as $key => $id_)
            {
                $list[] = $users[$id_];
            }
        }

        //推荐用户
        $recomend_userIds = [Yii::$app->user->id];
        $recomend_userList = User::find()->select(['id', 'email', 'nickname'])->where(['in', 'id', $recomend_userIds])->asArray()->all();

        $result = [
            'list' => $list,
            'groups' => $groups,
            'count' => $count,
            'recomend_users' => $recomend_userList,
            'roles' => AuthItem::getAdminRoles(),
            'task_user_count' => $taskUserCount,
            'task_user_ids' => $taskUserIds,
            'all_user_ids' => $allUserIds
        ];
        return $this->asJson(FormatHelper::resultStrongType($result));
    }
    
    public function actionAssignUser()
    {
        $_logs = [];
        
        $taskId = Yii::$app->request->post('task_id', null);
        $userId = Yii::$app->request->post('user_id', null);
        $teamId = Yii::$app->request->post('team_id', null);
        $op = Yii::$app->request->post('op', null);
        $_logs['$taskId'] = $taskId;
        
        if (!$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }
        
        $taskIds = FormatHelper::param_int_to_array($taskId);
        if (!$taskIds)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }

        if($teamId)
        {
            $teamId = (int)$teamId;
            if($teamId != Yii::$app->user->identity->teamUser->team_id)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_team_id_not_user_team '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'task_team_id_not_user_team', Yii::t('app', 'task_team_id_not_user_team')));
            }
        }
        else if($userId)
        {
            $userIds = FormatHelper::param_int_to_array($userId);
        }

        if (!$userIds)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }
        
        if (!in_array($op, ['add', 'delete']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
        
        //---------------------------------
        
        $effectiveTaskIds = Task::find()->select(['id'])->where(['in', 'id', $taskIds])->andWhere(['status' => Task::STATUS_NORMAL])->asArray()->column();
        if (count($effectiveTaskIds) < count($taskIds))
        {
            $diffTaskIdStr = implode(',', array_diff($taskIds, $effectiveTaskIds));
            $_logs['$diffTaskIdStr'] = $diffTaskIdStr;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_all_effective '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_not_all_effective', sprintf(Yii::t('app', 'task_not_all_effective'), $diffTaskIdStr)));
        }
        
        if ($op == 'delete')
        {
            foreach ($taskIds as $taskId)
            {
                $ids = TaskUser::find()->select(['id'])->where(['task_id' => $taskId])->andWhere(['in', 'user_id', $userIds])->asArray()->column();
                if ($ids)
                {
                    TaskUser::deleteAll(['in', 'id', $ids]);
                }
            }
        }
        elseif ($op == 'add')
        {
            foreach ($taskIds as $taskId)
            {
                $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
                foreach ($userIds as $userId_)
                {
                    $isExist = TaskUser::find()->where(['task_id' => $taskId, 'user_id' => $userId_])->asArray()->limit(1)->one();
                    if (!$isExist)
                    {
                        $taskUser = new TaskUser();
                        $taskUser->project_id = $task['project_id'];
                        $taskUser->task_id = $taskId;
                        $taskUser->user_id = $userId_;
                        $taskUser->created_at = time();
                        $taskUser->save();
                
                        //发通知
                        Message::sendTaskAssignedUser($userId_, $task['project_id'], $taskId);
                
                    }
                }
            }
        }
        
        $userIds = [];
        $taskUserIds = [];
        foreach ($taskIds as $taskId)
        {
            $userIds[$taskId] = TaskUser::find()->select(['user_id'])->where(['task_id' => $taskId])->asArray()->column();
            
            //更新任务的分配用户数
            $attributes = [
                'user_count' => count($userIds[$taskId])
            ];
            Task::updateAll($attributes, ['id' => $taskId]);

            if(empty($taskUserIds))
            {
                $taskUserIds = $userIds[$taskId];
            }
            else
            {
                $taskUserIds = array_intersect($taskUserIds, $userIds[$taskId]);
            }
        }

        $userIds_ = array_values($taskUserIds);
        $result = [
            'user_ids' => $userIds_,
            'count' => count($userIds_)
        ];
        return $this->asJson(FormatHelper::resultStrongType($result));
    }
    
    public function actionAssignUsers()
    {
        $_logs = [];
        
        $taskId = Yii::$app->request->post('task_id', null);
        $userId = Yii::$app->request->post('user_id', null);
        $_logs['$taskId'] = $taskId;
        $_logs['$userId'] = $userId;
        
        if (!$taskId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }
        $taskIds = FormatHelper::param_int_to_array($taskId);
        if (!$taskIds)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_id_not_given', Yii::t('app', 'task_id_not_given')));
        }

        if (!$userId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }
        $userIds = FormatHelper::param_int_to_array($userId);
        if (!$userIds)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }
        
        //---------------------------------
        $effectiveTaskIds = Task::find()->select(['id'])->where(['in', 'id', $taskIds])->andWhere(['status' => Task::STATUS_NORMAL])->asArray()->column();
        if (count($effectiveTaskIds) < count($taskIds))
        {
            $diffTaskIdStr = implode(',', array_diff($taskIds, $effectiveTaskIds));
            $_logs['$diffTaskIdStr'] = $diffTaskIdStr;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_all_effective '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'task_not_all_effective', sprintf(Yii::t('app', 'task_not_all_effective'), $diffTaskIdStr)));
        }

        //设置当前任务人员
        $transaction = Yii::$app->db->beginTransaction();
        try
        {
            foreach ($taskIds as $taskId)
            {
                $ids = TaskUser::find()->select(['user_id'])->where(['task_id' => $taskId])->asArray()->column();
                $addIds = array_diff($userIds, $ids);
                $deleteIds = array_diff($ids, $userIds);

                if($deleteIds)
                {
                    TaskUser::deleteAll(['and', ['in', 'user_id', $deleteIds], ['task_id' => $taskId]]);
                }
                if($addIds)
                {
                    $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
                    foreach ($addIds as $userId_)
                    {
                        $isExist = TaskUser::find()->where(['task_id' => $taskId, 'user_id' => $userId_])->asArray()->limit(1)->one();
                        if (!$isExist)
                        {
                            $taskUser = new TaskUser();
                            $taskUser->project_id = $task['project_id'];
                            $taskUser->task_id = $taskId;
                            $taskUser->user_id = $userId_;
                            $taskUser->created_at = time();
                            $taskUser->save();
                    
                            //发通知
                            Message::sendTaskAssignedUser($userId_, $task['project_id'], $taskId);
                    
                        }
                    }
                }

                //更新任务的分配用户数
                $attributes = [
                    'user_count' => count($userIds)
                ];
                Task::updateAll($attributes, ['id' => $taskId]);
            }
            $transaction->commit();

            $result = [
                'user_ids' => $userIds,
                'count' => count($userIds)
            ];
            $_logs['$result'] = $result;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task assign user success '.json_encode($_logs));

            return $this->asJson(FormatHelper::resultStrongType($result));
        }
        catch(Exception $e)
        {
           $transaction->rollback();

           $_logs['$e.message'] = $e->getMessage();
           $_logs['$e.code'] = $e->getCode();
           $_logs['$e.line'] = $e->getLine();
           Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task assign user fail '.json_encode($_logs));

           return $this->asJson(FormatHelper::resultStrongType('', 'task_assign_user_fail', Yii::t('app', 'task_assign_user_fail')));
        }
        
    }
}
