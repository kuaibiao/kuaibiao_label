<?php
/**
 * BatchController.php
 * 批次控制器
 * 
 */

namespace api\controllers;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\models\Batch;
use common\models\Project;
use common\components\AccessTokenAuth;
use common\helpers\FormatHelper;
use common\helpers\StringHelper;

class BatchController extends Controller 
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
            // rbac过滤器,判断是否有执行的权限
           'rbac' => [
               'class' => 'common\components\ActionRbacFilter',
            ],
        ];
    }


    /**
     * 批次列表
     * @param   $project_id 项目ID
     * @param   $keyword 关键词
     * @param   $page 页码数
     * @param   $limit 每页条目数
     * @param   $order_by 排序字段
     * @param   $sort 排序规则
     * @return  list
     */
    public function actionBatchs()
    {
        $_logs = [];
        
        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        $projectId = Yii::$app->request->post('project_id', null);
        $status = Yii::$app->request->post('status', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        $_logs['$projectId'] = $projectId;
        
        //处理参数
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 5;
        $offset = ($page-1)*$limit;

        //排序
        if (!in_array($orderby, ['id', 'created_at', 'updated_at', 'sort', 'amount']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        //----------------------
        
        if(!$projectId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_id_not_given', Yii::t('app', 'project_id_not_given')));
        }

        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if(!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('','project_not_found',Yii::t('app', 'project_not_found')));
        }

        //查询语句生成
        $query  = Batch::find()->where(['project_id' => $projectId]);

        //关键词查询
        if ($keyword)
        {
            $keyword = StringHelper::html_encode(trim($keyword));
            $query->andWhere(['or',
                ['like', 'id', $keyword],
                ['like', 'path', $keyword]
            ]);
        }
        if($status !== null)
        {
            $status = (int)$status;
            $query->andWhere(['status' => $status]);
        }

        //执行查询
        $count = $query->count();
        $list = $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->asArray()
            ->all();

        return $this->asJson(FormatHelper::resultStrongType([
            'list'  => $list,
            'count' => $count,
			'statuses' => Batch::getStatuses(),
        ]));
    }

    /**
     * 批次详情
     * @param   $id 批次ID
     * @return  info [<description>]
     */
    public function actionDetail(){
        //客户端检测
        $client     = Yii::$app->request->post();
        $_log['$client']    = $client;
        if(empty($client['id'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' id_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'id_not_given',
                Yii::t('app', 'id_not_given')
            ));
        }

        $batch  = Batch::find()->where(['id' => $client['id']])->asArray()->limit(1)->one();
        if(!$batch){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_batch_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'project_batch_not_found',
                Yii::t('app', 'project_batch_not_found')
            ));
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'info'  => $batch
        ]));
    }

}