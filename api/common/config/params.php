<?php
return [
    'adminEmail' => '',
    'supportEmail' => '',
    'user.accessTokenExpire' => 3600*24*60,
    'user.verifyTokenExpire' => 3600*24,
    'user_count_limit' => 10000,   //平台用户总数量限制
    //加密解密
    'security.secretkey' => '&^hl$***', //秘钥
    
    //系统授权配置
    'system.expiry_date' => '2025-3-15 10:30:00',//有效期
    'system.install_time' => '2019-3-15 10:30:00',//安装时间
    'system.last_running_time' => '2019-3-15 10:30:00',//最后运行时间,存储于setting.last_running_time;每隔1小时更新此值;一旦发现当前时间早于此时间,说明客户手动调整了服务器时间,将禁止使用本软件
    
    
    //最大使用内存
    'memory_max_size' => 4000000000,//4G
    
    //最大上传文件大小
    'post_max_size' => '500M',//8M-500M
    'upload_max_filesize' => '500M',//2M-500M

    //最大上传文件名称长度
    'post_max_file_name' => 200, //系统限制最大255字节
    
	//phpbin
    'phpbin' => 'php',
	//获取上传数据文件的目录结构
    'project_uploadfile_struct' => "nohup %s %s/yii site/uploadfile-struct %s %s >/dev/null 2>&1 &",
	//生成欣博友绩效统计
    'project_xbystat' => "nohup %s %s/yii site/xbystat %s %s %s > /dev/null 2>&1 &",
    //数据部署
    'deploy_file' => "nohup %s %s/yii site/deploy-file > /dev/null 2>&1 &",
    
    //上传附件目录名
    'attachment_dirname' => '',//attachment
    //上传数据文件目录名
    'uploadfile_dirname' => '',//uploadfile
    //暂存文件存储目录
    'temporaryStorage_dirname' => '',//temporaryStorage
    //下载文件目录名
    'downloadfile_dirname' => '',//downloadfile
    
    //上传包的同步解包和异步解包的临界值
    'task_source_extensions' => array(
        'csv' => ['uploadfile' => true, 'ftp' => true, 'background_process_limit' => '10000000'],//2万条对应容量1.4M
        'cvs' => ['uploadfile' => true, 'ftp' => true, 'background_process_limit' => '10000000'],//2万条对应容量1.4M
        'xls' => ['uploadfile' => true, 'ftp' => true, 'background_process_limit' => '10000000'],//2万条对应容量12M
        'xlsx' => ['uploadfile' => true, 'ftp' => true, 'background_process_limit' => '10000000'],//2万条对应容量376kb
        'zip' => ['uploadfile' => true, 'ftp' => true, 'background_process_limit' => '30000000'],
    ),
    
    //忽略文件
    'task_source_ignorefiles' => ['.', '..', '.svn', '__MACOSX', '.DS_Store', '*.unzip', '*.framegroup', '*.framegroupzips', '*.check.txt'],
    
    //所有图片的后缀格式
    'image_extension' => array('gif', 'jpeg','jpg', 'png', 'bmp'),
    
    'audio_extension' => array('wav', 'mp3'),
    
    'video_extension' => array('mp4', 'mkv', 'avi', 'wma'),
    
    //一次执行作业默认的领取张数
    'task_produce_receive_count' => 5,
    //一次审核作业默认的领取张数
    'task_audit_receive_count' => 20,
    //一次验收作业默认的领取张数
    'task_acceptance_receive_count' => 20,
    
    //一次作业的最大持续时间,单位秒
    'task_receive_expire' => 3600,
    
    //打回重做后提交重审
    'task_receive_expire_long' => 3600*48,
    
    //团队获取工作(批次)列表缓存时间, 单位秒
    'team_worklist_cachetime' => 600,
    
    //批次数上限
    'task_max_batch_count' => 60,
    
    //分步数上限
    'task_max_step_count' => 10,
    
    //当没有找到资源时的报错图片
    'task_sourcenotfound' => '/images/task_sourcenotfound.jpg',
    
    //数据文件上传方式切换时真假删除
    'task_upload_datafile_changetype_delete' => false, //true为真删除, false为假删除, 移动到其他文件夹
    
    //项目空间的最大值100m
    'task_space_maxsize' => 50000000,
    
    //工人工作绩效统计间隔, 单位秒
    'task_stat_timeout' => 600,
    
    //短信验证码失效时间, 单位秒, 服务商规定小于10分钟
    'mobilecode_timeout' => 600,
    
    //邮件验证码失效时间
    'emailcode_timeout' => 7200,
    
    //token
    'token' => [
        'key' => '806235b87fe148c3706ee3416eb2f01f',
        'tag' => '&^hl$hfody9', //解密标识
        'timeout' => 600 //过期时间,0表不过期
    ],
    
    //ftp配置
    'ftp.host' => '',
    'ftp.user_conf' => '',//ftp配置信息
    'ftp.user_home' => '',//ftp用户登录时的home, 此目录为ftp服务器路径, 非web服务器路径
    'domain.confd' => '',//ftp配置信息
    
    'withdrawal_rate' => 0.005,
    
    //请求频率ip白名单
    'ip.whitelist' => [
    ],
    
    //请求频率用户白名单, 针对我们自己测试账户
    'user.whitelist' => [
    ],
    
    //ffmpeg
    'ffmpeg_bin' => 'ffmpeg',
    
    //计划任务配置
    'crontabs' => [
        ['script' => 'site/timeout', 'timelong' => 60],
        ['script' => 'site/unpack', 'timelong' => 60],
        ['script' => 'site/pack', 'timelong' => 60],
    ],
    
    //绑定网卡, 为空即为不绑定;注意:若setting里也设置, 跟此处双重校验
    'macAddress' => '',
    
    //百度ai账号
    'baiduAi' => [
        'audio' => [
            'APP_ID' => '',
            'API_KEY' => '',
            'SECRET_KEY' => ''
        ],
        'ocr' => [
            'APP_ID' => '',
            'API_KEY' => '',
            'SECRET_KEY' => ''
        ],
        'image' => [
            'APP_ID' => '',
            'API_KEY' => '',
            'SECRET_KEY' => ''
        ],
        //mingze
        'nlp' => [
            'APP_ID' => '',
            'API_KEY' => '',
            'SECRET_KEY' => ''
        ],
        'chat' => [
            'APP_ID' => '',
            'API_KEY' => '',
            'SECRET_KEY' => ''
        ]
    ],
    
    //阿里云配置
    'aliyunConfig' => [
        'accessKeyId' => '',
        'accessKeySecret' => '',
    ]
];
