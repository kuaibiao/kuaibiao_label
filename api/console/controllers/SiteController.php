<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\models\Setting;
use common\components\ProjectHandler;
use common\components\TaskHandler;
use common\components\importHandler\ImportHandler;
use common\components\exportHandler\ExportHandler;
use common\models\Project;
use common\models\ProjectAttribute;
use common\models\Category;
use common\models\Task;
use common\models\Work;
use common\models\Pack;
use common\models\Stat;
use common\models\Data;
use common\models\DataResult;
use common\models\Message;
use common\models\Template;
use common\models\Step;
use common\models\Unpack;
use common\models\ProjectRecord;
use common\models\Deployment;
use common\models\WorkResult;
use Exception;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;
use common\helpers\JsonHelper;
use common\helpers\ProcessHelper;
use common\helpers\ZipHelper;
use common\helpers\StringHelper;
use common\helpers\ArrayHelper;
/**
 * 项目相关的计划任务
 *
 * 若涉及创建目录, 请使用nginx用户执行, 因权限问题
 * crontab -u nginx -e
 *
 *
 */
class SiteController extends Controller
{
    /**
     * 计划任务统一处理
     *
     * php ./yii site/crontab
     *
     * #查看待执行列表
     * zrangebyscore saasredis:crontab.20200617 1 +inf withscores
     *
     * #查看已执行列表
     * zrangebyscore saasredis:crontab.20200617 0 +inf withscores
     *
     */
    public function actionCrontab()
    {
        $_logs = [];
        
        //当前进程id
        $_logs['pid'] = getmypid();
        $_logs['datetime'] = date('Y-m-d H:i:s');
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' start '. json_encode($_logs));
        
        
        if (empty(Yii::$app->params['crontabs']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' crontabs config miss '. json_encode($_logs));
            return true;
        }
        
        if (empty(Yii::$app->params['phpbin']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' phpbin config miss '. json_encode($_logs));
            return true;
        }
        
        //配置脚本
        $cmds = Yii::$app->params['crontabs'];
        $phpbin = Yii::$app->params['phpbin'];
        $consoleFile = dirname(Yii::getAlias('@common')).'/yii';
        $_logs['$cmds'] = $cmds;
        $_logs['$phpbin'] = $phpbin;
        $_logs['$consoleFile'] = $consoleFile;
        
        ProcessHelper::crontabQueue($cmds, $phpbin, $consoleFile);
        
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        return true;
    }
    
    /**
     * 处理租户过期,项目过期,任务过期,作业过期
     *
     */
    public function actionTimeout()
    {
        $_logs = [];

        $cmd_keys = [dirname(Yii::getAlias('@app')),'site/timeout'];
        $_logs['$cmd_keys'] = $cmd_keys;
        if (ProcessHelper::processIsRunning($cmd_keys, 7200))
        {
            echo date('Y-m-d H:i:s')." - isRunning \n";
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Process isRunning '. json_encode($_logs));
            exit();
        }
        
        //当前进程id
        $_logs['pid'] = getmypid();

        //--------------------------------------
        
        $projectIds = Project::find()
        ->select(['id'])
        ->where(['status' => Project::STATUS_WORKING])
        ->andWhere(['<', 'end_time', time()])
        ->orderBy(['id' => SORT_ASC])->asArray()->column();
        $_logs['$projectIds.count'] = count($projectIds);
        
        if ($projectIds)
        {
            $attributes = [
                'status' => Project::STATUS_FINISH
            ];
            Project::updateAll($attributes, ['in', 'id', $projectIds]);
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project succ '. json_encode($_logs));
        }
        
        //--------------------------------------
        
        $taskIds = Task::find()
        ->select(['id'])
        ->where(['status' => Task::STATUS_NORMAL])
        ->andWhere(['<', 'end_time', time()])
        ->orderBy(['id' => SORT_ASC])->asArray()->column();
        $_logs['$taskIds.count'] = count($taskIds);
        
        if ($taskIds)
        {
            $attributes = [
                'status' => Task::STATUS_FINISH
            ];
            Task::updateAll($attributes, ['in', 'id', $taskIds]);
            
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task succ '. json_encode($_logs));
        }
        
        //--------------------------------------
        
        $projectList = Project::find()
        ->select(['id', 'table_suffix'])
        ->where(['status' => Project::STATUS_WORKING])
        ->orderBy('id asc')->asArray()->all();
        $_logs['$projectList.count'] = count($projectList);
        
        $timeNow = time();
        $timeout = Yii::$app->params['task_receive_expire'];
        
        $works = [];
        if ($projectList)
        {
            foreach ($projectList as $project)
            {
                $works[$project['table_suffix']][] = $project['id'];
            }
        }
        
        
        if ($works)
        {
            foreach ($works as $tableSuffix => $projectIds)
            {
                Work::setTable($tableSuffix);
        
                $query = Work::find()
                ->where(['in', 'project_id', $projectIds])
                ->andWhere(['in', 'status', [Work::STATUS_RECEIVED, Work::STATUS_EXECUTING]])
                ->andWhere('start_time+delay_time<'.$timeNow);
        
                $count = $query->count();
                $_logs['$count'] = $count;
        
                if ($count < 1)
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $count < 0 '. json_encode($_logs));
                    continue;
                }
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' result '. json_encode($_logs));
        
                $updated_count = 0;
        
                $list = $query->asArray()->all();
                foreach ($list as $v)
                {
                    //兼容老数据, 未设置延迟时间, 则默认过期时间
                    if ($v['delay_time'] < 1)
                    {
                        $v['delay_time'] = $timeout;
                    }
        
                    //开始时间+延迟时间>当前时间, 说明还未过期
                    if ($v['start_time'] + $v['delay_time'] > $timeNow)
                    {
                        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' delay_time '.json_encode($_logs));
                        continue;
                    }
        
                    $taskHandler = new TaskHandler();
                    $isinit = $taskHandler->init($v['project_id'], $v['batch_id'], $v['step_id'], $v['user_id']);
                    if (!$isinit)
                    {
                        $attributes = [
                            'status' => Work::STATUS_DELETED,
                            'type' => Work::TYPE_TIMEOUT,
                            'updated_at' => time()
                        ];
                        Work::updateAll($attributes, ['id' => $v['id']]);
        
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $taskHandler init error '.json_encode($_logs));
                        continue;
                    }
                    $taskHandler->timeout($v['data_id']);
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' timeout '.json_encode($_logs));
        
                    $updated_count++;
                }
        
                $_logs['$updated_count'] = $updated_count;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
        
            }
        }
        
        
        //--------------------------------------
        
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
    }

    /**
     * 读取项目上传文件的文件结构
     *
     * @param unknown $projectId
     * @param string $saveFilePath
     * @return boolean
     */
    public function actionUploadfileStruct($projectId, $saveFilePath = '')
    {
        $_logs['$projectId'] = $projectId;
        $_logs['$saveFilePath'] = $saveFilePath;

        $cmd_keys = [dirname(Yii::getAlias('@app')),'site/uploadfile-struct', $projectId];
        $_logs['$cmd_keys'] = $cmd_keys;
        if (ProcessHelper::processIsRunning($cmd_keys, 3600))
        {
            echo date('Y-m-d H:i:s')." - isRunning \n";
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Process isRunning '. json_encode($_logs));
            exit();
        }
        
        //当前进程id
        $_logs['pid'] = getmypid();

        //---------------------------------------

        $project = Project::find()->where(['id' => $projectId])->asArray()->limit(1)->one();
        if (!$project)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team_assign_tasknotfound '.json_encode($_logs));
            return false;
        }

        //查询分类
        $category = Category::find()->where(['id' => $project['category_id']])->asArray()->limit(1)->one();

        //--------------------------------------

        //保存路径
        $downloadfileDir = Setting::getDownloadPath($project['user_id'], $projectId);

        $saveDir = $downloadfileDir;
        $_logs['$saveDir'] = $saveDir;

        if ($saveFilePath)
        {
            $saveFilePath = StringHelper::base64_decode($saveFilePath);
        }
        else
        {
            $saveFilePath = $saveDir .'/uploadfile_struct_'.date('YmdHis').'.txt';
        }
        $_logs['$saveFilePath'] = $saveFilePath;

        if (file_exists($saveFilePath))
        {
            var_dump('savefile already exist');
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' savefile already exist '. json_encode($_logs));
            return false;
        }

        if (strpos($saveFilePath, $saveDir) === false)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' savefilepath fail '. json_encode($_logs));
            return false;
        }

        $saveFileExt = FileHelper::fileextension($saveFilePath);
        $_logs['$saveFileExt'] = $saveFileExt;
        if (!in_array($saveFileExt, ['txt', 'json']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' savefileext fail '. json_encode($_logs));
            return false;
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' running '. json_encode($_logs));

        //--------------------------------------

        if (!file_exists($saveDir))
        {
            @mkdir($saveDir, 0777, true);
            @chmod($saveDir, 0777);

            if (!file_exists($saveDir))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $saveDir mkdir fail '. json_encode($_logs));
                return false;
            }
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $saveDir mkdir succ '. json_encode($_logs));
        }

        //有源数据的情况, 比如:标注类,
        if (in_array($category['type'], [Category::TYPE_LABEL, Category::TYPE_EXTERNAL]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type TYPE_LABEL '. json_encode($_logs));

            //获取项目文件目录
            $uploadFilePath = Setting::getUploadfilePath($project['user_id'], $projectId);
            $_logs['$uploadFilePath'] = $uploadFilePath;

            //上传的数据文件
            $uploadFiles = FileHelper::get_dir_stat($uploadFilePath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
            $_logs['$uploadFiles'] = $uploadFiles;

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $uploadFiles '. json_encode($_logs));

            $datafiles = [];
            if ($uploadFiles)
            {
                foreach ($uploadFiles as $_file)
                {
                    $_logs['$_file'] = $_file;
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each file '.json_encode($_logs));


                    $_fileBasename = FileHelper::filebasename(rtrim($_file['path'], '/'));
                    $_filePath = $uploadFilePath . '/'. $_file['path'];
                    $_filePathRelative = ltrim(str_replace(Setting::getUploadRootPath(), '', $_filePath), '/');//文件的相对路径

                    $_logs['$_fileBasename'] = $_fileBasename;
                    $_logs['$_filePath'] = $_filePath;
                    $_logs['$_filePathRelative'] = $_filePathRelative;
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' each file '.json_encode($_logs));

                    if (is_dir($_filePath))
                    {
                        $_logs['filetype'] = 'is_dir';
                        // $datafiles[] = FileHelper::dir_get_all_files(Setting::getUploadRootPath().'/'.$_file['path'], [], ['']);
                        $datafiles[] = FileHelper::get_dir_struct($_filePath, $project['user_id'].'/'.$projectId.'/', Yii::$app->params['task_source_ignorefiles']);
                    }
                    elseif (FileHelper::is_zip($_filePath))
                    {
                        $_logs['filetype'] = 'is_zip';

                        $prefix = $project['user_id'].'/'.$projectId;
                        $_logs['$prefix'] = $prefix;

                        //$zipStructs = ZipHelper::get_zip_struct($_filePath, $prefix);
                        $zipStructs = ZipHelper::get_zip_struct_linux($_filePath, $prefix, Yii::$app->params['task_source_ignorefiles']);
                        if (!$zipStructs)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get_zip_struct error '.json_encode($_logs));
                            break;
                        }

                        foreach ($zipStructs as $struct)
                        {
                            $datafiles[] = FormatHelper::format_zip_struct($struct);
                        }
                    }
//                     elseif ($_fileMime == $mimes['tar'] || (is_array($mimes['tar']) && in_array($_fileMime, $mimes['tar'])))
//                     {
//                         $prefix = $project['user_id'].'/'.$projectId;
//                         $_logs['$prefix'] = $prefix;

//                         //$zipStructs = ZipHelper::get_zip_struct($_filePath, $prefix);
//                         $zipStructs = ZipHelper::get_tar_struct_linux($_filePath, $prefix, Yii::$app->params['task_source_ignorefiles']);
//                         if (!$zipStructs)
//                         {
//                             Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get_zip_struct error '.json_encode($_logs));
//                             break;
//                         }

//                         foreach ($zipStructs as $struct)
//                         {
//                             $datafiles[] = FormatHelper::format_zip_struct($struct);
//                         }
//                     }
                    else if (FileHelper::is_text($_filePath))
                    {
                        $_logs['filetype'] = 'is_text';

                        $list = FileHelper::file_readcontent($_filePath, false);
                        $size = FileHelper::file_size($_filePath);
                        $sizeFormat = FileHelper::file_size($_filePath, true);

                        $datafiles[] = [
                            'count' => count($list),
                            'size'  => $sizeFormat,
                            'size_number'  => $size,
                            'name'  => $_fileBasename,
                            'path'  => StringHelper::base64_encode($_filePathRelative),
                            'children' => [],
                        ];
                    }
                    elseif (FileHelper::is_csv($_filePath))
                    {
                        $_logs['filetype'] = 'is_csv';

                        $list = FileHelper::file_readcontent($_filePath, false);
                        $size = FileHelper::file_size($_filePath);
                        $sizeFormat = FileHelper::file_size($_filePath, true);

                        $datafiles[] = [
                            'count' => count($list),
                            'size'  => $sizeFormat,
                            'size_number'  => $size,
                            'name'  => $_fileBasename,
                            'path'  => StringHelper::base64_encode($_filePathRelative),
                            'children' => [],
                        ];
                    }
                    elseif (FileHelper::is_xls($_filePath))
                    {
                        $_logs['filetype'] = 'is_xls';

                        $list = FileHelper::file_readcontent($_filePath, false);
                        $size = FileHelper::file_size($_filePath);
                        $sizeFormat = FileHelper::file_size($_filePath, true);

                        $datafiles[] = [
                            'count' => count($list),
                            'size'  => $sizeFormat,
                            'size_number'  => $size,
                            'name'  => $_fileBasename,
                            'path'  => StringHelper::base64_encode($_filePathRelative),
                            'children' => [],
                        ];
                    }
                    elseif (FileHelper::is_video($_filePath))
                    {
                        $_logs['filetype'] = 'is_video';

                        //$datafiles[] = FileHelper::get_dir_struct($_filePath, $project['user_id'].'/'.$projectId.'/', Yii::$app->params['task_source_ignorefiles']);
                        $size = FileHelper::file_size($_filePath);
                        $sizeFormat = FileHelper::file_size($_filePath, true);

                        $datafiles[] = [
                            'count' => 1,
                            'size'  => $sizeFormat,
                            'size_number'  => $size,
                            'name'  => $_fileBasename,
                            'path'  => StringHelper::base64_encode($_filePathRelative),
                            'children' => [],
                        ];
                    }
                    else
                    {
                        $_logs['filetype'] = 'other';

                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));

                        $size = FileHelper::file_size($_filePath);
                        $sizeFormat = FileHelper::file_size($_filePath, true);

                        $datafiles[] = [
                            'count' => 1,
                            'size'  => $sizeFormat,
                            'size_number'  => $size,
                            'name'  => $_fileBasename,
                            'path'  => StringHelper::base64_encode($_filePathRelative),
                            'children' => [],
                        ];

                    }
                }
            }
        }
        //没有数据源的情况, 比如:采集类,
        elseif ((int)$category['type'] == Category::TYPE_COLLECTION)
        {
            $_filePathRelative = '1/';

            $datafiles[] = [
                'count' => 0,
                'size'  => 0,
                'size_number'  => 0,
                'name'  => '批次(1)',
                'path'  => StringHelper::base64_encode($_filePathRelative),
                'children' => [],
            ];
        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' other type '.json_encode($_logs));
            exit();
        }
        $_logs['$datafiles'] = ArrayHelper::var_desc($datafiles);
        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $datafiles '.json_encode($_logs));

        $dataInfo = [
            'count' => 0,
            'size' => 0,
            'size_number'  => 0,
            'files' => $datafiles
        ];
        if ($datafiles)
        {
            foreach ($datafiles as $datafile_)
            {
                $dataInfo['count'] += $datafile_['count'];
                $dataInfo['size_number'] += $datafile_['size_number'];
            }
        }

        $dataInfo['size'] = FileHelper::filesize_format($dataInfo['size_number']);

        FileHelper::file_write($saveFilePath, json_encode($dataInfo), 'w');

        var_dump('succ');
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '.json_encode($_logs));
    }

    /**
     * 查询待解包状态的任务文件包, 并执行解包
     *
     * php ./yii site/unpack
     *
     *
     */
    public function actionUnpack()
    {
        $_logs = [];

        $cmd_keys = [dirname(Yii::getAlias('@app')),'site/unpack'];
        $_logs['$cmd_keys'] = $cmd_keys;
        if (ProcessHelper::processIsRunning($cmd_keys, 36000, 3))
        {
            echo date('Y-m-d H:i:s')." - isRunning \n";
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Process isRunning '. json_encode($_logs));
            exit();
        }
        
        //当前进程id
        $_logs['pid'] = getmypid();

        //打包中, 最后更新时间过长, 说明已经无效
        $runningIds = Unpack::find()
        ->select(['id'])
        ->where(['status' => Unpack::STATUS_ENABLE, 'unpack_status' => Unpack::UNPACK_STATUS_RUNNING])
        ->andWhere(['<', 'updated_at', time() - 3600 * 6 ])
        ->orderBy(['id' => SORT_ASC])->asArray()->column();
        $_logs['$runningIds.count'] = count($runningIds);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $list '. json_encode($_logs));
        if ($runningIds)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  '. json_encode($_logs));
            foreach ($runningIds as $i => $id)
            {
                $attributes = [
                    'unpack_status' => Unpack::UNPACK_STATUS_FAILURE,
                    'unpack_message' => 'unpack_fail',
                    'updated_at' => time()
                ];
                Unpack::updateAll($attributes, ['id' => $id]);
            }
        }

        //查询等待打包的列表
        $waitingIds = Unpack::find()
        ->select(['id'])
        ->where(['status' => Unpack::STATUS_ENABLE, 'unpack_status' => Unpack::UNPACK_STATUS_WAITING])
        ->orderBy(['id' => SORT_ASC])->offset(0)->limit(10)->asArray()->column();
        $_logs['$waitingIds.count'] = count($waitingIds);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $list '. json_encode($_logs));

        if ($waitingIds)
        {
            foreach ($waitingIds as $i => $id)
            {
                $_logs['$id'] = $id;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop item '. json_encode($_logs));

                $unpack = Unpack::findOne($id);
                $_logs['$unpack'] = $unpack->getAttributes();

                if ($unpack->status != Unpack::STATUS_ENABLE)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $unpack status error '. json_encode($_logs));
                    continue;
                }

                if ($unpack->unpack_status != Unpack::UNPACK_STATUS_WAITING)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $unpack unpack_status error '. json_encode($_logs));
                    continue;
                }

                //更改为解包中
                $unpack->unpack_status = Unpack::UNPACK_STATUS_RUNNING;
                $unpack->unpack_start_time = time();
                $unpack->unpack_progress = 10;
                $unpack->updated_at = time();
                $unpack->save();

                //------------------------------------

                $project = Project::find()->where(['id' => $unpack->project_id])->asArray()->limit(1)->one();
                
                //查询状态是否是准备中的项目
                if(!in_array($project['status'], [Project::STATUS_PREPARING, Project::STATUS_PAUSED]))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $project status error '. json_encode($_logs));
                    continue;
                }

                //更新上传文件信息
                $uploadfilePath = Setting::getUploadfilePath($project['user_id'], $project['id']);
                $uploadfiles = FileHelper::get_dir_files($uploadfilePath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
                if($uploadfiles)
                {
                    foreach($uploadfiles as $key => $val){
                        $path = '/'.$project['user_id'] .'/'. $project['id'].'/'.$val['name'];
                        $uploadfiles[$key]['key'] = StringHelper::base64_encode($path);
                    }
                    ProjectAttribute::updateAll(['uploadfiles' => json_encode($uploadfiles)], ['project_id' => $project['id']]);
                }

                if ($project['status'] != Project::STATUS_PREPARING)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $project status error '.json_encode($_logs));
                    //反馈信息
                    $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                    $unpack->unpack_message = json_encode(['project_status_not_allow' => []]);
                    $unpack->updated_at = time();
                    $unpack->save();
                    continue;
                }

                //设置分表
                Data::setTable($project['table_suffix']);
                DataResult::setTable($project['table_suffix']);
                Work::setTable($project['table_suffix']);
                WorkResult::setTable($project['table_suffix']);

                $projectAttribute = ProjectAttribute::find()->where(['project_id' => $project['id']])->asArray()->limit(1)->one();
                $_logs[] = $projectAttribute['batch_config'];
                if (empty($projectAttribute['batch_config']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batch_config fail '.json_encode($_logs));

                    $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                    $unpack->unpack_message = json_encode(['site_unpack_batch_config_empty' => []]);
                    $unpack->updated_at = time();
                    $unpack->save();
                    continue;
                }

                $batchConfigJson = StringHelper::html_decode($projectAttribute['batch_config']);
                if (!JsonHelper::is_json($batchConfigJson))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batch_config fail '.json_encode($_logs));

                    $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                    $unpack->unpack_message = json_encode(['site_unpack_batch_config_fail' => []]);
                    $unpack->updated_at = time();
                    $unpack->save();
                    continue;
                }

                $batchConfigs = JsonHelper::json_decode_all($batchConfigJson);
                if (!$batchConfigs || !is_array($batchConfigs))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $batchConfigs empty '.json_encode($_logs));

                    $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                    $unpack->unpack_message = json_encode(['site_unpack_batch_config_fail' => []]);
                    $unpack->updated_at = time();
                    $unpack->save();
                    continue;
                }

                if (!isset($batchConfigs['assign_type']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' assign_type error '.json_encode($_logs));

                    $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                    $unpack->unpack_message = json_encode(['site_unpack_assign_type_fail' => []]);
                    $unpack->updated_at = time();
                    $unpack->save();
                    continue;
                }

                //手动设置方式
                if ($batchConfigs['assign_type'] == 1)
                {
                    if (empty($batchConfigs['paths']) || !is_array($batchConfigs['paths']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $batchConfigs paths error '.json_encode($_logs));

                        $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                        $unpack->unpack_message = json_encode(['site_unpack_assign_paths_fail' => []]);
                        $unpack->updated_at = time();
                        $unpack->save();
                        continue;
                    }

                    foreach ($batchConfigs['paths'] as $k => $config_)
                    {
                        $batchConfigs['paths'][$k] = StringHelper::base64_decode($config_);
                    }
                }
                elseif ($batchConfigs['assign_type'] == 2)
                {
                    if (empty($batchConfigs['batches']) || !is_array($batchConfigs['batches']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $batchConfigs batches error '.json_encode($_logs));

                        $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                        $unpack->unpack_message = json_encode(['site_unpack_assign_batches_fail' => []]);
                        $unpack->updated_at = time();
                        $unpack->save();
                        continue;
                    }
                }
                //自动设置方式
                else
                {
                    if (empty($batchConfigs['count']))
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' batchConfigs count error '.json_encode($_logs));

                        $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                        $unpack->unpack_message = json_encode(['site_unpack_assign_count_fail' => []]);
                        $unpack->updated_at = time();
                        $unpack->save();
                        continue;
                    }
                }
                $_logs['$batchConfigs'] = $batchConfigs;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $batchConfigs '.json_encode($_logs));

                //------------------------------------------------------------

                $categoryInfo = Category::find()->where(['id' => $project['category_id']])->asArray()->limit(1)->one();

                if (in_array($categoryInfo['type'], [Category::TYPE_LABEL, Category::TYPE_EXTERNAL]))
                {
                    //计算数据占用空间
                    $dataStruct = ProjectHandler::getDataStruct($project['user_id'], $project['id']);
                    if($dataStruct)
                    {
                        $dataSize = 0;
                        foreach ($dataStruct as $datafile_)
                        {
                            $dataSize += $datafile_['size_number'];
                        }

                        $dataSize = FormatHelper::filesize_format_mb($dataSize, 3, false);
                        $attributes = [
                            'disk_space' => $dataSize,
                            'updated_at' => time()
                        ];
                        Project::updateAll($attributes, ['id' => $project['id']]);
                    }
                    else
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' get project data struct error '. json_encode($_logs));
                        continue;
                    }

                    $script = 'label/label';
                }
                elseif ($categoryInfo['type'] == Category::TYPE_COLLECTION)
                {
                    $script = 'collection/collection';
                }

                $params = [
                    'unpack' => $unpack,
                    'project' => $project,
                    'batchConfigs' => $batchConfigs
                ];

                $importResult = ImportHandler::run($script, 'run', $params);
                $_logs['$importResult'] = $importResult;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $importResult '.json_encode($_logs));

                if ($importResult['error'])
                {
                    Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' ImportHandler run error '.json_encode($_logs));

                    $unpack->unpack_status = Unpack::UNPACK_STATUS_FAILURE;
                    $unpack->unpack_message = $importResult['message'];
                    $unpack->updated_at = time();
                    $unpack->save();
                    //保存项目操作记录
                    ProjectRecord::operateConfigure(ProjectRecord::SCENE_CONFIGURE_UNPACK_FAIL, $project['id']);
                    continue;
                }

                //解包成功
                $unpack->unpack_status = Unpack::UNPACK_STATUS_SUCCESS;
                $unpack->unpack_end_time = time();
                $unpack->unpack_progress = 100;
                $unpack->updated_at = time();
                $unpack->save();

                //执行成功
                $taskList = Task::find()->where(['project_id' => $project['id'], 'status' => Task::STATUS_NORMAL])->asArray()->all();
                if ($taskList)
                {
                    $attributes = [
                        'status' => Project::STATUS_WORKING,
                        'updated_at' => time()
                    ];
                    Project::updateAll($attributes, ['id' => $project['id']]);
                    
                    foreach ($taskList as $task)
                    {
                        //初始化数据
                        $taskHandler = new TaskHandler();
                        $isinit = $taskHandler->init($task['project_id'], $task['batch_id'], $task['step_id'], 1);
                        if (!$isinit)
                        {
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' TaskHandler init error '.json_encode($_logs));
                            continue;
                        }
                        $taskHandler->initData();
                    }
                }
                
                //保存项目操作记录
                ProjectRecord::operateConfigure(ProjectRecord::SCENE_CONFIGURE_UNPACK_SUCC, $project['id']);

                //发站内通知
                Message::sendProjectUnpackSucc($project['user_id'], $project['id']);
            }
        }

        //计算内容使用情况
        $memory	= round(memory_get_usage() / 1024 / 1024, 2).'MB';
        $_logs['$memory'] = $memory;

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' succ '. json_encode($_logs));
    }

    /**
     * 打包
     * 打包规则
     * 从每个租户的项目中选取一个打包任务
     * 
     */
    public function actionPack()
    {
        $cmd_keys = [dirname(Yii::getAlias('@app')),'site/pack'];
        $_logs['$cmd_keys'] = $cmd_keys;
        if (ProcessHelper::processIsRunning($cmd_keys, 72000, 6))
        {
            echo date('Y-m-d H:i:s')." - isRunning \n";
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Process isRunning '. json_encode($_logs));
            exit();
        }
        
        //当前进程id
        $_logs['pid'] = getmypid();

        //打包中, 最后更新时间过长, 说明已经无效
        $runningIds = Pack::find()
        ->select(['id'])
        ->where(['status' => Pack::STATUS_ENABLE, 'pack_status' => Pack::PACK_STATUS_RUNNING])
        ->andWhere(['<', 'updated_at', time() - 3600 * 6 ])
        ->orderBy(['sort' => SORT_ASC])->asArray()->column();
        $_logs['$runningIds.count'] = count($runningIds);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $list '. json_encode($_logs));
        if ($runningIds)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.'  '. json_encode($_logs));
            foreach ($runningIds as $i => $id)
            {
                $attributes = [
                    'pack_status' => Pack::PACK_STATUS_FAILURE,
                    'pack_message' => 'pack_fail',
                    'updated_at' => time()
                ];
                Pack::updateAll($attributes, ['id' => $id]);
            }
        }

        //查询等待打包的列表
        $packList = Pack::find()
        ->select(['id', 'project_id'])
        ->where(['status' => Pack::STATUS_ENABLE, 'pack_status' => Pack::PACK_STATUS_WAITING])
        ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();
        $_logs['$packList.count'] = count($packList);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $list '. json_encode($_logs));
        if (!$packList)
        {
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' no $packList '. json_encode($_logs));
            exit();
        }
        
        //获取项目=>打包对应关系
        $projectMapping = [];
        foreach ($packList as $v)
        {
            $projectMapping[$v['project_id']][] = $v['id'];
        }
        $projectIds = array_keys($projectMapping);
        $_logs['$projectMapping'] = $projectMapping;
        $_logs['$projectIds'] = $projectIds;
        
        //查询所有项目
        $projectList = Project::find()->select(['id'])->where(['in', 'id', $projectIds])->asArray()->all();

        //从每个租户的第一个项目中取一个打包任务
        $waitingIds = [];
        foreach ($projectList as $projectId_)
        {
        
            //获取项目第一个打包
            $packId_ = array_shift($projectMapping[$projectId_['id']]);
        
            //加入打包队列
            $waitingIds[] = $packId_;
        }
        $_logs['$waitingIds'] = $waitingIds;
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' $waitingIds '. json_encode($_logs));
        
        if ($waitingIds)
        {
            foreach ($waitingIds as $i => $id)
            {
                $_logs['$id'] = $id;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop item '. json_encode($_logs));

                $pack = Pack::find()->where(['id' => $id])->asArray()->with(['packScript'])->limit(1)->one();
                if ($pack['status'] != Pack::STATUS_ENABLE)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' status error '. json_encode($_logs));
                    continue;
                }

                if ($pack['pack_status'] == Pack::PACK_STATUS_RUNNING)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' is running '. json_encode($_logs));
                    continue;
                }
                elseif ($pack['pack_status'] != Pack::PACK_STATUS_WAITING)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' pack_status error '. json_encode($_logs));
                    continue;
                }

                //打包开始
                $attributes = [
                    'pack_pid' => getmypid(),
                    'pack_status' => Pack::PACK_STATUS_RUNNING,
                    'pack_start_time' => time(),
                    'updated_at' => time()
                ];
                Pack::updateAll($attributes, ['id' => $pack['id']]);
                $params = [
                    'pack' => $pack,
                ];
                $exportResult = ExportHandler::run($pack['packScript']['script'], 'run', $params);
                var_dump($exportResult);

                if ($exportResult['error'])
                {
                    $attributes = [
                        'pack_status' => Pack::PACK_STATUS_FAILURE,
                        'pack_message' => $exportResult['message'],
                        'pack_file' => '',
                        'pack_end_time' => time(),
                        'updated_at' => time()
                    ];
                    Pack::updateAll($attributes, ['id' => $pack['id']]);

                    //发站内通知
                    Message::sendProjectPackFail($pack['user_id'], $pack['project_id'], $exportResult['message']);
                }
                else
                {

                    $checkFile = $exportResult['data']['checkFile'];
                    $packFile = $exportResult['data']['packFile'];

                    $attributes = [
                        'check_file' => $checkFile,
                        'pack_status' => Pack::PACK_STATUS_SUCCESS,
                        'pack_message' => 'pack_success',
                        'pack_file' => $packFile,
                        'pack_end_time' => time(),
                        'updated_at' => time()
                    ];
                    Pack::updateAll($attributes, ['id' => $pack['id']]);

                    //发站内通知
                    Message::sendProjectPackSucc($pack['user_id'], $pack['project_id']);
                }
                
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' loop succ '. json_encode($_logs));
            }
        }
        
    }

    /**
     * 查询待部署状态的文件包, 并执行解包
     *
     *
     */
    public function actionDeployFile()
    {
        $_logs = [];
        
        $cmd_keys = [dirname(Yii::getAlias('@app')),'site/deploy-file'];
        $_logs['$cmd_keys'] = $cmd_keys;
        if (ProcessHelper::processIsRunning($cmd_keys, 36000, 3))
        {
            echo date('Y-m-d H:i:s')." - isRunning \n";
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' Process isRunning '. json_encode($_logs));
            exit();
        }
        
        //当前进程id
        $_logs['pid'] = getmypid();
        
        //部署最后更新时间过长, 说明已经无效
        $runningIds = Deployment::find()
            ->select(['id'])
            ->where(['status' => Deployment::STATUS_RUNNING])
            ->andWhere(['<', 'start_time', time() - 3600 * 24])
            ->asArray()->column();
        $_logs['$runningIds.count'] = count($runningIds);
        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__. json_encode($_logs));
        if($runningIds)
        {
            foreach ($runningIds as $id)
            {
                $attributes = [
                    'status' => Deployment::STATUS_FAIL,
                    'updated_at' => time()
                ];
                Deployment::updateAll($attributes, ['id' => $id]);
            }
        }
        
        //查询等待部署的列表
        $deploymentList = Deployment::find()
            ->where(['status' => Deployment::STATUS_WAIT])
            ->orderBy(['id' => SORT_ASC])
            ->offset(0)->limit(10)->all();
        if ($deploymentList)
        {
            foreach($deploymentList as $item)
            {
                $_logs['$item->status'] = $item->status;
                if ($item->status != Deployment::STATUS_WAIT)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' deploy status not wait '.json_encode($_logs));
                    continue;
                }
                
                $uploadPath = rtrim(Setting::getUploadRootPath(), '/').'/'.ltrim($item->upload_path, '/');
                $_logs['$uploadPath'] = $uploadPath;
                if(!FileHelper::dir_write_completed($uploadPath, 1))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' upload not prepared '.json_encode($_logs));
                    continue;
                }
                
                $item->status = Deployment::STATUS_RUNNING;
                $item->start_time = $item->updated_at = time();
                $item->save();
                
                $parseResult = false;

                $files = FileHelper::get_all_files_by_scan_dir($uploadPath, '', Yii::$app->params['task_source_ignorefiles'], ['zip', 'ZIP']);
                
                $fileCount = 0;
                $list = [];
                $listExt = [];//文件所有后缀名
                foreach($files as $file)
                {
                    //解包
                    $filePath = rtrim($item->upload_path, '/').'/'.$file;
                    $extractpath = Deployment::unzip($filePath);
                    if ($extractpath)
                    {
                        $parseResult = true;
                    }

                    //读取文件夹的文件列表
                    $fileList = FileHelper::get_all_files_by_scan_dir($extractpath, '', Yii::$app->params['task_source_ignorefiles']);
                    if ($fileList)
                    {
                        $baseUrl = $relativePath = rtrim($item->host, '/').'/upload/deploy/'.trim(str_replace('/deployment', '', $filePath), '/');
                        
                        if($result = Deployment::ruleBfDataset($fileList, $baseUrl)) //判断是否符合数据集规则
                        {

                            list($resultList, $resultExt) = $result;

                            if (empty($resultList) || empty($resultExt))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rule_same_basename is null '.json_encode($_logs));
                                continue;
                            }

                            $list[] = $resultList;
                            $listExt = array_merge($listExt, $resultExt);
                        }
                        // else if($result = Deployment::rule3d($extractpath, $baseUrl)) //判断是否符合3d规则
                        else if($result = Deployment::rule3dNew($extractpath, $baseUrl)) //判断是否符合3d规则
                        {
                            list($resultList, $resultExt) = $result;
                            if (empty($resultList) || empty($resultExt))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rule3d is null '.json_encode($_logs));
                                continue;
                            }

                            $list[] = $resultList;
                            $listExt = array_merge($listExt, $resultExt);
                        }
                        else if ($result = Deployment::ruleSameBasename($fileList, $baseUrl)) //判断是否符合basename同名文件规则
                        {
                            list($resultList, $resultExt) = $result;

                            if (empty($resultList) || empty($resultExt))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rule_same_basename is null '.json_encode($_logs));
                                continue;
                            }

                            $list[] = $resultList;
                            $listExt = array_merge($listExt, $resultExt);
                        }
                        else if($result = Deployment::ruleLikeBasename($fileList, $baseUrl)) //判断是否符合文件名相似规则
                        {

                            list($resultList, $resultExt) = $result;

                            if (empty($resultList) || empty($resultExt))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rule_like_basename is null '.json_encode($_logs));
                                continue;
                            }

                            $list[] = $resultList;
                            $listExt = array_merge($listExt, $resultExt);
                        }
                        else
                        {
                            $result = Deployment::ruleNo($fileList, $baseUrl);
                            list($resultList, $resultExt) = $result;

                            if (empty($resultList) || empty($resultExt))
                            {
                                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' rule_result is null '.json_encode($_logs));
                                continue;
                            }

                            $list[] = $resultList;
                            $listExt = array_merge($listExt, $resultExt);
                        }
                        
                        $fileCount += count($fileList);
                    }
                    
                }

                //所有的判断方法都返回包含的文件后缀，然后统一记录
                $item->message = '您的文件后缀包括：'.implode(', ', array_unique($listExt));

                if(!$parseResult) //所有包都解包失败，则失败
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' unzip fail '.json_encode($_logs));
                    $item->status = Deployment::STATUS_FAIL;
                    $item->end_time = $item->updated_at = time();
                    $item->save();
                    continue;
                }


                try{
                    $fileName = rtrim(Setting::getDeployRootPath(), '/').str_replace('/deployment', '', $item->upload_path).'.xlsx';
                    $result = FileHelper::file_write_excel($fileName, $list);
                    $_logs['$fileName'] = $fileName;
                    $_logs['$result'] = $result;
                    if(!$result)
                    {
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' write excel result error '.json_encode($_logs));
                        $item->status = Deployment::STATUS_FAIL;
                        $item->end_time = $item->updated_at = time();
                        $item->save();
                        continue;
                    }
                }
                catch(Exception $e){
                    $_logs['$e->getFile()-$e->getLine()-$e->getMessage()'] = $e->getFile().'-'.$e->getLine().'-'.$e->getMessage();
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' write excel fail '.json_encode($_logs));
                    $item->status = Deployment::STATUS_FAIL;
                    $item->end_time = $item->updated_at = time();
                    $item->save();
                    continue;
                }

                //统计文件大小
                $filePath = rtrim(Setting::getUploadRootPath(), '/').'/'.ltrim($item->upload_path, '/');
                $fileSize = FileHelper::dirsize($filePath);
                $fileSizeMB = FileHelper::filesize_format_mb($fileSize, 3, false);
                $_logs['$filePath'] = $filePath;
                $_logs['$fileSize'] = $fileSize;
                $_logs['$fileSizeMB'] = $fileSizeMB;
                
                $item->file_size_total = $fileSizeMB;
                $item->file_count = $fileCount;
                $item->file_list_path = str_replace(Setting::getPublicRootPath(), '', $fileName);
                $item->status = Deployment::STATUS_SUCCESS;
                $item->end_time = $item->updated_at = time();
                $item->save();
                
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' deploy '.$item->id.' success '.json_encode($_logs));
            }    
        }
        
    }

}