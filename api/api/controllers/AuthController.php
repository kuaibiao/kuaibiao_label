<?php
namespace api\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use common\components\AccessTokenAuth;
use common\models\AuthItem;
use common\models\User;
use common\helpers\FormatHelper;

/**
 * 权限控制器
 */
class AuthController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            //程序监控过滤器,记录每次请求的时间和内存
            'monitor' => [
                'class' => 'common\components\ActionMonitorFilter',
            ],
            //请求方式过滤器,检查用户是否是post提交
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    '*' => ['POST'],
                ],
            ],
            //accesstoken身份验证
            'authenticator' => [
                'class' => AccessTokenAuth::className(),
            ],
            //用户行为过滤器
            'userfilter' => [
                'class' => 'common\components\ActionUserFilter',
            ],
            //rbac过滤器,判断是否有执行的权限
            /*'rbac' => [
                'class' => 'common\components\ActionRbacFilter',
            ],*/
        ];
    }
    
    /**
     * 角色列表获取
     * @param   $page 页码数
     * @param   $limit 每页数量
     * @param   $keyword 关键车
     * @return  list [<description>]
     */
    public function actionRoles()
    {
        //客户端处理
        $client     = Yii::$app->request->post();
        $page   = empty($client['page']) || $client['page'] < 1 ? 1 : (int)$client['page'];
        $limit  = empty($client['limit']) || $client['limit'] < 1 ? 20 : (int)$client['limit'];
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);

        $roleName = isset($client['role_name'])?$client['role_name']:null;
        if(!empty($roleName))
        {
            if(in_array($roleName, array_keys(AuthItem::getAdminRoles())))
            {
                $roleName = array_keys(AuthItem::getAdminRoles());
            }
            elseif(in_array($roleName, array_keys(AuthItem::getCrowdsourcingRoles())))
            {
                $roleName = array_keys(AuthItem::getCrowdsourcingRoles());
            }
            elseif(in_array($roleName, array_keys(AuthItem::getTeamRoles())))
            {
                $roleName = array_keys(AuthItem::getTeamRoles());
            }
            elseif(in_array($roleName, array_keys(AuthItem::getCustomerRoles())))
            {
                $roleName = array_keys(AuthItem::getCustomerRoles());
            }
            else
            {
                $roleName = null;
            }
        }

        //排序
        if (!in_array($orderby, [ 'name', 'created_at']))
        {
            $orderby = 'name';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }

        //查询
        $query   = AuthItem::find()->where(['type' => 1]);
        if(!empty($client['keyword'])){
            $client['keyword'] = trim($client['keyword']);
            $query->andWhere(['or',
                ['like', 'description', $client['keyword']],
                ['like', 'name', $client['keyword']]
            ]);
        }
        if($roleName)
        {
            $query->andWhere(['in', 'name', $roleName]);
        }
        $count  = $query->count();
        $list   = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
            ->all();

        return $this->asJson(FormatHelper::resultStrongType([
            'list'  => $list,
            'count' => $count
        ]));
    }

    /**
     * 角色中用户列表获取
     * @param   $name 角色名称
     * @param   $page 页码数
     * @param   $limit 每页条目数
     * @return  list + count
     */
    public function actionRoleUsers(){
        //客户端处理
        $client     = Yii::$app->request->post();
        $_log['$client']    = $client;
        if(empty($client['name'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'name_not_given', Yii::t('app', 'name_not_given')));
        }
        $keyword = isset($client['keyword'])?$client['keyword']:null;

        $page   = empty($client['page']) || $client['page'] < 1 ? 1 : (int)$client['page'];
        $limit  = empty($client['limit']) || $client['limit'] < 1 ? 10 : (int)$client['limit'];

        $role   = AuthItem::findOne([
            'name'  => $client['name'],
            'type'  => 1
        ]);
        if($role === null){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'auth_role_not_found',
                Yii::t('app', 'auth_role_not_found')
            ));
        }

        $query  = $role->getUsers();

        if($keyword)
        {
            $query->andwhere(['or',
                ['like', 'id', $keyword],
                ['like', 'email', $keyword],
                ['like', 'nickname', $keyword]
                ]);
        }
        $count  = $query->count();
        $list   = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->asArray()
            ->all();

        return $this->asJson(FormatHelper::resultStrongType([
            'list'  => $list,
            'count' => $count
        ]));
    }

    /**
     * 角色详情获取
     * @param   $name 角色名称
     * @return  info
     */
    public function actionRoleDetail(){
        //客户端处理
        $client     = Yii::$app->request->post();
        $_log['$client']    = $client;
        if(empty($client['name'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', ' name_not_given', Yii::t('app', 'name_not_given')));
        }

        $auth   = Yii::$app->authManager;
        $role   = $auth->getRole($client['name']);
        if($role === null){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'auth_role_not_found',
                Yii::t('app', 'auth_role_not_found')
            ));
        }
        $permissions    = [];
        foreach($auth->getPermissionsByRole($role->name) as $permission){
            $permissions[]  = $permission->name;
        }
        return $this->asJson(FormatHelper::resultStrongType([
            'info'  => $role,
            'permissions'   => $permissions
        ]));
    }
    
    /**
     * 创建角色
     * @param   $name 角色名称
     * @param   $description [<description>]
     * @param   $permissions 权限列表
     * @return  role
     */
    public function actionRoleCreate()
    {   
        $request    = \Yii::$app->request;
        $client     = $request->get() + $request->post();
        $_log['$client']    = $client;

        //客户端参数检测
        if(!isset($client['name']) || $client['name'] == ''){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' param not found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', ' name_not_given', Yii::t('app', 'name_not_given')));
        }
        if(!isset($client['description']) || $client['description'] == ''){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' param not found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'auth_description_not_given',
                Yii::t('app', 'auth_description_not_given')
            ));
        }

        $auth   = \Yii::$app->authManager;
        $trans  = Yii::$app->db->beginTransaction();
        try{
            //创建角色
            $name   = $client['name'];
            $role   = $auth->createRole($name);
            $role->description  = $client['description'];
            $auth->add($role);

            //权限设置
            if(!empty($client['permissions'])){
                $permissions    = explode(',', str_replace(' ', '', $client['permissions']));
                $auth->addRolePermissions($role, $permissions);
            }
            
            $trans->commit();
        }catch(\Exception $e){
            $trans->rollback();
            $_log['$e.message'] = $e->getMessage();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_create_fail '.json_encode($_log));
            if($e->getCode() == 23000){
                return $this->asJson(FormatHelper::resultStrongType('',
                    'auth_role_create_fail',
                    Yii::t('app', 'auth_role_create_fail')
                ));
            } 
            
            return $this->asJson(FormatHelper::resultStrongType('',
                'auth_roleCreate_transError',
                $e->getMessage()
            ));
        }
        
        //清除权限cache
        Yii::$app->authManager->invalidateCache();
            
        return $this->asJson(FormatHelper::resultStrongType([
            'info'  => $role
        ]));
    }
    
    /**
     * 更改角色信息
     * @param   $name 角色名称
     * @param   $info 更新信息系
     * @param   $permissions [<description>]
     */

    public function actionRoleUpdate()
    {
        //客户端信息获取
        $request    = \Yii::$app->request;
        $client     = $request->get() + $request->post();
        $_log['$client'] = $client;

        //客户端参数检测
        if(empty($client['name'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', ' name_not_given', Yii::t('app', 'name_not_given')));
        }
        if(empty($client['info']) || !is_array($client['info'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_info_param_invalid '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                ' auth_info_param_invalid',
                Yii::t('app', 'auth_info_param_invalid')
            ));
        }

        $auth   = Yii::$app->authManager;
        $name   = $client['name'];
        $role   = $auth->getRole($name);
        if(!$role){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'auth_role_not_found',
                Yii::t('app', 'auth_role_not_found')
            ));
        }

        $trans  = Yii::$app->db->beginTransaction();
        try {
            //更改角色信息
            $info   = $client['info'];
            if(isset($info['name'])){
                $role->name     = $info['name'];
            }
            if(isset($info['description'])){
                $role->description  = $info['description'];
            }
            $auth->update($name, $role);

            //角色权限更改
            $permissions    = explode(',', $client['permissions']);
            $auth->removeChildren($role);
            $auth->addRolePermissions($role, $permissions);

            $trans->commit();
        }catch(\Exception $e){
            $trans->rollback();

            $_log['$e.message'] = $e->getMessage();
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' exception '.json_encode($_log));

            //主键重复
            if($e->getCode() == 23000){
                return $this->asJson(FormatHelper::resultStrongType('',
                    'auth_update_role_fail',
                    Yii::t('app', 'auth_update_role_fail')
                ));
            }
            
            return $this->asJson(FormatHelper::resultStrongType('',
                'exception',
                $e->getMessage()
            ));
        }
        
        //清除权限cache
        Yii::$app->authManager->invalidateCache();

        return $this->asJson(FormatHelper::resultStrongType([
            'info'    => $role
        ]));
    }
    
    /**
     * 删除角色
     * @param   $name 角色名称
     * @return  boolean [<description>]
     */
    public function actionRoleDelete()
    {
        //客户端信息获取
        $client     = Yii::$app->request->post();
        $_log['$client']    = $client;
        if(empty($client['name'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'name_not_given', Yii::t('app', 'name_not_given')));
        }

        $auth   = Yii::$app->authManager;
        $role   = $auth->getRole($client['name']);
        if(!$role){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_not_found', Yii::t('app', 'auth_role_not_found')));
        }
        $result = $auth->remove($role) ? true : false;
        
        //清除权限cache
        Yii::$app->authManager->invalidateCache();

        return $this->asJson(FormatHelper::resultStrongType([
            'result'    => $result
        ]));
        
    }
    
    /**
     * 权限列表
     * @param   $page 页码数
     * @param   $limit 每页条目数
     * @return  list count
     */
    public function actionPermissions()
    {   
        $client = Yii::$app->request->post();
        $_log['$client']    = $client;
        $page   = empty($client['page']) || $client['page'] < 1 ? 1 : (int)$client['page'];
        $limit  = empty($client['limit']) || $client['limit'] < 1 ? 10 : (int)$client['limit'];
        $orderby = Yii::$app->request->post('orderby', null);
        $sort = Yii::$app->request->post('sort', null);
        

        //排序
        if (!in_array($orderby, [ 'name', 'created_at']))
        {
            $orderby = 'name';
        }
        if (!in_array($sort, ['asc', 'desc']))
        {
            $sort = 'desc';
        }


        $query  = AuthItem::find()->where([
            'type'  => 2
        ]);
        //关键词查找
        if(!empty($client['keyword'])){
            $client['keyword'] = trim($client['keyword']);
            $query->andWhere(['or',
                ['like', 'description', $client['keyword']],
                ['like', 'name', $client['keyword']]
                ]);
        }

        $count  = $query->count();
        $list   = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->orderBy([$orderby => $sort == 'asc' ? SORT_ASC : SORT_DESC])
            ->all();

        
        return $this->asJson(FormatHelper::result([
            'list'  => $list,
            'count' => $count,
            'roles' => AuthItem::getRoles()
        ]));
    }

    //权限列表获取(分组形式)
    public function actionPermissionsToGroup(){
        $auth   = Yii::$app->authManager;
        $list   = $auth->getPermissions();

        return $this->asJson(FormatHelper::result([
            'list'  => AuthItem::toGroup($list)
        ]));
    }
    
    /**
     * 添加权限
     * @param   $name 名称
     * @param   $description 描述
     * @return  permission [<description>]
     */
    public function actionPermissionCreate()
    {
        //客户端信息获取
        $client = Yii::$app->request->post();
        $_log['$client'] = $client;
        
        //参数检测
        if(empty($client['name'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','name_not_given',Yii::t('app', 'name_not_given')));
        }
        if(empty($client['description'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_description_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_description_not_given', Yii::t('app', 'auth_description_not_given')));
        }

        $auth = Yii::$app->authManager;
        if ($auth->getPermission($client['name']))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_permission_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_permission_not_found', Yii::t('app', 'auth_permission_not_found')));
        }
        
        $permission = $auth->createPermission($client['name']);
        $permission->description = $client['description'];
        $auth->add($permission);
        
        //清除权限cache
        Yii::$app->authManager->invalidateCache();

        return $this->asJson(FormatHelper::resultStrongType([
            'info'   => $permission
        ]));
    }
    
    /**
     * 修改权限
     * @param   $name 权限名称
     * @param   $info name|description 新信息
     * @return  permission
     */
    public function actionPermissionUpdate()
    {
        //客户端信息获取
        $client = Yii::$app->request->post();
        $_log['$client'] = $client;
        
        if(empty($client['name'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','name_not_given',Yii::t('app', 'name_not_given')));
        }
        $name = $client['name'];
        
        if(empty($client['info']) || !is_array($client['info'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_info_param_invalid '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','auth_info_param_invalid',Yii::t('app', 'auth_info_param_invalid')));
        }
        $info = $client['info'];
        
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission($name);
        if(!$permission){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_permission_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('','auth_permission_not_found',Yii::t('app', 'auth_permission_not_found')));
        }

        //更改权限信息
        if(isset($info['name'])){
            $permission->name = $info['name'];
        }
        if(isset($info['description'])){
            $permission->description = $info['description'];
        }

        $result = $auth->update($name, $permission);
        
        //清除权限cache
        Yii::$app->authManager->invalidateCache();

        return $this->asJson(FormatHelper::resultStrongType([
            'info'  => $permission
        ]));

    }
    
    /**
     * 删除权限
     * @param   $name 权限名称
     * @return  boolean
     */
    public function actionPermissionDelete()
    {
        //客户端信息获取
        $client     = Yii::$app->request->post();
        $_log['$client']    = $client;
        if(empty($client['name'])){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' name_not_given '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('', 'name_not_given', Yii::t('app', 'name_not_given')));
        }

        $auth   = \Yii::$app->authManager;
        $name   = $client['name'];
        $permission    = $auth->getPermission($name);
        if(!$permission){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_permission_not_found '.json_encode($_log));
            return $this->asJson(FormatHelper::resultStrongType('',
                'auth_permission_not_found',
                Yii::t('app', 'auth_permission_not_found')
            ));
        }

        $result     = $auth->remove($permission) ? true : false;
        
        //清除权限cache
        Yii::$app->authManager->invalidateCache();

        return $this->asJson(FormatHelper::resultStrongType([
            'result'    => $result
        ]));
    }

    /**
     * 添加角色成员
     * @param string $userIds  用户id
     * @param string $roleName  角色名称
     * @return \yii\web\Response
     */
    public function actionUserCreate()
    {
        $_logs = [];

        $userIds = Yii::$app->request->post('user_ids', null);
        $roleName = Yii::$app->request->post('role_name', null);

        $_logs['$userIds'] = $userIds;
        $_logs['$roleName'] = $roleName;
        if(empty($userIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_ids_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_ids_not_given', Yii::t('app', 'user_ids_not_given')));
        }
        if(empty($roleName))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_name_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_name_not_given', Yii::t('app', 'auth_role_name_not_given')));
        }

        if (!empty($userIds) && is_string($userIds))
        {
            $userIds = trim($userIds, ',');
            if (strpos($userIds, ','))
            {
                $userIds = explode(',', $userIds);
            }
            else
            {
                $userIds = [$userIds];
            }
        }
        elseif (!empty($userIds) && is_array($userIds))
        {
            $userIds = (array)$userIds;
        }
        else
        {
            $userIds = [];
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notset assist user '.json_encode($_logs));
        }

        $auth   = Yii::$app->authManager;
        $role   = $auth->getRole($roleName);
        if(!$role){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_not_found', Yii::t('app', 'auth_role_not_found')));
        }
        $oldUserIds = $auth->getUserIdsByRole($roleName);
        $addUserIds = array_diff($userIds, $oldUserIds);

        $errorIds = [];
        if($addUserIds)
        {
            foreach($addUserIds as $key => $id)
            {
                $oldRoles = $auth->getRolesByUser($id);
                $userType = User::find()->select(['type'])->where(['id' => $id])->asArray()->limit(1)->one();
                if($oldRoles && $userType)
                {
                    $type = $userType['type'];
                    $roleKeys = array_keys(AuthItem::getRolesByType($type));

                    $isError = true;
                    foreach($oldRoles as $key => $val)
                    {
                        $name = $val->name;
                        if(in_array($roleName, $roleKeys) && in_array($name, $roleKeys))
                        {
                            $isError = false;
                        }
                    }

                    if($isError)
                    {
                        $errorIds[] = $id;
                    }
                }
                else
                {
                    $errorIds[] = $id;
                }
            }

            if($errorIds)
            {
                $_logs['$errorIds'] = $errorIds;
                Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_user_ids_not_allowed_to_role '.json_encode($_logs));
                return $this->asJson(FormatHelper::resultStrongType('', 'auth_user_ids_not_allowed_to_role', Yii::t('app', 'auth_user_ids_not_allowed_to_role')));
            }

            if($addUserIds)
            {
                foreach($addUserIds as $key => $id)
                {
                    $auth->removeUserAssignments($id);
                }

                $auth->addAssignmentUsers($roleName, $addUserIds);
            }

        }


        return $this->asJson(FormatHelper::resultStrongType([
            'addUser'    => $addUserIds,
            'errorUser'    => $errorIds
        ]));
    }

    /**
     * 删除角色成员
     * @param string $userIds  用户id
     * @param string $roleName  角色名称
     * @return \yii\web\Response
     */
    public function actionUserDelete()
    {
        $_logs = [];

        $userIds = Yii::$app->request->post('user_ids', null);
        $roleName = Yii::$app->request->post('role_name', null);

        $_logs['$userIds'] = $userIds;
        $_logs['$roleName'] = $roleName;
        if(empty($userIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_ids_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_ids_not_given', Yii::t('app', 'user_ids_not_given')));
        }
        if(empty($roleName))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_name_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_name_not_given', Yii::t('app', 'auth_role_name_not_given')));
        }

        if (!empty($userIds) && is_string($userIds))
        {
            $userIds = trim($userIds, ',');
            if (strpos($userIds, ','))
            {
                $userIds = explode(',', $userIds);
            }
            else
            {
                $userIds = [$userIds];
            }
        }
        elseif (!empty($userIds) && is_array($userIds))
        {
            $userIds = (array)$userIds;
        }
        else
        {
            $userIds = [];
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notset assist user '.json_encode($_logs));
        }

        $auth   = Yii::$app->authManager;
        $role   = $auth->getRole($roleName);
        if(!$role){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_not_found', Yii::t('app', 'auth_role_not_found')));
        }
        $oldUserIds = $auth->getUserIdsByRole($roleName);
        $delUserIds = array_intersect($userIds, $oldUserIds);

        if($delUserIds)
        {
            $auth->removeAssignmentUsers($roleName, $delUserIds);
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'delUser'    => $delUserIds
        ]));
    }


    /**
     * 移动角色成员
     * @param string $userIds  用户id
     * @param string $roleName  目标角色名称
     * @param string $oldRoleName  角色名称
     * @return \yii\web\Response
     */
    public function actionMoveUser()
    {
        $_logs = [];

        $userIds = Yii::$app->request->post('user_ids', null);
        $roleName = Yii::$app->request->post('role_name', null);
        $oldRoleName = Yii::$app->request->post('old_role_name', null);

        $_logs['$userIds'] = $userIds;
        $_logs['$roleName'] = $roleName;
        $_logs['$oldRoleName'] = $oldRoleName;
        if(empty($userIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' user_ids_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'user_ids_not_given', Yii::t('app', 'user_ids_not_given')));
        }
        if(empty($roleName))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_name_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_name_not_given', Yii::t('app', 'auth_role_name_not_given')));
        }
        if(empty($oldRoleName))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_old_role_name_not_given '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_old_role_name_not_given', Yii::t('app', 'auth_old_role_name_not_given')));
        }


        if(in_array($roleName, array_keys(AuthItem::getAdminRoles())) && in_array($oldRoleName, array_keys(AuthItem::getAdminRoles())))
        {

        }
        elseif(in_array($roleName, array_keys(AuthItem::getCrowdsourcingRoles())) && in_array($oldRoleName, array_keys(AuthItem::getCrowdsourcingRoles())))
        {

        }
        elseif(in_array($roleName, array_keys(AuthItem::getTeamRoles())) && in_array($oldRoleName, array_keys(AuthItem::getTeamRoles())))
        {

        }
        elseif(in_array($roleName, array_keys(AuthItem::getCustomerRoles())) && in_array($oldRoleName, array_keys(AuthItem::getCustomerRoles())))
        {

        }
        else
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_invalid '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_invalid', Yii::t('app', 'auth_role_invalid')));
        }

        if (!empty($userIds) && is_string($userIds))
        {
            $userIds = trim($userIds, ',');
            if (strpos($userIds, ','))
            {
                $userIds = explode(',', $userIds);
            }
            else
            {
                $userIds = [$userIds];
            }
        }
        elseif (!empty($userIds) && is_array($userIds))
        {
            $userIds = (array)$userIds;
        }
        else
        {
            $userIds = [];
            Yii::info(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' notset assist user '.json_encode($_logs));
        }

        $auth   = Yii::$app->authManager;
        $oldRole   = $auth->getRole($oldRoleName);
        if(!$oldRole){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_old_role_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_old_role_not_found', Yii::t('app', 'auth_old_role_not_found')));
        }

        $oldUserIds = $auth->getUserIdsByRole($oldRoleName);
        $userIds = array_intersect($userIds, $oldUserIds);

        if(empty($userIds))
        {
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_user_not_match_old_role '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_user_not_match_old_role', Yii::t('app', 'auth_user_not_match_old_role')));
        }

        $role   = $auth->getRole($roleName);
        if(!$role){
            Yii::error(__CLASS__.' '.__FUNCTION__.' '.__LINE__.' auth_role_not_found '.json_encode($_logs));
            return $this->asJson(FormatHelper::resultStrongType('', 'auth_role_not_found', Yii::t('app', 'auth_role_not_found')));
        }
        $roleUserIds = $auth->getUserIdsByRole($roleName);
        $MoveUserIds = array_diff($userIds, $roleUserIds);

        if($MoveUserIds)
        {
            $auth->addAssignmentUsers($roleName, $MoveUserIds);
            $auth->removeAssignmentUsers($oldRoleName, $MoveUserIds);
        }

        return $this->asJson(FormatHelper::resultStrongType([
            'moveUser'    => $MoveUserIds
        ]));
    }
}