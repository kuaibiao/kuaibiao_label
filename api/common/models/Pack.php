<?php

namespace common\models;

use Yii;

/**
 * file_pack
 *
 */
class Pack extends \yii\db\ActiveRecord
{
    const STATUS_ENABLE = 0;//正常
    const STATUS_PAUSED = 2;//暂停
    const STATUS_DISABLE = 1;//删除
    
    const EXTENSION_JPG = 0;
    const EXTENSION_PNG = 1;
    const EXTENSION_TXT = 2;
    
    const PACK_STATUS_DEFAULT = 0;//默认状态
    const PACK_STATUS_WAITING = 1;//待打包
    const PACK_STATUS_RUNNING = 2;//打包中
    const PACK_STATUS_SUCCESS = 3;//打包成功
    const PACK_STATUS_FAILURE = 4;//打包失败
    const PACK_STATUS_STOP = 5;//打包停止
    const PACK_STATUS_CANCELLED = 6;//打包取消, 对应STATUS_DISABLE
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pack';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id','batch_id','user_id','status','pack_status','pack_start_time','pack_end_time', 'created_at', 'updated_at'], 'integer'],
            [['pack_file','pack_message'], 'string', 'max' => 254],
            [['configs'], 'string', 'max' => 65535],//text=65535,mediumtext=16777215
        ];
    }
    
    public static function getPackStatus($status)
    {
        $statuses = self::getPackStatuses();
    
        return isset($statuses[$status]) ? $statuses[$status] : '';
    }
    public static function getStatuses()
    {
        return [
            self::STATUS_ENABLE => Yii::t('app', 'status_enable'),
            self::STATUS_DISABLE => Yii::t('app', 'status_disable'),
//             self::STATUS_PAUSED => Yii::t('app', 'status_paused'),
        ];
    }
    
    /**
     * @return array
     */
    public static function getPackStatuses()
    {
        return [
            self::PACK_STATUS_DEFAULT => Yii::t('app', 'pack_status_default'),
            self::PACK_STATUS_WAITING => Yii::t('app', 'pack_status_waiting'),
            self::PACK_STATUS_RUNNING => Yii::t('app', 'pack_status_running'),
            self::PACK_STATUS_SUCCESS => Yii::t('app', 'pack_status_success'),
            self::PACK_STATUS_FAILURE => Yii::t('app', 'pack_status_failure'),
            self::PACK_STATUS_STOP => Yii::t('app', 'pack_status_stop'),
//             self::PACK_STATUS_CANCELLED => Yii::t('app', 'pack_status_cancelled'),
        ];
    }
    
    public static function getExtention($key)
    {
        $arr = self::getExtentions();
    
        return isset($arr[$key]) ? $arr[$key] : '';
    }
    
    public static function getExtentions()
    {
        return [
            self::EXTENSION_TXT => Yii::t('app', 'pack_extension_txt'),
            self::EXTENSION_JPG => Yii::t('app', 'pack_extension_jpg'),
            self::EXTENSION_PNG => Yii::t('app', 'pack_extension_png'),
        ];
    }

    public function getBatch()
    {
        return $this->hasOne(Batch::className(), ['id' => 'batch_id']);
    }
    public function getPackScript()
    {
        return $this->hasOne(PackScript::className(), ['id' => 'pack_script_id']);
    }
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['id' => 'project_id'])->select(['id','name']);
    }
    
    public static function allowProcesses($packId)
    {
        $_logs = ['$packId' => $packId];
    
        $pack = Pack::find()->where(['id' => $packId])->limit(1)->asArray()->one();
        if (in_array($pack['pack_status'], [Pack::PACK_STATUS_STOP]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' fail '.json_encode($_logs));
            return false;
        }
    
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
        return true;
    }
    
    public static function cleanUpProcesses()
    {
        //当前进程id
        $_logs['pid'] = getmypid();
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' end success '.json_encode($_logs));
        Yii::$app->end();

    }

    public function getStep()
    {
        return $this->hasOne(Step::className(), ['id' => 'step_id'])->select(['id', 'name', 'type', 'status']);
    }
}
