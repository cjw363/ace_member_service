<?php
use validation\Validator as V;

class SalaryLoan extends Loan {

	public function __construct() {
		parent::__construct();
	}

	public function getSalaryLoanConfig() {
		$sql = 'select currency,min_loan_credit_per_member,max_loan_credit_per_member,member_total_loan_limit,loan_amount_unit,service_charge_rate,service_charge_min_amount from tbl_config_ace_lend_salary';
		$rt = $this->getLine($sql);
		if ($rt['service_charge_rate'] > 0){
			$rt['service_charge_rate'] = $rt['service_charge_rate'] / 100;
		} else{
			$rt['service_charge_rate'] = 0;
		}
		$c = new Config();
		$maxLoan = $c->getConfigLimitMaxLoan($_SESSION['LEVEL'], Currency::CURRENCY_USD) - parent::getMemberTotalLoan();
		$salaryLoanLimit = $rt['member_total_loan_limit'] - parent::getTotalSalaryLoan();
		$memberCreditLoan = $this->getSalaryCreditLoan($_SESSION['UID'])['credit'] - $this->getSalaryCreditLoan($_SESSION['UID'])['loan'];
		$rt['available_loan'] = min($maxLoan, $salaryLoanLimit, $memberCreditLoan) > 0 ? min($maxLoan, $salaryLoanLimit, $memberCreditLoan) : 0;
		return $rt;
	}

	public function getSalaryCreditLoan($memberID){
		if(!V::over_num($memberID,0)) throw new TrantorException("Member ID");
		$sql="select currency,credit,loan,due_date_initial,due_date from tbl_member_loan_salary where member_id=".qstr($memberID);
		return $this->getLine($sql);
	}

	public function getSalaryLoanRecentFlow($memberID) {
		if (!V::over_num($memberID, 0)) throw new TrantorException("Member ID");
		$sql = "select id,DATE_FORMAT(time,'%Y-%m-%d') time,type,currency,amount from tbl_member_loan_salary_flow where member_id=" . qstr($memberID) . " order by time desc limit 5";
		return $this->getArray($sql);
	}

	public function getSalaryLoanHistory($memberID, $page) {
		if (!V::over_num($memberID, 0)) throw new TrantorException("Member ID");
		$sql = "select id,DATE_FORMAT(time,'%Y-%m-%d') time,type,currency,amount from tbl_member_loan_salary_flow where member_id=" . qstr($memberID) . " order by time desc";
		return $this->getPageArray($sql, $page);
	}

	public function getSalaryLoanFlowDetail($memberID, $flowID) {
		if (!V::over_num($memberID, 0)) throw new TrantorException("Member ID");
		if (!V::over_num($flowID, 0)) throw new TrantorException("Flow ID");
		$sql = "select t1.id,t1.time,t1.type,t1.currency,t1.amount,t2.remark,ifnull(t2.amount,0) service_charge from tbl_member_loan_salary_flow t1 left join db_system_run.tbl_member_transaction t2 on(t2.main_id=t1.id and t2.sub_type=" .Transaction::TRANSACTION_SUB_TYPE_243_SALARY_LOAN_SERVICE_CHARGE . ") where t1.member_id=" . qstr($memberID) . " and t1.id=" . qstr($flowID);
		return $this->getLine($sql);
	}

	public function checkSalaryLoan($amount, $serviceCharge){
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($serviceCharge, 0)) throw new TrantorException('ServiceCharge');

		$t = new Transaction();
		$pendingAmount = $t->getDepositPendingAmount($_SESSION['UID']);
		$b = new Balance();
		$totalBalance = $b->getTotalBalanceUSD($_SESSION['UID']);
		$temp = $t->getDepositOrWithdrawLimitAmount(Constant::DEPOSIT, Currency::CURRENCY_USD);
		$maxBalance = $temp['max_balance'];
		$config = $this->getSalaryLoanConfig();
		$memberCreditAndLoan = $this->getSalaryCreditLoan($_SESSION['UID']);

		$errRs = array();
		$errRs['pending_amount']=$pendingAmount;
		$errRs['total_balance']=$totalBalance;
		$errRs['max_balance']=$maxBalance;
		$errRs['loan_config'] = $config;
		$errRs['credit_loan']=$memberCreditAndLoan;

		if ($amount + $memberCreditAndLoan['loan'] > $memberCreditAndLoan['credit']) return array('err_code' => MessageCode::ERR_1831_OVER_CREDIT, 'result' => $errRs);

		$charge = $config['service_charge_rate'] * $amount > $config['service_charge_min_amount'] ? $config['service_charge_rate'] * $amount : $config['service_charge_min_amount'];
		if (bccomp($serviceCharge, $charge, 2) != 0) return array('err_code' => MessageCode::ERR_1837_CONFIG_EXPIRED, 'result' => $errRs);

		if ($amount < $config['min_loan_credit_per_member']) return array('err_code' => MessageCode::ERR_1834_CAN_NOT_LESS_THAN_MIN_LOAN, 'result' => $errRs);
		if ($amount > $config['max_loan_credit_per_member']) return array('err_code' => MessageCode::ERR_1822_OVER_ONE_TIME_LIMIT, 'result' => $errRs);

		$availableLoan = $config['available_loan'];
		if ($amount > $availableLoan) return array('err_code' => MessageCode::ERR_1826_OVER_LOAN_LIMIT, 'result' => $errRs);

		if ($amount + $pendingAmount + $totalBalance > $maxBalance) return array('err_code' => MessageCode::ERR_1827_OVER_MEMBER_MAX_LIMIT, 'result' => $errRs);

		return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	public function salaryLoan($amount, $serviceCharge) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		if (!V::over_num($serviceCharge, 0)) throw new TrantorException('ServiceCharge');

		$rt = $this->checkSalaryLoan($amount, $serviceCharge);
		if ($rt['err_code'] != MessageCode::ERR_0_NO_ERROR) return $rt;

		//(1) 如果当前 Loan == 0，更新 due_date / initial_due_date = curdate()+30；
		//(2) 更新 tbl_member_loan_salary；
		$memberCreditAndLoan = $this->getSalaryCreditLoan($_SESSION['UID']);
		if ($memberCreditAndLoan['loan'] == 0) {
			$sql = "update tbl_member_loan_salary set loan=loan+$amount,due_date_initial=DATE_ADD(CURDATE(), INTERVAL 30 DAY),due_date=DATE_ADD(CURDATE(), INTERVAL 30 DAY) where member_id=" . qstr($_SESSION['UID']);
			$this->execute($sql);
		} else {
			$sql = "update tbl_member_loan_salary set loan=loan+$amount where member_id=" . qstr($_SESSION['UID']);
			$this->execute($sql);
		}
		//(3) 插入 tbl_member_loan_salary_flow数据；
		$insertID = $this->_addFlow($amount, Constant::DEPOSIT);
		//(4) Member 余额变动；
		$b = new Balance();
		$b->updateCurrentMemberBalance(Constant::DEPOSIT, Currency::CURRENCY_USD, $amount);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_USD, -$serviceCharge);
		//记账
		$ac = new Account();
		$vRemark = 'Salary Loan, [Member] ' . $_SESSION['PHONE'] . ', ' . $amount . ' ' . Currency::CURRENCY_USD ;
		$voucherID = $ac->keepAccounts(Account::ID_12014_MEMBER_LOAN_FROM_SALARY, Account::ID_20000_MEMBER_BALANCE, 0, 0, Currency::CURRENCY_USD, $amount, $vRemark);
		$t = new Transaction();
		$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_240_SALARY_LOAN, Currency::CURRENCY_USD, $amount, $insertID, 0, $voucherID, $vRemark);

		$voucherID = $ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_40018_MEMBER_LOAN_FEE, 0, 0, Currency::CURRENCY_USD, $serviceCharge, $vRemark);
		$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_243_SALARY_LOAN_SERVICE_CHARGE, Currency::CURRENCY_USD, -$serviceCharge, 0, $insertID, $voucherID, $vRemark);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	public function checkSalaryLoanReturn($amount) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		$b = new Balance();
		$rt = array();
		$rt['usd_balance']= $b->getBalanceByCurrency(Currency::CURRENCY_USD);
		if ($rt['usd_balance'] < $amount){
			$rt['current_loan']=$this->getSalaryCreditLoan($_SESSION['UID'])['loan'];
			return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE, 'result' => $rt);
		}
		return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	public function salaryLoanReturn($amount) {
		if (!V::over_num($amount, 0)) throw new TrantorException('Amount');
		$b = new Balance();
		$rt = $this->checkSalaryLoanReturn($amount);
		if ($rt['err_code'] != MessageCode::ERR_0_NO_ERROR) return $rt;
		//更新 tbl_member_loan_salary
		$sql = "update tbl_member_loan_salary set loan=loan-$amount where member_id=" . qstr($_SESSION['UID']);
		$this->execute($sql);
		//插入 tbl_member_loan_salary_flow 数据；
		$insertID = $this->_addFlow(-$amount, Constant::WITHDRAW);
		//Member Cash 余额扣减；
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_USD, -$amount);
		$ac = new Account();
		$vRemark = 'Return Salary Loan, [Member] ' . $_SESSION['PHONE'] . ', ' . number_format($amount, 2) . ' ' . Currency::CURRENCY_USD ;
		$voucherID = $ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_12014_MEMBER_LOAN_FROM_SALARY, 0, 0, Currency::CURRENCY_USD, $amount, $vRemark);
		$t = new Transaction();
		$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW, Transaction::TRANSACTION_SUB_TYPE_241_RETURN_SALARY_LOAN, Currency::CURRENCY_USD, -$amount, $insertID, 0, $voucherID, $vRemark);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	private function _addFlow($amount,$type){
		if(!in_array($type,[Constant::BEGINNING,Constant::DEPOSIT,Constant::WITHDRAW])) throw new TrantorException('Type');
		$sql="insert into tbl_member_loan_salary_flow(time,member_id,type,currency,amount)VALUES (now(),".qstr($_SESSION['UID']).",$type,".qstr(Currency::CURRENCY_USD).",$amount)";
		$this->execute($sql);
		return $this->insert_id();
	}

}