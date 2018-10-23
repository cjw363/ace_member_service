<?php
use validation\Validator as V;

class Transaction extends Base {
	const STATUS_1_PENDING = 1;
	const STATUS_2_APPROVED = 2;
	const STATUS_3_COMPLETED = 3;
	const STATUS_4_CANCELLED = 4;

	const PAY_TYPE_1_LOCAL_BANK = 1;
	const PAY_TYPE_2_BRANCH = 2;
	const PAY_TYPE_3_WING_PHONE = 3;
	const PAY_TYPE_4_AWL_PHONE = 4;
	const PAY_TYPE_6_LOCAL_BANK_DIRECT = 6;

	//-------------------transaction type----------------------//
	/**
	 * 1) N/A
	 * 2) 202 Deposit (via Bank) 203 Withdraw (via Bank) 204 Deposit (via Agent) 205 Withdraw (via Agent) 206 Deposit (via Branch) 207 Withdraw (via Branch) 215 Withdraw Fee 217 Bank Fee 221 Transfer Out 222 Transfer In 223 Transfer Out (A2C) 224 Receive to Account 225 Transfer Fee 232 ACE Loan 233 ACE Return Loan 236 Loan (Partner) 237 Return Loan (Partner) 239 Loan Service Charge (Partner) 240 Salary Loan 241 Salary Loan Return 243 Salary Loan Service Charge
	 * 280 Compensation 290 Currency Exchange
	 * 4) 401 Phone Top Up
	 * 5) 501 Scratch Card 502 Scratch Card Prize 503 Lottery 504 Lottery Prize
	 * 6) 603 Pay WSA 605 Pay WSA Fee 607 Pay EDC 609 Pay EDC Fee 611 Pay Partner Bill 613 Pay Partner Bill Fee
	 */
	const TRANSACTION_TYPE_1_BEGINNING = 1;

	const TRANSACTION_SUB_TYPE_0_NULL = 0;

	const TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW = 2;
	const TRANSACTION_TYPE_2_SELF=2;//tbl_partner_transaction
	const TRANSACTION_TYPE_7_LOAN = 7;
	const TRANSACTION_TYPE_8_SALARY = 8;
	const TRANSACTION_SUB_TYPE_202_DEPOSIT_VIA_BANK = 202;
	const TRANSACTION_SUB_TYPE_203_WITHDRAW_VIA_BANK = 203;
	const TRANSACTION_SUB_TYPE_204_DEPOSIT_VIA_AGENT = 204;
	const TRANSACTION_SUB_TYPE_205_WITHDRAW_VIA_AGENT = 205;
	const TRANSACTION_SUB_TYPE_206_DEPOSIT_VIA_BRANCH = 206;
	const TRANSACTION_SUB_TYPE_207_WITHDRAW_VIA_BRANCH = 207;
	const TRANSACTION_SUB_TYPE_215_WITHDRAW_FEE = 215;
	const TRANSACTION_SUB_TYPE_217_BANK_FEE = 217;
	const TRANSACTION_SUB_TYPE_221_TRANSFER_OUT = 221;
	const TRANSACTION_SUB_TYPE_222_TRANSFER_IN = 222;
	const TRANSACTION_SUB_TYPE_223_TRANSFER_OUT_A2C = 223;
	const TRANSACTION_SUB_TYPE_224_RECEIVE_TO_ACCOUNT = 224;
	const TRANSACTION_SUB_TYPE_225_TRANSFER_FEE = 225;

	const TRANSACTION_SUB_TYPE_232_LOAN = 232;//partner
	const TRANSACTION_SUB_TYPE_232_ACE_LOAN = 232;
	const TRANSACTION_SUB_TYPE_233_ACE_RETURN_LOAN = 233;
	const TRANSACTION_SUB_TYPE_233_RETURN_LOAN = 233;//partner
	const TRANSACTION_SUB_TYPE_235_LOAN_SERVICE_CHARGE = 235;
	const TRANSACTION_SUB_TYPE_236_PARTNER_LOAN = 236;
	const TRANSACTION_SUB_TYPE_237_RETURN_PARTNER_LOAN = 237;
	const TRANSACTION_SUB_TYPE_239_PARTNER_LOAN_SERVICE_CHARGE = 239;
	const TRANSACTION_SUB_TYPE_240_SALARY_LOAN = 240;
	const TRANSACTION_SUB_TYPE_241_RETURN_SALARY_LOAN = 241;
	const TRANSACTION_SUB_TYPE_243_SALARY_LOAN_SERVICE_CHARGE = 243;
	const TRANSACTION_SUB_TYPE_280_COMPENSATION = 280;
	const TRANSACTION_SUB_TYPE_290_CURRENCY_EXCHANGE = 290;

	const TRANSACTION_TYPE_4_TOP_UP = 4;
	const TRANSACTION_SUB_TYPE_401_PHONE_TOP_UP = 401;
	const TRANSACTION_SUB_TYPE_401_SELL_TOP_UP = 401;

	const TRANSACTION_TYPE_5_LOTTO = 5;
	const TRANSACTION_SUB_TYPE_501_SCRATCH_CARD = 501;
	const TRANSACTION_SUB_TYPE_501_SELL_SCRATCH_CARD = 501;
	const TRANSACTION_SUB_TYPE_502_SCRATCH_CARD_PRIZE = 502;
	const TRANSACTION_SUB_TYPE_503_LOTTERY = 503;
	const TRANSACTION_SUB_TYPE_504_LOTTERY_PRIZE = 504;

	const TRANSACTION_TYPE_6_PAYMENT = 6;
	const TRANSACTION_SUB_TYPE_603_PAY_WSA = 603;
	const TRANSACTION_SUB_TYPE_605_PAY_WSA_FEE = 605;
	const TRANSACTION_SUB_TYPE_607_PAY_EDC = 607;
	const TRANSACTION_SUB_TYPE_609_PAY_EDC_FEE = 609;
	const TRANSACTION_SUB_TYPE_611_PAY_PARTNER_BILL = 611;
	const TRANSACTION_SUB_TYPE_613_PAY_PARTNER_BILL_FEE = 613;
	const TRANSACTION_SUB_TYPE_621_BILL_PAYMENT_INCOME = 621;
	const TRANSACTION_SUB_TYPE_622_BILL_PAYMENT_RETURN = 622;
	const TRANSACTION_SUB_TYPE_623_API_PAY_INCOME = 623;
	const TRANSACTION_SUB_TYPE_708_LOAN_SERVICE_CHARGE = 708;
	const TRANSACTION_SUB_TYPE_801_PAY_SALARY = 801;
	const TRANSACTION_SUB_TYPE_901_COMMISSION = 901;
	//-------------------transaction type----------------------//


	//partner type,subtype
	public static $partnerTypeArr = array(self::TRANSACTION_TYPE_1_BEGINNING, self::TRANSACTION_TYPE_2_SELF, self::TRANSACTION_TYPE_4_TOP_UP, self::TRANSACTION_TYPE_5_LOTTO, self::TRANSACTION_TYPE_6_PAYMENT, self::TRANSACTION_TYPE_7_LOAN, self::TRANSACTION_TYPE_8_SALARY);
	public static $partnerSubTypeArr = array(self::TRANSACTION_SUB_TYPE_202_DEPOSIT_VIA_BANK, self::TRANSACTION_SUB_TYPE_203_WITHDRAW_VIA_BANK, self::TRANSACTION_SUB_TYPE_207_WITHDRAW_VIA_BRANCH, self::TRANSACTION_SUB_TYPE_215_WITHDRAW_FEE, self::TRANSACTION_SUB_TYPE_221_TRANSFER_OUT, self::TRANSACTION_SUB_TYPE_222_TRANSFER_IN, self::TRANSACTION_SUB_TYPE_232_LOAN, self::TRANSACTION_SUB_TYPE_233_RETURN_LOAN, self::TRANSACTION_SUB_TYPE_235_LOAN_SERVICE_CHARGE, self::TRANSACTION_SUB_TYPE_280_COMPENSATION, self::TRANSACTION_SUB_TYPE_290_CURRENCY_EXCHANGE, self::TRANSACTION_SUB_TYPE_401_SELL_TOP_UP, self::TRANSACTION_SUB_TYPE_901_COMMISSION, self::TRANSACTION_SUB_TYPE_501_SELL_SCRATCH_CARD, self::TRANSACTION_SUB_TYPE_502_SCRATCH_CARD_PRIZE, self::TRANSACTION_SUB_TYPE_503_LOTTERY, self::TRANSACTION_SUB_TYPE_504_LOTTERY_PRIZE, self::TRANSACTION_SUB_TYPE_621_BILL_PAYMENT_INCOME, self::TRANSACTION_SUB_TYPE_622_BILL_PAYMENT_RETURN, self::TRANSACTION_SUB_TYPE_623_API_PAY_INCOME, self::TRANSACTION_SUB_TYPE_708_LOAN_SERVICE_CHARGE, self::TRANSACTION_SUB_TYPE_801_PAY_SALARY);

	//TODO 这里type，subtype不同用户是不一样的，注意调用
	public static $typeArr = array(self::TRANSACTION_TYPE_1_BEGINNING, self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, self::TRANSACTION_TYPE_4_TOP_UP, self::TRANSACTION_TYPE_5_LOTTO, self::TRANSACTION_TYPE_6_PAYMENT);
	public static $subTypeArr = array(self::TRANSACTION_SUB_TYPE_0_NULL, self::TRANSACTION_SUB_TYPE_202_DEPOSIT_VIA_BANK, self::TRANSACTION_SUB_TYPE_203_WITHDRAW_VIA_BANK, self::TRANSACTION_SUB_TYPE_204_DEPOSIT_VIA_AGENT, self::TRANSACTION_SUB_TYPE_205_WITHDRAW_VIA_AGENT, self::TRANSACTION_SUB_TYPE_206_DEPOSIT_VIA_BRANCH, self::TRANSACTION_SUB_TYPE_207_WITHDRAW_VIA_BRANCH, self::TRANSACTION_SUB_TYPE_215_WITHDRAW_FEE, self::TRANSACTION_SUB_TYPE_217_BANK_FEE, self::TRANSACTION_SUB_TYPE_221_TRANSFER_OUT, self::TRANSACTION_SUB_TYPE_222_TRANSFER_IN, self::TRANSACTION_SUB_TYPE_223_TRANSFER_OUT_A2C, self::TRANSACTION_SUB_TYPE_224_RECEIVE_TO_ACCOUNT, self::TRANSACTION_SUB_TYPE_225_TRANSFER_FEE, self::TRANSACTION_SUB_TYPE_232_ACE_LOAN, self::TRANSACTION_SUB_TYPE_233_ACE_RETURN_LOAN, self::TRANSACTION_SUB_TYPE_235_LOAN_SERVICE_CHARGE, self::TRANSACTION_SUB_TYPE_236_PARTNER_LOAN, self::TRANSACTION_SUB_TYPE_237_RETURN_PARTNER_LOAN, self::TRANSACTION_SUB_TYPE_239_PARTNER_LOAN_SERVICE_CHARGE, self::TRANSACTION_SUB_TYPE_240_SALARY_LOAN, self::TRANSACTION_SUB_TYPE_241_RETURN_SALARY_LOAN, self::TRANSACTION_SUB_TYPE_243_SALARY_LOAN_SERVICE_CHARGE, self::TRANSACTION_SUB_TYPE_280_COMPENSATION, self::TRANSACTION_SUB_TYPE_290_CURRENCY_EXCHANGE, self::TRANSACTION_SUB_TYPE_401_PHONE_TOP_UP, self::TRANSACTION_SUB_TYPE_501_SCRATCH_CARD, self::TRANSACTION_SUB_TYPE_502_SCRATCH_CARD_PRIZE, self::TRANSACTION_SUB_TYPE_503_LOTTERY, self::TRANSACTION_SUB_TYPE_504_LOTTERY_PRIZE, self::TRANSACTION_SUB_TYPE_603_PAY_WSA, self::TRANSACTION_SUB_TYPE_605_PAY_WSA_FEE, self::TRANSACTION_SUB_TYPE_607_PAY_EDC, self::TRANSACTION_SUB_TYPE_609_PAY_EDC_FEE, self::TRANSACTION_SUB_TYPE_611_PAY_PARTNER_BILL, self::TRANSACTION_SUB_TYPE_613_PAY_PARTNER_BILL_FEE);

	const RELATE_TYPE_5_BANK_ACCOUNT = 5;
	const RELATE_TYPE_11_PARTNER = 11;
	const RELATE_TYPE_17_BRANCH_COUNTER = 17;
	const RELATE_TYPE_31_MEMBER = 31;
	const RELATE_TYPE_33_AGENT = 33;
	const RELATE_TYPE_45_PHONE_COMPANY = 45;

	public function __construct() {
		parent::__construct(Constant::MAIN_DB_RUN);
	}

	public function checkExchange($sourceCurrency,$amount, $targetCurrency) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported SourceCurrency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported TargetCurrency ' . $targetCurrency, 2);


		$s = new System();
		if(!$s->isFunctionRunning(Constant::FUNCTION_119_MEMBER_EXCHANGE))return MessageCode::ERR_505_FUNCTION_NOT_RUNNING;

		$b = new Balance();
		$balance = $b->getBalanceByCurrency($sourceCurrency);
		if ($amount > $balance) return  MessageCode::ERR_1709_NOT_ENOUGH_BALANCE;

		$c = new Currency();
		$exchangeData = $c->buildExchange();
		$rate = $exchangeData[$sourceCurrency . "->" . $targetCurrency];
		$targetAmount = $amount * $rate;

		if (!V::over_num($targetAmount, 0)) throw new TrantorException('Error TargetAmount', 2);
		return MessageCode::ERR_0_NO_ERROR;
	}

	public function exchange($sourceCurrency,$amount, $targetCurrency) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported SourceCurrency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported TargetCurrency ' . $targetCurrency, 2);
		$code = $this->checkExchange($sourceCurrency,$amount, $targetCurrency);
		if($code>0)return array('err_code' => $code);;
		$b = new Balance();
		$c = new Currency();
		$exchangeData = $c->buildExchange();
		$rate = $exchangeData[$sourceCurrency . "->" . $targetCurrency];
		$targetAmount = $amount * $rate;

		$remark = "Currency Exchange, from $sourceCurrency " . number_format($amount, 2) . " to $targetCurrency " . number_format($targetAmount, 2) . ", Rate: " . number_format($rate, 8);
		$sql = 'insert into tbl_user_currency_exchange (time,user_type,user_id,currency_source,currency_target,exchange,amount_source,amount_target,remark) values (now(),' . User::USER_TYPE_1_MEMBER . ',' . qstr($_SESSION['UID']) . ',' . qstr($sourceCurrency) . ',' . qstr($targetCurrency) . ',' . qstr($rate) . ',' . qstr($amount) . ',' . qstr($targetAmount) . ',' . qstr($remark).')';
		$this->execute($sql);
		$id = $this->insert_id();

		$a = new Account();

		$sourceVoucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_15002_CURRENCY_EXCHANGE_MEMBER, 0, 0, $sourceCurrency, $amount, $remark);
		$this->add(self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, self::TRANSACTION_SUB_TYPE_290_CURRENCY_EXCHANGE, $sourceCurrency, -$amount, $id, 0, $sourceVoucherID, $remark);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $sourceCurrency, -$amount);

		$targetVoucherID = $a->keepAccounts(Account::ID_15002_CURRENCY_EXCHANGE_MEMBER, Account::ID_20000_MEMBER_BALANCE, 0, 0, $targetCurrency, $targetAmount, $remark);
		$this->add(self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, self::TRANSACTION_SUB_TYPE_290_CURRENCY_EXCHANGE, $targetCurrency, $targetAmount, $id, 0, $targetVoucherID, $remark);
		$b->updateCurrentMemberBalance(Constant::DEPOSIT, $targetCurrency, $targetAmount);
		$r=array();
		$r['detail'] = array('source_currency'=>$sourceCurrency, 'source_amount'=>$amount, 'target_currency'=>$targetCurrency, 'target_amount'=>$targetAmount, 'time'=>Utils::getDBNow());
		$r['balance'] = $b->getBalance();

		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $r);
	}

	public function add($type, $subType, $currency, $amount, $bizID, $mainID , $voucherID, $remark = '', $memberID = 0) {
		if (!in_array($type, self::$typeArr)) throw new TrantorException('Type');
		if (!in_array($subType, self::$subTypeArr)) throw new TrantorException('SubType');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!is_numeric($amount) || $amount == 0) throw new TrantorException('Amount');
		if (!V::min_num($bizID, 0)) throw new TrantorException('Biz ID');
		if (!V::min_num($mainID, 0)) throw new TrantorException('Main ID');
		if (!V::min_num($voucherID, 0)) throw new TrantorException('VoucherID');
		if (!V::min_num($memberID, 0)) throw new TrantorException('MemberID');
		if ($memberID == 0) $memberID = $_SESSION['UID'];

		$sql = 'insert into tbl_member_transaction(date,time,member_id,type,sub_type,currency,amount,biz_id,main_id,voucher_id,remark) value(curdate(),now(),' . qstr($memberID) . ',' . $type . ',' . $subType . ',' . qstr($currency) . ',' . $amount . ',' . $bizID . ',' . $mainID . ',' . $voucherID . ',' . qstr($remark) . ')';
		$this->execute($sql);
		return $this->insert_id();
	}

	public function addPartner($partnerID,$type, $subType, $currency, $amount, $bizID, $mainID , $voucherID, $remark = '') {
		if (!V::min_num($partnerID, 0)) throw new TrantorException('PartnerID');
		if (!in_array($type, self::$partnerTypeArr)) throw new TrantorException('Type');
		if (!in_array($subType, self::$partnerSubTypeArr)) throw new TrantorException('SubType');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!is_numeric($amount) || $amount == 0) throw new TrantorException('Amount');
		if (!V::min_num($bizID, 0)) throw new TrantorException('Biz ID');
		if (!V::min_num($mainID, 0)) throw new TrantorException('Main ID');
		if (!V::min_num($voucherID, 0)) throw new TrantorException('VoucherID');

		$sql = 'insert into tbl_partner_transaction(date,time,partner_id,type,sub_type,currency,amount,biz_id,main_id,voucher_id,remark) value(curdate(),now(),' . qstr($partnerID) . ',' . $type . ',' . $subType . ',' . qstr($currency) . ',' . $amount . ',' . $bizID . ',' . $mainID . ',' . $voucherID . ',' . qstr($remark) . ')';
		$this->execute($sql);
		return $this->insert_id();
	}

	public function addMerchant($merchantID,$type, $subType, $currency, $amount, $bizID, $mainID , $voucherID, $remark = '') {
		if (!V::min_num($merchantID, 0)) throw new TrantorException('MerchantID');
		if (!in_array($type, self::$typeArr)) throw new TrantorException('Type');
		if (!in_array($subType, self::$subTypeArr)) throw new TrantorException('SubType');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!is_numeric($amount) || $amount == 0) throw new TrantorException('Amount');
		if (!V::min_num($bizID, 0)) throw new TrantorException('Biz ID');
		if (!V::min_num($mainID, 0)) throw new TrantorException('Main ID');
		if (!V::min_num($voucherID, 0)) throw new TrantorException('VoucherID');

		$sql = 'insert into tbl_merchant_transaction(date,time,merchant_id,type,sub_type,currency,amount,biz_id,main_id,voucher_id,remark) value(curdate(),now(),' . qstr($merchantID) . ',' . $type . ',' . $subType . ',' . qstr($currency) . ',' . $amount . ',' . $bizID . ',' . $mainID . ',' . $voucherID . ',' . qstr($remark) . ')';
		$this->execute($sql);
		return $this->insert_id();
	}

	public function checkOverBankTransferOneDayAmount($bankCode, $amount, $transferAmountMaxPerDay){
		if(!V::required($bankCode)) throw new TrantorException('Bank');
		if(!V::over_num($amount, 0)) throw new TrantorException('Amount');

		$sql = 'select ifnull(sum(abs(t1.amount)/t2.exchange),0) from tbl_user_bank_transaction_application t1 left join tbl_currency t2 on t1.currency=t2.currency where t1.bank_code = '.qstr($bankCode).' and t1.user_type = '.User::USER_TYPE_1_MEMBER.' and t1.user_id = '.qstr($_SESSION['UID']).' and t1.type = '.Constant::WITHDRAW.' and (t1.status = '.Constant::STATUS_1_PENDING.' or t1.status='.Constant::STATUS_3_COMPLETED.') and date(t1.time) = curdate()';
		$withdrawAmountTotal = $this->getOne($sql);
		if(!$withdrawAmountTotal) $withdrawAmountTotal = 0;
		return $amount + $withdrawAmountTotal > $transferAmountMaxPerDay;
	}

	public function withdraw($currency, $amount, $bankCode, $bankAccountNo, $remark) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::required($bankCode)) throw new TrantorException('BankCode');
		if (!V::required($bankAccountNo)) throw new TrantorException('BankAccountNo');

		$c = new Currency();
		$memberID = $_SESSION['UID'];
		$amountUSD=$c->transferAmount($amount,$currency,Currency::CURRENCY_USD);
		$code = $this->_chkWithdrawConfigLimit($memberID, $amountUSD);
		if ($code > 0) return array('code' => $code);

		$l = new LocalBank();
		$bankConfig = $l->getBankConfigByBankCode($bankCode);
		if(!$bankConfig) return array('code'=>MessageCode::ERR_1_ERROR);
		$transferAmountMaxPerDay = $c->transferAmount($bankConfig['transfer_amount_max_per_day'],$bankConfig['currency'],Currency::CURRENCY_USD);
		$transferAmountUnitFee=$c->transferAmount($bankConfig['transfer_amount_unit_fee'],$bankConfig['currency'],Currency::CURRENCY_USD);
		$transferAmountUnit=$c->transferAmount($bankConfig['transfer_amount_unit'],$bankConfig['currency'],Currency::CURRENCY_USD);
		if($this->checkOverBankTransferOneDayAmount($bankCode, $amount, $transferAmountMaxPerDay)) return array('code'=>MessageCode::ERR_1715_EXCEED_TRANSFER_CASH_CAP_PER_DAY);
		$bankFeeUSD = $transferAmountUnitFee * (ceil($amount / $transferAmountUnit));
		$bankFee=$c->transferFee($bankFeeUSD,Currency::CURRENCY_USD,$currency);


		$cfg = new Config();
		$withdrawConfigInfo = $cfg->getWithdrawConfig();
		if(!$withdrawConfigInfo) return array('code'=>MessageCode::ERR_1_ERROR);
		$withdrawFeeUSD = $withdrawConfigInfo['fee_amount'] * (ceil($amountUSD / $withdrawConfigInfo['fee_unit']));
		$withdrawFee = $c->transferFee($withdrawFeeUSD,Currency::CURRENCY_USD,$currency);

		if(!V::min_num($withdrawFee, 0) || !V::min_num($bankFee, 0)) throw new TrantorException('Error Fee', 2);

		$b = new Balance();
		$balance = $b->getBalanceByCurrency($currency);
		if ($withdrawFee + $bankFee + $amount > $balance) return array('code'=>MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$ac = new Account();
		$id = $this->_addBankApplication(Constant::WITHDRAW, $currency, -$amount, -$withdrawFee, -$bankFee, $bankCode, $bankAccountNo, 0, $remark);
		$vRemark='Bank Withdraw #'.$id .' '.$bankCode.'-'.$bankAccountNo.' '.$currency.' '.$amount;
		$voucherID = $ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_20001_MEMBER_BALANCE_PAYING, 0, 0, $currency, $amount, $vRemark);
		$transactionID = $this->add(self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, self::TRANSACTION_SUB_TYPE_203_WITHDRAW_VIA_BANK, $currency, -$amount, $id, 0, $voucherID, $remark);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$amount);

		if ($withdrawFee > 0) {
			$voucherID = $ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_40002_MEMBER_WITHDRAW_FEE, 0, 0, $currency, $withdrawFee, $vRemark);
			$this->add(self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, self::TRANSACTION_SUB_TYPE_215_WITHDRAW_FEE, $currency, -$withdrawFee, 0, $transactionID, $voucherID, $remark);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$withdrawFee);
		}
		if ($bankFee > 0) {
			$voucherID = $ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_24000_BANK_FEE, 0, 0, $currency, $bankFee, $vRemark);
			$this->add(self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, self::TRANSACTION_SUB_TYPE_217_BANK_FEE, $currency, -$bankFee, 0, $transactionID, $voucherID, $remark);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$bankFee);
		}

		return array('code'=>MessageCode::ERR_0_NO_ERROR,'id'=>$id);
	}

	public function deposit($currency, $amount, $bankCode, $bankAccountNo, $companyBankAccountNo, $remark) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::required($bankCode)) throw new TrantorException('BankCode');
		if (!V::required($bankAccountNo)) throw new TrantorException('BankAccountNo');
		if (!V::required($companyBankAccountNo)) throw new TrantorException('CompanyBankAccountNo');

		$lb = new LocalBank();
		if (!$lb->checkBankExisted($bankCode)) throw new TrantorException('BankCode');
		$bankAccountCompanyID = $lb->getBankAccountID($bankCode, $companyBankAccountNo);
		if (!$bankAccountCompanyID) throw new TrantorException('CompanyBankAccount is not exist', 2);

		$memberID = $_SESSION['UID'];
		$b = new Balance();
		$balanceTotalUSD = $b->getTotalBalanceUSD($memberID);
		$c = new Currency();
		$amountUSD = $c->transferAmount($amount,$currency, Currency::CURRENCY_USD);
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');

		$pendingAmountUSD = $this->getDepositPendingAmount($memberID);
		$code = $this->_chkDepositConfigLimit($memberID, $amountUSD, $pendingAmountUSD, $balanceTotalUSD);
		if ($code > 0) return array('code' => $code);

		$id = $this->_addBankApplication(Constant::DEPOSIT, $currency, $amount, 0, 0, $bankCode, $bankAccountNo, $bankAccountCompanyID, $remark);
		return array('code' => MessageCode::ERR_0_NO_ERROR, 'id' => $id);
	}

	private function _chkDepositConfigLimit($memberID, $amountUSD, $pendingAmountUSD, $balanceTotalUSD) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');
		if (!V::min_num($pendingAmountUSD, 0)) throw new TrantorException('PendingAmountUSD');
		if (!V::min_num($balanceTotalUSD, 0)) throw new TrantorException('BalanceTotalUSD');
		$c = new Config();
		$configInfo = $c->getDepositLimitConfig(User::USER_TYPE_1_MEMBER,$_SESSION['LEVEL'],Currency::CURRENCY_USD);
		if(!$configInfo) return MessageCode::ERR_1_ERROR;

		$maxBalance = $c->getMemberMaxBalance($memberID,$_SESSION['LEVEL'],Currency::CURRENCY_USD);
		$maxPerTimeUSD = $configInfo['max_deposit_per_time'];
		$maxPerDayUSD = $configInfo['max_deposit_per_day'];
		$bankAmount=$this->getUserBankTransactionApplicationOneDayAmount($memberID,Constant::DEPOSIT,Constant::STATUS_1_PENDING,Constant::STATUS_3_COMPLETED);
		$cashAmount=$this->getUserCashTransactionApplicationOneDayAmount($memberID,Constant::DEPOSIT,Constant::STATUS_1_PENDING,Constant::STATUS_3_COMPLETED);
		$oneDayAmountUSD =$bankAmount+$cashAmount;
		if($maxBalance < 0) return MessageCode::ERR_1_ERROR;

		if($amountUSD > $maxPerTimeUSD) return MessageCode::ERR_1822_OVER_ONE_TIME_LIMIT;
		if($amountUSD + $oneDayAmountUSD > $maxPerDayUSD) return MessageCode::ERR_1823_OVER_ONE_DAY_LIMIT;
		if($amountUSD + $pendingAmountUSD + $balanceTotalUSD > $maxBalance) return MessageCode::ERR_1824_OVER_MAX_BALANCE;

		return MessageCode::ERR_0_NO_ERROR;
	}

	private function _chkWithdrawConfigLimit($memberID, $amountUSD) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		if (!V::over_num($amountUSD, 0)) throw new TrantorException('AmountUSD');
		$c = new Config();
		$configInfo = $c->getWithdrawLimitConfig(User::USER_TYPE_1_MEMBER,$_SESSION['LEVEL'],Currency::CURRENCY_USD);
		if(!$configInfo) return MessageCode::ERR_1_ERROR;

		$maxPerTimeUSD = $configInfo['max_withdraw_per_time'];
		$maxPerDayUSD = $configInfo['max_withdraw_per_day'];
		$bankAmount=$this->getUserBankTransactionApplicationOneDayAmount($memberID,Constant::WITHDRAW,Constant::STATUS_1_PENDING,Constant::STATUS_3_COMPLETED);
		$cashAmount=$this->getUserCashTransactionApplicationOneDayAmount($memberID,Constant::WITHDRAW,Constant::STATUS_1_PENDING,Constant::STATUS_3_COMPLETED);
		$oneDayAmountUSD = $bankAmount+$cashAmount;

		if($amountUSD > $maxPerTimeUSD) return MessageCode::ERR_1822_OVER_ONE_TIME_LIMIT;
		if($amountUSD + $oneDayAmountUSD > $maxPerDayUSD) return MessageCode::ERR_1823_OVER_ONE_DAY_LIMIT;

		return MessageCode::ERR_0_NO_ERROR;
	}

	private function _addBankApplication($type, $currency, $amount, $transactionFee, $bankFee, $bankCode, $bankAccountNo, $bankAccountCompanyID, $remark = '') {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		if (!is_numeric($amount) || ($type == Constant::DEPOSIT && $amount <= 0) || ($type == Constant::WITHDRAW && $amount >= 0)) throw new TrantorException('Amount');
		if (!V::required($bankCode)) throw new TrantorException('BankCode');
		if (!V::required($bankAccountNo)) throw new TrantorException('BankAccountNo');
		if (!V::min_num($bankAccountCompanyID, 0)) throw new TrantorException('BankAccountCompanyID');
		if (!is_numeric($transactionFee) || ($type == Constant::DEPOSIT && $transactionFee < 0) || ($type == Constant::WITHDRAW && $transactionFee > 0)) throw new TrantorException('TransactionFee');
		if (!is_numeric($bankFee) || ($type == Constant::DEPOSIT && $bankFee < 0) || ($type == Constant::WITHDRAW && $bankFee > 0)) throw new TrantorException('BankFee');

		$sql = "insert into tbl_user_bank_transaction_application (time,user_type,user_id,type,currency,amount,transaction_fee,bank_fee,bank_code,bank_account_no,bank_account_name,bank_account_company_id,status,remark_applicant) value (now()," . User::USER_TYPE_1_MEMBER . "," . qstr($_SESSION['UID']) . "," . qstr($type) . "," . qstr($currency) . "," . qstr($amount) . "," . qstr($transactionFee) . "," . qstr($bankFee) . "," . qstr($bankCode) . "," . qstr($bankAccountNo) . "," . qstr($_SESSION['NAME']) . "," . qstr($bankAccountCompanyID) . ',' . self::STATUS_1_PENDING . ',' . qstr($remark) . ")";
		$this->execute($sql);
		return $this->insert_id();
	}

	//现业务暂时没有 tbl_transfer status = pending 的单, 有的话这里还需要汇总
	//用于Member
	public function getDepositPendingAmount($memberID) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('MemberID');
		$bankAmount=$this->getUserBankTransactionApplicationAmount(User::USER_TYPE_1_MEMBER, $memberID,Constant::DEPOSIT,self::STATUS_1_PENDING);
		$cashAmount=$this->getUserCashTransactionApplicationAmount($memberID,Constant::DEPOSIT,self::STATUS_1_PENDING);
		return abs($bankAmount) + abs($cashAmount);
	}

	//tbl_user_bank_transaction_application
	public function getUserBankTransactionApplicationAmount($userType, $userID, $type, $status1 = 0, $status2 = 0) {
		if (!in_array($userType, [User::USER_TYPE_1_MEMBER, User::USER_TYPE_2_AGENT, User::USER_TYPE_3_PARTNER, User::USER_TYPE_4_MERCHANT])) throw new TrantorException('User Type');
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!V::over_num($userID, 0)) throw new TrantorException("User ID");
		$sql = 'select ifnull(sum(t.amount/t1.exchange),"") amount from tbl_user_bank_transaction_application t left join tbl_currency t1 on(t.currency=t1.currency) where user_type=' . qstr($userType) . ' and user_id=' . qstr($userID) . ' and type=' . qstr($type);
		$status = "";
		if ($status1 > 0 && $status2 > 0) {
			$status = " and (status=" . qstr($status1) . " or status=" . qstr($status2) . ")";
		} else {
			if ($status1 > 0) $status = " and status=" . qstr($status1);
			if ($status2 > 0) $status = " and status=" . qstr($status2);
		}
		if ($status) $sql .= $status;
		$amount = $this->getOne($sql);
		if (!$amount) $amount = 0;
		return abs($amount);
	}

	public function getUserCashTransactionApplicationAmount($memberID, $type, $status1 = 0, $status2 = 0) {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!V::over_num($memberID, 0)) throw new TrantorException("Member ID");
		$sql = 'select ifnull(sum(t.amount/t1.exchange),"") amount from tbl_user_cash_transaction_application t left join tbl_currency t1 on(t.currency=t1.currency) where user_type=' . User::USER_TYPE_1_MEMBER . ' and user_id=' . qstr($memberID) . ' and type=' . qstr($type);
		$status = "";
		if ($status1 > 0 && $status2 > 0) {
			$status = " and (status=" . qstr($status1) . " or status=" . qstr($status2) . ")";
		} else {
			if ($status1 > 0) $status = " and status=" . qstr($status1);
			if ($status2 > 0) $status = " and status=" . qstr($status2);
		}
		if ($status) $sql .= $status;
		$amount = $this->getOne($sql);
		if (!$amount) $amount = 0;
		return abs($amount);
	}

	//for partner or merchant
	public function getUserCashTransactionApplication2Amount($userType, $userID, $type, $status1 = 0, $status2 = 0) {
		if (!in_array($userType, [User::USER_TYPE_3_PARTNER, User::USER_TYPE_4_MERCHANT])) throw new TrantorException('User Type');
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!V::over_num($userID, 0)) throw new TrantorException("User ID");
		$sql = 'select ifnull(sum(t.amount/t1.exchange),"") amount from tbl_user_cash_transaction_application2 t left join tbl_currency t1 on(t.currency=t1.currency) where user_type=' . qstr($userType) . ' and user_id=' . qstr($userID) . ' and type=' . qstr($type);
		$status = "";
		if ($status1 > 0 && $status2 > 0) {
			$status = " and (status=" . qstr($status1) . " or status=" . qstr($status2) . ")";
		} else {
			if ($status1 > 0) $status = " and status=" . qstr($status1);
			if ($status2 > 0) $status = " and status=" . qstr($status2);
		}
		if ($status) $sql .= $status;
		$amount = $this->getOne($sql);
		if (!$amount) $amount = 0;
		return abs($amount);
	}

	//tbl_user_bank_transaction_application
	public function getUserBankTransactionApplicationOneDayAmount($memberID, $type, $status1 = 0, $status2 = 0) {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!V::over_num($memberID, 0)) throw new TrantorException("Member ID");
		$sql = 'select ifnull(sum(t.amount/t1.exchange),"") amount from tbl_user_bank_transaction_application t left join tbl_currency t1 on(t.currency=t1.currency) where user_type=' . User::USER_TYPE_1_MEMBER . ' and user_id=' . qstr($memberID) . ' and type=' . $type . ' and date(time) = curdate()';
		$status = "";
		if ($status1 > 0 && $status2 > 0) {
			$status = " and (status=" . qstr($status1) . " or status=" . qstr($status2) . ")";
		} else {
			if ($status1 > 0) $status = " and status=" . qstr($status1);
			if ($status2 > 0) $status = " and status=" . qstr($status2);
		}
		if ($status) $sql .= $status;
		$amount = $this->getOne($sql);
		if (!$amount) $amount = 0;
		return abs($amount);
	}

	public function getUserCashTransactionApplicationOneDayAmount($memberID, $type, $status1 = 0, $status2 = 0) {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!V::over_num($memberID, 0)) throw new TrantorException("Member ID");
		$sql = 'select ifnull(sum(t.amount/t1.exchange),"") amount from tbl_user_cash_transaction_application t left join tbl_currency t1 on(t.currency=t1.currency) where user_type=' . User::USER_TYPE_1_MEMBER . ' and user_id=' . qstr($memberID) . ' and type=' . $type . ' and date(time) = curdate()';
		$status = "";
		if ($status1 > 0 && $status2 > 0) {
			$status = " and (status=" . qstr($status1) . " or status=" . qstr($status2) . ")";
		} else {
			if ($status1 > 0) $status = " and status=" . qstr($status1);
			if ($status2 > 0) $status = " and status=" . qstr($status2);
		}
		if ($status) $sql .= $status;
		$amount = $this->getOne($sql);
		if (!$amount) $amount = 0;
		return abs($amount);
	}


	public function getOneDayAmount($type, $currency) {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$bankAmount = $this->getUserBankTransactionApplicationOneDayAmount($_SESSION['UID'],$type,Constant::STATUS_1_PENDING,Constant::STATUS_3_COMPLETED);
		$cashAmount = $this->getUserCashTransactionApplicationOneDayAmount($_SESSION['UID'],$type,Constant::STATUS_1_PENDING,Constant::STATUS_3_COMPLETED);
		$total = $bankAmount + $cashAmount;
		$c = new Currency();
		return $c->transferAmount($total,Currency::CURRENCY_USD,$currency);
	}

	public function getDepositOrWithdrawLimitAmount($type, $currency) {
		if (!in_array($type, [Constant::DEPOSIT, Constant::WITHDRAW])) throw new TrantorException('Type');
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$cf = new Config();
		if ($type == Constant::DEPOSIT) {
			$rt = $cf->getDepositLimitConfig($_SESSION['TYPE'], $_SESSION['LEVEL'], $currency);
		} else {
			$rt = $cf->getWithdrawLimitConfig($_SESSION['TYPE'], $_SESSION['LEVEL'], $currency);
		}
		$rt['max_balance']=$cf->getMemberMaxBalance($_SESSION['UID'],$_SESSION['LEVEL'],$currency);
		return $rt;
	}

	public function getStatement($page, $dateStart = null, $dateEnd = null, $currency = null) {
		if (empty($dateStart) && empty($dateEnd)) {
			$dateFrom = \Utils::getDBDate();//默认展示当天数据
			$dateTo = \Utils::getDBDate();
		} else {
			$dateFrom = $dateStart;
			$dateTo = $dateEnd;
		}
		if (Utils::timeCompare($dateStart, $dateEnd) == 1) {
			$dateFrom = $dateEnd;
			$dateTo = $dateStart;
		}

		if ($currency) {
			//get statement list
			$sql = 'SELECT tat.date,tat.member_id,tat.type,tat.sub_type,tat.currency,SUM(tat.amount) amount FROM tbl_member_transaction tat WHERE tat.date<=' . qstr($dateTo) . ' and tat.date>=' . qstr($dateFrom) . ' and tat.member_id=' . qstr($_SESSION['UID']) . ' and tat.currency = ' . qstr($currency) . ' and tat.sub_type > 0';
			$sql .= ' GROUP BY tat.date,tat.sub_type,tat.currency';
			$rt = $this->getPageArray($sql, $page);

			//get beginning balance
			$sqlBeginning = "select sum(amount) amount from tbl_member_transaction where member_id=" . qstr($_SESSION['UID']) . " and type in (" . self::TRANSACTION_TYPE_1_BEGINNING . ", " . self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW . ", " . self::TRANSACTION_TYPE_4_TOP_UP . ", " . self::TRANSACTION_TYPE_5_LOTTO . ", " . self::TRANSACTION_TYPE_6_PAYMENT . ') and date<' . qstr($dateFrom) . ' and currency = ' . qstr($currency) . ' GROUP BY currency';
			$beginning = $this->getOne($sqlBeginning);
			if (!$beginning) $beginning = 0;
			$rt['beginning'] = $beginning;
		} else {
			//get statement list(all exchange to USD)
			$sql = "SELECT tat.date,tat.member_id,tat.type,tat.sub_type,'USD' as currency,SUM(tat.amount/t2.exchange) amount FROM tbl_member_transaction tat left join tbl_currency t2 on(tat.currency=t2.currency) WHERE tat.date<=" . qstr($dateTo) . ' and tat.date>=' . qstr($dateFrom) . ' and tat.member_id=' . qstr($_SESSION['UID']) . ' and tat.sub_type > 0';
			$sql .= ' GROUP BY tat.date,tat.sub_type';
			$rt = $this->getPageArray($sql, $page);

			//get beginning balance
			$sqlBeginning = "select sum(t.amount/t1.exchange) amount from tbl_member_transaction t left join tbl_currency t1 on(t.currency=t1.currency) where member_id=" . qstr($_SESSION['UID']) . " and type in (" . self::TRANSACTION_TYPE_1_BEGINNING . ", " . self::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW . ", " . self::TRANSACTION_TYPE_4_TOP_UP . ", " . self::TRANSACTION_TYPE_5_LOTTO . ", " . self::TRANSACTION_TYPE_6_PAYMENT . ') and date<' . qstr($dateFrom);
//			$sqlExchange = "select sum(t1.amount/t2.exchange) amount from (" . $sqlBeginning . ") t1 left join tbl_currency t2 on(t1.currency=t2.currency) where 1";
			$beginning = $this->getOne($sqlBeginning);
			if (!$beginning) $beginning = 0;
			$rt['beginning'] = $beginning;
		}
		return $rt;
	}

	public function getUserBankTransAppRecordByID($ID) {
		if(!V::over_num($ID,0)) throw new TrantorException('ID');
		$sql = "SELECT t1.amount,t1.transaction_fee,t1.bank_fee,t1.currency,t1.time,t1.status,t1.remark_applicant,t1.bank_code,t1.bank_account_no,t1.bank_account_name,t2.account_no company_bank_account_no,tb.name bank_name from tbl_user_bank_transaction_application t1 left join tbl_bank_account t2 on t1.bank_account_company_id=t2.id left join tbl_bank tb on (tb.code=t1.bank_code) WHERE t1.id=".qstr($ID);
		return $this->getLine($sql);
	}

	public function getUserBankTransAppRecordList($currency) {
		$sql = "SELECT id,type,amount,DATE_FORMAT(time,'%Y-%m-%d') time,status from tbl_user_bank_transaction_application WHERE user_type=" . qstr(User::USER_TYPE_1_MEMBER) . " AND user_id=" . qstr($_SESSION['UID']);
		if ($currency) $sql .= " AND currency=" . qstr($currency);
		$sql .= " ORDER BY time DESC LIMIT 5";
		return $this->getArray($sql);
	}

	public function getTransaction($page) {
		$sql = 'SELECT t.date,t.time,t.type,t.sub_type,t.currency,t.amount,t.biz_id,t.main_id,t.staff_id,t.remark,t2.name staff_name,t3.sub_type main_sub_type,t3.biz_id main_biz_id FROM tbl_member_transaction t LEFT JOIN tbl_staff t2 ON t2.id= t.staff_id LEFT JOIN tbl_member_transaction t3 ON t.main_id = t3.id WHERE t.member_id = ' . qstr($_SESSION['UID']) . ' ORDER BY t.time DESC';
		$rt = $this->getPageArray($sql, $page);

		// total
		$len = count($rt['list']);
		$dateBegin = $rt['list'][$len-1]['date'];
		if (!$dateBegin) return $rt;
		$sqlTotal = "SELECT DATE_FORMAT(date,'%Y-%m') months,sum(t.amount/t1.exchange) amount FROM tbl_member_transaction t left join tbl_currency t1 on(t.currency=t1.currency) WHERE member_id = ". qstr($_SESSION['UID']) . ' AND date >= ' . $dateBegin." group by months order by months desc";
		$rt['total_amount'] = Utils::simplifyKeyValue($this->getArray($sqlTotal));
		return $rt;
	}

	public function getBankWithdrawFee($applicationID){
		if (!V::over_num($applicationID, 0)) throw new TrantorException('ApplicationID');
		$sql = 'SELECT time,currency,amount,transaction_fee withdraw_fee,status FROM db_system_run.tbl_user_bank_transaction_application WHERE id = ' . qstr($applicationID);
		return $this->getLine($sql);
	}

	public function getCashWithdrawFee($applicationID){
		if (!V::over_num($applicationID, 0)) throw new TrantorException('ApplicationID');
		$sql = 'SELECT time,currency,amount,transaction_fee withdraw_fee,status FROM db_system_run.tbl_user_cash_transaction_application uct WHERE id = ' . qstr($applicationID);
		return $this->getLine($sql);
	}

	public function getWithdrawBankFee($applicationID){
		if (!V::over_num($applicationID, 0)) throw new TrantorException('ApplicationID');
		$sql = 'SELECT ubt.time,ubt.currency,ubt.amount,ubt.bank_fee,ubt.status,tb.name bank_name FROM db_system_run.tbl_user_bank_transaction_application ubt left join tbl_bank tb on (tb.code=ubt.bank_code) WHERE ubt.id = ' . qstr($applicationID);
		return $this->getLine($sql);
	}

	public function getMemberTransferLimitAmount($currency) {
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$cf = new Config();
		$rt = $cf->getMemberTransferLimitConfig($_SESSION['TYPE'], $_SESSION['LEVEL'], $currency);
		$rt['max_balance']=$cf->getMemberMaxBalance($_SESSION['UID'],$_SESSION['LEVEL'],$currency);
		return $rt;
	}

	public function getPartnerDepositPendingAmount($partnerID) {
		if (!V::over_num($partnerID, 0)) throw new TrantorException('Partner ID');
		$bankAmount=$this->getUserBankTransactionApplicationAmount(User::USER_TYPE_3_PARTNER, $partnerID,Constant::DEPOSIT,self::STATUS_1_PENDING);
		$cashAmount=$this->getUserCashTransactionApplication2Amount(User::USER_TYPE_3_PARTNER, $partnerID,Constant::DEPOSIT,self::STATUS_1_PENDING);
		return abs($bankAmount) + abs($cashAmount);
	}

	public function getMerchantDepositPendingAmount($merchantID) {
		if (!V::over_num($merchantID, 0)) throw new TrantorException('Merchant ID');
		$bankAmount=$this->getUserBankTransactionApplicationAmount(User::USER_TYPE_4_MERCHANT, $merchantID,Constant::DEPOSIT,self::STATUS_1_PENDING);
		$cashAmount=$this->getUserCashTransactionApplication2Amount(User::USER_TYPE_4_MERCHANT, $merchantID,Constant::DEPOSIT,self::STATUS_1_PENDING);
		return abs($bankAmount) + abs($cashAmount);
	}

}