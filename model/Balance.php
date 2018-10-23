<?php
use validation\Validator as V;

class Balance extends \Base {

	const PARTNER_MONEY_TYPE_1_MAIN = 1;
	const PARTNER_MONEY_TYPE_2_API = 2;
	const PARTNER_MONEY_TYPE_3_BILL_PAYMENT = 3;
	const PARTNER_MONEY_TYPE_4_LEND_DEPOSIT = 4;
	const PARTNER_MONEY_TYPE_5_LEND_SERVICE_CHARGE = 5;
	const PARTNER_MONEY_TYPE_6_PAYROLL = 6;

	public function __construct() {
		parent::__construct(System::DB_RUN);
	}

	public function getBalance() {
		$sql = 'SELECT currency,amount FROM tbl_member_balance where member_id = ' . qstr($_SESSION['UID']);
		$rs = Utils::sortBySpecifiedValues($this->getArray($sql),'currency',[Currency::CURRENCY_USD,Currency::CURRENCY_KHR,Currency::CURRENCY_VND,Currency::CURRENCY_THB]);
		return $rs;
	}

	//返回的是转换为USD的总共余额
	public function getTotalBalanceUSD($memberID) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		$sql = 'SELECT SUM(t1.amount/t2.exchange) FROM tbl_member_balance t1 LEFT JOIN tbl_currency t2 ON (t1.currency = t2.currency) WHERE t1.member_id = ' . $memberID.' GROUP BY t1.member_id';
		$balance = $this->getOne($sql);
		if(!$balance) $balance = 0;
		return $balance;
	}

	//返回的是Partner转换为USD的总共余额
	public function getPartnerTotalBalanceUSD($partnerID) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		$sql = 'SELECT SUM(t1.amount/t2.exchange) FROM tbl_partner_balance t1 LEFT JOIN tbl_currency t2 ON (t1.currency = t2.currency) WHERE t1.partner_id = ' . $partnerID.' GROUP BY t1.partner_id';
		$balance = $this->getOne($sql);
		if(!$balance) $balance = 0;
		return $balance;
	}

	//返回的是Merchant转换为USD的总共余额
	public function getMerchantTotalBalanceUSD($merchantID) {
		if (!V::over_num($merchantID, 0)) throw new TrantorException('MerchantID');
		$sql = 'SELECT SUM(t1.amount/t2.exchange) FROM tbl_merchant_balance t1 LEFT JOIN tbl_currency t2 ON (t1.currency = t2.currency) WHERE t1.merchant_id = ' . $merchantID.' GROUP BY t1.merchant_id';
		$balance = $this->getOne($sql);
		if(!$balance) $balance = 0;
		return $balance;
	}

	//获取某种货币余额
	public function getBalanceByCurrency($currency, $memberID = '') {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!$memberID) $memberID = $_SESSION['UID'];
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		$sql = 'select ifnull(amount,0) amount FROM tbl_member_balance where member_id = ' . qstr($memberID) . ' AND currency=' . qstr($currency);
		return $this->getOne($sql);
	}


	public function getTotalBalanceByCurrency($currency){
		$totalBalance=$this->getTotalBalanceUSD($_SESSION['UID']);
		$c = new Currency();
		return $c->transferAmount(abs($totalBalance),Currency::CURRENCY_USD,$currency);
	}

	public function updateCurrentMemberBalance($type, $currency, $amount,$transactionID=0) {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$balance=$this->getBalanceByCurrency($currency);
		if (!is_numeric($amount) || ($type == Constant::DEPOSIT && $amount <= 0) || ($type == Constant::WITHDRAW && ($amount >= 0||abs($amount)>$balance))) throw new TrantorException('Amount');

		$this->_updateBalance($type, $currency, $amount, $_SESSION['UID'],$transactionID);
	}

	public function updateOtherMemberBalance($type, $currency, $amount, $memberID) {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		$balance=$this->getBalanceByCurrency($currency,$memberID);
		if (!is_numeric($amount) || ($type == Constant::DEPOSIT && $amount <= 0) || ($type == Constant::WITHDRAW &&($amount >= 0||abs($amount)>$balance))) throw new TrantorException('Amount');

		$this->_updateBalance($type, $currency, $amount, $memberID);
	}

	private function _updateBalance($type, $currency, $amount, $memberID,$transactionID=0){
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!is_numeric($amount) || ($type == Constant::DEPOSIT && $amount <= 0) || ($type == Constant::WITHDRAW && $amount >= 0)) throw new TrantorException('Amount');
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		if ($transactionID && !V::over_num($transactionID,0)) throw new TrantorException('TransactionID');
		$sql = 'insert into tbl_member_balance (member_id, currency, amount) values(' . qstr($memberID) . ',' . qstr($currency) . ',' . $amount . ') on duplicate key update amount=amount+values(amount)';
		$this->execute($sql);
//		if($transactionID){
			$sql = 'insert into tbl_member_balance_flow (time, member_id, type, currency, amount,transaction_id) values(now(),' . qstr($memberID) . ',' . $type . ',' . qstr($currency) . ',' . $amount . ','.$transactionID.')';
//		}else{
//			$sql = 'insert into tbl_member_balance_flow (time, member_id, type, currency, amount) values(now(),' . qstr($memberID) . ',' . $type . ',' . qstr($currency) . ',' . $amount.')';
//		}
		$this->execute($sql);
	}

	public function updatePartnerBalance($partnerID, $moneyType, $type, $currency, $amount, $transactionID) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!in_array($moneyType, [self::PARTNER_MONEY_TYPE_1_MAIN, self::PARTNER_MONEY_TYPE_2_API, self::PARTNER_MONEY_TYPE_3_BILL_PAYMENT, self::PARTNER_MONEY_TYPE_4_LEND_DEPOSIT, self::PARTNER_MONEY_TYPE_5_LEND_SERVICE_CHARGE, self::PARTNER_MONEY_TYPE_6_PAYROLL])) throw new TrantorException("Partner Money Type Error");
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($transactionID, 0)) throw new TrantorException('TransactionID');
		$balance = $this->getOne('select ifnull(amount,0) amount FROM tbl_partner_balance where partner_id = ' . qstr($partnerID) . ' AND currency=' . qstr($currency));
		if (!is_numeric($amount) || ($type == Constant::DEPOSIT && $amount <= 0) || ($type == Constant::WITHDRAW && ($amount >= 0 || abs($amount) > $balance))) throw new TrantorException('Amount');

		$sql = 'insert into tbl_partner_balance (partner_id, money_type, currency, amount) values(' . qstr($partnerID) . ',' . qstr($moneyType) . ',' . qstr($currency) . ',' . $amount . ') on duplicate key update amount=amount+values(amount)';
		$this->execute($sql);

		$sql = 'insert into tbl_partner_balance_flow (time, partner_id, money_type, type, currency, amount,transaction_id) values(now(),' . qstr($partnerID) . ',' . qstr($moneyType) . ',' . $type . ',' . qstr($currency) . ',' . $amount . ','.qstr($transactionID).')';
		$this->execute($sql);
	}

	public function updateMerchantBalance($merchantID, $type, $currency, $amount) {
		if (!V::over_num($merchantID, 0)) throw new TrantorException('MerchantID');
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$balance=$this->getOne('select ifnull(amount,0) amount FROM tbl_merchant_balance where merchant_id = ' . qstr($merchantID) . ' AND currency=' . qstr($currency));
		if (!is_numeric($amount) || ($type == Constant::DEPOSIT && $amount <= 0) || ($type == Constant::WITHDRAW &&($amount >= 0||abs($amount)>$balance))) throw new TrantorException('Amount');

		$sql = 'insert into tbl_merchant_balance (merchant_id, currency, amount) values(' . qstr($merchantID) . ',' . qstr($currency) . ',' . $amount . ') on duplicate key update amount=amount+values(amount)';
		$this->execute($sql);

		$sql = 'insert into tbl_merchant_balance_flow (time, merchant_id, type, currency, amount) values(now(),' . qstr($merchantID) . ',' . $type . ',' . qstr($currency) . ',' . $amount . ')';
		$this->execute($sql);
	}

	public function getBalanceFlowList($currency,$page){
		if(!Currency::check($currency)) throw new TrantorException('Currency');
		if(!V::over_num($page,0)) throw new TrantorException('Page');
		$sql='select time,type,amount from tbl_member_balance_flow WHERE member_id='.qstr($_SESSION['UID']) .' and currency='.qstr($currency).' order by time';
		return $this->getPageArray($sql,$page);
	}

	public function getOutstanding($marketID,$currency){
		$sql="select amount from tbl_member_outstanding where market_id=".qstr($marketID)." and currency=".qstr($currency)." and member_id=".qstr($_SESSION['UID']);
		return $this->getOne($sql);
	}

	/*下注前锁credit*/
	function lockBalance($amount) {
		if (!V::over_num($amount, 0)) throw new Exception("User::lockBalance Parameter Error amount");
		$sql = "update tbl_member_balance set amount=amount-$amount where member_id=" . qstr($_SESSION['UID']) . " and amount-$amount>=0";
		$this->execute($sql);
		$temp = $this->affected_rows() > 0;
		$sql = 'insert into tbl_member_balance_lock (member_id, currency, lock_amount) values(' . qstr($_SESSION['UID']) . ',' . qstr(Currency::CURRENCY_USD) . ',' . qstr($amount) . ') on duplicate key update lock_amount=lock_amount+values(lock_amount)';
		$this->execute($sql);
		return $temp;
	}

	/* 下注后根据实际的接受情况将金额还原到 Balance 或调整到 Outstanding 中，正常情况，lockBalance 应该恢复为0 */
	function unlockBalance($lockAmount, $acceptedAmount, $marketID) {
		if (!V::over_num($lockAmount, 0)) throw new TrantorException("Lock Amount");
		if (!is_numeric($acceptedAmount)) throw new TrantorException("Accepted Amount");
		if (!V::over_num($marketID, 0)) throw new TrantorException('Market ID');
		if (1.0 * $lockAmount + \Constant::EPSILON < $acceptedAmount) throw new TrantorException("acceptedAmount($acceptedAmount)>lockAmount($lockAmount), user_id:{$_SESSION['UID']}");

		$sql = "insert into tbl_member_outstanding(member_id,market_id,currency,amount,flag_new) values(" . qstr($_SESSION['UID']) . "," . qstr($marketID) . "," . qstr(Currency::CURRENCY_USD) . "," . qstr($acceptedAmount) . "," . qstr(Constant::YES) . ") on duplicate key update amount=amount+values(amount),flag_new=" . Constant::YES;
		$this->execute($sql);

		$sql = "update tbl_member_balance set amount=amount+$lockAmount-$acceptedAmount where member_id=" . qstr($_SESSION['UID']);
		$this->execute($sql);
		$sql = "update tbl_member_balance_lock set lock_amount=lock_amount-$lockAmount where member_id=" . qstr($_SESSION['UID']);
		$this->execute($sql);
	}
}