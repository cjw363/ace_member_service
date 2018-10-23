<?php
/**
 * Created by YGH.
 * Date: 14-4-20 下午10:17
 */

function getIP() {
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "127.0.0.1")) $ip = getenv("HTTP_CLIENT_IP"); else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "127.0.0.1")) {
		$ip = getenv("HTTP_X_FORWARDED_FOR");
		list($ip) = explode(",", $ip);
	} else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "127.0.0.1")) $ip = getenv("REMOTE_ADDR"); else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "127.0.0.1")) $ip = $_SERVER['REMOTE_ADDR']; else
		$ip = "127.0.0.1";
	return ($ip);
}

/* 在php脚本中调用时,会使脚本停住,等待用户的输入,如果用户输入回车键,则会继续运行后续代码 */
function wait_util_input() {
	$fp = fopen('/dev/stdin', 'r');
	fgets($fp, 255);
	fclose($fp);
}

function url($params) {
	//quicklog("DEBUG","controller::url");
	//$_S=$GLOBALS['_S'];
	//$SID=($params[$_S])?$params[$_S]:$GLOBALS['SID'];
	//$CTRL=($params['CTRL'])?($params['CTRL'].".php"):$GLOBALS['CTRL'];
	//$ACT=($params['ACT'])?$params['ACT']:$GLOBALS['ACT'];
	//$ACTVAL=($params[$ACTVAL])?$params[$ACTVAL]:$GLOBALS['ACTVAL'];
	//$s = "$CTRL?";
	$s = "";
	//$args=array($ACT=>$ACTVAL,$_S=>$SID);
	$args = array();
	foreach ($params as $key => $value) {
		if (is_array($value)) {
			$args = array_merge($args, $value);
			unset($params[$key]);
		}
	}
	$args = array_merge($args, $params);
	//var_dump($args);
	$ua = Array();
	foreach ($args as $k => $v) {
		if (!empty($v)) $ua[] = "$k=$v";
	}
	$s .= join('&', $ua);
	return $s;
}

/** 增加特殊字符转换 */
function url2($params) {
	return htmlspecialchars(url($params));
}

function checkIP() {
	if (@$_SESSION['_ip'] != getIP()) {
		throw new Exception("IP Changed, Please login again.", 4444);
	}
}

function redirect($url) {
	?>
	<script type="text/javascript">window.location = "<?=$url?>";</script>
	<?
	exit(0);
}

function redirect2($url, $msg = "", $target = "window") {
	?>
	<script type="text/javascript">
		<!--
		<? if ($msg != "") { ?>
		window.alert("<?= $msg ?>");
		<? } ?>
		__target = <?= $target ?>;
		while (__target.parent != __target) {
			__target = __target.parent
		}
		__target.location = "<?= $url ?>";
		-->
	</script>
	<?
	exit();
}

function runCmd($p = array(), $_flag_check_ip = false) {
	if ($_flag_check_ip) checkIP();
	$maintenance = getConf('maintenance');
	if ($maintenance == 1) $cmd = "maintenance"; else {
		if (!empty($p)) {
			$cmd = $p['cmd'];
		} else {
			$cmd = @$_REQUEST['cmd'];
		}
		if ($cmd == '') {
			$cmd = 'index';
		}
	}
	$cmd = 'cmd' . ucfirst($cmd);
	if (!function_exists($cmd)) {
		die("method [$cmd] not found!");
	}
	$p = Utils::trimParams($p);
	$rt = call_user_func($cmd, $p);
	return json_encode($rt);
}

//把数据库取回来的形如(用js对象描述)[{id:1, name:'jonson'}, {id:2, name:'jonson2'}, {id:3, name:'jonson3'}]的数组转换成
// {fields:{'id':0, 'name':1}, data:[[1, 'jonson'], [2, 'jonson2'], [3, 'jonson3']]}的形式,以减少网络传输的数据量
function getArray(&$rs) {
	if (!function_exists('fields')) {
		function fields(&$rs) {
			$fields = array();
			$i = 0;
			while ($field = mysql_field_name($rs->_queryID, $i)) {
				$fields[$field] = $i++;
			}
			return $fields;
		}
	}

	$fv = array('fields' => fields($rs), 'data' => array()); //用来存储最终的结果,$fv意即field & value
	if (!$rs->fields) return $fv;
	$fv['data'][] = array_values($rs->fields);
	while ($row = mysql_fetch_row($rs->_queryID)) {
		$fv['data'][] = $row;
	}
	return $fv;
}

/* 对于从数据库查询返回(一般是$rs = $db->execute())的对象,
 * 例如 返回有两行记录(row1:[value1, value2], row2:[value3, value4])
 *  字段个数为2, 名称分别为 f1, f2, 那么该函数将返回一个数组 [value1, value2, value3, value4]
 */

function getLinearArray($rs) {
	if ($rs->EOF) return array();
	$vv = array();
	while ($rs->fields) {
		$row = $rs->fields;
		foreach ($row as $v) {
			$vv[] = $v;
		}
		if ($rs->fields = mysql_fetch_array($rs->_queryID, $rs->fetchMode)) {
			$rs->_currentRow++;
		} else if (!$rs->EOF) {
			$rs->_currentRow++;
			$rs->EOF = true;
		}
	}
	return $vv;
}

//多个url一齐拿, 以异步的方式
function curl_multi_file_get_contents($urls) {
	if (count($urls) == 0) return '';
	$curl_arr = array();
	$master = curl_multi_init();

	foreach ($urls as $i => $url) {
		$curl_arr[$i] = curl_init($url);
		curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_arr[$i], CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($curl_arr[$i], CURLOPT_TIMEOUT, 30);
		curl_multi_add_handle($master, $curl_arr[$i]);
	}

	$running = 1;
	do {
		curl_multi_exec($master, $running);
		usleep(60000); //降低cpu的占用率
	} while ($running > 0);

	$results = array();
	foreach ($curl_arr as $i => $curl) {
		$results[$i] = curl_multi_getcontent($curl);
		curl_multi_remove_handle($master, $curl);
	}
	curl_multi_close($master);
	return $results;
}

function curl_file_get_contents($url) {
	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 25);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)");
		//curl_setopt($ch, CURLOPT_REFERER,_REFERER_);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$r = curl_exec($ch);
		curl_close($ch);
	} catch (Exception $e) {
		quicklog('Exception', 'catch Exception in curl_file_get_contents:' . $e->__toString());
		$r = '';
	}
	if (!$r) $r = '';
	return $r;
}

function getConf($key, $path = array()) {
	$_r = Controller::$_conf_;
	$len = sizeof($path);
	for ($i = 0; $i < $len; $i++) {
		$_r = $_r[$path[$i]];
	}
	$val = $_r[$key];
	return $val;
}

register_shutdown_function('shutdown_handle');
function shutdown_handle() {
	$error = error_get_last();
	if ($error['type'] == 1) {
		quicklog("err-php", " $error[message] in $error[file] ($error[line])\r\n");
	}
}

function __autoload($className) {
	//debug("__autoload($className)");
	//magic func, load the class file if the class not found
	$default_class_path = Controller::$_conf_['default_class_path'];
	$classFile = $default_class_path . '/' . str_replace('\\', '/', $className) . '.php';
	$model_class_path = Controller::$_conf_['MODEL'];
	//debug("   $default_class_path $model_class_path");
	if (file_exists("$classFile")) {
		include_once("$classFile");
	} elseif (file_exists("$model_class_path/$className.php")) {
		include_once("$model_class_path/$className.php");
	} else {
		throw new Exception("class not found: $className");
	}
	if (!class_exists($className, false) && !interface_exists($className, false)) throw new Exception("class/interface not found: $className");
}

function getCacher($type = 'memcached') {
	switch ($type) {
		case 'memcached':
			$cacher = new Memcached;
			$cacher->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_JSON_ARRAY); //序列化器
			$cacher->setOption(Memcached::OPT_TCP_NODELAY, true); //启用tcp_nodelay
			$cacher->setOption(Memcached::OPT_NO_BLOCK, true); //启用异步IO
			$cacher->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT); //分布式策略
			$cacher->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true); //分布式服务组分散.推荐开启
			$memServers = getConf('mem_servers');
			$cacher->addServers($memServers);
			return $cacher;

		case 'memcache':
			$cacher = new Memcache;
			$memServers = getConf('mem_servers');
			foreach ($memServers as $server) {
				$host = $server['host'];
				$port = $server['port'];
				$cacher->addServer($host, $port);
			}
			return $cacher;
	}
}

function getMemcached() {
	$memType = getConf('cache_type');
	if ($memType == 'memcache') { //注意$useMemcache 末尾没有 d 字母
		$mem = new Memcache;
		$memServers = getConf('mem_servers');
		foreach ($memServers as $server) {
			$host = $server['host'];
			$port = $server['port'];
			$mem->addServer($host, $port);
		}
		return $mem;
	}
	if (!class_exists('Memcached')) return null;
	$mem = new Memcached;
	$flagDev = getConf('flag_dev');
	if ($flagDev == 1) {
		$mem->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_JSON_ARRAY); //使用 json_array 序列化器
		$mem->setOption(Memcached::OPT_TCP_NODELAY, true); //启用tcp_nodelay
		$mem->setOption(Memcached::OPT_NO_BLOCK, true); //启用异步IO
		$mem->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT); //分布式策略
		$mem->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true); //分布式服务组分散.推荐开启
	}
	$memServers = getConf('mem_servers');
	$mem->addServers($memServers);
	return $mem;
}

/**
 * 特殊情况下需要临时传入 db_host， db_user, db_password等信息
 * @param $conf 格式：array('main'=>array('db_host'=>xx， 'db_user'=>xx, 'db_password'=>xx));
 */
function getDB($dns = "", $conf = "") {
	return BaseDB::getInstance($dns, $conf);
}

function chkSession() {
	if (isset($_SESSION['SESSION_EXPIRE_TIME'])) {
		if ($_SESSION['SESSION_EXPIRE_TIME'] < time()) {
			unset($_SESSION['SESSION_EXPIRE_TIME']);
			redirect('../');
			exit(0);
		} else {
			$_SESSION['SESSION_EXPIRE_TIME'] = time() + getConf('session_expire_time');
		}
	}
}

/**
 * ajax返回的数据结构 可带参数 true/false，array, string，各类参数有且只有一个，最多三个参数
 * 如果传入字符串，会作为提示信息处理
 * @throws Exception
 * @return array
 */
function reply() {
	$paramArr = func_get_args();
	if (count($paramArr) > 3) throw new Exception('Over params limit (3)');
	$sts = 'OK';
	$rt = [];
	$msg = '';
	foreach ($paramArr as $v) {
		if ($v === false) {
			$sts = 'KO';
		} else if (is_array($v) && !empty($v)) {
			$rt = $v;
		} else if (is_string($v)) {
			$msg = i18n($v);
		}
	}

	if ($rt['err_code']) {
		$rt['sts'] = 'KO';
	}
	if (!$rt['sts']) {
		$rt['sts'] = $sts;
	}
	if ($msg) {
		if ($rt['sts'] == 'OK') {
			$rt['msg'] = $msg;
		} else {
			$rt['errmsg'] = $msg;
		}
	}
	return $rt;
}

/**
 * 用于给处理重复提交的代码生成TOKEN的key
 * @param $lv , 获取调用上级的层次
 * @return string
 */
function getTokenKey($lv = 1) {
	if ($lv < 1) $lv = 1;
	$trace = debug_backtrace(false);
	$class = $trace[$lv]['class'];
	$func = $trace[$lv]['function'];
	if ($class) {
		$class = str_replace('\\', '.', $class);
		$class = str_replace('\/', '.', $class);
		$key = $class;
	} else {
		$path = pathinfo($trace[$lv - 1]['file']);
		$fileName = $path['filename'];
		$key = $fileName;
	}
	$key .= "_$func";
	return preg_replace('/ - |_|cmd|\./', '', $key);
}