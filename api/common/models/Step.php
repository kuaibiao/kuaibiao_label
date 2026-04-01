<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use common\helpers\JsonHelper;
/**
 * step 表数据模型
 *
 */
class Step extends \yii\db\ActiveRecord
{
    const STATUS_NORMAL = 0;
    const STATUS_DELETED = 1;

    const TYPE_PRODUCE = 0;//执行
    const TYPE_AUDIT = 1;//审核
    const TYPE_CHECK = 2;//质检
    const TYPE_ACCEPTANCE = 3;//质检
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'step';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['project_id', 'integer'],
            ['project_id', 'default', 'value' => 0],

            ['status', 'integer'],
            ['status', 'default', 'value' => self::STATUS_NORMAL],
            ['status', 'default', 'value' => [self::STATUS_NORMAL, self::STATUS_DELETED]],

            ['type', 'integer'],
            ['type','in', 'range' => [self::TYPE_PRODUCE, self::TYPE_AUDIT, self::TYPE_ACCEPTANCE]],
            ['type', 'default', 'value' => self::TYPE_PRODUCE],

            ['sort', 'integer'],
            ['sort', 'default', 'value' => 0],

            ['category_id', 'integer'],
            ['category_id', 'default', 'value' => 0],
            ['category_id', 'filter', 'filter' => function($value) {
                return intval($value);
            }],

            ['template_id', 'integer'],
            ['template_id', 'default', 'value' => 0],
            ['template_id', 'filter', 'filter' => function($value) {
                return intval($value);
            }],

            ['ai_model_id', 'integer'],
            ['ai_model_id', 'default', 'value' => 0],
            ['ai_model_id', 'filter', 'filter' => function($value) {
                return intval($value);
            }],

            [['created_at', 'updated_at'], 'integer'],
            
            ['is_load_result', 'default', 'value' => 0],
            ['is_load_result', 'filter', 'filter' => function($value) {
                return intval($value);
            }],
            ['is_load_result','in', 'range' => [0, 1]],
            
            ['name', 'string', 'min' => 1, 'max' => 254],
            ['name', 'default', 'value' => ''],

            ['condition', 'string', 'min' => 1, 'max' => 65535],
            ['condition', 'default', 'value' => ''],
            ['description', 'string', 'min' => 1, 'max' => 65535],
            ['description', 'default', 'value' => ''],
        ];
    }
    
    public function behaviors()
    {
        return [
            'timeStamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => time()
            ]
        ];
    }
    
    public static function getType($type)
    {
        $types = self::getTypes();
    
        return isset($types[$type]) ? $types[$type] : '';
    }
    
    /**
     * @return array
     * 待发布,审核中,执行中,已完成
     */
    public static function getTypes()
    {
        return [
            self::TYPE_PRODUCE => Yii::t('app', 'step_type_produce'),
            self::TYPE_AUDIT => Yii::t('app', 'step_type_audit'),
            self::TYPE_ACCEPTANCE => Yii::t('app', 'step_type_acceptance'),
        ];
    }
    
    public static function batchDelete($projectId, $aliveStepIds = null)
    {
        $_logs = ['$projectId' => $projectId, '$aliveStepIds' => $aliveStepIds];
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchDelete='.json_encode($_logs));
        
        $query = Step::find()->select('id')->where(['project_id' => $projectId, 'status' => Step::STATUS_NORMAL]);
        $aliveStepIds && $query->andWhere(['not in', 'id', $aliveStepIds]);
        $stepIds = $query->asArray()->column();
        if ($stepIds)
        {
            foreach ($stepIds as $stepId)
            {
                if (!in_array($stepId, $aliveStepIds))
                {
                    $attributes = [
                        'status' => Step::STATUS_DELETED,
                        'updated_at' => time()
                    ];
                    Step::updateAll($attributes, ['id' => $stepId]);
                }
            }
        }
        
        //清除关联关系
        $query = Task::find()->select(['id'])->where(['project_id' => $projectId,'status' => Task::STATUS_NORMAL]);
        $aliveStepIds && $query->andWhere(['not in', 'step_id', $aliveStepIds]);
        $taskIds = $query->asArray()->column();
        if ($taskIds)
        {
            foreach ($taskIds as $taskId)
            {
                $attributes = [
                    'status' => Task::STATUS_DELETED,
                    'updated_at' => time()
                ];
                Task::updateAll($attributes, ['id' => $taskId]);
            }
        }
        
        
        //删除对应关系
//         $attr = array();
//         $attr['project_id'] = $projectId;
//         Yii::$app->db->createCommand()->delete(StepRelation::tableName(), $attr)->execute();
    }
    
    public static function checkCondition($stepChildId, $parentWorkId)
    {
        $_logs = ['$stepChildId' => $stepChildId, '$parentWorkId' => $parentWorkId];
        
        $stepinfo = Step::find()->where(['id' => $stepChildId])->asArray()->limit(1)->one();
        
        if (empty($stepinfo['condition']) || !JsonHelper::is_json($stepinfo['condition']))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no condition '.json_encode($_logs));
            return true;
        }
        
        $stepConditionArr = JsonHelper::json_decode_all($stepinfo['condition']);
        $_logs['$stepConditionArr'] = $stepConditionArr;
        
        $resultInfo = WorkResult::find()->where(['work_id' => $parentWorkId])->asArray()->limit(1)->one();
        $resultArr = JsonHelper::json_decode_all($resultInfo['result']);
        $_logs['$resultArr'] = $resultArr;
        
        $resultInfoArr = [];
        if (!empty($resultArr['info']))
        {
            $resultInfoArr = $resultArr['info'];
        }
        $_logs['$resultInfoArr'] = $resultInfoArr;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' test1 '.json_encode($_logs));
        
        $isInfoOk = 0;
        foreach ($stepConditionArr as $sck => $scv)
        {
            if ($resultInfoArr)
            {
                foreach ($resultInfoArr as $riv)
                {
                    if (empty($riv['type']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result no data '.json_encode($_logs));
                        continue;
                    }
        
                    if (in_array($riv['type'], ['single-input', 'multi-input', 'form-radio', 'form-checkbox', 'form-select']))
                    {
                        if (!isset($scv['id']) || !isset($riv['id']))
                        {
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition no id '.json_encode($_logs));
                            continue;
                        }
        
                        if ($scv['id'] != $riv['id'])
                        {
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition id diff '.json_encode($_logs));
                            continue;
                        }
        
                        if (isset($scv['value']) && isset($riv['value']) && json_encode($scv['value']) == json_encode($riv['value']))
                        {
                            $isInfoOk += 1;
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition match ok '.json_encode($_logs));
                        }
                        else
                        {
                            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition match fail '.json_encode($_logs));
                        }
                    }
                    else
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition other type '.json_encode($_logs));
                    }
                }
            }
        
        }
        
        $_logs['$isInfoOk'] = $isInfoOk;
        
        //计算符合各条件的数量
        if ($isInfoOk < count($stepConditionArr))
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition count match fail '.json_encode($_logs));
            return false;
        }
        else
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' condition count match succ '.json_encode($_logs));
            return true;
        }
        
    }
    
	public static function getParentStepIds($stepId)
	{
	    $_logs = ['$stepId' => $stepId];
	    
	    $step = Step::find()->where(['id' => $stepId])->asArray()->one();
	    if (!$step['step_group_id'])
	    {
	        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no step_group_id '.json_encode($_logs));
	        return [];
	    }
	    
	    $stepGroup = StepGroup::find()->where(['id' => $step['step_group_id']])->asArray()->limit(1)->one();
	    if (!$stepGroup['parent_id'])
	    {
	        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no parent_id '.json_encode($_logs));
	        return [];
	    }
	    
	    $stepParentIds = Step::find()->select(['id'])->where(['step_group_id' => $stepGroup['parent_id']])->asArray()->column();
	
	    return $stepParentIds;
	}
	
	public static function getChildStepIds($stepId)
	{
	    $_logs = ['$stepId' => $stepId];
	    
	    $step = Step::find()->where(['id' => $stepId])->asArray()->one();
	    if (!$step['step_group_id'])
	    {
	        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no step_group_id '.json_encode($_logs));
	        return [];
	    }
	    
        $stepGroupChild = StepGroup::find()->where(['parent_id' => $step['step_group_id']])->asArray()->limit(1)->one();
        if (!$stepGroupChild)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $stepGroupChild '.json_encode($_logs));
            return [];
        }
        
        $stepChildIds = Step::find()->select(['id'])->where(['step_group_id' => $stepGroupChild['id']])->asArray()->column();
	
	    return $stepChildIds;
	}
	
	public static function getBrotherStepIds($stepId)
	{
	    $_logs = ['$stepId' => $stepId];
	    
	    $step = Step::find()->where(['id' => $stepId])->asArray()->one();
	    if (!$step['step_group_id'])
	    {
	        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no step_group_id '.json_encode($_logs));
	        return [];
	    }
	    
	    $stepBrotherIds = Step::find()->select(['id'])->where(['step_group_id' => $step['step_group_id']])->asArray()->column();
	
	    return $stepBrotherIds;
	}
	
}
