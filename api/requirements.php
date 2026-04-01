<?php
/**
 * Application requirement checker script.
 *
 * In order to run this script use the following console command:
 * php requirements.php
 *
 * In order to run this script from the web, you should copy it to the web root.
 * If you are using Linux you can create a hard link instead, using the following command:
 * ln ../requirements.php requirements.php
 */

// you may need to adjust this path to the correct Yii framework path
$frameworkPath = dirname(__FILE__) . '/vendor/yiisoft/yii2';
$binFilePath = ['/usr', '/opt'];

if (!is_dir($frameworkPath)) {
    echo '<h1>Error</h1>';
    echo '<p><strong>The path to yii framework seems to be incorrect.</strong></p>';
    echo '<p>You need to install Yii framework via composer or adjust the framework path in file <abbr title="' . __FILE__ . '">' . basename(__FILE__) . '</abbr>.</p>';
    echo '<p>Please refer to the <abbr title="' . dirname(__FILE__) . '/README.md">README</abbr> on how to install Yii.</p>';
}

require_once $frameworkPath . '/requirements/YiiRequirementChecker.php';
$requirementsChecker = new YiiRequirementChecker();

$gdMemo = $imagickMemo = 'Either GD PHP extension with FreeType support or ImageMagick PHP extension with PNG support is required for image CAPTCHA.';
$zipMemo = 'ZIP extension is required for the Application';
$zipOK = $gdOK = $imagickOK = false;

if (extension_loaded('imagick')) {
    $imagick = new Imagick();
    $imagickFormats = $imagick->queryFormats('PNG');
    if (in_array('PNG', $imagickFormats)) {
        $imagickOK = true;
    } else {
        $imagickMemo = 'Imagick extension should be installed with PNG support in order to be used for image CAPTCHA.';
    }
}

if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    if (!empty($gdInfo['FreeType Support'])) {
        $gdOK = true;
    } else {
        $gdMemo = 'GD extension should be installed with FreeType support in order to be used for image CAPTCHA.';
    }
}

if (extension_loaded('zip')) {
    exec('unzip --help', $result);
    $result = implode('', $result);
    $env_res = strpos($result, ' -O ');
    if($env_res !== false) {
        $zipOK = true;
    }
    else {
        $zipMemo = "unzip doesn't support the -O parameter.";
    }
}

function find_linux_file($dirs = [], $fileName = '')
{
    $paths = [];

    if($dirs && $fileName)
    {
        if(is_array($dirs))
        {
            foreach($dirs as $dir)
            {
                exec('find '.$dir.' -name "'.$fileName.'"', $paths);
            }
        }
        else
        {
            exec('find '.$dirs.' -name "'.$fileName.'"', $paths);
        }
    }

    return $paths;
}

function find_bin_file($dirs = [], $fileName = '')
{
    $filePaths = find_linux_file($dirs, $fileName);
    foreach($filePaths as $path)
    {
        if(strpos($path, '/bin/'.$fileName) !== false)
        {
            // echo $path.' OK';
            return true;
        }
    }

    return false;
}

/**
 * Adjust requirements according to your application specifics.
 */
$requirements = array(
    // Database :
    array(
        'name' => 'PDO extension',
        'mandatory' => true,
        'condition' => extension_loaded('pdo'),
        'by' => 'All DB-related classes',
    ),
    array(
        'name' => 'PDO SQLite extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_sqlite'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for SQLite database.',
    ),
    array(
        'name' => 'PDO MySQL extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_mysql'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for MySQL database.',
    ),
    array(
        'name' => 'PDO PostgreSQL extension',
        'mandatory' => false,
        'condition' => extension_loaded('pdo_pgsql'),
        'by' => 'All DB-related classes',
        'memo' => 'Required for PostgreSQL database.',
    ),
    // Cache :
    array(
        'name' => 'Memcache extension',
        'mandatory' => false,
        'condition' => extension_loaded('memcache') || extension_loaded('memcached'),
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-caching-memcache.html">MemCache</a>',
        'memo' => extension_loaded('memcached') ? 'To use memcached set <a href="http://www.yiiframework.com/doc-2.0/yii-caching-memcache.html#$useMemcached-detail">MemCache::useMemcached</a> to <code>true</code>.' : ''
    ),
    array(
        'name' => 'APC extension',
        'mandatory' => false,
        'condition' => extension_loaded('apc'),
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-caching-apccache.html">ApcCache</a>',
    ),
    array(
        'name' => 'ZIP',
        'mandatory' => false,
        'condition' => find_bin_file($binFilePath, 'zip'),
        'by' => 'Pack and unpack files',
        'memo' => 'Required for pack and unpack files',
    ),
    array(
        'name' => 'ZIP extension',
        'mandatory' => false,
        'condition' => $zipOK,
        'by' => 'Pack and unpack files',
        'memo' => $zipMemo,
    ),
    array(
        'name' => 'FFMPEG',
        'mandatory' => false,
        'condition' => find_bin_file($binFilePath, 'ffmpeg'),
        'by' => 'Multimedia operate',
        'memo' => 'Required for Multimedia operate',
    ),
    array(
        'name' => 'FFMPEG extension',
        'mandatory' => false,
        'condition' => extension_loaded('ffmpeg'),
        'by' => 'Multimedia operate',
        'memo' => 'Required for multimedia operate.',
    ),
    array(
        'name' => 'REDIS',
        'mandatory' => false,
        'condition' => find_bin_file($binFilePath, 'redis-cli'),
        'by' => 'Data storage',
        'memo' => 'Required for the redis database',
    ),
    array(
        'name' => 'REDIS extension',
        'mandatory' => false,
        'condition' => extension_loaded('redis'),
        'by' => 'Data storage',
        'memo' => 'Required for data storage.',
    ),
    // CAPTCHA:
    array(
        'name' => 'GD PHP extension with FreeType support',
        'mandatory' => false,
        'condition' => $gdOK,
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-captcha-captcha.html">Captcha</a>',
        'memo' => $gdMemo,
    ),
    array(
        'name' => 'ImageMagick PHP extension with PNG support',
        'mandatory' => false,
        'condition' => $imagickOK,
        'by' => '<a href="http://www.yiiframework.com/doc-2.0/yii-captcha-captcha.html">Captcha</a>',
        'memo' => $imagickMemo,
    ),
    // PHP ini :
    'phpExposePhp' => array(
        'name' => 'Expose PHP',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpIniOff("expose_php"),
        'by' => 'Security reasons',
        'memo' => '"expose_php" should be disabled at php.ini',
    ),
    'phpAllowUrlInclude' => array(
        'name' => 'PHP allow url include',
        'mandatory' => false,
        'condition' => $requirementsChecker->checkPhpIniOff("allow_url_include"),
        'by' => 'Security reasons',
        'memo' => '"allow_url_include" should be disabled at php.ini',
    ),
    'phpSmtp' => array(
        'name' => 'PHP mail SMTP',
        'mandatory' => false,
        'condition' => strlen(ini_get('SMTP')) > 0,
        'by' => 'Email sending',
        'memo' => 'PHP mail SMTP server required',
    ),
    'phpOpcache' => array(
        'name' => 'PHP opcode cache',
        'mandatory' => false,
        'condition' => get_cfg_var('opcache.enable') == 1,
        'by' => 'Opcode cache',
        'memo' => '"opcache.enable" should be enabled at php.ini',
    ),
);
$requirementsChecker->checkYii()->check($requirements)->render();
