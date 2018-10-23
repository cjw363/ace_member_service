<?php
use validation\Validator as V;

class Account extends \Base {

	const ID_10000_CASH_ACCOUNT = 10000;
	const ID_10001_CASH_ACCOUNT_PAYING = 10001;
	const ID_10020_BANK_ACCOUNT = 10020;
	const ID_10021_BANK_ACCOUNT_PAYING = 10021;
	const ID_10030_COLLECTOR = 10030;
	const ID_10031_COLLECTOR_PAYING = 10031;
//	const ID_11000_CASH_ACCOUNT_BRANCH = 11000;
//	const ID_11001_CASH_ACCOUNT_BRANCH_PAYING = 11001;
//	const ID_11020_BANK_ACCOUNT_BRANCH = 11020;
//	const ID_11021_BANK_ACCOUNT_BRANCH_PAYING = 11021;
	const ID_12002_MEMBER_LOAN_FROM_ACE = 12002;
	const ID_12004_AGENT_LOAN_FROM_ACE = 12004;
	const ID_12008_PARTNER_LOAN_FROM_ACE = 12008;
	const ID_12012_MEMBER_LOAN_FROM_PARTNER = 12012;
	const ID_12014_MEMBER_LOAN_FROM_SALARY = 12014;
	const ID_13000_PINCODE_INVENTORY_POOL = 13000;
	const ID_13001_PINCODE_INVENTORY = 13001;
	const ID_15000_CURRENCY_EXCHANGE = 15000;
	const ID_15002_CURRENCY_EXCHANGE_MEMBER = 15002;
//	const ID_15004_CURRENCY_EXCHANGE_AGENT = 15004;
//	const ID_15006_CURRENCY_EXCHANGE_PARTNER = 15006;

	const ID_20000_MEMBER_BALANCE = 20000;
	const ID_20001_MEMBER_BALANCE_PAYING = 20001;
	const ID_21000_NON_MEMBER_CASH_BALANCE = 21000;
//	const ID_22000_AGENT_BALANCE = 22000;
//	const ID_22001_AGENT_BALANCE_PAYING = 22001;
	const ID_23000_PARTNER_BALANCE = 23000;
	const ID_23001_PARTNER_BALANCE_PAYING = 23001;
	const ID_23006_PARTNER_BALANCE_BILL_PAYMENT=23006;
	const ID_23010_PARTNER_LEND_DEPOSIT = 23010;
	const ID_23012_PARTNER_LEND_SERVICE_CHARGE = 23012;
	const ID_23050_MERCHANT_BALANCE = 23050;
	const ID_23051_MERCHANT_BALANCE_PAYING = 23051;
	const ID_24000_BANK_FEE = 24000;
	const ID_25000_PINCODE_COMMISSION = 25000;
	const ID_26001_EDC_BILL = 26001;
	const ID_26002_WSA_BILL = 26002;
	const ID_26004_PARTNER_BILL = 26004;
	const ID_26006_PARTNER_BILL_SUSPICIOUS = 26006;

	const ID_39600_CAPITAL = 39600;
	const ID_39610_CAPITAL_PINCODE = 39610;
	const ID_39700_DIVIDEND_PAID = 39700;

	const ID_40002_MEMBER_WITHDRAW_FEE = 40002;
	const ID_40004_NON_MEMBER_TRANSFER_FEE = 40004;
//	const ID_40006_AGENT_DEPOSIT_FEE = 40006;
//	const ID_40008_AGENT_WITHDRAW_FEE = 40008;
//	const ID_40016_AGENT_LOAN_FEE = 40016;
	const ID_40018_MEMBER_LOAN_FEE = 40018;
	const ID_40020_BANK_INTEREST = 40020;
//	const ID_40022_BANK_BRANCH_INTEREST = 40022;
	const ID_46001_EDC_BILL_FEE = 46001;
	const ID_46002_WSA_BILL_FEE = 46002;
	const ID_46003_BILL_PAYMENT_FEE = 46003;
	const ID_49000_OTHER_INCOME = 49000;

	const ID_60010_BANK_INTEREST_TAX = 60010;
	const ID_60012_BANK_MANAGEMENT_FEE = 60012;
	const ID_60014_BANK_REMITTANCE_CHARGE = 60014;
//	const ID_60020_BANK_BRANCH_INTEREST_TAX = 60020;
//	const ID_60022_BANK_BRANCH_MANAGEMENT_FEE = 60022;
//	const ID_60024_BANK_BRANCH_REMITTANCE_CHARGE = 60024;
	const ID_60032_MEMBER_TOP_UP_DISCOUNT = 60032;
//	const ID_60034_AGENT_COMMISSION = 60034;
//	const ID_60040_AFFILIATE_PROFIT = 60040;
	const ID_60050_OPERATOR_BONUS = 60050;
	const ID_60090_MEMBER_COMPENSATION = 60090;
//	const ID_60092_AGENT_COMPENSATION = 60092;
//	const ID_60094_PARTNER_COMPENSATION = 60094;
	const ID_66001_EDC_BILL_EXPENSE = 66001;
	const ID_66002_WSA_BILL_EXPENSE = 66002;
	const ID_69000_OTHER_EXPENSE = 69000;

	const ACCOUNT_TYPE_1_ASSETS = 1;
	const ACCOUNT_TYPE_2_LIABILITIES = 2;
	const ACCOUNT_TYPE_3_EQUITY = 3;
	const ACCOUNT_TYPE_4_INCOME = 4;
	const ACCOUNT_TYPE_5_EXPENSE = 5;

	const RELATE_TYPE_1_COMPANY = 1;
	const RELATE_TYPE_3_COLLECTOR = 3;
	const RELATE_TYPE_5_BANK_ACCOUNT = 5;
	const RELATE_TYPE_7_CASH_ACCOUNT = 7;
	const RELATE_TYPE_15_BANK_ACCOUNT_BRANCH = 15;
	const RELATE_TYPE_17_CASH_ACCOUNT_BRANCH = 17;
	const RELATE_TYPE_45_PHONE_COMPANY = 45;

	const JOURNAL_VOUCHER_TYPE_2_DEBIT = 2;
	const JOURNAL_VOUCHER_TYPE_3_CREDIT = 3;

	private static $relateTypeArr = [self::RELATE_TYPE_1_COMPANY, self::RELATE_TYPE_3_COLLECTOR, self::RELATE_TYPE_5_BANK_ACCOUNT, self::RELATE_TYPE_7_CASH_ACCOUNT, self::RELATE_TYPE_15_BANK_ACCOUNT_BRANCH, self::RELATE_TYPE_17_CASH_ACCOUNT_BRANCH, self::RELATE_TYPE_45_PHONE_COMPANY];

	public function __construct() {
		parent::__construct(System::DB_RUN);
	}

	public function keepAccounts($debitAccountID, $creditAccountID, $debitRelateID, $creditRelateID, $currency, $amount, $remark = '', $date = null) {
		if (!V::over_num($debitAccountID, 0)) throw new Exception('Account::keepAccounts Parameter Error DebitAccountID');
		if (!V::over_num($creditAccountID, 0)) throw new Exception('Account::keepAccounts Parameter Error CreditAccountID');
		if (!Currency::check($currency)) throw new Exception('Account::keepAccounts Unsupported Currency ' . $currency);
		if (!V::over_num($amount, 0)) throw new Exception('Account::keepAccounts Parameter Error Amount');

		$debitAccount = $this->_getAccountByAccountID($debitAccountID);
		$creditAccount = $this->_getAccountByAccountID($creditAccountID);
		if (!$debitAccount || !$creditAccount) throw new Exception('Account::keepAccounts Invalid Account');

		if (!in_array($debitAccount['relate_type'], self::$relateTypeArr)) throw new Exception('Account::keepAccounts Invalid DebitAccountRelateType');
		if (!in_array($creditAccount['relate_type'], self::$relateTypeArr)) throw new Exception('Account::keepAccounts Invalid CreditAccountRelateType');
		if (($debitAccount['relate_type'] == self::RELATE_TYPE_1_COMPANY && $debitRelateID != 0) || ($debitAccount['relate_type'] != self::RELATE_TYPE_1_COMPANY && $debitRelateID == 0)) throw new Exception('Account::keepAccounts Parameter Error DebitRelateID');
		if (($creditAccount['relate_type'] == self::RELATE_TYPE_1_COMPANY && $creditRelateID != 0) || ($creditAccount['relate_type'] != self::RELATE_TYPE_1_COMPANY && $creditRelateID == 0)) throw new Exception('Account::keepAccounts Parameter Error CreditRelateID');

		$voucherID = $this->_addVoucher($debitAccountID, $creditAccountID, $debitRelateID, $creditRelateID, $currency, $amount, $remark, $date);

		if (in_array($debitAccount['type'], array(self::ACCOUNT_TYPE_1_ASSETS, self::ACCOUNT_TYPE_5_EXPENSE))) {
			$this->_accountDeposit($debitAccountID, $debitRelateID, $currency, $amount);
		} else {
			$this->_accountWithdraw($debitAccountID, $debitRelateID, $currency, $amount);
		}

		if (in_array($creditAccount['type'], array(self::ACCOUNT_TYPE_2_LIABILITIES, self::ACCOUNT_TYPE_3_EQUITY, self::ACCOUNT_TYPE_4_INCOME))) {
			$this->_accountDeposit($creditAccountID, $creditRelateID, $currency, $amount);
		} else {
			$this->_accountWithdraw($creditAccountID, $creditRelateID, $currency, $amount);
		}

		return $voucherID;
	}

	private function _getAccountByAccountID($accountID) {
		if (!V::over_num($accountID, 0)) throw new Exception('Account::_getAccountByAccountID Parameter Error AccountID');
		return $this->getLine("select * from tbl_account where id=$accountID");
	}

	private function _accountDeposit($accountID, $relateID, $currency, $amount, $date = null) {
		if (!V::over_num($accountID, 0)) throw new Exception('Account::_accountDeposit Parameter Error AccountID');
		if (!V::numeric($relateID)) throw new Exception('Account::_accountDeposit Parameter Error RelateID');
		if (!Currency::check($currency)) throw new Exception('Account::_accountDeposit Unsupported Currency ' . $currency);
		if (!V::over_num($amount, 0)) throw new Exception('Account::_accountDeposit Parameter Error Amount');
		if (!V::date($date, 'null')) throw new Exception('Account::_accountDeposit Parameter Error Date');
		$this->_accountUpdate($accountID, $relateID, $currency, $amount, \Constant::DEPOSIT, $date);
	}

	private function _accountWithdraw($accountID, $relateID, $currency, $amount, $date = null) {
		if (!V::over_num($accountID, 0)) throw new Exception('Account::_accountWithdraw Parameter Error AccountID');
		if (!V::numeric($relateID)) throw new Exception('Account::_accountWithdraw Parameter Error RelateID');
		if (!Currency::check($currency)) throw new Exception('Account::_accountWithdraw Unsupported Currency ' . $currency);
		if (!V::over_num($amount, 0)) throw new Exception('Account::_accountWithdraw Parameter Error Amount');
		if (!V::date($date, 'null')) throw new Exception('Account::_accountWithdraw Parameter Error Date');
		$this->_accountUpdate($accountID, $relateID, $currency, -$amount, \Constant::WITHDRAW, $date);
	}

	private function _accountUpdate($accountID, $relateID, $currency, $amount, $type, $date = null) {
		if (!V::over_num($accountID, 0)) throw new Exception('Account::_accountUpdate Parameter Error AccountID');
		if (!V::numeric($relateID)) throw new Exception('Account::_accountUpdate Parameter Error RelateID');
		if (!Currency::check($currency)) throw new Exception('Account::_accountUpdate Unsupported Currency ' . $currency);
		if (!V::numeric($amount)) throw new Exception('Account::_accountUpdate Parameter Error Amount');
		if ($type != \Constant::DEPOSIT && $type != \Constant::WITHDRAW) throw new Exception('Account::_accountUpdate Parameter Error Type');
		if (($type == \Constant::DEPOSIT && $amount < 0) || ($type == \Constant::WITHDRAW && $amount > 0)) throw new Exception('Account::_accountUpdate Type/Amount Not Match');
		if (!V::date($date, 'null')) throw new Exception('Account::_accountUpdate Parameter Error Date');
		if (!$date) $date = 'curdate()'; else $date = qstr($date);

		$sql = "insert into tbl_account_balance (account_id,relate_id,currency,amount) values ($accountID, $relateID," . qstr($currency) . ", $amount) on duplicate key update amount=amount+values(amount)";
		$this->execute($sql);

		$sql = "insert into tbl_account_date_balance (account_id,relate_id,currency,date,type,amount) values ($accountID, $relateID, " . qstr($currency) . ", $date, $type, $amount) on duplicate key update amount=amount+values(amount)";
		$this->execute($sql);
	}

	private function _addVoucher($debitAccountID, $creditAccountID, $debitRelateID, $creditRelateID, $currency, $amount, $remark, $date = null) {
		if (!V::over_num($debitAccountID, 0)) throw new Exception('Account::_addVoucher Parameter Error DebitAccountID');
		if (!V::over_num($creditAccountID, 0)) throw new Exception('Account::_addVoucher Parameter Error CreditAccountID');
		if (!V::numeric($debitRelateID)) throw new Exception('Account::_addVoucher Parameter Error DebitRelateID');
		if (!V::numeric($creditRelateID)) throw new Exception('Account::_addVoucher Parameter Error CreditRelateID');
		if (!V::over_num($amount, 0)) throw new Exception('Account::_addVoucher Parameter Error Amount');
		if ($date && \Utils::timeCompare($date, \Utils::getDBDate()) > 0) throw new Exception('Account::_addVoucher Parameter Error Date');

		if ($date && \Utils::timeCompare($date, \Utils::getDBDate()) != 0) {
			$time = qstr($date . ' 00:00:00');
		} else $time = 'now()';
		$sql = 'insert into tbl_journal_voucher (time,flag_system,remark) values (' . $time . ',' . \Constant::NO . ',' . qstr($remark) . ')';
		$this->execute($sql);
		$voucherID = $this->insert_id();
		if (!($voucherID > 0)) throw new Exception('Account::_addVoucher Create Voucher Fail');

		$sql = "insert into tbl_journal_voucher_detail (voucher_id,seq,type,account_id,relate_id,currency,amount) values ($voucherID,1," . self::JOURNAL_VOUCHER_TYPE_2_DEBIT . ",$debitAccountID,$debitRelateID," . qstr($currency) . ",$amount), ($voucherID,2," . self::JOURNAL_VOUCHER_TYPE_3_CREDIT . ",$creditAccountID,$creditRelateID," . qstr($currency) . ",$amount)";
		$this->execute($sql);

		return $voucherID;
	}

} 