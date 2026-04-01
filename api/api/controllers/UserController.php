<?php
namespace api\controllers;

use common\models\GroupUser;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\models\User;
use common\models\UserStat;
use common\models\Message;
use api\models\UpdatePasswordForm;
use api\models\VerifyPasswordForm;
use api\models\VerifyPhoneForm;
use api\models\VerifyEmailForm;
use api\models\UserForm;
use common\models\UserAttribute;
use common\models\UserRecord;
use common\models\AuthItem;
use common\models\Project;
use common\models\Task;
use common\models\Group;
use common\models\Setting;
use common\models\UserFtp;
use common\models\UserDevice;
use common\components\FtpManager;
use common\models\AuthAssignment;
use common\models\Category;
use common\models\Stat;
use common\models\TaskUser;
use common\models\NoticeToPosition;
use common\models\Step;
use common\helpers\FormatHelper;
use common\helpers\StringHelper;
use common\helpers\FileHelper;
use common\models\SiteUser;
use common\models\Site;
/**
 * Site controller
 */
class UserController extends Controller
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

    public function actionIndex()
    {
        $_logs = [];

        //-------------------------------------
        $siteId = 0;
        
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
            
            $types = [User::TYPE_ADMIN, User::TYPE_WORKER];
        }
        //其他用户可以看到本租户人员
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
        }
        
        //---------------------------------------
        
        //本周开始时间
        $weekStartTime = strtotime('this week monday');
        $responseData = ['userTaskCount' => 0, 'userDataCount' => 0, 'userDataWorkCount' => 0, 'userDataFinishMonths' => 0];

        $_logs['$weekStartTime'] = date('Y-m-d H:i:s', $weekStartTime);

        //用户信息
        $user = User::find()
            ->select(User::privateFields())
            ->where(['id' => Yii::$app->user->id])
            ->with(['roles', 'userStat'])
            ->asArray()->limit(1)->one();

        $roleKes = Yii::$app->user->identity->roleKeys;
        //作业员执行作业数
        $dataWorkCount = 0;
        //可执行任务数
        $taskCount = 0;
        //作业总执行量
        $dataAmount = 0;
        //作业员可执行任务数量
        $userTaskCount = 0;
        //个人执行数据量
        $userDataFinishMonths = [];
        
        if(in_array(AuthItem::ROLE_ROOT, $roleKes))
        {
            //用户总量
            $userCount = User::find()->where(['not', ['status' => User::STATUS_DELETED]])->count();
            //活跃用户,本周登录过的用户
            $userActiveCount = User::find()->where(['>', 'updated_at', $weekStartTime])->andWhere(['not', ['status' => User::STATUS_DELETED]])->count();
            //执行中的项目总量
            $projectCount = Project::find()->where(['not', ['status' => Project::STATUS_DELETE]])->count();
            //运行项目：项目状态为作业中的项目
            $projectRunningCount = Project::find()->where(['status' => Project::STATUS_WORKING])->count();
            
            //全部可执行任务作业数
            $taskIds = Task::find()->select(['id'])->where(['status' => Task::STATUS_NORMAL])->asArray()->column();
            
            //全部可执行任务数
            $taskCount = count($taskIds);
            
            $taskList = Task::find()->select(['id', 'batch_id'])->andWhere(['in', 'id', $taskIds])->with(['batch'])->asArray()->all();
            $dataAmount = 0;
            if ($taskList)
            {
                foreach ($taskList as $v)
                {
                    $dataAmount += isset($v['batch']['amount']) ? $v['batch']['amount'] : 0;
                }
            }
            
            //作业总执行数量
            $dataWorkCount = (int)Stat::find()->where(['in', 'task_id', $taskIds])->asArray()->sum('work_count');
            //数据量统计
            $dataStat = [];
            $dataSize = [];
            $fileTypes = Category::find()->select(['file_type'])->andWhere(['status' => Category::STATUS_ENABLE])->groupBy(['file_type'])->asArray()->column();
            foreach ($fileTypes as $fileType_)
            {
                $categorIds_ = Category::find()->where(['file_type' => $fileType_, 'status' => Category::STATUS_ENABLE])->asArray()->column();
                
                //数据量统计
                $dataStat[] = [
                    'count' => (int)Project::find()->andWhere(['in', 'category_id', $categorIds_])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('amount'),
                    'name' => Category::getFileType($fileType_)
                ];
                
                //占用空间统计
                $disk_space = (int)Project::find()->andWhere(['in', 'category_id', $categorIds_])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('disk_space');
                $dataSize[] = [
                    'count' => round($disk_space/1024,3),//默认为G
                    'name' => Category::getFileType($fileType_)
                ];
            }
            
            //项目总览
            $projectStatByCategory = Project::find()->select(['category_id', 'count(*) as count'])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->groupBy(['category_id'])->with(['category'])->asArray()->all();
            if ($projectStatByCategory)
            {
                foreach ($projectStatByCategory as $k => $v)
                {
                    if (empty($v['category']) || $v['category']['status'] == Category::STATUS_DISABLE)
                    {
                        $projectStatByCategory[$k] = null;
                        unset($projectStatByCategory[$k]);
                    }
                    else
                    {
                        //翻译
                        if(!empty($v['category']['key']))
                        {
                            $categoryName = yii::t('app',$v['category']['key']);
                            if(!empty($categoryName))
                            {
                                $projectStatByCategory[$k]['category']['name'] = $categoryName;
                            }
                        }
                    }
                }
            }
            //发布数据量
            $dataPublishMonths = Project::find()
            ->select(['table_suffix', 'sum(amount) as amount'])
            ->andWhere(['not', ['status' => Project::STATUS_DELETE]])
            ->orderBy(['table_suffix' => SORT_DESC])
            ->groupBy(['table_suffix'])
            ->limit(6)
            ->asArray()->all();
            $dataPublishMonths = array_reverse($dataPublishMonths);
            //执行数据量
            $dataFinishMonths = Stat::find()
            ->select(['FROM_UNIXTIME(created_at, "%y%m") as table_suffix', 'SUM(amount) as amount'])
            ->where(['>', 'created_at', 0])
            ->groupBy(['table_suffix'])
            ->orderBy(['table_suffix' => SORT_DESC])
            ->limit(6)
            ->asArray()->all();
            
            $dataFinishMonths = array_reverse($dataFinishMonths);
            $responseData['userCountLimit'] = Yii::$app->params['user_count_limit'];
            $responseData['dataCount'] = (int)($dataAmount - $dataWorkCount);
            $responseData['dataWorkCount'] = $dataWorkCount;
            $responseData['userCount'] = $userCount;
            $responseData['userActiveCount'] = $userActiveCount;
            $responseData['projectCount'] = $projectCount;
            $responseData['projectRunningCount'] = $projectRunningCount;
            $responseData['taskCount'] = (int)$taskCount;
            $responseData['dataStat'] = $dataStat;
            $responseData['dataSize'] = $dataSize;
            $responseData['projectStatByCategory'] = $projectStatByCategory;
            $responseData['dataPublishMonths'] = $dataPublishMonths;
            $responseData['dataFinishMonths'] = $dataFinishMonths;
        }
        elseif($siteId && in_array(AuthItem::ROLE_MANAGER, $roleKes))
        {
            $userIds = SiteUser::find()->select(['user_id'])->where(['site_id' => $siteId])->column();
            
            //用户总量
            $userCount = User::find()->where(['not', ['status' => User::STATUS_DELETED]])->andWhere(['in', 'id', $userIds])->count();
            //活跃用户,本周登录过的用户
            $userActiveCount = User::find()->where(['>', 'updated_at', $weekStartTime])->andWhere(['not', ['status' => User::STATUS_DELETED]])->andWhere(['in', 'id', $userIds])->count();
            //执行中的项目总量
            $projectCount = Project::find()->where(['not', ['status' => Project::STATUS_DELETE]])->andWhere(['site_id' => $siteId])->count();
            //运行项目：项目状态为作业中的项目
            $projectRunningCount = Project::find()->where(['status' => Project::STATUS_WORKING, 'site_id' => $siteId])->count();
            
            //全部可执行任务作业数
            $taskIds = Task::find()->select(['id'])->where(['status' => Task::STATUS_NORMAL, 'platform_site_id' => $siteId])->asArray()->column();
            
            //全部可执行任务数
            $taskCount = count($taskIds);
            
            $taskList = Task::find()->select(['id', 'batch_id'])->andWhere(['in', 'id', $taskIds])->with(['batch'])->asArray()->all();
            $dataAmount = 0;
            if ($taskList)
            {
                foreach ($taskList as $v)
                {
                    $dataAmount += isset($v['batch']['amount']) ? $v['batch']['amount'] : 0;
                }
            }
            
            //作业总执行数量
            $dataWorkCount = (int)Stat::find()->where(['in', 'task_id', $taskIds])->asArray()->sum('work_count');
            //数据量统计
            $dataStat = [];
            $dataSize = [];
            $fileTypes = Category::find()->select(['file_type'])->andWhere(['status' => Category::STATUS_ENABLE])->groupBy(['file_type'])->asArray()->column();
            foreach ($fileTypes as $fileType_)
            {
                $categorIds_ = Category::find()->where(['file_type' => $fileType_, 'status' => Category::STATUS_ENABLE])->asArray()->column();
                
                //数据量统计
                $dataStat[] = [
                    'count' => (int)Project::find()->andWhere(['site_id' => $siteId])->andWhere(['in', 'category_id', $categorIds_])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('amount'),
                    'name' => Category::getFileType($fileType_)
                ];
                
                //占用空间统计
                $disk_space = (int)Project::find()->andWhere(['site_id' => $siteId])->andWhere(['in', 'category_id', $categorIds_])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('disk_space');
                $dataSize[] = [
                    'count' => round($disk_space/1024,3),//默认为G
                    'name' => Category::getFileType($fileType_)
                ];
            }
            
            //项目总览
            $projectStatByCategory = Project::find()->select(['category_id', 'count(*) as count'])->andWhere(['site_id' => $siteId])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->groupBy(['category_id'])->with(['category'])->asArray()->all();
            if ($projectStatByCategory)
            {
                foreach ($projectStatByCategory as $k => $v)
                {
                    if (empty($v['category']) || $v['category']['status'] == Category::STATUS_DISABLE)
                    {
                        $projectStatByCategory[$k] = null;
                        unset($projectStatByCategory[$k]);
                    }
                    else
                    {
                        //翻译
                        if(!empty($v['category']['key']))
                        {
                            $categoryName = yii::t('app',$v['category']['key']);
                            if(!empty($categoryName))
                            {
                                $projectStatByCategory[$k]['category']['name'] = $categoryName;
                            }
                        }
                    }
                }
            }
            //发布数据量
            $dataPublishMonths = Project::find()
            ->select(['table_suffix', 'sum(amount) as amount'])
            ->andWhere(['site_id' => $siteId])
            ->andWhere(['not', ['status' => Project::STATUS_DELETE]])
            ->orderBy(['table_suffix' => SORT_DESC])
            ->groupBy(['table_suffix'])
            ->limit(6)
            ->asArray()->all();
            $dataPublishMonths = array_reverse($dataPublishMonths);
            //执行数据量
            $dataFinishMonths = Stat::find()
            ->select(['FROM_UNIXTIME(created_at, "%y%m") as table_suffix', 'SUM(amount) as amount'])
            ->where(['>', 'created_at', 0])
            ->groupBy(['table_suffix'])
            ->orderBy(['table_suffix' => SORT_DESC])
            ->limit(6)
            ->asArray()->all();
            
            $dataFinishMonths = array_reverse($dataFinishMonths);
            $responseData['userCountLimit'] = Yii::$app->params['user_count_limit'];
            $responseData['dataCount'] = (int)($dataAmount - $dataWorkCount);
            $responseData['dataWorkCount'] = $dataWorkCount;
            $responseData['userCount'] = $userCount;
            $responseData['userActiveCount'] = $userActiveCount;
            $responseData['projectCount'] = $projectCount;
            $responseData['projectRunningCount'] = $projectRunningCount;
            $responseData['taskCount'] = (int)$taskCount;
            $responseData['dataStat'] = $dataStat;
            $responseData['dataSize'] = $dataSize;
            $responseData['projectStatByCategory'] = $projectStatByCategory;
            $responseData['dataPublishMonths'] = $dataPublishMonths;
            $responseData['dataFinishMonths'] = $dataFinishMonths;
        }
        elseif($siteId && in_array(AuthItem::ROLE_WORKER, $roleKes))
        {
            //可执行任务ID
            $taskIds = Task::find()->select(['id'])->where(['status' => Task::STATUS_NORMAL, 'platform_site_id' => $siteId])->asArray()->column();
            
            //作业员分配的任务ID
            $UserTaskIds = TaskUser::find()
                ->select(['task_id'])
                ->where(['user_id' => Yii::$app->user->id])
                ->andWhere(['in', 'task_id', $taskIds])
                ->asArray()->column();

            //作业员可执行任务数量
            $userTaskCount = count($UserTaskIds);

            //个人执行数据量
            $userDataFinishMonths = Stat::find()
                ->select(['FROM_UNIXTIME(created_at, "%y%m") as table_suffix', 'SUM(amount) as amount'])
                ->where(['>', 'created_at', 0])
                ->andWhere(['in','task_id',$UserTaskIds])
                ->groupBy(['table_suffix'])
                ->orderBy(['table_suffix' => SORT_DESC])
                ->limit(6)
                ->asArray()->all();

            //作业总执行数量
            $userDataWorkCount = (int)Stat::find()->where(['in', 'task_id', $UserTaskIds])->asArray()->sum('work_count');

            $taskList = Task::find()->select(['id', 'batch_id'])->andWhere(['in', 'id', $UserTaskIds])->with(['batch'])->asArray()->all();
            $userDataAmount = 0;
            if ($taskList)
            {
                foreach ($taskList as $v)
                {
                    $userDataAmount += isset($v['batch']['amount']) ? $v['batch']['amount'] : 0;
                }
            }

            $responseData['userTaskCount'] = $userTaskCount;
            $responseData['userDataCount'] = (int)($userDataAmount - $userDataWorkCount);
            $responseData['userDataWorkCount'] = $userDataWorkCount;
            $responseData['userDataFinishMonths'] = $userDataFinishMonths;
        }

        $responseData['user'] = $user;
        $responseData['statuses'] = User::getStatuses();
        $responseData['roles'] = AuthItem::getRoles();
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

    /**
     * 获取全站用户列表
     *
     * @return \yii\web\Response
     */
    public function actionUsers()
    {
        $_logs = [];
        
        //接收参数
        $siteId = Yii::$app->request->post('site_id', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', null));
        $status = Yii::$app->request->post('status', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $type = Yii::$app->request->post('type', null);
        $roleId = Yii::$app->request->post('role_id', null);
        $groupId = trim(Yii::$app->request->post('group_id', null));
        $types = [];
        
        //翻页
        ($page < 1 || $page > 1000) && $page = 1;
        ($limit < 1 || $limit > 100000) && $limit = 10;
        $offset = ($page-1)*$limit;

        //排序
        if (!in_array($orderby, ['id', 'created_at']))
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
            
            $types = [User::TYPE_ADMIN, User::TYPE_WORKER];
        }
        //其他用户可以看到本租户人员
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_site', Yii::t('app', 'user_no_site')));
        }
        
        //---------------------------------------

        //构建查询
        $query = User::find()->select(User::privateFields());
        
        if ($siteId)
        {
            $userIds = SiteUser::find()->select(['user_id'])->where(['site_id' => $siteId])->column();
            $query->andWhere(['in', 'id', $userIds]);
        }
        
        if(!empty($groupId))
        {
            $userIds = GroupUser::find()->where(['group_id'=>$groupId])->select('user_id')->asArray()->column();
            $query->andWhere(['in', 'id', $userIds]);
        }

        $statuses = array_keys(User::getStatuses());
        $statuses_ = FormatHelper::param_int_to_array($status);
        $statuses_ = array_intersect($statuses, $statuses_);
        if ($statuses_)
        {
            $query->andWhere(['in', 'status', $statuses_]);
        }
        else
        {
            $query->andWhere(['in', 'status', $statuses]);
        }


        if ($types)
        {
            $query->andWhere(['in', 'type', $types]);
        }

        if ($keyword)
        {
            $query->andFilterWhere([
                'or',
                ['like', 'id', $keyword],
                ['like', 'email', $keyword],
                ['like', 'nickname', $keyword],
            ]);
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

            $roleUserIds = AuthAssignment::find()->select(['user_id'])->andWhere(['in', 'item_name', $roleId])->asArray()->column();
            $query->andWhere(['in', 'id', $roleUserIds]);
        }

        //执行查询
        $count = $query->count();
        $list = [];
        if ($count > 0)
        {
            $list = $query->orderBy([$orderby => $sort == 'asc'? SORT_ASC: SORT_DESC])
            ->offset($offset)->limit($limit)
            ->with(['roles','group', 'site'])
            ->asArray()->all();
        }

        $groups = Group::getGroup();

        return $this->asJson(FormatHelper::resultStrongType([
            'keyword' => $keyword,
            'status' => $status,
            'orderby' => $orderby,
            'sort' => $sort,
            'count' => $count,
            'list' => $list,
            'groups' => $groups,
            'statuses' => User::getStatuses(),
            'roles' => AuthItem::getRoles(),
            'types' => User::getTypes(),
        ]));
    }


    /**
     * 用户详情
     * @return \yii\web\Response
     */
    public function actionDetail()
    {
        $_logs = [];

        //接收参数
        $userId = (int)Yii::$app->request->post('user_id', Yii::$app->user->id);
        $siteId = Yii::$app->request->post('site_id', null);
        
        if (empty($userId))
        {
            $userId = Yii::$app->user->id;
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

        //获取用户信息
        $user = User::find()
        ->select(User::privateFields())
        ->where(['id' => $userId])->with(['roles', 'createdByUser', 'site'])->asArray()->limit(1)->one();
        
        if(empty($user))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_info_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_info_not_found',Yii::t('app', 'user_info_not_found')));
        }
        
        if ($siteId)
        {
            $exist = SiteUser::find()->where(['site_id' => $siteId, 'user_id' => $user['id']])->exists();
            if (!$exist)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_info_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_info_not_found',Yii::t('app', 'user_info_not_found')));
            }
        }

        //获取用户权限
        $permisstions = Yii::$app->authManager->getPermissionsByUser($userId);

        if ($permisstions)
        {
            //过滤无效权限
            $permisstions_ = [];
            foreach($permisstions as $key => $val)
            {
                $permisstions_[] = $key;
            }
            $permisstions = $permisstions_;

            //是否开启crm功能
            if (empty(Setting::getSetting('open_crm')))
            {
                $removePermisstions_ = ['customer/list', 'followup/list', 'customer/statistics'];
                if ($removePermisstions_)
                {
                    foreach ($removePermisstions_ as $val)
                    {
                        if (in_array($val, $permisstions))
                        {
                            $permisstionKey_ = array_search($val, $permisstions);
                            $permisstions[$permisstionKey_] = '';
                            unset($permisstions[$permisstionKey_]);
                        }
                    }
                }
            }

            $permisstions = array_values($permisstions);
        }
        $user['permisstions'] = $permisstions;

        //用户ftp
        $userFtp = UserFtp::find()->where(['user_id' => $userId, 'status' => UserFtp::STATUS_ENABLE])->asArray()->limit(1)->one();
        $user['ftp'] = $userFtp;

        //开启自定义模板
        $user['open_template_diy'] = 1;
        if (empty(Setting::getSetting('open_template_diy')))
        {
            $user['open_template_diy'] = 0;
        }

        //语言
        $user['language_key'] = User::getLanguageKey($user['language']);

        //处理用户隐私
        //['phone'] = Functions::hidePhone($user['phone']);

        //获取用户角色
        $roles = AuthItem::getRoles();

        //站点设置项
        $settings = Setting::getSettings();

        $user['group'] = (object)Group::getUserGroup($userId);
        
        //返回值
        $responseData = [
            'user' => $user,
            'statuses' => User::getStatuses(),
            'languages' => User::getLanguages(),
            'language_keys' => User::getLanguageKeys(),
            'roles' => $roles,
            'settings' => $settings,
        ];

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

    /**
     * 用户实时统计
     * @return \yii\web\Response
     */
	public function actionStat()
	{
	    $_logs = [];
	    
	    $siteId = Yii::$app->request->post('site_id', null);
	    
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
	    
	    $noticeQuery = NoticeToPosition::find()
	    ->select(['id', 'notice_id'])
	    ->where(['<', 'show_start_time', time()])
	    ->andWhere(['>', 'show_end_time', time()])
	    ->andWhere(['status' => NoticeToPosition::STATUS_NORMAL]);

	    $notice = $noticeQuery->orderBy(['id' => SORT_DESC])->with(['notice'])->asArray()->limit(1)->one();
	    
	    $taskNewCount = 0;
	    if ($siteId)
	    {
	        $projectIds = Project::find()->select(['id'])->where(['site_id' => $siteId])->column();
	        if ($projectIds)
	        {
	            $stat = Stat::find()->select(['sum(amount) as amount', 'sum(work_count) as work_count'])->where(['in', 'project_id', $projectIds])->asArray()->one();
	            if (!empty($stat['amount']) || !empty($stat['work_count']))
	            {
	                $taskNewCount = $stat['amount'] - $stat['work_count'];
	            }
	        }
	    }

	    //返回值
	    $responseData = [
	        'new_message_count' => (int)UserStat::fetch(Yii::$app->user->id, 'new_message_count'),
	        'notice' => isset($notice['notice']) ? $notice['notice'] : '',
	        'task_new_count' => $taskNewCount,
	    ];

	    return $this->asJson(FormatHelper::resultStrongType($responseData));
	}

	/**
	 * 获取用户已授权的权限
	 *
	 */
	public function actionAuth()
	{
	    $auth = Yii::$app->authManager;
	    $userAuths = $auth->getPermissionsByUser(Yii::$app->user->id);
	    //var_dump($userAuths);

	    //返回值
	    $responseData = [
	        'auth' => $userAuths
	    ];
	    $_logs['$responseData'] = $responseData;

	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_userAuths_succ '. json_encode($_logs));
	    return $this->asJson(FormatHelper::resultStrongType($responseData));
	}

	public function actionForm()
	{
	    $_logs = [];
	    
	    $siteId = Yii::$app->request->post('site_id', null);
	    
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
	    
	    
	    $groups = Group::find()->select(['id', 'name'])->where(['status' => Group::STATUS_NORMAL])->asArray()->all();
	    
	    if ($siteId)
	    {
	        $sites = [];
	        $roles = AuthItem::getAdminRoles();
	        $types = User::getAdminTypes();
	    }
	    else
	    {
	        $sites = Site::find()->select(['id', 'name'])->asArray()->all();
	        $roles = AuthItem::getRoles();
	        $types = User::getTypes();
	    }
	    
	    $roleGroup = [
	        User::TYPE_ROOT => AuthItem::getRoles(),
	        User::TYPE_ADMIN => AuthItem::getAdminRoles(),
	        User::TYPE_WORKER => AuthItem::getWorkerRoles()
	    ];
	    
	    //返回值
	    $responseData = [
	        'statuses' => User::getStatuses(),
	        'languages' => User::getLanguages(),
	        'groups' => $groups,
	        'roles' => $roles,
	        'types' => $types,
	        'roleGroup' => $roleGroup,
	        'sites' => $sites
	    ];

	    return $this->asJson(FormatHelper::resultStrongType($responseData));
	}

    /**
     * 创建用户
     * @return \yii\web\Response
     */
	public function actionCreate()
	{
	    $_logs = [];

	    $postData = Yii::$app->request->post();
	    $siteId = Yii::$app->request->post('site_id', null);

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
	    
        //用户创建
        $userForm = new UserForm();
        $userForm->load($postData, '');
        $userForm->site_id = $siteId;
        if (!empty($postData['tag_name']))
        {
            $tagNames = explode(',', rtrim($postData['tag_name'], ','));
            $userForm->tags = $tagNames;
        }
        if (!empty($postData['roles']))
        {
            $roles = explode(',', rtrim($postData['roles'], ','));
            $userForm->roles = $roles;
        }
	    if (!$userForm->validate() || !$userForm->save())
	    {
            $errors = $userForm->getFirstErrors();
            $_logs['$model.$user'] = $errors;


            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_create_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
	    }
	    $userId = $userForm->id;

        $userRecord = new UserRecord();
        $result = $userRecord->saveRecord('signup', $userId, '');

	    //返回值
	    $responseData = [
	        'user_id' => $userId,
	    ];
	    $_logs['$responseData'] = $responseData;

	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_create_succ '.json_encode($_logs));
	    return $this->asJson(FormatHelper::resultStrongType($responseData));
	}

    /**
     * 更改用户信息
     * @param   $info 用户信息
     * @param   $team_id 团队ID
     * @param   $roles 角色列表(逗号隔开)
     */

	public function actionUpdate()
	{
	    $_logs = [];

	    $postData = Yii::$app->request->post();
	    $siteId = Yii::$app->request->post('site_id', null);

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

	    if (empty($postData['user_id']))
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
	    }
	    $userId = $postData['user_id'];

	    $user = User::find()->where(['id' => $userId])->limit(1)->one();
	    if (!$user)
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_found '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'user_not_found', Yii::t('app', 'user_not_found')));
	    }
	    
	    //------------------------------------

        $userForm = new UserForm();
        $userForm->load($postData, '');
        $userForm->id = $userId;
        $userForm->site_id = $siteId;
        if (!empty($postData['tag_name']))
        {
			if(!empty($postData['tag_name']))
			{
				$tagNames = explode(',', rtrim($postData['tag_name'], ','));
			}
			else
			{
				$tagNames = [];
			}
            $userForm->tags = $tagNames;
        }
        if (!empty($postData['roles']))
        {
            $roles = explode(',', rtrim($postData['roles'], ','));

            $userForm->roles = $roles;
        }
        if (!$userForm->validate() || !$userForm->save())
        {
            $errors = $userForm->getFirstErrors();
            $_logs['$model.$verifyPasswordForm'] = $errors;


            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_update_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }

        //发送通知
        Message::sendUserUpdateInfo($userId);

	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_update_succ '.json_encode($_logs));
	    return $this->asJson(FormatHelper::resultStrongType(1));
	}

    /**
     * 删除用户
     * @param   $userId
     * @return  boolean
     */
    public function actionDelete()
    {
        $_logs = [];

        $postData = Yii::$app->request->post();

        if(empty($postData['user_id']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }

        $userId = $postData['user_id'];
        $userIds = FormatHelper::param_int_to_array($userId);
        $user = User::find()->where(['in','id',$userIds])->limit(1)->one();
        if (!$user && count($userIds) === count($user))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_info_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_info_not_found', Yii::t('app', 'user_info_not_found')));
        }

        //---------------------------------------------

        //---------------------------------------------

        //把用户状态置为删除, 不删除记录, 不删除其他附属信息, 如所属团队关系等
        $attributes = [
            'status' => User::STATUS_DELETED,
            'updated_at' => time()
        ];
        User::updateAll($attributes, ['in','id',$userIds]);

        //修改小组人数
        $groupIds = GroupUser::find()->select('group_id')->where(['in','user_id',$userIds])->asArray()->column();
        $groupCounts = array_count_values($groupIds);

        foreach ($groupCounts as $k => $v)
        {
            $group = Group::findOne($k);
            $group->count -= $v;
            $group->updated_at = time();
            $group->save();
        }
        GroupUser::deleteAll(['in','user_id',$userIds]);

        $userRecord = new UserRecord();
        foreach($userIds as $uid)
        {
            $_userRecord = clone $userRecord;
            $result = $_userRecord->saveRecord('delete', $uid, '');
        }


        return $this->asJson(FormatHelper::resultStrongType([
            'result'    => true
        ]));
    }

    /**
     * 修改用户密码之验证
     * @return \yii\web\Response
     */
    public function actionUpdatePassword()
    {
        $verifyPasswordForm = new VerifyPasswordForm();

        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;

        if (isset($postData['op']) && $postData['op'] == 'verifyPassword')
        {
            $verifyPasswordForm->load($postData, '');
            if (!$verifyPasswordForm->validate())
            {
                $errors = $verifyPasswordForm->getFirstErrors();
                $error = key($errors);
                $message = current($errors);

                $_logs['$model.$errors'] = $errors;
                $_logs['$model.$error'] = $error;
                $_logs['$model.$message'] = $message;

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }

            //验证密码成功标志
            $uniqueKey = StringHelper::generate_uniqueid();
            $cacheKey = 'user:updatePassword:'.$uniqueKey;
            Yii::$app->cache->set($cacheKey, 1, 1800);

            //返回值
            $responseData = [
                'key' => $uniqueKey,
            ];
            $_logs['$responseData'] = $responseData;

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updatePassword_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType($responseData));
        }
        elseif (isset($postData['op']) && $postData['op'] == 'submitPasswordNew')
        {
            $model = new UpdatePasswordForm();

            $postData = Yii::$app->request->post();
            $_logs['$postData'] = $postData;

            //校验上一步
            if (!isset($postData['key']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' key_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'key_not_given', Yii::t('app', 'key_not_given')));
            }

            //判断是否验证
            $cacheKey = 'user:updatePassword:'.$postData['key'];
            if (!Yii::$app->cache->exists($cacheKey))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cachekey_not_exist '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'cachekey_not_exist', Yii::t('app', 'cachekey_not_exist')));
            }

            $model->load($postData, '');
            if (!$model->validate())
            {
                $errors = $model->getFirstErrors();
                $_logs['$model.$model'] = $errors;

                $error = key($errors);
                $message = current($errors);
                $_logs['$model.$error'] = $error;
                $_logs['$model.$message'] = $message;

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }

            //修改密码
            $model->updatePassword();

            //发通知
            Message::sendUserUpdatePassword(Yii::$app->user->id);

            //记录日志
            UserRecord::updatePasswordRecord(Yii::$app->user->id);

            //返回值
            $responseData = [
                'user_id' => Yii::$app->user->id,
            ];
            $_logs['$responseData'] = $responseData;

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updatePasswordNew_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType($responseData));
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
    }

	/**
	 * Update password.
	 *
	 * @param string $token
	 * @return mixed
	 */
	public function actionUpdatePasswordNew()
	{
	    $_logs = [];
	    $model = new UpdatePasswordForm();

	    $postData = Yii::$app->request->post();
	    $_logs['$postData'] = $postData;

	    //校验上一步
	    if (!isset($postData['key']))
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' key_not_given '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'key_not_given', Yii::t('app', 'key_not_given')));
	    }

	    //判断是否验证
	    $cacheKey = 'user:updatePassword:'.$postData['key'];
	    if (!Yii::$app->cache->exists($cacheKey))
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cachekey_not_exist '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'cachekey_not_exist', Yii::t('app', 'cachekey_not_exist')));
	    }

	    $model->load($postData, '');
	    if (!$model->validate())
	    {
            $errors = $model->getFirstErrors();
            $_logs['$model.$model'] = $errors;


            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_updatePasswordNew_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
	    }

	    //修改密码
	    $model->updatePassword();

	    //发通知
	    Message::sendUserUpdatePassword(Yii::$app->user->id);

	    //返回值
	    $responseData = [
	        'user_id' => Yii::$app->user->id,
	    ];
	    $_logs['$responseData'] = $responseData;

	    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updatePasswordNew_succ '.json_encode($_logs));
	    return $this->asJson(FormatHelper::resultStrongType($responseData));
	}

    /**
     * 修改邮箱之验证
     * @return \yii\web\Response
     */
	private function actionUpdateEmail()
	{
	    $verifyPasswordForm = new VerifyPasswordForm();
	    $verifyPhoneForm = new VerifyPhoneForm();

        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;

        if (isset($postData['op']) && $postData['op'] == 'verifyPassword')
        {
            $verifyPasswordForm->load($postData, '');
            if (!$verifyPasswordForm->validate())
            {
                $errors = $verifyPasswordForm->getFirstErrors();
                $_logs['$model.$verifyPasswordForm'] = $errors;


                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;

                $error = sprintf('user_updateEmail_verifyPassword_%sError', $key);

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
        }
        elseif (isset($postData['op']) && $postData['op'] == 'verifyPhone')
        {
            $verifyPhoneForm->phone = Yii::$app->user->identity->phone;
            $verifyPhoneForm->load($postData, '');
            if (!$verifyPhoneForm->validate())
            {
                $errors = $verifyPasswordForm->getFirstErrors();
                $_logs['$model.$verifyPasswordForm'] = $errors;


                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;

                $error = sprintf('user_updateEmail_verifyPhone_%sError', $key);

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }

        //验证密码成功标志
        $uniqueKey = FormatHelper::microtimetodate();
        $cacheKey = 'user:updateEmail:'.$uniqueKey;
        Yii::$app->cache->set($cacheKey, 1, 1800);

        //返回值
        $responseData = [
            'key' => $uniqueKey,
        ];
        $_logs['$responseData'] = $responseData;

        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updateEmail_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
	}


    /**
     * 修改邮箱
     * @return \yii\web\Response
     */
	private function actionUpdateEmailNew()
	{
	    $_logs = [];
	    
	    //验证规则
	    $verifyEmailForm = new VerifyEmailForm();
	    
        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;
        
        if (!isset($postData['key']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' key_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'key_not_given', Yii::t('app', 'key_not_given')));
        }
        
        //判断是否验证
        $cacheKey = 'user:updateEmail:'.$postData['key'];
        if (!Yii::$app->cache->exists($cacheKey))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cachekey_not_exist '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'cachekey_not_exist', Yii::t('app', 'cachekey_not_exist')));
        }
        
        $verifyEmailForm->load($postData, '');
        if (!$verifyEmailForm->validate())
        {
            $errors = $verifyEmailForm->getFirstErrors();
            $_logs['$model.$verifyEmailForm'] = $errors;


            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_updateEmailNew_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        
        //设置当前的邮箱验证状态
        $attributes = [
            'email' => $verifyEmailForm->email,
            'is_verify_email' => 1,
        ];
        User::updateAll($attributes, ['id' => Yii::$app->user->id]);
        
        Message::sendUserUpdateEmail(Yii::$app->user->id);
        
        //参加补全资料活动
//        $result = ActivityHandler::join('user_fillin_info', Yii::$app->user->id);
//        if ($result['error'])
//        {
//            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updateEmailNew_userFillInInfoError '.json_encode($_logs));
//        }
        
        //返回值
        $responseData = [
            'user_id' => Yii::$app->user->id,
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updateEmailNew_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
	}


    /**
     * 修改手机号之验证
     * @return \yii\web\Response
     */
	private function actionUpdatePhone()
	{
	    $verifyPasswordForm = new VerifyPasswordForm();
	    $verifyPhoneForm = new VerifyPhoneForm();
	    $verifyEmailForm = new VerifyEmailForm();
	    
        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;
        
        if (isset($postData['op']) && $postData['op'] == 'verifyPassword')
        {
            $verifyPasswordForm->load($postData, '');
            if (!$verifyPasswordForm->validate())
            {
                $errors = $verifyPasswordForm->getFirstErrors();
                $_logs['$model.$verifyPasswordForm'] = $errors;


                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;

                $error = sprintf('user_updatePhone_verifyPassword_%sError', $key);

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
        }
        elseif (isset($postData['op']) && $postData['op'] == 'verifyEmail')
        {
            $verifyEmailForm->email = Yii::$app->user->identity->email;
            $verifyEmailForm->load($postData, '');
            if (!$verifyEmailForm->validate())
            {
                $errors = $verifyEmailForm->getFirstErrors();
                $_logs['$model.$verifyEmailForm'] = $errors;

                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;

                $error = sprintf('user_updatePhone_verifyEmail_%sError', $key);

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
        }
        elseif (isset($postData['op']) && $postData['op'] == 'verifyPhone')
        {
            $verifyPhoneForm->phone = Yii::$app->user->identity->phone;
            $verifyPhoneForm->load($postData, '');
            if (!$verifyPhoneForm->validate())
            {
                $errors = $verifyPhoneForm->getFirstErrors();
                $_logs['$model.$verifyPhoneForm'] = $errors;


                $key = key($errors);
                $message = current($errors);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;

                $error = sprintf('user_updatePhone_verifyPhone_%sError', $key);

                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
        
        //验证密码成功标志
        $uniqueKey = FormatHelper::microtimetodate();
        $cacheKey = 'user:updatePhone:'.$uniqueKey;
        Yii::$app->cache->set($cacheKey, 1, 1800);
        
        //返回值
        $responseData = [
            'key' => $uniqueKey,
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updatePhone_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
	}


    /**
     * 修改手机号
     * @return \yii\web\Response
     */
	private function actionUpdatePhoneNew()
	{
	    $_logs = [];
	    
	    //验证规则
	    $verifyPhoneForm = new VerifyPhoneForm();
	    
        $postData = Yii::$app->request->post();
        $_logs['$postData'] = $postData;
        
        if (!isset($postData['key']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' key_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'key_not_given',Yii::t('app', 'key_not_given')));
        }
        
        //判断是否验证
        $cacheKey = 'user:updatePhone:'.$postData['key'];
        if (!Yii::$app->cache->exists($cacheKey))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' cachekey_not_exist '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'cachekey_not_exist', Yii::t('app', 'cachekey_not_exist')));
        }
        
        $verifyPhoneForm->load($postData, '');
        if (!$verifyPhoneForm->validate())
        {
            $errors = $verifyPhoneForm->getFirstErrors();
            $_logs['$model.$verifyPhoneForm'] = $errors;


            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_updatePhoneNew_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        
        //设置当前的邮箱验证状态
        $attributes = [
            'phone' => $verifyPhoneForm->phone,
            'is_verify_phone' => 1,
        ];
        User::updateAll($attributes, ['id' => Yii::$app->user->id]);
        
        Message::sendUserUpdatePhone(Yii::$app->user->id);
        
        //返回值
        $responseData = [
            'user_id' => Yii::$app->user->id,
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_updatePhoneNew_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
	}
	
	/**
	 * 发送短信验证码
	 *
	 * @return \yii\web\Response
	 */
	private function actionSendPhoneCode()
	{
	    $_logs = [];
	    
        $op = Yii::$app->request->post('op', 0);
        $_logs['$op'] = $op;
        
        if ($op == 'update_email')
        {
            $phone = Yii::$app->user->identity->phone;
            
            if (!StringHelper::valid_phone($phone))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_format_error', Yii::t('app', 'phone_format_error')));
            }
            
            $sms_type = 6;
        }
        else if ($op == 'update_phone')
        {
            $phone = Yii::$app->user->identity->phone;
            
            if (!StringHelper::valid_phone($phone))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_format_error', Yii::t('app', 'phone_format_error')));
            }
            
            $sms_type = 3;
        }
        else if ($op == 'update_phone_new')
        {
            $phone = Yii::$app->request->post('phone', 0);
            $_logs['$phone'] = $phone;
            
            if (!StringHelper::valid_phone($phone))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_format_error', 'phone_format_error'));
            }
            
            $isExist = User::find()->where(['phone' => $phone])->exists();
            if ($isExist)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_existed '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_existed', Yii::t('app', 'phone_existed')));
            }
            
            $sms_type = 3;
        }
        else if ($op == 'update_pasword')
        {
            $phone = Yii::$app->user->identity->phone;
            $_logs['$phone'] = $phone;
            
            if (!StringHelper::valid_phone($phone))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phone_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'phone_format_error', Yii::t('app', 'phone_format_error')));
            }
            
            $sms_type = 2;
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
        
        $verifyPhoneForm = new VerifyPhoneForm();
        $verifyPhoneForm->phone = $phone;
        $phoneCodeHash = $verifyPhoneForm->send($sms_type);
        
        //返回值
        $responseData = [
            'user_id' => Yii::$app->user->id,
        ];
        $_logs['$responseData'] = $responseData;
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_sendPhoneCode_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
	}


    /**
     * 发送邮箱验证码
     * @return \yii\web\Response
     */
	private function actionSendEmailCode()
	{
	    $_logs = [];
	    
        $op = Yii::$app->request->post('op', '');
        $_logs['$op'] = $op;
        
        if ($op == 'update_phone')
        {
            if (Yii::$app->user->isGuest)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_login '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_not_login', Yii::t('app', 'user_not_login')));
            }
            
            $email = Yii::$app->user->identity->email;
            
            if (!StringHelper::valid_email($email))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_format_error', Yii::t('app', 'email_format_error')));
            }
            
            $verifyEmailForm = new VerifyEmailForm();
            $verifyEmailForm->email = $email;
            $emailCodeHash = $verifyEmailForm->send('updatePhone');
            
            //返回值
            $responseData = [
                'email' => $email,
            ];
            $_logs['$responseData'] = $responseData;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_sendEmailCode_updatePhone_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType($responseData));
        }
        elseif ($op == 'update_email_new')
        {
            if (Yii::$app->user->isGuest)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_not_login '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_not_login', Yii::t('app','user_not_login')));
            }
            
            $email = Yii::$app->request->post('email', '');
            if (!StringHelper::valid_email($email))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_format_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_format_error', Yii::t('app','email_format_error')));
            }
            
            $isExist = User::find()->where(['email' => $email])->exists();
            if ($isExist)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' email_existed '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'email_existed', Yii::t('app','email_existed')));
            }
            
            $verifyEmailForm = new VerifyEmailForm();
            $verifyEmailForm->email = $email;
            $emailCodeHash = $verifyEmailForm->send('verifyNewEmail');
            
            //返回值
            $responseData = [
                'email' => $email,
            ];
            $_logs['$responseData'] = $responseData;
            
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_sendEmailCode_updateEmailNew_succ '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType($responseData));
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' op_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'op_param_invalid', Yii::t('app', 'op_param_invalid')));
        }
	}

    /**
     * 解析excel(多用户)
     * @param   $url excel地址
     * @return  list
     */
    public function actionImportParse(){
        $client     = Yii::$app->request->post();
        $_logs['$client']    = $client;
        if(empty($client['url'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' excel_url_field_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'excel_url_field_not_found', Yii::t('app', 'excel_url_field_not_found')));
        }

        $file   = Setting::getUploadRootPath() . $client['url'];

        if(!file_exists($file)){
            $_logs['$file']  = $file;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' excel_file_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'excel_file_not_found', Yii::t('app', 'excel_file_not_found')));
        }
        $list   = FileHelper::file_read_excel($file);
        $list = $list[0];//只取第一个sheet
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test list '.json_encode($list));
        if(!in_array('nickname', $list[0]) || !in_array('email', $list[0]) ||
            !in_array('phone', $list[0]) || !in_array('password', $list[0]))
        {
            return $this->asJson(FormatHelper::resultStrongType('','format error', Yii::t('app', 'content_format_error')));
        }

        $fieldCount = count($list[0]);
        $users = [];
        $titles = $list[0];
        array_shift($list);//去掉表头
        foreach($list as $index => $value)
        {
            if(count(array_filter($value)) == 0) //过滤空行数据
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user data invalid '.json_encode($_logs));
                continue;
            }
            $valueCount = count($value);
            if($fieldCount > $valueCount)
            {
                $fields = array_slice($titles, 0, $valueCount);
            }
            else
            {
                $list[$index] = array_slice($list[$index], 0, $fieldCount);
                $fields = $titles;
            }
            $users[$index] = array_combine($fields, $list[$index]);
        }
        if(!$users)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' read_file_error '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'read_file_error', Yii::t('app', 'read_file_error')));
        }

        $dataList = [];
        $users = array_values($users); //重新索引
        foreach($users as $key => $val)
        {
            $dataList[$key] = array_change_key_case($val);
            $dataList[$key]['status'] = User::STATUS_ACTIVE;//默认激活
            $dataList[$key]['role'] = [AuthItem::ROLE_WORKER];//默认团队成员
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'list'  => $dataList,
            'statuses' => User::getStatuses(),
            'roles' => AuthItem::getRoles()
        ]));
    }

    /**
     * 用户导入
     * @param   $team_id 团队ID
     * @param   $username
     * @param   $email
     * @param   $password 密码
     * @return  user
     */
    public function actionImportSubmit(){
        //客户端参数处理
        $client = Yii::$app->request->post();
        $_logs['$client']    = $client;

        $email = StringHelper::html_encode(trim($client['email']));

        //已存在的用户
        $userInfo = User::find()
        ->select(['id', 'email', 'phone', 'password_hash', 'nickname'])
        ->where(['email' => $email])
        ->asArray()->limit(1)->one();
        if($userInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' import_user_existed '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'import_user_existed', Yii::t('app', 'import_user_existed')));
        }

        //检查用户数量是否超出限制
        $userCountLimit = Yii::$app->params['user_count_limit'];
        $userCount = User::find()->where(['status'=>User::STATUS_ACTIVE])->count();
        if($userCount >= $userCountLimit)
        {
            $logs['$userCount'] = $userCount;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.user_count_exceed.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_count_exceed', Yii::t('app', 'user_count_exceed')));
        }

        //创建用户
        $user = new UserForm();
        $user->load($client, '');
        if (!empty($client['roles']))
        {
            $roles = explode(',', rtrim($client['roles'], ','));
            $user->roles = $roles;
        }
        if(!$user->validate() || !$user->save()){
            $errors = $user->getFirstErrors();
            $_logs['$model.$user'] = $errors;


            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('user_userImport_%sError', $key);

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $userId = $user->id;

        $userAttr = new UserAttribute();
        $userAttr->user_id = $userId;
        $userAttr->register_description = '';
        $userAttr->register_files = '';
        $userAttr->save();

        $userStat = new UserStat();
        $userStat->user_id = $userId;
        $userStat->new_message_count = 0;
        $userStat->save();

        $userRecord = new UserRecord();
        $result = $userRecord->saveRecord('import', $userId, '');

        return $this->asJson(FormatHelper::resultStrongType([
            'info'  => $userId
        ]));
    }

    public function actionOpenFtp()
    {
        $_logs = [];
        
        //开启商务只能看到自己的团队
        if (empty(Setting::getSetting('open_ftp')))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' system_not_open_ftp'.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'system_not_open_ftp', Yii::t('app', 'system_not_open_ftp')));
        }
    
        $userId = (int)Yii::$app->request->post('user_id', null);
        $_logs['$userId'] = $userId;
        
        if (empty($userId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given'.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }
    
        $userFtp = UserFtp::find()->where(['user_id' => $userId, 'status' => UserFtp::STATUS_ENABLE])->asArray()->limit(1)->one();
        if (!$userFtp)
        {
            //开通ftp账户
            $result = FtpManager::open($userId);
            if (!$result)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_ftp_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_ftp_fail', Yii::t('app', 'user_ftp_fail')));
            }
        }
        else 
        {
            //检测ftp账户
            $result = FtpManager::check($userId);
            if (!$result)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ftp_check_fail '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'ftp_check_fail', Yii::t('app', 'ftp_check_fail')));
            }
        }
    
        return $this->asJson(FormatHelper::resultStrongType(1));
    }
    
    /**
     * 用户记录
     */
    public function actionRecords()
    {
        $_logs = [];

        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', null));
        $userId = Yii::$app->request->post('user_id', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $date = Yii::$app->request->post('date', date('ym'));

        //翻页
        ($page < 1 || $page > 1000) && $page = 1;
        ($limit < 1 || $limit > 100000) && $limit = 10;
        $offset = ($page-1)*$limit;

        //排序
        if (!in_array($orderby, ['id', 'created_at', 'updated_at']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

        $_logs['$userId'] = $userId;
        if(empty($userId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }

        $user = User::find()->where(['id' => $userId])->limit(1)->one();
        if (!$user)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_info_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_info_not_found', Yii::t('app', 'user_info_not_found')));
        }

        $dates = UserRecord::getAllSuffixes();
        if (!$date)
        {
            $date = end($dates);
        }
        if($date)
        {
            if (!in_array($date, $dates))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_record_date_uncorrect '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'user_record_date_uncorrect ', Yii::t('app', 'user_record_date_uncorrect')));
            }
            UserRecord::setTable($date);
        }
        
        $query = UserRecord::find()->where(['user_id' => $userId]);
        if($keyword)
        {
            $query->andFilterWhere(['like', 'event', $keyword]);
        }

        $list = [];
        $count = $query->count();
        if($count > 0)
        {
            $list = $query->offset($offset)->limit($limit)
                ->orderBy([$orderby => $sort == 'asc' ? SORT_ASC: SORT_DESC] )
                ->asArray()->all();
        }


        return $this->asJson(FormatHelper::resultStrongType([
            'list'  => $list,
            'keyword' => $keyword,
            'orderby' => $orderby,
            'sort' => $sort,
            'count' => $count,
            'dates' => $dates,
            'date' => $date
        ]));
    }

    /**
     * 用户设备
     */
    public function actionDevices()
    {
        $_logs = [];

        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', null));
        $userId = Yii::$app->request->post('user_id', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

        //翻页
        ($page < 1 || $page > 1000) && $page = 1;
        ($limit < 1 || $limit > 100000) && $limit = 10;
        $offset = ($page-1)*$limit;

        //排序
        if (!in_array($orderby, ['id', 'created_at', 'updated_at', 'request_count']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

        $_logs['$userId'] = $userId;
        if(empty($userId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }

        $user = User::find()->where(['id' => $userId])->limit(1)->one();
        if (!$user)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_info_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_info_not_found', Yii::t('app', 'user_info_not_found')));
        }

        $query = UserDevice::find()->where(['user_id' => $userId]);
        if($keyword)
        {
            $query->andFilterWhere(['or',
                ['like', 'device_name', $keyword],
                ['like', 'app_key', $keyword]
                ]);
        }

        $list = [];
        $count = $query->count();
        if($count > 0)
        {
            $list = $query->offset($offset)->limit($limit)
                ->orderBy([$orderby => $sort == 'asc' ? SORT_ASC: SORT_DESC] )
                ->asArray()->all();
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'list'  => $list,
            'keyword' => $keyword,
            'orderby' => $orderby,
            'sort' => $sort,
            'count' => $count
        ]));
    }


}
