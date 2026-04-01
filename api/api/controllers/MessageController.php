<?php
/**
 * NoticeController.php
 * 消息控制器
 * 
 * @author  王中艺 <wangzy_smile@qq.com>
 * @date    2018-05-17
 */

namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\models\Message;
use common\models\MessageToUser;
use common\components\AccessTokenAuth;
use common\models\UserStat;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;

class MessageController extends Controller
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
     * 消息列表
     * @param   $page 页码数
     * @param   $limit 每页数量
     * @param   $user_id 发布通知的人
     * @return  list [<description>]
     */
    public function actionList()
    {
        $_logs = [];
        
        //客户端参数处理
		$page = (int)Yii::$app->request->post('page', 1);
		$limit = (int)Yii::$app->request->post('limit', 10);
		$keyword = trim(Yii::$app->request->post('keyword', ''));
        $date = Yii::$app->request->post('date', '');
		
		//处理参数
		$page < 1 && $page = 1;
		$limit < 1 && $limit = 5;
		$offset = ($page - 1) * $limit;
        
        //--------------------------------------------

        //获取所有分表
        $dates = Message::getAllSuffixes();
        if (!$date)
        {
            $date = end($dates);
        }
        
        //查询
        if ($date)
        {
            Message::setTable($date);
        }
        
        $query = Message::find()->where(['not', ['status' => Message::STATUS_DELETED]]);
        if(!empty($keyword))
        {
            $query->andWhere([
            	'or',
				['like', 'id', $keyword],
				['like', 'user_id', $keyword],
				['like', 'content', $keyword]
			]);
        }
        $count = $query->count();
        $list = $query->offset($offset)
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->with('sender')
            ->asArray()
            ->all();

        foreach($list as $k => $v)
        {
            if(!empty($v['content']) && JsonHelper::is_json($v['content']))
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' list message output1 '.$v['content']);
                $contentArr = JsonHelper::json_decode_all($v['content']);
                if(!empty($contentArr['content']))
                {
                    $contentArr['content'] = Yii::t('app', $contentArr['content']);
                    if(!empty($contentArr['trans_params']) && is_array($contentArr['trans_params']))
                    {
                        array_unshift($contentArr['trans_params'], $contentArr['content']);
                        $contentArr['content'] = call_user_func_array('sprintf', $contentArr['trans_params']);
                        
                        $froms = [];
                        if (!empty($contentArr['trans_params']['project_id']))
                        {
                            $froms['{project_id}'] = $contentArr['trans_params']['project_id'];
                        }
                        if (!empty($contentArr['trans_params']['reason']))
                        {
                            $froms['{reason}'] = Yii::t('app', $contentArr['trans_params']['reason']);
                        }
                        $contentArr['content'] = strtr($contentArr['content'], $froms);
                    }
                    
                    $v['content'] = JsonHelper::json_encode_cn($contentArr);
                    $list[$k] = $v;
                }
            }
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'date' => $date,
            'dates' => $dates,
            'type' => MessageToUser::getTypes(),
            'status' => Message::getStatuses(),
            'list' => $list,
            'count' => $count
        ]));
    }

    /**
     * 获取接收消息的人员
     */
    public function actionDetail()
    {
        $_logs = [];

        $messageId = (int)Yii::$app->request->post('message_id', 0);
        $date = Yii::$app->request->post('date', '');
        $type = Yii::$app->request->post('type', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

        //排序
        if (!in_array($orderby, ['id', 'created_at', 'updated_at']))
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
        $offset = ($page - 1) * $limit;

        if(empty($messageId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_id_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'message_id_not_given ', Yii::t('app', 'message_id_not_given')));
        }

        //获取所有分表
        $dates = Message::getAllSuffixes();
        if (!$date)
        {
            $date = end($dates);
        }
        if (!in_array($date, $dates))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_date_param_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'message_date_param_invalid ', Yii::t('app', 'message_date_param_invalid')));
        }

        Message::setTable($date);
        $message = Message::find()->where(['id' => $messageId])->asArray()->limit(1)->one();
        if(!$message)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'message_not_found ', Yii::t('app', 'message_not_found')));
        }
        // $date = $message['table_suffix'];


        //查询
        MessageToUser::setTable($date);
        $query = MessageToUser::find()->where(['status' => MessageToUser::STATUS_ENABLE]);
        $query->andWhere(['message_id' => $messageId]);
        if($type !== null)
        {
            $types_ = FormatHelper::param_int_to_array($type);
            if ($types_)
            {
                $query->andWhere(['in', 'type', $types_]);
            }
        }
        $count = $query->count();
        $list = [];
        if($count > 0)
        {
            $list = $query->offset($offset)->limit($limit)
                ->orderBy([$orderby => $sort == 'asc'?SORT_ASC : SORT_DESC])
                ->with(['user'])
                ->asArray()->all();
        }

        if($message)
        {
            if (!empty($message['content']) && JsonHelper::is_json($message['content']))
            {
                $message['content'] = JsonHelper::json_decode_all($message['content']);
            }
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'date' => $date,
            'message_id' => $messageId,
            'mesages' => $message,
            'list' => $list,
            'count' => $count,
            'dates' => $dates,
            'types' => MessageToUser::getTypes()
        ]));
    }

    /**
     * 用户消息获取
     * @param   $year 年份
     * @param   $month 月份
     * @param   $page 页码数
     * @param   $limit 每页条目数
     * @return  list [<description>]
     */
    public function actionUserMessages()
    {
        $_logs = [];
        
        $type = Yii::$app->request->post('type', null);
        $page = (int)Yii::$app->request->post('page', 1);
        $limit = (int)Yii::$app->request->post('limit', 10);
        // $messageId = (int)Yii::$app->request->post('message_id', 0);
        $date = Yii::$app->request->post('date', '');
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        // $_logs['$messageId'] = $messageId;
        $_logs['$date'] = $date;

        //排序
        if (!in_array($orderby, ['id', 'created_at']))
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
        $offset = ($page - 1) * $limit;
        
        //获取所有分表
        $dates = Message::getAllSuffixes();
        if (!$date)
        {
            $date = end($dates);
        }
        
        // if ($messageId)
        // {
        //     $info = Message::find()->where(['id' => $messageId])->asArray()->limit(1)->one();
        //     $date = $info['table_suffix'];
        // }
        
        //清空用户消息数
        UserStat::clearMessageCount(Yii::$app->user->id);
        
        //查询
        if ($date)
        {
            MessageToUser::setTable($date);
            Message::setTable($date);
        }
        
        $query = MessageToUser::find()->where(['status' => MessageToUser::STATUS_ENABLE]);
        if($type !== null)
        {
            $types_ = FormatHelper::param_int_to_array($type);
            if ($types_)
            {
                $query->andWhere(['in', 'type', $types_]);
            }
        }
        $query->andWhere(['in', 'user_id', [0, Yii::$app->user->id]]);
        // $messageId && $query->andWhere(['message_id' => $messageId]);
        $count  = $query->count();
        $list = $query->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
            ->offset($offset)
            ->limit($limit)
            ->with(['message', 'user'])
            ->asArray()->all();
        
        if ($list)
        {
            foreach ($list as $k => $v)
            {
                if (!empty($v['message']['content']) && StringHelper::is_json($v['message']['content']))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user message output '.$v['message']['content']);
                    $v['message']['content'] = JsonHelper::json_decode_all($v['message']['content']);
                    $v['message']['content']['content'] = Yii::t('app', $v['message']['content']['content']);
                    if(!empty($v['message']['content']['trans_params']) && is_array($v['message']['content']['trans_params']))
                    {
                        array_unshift($v['message']['content']['trans_params'], $v['message']['content']['content']);
                        $v['message']['content']['content'] = call_user_func_array('sprintf', $v['message']['content']['trans_params']);

                        $froms = [];
                        if (!empty($v['message']['content']['trans_params']['project_id']))
                        {
                            $froms['{project_id}'] = $v['message']['content']['trans_params']['project_id'];
                        }

                        if (!empty($v['message']['content']['trans_params']['reason']))
                        {
                            $reason = $v['message']['content']['trans_params']['reason'];
                            if(is_string($reason)){
                                $froms['{reason}'] = Yii::t('app', $reason);
                            }elseif (is_array($reason) && is_string($reason['checkMessage']['content'])){
                                $froms['{reason}'] = Yii::t('app', $reason['checkMessage']['content']);
                            }

                        }
                        $v['message']['content']['content'] = strtr($v['message']['content']['content'], $froms);
                    }
                    
                    $list[$k] = $v;
                }
            }
        }
        
        return $this->asJson(FormatHelper::resultStrongType([
            'date' => $date,
            'list' => $list,
            'count' => $count,
            'dates' => $dates,
            'types' => MessageToUser::getTypes()
        ]));
    }

    /**
     * 消息读取
     * @param   $message_id 消息ID
     * @return  result [<description>]
     */
    public function actionUserRead()
    {
        $_log = [];
        
        $date = Yii::$app->request->post('date');
        $messageId = Yii::$app->request->post('message_id');
        
        if(!$date)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' date_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','date_not_given',Yii::t('app', 'date_not_given')));
        }
        if(!$messageId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_id_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','message_id_not_given',Yii::t('app', 'message_id_not_given')));
        }

        $dates = Message::getAllSuffixes();
        if (!in_array($date, $dates))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_date_param_invalid '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','message_date_param_invalid',Yii::t('app', 'message_date_param_invalid')));
        }
        
        Message::setTable($date);
        Message::read($date, $messageId, Yii::$app->user->id);

        return $this->asJson(FormatHelper::resultStrongType(['result' => true]));
    }

    /**
     * 消息删除
     * @param   $message_id 消息ID
     * @return  result [<description>]
     */
    public function actionUserDelete()
    {
        $_log = [];
        
        $date = Yii::$app->request->post('date');
        $messageId = Yii::$app->request->post('message_id');
        
        if(!$date)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' date_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','date_not_given',Yii::t('app', 'date_not_given')));
        }
        if(!$messageId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_id_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','message_id_not_given',Yii::t('app', 'message_id_not_given')));
        }
        
        $dates = Message::getAllSuffixes();
        if (!in_array($date, $dates))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_date_param_invalid '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','message_date_param_invalid',Yii::t('app', 'message_date_param_invalid')));
        }
        
        Message::setTable($date);
        Message::del($date, $messageId, Yii::$app->user->id);

        return $this->asJson(FormatHelper::resultStrongType(['result' => true]));
    }

    /**
     * 撤销通知
     * @param   $message_id 消息ID
     * @return  result [<description>]
     */
    public function actionRevoke(){
        $_logs = [];

        $date = Yii::$app->request->post('date');
        $messageId = Yii::$app->request->post('message_id');
        $_log['$messageId'] = $messageId;

        if(!$date)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' date_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','date_not_given',Yii::t('app', 'date_not_given')));
        }
        if(!$messageId)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_id_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'message_id_not_given', Yii::t('app', 'message_id_not_given')));
        }

        $dates = Message::getAllSuffixes();
        if (!in_array($date, $dates))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_date_param_invalid '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','message_date_param_invalid',Yii::t('app', 'message_date_param_invalid')));
        }

        Message::setTable($date);
        MessageToUser::setTable($date);
        Message::revoke($messageId);
        return $this->asJson(FormatHelper::resultStrongType(['result' => true]));
    }
    
    public function actionForm()
    {
        return $this->asJson(FormatHelper::resultStrongType([
            'types' => array_intersect_key(MessageToUser::getTypes(), [MessageToUser::TYPE_SERVER => MessageToUser::TYPE_SERVER])
        ]));
    }
    
    /**
     * 发送消息
     * @param   $user_ids 用户ID集 ","隔开
     * @param   $type 消息类型
     * @param   $message 消息内容
     * @return  boolean
     */
    public function actionSend(){

        //客户端参数处理
        $userIds = Yii::$app->request->post('user_ids', 0);
        $type = Yii::$app->request->post('type', 0);
        $message = Yii::$app->request->post('app');
        $_log['$client'] = Yii::$app->request->post();

        // if(empty($userIds))
        // {
        //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_ids_not_given '.json_encode($_log));
        //     return $this->asJson(FormatHelper::resultStrongType('', 'user_ids_not_given', Yii::t('app', 'user_ids_not_given')));
        // }
        if(empty($message))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' message_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'message_not_given', Yii::t('app', 'message_not_given')));
        }

        $userIds = explode(',', str_replace(' ', '', $userIds));

        //消息发送
        $result = false;
        if(Message::send(Yii::$app->user->id, $userIds, $type, $message))
        {
            $result = true;
        }

        return $this->asJson(FormatHelper::resultStrongType(['result' => $result]));
    }
}
