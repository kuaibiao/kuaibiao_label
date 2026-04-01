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

/*Table structure for table `app` */

DROP TABLE IF EXISTS `app`;

CREATE TABLE `app` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `app_key` varchar(64) NOT NULL DEFAULT '',
  `app_version` varchar(64) NOT NULL DEFAULT '',
  `platform` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '平台',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `limit_login_ip` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否限制ip,0否1是',
  `limit_login_useragent` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '限制用户登录设备,0否1是',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `search` (`app_key`,`app_version`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='(对外授权的)应用表';

/*Table structure for table `app_record` */

DROP TABLE IF EXISTS `app_record`;

CREATE TABLE `app_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ip` char(15) NOT NULL DEFAULT '',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='应用记录表';

/*Table structure for table `app_stat` */

DROP TABLE IF EXISTS `app_stat`;

CREATE TABLE `app_stat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(11) unsigned NOT NULL DEFAULT '0',
  `date` date NOT NULL DEFAULT '1970-01-01',
  `count` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `search` (`app_id`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COMMENT='app统计表';

/*Table structure for table `auth_assignment` */

DROP TABLE IF EXISTS `auth_assignment`;

CREATE TABLE `auth_assignment` (
  `item_name` varchar(64) NOT NULL,
  `user_id` varchar(64) NOT NULL,
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_name`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Table structure for table `auth_item` */

DROP TABLE IF EXISTS `auth_item`;

CREATE TABLE `auth_item` (
  `name` varchar(64) NOT NULL,
  `type` smallint(6) unsigned NOT NULL DEFAULT '0',
  `description` varchar(254) NOT NULL DEFAULT '',
  `rule_name` varchar(64) DEFAULT NULL,
  `data` blob,
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`),
  KEY `rule_name` (`rule_name`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Table structure for table `auth_item_child` */

DROP TABLE IF EXISTS `auth_item_child`;

CREATE TABLE `auth_item_child` (
  `parent` varchar(64) NOT NULL DEFAULT '',
  `child` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`parent`,`child`),
  KEY `child` (`child`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Table structure for table `auth_rule` */

DROP TABLE IF EXISTS `auth_rule`;

CREATE TABLE `auth_rule` (
  `name` varchar(64) NOT NULL,
  `data` blob,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Table structure for table `batch` */

DROP TABLE IF EXISTS `batch`;

CREATE TABLE `batch` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(254) NOT NULL DEFAULT '' COMMENT '批次名',
  `path` varchar(254) NOT NULL DEFAULT '' COMMENT '原始文件的路径',
  `amount` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sort` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=453 DEFAULT CHARSET=utf8mb4 COMMENT='批次表';

/*Table structure for table `category` */

DROP TABLE IF EXISTS `category`;

CREATE TABLE `category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL COMMENT '分类名称',
  `key` varchar(50) NOT NULL COMMENT '分类key',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类别0标注类,1采集类',
  `file_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '文件类型',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `description_input` text COMMENT '输入描述',
  `description_output` text COMMENT '输出描述',
  `required_input_field` varchar(254) NOT NULL DEFAULT '' COMMENT '必须输入字段,中间用逗号隔开',
  `required_output_field` varchar(254) NOT NULL DEFAULT '' COMMENT '必须输出字段,中间用逗号隔开',
  `sort` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `view` varchar(32) NOT NULL DEFAULT '' COMMENT '视图',
  `icon` varchar(254) NOT NULL DEFAULT '' COMMENT 'icon url',
  `thumbnail` varchar(254) NOT NULL DEFAULT '' COMMENT '缩略图',
  `filter` text COMMENT '过滤条件,json',
  `draw_type` varchar(254) NOT NULL DEFAULT '' COMMENT '绘制类型,point,line,rect,polygon',
  `file_extensions` varchar(254) NOT NULL DEFAULT '' COMMENT '文件后缀,多个时用逗号隔开;如jpg,mp4等',
  `upload_file_extensions` varchar(254) NOT NULL DEFAULT '' COMMENT '上传文件后缀,多个时用逗号隔开;如csv,zip等',
  `video_as_frame` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '视频类是否抽帧处理',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COMMENT='分类表';

/*Table structure for table `data` */

DROP TABLE IF EXISTS `data`;

CREATE TABLE `data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '图片名等,用于搜索',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fn` (`name`),
  KEY `tf` (`project_id`,`batch_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作业表';

/*Table structure for table `data_result` */

DROP TABLE IF EXISTS `data_result`;

CREATE TABLE `data_result` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data` text COMMENT '数据源',
  `result` mediumtext COMMENT '客户提供的结果,json',
  `ai_result` mediumtext COMMENT 'ai提供的结果,json',
  `ai_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pb` (`project_id`,`batch_id`),
  KEY `data_id` (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='作业结果表';

/*Table structure for table `group` */

DROP TABLE IF EXISTS `group`;

CREATE TABLE `group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `count` int(11) DEFAULT '0' COMMENT '用户数量',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '小组状态：1正常2删除',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COMMENT='用户小组';

/*Table structure for table `group_user` */

DROP TABLE IF EXISTS `group_user`;

CREATE TABLE `group_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '用户id',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '小组id',
  `created_at` int(11) DEFAULT '0',
  `updated_at` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `message` */

DROP TABLE IF EXISTS `message`;

CREATE TABLE `message` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布通知的人',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态,0正常,1删除',
  `read_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户读取数',
  `content` text,
  `link_word` varchar(64) NOT NULL DEFAULT '' COMMENT '链接文字',
  `link_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '链接类型',
  `link_attribute` varchar(128) NOT NULL DEFAULT '' COMMENT '链接属性',
  `table_suffix` varchar(32) NOT NULL DEFAULT '' COMMENT '数据表后缀,按月分表如1803',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='消息(私信)表';

/*Table structure for table `message_to_user` */

DROP TABLE IF EXISTS `message_to_user`;

CREATE TABLE `message_to_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '接收者;0为全平台',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user` (`user_id`) USING BTREE,
  KEY `message` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='消息和用户关系表';

/*Table structure for table `notice` */

DROP TABLE IF EXISTS `notice`;

CREATE TABLE `notice` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布人',
  `title` varchar(254) NOT NULL DEFAULT '',
  `content` text,
  `link` varchar(254) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `read_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已读数',
  `show_start_time` int(11) unsigned NOT NULL DEFAULT '0',
  `show_end_time` int(11) unsigned NOT NULL DEFAULT '0',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ts` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通知(公告)表';

/*Table structure for table `notice_to_position` */

DROP TABLE IF EXISTS `notice_to_position`;

CREATE TABLE `notice_to_position` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `notice_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `position` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '与notice.status一致,冗余设计',
  `read_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已读数',
  `show_start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '与notice.show_start_time一致,冗余设计',
  `show_end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '与notice.show_end_time一致,冗余设计',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告栏';

/*Table structure for table `pack` */

DROP TABLE IF EXISTS `pack`;

CREATE TABLE `pack` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `step_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分步ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '类型;弃用',
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  `pack_script_id` int(11) unsigned NOT NULL DEFAULT '0',
  `check_file` varchar(254) NOT NULL DEFAULT '' COMMENT '生成的检测文件',
  `pack_file` varchar(254) NOT NULL DEFAULT '' COMMENT '打包文件',
  `pack_pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '进程id',
  `pack_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '解包状态,0默认1等待执行,2解包中3成功4失败',
  `pack_message` varchar(254) NOT NULL DEFAULT '' COMMENT '打包的错误信息',
  `pack_start_time` int(11) unsigned NOT NULL DEFAULT '0',
  `pack_end_time` int(11) unsigned NOT NULL DEFAULT '0',
  `pack_item_total` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '打包的作业总数',
  `pack_item_succ` int(11) unsigned NOT NULL DEFAULT '0',
  `pack_item_fail` int(11) unsigned NOT NULL DEFAULT '0',
  `extension` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '格式;弃用',
  `configs` text COMMENT '配置项,json',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COMMENT='打包表';

/*Table structure for table `pack_script` */

DROP TABLE IF EXISTS `pack_script`;

CREATE TABLE `pack_script` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `key` varchar(128) NOT NULL DEFAULT '' COMMENT '翻译脚本名称时的key值',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '脚本名',
  `script` varchar(255) NOT NULL DEFAULT '' COMMENT '脚本地址',
  `type` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '支持的项目分类;0不限,1图片2语音3文本',
  `sort` int(3) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '状态：0正常1隐藏',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='项目导出脚本表';

/*Table structure for table `project` */

DROP TABLE IF EXISTS `project`;

CREATE TABLE `project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '' COMMENT '项目名称',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分类',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自定义的模板',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态,详见project模型',
  `amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '总量',
  `data_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '作业量',
  `disk_space` decimal(11,3) unsigned NOT NULL DEFAULT '0.000' COMMENT '占用空间,单位M',
  `table_suffix` varchar(32) NOT NULL DEFAULT '' COMMENT '数据表后缀,按月分表如1803',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发布时间',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '截止时间',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=789 DEFAULT CHARSET=utf8mb4 COMMENT='项目表';

/*Table structure for table `project_attribute` */

DROP TABLE IF EXISTS `project_attribute`;

CREATE TABLE `project_attribute` (
  `project_id` int(11) unsigned NOT NULL,
  `description` text COMMENT '项目描述',
  `attachment` text COMMENT '附件,json格式',
  `fields` varchar(254) NOT NULL DEFAULT '' COMMENT '数据字段,中间用逗号隔开',
  `uploadfile_type` varchar(32) NOT NULL DEFAULT '' COMMENT '上传数据文件方式',
  `uploadfile_account` varchar(254) NOT NULL DEFAULT '' COMMENT '文件上传网盘账户',
  `uploadfiles` text COMMENT '上传文件信息',
  `batch_config` mediumtext COMMENT '批次配置文件json格式',
  PRIMARY KEY (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目属性表';

/*Table structure for table `project_record` */

DROP TABLE IF EXISTS `project_record`;

CREATE TABLE `project_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `scene` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ip` char(15) NOT NULL DEFAULT '',
  `message` text COMMENT '描述信息',
  `message_config` text COMMENT '信息配置,json',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='项目记录表';

/*Table structure for table `setting` */

DROP TABLE IF EXISTS `setting`;

CREATE TABLE `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `value` varchar(254) NOT NULL DEFAULT '',
  `value_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '值类型,0bool1num2string',
  `desc` varchar(254) NOT NULL DEFAULT '',
  `can_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否可以删除;0不可删;1可删',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态;0启用;1禁用',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `stat` */

DROP TABLE IF EXISTS `stat`;

CREATE TABLE `stat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `step_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `amount` int(11) unsigned NOT NULL DEFAULT '0',
  `work_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '作业时长',
  `work_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '作业张数',
  `submit_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交次数',
  `timeout_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '超时数',
  `allow_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核通过张数',
  `refuse_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核驳回次数',
  `refuse_revised_count` int(11) NOT NULL DEFAULT '0' COMMENT '审核驳回修正次数',
  `refuse_revise_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '返工作业修正次数',
  `refuse_receive_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核驳回领取数',
  `reset_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核重置次数',
  `audited_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被审核张数',
  `allowed_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被审核通过张数',
  `reseted_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被审核重置次数',
  `other_operated_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '其他被操作数, 如回退到父工序数',
  `refused_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被审核驳回数',
  `refused_revise_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被驳回修正数',
  `difficult_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '疑难作业次数',
  `difficult_revise_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被修正的疑难作业次数',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`) USING BTREE,
  KEY `task_id` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1066 DEFAULT CHARSET=utf8mb4 COMMENT='任务统计表';

/*Table structure for table `stat_result` */

DROP TABLE IF EXISTS `stat_result`;

CREATE TABLE `stat_result` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型,rect,point..',
  `action` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '动作,add,edit,del,allow,refuse,reset,allowed,refused,reseted,forcerefused,parentforcerefused,forcereseted,parentforcereseted,redo',
  `value` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务结果统计表';

/*Table structure for table `stat_result_data` */

DROP TABLE IF EXISTS `stat_result_data`;

CREATE TABLE `stat_result_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型,rect,point..',
  `action` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '动作,add,edit,del,allow,refuse,reset,allowed,refused,reseted,forcerefused,parentforcerefused,forcereseted,parentforcereseted,redo',
  `value` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务结果用户统计表';

/*Table structure for table `stat_result_user` */

DROP TABLE IF EXISTS `stat_result_user`;

CREATE TABLE `stat_result_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型,rect,point..',
  `action` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '动作,add,edit,del,allow,refuse,reset,allowed,refused,reseted,forcerefused,parentforcerefused,forcereseted,parentforcereseted,redo',
  `value` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ptu` (`project_id`,`task_id`,`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务结果用户统计表';

/*Table structure for table `stat_result_work` */

DROP TABLE IF EXISTS `stat_result_work`;

CREATE TABLE `stat_result_work` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data_id` int(11) unsigned NOT NULL DEFAULT '0',
  `work_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型,rect,point..',
  `action` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '动作,add,edit,del,allow,refuse,reset,allowed,refused,reseted,forcerefused,parentforcerefused,forcereseted,parentforcereseted,redo',
  `value` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ptw` (`project_id`,`task_id`,`work_id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COMMENT='任务结果用户统计表';

/*Table structure for table `stat_user` */

DROP TABLE IF EXISTS `stat_user`;

CREATE TABLE `stat_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `step_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `work_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '作业时长',
  `work_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '作业张数',
  `submit_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交次数',
  `join_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '参与作业数',
  `timeout_count` int(11) unsigned NOT NULL DEFAULT '0',
  `allow_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核通过张数',
  `refuse_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核驳回次数',
  `refuse_revised_count` int(11) NOT NULL DEFAULT '0' COMMENT '审核驳回修正次数',
  `refuse_revise_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '返工作业修正次数',
  `refuse_receive_count` int(11) NOT NULL DEFAULT '0' COMMENT '审核驳回领取数',
  `reset_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '审核重置次数',
  `audited_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被审核张数',
  `allowed_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被审核通过张数',
  `refused_count` int(11) unsigned NOT NULL DEFAULT '0',
  `reseted_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被审核重置次数',
  `other_operated_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '其他被操作数, 如回退到父工序数',
  `refused_revise_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '驳回修改作业数',
  `difficult_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '疑难作业数',
  `difficult_revise_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '被修正的疑难作业数',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `task_id` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=611 DEFAULT CHARSET=utf8mb4 COMMENT='任务用户统计表';

/*Table structure for table `step` */

DROP TABLE IF EXISTS `step`;

CREATE TABLE `step` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `step_group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(254) NOT NULL DEFAULT '',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型,0生产1审核',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sort` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0',
  `description` text COMMENT '描述',
  `condition` text COMMENT '执行条件',
  `is_load_result` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否加载原始数据结果;即dataresult.result的值',
  `ai_model_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '辅助模型',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1993 DEFAULT CHARSET=utf8mb4 COMMENT='分步表';

/*Table structure for table `step_group` */

DROP TABLE IF EXISTS `step_group`;

CREATE TABLE `step_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(60) NOT NULL DEFAULT '',
  `sort` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父工序组id',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0',
  `desc` varchar(254) NOT NULL DEFAULT '' COMMENT '备注',
  `condition` text,
  `is_load_result` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否加载最终结果',
  `execute_times` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `audit_times` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1993 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `task` */

DROP TABLE IF EXISTS `task`;

CREATE TABLE `task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `step_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型,0正式,1试标',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `sort` int(11) unsigned NOT NULL DEFAULT '0',
  `is_top` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否置顶,0非1是,置顶后变更sort的值',
  `name` varchar(254) NOT NULL DEFAULT '' COMMENT '任务名称',
  `amount` int(11) unsigned NOT NULL DEFAULT '0',
  `user_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '分配的用户数',
  `description` varchar(254) NOT NULL DEFAULT '',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0',
  `receive_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '领取张数',
  `receive_expire` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '领取过期时间',
  `max_times` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '最大执行次数',
  `unit_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '单价',
  `unit_price_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '计价单位',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1306 DEFAULT CHARSET=utf8mb4 COMMENT='任务表';

/*Table structure for table `task_user` */

DROP TABLE IF EXISTS `task_user`;

CREATE TABLE `task_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id,0表所有',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `priority` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '优先级',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `tu` (`task_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43054 DEFAULT CHARSET=utf8mb4 COMMENT='任务人员表';

/*Table structure for table `template` */

DROP TABLE IF EXISTS `template`;

CREATE TABLE `template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '类别id',
  `name` varchar(254) NOT NULL DEFAULT '' COMMENT '模板名称',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '原模板,用户根据原模板进行自定义',
  `project_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时的任务ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型,0公共1私有',
  `config` mediumtext COMMENT '配置数据,json',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=395 DEFAULT CHARSET=utf8mb4 COMMENT='任务模板表';

/*Table structure for table `unpack` */

DROP TABLE IF EXISTS `unpack`;

CREATE TABLE `unpack` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(254) NOT NULL DEFAULT '' COMMENT '文件包名',
  `filepath` varchar(254) NOT NULL DEFAULT '' COMMENT '文件存储路径',
  `filesize` varchar(254) NOT NULL DEFAULT '' COMMENT '文件大小,单位b',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `unpack_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '解包状态,0默认1等待执行,2解包中3成功4失败',
  `unpack_filepath` varchar(254) NOT NULL DEFAULT '' COMMENT '解包后的文件夹路径',
  `unpack_message` text COMMENT '解包的错误信息',
  `unpack_start_time` int(11) unsigned NOT NULL DEFAULT '0',
  `unpack_end_time` int(11) unsigned NOT NULL DEFAULT '0',
  `unpack_progress` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '进度,0-100',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `unpack` (`unpack_status`,`id`) USING BTREE,
  KEY `project` (`project_id`,`status`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=450 DEFAULT CHARSET=utf8mb4 COMMENT='解包表';

/*Table structure for table `user` */

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '',
  `realname` varchar(64) NOT NULL DEFAULT '' COMMENT '姓名',
  `nickname` varchar(64) NOT NULL DEFAULT '' COMMENT '昵称',
  `email` varchar(64) NOT NULL DEFAULT '',
  `phone` varchar(64) NOT NULL DEFAULT '' COMMENT '电话',
  `auth_key` varchar(32) NOT NULL DEFAULT '' COMMENT 'cookie验证用户身份',
  `password_hash` varchar(64) NOT NULL DEFAULT '' COMMENT '密码的hash值',
  `payment_password` varchar(64) NOT NULL DEFAULT '' COMMENT '支付密码',
  `access_token` varchar(254) NOT NULL DEFAULT '' COMMENT 'api验证用户身份',
  `is_verify_email` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证邮箱0否1是',
  `is_verify_mobile` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否验证手机0否1是',
  `avatar` varchar(254) NOT NULL DEFAULT '',
  `status` smallint(6) unsigned NOT NULL DEFAULT '0',
  `company_name` varchar(64) NOT NULL DEFAULT '' COMMENT '公司名',
  `sex` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `city` varchar(64) NOT NULL DEFAULT '',
  `province` varchar(64) NOT NULL DEFAULT '',
  `country` varchar(64) NOT NULL DEFAULT '',
  `position` varchar(128) NOT NULL DEFAULT '' COMMENT '位置,格式:省份-城市-县区',
  `language` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '语言,0中文,1英文',
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建人ID',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=398 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `user_attribute` */

DROP TABLE IF EXISTS `user_attribute`;

CREATE TABLE `user_attribute` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `register_description` text COMMENT '注册时需求说明',
  `register_files` text COMMENT '注册时附件,json格式',
  `address` varchar(254) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户属性表';

/*Table structure for table `user_device` */

DROP TABLE IF EXISTS `user_device`;

CREATE TABLE `user_device` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `device_name` varchar(64) NOT NULL DEFAULT '' COMMENT '设备名,如iphone x',
  `device_number` varchar(64) NOT NULL DEFAULT '' COMMENT '设备号,设备唯一标志,uuid',
  `device_token` varchar(64) NOT NULL DEFAULT '' COMMENT '设备token,ios推送用,不唯一会变化',
  `app_key` varchar(64) NOT NULL DEFAULT '',
  `app_version` varchar(64) NOT NULL DEFAULT '' COMMENT '应用版本号',
  `request_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '请求次数',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COMMENT='用户设备表';

/*Table structure for table `user_ftp` */

DROP TABLE IF EXISTS `user_ftp`;

CREATE TABLE `user_ftp` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ftp_host` varchar(64) NOT NULL DEFAULT '',
  `ftp_username` varchar(64) NOT NULL DEFAULT '',
  `ftp_password` varchar(64) NOT NULL DEFAULT '',
  `ftp_home` varchar(120) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ftp_username` (`ftp_username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户ftp表';

/*Table structure for table `user_record` */

DROP TABLE IF EXISTS `user_record`;

CREATE TABLE `user_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `event` varchar(64) NOT NULL DEFAULT '' COMMENT '事件,如登录,注册',
  `ip` char(15) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `created_by` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人的ID',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=889 DEFAULT CHARSET=utf8mb4;

/*Table structure for table `user_stat` */

DROP TABLE IF EXISTS `user_stat`;

CREATE TABLE `user_stat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0',
  `new_message_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '未读消息数',
  `login_last_platform` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '平台,0pc,1ios,2adr',
  `login_last_device_id` int(11) unsigned NOT NULL DEFAULT '0',
  `login_last_time` int(11) unsigned NOT NULL DEFAULT '0',
  `login_last_ip` char(15) NOT NULL DEFAULT '',
  `login_last_useragent` varchar(254) NOT NULL DEFAULT '' COMMENT '头信息里的user-agent',
  `request_count` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=353 DEFAULT CHARSET=utf8mb4 COMMENT='用户状态表';

/*Table structure for table `work` */

DROP TABLE IF EXISTS `work`;

CREATE TABLE `work` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `step_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0有效,1无效',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型:0未完成,1被驳回,2问题作业',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '执行者ID或审核者ID',
  `submit_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提交次数',
  `start_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工人开工时间',
  `end_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '工人结束时间',
  `delay_times` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '延迟次数',
  `delay_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '延迟时间,单位秒',
  `is_effective` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否有效, 用于条件类',
  `is_refused` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '驳回作业',
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `correct_rate` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '准确率;0-99',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `timeout` (`project_id`,`status`,`start_time`,`delay_time`) USING BTREE,
  KEY `userwork` (`project_id`,`user_id`,`status`) USING BTREE,
  KEY `task` (`batch_id`,`step_id`,`data_id`) USING BTREE,
  KEY `data` (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作表';

/*Table structure for table `work_record` */

DROP TABLE IF EXISTS `work_record`;

CREATE TABLE `work_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data_id` int(11) unsigned NOT NULL DEFAULT '0',
  `batch_id` int(11) unsigned NOT NULL DEFAULT '0',
  `step_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `work_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `after_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作后用户',
  `after_work_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '操作后作业状态',
  `before_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作前用户',
  `before_work_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '操作前作业状态',
  `created_at` int(11) unsigned NOT NULL DEFAULT '0',
  `updated_at` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `data_id` (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*Table structure for table `work_result` */

DROP TABLE IF EXISTS `work_result`;

CREATE TABLE `work_result` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data_id` int(11) unsigned NOT NULL DEFAULT '0',
  `task_id` int(11) unsigned NOT NULL DEFAULT '0',
  `work_id` int(11) unsigned NOT NULL DEFAULT '0',
  `result` mediumtext COMMENT '作业结果,json',
  `feedback` text COMMENT '作业反馈',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`work_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工作结果表';

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
