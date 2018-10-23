<?php
use validation\Validator as V;

class Log extends \Base{

	public function __construct() {
		parent::__construct(System::DB_RUN);
	}

	public function addLog($remark = '', $memberID = 0){
		if (!V::min_num($memberID, 0)) throw new TrantorException('MemberID');
		if ($memberID == 0) $memberID = $_SESSION['UID'];

		$sql = 'insert into tbl_member_log(time,member_id,ip,remark) values(now(),'.qstr($memberID).','.qstr(getIP()).','.qstr($remark).')';
		$this->execute($sql);
	}

	public function getLogList($page){
		$sql = 'select time,ip,remark from tbl_member_log where member_id = '.qstr($_SESSION['UID']).' ORDER BY time DESC';
		return $this->getPageArray($sql,$page);
//		$data=\Utils::simplifyData($rt['list']);

//		return array('list'=>$data['list'],'keys'=>$data['keys'],"total"=> $rt['total'],"page"=>$rt['page'],"size"=>$rt['size']);
	}

}