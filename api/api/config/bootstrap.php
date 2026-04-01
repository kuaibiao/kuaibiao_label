<?php
//设置头信息
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
// header('Access-Control-Allow-Headers: *');

#配置最大内存
ini_set('memory_limit', '-1');

#配置最大执行时间
ini_set('max_execution_time', '36000');
ini_set('max_input_time', '3600');

#配置最大输入行数
ini_set('max_input_vars', '30000');



#以下配置无效, 请在php.ini中配置
#ini_set('post_max_size', '500M');
#ini_set('upload_max_filesize', '500M');


