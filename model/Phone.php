<?php
use validation\Validator as V;

class Phone extends \Base {

	const DB_RUN = "phone_run";

	const STATUS_CARD_1_SELLING = 1;
	const STATUS_CARD_2_SOLD = 2;
	const RELATE_TYPE_45_PHONE_COMPANY = 45;

	const TOP_UP_TYPE_1_SHOW_PINCODE = 1;
	const TOP_UP_TYPE_2_DIRECTLY_TOP_UP = 2;
	const TOP_UP_TYPE_3_SEND_SMS = 3;

	const CARD_SELLER_TYPE_2_MEMBER = 2;

	const COUNTRY_CODE_855_CAMBODIA = 855;
	const COUNTRY_CODE_84_VIETNAM = 84;
	const COUNTRY_CODE_66_THAILAND = 66;
	const COUNTRY_CODE_86_CHINA = 86;

	public static $pinCode2Mapping = ['cellcard' => '*123*', 'smart' => '*888*', 'metfone' => '*197*', 'qbmore' => '*133*', 'ais' => '*120*', 'dtac' => '*100*', 'mobifone' => '*100*', 'viettel' => '*100*', 'vinaphone' => '*100*'];

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function getFaceValueByCompanyID($companyID) {
		if (!V::over_num($companyID, 0)) throw new TrantorException("CompanyID");
		$sql = "select t1.face_value,t1.status,(select COUNT(*) from tbl_card t2 WHERE t2.status=" . self::STATUS_CARD_1_SELLING . " and t1.phone_company_id=t2.phone_company_id and t1.face_value=t2.face_value and t1.currency=t2.currency) count from tbl_face_value t1 where t1.phone_company_id=" . qstr($companyID) . " order by face_value";
		return $this->getArray($sql);
	}

	public function getTopUpOrderList($page) {
		$sql = "select t1.id,t1.time,t1.currency,t1.face_value,t1.top_up_type,t1.phone,t1.pincode2,t1.amount price from tbl_order t1 where t1.seller_type=" . User::USER_TYPE_1_MEMBER . " and t1.seller_id=" . qstr($_SESSION['UID']) . " order by t1.time desc";
		return $this->getPageArray($sql, $page);
	}

	public function getTopUpOrder($ID) {
		if (!V::over_num($ID, 0)) throw new TrantorException("ID");
		$sql = "select t1.id,t1.time,t1.currency,t1.face_value,t1.top_up_type,t1.phone,t1.sn,t1.pincode2,t1.amount price,t3.name phone_company_name from tbl_order t1 left join db_system_run.tbl_phone_company t3 on(t1.phone_company_id=t3.id)where t1.id=" . qstr($ID);
		return $this->getLine($sql);
	}

	public function topUp($companyID, $faceValue, $price, $currency, $type, $phone) {
		if (!in_array($type, [self::TOP_UP_TYPE_1_SHOW_PINCODE, self::TOP_UP_TYPE_2_DIRECTLY_TOP_UP, self::TOP_UP_TYPE_3_SEND_SMS])) throw new TrantorException("Type");
		$s = new System();
		if (($type == self::TOP_UP_TYPE_1_SHOW_PINCODE && !$s->isFunctionRunning(Constant::FUNCTION_121_MEMBER_TOP_UP_SHOW_PIN_CODE)) || ($type == self::TOP_UP_TYPE_3_SEND_SMS && !$s->isFunctionRunning(Constant::FUNCTION_122_MEMBER_TOP_UP_SEND_SMS))) return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);

		if (!V::over_num($companyID, 0)) throw new TrantorException("CompanyID");
		if (!V::over_num($faceValue, 0)) throw new TrantorException("FaceValue");
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$phone = preg_replace('/^0*/', '', $phone);
		if ($type != self::TOP_UP_TYPE_1_SHOW_PINCODE && !V::required($phone)) throw new TrantorException("Phone");
		$sql = "select status from tbl_face_value where phone_company_id=" . qstr($companyID) . " and currency=" . qstr($currency) . " and face_value=" . qstr($faceValue);
		$status = $this->getOne($sql);
		if ($status != Constant::STATUS_1_ACTIVE) throw new TrantorException("FaceValue");

		$pc = new PhoneCompany();
		$c = $pc->getCompanyById($companyID);
		$discountAmount = $faceValue * $c['member_discount'] * 0.01;
		if (!V::min_num($discountAmount, 0) || $discountAmount >= $faceValue) throw new TrantorException("DisCountAmount");
		$amount = $faceValue - $discountAmount;
		if (!V::over_num($amount, 0)) throw new TrantorException('Error Amount', 2);
		if (bccomp($amount, $price, 2) != 0) throw new TrantorException("Error Price", 2);
		$b = new Balance();
		$balance = $b->getBalanceByCurrency($currency);
		if ($amount > $balance) return array('err_code' => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$sql = "select id,sn,pincode from tbl_card where phone_company_id=" . qstr($companyID) . " and currency=" . qstr($currency) . " and face_value=" . qstr($faceValue) . " and status=" . self::STATUS_CARD_1_SELLING . " limit 1";
		$rt = $this->getLine($sql);
		if (empty($rt)) throw new TrantorException("No Card", 2);

		$pinCode2 = $this->_getPinCode2($c['name'], $rt['pincode'], $rt['sn']);
		if (!V::required($pinCode2)) throw new TrantorException("Pincode2");

		$sql = "insert into tbl_order(time,seller_type,seller_id,card_id,phone_company_id,currency,face_value,amount,sn,pincode,top_up_type,pincode2,phone)values(now()," . User::USER_TYPE_1_MEMBER . "," . qstr($_SESSION['UID']) . "," . qstr($rt['id']) . "," . qstr($companyID) . "," . qstr($currency) . "," . qstr($faceValue) . "," . qstr($amount) . "," . qstr($rt['sn']) . "," . qstr($rt['pincode']) . "," . qstr($type) . "," . qstr($pinCode2) . "," . qstr($phone) . ")";
		$this->execute($sql);

		$orderId = $this->insert_id();
		$sql = "update tbl_card set status=" . self::STATUS_CARD_2_SOLD . ",time_sell=now(),seller_type=" . self::CARD_SELLER_TYPE_2_MEMBER . ",seller_id=" . qstr($_SESSION['UID']) . ",order_id=" . qstr($orderId) . " where id=" . qstr($rt['id']);
		$this->execute($sql);

		$vRemark = 'Top Up ';
		if ($type == self::TOP_UP_TYPE_1_SHOW_PINCODE) $vRemark .= "(Show Pin-code) #";
		if ($type == self::TOP_UP_TYPE_2_DIRECTLY_TOP_UP) $vRemark .= "(Directly Top Up) #";
		if ($type == self::TOP_UP_TYPE_3_SEND_SMS) $vRemark .= "(Send SMS) #";
		$vRemark .= $orderId . ' ' . $c['name'] . ' ' . $c['currency'] . ' ' . number_format($faceValue, 2);

		$at = new Account();
		$t = new Transaction();

		$voucherID = $at->keepAccounts(Account::ID_20000_MEMBER_BALANCE, Account::ID_13001_PINCODE_INVENTORY, 0, $companyID, $currency, $amount, $vRemark);
		$transactionID= $t->add(Transaction::TRANSACTION_TYPE_4_TOP_UP, Transaction::TRANSACTION_SUB_TYPE_401_PHONE_TOP_UP, $currency, -$amount, $orderId, 0,$voucherID,$vRemark);
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, $currency, -$amount,$transactionID);
		if ($type == self::TOP_UP_TYPE_3_SEND_SMS) {
			$msg ='[Asia Weiluy] ' . $c['name'] . ' ' . $faceValue . ' ' . $currency . ' ' . $pinCode2 . ' (SN: ' . $rt['sn'] . ')';
			$s = new SMSNotification();
			$s->addPhone2($phone, $msg);
		}
		if ($discountAmount > 0) {
			$at->keepAccounts(Account::ID_60032_MEMBER_TOP_UP_DISCOUNT, Account::ID_13001_PINCODE_INVENTORY, 0, $companyID, $currency, $discountAmount, $vRemark);
		}

		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array('order_id' => $orderId));
	}

	private function _getPinCode2($companyName, $pinCode, $sn) {
		$companyName = strtolower($companyName);
		if(in_array($companyName,['cootel','seatel'])) return $pinCode;
		$pinCode2 = self::$pinCode2Mapping[$companyName];
		if (!V::required($pinCode2)) return "";
		if ($companyName == 'dtac') $pinCode2 .= $sn;
		$pinCode2 .= $pinCode . "#";
		return $pinCode2;
	}
}