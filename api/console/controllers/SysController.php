<?php
namespace console\controllers;

use common\models\Pack;
use Yii;
use yii\console\Controller;
use common\helpers\ImageHelper;
use common\components\TaskHandler;
use common\models\Task;
use common\models\Setting;
use common\components\ActionUserFilter;
use common\components\Ffmpeg;
use common\models\Work;
use common\models\AuthAssignment;
use common\models\Project;
use common\models\Data;
use common\models\DataResult;
use common\models\WorkResult;
use common\components\exportHandler\ExportHandler;
use common\components\ProjectHandler;
use common\helpers\FileHelper;
use common\helpers\JsonHelper;
use common\helpers\ZipHelper;
use common\helpers\FormatHelper;
use common\helpers\NumberHelper;
use common\helpers\StringHelper;
use common\helpers\HttpHelper;
class SysController extends Controller
{
    /**
     * 刷新全站用户权限
     * php ./yii sys/refresh-auth 1
     * 
     */
    public function actionRefreshAuth($reload = 0)
    {
        $records = AuthAssignment::refreshAuth($reload);
        var_dump($records);
    }
    
    /**
     * 获取在线用户
     * php ./yii sys/online_user
     *
     */
    public function actionOnline_user()
    {
        
        $users = ActionUserFilter::getOnlineUsers();
        var_dump($users);
        
    }
    
    // php ./yii sys/img
    public function actionImg()
    {
        $dir = 'F:/1';
        $dirNew = 'F:/1_new';
        
        $files = FileHelper::dir_get_all_files($dir);
        var_dump($files);
        
        if ($files)
        {
            foreach ($files as $file)
            {
                $filepath = $dir.$file;
                var_dump($filepath);
                
                $imageString = @file_get_contents($filepath);
                var_dump(strlen($imageString));
                
                $imageResource = imagecreatefromstring($imageString);
                if(!$imageResource)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $createfunc err ');
                    var_dump('no image resouce');
                    exit();
                }
                
                $imageWidth = ImageSX($imageResource);
                $imageHeight = ImageSY($imageResource);
                
                
                $dst_im = imagecreatetruecolor($imageWidth, $imageHeight);
                imagecopyresampled($dst_im, $imageResource, 0, 0, 0, 0, $imageWidth, $imageHeight, $imageWidth, $imageHeight);
                $imageResource = $dst_im;
                
                
                $filePathNew = $dirNew.$file;
                $filepathNewDir = FileHelper::filedirname($filePathNew);
                
                if (!file_exists($filepathNewDir))
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' mkdir ');
                    @mkdir($filepathNewDir, 0777, true);
                }
                
                imagejpeg($imageResource, $filePathNew);
                imagedestroy($imageResource);
                
            }
        }
        
    }
    
    // php ./yii sys/reset-task 
    public function actionResetTask($taskId)
    {
        $task = Task::find()->where(['id' => $taskId])->asArray()->limit(1)->one();
        
        
        //实例化执行类
        $taskHandler = new TaskHandler();
        $isinit = $taskHandler->init($task['project_id'], $task['batch_id'], $task['step_id'], 1);
        if (!$isinit)
        {
            var_dump('break');
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_execute_userNoPermission ');
            exit();
        }
        
        $dataIds = Work::find()
        ->select(['data_id'])
        ->where(['batch_id' => $task['batch_id'], 'step_id' => $task['step_id']])
        ->andWhere(['not', ['status' => Work::STATUS_DELETED]])
        ->asArray()->column();
        
        if ($dataIds)
        {
            foreach ($dataIds as $dataId)
            {
                $taskHandler->forceRefuse($dataId);
                //exit();
            }
        }
        
        
        
        
        
    }


}