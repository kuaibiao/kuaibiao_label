<?php
namespace api\controllers;

use common\models\User;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use common\components\AccessTokenAuth;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\models\AuthItem;

class SettingController extends Controller{
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
	 * 列表
	 */
	public function actionList()
	{
		$_logs = [];

		//接收参数
		$page = (int)Yii::$app->request->post('page', 1);
		$limit = (int)Yii::$app->request->post('limit', 10);
		$keyword = trim(Yii::$app->request->post('keyword', null));
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

		// if (Yii::$app->user->identity->type !== User::TYPE_WORKER)
		// {
		// 	Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
		// 	return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		// }
		if(!in_array(AuthItem::ROLE_MANAGER, Yii::$app->user->identity->getRoleKeys()))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		}

		//翻页
		$page < 1 && $page = 1;
		$limit < 1 && $limit = 10;
		$offset = ($page-1)*$limit;

        //排序
        if (!in_array($orderby, ['id', 'last_follow_up']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

		$settingModel = Setting::find()->where(['status' => Setting::STATUS_ENABLE]);

		if(!empty($keyword))
		{
			$settingModel->where(['like', 'name', $keyword]);
		}
		$count = $settingModel->count();

		$list = [];
		if($count > 0)
		{
			$list = $settingModel->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
		}

		$return = [
			'list' => $list,
			'keyword' => $keyword,
			'count' => $count,
		    'value_types' => Setting::getValueTypes()
		];
		return $this->asJson(FormatHelper::resultStrongType($return));
	}


	/*
	 * 添加
	 */
	public function actionCreate()
	{
		$_logs = [];

		$createInfo = Yii::$app->request->post();

		// if (Yii::$app->user->identity->type !== User::TYPE_WORKER)
		// {
		// 	Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
		// 	return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		// }
		if(!in_array(AuthItem::ROLE_MANAGER, Yii::$app->user->identity->getRoleKeys()))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		}

		if(!$createInfo)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' param_error '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'param_error', Yii::t('app', 'param_error')));
		}

		$settingModel = new Setting();
		$settingModel->load($createInfo, '');
		if(!$settingModel->validate() || !$settingModel->save())
		{
			$errors = $settingModel->getFirstErrors();
			$_logs['$settingModel.$error'] = $errors;


			$key = key($errors);
			$message = current($errors);
			$_logs['$settingModel.$key'] = $key;
			$_logs['$settingModel.$message'] = $message;

			$error = sprintf('setting_create_%sError', $key);

			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
		}

		$return = [
			'key' => $settingModel->key
		];
		return $this->asJson(FormatHelper::resultStrongType($return));
	}

	/*
	 * 修改
	 */
	public function actionUpdate()
	{
		$_logs = [];

		$updateInfo = Yii::$app->request->post();

		// if (Yii::$app->user->identity->type !== User::TYPE_WORKER)
		// {
		// 	Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
		// 	return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		// }
		if(!in_array(AuthItem::ROLE_MANAGER, Yii::$app->user->identity->getRoleKeys()))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		}

		$setId = isset($updateInfo['id'])?$updateInfo['id']:'';
		$_logs['$setId'] = $setId;
		if(empty($setId))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' id_not_given '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'id_not_given', Yii::t('app', 'id_not_given')));
		}

		$settingModel = Setting::findOne($setId);
		if(!$settingModel)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setting_not_found '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'setting_not_found', Yii::t('app', 'setting_not_found')));
		}

		if(!in_array($settingModel->status, [Setting::STATUS_ENABLE]))
		{
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setting_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'setting_status_not_allow', Yii::t('app', 'setting_status_not_allow')));
        }

		$settingModel->load($updateInfo, '');
		if(!$settingModel->validate() || !$settingModel->save())
		{
			$errors = $settingModel->getFirstErrors();
			$_logs['$settingModel.$error'] = $errors;


			$key = key($errors);
			$message = current($errors);
			$_logs['$settingModel.$key'] = $key;
			$_logs['$settingModel.$message'] = $message;

			$error = sprintf('setting_update_%sError', $key);

			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
		}

		$return = [
			'id' => $setId
		];
		return $this->asJson(FormatHelper::resultStrongType($return));
	}

	/*
	 * 删除
	 */
	public function actionDelete()
	{
		$_logs = [];

        $setId = Yii::$app->request->post('id', null);

		// if (Yii::$app->user->identity->type !== User::TYPE_WORKER)
		// {
		// 	Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
		// 	return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		// }
		if(!in_array(AuthItem::ROLE_MANAGER, Yii::$app->user->identity->getRoleKeys()))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		}

		$_logs['$setId'] = $setId;
		if(empty($setId))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' id_not_given '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'id_not_given', Yii::t('app', 'id_not_given')));
		}

		$settingModel = Setting::find()->where(['id' => $setId])->limit(1)->asArray()->one();
		if(!$settingModel)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setting_not_found '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'setting_not_found', Yii::t('app', 'setting_not_found')));
		}

        if(!in_array($settingModel['status'], [Setting::STATUS_ENABLE]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' setting_status_not_allow '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'setting_status_not_allow', Yii::t('app', 'setting_status_not_allow')));
        }
		$updateData = [
		    'status' => Setting::STATUS_DISABLE
        ];
        Setting::updateAll($updateData, ['id' => $setId]);

		$return = [
			'id' => $setId
		];
		return $this->asJson(FormatHelper::resultStrongType($return));
	}
}