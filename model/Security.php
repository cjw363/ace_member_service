<?php
/**
 * Created by YGH.
 * Date: 14-3-24 下午12:57
 */
class Security {
	private static $_instance;
	private static $_code;
	private static $_key = 'g8HYiLT';
	private static $systemCode;

	private function __construct() {
		self::$systemCode = getConf("project");
	}

	//覆盖__clone()方法，禁止克隆
	private function __clone() {
	}


	public static function getInstance() {
		if (static::$_instance===null) {
			static::$_instance = new static();
		}
		return static::$_instance;
	}

	public static function getSecureCode(){
		return static::$_code;
	}

	/**
	 * 每秒内生成的code是一样的，使在iframe的父级生成的code与iframe内生成的code保持一致
	 * @param int $offset
	 * @return bool|string
	 */
	public static function genSecureCode($offset=0) {
		$now = date('U')+$offset*1;
		$code = sha1(self::$_key.$now);
		$start = hexdec(substr($code,-10,2))%16;
		$code = base64_encode($code);
		$code = substr($code,$start,5);
		static::$_code = $code;
		return $code;
	}

	/**
	 * 因为可能发生临界问题（即前一次生成的code发生在22:59:59:9999，后一次生成的code就可能在23:00:00:0001,前后相差不到一秒，但生成的code就不相同），
	 * 所以，当需要与前一秒的code对比时，会生成前后两秒的code来对比，如果这两个code与前一秒生成的code相同，就认为是合法的
	 * @param $code
	 * @return bool
	 */
	public static function validateSecureCode($code){
		return $code == self::genSecureCode() || $code == self::genSecureCode(-1);
	}

	/**
	 * @param $data
	 * @param string  $type
	 * @return string 返回加密后的字符串
	 * 此方法不可逆
	 */
	public static function safeEncrypt($data,$type = null){
		if(!$type) $type = self::$systemCode;
		$cfg = self::getSecurityConf($type);
		$prefix = '';
		$suffix = '';
		for($i=0;$i<$cfg['num_prefix'];$i++){
			$prefix .= dechex(mt_rand(0,15));
		}
		for($i=0;$i<$cfg['num_suffix'];$i++){
			$suffix .= dechex(mt_rand(0,15));
		}
		$s = $prefix.sha1($cfg['key'].strtoupper($data)).$suffix;
		return $s;
	}

	public static function comparePassword($dbPwd,$input,$type = null){
		if(!$type) $type = self::$systemCode;
		$cfg = self::getSecurityConf($type);
		$pwd = substr(substr($dbPwd,$cfg['num_prefix']),0,-$cfg['num_suffix']);
		return sha1($cfg['key'].strtoupper($input)) == $pwd;
	}

	private static function getSecurityConf($type){
		$cfg = getConf('security');
		$rt = $cfg[$type];
		if(!$rt) throw new Exception('Invalid Security type: '.$type);
		return $rt;
	}
}