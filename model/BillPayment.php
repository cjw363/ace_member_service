<?php
use validation\Validator as V;
class BillPayment extends Base {
	const DB_RUN = "payment_run";

	const PARTNER_BILLER_TYPE_1_FINANCE=1;
	const PARTNER_BILLER_TYPE_2_INSURANCE=2;
	const PARTNER_BILLER_TYPE_3_INTERNET_AND_TV=3;
	const PARTNER_BILLER_TYPE_4_OTHERS=4;

	const PARTNER_BILLER_SOURCE_TYPE_1_MEMBER=1;

	const STATUS_1_ACTIVE=1;

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}


	public function getBiller($type){
		if (!in_array($type, array(self::PARTNER_BILLER_TYPE_1_FINANCE, self::PARTNER_BILLER_TYPE_2_INSURANCE, self::PARTNER_BILLER_TYPE_3_INTERNET_AND_TV, self::PARTNER_BILLER_TYPE_4_OTHERS))) throw new TrantorException("Partner Biller Type");
		$sql="select t1.id,t1.partner_id,t2.code,t2.name,t2.phone_contact phone,t1.title,t1.type,t1.currency,t1.bill_id_title,t1.bill_id_length,t1.max_amount from tbl_partner_biller t1 left join db_system_run.tbl_partner t2 on(t1.partner_id=t2.id) where t1.status=".self::STATUS_1_ACTIVE." and t1.type=".qstr($type);
		return $this->getArray($sql);
	}

	public function getBillerByID($id){
		if(!V::over_num($id,0)) throw new TrantorException("Biller ID");
		$sql="select t1.id,t1.partner_id,t2.code,t2.name,t2.phone_contact phone,t1.title,t1.type,t1.currency,t1.bill_id_title,t1.bill_id_length,t1.max_amount from tbl_partner_biller t1 left join db_system_run.tbl_partner t2 on(t1.partner_id=t2.id) where t1.status=".self::STATUS_1_ACTIVE." and t1.id=".qstr($id);
		return $this->getLine($sql);
	}

	public function getBillerConfig($partnerID,$billID){
		if(!V::over_num($partnerID,0)) throw new Exception("Partner ID");
		if(!V::over_num($billID,0)) throw new Exception("Biller ID");
		$sql="select currency,split_amount,type_fee_and_commission,fee_percent,fee from tbl_config_partner_bill where partner_id=".qstr($partnerID)." and biller_id=".qstr($billID)." order by split_amount";
		return $this->getArray($sql);
	}

	public function  checkBillPayment($partnerID,$billID,$currency,$amount,$fee,$type,$billNumber){
		if (!V::over_num($partnerID, 0)) throw new Exception("Partner ID");
		if (!V::over_num($billID, 0)) throw new Exception("Biller ID");
		if (!Currency::check($currency)) throw new Exception("Currency");
		if (!V::over_num($amount, 0)) throw new Exception("Amount");
		if (!V::min_num($fee, 0)) throw new Exception("Fee");
		if (!in_array($type, array(self::PARTNER_BILLER_TYPE_1_FINANCE, self::PARTNER_BILLER_TYPE_2_INSURANCE, self::PARTNER_BILLER_TYPE_3_INTERNET_AND_TV, self::PARTNER_BILLER_TYPE_4_OTHERS))) throw new TrantorException("Partner Biller Type");
		$s=new System();
		$isFunction=$s->isFunctionRunning(Constant::FUNCTION_135_PAY_BILL_TO_PARTNER);
		if(!$isFunction) return MessageCode::ERR_505_FUNCTION_NOT_RUNNING;
		$sql = "select max_amount,bill_id_length from tbl_partner_biller where id=" . qstr($billID);
		$bl = $this->getLine($sql);
		$split=$this->_getConfigFee($partnerID,$billID,$amount,$currency);
		$minAmount=min($bl['max_amount'],$split['split_amount']);
		if ($amount > $minAmount) return MessageCode::ERR_1724_OVER_MAX_AMOUNT;

		$billLength=$bl['bill_id_length'];
		if(!in_array(strlen($billNumber),explode(",",$billLength))) return MessageCode::ERR_1725_OVER_BILL_ID_LENGTH;
		$b = new Balance();
		$balance = $b->getBalanceByCurrency($currency);
		if ($amount > $balance) return MessageCode::ERR_1709_NOT_ENOUGH_BALANCE;
		$fee1=$split['fee'];
		if (Utils::simplifyAmount($currency,$fee) != Utils::simplifyAmount($currency,$fee1)) throw new Exception("Fee");
		return MessageCode::ERR_0_NO_ERROR;
	}



	public function saveBillPayment($partnerID, $billID, $currency, $amount, $fee, $type,$billNumber, $remark) {
		if (!V::over_num($partnerID, 0)) throw new Exception("Partner ID");
		if (!V::over_num($billID, 0)) throw new Exception("Biller ID");
		if (!Currency::check($currency)) throw new Exception("Currency");
		if (!V::over_num($amount, 0)) throw new Exception("Amount");
		if (!V::min_num($fee, 0)) throw new Exception("Fee");
		if (!in_array($type, array(self::PARTNER_BILLER_TYPE_1_FINANCE, self::PARTNER_BILLER_TYPE_2_INSURANCE, self::PARTNER_BILLER_TYPE_3_INTERNET_AND_TV, self::PARTNER_BILLER_TYPE_4_OTHERS))) throw new TrantorException("Partner Biller Type");
		$code=$this->checkBillPayment($partnerID,$billID,$currency,$amount,$fee,$type,$billNumber);
		if($code>0) return $code;

		$sql = "insert into tbl_bill_partner(date,time,partner_id,biller_id,phone,partner_bill_id,currency,amount,source_type,source_id,currency_fee,fee,remark) values (curdate(),now()," . qstr($partnerID) . "," . qstr($billID) . "," . qstr($_SESSION['PHONE']) ."," .qstr($billNumber)."," . qstr($currency) . "," . qstr($amount) . "," . self::PARTNER_BILLER_SOURCE_TYPE_1_MEMBER . "," . $_SESSION['UID'] . "," . qstr($currency) . "," . qstr($fee) . "," . qstr($remark) . ")";
		$this->execute($sql);
		$id = $this->insert_id();
		$a = new Account();
		$t = new Transaction();
		$vRemark = "[Member] Bill Payment #$id";
		$remarkAmount = $vRemark.", Amount " . $currency . ' ' . number_format($amount, 2);
		$remarkFee = $vRemark.", Fee " . $currency . ' ' . number_format($fee, 2);
		$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_23006_PARTNER_BALANCE_BILL_PAYMENT, 0, 0, $currency, $amount, $vRemark);
		$transactionID = $t->add(Transaction::TRANSACTION_TYPE_6_PAYMENT, Transaction::TRANSACTION_SUB_TYPE_611_PAY_PARTNER_BILL, $currency, -$amount, $id, 0, $voucherID, $vRemark . $remarkAmount);
		$b = new Balance();
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$amount,$transactionID);
		if ($fee > 0) {
			$voucherID = $a->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_46003_BILL_PAYMENT_FEE, 0, 0, $currency, $fee, $vRemark);
			$FeeTransactionID=$t->add(Transaction::TRANSACTION_TYPE_6_PAYMENT, Transaction::TRANSACTION_SUB_TYPE_613_PAY_PARTNER_BILL_FEE, $currency, -$fee, 0, $transactionID, $voucherID, $vRemark . $remarkFee);
			$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$fee,$FeeTransactionID);
		}

		$partnerTransactionID=$t->addPartner($partnerID,Transaction::TRANSACTION_TYPE_6_PAYMENT, Transaction::TRANSACTION_SUB_TYPE_621_BILL_PAYMENT_INCOME, $currency, $amount, $id, 0, $voucherID, $remark);
		$b->updatePartnerBalance($partnerID,Balance::PARTNER_MONEY_TYPE_3_BILL_PAYMENT,Constant::DEPOSIT, $currency, $amount,$partnerTransactionID);

		$l = new Log();
		$l->addLog($vRemark . $remarkAmount . $remarkFee);
		return MessageCode::ERR_0_NO_ERROR;
	}

	private function _getConfigFee($partnerID, $billID, $amount, $currency) {
		$config = $this->getBillerConfig($partnerID, $billID);
		$c = new Currency();
		$fee = 0;
		$len = count($config);
		if($len===0) return array('fee'=>$fee,'split_amount'=>0);
		$r = $config[$len - 1];
		$rate = $c->getExchangeRate1($currency, $r['currency']);
		$maxAmount=round($r['split_amount'] * $rate,2);
		if ($amount >= $maxAmount) {
			$rate1 = $c->getExchangeFeeRate($currency, $r['currency']);
			if ($r['type_fee_and_commission'] == 2) {
				$fee = $r['fee'] * $rate1;
			} else {
				$fee = ($amount * $r['fee_percent'] * $rate1) / 100;
			}
		}
		if ($fee > 0) return array('fee'=>$fee,'split_amount'=>$maxAmount);
		foreach ($config as $r) {
			$rate = $c->getExchangeRate1($currency, $r['currency']);
			$maxAmount=round($r['split_amount'] * $rate,2);
			if ($amount <= $maxAmount) {
				$rate1 = $c->getExchangeFeeRate($currency, $r['currency']);
				if ($r['type_fee_and_commission'] == 2) {
					$fee = $r['fee'] * $rate1;
				} else {
					$fee = ($amount * $r['fee_percent'] * $rate1) / 100;
				}
				break;
			}
		}
		return array('fee'=>$fee,'split_amount'=>$maxAmount);
	}

	public function getBillPaymentHistoryList($page){
		$sql="select id,time,partner_bill_id,currency,amount,fee from tbl_bill_partner where source_type=".User::USER_TYPE_1_MEMBER." and source_id=".$_SESSION['UID']." order by time desc";
		return $this->getPageArray($sql,$page);
	}

	public function getBillPaymentDetail($id){
		$sql="select t1.title,t1.bill_id_title,t2.code,t2.name,t.time,t.phone,t.partner_bill_id,t.currency,t.amount,t.fee,t.remark from tbl_bill_partner t left join tbl_partner_biller t1 on(t.biller_id=t1.id) left join db_system_run.tbl_partner t2 on(t.partner_id=t2.id) where t.id=".qstr($id);
		return $this->getLine($sql);
	}
}