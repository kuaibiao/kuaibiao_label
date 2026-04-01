<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;
use common\helpers\StringHelper;
use common\helpers\ArrayHelper;
use common\helpers\JsonHelper;

/**
 * work_result 表数据模型
 * 
 * 存储本次作业的结果
 * 格式为:
 * {
 *      'data' : [{'type' : 'rect', ...},{...}],
 *      'info' : [],
 *      'verify': [],
 * }
 * 
 */
class WorkResult extends ModelComponent
{
    /**
     * 数据库表名
     *
     * @var string
     */
    public static $tableName = 'work_result';
    
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
            ['work_id', 'integer' ],
            ['work_id', 'default', 'value' => 0],

            ['result', 'string', 'max' => 16777215],
            ['result', 'default', 'value' => ''],

            ['feedback', 'string', 'max' => 65535],//text=65535,mediumtext=16777215
            ['feedback', 'default', 'value' => ''],
        ];
    }
    
    /**
     * 合并最终结果
     * @param int $id
     * @param array|string $newResult 新结果
     */
    public static function mergeResult($id, $newResult)
    {
        $_logs = [];
        $_logs['$id'] = $id;
        $_logs['$newResult'] = ArrayHelper::var_desc($newResult);
        
        //---------------------------------------------------
        // 把$newResult转化成数组
        if (StringHelper::is_json($newResult))
        {
            $newResult = JsonHelper::json_decode_all($newResult);
        }
        if (!empty($newResult['result']))
        {
            $newResult = $newResult['result'];
        }
        $_logs['$newResult.new'] = ArrayHelper::var_desc($newResult);
        
        //---------------------------------------------------
        
        //查询老结果
        $workResult = WorkResult::find()->where(['id' => $id])->asArray()->limit(1)->one();
        
        $workResultResult = [];
        if (!empty($workResult['result']) && StringHelper::is_json($workResult['result']))
        {
            $workResultResult = (array)JsonHelper::json_decode_all($workResult['result']);
        }
        $_logs['$workResultResult'] = ArrayHelper::var_desc($workResultResult);
        
        
        //-----------------------------------------------------
        
        if ($newResult && is_array($newResult))
        {
            foreach ($newResult as $field => $val)
            {
                
                //标注结果处理, 因data中的元素的ID为自动分配, 所以此处需为替换data
                if ($field == 'data')
                {
                    $workResultResult['data'] = $newResult['data'];
                }
                
                //审核结果处理, 因模板中的表单元素ID为固定, 所以此处为更新
                elseif ($field == 'info')
                {
                    $workResultResult['info'] = $newResult['info'];
                }
                
                //审核结果处理
                elseif ($field == 'verify')
                {
                    $workResultResult['verify'] = $newResult['verify'];
                }
                //$_logs['$workResultResult.1'] = $workResultResult;
                
                //标注组
                elseif ($field == 'group')
                {
                    $workResultResult['group'] = $newResult['group'];
                }
                //其他情况
                else
                {
                    $workResultResult[$field] = $newResult[$field];
                }
            }
        }
        
        //----------------------------------------------------------
        //更新结果版本号
        $workResultResult['version'] = time();
        
        $attributes = [
            'result' => json_encode($workResultResult),
        ];
        WorkResult::updateAll($attributes, ['id' => $id]);
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
}
