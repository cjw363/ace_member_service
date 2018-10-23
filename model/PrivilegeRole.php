<?php
use validation\Validator as V;

class PrivilegeRole extends \Base{

	public function __construct() {
		parent::__construct(System::DB_RUN);
	}

	public function chkPrivilege($uid,$privilegeCode){
		if(!V::over_num($uid,0)) throw new Exception('PrivilegeRole::chkPrivilege Parameter Error uid');
		if(!V::required($privilegeCode)) throw new Exception('PrivilegeRole::chkPrivilege Parameter Error privilegeCode');
		$sql = 'select 1 from tbl_user_staff_role t1 left join tbl_role_privilege t2 on (t1.role_id = t2.role_id) where t1.user_id = '.qstr($uid).' and t2.privilege_code = '.qstr($privilegeCode);
		return $this->getOne($sql) > 0;
	}

	public function getClickable(){
		$rt = array();
		if($_SESSION['STATUS'] == User::USER_STATUS_2_SUSPENDED){
			$rt = array(
				Constant::ACTIVITY_49_SETTING,
				Constant::ACTIVITY_50_PASSWORD,
				Constant::ACTIVITY_51_LOGOUT
			);
		}
		return $rt;
	}

	public function getPrivileges($uid = null){
		if(!$uid) $uid = $_SESSION['UID'];
		$rt = [];
		if($_SESSION['TYPE'] == User::USER_TYPE_1_MEMBER){
			$p=array(
				'2001'=>array( //基本功能
					Constant::ACTIVITY_49_SETTING,
					Constant::ACTIVITY_51_LOGOUT
				),

				'2022'=>array( //Member相关
					Constant::ACTIVITY_46_NEW_MEMBER,
					Constant::ACTIVITY_47_MEMBER_LIST)
			);

			$sql = 'SELECT DISTINCT privilege_code FROM tbl_role_privilege trp INNER JOIN tbl_user_staff_role tusr ON tusr.role_id = trp.role_id AND tusr.user_id='.qstr($uid).' AND privilege_code IN(2021,2022)';
			$rs = $this->getArray($sql);
			$rt = $p['2001'];
			if(empty($rs)){
				return $rt;
			}
			foreach($rs as $n){
				if(!empty($p[$n['privilege_code']])) $rt = array_merge_recursive($rt,$p[$n['privilege_code']]);
			}
		}

		$rt = array_unique($rt);
		sort($rt);

		return $rt;
	}
}