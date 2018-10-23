<?php

class Event extends \Base{

	public function  __construct(){
		parent::__construct(System::DB_RUN);
	}

	public function add($remark = "") {
		$ip = getIP();
		$sql = "insert into tbl_event(time,ip,remark) values(now()," . qstr($ip) .','. qstr($remark) . ")";
		$this->execute($sql);
	}

}