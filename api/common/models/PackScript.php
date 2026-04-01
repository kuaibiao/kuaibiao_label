<?php

namespace common\models;

use Yii;

/**
 * file_pack
 *
 */
class PackScript extends \yii\db\ActiveRecord
{
    const STATUS_ENABLE = 0;//正常状态
    const STATUS_DISABLE = 1;//隐藏状态

    const TYPE_COMMON = 0; //通用
    const TYPE_IMAGE = 1; //图片
    const TYPE_AUDIO = 2; //语音
    const TYPE_TEXT = 3; //文本
    const TYPE_VIDEO = 4; //视频
    const TYPE_3D = 5; //3d

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pack_script';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type'], 'integer'],
            [['name','script'], 'string'],
        ];
    }

}
