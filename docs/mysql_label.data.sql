/*
SQLyog Ultimate v12.3.1 (64 bit)
MySQL - 5.5.44-MariaDB : Database - kuaibiao_lite
*********************************************************************
*/


/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`kuaibiao_lite` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;

USE `kuaibiao_lite`;

/*Data for the table `app` */

insert  into `app`(`id`,`name`,`app_key`,`app_version`,`platform`,`status`,`limit_login_ip`,`limit_login_useragent`,`created_at`,`updated_at`) values 

(1,'pc','pc','1.0.0',1,0,1,0,0,0);

/*Data for the table `app_record` */

/*Data for the table `app_stat` */

/*Data for the table `auth_assignment` */

insert  into `auth_assignment`(`item_name`,`user_id`,`created_at`) values 

('manager','1',0),

('worker','1',0);

/*Data for the table `auth_item` */

insert  into `auth_item`(`name`,`type`,`description`,`rule_name`,`data`,`created_at`,`updated_at`) values 

('data/list',2,'作业列表',NULL,'',1569750226,1569750226),

('group/create',2,'创建小组',NULL,'',1569226396,1569226396),

('group/delete',2,'删除小组',NULL,'',1569226396,1569226396),

('group/detail',2,'小组详情',NULL,'',1569226396,1569226396),

('group/form',2,'小组表单',NULL,'',1569226396,1569226396),

('group/groups',2,'小组列表',NULL,'',1569226396,1569226396),

('group/update',2,'更改小组信息',NULL,'',1569226396,1569226396),

('group/user-create',2,'添加小组用户',NULL,'',1569226396,1569226396),

('group/user-delete',2,'添加小组用户',NULL,'',1569382718,1569382718),

('group/users',2,'获取小组用户',NULL,'',1569226396,1569226396),

('guest',1,'guest',NULL,'',1569225690,1569225690),

('manager',1,'role_manager',NULL,'',1569225690,1569225690),

('message/detail',2,'消息详情',NULL,'',1569407251,1569407251),

('message/form',2,'消息表单',NULL,'',1569407251,1569407251),

('message/list',2,'消息列表',NULL,'',1569407250,1569407250),

('message/revoke',2,'撤销通知',NULL,'',1569407251,1569407251),

('message/send',2,'发送消息',NULL,'',1569407251,1569407251),

('message/user-delete',2,'消息删除',NULL,'',1569407251,1569407251),

('message/user-messages',2,'获取用户消息',NULL,'',1569407251,1569407251),

('message/user-read',2,'消息读取',NULL,'',1569407251,1569407251),

('notice/create',2,'创建公告',NULL,'',1569407251,1569407251),

('notice/delete',2,'删除公告',NULL,'',1569407251,1569407251),

('notice/list',2,'公告列表',NULL,'',1569407251,1569407251),

('notice/update',2,'修改公告',NULL,'',1569407251,1569407251),

('pack/build',2,'文件打包',NULL,'',1571111702,1571111702),

('pack/dataset-list',2,'数据集列表',NULL,'',1571111702,1571111702),

('pack/form',2,'文件打包脚本列表',NULL,'',1569752993,1569752993),

('pack/get-ftp',2,'获取数据集的ftp信息并推送到ftp',NULL,'',1571111702,1571111702),

('pack/list',2,'文件打包列表',NULL,'',1569752993,1569752993),

('pack/renew',2,'重新打包',NULL,'',1571111702,1571111702),

('pack/stop',2,'结束打包',NULL,'',1571111702,1571111702),

('pack/top',2,'打包管理置顶',NULL,'',1571111702,1571111702),

('project/assign-data',2,'设置数据',NULL,'',1569226395,1569226395),

('project/assign-team',2,'分配团队',NULL,'',1569226395,1569226395),

('project/continue',2,'继续项目, 只有暂停可恢复',NULL,'',1569226395,1569226395),

('project/copy',2,'复制项目',NULL,'',1569226395,1569226395),

('project/create',2,'创建项目,选择分类',NULL,'',1569226395,1569226395),

('project/delete',2,'删除项目',NULL,'',1569226395,1569226395),

('project/detail',2,'项目详情',NULL,'',1569226395,1569226395),

('project/finish',2,'设置已完成项目',NULL,'',1569226395,1569226395),

('project/form',2,'显示项目表单',NULL,'',1569226395,1569226395),

('project/get-data',2,'获取数据结构',NULL,'',1569226395,1569226395),

('project/get-step',2,'获取分步',NULL,'',1569226395,1569226395),

('project/get-task',2,'获取项目的任务信息',NULL,'',1569226395,1569226395),

('project/pause',2,'暂停项目',NULL,'',1569226395,1569226395),

('project/projects',2,'所有项目列表',NULL,'',1569226395,1569226395),

('project/records',2,'项目操作记录',NULL,'',1569226395,1569226395),

('project/recovery',2,'重启完成的项目',NULL,'',1569226395,1569226395),

('project/restart',2,'重启停止的项目',NULL,'',1569226395,1569226395),

('project/set-step',2,'设置分步',NULL,'',1569226395,1569226395),

('project/set-task',2,'设置任务',NULL,'',1569226395,1569226395),

('project/stop',2,'停止项目',NULL,'',1569226395,1569226395),

('project/submit',2,'创建项目,提交表单',NULL,'',1569226395,1569226395),

('setting/create',2,'创建系统设置',NULL,NULL,1589190086,1589190086),

('setting/delete',2,'删除系统设置',NULL,NULL,1589190086,1589190086),

('setting/list',2,'获取系统设置列表',NULL,NULL,1589190086,1589190086),

('setting/update',2,'更新系统设置',NULL,NULL,1589190086,1589190086),

('site/delete-private-file',2,'删除私有文件',NULL,'',1569484907,1569484907),

('site/upload-private-file',2,'上传私有文件',NULL,'',1569484907,1569484907),

('site/upload-public-image',2,'上传公有图片(头像)',NULL,'',1571813845,1571813845),

('stat/export',2,'导出报表',NULL,'',1569813903,1569813903),

('stat/operation-export',2,'导出操作记录',NULL,'',1569813903,1569813903),

('stat/task',2,'获取任务的绩效列表',NULL,NULL,1589190085,1589190085),

('stat/user',2,'获取用户在每个任务的绩效列表',NULL,'',1570851442,1570851442),

('stat/user-stat-list',2,'获取用户在每个任务的绩效列表',NULL,'',1571129406,1571129406),

('stat/work',2,'获取用户作业的绩效统计',NULL,'',1571025985,1571025985),

('stat/work-form',2,'获取用户作业的绩效统计的表单',NULL,'',1571043262,1571043262),

('task/assign-user-list',2,'分配用户列表',NULL,'',1569408787,1569408787),

('task/assign-users',2,'分配用户',NULL,'',1569408788,1569408788),

('task/batch-execute',2,'批量执行任务',NULL,'',1569564905,1569564905),

('task/detail',2,'获取项目的任务详情',NULL,'',1569564905,1569564905),

('task/execute',2,'执行任务',NULL,'',1569564905,1569564905),

('task/list',2,'获取项目的任务列表',NULL,'',1569564905,1569564905),

('task/mark',2,'生成mark图',NULL,'',1569564905,1569564905),

('task/mask',2,'生成mask图有问题',NULL,'',1569564905,1569564905),

('task/resource',2,'任务资源',NULL,'',1569564905,1569564905),

('task/resources',2,'任务资源',NULL,'',1569564905,1569564905),

('task/tasks',2,'团队用户获取本团队的作业列表',NULL,'',1569564905,1569564905),

('task/top',2,'置顶某任务排序',NULL,'',1569564905,1569564905),

('template/copy',2,'复制模板',NULL,'',1569392043,1569392043),

('template/create',2,'新增模板',NULL,'',1569392043,1569392043),

('template/delete',2,'删除模板',NULL,'',1569392043,1569392043),

('template/detail',2,'模板详情',NULL,'',1569392043,1569392043),

('template/form',2,'模板表单',NULL,'',1569392043,1569392043),

('template/list',2,'模板列表',NULL,'',1569392043,1569392043),

('template/update',2,'修改模板',NULL,'',1569392043,1569392043),

('template/use',2,'使用模板',NULL,'',1569392043,1569392043),

('unpack/list',2,'文件解包列表',NULL,'',1571111702,1571111702),

('user/auth',2,'获取用户已授权的权限',NULL,'',1569226396,1569226396),

('user/create',2,'创建用户',NULL,'',1569226396,1569226396),

('user/delete',2,'删除用户',NULL,'',1569226396,1569226396),

('user/detail',2,'用户详情',NULL,'',1569226395,1569226395),

('user/devices',2,'用户设备列表',NULL,'',1569226396,1569226396),

('user/form',2,'用户表单',NULL,'',1569226396,1569226396),

('user/import-parse',2,'解析导入文件',NULL,'',1569226396,1569226396),

('user/import-submit',2,'提交导入数据',NULL,'',1569226396,1569226396),

('user/index',2,'用户首页',NULL,'',1569226395,1569226395),

('user/open-ftp',2,'开通ftp功能',NULL,'',1569226396,1569226396),

('user/records',2,'用户记录列表',NULL,'',1569226396,1569226396),

('user/send-email-code',2,'发送邮箱验证码',NULL,'',1569226396,1569226396),

('user/send-phone-code',2,'发送短信验证码',NULL,'',1569226396,1569226396),

('user/stat',2,'用户实时统计',NULL,'',1569226396,1569226396),

('user/update',2,'更改用户信息',NULL,'',1569226396,1569226396),

('user/update-email',2,'修改邮箱之验证',NULL,'',1569226396,1569226396),

('user/update-email-new',2,'修改邮箱',NULL,'',1569226396,1569226396),

('user/update-password',2,'修改密码之验证',NULL,'',1569226396,1569226396),

('user/update-password-new',2,'修改密码',NULL,'',1569226396,1569226396),

('user/update-phone',2,'修改手机号之验证',NULL,'',1569226396,1569226396),

('user/update-phone-new',2,'修改手机号',NULL,'',1569226396,1569226396),

('user/users',2,'获取全站用户列表',NULL,'',1569226395,1569226395),

('work/list',2,'工作列表',NULL,'',1570609709,1570609709),

('work/records',2,'工作列表',NULL,'',1570609709,1570609709),

('worker',1,'团队作业员',NULL,'',1569226395,1569226395);

/*Data for the table `auth_item_child` */

insert  into `auth_item_child`(`parent`,`child`) values 

('guest','data/list'),

('guest','message/detail'),

('guest','message/form'),

('guest','message/list'),

('guest','message/revoke'),

('guest','message/send'),

('guest','message/user-delete'),

('guest','message/user-messages'),

('guest','message/user-read'),

('guest','notice/create'),

('guest','notice/delete'),

('guest','notice/list'),

('guest','notice/update'),

('guest','project/detail'),

('guest','project/records'),

('guest','site/upload-private-file'),

('guest','site/upload-public-image'),

('guest','stat/export'),

('guest','stat/user'),

('guest','stat/user-stat-list'),

('guest','stat/work'),

('guest','stat/work-form'),

('guest','task/batch-execute'),

('guest','task/detail'),

('guest','task/execute'),

('guest','task/list'),

('guest','task/mark'),

('guest','task/mask'),

('guest','task/resource'),

('guest','task/resources'),

('guest','task/tasks'),

('guest','task/top'),

('guest','user/auth'),

('guest','user/delete'),

('guest','user/detail'),

('guest','user/form'),

('guest','user/index'),

('guest','user/send-email-code'),

('guest','user/send-phone-code'),

('guest','user/stat'),

('guest','user/update'),

('guest','user/update-email'),

('guest','user/update-email-new'),

('guest','user/update-password'),

('guest','user/update-password-new'),

('guest','user/update-phone'),

('guest','user/update-phone-new'),

('guest','user/users'),

('guest','work/list'),

('guest','work/records'),

('manager','data/list'),

('manager','group/create'),

('manager','group/delete'),

('manager','group/detail'),

('manager','group/form'),

('manager','group/groups'),

('manager','group/update'),

('manager','group/user-create'),

('manager','group/user-delete'),

('manager','group/users'),

('manager','message/detail'),

('manager','message/form'),

('manager','message/list'),

('manager','message/revoke'),

('manager','message/send'),

('manager','message/user-delete'),

('manager','message/user-messages'),

('manager','message/user-read'),

('manager','notice/create'),

('manager','notice/delete'),

('manager','notice/list'),

('manager','notice/update'),

('manager','pack/build'),

('manager','pack/dataset-list'),

('manager','pack/form'),

('manager','pack/get-ftp'),

('manager','pack/list'),

('manager','pack/renew'),

('manager','pack/stop'),

('manager','pack/top'),

('manager','project/assign-data'),

('manager','project/assign-team'),

('manager','project/continue'),

('manager','project/copy'),

('manager','project/create'),

('manager','project/delete'),

('manager','project/detail'),

('manager','project/finish'),

('manager','project/form'),

('manager','project/get-data'),

('manager','project/get-step'),

('manager','project/get-task'),

('manager','project/pause'),

('manager','project/projects'),

('manager','project/records'),

('manager','project/recovery'),

('manager','project/restart'),

('manager','project/set-step'),

('manager','project/set-task'),

('manager','project/stop'),

('manager','project/submit'),

('manager','setting/create'),

('manager','setting/delete'),

('manager','setting/list'),

('manager','setting/update'),

('manager','site/delete-private-file'),

('manager','site/upload-private-file'),

('manager','site/upload-public-image'),

('manager','stat/export'),

('manager','stat/operation-export'),

('manager','stat/task'),

('manager','stat/user'),

('manager','stat/user-stat-list'),

('manager','stat/work'),

('manager','stat/work-form'),

('manager','task/assign-user-list'),

('manager','task/assign-users'),

('manager','task/batch-execute'),

('manager','task/detail'),

('manager','task/execute'),

('manager','task/list'),

('manager','task/mark'),

('manager','task/mask'),

('manager','task/resource'),

('manager','task/resources'),

('manager','task/tasks'),

('manager','task/top'),

('manager','template/copy'),

('manager','template/create'),

('manager','template/delete'),

('manager','template/detail'),

('manager','template/form'),

('manager','template/list'),

('manager','template/update'),

('manager','template/use'),

('manager','unpack/list'),

('manager','user/auth'),

('manager','user/create'),

('manager','user/delete'),

('manager','user/detail'),

('manager','user/devices'),

('manager','user/form'),

('manager','user/import-parse'),

('manager','user/import-submit'),

('manager','user/index'),

('manager','user/open-ftp'),

('manager','user/records'),

('manager','user/send-email-code'),

('manager','user/send-phone-code'),

('manager','user/stat'),

('manager','user/update'),

('manager','user/update-email'),

('manager','user/update-email-new'),

('manager','user/update-password'),

('manager','user/update-password-new'),

('manager','user/update-phone'),

('manager','user/update-phone-new'),

('manager','user/users'),

('manager','work/list'),

('manager','work/records'),

('worker','data/list'),

('worker','message/detail'),

('worker','message/form'),

('worker','message/list'),

('worker','message/revoke'),

('worker','message/send'),

('worker','message/user-delete'),

('worker','message/user-messages'),

('worker','message/user-read'),

('worker','notice/create'),

('worker','notice/delete'),

('worker','notice/list'),

('worker','notice/update'),

('worker','project/detail'),

('worker','project/records'),

('worker','site/upload-private-file'),

('worker','site/upload-public-image'),

('worker','stat/export'),

('worker','stat/user'),

('worker','stat/user-stat-list'),

('worker','stat/work'),

('worker','stat/work-form'),

('worker','task/batch-execute'),

('worker','task/detail'),

('worker','task/execute'),

('worker','task/list'),

('worker','task/mark'),

('worker','task/mask'),

('worker','task/resource'),

('worker','task/resources'),

('worker','task/tasks'),

('worker','task/top'),

('worker','user/auth'),

('worker','user/delete'),

('worker','user/detail'),

('worker','user/form'),

('worker','user/index'),

('worker','user/send-email-code'),

('worker','user/send-phone-code'),

('worker','user/stat'),

('worker','user/update'),

('worker','user/update-email'),

('worker','user/update-email-new'),

('worker','user/update-password'),

('worker','user/update-password-new'),

('worker','user/update-phone'),

('worker','user/update-phone-new'),

('worker','user/users'),

('worker','work/list'),

('worker','work/records');

/*Data for the table `auth_rule` */

/*Data for the table `batch` */

/*Data for the table `category` */

insert  into `category`(`id`,`name`,`key`,`type`,`file_type`,`status`,`description_input`,`description_output`,`required_input_field`,`required_output_field`,`sort`,`view`,`icon`,`thumbnail`,`filter`,`draw_type`,`file_extensions`,`upload_file_extensions`,`video_as_frame`,`created_at`,`updated_at`) values 

(1,'picture_review','picture_review',0,0,0,'','','image_url','',10,'image_transcription','/images/category/icon-small/image-clean.png','/images/category/icon-big/image-clean.png',NULL,'','jpg,jpeg,png,bmp','xls,xlsx,csv,zip',1,0,1561428623),

(2,'image_overlays','image_overlays',0,0,0,'','','image_url','',11,'image_label','/images/category/icon-small/image-labelling.png','/images/category/icon-big/image-labelling.png',NULL,'','jpg,jpeg,png,bmp','xls,xlsx,csv,zip',1,0,1551428362),

(3,'text_review','text_review',0,2,0,'','','subject','',20,'text_analysis','/images/category/icon-small/text-clean.png','/images/category/icon-big/text-clean.png',NULL,'','txt','xls,xlsx,csv,zip,txt',0,0,1550561432),

(4,'text_overlays','text_overlays',0,2,0,NULL,NULL,'subject','',21,'text_annotation','/images/category/icon-small/text-labelling.png','/images/category/icon-big/text-labelling.png',NULL,'','txt','xls,xlsx,csv,zip,txt',0,1529656649,1550561440),

(5,'voice_audit','voice_audit',0,1,0,NULL,NULL,'voice_url','',30,'voice_classify','/images/category/icon-small/audio-clean.png','/images/category/icon-big/audio-clean.png',NULL,'','wav,mp3,v3,m4a','xls,xlsx,csv,zip',0,1530169302,1550116409),

(6,'voice_split','voice_split',0,1,0,'','','voice_url','',31,'voice_transcription','/images/category/icon-small/audio-labelling.png','/images/category/icon-big/audio-labelling.png',NULL,'','wav,mp3,v3,m4a','xls,xlsx,csv,zip',0,0,1550116418),

(7,'video_audit','video_audit',0,3,0,NULL,NULL,'video_url','',40,'video_classify','/images/category/icon-small/video-clean.png','/images/category/icon-big/video-clean.png',NULL,'rect','mp4','zip',0,1541130786,1550226045),

(9,'3d_point_cloud','3d_point_cloud',0,4,0,NULL,NULL,'3d_url','',50,'3d_pointcloud','/images/category/icon-small/3d.png','/images/category/icon-big/3d.png',NULL,'','pcd,jpg,jpeg,png,bmp,txt','zip,xls,xlsx,csv',0,1547098452,1552467972),

(10,'pointcloud_segment','pointcloud_segment',0,4,0,NULL,NULL,'3d_url','',0,'pointcloud_segment','/images/category/icon-small/icon-default-image@2x.png','/images/category/icon-big/icon-default-image@2x.png',NULL,'','jpg,jpeg,png,bmp,pcd','xls,csv,zip,xlsx',0,1562553075,1562555351),

(13,'video_segmentation','video_segmentation',0,3,0,NULL,NULL,'video_url','',0,'video_segmentation','/images/category/icon-small/icon-default-image@2x.png','/images/category/icon-big/icon-default-image@2x.png',NULL,'','mp4','zip',0,1565686651,1565768158);

/*Data for the table `data` */

/*Data for the table `data_result` */

/*Data for the table `group` */

/*Data for the table `group_user` */

/*Data for the table `message` */

/*Data for the table `message_to_user` */

/*Data for the table `notice` */

/*Data for the table `notice_to_position` */

/*Data for the table `pack` */

/*Data for the table `pack_script` */

insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('1','pack_script_common_JsonAndAllInOne','json(所有作业在一个json)','common/JsonAndAllInOne','0','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('2','pack_script_image_PascalVoc','pascal voc(只适用于图片标注)','image/PascalVoc','1','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('3','pack_script_image_CoCo','coco(通用json格式)','image/CoCo','1','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('5','pack_script_original_result','json(原始结果)','common/json','0','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('6','pack_script_mask_image','mask图(png)','image/MaskPng','1','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('7','pack_script_mark_image_with_tag','mark图有标签(jpg)','image/MarkJpgHasLabel','1','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('8','pack_script_mark_image_without_tag','mark图无标签(jpg)','image/MarkJpgNoLabel','1','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('9','pack_script_mark_image_fill','mark图且填充(jpg)','image/MarkJpgFill','1','0','0','1531384404','1531384404');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('10','pack_script_common_JsonAndOneToOne','Json(一图一json)','common/JsonAndOneToOne','0','0','0','0','0');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('11','pack_script_mark_image_fill_without_tag','mark图填充无标签','image/MarkJpgFillNoLabel','1','0','0','0','0');
-- insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('12','pack_script_common_JsonAndOneToOneForTheShow','Json(一图一json反显专用)','common/JsonAndOneToOneForTheShow','0','0','0','0','0');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('13','pack_script_mark_jpg_no_label_imagick','mark图无标签1.0','image/MarkJpgNoLabelImagick','1','0','0','0','0');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('14','pack_script_mask_png_v1','mask图(png)imagick版1.0','image/MaskPngV1','1','0','0','0','0');
insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('15','pack_script_image_text_yolo','YOLO（矩形框txt）','image/TxtYolo','1','0','0','0','0');
-- insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('16','pack_script_video_MarkJpgHasLabelFrameStack','mark图有标签(一帧一结果)','video/MarkJpgHasLabelFrameStack','4','0','0','0','0');
-- insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('17','pack_script_video_JsonFrameStack','json（2D一帧一结果）','video/JsonFrameStack','4','0','0','0','0');
-- insert into `pack_script` (`id`, `key`, `name`, `script`, `type`, `sort`, `status`, `created_at`, `updated_at`) values('18','pack_script_three_JsonFrameStack3D','json（3D一帧一结果）','three/JsonFrameStack3D','5','0','0','0','0');

/*Data for the table `project` */

/*Data for the table `project_attribute` */

/*Data for the table `project_record` */

/*Data for the table `setting` */

insert  into `setting`(`id`,`key`,`name`,`value`,`value_type`,`desc`,`can_delete`,`status`) values 

(12,'open_template_diy','开启自定义模板','1',0,'开启自定义模板',0,0),

(15,'site_logo','站点logo','/images/logo_full.png',2,'请设置标注平台的站点logo',1,0),

(16,'site_name','站点名称','倍赛标注系统',2,'请填写标注平台的站点名称',1,0),

(17,'site_favicon','站点小图标','/images/favicon.ico',2,'',1,0),

(18,'open_languages','开启语言','zh-CN,en',2,'',0,0);

/*Data for the table `stat` */

/*Data for the table `stat_result` */

/*Data for the table `stat_result_data` */

/*Data for the table `stat_result_user` */

/*Data for the table `stat_result_work` */

/*Data for the table `stat_user` */

/*Data for the table `step` */

/*Data for the table `step_group` */

/*Data for the table `task` */

/*Data for the table `task_user` */

/*Data for the table `template` */

insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('1','1','picture_review','0','0','1','1569482801','0','0','[{\"type\":\"show-text\",\"text\":\"<h4>\\n\\t<span><strong>• &nbsp;先判断图片是否清晰，然后分析图中人物表情</strong></span>\\n</h4>\\n<p>\\n\\t<span><span style=\\\"color:#999999;\\\">&nbsp; &nbsp; &nbsp; 如判断图片为不清晰，则无需标记。</span><span style=\\\"color:#999999;\\\"></span></span> \\n</p>\",\"id\":\"e60a4l7ss4m\"},{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"task-file-placeholder\",\"header\":\"图片文件占位符: \",\"tips\":\"\",\"id\":\"2dab4adb-35d2-4adb-8f13-4f759517aa9e\",\"anchor\":\"image_url\"},{\"type\":\"form-radio\",\"header\":\"请在下列选项中选择一个合适的选项: \",\"tips\":\"\",\"vertical\":false,\"data\":[{\"checked\":true,\"text\":\"选项一\"},{\"checked\":false,\"text\":\"选项二\"},{\"checked\":false,\"text\":\"选项三\"}],\"value\":\"选项一\",\"required\":false,\"id\":\"4b893011-dda3-4a48-bf04-0b7f7edb671c\",\"rules\":[]}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"form-radio\",\"header\":\"图的表情为：\",\"tips\":\"\",\"vertical\":false,\"data\":[{\"checked\":false,\"text\":\"悲伤\"},{\"checked\":false,\"text\":\"愤怒\"},{\"checked\":false,\"text\":\"开心\"},{\"checked\":false,\"text\":\"平静\"},{\"text\":\"hh\",\"checked\":false}],\"required\":false,\"id\":\"89jvtkvsf17\"}]},\"id\":\"77074949-25c5-4231-acba-f31beae52f91\",\"ratio\":3,\"scene\":\"edit\"}]','1505377292','1569484360');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('2','3','text_review','0','0','1','1','0','0','[{\"type\":\"show-text\",\"text\":\"<h4>\\n\\t<strong>• &nbsp;请先判断针对图中的转录内容是否准确。</strong>\\n</h4>\\n<p>\\n\\t<strong>&nbsp; </strong><span style=\\\"color:#999999;\\\">&nbsp; &nbsp;请仔细审核，精确到字。</span> \\n</p>\",\"id\":\"r7b55tpu13s\"},{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"text-file-placeholder\",\"header\":\"文本文件占位符: \",\"tips\":\"\",\"id\":\"4c22a38a-7d57-44f5-b524-de3104376448\",\"anchor\":\"text_url\"}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"show-text\",\"text\":\"<h4>\\n\\t<strong>转录内容</strong>\\n</h4>\\n<p>\\n\\t<span style=\\\"color:#999999;\\\">这是左侧文件中的一段转录的文字，此任务是需内容审核，需要操作员判断这段文字的对错。</span>\\n</p>\",\"id\":\"xs8v8scgay\"},{\"type\":\"form-radio\",\"header\":\"1. 请判断转录是否准确：\",\"tips\":\"\",\"vertical\":false,\"data\":[{\"checked\":true,\"text\":\"准确\"},{\"checked\":false,\"text\":\"不准确\"}],\"required\":false,\"id\":\"1x29uq4v66e\"},{\"type\":\"multi-input\",\"header\":\"2. 若转录不准确，请您进行修改\",\"tips\":\"转录部分可复制。\",\"required\":false,\"id\":\"b6xr0me5h88\"}]},\"id\":\"b95877ef-e1a5-49c4-b2a1-743015e42492\",\"ratio\":3,\"scene\":\"edit\"}]','1505378517','1533283975');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('3','2','image_overlays','0','0','1','1569481298','0','0','[{\"type\":\"show-text\",\"id\":\"faf9c4ff-4ece-4dd0-9eaf-2afa575b195d\",\"text\":\"<h4><strong>•&nbsp;请在清晰图片中，用矩形框标记出所有行人的朝向</strong></h4><p><strong>&nbsp;&nbsp;&nbsp;</strong><span style=\\\"color: rgb(153, 153, 153);\\\">&nbsp;&nbsp;如判断图片为不清晰，则无需标记。</span></p>\",\"scene\":\"edit\"},{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"task-file-placeholder\",\"header\":\"图片文件占位符: \",\"tips\":\"\",\"id\":\"6516ff92-952a-4d33-8a38-85a651558022\",\"anchor\":\"image_url\"},{\"type\":\"image-label-tool\",\"id\":\"8ae6092f-fc1a-43d1-b98e-42b64bf6165e\",\"supportShapeType\":[\"rect\",\"polygon\",\"trapezoid\",\"point\",\"bonepoint\",\"cuboid\",\"triangle\",\"quadrangle\",\"unclosedpolygon\",\"line\",\"closedcurve\",\"pencilline\",\"splinecurve\"],\"advanceTool\":[]}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"tag\",\"header\":\"请选择合适的标签：\",\"tips\":\"\",\"subType\":\"single\",\"data\":[{\"text\":\"正面\",\"shortValue\":\"\",\"color\":\"#13C2C2\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"背面\",\"shortValue\":\"\",\"color\":\"#F14B2A\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0}],\"tagIsRequired\":0,\"tagIsUnique\":0,\"pointDistanceMin\":0,\"pointPositionNoLimit\":false,\"deepLevel\":1,\"id\":\"024b2ca0-4fd9-4a8d-9f9b-f9ec090d9838\",\"tagIsSearchAble\":true,\"pointTagShapeType\":[],\"tagGroupOpen\":false,\"tagLayoutType\":\"list\"}]},\"id\":\"1213d376-3fe3-41e8-a5b0-04eaf8a55d5e\",\"ratio\":3,\"scene\":\"edit\"}]','1505382421','1569481298');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('5','4','text_overlays','0','0','1','1551770788','0','0','[{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"text-file-placeholder\",\"header\":\"文本文件占位符: \",\"tips\":\"\",\"id\":\"f1ba05e0-84af-4298-8408-44f0635be530\",\"anchor\":\"text_url\"}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"tag\",\"header\":\"类型选择: \",\"tips\":\"为标注对象选择所属类型\",\"subType\":\"single\",\"data\":[{\"text\":\"冰箱\",\"shortValue\":\"\",\"color\":\"#13C2C2\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"香蕉\",\"shortValue\":\"\",\"color\":\"#13C2C2\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"火腿\",\"shortValue\":\"\",\"color\":\"#13C2C2\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0}],\"tagIsRequired\":0,\"tagIsUnique\":0,\"pointDistanceMin\":0,\"pointPositionNoLimit\":false,\"deepLevel\":1,\"id\":\"1415df32-d7cc-430c-a2b5-734834548e16\",\"tagIsSearchAble\":true,\"pointTagShapeType\":[],\"tagGroupOpen\":false,\"tagLayoutType\":\"list\",\"defaultColor\":\"#13C2C2\"}]},\"id\":\"b941c4dc-2e22-4a87-a354-1080e6860134\",\"ratio\":3,\"scene\":\"edit\"}]','1529656725','1551770788');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('6','5','voice_audit','0','0','1','0','0','0','[{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"audio-file-placeholder\",\"header\":\"音频文件占位符: \",\"tips\":\"\",\"id\":\"a6e68e80-fa7b-48bd-9e31-5994c731200a\",\"anchor\":\"audio_url\"}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"form-radio\",\"header\":\"描述的内容是否与金融有关\",\"tips\":\"\",\"vertical\":false,\"data\":[{\"checked\":false,\"text\":\"无关\"},{\"checked\":true,\"text\":\"有关\"}],\"value\":\"有关\",\"required\":false,\"id\":\"6618ef6a-6e08-415d-b778-efc2c95c3e9a\",\"rules\":[]}]},\"id\":\"8ec78729-1665-4814-b8c0-819b2ad36f4f\",\"ratio\":3,\"scene\":\"edit\"}]','1530169380','0');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('7','7','video_audit','0','0','1','0','0','0','[{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"video-file-placeholder\",\"header\":\"视频文件占位符: \",\"tips\":\"\",\"id\":\"00293c31-82e7-4f14-8c9a-9fb16d366e58\",\"anchor\":\"video_url\"}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"tag\",\"header\":\"类型选择: \",\"tips\":\"为标注对象选择所属类型\",\"subType\":\"single\",\"data\":[{\"text\":\"人1\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"人2\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"人3\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0}],\"tagIsRequired\":0,\"tagIsUnique\":0,\"pointDistanceMin\":0,\"pointPositionNoLimit\":0,\"tagGroupLock\":false,\"deepLevel\":1,\"id\":\"a8929626-b699-43f8-987d-98d19f049714\"}]},\"id\":\"38ab5842-4ded-42ba-b80c-ac6ae6c8bcad\",\"ratio\":3,\"scene\":\"edit\"}]','1532922568','1533713408');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('8','6','voice_split','0','0','1','0','0','0','[{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"audio-file-placeholder\",\"header\":\"\",\"tips\":\"\",\"id\":\"774565ec-0c3e-4ad8-b86f-d30a98f67536\",\"anchor\":\"audio_url\"}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"form-radio\",\"header\":\"音频是否有效\",\"tips\":\"\",\"vertical\":false,\"data\":[{\"checked\":true,\"text\":\"有效\"},{\"checked\":false,\"text\":\"无效\"}],\"value\":\"有效\",\"required\":false,\"id\":\"f36477bc-bf38-4173-bf46-b6cf7695e39f\",\"rules\":[]}]},\"id\":\"5831d88c-ed2d-4b26-96b0-2388ec8e6a6c\",\"ratio\":3,\"scene\":\"edit\"}]','1541130556','0');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('10','9','pointcloud_overlays','0','0','1','1583739104','0','0','[{\"type\":\"layout\",\"column0\":{\"span\":20,\"children\":[{\"type\":\"task-file-placeholder\",\"header\":\"\",\"tips\":\"\",\"id\":\"ccb33b45-ad74-4be6-93f7-f72c5deabf03\",\"anchor\":\"image_url\"}]},\"column1\":{\"span\":4,\"children\":[{\"type\":\"tag\",\"header\":\"\",\"tips\":\"\",\"subType\":\"single\",\"defaultColor\":\"#ffff00\",\"data\":[{\"text\":\"Car\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"minDepth\":0,\"maxDepth\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"Pedestrian\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"minDepth\":0,\"maxDepth\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"truck\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"minDepth\":0,\"maxDepth\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"bicycle\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"minDepth\":0,\"maxDepth\":0,\"exampleImageSrc\":\"\",\"isRequired\":0}],\"tagIsRequired\":0,\"tagIsUnique\":0,\"pointDistanceMin\":0,\"pointPositionNoLimit\":false,\"pointTagShapeType\":[],\"tagGroupLock\":false,\"tagGroupOpen\":false,\"tagIsSearchAble\":true,\"deepLevel\":1,\"id\":\"5eb5b876-f003-4ff3-af80-7d565ced8db2\",\"scene\":\"edit\",\"tagLayoutType\":\"list\"}]},\"id\":\"8e4dfed0-19c1-45b7-b9e9-7cc2f61fb728\",\"ratio\":5,\"scene\":\"edit\"}]','1583739089','1583739104');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('11','13','video_segmentation','0','0','1','1578475058','0','0','[{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"task-file-placeholder\",\"header\":\"\",\"tips\":\"\",\"id\":\"f1b88965-f61b-45ab-8c20-9dadfc81b837\",\"anchor\":\"video_url\"}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"show-text\",\"id\":\"45a9a239-f908-43e1-8807-ae4b40473f19\",\"text\":\"<p><strong>需求描述：</strong></p><p>请给视频中人物分时间段打上相应标签</p>\"},{\"type\":\"tag\",\"header\":\"类型选择: \",\"tips\":\"\",\"subType\":\"single\",\"defaultColor\":\"#ffff00\",\"data\":[{\"text\":\"A\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"B\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"exampleImageSrc\":\"\",\"isRequired\":0}],\"tagIsUnique\":0,\"pointDistanceMin\":0,\"pointPositionNoLimit\":false,\"pointTagShapeType\":[],\"tagGroupLock\":false,\"tagGroupOpen\":false,\"tagIsSearchAble\":true,\"deepLevel\":1,\"id\":\"34c3c881-97e4-4d49-ae58-43134154c034\",\"tagLayoutType\":\"list\",\"tagIsRequired\":0},{\"type\":\"single-input\",\"header\":\"请对您看到的图片进行一个描述:\",\"tips\":\"\",\"required\":true,\"id\":\"8b244a81-a44c-42fb-a04e-65ac8caa5302\",\"rules\":[],\"value\":\"\",\"placeholder\":\"\"},{\"type\":\"ocr\",\"header\":\"OCR\",\"tips\":\"\",\"id\":\"28a07a09-f8ae-469a-8c08-157488dbf6b8\"}]},\"id\":\"e70ae489-a539-47bc-ba5a-892569f66470\",\"ratio\":3,\"scene\":\"edit\"}]','1566972157','1578475058');
insert into `template` (`id`, `category_id`, `name`, `parent_id`, `project_id`, `user_id`, `sort`, `status`, `type`, `config`, `created_at`, `updated_at`) values('12','10','pointcloud_segment','0','0','1','1589446842','0','0','[{\"type\":\"layout\",\"column0\":{\"span\":18,\"children\":[{\"type\":\"task-file-placeholder\",\"header\":\"\",\"tips\":\"\",\"id\":\"945e9ff7-8715-4503-9dfc-9b3e51efd66a\",\"anchor\":\"3d_url\"}]},\"column1\":{\"span\":6,\"children\":[{\"type\":\"tag\",\"header\":\"\",\"tips\":\"\",\"subType\":\"single\",\"defaultColor\":\"#ffff00\",\"data\":[{\"text\":\"Car\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"minDepth\":0,\"maxDepth\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"Person\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"minDepth\":0,\"maxDepth\":0,\"exampleImageSrc\":\"\",\"isRequired\":0},{\"text\":\"Sky\",\"shortValue\":\"\",\"color\":\"#ffff00\",\"minWidth\":0,\"minHeight\":0,\"maxWidth\":0,\"maxHeight\":0,\"minDepth\":0,\"maxDepth\":0,\"exampleImageSrc\":\"\",\"isRequired\":0}],\"tagIsRequired\":0,\"tagIsUnique\":0,\"pointDistanceMin\":0,\"pointPositionNoLimit\":false,\"pointTagShapeType\":[],\"tagGroupLock\":false,\"tagGroupOpen\":false,\"tagIsSearchAble\":true,\"deepLevel\":1,\"id\":\"1b09af11-c8a0-415b-9277-7a1f7a458677\",\"tagLayoutType\":\"list\",\"randomColorType\":\"0\",\"randomColor\":\"dark\"}]},\"id\":\"00fa558e-443c-4479-86e3-925e29a54701\",\"ratio\":3,\"scene\":\"edit\"}]','1589446842','1589446842');

/*Data for the table `unpack` */

/*Data for the table `user` */

insert  into `user`(`id`,`username`,`realname`,`nickname`,`email`,`phone`,`auth_key`,`password_hash`,`payment_password`,`access_token`,`is_verify_email`,`is_verify_mobile`,`avatar`,`status`,`company_name`,`sex`,`city`,`province`,`country`,`position`,`language`,`created_by`,`created_at`,`updated_at`) values 

(1,'admin','admin','admin','admin@kb.com.cn','','WwZ0esExGGl9Y-S8kYoNgYsglqxSGYHD','$2y$13$yWWT5MzHq4TsYtCoFQVe4Osu3wzttRy0djWOtD1X9HSx4hxDEY8LW','','TXhYQ2tZb1BFNWdxQ2FRQzZqNnFmNXItT21LMy1lVkZ8fHwxfHx8MTkyLjE2OC4xLjE5fHx8MTU3MTYyNzgxNQ_c_c',0,0,'/upload/2019/10/17/5da80c2a10a84_1571294250.jpeg',1,'',1,'','','','',0,0,0,1571974484);

/*Data for the table `user_attribute` */

insert  into `user_attribute`(`user_id`,`register_description`,`register_files`,`address`) values 

(1,'','','');

/*Data for the table `user_device` */

/*Data for the table `user_ftp` */

/*Data for the table `user_record` */

/*Data for the table `user_stat` */

/*Data for the table `work` */

/*Data for the table `work_record` */

/*Data for the table `work_result` */

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
