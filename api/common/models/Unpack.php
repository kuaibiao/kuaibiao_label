<?php

namespace common\models;

use Yii;
use common\helpers\JsonHelper;

/**
 * batch_file 表数据模型
 * 客户上传的数据文件包
 *
 */
class Unpack extends \yii\db\ActiveRecord
{
    const STATUS_ENABLE = 0;
    const STATUS_DISABLE = 1;
    
    const UNPACK_STATUS_DEFAULT = 0;//默认状态
    const UNPACK_STATUS_WAITING = 1;//待解包
    const UNPACK_STATUS_RUNNING = 2;//解包中
    const UNPACK_STATUS_SUCCESS = 3;//解包成功
    const UNPACK_STATUS_FAILURE = 4;//解包失败
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'unpack';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id','user_id','status', 'created_at', 'updated_at'], 'integer'],
            [['filename', 'filepath'], 'string', 'max' => 254],
            ['filesize', 'double'],
            [['unpack_status', 'unpack_start_time','unpack_end_time','unpack_progress'], 'integer'],
            
            /**
             *
             * 方案:采用json_encode(['翻译的key' => [翻译的占位符替换值, 翻译的占位符替换值, ...]])
             * demo
             * $unpack->unpack_message = json_encode(['label_unpack_succ_tip_format_error' => ['txt', 'json']]);
             */
            ['unpack_message', 'string', 'max' => 65535],
            ['unpack_message', 'default', 'value' => ''],
        ];
    }

    public static function getUnpackStatus($var)
    {
        $vars = self::getUnpackStatuses();
    
        return isset($vars[$var]) ? $vars[$var] : '';
    }
    
    /**
     * @return array
     */
    public static function getUnpackStatuses()
    {
        return [
            self::UNPACK_STATUS_DEFAULT => Yii::t('app', 'unpack_status_default'),
            self::UNPACK_STATUS_WAITING => Yii::t('app', 'unpack_status_waiting'),
            self::UNPACK_STATUS_RUNNING => Yii::t('app', 'unpack_status_running'),
            self::UNPACK_STATUS_SUCCESS => Yii::t('app', 'unpack_status_success'),
            self::UNPACK_STATUS_FAILURE => Yii::t('app', 'unpack_status_failure'),
        ];
    }
    
    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ENABLE => Yii::t('app', 'unpack_status_enable'),
            self::STATUS_DISABLE => Yii::t('app', 'unpack_status_disable')
        ];
    }
    
    public function getProject()
    {
        return $this->hasOne(Project::className(), ['id' => 'project_id'])
        ->select(['id','name']);
    }
    
    public static function translate($unpack_message)
    {
        $unpack_messages = [];
        if($unpack_message)
        {
            $unpack_messages_ = JsonHelper::json_decode_all($unpack_message);
        
            //翻译解包时的提示信息
            if ($unpack_messages_ && is_array($unpack_messages_))
            {
                foreach($unpack_messages_ as $langKey => $langArgs)
                {
                    if ($langKey)
                    {
                        $langVal = Yii::t('app', $langKey);
                        
                        if (strpos($langVal, '%s') === false)
                        {
                            $unpack_messages[] = $langVal;
                        }
                        elseif ($langArgs)
                        {
                            $unpack_messages[] = vsprintf(Yii::t('app', $langKey), $langArgs);
                        }
                    }
                }
            }
        }
        
        return $unpack_messages;
    }
    
}
