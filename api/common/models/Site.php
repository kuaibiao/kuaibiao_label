<?php

namespace common\models;

use Yii;
use common\helpers\FormatHelper;
use yii\behaviors\TimestampBehavior;

/**
 * Site 数据表模型
 *
 */
class Site extends \yii\db\ActiveRecord
{
    //状态
    const STATUS_NOTACTIVE = 0;//未激活
    const STATUS_ACTIVED = 1;//已激活
    const STATUS_DISABLED = 2;//已禁用 已冻结
    const STATUS_EXPIRED = 3;//已过期
    const STATUS_DELETED = 4;//已删除

    //租户类型
    const TYPE_WORKER = 0; //团队,即供应商（20211009注册优化改版）
    const TYPE_DEMANDER = 1; //需求方

    //默认语言
    const LANGUAGE_ZH_CN = 0;
    const LANGUAGE_EN = 1;

    //来源
    const CREATED_FROM_CREATE = 0; //创建
    const CREATED_FROM_WEB_SIGNUP = 1; //官网注册
    const CREATED_FROM_DEMAND = 2; //需求转化
    const CREATED_FROM_PLATFORM_SIGNUP = 3; //平台注册

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'site';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['created_by', 'integer'],
            ['created_by', 'default', 'value' => 0],

            [['created_at', 'updated_at'], 'integer'],

            ['name', 'trim'],
            ['name', 'required'],
            ['name', 'string', 'max' => 60],
            ['name', 'validateName'],

            ['logo', 'string', 'max' => 254],
            ['logo', 'default', 'value' => ''],

            ['language', 'integer'],
            ['language', 'default', 'value' => 0],
            
            //[['start_time', 'end_time'], 'required'],
            [['start_time', 'end_time'], 'integer'],
            ['start_time', 'compare', 'compareAttribute' => 'end_time', 'operator' => '<='],
            //[['start_time', 'end_time'], 'validateTime'],

            ['user_count_limit', 'integer'],
            ['user_count_limit', 'default', 'value' => 0],

            ['data_count_limit', 'integer'],
            ['data_count_limit', 'default', 'value' => 0],

            ['disk_space_limit', 'integer'],
            ['disk_space_limit', 'default', 'value' => 0],
            
            ['last_login_time', 'integer'],
            ['last_login_time', 'default', 'value' => 0],
            
            ['status', 'trim'],
            ['status', 'integer'],
            ['status', 'required'],
            ['status', 'default', 'value' => self::STATUS_NOTACTIVE],
            ['status', 'in', 'range' => array_keys(self::getStatuses())],

            ['type', 'integer'],
            ['type', 'default', 'value' => self::TYPE_DEMANDER],
            //['type', 'in', 'range' => array_keys(self::getTypes())],

            ['created_from', 'integer'],
            ['created_from', 'default', 'value' => 0],
        ];
    }
    
    public function behaviors()
    {
        return [
            'timeStamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => time()
            ]
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => Yii::t('app', 'site_field_name'),
            'logo' => Yii::t('app', 'site_field_logo'),
            'start_time' => Yii::t('app', 'site_field_start_time'),
            'end_time' => Yii::t('app', 'site_field_end_time'),
            'status' => Yii::t('app', 'site_field_status'),
        ];
    }
    
    public function validateName($attribute, $params)
    {
        $_logs = ['name' => $this->name];
    
        if (!$this->hasErrors())
        {
            $site = Site::find()
            ->select(['id', 'name'])
            ->where(['name' => $this->name])
            ->andWhere(['not', ['status' => Site::STATUS_DELETED]])
            ->limit(1)->asArray()->one();
            if ($site)
            {
                if ($site['id'] != $this->id)
                {
                    $this->addError($attribute, Yii::t('app', 'site_name_existed'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_name_existed '.json_encode($_logs));
                    return false;
                }
            }
    
            return true;
        }
    }

    public function validateTime($attribute, $params)
    {
        $_logs = ['start_time' => $this->start_time, 'end_time' => $this->end_time];
    
        if (!$this->hasErrors())
        {
            if ($this->end_time < $this->start_time)
            {
                $this->addError($attribute, Yii::t('app', 'endtime_must_be_longer'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' endtime_must_be_longer '.json_encode($_logs));
                return false;
            }
        }
    }
    
    public static function getStatus($var)
    {
        $vars = self::getStatuses();
        return isset($vars[$var]) ? $vars[$var] : null;
    }
    
    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_NOTACTIVE => yii::t('app', 'site_status_not_active'),
            self::STATUS_ACTIVED => yii::t('app', 'site_status_active'),
            self::STATUS_DISABLED => yii::t('app', 'site_status_disabled'),
            self::STATUS_EXPIRED => yii::t('app', 'site_status_expired'),
        ];
    }

    public static function getCreatedFrom()
    {
        return [
            self::CREATED_FROM_CREATE => yii::t('app', 'site_created_from_create'),
            self::CREATED_FROM_WEB_SIGNUP => yii::t('app', 'site_created_from_web_signup'),
            self::CREATED_FROM_DEMAND => yii::t('app', 'site_created_from_demand'),
            self::CREATED_FROM_PLATFORM_SIGNUP => yii::t('app', 'site_created_from_platform_signup'),
        ];
    }

    public function getCreator(){
        return $this->hasOne(User::className(), ['id' => 'created_by'])
        ->select(['id', 'email', 'nickname', 'type', 'status', 'mobile']);
    }
    
    /**
     * 判断站点是否可用
     * 
     * @param int $siteId
     * @param number $add_data_count 要增加的数据量, 单位个
     * @param number $add_disk_space 要增加的存储空间, 单位MB
     */
    public static function canCreate($siteId, $add_data_count = 0, $add_disk_space = 0) 
    {
        $siteInfo = Site::find()->where(['id' => $siteId])->asArray()->limit(1)->one();
        $_logs['$siteInfo'] = $siteInfo;
        
//        $siteUserCount = User::find()->where(['site_id' => $siteId])->andWhere(['not', ['status' => User::STATUS_DELETED]])->count();
        $siteUserCount = SiteUser::find()->where(['site_id' => $siteId])->andWhere(['status' => SiteUser::STATUS_ENABLE])->count();
        $_logs['$siteUserCount'] = $siteUserCount;
        if ($siteUserCount >= $siteInfo['user_count_limit'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_members_limited '.json_encode($_logs));
            return FormatHelper::resultStrongType('', 'site_members_limited', Yii::t('app', 'site_members_limited'));
        }
        
        // //$siteDataCount = Project::find()->where(['site_id' => $siteId])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('amount');
        // $siteDataCount = (int)self::userFileAmountUsed($siteId);
        // $_logs['$siteDataCount'] = $siteDataCount;
        // if ($siteDataCount + $add_data_count >= $siteInfo['data_count_limit'] * 10000)
        // {
        //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_datas_limited '.json_encode($_logs));
        //     return FormatHelper::resultStrongType('', 'site_datas_limited', Yii::t('app', 'site_datas_limited'));
        // }
        
        //$siteDiskSpace = Project::find()->where(['site_id' => $siteId])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('disk_space');
        $siteDiskSpace = self::userDiskSpaceUsed($siteId);
        if ($siteDiskSpace + $add_disk_space >= $siteInfo['disk_space_limit'] * 1000)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_diskspace_limited '.json_encode($_logs));
            return FormatHelper::resultStrongType('', 'site_diskspace_limited', Yii::t('app', 'site_diskspace_limited'));
        }
        
        return FormatHelper::resultStrongType(1);
    }
    
    public static function canCreateUser($siteId)
    {
        $siteInfo = Site::find()->where(['id' => $siteId])->asArray()->limit(1)->one();
        $_logs['$siteInfo'] = $siteInfo;
        
        if (!$siteInfo)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_not_exist '.json_encode($_logs));
            return FormatHelper::resultStrongType('', 'site_not_exist', Yii::t('app', 'site_not_exist'));
        }
        
        $siteUserCount = SiteUser::find()->where(['site_id' => $siteId, 'status' => SiteUser::STATUS_ENABLE])->count();

        if ($siteInfo['user_count_limit'] > 0 && $siteUserCount >= $siteInfo['user_count_limit'])
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_members_limited '.json_encode($_logs));
            return FormatHelper::resultStrongType('', 'site_members_limited', Yii::t('app', 'site_members_limited'));
        }
        
        return FormatHelper::resultStrongType(1);
    }
    
    /**
     * 租户是否可以执行任务
     */
    public static function canExecuteTask($siteId, $taskId)
    {
        $_logs = ['$siteId' => $siteId, '$taskId' => $taskId];
        
        if(empty($taskId))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_id_not_given '.json_encode($_logs));
            return FormatHelper::result('', 'task_id_not_given', Yii::t('app', 'task_id_not_given'));
        }
        $task = Task::find()->where(['id' => $taskId])->with(['project', 'step'])->asArray()->limit(1)->one();
        if(empty($task))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_not_found '.json_encode($_logs));
            return FormatHelper::result('', 'task_not_found', Yii::t('app', 'task_not_found'));
        }
        if($task['status'] != Task::STATUS_NORMAL)
        {
            if($task['status'] == Task::STATUS_DELETED)
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_status_deleted '.json_encode($_logs));
                return FormatHelper::result('', 'task_status_deleted', Yii::t('app', 'task_status_deleted'));
            }
            else if(in_array($task['status'], [Task::STATUS_FINISH, Task::STATUS_PAUSED]))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_status_finished_or_paused '.json_encode($_logs));
                return FormatHelper::result('', 'task_status_finished_or_paused', Yii::t('app', 'task_status_finished_or_paused'));
            }
            else
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task_status_not_allow '.json_encode($_logs));
                return FormatHelper::result('', 'task_status_not_allow', Yii::t('app', 'task_status_not_allow'));
            }
        }
        if(empty($task['project']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' project_not_found '.json_encode($_logs));
            return FormatHelper::result('', 'project_not_found', Yii::t('app', 'project_not_found'));
        }
        $project = $task['project'];
        $_logs['$project'] = $project;

        //root和admin不做限制
        if(in_array($task['platform_type'], [Task::PLATFORM_TYPE_ROOT, Task::PLATFORM_TYPE_ADMIN]))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task root or admin '.json_encode($_logs));
            return FormatHelper::result(1);
        }

        $site = Site::find()->where(['id' => $siteId])->with(['categoryToSite', 'money'])->asArray()->limit(1)->one();
        $_logs['$site'] = $site;
        if(empty($site))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site not found '.json_encode($_logs));
            return FormatHelper::result('', 'site_not_found', Yii::t('app', 'site_not_found'));
        }
        
        /*分类权限不再影响执行作业
        //外部项目不对分类做限制
        if(!in_array($project['type'], [Project::TYPE_ENTRUST, Project::TYPE_NORMAL, Project::TYPE_TENDER]))
        {
            if(empty($site['categoryToSite']))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site no category '.json_encode($_logs));
                return FormatHelper::result('', 'task_site_no_category', Yii::t('app', 'task_site_no_category'));
            }
            //$siteCategoryIds = CategoryToSite::find()->select(['category_id'])->where(['site_id' => Yii::$app->user->identity->site->id])->asArray()->column();
            $siteCategoryIds = empty($site['categoryToSite']) ? [] : array_column($site['categoryToSite'], 'category_id');
            $_logs['$siteCategoryIds'] = $siteCategoryIds;
            if(!in_array($project['category_id'], $siteCategoryIds))
            {
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site no category '.json_encode($_logs));
                return FormatHelper::result('', 'task_site_no_category', Yii::t('app', 'task_site_no_category'));
            }
        }*/

        /*//做自己租户的项目需要余额限制（也包括是外部项目分配给租户自己团队的情况）
        if($site['id'] == $task['site_id'])
        {*/
            if(Setting::getSetting('open_charge'))
            {
                if(!empty($site['money']['money']) && $site['money']['money'] < 0)
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site no work_time '.json_encode($_logs));
                    return FormatHelper::result('', 'task_site_no_work_time', Yii::t('app', 'task_site_no_work_time'));
                }
            }
            /*else
            {
                $siteResourceUsed = Site::getCurrentResourceUsed($siteId);
                $workTimeUsed = round($siteResourceUsed['work_time'] / 3600, 3);
                $workTimeLimit = Yii::$app->params['root_work_time_limit'];

                if(empty($site['work_time_limit']))
                {
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site no work_time '.json_encode($_logs));
                    return FormatHelper::result('', 'task_site_no_work_time', Yii::t('app', 'task_site_no_work_time'));
                }
                
                if($workTimeLimit !== 0)
                {
                    if($site['work_time_limit'] > $workTimeLimit)
                    {
                        $_logs['$site.work_time_limit'] = $site['work_time_limit'];
                        $_logs['$workTimeLimit'] = $workTimeLimit;

                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' task site exception '.json_encode($_logs));
                        return FormatHelper::result('', 'task_site_exception', Yii::t('app', 'task_site_exception'));
                    }
                }
                
                if($workTimeUsed >= $site['work_time_limit'])
                {
                    $_logs['$workTimeUsed'] = $workTimeUsed;
                    $_logs['$site.work_time_limit'] = $site['work_time_limit'];
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site no work_time '.json_encode($_logs));
                    return FormatHelper::result('', 'task_site_no_work_time', Yii::t('app', 'task_site_no_work_time'));
                }
            }
        }*/

        return FormatHelper::result(1);
    }
    
    public static function canCreateProject($siteId, $add_data_count = 0, $add_disk_space = 0)
    {
        $siteInfo = Site::find()->where(['id' => $siteId])->asArray()->limit(1)->one();
        $_logs['$siteInfo'] = $siteInfo;
        
        // //$siteDataCount = Project::find()->where(['site_id' => $siteId])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('amount');
        // $siteDataCount = (int)self::userFileAmountUsed($siteId);
        // $_logs['$siteDataCount'] = $siteDataCount;
        // if ($siteDataCount + $add_data_count >= $siteInfo['data_count_limit'] * 10000)
        // {
        //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_datas_limited '.json_encode($_logs));
        //     return FormatHelper::resultStrongType('', 'site_datas_limited', Yii::t('app', 'site_datas_limited'));
        // }
        
        //$siteDiskSpace = Project::find()->where(['site_id' => $siteId])->andWhere(['not', ['status' => Project::STATUS_DELETE]])->sum('disk_space');
        $siteDiskSpace = self::userDiskSpaceUsed($siteId);
        
        if ($siteDiskSpace + $add_disk_space >= $siteInfo['disk_space_limit'] * 1024 * 1024 * 1024)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' site_diskspace_limited '.json_encode($_logs));
            return FormatHelper::resultStrongType('', 'site_diskspace_limited', Yii::t('app', 'site_diskspace_limited'));
        }
        
        return FormatHelper::resultStrongType(1);
    }
    
    
}
