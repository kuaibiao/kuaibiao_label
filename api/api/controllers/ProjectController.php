<?php
namespace api\controllers;

use common\models\StatUser;
use common\models\TaskUser;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\models\Setting;
use common\models\User;
use common\models\Project;
use common\models\ProjectAttribute;
use common\models\ProjectRecord;
use common\models\Category;
use common\models\Template;
use common\models\Batch;
use common\models\Step;
use common\models\Stat;
use common\models\Task;
use common\models\UserFtp;
use common\components\FtpManager;
use common\models\Unpack;
use common\components\TaskHandler;
use common\models\AuthAssignment;
use common\models\AuthItem;
use common\models\Message;
use common\models\StepGroup;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use Exception;
use common\models\SiteUser;

/**
 * Project controller
 */
class ProjectController extends Controller
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
                'optional' => [],
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

    public function actionForm()
    {
        $categories = Category::find()->where(['status' => Category::STATUS_ENABLE])->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();

        foreach ($categories as $k => $v)
        {
            if(!empty($v['key']))
            {
                $categoryName = yii::t('app',$v['key']);
                if(!empty($categoryName))
                {
                    $categories[$k]['name'] = $categoryName;
                }
            }
        }
    
        return $this->asJson(FormatHelper::resultStrongType([
            'categories' => $categories,
            'statuses' => Project::getStatuses(),
            'assign_types' => Project::getAssignTypes()
        ]));
    }
    
    /**
     * 创建项目,选择分类
     * @return \yii\web\Response
     */
    public function actionCreate()
    {
        $_logs = [];

        //接收参数
        $categoryId = Yii::$app->request->post('category_id', null);
        $siteId = Yii::$app->request->post('site_id', null);
        
        if (empty($categoryId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_category_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_category_id_not_given', Yii::t('app', 'project_category_id_not_given')));
        }
        
        
        //-------------------------------------
        
        //root权限可操作所有租户信息
        if (Yii::$app->user->identity->type == User::TYPE_ROOT)
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

        $category = Category::find()->where(['id' => $categoryId])->andWhere(['status' => Category::STATUS_ENABLE])->asArray()->limit(1)->one();
        if (!$category)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_category_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_category_not_found', Yii::t('app', 'project_category_not_found')));
        }

        //使用默认名称
        $categoryName = Category::getNameById($categoryId);
        $_logs['$categoryName'] = $categoryName;

        //--------------------------

        //创建project记录
        $project = new Project();
        $project->site_id = $siteId;
        $project->name = Project::defaultName($categoryName,Yii::$app->user->id);
        $project->user_id = Yii::$app->user->id;
        $project->category_id = $categoryId;
        $project->start_time = time();
        $project->end_time = time() + 86400*30;
        $project->created_at = time();
        if (!$project->validate())
        {
            $errors = $project->getFirstErrors();
            $key = key($errors);
            $message = current($errors);
            $error = sprintf('project_create_%sError', $key);
            $_logs['$model.$project'] = $errors;
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $project->save();

        //创建project属性记录
        $projectAttribute = new ProjectAttribute();
        $projectAttribute->project_id = $project->id;
        if (!$projectAttribute->validate())
        {
            $errors = $projectAttribute->getFirstErrors();
            $key = key($errors);
            $message = current($errors);
            $error = sprintf('project_create_%sError', $key);
            $_logs['$model.$projectAttribute'] = $errors;
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $projectAttribute->save();

        //保存项目操作记录
        ProjectRecord::operateCreate(ProjectRecord::SCENE_CREATE_CREATE, $project->id);
        
        //创建项目文件夹
        $uploadFilePath = Setting::getUploadfilePath($project->user_id, $project->id);
        $_logs['$uploadFilePath'] = $uploadFilePath;
        $isCreate = FileHelper::touchPath($uploadFilePath);
        if (!$isCreate)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' create_dir_fail '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'create_dir_fail', Yii::t('app', 'create_dir_fail')));
        }
        
        //------------------------------
        
        $produceStepGroup = new StepGroup();
        $produceStepGroup->project_id = $project->id;
        $produceStepGroup->status = StepGroup::STATUS_NORMAL;
        $produceStepGroup->parent_id = 0;
        $produceStepGroup->sort = 0;
        $produceStepGroup->save();
        
        $produceStep = new Step();
        $produceStep->project_id = $project->id;
        $produceStep->step_group_id = $produceStepGroup->id;
        $produceStep->type = Step::TYPE_PRODUCE;
        $produceStep->status = Step::STATUS_NORMAL;
        $produceStep->sort = 0;
        $produceStep->save();
        
        $auditStepGroup = new StepGroup();
        $auditStepGroup->project_id = $project->id;
        $auditStepGroup->status = StepGroup::STATUS_NORMAL;
        $auditStepGroup->parent_id = $produceStepGroup->id;
        $auditStepGroup->sort = 0;
        $auditStepGroup->save();
        
        $auditStep = new Step();
        $auditStep->project_id = $project->id;
        $auditStep->step_group_id = $auditStepGroup->id;
        $auditStep->type = Step::TYPE_AUDIT;
        $auditStep->status = Step::STATUS_NORMAL;
        $auditStep->sort = 0;
        $auditStep->save();
        
        $acceptanceStepGroup = new StepGroup();
        $acceptanceStepGroup->project_id = $project->id;
        $acceptanceStepGroup->status = StepGroup::STATUS_NORMAL;
        $acceptanceStepGroup->parent_id = $auditStepGroup->id;
        $acceptanceStepGroup->sort = 0;
        $acceptanceStepGroup->save();
        
        $acceptanceStep = new Step();
        $acceptanceStep->project_id = $project->id;
        $acceptanceStep->step_group_id = $acceptanceStepGroup->id;
        $acceptanceStep->type = Step::TYPE_ACCEPTANCE;
        $acceptanceStep->status = Step::STATUS_NORMAL;
        $acceptanceStep->sort = 0;
        $acceptanceStep->save();
        
        //------------------------------

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_create_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType([
            'project_id' => $project->id,
        ]));
    }
    
    public function actionSubmit()
    {
        $_logs = [];
        
        $postData = Yii::$app->request->post();
        $siteId = Yii::$app->request->post('site_id', null);
        
        if (empty($postData['project_id']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
        $projectId = $postData['project_id'];
        
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

        //查询任务信息
        $project = Project::findOne($projectId);
        if (empty($project))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        
        //查询任务属性信息
        $projectAttribute = ProjectAttribute::findOne($projectId);
        if (empty($projectAttribute))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_attribute_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_attribute_not_found', Yii::t('app', 'project_attribute_not_found')));
        }

        //-------------------------------------------------

        //选择了上传文件方式
        if (!empty($postData['uploadfile_type']) && in_array($project->status, [Project::STATUS_RELEASING, Project::STATUS_SETTING]) && isset($postData['op']) && $postData['op'] == 'next')
        {
            //判断上传数据
            if ($postData['uploadfile_type'] == ProjectAttribute::UPLOADFILE_TYPE_WEB)
            {
                $uploadfiles = FileHelper::get_dir_files(Setting::getUploadfilePath($project->user_id, $projectId), Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
                if (!$uploadfiles)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_import_file_not_found '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'project_import_file_not_found', Yii::t('app', 'project_import_file_not_found')));
                }
            }
            elseif ($postData['uploadfile_type'] == ProjectAttribute::UPLOADFILE_TYPE_FTP)
            {
                $userftp = UserFtp::find()->where(['user_id' => $project->user_id, 'status' => UserFtp::STATUS_ENABLE])->asArray()->limit(1)->one();
                if (!$userftp)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_ftp_not_open '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'user_ftp_not_open', Yii::t('app', 'user_ftp_not_open')));
                }

            }
            elseif ($postData['uploadfile_type'] == ProjectAttribute::UPLOADFILE_TYPE_SSH)
            {
                $uploadfiles = FileHelper::get_dir_files(Setting::getUploadfilePath($project->user_id, $projectId), Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
                if (!$uploadfiles)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_import_file_not_found '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'project_import_file_not_found', Yii::t('app', 'project_import_file_not_found')));
                }
            }
            else
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_uploadfile_type_invalid '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'project_uploadfile_type_invalid', Yii::t('app', 'project_uploadfile_type_invalid')));
            }
        }
        //-------------------------------------------------
        
        //保存项目信息
        if(!empty($postData['is_start']) && $project->status == Project::STATUS_SETTING)
        {
            $project->status = Project::STATUS_PREPARING;
        }
        else if($project->status == Project::STATUS_RELEASING)
        {
            $project->status = Project::STATUS_SETTING;
        }
        if (!empty($postData['name']))
        {
            $project->name = $postData['name'];
        }
        if (!empty($postData['template_id']))
        {
            $project->template_id = $postData['template_id'];
        }
        if (!empty($postData['start_time']))
        {
            $project->start_time = $postData['start_time'];
        }
        if (!empty($postData['end_time']))
        {
            $project->end_time = $postData['end_time'];
        }
        if (isset($postData['assign_type']) && is_numeric($postData['assign_type']))
        {
            $project->assign_type = $postData['assign_type'];
        }
        $project->updated_at = time();
        if (!$project->validate() || !$project->save())
        {
            $errors = $project->getFirstErrors();
            $key = key($errors);
            $message = current($errors);
            $error = sprintf('project_importData_%sError', $key);
            $_logs['$model.$projectAttribute'] = $errors;
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;
        
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        
        //----------------------------------------------
        
        //保存数据
        if (!empty($postData['uploadfile_type']))
        {
            $projectAttribute->uploadfile_type = $postData['uploadfile_type'];
        }
        if (empty($projectAttribute['batch_config']))
        {
            $batchConfig = [];
            $batchConfig['assign_type'] = 2; //按照数量分批
            $batchConfig['batches'] = [['count' => 999999999, 'name' => $project->name]];
            $projectAttribute->batch_config = json_encode($batchConfig);
        }
        if (!$projectAttribute->validate() || !$projectAttribute->save())
        {
            $errors = $projectAttribute->getFirstErrors();
            $key = key($errors);
            $message = current($errors);
            $error = sprintf('project_importData_%sError', $key);
            $_logs['$model.$projectAttribute'] = $errors;
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;
        
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        
        //----------------------------------------------
        
        //保存项目操作记录
        ProjectRecord::operateCreate(ProjectRecord::SCENE_CREATE_SUBMIT, $project->id);
        
        //----------------------------------------------
        
        //初始化批次信息
        $batch = Batch::find()->where(['project_id' => $projectId])->limit(1)->one();
        if(empty($batch))
        {
            //增加batch新纪录, 为了下一步的批次配置
            $batch = new Batch();
            $batch->project_id = $projectId;
            $batch->name = $project->name;
            $batch->path = md5($project->name.'9999999990');
            $batch->status = Batch::STATUS_WAITING;
            $batch->sort = 0;
            $batch->created_at = time();
            if (!$batch->validate() || !$batch->save())
            {
                $errors = $batch->getFirstErrors();
                $error = key($errors);
                $message = current($errors);
                $_logs['$model.$batch'] = $errors;
                $_logs['$model.$error'] = $error;
                $_logs['$model.$message'] = $message;
        
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
        }
        
        //配置任务
        if(!empty($postData['tasks']))
        {
            foreach ($postData['tasks'] as $k => $task_)
            {
                $_logs['$task_'] = $task_;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
        
                if (!isset($task_['name']) || !isset($task_['receive_count']) || !isset($task_['receive_expire']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_tasks_param_error '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'project_tasks_param_error', Yii::t('app','project_tasks_param_error')));
                }
        
                $task_['receive_count'] = intval($task_['receive_count']);
                if ($task_['receive_count'] < 0)
                {
                    $task_['receive_count'] = 0;
                }
                $task_['receive_expire'] = intval($task_['receive_expire']);
                if ($task_['receive_expire'] < 0)
                {
                    $task_['receive_expire'] = 0;
                }
        
                $task = Task::find()->where(['id' => $task_['id']])->with(['step'])->limit(1)->one();
                if (empty($task))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_tasks_not_found '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'project_tasks_not_found', Yii::t('app','project_tasks_not_found')));
                }
                if (empty($task->step))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_tasks_not_found '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', 'project_tasks_not_found', Yii::t('app','project_tasks_not_found')));
                }
        
                $task->load($task_, '');
                
                $task->platform_site_id = $siteId;
                //$task->platform_type = Task::PLATFORM_TYPE_ADMIN;
                //$task->platform_id = 0;
                
                //未达到结束时间置为正常，包括未达到开始时间
                if(time() <= $project->end_time)
                {
                    $task->status = Task::STATUS_NORMAL;
                }
                $task->sort = intval($projectId . $task->step->sort);
                $task->updated_at = time();
                if (!$task->validate() || !$task->save())
                {
                    $errors = $task->getFirstErrors();
                    $_logs['$model.$step'] = $errors;
        
                    $key = key($errors);
                    $message = current($errors);
                    $error = sprintf('project_setTask_%sError', $key);
                    $_logs['$model.$key'] = $key;
                    $_logs['$model.$message'] = $message;
        
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
                }
        
                //初始化绩效表
                Stat::updateCounter($projectId, $task->batch_id, $task->step_id, []);
        
            }
        }
        
        //-------------------------------------
        
        //更新任务的时间
        $taskList = Task::find()->where(['project_id' => $projectId])->andWhere(['not', ['status' => Task::STATUS_DELETED]])->asArray()->all();
        if ($taskList)
        {
            foreach ($taskList as $task_)
            {
                $attributes = [];
        
                //延后了开始时间
                if ($project->start_time != $task_['start_time'])
                {
                    $attributes['start_time'] = $project->start_time;
                }
        
                //提前了结束时间
                if ($project->end_time != $task_['end_time'])
                {
                    $attributes['end_time'] = $project->end_time;
                    
                    if ($project->end_time > time()) {
                        $attributes['status'] = Task::STATUS_NORMAL;
                    }
                }
                
                if ($attributes)
                {
                    $attributes['updated_at'] = time();
                    Task::updateAll($attributes, ['id' => $task_['id']]);
                }
            }
        }

        //----------------------------------------------
        
        //解包信息
        $unpack = Unpack::find()->where(['project_id' => $projectId, 'status' => Unpack::STATUS_ENABLE])->limit(1)->one();
        if(empty($unpack))
        {
            $unpack = new Unpack();
            $unpack->project_id = $projectId;
            $unpack->user_id = Yii::$app->user->id;
            $unpack->status = Unpack::STATUS_ENABLE;
            $unpack->unpack_status = Unpack::UNPACK_STATUS_DEFAULT;
            $unpack->created_at = time();
            $unpack->updated_at = time();
            $unpack->save();
        }
        //当项目状态为准备中且解包状态为无状态时, 变更解包状态为待解包
        if ($project->status == Project::STATUS_PREPARING && $unpack->status == Unpack::UNPACK_STATUS_DEFAULT)
        {
            //可以开始解包
            $unpack->status = Unpack::STATUS_ENABLE;
            $unpack->unpack_status = Unpack::UNPACK_STATUS_WAITING;
            $unpack->updated_at = time();
            $unpack->save();
        }
        
        //----------------------------------------------

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_submit_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType([
            'project_id' => $project->id,
        ]));
    }

    /**
     * 获取项目的任务信息
     * 
     * @return \yii\web\Response
     */
    private function actionGetTask()
    {
        $_logs = [];
        
        $postData = Yii::$app->request->post();
        
        if (empty($postData['project_id']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app','project_id_not_given')));
        }
        $projectId = $postData['project_id'];
        
        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app','project_not_found')));
        }
        
        if (!in_array($project['status'], [Project::STATUS_SETTING, Project::STATUS_WORKING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app','project_status_not_allow')));
        }
        
        //--------------------
        
        $batch = Batch::find()
            ->where(['project_id' => $projectId])
            ->limit(1)
            ->asArray()
            ->one();
        
        //查询所有分步
        $list = Task::find()
            ->where(['project_id' => $projectId])
            ->andWhere(['not', ['status' => Task::STATUS_DELETED]])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
            ->with(['step'])
            ->asArray()->all();

        if (!$list)
        {
            $userIds = User::find()->select(['id'])->where(['status' => User::STATUS_ACTIVE])->column();
            $userWorkerRoleIds = AuthAssignment::find()->select(['user_id'])->where(['item_name' => AuthItem::ROLE_WORKER])->column();
            $workerIds = array_intersect($userIds, $userWorkerRoleIds); //获取所有作业员ID
            $userManagerRoleIds = AuthAssignment::find()->select(['user_id'])->where(['item_name' => AuthItem::ROLE_MANAGER])->column();
            $managerIds = array_intersect($userIds, $userManagerRoleIds); //获取所有管理员ID

            //创建任务并分配人员
            $transaction = Yii::$app->db->beginTransaction();
            try
            {
                $stepList = Step::find()->where(['project_id' => $projectId,'status' => Step::STATUS_NORMAL])->orderBy(['sort' => SORT_ASC])->asArray()->all();

                foreach ($stepList as $step)
                {
                    $task = new Task();
                    $task->project_id = $projectId;
                    $task->batch_id = $batch['id'];
                    $task->step_id = $step['id'];
                    $task->name = sprintf('%s - %s', $project['name'], Step::getType($step['type']));
                    if($step['type'] == Step::TYPE_PRODUCE) //执行默认领取作业张数
                    {
                        $task->receive_count = Yii::$app->params['task_produce_receive_count'];
                    }
                    else if($step['type'] == Step::TYPE_AUDIT) //审核默认领取作业张数
                    {
                        $task->receive_count = Yii::$app->params['task_audit_receive_count'];
                    }
                    else if($step['type'] == Step::TYPE_ACCEPTANCE) //验收默认领取作业张数
                    {
                        $task->receive_count = Yii::$app->params['task_acceptance_receive_count'];
                    }
                    $task->receive_expire = Yii::$app->params['task_receive_expire'];
                    $task->start_time = $project['start_time'];
                    $task->end_time = $project['end_time'];
                    $task->status = Task::STATUS_NORMAL;
                    $task->sort = intval($projectId . $step['sort']);
                    $task->created_at = time();
                    $task->updated_at = time();
                    $task->save();

                    if($step['type'] == Step::TYPE_PRODUCE || $step['type'] == Step::TYPE_AUDIT)
                    {
                        // 为每个任务分配所有作业员
                        foreach($workerIds as $workerId)
                        {
                            $taskUser = new TaskUser();
                            $taskUser->project_id = $projectId;
                            $taskUser->task_id = $task->id;
                            $taskUser->user_id = $workerId;
                            $taskUser->created_at = time();
                            $taskUser->save();
                    
                            //发通知
                            Message::sendTaskAssignedUser($workerId, $projectId, $task->id);
                        }
                    }
                    else if($step['type'] == Step::TYPE_ACCEPTANCE)
                    {
                        // 为验收任务分配所有管理员
                        foreach($managerIds as $managerId)
                        {
                            $taskUser = new TaskUser();
                            $taskUser->project_id = $projectId;
                            $taskUser->task_id = $task->id;
                            $taskUser->user_id = $managerId;
                            $taskUser->created_at = time();
                            $taskUser->save();
                    
                            //发通知
                            Message::sendTaskAssignedUser($managerId, $projectId, $task->id);
                        }
                    }

                    //更新任务的分配用户数
                    $attributes = [
                        'user_count' => count($workerIds)
                    ];
                    Task::updateAll($attributes, ['id' => $task->id]);
                }

                $transaction->commit();
            }
            catch(Exception $e)
            {
                $transaction->rollback();

                $_logs['$e.message'] = $e->getMessage();
                $_logs['$e.code'] = $e->getCode();
                $_logs['$e.line'] = $e->getLine();
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' create task fail '.json_encode($_logs));
    
                return $this->asJson(FormatHelper::resultStrongType('', 'task_create_fail', Yii::t('app', 'task_create_fail')));
            }
            

            $list = Task::find()
                ->where(['project_id' => $projectId])
                ->andWhere(['not', ['status' => Task::STATUS_DELETED]])
                ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
                ->with(['step'])
                ->asArray()->all();
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'list' => $list,
            'step_types' => Step::getTypes()
        ]));
    }
    
    /**
     * 设置任务
     * 
     * @return \yii\web\Response
     */
    private function actionSetTask()
    {
        $_logs = [];
        
        $postData = Yii::$app->request->post();
        
        if (empty($postData['project_id']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app','project_id_not_given')));
        }
        $projectId = $postData['project_id'];
        
        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app','project_not_found')));
        }
        if (!in_array($project['status'], [Project::STATUS_SETTING, Project::STATUS_WORKING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app','project_status_not_allow')));
        }
        
        //查询分类
        //$category = Category::findOne($project['category_id']);
        
        //----------------------------------
        
        //参数获取
        if (empty($postData['tasks']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_tasks_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_tasks_not_given', Yii::t('app','project_tasks_not_given')));
        }
        $tasks = $postData['tasks'];
        
        foreach ($tasks as $k => $task_)
        {
            $_logs['$task_'] = $task_;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test '.json_encode($_logs));
        
            if (!isset($task_['name']) || !isset($task_['receive_count']) || !isset($task_['receive_expire']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_tasks_param_error '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'project_tasks_param_error', Yii::t('app','project_tasks_param_error')));
            }
            
            $task_['receive_count'] = intval($task_['receive_count']);
            if ($task_['receive_count'] < 0)
            {
                $task_['receive_count'] = 0;
            }
            $task_['receive_expire'] = intval($task_['receive_expire']);
            if ($task_['receive_expire'] < 0)
            {
                $task_['receive_expire'] = 0;
            }
            
            $task = Task::find()->where(['id' => $task_['id']])->with(['step'])->limit(1)->one();
            if (empty($task))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_tasks_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'project_tasks_not_found', Yii::t('app','project_tasks_not_found')));
            }
            if (empty($task->step))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_tasks_not_found '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'project_tasks_not_found', Yii::t('app','project_tasks_not_found')));
            }
            
            //若变更团队, 则清除已分配的人员
//             if(!empty($task->platform_id))
//             {
//                 if($task->platform_id != $task_['platform_id'])
//                 {
//                     TaskUser::deleteAll(['task_id' => $task->id]);
//                     $task->user_count = 0;
//                 }
//             }

            $task->load($task_, '');
            
            //未达到结束时间置为正常，包括未达到开始时间
            if(time() <= $project['end_time'])
            {
                $task->status = Task::STATUS_NORMAL;
            }
            $task->sort = intval($projectId . $task->step->sort);
            $task->updated_at = time();
            if (!$task->validate())
            {
                $errors = $task->getFirstErrors();
                $_logs['$model.$step'] = $errors;
        
                $key = key($errors);
                $message = current($errors);
                $error = sprintf('project_setTask_%sError', $key);
                $_logs['$model.$key'] = $key;
                $_logs['$model.$message'] = $message;
        
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
            }
            $task->save();
            
            //初始化绩效表
            Stat::updateCounter($projectId, $task->batch_id, $task->step_id, []);
            
        }
        
        //判断是否解析完成
        $updateData = [];
        $isUnpack = Unpack::find()->where(['project_id' => $projectId, 'status' => Unpack::STATUS_ENABLE, 'unpack_status' => Unpack::UNPACK_STATUS_SUCCESS])->exists();
        if ($isUnpack)
        {
            $updateData['status'] = Project::STATUS_WORKING;
        }
        $updateData['updated_at'] = time();
        Project::updateAll($updateData, ['id' => $projectId]);
        
        //解包完成, 任务初始化
        if ($isUnpack)
        {
            //执行成功
            $taskList = Task::find()->where(['project_id' => $project['id'], 'status' => Task::STATUS_NORMAL])->asArray()->all();
            if ($taskList)
            {
                foreach ($taskList as $task)
                {
                    //初始化数据
                    $taskHandler = new TaskHandler();
                    $isinit = $taskHandler->init($task['project_id'], $task['batch_id'], $task['step_id'], 1);
                    if (!$isinit)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' TaskHandler init error '.json_encode($_logs));
                        continue;
                    }
                    $taskHandler->initData();
                }
            }
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_setBatch_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType(1));
    }
    
    /**
     * 所有项目列表
     *
     * @return \yii\web\Response
     */
    public function actionProjects()
    {
        $_logs = [];

        //接收参数
        $siteId = Yii::$app->request->post('site_id', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $status = Yii::$app->request->post('status', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $userId = Yii::$app->request->post('user_id', null);
        $categoryId = Yii::$app->request->post('category_id', null);
        $projectId = Yii::$app->request->post('project_id', null);
        $fileType = Yii::$app->request->post('file_type', null);

        //翻页
        ($page < 1 || $page > 1000) && $page = 1;
        ($limit < 1 || $limit > 1000) && $limit = 10;
        $offset = ($page-1)*$limit;
        
        //排序
        if (!in_array($orderby, ['id', 'start_time', 'end_time', 'amount']))
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

        //构建查询
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);

        //分类
        $categoryIds = $query->select(['category_id'])->asArray()->column();
        $categoryIds = array_unique($categoryIds);
        $categories = Category::getNameByIds($categoryIds);
        
        //筛选条件
        if ($status != null)
        {
            $statuses_ = FormatHelper::param_int_to_array($status);
            if ($statuses_)
            {
                $query->andWhere(['in', 'status', $statuses_]);
            }
        }
        else
        {
            $query->andWhere(['in', 'status', array_keys(Project::getStatuses())]);
        }
        
        if ($userId)
        {
            $query->andWhere(['user_id' => $userId]);
        }
        if($fileType !== null)
        {
            $fileTypes = FormatHelper::param_int_to_array($fileType);
            $category_ids = Category::find()->select(['id'])->where(['in' ,'file_type', $fileTypes])->asArray()->column();
            if ($categoryId)
            {
                $categoryIds_ = FormatHelper::param_int_to_array($categoryId);
                $categoryIds_ = array_merge($categoryIds_, $category_ids);
                $category_ids = array_unique($categoryIds_);
            }

            $query->andWhere(['in', 'category_id', $category_ids]);
        }
        else
        {
            if ($categoryId)
            {
                $categoryIds_ = FormatHelper::param_int_to_array($categoryId);
                $query->andWhere(['in', 'category_id', $categoryIds_]);
            }
        }


        if ($projectId)
        {
            $projectIds_ = FormatHelper::param_int_to_array($projectId);
            if ($projectIds_)
            {
                $query->andWhere(['in', 'id', $projectIds_]);
            }
        }


        if ($keyword)
        {
            $userQuery = User::find()->select(['id']);
            $userIds = $userQuery->andWhere([
                'or',
                // ['like', 'email', $keyword],
                ['like', 'nickname', $keyword],
            ])->asArray()->column();

            $query->andFilterWhere([
                'or',
                ['like', 'id', $keyword],
                ['like', 'user_id', $keyword],
                ['like', 'name', $keyword],
                ['in', 'user_id', $userIds],
            ]);
        }

        //执行查询
        $count = $query->count();
        $list = [];
        if ($count > 0)
        {
            $query->select('*')->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
            $list = $query->offset($offset)->limit($limit)->asArray()->with(['user', 'category', 'unpack', 'batches'])->all();
        }
        if($list)
        {
            foreach($list as $key => $val)
            {
                if($val['unpack'])
                {
                    $list[$key]['unpack']['unpack_message'] = Unpack::translate($val['unpack']['unpack_message']);
                }
            }
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'count' => $count,
            'list' => $list,
            'categories' => $categories,
            'statuses' => Project::getStatuses(),
            'unpack_statuses' => Unpack::getUnpackStatuses(),
            'assign_types' => Project::getAssignTypes()
        ]));
    }
    
    /**
     * 项目详情
     * @return \yii\web\Response
     */
    public function actionDetail()
    {
        $_logs = [];

        $projectId = Yii::$app->request->post('project_id');
        $siteId = Yii::$app->request->post('site_id', null);

        if (empty($projectId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
		$_logs['$projectId'] = $projectId;
		
		
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
		
		$query = Project::find();
		$siteId && $query->andWhere(['site_id' => $siteId]);
		
		//查询任务信息
		$project = $query->andWhere(['id' => $projectId])->with(['attr', 'category', 'steps', 'batches', 'tasks', 'user', 'unpack'])->asArray()->limit(1)->one();
		if (!$project)
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found')));
		}
		if (in_array($project['status'], array(Project::STATUS_DELETE)))
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow')));
		}
		//-------------------------------------

        //如果无任务信息,初始化任务
        if(empty($project['tasks']) && $project['status'] == Project::STATUS_SETTING)
        {
            $batch = Batch::find()->where(['project_id' => $projectId])->limit(1)->asArray()->one();
            //查询所有分步
            $taskList = Task::find()->where(['project_id' => $projectId])
                ->andWhere(['not', ['status' => Task::STATUS_DELETED]])
                ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
                ->with(['step'])
                ->asArray()->all();

            if (!$taskList) {
                
                if ($siteId)
                {
                    $userIds = SiteUser::find()->select(['user_id'])->where(['site_id' => $siteId])->column();
                }
                else 
                {
                    $userIds = User::find()->select(['id'])->where(['status' => User::STATUS_ACTIVE])->column();
                }
                
                $userWorkerRoleIds = AuthAssignment::find()->select(['user_id'])->where(['item_name' => AuthItem::ROLE_WORKER])->column();
                $workerIds = array_intersect($userIds, $userWorkerRoleIds); //获取所有作业员ID
                $userManagerRoleIds = AuthAssignment::find()->select(['user_id'])->where(['item_name' => AuthItem::ROLE_MANAGER])->column();
                $managerIds = array_intersect($userIds, $userManagerRoleIds); //获取所有管理员ID

                //创建任务并分配人员
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $stepList = Step::find()->where(['project_id' => $projectId, 'status' => Step::STATUS_NORMAL])->orderBy(['sort' => SORT_ASC])->asArray()->all();

                    foreach ($stepList as $step) {
                        $task = new Task();
                        $task->project_id = $projectId;
                        $task->batch_id = $batch['id'];
                        $task->step_id = $step['id'];
                        $task->name = sprintf('%s - %s', $project['name'], Step::getType($step['type']));
                        if ($step['type'] == Step::TYPE_PRODUCE) //执行默认领取作业张数
                        {
                            $task->receive_count = Yii::$app->params['task_produce_receive_count'];
                        } else if ($step['type'] == Step::TYPE_AUDIT) //审核默认领取作业张数
                        {
                            $task->receive_count = Yii::$app->params['task_audit_receive_count'];
                        } else if ($step['type'] == Step::TYPE_ACCEPTANCE) //验收默认领取作业张数
                        {
                            $task->receive_count = Yii::$app->params['task_acceptance_receive_count'];
                        }
                        $task->receive_expire = Yii::$app->params['task_receive_expire'];
                        $task->start_time = $project['start_time'];
                        $task->end_time = $project['end_time'];
                        $task->status = Task::STATUS_NORMAL;
                        $task->sort = intval($projectId . $step['sort']);
                        $task->created_at = time();
                        $task->updated_at = time();
                        $task->save();

                        if ($step['type'] == Step::TYPE_PRODUCE || $step['type'] == Step::TYPE_AUDIT) {
                            // 为每个任务分配所有作业员
                            foreach (array_merge($managerIds, $workerIds) as $workerId) {
                                $taskUser = new TaskUser();
                                $taskUser->project_id = $projectId;
                                $taskUser->task_id = $task->id;
                                $taskUser->user_id = $workerId;
                                $taskUser->created_at = time();
                                $taskUser->save();

                                //发通知
                                Message::sendTaskAssignedUser($workerId, $projectId, $task->id);
                            }
                            
                            //更新任务的分配用户数
                            $attributes = [
                                'user_count' => count($managerIds) + count($workerIds)
                            ];
                            Task::updateAll($attributes, ['id' => $task->id]);
                        } else if ($step['type'] == Step::TYPE_ACCEPTANCE) {
                            // 为验收任务分配所有管理员
                            foreach ($managerIds as $managerId) {
                                $taskUser = new TaskUser();
                                $taskUser->project_id = $projectId;
                                $taskUser->task_id = $task->id;
                                $taskUser->user_id = $managerId;
                                $taskUser->created_at = time();
                                $taskUser->save();

                                //发通知
                                Message::sendTaskAssignedUser($managerId, $projectId, $task->id);
                            }
                            
                            //更新任务的分配用户数
                            $attributes = [
                                'user_count' => count($managerIds)
                            ];
                            Task::updateAll($attributes, ['id' => $task->id]);
                        }
                    }

                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();

                    $_logs['$e.message'] = $e->getMessage();
                    $_logs['$e.code'] = $e->getCode();
                    $_logs['$e.line'] = $e->getLine();
                    Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' create task fail ' . json_encode($_logs));

                    return $this->asJson(FormatHelper::resultStrongType('', 'task_create_fail', Yii::t('app', 'task_create_fail')));
                }
                $taskList = Task::find()
                    ->where(['project_id' => $projectId])
                    ->andWhere(['not', ['status' => Task::STATUS_DELETED]])
                    ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
                    ->with(['step'])
                    ->asArray()->all();
            }
            $project['tasks'] = $taskList;
        }
        
        //查询流程
		$stepGroupList = StepGroup::find()
		->select(['id', 'project_id', 'status', 'sort', 'parent_id'])
		->where(['project_id' => $projectId, 'status' => StepGroup::STATUS_NORMAL])
		->with(['steps'])
		->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])
		->asArray()->all();
		
		//参与人数
		$statUserCount = StatUser::find()->select(['user_id'])->where(['project_id' => $projectId])->groupBy(['user_id'])->asArray()->count();
		$project['stat']['people_count'] = $statUserCount;
		
		//累计工时
		$statWorkTimeCount = Stat::find()->where(['project_id' => $projectId])->sum('work_time');
		$project['stat']['work_time_count'] = $statWorkTimeCount;
		
        //项目分类名称翻译
        $project['category']['name'] = yii::t('app',$project['category']['key']);
        //------------------------------------

        if ($project['template_id'])
        {
            $project['template'] = Template::findOne($project['template_id']);
        }

        $userftp = UserFtp::find()->where(['user_id' => $project['user_id'], 'status' => UserFtp::STATUS_ENABLE])->asArray()->limit(1)->one();
        if (!$userftp && !empty(Setting::getSetting('open_crm')))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_ftp_not_open '.json_encode($_logs));
            
            //自动开通, 安全原因, 不自动开通
            $result = FtpManager::open($project['user_id']);
            if (!$result)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' FtpManager createUser error '.json_encode($_logs));
            }

            $userftp = UserFtp::find()->where(['user_id' => $project['user_id'], 'status' => UserFtp::STATUS_ENABLE])->asArray()->limit(1)->one();
        }
        
        if ($userftp)
        {
            $userftp['ftp_host'] = rtrim($userftp['ftp_host'], '/').'/'.$project['id'];
        }
        
        //项目空间路径
        $attachmentPath = Setting::getAttachmentPath($project['user_id'], $projectId);
        $attachments = FileHelper::get_dir_files($attachmentPath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::ATTACHMENT_EXTS);
        if($attachments)
        {
            foreach($attachments as $key => $val){
                $path = '/'.$project['user_id'] .'/'. $projectId.'/'.$val['name'];
                $attachments[$key]['key'] = StringHelper::base64_encode($path);
            }
        }

        $projectAttribute = ProjectAttribute::find()->where(['project_id' => $projectId])->limit(1)->one();
        if (!$projectAttribute)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_attribute_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_attribute_not_found', Yii::t('app', 'project_attribute_not_found')));
        }
        if(!empty($projectAttribute->uploadfiles))
        {
            $uploadfiles = json_decode($projectAttribute->uploadfiles, true);
        }
        else 
        {
            $uploadfilePath = Setting::getUploadfilePath($project['user_id'], $projectId);
            $uploadfiles = FileHelper::get_dir_files($uploadfilePath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
            if($uploadfiles)
            {
                foreach($uploadfiles as $key => $val){
                    $path = '/'.$project['user_id'] .'/'. $projectId.'/'.$val['name'];
                    $uploadfiles[$key]['key'] = StringHelper::base64_encode($path);
                }
//              $projectAttribute->uploadfiles = json_encode($uploadfiles);
//              $projectAttribute->save();
            }
        }
        
        //处理解包信息
        if(!empty($project['unpack']))
        {
            $project['unpack']['unpack_message'] = Unpack::translate($project['unpack']['unpack_message']);
        }
        
        $responseData = [
            'project' => $project,
            'statuses' => Project::getStatuses(),
            'attachments' => $attachments,
            'uploadfiles' => $uploadfiles,
            'userftp' => $userftp,
            'uploadfileTypes' => ProjectAttribute::getUploadfileTypes(),
            'attachmentExts' => ProjectAttribute::ATTACHMENT_EXTS,
            'uploadfileExts' => ProjectAttribute::UPLOADFILE_EXTS,
            'unpack_statuses' => Unpack::getUnpackStatuses(),
            'stepGroups' => $stepGroupList,
            'stepTypes' => Step::getTypes(),
            'assign_types' => Project::getAssignTypes()
        ];

        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }


    /**
     * 删除项目
     * @return \yii\web\Response
     */
    public function actionDelete()
    {
        $_logs = [];

        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $siteId = Yii::$app->request->post('site_id', null);
        
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
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

        //查询任务信息
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);
        $project = $query->andWhere(['id' => $projectId])->asArray()->limit(1)->one();
        
        if (empty($project))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found')));
        }
        
        //判断状态, 只限发布中，已停止，已完成
        if (!in_array($project['status'], array(Project::STATUS_RELEASING, Project::STATUS_SETTING,Project::STATUS_PAUSED, Project::STATUS_STOPPED, Project::STATUS_FINISH)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow')));
        }
        //-------------------------------------
        
        //---------------------------------------

        //更改任务状态
        $attributes = [
            'status' => Project::STATUS_DELETE,
            'updated_at' => time()
        ];
        Project::updateAll($attributes, ['id' => $projectId]);
        
        //保存项目操作记录
        ProjectRecord::operateEdit(ProjectRecord::SCENE_EDIT_DELETE, $projectId);
        
        //删除任务
        $attributes = [
            'status' => Task::STATUS_DELETED,
            'updated_at' => time()
        ];
        Task::updateAll($attributes, ['project_id' => $projectId]);
        

        $data = [
            'project_id' => $projectId
        ];
        //处理回跳
        return $this->asJson(FormatHelper::resultStrongType($data));
    }

    /**
     *
     * 暂停项目
     * @return \yii\web\Response
     */
    public function actionPause()
    {
        $_logs = [];

        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $siteId = Yii::$app->request->post('site_id', null);

        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
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
        
        //查询任务信息
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);
        $project = $query->andWhere(['id' => $projectId])->asArray()->limit(1)->one();

        if (empty($project))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found')));
        }
        
        //判断状态, 只限暂停状态
        if (!in_array($project['status'], array(Project::STATUS_WORKING, Project::STATUS_PREPARING)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow')));
        }
        
        //-------------------------------------

        //更改任务状态
        $attributes = [
            'status' => Project::STATUS_PAUSED,
            'updated_at' => time()
        ];
        Project::updateAll($attributes, ['id' => $projectId]);

        //保存项目操作记录
        ProjectRecord::operateEdit(ProjectRecord::SCENE_EDIT_PAUSE, $projectId);
        
        //删除任务
        $attributes = [
            'status' => Task::STATUS_PAUSED,
            'updated_at' => time()
        ];
        Task::updateAll($attributes, ['project_id' => $projectId, 'status' => Task::STATUS_NORMAL]);

        $data = [
            'project_id' => $projectId
        ];
        return $this->asJson(FormatHelper::resultStrongType($data));
    }

    /**
     * 继续项目, 只有暂停可恢复
     * 
     * @return \yii\web\Response
     */
    public function actionContinue()
    {
        $_logs = [];

        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $siteId = Yii::$app->request->post('site_id', null);

        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
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

        //查询任务信息
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);
        $project = $query->andWhere(['id' => $projectId])->asArray()->limit(1)->one();

        //校验任务信息
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }

        //判断状态, 只限暂停状态
        if (!in_array($project['status'], array(Project::STATUS_PAUSED)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app', 'project_status_not_allow')));
        }

        
        //-------------------------------------

        //更改为执行中状态
        $attributes = [
            'status' => Project::STATUS_WORKING,
            'updated_at' => time()
        ];
        Project::updateAll($attributes, ['id' => $projectId]);

        //保存项目操作记录
        ProjectRecord::operateEdit(ProjectRecord::SCENE_EDIT_CONTINUE, $projectId);
        
        
        //删除任务
        $attributes = [
            'status' => Task::STATUS_NORMAL,
            'updated_at' => time()
        ];
        Task::updateAll($attributes, ['project_id' => $projectId, 'status' => Task::STATUS_PAUSED]);

        $data = [
            'project_id' => $projectId
        ];
        //回跳
        return $this->asJson(FormatHelper::resultStrongType($data));
    }
    
    /**
     * 停止项目
     * 只有管理员才有此权限
     * 
     * @return \yii\web\Response
     */
    private function actionStop()
    {
        $_logs = [];
    
        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $_logs['$projectId'] = $projectId;
    
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
    
        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
    
        //校验任务信息
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found')));
        }
    
        $_logs['user_id'] = Yii::$app->user->id;
    
        //校验是否属于商务审核人员
        if (Yii::$app->user->identity->type != User::TYPE_ADMIN)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission ', Yii::t('app', 'user_no_permission')));
        }
    
        //判断状态, 只限暂停状态
        if (!in_array($project['status'], array(Project::STATUS_RELEASING, Project::STATUS_SETTING, Project::STATUS_WORKING, Project::STATUS_PAUSED)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow')));
        }
    
        //更改任务状态
        $attributes = [
            'status' => Project::STATUS_STOPPED,
            'updated_at' => time()
        ];
        Project::updateAll($attributes, ['id' => $projectId]);
        
        //保存项目操作记录
        ProjectRecord::operateEdit(ProjectRecord::SCENE_EDIT_STOP, $projectId);

        $data = [
            'project_id' => $projectId
        ];
        return $this->asJson(FormatHelper::resultStrongType($data));
    }
    
    /**
     * 重启项目, 只有stop后才可以
     *
     * @return \yii\web\Response
     */
    private function actionRestart()
    {
        $_logs = [];
    
        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $_logs['$projectId'] = $projectId;
    
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
    
        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->limit(1)->asArray()->one();
    
        //校验任务信息
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
    
        $_logs['user_id'] = Yii::$app->user->id;
    
        //校验是否属于当前用户及商务审核人员
        if ($project['user_id'] != Yii::$app->user->id && Yii::$app->user->identity->type != User::TYPE_ADMIN)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
        }
    
        //判断状态, 只限暂停状态
        if (!in_array($project['status'], array(Project::STATUS_STOPPED)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app', 'project_status_not_allow')));
        }
    
        //更改执行中状态
        $attributes = [
            'status' => Project::STATUS_WORKING,
            'updated_at' => time()
        ];
        Project::updateAll($attributes, ['id' => $projectId]);
        
        //保存项目操作记录
        ProjectRecord::operateRestart(ProjectRecord::SCENE_RESTART_RESTART, $projectId);
    
        $data = [
            'project_id' => $project['id']
        ];
        return $this->asJson(FormatHelper::resultStrongType($data));
    }


    /**
     * 复制项目
     * @return \yii\web\Response
     */
    public function actionCopy()
    {
        $_logs = [];

        $projectId = Yii::$app->request->post('project_id', null);
        $siteId = Yii::$app->request->post('site_id', null);

        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
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
        
        //查询任务信息
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);
        $project = $query->andWhere(['id' => $projectId])->asArray()->limit(1)->one();

        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        
        //判断状态, 发布中,删除状态不能复制
        if (in_array($project['status'], [Project::STATUS_RELEASING,Project::STATUS_DELETE]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app', 'project_status_not_allow')));
        }
        
        
        
        //-------------------------------------


        $projectNew = new Project();
        $projectNew->site_id = $project['site_id'];
        $projectNew->user_id = $project['user_id'];
        $projectNew->name = Project::defaultName($project['name']);
        $projectNew->category_id = $project['category_id'];
        $projectNew->template_id = $project['template_id'];
        $projectNew->status = Project::STATUS_RELEASING;
        $projectNew->start_time = time();
        $projectNew->end_time = time() + 86400*30;
        $projectNew->created_at = time();
        $projectNew->updated_at = time();
        $projectNew->save();
 
        $projectAttribute = ProjectAttribute::findOne($projectId);

        $projectAttributeNew = new ProjectAttribute();
        $projectAttributeNew->project_id = $projectNew->id;
        $projectAttributeNew->description = $projectAttribute->description;
        $projectAttributeNew->attachment = $projectAttribute->attachment;
        $projectAttributeNew->save();

        //保存项目操作记录
        ProjectRecord::operateCreate(ProjectRecord::SCENE_CREATE_CREATE, $projectNew->id);

        //创建项目文件夹
        $uploadFilePath = Setting::getUploadfilePath($projectNew->user_id, $projectNew->id);
        $_logs['$uploadFilePath'] = $uploadFilePath;
        $isCreate = FileHelper::touchPath($uploadFilePath);
        if (!$isCreate)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' create_dir_fail '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'create_dir_fail', Yii::t('app', 'create_dir_fail')));
        }

        //------------------------------
        
        $produceStepGroup = new StepGroup();
        $produceStepGroup->project_id = $projectNew->id;
        $produceStepGroup->status = StepGroup::STATUS_NORMAL;
        $produceStepGroup->parent_id = 0;
        $produceStepGroup->sort = 0;
        $produceStepGroup->save();
        
        $produceStep = new Step();
        $produceStep->project_id = $projectNew->id;
        $produceStep->step_group_id = $produceStepGroup->id;
        $produceStep->type = Step::TYPE_PRODUCE;
        $produceStep->status = Step::STATUS_NORMAL;
        $produceStep->sort = 0;
        $produceStep->save();
        
        $auditStepGroup = new StepGroup();
        $auditStepGroup->project_id = $projectNew->id;
        $auditStepGroup->status = StepGroup::STATUS_NORMAL;
        $auditStepGroup->parent_id = $produceStepGroup->id;
        $auditStepGroup->sort = 0;
        $auditStepGroup->save();
        
        $auditStep = new Step();
        $auditStep->project_id = $projectNew->id;
        $auditStep->step_group_id = $auditStepGroup->id;
        $auditStep->type = Step::TYPE_AUDIT;
        $auditStep->status = Step::STATUS_NORMAL;
        $auditStep->sort = 0;
        $auditStep->save();
        
        $acceptanceStepGroup = new StepGroup();
        $acceptanceStepGroup->project_id = $projectNew->id;
        $acceptanceStepGroup->status = StepGroup::STATUS_NORMAL;
        $acceptanceStepGroup->parent_id = $auditStepGroup->id;
        $acceptanceStepGroup->sort = 0;
        $acceptanceStepGroup->save();
        
        $acceptanceStep = new Step();
        $acceptanceStep->project_id = $projectNew->id;
        $acceptanceStep->step_group_id = $acceptanceStepGroup->id;
        $acceptanceStep->type = Step::TYPE_ACCEPTANCE;
        $acceptanceStep->status = Step::STATUS_NORMAL;
        $acceptanceStep->sort = 0;
        $acceptanceStep->save();
        
        //--------------------------------------
        //复制上传文件
        $save_path = Setting::getUploadfilePath($project['user_id'], $project['id']);
        $new_save_path = Setting::getUploadfilePath($projectNew->user_id, $projectNew->id);
        
        $_logs['$save_path'] = $save_path;
        $_logs['$new_save_path'] = $new_save_path;
        
        $uploadfiles = FileHelper::get_dir_files($save_path, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS1);
        
        $_logs['$uploadfiles'] = $uploadfiles;
        
        if ($uploadfiles)
        {
            foreach ($uploadfiles as $uploadfile) {
                
                FileHelper::copyFileToDir($uploadfile['fullpath'], $new_save_path);
            }
        }
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        $data = [
            'project_id' => $projectNew->id
        ];
        return $this->asJson(FormatHelper::resultStrongType($data));
    }
    
    /**
     *
     * 设置已完成
     * @return \yii\web\Response
     */
    public function actionFinish()
    {
        $_logs = [];

        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $siteId = Yii::$app->request->post('site_id', null);

        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
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
        
        //查询任务信息
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);
        $project = $query->andWhere(['id' => $projectId])->asArray()->limit(1)->one();

        //校验任务信息
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }

        //判断状态, 只限作业中状态
        if (!in_array($project['status'], array(Project::STATUS_WORKING)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app', 'project_status_not_allow')));
        }

        //-------------------------------------

        //更改任务状态
        $attributes = [
            'status' => Project::STATUS_FINISH,
            'updated_at' => time()
        ];
        Project::updateAll($attributes, ['id' => $projectId]);

        //保存项目操作记录
        ProjectRecord::operateFinish(ProjectRecord::SCENE_FINISH_FINISH, $projectId);
        
        //项目下的任务更改为已完成
        $attributes = [
            //'start_time' => $project['start_time'],
            //'end_time' => $project['end_time'],
            'status' => Task::STATUS_FINISH,
            'updated_at' => time()
        ];
        Task::updateAll($attributes, ['project_id' => $projectId, 'status' => Task::STATUS_NORMAL]);

        $data = [
            'project_id' => $projectId
        ];
        return $this->asJson(FormatHelper::resultStrongType($data));
    }

    /**
     * 完成的项目重新开启
     *
     *
     * @return \yii\web\Response
     */
    public function actionRecovery()
    {
        $_logs = [];
    
        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $siteId = Yii::$app->request->post('site_id', null);
    
        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
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
        
        //查询任务信息
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);
        $project = $query->andWhere(['id' => $projectId])->asArray()->limit(1)->one();
    
        //校验任务信息
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found ', Yii::t('app', 'project_not_found')));
        }
    
        //判断状态, 只限暂停状态
        if (!in_array($project['status'], array(Project::STATUS_FINISH)))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow ', Yii::t('app', 'project_status_not_allow')));
        }

        //-------------------------------------
        $startTime = time();
        $endTime = time() + 86400*30;
    
        //更改执行中状态
        $attributes = [
            'status' => Project::STATUS_WORKING,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'updated_at' => time()
        ];
        Project::updateAll($attributes, ['id' => $projectId]);


        //保存项目操作记录
        ProjectRecord::operateRestart(ProjectRecord::SCENE_RESTART_RESTART, $projectId);
    
        //项目下的任务更改为正常
        $attributes = [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => Task::STATUS_NORMAL,
            'updated_at' => time()
        ];
        Task::updateAll($attributes, ['project_id' => $projectId, 'status' => Task::STATUS_FINISH]);
    
        $data = [
            'project_id' => $projectId
        ];
        return $this->asJson(FormatHelper::resultStrongType($data));
    }

    /**
     * 项目操作记录
     *
     */
    public function actionRecords()
    {
        $_logs = [];
    
        //接收参数
        $siteId = Yii::$app->request->post('site_id', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
    
        if(!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id', Yii::t('app', 'project_id')));
        }
        $_logs['$projectId'] = $projectId;
    
        //翻页
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 10;
        $offset = ($page-1)*$limit;
        
        //排序
        if (!in_array($orderby, ['id', 'start_time', 'end_time', 'created_at', 'updated_at']))
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
        
        //查询任务信息
        $query = Project::find();
        $siteId && $query->andWhere(['site_id' => $siteId]);
        $project = $query->andWhere(['id' => $projectId])->asArray()->limit(1)->one();
        
        if(!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }

        if(empty($project['table_suffix']))
        {
            $_logs['$project.table_suffix'] = $project;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_record_suffix_not_found '. json_encode($_logs));
            return false;
        }
        ProjectRecord::setTable($project['table_suffix']);

        //构建查询
        $query = ProjectRecord::find()->where(['project_id' => $projectId]);
        if ($keyword)
        {
            $ids = User::find()->select(['id'])->where([ 'or',
                ['like', 'id', $keyword],
                ['like', 'email', $keyword],
                ['like', 'phone', $keyword],
                ['like', 'nickname', $keyword],])->asArray()->column();
            $query->andWhere([
                'in', 'created_by', $ids
            ]);
        }
        $count = $query->count();
        $list = [];
        if($count > 0)
        {
            $list = $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
                ->offset($offset)->limit($limit)->with(['user','userAuth'])->asArray()->all();
        }
        $data = [
            'orderby' => $orderby,
            'sort' => $sort,
            'page' => $page,
            'limit' => $limit,
            'list' => $list,
            'count' => $count,
            'project_id' => $projectId,
            'types' => ProjectRecord::getTypes(),
            'scence' => ProjectRecord::getScenes(),
            'roles' => AuthItem::getRoles()
        ];
    
        return $this->asJson(FormatHelper::resultStrongType($data));
    }
}