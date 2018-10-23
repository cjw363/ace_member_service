<?php
/**
 * Created by YGH.
 * Date: 14-4-20 下午10:26
 */

//hack defends
//$log_filename=str_replace("..","",$log_filename);
//$log_filename=str_replace("&","",$log_filename);
function logger($log_filename, $log_content, $prefix = "", $gzf) {
	if (!defined('_LOG_')) {
		throw new Exception("//_LOG_ not defined to call logger");
	}
	if ($prefix == "DEFAULT")
		$prefix = "--" . date('ymd His') . ":";
	//$suffix="\r\n";//for windows
	$suffix = "\n"; //for all
	if ($gzf) {
		$gz = gzopen(_LOG_ . '/' . $log_filename . '.gz', 'a');
		gzwrite($gz, $prefix . $log_content . $suffix);
		gzclose($gz);
	} else {
		file_put_contents(_LOG_ . '/' . $log_filename, $prefix . $log_content . $suffix, FILE_APPEND);
	}
}

function quicklog($log_type, $log_content, $gz = false) {
	logger($log_type . "-" . date('Ymd') . ".log", $log_content, "DEFAULT", $gz);
}

function get_msg($msgs) {
	$rt = '';
	foreach ($msgs as $msg) {
		if (is_array($msg) || is_object($msg)) {
			$msg = '   |' . json_encode($msg) . '|   ';
		}
		$rt .= '   |' . $msg . '|   ';
	}
	return $rt;
}

/**
 * 为qlog,dlog生成文件名
 * by ygh
 */
function genFileNameForLog($trace){
	$class = $trace[1]['class'];
	$func = $trace[1]['function'];
	if($class){
		$class = str_replace('\\','.',$class);
		$class = str_replace('\/','.',$class);
		$logFile = "[$class]";
	}else{
		$fullFile = $trace[0]['file'];
		preg_match(';((\w+)(\.|-))+\w+;', $fullFile, $matches);
		$file = $matches[0];
		$logFile = "[$file]";
	}
	$logFile .= "-$func";
	return $logFile;
}
/**
 * 使用详细的名称命名,打印简单调试信息
 * 可接收多个参数
 * by ygh
 */
function qlog() {
	$params = func_get_args();
	$trace = debug_backtrace(false);
	$fileName = genFileNameForLog($trace);
	quicklog($fileName, get_msg($params));
}

/**
 * 使用详细的名称命名,打印详细调试信息
 * 可接收多个参数
 * by ygh
 */
function dlog(){
	$params = func_get_args();
	$trace = debug_backtrace(false);
	$info = "";
	foreach($trace as $list){
		$info .= "\n\t";
		$hasClass = false;
		if($list['class']){
			$hasClass = true;
			$info .= "at {$list['class']}()::";
		}
		if(!$hasClass) $info .= 'at ';
		$info .= "{$list['function']}()";
		if($list['line']){
			$info .= ": line {$list['line']}";
		}
		if($list['file']){
			$info .= " [{$list['file']}]";
		}
	}

	$fileName = genFileNameForLog($trace);
	$log = get_msg($params);
	$log .= $info."\r\n";
	quicklog($fileName, $log);
	return $log;
}