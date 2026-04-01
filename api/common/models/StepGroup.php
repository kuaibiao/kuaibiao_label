<?php

namespace common\models;

use Yii;
use common\helpers\JsonHelper;
use yii\behaviors\TimestampBehavior;

/**
 * step_group 表数据模型
 *
 */
class StepGroup extends \yii\db\ActiveRecord
{
    const STATUS_NORMAL = 0;
    const STATUS_DELETED = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'step_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['project_id', 'integer'],
            ['project_id', 'default', 'value' => 0],

            ['sort', 'integer'],
            ['sort', 'default', 'value' => 0],

            ['status', 'integer'],
            ['status', 'default', 'value' => self::STATUS_NORMAL],
            ['status', 'in', 'range' => [self::STATUS_NORMAL, self::STATUS_DELETED]],

            [['created_at', 'updated_at'], 'integer'],
            
            ['name', 'string', 'min' => 1, 'max' => 64],
            ['name', 'default', 'value' => ''],
            
            [['desc'], 'string', 'min' => 1, 'max' => 254],
            ['desc', 'default', 'value' => ''],
            
            ['is_load_result', 'integer'],
            ['is_load_result', 'default', 'value' => 0],
            
            ['execute_times', 'integer', 'min' => 1, 'max' => 6, 'integerOnly' => true],
            ['execute_times', 'default', 'value' => 0],
            ['audit_times', 'integer', 'min' => 1, 'max' => 6, 'integerOnly' => true],
            ['audit_times', 'default', 'value' => 0],
            
            ['template_id', 'integer'],
            ['template_id', 'default', 'value' => 0],

            ['category_id', 'integer'],
            ['category_id', 'default', 'value' => 0],

            ['condition', 'default', 'value' => ''],
            ['condition', 'string']
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
    
    public function getSteps()
    {
        return $this->hasMany(Step::className(), ['step_group_id' => 'id'])
        ->andWhere(['status' => Step::STATUS_NORMAL])
        ->select(['id', 'project_id', 'step_group_id', 'type', 'status', 'sort']);
    }
    
    //绑定模板模型
    public function getTemplate()
    {
        return $this->hasOne(Template::className(), ['id' => 'template_id'])->select(['id', 'name', 'type', 'config']);
    }
  
    public static function batchDelete($projectId, $aliveIds = null)
    {
        $_logs = ['$projectId' => $projectId, '$aliveIds' => $aliveIds];
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchDelete='.json_encode($_logs));
    
        $query = StepGroup::find()->select('id')->where(['project_id' => $projectId]);//, 'status' => self::STATUS_NORMAL
        $aliveIds && $query->andWhere(['not in', 'id', $aliveIds]);
        $ids = $query->asArray()->column();
        if ($ids)
        {
            $attributes = [
                'status' => StepGroup::STATUS_DELETED,
                'updated_at' => time()
            ];
            StepGroup::updateAll($attributes, ['in', 'id', $ids]);
            
            $attributes = [
                'status' => Step::STATUS_DELETED,
                'updated_at' => time()
            ];
            Step::updateAll($attributes, ['in', 'step_group_id', $ids]);
            
            $attributes = [
                'status' => Task::STATUS_DELETED,
                'updated_at' => time()
            ];
            Task::updateAll($attributes, ['in', 'step_group_id', $ids]);
            
//             $attributes = [
//                 'parent_step_group_id' => 0,
//                 'updated_at' => time()
//             ];
//             StepRelation::updateAll($attributes, ['in', 'parent_step_group_id', $ids]);
            
//             $attributes = [
//                 'status' => StepRelation::STATUS_DELETED,
//                 'updated_at' => time()
//             ];
//             StepRelation::updateAll($attributes, ['in', 'child_step_group_id', $ids]);
        }
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
    
    //获取工作组最后一层分步, 可为多个分步
    public function fetchLastSteps()
    {
        
    }
    
}
