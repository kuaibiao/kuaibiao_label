<?php

namespace common\models;

use Yii;

/**
 * Task 数据表模型
 *
 */
class Task extends \yii\db\ActiveRecord
{
    
    const STATUS_NORMAL = 0;//正常
    const STATUS_FINISH = 1;//结束
    const STATUS_PAUSED = 2;//暂停
    const STATUS_DELETED = 5;//删除
    
    
    const TYPE_NORMAL = 0;//正式
    const TYPE_TEST = 1;//试标
    
    const UNIT_PRICE_TYPE_ONE = '0';//一份作业/一张图/一次作业
    const UNIT_PRICE_TYPE_LABEL = '1';//一个图形/一段语音
    const UNIT_PRICE_TYPE_POINT = '2';//一个点
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id','batch_id','step_id','created_at', 'updated_at'], 'integer'],
            
            [['name', 'description'], 'string', 'max' => 254],
            
            [['receive_count','receive_expire','max_times'], 'integer'],
            
            ['platform_type', 'integer'],
            ['platform_type', 'default', 'value' => 0],
            
            ['platform_id', 'integer'],
            ['platform_id', 'default', 'value' => 0],
            
            ['platform_site_id', 'integer'],
            ['platform_site_id', 'default', 'value' => 0],
            
            ['start_time', 'filter', 'filter' => function($value) {
                return is_numeric($value) ? $value : strtotime($value);
            }],
            ['end_time', 'filter', 'filter' => function($value) {
                return is_numeric($value) ? $value : strtotime($value);
            }],
            [['start_time', 'end_time'], 'integer'],
            ['start_time', 'compare', 'compareAttribute' => 'end_time', 'operator' => '<='],
            
            ['is_public', 'integer'],
            ['is_public', 'default', 'value' => 0],
            
        ];
    }
    
    public static function getType($val)
    {
        $vals = self::getTypes();
        return isset($vals[$val]) ? $vals[$val] : null;
    }
    
    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_NORMAL => Yii::t('app', 'task_type_normal'),
            self::TYPE_TEST => Yii::t('app', 'task_type_test'),
        ];
    }
    
    public static function getStatus($val)
    {
        $vals = self::getStatuses();
        return isset($vals[$val]) ? $vals[$val] : null;
    }
    
    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_NORMAL => Yii::t('app', 'task_status_normal'),
            self::STATUS_FINISH => Yii::t('app', 'task_status_finished'),
            self::STATUS_PAUSED => Yii::t('app', 'task_status_paused'),
            self::STATUS_DELETED => Yii::t('app', 'task_status_deleted'),
        ];
    }
    
    public static function getUnitPriceType($val)
    {
        $vals = self::getUnitPriceTypes();
        return isset($vals[$val]) ? $vals[$val] : null;
    }
    
    /**
     * @return array
     */
    public static function getUnitPriceTypes()
    {
        return [
            self::UNIT_PRICE_TYPE_ONE => Yii::t('app', 'task_unit_price_type_one'),
            self::UNIT_PRICE_TYPE_LABEL => Yii::t('app', 'task_unit_price_type_label'),
            self::UNIT_PRICE_TYPE_POINT => Yii::t('app', 'task_unit_price_type_point'),
        ];
    }

    //绑定众包用户模型
    public function getProject(){
        return $this->hasOne(Project::className(), ['id' => 'project_id'])
        ->select(['id', 'category_id', 'template_id', 'name', 'user_id', 'assign_type'])
        ->with('category');
    }
    
    //关联批次
    function getBatch(){
        return $this->hasOne(Batch::className(), ['id' => 'batch_id']);
    }
    
    //关联批次
    function getStep(){
        return $this->hasOne(Step::className(), ['id' => 'step_id']);
    }
    
    //绑定众包用户模型
    public function getStat(){
        //SELECT * FROM `stat` WHERE (`batch_id`, `step_id`) IN (('1259', '1736'), ('1259', '1735'))
        return $this->hasOne(Stat::className(), ['batch_id' => 'batch_id', 'step_id' => 'step_id']);
    }

}