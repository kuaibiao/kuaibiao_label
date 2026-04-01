<?php
namespace api\models;

use Yii;
use yii\base\Model;
use common\models\UserDevice;


/**
 * Login form
 */
class InitForm extends Model
{
    public $device_name;
    public $device_number;
    public $device_token;
    public $app_key;
    public $app_version;
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //['device_name', 'required'],
            ['device_name', 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            ['device_name', 'string', 'max' => 30],
            
            //['device_number', 'required'],
            ['device_number', 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            ['device_number', 'string', 'max' => 64],
            
            ['device_token', 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            ['device_token', 'string', 'max' => 64],
            
            //['app_key', 'required'],
            ['app_key', 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            ['app_key', 'string', 'max' => 64],
            
            //['app_version', 'required'],
            ['app_version', 'filter', 'filter' => 'trim', 'skipOnArray' => true],
            ['app_version', 'string', 'max' => 64],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
        ];
    }

    /**
     * 
     */
    public function save()
    {
        $_logs =  [];
        
        if (!$this->device_number)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' device_number empty '.json_encode($_logs));
            return false;
        }
        
        
        //更新用户设备
        $userDeviceList = UserDevice::find()->where(['device_number' => $this->device_number])->asArray()->all();
        if ($userDeviceList)
        {
//             foreach ($userDeviceList as $userDevice)
//             {
//                 $updatedAtAdd = time() - $userDevice['updated_at'];
//                 $counters = [
//                     'request_count' => 1,
//                     'updated_at' => $updatedAtAdd
//                 ];
//                 UserDevice::updateAllCounters($counters, ['id' => $userDevice['id']]);
                
//                 //设备token变更
//                 if ($userDevice['device_token'] != $this->device_token || $userDevice['app_key'] != $this->app_key || $userDevice['app_version'] != $this->app_version)
//                 {
//                     $attributes = [
//                         'device_token' => $this->device_token,
//                         'app_key' => $this->app_key,
//                         'app_version' => $this->app_version,
//                         'updated_at' => time()
//                     ];
//                     UserDevice::updateAll($attributes, ['id' => $userDevice['id']]);
//                 }
//             }
        }
        else
        {
            $userDevice = new UserDevice();
            $userDevice->user_id = 0;
            $userDevice->device_name = $this->device_name;
            $userDevice->device_number = $this->device_number;
            $userDevice->device_token = (string)$this->device_token;
            $userDevice->app_key = (string)$this->app_key;
            $userDevice->app_version = $this->app_version;
            $userDevice->request_count = 1;
            $userDevice->created_at = time();
            $userDevice->save();
        }
        
        return true;
    }
}