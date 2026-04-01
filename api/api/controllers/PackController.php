<?php
/**
 * 文件下载打包
 *
 */

namespace api\controllers;

use Yii;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use yii\web\Controller;
use common\models\Batch;
use common\models\Project;
use common\models\Category;
use common\models\Pack;
use common\models\PackScript;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
use common\models\Step;

class PackController extends Controller
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
    /*
     * 获取脚本列表
     * 
     */
    public function actionForm()
    {
        $_logs = [];
        
        $projectId = Yii::$app->request->post('project_id', null);
        
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
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        
        //查询分类
        $category = Category::find()->where(['id' => $project['category_id']])->asArray()->limit(1)->one();
        $fileType = $category['file_type'];
        $types = [0];
        if($fileType == Category::FILE_TYPE_IMAGE){
            $types[] = 1;
        }elseif($fileType == Category::FILE_TYPE_AUDIO){
            $types[] = 2;
        }elseif($fileType == Category::FILE_TYPE_TEXT){
            $types[] = 3;
        }elseif($fileType == Category::FILE_TYPE_VIDEO){
            $types[] = 4;
        }elseif($fileType == Category::FILE_TYPE_3D){
            $types[] = 5;
        }

        //获取脚本列表
        $packScriptList = PackScript::find()
        ->where(['in','type',$types])
        ->andWhere(['status' => PackScript::STATUS_ENABLE])
        ->orderBy(['id' => SORT_ASC])
        ->asArray()->all();
        
        //打包脚本翻译
        foreach ($packScriptList as $k => $v)
        {
        	if(!empty($v['key']) && is_string($v['key']))
        	{
        		$packScriptList[$k]['name'] = Yii::t('app', $v['key']);
        	}
        }
        
        return $this->asJson(FormatHelper::resultStrongType([
            'pack_scripts' => $packScriptList
        ]));
        
    }
    
    
    /*
     * 下载记录列表
     */
    public function actionList()
    {
        $_logs = [];
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $projectId = Yii::$app->request->post('project_id', null);
        $keyword = Yii::$app->request->post('keyword', null);
        $packStatus = Yii::$app->request->post('pack_status', null);
        $orderby = Yii::$app->request->post('orderby', '');
        $sort = Yii::$app->request->post('sort', null);
        
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 10;
        $offset = ($page-1)*$limit;
        
        //获取所有打包状态
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
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }

        //批次列表
        $batchList = Batch::find()->where(['project_id' => $projectId])->orderBy(['sort' => SORT_ASC])->asArray()->all();

        //排序
        if (!in_array($orderby, ['id', 'pack_end_time', 'pack_start_time', 'created_at', 'sort', 'project_id']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        
        
        //文件下载列表
        $query = Pack::find()->where(['in', 'status', [Pack::STATUS_ENABLE, Pack::STATUS_PAUSED]]);

        $projectId && $query->andWhere(['project_id' => $projectId]);
        if($keyword)
        {
            $keyword = StringHelper::html_encode($keyword);
            
            $projectIds = Project::find()->select(['id'])->where([
                'or',
                ['like', 'id', $keyword],
                ['like', 'name', $keyword],
            ])->asArray()->column();
            
            $query->andWhere(['or',
                ['like', 'id', $keyword],
                ['like', 'project_id', $keyword],
                ['like', 'batch_id', $keyword],
                ['like', 'pack_file', $keyword],
                ['in', 'project_id', $projectIds]
            ]);
        }
        if($packStatus !== null)
        {
            $packStatuses = FormatHelper::param_int_to_array($packStatus);
            if ($packStatuses)
            {
                $query->andWhere(['in', 'pack_status', $packStatuses]);
            }
        }
        
        $count = $query->count();
        $list = $query->orderBy([$orderby => $sort == 'desc' ? SORT_DESC: SORT_ASC, 'id' => SORT_DESC])
        ->with(['batch','project','packScript', 'step'])
        ->offset($offset)->limit($limit)
        ->asArray()->all();
        
        if ($list)
        {
            foreach ($list as $k => $v)
            {
                $list[$k]['pack_file_key'] = StringHelper::base64_encode($v['pack_file']);
                $list[$k]['check_file_key'] = StringHelper::base64_encode($v['check_file']);
                
                $batchIds = [];
                if ($v['batch_id'])
                {
                    $batchIds = [$v['batch_id']];
                }
                elseif ($v['configs'])
                {
                    $configs = json_decode($v['configs'], true);
                    if (isset($configs['batch_id']))
                    {
                        $batchIds = explode(',', $configs['batch_id']);
                    }
                }
                
                $batchList = [];
                if ($batchIds)
                {
                    $batchList = Batch::find()->where(['in', 'id', $batchIds])->asArray()->all();
                }
                
                if(!empty($v['packScript']['key']) && is_string($v['packScript']['key']))
                {
                	$list[$k]['packScript']['name'] = Yii::t('app', $v['packScript']['key']);
                }

                if($v['pack_message'])
                {
                    $list[$k]['pack_message'] = Yii::t('app', $v['pack_message']);
                }

                if(empty($v['step']['name']))
                {
                    $list[$k]['step']['name'] = Step::getType($v['step']['type']);
                }
                
                $list[$k]['batches'] = $batchList;
            }
        }
        
        return $this->asJson(FormatHelper::resultStrongType([
            'list' => $list,
            'count' => $count,
            'pack_statuses' => Pack::getPackStatuses(),
            'status' => Pack::getStatuses(), 
            'batchList' => $batchList
        ]));
    }
    

    public function actionBuild()
    {
        $_logs = [];

        //接收参数
        $projectId = Yii::$app->request->post('project_id', null);
        $batchIds = Yii::$app->request->post('batch_ids', null);
        $stepId = Yii::$app->request->post('step_id', 0);
        $packScriptId = Yii::$app->request->post('pack_script_id', null);
        $configs = Yii::$app->request->post('configs', '');
        $_logs['$projectId'] = $projectId;
        $_logs['$batchIds'] = $batchIds;
        $_logs['$configs'] = $configs;

        //校验参数
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }

        if($configs && !JsonHelper::is_json($configs))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_configs_not_json '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_configs_not_json', Yii::t('app', 'project_configs_not_json')));
        }

        //查询任务信息
        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();

        //校验任务信息
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        if(in_array($project['status'], [Project::STATUS_RELEASING, Project::STATUS_SETTING, Project::STATUS_PREPARING]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_status_not_allow', Yii::t('app', 'project_status_not_allow')));
        }

        $configArr = JsonHelper::json_decode_all($configs);
        
        //获取下一个排序数
        $packSort = time();
        
        if(isset($configArr['download']) && $configArr['download'] == 'project')
        {
            if(!isset($configArr['batch_id']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batch_id_not_given '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'batch_id_not_given', Yii::t('app', 'batch_id_not_given')));
            }
            $pack = new Pack();
            $pack->project_id = $projectId;
            $pack->batch_id = $batchIds;
            $pack->sort = $packSort;
            $pack->step_id = $stepId;
            $pack->user_id = Yii::$app->user->id;
            $pack->pack_script_id = $packScriptId;
            $pack->extension = 0;
            $pack->status = Pack::STATUS_ENABLE;
            $pack->pack_file = '';
            $pack->pack_status = Pack::PACK_STATUS_WAITING;
            $pack->pack_message = '';
            $pack->pack_start_time = 0;
            $pack->pack_end_time = 0;
            $pack->configs = $configs;//{"cnEscape":0}
            $pack->created_at = time();
            $pack->updated_at = time();
            $pack->save();
        }
        else
        {
            if (!empty($batchIds))
            {
                $batchIds = explode(',', $batchIds);
            }
            else
            {
                $batchIds = Batch::find()->select(['id'])->where(['project_id' => $projectId])->asArray()->column();
            }
            $_logs['$batchIds'] = $batchIds;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $batchIds '.json_encode($_logs));

            foreach ($batchIds as $batchId)
            {
                $pack = new Pack();
                $pack->project_id = $projectId;
                $pack->batch_id = $batchId;
                $pack->step_id = $stepId;
                $pack->sort = $packSort;
                $pack->user_id = Yii::$app->user->id;
                $pack->pack_script_id = $packScriptId;
                $pack->extension = 0;
                $pack->status = Pack::STATUS_ENABLE;
                $pack->pack_file = '';
                $pack->pack_status = Pack::PACK_STATUS_WAITING;
                $pack->pack_message = '';
                $pack->pack_start_time = 0;
                $pack->pack_end_time = 0;
                $pack->configs = $configs;//{"cnEscape":0}
                $pack->created_at = time();
                $pack->updated_at = time();
                $pack->save();
            }
        }

        return $this->asJson(FormatHelper::resultStrongType(1));
    }
    
    /*
     * 置顶
     */
    public function actionTop()
    {
        $_logs = [];
        
        $packId = Yii::$app->request->post('pack_id', null);
        $_logs['$packId'] = $packId;
        
        //校验参数
        if(!$packId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_id_not_given', Yii::t('app', 'pack_id_not_given')));
        }
        $packIds = FormatHelper::param_int_to_array($packId);
        if(empty($packIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_id_not_given', Yii::t('app', 'pack_id_not_given')));
        }
        
        //查询待置顶的打包数据
        $packs = Pack::find()
        ->where(['in', 'id', $packIds])
        ->all();
        if(empty($packs))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_not_found', Yii::t('app', 'pack_not_found')));
        }
        
        //检查状态并置顶
        foreach($packs as $pack)
        {
            if($pack->status != Pack::STATUS_ENABLE)
            {
                $_logs['$pack->id'] = $pack->id;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack '.$pack->id.' status not allowed '.json_encode($_logs));
                continue;
            }
            if($pack->pack_status != Pack::PACK_STATUS_WAITING)
            {
                $_logs['$pack->id'] = $pack->id;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_status_not_allowed '.json_encode($_logs));
                continue;
            }
        
            $pack->sort = $pack->sort ? 0 : $pack->created_at;
            $pack->save(false);
            
            $_logs['$pack->sort'] = $pack->sort;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack item '.$pack->id.' top '.json_encode($_logs));
        }
        
        return $this->asJson(FormatHelper::resultStrongType(1));
    }
    
    
    /**
     * 结束打包
     * @return \yii\web\Response
     */
    public function actionStop()
    {
        $_logs = [];
        
        $packId = Yii::$app->request->post('pack_id', null);
        $_logs['$packId'] = $packId;
        
        //检查参数
        if(empty($packId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_id_not_given', Yii::t('app', 'pack_id_not_given')));
        }
        
        //查询待结束的打包记录
        $pack = Pack::find()->where(['id' => $packId])->limit(1)->one();
        if(empty($pack))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_not_found', Yii::t('app', 'pack_not_found')));
        }
        
        //检查状态并结束
        if($pack->status != Pack::STATUS_ENABLE)
        {
            $_logs['$pack->id'] = $pack->id;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_status_not_allowed '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_status_not_allowed', Yii::t('app', 'pack_status_not_allowed')));
        }
        if(!in_array($pack->pack_status, [Pack::PACK_STATUS_RUNNING, Pack::PACK_STATUS_WAITING]))
        {
            $_logs['$pack->id'] = $pack->id;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_status_not_allowed '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_status_not_allowed', Yii::t('app', 'pack_status_not_allowed')));
        }
        
        $pack->pack_status = Pack::PACK_STATUS_STOP;
        $pack->updated_at = time();
        $pack->save(false);
        
        //清理已停止打包的却在运行的进程
//         Pack::cleanUpProcesses();
        
        return $this->asJson(FormatHelper::resultStrongType($packId));
    }
        
    /**
     * 重新打包
     * @return \yii\web\Response
     */
    public function actionRenew()
    {
        $_logs = [];
        
        $packId = Yii::$app->request->post('pack_id', null);
        $_logs['$packId'] = $packId;
        
        //检查参数
        if(empty($packId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_id_not_given', Yii::t('app', 'pack_id_not_given')));
        }
        
        //查询待重新打包的打包记录
        $pack = Pack::find()->where(['id' => $packId])->limit(1)->one();
        if(empty($pack))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_not_found', Yii::t('app', 'pack_not_found')));
        }
        
        //检查状态并重新打包
        if($pack->status != Pack::STATUS_ENABLE)
        {
            $_logs['$pack->id'] = $pack->id;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_status_not_allowed '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_status_not_allowed', Yii::t('app', 'pack_status_not_allowed')));
        }
        if(!in_array($pack->pack_status, [Pack::PACK_STATUS_FAILURE, Pack::PACK_STATUS_SUCCESS, Pack::PACK_STATUS_STOP]))
        {
            $_logs['$pack->id'] = $pack->id;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_status_not_allowed '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'pack_status_not_allowed', Yii::t('app', 'pack_status_not_allowed')));
        }
        $pack->pack_status = Pack::PACK_STATUS_WAITING;
        $pack->pack_item_total = 0;
        $pack->pack_item_succ = 0;
        $pack->pack_item_fail = 0;
        $pack->pack_pid = 0;
        $pack->pack_start_time = 0;
        $pack->pack_end_time = 0;
        $pack->pack_file = '';
        $pack->check_file = '';
        $pack->pack_message = '';
        $pack->sort = time();
        $pack->updated_at = time();
        $pack->save(false);
        
        return $this->asJson(FormatHelper::resultStrongType($packId));
    }
    
}