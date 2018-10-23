<?php
/**
 * Created by YGH.
 * Date: 14-2-11 下午3:56
 */
/**
 * 使用方法
 * $rules = array(
	'db_host' => 'required',
	'db_user' => array('required','personal_rule'=>function(){return false;}),
	'mem_port' => 'required|numeric|max_length(5)',
);
 * Validator::validate($params,$rules);
 * 或者 use \validation\Validator as V;
 *      V::vaidate($params,$rules);
 */
namespace validation;
use Exception;

class Validator {

	private static $simplePassword = array('abcd1234','abc12345','aa123456','aaa12345','aaa123456','a1234567','a12345678','qq123456','asd12345','asdf1234','qwer1234','1234qwer','1234abcd','abcd6789','uiop7890','hjkl7890','poiu0987','zxcv1234','Aaaa1234');

	/**
	 * Gets the parameter names of a rule
	 * @param $rule
	 * @return mixed
	 */
	final private static function getParams($rule) {
		if (preg_match("#^([a-zA-Z0-9_]+)\((.+?)\)$#", $rule, $matches)) {
			return array(
				'rule' => $matches[1],
				'params' => explode(",", $matches[2])
			);
		}
		return array(
			'rule' => $rule,
			'params' => array()
		);
	}

	/**
	 * Handle parameter with input name
	 * eg: equals(:name)
	 * @param mixed $params
	 * @return mixed
	 */
	final private static function getParamValues($params, $inputs) {
		foreach ($params as $key => $param) {
			if (preg_match("#^:([a-zA-Z0-9_]+)$#", $param, $param_type)) {
				$params[$key] = @$inputs[(string) $param_type[1]];
			}
		}
		return $params;
	}

	/**
	 *
	 * @param Array $inputs
	 * @param Array $rules
	 * @param Array $naming
	 * @return Validator
	 */
	final public static function validate($inputs, $rules, $flag_return=false, $return_boolean=true) {
		$errors = null;
		foreach ($rules as $input => $input_rules_str) {
			if(!is_array($input_rules_str)){ //自定义规则时，需要传入数组，格式如：array('required','personal_rule'=>function(){return false;}),
				$input_rules = explode('|',$input_rules_str);
			}else{
				$input_rules = $input_rules_str;
			}
			if (is_array($input_rules)) {
				foreach ($input_rules as $rule => $closure) {
					if (!isset($inputs[(string) $input]))
						$input_value = null;
					else
						$input_value = $inputs[(string) $input];
					/**
					 * if the key of the $input_rules is numeric that means
					 * it's neither an anonymous nor an user function.
					 */
					if (is_numeric($rule)) {
						$rule = $closure;
					}
					$rule_and_params = static::getParams($rule);
					$params = $real_params = $rule_and_params['params'];
					$rule = $rule_and_params['rule'];
					$params = static::getParamValues($params, $inputs);
					array_unshift($params, $input_value);
					/**
					 * Handle anonymous functions
					 */
					if (@get_class($closure) == 'Closure') {
						$refl_func = new \ReflectionFunction($closure);
						$validation = $refl_func->invokeArgs($params);
					}/**
					 * handle class methods
					 */ else if (@method_exists(get_called_class(), $rule)) {
						$refl = new \ReflectionMethod(get_called_class(), $rule);
						if ($refl->isStatic()) {
							$refl->setAccessible(true); //php need >=5.3.2
							$validation = $refl->invokeArgs(null, $params);
						} else {
							throw new ValidatorException(ValidatorException::STATIC_METHOD, $rule);
						}
					} else {
						throw new ValidatorException(ValidatorException::UNKNOWN_RULE, $rule);
					}
					if ($validation == false) {
						if($flag_return && $return_boolean) return false;
						$errors[] = '['.$inputs[(string) $input] .'] is Invalid, Required '.(string) $rule.' format';
					}
				}
			} else {
				throw new ValidatorException(ValidatorException::ARRAY_EXPECTED, $input);
			}
		}
		if($flag_return && $return_boolean){
			return empty($errors) == true;
		}else{
			if(!(empty($errors) == true)){
				$err_str = json_encode($errors);
				if($flag_return && !$return_boolean){
					return $err_str;
				}else{
					throw new Exception($err_str);
				}
			}
		}
	}

	public static function required($input = null) {
		return (!is_null($input) && (trim($input) != ''));
	}

	public static function numeric($input,$null='') {
		if($null=='null' && !$input) return true;
		return is_numeric($input);
	}

	public static function email($input,$null='') {
		if($null=='null' && !$input) return true;
		return filter_var($input, FILTER_VALIDATE_EMAIL);
	}

	public static function integer($input,$null='') {
		if($null=='null' && !$input) return true;
		return is_int($input) || ($input == (string) (int) $input);
	}

	public static function float($input,$null='') {
		if($null=='null' && !$input) return true;
		return is_float($input) || ($input == (string) (float) $input);
	}

	public static function alpha($input,$null='') {
		if($null=='null' && !$input) return true;
		return (preg_match("#^[a-zA-Z]+$#", $input) == 1);
	}

	public static function alpha_numeric($input,$null='') {
		if($null=='null' && !$input) return true;
		return (preg_match("#^[a-zA-Z0-9]+$#", $input) == 1);
	}

	public static function ip($input,$null='') {
		if($null=='null' && !$input) return true;
		return filter_var($input, FILTER_VALIDATE_IP);
	}

	public static function host($input,$null=''){
		if($null=='null' && !$input) return true;
		list($ip,$port) = explode(':',$input);
		preg_match('/\d{1,5}/',$port,$result);
		return filter_var($ip,FILTER_VALIDATE_IP) && $result;
	}
	public static function ipv4($input,$null='') {
		if($null=='null' && !$input) return true;
		return filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}

	public static function ipv6($input,$null='') {
		if($null=='null' && !$input) return true;
		return filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	public static function url($input,$null='') {
		if($null=='null' && !$input) return true;
		return filter_var($input, FILTER_VALIDATE_URL);
	}

	public static function max_length($input, $length) {
		return (strlen($input) <= $length);
	}

	public static function min_length($input, $length) {
		return (strlen($input) >= $length);
	}

	public static function isKhePhone($phone){
		if(!$phone) return true;
		$phone = preg_replace('/\s/','',$phone);
		$len = strlen($phone);
		return !($len>0 && ($len<7 || $len>12 || !preg_match('/^0[1-9]\d+$/',$phone)));
	}

	public static function isKhePhones($phone){
		if(!$phone) return true;
		$phone = preg_replace('/\s/','',$phone);
		$arr = preg_split('/[,;\/]/',$phone);
		foreach($arr as $a){
			$len = strlen($a);
			if($len>0 && ($len<7 || $len>12 || !preg_match('/^0[1-9]\d+$/',$a))) return false;
		}
		return true;
	}

	public static function exact_length($input, $length) {
		return (strlen($input) == $length);
	}

	public static function equals($input, $param) {
		return ($input == $param);
	}

	public static function min_num($input, $param){
		return (is_numeric($input) && $input >= $param);
	}

	public static function max_num($input, $param){
		return (is_numeric($input) && $input <= $param);
	}

	public static function over_num($input, $param){
		return (is_numeric($input) && $input > $param);
	}

	public static function under_num($input, $param){
		return (is_numeric($input) && $input < $param);
	}

	public static function num_equals($input, $param){
		return (is_numeric($input) && is_numeric($param) && $input == $param);
	}

	public static function between_num($input, $param1, $param2){
		return (is_numeric($input) && ($input >= $param1) && ($input <= $param2));
	}

	/**
	 * @param $input: date ('Y-m-d') or datetime ('Y-m-d H:i:s')
	 * @param $null,若传入null字符串，表明$input可以为空
	 * @return bool
	 */
	public static function date($input,$null=''){
		if($null=='null' && !$input) return true;
		$date1 = date('Y-m-d', strtotime($input));
		$date2 = date('Y-m-d H:i:s', strtotime($input));
		return ($date1==$input || $date2==$input);
	}

	public static function ipRange($input){
		return (preg_match('/^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])(\/(1[6-9]|2[0-9]|3[0-2]))?$/',$input)==1);
	}

	public static function account($input,$null=''){
		if($null=='null' && !$input) return true;
		return (preg_match('/^[^0-9][A-Za-z0-9_]{2,19}$/',$input)==1);
	}

	public static function  isValidUser($input){
		return (preg_match('/^[A-Za-z]{1}([A-Za-z0-9_ ]){3,}$/',$input)==1);
	}

	public static function isYesNo($input) {
		return is_numeric($input) && ($input==1 || $input==2);
	}

	public static function custom($reg, $input, $null=''){
		if($null=='null' && !$input) return true;
		return (preg_match($reg,$input)==1);
	}

	/** 检查密码的强度
	 * @param $password
	 * @return int 0 OK 1 长度或包含字符类型不对 2 密码过于简单
	 */
	public static function checkPassword($password){
		$match = array();
		preg_match_all('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $password, $match);
		if(empty($match) || strlen($password) < 8) return 1;
		$length = strlen($password);
		$temp = array();
		for($i=0; $i < $length; $i++){
			$temp[$password[$i]] = 1;
		}
		if(count($temp) < 6) return 2;
		if(in_array($password, self::$simplePassword)) return 2;
		return 0;
	}

	/**
	 * name验证，适用member、agent
	 * @param $input
	 * @return bool
	 */
	public static function nameMember($input){
		return preg_match('/^[A-Za-z ]{1,40}$/',$input);
	}

	/**phone验证
	 * @param $input phone: +xx/xxx-xxx...xxx
	 * @return bool
	 */
	public static function phone($input){
		return preg_match('/^\+\d{2,3}-[1-9]\d{5,11}?$/',$input);
	}

	/** 检查交易密码的强度
	 * @param $password
	 * @return int 0 OK 1 长度或包含字符类型不对 2 密码过于简单（取消）
	 */
	public static function checkTradingPassword($password){
		$match = array();
		preg_match_all('/[0-9]/', $password, $match);
		if(empty($match) || strlen($password) != 6) return 1;
//		$length = strlen($password);
//		$temp = array();
//		for($i=0; $i < $length; $i++){
//			$temp[$password[$i]] = 1;
//		}
//		if(count($temp) < 4) return 2;
//		if(in_array($password, self::$simplePassword)) return 2;
		return 0;
	}

	/**
	 * 检查验证码
	 * @param $code
	 * @return bool
	 */
	public static function verificationCode($code){
		$len = strlen($code);
		if ($len == 4 || $len == 6) return true;
		return false;
	}
}