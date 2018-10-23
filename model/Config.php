<?php
use validation\Validator as V;

class Config extends Base {

	const WITHDRAW_CONFIG_STATUS_1_ACTIVE=1;
	const WITHDRAW_CONFIG_STATUS_2_SUSPENDED=2;
	const WITHDRAW_CONFIG_STATUS_3_INACTIVE=3;

	const DEPOSIT_WITHDRAW_TYPE_2_DEPOSIT=2;
	const DEPOSIT_WITHDRAW_TYPE_3_WITHDRAW=3;


	public function __construct($db = null) {
		parent::__construct($db ? $db : Constant::MAIN_DB_RUN);
	}

	public function getMemberMaxBalance($memberID, $level, $currency = Currency::CURRENCY_USD) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('member ID');
		if (!V::over_num($level, 0)) throw new TrantorException('UserLevel');
		$sql = 'select cash_max from db_system_run.tbl_member_limit_cash where member_id = ' . $memberID;
		$maxBalance = $this->getOne($sql);
		if (!$maxBalance && $maxBalance !== 0) {
			$maxBalance = $this->getConfigLimitMaxBalance(User::USER_TYPE_1_MEMBER, $level);
		}
		if ($currency == Currency::CURRENCY_USD) return $maxBalance;
		$c = new Currency();
		return $c->transferAmount($maxBalance, Currency::CURRENCY_USD, $currency);
	}

	public function getPartnerMaxBalance($partnerID, $level, $currency = Currency::CURRENCY_USD) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('Partner ID');
		if (!V::over_num($level, 0)) throw new TrantorException('UserLevel');
		$sql = 'select cash_max from db_system_run.tbl_partner_limit_cash where partner_id = ' . $partnerID;
		$maxBalance = $this->getOne($sql);
		if (!$maxBalance && $maxBalance !== 0) {
			$maxBalance = $this->getConfigLimitMaxBalance(User::USER_TYPE_3_PARTNER, $level);
		}
		if ($currency == Currency::CURRENCY_USD) return $maxBalance;
		$c = new Currency();
		return $c->transferAmount($maxBalance, Currency::CURRENCY_USD, $currency);
	}

	public function getMerchantMaxBalance($merchantID, $level, $currency = Currency::CURRENCY_USD) {
		if (!V::over_num($merchantID, 0)) throw new TrantorException('Merchant ID');
		if (!V::over_num($level, 0)) throw new TrantorException('UserLevel');
		$sql = 'select cash_max from db_system_run.tbl_merchant_limit_cash where merchant_id = ' . $merchantID;
		$maxBalance = $this->getOne($sql);
		if (!$maxBalance && $maxBalance !== 0) {
			$maxBalance = $this->getConfigLimitMaxBalance(User::USER_TYPE_4_MERCHANT, $level);
		}
		if ($currency == Currency::CURRENCY_USD) return $maxBalance;
		$c = new Currency();
		return $c->transferAmount($maxBalance, Currency::CURRENCY_USD, $currency);
	}

	public function getConfigLimitMaxBalance($userType, $level){
		if (!V::over_num($level, 0)) throw new TrantorException('UserLevel');
		$sql = 'select max_balance from tbl_config_limit where user_type = '.qstr($userType).' and user_level = '.$level;
		$balance = $this->getOne($sql);
		if(!$balance) $balance = 0;
		return $balance;
	}

	private function _getConfigLimitMaxLoan($level){
		if (!V::over_num($level, 0)) throw new TrantorException('UserLevel');
		$sql = 'select max_loan from tbl_config_limit where user_type = '.User::USER_TYPE_1_MEMBER.' and user_level = '.$level;
		$balance = $this->getOne($sql);
		if(!$balance) $balance = 0;
		return $balance;
	}

	public function getConfigLimitMaxLoan($level,$currency){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$maxLoan=$this->_getConfigLimitMaxLoan($level);
		$c = new Currency();
		return $c->transferAmount($maxLoan,Currency::CURRENCY_USD,$currency);
	}

	public function  getTransferLimitConfig($level){
		if (!V::over_num($level, 0)) throw new TrantorException('UserLevel');
		$sql = 'select currency,max_balance,max_transfer_per_time,max_transfer_per_day,max_loan from tbl_config_limit where user_type = '.User::USER_TYPE_1_MEMBER.' and user_level = '.$level;
		return $this->getLine($sql);
	}

	public function getDepositLimitConfig($userType,$level,$currency) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$rt=$this->_getDepositLimitConfig($userType,$level);
		if (empty($rt)) return array('max_balance'=>0,'max_deposit_per_time'=>0,'max_deposit_per_day'=>0,'max_loan'=>0,'currency'=>$currency);
		$c = new Currency();
		$a['currency'] = $currency;
		$a['max_deposit_per_time'] = $c->transferAmount($rt['max_deposit_per_time'],Currency::CURRENCY_USD, $currency);
		$a['max_deposit_per_day'] = $c->transferAmount($rt['max_deposit_per_day'],Currency::CURRENCY_USD, $currency);
		$a['max_loan']=$c->transferAmount($rt['max_loan'],Currency::CURRENCY_USD,$currency);
		return $a;
	}


	//这里设置的currency只有USD,所以其他地方注意转换
	private  function _getDepositLimitConfig($userType,$level){
		if(!in_array($userType,array(User::USER_TYPE_1_MEMBER,User::USER_TYPE_2_AGENT,User::USER_TYPE_3_PARTNER))) throw new TrantorException("User Type Error");
		if(!V::over_num($level,0)) throw new TrantorException("User lever Error");
		$sql="select max_deposit_per_time,max_deposit_per_day,max_loan from tbl_config_limit where user_type=".qstr($userType)." and user_level=".qstr($level);
		return $this->getLine($sql);
	}

	private  function _getWithdrawLimitConfig($userType,$level){
		if(!in_array($userType,array(User::USER_TYPE_1_MEMBER,User::USER_TYPE_2_AGENT,User::USER_TYPE_3_PARTNER))) throw new TrantorException("User Type Error");
		if(!V::over_num($level,0)) throw new TrantorException("User lever Error");
		$sql="select max_withdraw_per_time,max_withdraw_per_day,max_loan from tbl_config_limit where user_type=".qstr($userType)." and user_level=".qstr($level);
		return $this->getLine($sql);
	}

	public function getWithdrawLimitConfig($userType,$level,$currency) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$rt=$this->_getWithdrawLimitConfig($userType,$level);
		if (empty($rt)) return array('max_balance'=>0,'max_withdraw_per_time'=>0,'max_withdraw_per_day'=>0,'max_loan'=>0,'currency'=>$currency);
		$c = new Currency();
		$a['currency'] = $currency;
		$a['max_withdraw_per_time'] = $c->transferAmount($rt['max_withdraw_per_time'],Currency::CURRENCY_USD, $currency);
		$a['max_withdraw_per_day'] = $c->transferAmount($rt['max_withdraw_per_day'],Currency::CURRENCY_USD, $currency);
		$a['max_loan']=$c->transferAmount($rt['max_loan'],Currency::CURRENCY_USD,$currency);
		return $a;
	}

	// Withdraw

	public function getWithdrawConfig() {
		$sql = "select currency,fee_unit,fee_amount,status from tbl_config_deposit_withdraw_fee where user_type=" . User::USER_TYPE_1_MEMBER." and type=".self::DEPOSIT_WITHDRAW_TYPE_3_WITHDRAW." and status=".self::WITHDRAW_CONFIG_STATUS_1_ACTIVE;
		return $this->getLine($sql);
	}

	public function getMemberTransferLimitConfig($userType,$level,$currency) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$rt=$this->_getTransferLimitConfig($userType,$level);
		if (empty($rt)) return array('max_balance'=>0,'max_transfer_per_time'=>0,'max_transfer_per_day'=>0,'max_loan'=>0,'currency'=>$currency,'day_amount'=>0);
		$c = new Currency();
		$a['currency'] = $currency;
		$a['max_transfer_per_time'] = $c->transferAmount($rt['max_transfer_per_time'],Currency::CURRENCY_USD, $currency);
		$a['max_transfer_per_day'] = $c->transferAmount($rt['max_transfer_per_day'],Currency::CURRENCY_USD, $currency);
		$a['max_loan']=$c->transferAmount($rt['max_loan'],Currency::CURRENCY_USD,$currency);
		$t = new Transfer();
		$a['day_amount'] = $t->_getOneDayTransferAmountUSD();

		return $a;
	}

	private  function _getTransferLimitConfig($userType,$level){
		if(!in_array($userType,array(User::USER_TYPE_1_MEMBER,User::USER_TYPE_2_AGENT,User::USER_TYPE_3_PARTNER))) throw new TrantorException("User Type Error");
		if(!V::over_num($level,0)) throw new TrantorException("User lever Error");
		$sql="select max_transfer_per_time,max_transfer_per_day,max_loan from tbl_config_limit where user_type=".qstr($userType)." and user_level=".qstr($level);
		return $this->getLine($sql);
	}

}