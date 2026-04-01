<?php

namespace common\models;

use Yii;
use common\helpers\JsonHelper;
use common\components\ModelComponent;

/**
 * data_result 表数据模型
 *
 */
class DataResult extends ModelComponent
{
    /**
     * 数据库表名
     *
     * @var string
     */
    public static $tableName = 'data_result';
    
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
            [['project_id','batch_id','data_id', 'ai_time'], 'integer'],
            [['data'], 'string', 'max' => 65535],//text=65535,mediumtext=16777215
            [['result', 'ai_result'], 'string', 'max' => 16777215],//text=65535,mediumtext=16777215
        ];
    }
    
    //合并结果
    public static function mergeResult($data_id, $result_)
    {
        $_logs = ['$data_id' => $data_id];
        
        if (is_array($result_))
        {
            if (!empty($result_['result']))
            {
                $result_ = $result_['result'];
            }
        }
        
        if (JsonHelper::is_json($result_))
        {
            $result_ = JsonHelper::json_decode_all($result_);
        }
        //$_logs['$result_'] = $result_;
        
        $dataResult = DataResult::find()->where(['data_id' => $data_id])->asArray()->limit(1)->one();
        //$_logs['$dataResult'] = $dataResult;
        
        $dataResultResult = [];
        if (!empty($dataResult['result']) && JsonHelper::is_json($dataResult['result']))
        {
            $dataResultResult = (array)JsonHelper::json_decode_all($dataResult['result']);
        }
        //$_logs['$dataResultResult'] = $dataResultResult;
        
        //标注结果处理, 因data中的元素的ID为自动分配, 所以此处需为替换data
        if (isset($result_['data']))
        {
            $dataResultResult['data'] = $result_['data'];
        }
        
        //审核结果处理, 因模板中的表单元素ID为固定, 所以此处为更新
        if (isset($result_['info']) && isset($dataResultResult['info']))
        {
            if (!empty($result_['info']))
            {
                $info = [];
                foreach ($result_['info'] as $k => $element)
                {
                    if (is_array($element) && !empty($element['id']))
                    {
                        $info[$element['id']] = $element;
                    }
                    else
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $result_.info format error!!! '.json_encode($_logs));
                    }
                }
                $result_['info'] = $info;
            }
            
            if (!empty($dataResultResult['info']) )
            {
                $info = [];
                foreach ($dataResultResult['info'] as $k => $element)
                {
                    if (is_array($element) && !empty($element['id']))
                    {
                        $info[$element['id']] = $element;
                    }
                    else
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $dataResultResult.info format error!!! '.json_encode($_logs));
                    }
                }
                $dataResultResult['info'] = $info;
            }
            
            //合并结果
            $dataResultResult['info'] = array_merge($dataResultResult['info'], $result_['info']);
            if (!empty($dataResultResult['info']))
            {
                $dataResultResult['info'] = array_values($dataResultResult['info']);
            }
        }
        elseif (isset($result_['info']))
        {
            $dataResultResult['info'] = $result_['info'];
        }
        
        //审核结果处理
        if (isset($result_['verify']))
        {
            $dataResultResult['verify'] = $result_['verify'];
        }
        //$_logs['$dataResultResult.1'] = $dataResultResult;
        
        //标注组
        if (isset($result_['group']))
        {
            $dataResultResult['group'] = $result_['group'];
        }
        
        $attributes = [
            'updated_at' => time(),
        ];
        Data::updateAll($attributes, ['id' => $data_id]);
        
        $attributes = [
            'result' => json_encode($dataResultResult),
        ];
        DataResult::updateAll($attributes, ['data_id' => $data_id]);
        
        //排查出现null的情况
        if (strpos($attributes['result'], 'null'))
        {
            $_logs['$result_'] = $result_;
            $_logs['$dataResult'] = $dataResult;
            $_logs['$dataResultResult'] = $dataResultResult;
            $_logs['$dataResultResult.1'] = $dataResultResult;
            
            Yii::trace(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' find null!!! '.json_encode($_logs));
        }
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
}
