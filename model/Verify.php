<?php
use validation\Validator as V;

class Verify extends Base {
	const DB_RUN = "assist_run";

	const CERTIFICATE_TYPE_1_ID = 1;
	const CERTIFICATE_TYPE_2_PASSPORT = 2;
	const CERTIFICATE_TYPE_3_DRIVE_LICENSE = 3;
	const CERTIFICATE_TYPE_4_FAMILY_BOOK = 4;

	const CERTIFICATE_STATUS_1_PENDING = 1;
	const CERTIFICATE_STATUS_2_ACCEPTED = 2;
	const CERTIFICATE_STATUS_3_REJECTED = 3;

	const ID_SIDE_1_FRONT=1;
	const ID_SIDE_2_BACK=2;
	const ID_SIDE_3_HOLDER=3;

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function saveIDVerify($IDType, $IDNumber, $filename0,$filename1,$sex=0,$nationality=0,$birthday=0) {
		if (!in_array($IDType, array(self::CERTIFICATE_TYPE_1_ID, self::CERTIFICATE_TYPE_2_PASSPORT, self::CERTIFICATE_TYPE_3_DRIVE_LICENSE,self::CERTIFICATE_TYPE_4_FAMILY_BOOK))) throw new TrantorException('IDType');
		if (!V::required($IDNumber)) throw new TrantorException('IDNumber');
		if(!V::required($filename0) || !V::required($filename1)) throw new TrantorException('Filename');
		$status = $this->checkIDVerify()['status'];
		if ($status==self::CERTIFICATE_STATUS_1_PENDING) throw new TrantorException("Don't verify again");
		$sql = "insert into tbl_user_certificate_to_verify(time,user_type,user_id,certificate_type,certificate_number,status)values(now()," . User::USER_TYPE_1_MEMBER . "," . qstr($_SESSION['UID']) . "," . qstr($IDType) . "," . qstr($IDNumber) . ",".self::CERTIFICATE_STATUS_1_PENDING.")";
		$this->execute($sql);
		$mainID=$this->insert_id();
		$sql="insert into tbl_user_certificate_to_verify_copy(main_id,side,filename)VALUES(".$mainID.",".self::ID_SIDE_1_FRONT.",".qstr($filename0)."),(".$mainID.",".self::ID_SIDE_3_HOLDER.",".qstr($filename1).")";
		$this->execute($sql);
		if($sex!=0 && $nationality!=0 && $birthday!=0){
			$u=new User();
			$u->setProfile($sex,$nationality,$birthday);
		}
		return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	public function checkIDVerify() {
		$sql = "select certificate_type,certificate_number,status,remark from tbl_user_certificate_to_verify where user_type=" . User::USER_TYPE_1_MEMBER . " and user_id=" . qstr($_SESSION['UID']) ." order by time desc limit 1";
		return $this->getLine($sql);
	}
} 