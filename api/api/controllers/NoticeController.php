<?php
/**
 * NoticeController.php
 * 公告控制器
 * 
 * @author  王中艺 <wangzy_smile@qq.com>
 * @date    2018-05-19
 */

namespace api\controllers;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\models\Notice;
use common\components\AccessTokenAuth;
use common\models\NoticeToPosition;
use common\helpers\FormatHelper;

class NoticeController extends Controller {

    function behaviors(){
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
     * 公告列表
     * @param   $page 
     * @param   $limit
	 * @param   $keyword  关键字搜索
	 * @param   $type  公告状态
	 * @return  list + count [<description>]
     */
    function actionList()
    {
        $_logs  =[];
        
        //客户端参数获取
		$page = (int)Yii::$app->request->post('page', 1);
		$limit = (int)Yii::$app->request->post('limit', 10);
		$keyword = trim(Yii::$app->request->post('keyword', ''));
        $show = Yii::$app->request->post('show_time_limit', 0);
		$type = Yii::$app->request->post('type', null);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        
        //排序
        if (!in_array($orderby, ['id', 'show_start_time', 'show_end_time', 'read_count']))
        {
            $orderby = 'id';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

		//分页
		$page < 1 && $page = 1;
		$limit < 1 && $limit = 5;
		$offset = ($page-1)*$limit;

		//---------------------------------------

        //查询
        $query = Notice::find()->where(['status' => Notice::STATUS_NORMAL]);

        if(!empty($keyword))
        {
            $query->andWhere(['or',
                ['like', 'id', $keyword],
                ['like', 'content', $keyword],
                ['like', 'user_id', $keyword]]
            );
        }
        if($type !== null)
        {
            $types_ = FormatHelper::param_int_to_array($type);
            if ($types_)
            {
                $ids_ = NoticeToPosition::find()
                ->select(['notice_id'])
                ->where(['status' => NoticeToPosition::STATUS_NORMAL])
                ->andWhere(['in', 'type', $types_])
                ->groupBy(['notice_id'])
                ->asArray()->column();
                $query->andWhere(['in', 'id', $ids_]);
            }
        }
        if(empty($show))
        {
            $query->andWhere(['>', 'show_end_time', time()]);
        }
        $count = $query->count();
        $list = $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
			->offset($offset)
            ->limit($limit)
            ->with(['sender', 'positions'])
            ->asArray()
            ->all();

        return $this->asJson(FormatHelper::resultStrongType([
            'list' => $list,
            'count' => $count,
            'type' => Notice::getTypes(),
            'status' => Notice::getStatuses()
        ]));
    }

    public function actionForm()
    {
        return $this->asJson(FormatHelper::resultStrongType([
            'type' => Notice::getTypes(),
            'status' => Notice::getStatuses()
        ]));
    }
    
    /**
     * 创建公告
     * @param   $type 类型
     * @param   $content
     * @return  info
     */
    function actionCreate()
    {
        $_logs = [];
        
        //客户端参数获取
        $client = Yii::$app->request->post();
        $_log['$client'] = $client;
        
        //----------------------------------------------

        //创建数据
        $notice = new Notice();
        $notice->load($client, '');
        $notice->user_id = Yii::$app->user->id;
        $notice->created_at = time();
        $notice->updated_at = time();
        if(!$notice->validate())
        {
            $errors = $notice->getFirstErrors();
            $key = key($errors);
            $message = current($errors);
            $error = sprintf('team_create_%sError', $key);
            
            $_logs['$model.$errors'] = $errors;
            $_logs['$model.$key'] = $key;
            $_logs['$model.$message'] = $message;

            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' '.$error.' '. json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', $error, $message));
        }
        $notice->save();
        
        
        //添加新的广告位
        if (!empty($client['type']))
        {
            $types = FormatHelper::param_int_to_array($client['type']);
            if ($types)
            {
                foreach ($types as $type_)
                {
                    if (!NoticeToPosition::getType($type_))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type error '.json_encode($_log));
                        continue;
                    }
        
                    $noticeToPositon = new NoticeToPosition();
                    $noticeToPositon->notice_id = $notice->id;
                    $noticeToPositon->type = $type_;
                    $noticeToPositon->position = 0;
                    $noticeToPositon->status = NoticeToPosition::STATUS_NORMAL;
                    $noticeToPositon->read_count = 0;
                    $noticeToPositon->show_start_time = $notice->show_start_time;
                    $noticeToPositon->show_end_time = $notice->show_end_time;
                    $noticeToPositon->save();
                }
            }
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'result'    => true
        ]));
    }

    /**
     * 修改公告
     * @param   $notice_id
     * @param   $content
     * @param   $type 类型
     * @param   $status
     * @return  info
     */
    function actionUpdate()
    {
        
        //客户端数据获取
        $client = Yii::$app->request->post();
        $_log['$client'] = $client;
        
        if(empty($client['notice_id']) || !is_numeric($client['notice_id']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notice_id_param_invalid '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'notice_id_param_invalid', Yii::t('app', 'notice_id_param_invalid')));
        }
        
        //更改
        $notice = Notice::find()->where(['id' => $client['notice_id']])->andWhere(['!=', 'status', Notice::STATUS_DELETED])->limit(1)->one();
        if(!$notice)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notice_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'notice_not_found', Yii::t('app', 'notice_not_found')));
        }

        //----------------------------------------------
        
        $notice->load($client, '');
        $notice->user_id = Yii::$app->user->id;
        if(!$notice->validate())
        {
            $errors = $notice->getFirstErrors();
            $_log['$errors']    = $errors;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notice_create_error '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', sprintf('notice_create_%sError', key($errors)), current($errors)));
        }
        $notice->save();
        
        NoticeToPosition::deleteAll(['notice_id' => $notice->id]);
        
        //添加新的广告位
        if (!empty($client['type']))
        {
            $types = FormatHelper::param_int_to_array($client['type']);
            if ($types)
            {
                foreach ($types as $type_)
                {
                    if (!NoticeToPosition::getType($type_))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type error '.json_encode($_log));
                        continue;
                    }
                    
                    $noticeToPositon = new NoticeToPosition();
                    $noticeToPositon->notice_id = $notice->id;
                    $noticeToPositon->type = $type_;
                    $noticeToPositon->position = 0;
                    $noticeToPositon->status = NoticeToPosition::STATUS_NORMAL;
                    $noticeToPositon->read_count = 0;
                    $noticeToPositon->show_start_time = $notice->show_start_time;
                    $noticeToPositon->show_end_time = $notice->show_end_time;
                    $noticeToPositon->save();
                }
            }
        }
        
        return $this->asJson(FormatHelper::resultStrongType([
            'result'    => true
        ]));
    }
    
    function actionDelete()
    {
        $_logs = [];
    
        //客户端数据获取
        $client = Yii::$app->request->post();
        $_log['$client'] = $client;
    
        if(empty($client['notice_id']) || !is_numeric($client['notice_id']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notice_id_param_invalid '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'notice_id_param_invalid',
                Yii::t('app', 'notice_id_param_invalid')
            ));
        }
    
        //更改
        $notice = Notice::find()->where(['id' => $client['notice_id']])->andWhere(['!=', 'status', Notice::STATUS_DELETED])->asArray()->limit(1)->one();
    
        if(!$notice)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notice_not_found '.json_encode($_log));
    
            return $this->asJson(FormatHelper::resultStrongType('', 'notice_not_found', Yii::t('app', 'notice_not_found')));
        }

        //----------------------------------------------

        $updateData = [
            'status' => Notice::STATUS_DELETED
        ];
        Notice::updateAll($updateData, ['id' => $client['notice_id']]);
        
        //同步修改显示记录, 冗余设计
        $updateData = [
            'status' => Notice::STATUS_DELETED
        ];
        NoticeToPosition::updateAll($updateData, ['id' => $client['notice_id']]);
        
    
        return $this->asJson(FormatHelper::resultStrongType([
            'result'    => true
        ]));
    }
}