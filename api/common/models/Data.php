<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;
use common\helpers\FileHelper;
use common\helpers\ArrayHelper;
use common\helpers\JsonHelper;
use common\helpers\StringHelper;
/**
 * data 表数据模型
 *
 */
class Data extends ModelComponent
{
    /**
     * 数据库表名
     * 
     * @var string
     */
    public static $tableName = 'data';
    
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
            [['project_id','batch_id','sort','data_parent_id','created_at','updated_at'], 'integer'],
            [['name', 'data_key'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'project_id' => 'project_id',
            'status' => 'status',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
    
    public static function filterName($v, $maxLongth = 128)
    {
        $_logs = ['$v' => ArrayHelper::var_desc($v), '$maxLongth' => $maxLongth];
    
        $name_ = '';
        if (isset($v['name']))
        {
            $name_ = $v['name'];
        }
//         elseif (is_array($v) && count($v) == 1)
//         {
//             $name_ = array_pop($v);
//             if (!is_string($name_))
//             {
//                 $name_ = serialize($name_);
//             }
//         }
        elseif (is_array($v))
        {
            foreach ($v as $k_ => $v_)
            {
                if (is_string($v_) && !empty($v_))
                {
                    if (mb_strlen($v_, 'utf-8') < 32)
                    {
                        $name_ .= ($name_ ? ',' : '') . $v_;
                    }
                    else if(StringHelper::is_url($v_)) //判断是否是url, 从后往前截取
                    {
                        $v_ = FileHelper::filebasename($v_);
                        $name_ .= ($name_ ? ',' : '') . $v_;
                    }
                    else if(StringHelper::is_relativepath($v_)) //判断是否是相对路径, 从后往前截取
                    {
                        $v_ = FileHelper::filebasename($v_);
                        $name_ .= ($name_ ? ',' : '') . $v_;
                    }
                    else
                    {
                        $name_ .= ($name_ ? ',' : '') . $v_;
                    }
                }
            }
    
            if (empty($name_))
            {
                $name_ = array_pop($v);
                if (is_array($name_))
                {
                    $name_ = self::filterName($name_);
                }
                else
                {
                    $name_ = serialize($name_);
                }
            }
        }
        $name_ = StringHelper::toutf8($name_);
        $_logs['$name_'] = $name_;
    
        //控制长度
        if (mb_strlen($name_, 'utf-8') > $maxLongth)
        {
            if(StringHelper::is_url($name_)) //判断是否是url, 从后往前截取
            {
                $_logs['$type'] = 'url';
                $name_ = FileHelper::filebasename($name_);
                if (mb_strlen($name_, 'utf-8') > $maxLongth)
                {
                    $name_ = mb_substr($name_, -$maxLongth, $maxLongth, 'utf-8');
                }
            }
            else if(StringHelper::is_relativepath($name_)) //判断是否是相对路径, 从后往前截取
            {
                $_logs['$type'] = 'relativepath';
                $name_ = FileHelper::filebasename($name_);
                if (mb_strlen($name_, 'utf-8') > $maxLongth)
                {
                    $name_ = mb_substr($name_, -$maxLongth, $maxLongth, 'utf-8');
                }
            }
            else if(is_string($name_)) //就是文本类字符串, 从前往后截取
            {
                $_logs['$type'] = 'string';
                $name_ = mb_substr($name_, 0, $maxLongth, 'utf-8');
            }
        }
    
        $_logs['$name_.new'] = $name_;
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' filter complete '.json_encode($_logs));
    
        return $name_;
    }
    
    /**
     * 批量存储
     * 
     * @param int $project_id
     * @param int $batch_id
     * @param int $data
     * @return boolean
     */
    public static function batchInsert($project_id, $batch_id, $data)
    {
        $_logs = ['$project_id' => $project_id, '$batch_id' => $batch_id, 'count' => count($data)];
        
        $startTime = microtime(true);
        
        $itemFileds = ['project_id','batch_id','name','sort','created_at', 'updated_at'];
        $created_at = $updated_at = time();
        $resultFileds = ['project_id','batch_id','data_id','data', 'result','ai_result'];
        
        $project = Project::find()->where(['id' => $project_id])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project not found '.json_encode($_logs));
            return false;
        }
        
        Data::setTable($project['table_suffix']);
        DataResult::setTable($project['table_suffix']);
        
        $onceCount = 10;
        $sorts = [];
        $items = [];
        $results = [];
        $aiResults = [];
        foreach ($data as $i => $v)
        {
            $sort_ = $i+1;
            
            //默认结果
            if (!empty($v['ai_result']) && JsonHelper::is_json($v['ai_result']))
            {
                $aiResults[$i] = $v['ai_result'];
            }
            unset($v['ai_result']);
            $data[$i] = $v;
            
            //处理name值
            $name_ = self::filterName($v, 128);
            
            $sorts[] = $sort_;
            $items[] = array(
                $project_id,
                $batch_id,
                $name_,
                $sort_,
                $created_at,
                $updated_at
            );
            
            //积累到N条, 则批量执行一次
            if (count($items) >= $onceCount )
            {
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchInsert '.$onceCount.' '.json_encode($_logs));
                Yii::$app->db->createCommand()->batchInsert(self::tableName(), $itemFileds, $items)->execute();
                
                $dataList = Data::find()
                ->select(['id', 'sort'])
                ->where(['project_id' => $project_id, 'batch_id' => $batch_id])
                ->andWhere(['in', 'sort', $sorts])
                ->asArray()->all();
                
                foreach ($dataList as $dataItem)
                {
                    $sort_ = $dataItem['sort'] - 1;
                    $results[] = [
                        $project_id,
                        $batch_id,
                        $dataItem['id'],
                        isset($data[$sort_]) ? json_encode($data[$sort_]) : '',
                        '',
                        $aiResults && isset($aiResults[$sort_]) ? $aiResults[$sort_] : ''
                    ];
                }
                
                if (count($results) != $onceCount)
                {
                    $_logs['$results.count'] = count($results);
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $results count != '.$onceCount.' '.json_encode($_logs));
                    return false;
                }
                
                Yii::$app->db->createCommand()->batchInsert(DataResult::tableName(), $resultFileds, $results)->execute();
                
                $sorts = [];
                $items = [];
                $results = [];
                $aiResults = [];
            }
        }
        
        //剩余的数据需要再执行一次
        if ($items)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchInsert last '.json_encode($_logs));
            Yii::$app->db->createCommand()->batchInsert(self::tableName(), $itemFileds, $items)->execute();
            
            $dataList = Data::find()
            ->select(['id', 'sort'])
            ->where(['project_id' => $project_id, 'batch_id' => $batch_id])
            ->andWhere(['in', 'sort', $sorts])
            ->asArray()->all();
            
            foreach ($dataList as $dataItem)
            {
                $sort_ = $dataItem['sort'] - 1;
                $results[] = [
                    $project_id,
                    $batch_id,
                    $dataItem['id'],
                    isset($data[$sort_]) ? json_encode($data[$sort_]) : '',
                    '',
                    $aiResults && isset($aiResults[$sort_]) ? $aiResults[$sort_] : ''
                ];
            }
            
            if (count($results) > $onceCount)
            {
                $_logs['$results.count'] = count($results);
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $results count > '.$onceCount.' '.json_encode($_logs));
                return false;
            }
            
            Yii::$app->db->createCommand()->batchInsert(DataResult::tableName(), $resultFileds, $results)->execute();
            
            $sorts = [];
            $items = [];
            $results = [];
            $aiResults = [];
        }
        
        $usedTime = microtime(true) - $startTime;
        $_logs['$usedTime'] = $usedTime;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    //绑定分类关联模型
    public function getDataResult(){ 
        return $this->hasOne(DataResult::className(), ['data_id' => 'id']);
    }
    
    //绑定分类关联模型
    public function getProject(){
        return $this->hasOne(Project::className(), ['id' => 'project_id']);
    }
    
    //绑定分类关联模型
    public function getBatch(){
        return $this->hasOne(Batch::className(), ['id' => 'batch_id']);
    }
    
}
