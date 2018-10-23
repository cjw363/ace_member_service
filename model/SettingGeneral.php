<?php

class SettingGeneral extends \Base {

	public function __construct(){
		parent::__construct(System::DB_RUN);
	}

	public function getSystemFunctionByCode($code) {
		if(!($code > 0))
			return 0;
		$sql = " select status from tbl_system_function where code = $code";
		return $this->getOne($sql);
	}

} 