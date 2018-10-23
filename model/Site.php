<?php

class Site extends \Base {

	public function __construct() {
		parent::__construct(System::DB_RUN);
	}

	public function getSite() {
		$sql = 'SELECT site_type,site_id,division_code,address,address_remark,address_kh,address_remark_kh,longitude,latitude,CASE WHEN site_type = 1 THEN (SELECT name FROM tbl_branch WHERE id = site_id) WHEN site_type = 2 THEN (SELECT NAME FROM tbl_agent WHERE id = site_id) END name FROM tbl_site_address WHERE 1 ';
		return $this->getArray($sql);
	}

	public function getActiveAgentCount() {
		$sql = 'SELECT COUNT(id) FROM tbl_agent WHERE status=' . Constant::STATUS_1_ACTIVE;
		return $this->getOne($sql);
	}

	public function getActiveBranchCount() {
		$sql = 'SELECT COUNT(id) FROM tbl_branch WHERE status=' . Constant::STATUS_1_ACTIVE;
		return $this->getOne($sql);
	}

}