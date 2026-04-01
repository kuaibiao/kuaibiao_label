<?php

namespace common\models;

use Yii;

/**
 * batch 表数据模型
 *
 */
class Batch extends \yii\db\ActiveRecord
{
    const STATUS_WAITING = 0;//待解包
    const STATUS_ENABLE = 1;//执行中
    const STATUS_DISABLE = 2;//已暂停
    const STATUS_DELETED = 3;//已删除
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'batch';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id','sort','status','amount','upload_data_count','created_at', 'updated_at'], 'integer'],
            
            ['name', 'default', 'value' => ''],
            ['name', 'filter', 'filter' => function($value) {
                //自动截取前250字符串
                return strlen($value) > 250 ? substr($value, -250, 250) : $value;
            }],
            ['name', 'string', 'max' => 254],
            
            ['path', 'default', 'value' => ''],
            ['path', 'string', 'max' => 254],
        ];
    }
    
    public static function getStatus($status)
    {
        $statuses = self::getStatuses();
        
        return isset($statuses[$status]) ? $statuses[$status] : '';
    }
    
    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_WAITING => Yii::t('app','batch_status_waiting'),
            self::STATUS_ENABLE => Yii::t('app','batch_status_enable'),
            self::STATUS_DISABLE => Yii::t('app','batch_status_disable'),
        ];
    }
    
    public static function getSort($sort)
    {
        return sprintf(Yii::t('app', 'batch_sort_name'), $sort);
    }
    
    public static function batchDelete($projectId)
    {
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchDelete='.json_encode([$projectId]));
        
        $attr = array();
        $attr['project_id'] = $projectId;
        
        Yii::$app->db->createCommand()->delete(self::tableName(), $attr)->execute();
    }
    
}