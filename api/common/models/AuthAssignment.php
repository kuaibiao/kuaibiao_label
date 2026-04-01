<?php

namespace common\models;

use Yii;

/**
 * auth_assignment
 *
 */
class AuthAssignment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_assignment';
    }
    
    /**
     * 重新导入用户权限 
     * 只限开发阶段使用,正式环境需禁用
     */
    public static function refreshAuth($reload = 0)
    {
        $records = [];
        $auth = Yii::$app->authManager;
    
        //----------------------------
    
        //清空所有tpey=2的记录
        //$auth->removeAllPermissions();
        //$auth->removeAllRoles();
        //$auth->removeAllAssignments();
    
        //----------------------------
    
        $roles = AuthItem::getInitRoles();
        if ($roles)
        {
            foreach ($roles as $k => $v)
            {
                $role = $auth->getRole($k);
                if (!$role)
                {
                    $role = $auth->createRole($k);
                    $role->description = $v['name'];
                    $auth->add($role);
                    //var_dump('add role '.$k);
                    $records[] = 'add role '.$k;
                }
    
            }
        }
    
        
        $routes = AuthItem::getInitRoutes();
        if ($routes)
        {
            foreach ($routes as $k => $v)
            {
                $permission = $auth->getPermission($k);
                if (!$permission)
                {
                    $permission = $auth->createPermission($k);
                    $permission->description = $v['name'];
                    $auth->add($permission);
                    //var_dump('add permission '.$k);
                    $records[] = 'add permission '.$k;
                }
    
                if ($reload)
                {
                    $roles = array();
                    if ($v['role'] == '*')
                    {
                        $roles = AuthItem::getInitRoles();
                        $roles = array_keys($roles);
                    }
                    elseif (strpos($v['role'], ','))
                    {
                        $roles = explode(',', $v['role']);
                    }
                    elseif ($v['role'])
                    {
                        $roles = [$v['role']];
                    }
                    
                    if ($roles)
                    {
                        foreach ($roles as $r)
                        {
                            $role = $auth->getRole($r);
                            if (!$auth->hasChild($role, $permission))
                            {
                                $auth->addChild($role, $permission);
                                //var_dump('assign permission to role:'.$role->name.';'.$permission->name);
                                $records[] = 'assign permission to role:'.$role->name.';'.$permission->name;
                            }
                    
                        }
                    }
                    
                    
                }
                
    
                //$auth = Yii::$app->authManager;
    
                //$permission = $auth->getPermission($k);
                //$permission->data = ['menu' => $v['menu']];
                //$auth->update($k, $permission);
    
                //去除删除的角色
                //暂无
    
            }
        }
    
    
        $auth->invalidateCache();
        //var_dump('refresh cache');
        $records[] = 'refresh cache';
        
        return $records;
    }
}