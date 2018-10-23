<?php
include_once _PLATFORM_."/../".Controller::$_conf_['dz']['dir_name']."/template.class.php";
function getAssign($arr,$GetVAR=false){
	$rt = '';
	foreach($arr as $key){
		$rt.='$Viewer->assign("'.$key.'",$'.$key.');';
		if($GetVAR){
			$rt.='$'.$key.'="'.str_replace("\"","\\\"",$key).'";';
		}
	}
	return $rt;
}
function getAssignI18N($arr,$GetVAR=false){
	$rt="";
	foreach($arr as $key){
		$rt.='$Viewer->assign("'.$key.'",I18N_'.$key.');';
		if($GetVAR){
			$rt.='$'.$key.'=I18N_'.$key.';';
		}
	}
	return $rt;
}

//$Viewer = new Smarty;
$Viewer = DzTemplate::getInstance();

$Viewer->template_dir = Controller::$_conf_['dz']['template_dir'];
$Viewer->cache_dir = Controller::$_conf_['dz']['compile_dir'];
$Viewer->compile_check = Controller::$_conf_['dz']['force_compile'];
//$tpl->left_delimiter = '{';					//模板标记的左分隔符
//$tpl->right_delimiter = '}';				//模板标记的右分隔符
//$tpl->compile_check = true;					//模板文件修改后是否需要重新编译
//$tpl->cache_lifetime = 3600;				//缓存文件的有效时间(秒),设为0则永不过期

//print "<h1>test here:".$Viewer->compile_dir."</h1>";die;

global $CTRL,$ACT,$_S,$SID,$ACTVAL,$CTRLName,$ver_file;

$lang=$_SESSION['lang'];
$ver_js = $ver_file['js'];
#$Viewer->assign("currency",$currency);

function _smarty_function_I18N($params, &$smarty){
	$k=$params['k'];
	return I18N($k);
}
