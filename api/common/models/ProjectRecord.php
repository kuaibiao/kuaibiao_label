<?php

namespace common\models;

use Yii;
use common\components\ModelComponent;
use common\helpers\FileHelper;
use common\helpers\FormatHelper;

/**
 * project_record 表数据模型
 *
 */
class ProjectRecord extends ModelComponent
{
    /**
     *  日志类型
     */
    const TYPE_CREATE = 1;
    const TYPE_EDIT = 2;
    const TYPE_EXAMINE = 3;
    const TYPE_CONFIGURE = 4;
    const TYPE_FINISH = 5;
    const TYPE_RESTART = 6;
    const TYPE_REFUSED = 7;

    /**
     *  日志场景
     */
    // 创建项目
    const SCENE_CREATE_SUBMIT = 1;        //创建提交基础信息
    const SCENE_CREATE_CREATE = 2;        //创建项目
    // 编辑项目
    const SCENE_EDIT_INFO = 3;                //编辑基础信息
    const SCENE_EDIT_PAUSE = 4;              //暂停项目
    const SCENE_EDIT_CONTINUE = 5;        //继续项目
    const SCENE_EDIT_DELETE = 6;        //删除项目
    const SCENE_EDIT_STOP = 7;              //停止项目

    //项目审核
    const SCENE_EXAMINE_PASS = 8;                //审核项目 通过
    const SCENE_EXAMINE_DENY = 9;                //审核项目 拒绝
    //项目配置
    const SCENE_CONFIGURE_CONFIGURE = 10;        //配置项目
    const SCENE_CONFIGURE_TEMPLATE = 11;          //修改模板
    const SCENE_CONFIGURE_UNPACK_SUCC = 12;              //解包结果
    const SCENE_CONFIGURE_UNPACK_FAIL = 13;        //解包中
    // 项目完成
    const SCENE_FINISH_FINISH = 14;                    // 项目完成
    const SCENE_FINISH_EXPIRE= 15;                    //项目过期 自动完成
    // 项目重启
    const SCENE_RESTART_RESTART = 16;                //完成项目 重启

    public static $tableName = 'project_record';
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
            ['project_id', 'integer'],
            ['project_id', 'default', 'value' => 0],

            ['type', 'integer'],
            ['type', 'default', 'value' => 0],
            ['type', 'in', 'range' => array_keys(self::getTypes())],
            ['type', 'default', 'value' => self::TYPE_CREATE],

            ['scene', 'integer'],
            ['scene', 'default', 'value' => 0],

            ['created_by', 'integer'],
            ['created_by', 'default', 'value' => 0],

            [['created_at','updated_at'], 'integer'],

            ['message', 'string'],
            ['message', 'default', 'value' => ''],

            ['scene', 'in', 'range' => array_keys(self::getScenes())],
            ['scene', 'integer'],
            ['scene', 'default', 'value' => self::SCENE_CREATE_SUBMIT],
        ];
    }

    public static function getType($var)
    {
        $vars = self::getTypes();
        return isset($vars[$var]) ? $vars[$var] : null;
    }

    public static function getTypes()
    {
        return [
            self::TYPE_CREATE => yii::t('app', 'project_record_type_create'),
            self::TYPE_EDIT => yii::t('app', 'project_record_type_edit'),
            self::TYPE_EXAMINE => yii::t('app', 'project_record_type_examine'),
            self::TYPE_CONFIGURE => yii::t('app', 'project_record_type_configure'),
            self::TYPE_RESTART => yii::t('app', 'project_record_type_restart'),
            self::TYPE_FINISH => yii::t('app', 'project_record_type_finish'),
            self::TYPE_REFUSED => yii::t('app', 'project_record_type_refuse'),
        ];
    }

    public static function getScene($var)
    {
        $vars = self::getScenes();
        return isset($vars[$var]) ? $vars[$var] : null;
    }

    /**
     * @return array
     */
    public static function getScenes()
    {
        return [
            self::SCENE_CREATE_SUBMIT => yii::t('app', 'project_record_scene_create_submit'),
            self::SCENE_CREATE_CREATE => yii::t('app', 'project_record_scene_create_create'),
            self::SCENE_EDIT_INFO => yii::t('app', 'project_record_scene_edit_info'),
            self::SCENE_EDIT_PAUSE => yii::t('app', 'project_record_scene_edit_pause'),
            self::SCENE_EDIT_CONTINUE => yii::t('app', 'project_record_scene_edit_continue'),
            self::SCENE_EDIT_DELETE => yii::t('app', 'project_record_scene_edit_delete'),
            self::SCENE_EDIT_STOP => yii::t('app', 'project_record_scene_edit_stop'),
            self::SCENE_EXAMINE_PASS => yii::t('app', 'project_record_scene_examine_pass'),
            self::SCENE_EXAMINE_DENY => yii::t('app', 'project_record_scene_examine_deny'),
            self::SCENE_CONFIGURE_CONFIGURE => yii::t('app', 'project_record_scene_configure_configure'),
            self::SCENE_CONFIGURE_TEMPLATE => yii::t('app', 'project_record_scene_configure_template'),
            self::SCENE_CONFIGURE_UNPACK_SUCC => yii::t('app', 'project_record_scene_configure_unpack_succ'),
            self::SCENE_CONFIGURE_UNPACK_FAIL => yii::t('app', 'project_record_scene_configure_unpack_fail'),
            self::SCENE_FINISH_FINISH => yii::t('app', 'project_record_scene_finish_finish'),
            self::SCENE_FINISH_EXPIRE => yii::t('app', 'project_record_scene_finish_expire'),
            self::SCENE_RESTART_RESTART => yii::t('app', 'project_record_scene_restart_restart'),
        ];
    }

    function getUser(){
        return $this->hasOne(User::className(), ['id' => 'created_by'])->select(User::publicFields());
    }

    function getUserAuth(){
        return $this->hasMany(AuthAssignment::className(), ['user_id' => 'created_by']);
    }

    public static function saveRecord($projectId = '', $type = '',$scene = '', $userId = 0 , $message = '')
    {
        $_logs = [];

        $project = Project::find()->select(['table_suffix'])->where(['id' => $projectId])->asArray()->limit(1)->one();
        if(empty($project['table_suffix'])){
            $_logs['$project.table_suffix'] = $project;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_record_suffix_not_found '. json_encode($_logs));
            return false;
        }
        ProjectRecord::setTable($project['table_suffix']);

        $projectRecord  = new ProjectRecord();
        $projectRecord->project_id = $projectId;
        $projectRecord->type = $type;
        $projectRecord->message = $message;
        $projectRecord->scene = $scene;
        $projectRecord->created_by = $userId;
        $projectRecord->created_at = time();
        $projectRecord->updated_at = time();
        if (!$projectRecord->validate() || !$projectRecord->save())
        {
            $_logs['$projectRecord'] = $projectRecord;
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' projectRecord_fail '. json_encode($_logs));
            return false;
        }

        return true;

    }

    /**
     *  创建项目日志记录
     *
     *  $scene 类型对应的场景
     *  $projectId 项目id
     *  $messageData 不传则显示默认信息
     */
    public static function operateCreate($scene, $projectId)
    {
        $userId = 0;
        if(!empty(Yii::$app->user) && !empty(Yii::$app->user->id))
        {
            $userId = Yii::$app->user->id;
        }
        //查找当前项目的创建人的语言
        $project = Project::find()->where(['id' => $projectId])->with(['user'])->asArray()->limit(1)->one();
        if(empty($project) || empty($project['user']) || !isset($project['user']['language']))
        {
            $_logs['$project'] = $project;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' project_not_found ' . json_encode($_logs));
            return false;
        }
        //获取当前语言
        $oldLanguage = Yii::$app->language;

        //设置创建项目人的语言
        Yii::$app->language = User::getLanguageKey($project['user']['language']);
        
        $message = self::getScene($scene);

        if($scene == self::SCENE_CREATE_SUBMIT) {
            //获取项目文件信息
            $uploadfilePath = Setting::getUploadfilePath($project['user_id'], $projectId);
            // $attachmentPath = Setting::getAttachmentPath($project['user_id'], $projectId);

            $uploadfiles = FileHelper::get_dir_files($uploadfilePath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::UPLOADFILE_EXTS);
            // $attachmentfiles = FileHelper::get_dir_files($attachmentPath, Yii::$app->params['task_source_ignorefiles'], ProjectAttribute::ATTACHMENT_EXTS);
            $fileNum = 0;      //文件个数
            $attachmentNum = 0;      //文件个数
            $fileSize = 0;     //文件大小
            foreach ($uploadfiles as $vfile) {
                //如果文件不存在 下次循环
                if (empty($vfile)) {
                    continue;
                }
                $fileNum++;
                $fileSize += intval($vfile['size']);
            }
            // foreach ($attachmentfiles as $vfile) {
            //     //如果文件不存在 下次循环
            //     if (empty($vfile)) {
            //         continue;
            //     }
            //     $fileSize += intval($vfile['size']);
            //     $attachmentNum++;
            // }
            //文件大小换算
            $fileSize = FormatHelper::filesize_format($fileSize);
            $message = sprintf($message, $fileNum, $fileSize);
        }

        //恢复当前语言
        Yii::$app->language = $oldLanguage;

        ProjectRecord::saveRecord($projectId, self::TYPE_CREATE, $scene, $userId, $message);
    }

    /**
     *  编辑项目日志记录
     *
     *  $scene 类型对应的场景
     *  $projectId 项目id
     *  $messageData 不传则显示默认信息
     *  $oldData  修改前的数据对象
     */
    public static function operateEdit($scene, $projectId, $oldData = '')
    {
        $_logs = [];

        $userId = 0;
        if(!empty(Yii::$app->user) && !empty(Yii::$app->user->id))
        {
            $userId = Yii::$app->user->id;
        }
        //查找当前项目的创建人的语言
        $project = Project::find()->where(['id' => $projectId])->with(['user'])->asArray()->limit(1)->one();

        if(empty($project) || empty($project['user']) || !isset($project['user']['language']))
        {
            $_logs['$project'] = $project;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' project_not_found ' . json_encode($_logs));
            return false;
        }
        //获取当前语言
        $oldLanguage = Yii::$app->language;

        //设置创建项目人的语言
        Yii::$app->language = User::getLanguageKey($project['user']['language']);

        $message = self::getScene($scene);
        if($scene == self::SCENE_EDIT_INFO)
        {
            //修改项目名称 或时间
            if (!$oldData instanceof Project) {
                $_logs['$oldData'] = $oldData;
                Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' $oldData_SCENE_error ' . json_encode($_logs));
                return false;
            }
            //获取项目文件信息
            if ($oldData->name != $project['name']) {
                $message .= sprintf(yii::t('app', 'project_record_scene_edit_info_name'), $project['name']);
            }
            if (($oldData->start_time != $project['start_time']) || ($oldData->end_time != $project['end_time'])) {
                $message .= sprintf(yii::t('app', 'project_record_scene_edit_info_date'), date('Y-m-d', $project['start_time']), date('Y-m-d', $project['end_time']));
            }
        }

        //恢复当前语言
        Yii::$app->language = $oldLanguage;

        ProjectRecord::saveRecord($projectId, self::TYPE_EDIT, $scene, $userId, $message);
    }

    /**
     *  审核项目日志记录  审核状态需要改一下
     *
     *  $scene 类型对应的场景
     *  $projectId 项目id
     *  $messageData 不传则显示默认信息
     */
    public static function operateExamine($scene, $projectId)
    {

        $_logs = [];

        $userId = 0;
        if(!empty(Yii::$app->user) && !empty(Yii::$app->user->id))
        {
            $userId = Yii::$app->user->id;
        }

        //查找当前项目的创建人的语言
        $project = Project::find()->where(['id' => $projectId])->with(['user'])->asArray()->limit(1)->one();
        if(empty($project) || empty($project['user']) || !isset($project['user']['language']))
        {
            $_logs['$project'] = $project;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' project_not_found ' . json_encode($_logs));
            return false;
        }
        //获取当前语言
        $oldLanguage = Yii::$app->language;
        //设置创建项目人的语言
        Yii::$app->language = User::getLanguageKey($project['user']['language']);

        $message = self::getScene($scene);

        if($scene == self::SCENE_EXAMINE_PASS) {
            $message = sprintf($message, date('Y-m-d', $project['start_time']), date('Y-m-d', $project['end_time']));
        }

        //恢复当前语言
        Yii::$app->language = $oldLanguage;

        ProjectRecord::saveRecord($projectId, self::TYPE_EXAMINE, $scene, $userId, $message);
    }

    /**
     *  项目配置 日志
     *
     *  $scene 类型对应的场景
     *  $projectId 项目id
     *  $messageData 不传则显示默认信息
     */
    public static function operateConfigure($scene, $projectId)
    {
        $userId = 0;
        if(!empty(Yii::$app->user) && !empty(Yii::$app->user->id))
        {
            $userId = Yii::$app->user->id;
        }
        //查找当前项目的创建人的语言
        $project = Project::find()->where(['id' => $projectId])->with(['user'])->asArray()->limit(1)->one();

        if(empty($project) || empty($project['user']) || !isset($project['user']['language']))
        {
            $_logs['$project'] = $project;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' project_not_found ' . json_encode($_logs));
            return false;
        }
        //获取当前语言
        $oldLanguage = Yii::$app->language;

        //设置创建项目人的语言
        Yii::$app->language = User::getLanguageKey($project['user']['language']);

        $message = self::getScene($scene);

        //恢复当前语言
        Yii::$app->language = $oldLanguage;

        ProjectRecord::saveRecord($projectId, self::TYPE_CONFIGURE, $scene, $userId, $message);
    }

    /**
     *  项目完成 日志
     *
     *  $scene 类型对应的场景
     *  $projectId 项目id
     *  $messageData 不传则显示默认信息
     */
    public static function operateFinish($scene, $projectId)
    {
        $userId = 0;
        if(!empty(Yii::$app->user)&&!empty(Yii::$app->user->id))
        {
            $userId = Yii::$app->user->id;
        }
        //查找当前项目的创建人的语言
        $project = Project::find()->where(['id' => $projectId])->with(['user'])->asArray()->limit(1)->one();
        if(empty($project) || empty($project['user']) || !isset($project['user']['language']))
        {
            $_logs['$project'] = $project;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' project_not_found ' . json_encode($_logs));
            return false;
        }
        //获取当前语言
        $oldLanguage = Yii::$app->language;
        //设置创建项目人的语言
        Yii::$app->language = User::getLanguageKey($project['user']['language']);

        $message = self::getScene($scene);

        //恢复当前语言
        Yii::$app->language = $oldLanguage;

        ProjectRecord::saveRecord($projectId, self::TYPE_FINISH, $scene, $userId, $message);
    }

    /**
     *  项目完成 日志
     *
     *  $scene 类型对应的场景
     *  $projectId 项目id
     *  $messageData 不传则显示默认信息
     */
    public static function operateRefuse($scene, $projectId)
    {
        $userId = 0;
        if(!empty(Yii::$app->user)&&!empty(Yii::$app->user->id))
        {
            $userId = Yii::$app->user->id;
        }
        //查找当前项目的创建人的语言
        $project = Project::find()->where(['id' => $projectId])->with(['user'])->asArray()->limit(1)->one();
        if(empty($project) || empty($project['user']) || !isset($project['user']['language']))
        {
            $_logs['$project'] = $project;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' project_not_found ' . json_encode($_logs));
            return false;
        }
        //获取当前语言
        $oldLanguage = Yii::$app->language;
        //设置创建项目人的语言
        Yii::$app->language = User::getLanguageKey($project['user']['language']);

        $message = self::getScene($scene);

        //恢复当前语言
        Yii::$app->language = $oldLanguage;

        ProjectRecord::saveRecord($projectId, self::TYPE_REFUSED, $scene, $userId, $message);
    }

    /**
     *  项目重启 日志
     *
     *  $scene 类型对应的场景
     *  $projectId 项目id
     *  $messageData 不传则显示默认信息
     */
    public static function operateRestart($scene, $projectId)
    {
        $userId = 0;
        if(!empty(Yii::$app->user) && !empty(Yii::$app->user->id))
        {
            $userId = Yii::$app->user->id;
        }

        //查找当前项目的创建人的语言
        $project = Project::find()->where(['id' => $projectId])->with(['user'])->asArray()->limit(1)->one();
        if(empty($project) || empty($project['user']) || !isset($project['user']['language']))
        {
            $_logs['$project'] = $project;
            Yii::error(__CLASS__ . ' ' . __FUNCTION__ . ' ' . __LINE__ . ' project_not_found ' . json_encode($_logs));
            return false;
        }
        //获取当前语言
        $oldLanguage = Yii::$app->language;
        //设置创建项目人的语言
        Yii::$app->language = User::getLanguageKey($project['user']['language']);
        $message = self::getScene($scene);
        if($scene == self::SCENE_RESTART_RESTART)
        {
            $message = sprintf($message, date('Y-m-d', $project['start_time']), date('Y-m-d', $project['start_time']));
        }
        //恢复当前语言
        Yii::$app->language = $oldLanguage;

        ProjectRecord::saveRecord($projectId, self::TYPE_RESTART, $scene, $userId, $message);
    }


}
