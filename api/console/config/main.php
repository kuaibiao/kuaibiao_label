<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'components' => [
        'log' => [
            'targets' => [
                'file' => [
                    'class' => 'yii\log\FileTarget',
                    'logVars' => ['_GET','_POST'],
                    //'levels' => ['error', 'warning'],
                    'logFile' => '@app/../logfile/'.date('ymd').'/console.'.date('H').'.log',
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
