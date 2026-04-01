<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/27
 * Time: 15:11
 */

namespace api\controllers;

use common\models\Step;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\models\Template;
use common\models\Category;
use common\models\Project;
use common\models\User;
use common\models\Setting;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;

class TemplateController extends Controller
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

	public function actionForm()
	{
	    $_logs = [];
	    $types = Template::getTypes();
	    
	    $categories = Category::find()->select(['id', 'type','key', 'file_type', 'icon', 'thumbnail', 'view'])->where(['status' => Category::STATUS_ENABLE])->asArray()->all();
	    if($categories){
	        
	        foreach ($categories as $k => $v)
	        {
	            $categories[$k]['desc'] = Category::getCategoryDesc($v['key']);
	        }
	        
	        $results = [
	            'types' => $types,
	            'categories' => $categories
	        ];
	        
	        return $this->asJson(FormatHelper::resultStrongType($results));
	    }
	    else
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' category_not_found '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'category_not_found', Yii::t('app', 'category_not_found')));
	    }
	    
	}
	
	/**
	 * 模板列表
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
		$projectId = Yii::$app->request->post('project_id', null);
		$keyword = trim(Yii::$app->request->post('keyword', null));
		$parentId = Yii::$app->request->post('parent_id', null);
		$categoryId = Yii::$app->request->post('category_id', null);
		$type = Yii::$app->request->post('type', null);
		$userId = Yii::$app->request->post('user_id', 0);

		//翻页
		$page < 1 && $page = 1;
		$limit < 1 && $limit = 10;
		$offset = ($page-1)*$limit;

		//排序
		if (!in_array($orderby, ['id','sort','created_at','updated_at']))
		{
			$orderby = 'sort';
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
		
		$categories = Category::find()
			->select(['id', 'key','type', 'file_type', 'icon', 'thumbnail'])
			->where(['status' => Category::STATUS_ENABLE])
			->asArray()
			->all();
		//----------------------------------------------
        foreach ($categories as $k => $v)
        {
            $categories[$k]['desc'] = Category::getCategoryDesc($v['key']);
        }

		//构建sql
		$query = Template::find()
		->select(['id', 'category_id', 'name', 'user_id','status', 'type', 'sort', 'created_at', 'updated_at'])
		->where(['status' => Template::STATUS_ENABLE]);
		
		if ($siteId)
		{
		    $query->andWhere(['or',
		        ['type' => Template::TYPE_PUBLIC],
		        ['type'=> Template::TYPE_PRIVATE, 'site_id' => $siteId],
		    ]);
		}
		
		if ($keyword)
		{
		    $keyword = StringHelper::html_encode($keyword);

		    $userIds_ = User::find()->select(['id'])->where([
	            'or',
	            ['like', 'nickname', $keyword],
	        ])->asArray()->column();
			$query->andWhere(['or',
				['like', 'name', $keyword],
				['like', 'id', $keyword],
			    ['in', 'user_id', $userIds_],
			]);
		}
		$userId && $query->andWhere(['user_id' => $userId]);

		if($projectId)
		{
			$projectId = (int)$projectId;
			$query->andWhere(['project_id' => $projectId]);
		}

		if($parentId)
		{
			$parentId = (int)$parentId;
			$query->andWhere(['parent_id' => $parentId]);
		}

		if($categoryId)
		{
			$query->andWhere(['category_id' => $categoryId]);
		}
		
		if ($type !== null)
		{
		    $types_ = FormatHelper::param_int_to_array($type);
		    if ($types_)
		    {
		        $query->andWhere(['in', 'type', $types_]);
		    }
		}

		//获取总数量
		$count = $query->count();
		$list = [];
		if($count > 0)
		{
		    $list = $query->orderBy(['type' => SORT_ASC, $orderby => $sort == 'asc'? SORT_ASC: SORT_DESC])
                ->offset($offset)->limit($limit)
                ->asArray()->with(['user', 'category'])->all();
		}

        foreach ($list as $k => $v)
        {
            $list[$k]['category'] =  Category::getCategoryDesc($v['category']['key']);
            $nameArr = explode('.', $v['name']);
            foreach($nameArr as $index => $nameItem)
            {
            	$nameArr[$index] = Yii::t('app', $nameItem);
            }
            $list[$k]['name'] =  implode('.', $nameArr);
        }

		return $this->asJson(FormatHelper::resultStrongType([
		    'count' => $count,
		    'list' => $list,
			'page' => $page,
			'limit' => $limit,
			'orderby' => $orderby,
			'sort' => $sort,
			'projectId' => $projectId,
			'keyword' => $keyword,
		    'types' => Template::getTypes(),
		    'categories' => $categories
		]));
	}
	
	/**
	 * 模板详情
	 */
	public function actionDetail()
	{
		$_logs = [];

		//接收参数
		$templateId = Yii::$app->request->post('template_id', null);
		$siteId = Yii::$app->request->post('site_id', null);
		
		if(!$templateId)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_id_not_given '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_id_not_given', Yii::t('app', 'template_id_not_given')));
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
		
		//模板是否存在
		$query = Template::find()->where(['id' => $templateId])->andWhere(['status' => Template::STATUS_ENABLE]);
		
		if ($siteId)
		{
		    $query->andWhere(['or',
		        ['type' => Template::TYPE_PUBLIC],
		        ['type'=> Template::TYPE_PRIVATE, 'site_id' => $siteId],
		    ]);
		}
		
		$template = $query->asArray()->limit(1)->one();
		
		$_logs['$template'] = $template;
		if(!$template)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_not_found '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_not_found', Yii::t('app', 'template_not_found')));
		}

		//已删除的，不能展示
		if(!in_array($template['status'], [Template::STATUS_ENABLE]))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_status_not_allow '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_status_not_allow', Yii::t('app', 'template_status_not_allow')));
		}
		
		//------------------------------------
		
		//保持原始类型, 前端会使用
		$template['config'] = JsonHelper::json_decode_all($template['config'], false);

		//使用此模板的正在进行的项目
        $stepProjectIds = Step::find()->select(['project_id'])->where(['template_id' => $templateId])->asArray()->column();
        $projects = Project::find()->select(['name'])->where(['in','id', $stepProjectIds])->andWhere(['status' => Project::STATUS_WORKING])->asArray()->column();

		return $this->asJson(FormatHelper::resultStrongType([
		    'template' => $template,
            'projects' => $projects,
		]));
	}


	/**
	 * 新增模板
	 */
	public function actionCreate()
	{
		$_logs = [];
		
		//开启自定义模板
		if (empty(Setting::getSetting('open_template_diy')))
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		}

		//接收参数
		$postInfo = Yii::$app->request->post();
		$siteId = Yii::$app->request->post('site_id', 0);
		
		if(!$postInfo)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' param_not_found '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'param_not_found', Yii::t('app', 'param_not_found')));
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
		
		//添加数据
		$template = new Template();
		$template->sort = time();
		$template->load($postInfo, '');
		$template->site_id = $siteId;
		$template->user_id = Yii::$app->user->id;
		$template->created_at = time();
		$template->updated_at = time();
		if(!$template->validate())
		{
			$errors = $template->getFirstErrors();
			$key = key($errors);
			$message = current($errors);
			$error = sprintf('template_create_%sError', $key);
			
			$_logs['$model.$template'] = $errors;
			$_logs['$model.$key'] = $key;
			$_logs['$model.$message'] = $message;

			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
		}
		$template->save();

		return $this->asJson(FormatHelper::resultStrongType([
		    'template_id'=> $template->id
		]));
	}


	/**
	 * 修改模板
	 */
	public function actionUpdate()
	{
		$_logs = [];

		//开启自定义模板
		if (empty(Setting::getSetting('open_template_diy')))
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		}

		//接收参数
		$postInfo = Yii::$app->request->post();
		$siteId = Yii::$app->request->post('site_id', null);
		
		if(empty($postInfo['template_id']))
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' param_not_found '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'param_not_found', Yii::t('app', 'param_not_found')));
		}
		$templateId = $postInfo['template_id'];
		
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
		
		//模板是否存在
		$query = Template::find()->where(['id' => $templateId])->andWhere(['status' => Template::STATUS_ENABLE]);
		
		if ($siteId)
		{
		    $query->andWhere(['or',
		        ['type' => Template::TYPE_PUBLIC],
		        ['type'=> Template::TYPE_PRIVATE, 'site_id' => $siteId],
		    ]);
		}
		
		$template = $query->limit(1)->one();
		
		if(!$template)
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_not_found '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'template_not_found', Yii::t('app', 'template_not_found')));
		}
		
		//已删除的，不能修改
		if(!in_array($template->status, [Template::STATUS_ENABLE]))
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_status_not_allow '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'template_status_not_allow', Yii::t('app', 'template_status_not_allow')));
		}
		
		//只能是私有模板
		//if ($template['type'] != Template::TYPE_PUBLIC) {
		    //$postInfo['type'] = Template::TYPE_PRIVATE;
		//}

		//------------------------------------
		
		$template->sort = time();
		$template->load($postInfo, '');
		$template->updated_at = time();
		if(!$template->validate())
		{
			$errors = $template->getFirstErrors();
			$_logs['$model.$template'] = $errors;


			$key = key($errors);
			$message = current($errors);
			$_logs['$model.$key'] = $key;
			$_logs['$model.$message'] = $message;

			$error = sprintf('template_update_%sError', $key);

			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
		}
		$template->save();

		return $this->asJson(FormatHelper::resultStrongType([
		    'template_id'=> $template->id
		]));
	}


	/**
	 * 删除模板
	 */
	public function actionDelete(){
		$_logs = [];
		
		//开启自定义模板
		if (empty(Setting::getSetting('open_template_diy')))
		{
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', 'user_no_permission', Yii::t('app', 'user_no_permission')));
		}

		//接收参数
		$templateId = Yii::$app->request->post('template_id', null);
		$siteId = Yii::$app->request->post('site_id', null);

		if(!$templateId)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_id_not_given '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_id_not_given', Yii::t('app', 'template_id_not_given')));
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
		
		//模板是否存在
		$query = Template::find()->where(['id' => $templateId])->andWhere(['status' => Template::STATUS_ENABLE]);
		
		if ($siteId)
		{
		    $query->andWhere(['or',
		        ['type' => Template::TYPE_PUBLIC],
		        ['type'=> Template::TYPE_PRIVATE, 'site_id' => $siteId],
		    ]);
		}
		
		$template = $query->asArray()->limit(1)->one();

		if(!$template)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_not_found '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_not_found', Yii::t('app', 'template_not_found')));
		}

		//已删除的，直接返回
		if(in_array($template['status'], [Template::STATUS_DISABLE]))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_status_not_allow '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_status_not_allow', Yii::t('app', 'template_status_not_allow')));
		}
		
		//------------------------------------

		//修改状态，已删除
		$updata = [
		    'status' => Template::STATUS_DISABLE,
		    'updated_at' => time()
		];
		Template::updateAll($updata, ['id' => $templateId]);

		$data = [
			'template_id' => $templateId
		];
		return $this->asJson(FormatHelper::resultStrongType($data));
	}

	/**
	 * 复制模板
	 */
	public function actionCopy()
	{
		$_logs = [];

		//获取参数
		$templateId = Yii::$app->request->post('template_id', null);
		$siteId = Yii::$app->request->post('site_id', null);
		
		if(empty($templateId))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_id_not_given '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_id_not_given', Yii::t('app', 'template_id_not_given')));
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

		//获取旧的模板信息
		$query = Template::find()->where(['id' => $templateId])->andWhere(['status' => Template::STATUS_ENABLE]);
		
		if ($siteId)
		{
		    $query->andWhere(['or',
		        ['type' => Template::TYPE_PUBLIC],
		        ['type'=> Template::TYPE_PRIVATE, 'site_id' => $siteId],
		    ]);
		}
		
		$template = $query->asArray()->limit(1)->one();
		
		if(!$template)
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_not_found '.json_encode($_logs));
			return $this->asJson(FormatHelper::resultStrongType('', 'template_not_found', Yii::t('app', 'template_not_found')));
		}

		//------------------------------------
		
		$time = strrchr($template['name'], '.');
		if ($time)
		{
		    $template['name'] = str_replace($time, '.'.date('YmdHis'), $template['name']);
		}
		else
		{
		    $template['name'] = $template['name'].'.'.time();
		}

		//添加新的模板信息
		$templateNew = new Template();
		$templateNew->site_id = $siteId;
		$templateNew->category_id = $template['category_id'];
		$templateNew->name = $template['name'];
		$templateNew->parent_id = $template['parent_id'];
		$templateNew->user_id = Yii::$app->user->id;
		$templateNew->sort = time();
		$templateNew->status = $template['status'];
		$templateNew->type = Template::TYPE_PRIVATE;
		$templateNew->config = $template['config'];
		$templateNew->project_id = 0;
		$templateNew->created_at = time();
		$templateNew->updated_at = time();
		if (!$templateNew->validate())
		{
		    $errors = $templateNew->getFirstErrors();
		    $key = key($errors);
		    $message = current($errors);
		    $error = sprintf('template_copy_%sError', $key);
		    
		    $_logs['$model.$template'] = $errors;
		    $_logs['$model.$key'] = $key;
		    $_logs['$model.$message'] = $message;
		    
		    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
		    return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
		}
		$templateNew->save();

		$info = Template::find()->where(['id' => $templateNew->id])->asArray()->limit(1)->one();
		if(!empty($info['name']))
		{
			$nameArr = explode('.', $info['name']);
			foreach($nameArr as $index => $name)
			{
				$nameArr[$index] = Yii::t('app', $name);
			}
			$info['name'] = implode('.', $nameArr);
		}
		$return = [
			'info' => $info
		];

		return $this->asJson(FormatHelper::resultStrongType($return));
	}
	
	/**
	 * 复制模板
	 */
	public function actionUse()
	{
	    $_logs = [];
	
	    //获取参数
	    $templateId = Yii::$app->request->post('template_id', null);
	    $siteId = Yii::$app->request->post('site_id', null);
	    
	    if(empty($templateId))
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_id_not_given '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'template_id_not_given', Yii::t('app', 'template_id_not_given')));
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
	
	    //获取旧的模板信息
	    $query = Template::find()->where(['id' => $templateId])->andWhere(['status' => Template::STATUS_ENABLE]);
	    
	    if ($siteId)
	    {
	        $query->andWhere(['or',
	            ['type' => Template::TYPE_PUBLIC],
	            ['type'=> Template::TYPE_PRIVATE, 'site_id' => $siteId],
	        ]);
	    }
	    
	    $template = $query->asArray()->limit(1)->one();
	    
	    if(!$template)
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_not_found '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'template_not_found', Yii::t('app', 'template_not_found')));
	    }
	
	    //已删除的，直接返回
	    if(in_array($template['status'], [Template::STATUS_DISABLE]))
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' template_status_not_allow '.json_encode($_logs));
	        return $this->asJson(FormatHelper::resultStrongType('', 'template_status_not_allow', Yii::t('app', 'template_status_not_allow')));
	    }

	    //------------------------------------
	
	    //修改排序
	    $updata = [
		    'updated_at' => time()
		];
		Template::updateAll($updata, ['id' => $templateId]);
	
	    $return = [
	        'info' => $template
	    ];
	
	    return $this->asJson(FormatHelper::resultStrongType($return));
	}

}