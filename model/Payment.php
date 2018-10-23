<?php
use validation\Validator as V;

class Payment extends \Base {

	const DB_RUN = "payment_run";

	const SOURCE_TYPE_1_MEMBER = 1;

	const STATUS_1_PENDING = 1;
	const STATUS_3_COMPLETED = 3;
	const STATUS_4_CANCELLED = 4;

	const BILL_WAS_1_PPWSA=1;
	const BILL_WAS_2_SRWSA=2;

	const TYPE_1_EDC = 1;
	const TYPE_2_WSA=2;

	const BILL_SOURCE_TYPE_1_MEMBER = 1;
	const BILL_SOURCE_TYPE_2_AGENT = 2;

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function saveBillEdc($type, $number, $phone, $amount, $fee, $image, $applicantRemark) {
		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_131_MEMBER_PAY_EDC_BILL))  return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);
		if (!in_array($type, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24])) throw new TrantorException('Type');
		if (!V::required($number)) throw new TrantorException('Number');
		$phone = preg_replace('/^0*/', '', $phone);
		if (!V::required($phone)) throw new TrantorException('Phone');
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::required($image)) throw new TrantorException('Image');

		$phone = '+' . Phone::COUNTRY_CODE_855_CAMBODIA . '-' . $phone;
		$configInfo = $this->_getConfig($amount, "EDC");
		$configFee = $configInfo['fee'];
		$expense = $configInfo['expense'];
		$sourceID = $_SESSION['UID'];
		if (!V::over_num($configFee, 0)) throw new TrantorException('Config Fee <= 0', 2);
		if (bccomp($configFee, $fee, 2) != 0) throw new TrantorException("Fee");
		if ($expense > $configFee) throw new TrantorException('Expense > Fee', 2);

		$deductedAmount = $amount + $configFee;
		$b = new Balance();
		$balance = $b->getBalanceByCurrency(Currency::CURRENCY_KHR);
//		$balance = $balanceInfo['amount'];
		if ($deductedAmount > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$sql = 'insert into tbl_bill_edc (time,type,consumer_number,customer_phone,currency,amount,source_type,source_id,fee,status,ip,applicant_remark) value (now(),' . $type . ',' . qstr($number) . ',' . qstr($phone) . ',' . qstr(Currency::CURRENCY_KHR) . ',' . $amount . ',' . self::SOURCE_TYPE_1_MEMBER . ',' . $sourceID . ',' . $configFee . ',' . self::STATUS_1_PENDING . ',' . qstr(getIP()) . ',' . qstr($applicantRemark) . ')';
		$this->execute($sql);
		$id = $this->insert_id();
		$sql = 'insert into  tbl_bill_edc_attachment(time,bill_id,filename) value (now(),' . $id . ',' . qstr($image) . ')';
		$this->execute($sql);

		$a = new Account();
		$t = new Transaction();
//		$b = new Balance();

		$vRemark = "[Member] Pay EDC Bill #$id";
		$remarkAmount = ", Amount " . Currency::CURRENCY_KHR . ' ' . number_format($amount, 2);
		$remarkFee = ", Fee " . Currency::CURRENCY_KHR . ' ' . number_format($configFee, 2);
		$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_26001_EDC_BILL, 0, 0, Currency::CURRENCY_KHR, $amount, $vRemark);
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_6_PAYMENT, Transaction::TRANSACTION_SUB_TYPE_607_PAY_EDC, Currency::CURRENCY_KHR, -$amount, $id, 0, $voucherID, $vRemark . $remarkAmount);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_KHR, -$amount,$transactionID);
		if ($configFee > 0) {
			$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_46001_EDC_BILL_FEE, 0, 0, Currency::CURRENCY_KHR, $configFee, $vRemark);
			$transactionID=$t->add(Transaction::TRANSACTION_TYPE_6_PAYMENT, Transaction::TRANSACTION_SUB_TYPE_609_PAY_EDC_FEE, Currency::CURRENCY_KHR, -$configFee, 0, $transactionID, $voucherID, $vRemark . $remarkFee);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_KHR, -$configFee,$transactionID);
		}

		$l = new Log();
		$l->addLog($vRemark . $remarkAmount . $remarkFee);

		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array('bill_id' => $id));
	}

	private function _getConfig($amount, $config) {
		if (!in_array($config, ['WSA', 'EDC'])) throw new TrantorException('Config');
		if ($config == 'WSA') return $this->_getConfigWsaByAmount($amount);
		return $this->_getConfigEdcByAmount($amount);
	}

	private function _getConfigWsaByAmount($amount) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');

		if ($amount <= 50000) $split = 1; else if ($amount <= 400000) $split = 2; else $split = 3;

		$sql = 'select fee,commission_agent,expense from tbl_config_wsa where split = ' . $split;
		return $this->getLine($sql);
	}

	private function _getConfigEdcByAmount($amount) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');

		if ($amount <= 100000) $split = 1; else $split = 2;

		$sql = 'select fee,expense from tbl_config_edc where split = ' . $split;
		return $this->getLine($sql);
	}

	public function saveBillWsa($type, $number, $phone, $amount, $fee, $image, $applicantRemark) {
		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_132_MEMBER_PAY_WSA_BILL))  return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);

		if (!in_array($type, array(self::BILL_WAS_1_PPWSA, self::BILL_WAS_2_SRWSA))) throw new TrantorException('Type');
		if (!V::required($number)) throw new TrantorException('Number');
		$phone = preg_replace('/^0*/', '', $phone);
		if (!V::required($phone)) throw new TrantorException('Phone');
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::required($image)) throw new TrantorException('Image');

		$phone = '+' . Phone::COUNTRY_CODE_855_CAMBODIA . '-' . $phone;


		$configInfo = $this->_getConfig($amount, "WSA");
		$configFee = $configInfo['fee'];
		$expense = $configInfo['expense'];
		$sourceID = $_SESSION['UID'];
		if (!V::over_num($configFee, 0)) throw new TrantorException('Config Fee <= 0', 2);
		if (bccomp($configFee, $fee, 2) != 0) throw new TrantorException("Fee");
		if ($expense > $configFee) throw new TrantorException('Expense > Fee', 2);

		$deductedAmount = $amount + $configFee;
		$b = new Balance();
		$balance = $b->getBalanceByCurrency(Currency::CURRENCY_KHR);
		if ($deductedAmount > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$sql = 'insert into tbl_bill_wsa (time,type,bill_number,customer_phone,currency,amount,source_type,source_id,fee,status,ip,applicant_remark) value (now(),' . $type . ',' . qstr($number) . ',' . qstr($phone) . ',' . qstr(Currency::CURRENCY_KHR) . ',' . $amount . ',' . self::SOURCE_TYPE_1_MEMBER . ',' . $sourceID . ',' . $configFee . ',' . self::STATUS_1_PENDING . ',' . qstr(getIP()) . ',' . qstr($applicantRemark) . ')';
		$this->execute($sql);
		$id = $this->insert_id();
		$sql = 'insert into  tbl_bill_wsa_attachment(time,bill_id,filename) value (now(),' . $id . ',' . qstr($image) . ')';
		$this->execute($sql);
		$a = new Account();
		$t = new Transaction();
//		$b = new Balance();

		$vRemark = "[Member] Pay WSA Bill #$id";
		$remarkAmount = ", Amount " . Currency::CURRENCY_KHR . ' ' . number_format($amount, 2);
		$remarkFee = ", Fee " . Currency::CURRENCY_KHR . ' ' . number_format($configFee, 2);
		$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_26002_WSA_BILL, 0, 0, Currency::CURRENCY_KHR, $amount, $vRemark);
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_6_PAYMENT, Transaction::TRANSACTION_SUB_TYPE_603_PAY_WSA, Currency::CURRENCY_KHR, -$amount, $id, 0, $voucherID, $vRemark . $remarkAmount);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_KHR, -$amount,$transactionID);
		if ($configFee > 0) {
			$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_46002_WSA_BILL_FEE, 0, 0, Currency::CURRENCY_KHR, $configFee, $vRemark);
			$transactionID=$t->add(Transaction::TRANSACTION_TYPE_6_PAYMENT, Transaction::TRANSACTION_SUB_TYPE_605_PAY_WSA_FEE, Currency::CURRENCY_KHR, -$configFee, 0, $transactionID, $voucherID, $vRemark . $remarkFee);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_KHR, -$configFee,$transactionID);
		}

		$l = new Log();
		$l->addLog($vRemark . $remarkAmount . $remarkFee);

		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array('bill_id' => $id));
	}

	public function getConfigEdc() {
		$sql = 'select split,fee from tbl_config_edc';
		return $this->getArray($sql);
	}

	public function getConfigWsa() {
		$sql = 'select split,fee from tbl_config_wsa';
		return $this->getArray($sql);
	}

	public function getEdcRecent() {
		$sql = "select MAX(TIME) TIME,customer_phone FROM tbl_bill_edc WHERE source_type=" . qstr(User::USER_TYPE_1_MEMBER) . " and source_id=" . qstr($_SESSION['UID']) . " and status in(" . self::STATUS_1_PENDING . "," . self::STATUS_3_COMPLETED . ")  GROUP BY customer_phone ORDER BY TIME DESC LIMIT 10";
		return $this->getArray($sql);
	}

	public function getWsaRecent() {
		$sql = "select MAX(TIME) TIME,customer_phone FROM tbl_bill_wsa WHERE source_type=" . qstr(User::USER_TYPE_1_MEMBER) . " and source_id=" . qstr($_SESSION['UID']) . " and status in(" . self::STATUS_1_PENDING . "," . self::STATUS_3_COMPLETED . ")  GROUP BY customer_phone ORDER BY TIME DESC LIMIT 10";
		return $this->getArray($sql);
	}

	public function getLatestEdcBill() {
		$sql = 'select customer_phone, type from tbl_bill_edc where source_type=' . self::BILL_SOURCE_TYPE_1_MEMBER . ' and source_id=' . $_SESSION['UID'] . ' and status in(' . self::STATUS_1_PENDING . ',' . self::STATUS_3_COMPLETED . ') order by time desc limit 1';
		return $this->getLine($sql);
	}

	public function getLatestWsaBill() {
		$sql = 'select customer_phone, type from tbl_bill_wsa where source_type=' . self::BILL_SOURCE_TYPE_1_MEMBER . ' and source_id=' . $_SESSION['UID'] . ' and status in(' . self::STATUS_1_PENDING . ',' . self::STATUS_3_COMPLETED . ') order by time desc limit 1';
		return $this->getLine($sql);
	}

	public function getEdcBillByID($ID) {
		if (!V::required($ID)) throw new TrantorException('ID');
		$sql = "select t1.id,t1.time,t1.type,t1.consumer_number,t1.customer_phone,t1.currency,t1.amount,t1.fee,t1.expense,t1.status,applicant_remark,t2.filename from tbl_bill_edc t1 LEFT JOIN tbl_bill_edc_attachment t2 on t1.id=t2.bill_id where t1.id=" . qstr($ID);
		return $this->getLine($sql);
	}

	public function getWsaBillByID($ID) {
		if (!V::required($ID)) throw new TrantorException('ID');
		$sql = "select t1.id,t1.time,t1.type,t1.bill_number,t1.customer_phone,t1.currency,t1.amount,t1.fee,t1.expense,t1.status,applicant_remark,t2.filename from tbl_bill_wsa t1 LEFT JOIN tbl_bill_wsa_attachment t2 on t1.id=t2.bill_id where t1.id=" . qstr($ID);
		return $this->getLine($sql);
	}

	public function getEdcBillList($page) {
		if (!V::over_num($page, 0)) throw new TrantorException("Page");
		$sql = "select id,time,customer_phone,currency,amount,fee,status from tbl_bill_edc where source_type=" . User::USER_TYPE_1_MEMBER . " and source_id=" . qstr($_SESSION['UID']) . " order by time desc";
		return $this->getPageArray($sql, $page);
	}

	public function getWsaBillList($page) {
		if (!V::over_num($page, 0)) throw new TrantorException("Page");
		$sql = "select id,time,customer_phone,currency,amount,fee,status from tbl_bill_wsa where source_type=" . User::USER_TYPE_1_MEMBER . " and source_id=" . qstr($_SESSION['UID']) . " order by time desc";
		return $this->getPageArray($sql, $page);
	}

	public function getPaymentHistory($from, $to, $type, $page) {
		if (!$to) $to = Utils::getDBDate();
		if (!$from) $from = $to;

		$sql1 = 'select a.id,a.time,a.amount,a.fee,a.currency,a.status,1 type from tbl_bill_edc a where a.source_id = ' . qstr($_SESSION['UID']) . ' and a.source_type  =' . self::BILL_SOURCE_TYPE_1_MEMBER . ' and a.time>' . qstr($from) . ' and a.time<' . qstr(Utils::maxTime($to));

		$sql2 = 'select b.id,b.time,b.amount,b.fee,b.currency,b.status,2 type from tbl_bill_wsa b where b.source_id= ' . qstr($_SESSION['UID']) . ' and b.source_type  =' . self::BILL_SOURCE_TYPE_1_MEMBER . ' and b.time>' . qstr($from) . ' and b.time<' . qstr(Utils::maxTime($to));

		$sql = '';
		if ($type) {
			if ($type == self::TYPE_1_EDC) $sql .= $sql1; else $sql .= $sql2;
		} else {
			$sql .= $sql1 . ' union all ' . $sql2;
		}
		$sql .= ' ORDER BY time DESC';

		return $this->getPageArray($sql, $page);
	}

}
 