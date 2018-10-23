<?php
/**
 * Functions which used in DzTemplate Class
 * 
 */

function transamp($str) {
	$str = str_replace('&', '&amp;', $str);
	$str = str_replace('&amp;amp;', '&amp;', $str);
	$str = str_replace('\"', '"', $str);
	return $str;
}
/*
function addquote($var) {
	return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
}
*/
function dz_languagevar($var) {
	if(isset($GLOBALS['language'][$var])) {
		return $GLOBALS['language'][$var];
	} else {
		return "!$var!";
	}
}

function stripvtags($expr, $statement) {
	$expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
	$statement = str_replace("\\\"", "\"", $statement);
	return $expr.$statement;
}

function stripscriptamp($s) {
	$s = str_replace('&amp;', '&', $s);
	return "<script src=\"$s\" type=\"text/javascript\"></script>";
}

function stripblock($var, $s) {
	$s = str_replace('\\"', '"', $s);
	$s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
	preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
	$constadd = '';
	$constary[1] = array_unique($constary[1]);
	foreach($constary[1] as $const) {
		$constadd .= '$__'.$const.' = '.$const.';';
	}
	$s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
	$s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
	$s = str_replace('<?', "\nEOF;\n", $s);
	return "<?\n$constadd\$$var = <<<EOF\n".$s."\nEOF;\n?>";
}

function dz_parse_string_param($match) {
  $quote = $match[1];
  $param = $match[2];
  $param = preg_replace('/(\$\w+(\[.+?])*)/', $quote.".\\1.".$quote, $param);
  $rt = "($quote$param$quote)";
  return $rt;
}

function dz_parse_php_string_var($match) {
  $phpTag = $match[1];
  $phpCode = $match[4];
  $phpCode = preg_replace_callback("/\(\s*('|\")(.+?)('|\")\s*\)/s", "dz_parse_string_param", $phpCode);
  return "$phpTag $phpCode ?>";
}

function dz_function_filetime($fn){
	if(file_exists($fn)){
		return filemtime($fn);
	}
	return filemtime( realpath(_LIB_ ."/". $fn));
	//return realpath(_ROOT_."/".dirname($fn)."/".basename($fn));//shit..
}

function dz_function_url($params_str, &$smarty){
  global $CTRL;
  $params_str = preg_replace("/\s+/is", "&", $params_str);
  $url = "$CTRL?$params_str";
  if ($_SESSION['IS_WAP'] || $isWap)  return $url = htmlspecialchars($url);
  return $url;
  /* 下面是原来的方法，
   * 模板改了一点之后，下面这一步list($k,$v)=split('=',$pal);会造成不能在url中传递 = 号，因此，使用现在的方法 */
//	$pa=split(' ',$params_str);
//	$p=array();
//	foreach($pa as $pal){
//		list($k,$v)=split('=',$pal);
//		$p[$k]=$v;
//	}
//	return url($p);
}
?>