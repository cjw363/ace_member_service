<?php
use validation\Validator as V;

class PhoneCompany extends \Base {

	public function __construct() {
		parent::__construct(Constant::MAIN_DB_RUN);
	}

	public function getCompanyById($ID) {
		if (!V::over_num($ID,0)) throw new TrantorException("ID");
		$sql = "select id,country_code,name,currency,member_discount,status from tbl_phone_company where id=" . qstr($ID) . " and status=" . Constant::STATUS_1_ACTIVE;
		return $this->getLine($sql);
	}

	public function getActiveCompanyList() {
		$sql = "select id,country_code,name,currency,member_discount,status from tbl_phone_company where status=" . Constant::STATUS_1_ACTIVE." order by field(country_code,".Phone::COUNTRY_CODE_855_CAMBODIA.",".Phone::COUNTRY_CODE_84_VIETNAM.",".Phone::COUNTRY_CODE_66_THAILAND.",".Phone::COUNTRY_CODE_86_CHINA.")";
		return $this->getArray($sql);
	}
}