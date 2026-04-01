<?php
/**
 * 添加或修改用户表单模型
 */

namespace api\models;

use Yii;
use yii\base\Model;
use common\models\AuthItem;
use common\models\Site;
use common\models\SiteUser;
use common\models\Team;
// use common\models\TeamUser;
// use common\models\TeamGroup;
use common\models\User;
use common\models\UserAttribute;
use common\models\UserStat;
// use common\models\CrowdsourcingUser;
use common\models\Task;
use common\models\UserDevice;
use common\models\App;

class UserForm extends Model
{
    public $id;
    public $email;
    public $password;
    public $mobile;
    public $phone;
    public $nickname;
    public $realname;
    public $company;
    public $type;
    public $status;
    public $roles;
    public $tags;
    //public $team_id;
    //public $team_group_id;
    public $avatar;
    public $language;
    //public $crowdsourcing_id;
    public $site_id;
    public $created_by;
    public $created_from;
    public $push_token;
    public $app_key;
    public $isEdit;
    //public $relate_team_id;
    public $verify_token;
    public $assign_user_type; //仅处理指定用户类型

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            
            [['phone', 'nickname', 'realname', 'company'], 'string', 'max' => 64],
            
//            ['nickname','required'],

            ['type', 'integer'],
            ['type', 'in', 'range' => array_keys(User::getTypes())],
            
			['email', 'email'],

            ['mobile', 'string', 'max' => 32],
            ['mobile', 'match', 'pattern'=> User::getMobileRegex(), 'message'=> yii::t('app', 'user_mobile_format_error')],
            
            ['phone', 'match', 'pattern'=>User::getPhoneRegex(), 'message'=> yii::t('app', 'phone_format_error')],

            ['password', 'string', 'min' => 6, 'max' => 18,  'tooLong'=> Yii::t('app', 'password_length_error'), 'tooShort'=>Yii::t('app', 'password_length_error')],
            ['password', 'match', 'pattern'=>User::getPasswordRegex(),'message'=> Yii::t('app', 'password_format_error')],

            ['push_token', 'string', 'max' => 254],

            ['app_key', 'string', 'max' => 64],
            ['app_key', 'default', 'value' => ''],

            ['avatar', 'string', 'max' => 254],
            
            ['language', 'integer'],
            ['language', 'default', 'value' => key(User::getLanguages())],
            ['language', 'in', 'range' => array_keys(User::getLanguages())],
            
            //['team_group_id', 'default', 'value' => 0],
            
            ['site_id', 'integer'],
            ['site_id', 'default', 'value' => 0],

            ['status', 'integer'],
            ['status', 'default', 'value' => User::STATUS_ACTIVE],
            ['status', 'in', 'range' => [User::STATUS_NOTACTIVE, User::STATUS_ACTIVE, User::STATUS_DELETED, User::STATUS_DISABLE]],

            ['created_by', 'integer'],
            ['created_by', 'default', 'value' => 0],

            ['created_from', 'integer'],

            ['verify_token', 'string'],
            ['verify_token', 'default', 'value' => ''],
        ];
    }


    public function attributeLabels()
    {
        return [
            'phone' => Yii::t('app', 'userForm_field_phone'),
            'nickname' => Yii::t('app', 'userForm_field_nickname'),
            'realname' => Yii::t('app', 'userForm_field_realname'),
            'company' => Yii::t('app', 'userForm_field_company'),
            'type' => Yii::t('app', 'userForm_field_type'),
            'email' => Yii::t('app', 'userForm_field_email'),
            'mobile' => Yii::t('app', 'userForm_field_mobile'),
            'password' => Yii::t('app', 'userForm_field_password'),
            'avatar' => Yii::t('app', 'userForm_field_avatar'),
            'language' => Yii::t('app', 'userForm_field_language'),
            'created_from' => Yii::t('app', 'userForm_field_created_from'),
        ];
    }


	/**
	 * notes:	保存个人信息
	 * return	object
	 */
    public function save()
    {
        $_logs = [];

        //编辑用户
        if ($this->id)
        {
            //$user = User::findOne($this->id);
            $user = User::find()->where(['id'=>$this->id])->with('site')->one();
            $user->updated_at = time();

            $_logs['$user'] = $user->getAttributes();
        }
        //注册新用户
        else
        {
            $user = new User();
            $user->created_by = $this->created_by ? $this->created_by : Yii::$app->user->id;
            $user->created_at = time();
            //$user->created_from = $this->created_from ? $this->created_from : User::CREATED_FROM_DEFAULT;
            $user->verify_token = $this->verify_token;

            if($this->status == User::STATUS_DELETED)
            {
                //禁止添加已删除状态的用户
                $this->addError('roles', Yii::t('app', 'user_status_error'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_status_error '.json_encode($_logs));
                return false;
            }
        }

        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_type '.json_encode($_logs));
        //------------------------------------

        //更新push_token
        if(!is_null($this->push_token))
        {
            //为空字符串时则清空
            $userDevice = UserDevice::getUserDeviceByAccessToken();
            if($userDevice && $userDevice['push_token'] != $this->push_token)
            {
                UserDevice::updateAll(['push_token'=>$this->push_token],['id'=>$userDevice['id']]);
            }

        }

        //用户中心修改自己的信息
        if ($this->type === null)
        {
            if ($user->id != Yii::$app->user->id)
            {
                $this->addError('user_no_permission', Yii::t('app', 'user_no_permission'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
                return false;
            }

            $this->nickname !== null && $user->nickname = $this->nickname ?: '';
            $this->realname !== null && $user->realname = $this->realname ?: '';
            $this->email !== null && $user->email = $this->email;
            $this->mobile !== null && $user->mobile = $this->mobile;
            $this->phone !== null && $user->phone = $this->phone;
            $user->company = $this->company ?: '';
            $this->language !== null && $user->language = $this->language;
            $this->avatar !== null && $user->avatar = $this->avatar;
            if(!$user->validate() || !$user->save())
            {
                $this->addErrors($user->getErrors());
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                return false;
            }
        }
        //执行者为管理员, 被操作者可为任意类型
        elseif (Yii::$app->user->identity->type == User::TYPE_ROOT)
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' operator type == root '.json_encode($_logs));
            
			//自己不能在非用户中心修改自己的资料
			if ($user->id == Yii::$app->user->id)
			{
				$this->addError('email', Yii::t('app', 'user_cannot_updateself'));
				Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_cannot_updateself '.json_encode($_logs));
				return false;
			}
			
			//不能把租户中最后一个用户移除
			if(isset($user->site->id) && isset($this->site_id) && $user->site->id != $this->site_id &&  $user->site->status != Site::STATUS_DELETED)
			{
				$siteUserCount = SiteUser::find()->where(['site_id'=>$user->site->id])->andWhere(['status'=>SiteUser::STATUS_ENABLE])->asArray()->count();
				if($siteUserCount <= 1)
				{
					$this->addError('user_cannot_remove_last_site_user', Yii::t('app', 'user_cannot_remove_last_site_user'));
					Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_cannot_remove_last_site_user '.json_encode($_logs));
					return false;
				}
			}

            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_type=root '.json_encode($_logs));
            if (in_array(AuthItem::ROLE_ROOT, Yii::$app->user->identity->roleKeys))
            {
                
            }
            //运营人员
            else
            {
                //若目标用户有管理员权限, 则不通过
                if (in_array(AuthItem::ROLE_ROOT, $user->roleKeys))
                {
                    $this->addError('roles', Yii::t('app', 'user_permission_forbidden_for_manager'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden_for_manager '.json_encode($_logs));
                    return false;
                }
                
                //若修改后的权限包含管理员权限, 则不通过
                if (in_array(AuthItem::ROLE_ROOT, $this->roles))
                {
                    $this->addError('roles', Yii::t('app', 'user_permission_forbidden_for_manager'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden_for_manager '.json_encode($_logs));
                    return false;
                }
            }
            
            $this->email !== null && $user->email = $this->email;
            $this->mobile !== null && $user->mobile = $this->mobile;
            $this->phone !== null && $user->phone = $this->phone;
            $this->nickname !== null && $user->nickname = $this->nickname ?: '';
            $this->company !== null && $user->company = $this->company ?: '';
            $this->type !== null && $user->type = $this->type;
            $this->status !== null && $user->status = $this->status;
            $this->language !== null && $user->language = $this->language;
            $this->avatar !== null && $user->avatar = $this->avatar;
            if ($this->password)
            {
                //Root修改他人密码后，且密码变更，被修改用户需重新登录
                if($user->id != Yii::$app->user->id && !$user->validatePassword($this->password))
                {
                    //设置用户设备离线
                    //UserDevice::setOffLine($user->id);
                }

                $user->setPassword($this->password);
            }
            $user->generateAuthKey();
            if(!$user->validate())
            {
                $this->addErrors($user->getErrors());
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                return false;
            }
            
            if ($this->type !== null && $this->type == User::TYPE_ROOT)
            {
                if(!$user->save())
                {
                    $this->addErrors($user->getErrors());
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
                
                if(!empty($user->siteUser))
                {
                    SiteUser::deleteAll(['user_id' => $user->id]);
                }
            
//                 if(!empty($user->teamUser))
//                 {
//                     $teamUser = $user->teamUser;
//                     $teamUser->status = TeamUser::STATUS_DISABLE;
//                     $teamUser->updated_at = time();
//                     $teamUser->save();
//                 }

                //删除已分配给该用户的任务
                //Task::delTaskUser([$user->id]);
            
//                 if(!empty($user->crowdsourcingUser))
//                 {
//                     $crowdsourcingUser = $user->crowdsourcingUser;
//                     $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
//                     $crowdsourcingUser->save();
//                 }
            }
            elseif ($this->type !== null && $this->type == User::TYPE_ADMIN)
            {
                //20210201 编辑admin 修改租户id
                if ($this->id)
                {
                    if (!empty($user->siteUser) && $user->siteUser->site_id != $this->site_id)
                    {
                        //检查站点是否可用
                        $canCreate = Site::canCreateUser($this->site_id);
                        if ($canCreate['error'])
                        {
                            $_logs['$canCreate'] = $canCreate;
                            $this->addError('site_id', $canCreate['message']);
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                            return false;
                        }
                    }
                }
                //新增用户
                elseif (!$this->id)
                {
                    //检查站点是否可用
                    $canCreate = Site::canCreateUser($this->site_id);
                    if ($canCreate['error'])
                    {
                        $_logs['$canCreate'] = $canCreate;
                        $this->addError('site_id', $canCreate['message']);
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                        return false;
                    }
                }
                
                if(!$user->save())
                {
                    $this->addErrors($user->getErrors());
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
                
                //更新租户
                if(!empty($user->siteUser))
                {
                    SiteUser::deleteAll(['user_id' => $user->id]);
                }
                
                $siteUser = new SiteUser();
                $siteUser->site_id = $this->site_id;
                $siteUser->user_id = $user->id;
                $siteUser->status = SiteUser::STATUS_ENABLE;
                $siteUser->save();

                //更新租户用户数
                $site = Site::find()->where(['id' => $siteUser->site_id])->asArray()->limit(1)->one();
                if($site)
                {
                    $counters = [
                        'user_count' => 1,
                        'updated_at' => time() - $site['updated_at']
                    ];
                    Site::updateAllCounters($counters, ['id' => $site['id']]);
                }
                

                //-----------------------------

                //关联团队变更
//                 if (!empty($user->teamUser))
//                 {
//                     $teamUser = $user->teamUser;
//                     $teamUser->status = TeamUser::STATUS_DISABLE;
//                     $teamUser->updated_at = time();
//                     $teamUser->save();

//                     //变更团队 删除已分配给该用户的任务
//                     $user->teamUser->team_id == $this->relate_team_id && Task::delTaskUser([$user->id]);
//                 }

                //关联团队 如果存在关联则 删除关联关系
                //201226 优化-项目发布断点 注释
                /*$existTeamToUser = TeamToUser::find()->where(['user_id' => $user->id])->asArray()->limit(1)->one();
                if ($existTeamToUser)
                {
                    TeamToUser::deleteAll(['user_id' => $user->id]);
                }

                if (!empty($this->relate_team_id))
                {
                    //验证团队是否属于租户
                    $team = Team::find()->where(['id' => $this->relate_team_id, 'site_id' => $this->site_id])->asArray()->limit(1)->one();
                    if (empty($team))
                    {
                        $_logs['relate_team_id'] = $this->relate_team_id;
                        $this->addError('relate_team_id', Yii::t('app', 'team_ineffective'));
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not found '.json_encode($_logs));
                        return false;
                    }


                    $dataList[] = ['user_id' => $user->id, 'team_id' => $this->relate_team_id, 'created_by' => Yii::$app->user->id, 'created_at' => time()];
                    //写入用户关联团队表
                    TeamToUser::batchInsert(['user_id', 'team_id', 'created_by', 'created_at'], $dataList);
                }*/

//                 if(!empty($user->crowdsourcingUser))
//                 {
//                     $crowdsourcingUser = $user->crowdsourcingUser;
//                     $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
//                     $crowdsourcingUser->save();
//                 }

//                 if(!empty($user->crowdsourcingUser))
//                 {
//                     $crowdsourcingUser = $user->crowdsourcingUser;
//                     $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
//                     $crowdsourcingUser->save();
//                 }
            }
            elseif ($this->type !== null && $this->type == User::TYPE_WORKER)
            {
                //20210201 编辑team 修改租户id
                if ($this->id)
                {
                    if (!empty($user->siteUser) && $user->siteUser->site_id != $this->site_id)
                    {
                        //检查站点是否可用
                        $canCreate = Site::canCreateUser($this->site_id);
                        if ($canCreate['error'])
                        {
                            $_logs['$canCreate'] = $canCreate;
                            $this->addError('site_id', $canCreate['message']);
                            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                            return false;
                        }
                    }
                    
                    //检查站点是否可用
                    // $canCreate = Site::canCreateTeamUser($this->site_id, $this->team_id);
                    // if ($canCreate['error'])
                    // {
                    //     $_logs['$canCreate'] = $canCreate;
                    //     $this->addError('site_id', $canCreate['message']);
                    //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    //     return false;
                    // }
                }
                //新增用户
                elseif (!$this->id)
                {
                    //检查站点是否可用
                    $canCreate = Site::canCreateUser($this->site_id);
                    if ($canCreate['error'])
                    {
                        $_logs['$canCreate'] = $canCreate;
                        $this->addError('site_id', $canCreate['message']);
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                        return false;
                    }
                }
                
//                 if (empty($this->team_id))
//                 {
//                     $_logs['team_id'] = $this->team_id;
//                     $this->addError('team_id', Yii::t('app', 'team_notselected'));
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team_id empty '.json_encode($_logs));
//                     return false;
//                 }
                
                //判断团队是否属于租户
//                 $team = Team::find()->where(['id' => $this->team_id])->asArray()->limit(1)->one();
//                 if (empty($team))
//                 {
//                     $_logs['team_id'] = $this->team_id;
//                     $this->addError('team_id', Yii::t('app', 'team_ineffective'));
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not found '.json_encode($_logs));
//                     return false;
//                 }
                
//                 if ($team['site_id'] != $this->site_id)
//                 {
//                     $_logs['team_id'] = $this->team_id;
//                     $this->addError('team_id', Yii::t('app', 'team_ineffective'));
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not belong to site '.json_encode($_logs));
//                     return false;
//                 }
                
                if(!$user->save())
                {
                    $this->addErrors($user->getErrors());
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
                
                //更新租户
                if(!empty($user->siteUser))
                {
                    SiteUser::deleteAll(['user_id' => $user->id]);
                }
                
                $siteUser = new SiteUser();
                $siteUser->site_id = $this->site_id;
                $siteUser->user_id = $user->id;
                $siteUser->status = SiteUser::STATUS_ENABLE;
                $siteUser->save();

                //更新租户用户数
                $site = Site::find()->where(['id' => $siteUser->site_id])->asArray()->limit(1)->one();
                if($site)
                {
                    $counters = [
                            'user_count' => 1,
                            'updated_at' => time() - $site['updated_at']
                    ];
                    Site::updateAllCounters($counters, ['id' => $site['id']]);
                }
                
                /*
                if(!empty($user->teamUser))
                {
                    $teamUser = $user->teamUser;
                    $oldTeamGroupId = $teamUser->team_group_id;
                    $oldTeamId = $teamUser->team_id;
            
                    $teamUser->team_id = $this->team_id;
                    $teamUser->team_group_id = $this->team_group_id;
                    $teamUser->status = TeamUser::STATUS_ENABLE;
                    $teamUser->updated_at = time();
                    $teamUser->save();
            
                    if($oldTeamGroupId != $this->team_group_id)
                    {
                        if($this->team_group_id > 0)
                        {
                            //新小组加1
                            TeamGroup::updateAllCounters( ['count' => 1], ['id' => $this->team_group_id] );
                        }
            
                        if($oldTeamGroupId > 0)
                        {
                            //旧小组减一
                            TeamGroup::updateAllCounters(['count' => -1], ['id' => $oldTeamGroupId]);
                        }
                    }

                    //变更团队
                    if($oldTeamId != $this->team_id){
                        //删除已分配给该用户的任务
                        Task::delTaskUser([$user->id]);
                    }

                }
                else
                {
                    $teamUser = new TeamUser();
                    $teamUser->user_id = $user->id;
                    $teamUser->team_id = $this->team_id;
                    $teamUser->team_group_id = $this->team_group_id;
                    $teamUser->status = TeamUser::STATUS_ENABLE;
        
                    $teamUser->created_at = time();
                    $teamUser->save();
                    
                    //小组成员加1
                    if($this->team_group_id)
                    {
                        TeamGroup::updateAllCounters( ['count' => 1], ['id' => $this->team_group_id] );
                    }
                }
                 
                if(!empty($user->crowdsourcingUser))
                {
                    $crowdsourcingUser = $user->crowdsourcingUser;
                    $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
                    $crowdsourcingUser->save();
                }*/
            }
//             else if ($this->type !== null && $this->type == User::TYPE_CROWDSOURCING)
//             {
                
//                 if(!$user->save())
//                 {
//                     $this->addErrors($user->getErrors());
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
//                     return false;
//                 }

//                 //删除已分配给该用户的任务
//                 Task::delTaskUser([$user->id]);
//             }
            else
            {
                $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
                return false;
            }
            
            //处理标签和角色
            if ($this->tags !== null)
            {
                User::updateUserTag($user->id, $this->tags);
            }

            if ($this->roles !== null)
            {
                User::updateUserRole($user->id, $this->roles, $this->assign_user_type);
            }
        }
        elseif (in_array(Yii::$app->user->identity->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' operator type == admin,worker '.json_encode($_logs));
	        
			//自己不能在非用户中心修改自己的资料
			if ($user->id == Yii::$app->user->id)
			{
				$this->addError('email', Yii::t('app', 'user_cannot_updateself'));
				Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_cannot_updateself '.json_encode($_logs));
				return false;
			}

	        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_type=admin '.json_encode($_logs));
            if (empty(Yii::$app->user->identity->site))
            {
                $this->addError('username', Yii::t('app', 'user_no_site'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return false;
            }
	        $siteId = Yii::$app->user->identity->site->id;
	        $_logs['$siteId'] = $siteId;

            //20210201 编辑team 修改租户id
            if ($this->id)
	        {
                if (!empty($user->siteUser) && $user->siteUser->site_id != $this->site_id)
                {
                    //检查站点是否可用
                    $canCreate = Site::canCreateUser($this->site_id);
                    if ($canCreate['error'])
                    {
                        $_logs['$canCreate'] = $canCreate;
                        $this->addError('site_id', $canCreate['message']);
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                        return false;
                    }
                }
	        }
            //新增用户
            elseif (!$this->id)
            {
                //检查站点是否可用
                $canCreate = Site::canCreateUser($this->site_id);
                if ($canCreate['error'])
                {
                    $_logs['$canCreate'] = $canCreate;
                    $this->addError('site_id', $canCreate['message']);
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
            }

	        if ($this->type == User::TYPE_WORKER && !($this->id))
	        {
	            //检查站点是否可用
	            // $canCreate = Site::canCreateTeamUser($siteId, $this->team_id);
	            // if ($canCreate['error'])
	            // {
	            //     $_logs['$canCreate'] = $canCreate;
	            //     $this->addError('site_id', $canCreate['message']);
	            //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
	            //     return false;
	            // }
	        }
	        
	        if ($this->id)
	        {
	            //目标用户类型必须为管理或团队
	            if (!in_array($user->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
	            {
	                $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
	                return false;
	            }
	            
	            if (empty($user->site))
	            {
	                $this->addError('username', Yii::t('app', 'user_no_site'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
	                return false;
	            }
	            
	            //操作人租户必须和被操作人租户一直
	            if ($siteId != $user->site->id)
	            {
	                $this->addError('username', Yii::t('app', 'user_permission_forbidden'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
	                return false;
	            }
	        }
	        
	        //更新后用户类型必须为管理或团队
	        if (!in_array($this->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
	        {
	            $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
	            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
	            return false;
	        }

            $this->site_id = $siteId;

	        if (in_array(AuthItem::ROLE_MANAGER, Yii::$app->user->identity->roleKeys))
	        {
	        
	        }
	        //运营人员
	        else
	        {
	            //若目标用户有管理员权限, 则不通过
	            if (in_array(AuthItem::ROLE_MANAGER, $user->roleKeys))
	            {
	                $this->addError('roles', Yii::t('app', 'user_permission_forbidden_for_manager'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden_for_manager '.json_encode($_logs));
	                return false;
	            }
	            
	            //若修改后的权限包含管理员权限, 则不通过
	            if (in_array(AuthItem::ROLE_MANAGER, $this->roles))
	            {
	                $this->addError('roles', Yii::t('app', 'user_permission_forbidden_for_manager'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden_for_manager '.json_encode($_logs));
	                return false;
	            }
	        }
	        
            $this->email !== null && $user->email = $this->email;
            $this->mobile !== null && $user->mobile = $this->mobile;
            $this->phone !== null && $user->phone = $this->phone;
            $this->nickname !== null && $user->nickname = $this->nickname ?: '';
            $this->company !== null && $user->company = $this->company ?: '';
            $this->type !== null && $user->type = $this->type;
            $this->status !== null && $user->status = $this->status;
            $this->language !== null && $user->language = $this->language;
            $this->avatar !== null && $user->avatar = $this->avatar;
            if ($this->password)
            {
                $user->setPassword($this->password);
            }
            $user->generateAuthKey();
            if(!$user->validate())
            {
                $this->addErrors($user->getErrors());
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                return false;
            }
            
            if ($this->type == User::TYPE_ADMIN)
            {
                if(!$user->save())
                {
                    $this->addErrors($user->getErrors());
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
                
                if(!empty($user->siteUser))
                {
                    SiteUser::deleteAll(['user_id' => $user->id]);
                }
                
                $siteUser = new SiteUser();
                $siteUser->site_id = $siteId;
                $siteUser->user_id = $user->id;
                $siteUser->status = SiteUser::STATUS_ENABLE;
                $siteUser->created_by = Yii::$app->user->id;
                $siteUser->save();

                //更新租户用户数
                $site = Site::find()->where(['id' => $siteUser->site_id])->asArray()->limit(1)->one();
                if($site)
                {
                    $counters = [
                        'user_count' => 1,
                        'updated_at' => time() - $site['updated_at']
                    ];
                    Site::updateAllCounters($counters, ['id' => $site['id']]);
                }

                //--------------------------------

                //关联团队变更
//                 if (!empty($user->teamUser))
//                 {
//                     $teamUser = $user->teamUser;
//                     $teamUser->status = TeamUser::STATUS_DISABLE;
//                     $teamUser->updated_at = time();
//                     $teamUser->save();

//                     //变更团队 删除已分配给该用户的任务
//                     $user->teamUser->team_id == $this->relate_team_id && Task::delTaskUser([$user->id]);
//                 }

                //关联团队 如果存在关联则 删除关联关系
                //201226 优化-项目发布断点 注释
                /*$existTeamToUser = TeamToUser::find()->where(['user_id' => $user->id])->asArray()->limit(1)->one();
                if ($existTeamToUser)
                {
                    TeamToUser::deleteAll(['user_id' => $user->id]);
                }

                if (!empty($this->relate_team_id))
                {
                    //验证团队是否属于租户
                    $team = Team::find()->where(['id' => $this->relate_team_id, 'site_id' => $this->site_id])->asArray()->limit(1)->one();
                    if (empty($team))
                    {
                        $_logs['relate_team_id'] = $this->relate_team_id;
                        $this->addError('relate_team_id', Yii::t('app', 'team_ineffective'));
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not found '.json_encode($_logs));
                        return false;
                    }


                    $dataList[] = ['user_id' => $user->id, 'team_id' => $this->relate_team_id, 'created_by' => Yii::$app->user->id, 'created_at' => time()];
                    //写入用户关联团队表
                    TeamToUser::batchInsert(['user_id', 'team_id', 'created_by', 'created_at'], $dataList);
                }*/

//                 if(!empty($user->crowdsourcingUser))
//                 {
//                     $crowdsourcingUser = $user->crowdsourcingUser;
//                     $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
//                     $crowdsourcingUser->save();
//                 }
            }
            elseif ($this->type == User::TYPE_WORKER)
            {
                /*
                if (empty($this->team_id))
                {
                    $_logs['team_id'] = $this->team_id;
                    $this->addError('team_id', Yii::t('app', 'team_notselected'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team_id empty '.json_encode($_logs));
                    return false;
                }
                 
                //判断团队是否属于租户
                $team = Team::find()->where(['id' => $this->team_id])->asArray()->limit(1)->one();
                if (empty($team))
                {
                    $_logs['team_id'] = $this->team_id;
                    $this->addError('team_id', Yii::t('app', 'team_ineffective'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not found '.json_encode($_logs));
                    return false;
                }
                 
                if ($team['site_id'] != $this->site_id)
                {
                    $_logs['team_id'] = $this->team_id;
                    $this->addError('team_id', Yii::t('app', 'team_ineffective'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not belong to site '.json_encode($_logs));
                    return false;
                }*/

                if(!$user->save())
                {
                    $this->addErrors($user->getErrors());
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
                
                if(!empty($user->siteUser))
                {
                    SiteUser::deleteAll(['user_id' => $user->id]);
                }
                
                $siteUser = new SiteUser();
                $siteUser->site_id = $siteId;
                $siteUser->user_id = $user->id;
                $siteUser->status = SiteUser::STATUS_ENABLE;
                $siteUser->created_by = Yii::$app->user->id;
                $siteUser->save();

                //更新租户用户数
                $site = Site::find()->where(['id' => $siteUser->site_id])->asArray()->limit(1)->one();
                if($site)
                {
                    $counters = [
                        'user_count' => 1,
                        'updated_at' => time() - $site['updated_at']
                    ];
                    Site::updateAllCounters($counters, ['id' => $site['id']]);
                }

                /*
                if(!empty($user->teamUser))
                {
                    $teamUser = $user->teamUser;
                    $oldTeamGroupId = $teamUser->team_group_id;
                    $oldTeamId = $teamUser->team_id;
            
                    $teamUser->team_id = $this->team_id;
                    $teamUser->team_group_id = $this->team_group_id;
                    $teamUser->status = TeamUser::STATUS_ENABLE;
                    $teamUser->updated_at = time();
                    $teamUser->save();
            
                    if($oldTeamGroupId != $this->team_group_id)
                    {
                        if($this->team_group_id > 0)
                        {
                            //新小组加1
                            TeamGroup::updateAllCounters( ['count' => 1], ['id' => $this->team_group_id] );
                        }
            
                        if($oldTeamGroupId > 0)
                        {
                            //旧小组减一
                            TeamGroup::updateAllCounters(['count' => -1], ['id' => $oldTeamGroupId]);
                        }
                    }

                    //变更团队
                    if($oldTeamId != $this->team_id){
                        //删除已分配给该用户的任务
                        Task::delTaskUser([$user->id]);
                    }

                }
                else
                {
                    $teamUser = new TeamUser();
                    $teamUser->user_id = $user->id;
                    $teamUser->team_id = $this->team_id;
                    $teamUser->team_group_id = $this->team_group_id;
                    $teamUser->status = TeamUser::STATUS_ENABLE;
        
                    $teamUser->created_at = time();
                    $teamUser->save();
                    
                    if($this->team_group_id)
                    {
                        //小组成员加1
                        TeamGroup::updateAllCounters( ['count' => 1], ['id' => $this->team_group_id] );
                    }
                }
                 
                if(!empty($user->crowdsourcingUser))
                {
                    $crowdsourcingUser = $user->crowdsourcingUser;
                    $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
                    $crowdsourcingUser->save();
                }*/
            }
            else
            {
                $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
                return false;
            }
            	
            if ($this->tags !== null)
            {
                User::updateUserTag($user->id, $this->tags);
            }
            
            if ($this->roles !== null)
            {
                User::updateUserRole($user->id, $this->roles);
            }
	        
	    }
	    //执行者为团队类型, 被操作者只能为团队类型
	    elseif (in_array(Yii::$app->user->identity->type, [User::TYPE_ADMIN, User::TYPE_WORKER]))
	    {
	        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' operator type == admin,worker '.json_encode($_logs));
	        
			//自己不能在非用户中心修改自己的资料
			if ($user->id == Yii::$app->user->id)
			{
// 				if(empty($user->teamUser) || empty($this->team_group_id) || $user->teamUser->team_group_id == $this->team_group_id){
//                     $userDevices = $user->userDevice;
//                     $userDeviceTypes = array_column($userDevices, 'device_type');
//                     if(!in_array(UserDevice::DEVICE_TYPE_MOBILE, $userDeviceTypes) || $this->status != User::STATUS_DISABLE) //APP作业员可以自己注销
//                     {
//     					$this->addError('email', Yii::t('app', 'user_cannot_updateself'));
//     					Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_cannot_updateself '.json_encode($_logs));
//     					return false;
//                     }
// 				}
			}

	        Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_type=team '.json_encode($_logs));
            if (empty(Yii::$app->user->identity->site))
            {
                $this->addError('roles', Yii::t('app', 'user_no_site'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_site '.json_encode($_logs));
                return false;
            }
	        $siteId = Yii::$app->user->identity->site->id;

            //20210201 编辑team 修改租户id
            if ($this->id)
	        {
                if (!empty($user->siteUser) && $user->siteUser->site_id != $this->site_id)
                {
                    //检查站点是否可用
                    $canCreate = Site::canCreateUser($this->site_id);
                    if ($canCreate['error'])
                    {
                        $_logs['$canCreate'] = $canCreate;
                        $this->addError('site_id', $canCreate['message']);
                        Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                        return false;
                    }
                }
    	         
    	        //检查站点是否可用
    	        // $canCreate = Site::canCreateTeamUser($siteId, $this->team_id);
    	        // if ($canCreate['error'])
    	        // {
    	        //     $_logs['$canCreate'] = $canCreate;
    	        //     $this->addError('site_id', $canCreate['message']);
    	        //     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
    	        //     return false;
    	        // }
	        }
            //新增用户
            elseif (!$this->id)
            {
                //检查站点是否可用
                $canCreate = Site::canCreateUser($this->site_id);
                if ($canCreate['error'])
                {
                    $_logs['$canCreate'] = $canCreate;
                    $this->addError('site_id', $canCreate['message']);
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
            }
	        
	        if ($this->id)
	        {
	            //目标用户类型必须为团队
	            if (!in_array($user->type, [User::TYPE_WORKER]))
	            {
	                $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
	                return false;
	            }
	            
	            if (empty($user->site))
	            {
	                $this->addError('roles', Yii::t('app', 'user_no_site'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
	                return false;
	            }
	             
	            //操作人租户必须和被操作人租户一直
	            if ($siteId != $user->site->id)
	            {
	                $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
	                return false;
	            }
	        }
	        
	        //判断更新后的用户类型
// 	        if (!in_array($this->type, [User::TYPE_WORKER]))
// 	        {
// 	            $_logs['$this.type'] = $this->type;
// 	            $this->addError('type', '用户类型错误');
// 	            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' type error '.json_encode($_logs));
// 	            return false;
// 	        }

            $this->site_id = $siteId;

	        if (in_array(AuthItem::ROLE_TEAM_MANAGER, Yii::$app->user->identity->roleKeys))
	        {
	            $_logs['type'] = $this->type;
	            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' TYPE_WORKER,ROLE_TEAM_MANAGER '.json_encode($_logs));

                if($this->status == User::STATUS_DISABLE && $user->id == Yii::$app->user->id) //团队管理员不能注销/禁用自己
                {
                    $this->addError('status', Yii::t('app', 'user_disable_self_no_permission'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' can not disable self '.json_encode($_logs));
                    return false;
                }
	            
	            $this->email !== null && $user->email = $this->email;
	            $this->mobile !== null && $user->mobile = $this->mobile;
	            $this->phone !== null && $user->phone = $this->phone;
	            $this->nickname !== null && $user->nickname = $this->nickname ?: '';
	            $this->company !== null && $user->company = $this->company ?: '';
	            $this->type !== null && $user->type = $this->type;
	            $this->status !== null && $user->status = $this->status;
	            $this->language !== null && $user->language = $this->language;
	            $this->avatar !== null && $user->avatar = $this->avatar;
	            if ($this->password)
	            {
	                $user->setPassword($this->password);
	            }
	            $user->generateAuthKey();
	            if(!$user->validate())
	            {
	                $this->addErrors($user->getErrors());
	                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
	                return false;
	            }
	            
//                 if (empty($this->team_id))
//                 {
//                     $_logs['team_id'] = $this->team_id;
//                     $this->addError('team_id', Yii::t('app', 'team_notselected'));
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team_id empty '.json_encode($_logs));
//                     return false;
//                 }
                
                /*
                $team = Team::find()->where(['id' => $this->team_id])->asArray()->limit(1)->one();
                if (empty($team))
                {
                    $_logs['team_id'] = $this->team_id;
                    $this->addError('team_id', Yii::t('app', 'team_ineffective'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not found '.json_encode($_logs));
                    return false;
                }
                
                if ($team['site_id'] != $this->site_id)
                {
                    $_logs['team_id'] = $this->team_id;
                    $this->addError('team_id', Yii::t('app', 'team_ineffective'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' team not belong to site '.json_encode($_logs));
                    return false;
                }*/
                
                if(!$user->save())
                {
                    $this->addErrors($user->getErrors());
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
                
                if(!empty($user->siteUser))
                {
                    SiteUser::deleteAll(['user_id' => $user->id]);
                }
                
                $siteUser = new SiteUser();
                $siteUser->site_id = $siteId;
                $siteUser->user_id = $user->id;
                $siteUser->status = SiteUser::STATUS_ENABLE;
                $siteUser->created_by = Yii::$app->user->id;
                $siteUser->save();

                //更新租户用户数
                $site = Site::find()->where(['id' => $siteUser->site_id])->asArray()->limit(1)->one();
                if($site)
                {
                    $counters = [
                        'user_count' => 1,
                        'updated_at' => time() - $site['updated_at']
                    ];
                    Site::updateAllCounters($counters, ['id' => $site['id']]);
                }

                /*
                if(!empty($user->teamUser))
                {
                    $teamUser = $user->teamUser;
                    $oldTeamGroupId = $teamUser->team_group_id;
                    $oldTeamId = $teamUser->team_id;

                    $teamUser->team_id = $this->team_id;
                    $teamUser->team_group_id = $this->team_group_id;
                    $teamUser->status = TeamUser::STATUS_ENABLE;
                    $teamUser->updated_at = time();
                    $teamUser->save();

                    if($oldTeamGroupId != $this->team_group_id)
                    {
                        if($this->team_group_id)
                        {
                            //新小组加1
                            TeamGroup::updateAllCounters( ['count' => 1], ['id' => $this->team_group_id] );
                        }

                        if($oldTeamGroupId)
                        {
                            //旧小组减一
                            TeamGroup::updateAllCounters(['count' => -1], ['id' => $oldTeamGroupId]);
                        }
                    }

                    //变更团队
                    if($oldTeamId != $this->team_id){
                        //删除已分配给该用户的任务
                        Task::delTaskUser([$user->id]);
                    }

                }
                else
                {
                    $teamUser = new TeamUser();
                    $teamUser->team_id = $this->team_id;
                    $teamUser->team_group_id = $this->team_group_id;
                    $teamUser->user_id = $user->id;
                    $teamUser->status = TeamUser::STATUS_ENABLE;
                    $teamUser->created_at = time();
                    $teamUser->save();
                    
                    if($this->team_group_id)
                    {
                        //小组成员加1
                        TeamGroup::updateAllCounters( ['count' => 1], ['id' => $this->team_group_id] );
                    }
                }
                 
                if(!empty($user->crowdsourcingUser))
                {
                    $crowdsourcingUser = $user->crowdsourcingUser;
                    $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
                    $crowdsourcingUser->save();
                }*/
	            
	            if ($this->tags !== null)
	            {
	                User::updateUserTag($user->id, $this->tags);
	            }
	            
	            if ($this->roles !== null)
	            {
	                User::updateUserRole($user->id, $this->roles);
	            }
	        }
            elseif(in_array(AuthItem::ROLE_TEAM_WORKER, Yii::$app->user->identity->roleKeys)) //APP作业员注销
            {
                $_logs['type'] = $this->type;
                Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' TYPE_WORKER '.json_encode($_logs));

                if($user->id != Yii::$app->user->id)
                {
                    $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
                    return false;
                }
                $this->status !== null && $user->status = $this->status;
                if(!$user->validate() || !$user->save())
                {
                    $this->addErrors($user->getErrors());
                    Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
                    return false;
                }
            }
	        else
	        {
                $this->addError('roles', Yii::t('app', 'user_permission_forbidden'));
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_permission_forbidden '.json_encode($_logs));
                return false;
	        }
	    }
	    //执行者为众包, 被执行者只能为众包
// 	    elseif (Yii::$app->user->identity->type == User::TYPE_CROWDSOURCING)
// 	    {
// 			//自己不能在非用户中心修改自己的资料
// 			if ($user->id == Yii::$app->user->id)
// 			{
// 				$this->addError('email', Yii::t('app', 'user_cannot_updateself'));
// 				Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_cannot_updateself '.json_encode($_logs));
// 				return false;
// 			}

// 	        if (!in_array($user->type, [User::TYPE_CROWDSOURCING]))
// 	        {
// 	            $_logs['$user.type'] = $user->type;
// 	            $this->addError('type', Yii::t('app', 'user_no_permission'));
// 	            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission error '.json_encode($_logs));
// 	            return false;
// 	        }
	        
// 	        if (in_array(AuthItem::ROLE_CROWDSOURCING_MANAGER, Yii::$app->user->identity->roleKeys))
//             {
//                 Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' TYPE_CROWDSOURCING,ROLE_CROWDSOURCING_MANAGER '.json_encode($_logs));
                
//                 $this->email !== null && $user->email = $this->email;
//                 $this->mobile !== null && $user->mobile = $this->mobile;
//                 $this->phone !== null && $user->phone = $this->phone;
//                 $this->nickname !== null && $user->nickname = $this->nickname ?: '';
//                 $this->company !== null && $user->company = $this->company ?: '';
//                 $this->type !== null && $user->type = $this->type;
//                 $this->status !== null && $user->status = $this->status;
//                 $this->language !== null && $user->language = $this->language;
//                 $this->avatar !== null && $user->avatar = $this->avatar;
//                 if ($this->password)
//                 {
//                     $user->setPassword($this->password);
//                 }
//                 $user->generateAuthKey();
//                 if(!$user->validate() || !$user->save())
//                 {
//                     $this->addErrors($user->getErrors());
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_save_error '.json_encode($_logs));
//                     return false;
//                 }
                
//                 if ($this->tags !== null)
//                 {
//                     User::updateUserTag($user->id, $this->tags);
//                 }
                
//                 if ($this->roles !== null)
//                 {
//                     User::updateUserRole($user->id, $this->roles);
//                 }
                
//                 /*
//                 if (empty($this->crowdsourcing_id))
//                 {
//                     $this->addError('crowdsourcing_id', '请选择众包');
//                     Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_crowdsourcing_id '.json_encode($_logs));
//                     return false;
//                 }
//                 */
            
//                 if(!empty($user->teamUser))
//                 {
//                     $teamUser = $user->teamUser;
//                     $teamUser->status = TeamUser::STATUS_DISABLE;
//                     $teamUser->updated_at = time();
//                     $teamUser->save();
//                 }
            
//                 if(!empty($user->crowdsourcingUser))
//                 {
//                     $crowdsourcingUser = $user->crowdsourcingUser;
//                     $crowdsourcingUser->crowdsourcing_id = $this->crowdsourcing_id;
//                     $crowdsourcingUser->status = CrowdsourcingUser::STATUS_ENABLE;
//                     $crowdsourcingUser->save();
//                 }
//                 else
//                 {
//                     $crowdsourcingUser = new CrowdsourcingUser();
//                     $crowdsourcingUser->user_id = $user->id;
//                     $crowdsourcingUser->crowdsourcing_id = $this->crowdsourcing_id;
//                     $crowdsourcingUser->status = CrowdsourcingUser::STATUS_ENABLE;
//                     $crowdsourcingUser->created_at = time();
//                     $crowdsourcingUser->save();
//                 }
//             }
//             else
//             {
//                 $this->addError('roles', Yii::t('app', 'user_no_permission'));
//                 Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
//                 return false;
//             }
// 	    }
// 	    //执行者为客户, 被执行者只能为客户
// 	    elseif (Yii::$app->user->identity->type == User::TYPE_CUSTOMER)
// 	    {
//             $this->addError('roles', Yii::t('app', 'user_no_permission'));
//             Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_no_permission '.json_encode($_logs));
//             return false;
// 	    }

	    //处理删除时的所属关系
	    if ($user->status == User::STATUS_DELETED)
	    {
	        //删除用户所属租户关系
	        if(!empty($user->siteUser))
	        {
	            SiteUser::deleteAll(['user_id' => $user->id]);
	        }
	        
	        //删除用户所属团队关系
// 	        if(!empty($user->teamUser))
// 	        {
// 	            $teamUser = $user->teamUser;
// 	            $teamUser->status = TeamUser::STATUS_DISABLE;
//                 $teamUser->updated_at = time();
// 	            $teamUser->save();
// 	        }

            //删除已分配给该用户的任务
            //Task::delTaskUser([$user->id]);

	        //删除用户所属团队关系
// 	        if(!empty($user->crowdsourcingUser))
// 	        {
// 	            $crowdsourcingUser = $user->crowdsourcingUser;
// 	            $crowdsourcingUser->status = CrowdsourcingUser::STATUS_DISABLE;
// 	            $crowdsourcingUser->save();
// 	        }

            //删除团队用户关联关系
            /*if (!empty($user->getTeamToUser))
            {
                TeamToUser::deleteAll(['user_id' => $user->id]);
            }*/
	    }

        //修改了密码
        if ($this->password)
        {
            //设置用户设备离线
//            UserDevice::setOffLine($user->id);

            //清除登录access_token缓存，避免缓存引起的验证错误
            User::clearCache($user->id);
        }
	    
	    $userAttr = UserAttribute::find()->where(['user_id' => $user->id])->limit(1)->asArray()->one();
	    if (!$userAttr)
	    {
	        $userAttr = new UserAttribute();
	        $userAttr->user_id = $user->id;
	        $userAttr->save();
	    }
	    
	    $userStat = UserStat::find()->where(['user_id' => $user->id])->limit(1)->asArray()->one();
	    if (!$userStat)
	    {
	        $userStat = new UserStat();
	        $userStat->user_id = $user->id;
	        $userStat->new_message_count = 0;
	        $userStat->save();
	    }

        //清除用户缓存
	    User::clearCache($user->id);
	    
	    $this->id = $user->id;
        return $user;
    }
    
}