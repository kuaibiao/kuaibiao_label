<?php
/**
 * Created by 权限
 * User: jiashuaiqun
 * Date: 2018/3/15 0015
 * Time: 下午 15:14
 */

namespace common\models;
use Yii;

class AuthItem extends \yii\db\ActiveRecord
{
    
    const TYPE_PERMISSION = 1;//权限
    const TYPE_ROLE = 2;//角色
    //const TYPE_GROUP = 3;//用户组
    
    const ROLE_GUEST = 'guest';

    const ROLE_WORKER = 'worker';
    const ROLE_MANAGER = 'manager';
    const ROLE_ROOT = 'root';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name','description'], 'required'],
            [['created_at','updated_at'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'description' => 'Description',
            'name' => 'Name',
            'rule_name' => 'RuleName',
            'data' => 'Data',
            'created_at' => 'CreatedAt',
            'updated_at' => 'UpdatedAt',
        ];
    }
    
    public static function getRole($var)
    {
        $vars = self::getRoles();
        
        return isset($vars[$var]) ? $vars[$var] : null;
    }
    
    public static function getRoles()
    {
        return [
//            self::ROLE_GUEST => yii::t('app', 'role_guest'),
            self::ROLE_MANAGER => yii::t('app', 'role_manager'),
            self::ROLE_WORKER => yii::t('app', 'role_team_worker'),
            self::ROLE_ROOT => yii::t('app', 'role_root'),

        ];
    }

    public static function getAdminRoles()
    {
        return array_intersect_key(self::getRoles(), array_flip([
//            self::ROLE_GUEST,
            self::ROLE_WORKER,
            self::ROLE_MANAGER,
        ]));
    }
    
    public static function getWorkerRoles()
    {
        return array_intersect_key(self::getRoles(), array_flip([
        //            self::ROLE_GUEST,
            self::ROLE_WORKER,
        ]));
    }
    
    public static function getRolesByType($type)
    {
        $roles = [];
        if ($type == User::TYPE_WORKER)
        {
            $roles = self::getWorkerRoles();
        }
//         elseif ($type == User::TYPE_CROWDSOURCING)
//         {
//             $roles = self::getCrowdsourcingRoles();
//         }
//         elseif ($type == User::TYPE_CUSTOMER)
//         {
//             $roles = self::getCustomerRoles();
//         }
        elseif ($type == User::TYPE_ADMIN)
        {
            $roles = self::getAdminRoles();
        }
        elseif ($type == User::TYPE_ROOT)
        {
            $roles = self::getRoles();
        }
        
        //        $roles = array_merge($roles, self::getPlatformRoles($type));
        
        return $roles;
    }

    /**
     * 权限分组
     * @param   $permissions 权限列表
     * @return  array [<description>]
     */
    static function toGroup($permissions){
        $result     = [];
        foreach($permissions as $permission){
            $group  = substr($permission->name, 0, strpos($permission->name, '/'));
            $group  = $group ? $group : 'none';
            isset($result[$group]) || $result[$group] = [];
            $result[$group][]   = $permission;
        }

        return $result;
    }

    //关联用户
    function getUsers(){
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->viaTable(AuthAssignment::tableName(), ['item_name' => 'name'])
            ->select(['id', 'email', 'nickname', 'avatar', 'type', 'status', 'sex', 'language']);
    }
    
    
    /**
     * 初始化角色表
     *
     * @return string[][]
     */
    public static function getInitRoles()
    {
    
        return array(
            'guest' => array('name' => 'guest'),
    
            /*'customer_manager' => array('name' => self::getRole('customer_manager'), 'userid' => '22,29,30'),
            'customer_worker' => array('name' => self::getRole('customer_worker'), 'userid' => '22,29,30'),*/
    
            'manager' => array('name' => self::getRole('manager'), 'userid' => '24'),
            'worker' => array('name' => self::getRole('worker'), 'userid' => '25,26,31,32,33'),
    
            /*'crowdsourcing_manager' => array('name' => self::getRole('crowdsourcing_manager'), 'userid' => '23,27,28'),
            'crowdsourcing_worker' => array('name' => self::getRole('crowdsourcing_worker'), 'userid' => '23,27,28'),
            
            'admin_manager' => array('name' => self::getRole('admin_manager'), 'userid' => '45'),
            'admin_worker' => array('name' => self::getRole('admin_worker'), 'userid' => '1'),
            
            'root_manager' => array('name' => self::getRole('root_manager'), 'userid' => '1'),
            'root_worker' => array('name' => self::getRole('root_worker'), 'userid' => '1'),*/
        );
    }
    
    /**
     * 初始化路由表
     *
     * @return string[][]
     */
    public static function getInitRoutes()
    {
        return array(
    
            'project/form' => array('name' => '显示项目表单', 'role' => 'manager'),
            'project/create' => array('name' => '创建项目,选择分类', 'role' => 'manager'),
            'project/submit' => array('name' => '创建项目,提交表单', 'role' => 'manager'),
            'project/get-task' => array('name' => '获取项目的任务信息', 'role' => 'manager'),
            'project/set-task' => array('name' => '设置任务', 'role' => 'manager'),
            'project/projects' => array('name' => '所有项目列表', 'role' => 'manager'),
            'project/detail' => array('name' => '项目详情', 'role' => 'manager,worker'),
            'project/delete' => array('name' => '删除项目', 'role' => 'manager'),
            'project/pause' => array('name' => '暂停项目', 'role' => 'manager'),
            'project/continue' => array('name' => '继续项目, 只有暂停可恢复', 'role' => 'manager'),
            'project/stop' => array('name' => '停止项目', 'role' => 'manager'),
            'project/restart' => array('name' => '重启停止的项目', 'role' => 'manager'),
            'project/copy' => array('name' => '复制项目', 'role' => 'manager'),
            'project/records' => array('name' => '项目操作记录', 'role' => 'manager,worker'),
            'project/recovery' => array('name' => '重启完成的项目', 'role' => 'manager'),
            'project/finish' => array('name' => '设置已完成项目', 'role' => 'manager'),


            'task/assign-user-list' => array('name' => '分配用户列表', 'role' => 'manager'),
            'task/assign-users' => array('name' => '分配用户', 'role' => 'manager'),

            'site/upload-private-file' => array('name' => '上传私有文件', 'role' => 'manager,worker'),
            'site/delete-private-file' => array('name' => '删除私有文件', 'role' => 'manager'),
            'site/upload-public-image' => array('name' => '上传公有图片(头像)', 'role' => 'manager,worker'),

            'pack/form' => array('name' => '文件打包脚本列表', 'role' => 'manager'),
            'pack/list' => array('name' => '文件打包列表', 'role' => 'manager'),

            'stat/export' => array('name' => '导出报表', 'role' => 'manager,worker'),
            'stat/operation-export' => array('name' => '导出操作记录', 'role' => 'manager'),
            'stat/user-stat-list' => array('name' => '获取用户在每个任务的绩效列表', 'role' => 'manager,worker'),
            'stat/user' => array('name' => '获取用户任务的绩效', 'role' => 'manager,worker'),
            'stat/work-form' => array('name' => '获取用户作业的绩效统计的表单', 'role' => 'manager,worker'),
            'stat/work' => array('name' => '获取用户作业的绩效统计', 'role' => 'manager,worker'),
            'stat/task' => array('name' => '获取任务的绩效列表', 'role' => '*'),
            /* 
            'aimodel/list' => array('name' => 'AI模型列表', 'role' => 'root_worker,root_manager'),
            'aimodel/create' => array('name' => 'AI模型创建', 'role' => 'root_worker,root_manager'),
            'aimodel/update' => array('name' => 'AI模型更新', 'role' => 'root_worker,root_manager'),
            'aimodel/delete' => array('name' => 'AI模型删除', 'role' => 'root_worker,root_manager'),
            'aimodel/detail' => array('name' => 'AI模型详情', 'role' => 'root_worker,root_manager'),
            'aimodel/copy' => array('name' => 'AI模型复制', 'role' => 'root_worker,root_manager'),
            'aimodel/category-list' => array('name' => 'AI模型分类列表', 'role' => 'root_worker,root_manager'),
            'aimodel/category-create' => array('name' => 'AI模型分类创建', 'role' => 'root_worker,root_manager'),
            'aimodel/category-update' => array('name' => 'AI模型分类更新', 'role' => 'root_worker,root_manager'),
            'aimodel/category-delete' => array('name' => 'AI模型分类删除', 'role' => 'root_worker,root_manager'),
            'aimodel/category-detail' => array('name' => 'AI模型分类详情', 'role' => 'root_worker,root_manager'),
            
            //---auth 部分---------------------------------------------------------//
            
            'auth/roles' => array('name' => '角色列表获取', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/role-users' => array('name' => '获取角色中用户列表', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/role-detail' => array('name' => '获取角色详情', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/role-create' => array('name' => '创建角色', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/role-update' => array('name' => '更改角色信息', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/role-delete' => array('name' => '删除角色', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/permissions' => array('name' => '权限列表', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/permissions-to-group' => array('name' => '权限列表获取(分组形式)', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/permission-create' => array('name' => '添加权限', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/permission-update' => array('name' => '修改权限', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/permission-delete' => array('name' => '删除权限', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/user-create' => array('name' => '添加角色成员', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/user-delete' => array('name' => '删除角色成员', 'role' => 'admin_manager,root_worker,root_manager'),
            'auth/move-user' => array('name' => '转移角色成员', 'role' => 'admin_manager,root_worker,root_manager'),
    
            //---batch 部分---------------------------------------------------------//
    
            'batch/batchs' => array('name' => '批次列表', 'role' => '*'),
            'batch/detail' => array('name' => '批次详情', 'role' => '*'),
    
            //---category 部分---------------------------------------------------------//
    
            'category/categories' => array('name' => '分类列表（分语言）', 'role' => 'root_worker,root_manager'),
            'category/categories-with-language' => array('name' => '分类列表', 'role' => 'root_worker,root_manager'),
            'category/detail' => array('name' => '分类详情（分语言）', 'role' => 'root_worker,root_manager'),
            'category/detail-with-language' => array('name' => '分类详情', 'role' => 'root_worker,root_manager'),
            'category/form' => array('name' => '分类表单', 'role' => 'root_worker,root_manager'),
            'category/create' => array('name' => '创建分类', 'role' => 'root_worker,root_manager'),
            'category/update' => array('name' => '修改分类（分语言）', 'role' => 'root_worker,root_manager'),
            'category/delete' => array('name' => '删除分类', 'role' => 'root_worker,root_manager'),
    
            'crowdsourcing/list' => array('name' => '众包列表', 'role' => '*'),
            'crowdsourcing/create' => array('name' => '创建众包', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'crowdsourcing/update' => array('name' => '更改众包', 'role' => 'crowdsourcing_manager,admin_worker,admin_manager,root_worker,root_manager'),
            'crowdsourcing/users' => array('name' => '获取众包成员', 'role' => 'crowdsourcing_manager,admin_worker,admin_manager,root_worker,root_manager'),
            'crowdsourcing/user-update' => array('name' => '众包成员编辑', 'role' => 'crowdsourcing_manager,admin_worker,admin_manager,root_worker,root_manager'),
            'crowdsourcing/user-add' => array('name' => '众包成员添加', 'role' => 'crowdsourcing_manager,admin_worker,admin_manager,root_worker,root_manager'),
            'crowdsourcing/user-delete' => array('name' => '众包成员删除', 'role' => 'crowdsourcing_manager,admin_worker,admin_manager,root_worker,root_manager'),
            'crowdsourcing/detail' => array('name' => '获取众包详情', 'role' => 'crowdsourcing_manager,admin_worker,admin_manager,root_worker,root_manager'),
    
            //---crowdsourcing 部分---------------------------------------------------------//
    
            'crowdsourcing/list' => array('name' => '众包列表获取', 'role' => '*'),
            'crowdsourcing/create' => array('name' => '创建众包', 'role' => '*'),
            'crowdsourcing/update' => array('name' => '更改众包', 'role' => '*'),
            'crowdsourcing/users' => array('name' => '获取众包成员', 'role' => '*'),
            'crowdsourcing/user-add' => array('name' => '众包成员添加', 'role' => '*'),
            'crowdsourcing/user-update' => array('name' => '众包成员编辑', 'role' => '*'),
            'crowdsourcing/user-delete' => array('name' => '众包成员删除', 'role' => '*'),
            'crowdsourcing/detail' => array('name' => '获取众包详情', 'role' => '*'),
    */
    
            //---data 部分---------------------------------------------------------//
    
            'data/list' => array('name' => '数据列表', 'role' => 'manager'),
            
            //---message 部分---------------------------------------------------------//
    
            'message/list' => array('name' => '消息列表', 'role' => 'manager,worker'),
            'message/detail' => array('name' => '消息详情', 'role' => 'manager,worker'),
            'message/user-messages' => array('name' => '获取用户消息', 'role' => 'manager,worker'),
            'message/user-read' => array('name' => '消息读取', 'role' => 'manager,worker'),
            'message/user-delete' => array('name' => '消息删除', 'role' => 'manager,worker'),
            'message/revoke' => array('name' => '撤销通知', 'role' => 'manager,worker'),
            'message/send' => array('name' => '发送消息', 'role' => 'manager,worker'),
            'message/form' => array('name' => '消息表单', 'role' => 'manager,worker'),
            /*
            //---money 部分---------------------------------------------------------//
    
            'money/moneys' => array('name' => '资金列表', 'role' => '*'),
            'money/money-records' => array('name' => '资金记录列表', 'role' => '*'),
            'money/withdrawal-records' => array('name' => '提现记录列表', 'role' => '*'),
            'money/user-money' => array('name' => '我的银两', 'role' => '*'),
            'money/user-money-record' => array('name' => '用户收入记录列表', 'role' => '*'),
            'money/user-withdrawal' => array('name' => '获取用户资金（提现）', 'role' => '*'),
            'money/user-withdrawal-submit' => array('name' => '用户提现申请', 'role' => '*'),
            'money/user-withdrawal-record' => array('name' => '用户收入列表', 'role' => '*'),
            'money/payments' => array('name' => '收款列表', 'role' => '*'),
            'money/withdrawals' => array('name' => '提现记录列表', 'role' => '*'),
            'money/incomes' => array('name' => '收入列表', 'role' => '*'),
            'money/create-payment' => array('name' => '增加余额', 'role' => '*'),

            //---notice 部分---------------------------------------------------------//
            */
            'notice/list' => array('name' => '公告列表', 'role' => 'manager,worker'),
            'notice/create' => array('name' => '创建公告', 'role' => 'manager,worker'),
            'notice/update' => array('name' => '修改公告', 'role' => 'manager,worker'),
            'notice/delete' => array('name' => '删除公告', 'role' => 'manager,worker'),
            /*
            //---project 部分---------------------------------------------------------//
            'project/form' => array('name' => '显示项目表单', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/create' => array('name' => '创建项目,选择分类', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/submit' => array('name' => '创建项目,提交表单', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/assign-team' => array('name' => '分配团队', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/get-data' => array('name' => '获取数据结构', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/assign-data' => array('name' => '设置数据', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/get-step' => array('name' => '获取分步', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/set-step' => array('name' => '设置分步', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/get-task' => array('name' => '获取项目的任务信息', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/set-task' => array('name' => '设置任务', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/projects' => array('name' => '所有项目列表', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/detail' => array('name' => '项目详情', 'role' => '*'),
            'project/delete' => array('name' => '删除项目', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/pause' => array('name' => '暂停项目', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'project/continue' => array('name' => '继续项目, 只有暂停可恢复', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'project/stop' => array('name' => '停止项目', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/restart' => array('name' => '重启停止的项目', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/copy' => array('name' => '复制项目', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/records' => array('name' => '项目操作记录', 'role' => '*'),
            'project/recovery' => array('name' => '重启完成的项目', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'project/finish' => array('name' => '设置已完成项目', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),

            //---site 部分---------------------------------------------------------//
            
            'setting/list' => array('name' => '设置管理', 'role' => 'root_worker,root_manager'),
            'setting/create' => array('name' => '添加', 'role' => 'root_worker,root_manager'),
            'setting/update' => array('name' => '更新', 'role' => 'root_worker,root_manager'),
            'setting/delete' => array('name' => '删除', 'role' => 'root_worker,root_manager'),
    
            'site/error' => array('name' => '错误处理', 'role' => '*'),
            'site/offline' => array('name' => '服务器维护', 'role' => '*'),
            'site/init' => array('name' => '初始化配置', 'role' => '*'),
            'site/login' => array('name' => '登录', 'role' => '*'),
            'site/login-quick' => array('name' => '快速登录', 'role' => '*'),
            'site/signup' => array('name' => '注册', 'role' => '*'),
            'site/captcha' => array('name' => '图片验证码', 'role' => '*'),
            'site/forget-password' => array('name' => '忘记密码', 'role' => '*'),
            'site/forget-password-new' => array('name' => '忘记密码提交', 'role' => '*'),
            'site/send-phone-code' => array('name' => '发送短信验证码', 'role' => '*'),
            'site/send-email-code' => array('name' => '发送邮箱验证码', 'role' => '*'),
            'site/categorylist' => array('name' => '官网的分类页面调用的接口', 'role' => '*'),
            'site/upload-public-image' => array('name' => '上传公有图片(头像)', 'role' => '*'),
            'site/upload-private-file' => array('name' => '上传私有文件', 'role' => '*'),
            'site/delete-private-file' => array('name' => '删除私有文件', 'role' => '*'),
            'site/download-private-file' => array('name' => '加载私有文件(binary)', 'role' => '*'),
            'site/fetch-private-file' => array('name' => '分批加载私有文件(base64)', 'role' => '*'),
            'site/upload-public-file' => array('name' => '上传公有文件', 'role' => '*'),
            'site/download-public-file' => array('name' => '下载文件', 'role' => '*'),
            'site/stat' => array('name' => '系统统计', 'role' => 'root_worker,root_manager'),
            'site/logs' => array('name' => '系统日志', 'role' => 'root_worker,root_manager'),
            'site/download-log-file' => array('name' => '下载系统日志', 'role' => 'root_worker,root_manager'),
            'site/get-online-users' => array('name' => '获取在线用户列表', 'role' => 'root_worker,root_manager'),
            'site/list' => array('name' => '站点列表', 'role' => 'root_worker,root_manager'),
            'site/detail' => array('name' => '站点详情', 'role' => 'root_worker,root_manager,admin_manager'),
            'site/form' => array('name' => '站点表单', 'role' => 'root_worker,root_manager'),
            'site/create' => array('name' => '站点添加', 'role' => 'root_worker,root_manager'),
            'site/update' => array('name' => '站点更新', 'role' => 'root_worker,root_manager,admin_manager'),
            'site/delete' => array('name' => '站点删除', 'role' => 'root_worker,root_manager'),
            'site/close' => array('name' => '站点禁用', 'role' => 'root_worker,root_manager'),
            'site/open' => array('name' => '站点开启', 'role' => 'root_worker,root_manager'),
            'site/system-info' => array('name' => '服务端系统信息', 'role' => 'root_worker,root_manager'),
    
            //---stat 部分---------------------------------------------------------//
            'stat/list' => array('name' => '统计列表', 'role' => '*'),
            'stat/user' => array('name' => '获取用户在每个任务的绩效列表', 'role' => '*'),
            'stat/user-by-day' => array('name' => '获取用户在每个任务的每天绩效列表', 'role' => '*'),
            'stat/task' => array('name' => '获取任务的绩效列表', 'role' => '*'),
            'stat/export' => array('name' => '导出报表', 'role' => '*'),
            'stat/team' => array('name' => '团队每日绩效', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'stat/team-by-day' => array('name' => '团队每日绩效', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'stat/operation-export' => array('name' => '导出操作记录', 'role' => '*'),
    
            //---task 部分---------------------------------------------------------//
            */
            'task/list' => array('name' => '获取项目的任务列表', 'role' => 'manager,worker'),
            'task/detail' => array('name' => '获取项目的任务详情', 'role' => 'manager,worker'),
            'task/top' => array('name' => '置顶某任务排序', 'role' => 'manager,worker'),
            'task/tasks' => array('name' => '团队用户获取本团队的作业列表', 'role' => 'manager,worker'),
            'task/execute' => array('name' => '执行任务', 'role' => 'manager,worker'),
            'task/batch-execute' => array('name' => '批量执行任务', 'role' => 'manager,worker'),
            'task/resource' => array('name' => '任务资源', 'role' => 'manager,worker'),
            'task/resources' => array('name' => '任务资源', 'role' => 'manager,worker'),
            'task/mark' => array('name' => '生成mark图', 'role' => 'manager,worker'),
            'task/mask' => array('name' => '生成mask图有问题', 'role' => 'manager,worker'),
            /*
    
            //---team 部分---------------------------------------------------------//
    
            'team/teams' => array('name' => '团队列表获取', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'team/detail' => array('name' => '获取团队详情', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/form' => array('name' => '创建团队表单', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'team/create' => array('name' => '创建团队', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'team/update' => array('name' => '更改团队', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/restore' => array('name' => '恢复团队', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/delete' => array('name' => '删除团队', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/users' => array('name' => '获取团队成员', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/user-create' => array('name' => '团队成员添加', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/user-update' => array('name' => '团队成员编辑', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/user-delete' => array('name' => '团队成员删除', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/parse-users-excel' => array('name' => '解析excel(多用户)', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/user-import' => array('name' => '用户导入', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/user-all' => array('name' => '获取所有用户', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/group-list' => array('name' => '获取小组列表', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/create-group' => array('name' => '创建小组', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/update-group' => array('name' => '编辑小组', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/delete-group' => array('name' => '删除小组', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/delete-group-user' => array('name' => '删除小组成员', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/move-group-user' => array('name' => '删除小组成员', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/move-team-user' => array('name' => '批量移动团队成员', 'role' => 'admin_manager,root_worker,root_manager'),
            'team/multiple-moves-team-user' => array('name' => '批量移动团队成员（多个团队）', 'role' => 'admin_manager,root_worker,root_manager'),
            'team/import-parse' => array('name' => '解析导入文件', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            'team/import-submit' => array('name' => '提交导入数据', 'role' => 'manager,admin_worker,admin_manager,root_worker,root_manager'),
            
            //---template 部分---------------------------------------------------------//*/
    
            'template/list' => array('name' => '模板列表', 'role' => 'manager'),
            'template/detail' => array('name' => '模板详情', 'role' => 'manager'),
            'template/form' => array('name' => '模板表单', 'role' => 'manager'),
            'template/create' => array('name' => '新增模板', 'role' => 'manager'),
            'template/update' => array('name' => '修改模板', 'role' => 'manager'),
            'template/delete' => array('name' => '删除模板', 'role' => 'manager'),
            'template/copy' => array('name' => '复制模板', 'role' => 'manager'),
            'template/use' => array('name' => '使用模板', 'role' => 'manager'),
    
            //---user 部分---------------------------------------------------------//
            'user/index' => array('name' => '用户首页', 'role' => 'manager,worker'),
            'user/users' => array('name' => '获取全站用户列表', 'role' => 'manager,worker'),
            'user/detail' => array('name' => '用户详情', 'role' => 'manager,worker'),
            'user/stat' => array('name' => '用户实时统计', 'role' => 'manager,worker'),
            'user/auth' => array('name' => '获取用户已授权的权限', 'role' => 'manager,worker'),
            'user/form' => array('name' => '用户表单', 'role' => 'manager,worker'),
            'user/create' => array('name' => '创建用户', 'role' => 'manager'),
            'user/update' => array('name' => '更改用户信息', 'role' => 'manager,worker'),
            'user/delete' => array('name' => '删除用户', 'role' => 'manager,worker'),
            'user/update-password' => array('name' => '修改密码之验证', 'role' => 'manager,worker'),
            'user/update-password-new' => array('name' => '修改密码', 'role' => 'manager,worker'),
            'user/update-email' => array('name' => '修改邮箱之验证', 'role' => 'manager,worker'),
            'user/update-email-new' => array('name' => '修改邮箱', 'role' => 'manager,worker'),
            'user/update-phone' => array('name' => '修改手机号之验证', 'role' => 'manager,worker'),
            'user/update-phone-new' => array('name' => '修改手机号', 'role' => 'manager,worker'),
            'user/send-phone-code' => array('name' => '发送短信验证码', 'role' => 'manager,worker'),
            'user/send-email-code' => array('name' => '发送邮箱验证码', 'role' => 'manager,worker'),
            'user/import-parse' => array('name' => '解析导入文件', 'role' => 'manager'),
            'user/import-submit' => array('name' => '提交导入数据', 'role' => 'manager'),
            'user/open-ftp' => array('name' => '开通ftp功能', 'role' => 'manager'),
            'user/records' => array('name' => '用户记录列表', 'role' => 'manager'),
            'user/devices' => array('name' => '用户设备列表', 'role' => 'manager'),

            //--user_group 部分--------------------------------------------//
            'group/groups' => array('name' => '小组列表', 'role' => 'manager'),
            'group/detail' => array('name' => '小组详情', 'role' => 'manager'),
            'group/create' => array('name' => '创建小组', 'role' => 'manager'),
            'group/update' => array('name' => '更改小组信息', 'role' => 'manager'),
            'group/delete' => array('name' => '删除小组', 'role' => 'manager'),
            'group/user-create' => array('name' => '添加小组用户', 'role' => 'manager'),
            'group/user-delete' => array('name' => '添加小组用户', 'role' => 'manager'),

            //---work 部分---------------------------------------------------------//
    
            'work/list' => array('name' => '工作列表', 'role' => 'manager,worker'),
            'work/records' => array('name' => '工作列表', 'role' => 'manager,worker'),

            /*//---tag 部分---------------------------------------------------------//
            'tag/tags' => array('name' => '获取所有标签', 'role' => '*'),
            'tag/create' => array('name' => '创建标签', 'role' => '*'),
            'tag/update' => array('name' => '修改标签', 'role' => '*'),
            'tag/delete' => array('name' => '删除标签', 'role' => '*'),
            'tag/tag-users' => array('name' => '标签用户列表', 'role' => '*'),
            'tag/update-tag-user' => array('name' => '更新标签用户', 'role' => '*'),
            
            'customer/list' => array('name' => '客户列表', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/detail' => array('name' => '客户详情', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/create' => array('name' => '创建客户', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/update' => array('name' => '更新客户', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/receive' => array('name' => '领取客户', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/user-list' => array('name' => '客户人员列表', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/create-user' => array('name' => '更新客户人员', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/update-user' => array('name' => '更新客户人员', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/delete-user' => array('name' => '删除客户人员', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'customer/statistics' => array('name' => '客户统计', 'role' => 'admin_manager,root_worker,root_manager'),
            'customer/new-register' => array('name' => '新注册客户', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            
            'followup/list' => array('name' => '跟进事项列表', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'followup/create' => array('name' => '创建跟进事项', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'followup/update' => array('name' => '更新跟进事项', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'followup/detail' => array('name' => '跟进事项详情', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'followup/record-list' => array('name' => '跟进记录列表', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'followup/create-record' => array('name' => '创建跟进记录', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            */
            'pack/form' => array('name' => '文件打包脚本列表', 'role' => 'manager'),
            'pack/list' => array('name' => '文件打包列表', 'role' => 'manager'),
            'pack/build' => array('name' => '文件打包', 'role' => 'manager'),
            'pack/top' => array('name' => '打包管理置顶', 'role' => 'manager'),
            'pack/stop' => array('name' => '结束打包', 'role' => 'manager'),
            'pack/renew' => array('name' => '重新打包', 'role' => 'manager'),
            'pack/dataset-list' => array('name' => '数据集列表', 'role' => 'manager'),
            'pack/get-ftp' => array('name' => '获取数据集的ftp信息并推送到ftp', 'role' => 'manager'),
            
            'unpack/list' => array('name' => '文件解包列表', 'role' => 'manager'),
            /*
            'step/group-list' => array('name' => '流程列表', 'role' => 'root_worker,root_manager'),
            'step/group-form' => array('name' => '流程表单', 'role' => 'root_worker,root_manager'),
            'step/group-create' => array('name' => '流程创建', 'role' => 'root_worker,root_manager'),
            'step/group-update' => array('name' => '流程更新', 'role' => 'root_worker,root_manager'),
            'step/group-delete' => array('name' => '流程删除', 'role' => 'root_worker,root_manager'),
            'step/group-detail' => array('name' => '流程详情', 'role' => 'root_worker,root_manager'),
            'step/group-close' => array('name' => '流程关闭', 'role' => 'root_worker,root_manager'),
            'step/group-open' => array('name' => '流程开启', 'role' => 'root_worker,root_manager'),
            
            //---deploy 部分---------------------------------------------------------//
            'deployment/list' => array('name' => '数据部署列表', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'deployment/create' => array('name' => '数据部署', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'deployment/delete' => array('name' => '数据部署删除', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'deployment/form' => array('name' => '重新部署表单', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'deployment/update' => array('name' => '重新部署', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
            'deployment/detail' => array('name' => '数据部署详情', 'role' => 'admin_worker,admin_manager,root_worker,root_manager'),
             */
        );
    }
}