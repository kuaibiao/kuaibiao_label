<?php
namespace common\models;

use Yii;
use common\components\ModelComponent;
use common\helpers\FormatHelper;

/**
 * stat_result 表数据模型
 *
 */
class StatResultWork extends ModelComponent
{
	const TYPE_UNKNOWN_COUNT = 0; //未知类型
	const TYPE_CIRCLE_COUNT = 1; //标注总圆数
	const TYPE_ELLIPSE_COUNT = 2; //标注总椭圆数
	const TYPE_UNCLOSEDPOLYGON_COUNT = 3; //标注总折线数
	const TYPE_RECT_COUNT = 4; //标注总矩形数
	const TYPE_RECT_POINT_COUNT = 5; //标注总矩形+点数
	const TYPE_POLYGON_COUNT = 6; //标注总多边形数
	const TYPE_TRAPEZOID_COUNT = 7; //标注总梯形数
	const TYPE_TRIANGLE_COUNT = 8; //标注总三角形数
	const TYPE_QUADRANGLE_COUNT = 9; //标注总四边形数
	const TYPE_CUBOID_COUNT = 10; //标注总长方体数
	const TYPE_LINE_COUNT = 11; //标注总线数
	const TYPE_POINT_COUNT = 12; //标注总点数
	const TYPE_BONEPOINT_COUNT = 13; //标注有序关键点总数数
	const TYPE_CLOSEDCURVE_COUNT = 14; //标注闭合曲线总数
	const TYPE_SPLINECURVE_COUNT = 15; //曲线总数
	const TYPE_PENLINE_COUNT = 16; //标注钢笔总线数
	const TYPE_MEDIA_DURATION = 17; //原媒体文件总时长
	const TYPE_MEDIA_EFFECTIVE_DURATION = 18; //媒体有效总时长
	const TYPE_TEXT_WORD_COUNT = 19; //文本标注数
	const TYPE_FILE_TEXT_WORD_COUNT = 20; //原文本字符数
	const TYPE_3D_CLOUDPOINT_COUNT = 21; //3D点云标注框数
	const TYPE_LABEL_NO_COUNT = 22; //被标记为No的无效数据数
	const TYPE_LABEL_YES_COUNT = 23; //被标记为Yes的有效数据数
	const TYPE_LABEL_UNKNOWN_COUNT = 24; //被标记为Unknown的有效数据数
	const TYPE_RECT_SEAL_COUNT = 25; //矩形印章
	const TYPE_FORM_COUNT = 26; //标注表单总数
	const TYPE_TEXT_COUNT = 27; //标注文本总数
	const TYPE_AUDIO_COUNT = 28; //标注语音总数
	const TYPE_VIDEO_COUNT = 29; //标注视频总数
	const TYPE_2D_CLOUDPOINT_COUNT = 30; //2D点云标注框数
	const TYPE_2D_OBJECT_COUNT = 31; //2D物体数
	const TYPE_3D_OBJECT_COUNT = 32; //3D物体数

	const ACTION_UNKNOWN = 0; //未知操作
	const ACTION_ADD = 1; //新增
	const ACTION_EDIT = 2; //编辑
	const ACTION_DELETE = 3; //删除
	const ACTION_ALLOW = 4; //通过
	const ACTION_REFUSE = 5; //驳回
	const ACTION_RESET = 6; //重置
	const ACTION_ALLOWED = 7; //被通过
	const ACTION_REFUSED = 8; //未通过被驳回
	const ACTION_REFUSED_AFTER_ALLOWED = 9; //被通过之后被驳回
	const ACTION_RESETED = 10; //被重置
	const ACTION_RESETED_AFTER_ALLOWED = 11; //被通过之后被重置
	const ACTION_REDO = 12; //重做
	const ACTION_FORCEREFUSED = 13; //被强制驳回
	const ACTION_FORCEREFUSED_AFTER_ALLOWED = 14; //被通过之后被强制驳回
	const ACTION_FORCERESETED = 15; //被强制重置
	const ACTION_FORCERESETED_AFTER_ALLOWED = 16; //被通过之后被强制重置
	const ACTION_PARENTFORCEREFUSED = 17; //父分步被强制驳回
	const ACTION_PARENTFORCERESETED = 18; //父分步被强制重置
	
	public static $tableName = 'stat_result_work';

	/**
     * @inheritdoc
     */
	public static function tableName()
	{
		return self::$tableName;
	}

	/**
     * @inheritdoc
     */
	public function rules()
	{
		return [
			['user_id', 'required'],
			['user_id', 'integer'],
			['project_id', 'required'],
			['project_id', 'integer'],
			['task_id', 'required'],
			['task_id', 'integer'],
			['data_id', 'required'],
			['data_id', 'integer'],
			['work_id', 'required'],
			['work_id', 'integer'],
			['type', 'required'],
			['type', 'integer'],
			['type', 'in', 'range' => [
				self::TYPE_UNKNOWN_COUNT,
				self::TYPE_CIRCLE_COUNT,
				self::TYPE_ELLIPSE_COUNT,
				self::TYPE_UNCLOSEDPOLYGON_COUNT,
				self::TYPE_RECT_COUNT,
				self::TYPE_RECT_POINT_COUNT,
				self::TYPE_POLYGON_COUNT,
				self::TYPE_TRAPEZOID_COUNT,
				self::TYPE_TRIANGLE_COUNT,
				self::TYPE_QUADRANGLE_COUNT,
				self::TYPE_CUBOID_COUNT,
				self::TYPE_LINE_COUNT,
				self::TYPE_POINT_COUNT,
				self::TYPE_BONEPOINT_COUNT,
				self::TYPE_CLOSEDCURVE_COUNT,
				self::TYPE_SPLINECURVE_COUNT,
				self::TYPE_PENLINE_COUNT,
				self::TYPE_MEDIA_DURATION,
				self::TYPE_MEDIA_EFFECTIVE_DURATION,
				self::TYPE_TEXT_WORD_COUNT,
				self::TYPE_FILE_TEXT_WORD_COUNT,
				self::TYPE_3D_CLOUDPOINT_COUNT,
				self::TYPE_LABEL_NO_COUNT,
				self::TYPE_LABEL_YES_COUNT,
				self::TYPE_LABEL_UNKNOWN_COUNT,
				self::TYPE_RECT_SEAL_COUNT,
				self::TYPE_FORM_COUNT,
				self::TYPE_TEXT_COUNT,
				self::TYPE_AUDIO_COUNT,
				self::TYPE_VIDEO_COUNT,
				self::TYPE_2D_CLOUDPOINT_COUNT,
				self::TYPE_2D_OBJECT_COUNT,
				self::TYPE_3D_OBJECT_COUNT
			]],
			['action', 'required'],
			['action', 'integer'],
			['action', 'in', 'range' => [
				self::ACTION_UNKNOWN,
				self::ACTION_ADD,
				self::ACTION_EDIT,
				self::ACTION_DELETE,
				self::ACTION_ALLOW,
				self::ACTION_REFUSE,
				self::ACTION_RESET,
				self::ACTION_ALLOWED,
				self::ACTION_REFUSED,
				self::ACTION_REFUSED_AFTER_ALLOWED,
				self::ACTION_RESETED,
				self::ACTION_RESETED_AFTER_ALLOWED,
				self::ACTION_REDO,
				self::ACTION_FORCEREFUSED,
				self::ACTION_FORCEREFUSED_AFTER_ALLOWED,
				self::ACTION_FORCERESETED,
				self::ACTION_FORCERESETED_AFTER_ALLOWED,
				self::ACTION_PARENTFORCEREFUSED,
				self::ACTION_PARENTFORCERESETED,
			]],
			['value', 'integer'],
		];
	}

	public static function getTypes()
	{
		return [
			self::TYPE_UNKNOWN_COUNT => Yii::t('app', 'stat_result_type_unknown_count'),
			self::TYPE_CIRCLE_COUNT => Yii::t('app', 'stat_result_type_circle_count'),
			self::TYPE_ELLIPSE_COUNT => Yii::t('app', 'stat_result_type_ellipse_count'),
			self::TYPE_UNCLOSEDPOLYGON_COUNT => Yii::t('app', 'stat_result_type_unclosedpolygon_count'),
			self::TYPE_RECT_COUNT => Yii::t('app', 'stat_result_type_rect_count'),
			self::TYPE_RECT_POINT_COUNT => Yii::t('app', 'stat_result_type_rect_point_count'),
			self::TYPE_POLYGON_COUNT => Yii::t('app', 'stat_result_type_polygon_count'),
			self::TYPE_TRAPEZOID_COUNT => Yii::t('app', 'stat_result_type_trapezoid_count'),
			self::TYPE_TRIANGLE_COUNT => Yii::t('app', 'stat_result_type_triangle_count'),
			self::TYPE_QUADRANGLE_COUNT => Yii::t('app', 'stat_result_type_quadrangle_count'),
			self::TYPE_CUBOID_COUNT => Yii::t('app', 'stat_result_type_cuboid_count'),
			self::TYPE_LINE_COUNT => Yii::t('app', 'stat_result_type_line_count'),
			self::TYPE_POINT_COUNT => Yii::t('app', 'stat_result_type_point_count'),
			self::TYPE_BONEPOINT_COUNT => Yii::t('app', 'stat_result_type_bonepoint_count'),
			self::TYPE_CLOSEDCURVE_COUNT => Yii::t('app', 'stat_result_type_closedcurve_count'),
			self::TYPE_SPLINECURVE_COUNT => Yii::t('app', 'stat_result_type_splinecurve_count'),
			self::TYPE_PENLINE_COUNT => Yii::t('app', 'stat_result_type_pencilline_count'),
			self::TYPE_MEDIA_DURATION => Yii::t('app', 'stat_result_type_media_duration'),
			self::TYPE_MEDIA_EFFECTIVE_DURATION => Yii::t('app', 'stat_result_type_effective_duration'),
			self::TYPE_TEXT_WORD_COUNT => Yii::t('app', 'stat_result_type_text_word_count'),
			self::TYPE_FILE_TEXT_WORD_COUNT => Yii::t('app', 'stat_result_type_file_text_word_count'),
			self::TYPE_3D_CLOUDPOINT_COUNT => Yii::t('app', 'stat_result_type_3d_cloudpoint_count'),
			self::TYPE_LABEL_NO_COUNT => Yii::t('app', 'stat_result_type_label_no_count'),
			self::TYPE_LABEL_YES_COUNT => Yii::t('app', 'stat_result_type_label_yes_count'),
			self::TYPE_LABEL_UNKNOWN_COUNT => Yii::t('app', 'stat_result_type_label_unknown_count'),
			self::TYPE_RECT_SEAL_COUNT => Yii::t('app', 'stat_result_type_rect_seal_count'),
			self::TYPE_FORM_COUNT => Yii::t('app', 'stat_result_type_form_count'),
			self::TYPE_TEXT_COUNT => Yii::t('app', 'stat_result_type_text_count'),
			self::TYPE_AUDIO_COUNT => Yii::t('app', 'stat_result_type_audio_count'),
			self::TYPE_VIDEO_COUNT => Yii::t('app', 'stat_result_type_video_count'),
			self::TYPE_2D_CLOUDPOINT_COUNT => Yii::t('app', 'stat_result_type_2d_cloudpoint_count'),
			self::TYPE_2D_OBJECT_COUNT => Yii::t('app', 'stat_result_type_2d_object_count'),
			self::TYPE_3D_OBJECT_COUNT => Yii::t('app', 'stat_result_type_3d_object_count'),
		];
	}

	public static function getType($type)
	{
		$types = self::getTypes();

		return isset($types[$type]) ? $types[$type] : '';
	}

	/**
     * 类型对应的模板工具
     */
	public static function getLabelNames()
	{
		return [
			self::TYPE_UNKNOWN_COUNT => 'unknown',
			self::TYPE_CIRCLE_COUNT => 'circler',
			self::TYPE_ELLIPSE_COUNT => 'ellipse',
			self::TYPE_UNCLOSEDPOLYGON_COUNT => 'unclosedpolygon',
			self::TYPE_RECT_COUNT => 'rect',
			self::TYPE_RECT_POINT_COUNT => 'rectP',
			self::TYPE_POLYGON_COUNT => 'polygon',
			self::TYPE_TRAPEZOID_COUNT => 'trapezoid',
			self::TYPE_TRIANGLE_COUNT => 'triangle',
			self::TYPE_QUADRANGLE_COUNT => 'quadrangle',
			self::TYPE_CUBOID_COUNT => 'cuboid',
			self::TYPE_LINE_COUNT => 'line',
			self::TYPE_POINT_COUNT => 'point',
			self::TYPE_BONEPOINT_COUNT => 'bonepoint',
			self::TYPE_CLOSEDCURVE_COUNT => 'closedcurve',
			self::TYPE_SPLINECURVE_COUNT => 'splinecurve',
			self::TYPE_PENLINE_COUNT => 'pencilline',
			self::TYPE_MEDIA_DURATION => 'media_duration',
			self::TYPE_MEDIA_EFFECTIVE_DURATION => 'effective_duration',
			self::TYPE_TEXT_WORD_COUNT => 'text_word',
			self::TYPE_FILE_TEXT_WORD_COUNT => 'file_text_word',
			self::TYPE_3D_CLOUDPOINT_COUNT => '3d_cloudpoint',
			self::TYPE_LABEL_NO_COUNT => 'label_no',
			self::TYPE_LABEL_YES_COUNT => 'label_yes',
			self::TYPE_LABEL_UNKNOWN_COUNT => 'label_unknown',
			self::TYPE_RECT_SEAL_COUNT => 'rectS',
			self::TYPE_FORM_COUNT => 'form',
			self::TYPE_TEXT_COUNT => 'text',
			self::TYPE_AUDIO_COUNT => 'audio',
			self::TYPE_VIDEO_COUNT => 'video',
			self::TYPE_2D_CLOUDPOINT_COUNT => '2d_cloudpoint',
			self::TYPE_2D_OBJECT_COUNT => '2d_object',
			self::TYPE_3D_OBJECT_COUNT => '3d_object'
		];
	}

	public static function getLabelName($type)
	{
		$labelNames = self::getLabelNames();

		return isset($labelNames[$type]) ? $labelNames[$type] : '';
	}

	public static function updateCounter($where = [], $counters = [], $lockTimes = 0)
	{
		$_logs = ['$where' => $where, '$counters' => $counters, '$lockTimes' => $lockTimes];

		$projectId = empty($where['project_id']) ? 0 : $where['project_id'];
		$taskId = empty($where['task_id']) ? 0 : $where['task_id'];
		$dataId = empty($where['data_id']) ? 0 : $where['data_id'];
		$workId = empty($where['work_id']) ? 0 : $where['work_id'];
		$userId = empty($where['user_id']) ? 0 : $where['user_id'];
		$type = empty($where['type']) ? 0 : $where['type'];
		$action = empty($where['action']) ? 0 : $where['action'];
        
        $statResultWork = self::find()
	        ->where([
	        	'project_id' => $projectId, 
	        	'task_id' => $taskId, 
	        	'data_id' => $dataId, 
	        	'work_id' => $workId, 
	        	'user_id' => $userId, 
	        	'type' => $type, 
	        	'action' => $action])
	        ->asArray()->limit(1)->one();
        if (!$statResultWork)
        {
            //并发锁, 防止N多人同时第一次点击执行任务
            $cacheKey = sprintf('statResultWork.updateCounter.%s.%s.%s.%s.%s.%s.%s.lock', $projectId, $taskId, $dataId, $workId, $userId, $type, $action);
            $lockKey = Yii::$app->redis->buildKey($cacheKey);
            $_logs['$lockKey'] = $lockKey;
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $lockKey '.json_encode($_logs));
            
            //成功返回 1,失败返回0
            if (!Yii::$app->redis->setnx($lockKey, 1))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locked '.json_encode($_logs));
                if ($lockTimes > 5)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' locked, locktimes > 5 '.json_encode($_logs));
                    return false;
                }
                sleep(1);
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' sleep 1 '.json_encode($_logs));
            
                return self::updateCounter($where, $counters, $lockTimes + 1);
            }
            Yii::$app->redis->expire($lockKey, 30);
            
            //-----------------------------------
            $statResultWork = new self();
            $statResultWork->project_id = $projectId;
            $statResultWork->task_id = $taskId;
            $statResultWork->data_id = $dataId;
            $statResultWork->work_id = $workId;
            $statResultWork->user_id = $userId;
            $statResultWork->type = $type;
            $statResultWork->action = $action;
            $statResultWork->value = 0;
            $statResultWork->save();
            
            $statResultWork = $statResultWork->getAttributes();
            
            //删除lockkey
            Yii::$app->redis->del($lockKey);
        }
        
        if ($counters)
        {
            //检测产生负数的情况
            foreach ($counters as $k => $v)
            {
                if ($v < 0 && isset($statResultWork[$k]) && ($statResultWork[$k] + $v < 0))
                {
                    $_logs['$counters.$k'] = $k;
                    $_logs['$counters.$v'] = $v;
                    $_logs['$statResultWork.$v'] = $statResultWork[$k];
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $statResultWork value <0 '.json_encode($_logs));
                    
                    //删除此项
                    $counters[$k] = '';
                    unset($counters[$k]);
                }
            }
            
            self::updateAllCounters($counters, ['id' => $statResultWork['id']]);
        }
        
        return true;
	}


	public static function fetchData($param = [])
	{
		$_logs = ['$param' => $param];
		$statData = [];

		if(empty($param['project_id']))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__. ' stat validate result project_id not given '.json_encode($_logs));

			return FormatHelper::result('', 'stat_result_project_id_not_given', Yii::t('app', 'stat_result_project_id_not_given'));
		}
		$projectId = $param['project_id'];

		if(empty($param['task_id']))
		{
			if(empty($param['step_id']))
			{
				//仅统计执行的绩效
				$stepIds = Step::find()->select(['id'])->where(['project_id' => $projectId, 'type' => Step::TYPE_PRODUCE, 'status' => Step::STATUS_NORMAL])->column();
			}
			else
			{
				$stepIds = is_array($param['step_id']) ? $param['step_id'] : [$param['step_id']];
			}
			$taskIds = Task::find()->select(['id'])->where(['project_id' => $projectId])->andWhere(['in', 'step_id', $stepIds])->column();
		}
		else
		{
			$taskIds = is_array($param['task_id']) ? $param['task_id'] : [$param['task_id']];
		}
		if(empty($taskIds))
		{
			Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__. ' stat validate result no task need stat '.json_encode($_logs));

			return FormatHelper::result('', 'stat_result_no_task_need_stat', Yii::t('app', 'stat_result_no_task_need_stat'));
		}

		$query = self::find()
			->select(['work_id', 'type', 'action', 'SUM(value) as total'])
			->where(['project_id' => $projectId]);
		if(!empty($param['work_id']))
		{
			$workIds = is_array($param['work_id']) ? $param['work_id'] : [$param['work_id']];
			$query->andWhere(['in', 'work_id', $workIds]);
		}

		//查询
		$statResults = $query->andWhere(['in', 'task_id', $taskIds])
			->andWhere(['!=', 'value', 0])
			->groupBy(['work_id', 'type', 'action'])
			->asArray()
			->all();

		//处理查询结果
		$labelNames = self::getLabelNames();
		$resultsByWork = [];
		foreach($statResults as $results)
		{
			$resultsByWork[$results['work_id']][] = $results; //按照task_id分组
		}
		foreach($resultsByWork as $workId => $results)
		{
			$resultsByType = [];
			foreach($results as $result)
			{
				$typeName = isset($labelNames[$result['type']]) ? $labelNames[$result['type']] : '';
				$resultsByType[$typeName][] = $result; //按照type分组
			}

			$allTypes = array_keys(self::getTypes());
			foreach($allTypes as $type)
			{
				$typeName = isset($labelNames[$type]) ? $labelNames[$type] : '';
				if(empty($resultsByType[$typeName]))
				{
					$statData[$workId][$typeName] = ['effective_count' => 0, 'allowed_count' => 0, 'toaudit_count' => 0, 'delete_count' => 0];
				}
				else //有效数据=已被通过+待审核
				{
					$submitTotal = 0; //提交总数
					$deleteTotal = 0; //删除总数
					$positiveTotal = 0; 
					$negativeTotal = 0;
					$effectiveTotal = 0; //有效标注总数
					$allowedTotal = 0; //已被通过总数
					$negativeAfterAllowedTotal = 0; //被通过之后重置或者驳回
					$toAuditTotal = 0; //待审核
					foreach($resultsByType[$typeName] as $result)
					{
						if(in_array($result['action'], [self::ACTION_ADD, self::ACTION_EDIT]))
						{
							$submitTotal += $result['total'];
						}
						else if(in_array($result['action'], [self::ACTION_DELETE]))
						{
							$deleteTotal += $result['total'];
						}
						else if(in_array($result['action'], [self::ACTION_ALLOWED]))
						{
							$positiveTotal += $result['total'];
						}
						else if(in_array($result['action'], [self::ACTION_REFUSED, self::ACTION_RESETED, self::ACTION_REDO]))
						{
							$negativeTotal += $result['total'];
						}
						else if(in_array($result['action'], [self::ACTION_REFUSED_AFTER_ALLOWED, self::ACTION_RESETED_AFTER_ALLOWED, self::ACTION_FORCEREFUSED_AFTER_ALLOWED, self::ACTION_FORCERESETED_AFTER_ALLOWED]))
						{
							$negativeAfterAllowedTotal += $result['total'];
						}
					}
					$effectiveTotal = $submitTotal - $negativeTotal - $negativeAfterAllowedTotal;
					$effectiveTotal = $effectiveTotal > 0 ? $effectiveTotal : 0;
					$allowedTotal = $positiveTotal - $negativeAfterAllowedTotal; //已被通过并且有效
					$allowedTotal = $allowedTotal > 0 ? $allowedTotal : 0;
					$toAuditTotal = $effectiveTotal - $allowedTotal;
					$toAuditTotal = $toAuditTotal > 0 ? $toAuditTotal : 0;

					$statData[$workId][$typeName] = ['effective_count' => $effectiveTotal, 'allowed_count' => $allowedTotal, 'toaudit_count' => $toAuditTotal, 'delete_count' => $deleteTotal];
				}
			}
			
		}

		return FormatHelper::result($statData, '', '');
	}
}