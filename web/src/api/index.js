import {
    ico,
    apiBase,
    staticBase,
    helpBase,
} from './init';

console.log(apiBase,staticBase,helpBase)

export default {
    staticBase,
    helpBase,
    apiBase,

    site: {
        register: apiBase + '/site/register',
        captcha: apiBase + '/site/captcha',
        list: apiBase + '/site/list',
        fetchFile: apiBase + '/site/fetch-private-file',
        forgetPassword: apiBase + '/site/forget-password',
        siteStat: apiBase + '/site/stat',
        siteLogs: apiBase + '/site/logs',
        login: apiBase + '/site/login',
        init: apiBase + '/site/init',
        thirdpartylogin: apiBase + '/site/thirdparty-login',
    },
    // project: {
    //     projects: apiBase + '/project/projects', // 项目列表
    //     create: apiBase + '/project/create', // 选择分类后 创建项目，
    //     delete: apiBase + '/project/delete', // 删除项目
    //     pause: apiBase + '/project/pause', // 暂停项目
    //     stop: apiBase + '/project/stop', // 停止项目
    //     recover: apiBase + '/project/continue', // 恢复项目
    //     restart: apiBase + '/project/restart', // 重启项目
    //     copy: apiBase + '/project/copy', // 复制项目
    //     importData: apiBase + '/project/import-data', // 非采集任务导入数据
    //     setTemplate: apiBase + '/project/set-template', // 设置项目作业模板
    //     setAttribute: apiBase + '/project/set-attribute', // 设置项目描述
    //     preview: apiBase + '/project/preview', // 发布预览
    //     detail: apiBase + '/project/detail', // 获取项目详情
    //     getData: apiBase + '/project/get-data', // 获取项目详情
    //     assignData: apiBase + '/project/assign-data', // 配置项目第1步之提交分配信息
    //     getStep: apiBase + '/project/get-step', // 配置项目第2步之获取分布信息
    //     setStep: apiBase + '/project/set-step', // 配置项目第2步之提交分布设置
    //     getTeam: apiBase + '/project/get-team', // 配置项目第3步之获取团队
    //     assignTeam: apiBase + '/project/assign-team', // 配置项目第3步之设置团队
    //     records: apiBase + '/project/records' // 项目操作记录
    // },
    project: {
        projects: apiBase + '/project/projects', // 项目列表
        form: apiBase + '/project/form', // 项目类型
        create: apiBase + '/project/create', // 选择分类后 创建项目，
        delete: apiBase + '/project/delete', // 删除项目
        refuse: apiBase + '/project/refuse', // 拒绝项目
        pause: apiBase + '/project/pause', // 暂停项目
        stop: apiBase + '/project/stop', // 停止项目
        recover: apiBase + '/project/continue', // 恢复项目
        restart: apiBase + '/project/restart', // 重启项目
        reopen: apiBase + '/project/recovery', // 已完成重启项目
        copy: apiBase + '/project/copy', // 复制项目
        finish: apiBase + '/project/finish', // 完成项目
        importData: apiBase + '/project/import-data', // 非采集任务导入数据
        setTemplate: apiBase + '/project/set-template', // 设置项目作业模板
        setAttribute: apiBase + '/project/set-attribute', // 设置项目描述
        preview: apiBase + '/project/preview', // 发布预览
        detail: apiBase + '/project/detail', // 获取项目详情
        getData: apiBase + '/project/get-data', // 获取项目数据
        assignData: apiBase + '/project/assign-data', // 配置项目第1步之提交分配信息
        getStep: apiBase + '/project/get-step', // 配置项目第2步之获取分布信息
        setStep: apiBase + '/project/set-step', // 配置项目第2步之提交分布设置
        getTask: apiBase + '/project/get-task', // 配置项目第3步之获取任务信息
        setTask: apiBase + '/project/set-task', // 配置项目第3步之设置任务信息
        records: apiBase + '/project/records', // 项目操作记录
        projectStep: apiBase + '/project/steps', // 项目工序
        stepStat: apiBase + '/project/step-stat', // 获取工序统计
        statExport: apiBase + '/project/stat-export', // 导出数据
        submit: apiBase + '/project/submit'
    },
    // 批次相关
    batch: {
        batchs: apiBase + '/batch/batchs',
        batchDetail: apiBase + '/batch/detail' // 获取批次详情
    },
    // 作业相关
    task: {
        detail: apiBase + '/task/detail',
        tasks: apiBase + '/task/tasks',
        execute: apiBase + '/task/execute', // 包括领取 开始执行， 提交 修改 暂存 等
        batchExecute: apiBase + '/task/batch-execute', // 按正确率审核
        resource: apiBase + '/task/resource', // 获取任务数据资源
        resources: apiBase + '/task/resources', // 批量获取任务数据资源
        mark: apiBase + '/task/mark', // 获取图片标注的mark图
        mask: apiBase + '/task/mask', // 获取图片标注的mask图
        list: apiBase + '/task/list',
        assignedUser: apiBase + '/task/assign-user-list',
        assignUsers: apiBase + '/task/assign-users',
        assignedUsers: apiBase + '/stat/task',
    },
    // 分类管理
    category: {
        categories: apiBase + '/category/categories', // 获取所有分类信息(默认中文)
        categories_language: apiBase + '/category/categories-with-language', // 获取所有分类信息(中英文)
        create: apiBase + '/category/create', // 新增分类
        delete: apiBase + '/category/delete', // 新增分类(默认中文)
        detail_language: apiBase + '/category/detail-with-language', // 新增分类(中英文)
        detail: apiBase + '/category/detail', // 分类详情
        update: apiBase + '/category/update', // 修改分类
    },
    // 模板相关
    template: {
        list: apiBase + '/template/list', // 获取模板列表
        detail: apiBase + '/template/detail', // 模板详情
        create: apiBase + '/template/create', // 创建新模板
        update: apiBase + '/template/update', // 编辑模板
        delete: apiBase + '/template/delete', // 删除模板
        form: apiBase + '/template/form', // 获取模板的分类
        copy: apiBase + '/template/copy',
        use: apiBase + '/template/use',
    },
    // 用户管理
    user: {
        'index': apiBase + '/user/index',
        'stat': apiBase + '/user/stat',
        'detail': apiBase + '/user/detail',
        'form': apiBase + '/user/form',
        'list': apiBase + '/user/users', // 获取所有用户列表
        'create': apiBase + '/user/create', // 创建新用户
        'detail': apiBase + '/user/detail', // 获取用户详情
        'update': apiBase + '/user/update', // 更新用户信息
        'delete': apiBase + '/user/delete', // 删除用户
        'getTeams': apiBase + '/team/teams', // 获取所有团队列表
        'getRoles': apiBase + '/auth/roles', // 获取所有角色
        'emailImport': apiBase + '/user/import',
        'userImport': apiBase + '/user/import-parse',
        'userImportSubmit': apiBase + '/user/import-submit',
        'userRecord': apiBase + '/user/records', // 获取用户操作记录
        'userDevice': apiBase + '/user/devices', // 获取用户设备信息
        'updateEmail': apiBase + '/user/update-email',
        'updateMobile': apiBase + '/user/update-mobile',
        'updatePassword': apiBase + '/user/update-password',
        'updatePasswordNew': apiBase + '/user/update-password-new',
        'openFtp': apiBase + '/user/open-ftp' // 开通ftp功能

    },
    // 团队管理
    team: {
        'teamList': apiBase + '/team/teams', // 获取所有团队列表
        'teamCrate': apiBase + '/team/create', // 创建新团队
        'teamUpdate': apiBase + '/team/update', // 更新团队信息
        'teamUsers': apiBase + '/team/users', // 获取团队成员
        'teamUserUpdate': apiBase + '/team/user-update', // 更新团队成员
        'teamUserCreate': apiBase + '/team/user-create', // 添加团队成员
        'teamUserDel': apiBase + '/team/user-delete', // 添加团队成员
        'userExcel': apiBase + '/team/parse-users-excel', // 解析Excel文件

        'emailImport': apiBase + '/user/import', // 导入Excel文件成员
        'userImport': apiBase + '/team/user-import', // 导入Excel文件成员
        'teamDetail': apiBase + '/team/detail', //  团队详情
        'teamTags': apiBase + '/team/tags', // 团队标签
        'userTags': apiBase + '/team/user-tags', // 获取用户标签
        'groupList': apiBase + '/team/group-list', // 获取团队分组
        'createGroup': apiBase + '/team/create-group', // 创建分组
        'updateGroup': apiBase + '/team/update-group', // 修改分组
        'deleteGroup': apiBase + '/team/delete-group', // 删除分组
        'deleteGroupUser': apiBase + '/team/delete-group-user',
        'moveGroupUser': apiBase + '/team/move-group-user', // 批量移动小组成员
        'teamImportUser': apiBase + '/team/import-parse', // 解析excel
        'teamImportUserSubmit': apiBase + '/team/import-submit', // 导入成员
        'statTeam': apiBase + '/stat/team', // 团队绩效
        'statTeamByDay': apiBase + '/stat/team-by-day' // 团队每日绩效
    },
    group: {
        'create': apiBase + '/group/create',
        'update': apiBase + '/group/update',
        'delete': apiBase + '/group/delete',
        'groups': apiBase + '/group/groups',
        'userCreate': apiBase + '/group/user-create',
        'userDelete': apiBase + '/group/user-delete',
    },
    // 权限配置
    auth: {
        'roles': apiBase + '/auth/roles', // 获取角色列表
        'roleUsers': apiBase + '/auth/role-users', // 获取角色成员列表
        'permissions': apiBase + '/auth/permissions', // 获取全部权限
        'permissionsToGroup': apiBase + '/auth/permissions-to-group', // 获取分组权限
        'roleDetail': apiBase + '/auth/role-detail', // 获取全部权限
        'roleUpdate': apiBase + '/auth/role-update', // 角色编辑
        'roleCreate': apiBase + '/auth/role-create', // 角色创建
        'roleDel': apiBase + '/auth/role-delete', // 角色创建
        'permissionCreate': apiBase + '/auth/permission-create', // 权限编辑
        'permissionUpdate': apiBase + '/auth/permission-update', // 权限创建
        'permissionDel': apiBase + '/auth/permission-delete', // 权限删除
    },
    // 公告管理
    notice: {
        list: apiBase + '/notice/list', // 消息列表获取
        create: apiBase + '/notice/create',
        delete: apiBase + '/notice/delete',
        update: apiBase + '/notice/update', // 公告修改
    },
    // 财务管理
    money: {
        moneys: apiBase + '/money/moneys', // 余额管理
        payments: apiBase + '/money/payments', // 收款管理
        withdrawals: apiBase + '/money/withdrawals' // 提现管理
    },
    upload: {
        image: apiBase + '/site/upload-public-image', // 上传头像
        projectFiles: apiBase + '/site/upload-private-file', // 上传项目说明附件
        resourceFiles: apiBase + '/site/upload-resource-file', // 上传文件
        delProjectFiles: apiBase + '/site/delete-private-file', // 删除项目说明附件
    },
    download: {
        filePack: apiBase + '/pack/list',
        unpacklist: apiBase + '/unpack/list',
        packForm: apiBase + '/pack/form',
        fileBuild: apiBase + '/pack/build',
        datasetList: apiBase + '/pack/dataset-list',
        getFtp: apiBase + '/pack/get-ftp',
        file: apiBase + '/site/download-private-file', // 例：url+?file=keyVal 绝对路径
        downloadLog: apiBase + '/site/download-log-file'
    },
    work: {
        workList: apiBase + '/work/list', // 获取当前用户所在团队的工作(批次)列表
        // tasks: apiBase + '/task/tasks'
        workRecords: apiBase + '/work/records'
    },
    stat: {
        statTask: apiBase + '/stat/task',
        workstat: apiBase + '/stat/user',
        statByDay: apiBase + '/stat/user-by-day',
        statExport: apiBase + '/stat/export',
        operationExport: apiBase + '/stat/operation-export',
        workForm: apiBase + '/stat/work-form',
        work: apiBase + '/stat/work',
        workStatList: apiBase + '/stat/user-stat-list'
    },
    // 消息管理
    message: {
        send: apiBase + '/message/send', // 消息发送
        revoke: apiBase + '/message/revoke', // 撤销已发送消息
        list: apiBase + '/message/list', // 消息列表获取(整体)
        userMessages: apiBase + '/message/user-messages', // 消息列表获取(个人)
        userRead: apiBase + '/message/user-read', // 消息读取
        userDelete: apiBase + '/message/user-delete', // 消息删除(个人)
        messageRead: apiBase + '/message/user-read',
        messageDel: apiBase + '/message/user-delete',
        detail: apiBase + '/message/detail'
    },
    tag: {
        tagList: apiBase + '/tag/tags', // tag列表
        create: apiBase + '/tag/create',
        userTag: apiBase + '/tag/user-tags', // 获取用户标签
        teamUserTag: apiBase + '/tag/tags', // 获取团队用户标签
        deleteTag: apiBase + '/tag/delete', // 删除用户标签
        teamUserUpdateTag: apiBase + '/tag/update-tag-user' // 添加或删除团队用户的指定标签
    },
    // 用户邀请
    invitation: {
        form: apiBase + '/invitation/form',
        create: apiBase + '/invitation/create',
        update: apiBase + '/invitation/update',
        list: apiBase + '/invitation/list',
        detail: apiBase + '/invitation/detail',
        userList: apiBase + '/invitation/user-list',
        activeUser: apiBase + '/invitation/active-user',
        deleteUser: apiBase + '/invitation/delete-user',
    },
    data: {
        list: apiBase + '/data/list'
    },
    // 系统设置
    setting: {
        list: apiBase + '/setting/list',
        create: apiBase + '/setting/create',
        delete: apiBase + '/setting/delete',
        update: apiBase + '/setting/update',
    },
};
