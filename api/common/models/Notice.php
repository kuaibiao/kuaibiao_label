<?php

namespace common\models;

use Yii;

/**
 * 通知(公告)表数据模型
 *
 */
class Notice extends \yii\db\ActiveRecord
{
    const STATUS_NORMAL = 0;
    const STATUS_DELETED = 1;

    // const TYPE_CUSTOMER = 0;//客户
    // const TYPE_ADMIN = 1;//管理员
    const TYPE_WORKER = 2;//团队
    // const TYPE_CROWDSOURCING = 3;//众包
    // const TYPE_ROOT = 5;//超级管理员
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notice';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id','status','read_count','created_at', 'updated_at'], 'integer'],
            
            [['content', 'title'], 'required'],
            ['content', 'string', 'min' => 1, 'max' => 2000],
            ['title', 'string', 'min' => 1, 'max' => 60],
            
            ['status', 'in', 'range' => array_keys(self::getStatuses())],
            
            ['show_start_time', 'filter', 'filter' => function($value) {
                //转化为当天0点的时间
                $time = is_numeric($value) ? $value : strtotime($value);
                return strtotime(date('Y-m-d', $time));
            }],
            ['show_end_time', 'filter', 'filter' => function($value) {
                //转化为当天0点的时间+24小时
                $time = is_numeric($value) ? $value : strtotime($value);
                return strtotime(date('Y-m-d', $time)) + 24*3600 - 1;
            }],
            [['show_start_time', 'show_end_time'], 'required'],
            [['show_start_time', 'show_end_time'], 'integer'],
            ['show_start_time', 'compare', 'compareAttribute' => 'show_end_time', 'operator' => '<='],
        ];
    }
    
    public static function getType($type)
    {
        $types = self::getTypes();
        return isset($types[$type]) ? $types[$type] : null;
    }
    
    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            //self::TYPE_CUSTOMER => yii::t('app', 'notice_type_customer'),
            // self::TYPE_ADMIN => yii::t('app', 'notice_type_admin'),
            self::TYPE_WORKER => yii::t('app', 'notice_type_worker'),
            // self::TYPE_ROOT => yii::t('app', 'notice_type_root'),
            //self::TYPE_CROWDSOURCING => yii::t('app', 'notice_type_crowdsourcing'),
        ];
    }
    
    public static function getStatus($status)
    {
        $statuses = self::getStatuses();
        return isset($statuses[$status]) ? $statuses[$status] : null;
    }
    
    public static function getStatuses(){
        return [
            self::STATUS_DELETED => yii::t('app', 'notice_status_deleted'),
            self::STATUS_NORMAL => yii::t('app', 'notice_status_normal')
        ];
    }
    
    function getSender(){
        return $this->hasOne(User::className(), ['id' => 'user_id'])->select(['id', 'email', 'avatar', 'nickname']);
    }

    function getPositions(){
        return $this->hasMany(NoticeToPosition::className(), ['notice_id' => 'id'])->select(['notice_id', 'type']);
    }
}
