<?php
use validation\Validator as V;

class Transfer extends \Base {

	const DB_RUN = "transfer_run";

	const TRANSFER_TYPE_1_MEMBER = 1;
	const TRANSFER_TYPE_2_AGENT = 2;
	const TRANSFER_TYPE_3_PARTNER = 3;
	const TRANSFER_TYPE_4_MERCHANT = 4;

	const TRANSFER_12_MEMBER_DEPOSIT_CASH = 12;
	const TRANSFER_12_MEMBER_WITHDRAW_CASH = 13;

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	private function _getSecurityCode($sourcePhone, $targetPhone) {
		do {
			$securityCode = Utils::getBarCode(6, "0123456789");
		} while ($this->_isSecurityCodeConflict($securityCode, $sourcePhone, $targetPhone));
		return $securityCode;
	}

	private function _isSecurityCodeConflict($securityCode, $sourcePhone, $targetPhone) {
		$sql = 'select id from tbl_transfer_cash where accept_code = ' . qstr($securityCode) . ' and (phone_source = ' . qstr($sourcePhone) . ' or phone_target = ' . qstr($targetPhone) . ')';
		return $this->getOne($sql) > 0;
	}

	public function getTransferCashRecord($page) {
		$sql = "select a.id,a.time,a.currency,a.amount,a.phone_source,a.source_type,case when a.source_type = 1 then (SELECT name FROM db_system_run.tbl_member WHERE id = a.source_id) end source  from tbl_transfer_cash a where a.target_id=".qstr($_SESSION['UID'])." and a.target_type=".self::TRANSFER_TYPE_1_MEMBER." and a.status=".Constant::STATUS_3_COMPLETED." order by a.time desc";
		$rt = $this->getPageArray($sql, $page);
		$data = \Utils::simplifyData($rt['list']);
		return array('list' => $data['list'], 'keys' => $data['keys'], "total" => $rt['total'], "page" => $rt['page'], "size" => $rt['size']);
	}

	public function getTransferCashDetailByID($id) {
		if(!V::over_num($id,0)) throw new TrantorException('ID');
		$sql = 'select a.time,a.currency,a.amount,a.phone_source,a.source_type,case when a.source_type = 1 then (SELECT NAME FROM db_system_run.tbl_member WHERE id = a.source_id)  when a.source_type = 2 then (SELECT name FROM db_system_run.tbl_agent WHERE id = a.source_id) when a.source_type = 3 then (SELECT b.name FROM db_system_run.tbl_branch b WHERE b.id = a.source_id) end  source,a.accept_code,a.status,a.remark from tbl_transfer_cash a where a.id='.qstr($id);
		return $this->getLine($sql);
	}

	private function _checkOverMaxBalance($memberID, $amountUSD, $pendingAmountUSD, $balanceUSD){
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');
		if (!V::min_num($pendingAmountUSD, 0)) throw new TrantorException('PendingAmountUSD');
		if (!V::min_num($balanceUSD, 0)) throw new TrantorException('BalanceUSD');

		$m = new User();
		$userLevel = $m->getMemberLevelByID($memberID);
		$cf = new Config();
		$maxBalance=$cf->getMemberMaxBalance($memberID,$userLevel,Currency::CURRENCY_USD);
		$total=$balanceUSD + $pendingAmountUSD + $amountUSD;
		return $total > $maxBalance;
	}

	private function _checkPartnerOverMaxBalance($partnerID, $amountUSD, $pendingAmountUSD, $balanceUSD){
		if (!V::over_num($partnerID, 0)) throw new TrantorException('PartnerID');
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');
		if (!V::min_num($pendingAmountUSD, 0)) throw new TrantorException('PendingAmountUSD');
		if (!V::min_num($balanceUSD, 0)) throw new TrantorException('BalanceUSD');

		$m = new User();
		$userLevel = $m->getPartnerLevelByID($partnerID);
		$cf = new Config();
		$maxBalance=$cf->getPartnerMaxBalance($partnerID,$userLevel,Currency::CURRENCY_USD);
		$total=$balanceUSD + $pendingAmountUSD + $amountUSD;
		return $total > $maxBalance;
	}

	private function _checkMerchantOverMaxBalance($merchantID, $amountUSD, $pendingAmountUSD, $balanceUSD){
		if (!V::over_num($merchantID, 0)) throw new TrantorException('MerchantID');
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');
		if (!V::min_num($pendingAmountUSD, 0)) throw new TrantorException('PendingAmountUSD');
		if (!V::min_num($balanceUSD, 0)) throw new TrantorException('BalanceUSD');

		$m = new User();
		$userLevel = $m->getMerchantLevelByID($merchantID);
		$cf = new Config();
		$maxBalance=$cf->getMerchantMaxBalance($merchantID,$userLevel,Currency::CURRENCY_USD);
		$total=$balanceUSD + $pendingAmountUSD + $amountUSD;
		return $total > $maxBalance;
	}


	public function receiveToAcct($securityCode) {
		if (!V::required($securityCode)) throw new TrantorException('SecurityCode');
		$s=new System();
		$isRunning=$s->isFunctionRunning(Constant::FUNCTION_118_RECEIVE_TO_ACCOUNT);
		if(!$isRunning) return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);;

		$sql = 'select id,source_type,source_id,currency,amount,phone_source,phone_target from tbl_transfer_cash where phone_target = ' . qstr($_SESSION['PHONE']).' and accept_code = '. qstr($securityCode).' and status = '.Constant::STATUS_1_PENDING;
		$cashInfo = $this->getLine($sql);
		if (!$cashInfo) return array('err_code' => MessageCode::ERR_1750_INVALID_ACCEPT_CODE);

		$sourceType = $cashInfo['source_type'];
		if (!in_array($sourceType, [1, 2, 3, 99])) throw new TrantorException('Source Type Error', 2);

		$m = new User();
		$memberID = $m->getUserIDByPhone($_SESSION['PHONE']);
		if (!V::over_num($memberID, 0)) return array('err_code' => MessageCode::ERR_1750_INVALID_ACCEPT_CODE);

		$cashID = $cashInfo['id'];
		$currency = $cashInfo['currency'];
		$amount = $cashInfo['amount'];

		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');

		$c = new Currency();
		$b = new Balance();
		$t = new Transaction();
		$amountUSD = $c->transferAmount($amount,$currency, Currency::CURRENCY_USD);

		$balanceTotalUSD = $b->getTotalBalanceUSD($memberID);
		$pendingAmountUSD = $t->getDepositPendingAmount($memberID);
		$rt = $this->_checkOverMaxBalance($memberID, $amountUSD, $pendingAmountUSD, $balanceTotalUSD);
		if($rt) return array('err_code' => MessageCode::ERR_1723_OVER_MAX_BALANCE_LIMIT);

		$a = new Account();
//		if ($sourceType == self::TRANSFER_TYPE_1_MEMBER) $accountID = Account::ID_20001_MEMBER_BALANCE_PAYING; else $accountID = Account::ID_21000_NON_MEMBER_CASH_BALANCE;
		$vRemark = 'A2C Receive #' . $cashID . ', Member '.$cashInfo['phone_target'];
		$voucherID = $a->keepAccounts(Account::ID_21000_NON_MEMBER_CASH_BALANCE, Account::ID_20000_MEMBER_BALANCE, 0, 0, $currency, $amount, $vRemark);

		$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_224_RECEIVE_TO_ACCOUNT, $currency, $amount, $cashID, 0, $voucherID, $vRemark);
		$b->updateCurrentMemberBalance(Constant::DEPOSIT, $currency, $amount);

		$sql = 'update tbl_transfer_cash set target_type = '.self::TRANSFER_TYPE_1_MEMBER.',target_id = ' . qstr($memberID).',status = '. Constant::STATUS_3_COMPLETED . ' where id = ' . qstr($cashID);
		$this->execute($sql);

		$r = array('source_type'=>$sourceType,'phone_source' => $cashInfo['phone_source'], 'amount' => $amount, 'currency' => $currency, 'time' => Utils::getDBNow());
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $r);
	}

	public function getReceiveMoneyResult() {
		$sql="select t.time,t.currency,t.amount,t2.phone,t2.name,t.status,t.remark from tbl_transfer t left join db_system_run.tbl_member t2 on t.user_id=t2.id where to_user_type=".qstr(User::USER_TYPE_1_MEMBER).' and to_user_id='.qstr($_SESSION['UID']) .' order by t.time desc limit 1';
		return $this->getLine($sql);
	}

	public function getDepositCashResult($time) {
		if (!V::required($time)) throw new TrantorException('Time');
		$sql = 'select id,currency,amount from db_system_run.tbl_user_cash_transaction_application where user_type = ' . qstr(User::USER_TYPE_1_MEMBER) . ' and user_id = ' . qstr($_SESSION['UID']) . ' and time > ' . qstr($time) . ' and status = ' . qstr(Transaction::STATUS_3_COMPLETED) . ' order by time desc limit 1';
		$row = $this->getLine($sql);
		if (!empty($row) && $row['id'] > 0) {
			return array('not_data' => false, 'data' => $row);
		} else {
			return array('not_data' => true);
		}
	}

	public function getWithdrawCashResult() {
		$sql = 'select id,currency,amount,transaction_fee fee,time from db_system_run.tbl_user_cash_transaction_application where user_type = ' . qstr(User::USER_TYPE_1_MEMBER) . ' and user_id = ' . qstr($_SESSION['UID']) . '  and status = ' . qstr(Transaction::STATUS_1_PENDING) . ' order by time desc limit 1';
		$row = $this->getLine($sql);
		if (!empty($row) && $row['id'] > 0) {
			return array('not_data' => false, 'data' => $row);
		} else {
			return array('not_data' => true);
		}
	}

	public function getWithdrawCashResultByID($id) {
		if (!V::over_num($id, 0)) throw new TrantorException('ID');
		$sql = 'select id,currency,status,amount,transaction_fee fee,time from db_system_run.tbl_user_cash_transaction_application where id = ' . qstr($id);
		$row = $this->getLine($sql);
		if (!empty($row) && $row['id'] > 0) {
			return array('not_data' => false, 'data' => $row);
		} else {
			return array('not_data' => true);
		}
	}

	public function confirmWithdrawCash($id) {
		$sql = 'select verify_code,currency,amount,transaction_fee from db_system_run.tbl_user_cash_transaction_application where id = ' . qstr($id);
		$info = $this->getLine($sql);
		$b = new Balance();
		$balance = $b->getBalanceByCurrency($info['currency']);
		if ($info['amount'] + $info['transaction_fee'] > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);
		$verifyCode = $info['verify_code'];
		if (!$verifyCode) {
			$verifyCode = self::_getVerifyCode();
			$sql = 'update db_system_run.tbl_user_cash_transaction_application set verify_code = ' . qstr($verifyCode) . ',time_verify_code_invalid = date_add(now(),interval '.qstr(SMSNotification::VERIFY_CODE_EXPIRED).' second) where id = ' . qstr($id);
			$this->execute($sql);
		}
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array("verify_code" => $verifyCode));
	}

	private function _getVerifyCode() {
		return Utils::getBarCode(6, "0123456789");
	}

	public function getTransferCashConfigList(){
		$c=new Currency();
		$l=$c->getCurrencyList();
		$list=[];
		foreach($l as $r){
			$rt['currency']=$r['currency'];
			$rt['config']=$this->getTransferCashConfig($r['currency']);
			$list[]=$rt;
//			$rt[$r['currency']]=$this->getTransferCashConfig($r['currency']);
//			$list[]=$rt;
		}
		return $list;
	}


	public function getTransferCashConfig($currency) {
		if(!Currency::check($currency)) throw new TrantorException("Currency");
		$sql = 'select currency,split_amount,fee_amount,commission_amount_from,commission_amount_to from tbl_config_transfer_cash where currency='.qstr($currency).' ORDER BY split_amount ASC';
		return $this->getArray($sql);
	}

	public function _getOneDayTransferAmountUSD() {
		$sql = 'select ifnull(sum(t.amount/t1.exchange),"") amount from tbl_transfer t left join db_system_run.tbl_currency t1 on(t.currency=t1.currency) where user_type = ' . User::USER_TYPE_1_MEMBER . ' and user_id = ' . qstr($_SESSION['UID']) . ' and date(time) = curdate() and (status=' . Constant::STATUS_1_PENDING . ' or status=' . Constant::STATUS_3_COMPLETED . ')';
		$amount = $this->getOne($sql);
		if(!$amount) $amount = 0;

		$sql = 'select ifnull(sum(t.amount/t1.exchange),"") amount from tbl_transfer_cash t left join db_system_run.tbl_currency t1 on(t.currency=t1.currency) where source_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' and source_id = ' . qstr($_SESSION['UID']) . ' and date(time) = curdate() and (status=' . Constant::STATUS_1_PENDING . ' or status=' . Constant::STATUS_3_COMPLETED . ')';
		$cashAmount = $this->getOne($sql);
		if(!$cashAmount) $cashAmount = 0;

		return abs($amount) + abs($cashAmount);
	}

	private function _chkTransferConfigLimit($amountUSD, $currency) {
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');

//		$m = new User();
//		$userLevel = $m->getMemberLevelByID($_SESSION['UID']);

		$cfg = new Config();
		$configInfo = $cfg->getTransferLimitConfig($_SESSION['LEVEL']);
		if(!$configInfo) return array('err_code' => MessageCode::ERR_1_ERROR);

		$maxPerTimeUSD = $configInfo['max_transfer_per_time'];
		$maxPerDayUSD = $configInfo['max_transfer_per_day'];
		$oneDayAmountUSD = $this->_getOneDayTransferAmountUSD();

		$c = new Currency();
		$exchange = $c->getExchangeByCurrency($currency);

		if($amountUSD > $maxPerTimeUSD) return array('err_code' => MessageCode::ERR_1714_EXCEED_SINGLE_TRANSFER_CASH_CAP, 'result' => array('max_amount' => $maxPerTimeUSD * $exchange, 'currency' => $currency));
		if($amountUSD + $oneDayAmountUSD > $maxPerDayUSD) return array('err_code' => MessageCode::ERR_1715_EXCEED_TRANSFER_CASH_CAP_PER_DAY, 'result' => array('max_amount' => $amountUSD * $exchange - $oneDayAmountUSD * $exchange, 'currency' => $currency));

		return true;
	}

	public function transferToMember($countryCode, $receiver, $currency, $amount, $remark) {
		if (!V::required($countryCode)) throw new TrantorException('CountryCode');
		if (!V::required($receiver)) throw new TrantorException('Receiver');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');

		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_113_MEMBER_TO_MEMBER)){
			return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);
		}
		$c = new Currency();
		$amountUSD = $c->transferAmount($amount,$currency, Currency::CURRENCY_USD);
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');

		$rt = $this->_chkTransferConfigLimit($amountUSD, $currency);
		if($rt !== true) return $rt;

		$b = new Balance();
		$balance = $b->getBalanceByCurrency($currency);

		if ($amount > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$receiver = Utils::formatPhone3($countryCode, $receiver);

		if (!$s->isDuplicated('tbl_member', array('phone' => $receiver))) {
			return array('err_code' => MessageCode::ERR_1721_ONLY_TRANSFER_TO_MEMBER);
		}
		if ($_SESSION['PHONE'] == $receiver) {
			return array('err_code' => MessageCode::ERR_1722_FORBIDDEN_TO_TRANSFER_TO_SELF);
		}

		$m = new User();
		$t = new Transaction();
		$targetMemberID = $m->getUserIDByPhone($receiver);
		$balanceTotalUSD = $b->getTotalBalanceUSD($targetMemberID);
		$pendingAmountUSD = $t->getDepositPendingAmount($targetMemberID);
		$rt = $this->_checkOverMaxBalance($targetMemberID, $amountUSD, $pendingAmountUSD, $balanceTotalUSD);
		if($rt) return array('err_code' => MessageCode::ERR_1723_OVER_MAX_BALANCE_LIMIT);

		$id = $this->_addTransfer($currency, $amount, 0, self::TRANSFER_TYPE_1_MEMBER, $targetMemberID, $remark);

		$a = new Account();
		$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_20000_MEMBER_BALANCE, 0, 0, $currency, $amount, "[A2C] from [Member] " . $_SESSION['PHONE'] . " to [Member] $receiver, Amount $currency " . number_format($amount, 2));

		$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_221_TRANSFER_OUT, $currency, -$amount, $id, 0, $voucherID, $remark);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$amount);

		$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_222_TRANSFER_IN, $currency, $amount, $id, 0, $voucherID, $remark, $targetMemberID);
		$b->updateOtherMemberBalance(Constant::DEPOSIT, $currency, $amount, $targetMemberID);

		$r = array('time' => Utils::getDBNow(),'transfer_id' => $id);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $r);
	}

	public function checkToNonMember($currency, $amount, $fee){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($fee, 0)) throw new TrantorException('Fee');

		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_114_MEMBER_TO_NON_MEMBER)){
			return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);
		}

		$cashConfig = $this->getTransferCashConfig($currency);
		if(count($cashConfig) == 0) return array('err_code' => MessageCode::ERR_1_ERROR);

		$multiple = 0;
		$calFee = 0;
		$calAmount = $amount;
		$maxSplitAmount = $cashConfig[count($cashConfig) - 1]['split_amount'];
		$maxFeeAmount = $cashConfig[count($cashConfig) - 1]['fee_amount'];
		if($calAmount > $maxSplitAmount){
			$multiple = intval($amount/($maxSplitAmount));
			$calAmount = $amount - ($maxSplitAmount * $multiple);
		}
		foreach ($cashConfig as $v) {
			if ($calAmount <= $v['split_amount']) {
				$calFee = $v['fee_amount'];
				break;
			}
		}
		$calFee = round($maxFeeAmount * $multiple + $calFee,2);

		if($calFee != $fee)  return array('err_code' => MessageCode::ERR_1_ERROR);

		$b = new Balance();
		$balance = $b->getBalanceByCurrency($currency);

		if ($amount + $fee > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		return MessageCode::ERR_0_NO_ERROR;
	}

	public function transferToNonMember($countryCode, $receiver, $currency, $amount, $fee, $remark) {
		if (!V::required($countryCode)) throw new TrantorException('CountryCode');
		if (!V::required($receiver)) throw new TrantorException('Receiver');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');

		$rt = $this->checkToNonMember($currency, $amount, $fee);
		if($rt != MessageCode::ERR_0_NO_ERROR) return $rt;

		$receiver = Utils::formatPhone3($countryCode, $receiver);
		$acceptCode = $this->_getSecurityCode($_SESSION['PHONE'], $receiver);
		$id = $this->_addTransferCash($currency, $amount, $fee, $receiver,'','', $acceptCode, $remark);

		$a = new Account();
		$t = new Transaction();

		$vRemark = '[A2C] from '.$_SESSION['PHONE']." to $receiver";
		$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_21000_NON_MEMBER_CASH_BALANCE, 0, 0, $currency, $amount, $vRemark.", Amount $currency ".number_format($amount, 2));
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_223_TRANSFER_OUT_A2C, $currency, -$amount, $id, 0, $voucherID, $remark);
		$b = new Balance();
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$amount);

		if ($fee > 0) {
			$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_40004_NON_MEMBER_TRANSFER_FEE, 0, 0, $currency, $fee, $vRemark.", Fee $currency " .number_format($fee, 2));
			$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_225_TRANSFER_FEE, $currency, -$fee, 0, $transactionID, $voucherID, $remark);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$fee);
		}

		$r = array('accept_code' => $acceptCode, "fee" => $fee, 'target_phone' => $receiver, 'currency' => $currency, 'amount' => $amount, 'time' => Utils::getDBNow());
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $r);
	}

	private function _addTransferCash($currency, $amount, $fee, $targetPhone, $targetType, $targetID, $securityCode, $remark) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::min_num($fee, 0)) throw new TrantorException('Fee');
		if (!V::required($targetPhone)) throw new TrantorException('TargetPhone');
		if (!V::required($securityCode)) throw new TrantorException('SecurityCode');
		$sql = 'insert into tbl_transfer_cash(time,currency,amount,fee,phone_source,phone_target,accept_code,source_type,source_id,target_type,target_id,status,remark) values(now(),' . qstr($currency) . ',' . qstr($amount) . ',' . qstr($fee) . ',' . qstr($_SESSION['PHONE']) . ',' . qstr($targetPhone) . ',' . qstr($securityCode) . ',' . self::TRANSFER_TYPE_1_MEMBER . ',' . qstr($_SESSION['UID']) . ',' . qstr($targetType) . ',' . qstr($targetID) . ',' . Constant::STATUS_1_PENDING . ',' . qstr($remark) . ')';
		$this->execute($sql);
		return $this->insert_id();
	}

	public function getTransferRecent() {

		$sql = 'SELECT * FROM((SELECT IFNULL(tm.name, "") name,tm.phone,tt.time,tt.to_user_type type FROM tbl_transfer tt LEFT JOIN db_system_run.tbl_member tm ON (tm.id = tt.to_user_id) WHERE tt.user_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' AND tt.user_id = '.qstr($_SESSION['UID']).' AND tt.to_user_type = ' . self::TRANSFER_TYPE_1_MEMBER . '  GROUP BY tm.phone LIMIT 10) 
		UNION ALL (SELECT "" AS name, tc.phone_target phone, tc.time, 5 AS TYPE FROM tbl_transfer_cash tc  WHERE tc.source_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' AND tc.source_id = '.qstr($_SESSION['UID']).' GROUP BY tc.phone_target LIMIT 10)
    UNION ALL (SELECT CONCAT(tmc.code, " ", tmc.name) name,tmc.phone_contact phone,tt.time,tt.to_user_type TYPE FROM tbl_transfer tt LEFT JOIN db_system_run.tbl_merchant tmc ON (tmc.id = tt.to_user_id) WHERE tt.user_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' AND tt.user_id = '.qstr($_SESSION['UID']).' AND tt.to_user_type = ' . self::TRANSFER_TYPE_4_MERCHANT . ' GROUP BY tmc.phone_contact LIMIT 10) 
    UNION ALL (SELECT CONCAT(tp.code, " ", tp.name) name, tp.phone_contact phone, tt.time, tt.to_user_type TYPE FROM tbl_transfer tt LEFT JOIN db_system_run.tbl_partner tp ON (tp.id = tt.to_user_id) WHERE tt.user_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' AND tt.user_id = '.qstr($_SESSION['UID']).' AND tt.to_user_type = ' . self::TRANSFER_TYPE_3_PARTNER . ' GROUP BY tp.phone_contact LIMIT 10) ) t3 ORDER BY TIME DESC LIMIT 10 ';

		return $this->getArray($sql);
	}

	public function getMemberTransferHistoryList($page) {
		$sql = 'SELECT t1.id, t1.time, t1.amount, t1.currency, t1.status, tm.phone FROM tbl_transfer t1 LEFT JOIN db_system_run.tbl_member tm ON(tm.id = t1.to_user_id) WHERE t1.user_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' AND t1.user_id = ' . $_SESSION['UID'] . ' AND t1.to_user_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' ORDER BY TIME DESC';
		return $this->getPageArray($sql, $page);
	}

	public function getNonMemberTransferHistoryList($page) {
		$sql = 'SELECT phone_target phone, id, time, amount, currency, status from tbl_transfer_cash where source_type = ' . self::TRANSFER_TYPE_1_MEMBER . ' and source_id = ' . $_SESSION['UID'] . ' ORDER BY time DESC ';
		return $this->getPageArray($sql, $page);
	}

	public function getTransferRecentDetail($id, $source) {
		if (!V::required($id)) throw new TrantorException('ID');
		if (!is_numeric($source)) throw new TrantorException('Source');
		$sql = '';
		if ($source == self::TRANSFER_TYPE_1_MEMBER) {
			$sql = "SELECT t1.id, t1.time, t1.currency, t1.amount, t1.fee, t1.status, t1.remark, t2.phone target_phone, t2.name target_name, t3.phone source_phone, t3.name source_name FROM tbl_transfer t1 LEFT JOIN db_system_run.tbl_member t2 ON (t1.to_user_id = t2.id) LEFT JOIN db_system_run.tbl_member t3 ON (t1.user_id = t3.id)
WHERE t1.id = " . qstr($id);
		} else if ($source == self::TRANSFER_TYPE_2_AGENT){
			$sql = "SELECT t1.id, t1.time, t1.currency, t1.amount, t1.fee,t1.phone_source source_phone, t1.phone_target target_phone, t1.accept_code, t1.status, t1.remark, t2.name target_name, t3.name source_name FROM tbl_transfer_cash t1 LEFT JOIN db_system_run.tbl_member t2 ON (t1.target_id = t2.id) LEFT JOIN db_system_run.tbl_member t3 ON (t1.source_id = t3.id) WHERE t1.id = " . qstr($id);
		} else if ($source == self::TRANSFER_TYPE_3_PARTNER){
			$sql = "SELECT t1.id, t1.time, t1.currency, t1.amount, t1.fee, t1.status, t1.remark,ifnull(concat(t2.code,' ',upper(t2.name)),'') target, t3.phone source_phone FROM tbl_transfer t1 LEFT JOIN db_system_run.tbl_partner t2 ON (t1.to_user_id = t2.id) LEFT JOIN db_system_run.tbl_member t3 ON (t1.user_id = t3.id)
WHERE t1.id = " . qstr($id);
		} else if ($source == self::TRANSFER_TYPE_4_MERCHANT){
			$sql = "SELECT t1.id, t1.time, t1.currency, t1.amount, t1.fee, t1.status, t1.remark,ifnull(concat(t2.code,' ',upper(t2.name)),'') target, t3.phone source_phone FROM tbl_transfer t1 LEFT JOIN db_system_run.tbl_merchant t2 ON (t1.to_user_id = t2.id) LEFT JOIN db_system_run.tbl_member t3 ON (t1.user_id = t3.id)
WHERE t1.id = " . qstr($id);
		}

		return $this->getLine($sql);
	}

	//TODO $targetType, $targetID补充验证
	private function _addTransfer($currency, $amount, $fee, $targetType, $targetID, $remark) {
		if (!Currency::check($currency)) throw new \TrantorException('Currency ' . $currency);
		if (!V::over_num($amount, 0)) throw new \TrantorException('Amount');
		if (!V::min_num($fee, 0)) throw new \TrantorException('Fee');
		$sql = 'insert into tbl_transfer(time,currency,amount,fee,user_type,user_id,to_user_type,to_user_id,status,remark) values(now(),' . qstr($currency) . ',' . qstr($amount) . ',' . qstr($fee) . ',' . self::TRANSFER_TYPE_1_MEMBER . ',' . qstr($_SESSION['UID']) . ',' . qstr($targetType) . ',' . qstr($targetID) . ',' . Constant::STATUS_3_COMPLETED . ',' . qstr($remark) . ')';
		$this->execute($sql);
		return $this->insert_id();
	}

	public function getWithdrawViaAgentInfo($id) {
		if (!V::over_num($id, 0)) throw new TrantorException("ID");
		$sql = 'SELECT uct.id,uct.time,uct.user_type,uct.user_id,uct.user_input_type,uct.type,uct.currency,uct.amount,uct.transaction_fee,uct.status,uct.source_type,tmt.remark,ta.phone source_phone FROM db_system_run.tbl_user_cash_transaction_application uct LEFT JOIN db_system_run.tbl_member_transaction tmt on (tmt.biz_id = uct.id) LEFT JOIN db_system_run.tbl_agent ta ON (ta.id=uct.source_id) WHERE uct.id = ' . qstr($id) . ' AND uct.source_type=' . self::TRANSFER_TYPE_2_AGENT;
		return $this->getLine($sql);
	}

	public function checkToPartner($currency, $amount, $fee){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($fee, 0)) throw new TrantorException('Fee');

		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_115_MEMBER_TO_PARTNER)){
			return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);
		}

		$partnerInfo = $this->getPartnerInfo();
		if(!V::required($partnerInfo['name'])) return array('err_code' => MessageCode::ERR_1_ERROR);

		$c = new Currency();
		$amountUSD=$c->transferAmount($amount,$currency,Currency::CURRENCY_USD);
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');

		$cfg = new Config();
		$withdrawConfigInfo = $cfg->getWithdrawConfig();
		if(!$withdrawConfigInfo) return array('code'=>MessageCode::ERR_1_ERROR);
		$feeUSD = $withdrawConfigInfo['fee_amount'] * (ceil($amountUSD / $withdrawConfigInfo['fee_unit']));
		$calFee = $c->transferFee($feeUSD,Currency::CURRENCY_USD,$currency);
		if($calFee != $fee)  return array('err_code' => MessageCode::ERR_1_ERROR);

		$rt = $this->_chkTransferConfigLimit($amountUSD, $currency);
		if($rt !== true) return $rt;

		$b = new Balance();
		$balance = $b->getBalanceByCurrency($currency);
		if ($amount > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$t = new Transaction();
		$targetPartnerID = $partnerInfo['id'];
		$balanceTotalUSD = $b->getPartnerTotalBalanceUSD($targetPartnerID);
		$pendingAmountUSD = $t->getPartnerDepositPendingAmount($targetPartnerID);
		$rt = $this->_checkPartnerOverMaxBalance($targetPartnerID, $amountUSD, $pendingAmountUSD, $balanceTotalUSD);
		if($rt) return array('err_code' => MessageCode::ERR_1723_OVER_MAX_BALANCE_LIMIT);

		return MessageCode::ERR_0_NO_ERROR;
	}

	public function transferToPartner($currency, $amount, $fee, $remark){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');

		$rt = $this->checkToPartner($currency, $amount, $fee);
		if($rt != MessageCode::ERR_0_NO_ERROR) return $rt;

		$partnerInfo = $this->getPartnerInfo();
		$partnerName = $partnerInfo['name'];
		$targetPartnerID = $partnerInfo['id'];

		$id = $this->_addTransfer($currency, $amount, $fee, self::TRANSFER_TYPE_3_PARTNER, $targetPartnerID, $remark);

		$a = new Account();
		$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_23000_PARTNER_BALANCE, 0, 0, $currency, $amount, "[A2C] from [Member] " . $_SESSION['PHONE'] . " to [Partner] ".$partnerName.", Amount $currency " . number_format($amount, 2));

		$t = new Transaction();
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_221_TRANSFER_OUT, $currency, -$amount, $id, 0, $voucherID, $remark);
		$b = new Balance();
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$amount,$transactionID);

		$partnerTransactionID=$t->addPartner($targetPartnerID,Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_222_TRANSFER_IN, $currency, $amount, $id, 0, $voucherID, $remark);
		$b->updatePartnerBalance($targetPartnerID,Balance::PARTNER_MONEY_TYPE_1_MAIN,Constant::DEPOSIT, $currency, $amount,$partnerTransactionID);

		if ($fee > 0) {
			$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_40002_MEMBER_WITHDRAW_FEE, 0, 0, $currency, $fee, "[A2C] from [Member] " . $_SESSION['PHONE'] . " to [Partner] ". $partnerName .", Fee $currency " . number_format($fee, 2));
			$feeTransactionID=$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_225_TRANSFER_FEE, $currency, -$fee, 0, $transactionID, $voucherID, $remark);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$fee,$feeTransactionID);
		}

		$r = array('time' => Utils::getDBNow());
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $r);
	}

	public function getPartnerTransferHistoryList($page) {
		$sql = "SELECT t1.id, t1.time, t1.amount, t1.currency, t1.status,ifnull(concat(tp.code,' ',upper(tp.name)),'') phone FROM tbl_transfer t1 LEFT JOIN db_system_run.tbl_partner tp ON(tp.id = t1.to_user_id) WHERE t1.user_type = " . self::TRANSFER_TYPE_1_MEMBER . ' AND t1.user_id = ' . $_SESSION['UID'] . ' AND t1.to_user_type = ' . self::TRANSFER_TYPE_3_PARTNER . ' ORDER BY TIME DESC';
		return $this->getPageArray($sql, $page);
	}

	public function getDepositViaAgentDetail($id) {
		if (!V::required($id)) throw new TrantorException('ID');
		$sql = "select t1.id,t1.time,t1.user_input_type,t1.currency,t1.amount,t1.status,t1.source_type,t2.phone source_phone,t3.remark from db_system_run.tbl_user_cash_transaction_application t1 left join db_system_run.tbl_agent t2 on (t1.source_id = t2.id) left join db_system_run.tbl_member_transaction t3 on(t1.id = t3.biz_id) where t1.id = " . qstr($id);
		return $this->getLine($sql);
	}

	public function getPartnerInfo(){
		$sql = "select id,ifnull(concat(code,' ',upper(name)),'') name,phone_contact phone from db_system_run.tbl_partner where relate_member_id = " . $_SESSION['UID'] . ' and status = ' . Constant::STATUS_1_ACTIVE;
		return $this->getLine($sql);
	}

	public function getMerchantInfo(){
		$sql = "select id,ifnull(concat(code,' ',upper(name)),'') name,phone_contact phone from db_system_run.tbl_merchant where relate_member_id = " . $_SESSION['UID'] . ' and status = ' . Constant::STATUS_1_ACTIVE;
		return $this->getLine($sql);
	}

	public function checkToMerchant($currency, $amount, $fee){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($fee, 0)) throw new TrantorException('Fee');

		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_116_MEMBER_TO_MERCHANT)){
			return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);
		}

		$merchantInfo = $this->getMerchantInfo();
		if(!V::required($merchantInfo['name'])) return array('err_code' => MessageCode::ERR_1_ERROR);

		$c = new Currency();
		$amountUSD = $c->transferAmount($amount, $currency, Currency::CURRENCY_USD);
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');

		$cfg = new Config();
		$withdrawConfigInfo = $cfg->getWithdrawConfig();
		if(!$withdrawConfigInfo) return array('code'=>MessageCode::ERR_1_ERROR);
		$feeUSD = $withdrawConfigInfo['fee_amount'] * (ceil($amountUSD / $withdrawConfigInfo['fee_unit']));
		$calFee = $c->transferFee($feeUSD,Currency::CURRENCY_USD,$currency);
		if($calFee != $fee)  return array('err_code' => MessageCode::ERR_1_ERROR);

		$rt = $this->_chkTransferConfigLimit($amountUSD, $currency);
		if($rt !== true) return $rt;

		$b = new Balance();
		$balance = $b->getBalanceByCurrency($currency);
		if ($amount > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$t = new Transaction();
		$targetMerchantID = $merchantInfo['id'];
		$balanceTotalUSD = $b->getMerchantTotalBalanceUSD($targetMerchantID);
		$pendingAmountUSD = $t->getMerchantDepositPendingAmount($targetMerchantID);
		$rt = $this->_checkMerchantOverMaxBalance($targetMerchantID, $amountUSD, $pendingAmountUSD, $balanceTotalUSD);
		if($rt) return array('err_code' => MessageCode::ERR_1723_OVER_MAX_BALANCE_LIMIT);

		return MessageCode::ERR_0_NO_ERROR;
	}

	public function transferToMerchant($currency, $amount, $fee, $remark){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($fee, 0)) throw new TrantorException('Fee');

		$rt = $this->checkToMerchant($currency, $amount, $fee);
		if($rt != MessageCode::ERR_0_NO_ERROR) return $rt;

		$merchantInfo = $this->getMerchantInfo();
		$merchantName = $merchantInfo['name'];
		$targetMerchantID = $merchantInfo['id'];

		$id = $this->_addTransfer($currency, $amount, $fee, self::TRANSFER_TYPE_4_MERCHANT, $targetMerchantID, $remark);

		$a = new Account();
		$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_23050_MERCHANT_BALANCE, 0, 0, $currency, $amount, "[A2C] from [Member] " . $_SESSION['PHONE'] . " to [Merchant] $merchantName, Amount $currency " . number_format($amount, 2));

		$t = new Transaction();
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_221_TRANSFER_OUT, $currency, -$amount, $id, 0, $voucherID, $remark);
		$b = new Balance();
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$amount);

		$t->addMerchant($targetMerchantID,Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_222_TRANSFER_IN, $currency, $amount, $id, 0, $voucherID, $remark);
		$b->updateMerchantBalance($targetMerchantID,Constant::DEPOSIT, $currency, $amount);

		if ($fee > 0) {
			$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_40002_MEMBER_WITHDRAW_FEE, 0, 0, $currency, $fee, "[A2C] from [Member] " . $_SESSION['PHONE'] . " to [Merchant] $merchantName, Fee $currency " . number_format($fee, 2));
			$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_225_TRANSFER_FEE, $currency, -$fee, 0, $transactionID, $voucherID, $remark);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$fee);
		}

		$r = array('time' => Utils::getDBNow());
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $r);
	}

	public function getMerchantTransferHistoryList($page) {
		$sql = "SELECT t1.id, t1.time, t1.amount, t1.currency, t1.status,ifnull(concat(t2.code,' ',upper(t2.name)),'') phone FROM tbl_transfer t1 LEFT JOIN db_system_run.tbl_merchant t2 ON(t2.id = t1.to_user_id) WHERE t1.user_type = " . self::TRANSFER_TYPE_1_MEMBER . ' AND t1.user_id = ' . $_SESSION['UID'] . ' AND t1.to_user_type = ' . self::TRANSFER_TYPE_4_MERCHANT . ' ORDER BY TIME DESC';
		return $this->getPageArray($sql, $page);
	}
}
 