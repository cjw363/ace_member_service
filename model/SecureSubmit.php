<?php

class SecureSubmit {

	const KEY_TOKEN = 'unique_token';

	private function __construct() {

	}

	//覆盖__clone()方法，禁止克隆
	private function __clone() {}

	/**
	 * @param $action
	 * @param $data
	 * @param bool $flagValidateTime 是否验证下注间隔
	 * @return mixed array
	 */
	public static function checkToken($action, $data, $flagValidateTime = true){
		$key = self::KEY_TOKEN;
		if(!isset($_SESSION[$key][$action]['time'])){
			$_SESSION[$key][$action]['time'] = time();
		}else {
			if($flagValidateTime){
				if(time() - $_SESSION[$key][$action]['time'] < 2){
					self::logDuplicateSubmit($action, $data, "Submit Too Fast");
					return false;
				}
			}
		}
		$token = $data[$key];
		if(!$token){
			self::logDuplicateSubmit($action, $data);
			return false;
		}
		if(isset($_SESSION[$key][$action])) {
			if(isset($_SESSION[$key][$action][$token])){
				unset($_SESSION[$key][$action][$token]);
			}else {
				self::logDuplicateSubmit($action, $data);
				return false;
			}
		} else {
			self::logDuplicateSubmit($action, $data);
			return false;
		}

		$_SESSION[$key][$action]['time'] = time();
		return true;
	}

	static function logDuplicateSubmit($action, $data, $remark = ''){
		quicklog('submit_duplicate', $action. ' ' .json_encode($data). '#' . $_SESSION['login_id'] . $remark);
	}

	static function genToken($action) {
		$key = self::KEY_TOKEN;
		$k = self::genString(15);
		if(!isset($_SESSION[$key])) {
			$_SESSION[$key] = array();
		}
		unset($_SESSION[$key][$action]);
		$_SESSION[$key][$action] = array();
		$_SESSION[$key][$action][$k] = 1;
		return $k;
	}

	static function genString($len, $inputStr = '') {
		if($inputStr == '')
			$inputStr = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$count = strlen($inputStr);
		$outputStr = '';
		for ($i = 0; $i < $len; $i++) {
			$outputStr .= $inputStr[mt_rand(0, $count - 1)];
		}
		return $outputStr;
	}

}