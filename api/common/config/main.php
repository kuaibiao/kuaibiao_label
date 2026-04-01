<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'charset' => 'utf-8',//不得更改
    'language' => 'zh-CN',//默认语言
    'timeZone' => 'UTC',//为解决国际时间问题, 必须是utc, 不得更改
    'components' => [
        'redis' => [
            'class' => 'diszz\phpredis\Connection',
            'hostname' => '127.0.0.1',
            'password' => null,//qweasdzxc123456
            'port' => 6379,
            'database' => 0,
            'keyPrefix' => 'lite.redis:',
            'sentinel' => 0,
            'cluster' => 0,
            'servers' => ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7002'],
        ],
        'cache' => [
            //'class' => 'yii\caching\FileCache',
            'class' => 'diszz\phpredis\Cache',
            'keyPrefix' => 'lite.cache:',
        ],
        'session' => [
            'class' => 'diszz\phpredis\Session',
            'keyPrefix' => 'lite.session:',
            // 'class' => 'yii\web\DbSession',
            // 'db' => 'mydb',  // 数据库连接的应用组件ID，默认为'db'.
            // 'sessionTable' => 'my_session', // session 数据表名，默认为'session'.
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
            ],
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => false,
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.ym.163.com',
                'username' => 'demo@youdomain.com',
                'password' => '',
                'port' => '994',
                'encryption' => 'ssl',//tls | ssl
            ],
            'messageConfig'=>[
                'charset'=>'UTF-8',
                'from'=>['demo@youdomain.com'=>'demo']
            ],
        ],
        'log' => [
            //'traceLevel' => 3,//YII_DEBUG ? 3 : 0
            'targets' => [
                'file' => [
                    'class' => 'yii\log\FileTarget',
                    'logVars' => ['_GET','_POST'],
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@app/../logfile/'.date('ymd').'/app.'.date('H').'.log',
                    'maxLogFiles' => 4,
                    'maxFileSize' => 512000, // in KB
                    'fileMode' => 0777,
                    'dirMode' => 0777,
                    'rotateByCopy' => false,
                ],
            ],
        ],
        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                ],
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                ],
            ],
        ],
        
        //认证组件配置
        'authManager' => [
            'class' => 'common\components\AuthManager',
            'defaultRoles' => [],
            'itemTable' => 'auth_item',
            'assignmentTable' => 'auth_assignment',
            'itemChildTable' => 'auth_item_child',
            'cache' => 'cache',
            'cacheKey' => 'lite.rbac1',
            'cacheTime' => 1200
        ],
    ],
];
