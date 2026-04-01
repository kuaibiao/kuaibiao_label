<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],//gii
    'modules' => [],
    /////'catchAll' => ['site/offline'],//网站维护,拦截所有请求
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'cookieValidationKey' => 'tnBWrygNOZ69HuhJHUWPQSj6ocJ73ILu',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'enableSession' => false,
            'loginUrl' => null
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
                'forward'=>'site/forward',
            ],
        ],
        'log' => [
            'targets' => [
                'file' => [
                    'class' => 'yii\log\FileTarget',
                    //'levels' => ['error', 'warning'],
                    'logFile' => '@app/../logfile/'.date('ymd').'/api.'.date('H').'.log',
                    'maxLogFiles' => 4,
                    'maxFileSize' => 512000, // in KB
                    'fileMode' => 0777,
                    'dirMode' => 0777,
                    'rotateByCopy' => false,
                ],
            ],
        ],
    ],
    'params' => $params,
];
