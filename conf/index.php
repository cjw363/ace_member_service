<?php 
error_reporting(E_ERROR|E_COMPILE_ERROR);

define('_PLATFORM_',realpath(_ROOT_.'/../platform/lib.mini-mvc/'));
define('_LOG_',realpath(_ROOT_.'/../logs/'));
define('_TMP_',realpath(_ROOT_.'/../tmp/'));
define('_CONF_',realpath(_ROOT_.'/../conf'));
session_save_path(_TMP_);
session_set_cookie_params(3600);
ini_set('session.use_cookies',0);//no cookie for session
ini_set('session.name','_s');//tmp.solution for phprpc

require(_PLATFORM_.'/controller.php');//class of Controller

require('conf.switch.php');
require('config.lotto.php');


$_conf_common = array(
	'version'=>'0.0331',
	'supported_apk_versions'=>array('1.3.25','1.3.26','1.3.27','1.3.28','1.3.29','1.3.30','1.3.31','1.3.32','1.3.33','1.3.34'), //apk显示的版本号
	'default_class_path'=>'../model',
	'ACT'=>'_a',
	'_S'=>'_s',
	'MODEL'=>'php/action',
	'ACTION'=>'php/action',
	'CTRL'=>'./',
	'cache_type' => 'memcached',
	'maintenance'=>0, 
	'maintenanceTime'=>'2011/11/08 12:00-13:00',
	'dz'=>array(
		'dir_name' => 'lib.DzTemplate-1.0.0.mega',
		'template_dir'=> _ROOT_,
		'compile_dir' => _ROOT_ .'/../tmp/',
		'force_compile' => true,
	),
	'cache_lite'=>array(
		'dir_name' => 'lib.Cache_Lite-1.7.2',
		'cache_dir' => _ROOT_ .'/../tmp/',
	),
	'lang'=>array(
		'dir_name'=>'php/lang',
	),
	'page_size'=>60,
	'login_try'=>3,
	'SERVER_TIMEZONE'=>'Asia/Ho_Chi_Minh',
	'flag_timezone_hk'=>0,
	'project'=>'ace_android_service',
	'memcache_key_prefix'=>'asia_weiluy',
	'security'=>array(
		'ace_android_service'=>array('key'=>'ace_20170109','num_prefix'=>'5','num_suffix'=>'3')
	),
	'cookie'=>array('prefix'=>'ace_android_service','domain'=>'','path'=>'/'),
	'session_expire_time'=>3600, //秒
	'printers'=>array('P58A_F','SP200','P58A+','P80A','ANDROID BT','INNERPRINTER'),
	'customer_service'=>array('+855-98-6666-78', '+855-99-6666-78', '+855-71-6666-078'),
);

Controller::$_conf_ = $_conf_self + $_conf_common; // Same key, use the setting before the '+'
include_once('../platform/lib.Cache_Lite-1.7.2/Lite.php');

