<?php

namespace common\models;

use Yii;
use common\helpers\FileHelper;
/**
 * Setting 数据表模型
 *
 */
class Setting extends \yii\db\ActiveRecord
{
    const STATUS_ENABLE = 0;
    const STATUS_DISABLE = 1;

    const VALUE_TYPE_BOOLEAN = 0;
    const VALUE_TYPE_NUMBER = 1;
    const VALUE_TYPE_STRING = 2;

    const CAN_DELETE_NO = 0;
    const CAN_DELETE_YES = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'setting';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['can_delete', 'integer'],
            ['can_delete', 'default', 'value' => self::CAN_DELETE_NO],
            ['can_delete', 'in', 'range' => [self::CAN_DELETE_NO, self::CAN_DELETE_YES]],

            ['value_type', 'integer'],
            ['value_type', 'default', 'value' => self::VALUE_TYPE_BOOLEAN],
            ['value_type', 'in', 'range' => array_keys(self::getValueTypes())],

            ['key', 'trim'],
            ['key', 'required'],
            ['key', 'string', 'max' => 32],
            ['key', 'unique', 'message' => Yii::t('app', 'setting_field_key_existed')],
            ['key', 'default', 'value' =>''],

            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string'],
            ['name', 'string', 'max' => 32],
            ['name', 'default', 'value' =>''],

            ['value', 'trim'],
            ['value', 'required'],
            ['value', 'string', 'max' => 254],
            ['value', 'default', 'value' =>''],

            ['desc', 'string', 'max' => 254],
            ['desc', 'default', 'value' =>''],
        ];
    }

    public static function getValueType($key)
    {
        $vals = self::getValueTypes();

        return isset($vals[$key]) ? $vals[$key] : null;
    }

    public static function getValueTypes()
    {
        return [
            self::VALUE_TYPE_BOOLEAN => Yii::t('app', 'setting_value_type_boolean'),
            self::VALUE_TYPE_NUMBER => Yii::t('app', 'setting_value_type_number'),
            self::VALUE_TYPE_STRING => Yii::t('app', 'setting_value_type_string'),
        ];
    }

    private static $_settingData = [];
    public static function getSettings()
    {
        if (empty(self::$_settingData))
        {
            $cacheKey = 'settings';
            $_logs['$cacheKey'] = $cacheKey;

            if (Yii::$app->cache->exists($cacheKey))
            {
                self::$_settingData = unserialize(Yii::$app->cache->get($cacheKey));
            }
            else
            {
                $settingList = Setting::find()->select(['key', 'value'])->where(['status' => Setting::STATUS_ENABLE])->asArray()->all();
                if ($settingList)
                {
                    $settingData = [];
                    foreach ($settingList as $v)
                    {
                        $settingData[$v['key']] = $v['value'];
                    }
                    self::$_settingData = $settingData;
                    Yii::$app->cache->set($cacheKey, serialize($settingData), 600);
                }
            }
        }

        return self::$_settingData;
    }

    public static function getSetting($key = null)
    {
        $settings = self::getSettings();

        if (!empty($key))
        {
            return isset($settings[$key]) ? $settings[$key] : null;
        }
        return $settings;
    }

    public static function refreshCache()
    {
        $cacheKey = 'settings';
        $_logs['$cacheKey'] = $cacheKey;

        if (Yii::$app->cache->exists($cacheKey))
        {
            Yii::$app->cache->delete($cacheKey);
        }
    }

    public static function updateSetting($key, $val)
    {
        if (self::getSetting($key) === null)
        {
            return false;
        }

        $attributes = [
            'value' => $val
        ];
        Setting::updateAll($attributes, ['key' => $key]);

        self::refreshCache();

        return true;
    }

    /**
     *
     * 获取资源根目录
     *
     * @return string
     */
    public static function getResourceRootPath()
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/resource';

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     *
     * 获取文件上传根目录
     *
     * @return string
     */
    public static function getResourcePath($userId, $projectId)
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/resource/'.$userId.'/'.$projectId;

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     *
     * 获取文件上传根目录
     *
     * @return string
     */
    public static function getUploadRootPath()
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/uploadfile';

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     *
     * 获取文件上传根目录
     *
     * @return string
     */
    public static function getAttachmentPath($userId, $projectId)
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/uploadfile/'.$userId.'/'.$projectId;
        if (!empty(Yii::$app->params['attachment_dirname']))
        {
            $path .= '/'.Yii::$app->params['attachment_dirname'];
        }

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     * 相对路径
     * 用户id/项目id/attachment_dirname
     *
     * @param int $userId
     * @param int $projectId
     */
    public static function getAttachmentRelativePath($userId, $projectId)
    {
        $path = $userId.'/'.$projectId;
        if (!empty(Yii::$app->params['attachment_dirname']))
        {
            $path .= '/'.Yii::$app->params['attachment_dirname'];
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     *
     * 获取文件上传根目录
     *
     * @return string
     */
    public static function getUploadfilePath($userId, $projectId)
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/uploadfile/'.$userId.'/'.$projectId;
        if (!empty(Yii::$app->params['uploadfile_dirname']))
        {
            $path .= '/'.Yii::$app->params['uploadfile_dirname'];
        }

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     * 相对路径
     * 用户id/项目id/uploadfile_dirname
     *
     * @param int $userId
     * @param int $projectId
     */
    public static function getUploadfileRelativePath($userId, $projectId)
    {
        $path = $userId.'/'.$projectId;
        if (!empty(Yii::$app->params['uploadfile_dirname']))
        {
            $path .= '/'.Yii::$app->params['uploadfile_dirname'];
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     *
     * 获取文件上传根目录
     *
     * @return string
     */
    public static function getTemporaryStoragePath($userId, $projectId)
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/uploadfile/'.$userId.'/'.$projectId;
        if (!empty(Yii::$app->params['temporaryStorage_dirname']))
        {
            $path .= '/'.Yii::$app->params['temporaryStorage_dirname'];
        }

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     * 相对路径
     * 用户id/项目id/uploadfile_dirname
     *
     * @param int $userId
     * @param int $projectId
     */
    public static function getTemporaryStorageRelativePath($userId, $projectId)
    {
        $path = $userId.'/'.$projectId;
        if (!empty(Yii::$app->params['temporaryStorage_dirname']))
        {
            $path .= '/'.Yii::$app->params['temporaryStorage_dirname'];
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     *
     * 获取资源根目录
     *
     * @return string
     */
    public static function getDownloadPath($userId, $projectId)
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/uploadfile/'.$userId.'/'.$projectId;
        if (!empty(Yii::$app->params['downloadfile_dirname']))
        {
            $path .= '/'.Yii::$app->params['downloadfile_dirname'];
        }

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     * 相对路径
     * 用户id/项目id/downloadfile_dirname
     *
     * @param int $userId
     * @param int $projectId
     */
    public static function getDownloadRelativePath($userId, $projectId)
    {
        $path = $userId.'/'.$projectId;
        if (!empty(Yii::$app->params['downloadfile_dirname']))
        {
            $path .= '/'.Yii::$app->params['downloadfile_dirname'];
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     *
     * 获取对外资源根目录
     *
     * @return string
     */
    public static function getPublicRootPath()
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/publicfile';

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    public static function getLogRootPath()
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/logfile';

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    public static function getMaintenanceRootPath()
    {
        $rootPath = dirname(Yii::getAlias('@common'));
        $path = $rootPath .'/maintenancefile';

        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }

    /**
     * 获取数据部署目录
     */
    public static function getDeployRootPath()
    {
        $publicRootPath = self::getPublicRootPath();
        $path = rtrim($publicRootPath, '/').'/upload/deploy';
        if (!file_exists($path))
        {
            FileHelper::mkdir($path,0777);

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path mkdir '.json_encode([$path]));
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $path '.json_encode([$path]));
        return $path;
    }
}
