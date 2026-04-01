import Main from '@/views/Main.vue';

// 不作为Main组件的子页面展示的页面单独写，如下
export const loginRouter = {
    path: '/login',
    name: 'login',
    meta: {
        title: '欢迎登录'
    },
    component: () =>
        import('@/views/login.vue')
};

export const Thirdpartylogin = {
    path: '/thirdpartylogin',
    name: 'thirdpartylogin',
    meta: {
        title: '欢迎登录'
    },
    component: () =>
        import('@/views/thirdpartylogin.vue')
};

export const registerRouter = {
    path: '/register',
    name: 'register',
    meta: {
        title: '欢迎注册'
    },
    component: () =>
        import('@/views/register.vue')
};

export const forgetPasswordRouter = {
    path: '/forget-password',
    name: 'forget-password',
    meta: {
        title: '找回密码'
    },
    component: () =>
        import('@/views/forget-password.vue')
};

export const page404 = {
    path: '/*',
    name: 'error-404',
    meta: {
        title: {i18n: 'error_no_exit'}
    },
    component: () =>
        import('@/views/error-page/404.vue')
};

export const page403 = {
    path: '/403',
    meta: {
        title: {i18n: 'error_403'}
    },
    name: 'error-403',
    component: () =>
        import('@//views/error-page/403.vue')
};

export const page500 = {
    path: '/500',
    meta: {
        title: {i18n: 'error_server_error'}
    },
    name: 'error-500',
    component: () =>
        import('@/views/error-page/500.vue')
};

export const locking = {
    path: '/locking',
    name: 'locking',
    component: () =>
        import('@/views/main-components/lockscreen/components/locking-page.vue')
};

// 作为Main组件的子页面展示但是不在左侧菜单显示的路由写在otherRouter里
export const otherRouter = {
    path: '/',
    name: 'otherRouter',
    redirect: '/home',
    component: Main,
    children: [
        {
            path: 'home',
            title: { i18n: 'router_home' },
            meta: {
                title: { i18n: 'router_home' }
            },
            name: 'home_index',
            component: () =>
                import('@/views/home/home.vue')
        },
        {
            path: 'ownspace',
            title: { i18n: 'router_ownspace' },
            meta: {
                title: { i18n: 'router_ownspace' }
            },
            name: 'ownspace_index',
            component: () =>
                import('@/views/own-space/own-space.vue')
        },
        {
            path: 'message',
            title: { i18n: 'router_mc' },
            name: 'message_index',
            meta: {
                title: { i18n: 'router_mc' },
            },
            component: () =>
                import('@/views/message/message.vue')
        },
        {
            path: 'notice',
            title: {i18n: 'router_latest_notice'},
            meta: {
                title: {i18n: 'router_latest_notice'},
            },
            name: 'notice_index',
            component: () =>
                import('@/views/message/notice.vue')
        },
        {
            path: 'project/create/:id',
            title: {i18n: 'router_setting_project'},
            meta: {
                title: {i18n: 'router_setting_project'},
                parent: 'project-management',
                father: {
                    name: 'project-management',
                    path: '/project'
                }
            },
            name: 'project-create',
            component: () =>
                import('@/views/project-management/project-create.vue')
        },
        {
            path: 'project/configuration/:id',
            title: {i18n: 'router_setting_project'},
            meta: {
                title: {i18n: 'router_setting_project'},
                parent: 'project-management',
                father: {
                    name: 'project-management',
                    path: '/project'
                }
            },
            name: 'project-configuration',
            component: () =>
                import('@/views/project-management/configuration.vue')
        },
        {
            path: 'project/detail/:id/:tab',
            title: {i18n: 'router_project_detail'},
            meta: {
                title: {i18n: 'router_project_detail'},
                parent: 'project-management',
                father: {
                    name: 'project-management',
                    path: '/project'
                }
            },
            name: 'project-detail',
            component: () =>
                import('@/views/project-management/project-detail.vue')
        },
        {
            path: 'project/:projectId/task/:id/worklist/:index',
            title: {i18n: 'admin_list_job'},
            meta: {
                title: {i18n: 'admin_list_job'},
                parent: 'project-management',
                father: {
                    name: 'project-management',
                    path: '/project'
                }
            },
            name: 'qc-work-list',
            component: () =>
                    import('@/views/project-management/components/work-list.vue')
        },
        {
            path: 'project/task',
            title: { i18n: 'router_task_view' },
            meta: {
                title: { i18n: 'router_task_view' },
                // parent: 'my-task',
                // parentPath: '/my-task/list'
            },
            name: 'perform-task',
            component: () =>
                    import('@/views/task-perform/index.vue')
        },
        {
            path: 'task/batch-audit',
            title: { i18n: 'router_batch_review' },
            meta: {
                title: { i18n: 'router_batch_review' },
                parent: 'my-task',
                parentPath: '/my-task/list'
            },
            name: 'perform-batch-audit',
            component: () =>
                    import('@/views/task-perform/batch-audit/text-analysis.vue')
        },
        {
            path: 'my-task/detail/:id/:tab/:index',
            title: { i18n: 'router_task_details' },
            name: 'my-task-detail',
            meta: {
                title: { i18n: 'router_task_details' },
                parent: 'my-task',
                parent2: 'my-performance-list',
                parentPath: '/my-task/list'
            },
            component: () =>
                    import('@/views/my-task/task-management.vue')
        },
        {
            path: 'user/detail/:id/:tab',
            title: { i18n: 'router_employee_details' },
            name: 'user-detail',
            meta: {
                title: { i18n: 'router_employee_details' },
                parent: 'user-list',
                parentPath: '/user/list'
            },
            component: () =>
                    import('@/views/user/user-detail.vue')
        },
        {

            path: 'user/import',
            title: {i18n: 'router_import_user'},
            name: 'import-user',
            meta: {
                title: {i18n: 'router_import_user'},
                parent: 'user-list',
                parentPath: '/user/list'
            },
            component: () => import('@/views/user/import-user.vue')
        },
        {
            path: 'user/group/list',
            title: { i18n: 'router_ugm' },
            name: 'group-management',
            meta: {
                title: { i18n: 'router_ugm' },
                parent: 'user-list',
                parentPath: '/user/list'
            },
            component: () =>
                    import('@/views/user/group-management.vue')
        },
        {
            path: 'user/group/:id/users',
            title: { i18n: 'router_tmm' },
            name: 'group-detail',
            meta: {
                title: { i18n: 'router_tmm' },
                parent: 'user-list',
                parentPath: '/user/list'
            },
            component: () =>
                    import('@/views/user/group-detail.vue')
        },
        {
            path: 'template/edit/:id/:categoryId',
            title: {i18n: 'router_edit_template'},
            meta: {
                title: {i18n: 'router_edit_template'},
                parent: 'template-management',
                father: {
                    name: 'template-management',
                    path: '/template'
                }
            },
            name: 'template-edit',
            component: () =>
                import('@/views/template-management/template-edit.vue')
        },
        {
            path: 'system/setting-setting',
            title: {i18n: 'router_system_settings'},
            name: 'system-setting',
            meta: {
                title: {i18n: 'router_system_settings'},
            },
            component: () => import('@/views/system-management/system-setting.vue')
        },
        {
            path: 'system/role-list',
            title: {i18n: 'router_system_role_list'},
            name: 'role-list',
            meta: {
                title: {i18n: 'router_system_role_list'},
            },
            component: () => import('@/views/system-management/role-list.vue')
        },
        {
            path: 'system/role-add',
            title: {i18n: 'router_system_role_add'},
            name: 'role-add',
            meta: {
                title: {i18n: 'router_system_role_add'},
            },
            component: () => import('@/views/system-management/role-add.vue')
        },
        {
            path: 'system/role-edit',
            title: {i18n: 'router_system_role_edit'},
            name: 'role-edit',
            meta: {
                title: {i18n: 'router_system_role_edit'},
            },
            component: () => import('@/views/system-management/role-edit.vue')
        },
        {
            path: 'system/permission-list',
            title: {i18n: 'router_system_permission_list'},
            name: 'permission-list',
            meta: {
                title: {i18n: 'router_system_permission_list'},
            },
            component: () => import('@/views/system-management/permission-list.vue')
        },
        {
            path: 'site/add',
            title: {i18n: 'router_site_add'},
            name: 'site-add',
            meta: {
                title: {i18n: 'router_site_add'},
            },
            component: () => import('@/views/site-management/site-add.vue')
        },
        {
            path: 'site/edit',
            title: {i18n: 'router_site_edit'},
            name: 'site-edit',
            meta: {
                title: {i18n: 'router_site_edit'},
            },
            component: () => import('@/views/site-management/site-edit.vue')
        },
    ]
};

// 作为Main组件的子页面展示并且在左侧菜单显示的路由写在appRouter里
export const appRouter = [
    {
        path: '/site',
        name: 'site',
        title: {i18n: 'router_site_management'},
        meta: {
            title: {i18n: 'router_site_management'},
        },
        permissions: {
            'and': [],
            'or': []
        },
        component: Main,
        children: [
            {
                path: 'list',
                icon: 'ios-book',
                name: 'site-management',
                title: {i18n: 'router_site_management'},
                meta: {
                    title: {i18n: 'router_site_management'},
                },
                permissions: {
                    'and': [],
                    'or': []
                },
                component: () =>
                    import('@/views/site-management/site-management.vue')
            }
        ]
    },
    {
        path: '/system',
        name: 'system',
        title: {i18n: 'router_system_management'},
        meta: {
            title: {i18n: 'router_system_management'},
        },
        permissions: {
            'and': [],
            'or': []
        },
        component: Main,
        children: [
            {
                path: 'list',
                icon: 'ios-book',
                name: 'system-management',
                title: {i18n: 'router_system_management'},
                meta: {
                    title: {i18n: 'router_system_management'},
                },
                permissions: {
                    'and': [],
                    'or': []
                },
                component: () =>
                    import('@/views/system-management/system-management.vue')
            }
        ]
    },
    {
        path: '/project',
        name: 'project',
        title: {i18n: 'router_project_management'},
        meta: {
            title: {i18n: 'router_project_management'},
        },
        permissions: {
            'and': [],
            'or': []
        },
        component: Main,
        children: [
            {
                path: 'list',
                icon: 'ios-book',
                name: 'project-management',
                title: {i18n: 'router_project_management'},
                meta: {
                    title: {i18n: 'router_project_management'},
                },
                permissions: {
                    'and': [],
                    'or': []
                },
                component: () =>
                    import('@/views/project-management/project-management.vue')
            }
        ]
    },
    {
        path: '/template',
        name: 'template',
        title: {i18n: 'router_template_management'},
        meta: {
            title: {i18n: 'router_template_management'},
        },
        permissions: {
            'and': [],
            'or': []
        },
        // access: '0',
        component: Main,
        children: [
            {
                path: 'list',
                icon: 'ios-keypad',
                title: {i18n: 'router_template_management'},
                name: 'template-management',
                meta: {
                    title: {i18n: 'router_template_management'},
                },
                permissions: {
                    'and': [],
                    'or': []
                },
                component: () => import('@/views/template-management/template-list.vue')
            }
        ]
    },
    {
        path: '/user',
        icon: 'ios-people',
        title: { i18n: 'router_user_management' },
        name: 'user',
        permissions: {
            'and': [],
            'or': []
        },
        component: Main,
        children: [
            {
                path: 'list',
                title: { i18n: 'router_user_management' },
                name: 'user-list',
                permissions: {
                    'and': [],
                    'or': []
                },
                meta: {
                    title: { i18n: 'router_user_management' },
                },
                component: () => import('@/views/user/user-list.vue')
            },

        ]
    },
    {
        path: '/my-task',
        icon: 'logo-buffer',
        meta: {
            title: { i18n: 'router_my_task' }
        },
        title: { i18n: 'router_my_task' },
        name: 'task',
        permissions: {
            'and': [],
            'or': []
        },
        component: Main,
        children: [
            {
                path: 'list',
                title: { i18n: 'router_my_task' },
                meta: {
                    title: { i18n: 'router_my_task' }
                },
                permissions: {
                    'and': [],
                    'or': []
                },
                name: 'my-task',
                component: () => import('@/views/my-task/my-task.vue')
            }
        ]
    },
    {
        path: '/my-performance',
        icon: 'ios-podium',
        title: { i18n: 'router_my_performance' },
        name: 'my-performance',
        permissions: {
            'and': [],
            'or': []
        },
        component: Main,
        children: [
            {
                path: 'list',
                title: { i18n: 'router_my_performance' },
                name: 'my-performance-list',
                meta: {
                    title: { i18n: 'router_my_performance' }
                },
                permissions: {
                    'and': [],
                    'or': []
                },
                component: () => import('@/views/performance/my-performance.vue')
            }
        ]
    },
    // {
    //     path: '/task',
    //     icon: 'ios-albums',
    //     title: { i18n: 'router_team_task' },
    //     name: 'management',
    //     // access: 0,
    //     permissions: {
    //         'and': [],
    //         'or': ['task/list']
    //     },
    //     component: Main,
    //     children: [
    //         {
    //             path: 'management',
    //             title: { i18n: 'router_team_task' },
    //             name: 'task-management',
    //             // access: 0,
    //             permissions: {
    //                 'and': [],
    //                 'or': ['task/list']
    //             },
    //             meta: {
    //                 title: { i18n: 'router_team_task' },
    //             },
    //             component: () => import('@/views/task-management/management.vue') }
    //     ]
    // },
    // {
    //     path: '/performance',
    //     icon: 'ios-podium',
    //     title: { i18n: 'router_team_performance' },
    //     name: 'team-performance',
    //     permissions: {
    //         'and': [],
    //         'or': ['task/list']
    //     },
    //     component: Main,
    //     children: [
    //         { path: 'list',
    //             title: { i18n: 'router_team_performance' },
    //             name: 'performance-list',
    //             // access: 0,
    //             permissions: {
    //                 'and': [],
    //                 'or': ['task/list']
    //             },
    //             meta: {
    //                 title: { i18n: 'router_team_performance' },
    //             },
    //             component: () => import('@/views/team/performance.vue')
    //         },

    //     ]
    // },

];

// 所有上面定义的路由都要写在下面的routers里
export const routers = [
    loginRouter,
    Thirdpartylogin,
    registerRouter,
    forgetPasswordRouter,
    // loginhuicui,
    // loginbeisai,
    // quicklogin,
    // activate,
    otherRouter,
    locking,
    ...appRouter,
    page500,
    page403,
    page404
];
