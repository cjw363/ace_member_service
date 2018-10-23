<?php
/**
 * Created by YGH.
 * Date: 14-4-20 下午10:34
 */

function validate_c($text, $condition, &$info, &$passed, $msg = '') {
	if ($msg == '')
		$msg = "$text validation error.";
	if ($condition) {

	} else {
		$info.=" $msg";
		$passed = 0;
	}
}

function qstr2($s) {
	return str_replace("'", "''", $s);
}

function qstr($s) {
	$s = str_replace("\\", "\\\\", $s);
	$x = "'" . str_replace("'", "''", $s) . "'";
	return $x;
}

//防注入，处理那些不适合加引号的情况
function ss($s){
	$s = trim($s);
	if(get_magic_quotes_gpc()){
		return $s;
	}else{
		return mysql_real_escape_string($s);
	}
}

function chkSecurity(){
	$u = $_REQUEST['_u'];
	$s = $_SESSION['S_CODE'];
	if($u && !$s){
		if(Security::validateSecureCode($u)){
			$_SESSION['S_CODE'] = $u;
		}else{
			//die('hacking attempt');
			redirect('../');
		}
	}else{
		if (empty($_SESSION)) {
			$_SESSION['S_CODE'] = Security::genSecureCode();
		}else if((!$u && !$s) || ($u && $s && $u != $s)){
			if(!defined('IN_MG_SYSTEM')){
				//die('Hacking attempt');
				redirect('../');
			}
		}
	}
	return true;
}
