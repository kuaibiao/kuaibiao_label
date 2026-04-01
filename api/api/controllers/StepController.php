<?php
/**
 * StepController.php
 * 流程控制器
 * 
 * @author  yuyongshi <768635996@qq.com>
 * @date    2018-12-29
 */

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\models\StepGroup;
use common\models\User;
use common\helpers\FormatHelper;

class StepController extends Controller {
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


    public function actionGroupList()
    {
        $_logs = [];
        
        //客户端参数处理
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $keyword = trim(Yii::$app->request->post('keyword', ''));
        
        //处理参数
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 5;
        $offset = ($page - 1) * $limit;

        if (Yii::$app->user->identity->type != User::TYPE_WORKER)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission ', Yii::t('app', 'user_no_permission')));
        }

        $query = StepGroup::find()->where(['not', ['status' => StepGroup::STATUS_DELETE]]);
        if(!empty($keyword))
        {
            $query->andWhere([
                'or',
                ['like', 'desc', $keyword],
                ['like', 'name', $keyword]
            ]);
        }
        $query->with(['items']);
        $count = $query->count();
        $list = $query->offset($offset)
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->asArray()
            ->all();

        return $this->asJson(FormatHelper::resultStrongType([
            'list' => $list,
            'count' => $count
        ]));
    }


    private function actionGroupCreate()
    {
        $_logs = [];
        $postData = Yii::$app->request->post();

        if(Yii::$app->user->identity->type != User::TYPE_ROOT)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app','user_no_permission')));
        }
        //参数获取
        if (empty($postData['steps']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_steps_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_steps_not_given', Yii::t('app','project_steps_not_given')));
        }
        if (empty($postData['name']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'name_not_given', Yii::t('app','name_not_given')));
        }

        $time = time();

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' step_gtoup_createGroup_succ '.json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType(1));
    }

    private function actionGroupDetail()
    {
        $_logs = [];

        //接收参数
        $stepGroupId = Yii::$app->request->post('step_group_id', null);
        $_logs['$stepGroupId'] = $stepGroupId;

        if(Yii::$app->user->identity->type != User::TYPE_ROOT)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app','user_no_permission')));
        }

        //校验参数
        if (!$stepGroupId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_id_not_given', Yii::t('app', 'project_step_group_id_not_given')));
        }

        //查询流程信息
        $stepGroup = StepGroup::find()
            ->where(['id' => $stepGroupId])
            // ->andWhere(['not', ['status', StepGroup::STATUS_DELETE]])
            ->with(['items'])
            ->asArray()
            ->limit(1)
            ->one();
        
        if (empty($stepGroup))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_not_found ', Yii::t('app', 'project_step_group_not_found')));
        }
        if($stepGroup['status'] == StepGroup::STATUS_DELETE)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_deleted '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_deleted ', Yii::t('app', 'project_step_group_deleted')));
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'info' => $stepGroup
        ]));
    }

    /**
    * 关闭流程
    * 关闭流程时不需要解除和租户的关联，给租户选择支持流程时，使用已启用的流程
    */
    public function actionGroupClose()
    {
        $_logs = [];

        //接收参数
        $stepGroupId = Yii::$app->request->post('step_group_id', null);
        $_logs['$stepGroupId'] = $stepGroupId;
        //校验参数
        if (!$stepGroupId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_id_not_given', Yii::t('app', 'project_step_group_id_not_given')));
        }

        //查询流程信息
        $stepGroup = StepGroup::find()->where(['id' => $stepGroupId])->asArray()->limit(1)->one();
        if (empty($stepGroup))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_not_found ', Yii::t('app', 'project_step_group_not_found')));
        }
        if($stepGroup['status'] == StepGroup::STATUS_DELETE)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_deleted '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_deleted ', Yii::t('app', 'project_step_group_deleted')));
        }
        if($stepGroup['status'] == StepGroup::STATUS_DISABLE)
        {
            return $this->asJson(FormatHelper::resultStrongType(1));
        }

        $updateData = [
            'status' => StepGroup::STATUS_DISABLE,
            'updated_at' => time()
        ];
        StepGroup::updateAll($updateData, ['id' => $stepGroupId]);

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' step_gtoup_close_succ '.json_encode($_logs));

        return $this->asJson(FormatHelper::resultStrongType(1));
    }

    public function actionGroupOpen()
    {
        $_logs = [];

        //接收参数
        $stepGroupId = Yii::$app->request->post('step_group_id', null);
        $_logs['$stepGroupId'] = $stepGroupId;
        //校验参数
        if (!$stepGroupId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_id_not_given', Yii::t('app', 'project_step_group_id_not_given')));
        }

        //查询流程信息
        $stepGroup = StepGroup::find()->where(['id' => $stepGroupId])->asArray()->limit(1)->one();
        if (empty($stepGroup))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_not_found ', Yii::t('app', 'project_step_group_not_found')));
        }
        if($stepGroup['status'] == StepGroup::STATUS_DELETE)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_step_group_deleted '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'project_step_group_deleted ', Yii::t('app', 'project_step_group_deleted')));
        }
        if($stepGroup['status'] == StepGroup::STATUS_ENABLE)
        {
            return $this->asJson(FormatHelper::resultStrongType(1));
        }

        $updateData = [
            'status' => StepGroup::STATUS_ENABLE,
            'updated_at' => time()
        ];
        StepGroup::updateAll($updateData, ['id' => $stepGroupId]);

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' step_gtoup_open_succ '.json_encode($_logs));

        return $this->asJson(FormatHelper::resultStrongType(1));
    }
}