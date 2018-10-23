<?php
/**
 * Created by YGH.
 * Date: 14-4-20 下午10:33
 * 暂时没用上的方法
 */

function getInf($option, $default = null) {
	//TODO upgrade program later
	//$default=getConf($option);
	if (!isset($_SESSION["_MEGA_GAME_USER_INF"])) {
		//return $default;
		$_SESSION["_MEGA_GAME_USER_INF"] = array();
	}
	$inf = $_SESSION["_MEGA_GAME_USER_INF"];
	//return isset($inf[$option]) ?  $inf[$option] : $default;
	return $inf[$option];
}

function setInf($option, $data = null) {
	//TODO upgrade program later
	if (!isset($_SESSION["_MEGA_GAME_USER_INF"])) {
		$_SESSION["_MEGA_GAME_USER_INF"] = array();
	}
	$inf = $_SESSION["_MEGA_GAME_USER_INF"];
	if (is_array($option)) {
		$inf = array_merge($inf, $option);
	} else {
		$inf[$option] = $data;
	}
	$_SESSION["_MEGA_GAME_USER_INF"] = $inf;
}



/* $script 使用相对路径 */
/*
function async_local($script, $params) {
	$fp = fsockopen('127.0.0.1', $_SERVER['SERVER_PORT'], &$errno, &$errstr, 5);
	if (!$fp) {
		echo "$errstr ($errno)<br />\n";
	}
	$scriptName = $_SERVER['SCRIPT_NAME'];
	$tmp = explode('/', $scriptName);
	$dir = $tmp[1];
//	al($dir);
	fputs($fp, "GET /$dir/$script?$params\r\n");
	fclose($fp);
}
*/

//*********************************************************************************************set user infomation
if (!function_exists("async_call")) {

	function async_call($ACTVAL, $param = "", $usleep = 200, $dolog = true) {
		global $ACT;

		$SERVER_ADDR = @$_SERVER['SERVER_ADDR'];
		$SERVER_NAME = @$_SERVER['SERVER_NAME'];
		if ($SERVER_NAME == '')
			$SERVER_NAME = '127.0.0.1';
		if ($SERVER_ADDR == '')
			$SERVER_ADDR = '127.0.0.1';

		if ($ACT == '')
			$ACT = '_a';
		$remote_url = $_SERVER['SCRIPT_NAME'] . "?$ACT=$ACTVAL&$param";
		$SERVER_PORT = $_SERVER['SERVER_PORT'];
		if ($SERVER_PORT > 0) {
			$errno = "";
			$errmsg = "";
			$http_message = "GET $remote_url HTTP/1.1\r\nHost: $SERVER_NAME\r\n\r\n";
			//异步呼叫.
			$fp = stream_socket_client("$SERVER_ADDR:$SERVER_PORT", $_errno, $_errstr, 1, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT);
			if ($fp) {
				if (stream_set_blocking($fp, false)) {
					fputs($fp, $http_message);
					//如果usleep设成<=0,那就返回这个fp,让外面来sleep
					if ($usleep > 0) {
						usleep($usleep);
						//sleep(1);
					}
				}
			}
			//$socks_return=fgets($fp);
			//if($socks_return==""){
			//	//write log for debugging
			//}
			if ($dolog) {
				if (function_exists("mylog")) {
					//mylog("http://" . $SERVER_NAME . "" . $remote_url . " is tried called.(" . $_errno . ":" . $_errstr . ")(" . $_SERVER['SERVER_PORT'] . ")$socks_return");
					mylog("http://" . $SERVER_NAME . "" . $remote_url . " is tried called.(" . $_errno . ":" . $_errstr . ")(" . $_SERVER['SERVER_PORT'] . ")");
				} else {
					//quicklog("async_call","http://".$SERVER_NAME."".$remote_url ." is tried called.(".$_errno.":".$_errstr.")(".$_SERVER['SERVER_PORT'].")$socks_return");
				}
			}
		} else {
			throw new Exception($SERVER_ADDR . $_SERVER['SERVER_PORT'] . $remote_url . " is FAILED called port=0");
		}
		//if($usleep<=0) return $fp;
		//else return null;
		return $fp;
	}

}
if (!function_exists("key_underline")) {

	/**
	 * TODO: remove it, if modification of json_encode is ok. (by splutter)
	 * 对于 array("1"->"name1","2"->"name2") 这样的数组，由于js不支持以数值为key，所以每个key前补一个下划线，即array("_1"->"name1","_2"->"name2")
	 */
	function key_underline($arr = null) {
		$rt = array();
		foreach ($arr as $key => $val) {
			$rt[$key] = $val;
		}
		return $rt;
	}

}
if (!function_exists("json_encode")) {
	/*
	  function json_encode($o){
	  global $_g_json;
	  $_g_json=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
	  $s=$_g_json->encode($o);
	  //$s = json_encode($o);
	  return $s;
	  }
	 *
	 */

	function my_json_encode_failed($a = false, $f_encode_number_key = true, $standard = true) {
		if (is_null($a))
			return 'null';
		if ($a === false)
			return 'false';
		if ($a === true)
			return 'true';
		if (is_scalar($a)) {
			if (is_float($a)) {
				// Always use "." for floats.
				return floatval(1.0 * str_replace(",", ".", strval($a)));
			}
			if (is_numeric($a)) {
				return floatval($a); //faint
			}
			if (is_string($a)) {
				static $jsonReplaces = array(array("\\", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
			} else
				return $a;
		}
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
			if (key($a) !== $i) {
				$isList = false;
				break;
			}
		}
		$result = array();
		if ($isList) {
			foreach ($a as $v)
				$result[] = json_encode($v);
			return '[' . join(',', $result) . ']';
		} else {
			//foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
			foreach ($a as $k => $v) {
				if (is_numeric($k)) {
					if ($f_encode_number_key) {
						if ($standard)
							$result[] = "\"$k\":" . json_encode($v);
						else
							$result[] = "$k:" . json_encode($v);
					}
				}else {
					if ($standard)
						$result[] = "\"$k\":" . json_encode($v);
					else
						$result[] = "$k:" . json_encode($v);
				}
			}
			//return '{' . join(', ', $result) . '}';
			return '{' . join(',', $result) . '}';
		}
	}

}
if (!function_exists("my_json_decode")) {

	function my_json_decode($s) {
		//		global $_g_json;
		//		$_g_json=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		//		$a=$_g_json->decode($s);
		$a = json_decode($s, true);
		return $a;
	}

}

function get_utf8_from_base64_utf16(&$str) {
	$utf16 = chr(hexdec('FF')) . chr(hexdec('FE')) . base64_decode($str);
	return iconv('UTF-16', 'UTF-8', $utf16);
}