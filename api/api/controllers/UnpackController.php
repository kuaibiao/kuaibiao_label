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
use common\models\Project;
use common\models\Unpack;
use common\helpers\FormatHelper;
use common\helpers\StringHelper;

class UnpackController extends Controller
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
            //rbac过滤器,判断是否有执行的权限
//             'rbac' => [
//                 'class' => 'common\components\ActionRbacFilter',
//             ],
        ];
    }
       
    /*
     * 解包记录列表
     */
    private function actionList()
    {
        $_logs = [];
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $projectId = Yii::$app->request->post('project_id', null);
        $keyword = Yii::$app->request->post('keyword', null);
        $unpackStatus = Yii::$app->request->post('unpack_status', null);
        $orderby = Yii::$app->request->post('orderby', '');
        $sort = Yii::$app->request->post('sort', null);
        
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 10;
        $offset = ($page-1)*$limit;

        
        //---------------------------------
        
        
        //排序
        if (!in_array($orderby, ['id', 'unpack_end_time', 'unpack_start_time', 'created_at', 'sort']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }
        
        $query = Unpack::find()->where(['in', 'status', [Unpack::STATUS_ENABLE, Unpack::STATUS_DISABLE]]);
        $projectId && $query->andWhere(['project_id' => $projectId]);
        if($keyword)
        {
            $keyword = StringHelper::html_encode($keyword);
            
            $projectIds = Project::find()->select(['id'])->where([
                'or',
                ['like', 'id', $keyword],
                ['like', 'name', $keyword],
            ])->asArray()->column();
            
            $query->andWhere([
                'or',
                ['like', 'id', $keyword],
                ['like', 'project_id', $keyword],
                ['in', 'project_id', $projectIds]
            ]);
        }
        if($unpackStatus !== null)
        {
            $unpackStatus = FormatHelper::splitToArray($unpackStatus);
            if ($unpackStatus)
            {
                $query->andWhere(['in', 'unpack_status', $unpackStatus]);
            }
        }
        
        $count = $query->count();
        $list = $query->orderBy([$orderby => $sort == 'desc'?SORT_DESC:SORT_ASC, 'id' => SORT_DESC])
        ->with(['project'])
        ->offset($offset)->limit($limit)
        ->asArray()->all();
        
        return $this->asJson(FormatHelper::resultStrongType([
            'list' => $list,
            'count' => $count,
            'unpack_statuses' => Unpack::getUnpackStatuses(),
            'status' => Unpack::getStatuses(), 
        ]));
    }
}