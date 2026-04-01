<?php
namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
// use common\models\Batch;
use common\models\Project;
use common\models\Data;
use common\models\Work;
use common\models\DataResult;
use common\models\WorkResult;
use common\helpers\FormatHelper;
use common\models\StatResultData;
use common\models\Stat;
use common\models\Batch;
use common\models\User;
// use common\models\Step;

/**
 * 作业控制器
 */
class DataController extends Controller
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

    /*
     * 作业列表
     *
     */
    public function actionList()
    {
        $_logs = [];
        
        //接收参数
        $siteId = Yii::$app->request->post('site_id', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $projectId = (int)Yii::$app->request->post('project_id', 0);
        $batchId = (int)Yii::$app->request->post('batch_id', 0);
        $dataId = (int)Yii::$app->request->post('data_id', 0);
        $createStartTime = Yii::$app->request->post('create_start_time', null);
        $createEndTime = Yii::$app->request->post('create_end_time', null);
        $updateStartTime = Yii::$app->request->post('update_start_time', null);
        $updateEndTime = Yii::$app->request->post('update_end_time', null);
        // $status = Yii::$app->request->post('status', null);
        
        //--------------------------------------
        
        if (!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }
        
        //排序
        if (!in_array($orderby, ['id', 'sort', 'created_at', 'updated_at']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        //翻页
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 10;
        $offset = ($page-1)*$limit;
        
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
        
        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        if ($siteId && $project['site_id'] != $siteId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_not_found', Yii::t('app', 'project_not_found')));
        }
        
        $batchList = Batch::find()->select(['id', 'name'])->where(['project_id' => $projectId])->asArray()->all();
        $batches = [];
        foreach ($batchList as $v)
        {
            $batches[$v['id']] = $v['name'];
        }
        
        //设置分表
        Data::setTable($project['table_suffix']);
        Work::setTable($project['table_suffix']);
        DataResult::setTable($project['table_suffix']);
        WorkResult::setTable($project['table_suffix']);
        StatResultData::setTable($project['table_suffix']);

        //构建查询
        $query = Data::find()->where(['project_id' => $projectId]);
        $dataId && $query->andWhere(['id' => $dataId]);
        if($createStartTime)
        {
            $query->andWhere(['>=', 'created_at', strtotime($createStartTime)]);
        }
        if($createEndTime)
        {
            $query->andWhere(['<=', 'created_at', strtotime($createEndTime)]);
        }
        if($updateStartTime)
        {
            $query->andWhere(['>=', 'updated_at', strtotime($updateStartTime)]);
        }
        if($updateEndTime)
        {
            $query->andWhere(['<=', 'updated_at', strtotime($updateEndTime)]);
        }
        if($batchId)
        {
            $query->andwhere(['batch_id' => $batchId]);
        }
        if($keyword)
        {
            $query->andWhere([
                'or',
                ['like', 'id', $keyword],
                ['like', 'name', $keyword]
            ]);
        }
        $count = $query->count();
        $list = [];
        if($count > 0)
        {
            $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC]);
            $list = $query->offset($offset)->limit($limit)->asArray()->with(['project', 'batch', 'dataResult'])->all();
            foreach($list as $index => $data)
            {
                $dataStat = StatResultData::fetchData(['project_id' => $projectId, 'data_id' => $data['id']]);
                if($dataStat['error'])
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' data stat error '.json_encode($_logs));
                    return $this->asJson(FormatHelper::resultStrongType('', $dataStat['error'], $dataStat['message']));
                }
                if(isset($dataStat['data'][$data['id']]))
                {
                    $list[$index]['stat'] = $dataStat['data'][$data['id']];
                    $list[$index]['invalid_data_effective_count'] = isset($dataStat['data'][$data['id']]['label_no']['effective_count']) ? $dataStat['data'][$data['id']]['label_no']['effective_count'] : 0;
                }
                else
                {
                    $list[$index]['stat'] = [];
                    $list[$index]['invalid_data_effective_count'] = 0;
                }

                // $work = [];
                // $query = Work::find()->where(['project_id' => $projectId, 'data_id' => $data['id']]);
                // if($status !== null && $status !== '')
                // {
                //     $status = (int)$status;
                //     switch($status)
                //     {
                //         case Work::STATUS_NEW:
                //             $query->andWhere(['status' => Work::STATUS_NEW]);
                //             break;
                //         case Work::STATUS_RECEIVED:
                //             $query->andWhere(['status' => Work::STATUS_RECEIVED]);
                //             break;
                //         case Work::STATUS_EXECUTING:
                //             $query->andWhere(['status' => Work::STATUS_EXECUTING]);
                //             break;
                //         case Work::STATUS_SUBMITED:
                //             $query->andWhere(['status' => Work::STATUS_SUBMITED]);
                //             break;
                //         case Work::STATUS_FINISH:
                //             $query->andWhere(['status' => Work::STATUS_FINISH]);
                //             break;
                //         case Work::STATUS_DELETED:
                //             $query->andWhere(['status' => Work::STATUS_DELETED]);
                //             break;
                //         case Work::STATUS_REFUSED:
                //             $query->andWhere(['status' => Work::STATUS_REFUSED]);
                //             break;
                //         case Work::STATUS_REFUSEDSUBMIT:
                //             $query->andWhere(['status' => Work::STATUS_REFUSEDSUBMIT]);
                //             break;
                //         case Work::STATUS_DIFFICULT:
                //             $query->andWhere(['status' => Work::STATUS_DIFFICULT]);
                //             break;
                //         case Work::STATUS_RESETED:
                //             $query->andWhere(['in', 'status', [Work::STATUS_DELETED]])->andWhere(['in', 'type', [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]]);
                //             break;
                //         case Work::STATUS_GIVE_UP:
                //             $query->andWhere(['in', 'status', [Work::STATUS_DELETED]])->andWhere(['type' => Work::TYPE_GIVEUP]);
                //             break;
                //         case Work::STATUS_TIME_OUT:
                //             $query->andWhere(['in', 'status', [Work::STATUS_DELETED]])->andWhere(['type' => Work::TYPE_TIMEOUT]);
                //             break;
                //         case Work::STATUS_TO_AUDIT:
                //             $query->andWhere(['in', 'status', [Work::STATUS_SUBMITED]]);
                //             $produceStepIds = Step::find()->select(['id'])->where(['project_id' => $projectId, 'type' => Step::TYPE_PRODUCE])->asArray()->column();
                //             $query->andWhere(['in', 'step_id', $produceStepIds]);
                //             break;
                //         case Work::STATUS_TO_ACCEPT:
                //             $query->andWhere(['in', 'status', [Work::STATUS_SUBMITED]])->joinWith(['step' => function($query){
                //                 $query->where(['step.type' => Step::TYPE_AUDIT]); //lite审核之后就是验收
                //             }]);
                //             break;
                //         case Work::STATUS_AUDIT_REFUSE:
                //             $query->andWhere(['in', 'status', [Work::STATUS_DELETED]])->andWhere(['type' => Work::TYPE_AUDITREFUSE]);
                //             break;
                //         case Work::STATUS_AUDIT_RESET:
                //             $query->andWhere(['in', 'status', [Work::STATUS_DELETED]])->andWhere(['type' => Work::TYPE_AUDITRESET]);
                //             break;
                //         case Work::STATUS_PRODUCE_EXECUTING:
                //             $query->andWhere(['in', 'status', [Work::STATUS_EXECUTING]])->joinWith(['step' => function($query){
                //                 $query->where(['step.type' => Step::TYPE_PRODUCE]);
                //             }]);
                //             break;
                //         case Work::STATUS_AUDIT_EXECUTING:
                //             $query->andWhere(['status' => Work::STATUS_RECEIVED])->joinWith(['step' => function($query){
                //                 $query->where(['step.type' => Step::TYPE_AUDIT]);
                //             }]);
                //             break;
                //         case Work::STATUS_ACCEPTANCE_EXECUTING:
                //             $query->andWhere(['status' => Work::STATUS_RECEIVED])->joinWith(['step' => function($query){
                //                 $query->where(['step.type' => Step::TYPE_ACCEPTANCE]);
                //             }]);
                //             break;
                //         case Work::STATUS_ACCEPTANCE_FINISH:
                //             $query->andWhere(['status' => Work::STATUS_SUBMITED])->joinWith(['step' => function($query){
                //                 $query->where(['step.type' => Step::TYPE_ACCEPTANCE]);
                //             }]);
                //             break;
                //     }
                // }

                // $list[$index]['status'] = '';
                // $work = $query->orderBy(['updated_at' => SORT_DESC])->asArray()->limit(1)->one();
                // if($work)
                // {
                //     if($work['status'] == Work::STATUS_DELETED)
                //     {
                //         if(in_array($work['type'], [Work::TYPE_AUDITRESETED, Work::TYPE_FORCERESET]))
                //         {
                //             $list[$index]['status'] = Work::STATUS_RESETED;
                //         }
                //         else if($work['type'] == Work::TYPE_GIVEUP)
                //         {
                //             $list[$index]['status'] = Work::STATUS_GIVE_UP;
                //         }
                //         else if($work['type'] == Work::TYPE_TIMEOUT)
                //         {
                //             $list[$index]['status'] = Work::STATUS_TIME_OUT;
                //         }
                //         else if($work['type'] == Work::TYPE_AUDITREFUSE)
                //         {
                //             $list[$index]['status'] = Work::STATUS_AUDIT_REFUSE;
                //         }
                //         else if($work['type'] == Work::TYPE_AUDITRESET)
                //         {
                //             $list[$index]['status'] = Work::STATUS_AUDIT_RESET;
                //         }
                //     }
                //     else if($work['status'] == Work::STATUS_SUBMITED)
                //     {
                //         $step = Step::find()->where(['id' => $work['step_id']])->asArray()->limit(1)->one();
                //         if($step['type'] == Step::TYPE_PRODUCE)
                //         {
                //             $list[$index]['status'] = Work::STATUS_TO_AUDIT;
                //         }
                //         else if($step['type'] == Step::TYPE_AUDIT)
                //         {
                //             $list[$index]['status'] = Work::STATUS_TO_ACCEPT;
                //         }
                //         else if($step['type'] == Step::TYPE_ACCEPTANCE)
                //         {
                //             $list[$index]['status'] = Work::STATUS_ACCEPTANCE_FINISH;
                //         }
                //     }
                //     else if($work['status'] == Work::STATUS_RECEIVED)
                //     {
                //         $step = Step::find()->where(['id' => $work['step_id']])->asArray()->limit(1)->one();
                //         if($step['type'] == Step::TYPE_AUDIT)
                //         {
                //             $list[$index]['status'] = Work::STATUS_AUDIT_EXECUTING;
                //         }
                //         else if($step['type'] == Step::TYPE_ACCEPTANCE)
                //         {
                //             $list[$index]['status'] = Work::STATUS_ACCEPTANCE_EXECUTING;
                //         }
                //     }
                //     else if($work['status'] == Work::STATUS_EXECUTING)
                //     {
                //         $step = Step::find()->where(['id' => $work['step_id']])->asArray()->limit(1)->one();
                //         if($step['type'] == Step::TYPE_PRODUCE)
                //         {
                //             $list[$index]['status'] = Work::STATUS_PRODUCE_EXECUTING;
                //         }
                //     }
                //     else
                //     {
                //         $list[$index]['status'] = $work['status'];
                //     }
                // }
            }
        }

        $data = [
            'list' => $list,
            'count' => $count,
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'orderby' => $orderby,
            // 'statuses' => Work::getListStatuses(),
            // 'keyword' => $keyword,
            'project_id' => $projectId,
            'batch_id' => $batchId,
            'batches' => $batches,
            'template_label_types' => Stat::getLabelsByTemplate($project['template_id']),
            'label_types' => array_combine(StatResultData::getLabelNames(), StatResultData::getTypes()) //模板标注工具和标注的对应翻译
        ];

        return $this->asJson(FormatHelper::resultStrongType($data));
    }

}
