<?php
/**
 * 中文翻译
 * 
 */
return [
    
    //谨慎改动
    'language' => '语言',
    'language_zh_cn' => '简体中文',
    'language_zh_cn_simple' => '中',
    'language_en' => 'English',
    'language_en_simple' => 'En',
    'help' => '帮助',

    'system_expiry_date_passed' => '系统程序异常, 请联系管理员解决.',
    'system_ip_request_frequently' => '您访问频率过于频繁, 请稍后再试.',
    'system_not_open_ftp' => '系统未开启FTP功能',
    'mail_from_name' => '',
    'system_offline' => '系统正在维护中',
    
    'config_post_max_size_error' => 'post_max_size配置错误，请联系管理员',
    'config_upload_max_filesize_error' => 'upload_max_fileseze配置错误，请联系管理员',

    //------------------------------------
    
    'email_existed' => '邮箱已存在',
    'email_not_exist' => '账号不存在',
    'email_not_given' => '邮箱不能为空',
    'email_format_error' => '邮箱格式错误',
    'phone_format_error' => '手机号码格式错误',
    'phone_not_exist' => '手机号码不存在',
    'phone_existed' => '手机号码已存在',
    'phone_not_given' => '手机号码不能为空',
    'phone_registered' => '手机号码已注册',
    'verifcode_incorrect' => '验证码不正确',
    'verifcode_invalid' => '验证码失效, 请重新获取',
    'phone_verify_excessive' => '短信验证次数过多',
    'email_verify_excessive' => '邮件验证次数过多',
    'send_phone_code_excessive' => '发送短信验证码次数过多',
    'send_email_code_excessive' => '发送邮件验证码次数过多',
    'resetPasswordForm_verify_code_notexist' => '邮件验证码不存在或失效, 请重新发送',
    'resetPasswordForm_verify_code_error' => '邮件验证码错误',

    'key_not_given' => '缺少key参数',
    'op_param_invalid' => 'op无效',
    'cachekey_not_exist' => '缓存密钥不存在',
    'app_check_fail' => '应用检查失败',

    'format_error' => '文件格式错误',
    'param_error' => '参数错误',
    'param_not_given' => '参数没有找到',
    'name_not_given' => '没有找到名字参数',
    'id_not_given' => '没有找到ID参数',
    'file_not_exist' => '文件不存在',
    'dir_no_files' => '文件不存在',
    'file_not_match' => '文件不匹配',
    'copy_dir_fail' => '无法复制文件夹,或因无权限',
    'copy_file_fail' => '无法复制文件,或因无权限',
    'content_format_error' => '内容格式错误',

    'create_dir_fail' => '创建文件夹失败(请查看磁盘空间是否充足)',
    'file_path_error' => '文件路径错误',
    'param_not_found' => '参数没有找到',


    
	########################################################################
	### 发送邮箱 标题
	########################################################################
	'mail_subject_bs_forgetPassword' => '忘记密码',
	'mail_subject_bs_updatePhone' => '更改绑定手机号',
	'mail_subject_bs_updatePassword' => '更改密码',
	'mail_subject_bs_verifyNewEmail' => '绑定邮箱',


    ########################################################################
    ### 用户通用
    ########################################################################
    'user_not_exist' => '用户不存在',
    'user_not_login' => '请先登录',
    'login_not_allowed' => '此平台不允许你访问, 请查看帮助文档',
    'user_logged_in' => '您已登录',
    'ip_changed' => '您的IP发生变更, 请重新登录',
    'user_add_fail' => '添加用户失败',
    'signup_excessive' => '注册过于频繁',
    'login_excessive' => '登录过于频繁',
    'reset_password_link_invalid' => '您的重置密码链接无效',
    'reset_password' => '重置密码',
    'set_password' => '设置密码',
    'orig_password_incorrect' => '原始密码错误！',
    'passwords_not_consistent' => '两次输入的密码不一致!',
    'password_format_error'=>'密码格式错误!',
    'username_or_password_fail' => '账户或密码不正确！',
    'password_incorrect' => '密码不正确！',
    'password_fail_excessive' => '密码错误次数过多, 请稍后再试',
    'password_length_error' => '密码长度错误，长度须为6~18个字符,以字母开头',
    'user_auditing' => '该账号正在审核中',
    'user_unavailable' => '该账号不可用',
    'email_field_not_found' => '缺少邮箱字段',
    'password_field_not_found' => '缺少密码字段',
    'teamid_field_not_found' => '缺少团队ID字段',
    'type_field_not_found' => '缺少类型字段',
    'crowdsourcingid_field_not_found' => '缺少众包ID字段',
    'excel_url_field_not_found' => '缺少Excel地址字段',
    'excel_file_not_found' => 'Excel文件未找到',
    'read_file_error' => '读取文件错误',
    'user_info_not_found' => '未找到用户信息',
    'import_user_existed' => '导入用户已存在',
    'import_user_existed_for_email' => '当前邮箱已存在',
    'cannot_delete_self' => '无法删除自己',
    'ftp_check_fail' => 'FTP账户检查失败',
    'user_not_in_teams' => '您没有加入任何团队',
    'user_not_in_crowdsourcings' => '您没有加入任何众包',
    'user_language_zh_cn' => '中文',
    'user_language_en' => '英文',
    'user_no_permission' => '没有权限',
    'user_cannot_updateself' => '禁止在非用户中心修改自己资料',
    'user_not_found' => '未找到用户',
    'user_id_not_given' => '用户ID参数没有找到',
    'user_ftp_fail' => '用户ftp失败',
    'user_type_error' => '用户类型错误',
    'user_not_assign_role' => '用户没有分类角色',
    'user_ids_not_given' => '没有找到用户ID参数',
    'user_ftp_not_open' => '你没有开通FTP功能',
    'user_access_token_existed' => '用户access_token已存在',
    'nickname_forbidden' => '用户昵称含有敏感词',
    'user_no_site' => '用户没有加入租户',
    'user_no_team' => '用户没有加入团队',
    'team_notselected' => '必须选择一个团队',
    'team_ineffective' => '必须选择一个有效的团队',
    'user_permission_forbidden' => '您没有操作权限',
    'user_permission_forbidden_for_manager' => '您没有操作管理员的权限',
    'user_email_existed' => '邮箱已存在',

    'user_phone_format_error' => '电话号码格式错误',
    'user_nickname_format_error' => '昵称由英文字母、汉字、数字、下划线和点(.)组成，2-16个字符',
    'user_phone_existed' => '电话号码已存在',
    'user_status_not_active' => '未激活',
    'user_status_active' => '已激活',
    'user_status_disabled' => '已禁用',
    'user_status_deleted' => '已删除',
    'user_type_customer' => '客户',
    'user_type_admin' => '管理员',
    'user_type_worker' => '作业员',
    'user_type_crowdsourcing' => '众包',
    'user_type_root' => 'ROOT',

    'usermark_type_0' => '拜访',
    'usermark_type_1' => '电话',
    'usermark_type_2' => '邮件',
    'usermark_type_3' => '微信/qq',
    'usermark_type_4' => '其他',



    ########################################################################
    ### aimodel
    ########################################################################
    'aimodel_id_not_given' => '没有找到模板id参数',
    'aimodel_not_found' => '模板没有找到',
    'aimodel_status_deleted' => '模板已删除',
    'aimodel_status_not_disabled' => '未启用的模板，才可以删除',
    'aimodel_category_name_existed' => '模板分类名称已存在',
    'aimodel_category_not_found' => '分类信息未找到',
    'status_not_allowed' => '模板状态不允许',


    ########################################################################
    ### auth
    ########################################################################
    'auth_role_not_found' =>  '没有找到角色权限信息',
    'auth_description_not_given' => '没有找到描述参数',
    'auth_role_create_fail' => '角色创建失败',
    'auth_info_param_invalid' => '更新信息无效',
    'auth_update_role_fail' => '更新角色信息失败',
    'auth_permission_not_found' => '没有找到权限名称',
    'auth_role_name_not_given' => '没有找到角色名称参数',
    'auth_user_ids_not_allowed_to_role' => '用户不允许加入到该角色',
    'auth_old_role_name_not_given' => '没有找到原角色名称参数',
    'auth_role_invalid' => '角色无效',
    'auth_old_role_not_found' => '原角色信息不存在',
    'auth_user_not_match_old_role' => '用户和角色不匹配',


    ########################################################################
    ### category
    ########################################################################
    'category_id_not_given' => '没有找到分类ID参数',
    'category_not_found' => '没有找到分类信息',
    'category_desc_not_found' => '没有找到分类属性信息',
    'category_param_invalid' => '分类参数无效',
    'category_zh_param_invalid' => '中文分类参数无效',
    'category_en_param_invalid' => '英文分类参数无效',
    'category_zh_desc_not_found' => '没有找到中文分类属性信息',
    'category_en_desc_not_found' => '没有找到英文分类属性信息',


    ########################################################################
    ### crowdsourcing
    ########################################################################
    'crowdsourcing_list_typeNoPermisstion' => '用户类型不允许',
    'crowdsourcing_create_nameParamNotFound' => '没有找到众包名称',
    'crowdsourcing_create_typeNotPermission' => '用户类型不允许',
    'crowdsourcing_update_crowdsourcingIdParamNotFound' => '没有找到众包id',
    'crowdsourcing_update_crowdsourcingIdNotSelf' => '不是自己的众包',
    'crowdsourcing_update_typeNotPermission' => '用户类型不允许',
    'crowdsourcing_update_crowdsourcingNotFound' => '没有找到众包信息',
    'crowdsourcing_users_crowdsourcingIdParamNotFound' => '没有找到众包id',
    'crowdsourcing_users_typeNotPermission' => '用户类型不允许',
    'crowdsourcing_users_crowdsourcingNotFound' => '没有找到众包信息',
    'crowdsourcing_userUpdate_userIdparamNotFound' => '没有找到用户ID',
    'crowdsourcing_userUpdate_roleParamNotFound' => '没有找到角色',
    'crowdsourcing_userUpdate_crowdsourcingIdParamNotFound' => '没有找到众包id',
    'crowdsourcing_userUpdate_crowdsourcingNotSelf' => '不是自己的众包',
    'crowdsourcing_userUpdate_typeNotPermission' => '用户类型不允许',
    'crowdsourcing_userUpdate_CrowdsourcingUserNotFound' => '没有找到众包成员',
    'crowdsourcing_userAdd_crowdsourcingIdParamNotFound' => '没有找到众包id',
    'crowdsourcing_userAdd_emailParamNotFound' => '没有找到邮箱',
    'crowdsourcing_userAdd_roleParamNotFound' => '没有找到角色',
    'crowdsourcing_userAdd_crowdsourcingNotFound' => '没有找到众包信息',
    'crowdsourcing_userAdd_userNotFound' => '没有找到用户信息',
    'crowdsourcing_userAdd_userCrowdsourcingExist' => '用户已有众包团队',
    'crowdsourcing_userAdd_typeNoPermission' => '用户类型不允许',
    'crowdsourcing_userAdd_roleNotRange' => '角色超出范围',
    'crowdsourcing_userDelete_crowdsourcingIdParamNotFound' => '没有找到众包id',
    'crowdsourcing_userDelete_userIdsParamNotFound' => '没有找到用户ID',
    'crowdsourcing_userDelete_crowdsourcingNotSelf' => '不是自己的众包',
    'crowdsourcing_userDelete_typeNoPermission' => '用户类型不允许',
    'crowdsourcing_userDelete_CrowdsourcingNotFound' => '没有找到众包信息',
    'crowdsourcing_detail_crowdsourcingIdParamNotFound' => '没有找到众包id',
    'crowdsourcing_detail_typeNoPermisstion' => '用户类型不允许',
    'crowdsourcing_detail_crowdsourcingNotFound' => '没有找到众包信息',


    ########################################################################
    ### customer
    ########################################################################
    'customer_id_not_given' => '没有找到客户ID参数',
    'customer_bus_not_found' => '商务负责人没有找到',
    'customer_not_found' => '没有找到客户详情',
    'customer_user_is_other_customer' => '用户已是其他客户的人',
    'customer_user_type_not_customer' => '用户类型不是客户',
    'customer_type_param_invalid' => '类型不支持',
    'customer_source_type_param_invalid' => '来源不支持',
    'customer_not_match_manager' => '用户已是其他客户负责人',
    'customer_received' => '客户已被领取',
    'customer_not_allow_to_receive' => '客户状态不允许领取',
    'customer_user_id' => '没有找到联系人ID',
    'customer_user_not_found' => '没有找到客户联系人信息',
    'customer_user_is_customer_manager' => '客户负责人不能删除',
    'customer_user_not_business' => '用户不是商务负责人和协助人员',
    'customer_user_not_customer' => '客户联系人不存在',
    'customer_business_manager_id_not_given' => '商务负责人ID没有找到',


    ########################################################################
    ### followup
    ########################################################################
    'followup_id_not_given' => '没有找到事项ID',
    'followup_not_found' => '事项没有找到',
    'followup_user_not_found' => '事项负责人没有找到',
    'followup_customer_id_and_followup_id_not_given' => '没有找到客户ID和事项ID',


    ########################################################################
    ### message
    ########################################################################
    'message_date_param_invalid' => '日期参数无效',
    'message_not_given' => '没有找到信息参数',
    'message_not_found' => '没有找到信息',


    ########################################################################
    ### money
    ########################################################################
    'money_userWithdrawal_WithdrawalsHasExist' => '您的上一笔提现申请尚未完成审核，请耐心等待。审核完成后，您可再次提现',
    'money_userWithdrawal_numberHasRunOut' => '今日提现次数已用完，可在24:00之后再次提现',
    'money_userWithdrawalSubmit_withdrawalsHasExist' => '您的上一笔提现申请尚未完成审核，请耐心等待。审核完成后，您可再次提现',
    'money_userWithdrawalSubmit_numberHasRunOut' => '今日提现次数已用完，可在24:00之后再次提现',
    'money_userWithdrawalSubmit_moneyInsufficient' => '账户金额不足',
    'money_createPayment_paramsNotFound' => '参数错误',
    'money_createPayment_userNotFound' => '没有找到用户信息',
    'money_createPayment_projectNotFound' => '没有找到项目信息',
    'money_createPayment_projectdel' => '项目已删除',


    ########################################################################
    ### notice
    ########################################################################
    'notice_id_param_invalid' => '通知ID参数无效',
    'notice_not_found' => '没有找到通知信息',


    ########################################################################
    ### project
    ########################################################################
    'project_batch_not_found' => '没有找到批次信息',
    'project_category_id_not_given' => '缺少category_id参数',
    'project_category_not_found' => '没有找到分类信息',
    'project_not_found' => '没有找到项目信息',
    'project_id_not_given' => '缺少project_id参数',
    'project_name_not_given' => '缺少项目名称参数',
    'project_name_existed' => '项目名称已存在',
    'project_attribute_not_found' => '没有找到项目属性信息',
    'project_uploadfile_type_not_given' => '请选择数据的上传方式',
    'project_import_file_not_found' => '请上传数据文件',
    'project_uploadfile_type_invalid' => 'uploadfile_type无效',
    'project_status_not_allow' => '项目状态不允许',
    'project_status_not_allow_setting' => '项目正在准备中, 请稍后重试',
    'project_work_status_not_allow' => '项目工作状态不允许',
//    'unit_price_not_given' => '单价未找到，请先设置单价',
//    'unit_price_type_not_given' => '计价单位未找到，请先设置计价单位',
//    'out_of_unit_price_type' => '超出计价单位范围',
    'project_batch_setted' => '批次已配置',
    'project_assign_type_not_given' => '没有找到赋值类型',
    'project_paths_not_given' => '没有找到原始文件路径',
    'project_paths_not_json' => '原始文件路径不是json类型',
    'project_batch_count_too_many' => '批次数不能多于 %s',
    'project_step_count_too_many' => '工序数不能超过 %s',
    'project_count_not_given' => '没有找到批次参数',
    'project_count_less_than_one' => '批次数小于1',
    'project_batches_not_given' => '没有找到批次参数',
    'project_batches_not_json' => '批次数值不是json类型',
    'project_batches_param_error' => '批次参数错误',
    'project_tasks_param_error' => '任务参数错误',
    'project_file_path_not_exist' => '文件路径不存在',
    'project_struct_file_content_error' => '文件内容错误',
    'project_steps_not_given' => '没有找到分步参数',
    'project_step_not_found' => '没有找到分步信息',
    'project_tasks_not_given' => '没有找到任务参数',
    'project_tasks_not_found' => '没有找到任务',
    'project_step_id_not_given' => '没有找到分步ID',
    'project_step_group_not_found' => '流程没有找到',
    'project_step_group_deleted' => '流程已删除',
    'project_step_group_id_not_given' => '流程ID参数没有找到',
    'project_stat_export_excessive' => '项目分步绩效导出次数过多，频率限制',
    'project_configs_not_json' => '参数configs不是正确的json格式',
    'project_time_not_start' => '项目未开始',
    'project_time_ended' => '项目已结束',
    'resource_too_large' => '图片大小超过内存限制, 图片宽为%s高为%s, 图片路径为%s',
    'resource_parse_fail' => '图片解析失败, 图片路径为%s',
    'resource_not_exist' => '图片不存在, 图片路径为%s',


    ########################################################################
    ### setting
    ########################################################################
    'setting_not_found' => '没有找到数据信息',
    'setting_status_not_allow' => '当前系统设置已删除',


    ########################################################################
    ### site
    ########################################################################
    //项目数据解包方面
    'site_unpack_batch_config_empty' => '解包失败, 批次配置错误',
    'site_unpack_batch_config_fail' => '解包失败, 批次配置错误',
    'site_unpack_assign_type_fail' => '解包失败, 数据分配方式错误',
    'site_unpack_assign_paths_fail' => '解包失败, 分配路径信息错误',
    'site_unpack_assign_batches_fail' => '解包失败, 分配路径信息错误',
    'site_unpack_assign_count_fail' => '解包失败, 分配数量错误',
//    'unpack_import_fail' => '解包失败, 导入数据错误',
    'site_unpack_fail_vallid_file_not_found' => '解包失败, 没有找到有效文件',
    'site_unpack_status_not_allowed' => '解包状态不允许',
    'unpack_fail_type_not_supported' => '解包失败，暂不支持该类型文件',
    'unpack_fail_batch_notfound' => '解包失败，批次不存在',
    'unpack_sql_exception' => '数据解析失败，请检查上传文件，重新创建项目',
    'unpack_script_exception' => '数据解析失败，请重新创建项目或联系平台管理员',
    'unpack_fail' => '解包失败',
    'unpack_success' => '解包成功',

    'label_unpack_succ_tip' => '成功解包 %s 个文件, %s 个文件被忽视. ',
    'label_unpack_succ_tip_format_error' => '%s 为错误文件格式',
    'label_unpack_error_filePath_empty'=>'文件路径为空',
    'label_unpack_error_extractpath_exist' => '解压目录已经存在',
    'label_unpack_error_mkdir_fail' => '创建目录失败',
    'label_unpack_error_zip_extractTo_fail' => '解压文件失败',
    'label_unpack_error_file_list_empty' => '读取文件列表失败',
    'label_unpack_error_data_datafile_fileempty'=>'文件列表为空',
    'label_unpack_error_data_empty'=>'结果格式错误',
    'label_unpack_succ_tip_default_field_not_found' => '缺少必须的默认字段',

    'site_upload_imgage_not_given' => '没有找到上传的图片',
    'site_upload_file_not_given' => '上传文件失败,最大支持上传文件%s',
    'site_upload_file_save_fail' => '上传文件保存失败',
    'site_upload_file_name_too_long' => '上传文件名称过长',
    'site_upload_file_too_large' => '上传文件过大, 您上传的文件%s, 超过了上限%s',

    'site_page_not_found' => '页面没有找到',
    'site_save_path_not_exist' => '文件保存路径不存在',
    'site_save_file_exist' => '文件已存在',
    'site_file_path_decode_fail' => '参数(文件)错误',
    'site_file_not_given' => '文件参数没有找到',

    'site_not_exists' => '租户记录不存在',
    'site_auth_fail' => '用户没有登录',
    'site_id_not_given' => '租户ID参数没有找到',
    'site_team_disabled' => '团队已停用',
    'site_file_project_fail' => '项目状态错误',

    'site_status_not_active' => '未激活',
    'site_status_active' => '已激活',
    'site_status_disabled' => '已禁用',
    'site_status_expired' => '已过期',
    'site_not_active' => '租户未激活',
    'site_name_existed' => '租户名字已存在',


    ########################################################################
    ### tag
    ########################################################################
    'tag_name_not_given' => '没有找到标签名称参数',
    'tag_id_not_given' => '没有找到标签ID参数',
    'user_id_not_given' => '没有找到用户ID参数',
    'tag_not_found' => '没有找到标签信息',


    ########################################################################
    ### task
    ########################################################################
    'task_id_not_given' => '任务ID参数没有找到',
    'task_not_found' => '任务没有找到, 或已过期(任务ID为:%s)',
    'task_not_all_effective' => '不能配置已过期或已删除或已暂停的任务(ID为:%s)',
    'task_exe_param_error' => '执行任务参数错误',
    'task_project_not_match' => '批次中不存在当前项目ID',
    'task_status_not_allow' => '任务状态不允许',
    'task_category_not_found' => '没有找到分类信息',
    'task_data_count_too_large' => '显示作业条数过多',
    'task_submit_data_empty' => '参数错误',
    'task_result_param_invalid' => '找不到结果参数或不是数组',
    'task_work_result_repeat' => '作业结果重复',
    'task_data_id_not_given' => '没有找到作业ID',
    'task_data_result_not_json' => '数据结果不是json',
    'task_data_not_exist' => '未找到作业信息',
    'task_work_not_exist' => '工作不存在',
    'task_work_status_not_allow' => '提交失败，作业状态不允许提交',
    'task_user_not_receive' => '提交失败, 您没有领取此作业',
    'task_data_not_received' => '提交失败, 数据没有被领取',
    'task_data_initializing' => '数据初始化中, 请稍后再试',
    'task_data_get_error' => '数据获取发生错误',
    'task_data_waitting' => '没有可执行作业',
    'task_exe_max_times' => '您已执行%s次, 已达到次数限制',
    'task_time_not_start' => '没到任务开始时间',
    'task_time_ended' => '已过任务结束时间',
    'task_execute_data_notAllow' => '此作业被管理员驳回或重置，已为您重新领取新作业',
    'task_execute_notopen' => '此功能未开放',
    'time_type_not_correct' => '时间类型参数不正确',
    'task_type_not_allow' => '工序类型不允许',
    'task_currect_workids_not_found' => '未指定正确的作业',


    'task_received_finished' => '作业已被领取完',
    'task_batch_status_not_allow' => '批次状态不允许',
    'task_submit_param_error' => '提交作业参数错误',
    'task_submit_feedback_toolong' => '驳回原因过长',
    'task_submit_data_invalid' => '提交的作业数据无效',
    'task_work_not_found' => '作业没有找到',
    'task_stat_not_found' => '统计信息没有找到',
    'task_cache_data_not_found' => '剩余作业被管理员驳回或重置，已为您重新领取新作业',
    'task_user_not_found' => '任务用户没有找到',
    'task_plat_not_team' => '任务平台不是团队',
    'task_plat_not_crowdsourcings' => '任务平台不是众包',
    'task_init_fail' => '任务初始化失败',
    'task_usercache_empty' => '您未领取此作业, 或因被管理员驳回或重置, 请查看操作记录',
    'task_dataid_notin_usercache' => '您未领取此作业, 或因被管理员驳回或重置, 请查看操作记录',
    'task_team_not_found' => '没有找到对应团队',
    'task_team_id_not_user_team' => '与当前用户的团队id不一致',
    'task_submit_categoryTypeNotExist' => '分类错误',
    'task_execute_forceRefuse_notAllow' => '此操作不可执行',
    'task_list_userNoPermission' => '没有权限执行此操作',
    'task_list_userNotJoinTeam' => '用户没有加入团队',
    'user_nopermission' => '用户没有权限',
    'task_list_userNotJoinCrowdsourcing' => '不是众包用户',
    'task_users_taskIdParamNotFound' => '缺少任务ID',
    'task_users_taskNotFound' => '未找到用户任务信息',
    'task_stat_notfound' => '没有找到任务绩效数据',

	'task_group_list_default'=>'非小组成员',

    ########################################################################
    ### team
    ########################################################################
    'team_id_not_given' => '没有找到团队ID',
    'team_not_found' => '没有找到团队信息',
    'team_manager_id_not_number' => '管理员ID不是数字',
    'team_not_disabled' => '团队没有停用',
    'team_not_normal' => '团队状态不允许',
    'group_id_not_given' => '没有找到团队小组ID',
    'team_group_not_found' => '没有找到团队小组信息',

    'team_user_move_not_allowed' => '用户不可移动',
    'team_user_not_same_team' => '用户不属于同一个团队',
    'team_user_not_team_active' => '用户不是团队类型或未激活',
    'team_password_not_given' => '没有找到密码',
    'team_url_not_given' => '没有找到excel地址',
    'team_file_read_error' => '读取文件错误',
    'team_file_content_format_error' => '文件内容格式错误',
    'team_user_type_not_team' => '用户不是团队类型',
    'team_group_id_not_given' => '团队小组ID参数没有找到',



    ########################################################################
    ### template
    ########################################################################
    'template_id_not_given' => '没有找到模板ID参数',
    'template_not_found' => '没有找到模板信息',
    'template_is_deleted' => '模板已删除，请联系项目负责人',

    'picture_review' => '图片审核',
    'text_review' => '文本审核',
    'image_overlays' => '图片标注',
    'text_overlays' => '文本标注',
    'voice_audit' => '语音审核',
    'video_audit' => '视频审核',
    'voice_split' => '语音分割',
    'pointcloud_overlays' => '点云标注',
    'video_segmentation' => '视频分割',
    'pointcloud_segment' => '点云分割',


    ########################################################################
    ### stat
    ########################################################################
    'stat_project_id_not_given' => '缺少项目ID参数',
    'stat_project_not_found' => '项目不存在',
    'stat_task_not_found' => '任务不存在',
    'stat_task_id_not_given' => '缺少任务ID参数',

    ########################################################################
    ### work
    ########################################################################
    'step_id_not_exits' => '没有找到工序ID',
    'data_pack_empty' => '没有找到打包数据',

    ########################################################################
    ### pack
    ########################################################################
    'batch_id_not_given' => '没有获取到批次id',
    'pack_id_not_given' => '缺少打包记录id参数',
    'pack_is_disable' => '打包记录已删除',
    'pack_is_disable_or_paused' => '打包记录已删除或已暂停',
    'pack_status_not_delete' => '打包状态不允许删除',
    'pack_status_not_allowed' => '打包状态不允许',
    'pack_not_found' => '未找到打包记录',
    'pack_deleted' => '打包记录已删除',
    'pack_fail' => '打包失败',
    'pack_success' => '打包成功',
    
    ########################################################################
    ### deploy
    ########################################################################
    'deploy_name_not_given' => '缺少name参数',
    'deploy_upload_path_not_given' => '缺少upload_path参数',
    'deployment_id_not_given' => '缺少deploy_id参数',
    'deploy_path_not_given' => '缺少deploy_path参数',
    'deploy_file_empty' => '缺少部署文件，请先上传',
    'deployment_deleted' => '该条数据部署已删除',
    'deployment_existed' => '数据部署名称已存在',
    'deployment_user_no_permission' => '没有权限',


    ############################################################################################
    ##################################  model     分割线  ######################################
    ############################################################################################




    ########################################################################
    ### AuthItem model
    ########################################################################
    'role_guest' => '访客',
    'role_customer_manager' => '客户管理员',
    'role_customer_worker' => '客户操作员',
    'role_team_manager' => '团队管理员',
    'role_team_worker' => '作业员',
    'role_crowdsourcing_manager' => '众包管理员',
    'role_crowdsourcing_worker' => '众包作业员',
    'role_admin_worker' => '操作员',
    'role_admin_manager' => '管理员',
    'role_root_manager' => 'ROOT管理',
    'role_root_worker' => 'ROOT运营',
    'role_manager' => '管理员',
    'role_root' => 'ROOT',

    ########################################################################
    ### Category model
    ########################################################################
    'category_status_enabled' => '正常状态',
    'category_status_disabled' => '删除状态',
    'category_type_label' => '标注类',
    'category_type_collection' => '采集类',
    'category_type_external' => '外部链接',
    'category_filetype_image' => '图片类',
    'category_filetype_audio' => '语音类',
    'category_filetype_text' => '文本类',
    'category_filetype_video' => '视频类',
    'category_filetype_3d' => '3D类',
    'category_shape_type_rect' => '矩形',
    'category_shape_type_polygon' => '多边形',
    'category_shape_type_point' => '点',
    'category_shape_type_line' => '线',
    'category_shape_type_polyline' => '折线',
    'category_shape_type_triangle' => '三角形',
    'category_shape_type_bonepoint' => '骨骼点',
    'category_shape_type_splinecurve' => '三次样条曲线',
    'category_shape_type_closedcurve' => '闭合曲线',
    'category_shape_type_cuboid' => '长方体',
    'category_shape_type_trapezoid' => '梯形',
    'category_shape_type_quadrangle' => '四边形',
    'category_shape_type_pencilline' => '钢笔线',
    'category_shape_type_3dcube' => '3D立方体',
    'category_file_extension_jpg' => '.jpg',
    'category_file_extension_jpeg' => '.jpeg',
    'category_file_extension_png' => '.png',
    'category_file_extension_bmp' => '.bmp',
    'category_file_extension_wav' => '.wav',
    'category_file_extension_mp3' => '.mp3',
    'category_file_extension_v3' => '.v3',
    'category_file_extension_mp4' => '.mp4',
    'category_file_extension_avi' => '.avi',
    'category_file_extension_wma' => '.wma',
    'category_file_extension_m4a' => '.m4a',
    'category_file_extension_wmv' => '.wmv',
    'category_file_extension_mkv' => '.mkv',
    'category_file_extension_txt' => '.txt',
    'category_file_extension_pcd' => '.pcd',
    'category_upload_file_extension_xls' => '.xls',
    'category_upload_file_extension_xlsx' => '.xlsx',
    'category_upload_file_extension_csv' => '.csv',
    'category_upload_file_extension_zip' => '.zip',
    'category_upload_file_extension_mp4' => '.mp4',
    'category_upload_file_extension_avi' => '.avi',
    'category_upload_file_extension_wma' => '.wma',
    'category_upload_file_extension_wmv' => '.wmv',
    'category_upload_file_extension_mkv' => '.mkv',
    'category_upload_file_extension_txt' => '.txt',


    ########################################################################
    ### Crowdsourcing model
    ########################################################################
    'crowdsourcing_status_default' => '未审核',
    'crowdsourcing_status_normal' =>  '通过',
    'crowdsourcing_status_disabled' => '未通过',
    'crowdsourcing_not_found' => '没有找到众包信息',
    'crowdsourcing_id_not_given' => '没有找到众包id',


    ########################################################################
    ### Customer model
    ########################################################################
    'customer_status_default' => '待确认',
    'customer_status_receive' => '已领取',
    'customer_status_normal' => '有效客户',
    'customer_status_disable' => '无效客户',
    'customer_status_deleted' => '已删除',

    'customer_level_copper' => '铜牌客户',
    'customer_level_silver' => '银牌客户',
    'customer_level_gold' => '金牌客户',

    'customer_type_company'=> '企业',
    'customer_type_school'=> '院校',

    'customer_source_type_regiter' => '官网',
    'customer_source_type_activity' => '活动',
    'customer_source_type_phone' => '电话',



    ########################################################################
    ### Message model
    ########################################################################
    'message_status_enable' => '已启用',
    'message_status_disabled' => '已撤销',
    'message_status_deleted' => '已删除',
//    'message_link_type_userinfo' => '链接类型',

//    'messagetouser_isread_no' => '未读取',
//    'messagetouser_isread_yes' => '已读取',
//    'messagetouser_status_enable' => '正常',
//    'messagetouser_status_deleted' => '删除',

    'message_task_assigned_user' => '您被添加到作业执行人员列表, %s点击执行任务%s',
    'message_task_timeout' => '您的作业已超时, %s点击查看%s',
    'message_task_refuse' => '您的作业被驳回, %s点击查看%s',
    'message_task_reset' => '您的作业被重置, %s点击查看%s',
    'message_task_allow' => '恭喜, 您的作业审核通过!',
    'message_task_force_refuse' => '您的作业已重做, %s点击查看%s',
    'message_task_force_reset' => '您的作业已重置, %s点击查看%s',
    'message_task_audit_confirm' => '您审核驳回的作业已提交, %s点击查看%s',

    'message_user_update_info' => '您修改了资料',
    'message_user_update_password' => '您修改了密码',
    'message_user_update_email' => '您修改了邮箱',
    'message_user_update_phone' => '您修改了手机号码',
    'message_user_signup_succ' => '恭喜您注册成功',

    'message_project_unpack_fail' => '项目(id:{project_id})解包失败, 原因如下:{reason}',
    'message_project_unpack_fail_reason_unsupport' => '数据文件解析失败, 暂不支持此类型的数据文件',
    'message_project_unpack_fail_reason_wrongtype' => '数据文件解析失败, 类型配置错误',
    'message_project_unpack_fail_reason_invaildfile' => '解包失败，没有找到有效的文件',
    'message_project_unpack_fail_reason_wrongpath' => '数据文件解析失败, 批次路径无效',
    'message_project_unpack_succ' => '项目(id:{project_id})解包完成, 发布完成',
    'message_project_pack_fail' => '项目(id:%s)打包失败, 原因如下:%s',
    'message_project_pack_succ' => '项目(id:%s)打包完成',




    ########################################################################
    ### MessageToUser model
    ########################################################################
    'messagetouser_type_server' => '服务消息',
    'messagetouser_type_user' => '账户消息',
    'messagetouser_type_project' => '项目消息',
    'messagetouser_type_task' => '作业消息',
    'messagetouser_type_activity' => '活动消息',



    ########################################################################
    ### MoneyIncome model
    ########################################################################
    'moneylncome_type_task' => '任务奖励',
    'moneylncome_type_system' => '系统奖励',
    'moneylncome_type_activity' => '活动奖励',


    ########################################################################
    ### MoneyPayment model
    ########################################################################
    'moneypayment_object_task' => '项目付费',
    'moneypayment_object_system' => '服务付费',
    'moneypayment_object_recharge' => '余额充值',
    'moneypayment_object_activity' => '活动奖励',
    'moneypayment_payment_balance' => '余额支付',
    'moneypayment_payment_aliapy' => '支付宝支付',
    'moneypayment_payment_wechat' => '微信支付',
    'moneypayment_payment_offline' => '线下支付',
    'moneypayment_status_paying'     => '支付中',
    'moneypayment_status_pay_success'  => '支付成功',
    'moneypayment_status_pay_fail'     => '支付失败',
    'moneypayment_map_status'   => [
        0   => '支付中',
        1   => '支付成功',
        3   => '支付失败'
    ],




    ########################################################################
    ### MoneyRecord model
    ########################################################################
    'moneyrecord_type_recharge' => '充值',
    'moneyrecord_type_withdrawal' => '提现',
    'moneyrecord_type_earnings' => '赚的钱',
    'moneyrecord_type_reward' => '奖金',
    'moneyrecord_format_jpg' => '.jpg',
    'moneyrecord_format_png' => '.png',
    'moneyrecord_format_json' => '.json',
    'moneyrecord_format_xml' => '.xml',
    'moneyrecord_format_other' => '其他',
    'moneyrecord_unit_price_type_0' => '人/件',
    'moneyrecord_unit_price_type_1' => '人/时',



    ########################################################################
    ### MoneyWithdrawal model
    ########################################################################
    'moneywithdrawal_status_verifying' => '提现操作中',
    'moneywithdrawal_status_verify_fail' => '提现失败',
    'moneywithdrawal_status_paying' => '提现操作中',
    'moneywithdrawal_status_pay_fail' => '提现失败',
    'moneywithdrawal_status_finish' => '提现成功',
    'moneywithdrawal_status_deleted' => '删除',
    'moneywithdrawal_type_alipay' => '支付宝',
    'moneywithdrawal_type_wxpay' => '微信',
    'moneywithdrawal_money_less_minimum_amount' => '输入金额小于最低可提金额',
    'moneywithdrawal_money_exceed_available_amount' => '输入金额超过当前可提金额',



    ########################################################################
    ### Notice model
    ########################################################################
    'notice_status_normal' => '正常',
    'notice_status_deleted' => '删除',
    'notice_type_admin' => '管理员',
    'notice_type_team' => '团队',
    'notice_type_customer' => '客商',
    'notice_type_crowdsourcing' => '众包',
    'notice_type_root' => 'ROOT',
    'notice_type_win' => 'windows',
    'notice_type_android' => 'android',
    'notice_type_ios' => 'ios',



    ########################################################################
    ### Project model
    ########################################################################
    'project_status_releasing' => '发布中',
    'project_status_setting' => '配置中',
    'project_status_preparing' => '作业准备中',//准备中
    'project_status_working' => '作业中',
    'project_status_paused' => '已暂停',
    'project_status_stopped' => '已停止',
    'project_status_finished' => '完成',
    'project_status_deleted' => '删除',
    'project_release_status_category_select' => '选择分类',
    'project_release_status_form_fill' => '填写表单',
    'project_release_status_temp_store' => '暂存发布',
    'project_release_status_finished' => '发布完成',
    'project_work_status_setting' => '配置中',
    'project_work_status_data_to_load' => '等待数据初始化',
    'project_work_status_data_loading' => '数据初始化中',
    'project_work_status_data_load_fail' => '数据初始化失败',
    'project_work_status_executing' => '作业中',
    'project_work_status_finished' => '已交付',
    'project_work_status_canceled' => '取消',
    'project_assign_type_speed_first' => '速度优先',
    'project_assign_type_average' => '平均分配',
    'project_price_per_piece' => '每张',
    'project_price_per_pic' => '每个图形',
    'project_price_per_point' => '每个点',
    
    'project_assign_type_normal' => '抢单模式',
    'project_assign_type_study' => '教学共享模式',

    ########################################################################
    ### Project Record
    ########################################################################
    'project_record_type_create' => '创建项目',
    'project_record_type_edit' => '编辑项目',
    'project_record_type_examine' => '项目审核',
    'project_record_type_configure' => '项目配置',
    'project_record_type_restart' => '项目重启',
    'project_record_type_finish' => '项目完成',
    'project_record_type_refuse' => '项目未通过',

    'project_record_scene_create_submit' => '项目创建完成; %s 个数据文件,共 %s',
    'project_record_scene_create_create' => '创建项目',
    'project_record_scene_edit_info' => '编辑 ',
    'project_record_scene_edit_info_name' => '更改项目名称为\'%s\' ',
    'project_record_scene_edit_info_date' => '更改项目执行时间为 %s ~ %s ',
    'project_record_scene_edit_pause' => '暂停项目',
    'project_record_scene_edit_continue' => '恢复项目',
    'project_record_scene_edit_delete' => '删除项目',
    'project_record_scene_edit_stop' => '停止项目',
    'project_record_scene_examine_pass' => '审核通过,项目时间 %s ~ %s',
    'project_record_scene_examine_deny' => '拒绝项目',
    'project_record_scene_configure_configure' => '配置项目',
    'project_record_scene_configure_template' => '修改模板',
    'project_record_scene_configure_unpack_succ' => '数据解包成功',
    'project_record_scene_configure_unpack_fail' => '数据解包失败',
    'project_record_scene_finish_finish' => '项目完成',
    'project_record_scene_finish_expire' => '项目到期',
    'project_record_scene_restart_restart' => '项目重启,项目执行时间 %s ~ %s',

    'projectAttribute_field_description' => '项目描述',
    'projectAttribute_field_attachment' => '项目附件',
    'projectAttribute_field_fields' => '数据字段',
    'projectAttribute_field_uploadfile' => '上传文件路径',
    'projectAttribute_field_uploadfile_type' => '上传数据文件方式',
    'projectAttribute_field_uploadfile_account' => '文件上传网盘账户',
    'projectAttribute_field_batch_config' => '批次配置',

    ########################################################################
    ### ProjectAttribute model
    ########################################################################
    'project_upload_file_type_web' => '网页上传',
    'project_upload_file_type_ftp' => 'FTP上传',
    'project_upload_file_type_ssh' => 'SSH上传',

    ########################################################################
    ### Batch model
    ########################################################################
    'batch_status_waiting' => '待处理',
    'batch_status_enable' => '已启用',
    'batch_status_disable' => '未启用',
    'batch_sort_name' => '批次%s',


    ########################################################################
    ### Pack model
    ########################################################################
    'pack_extension_jpg' => 'jpg',
    'pack_extension_png' => 'png',
    'pack_extension_txt' => 'txt',
    'pack_status_default' => '默认状态',
    'pack_status_waiting' => '待打包',
    'pack_status_running' => '打包中',
    'pack_status_success' => '打包成功',
    'pack_status_failure' => '打包失败',
    'pack_status_stop' => '打包停止',
    'pack_status_cancelled' => '打包取消',
    'status_enable' => '正常',
    'status_disable' => '删除',
    'status_paused' => '暂停',

    ########################################################################
    ### Unpack model
    ########################################################################
    'unpack_status_default' => '默认状态',
    'unpack_status_waiting' => '待解包',
    'unpack_status_running' => '解包中',
    'unpack_status_success' => '解包成功',
    'unpack_status_failure' => '解包失败',
    
    'unpack_status_enable' => '正常',
    'unpack_status_disable' => '删除',
//    'pack_not_found' => '文件没有找到',


    ########################################################################
    ### UploadFileForm model
    ########################################################################
    'resources_empty' => '资源为空',


    ########################################################################
    ### Setting model
    ########################################################################
    'setting_value_type_boolean' => '布尔类型',
    'setting_value_type_number' => '数字类型',
    'setting_value_type_string' => '字符串',
    'setting_field_key_existed' => '当前key值已存在',



    ########################################################################
    ### Site model
    ########################################################################
    'site_not_exist' => '租户不存在',
    'site_members_limited' => '租户的用户数达到上限',
    'site_teams_limited' => '可创建团队数不足, 请联系客服申请!',
    'site_datas_limited' => '可发布数据量不足, 请联系客服申请!',
    'site_diskspace_limited' => '可使用存储空间不足, 请联系客服申请!',



    ########################################################################
    ### Tag model
    ########################################################################
    'tag_existed' => '标签已存在',



    ########################################################################
    ### Task model
    ########################################################################
    'task_unit_price_type_one' => '按张',
    'task_unit_price_type_label' => '按图形',
    'task_unit_price_type_point' => '按点数',
    'task_platform_type_team' => '团队',
    'task_platform_type_crowdsourcing' => '众包',
    'task_platform_type_aimodel' => 'AI模型',
    'task_type_normal' => '正式任务',
    'task_type_test' => '试标任务',

    'task_status_normal' => '正常',
    'task_status_finished' => '已完成',
    'task_status_paused' => '暂停',
    'task_status_deleted' => '已删除',

    ########################################################################
    ### Team model
    ########################################################################
    'team_status_default' => '未审核',
    'team_status_normal' => '通过',
    'team_status_disabled' => '禁用',
    'team_open_payment_no'  => '否',
    'team_open_payment_yes' => '是',
    'team_map_open_payment' => [
        0   => '否',
        1   => '是'
    ],
    'team_map_status' => [
        0   => '未审核',
        1   => '通过',
        2   => '禁用'
    ],
    'team_domain_format_error' => '团队域名格式错误',

//    'teamuser_status_enable' => '正常',
//    'teamuser_status_disabled' => '删除',

    'team_not_active' => '团队状态不可用',
    'team_disabled' => '团队状态禁用',
    'team_name_existed' => '团队名称已存在',
    'user_count_exceed' => '用户数量已超出限制',

    ########################################################################
    ### Template model
    ########################################################################
    'template_status_enable' => '启用',
    'template_status_disable' => '删除',
    'template_type_public' => '公共模板',
    'template_type_private' => '私有模板',
    'template_name_exist' => '模板名称已存在',

    ########################################################################
    ### TeamGroup model
    ########################################################################
    'teamgroup_name_exited' => '小组名称已存在',


    ########################################################################
    ### Work model
    ########################################################################
    'work_status_new' => '待领取',
    'work_status_received' => '领取',
    'work_status_executing' => '执行中',
    'work_status_submited' => '已提交',
    'work_status_to_audit' => '待审核',
    'work_status_audit_submited' => '已提交',
    'work_status_finished' => '通过',
    'work_status_deleted' => '已失效',
    'work_status_refused' => '被驳回',
    'work_status_refusedsubmit' => '待复审',
    'work_status_difficult' => '挂起中',
    'work_status_reseted' => '被重置',
    'work_status_give_up' => '已放弃',
    'work_status_time_out' => '已超时',
    'work_status_to_check' => '待质检',
    'work_status_to_accept' => '待验收',
    'work_status_audit_refuse' => '已驳回',
    'work_status_audit_reset' => '已重置',
    'work_status_produce_executing' => '作业中',
    'work_status_audit_executing' => '审核中',
    'work_status_acceptance_executing' => '验收中',
    'work_status_acceptance_finish' => '已完成', //验收已完成
    // 'work_status_to_reaudit' => '待复审',
    'work_type_normal' => '待领取',
    'work_type_giveup' => '放弃',
    'work_type_timeout' => '超时',
    'work_type_difficult' => '挂起',
    'work_type_auditallow' => '通过',
    'work_type_auditrefuse' => '驳回',
    'work_type_auditreset' => '重置',
    'work_type_auditallowed' => '通过',
    'work_type_auditrefused' => '被驳回',
    'work_type_auditreseted' => '被重置',
    'work_type_forcerefuse' => '被管理员驳回',
    'work_type_forcereset' => '被管理员重置',
    'work_type_parentrefused' => '父工序驳回',
    'work_type_parentreseted' => '父工序重置',
    'work_type_parentforcerefuse' => '父工序驳回',
    'work_type_parentforcereset' => '父工序重置',
    'work_type_refuserevise' => '驳回作业重做',
    'work_type_refusereset' => '驳回作业重置',
    'work_type_refusesubmitreverse' => '返工作业重做',
    'work_type_refusesubmitreset' => '返工作业重置',
    'work_type_difficultreverse' => '疑难作业重做',
    'work_type_difficultreset' => '疑难作业重置',
    'work_type_redo' => '修改',
    'work_type_parentredo' => '父工序修改',


    ########################################################################
    ### WorkRecord model
    ########################################################################
    'work_record_type_fetch' => '领取',
    'work_record_type_execute' => '执行',
    'work_record_type_submit' => '提交',
    'work_record_type_giveup' => '放弃',
    'work_record_type_timeout' => '超时',
    'work_record_type_difficult' => '挂起',
    'work_record_type_auditallow' => '通过',
    'work_record_type_auditrefuse' => '驳回',
    'work_record_type_auditreset' => '重置',
    'work_record_type_auditallowed' => '通过',
    'work_record_type_auditrefused' => '被驳回',
    'work_record_type_auditreseted' => '被重置',
    'work_record_type_forcerefuse' => '被管理员驳回',
    'work_record_type_forcereset' => '被管理员重置',
    'work_record_type_parentrefused' => '父工序驳回',
    'work_record_type_parentreseted' => '父工序重置',
    'work_record_type_parentforcerefuse' => '父工序驳回',
    'work_record_type_parentforcereset' => '父工序重置',
    'work_record_type_refuserevise' => '驳回作业重做',
    'work_record_type_refusereset' => '驳回作业重置',
    'work_record_type_refusesubmitreverse' => '返工作业重做',
    'work_record_type_refusesubmitreset' => '返工作业重置',
    'work_record_type_difficultreverse' => '疑难作业重做',
    'work_record_type_difficultreset' => '疑难作业重置',
    'work_record_type_redo' => '修改',
    'work_record_type_parentredo' => '父工序修改',
    'work_record_type_backtosubmit' => '回退到已提交',
    'work_record_type_auditeditsubmit' => '审核修改提交',
    'work_record_type_backtorefuse' => '回退到驳回作业',
    'work_record_type_temporarystorage' => '暂存',


    ########################################################################
    ### Step model
    ########################################################################
    'step_type_produce' => '执行',
    'step_type_audit' => '审核',
    'step_type_acceptance' => '验收',


    ########################################################################
    ### DataDeployment model
    ########################################################################
    'data_deployment_status_wait' => '待部署',
    'data_deployment_status_running' => '部署中',
    'data_deployment_status_success' => '部署成功',
    'data_deployment_status_fail' => '部署失败',
    'data_deployment_status_deleted' => '已删除',

    ########################################################################
    ### auth_item model
    ########################################################################
    'auth_admin_manager' => '管理员',
    'auth_admin_worker' => '操作员',
    'auth_aimodel_category-create' => 'AI模型分类创建',
    'auth_aimodel_category-delete' => 'AI模型分类删除',
    'auth_aimodel_category-detail' => 'AI模型分类详情',
    'auth_aimodel_category-list' => 'AI模型分类列表',
    'auth_aimodel_category-update' => 'AI模型分类更新',
    'auth_aimodel_copy' => 'AI模型复制',
    'auth_aimodel_create' => 'AI模型创建',
    'auth_aimodel_delete' => 'AI模型删除',
    'auth_aimodel_detail' => 'AI模型详情',
    'auth_aimodel_list' => 'AI模型列表',
    'auth_aimodel_update' => 'AI模型更新',
    'auth_auth_move-user' => '转移角色成员',
    'auth_auth_permission-create' => '添加权限',
    'auth_auth_permission-delete' => '删除权限',
    'auth_auth_permission-update' => '修改权限',
    'auth_auth_permissions' => '权限列表',
    'auth_auth_permissions-to-group' => '权限列表获取(分组形式)',
    'auth_auth_role-create' => '创建角色',
    'auth_auth_role-delete' => '删除角色',
    'auth_auth_role-detail' => '获取角色详情',
    'auth_auth_role-update' => '更改角色信息',
    'auth_auth_role-users' => '获取角色中用户列表',
    'auth_auth_roles' => '角色列表获取',
    'auth_auth_user-create' => '添加角色成员',
    'auth_auth_user-delete' => '删除角色成员',
    'auth_batch_batchs' => '批次列表',
    'auth_batch_detail' => '批次详情',
    'auth_category_categories' => '分类列表（分语言）',
    'auth_category_categories-with-language' => '分类列表',
    'auth_category_create' => '创建分类',
    'auth_category_delete' => '删除分类',
    'auth_category_detail' => '分类详情（分语言）',
    'auth_category_detail-with-language' => '分类详情',
    'auth_category_form' => '分类表单',
    'auth_category_update' => '修改分类（分语言）',
    'auth_crowdsourcing_create' => '创建众包',
    'auth_crowdsourcing_detail' => '获取众包详情',
    'auth_crowdsourcing_list' => '众包列表获取',
    'auth_crowdsourcing_update' => '更改众包',
    'auth_crowdsourcing_user-add' => '众包成员添加',
    'auth_crowdsourcing_user-delete' => '众包成员删除',
    'auth_crowdsourcing_user-update' => '众包成员编辑',
    'auth_crowdsourcing_users' => '获取众包成员',
    'auth_crowdsourcing_manager' => '众包管理员',
    'auth_crowdsourcing_worker' => '众包作业员',
    'auth_customer_create' => '创建客户',
    'auth_customer_create-user' => '更新客户人员',
    'auth_customer_delete-user' => '删除客户人员',
    'auth_customer_detail' => '客户详情',
    'auth_customer_list' => '客户列表',
    'auth_customer_new-register' => '新注册客户',
    'auth_customer_receive' => '领取客户',
    'auth_customer_statistics' => '客户统计',
    'auth_customer_update' => '更新客户',
    'auth_customer_update-user' => '更新客户人员',
    'auth_customer_user-list' => '客户人员列表',
    'auth_customer_manager' => '客户管理员',
    'auth_customer_worker' => '客户操作员',
    'auth_data_list' => '作业列表',
    'auth_deployment_create' => '数据部署',
    'auth_deployment_delete' => '数据部署删除',
    'auth_deployment_detail' => '数据部署详情',
    'auth_deployment_form' => '重新部署表单',
    'auth_deployment_list' => '数据部署列表',
    'auth_deployment_update' => '重新部署',
    'auth_file-pack_build' => '文件打包',
    'auth_file-pack_list' => '文件打包列表',
    'auth_followup_create' => '创建跟进事项',
    'auth_followup_create-record' => '创建跟进记录',
    'auth_followup_detail' => '跟进事项详情',
    'auth_followup_list' => '跟进事项列表',
    'auth_followup_record-list' => '跟进记录列表',
    'auth_followup_update' => '更新跟进事项',
    'auth_guest' => 'guest',
    'auth_message_detail' => '消息详情',
    'auth_message_form' => '消息表单',
    'auth_message_list' => '消息列表',
    'auth_message_messages' => '消息列表',
    'auth_message_revoke' => '撤销通知',
    'auth_message_send' => '发送消息',
    'auth_message_user-delete' => '消息删除',
    'auth_message_user-messages' => '获取用户消息',
    'auth_message_user-read' => '消息读取',
    'auth_money_create-payment' => '增加余额',
    'auth_money_incomes' => '收入列表',
    'auth_money_money-records' => '资金记录列表',
    'auth_money_moneys' => '资金列表',
    'auth_money_payments' => '收款列表',
    'auth_money_user-money' => '我的银两',
    'auth_money_user-money-record' => '用户收入记录列表',
    'auth_money_user-withdrawal' => '获取用户资金（提现）',
    'auth_money_user-withdrawal-record' => '用户收入列表',
    'auth_money_user-withdrawal-submit' => '用户提现申请',
    'auth_money_withdrawal-records' => '提现记录列表',
    'auth_money_withdrawals' => '提现记录列表',
    'auth_notice_create' => '创建公告',
    'auth_notice_delete' => '删除公告',
    'auth_notice_list' => '公告列表',
    'auth_notice_update' => '修改公告',
    'auth_pack_build' => '文件打包',
    'auth_pack_dataset-list' => '数据集列表',
    'auth_pack_delete' => '打包管理删除',
    'auth_pack_form' => '文件打包脚本列表',
    'auth_pack_get-ftp' => '获取数据集的ftp信息并推送到ftp',
    'auth_pack_list' => '文件打包列表',
    'auth_pack_pause' => '打包管理暂停',
    'auth_pack_regain' => '打包管理恢复',
    'auth_pack_renew' => '重新打包',
    'auth_pack_stop' => '结束打包',
    'auth_pack_update-sort' => '打包管理置顶',
    'auth_project_assign-data' => '设置数据',
    'auth_project_assign-team' => '分配团队',
    'auth_project_continue' => '继续项目, 只有暂停可恢复',
    'auth_project_copy' => '复制项目',
    'auth_project_create' => '创建项目,选择分类',
    'auth_project_delete' => '删除项目',
    'auth_project_detail' => '项目详情',
    'auth_project_finish' => '设置已完成项目',
    'auth_project_form' => '显示项目表单',
    'auth_project_get-data' => '获取数据结构',
    'auth_project_get-step' => '获取分步',
    'auth_project_get-task' => '获取项目的任务信息',
    'auth_project_pause' => '暂停项目',
    'auth_project_projects' => '所有项目列表',
    'auth_project_records' => '项目操作记录',
    'auth_project_recovery' => '重启完成的项目',
    'auth_project_restart' => '重启停止的项目',
    'auth_project_set-step' => '设置分步',
    'auth_project_set-task' => '设置任务',
    'auth_project_stop' => '停止项目',
    'auth_project_submit' => '创建项目,提交表单',
    'auth_root_manager' => 'ROOT管理',
    'auth_root_worker' => 'ROOT运营',
    'auth_setting_create' => '添加',
    'auth_setting_delete' => '删除',
    'auth_setting_list' => '设置管理',
    'auth_setting_update' => '更新',
    'auth_site_captcha' => '图片验证码',
    'auth_site_categorylist' => '官网的分类页面调用的接口',
    'auth_site_close' => '站点禁用',
    'auth_site_create' => '站点添加',
    'auth_site_delete' => '站点删除',
    'auth_site_delete-private-file' => '删除上传文件',
    'auth_site_detail' => '站点详情',
    'auth_site_download-log-file' => '下载系统日志',
    'auth_site_download-private-file' => '下载文件',
    'auth_site_download-public-file' => '下载文件',
    'auth_site_error' => '错误处理',
    'auth_site_fetch-private-file' => '下载文件(base64)',
    'auth_site_forget-password' => '忘记密码',
    'auth_site_forget-password-new' => '忘记密码提交',
    'auth_site_form' => '站点表单',
    'auth_site_get-online-users' => '获取在线用户列表',
    'auth_site_init' => '初始化配置',
    'auth_site_list' => '站点列表',
    'auth_site_login' => '登录',
    'auth_site_login-huicui' => '荟萃用户登录',
    'auth_site_login-quick' => '快速登录',
    'auth_site_logs' => '系统日志',
    'auth_site_open' => '站点开启',
    'auth_site_recovery' => '站点恢复',
    'auth_site_refresh-auth' => '重新导入用户权限配置(只增量,不删除已存在的)',
    'auth_site_send-email-code' => '发送邮箱验证码',
    'auth_site_send-phone-code' => '发送短信验证码',
    'auth_site_signup' => '注册',
    'auth_site_stat' => '系统统计',
    'auth_site_system-info' => '服务端系统信息',
    'auth_site_update' => '站点更新',
    'auth_site_upload-private-file' => '上传私有文件',
    'auth_site_upload-public-file' => '上传公有文件',
    'auth_site_upload-public-image' => '上传可对外访问的图片',
    'auth_stat_export' => '导出报表',
    'auth_stat_list' => '统计列表',
    'auth_stat_operation-export' => '导出操作记录',
    'auth_stat_task' => '获取任务的绩效列表',
    'auth_stat_team' => '团队每日绩效',
    'auth_stat_team-by-day' => '团队每日绩效',
    'auth_stat_user' => '获取用户在每个任务的绩效列表',
    'auth_stat_user-by-day' => '获取用户在每个任务的每天绩效列表',
    'auth_step_group-close' => '流程关闭',
    'auth_step_group-create' => '流程创建',
    'auth_step_group-delete' => '流程删除',
    'auth_step_group-detail' => '流程详情',
    'auth_step_group-form' => '流程表单',
    'auth_step_group-list' => '流程列表',
    'auth_step_group-open' => '流程开启',
    'auth_step_group-update' => '流程更新',
    'auth_tag_create' => '创建标签',
    'auth_tag_delete' => '删除标签',
    'auth_tag_tag-users' => '标签用户列表',
    'auth_tag_tags' => '获取所有标签',
    'auth_tag_update' => '修改标签',
    'auth_tag_update-tag-user' => '更新标签用户',
    'auth_task_assign-user' => '分配用户',
    'auth_task_assign-user-list' => '分配用户列表',
    'auth_task_assigned-userids' => '获取已分配的用户ID',
    'auth_task_batch-execute' => '批量执行任务',
    'auth_task_detail' => '获取项目的任务详情',
    'auth_task_execute' => '执行任务',
    'auth_task_list' => '获取项目的任务列表',
    'auth_task_mark' => '生成mark图',
    'auth_task_mask' => '生成mask图有问题',
    'auth_task_resource' => '任务资源',
    'auth_task_resources' => '任务资源',
    'auth_task_tasks' => '团队用户获取本团队的作业列表',
    'auth_task_top' => '置顶某任务排序',
    'auth_team_create' => '创建团队',
    'auth_team_create-group' => '创建小组',
    'auth_team_delete' => '删除团队',
    'auth_team_delete-group' => '删除小组',
    'auth_team_delete-group-user' => '删除小组成员',
    'auth_team_detail' => '获取团队详情',
    'auth_team_form' => '创建团队表单',
    'auth_team_group-list' => '获取小组列表',
    'auth_team_import-parse' => '解析导入文件',
    'auth_team_import-submit' => '提交导入数据',
    'auth_team_move-group-user' => '删除小组成员',
    'auth_team_move-team-user' => '批量移动团队成员',
    'auth_team_multiple-moves-team-user' => '批量移动团队成员（多个团队）',
    'auth_team_parse-users-excel' => '解析excel(多用户)',
    'auth_team_restore' => '恢复团队',
    'auth_team_teams' => '团队列表获取',
    'auth_team_update' => '更改团队',
    'auth_team_update-group' => '编辑小组',
    'auth_team_user-all' => '获取所有用户',
    'auth_team_user-create' => '团队成员添加',
    'auth_team_user-delete' => '团队成员删除',
    'auth_team_user-import' => '用户导入',
    'auth_team_user-update' => '团队成员编辑',
    'auth_team_users' => '获取团队成员',
    'auth_team_manager' => '团队管理员',
    'auth_team_worker' => '团队作业员',
    'auth_template_copy' => '复制模板',
    'auth_template_create' => '新增模板',
    'auth_template_delete' => '删除模板',
    'auth_template_detail' => '模板详情',
    'auth_template_form' => '模板详情',
    'auth_template_list' => '模板列表',
    'auth_template_update' => '修改模板',
    'auth_template_use' => '使用模板',
    'auth_unpack_list' => '文件解包列表',
    'auth_user_auth' => '获取用户已授权的权限',
    'auth_user_create' => '创建用户',
    'auth_user_delete' => '删除用户',
    'auth_user_detail' => '用户详情',
    'auth_user_devices' => '用户设备列表',
    'auth_user_form' => '用户表单',
    'auth_user_import-parse' => '解析导入文件',
    'auth_user_import-submit' => '提交导入数据',
    'auth_user_index' => '用户首页',
    'auth_user_open-ftp' => '开通ftp功能',
    'auth_user_records' => '用户记录列表',
    'auth_user_send-email-code' => '发送邮箱验证码',
    'auth_user_send-phone-code' => '发送短信验证码',
    'auth_user_stat' => '用户实时统计',
    'auth_user_update' => '更改用户信息',
    'auth_user_update-email' => '修改邮箱之验证',
    'auth_user_update-email-new' => '修改邮箱',
    'auth_user_update-password' => '修改密码之验证',
    'auth_user_update-password-new' => '修改密码',
    'auth_user_update-phone' => '修改手机号之验证',
    'auth_user_update-phone-new' => '修改手机号',
    'auth_user_users' => '用户列表',
    'auth_work_list' => '工作列表',
    'auth_work_records' => '工作记录列表',

	########################################################################
	### pack_script
	########################################################################
	'pack_script_common_JsonAndAllInOne' => 'Json(所有作业在一个json)',
	'pack_script_image_PascalVoc' => 'pascal voc(只适用于图片标注)',
	'pack_script_image_CoCo' => 'coco(通用json格式)',
    'pack_script_common_JsonAndOneToOne' => 'Json(一作业一结果)',
    'pack_script_original_result' => 'Json(原始结果)',
    'pack_script_mask_image' => 'mask图(png)',
    'pack_script_mark_image_with_tag' => 'mark图有标签(jpg)',
    'pack_script_mark_image_without_tag' => 'mark图无标签(jpg)',
    'pack_script_mark_image_fill' => 'mark图且填充(jpg)',
    'pack_script_mark_image_fill_without_tag' => 'mark图填充无标签',
    'pack_script_common_JsonAndOneToOneForTheShow' => 'Json(一图一json反显专用)',
    'pack_script_mark_jpg_no_label_imagick' => 'mark图无标签1.0',
    'pack_script_mask_png_v1' => 'mask图(png)imagick版1.0',
    'pack_script_image_text_yolo' => 'YOLO（矩形框txt）',
    
	
    ########################################################################
    ### category model
    ########################################################################
    'picture_review' => '图片审核',
    'picture_review_keywords' => '表情，情绪，分析',
    'picture_review_description' => '根据人物展现的表情判断情绪分类',
    'image_overlays' => '图片标注',
    'image_overlays_keywords' => '图片，矩形框',
    'image_overlays_description' => '在图片中将规定的品类用矩形框标出',
    'text_review' => '文本审核',
    'text_review_keywords' => '审核，内容，标签',
    'text_review_description' => '审核给出的内容是否符合其标签或者描述',
    'text_overlays' => '文本标注',
    'text_overlays_keywords' => '文本  标注 ',
    'text_overlays_description' => '文本标注',
    'voice_audit' => '语音审核',
    'voice_audit_keywords' => '语音 分类',
    'voice_audit_description' => '根据语音内容，分类',
    'voice_split' => '语音分割',
    'voice_split_keywords' => '语音，分割，截取',
    'voice_split_description' => '按要求从长音频中截取音频段',
    'video_audit' => '视频审核',
    'video_audit_keywords' => '视频 审核 分类',
    'video_audit_description' => '观看视频，根据内容将其分类',
    'object_tracking' => '跟踪标注',
    'object_tracking_keywords' => '标记，视频',
    'object_tracking_description' => '连续标记视频中出现的物体',
    '3d_point_cloud' => '3D点云',
    '3d_point_cloud_keywords' => '3d,点云',
    '3d_point_cloud_description' => '对3d点云的标注',
    'pointcloud_segment' => '点云分割',
    'pointcloud_segment_keywords' => '点云 分割 ',
    'pointcloud_segment_description' => '识别点云中的物体',
    'image_collection' => '图片采集',
    'image_collection_keywords' => '图片 采集',
    'image_collection_description' => '图片 采集',
    'point_cloud_tracking' => '点云追踪',
    'point_cloud_tracking_keywords' => '点云追踪',
    'point_cloud_tracking_description' => '点云追踪',
    'video_segmentation' => '视频分割',
    'video_segmentation_keywords' => '视频,分割',
    'video_segmentation_description' => '视频,分割',

    ########################################################################
    ### Group model
    ########################################################################

    'group_not_active' => '小组状态不可用',
    'group_disabled' => '小组状态禁用',
    'group_name_existed' => '小组名称已存在',
    'group_not_found' => '小组未找到',
    'group_not_normal' => '部分用户不属于本小组',
    'group_is_exist' => '用户已在其他小组中存在',
    'group_user_is_exist' => '用户已在小组中存在',
    'group_id_not_given' => '没有找到团队小组ID',
    'group_status_normal' => '正常',
    'group_status_disabled' => '禁用',

    ########################################################################
    ### StatResult model
    ########################################################################

    'stat_result_type_unknown_count' => '未知类型数',
    'stat_result_type_circle_count' => '标注总圆数',
    'stat_result_type_ellipse_count' => '标注总椭圆数',
    'stat_result_type_unclosedpolygon_count' => '标注总折线数',
    'stat_result_type_rect_count' => '标注总矩形数',
    'stat_result_type_rect_point_count' => '标注总矩形+点数',
    'stat_result_type_polygon_count' => '标注总多边形数',
    'stat_result_type_trapezoid_count' => '标注总梯形数',
    'stat_result_type_triangle_count' => '标注总三角形数',
    'stat_result_type_quadrangle_count' => '标注总四边形数',
    'stat_result_type_cuboid_count' => '标注总长方体数',
    'stat_result_type_line_count' => '标注总线数',
    'stat_result_type_point_count' => '标注总点数',
    'stat_result_type_bonepoint_count' => '标注有序关键点总数',
    'stat_result_type_closedcurve_count' => '标注闭合曲线总数',
    'stat_result_type_splinecurve_count' => '标注曲线总数',
    'stat_result_type_pencilline_count' => '标注钢笔总线数',
    'stat_result_type_media_duration' => '原媒体文件总时长',
    'stat_result_type_effective_duration' => '媒体有效总时长',
    'stat_result_type_text_word_count' => '文本标注数',
    'stat_result_type_file_text_word_count' => '原文本字符数',
    'stat_result_type_3d_cloudpoint_count' => '3D点云标注框数',
    'stat_result_type_label_no_count' => '无效数据数',
    'stat_result_type_label_yes_count' => '有效数据数',
    'stat_result_type_label_unknown_count' => '未知数据数',
    'stat_result_type_rect_seal_count' => '矩形印章总数',
    'stat_result_type_form_count' => '标注表单总数',
    'stat_result_type_text_count' => '标注文本总数',
    'stat_result_type_audio_count' => '标注语音总数',
    'stat_result_type_video_count' => '标注视频总数',
    'stat_result_type_2d_cloudpoint_count' => '标注2D点云框总数',
    'stat_result_type_2d_object_count' => '标注2D物体总数',
    'stat_result_type_3d_object_count' => '标注3D物体总数',

    ########################################################################
    ### stat_controller
    ########################################################################
    'stat_controller_performance' => '绩效',
    'stat_controller_performance_final' => '最终绩效',
    'stat_controller_step_name' => '工序名称：',
    'stat_controller_step_type' => '工序类型：',
    'stat_controller_task_total_time' => '作业总时间（秒）：',
    'stat_controller_task_active_time' => '作业有效总时间（秒）：',
    'stat_controller_allow_count_total' => '通过总张数：',
    'stat_controller_submit_times_total' => '提交总次数：',
    'stat_controller_submit_count_total' => '提交总张数：',
    'stat_controller_invalid_count_total' => '无效总张数：',
    'stat_controller_refused_count_total' => '被驳回总张数：',
    'stat_controller_reseted_count_total' => '被重置总张数：',
    'stat_controller_correct_rate_colon' => '正确率：',
    'stat_controller_valid' => '有效总张数：',
    'stat_controller_work_result_count_total' => '作业总标注数：',
    'stat_controller_work_point_count_total' => '作业总点数：',
    'stat_controller_work_line_count_total' => '作业总线数：',
    'stat_controller_work_rectangle_count_total' => '作业总矩形数：',
    'stat_controller_work_polygon_count_total' => '作业总多边形数：',
    'stat_controller_work_other_count_total' => '其他总数：',
    'stat_controller_audio_work_time' => '作业中时长（语音）：',
    'stat_controller_correct_rate_format' => '正确率公式：',
    'stat_controller_correct_rate_format_math' => '正确率 = 通过数/被审核数',
    'stat_controller_nickname' => '昵称',
    'stat_controller_account' => '账号',
    'stat_controller_team' => '所属团队',
    'stat_controller_submit_times' => '提交次数',
    'stat_controller_submit_pic_count' => '提交张数',
    'stat_controller_allow_pic_count' => '通过张数',
    'stat_controller_refused_count' => '被驳回',
    'stat_controller_reseted_count' => '被重置',
    'stat_controller_correct_rate' => '正确率',
    'stat_controller_invalid_pic_count' => '无效张数',
    'stat_controller_valid_pic_count' => '有效张数',
    'stat_controller_valid_data' => '有效数据',
    'stat_controller_revise_valid_data' => '修改有效数据',
    'stat_controller_accumulation_work_time' => '累积工时（s）',
    'stat_controller_active_work_time' => '作业有效时长（s）',
    'stat_controller_average_work_time' => '作业平均时长（s）',
    'stat_controller_label_count' => '标注数',
    'stat_controller_point' => '点',
    'stat_controller_line' => '线',
    'stat_controller_rectangle' => '矩形',
    'stat_controller_polyon' => '多边形',
    'stat_controller_other' => '其他',
    'stat_controller_audio_time' => '时长（语音）',
    'stat_controller_allow_count' => '通过数',
    'stat_controller_refuse_count' => '驳回数',
    'stat_controller_reset_count' => '重置数',
    'stat_controller_project_id' => '项目id：',
    'stat_controller_project_name' => '项目名称：',
    'stat_controller_batch_id' => '批次id',
    'stat_controller_data_id' => '作业id',
    'stat_controller_task_id' => '作业id',
    'stat_controller_task_name' => '作业名称',
    'stat_controller_last_worker' => '最终作业员',
    'stat_controller_worker_id' => '作业员id',
    'stat_controller_submit_time' => '提交时间',
    'stat_controller_produce' => '执行',
    'stat_controller_audit' => '审核',
    'stat_controller_check' => '质检',
    
    'email_reset_password_code_content' => "<div>尊敬的用户：</div><div style='text-indent:2em'><p>您正在使用重置密码功能。验证码为：%s，此验证码在%s小时内有效。</p><p>此邮件为系统自动发出的邮件，请勿直接回复。</p></div>",
    
    
    
];