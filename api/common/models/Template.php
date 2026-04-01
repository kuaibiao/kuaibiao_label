<?php

namespace common\models;

use Yii;

/**
 * template 表数据模型
 *
 */
class Template extends \yii\db\ActiveRecord
{
    const STATUS_ENABLE = 0;  //启用
    const STATUS_DISABLE = 1;   //删除
    
    const TYPE_PUBLIC = 0;  //公共模板
    const TYPE_PRIVATE = 1;   //私有模板
    
    const COMPONENT_TYPE_TEXT = 'text';
    const COMPONENT_TYPE_IMAGE = 'image';
    const COMPONENT_TYPE_AUDIO = 'audio';
    const COMPONENT_TYPE_VIDEO = 'video';
    
    //const COMPONENT_TYPE_TEXT = 1;
    //const COMPONENT_TYPE_TEXT = 1;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'template';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            
            ['site_id', 'required'],
            ['site_id', 'integer'],
            
            ['name', 'trim'],
            ['name', 'required'],
        	['name', 'string', 'max' => 254],
            ['name', 'validateName'],
            
            [['user_id', 'parent_id', 'project_id', 'sort','type','created_at', 'updated_at'], 'integer'],
            
            ['category_id', 'required'],
            ['category_id', 'integer'],

            ['config', 'default', 'value' => ''],
            ['config', 'string', 'max' => 16777215],//mediumtext
        ];
    }
    
    public function validateName()
    {
        $_logs = ['name' => $this->name];
    
        if (!$this->hasErrors())
        {
            //规则, 不可与公共模板重名
            $template = Template::find()->where(['name' => $this->name, 'type' => Template::TYPE_PUBLIC])->orderBy(['id' => SORT_ASC])->limit(1)->asArray()->one();
            if ($template)
            {
                if ($template['id'] != $this->id)
                {
                    $this->addError('name', Yii::t('app', 'template_name_exist'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' domain_format_error '.json_encode($_logs));
                    return false;
                }
            }
            
            //规则, 不可与租户内重名
            $template = Template::find()->where(['name' => $this->name])->orderBy(['id' => SORT_ASC])->limit(1)->asArray()->one();
            if ($template)
            {
                if ($template['id'] != $this->id)
                {
                    $this->addError('name', Yii::t('app', 'template_name_exist'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' domain_format_error '.json_encode($_logs));
                    return false;
                }
            }
            
            return true;
        }
    }

    public static function getStatus($status)
    {
        $statuses = self::getStatuses();

        return isset($statuses[$status])?$statuses[$status]: '';
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_ENABLE => Yii::t('app', 'template_status_enable'),
            self::STATUS_DISABLE => Yii::t('app', 'template_status_disable')
        ];
    }
    
    public static function getType($type)
    {
        $types = self::getTypes();
    
        return isset($types[$type])?$types[$type]: '';
    }
    
    public static function getTypes()
    {
        return [
            self::TYPE_PUBLIC => Yii::t('app', 'template_type_public'),
            self::TYPE_PRIVATE => Yii::t('app', 'template_type_private')
        ];
    }

    //绑定用户
	public function getUser()
	{
		return $this->hasOne(User::className(), ['id' => 'user_id'])->select(['id','email', 'nickname']);
	}

    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id'])
                    ->select(['id', 'key']);
    }
}
