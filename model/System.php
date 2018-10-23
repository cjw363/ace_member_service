<?php
use \validation\Validator as V;

class System extends \Base {

	const SYSTEM_FUNCTION_STATUS_1_RUNNING = 1;
	const SYSTEM_FUNCTION_STATUS_2_PAUSE = 2;

	const DB_RUN = "system_run";

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function getGlobalSetting($code){
		if(!V::over_num($code,0)) throw new Exception("System::getGlobalSetting Parameter Error code");
		return $this->getLine('select code, amount from tbl_global_setting where code='.qstr($code));
	}

	public function isFunctionRunning($code) {
		if (!V::over_num($code, 0)) return false;
		$sql = 'select status from tbl_function where code=' . qstr($code);
		return $this->getOne($sql) == self::SYSTEM_FUNCTION_STATUS_1_RUNNING;
	}

	public function getCountryCodeList() {
		$sql = "select code from tbl_country";
		return $this->getArray($sql);
	}

}