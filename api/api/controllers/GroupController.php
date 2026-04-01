<?php
namespace api\controllers;

use common\models\GroupUser;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\models\User;
use common\models\UserRecord;
use common\models\Group;
use common\helpers\FormatHelper;
/**
 * 小组控制器
 */
class GroupController extends Controller
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
     * 获取团队详情
     * @param int $groupId 团队ID
     * @return array
     */
    public function actionDetail()
    {
        $_logs = [];

        $groupId = Yii::$app->request->post('group_id', NULL);

        if (empty($groupId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_create_exception ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_id_not_given', Yii::t('app', 'group_id_not_given')));
        }
        //构建查询
        $group = Group::find()->where(['id'=>$groupId])->select('id,name,status,count')->asArray()->limit(1)->one();

        if (!$group)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_found ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_found', Yii::t('app', 'group_not_found')));
        }

        //获取状态列表
        $statuses = Group::getStatuses();

        $data = [
            'group_id' => $groupId,
            'group' => $group,
            'statuses' => $statuses,
        ];

        return $this->asJson(FormatHelper::resultStrongType($data));

    }

    /**
     * 创建小组
     * @param   $name 小组名称
     * @return  id [<description>]
     */
    public function actionCreate()
    {
        //客户端参数处理
        $client = Yii::$app->request->post();
        $_logs['$client'] = $client;

        //团队添加
        $group = new Group();
        $group->created_at = time();
        $group->load($client, '');
        if (!$group->validate() || !$group->save())
        {
            $errors = $group->getFirstErrors();
            $_logs['$model.$group'] = $errors;

            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('group_create_%sError', $key);

            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' ' . $error . ' ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }


        return $this->asJson(FormatHelper::resultStrongType([
            'group_id' => $group->id
        ]));
    }

    /**
     * 更改小组
     * @param   $group_id 团队ID
     * @param   $name 团队名称
     * @return  group [<description>]
     */
    public function actionUpdate()
    {
        $_logs = [];

        $postData = Yii::$app->request->post();
        //------------------------------------
        $groupId = $postData['group_id'];
        if (empty($groupId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_create_exception ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_id_not_given', Yii::t('app', 'group_id_not_given')));
        }

        //模型查找
        $group = Group::findOne($groupId);
        if (!$group)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_found ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_found', Yii::t('app', 'group_not_found')));
        }

        //模型规则验证
        $group->load($postData, '');
        $group->updated_at = time();
        if (!$group->validate() || !$group->save())
        {
            $errors = $group->getFirstErrors();
            $_logs['$model.$group'] = $errors;


            $key = key($errors);
            $message = current($errors);
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            $error = sprintf('group_update_%sError', $key);

            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' ' . $error . ' ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        //响应
        return $this->asJson(FormatHelper::resultStrongType([
            'info' => $group
        ]));
    }

    /**
     * 小组成员添加
     * @param   $group_id 小组ID
     * @return  groupUser
     */
    public function actionUserCreate()
    {
        $_logs = [];

        $postData = Yii::$app->request->post();

        $groupId = (int)$postData['group_id'];

        if (empty($groupId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_id_not_given ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_id_not_given', Yii::t('app', 'group_id_not_given')));
        }

        $_logs['$groupId'] = $groupId;

        //团队查找
        //构建查询
        $group = Group::find()->where(['id'=>$groupId])->select('id,name,status,count')->asArray()->limit(1)->one();

        if ($group === null)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_found ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_found', Yii::t('app', 'group_not_found')));
        }

        //已通过状态的团队，可以添加
        if ($group['status'] != Group::STATUS_NORMAL)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_normal ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_normal', Yii::t('app', 'group_not_normal')));
        }

        $_logs['$groupId'] = $groupId;

        $userId = $postData['user_id'];
        if (empty($userId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_id_not_given ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }

        $user = User::find()->where(['id' => $userId])->asArray()->limit(1)->one();

        if (empty($user))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_id_not_given ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }

        $groupUser = GroupUser::find()->where(['user_id' => $userId])->asArray()->limit(1)->one();

        if ($groupUser)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_user_is_exist ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_user_is_exist', Yii::t('app', 'group_user_is_exist')));
        }

        $group = Group::findOne($groupId);
        $group->count += 1;
        $group->updated_at = time();

        if($group->save() === false)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_create_error ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_is_exist', Yii::t('app', 'group_is_exist')));
        }

        $groupUserModel = new GroupUser();
        $groupUserModel->group_id = $groupId;
        $groupUserModel->user_id = $userId;
        $groupUserModel->created_at = time();

        if($groupUserModel->save() === false)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_create_error ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_is_exist', Yii::t('app', 'group_is_exist')));
        }
        //返回值
        $responseData = [
            'user_id' => $userId,
        ];

        $_logs['$responseData'] = $responseData;

        Yii::info(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_create_succ ' . json_encode($_logs));
        return $this->asJson(FormatHelper::resultStrongType($responseData));
    }

    /**
     * 团队成员删除
     * @param   $group_id 团队ID
     * @param   $user_ids 用户ID列表 ","分割
     * @return  boolean
     */
    public function actionUserDelete()
    {
        $_logs = [];

        $postData = Yii::$app->request->post();
        $groupId = $postData['group_id'];
        if (empty($groupId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_id_not_given ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_id_not_given', Yii::t('app', 'group_id_not_given')));
        }


        $_logs['$groupId'] = $groupId;

        $userId = $postData['user_id'];
        if (empty($userId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_id_not_given ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_id_not_given', Yii::t('app', 'user_id_not_given')));
        }

        $userIds = FormatHelper::param_int_to_array($userId);
        $query = User::find()->Where(['in', 'id', $userIds]);
        $user = $query->asArray()->all();
        if (empty($user))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_info_not_found ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType(1));
        }


        //小组查找
        $group = Group::find()->where(['id' => $groupId])->limit(1)->asArray()->one();

        if ($group === null)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_found ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_found', Yii::t('app', 'group_not_found')));
        }

        //已通过状态的团队，可以添加
        if ($group['status'] != Group::STATUS_NORMAL)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_normal ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_normal', Yii::t('app', 'group_not_normal')));
        }

        $groupUser = GroupUser::find()->where(['in', 'user_id', $userIds])->count();
        $count = count($userIds);
        if ((int)$groupUser === $count)
        {

            foreach ($userIds as $uid)
            {
                GroupUser::deleteAll(['user_id' => $uid]);
            }

            $group = Group::findOne($groupId);
            $group->count -= count($userIds);
            $group->updated_at = time();
            if($group->save() === false)
            {
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_delete_error ' . json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'group_not_normal', Yii::t('app', 'group_not_normal')));
            }

            $userRecord = new UserRecord();
            $result = $userRecord->saveRecord('delete', $userId, '');
            if($result === false)
            {
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' user_delete_error ' . json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'group_not_normal', Yii::t('app', 'group_not_normal')));
            }

            return $this->asJson(FormatHelper::resultStrongType([
                'result' => true
            ]));
        }
        else
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_normal ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_normal', Yii::t('app', 'group_not_normal')));
        }
        //---------------------------------------------

    }


    /*
     * 停用状态
     * @param  $groupId  int  小组id
     * return array
     */
    public function actionDelete()
    {
        $_logs = [];

        $groupId = Yii::$app->request->post('group_id', null);
        $_logs['$groupId'] = $groupId;
        if (empty($groupId))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_id_not_given ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('',
                'group_id_not_given',
                Yii::t('app', 'group_id_not_given')
            ));
        }
        $groupIds = FormatHelper::param_int_to_array($groupId);
        //查询团队是否存在
        $groupStatus = Group::find()->where(['in','id',$groupIds])->select('status')->asArray()->column();

        if (!$groupStatus)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_not_found ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('',
                'group_not_found',
                Yii::t('app', 'group_not_found')
            ));
        }

        //检查是否有停用状态的小组
        if (in_array(Group::STATUS_DISABLE,$groupStatus))
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_disabled ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('',
                'group_disabled',
                Yii::t('app', 'group_disabled')
            ));
        }
        $updateData = [
            'status' => Group::STATUS_DISABLE,
            'count' => 0,
            'updated_at' => time()
        ];

        if(Group::updateAll($updateData, ['id' => $groupIds]) === false)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_update_error ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_normal', Yii::t('app', 'group_not_normal')));
        }

        if(GroupUser::deleteAll(['group_id' => $groupIds]) === false)
        {
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' group_user_update_error ' . json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'group_not_normal', Yii::t('app', 'group_not_normal')));
        }
        $data = [
            'group_id' => $groupId
        ];

        return $this->asJson(FormatHelper::resultStrongType($data));
    }


    /**
     * 团队小组列表
     */
    public function actionGroups()
    {
        //接收参数
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $groupId = (int)Yii::$app->request->post('group_id', 0);
        $keyword = Yii::$app->request->post('keyword', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

        //翻页
        $page < 1 && $page = 1;
        $limit < 1 && $limit = 10;
        $offset = ($page - 1) * $limit;

        //排序
        if (!in_array($orderby, ['id', 'created_at', 'updated_at']))
        {
            $orderby = 'id';
        }

        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }


        //--------------------------------------

        $userGroupModel = Group::find();

        if ($keyword)
        {
            $userGroupModel->andFilterWhere([
                'or',
                ['like', 'name', $keyword],
                ['like', 'id', $keyword],
            ]);
        }

        if ($groupId)
        {
            $userGroupModel->andwhere(['id' => $groupId]);
        }

        $userGroupModel->andwhere(['status' => Group::STATUS_NORMAL]);
        $count = $userGroupModel->count();

        $list = [];
        if ($count > 0)
        {
            $list = $userGroupModel->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
                ->offset($offset)->limit($limit)->asArray()->all();
        }

        $data = [
            'list' => $list,
            'keyword' => $keyword,
            'count' => $count
        ];
        return $this->asJson(FormatHelper::resultStrongType($data));
    }

}

