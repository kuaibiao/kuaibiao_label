<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;

/**
 * 第三方表模型
 *
 */
class Thirdparty extends ModelComponent
{
    const STATUS_WAITING = 0;//待申请
    const STATUS_VERIFYING = 1;//审核中
    const STATUS_VERIFY_FAIL = 2;//审核失败
    const STATUS_PUBLISHING = 3;//发布中
    const STATUS_PUBLISH_SUCC = 4;//发布成功
    
    const TYPE_WEIXIN = 0;//微信小程序
    const TYPE_ALIPAY = 1;//支付宝小程序
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'thirdparty';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type','status','request_count','site_id','app_open_ad_status', 'app_open_ad_time'], 'integer'],
            [['type','status','request_count','site_id','app_open_ad_status', 'app_open_ad_time'], 'default', 'value' => 0],

            [['name','appid', 'appkey', 'appsecret', 'token', 'verify_reason', 'app_qr'], 'string'],
            [['name','appid', 'appkey', 'appsecret', 'token', 'verify_reason', 'app_qr'], 'default', 'value' => ''],
            
            [['created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['created_at', 'updated_at', 'deleted_at'], 'default', 'value' => 0],
            
            ['app_open_ad', 'string'],
            ['app_open_ad', 'default', 'value' => ''],
            
            ['app_category_index_top_ad', 'string'],
            ['app_category_index_top_ad', 'default', 'value' => ''],
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
            self::STATUS_WAITING => Yii::t('app','thirdparty_status_waiting'),
            self::STATUS_VERIFYING => Yii::t('app','thirdparty_status_verifying'),
            self::STATUS_VERIFY_FAIL => Yii::t('app','thirdparty_status_verify_fail'),
            self::STATUS_PUBLISHING => Yii::t('app','thirdparty_status_publishing'),
            self::STATUS_PUBLISH_SUCC => Yii::t('app','thirdparty_status_publish_succ'),
        ];
    }
    
    function getSite(){
        return $this->hasOne(Site::className(), ['id' => 'site_id']);
    }
}
