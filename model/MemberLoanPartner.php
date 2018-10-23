<?php
use validation\Validator as V;

class MemberLoanPartner extends Base {
	const DB_RUN = "loan_run";

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function getLatestBill() {
		$id = $this->_getSamrithisakPartnerID();
		if (!V::over_num($id, 0)) throw new TrantorException('No Partner');
		$sql = 'select date,amount from tbl_member_loan_partner_bill WHERE member_id=' . qstr($_SESSION['UID']) . ' and partner_id=' . qstr($id) . ' order by date desc limit 1';
		$rt = $this->getLine($sql);
		if (!$rt) return null;
		$sql = 'select due_day from tbl_config_partner_lend WHERE partner_id=' . qstr($id);
		$rt['due_day'] = $this->getOne($sql);
		$sql = 'select ifnull(abs(sum(amount)),0) from tbl_member_loan_partner_loan_flow WHERE member_id=' . qstr($_SESSION['UID']) . ' and partner_id=' . qstr($id) . ' and type=' . Constant::WITHDRAW . ' and time>=' . qstr($rt['date']);
		$rt['paid_amount'] = $this->getOne($sql);
		return $rt;
	}

	private function _getSamrithisakPartnerID() {
		$u = new User();
		return $u->getPartnerIDByCode(Constant::PARTNER_8888_SAMRITHISAK);
	}

	public function loanFromSamrithisak($amount, $serviceCharge) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($serviceCharge, 0)) throw new TrantorException('ServiceCharge');
		$chk = $this->checkMemberLoanPartner($amount, $serviceCharge);
		if ($chk['err_code'] != MessageCode::ERR_0_NO_ERROR) return $chk;

		$partnerID = $this->_getSamrithisakPartnerID();
		if (!V::over_num($partnerID, 0)) throw new TrantorException('No partner');
		$b = new Balance();
		$t = new Transaction();

		$sql = "update tbl_member_loan_partner set loan=loan+$amount where member_id=" . qstr($_SESSION['UID']) . " and partner_id=" . qstr($partnerID);
		$this->execute($sql);

		$insertID = $this->_addFlow($amount, $partnerID, Constant::DEPOSIT);
		$ac = new Account();
		$vRemark = 'Member ' . $_SESSION['PHONE'] . ' Loan from Partner #' . Constant::PARTNER_8888_SAMRITHISAK . ', ' . Currency::CURRENCY_USD . ' ' . number_format($amount, 2);
		$voucherID = $ac->keepAccounts(Account::ID_12012_MEMBER_LOAN_FROM_PARTNER, Account::ID_20000_MEMBER_BALANCE, 0, 0, Currency::CURRENCY_USD, $amount, $vRemark);
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_236_PARTNER_LOAN, Currency::CURRENCY_USD, $amount, $insertID, 0, $voucherID, $vRemark);
		$b->updateCurrentMemberBalance(Constant::DEPOSIT, Currency::CURRENCY_USD, $amount, $transactionID);

		$voucherID = $ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_23012_PARTNER_LEND_SERVICE_CHARGE, 0, 0, Currency::CURRENCY_USD, $serviceCharge, $vRemark);
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_239_PARTNER_LOAN_SERVICE_CHARGE, Currency::CURRENCY_USD, -$serviceCharge, 0, $insertID, $voucherID, $vRemark);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_USD, -$serviceCharge, $transactionID);
		return array('code' => MessageCode::ERR_0_NO_ERROR, 'id' => $insertID);
	}

	public function checkMemberLoanPartner($amount, $serviceCharge) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($serviceCharge, 0)) throw new TrantorException('ServiceCharge');

		$cf = $this->getSamrithisakLoanConfig();
		$creditLoan = $cf['credit_loan'];
		$loanConfig = $cf['loan_config'];
		if (!$creditLoan || !$loanConfig) return $cf;

		$realMaxLoan = min($cf['max_loan'], $loanConfig['max_loan_credit_per_member'], $loanConfig['available_loan']);
		if ($loanConfig['is_over_member_count_limit']) return array('err_code' => MessageCode::ERR_1830_OVER_MEMBER_COUNT, 'result' => $cf);

		if ($amount + $creditLoan['loan'] > $creditLoan['credit']) return array('err_code' => MessageCode::ERR_1831_OVER_CREDIT, 'result' => $cf);

		$charge = $loanConfig['service_charge_rate'] * 0.01 * $amount;
		if ($charge < $loanConfig['service_charge_min_amount']) $charge = $loanConfig['service_charge_min_amount'];
		if (bccomp($serviceCharge, $charge, 2) != 0) return array('err_code' => MessageCode::ERR_1832_SERVICE_CHARGE_NOT_MATCH, 'result' => $cf);

		if ($amount < $charge) return array('err_code' => MessageCode::ERR_1833_NEED_GREATER_THAN_SERVICE_CHARGE, 'result' => $cf);

		if ($amount < $cf['min_loan_credit_per_member']) return array('err_code' => MessageCode::ERR_1834_CAN_NOT_LESS_THAN_MIN_LOAN, 'result' => $cf);

		if ($amount + $creditLoan['loan'] > $realMaxLoan) return array('err_code' => MessageCode::ERR_1836_CAN_NOT_GREATER_THAN_MAX_LOAN, 'result' => $cf);

		if ($amount + $cf['pending_amount'] + $cf['total_balance'] > $cf['max_balance']) return array('err_code' => MessageCode::ERR_1723_OVER_MAX_BALANCE_LIMIT, 'result' => $cf);

		return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	public function getSamrithisakLoanConfig() {
		$t = new Transaction();
		$rt['pending_amount'] = $t->getDepositPendingAmount($_SESSION['UID']);
		$b = new Balance();
		$rt['total_balance'] = $b->getTotalBalanceUSD($_SESSION['UID']);
		$temp = $t->getDepositOrWithdrawLimitAmount(Constant::DEPOSIT, Currency::CURRENCY_USD);
		$rt['max_balance'] = $temp['max_balance'];
		$c = new Config();
		$rt['max_loan'] = $c->getConfigLimitMaxLoan($_SESSION['LEVEL'], Currency::CURRENCY_USD);
		$rt['credit_loan'] = $this->getSamrithisakCreditAndLoan();
		$rt['loan_config'] = $this->_getSamrithisakLoanConfig();
		return $rt;
	}

	public function getSamrithisakCreditAndLoan() {
		$id = $this->_getSamrithisakPartnerID();
		if (!V::over_num($id, 0)) return null;
		$sql = 'select t1.credit, t1.loan from tbl_member_loan_partner t1 where t1.member_id=' . qstr($_SESSION['UID']) . ' and t1.partner_id =' . $id;
		return $this->getLine($sql);
	}

	private function _getSamrithisakLoanConfig() {
		$partnerID = $this->_getSamrithisakPartnerID();
		if (!V::over_num($partnerID, 0)) throw new TrantorException('No Partner');
		$rt = $this->_getConfigPartnerLendByPartnerID($partnerID);
		if (!$rt) return null;
		$rt['available_loan'] = $rt['member_total_loan_limit'] - $this->_getCurrentTotalLoanAmountByPartnerID($partnerID);
		$currentMemberCount = $this->_getCurrentMemberCountByPartnerID($partnerID);
		$hadLoan = $this->_checkMemberHadLoanByPartnerID($partnerID);
		$rt['is_over_member_count_limit'] = $currentMemberCount + ($hadLoan ? 0 : 1) > $rt['member_limit'];
		return $rt;
	}

	private function _getConfigPartnerLendByPartnerID($partnerID) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		$sql = 'select service_charge_rate,service_charge_min_amount,min_loan_credit_per_member,max_loan_credit_per_member,member_limit,member_total_loan_limit FROM tbl_config_partner_lend  WHERE partner_id=' . $partnerID . ' and status=' . Constant::STATUS_1_ACTIVE;
		return $this->getLine($sql);
	}

	private function _getCurrentTotalLoanAmountByPartnerID($partnerID) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		$sql = 'select sum(loan) from tbl_member_loan_partner WHERE partner_id=' . qstr($partnerID);
		return $this->getOne($sql);
	}

	private function _getCurrentMemberCountByPartnerID($partnerID) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		$sql = 'select count(member_id) from tbl_member_loan_partner WHERE partner_id=' . qstr($partnerID);
		return $this->getOne($sql);
	}

	private function _checkMemberHadLoanByPartnerID($partnerID) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		$sql = 'select count(member_id) from tbl_member_loan_partner WHERE member_id=' . qstr($_SESSION['UID']) . ' and partner_id=' . qstr($partnerID);
		$hadLoan = $this->getOne($sql);
		return $hadLoan >= 1;
	}

	private function _addFlow($amount, $partnerID, $type) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		if (!in_array($type, [Constant::BEGINNING, Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		$sql = "insert into tbl_member_loan_partner_loan_flow(time,member_id,partner_id,type,currency,amount)VALUES (now()," . qstr($_SESSION['UID']) . "," . qstr($partnerID) . ",$type," . qstr(Currency::CURRENCY_USD) . ",$amount)";
		$this->execute($sql);
		return $this->insert_id();
	}

	public function returnSamrithisakLoan($amount) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		$loan = $this->getSamrithisakCreditAndLoan();
		if (!$loan) throw new TrantorException('No Loan');
		if ($amount > $loan['loan']) throw new TrantorException('Amount');
		$partnerID = $this->_getSamrithisakPartnerID();
		if (!V::over_num($partnerID, 0)) throw new TrantorException('No partner');
		$b = new Balance();
		$t = new Transaction();
		if ($b->getBalanceByCurrency(Currency::CURRENCY_USD) < $amount) throw new TrantorException('More than balance');

		$sql = "update tbl_member_loan_partner set loan=loan-$amount where member_id=" . qstr($_SESSION['UID']) . " and partner_id=" . qstr($partnerID);
		$this->execute($sql);

		$insertID = $this->_addFlow($amount, $partnerID, Constant::WITHDRAW);

		$ac = new Account();
		$vRemark = 'Member ' . $_SESSION['PHONE'] . ' Return loan to Partner #' . Constant::PARTNER_8888_SAMRITHISAK . ', ' . Currency::CURRENCY_USD . ' ' . number_format($amount, 2);
		$voucherID = $ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_12012_MEMBER_LOAN_FROM_PARTNER, 0, 0, Currency::CURRENCY_USD, $amount, $vRemark);
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_237_RETURN_PARTNER_LOAN, Currency::CURRENCY_USD, -$amount, 0, $insertID, $voucherID, $vRemark);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_USD, -$amount, $transactionID);
		return array('code' => MessageCode::ERR_0_NO_ERROR, 'id' => $insertID);
	}

	public function checkReturnLoan($amount) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		$rt = $this->getSamrithisakCreditAndLoan();
		if (!$rt) throw new TrantorException('No Loan');
		if ($amount > $rt['loan']) return array('err_code' => MessageCode::ERR_1838_OVER_RETURN_LOAN);
		$partnerID = $this->_getSamrithisakPartnerID();
		if (!V::over_num($partnerID, 0)) throw new TrantorException('No partner');
		$b = new Balance();
		if ($b->getBalanceByCurrency(Currency::CURRENCY_USD) < $amount) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE, 'result' => $rt);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	public function getSamrithisakLoanHistory($page) {
		if (!V::over_num($page, 0)) throw new TrantorException('Page');
		$sql = 'select t.time,t.type,t.amount,concat(t2.code,t2.name) partner from tbl_member_loan_partner_loan_flow t LEFT JOIN db_system_run.tbl_partner t2 on t.partner_id=t2.id WHERE t.member_id=' . qstr($_SESSION['UID']) . ' order by t.time desc';
		return $this->getPageArray($sql, $page);

	}

	public function getSamrithisakServiceCharge() {
		$partnerID = $this->_getSamrithisakPartnerID();
		if (!V::over_num($partnerID, 0)) throw new TrantorException('No Partner');
		$sql = 'select ifnull(service_charge_rate,0) service_charge_rate,ifnull(service_charge_min_amount,0) service_charge_min_amount FROM tbl_config_partner_lend  WHERE partner_id=' . $partnerID . ' and status=' . Constant::STATUS_1_ACTIVE;
		$rt = $this->getLine($sql);
		if (!$rt['service_charge_rate']) $rt['service_charge_rate'] = '0';
		if (!$rt['service_charge_min_amount']) $rt['service_charge_min_amount'] = '0';
		return $rt;
	}

}