<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;

/**
 * work_record 表数据模型
 *
 */
class WorkRecord extends ModelComponent
{
    const TYPE_FETCH = 0;
    const TYPE_EXECUTE = 1;
    const TYPE_SUBMIT = 2;
    const TYPE_GIVEUP = 6;
    const TYPE_TIMEOUT = 7;
    const TYPE_DIFFICULT = 9;
    const TYPE_AUDITALLOW = 10;
    const TYPE_AUDITREFUSE = 11;
    const TYPE_AUDITRESET = 12;
    const TYPE_AUDITIGNORE = 18;
    const TYPE_AUDITALLOWED = 13;
    const TYPE_AUDITREFUSED = 14;
    const TYPE_AUDITRESETED = 15;
    const TYPE_AUDITIGNORED = 19;
    const TYPE_PARENTREFUSED = 16;
    const TYPE_PARENTRESETED = 17;
    const TYPE_REFUSEREVISE = 20;
    const TYPE_REFUSERESET = 21;
    const TYPE_REFUSESUBMITREVISE = 22;
    const TYPE_REFUSESUBMITRESET = 23;
    const TYPE_REDO = 40;
    const TYPE_PARENTREDO = 41;
    const TYPE_FORCEREFUSE = 50;
    const TYPE_FORCERESET = 51;
    const TYPE_PARENTFORCEREFUSE = 52;
    const TYPE_PARENTFORCERESET = 53;
    const TYPE_DIFFICULTREVISE = 60;
    const TYPE_DIFFICULTRESET = 61;
    const TYPE_BACKTOSUBMIT = 70;
    const TYPE_AUDITEDITSUBMIT = 71;
    const TYPE_BACKTOREFUSE = 72;
    const TYPE_TEMPORARYSTORAGE = 73;
    
    /**
     * 数据库表名
     *
     * @var string
     */
    public static $tableName = 'work_record';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return self::$tableName;
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['project_id','batch_id','step_id','task_id','data_id','work_id', 'type', 'after_user_id', 'before_user_id'], 'integer'],
        ];
    }
    
    public static function getType($type)
    {
        $types = self::getTypes();
        return isset($types[$type]) ? $types[$type] : '';
    }
    
    /**
     * @return array
     * 待发布,审核中,执行中,已完成
     */
    public static function getTypes()
    {
        return [
            self::TYPE_FETCH => Yii::t('app', 'work_record_type_fetch'),
            self::TYPE_EXECUTE => Yii::t('app', 'work_record_type_execute'),
            self::TYPE_SUBMIT => Yii::t('app', 'work_record_type_submit'),
            self::TYPE_GIVEUP => Yii::t('app', 'work_record_type_giveup'),
            self::TYPE_TIMEOUT => Yii::t('app', 'work_record_type_timeout'),
            self::TYPE_DIFFICULT => Yii::t('app', 'work_record_type_difficult'),
            self::TYPE_AUDITALLOW => Yii::t('app', 'work_record_type_auditallow'),
            self::TYPE_AUDITREFUSE => Yii::t('app', 'work_record_type_auditrefuse'),
            self::TYPE_AUDITRESET => Yii::t('app', 'work_record_type_auditreset'),
            self::TYPE_AUDITALLOWED => Yii::t('app', 'work_record_type_auditallowed'),
            self::TYPE_AUDITREFUSED => Yii::t('app', 'work_record_type_auditrefused'),
            self::TYPE_AUDITRESETED => Yii::t('app', 'work_record_type_auditreseted'),
            self::TYPE_FORCEREFUSE => Yii::t('app', 'work_record_type_forcerefuse'),
            self::TYPE_FORCERESET => Yii::t('app', 'work_record_type_forcereset'),
            self::TYPE_PARENTREFUSED => Yii::t('app', 'work_record_type_parentrefused'),
            self::TYPE_PARENTRESETED => Yii::t('app', 'work_record_type_parentreseted'),
            self::TYPE_PARENTFORCEREFUSE => Yii::t('app', 'work_record_type_parentforcerefuse'),
            self::TYPE_PARENTFORCERESET => Yii::t('app', 'work_record_type_parentforcereset'),
            self::TYPE_REFUSEREVISE => Yii::t('app', 'work_record_type_refuserevise'),
            self::TYPE_REFUSERESET => Yii::t('app', 'work_record_type_refusereset'),
            self::TYPE_REFUSESUBMITREVISE => Yii::t('app', 'work_record_type_refusesubmitreverse'),
            self::TYPE_REFUSESUBMITRESET => Yii::t('app', 'work_record_type_refusesubmitreset'),
            self::TYPE_DIFFICULTREVISE => Yii::t('app', 'work_record_type_difficultreverse'),
            self::TYPE_DIFFICULTRESET => Yii::t('app', 'work_record_type_difficultreset'),
            self::TYPE_REDO => Yii::t('app', 'work_record_type_redo'),
            self::TYPE_PARENTREDO => Yii::t('app', 'work_record_type_parentredo'),
            self::TYPE_BACKTOSUBMIT => Yii::t('app', 'work_record_type_backtosubmit'),
            self::TYPE_AUDITEDITSUBMIT => Yii::t('app', 'work_record_type_auditeditsubmit'),
            self::TYPE_BACKTOREFUSE => Yii::t('app', 'work_record_type_backtorefuse'),
            self::TYPE_TEMPORARYSTORAGE => Yii::t('app', 'work_record_type_temporarystorage'),
        ];
    }
    
    //关联批次
    function getBeforeUser(){
        return $this->hasOne(User::className(), ['id' => 'before_user_id'])->select(['id', 'email', 'nickname']);
    }
    function getAfterUser(){
        return $this->hasOne(User::className(), ['id' => 'after_user_id'])->select(['id', 'email', 'nickname']);
    }
    function getStep(){
        return $this->hasOne(Step::className(), ['id' => 'step_id'])->select(['id', 'name', 'type']);
    }
}
