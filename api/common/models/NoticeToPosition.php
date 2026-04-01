<?php

namespace common\models;

use Yii;

/**
 * 通知(公告)表数据模型
 *
 */
class NoticeToPosition extends \yii\db\ActiveRecord
{
    const STATUS_NORMAL = Notice::STATUS_NORMAL;
    const STATUS_DELETED = Notice::STATUS_DELETED;

    // const TYPE_CUSTOMER = Notice::TYPE_CUSTOMER;//客户
    // const TYPE_ADMIN = Notice::TYPE_ADMIN;//管理员
    const TYPE_WORKER = Notice::TYPE_WORKER;//团队
    // const TYPE_CROWDSOURCING = Notice::TYPE_CROWDSOURCING;//众包
    // const TYPE_ROOT = Notice::TYPE_ROOT;//超级管理员
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notice_to_position';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['notice_id','type','position','status','read_count'], 'integer'],
            
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
        return Notice::getType($type);
    }
    
    /**
     * @return array
     */
    public static function getTypes()
    {
        return Notice::getTypes();
    }

    /**
     * 状态类型获取
     * @return   array
     */
    public static function getStatuses(){
        return Notice::getStatuses();
    }

    public static function getStatus($status)
    {
        return Notice::getStatus($status);
    }
    
    function getNotice(){
        return $this->hasOne(Notice::className(), ['id' => 'notice_id']);
    }
}
