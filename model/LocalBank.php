<?php
use validation\Validator as V;

class LocalBank extends Base {

	public function __construct() {
		parent::__construct(Constant::MAIN_DB_RUN);
	}

	public function addMemberBankAccount($currency,$bankCode,$accountNo){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if(!V::required($bankCode)) throw new TrantorException('BankCode');
		if(!V::required($accountNo)) throw new TrantorException('AccountNo');

		if($this->_checkMemberBankAccount($bankCode, $accountNo)) return MessageCode::ERR_1710_BANK_ACCOUNT_EXISTS;

		$sql="insert into tbl_member_bank_account (member_id,bank_code,account_no,currency,account_name,status) values (".qstr($_SESSION['UID']).",".qstr($bankCode).",".qstr($accountNo).",".qstr($currency).",".qstr($_SESSION['NAME']).",".Constant::STATUS_1_ACTIVE.")";
		$this->execute($sql);

		return MessageCode::ERR_0_NO_ERROR;
	}

	private function _checkMemberBankAccount($bankCode, $accountNo) {
		if (!V::required($bankCode)) throw new TrantorException('BankCode');
		if (!V::required($accountNo)) throw new TrantorException('AccountNo');
		$sql = "select id from tbl_member_bank_account where bank_code=" . qstr($bankCode) . " and account_no=" . qstr($accountNo);
		return $this->getOne($sql);
	}

	public function deleteMemberBankAccount($ID) {
		if (!V::over_num($ID, 0)) throw new TrantorException('ID');
		$sql = 'DELETE FROM tbl_member_bank_account WHERE member_id = ' . qstr($_SESSION['UID']) . ' and id = '.$ID;
		$this->execute($sql);
		$id = $this->affected_rows();
		return $id >0 ? MessageCode::ERR_0_NO_ERROR : MessageCode::ERR_1_ERROR;
	}

	public function getBankAccountList($currency, $flagMemberDeposit = 0){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);

		$sql = 'SELECT t1.bank_code code,t1.account_no,t1.account_name,t1.currency,t2.name FROM tbl_bank_account t1 LEFT JOIN tbl_bank t2 on(t1.bank_code=t2.code) WHERE 1';
		if($flagMemberDeposit>0) $sql.=' AND t1.flag_member_deposit = '.Constant::YES;
		$sql.=' AND t1.status = ' . Constant::STATUS_1_ACTIVE . ' AND t2.status = ' . Constant::STATUS_1_ACTIVE . ' AND t1.currency = ' . qstr($currency).' ORDER BY t1.bank_code,t1.id';
		return $this->getArray($sql);
	}

	public function getMemberBankAccountList($currency){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);

		$sql = 'SELECT t1.id bank_id,t1.bank_code code,t1.account_no,t1.currency,t1.account_name,t2.name FROM tbl_member_bank_account t1 LEFT JOIN tbl_bank t2 on(t1.bank_code=t2.code) WHERE t1.status = ' . Constant::STATUS_1_ACTIVE . ' AND t2.status = ' . Constant::STATUS_1_ACTIVE . ' AND t1.currency = ' . qstr($currency) . ' AND t1.member_id = ' . qstr($_SESSION['UID']).' ORDER BY t1.bank_code,t1.id';
		return $this->getArray($sql);
	}

//$type Deposit,withdraw
	public function getBankOneDayAmountList($type,$currency){
		if(!in_array($type,array(Constant::WITHDRAW,Constant::DEPOSIT))) throw new TrantorException("Type");
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$sql='select bank_code,ifnull(abs(sum(amount)),0) amount from tbl_user_bank_transaction_application where user_type='.User::USER_TYPE_1_MEMBER.' and user_id='.qstr($_SESSION['UID']) .' and currency='.qstr($currency).' and type='.qstr($type).' and (status='.Constant::STATUS_1_PENDING.' or status='.Constant::STATUS_3_COMPLETED.') and date(time) = curdate()' .' group by bank_code,currency';
		$rt=$this->getArray($sql);
		return $rt;
	}

	public function checkBankExisted($code) {
		if (!V::required($code)) throw new TrantorException('Code');
		$sql = "select 1 from tbl_bank where code=" . qstr($code);
		return $this->getOne($sql) > 0;
	}

	public function getBankConfigByBankCode($bankCode){
		if(!V::required($bankCode)) throw new TrantorException('BankCode');
		$sql="select currency,transfer_amount_unit,transfer_amount_unit_fee,transfer_amount_max_per_day from tbl_bank_config where bank_code=".qstr($bankCode);
		return $this->getLine($sql);
	}

	public function getBankConfig($currency) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$sql = "select bank_code,transfer_amount_unit,transfer_amount_unit_fee,transfer_amount_max_per_day from tbl_bank_config where currency=" . qstr($currency);
		return $this->getArray($sql);
	}

	public function getBankConfigOfMemberBankAccount($currency){
		$sql='select distinct bank_code code from tbl_member_bank_account where member_id='.qstr($_SESSION['UID']).' AND status = ' . Constant::STATUS_1_ACTIVE . ' AND currency = ' . qstr($currency);
		$list= $this->getArray($sql);
		if(!$list) return null;
		$s='';
		for($i=0,$n=count($list);$i<$n;$i++){
			$s.=qstr($list[$i]['code']);
			$s.=",";
		}
		$s=substr($s,0,strlen($s)-1);
		$sql="select bank_code,transfer_amount_unit,transfer_amount_unit_fee,transfer_amount_max_per_day from tbl_bank_config where bank_code in(" .$s.")";
		return $this->getArray($sql);
	}

	public function getBankAccountID($bankCode,$accountNo){
		if(!V::required($bankCode)) throw new TrantorException('BankCode');
		if(!V::required($accountNo)) throw new TrantorException('AccountNo');
		$sql = "select id from tbl_bank_account where bank_code=".qstr($bankCode) . " and account_no=".qstr($accountNo);
		return $this->getOne($sql);
	}

//	public function checkMaxAmountPerDay($bankCode,$amount,$currency){
//		$rt=$this->getBankConfigByBankCodeAndCurrency($bankCode,$currency);
//		return $amount<= $rt['transfer_amount_max_per_day'];
//	}
}