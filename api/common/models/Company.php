<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;


/**
 * Company 表数据模型
 *
 */
class Company extends \yii\db\ActiveRecord
{
    const TYPE_SCHOOL = 1;//公司类型 科研院校
    const TYPE_COMPANY = 2;//公司类型 企业

    const STATUS_ZERO = 0;//状态 正常
    const STATUS_DELETE = 99;//状态 删除

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'company';
    }
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type','created_by','created_at','updated_at'], 'integer'],
            ['name', 'string', 'min' => 1, 'max' => 254]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '机构名称',
            'type' => '机构类型',
            'created_at' => '创建日期',
            'created_by' => '创建人',
        ];
    }
    
    
    public static function getType($type)
    {
        $Types = self::getTypes();
    
        return isset($Types[$type]) ? $Types[$type] : '';
    }
    
    /**
     * @return array
     */
    public static function getTypes()
    {
        return [
            self::TYPE_SCHOOL => '科研院校',
            self::TYPE_COMPANY => '企业'
        ];
    }

    public static function getCompanys(){
        $companys = self::find()->select(['id','name'])->orderBy(['id'=>'asc'])->asArray()->all();
        $keys = array_column($companys,'id');
        $values = array_column($companys,'name');
        return array_combine($keys,$values);
    }

    public function getUser()
    {
        return $this->hasone(User::className(), ['id' => 'created_by'])
            ->select(['id','nickname']);
    }
    
}
