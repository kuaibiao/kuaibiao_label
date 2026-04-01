# yii2-phpredis
yii2框架的基于new Redis() 方式连接redis, 有效解决fgets, fwrite 操作socket方式的弊端. 支持哨兵模式!

# 安装方法

1.命令安装
php composer.phar require --prefer-dist diszz/yii2-phpredis dev-master
或
composer require --prefer-dist diszz/yii2-phpredis dev-master

2.下载文件包, 
在vendor文件夹下新建文件夹diszz, 解压后复制yii2-phpredis到diszz文件夹下
结构将是如下结构

``` php
vendor\diszz\yii2-phpredis\Connection.php

```

并在vendor\yiisoft\extensions.php 文件末尾添加如下配置:

``` php
'diszz/yii2-phpredis' =>
    array (
        'name' => 'diszz/yii2-phpredis',
        'version' => '1.0.0.0',
        'alias' =>
        array (
            '@diszz/phpredis' => $vendorDir . '/diszz/yii2-phpredis',
        ),
    ),

```

# 使用方法
在main.php 或 main-local.php 配置如下信息:

``` php
<?php
return [
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'charset' => 'utf-8',
    'language' => 'zh-CN',
    'timeZone' => 'PRC',
    'components' => [
        'cache' => [
            //'class' => 'diszz\caching\FileCache',
            'class' => 'diszz\phpredis\Cache',
        ],
        'redis' => [
            'class' => 'diszz\phpredis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'session' => [
            'class' => 'diszz\phpredis\Session',
            // 'class' => 'diszz\web\DbSession',
            // 'db' => 'mydb',  // 数据库连接的应用组件ID，默认为'db'.
            // 'sessionTable' => 'my_session', // session 数据表名，默认为'session'.
        ],
        
 ....

 ```


# 代码中使用

``` php
	$key = 'aaaaa';
        $value = 'aaaa1111';
        if (Yii::$app->cache->exists($key))
        {
            var_dump('get');
            var_dump(Yii::$app->cache->get($key));
            
        }
        else
        {
            var_dump('set');
            Yii::$app->cache->set($key, $value, 3000);
            
        }
        
        $key = 'ccc';
        $value = 'ccccc111';
        
        if (Yii::$app->session->get($key))
        {
            var_dump('get');
            var_dump(Yii::$app->session->get($key));
            
        }
        else
        {
            var_dump('set');
            Yii::$app->session->set($key, $value);
            
        }

```


