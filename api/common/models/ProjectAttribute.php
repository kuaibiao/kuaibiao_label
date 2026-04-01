<?php

namespace common\models;

use Yii;

/**
 * project_attribute 表数据模型
 *
 */
class ProjectAttribute extends \yii\db\ActiveRecord
{
    const UPLOADFILE_TYPE_WEB = 'web';
    const UPLOADFILE_TYPE_FTP = 'ftp';
    const UPLOADFILE_TYPE_SSH = 'ssh';
    
    const UPLOADFILE_EXTS = ['csv','cvs','xls', 'xlsx', 'zip', 'txt', '', 'mp4', 'avi', 'wma', 'wmv', 'mkv'];
    const UPLOADFILE_EXTS1 = ['zip'];
    const ATTACHMENT_EXTS = ['doc','docx', 'pdf', 'jpg', 'png', 'pdf'];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'project_attribute';
    }
    
    public static function primaryKey()
    {
        return ['project_id'];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        	
        	['project_id', 'integer'],
        	['project_id', 'required'],
        	
            ['description', 'default', 'value' => ''],
            ['description', 'string', 'min' => 0, 'max' => 2000],//text=65535,mediumtext=16777215
            
            ['attachment', 'default', 'value' => ''],
            ['attachment', 'string', 'min' => 0, 'max' => 2000],//text=65535,mediumtext=16777215
            
        	['fields', 'default', 'value' => ''],
        	['fields', 'string', 'max' => 254],
            
            ['uploadfile_type', 'default', 'value' => ''],
            ['uploadfile_type', 'string', 'max' => 30],
            ['uploadfile_type', 'in', 'range' => [self::UPLOADFILE_TYPE_WEB, self::UPLOADFILE_TYPE_FTP, self::UPLOADFILE_TYPE_SSH]],
            
            ['uploadfile_account', 'string', 'max' => 254],
            
            //text字段默认值
            ['batch_config', 'default', 'value' => ''],
            ['batch_config', 'string', 'max' => 16777215],
        ];
    }
    
    public static function getUploadfileType($type)
    {
        $types = self::getUploadfileTypes();
    
        return isset($types[$type]) ? $types[$type] : '';
    }
    
    /**
     * @return array
     * 待发布,审核中,执行中,已完成
     */
    public static function getUploadfileTypes()
    {
        return [
            self::UPLOADFILE_TYPE_FTP => Yii::t('app', 'project_upload_file_type_ftp'),
            self::UPLOADFILE_TYPE_WEB => Yii::t('app', 'project_upload_file_type_web'),
            self::UPLOADFILE_TYPE_SSH => Yii::t('app', 'project_upload_file_type_ssh'),
        ];
    }
    
}
