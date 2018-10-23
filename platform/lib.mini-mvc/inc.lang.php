<?php
/**
 * Created by YGH.
 * Date: 14-4-20 下午10:53
 */

function setSessionLang(){
	if (@$_REQUEST['lang'] != '') {
		if (eregi("^[a-zA-Z0-9-]+$", $_REQUEST['lang']))
			$_SESSION['lang'] = $_REQUEST['lang'];
	}else {
		if ($_SESSION['lang'] == '') {
			//preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
			//$lang = $matches[1];
			//if ($lang!='') $_SESSION['lang']=$lang;
			//else
			$_SESSION['lang'] = "en";
		}
	}

	$lang_path = Controller::$_conf_['lang']['dir_name'];
	$_fn = "$lang_path/inc." . $_SESSION['lang'] . ".php";
	if (file_exists($_fn)) {
		include_once($_fn);
	} else {
		$_fn = "$lang_path/inc.en.php";
		include_once($_fn);
	}
}

// I18N
function I18N($k) {
	$k = preg_replace('/[\s\/,]*/', '', $k);
	$v = "$k";
	if (defined("I18N_$k")) {
		eval("\$v=I18N_$k;");
	} else if (defined("I18N_" . strtolower($k))) {
		eval("\$v=I18N_" . strtolower($k) . ";");
	};
	return $v;
}

function arrI18N($name_arr) {
	$rt = array();
	foreach ($name_arr as $key) {
		eval("\$rt[\$key]=I18N_$key;");
	}
	return $rt;
}

/**
 * load language from web/php/lang/$lang/xxx/xxx.php
 * 应用于某些特殊的页面，如rules页面
 */
function lang($file){
	$lang_path = Controller::$_conf_['lang']['dir_name'];
	include_once("$lang_path/{$_SESSION['lang']}/{$file}");
}
