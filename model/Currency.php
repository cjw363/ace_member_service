<?php
use validation\Validator as V;
class Currency extends \Base {
	const CURRENCY_USD = 'USD';
	const CURRENCY_VND = 'VND';
	const CURRENCY_KHR = 'KHR';
	const CURRENCY_THB = 'THB';
	const CURRENCY_DEFAULT = 'USD';

	static $CURRENCY_ORDER = [self::CURRENCY_USD, self::CURRENCY_KHR, self::CURRENCY_VND, self::CURRENCY_THB];

	public function __construct() {
		parent::__construct(System::DB_RUN);
	}

	public static function check($currency) {
		$sql = 'select count(*) from tbl_currency where currency=' . qstr($currency);
		$db = getDB(System::DB_RUN);
		return $db->getOne($sql) > 0;
	}

	public function getCurrencyList($currency = "") {
		$set = "";
		if ($currency != "") $set = " and currency <>" . qstr($currency);
		$sql = "select currency ,exchange,exchange_fee from tbl_currency where 1 ".$set;
		$rs = Utils::sortBySpecifiedValues($this->getArray($sql),'currency',[self::CURRENCY_USD,self::CURRENCY_KHR,self::CURRENCY_VND,self::CURRENCY_THB]);
		return $rs;
	}

	public function getCurrency($currency){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$sql = "select currency ,exchange,exchange_fee from tbl_currency where currency=".qstr($currency);
		return $this->getLine($sql);
	}

	public function getExchangeRate() {
		return array("exchange_rate_show" => $this->_buildExchange2(), "exchange_rate" => $this->buildExchange());
	}

	public function buildExchange() {
		return Utils::getDataFromMemory('exchange_market');
	}

	public function _buildExchange2() {
		$sql = "select concat(currency_source," . qstr("/") . ",currency_target) exchange_currency,currency_source,currency_target,exchange_buy buy,exchange_sell sell from tbl_currency_market order by currency_source DESC";

		$rs = Utils::sortBySpecifiedValues2($this->getArray($sql),['currency_source','currency_target'],[self::CURRENCY_USD,self::CURRENCY_KHR,self::CURRENCY_VND,self::CURRENCY_THB]);
		return $rs;
	}

	public function getExchangeFee() {
		$rs =  $this->getArray('SELECT currency,exchange_fee FROM tbl_currency');
		$exchange = array();
		foreach ($rs as $cu) {
			$exchange[$cu["currency"]] = $cu["exchange_fee"];
		}
		return $exchange;
	}


	public function transferAmount($amount,$sourceCurrency,$targetCurrency){
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported Currency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported Currency ' . $targetCurrency, 2);
		if(!V::min_num($amount,0)) throw new TrantorException("Amount");
		if($amount==0) return 0;
		$exchange=$this->getExchangeRate1($sourceCurrency,$targetCurrency);
		return round($amount*$exchange,2);
	}

	public function transferFee($amount,$sourceCurrency,$targetCurrency){
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported Currency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported Currency ' . $targetCurrency, 2);
		if(!V::min_num($amount,0)) throw new TrantorException("Amount");
		if($amount==0) return 0;
		$exchange=$this->getExchangeFeeRate($sourceCurrency,$targetCurrency);
		return round($amount*$exchange,2);
	}

	public function _getMarketInfo($sourceCurrency,$targetCurrency){
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported Currency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported Currency ' . $targetCurrency, 2);
		$sql="select currency_source,currency_target,exchange_buy,exchange_sell,exchange_min,exchange_max from tbl_currency_market where (currency_source=".qstr($sourceCurrency)." and currency_target=".qstr($targetCurrency).") or (currency_source=".qstr($targetCurrency)." and currency_target=".qstr($sourceCurrency).")";
		return $this->getLine($sql);
	}

	public function getExchangeHistory($page) {
		$sql = "SELECT time,currency_source,currency_target,amount_source,amount_target FROM tbl_user_currency_exchange WHERE user_type = " . qstr(User::USER_TYPE_1_MEMBER) . " and user_id = " . qstr($_SESSION['UID']) . " ORDER BY time DESC";
		return $this->getPageArray($sql,$page);
	}

	public function getExchange2($sourceCurrency,$targetCurrency){
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported Currency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported Currency ' . $targetCurrency, 2);
		if($sourceCurrency==$targetCurrency) return 1;
		$rt=$this->_getMarketInfo($sourceCurrency,$targetCurrency);
		if(strtolower($rt['currency_source'])==strtolower($sourceCurrency)) return min(max($rt['exchange_buy'],$rt['exchange_min']),$rt['exchange_max']);
		return min(max($rt['exchange_sell'],$rt['exchange_min']),$rt['exchange_max']);
	}

	public function getExchangeRate1($sourceCurrency,$targetCurrency){
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported Currency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported Currency ' . $targetCurrency, 2);
		$exchange1=$this->getExchangeByCurrency($sourceCurrency);
		$exchange2=$this->getExchangeByCurrency($targetCurrency);
		return $exchange2/$exchange1;
	}

	public function getExchangeByCurrency($currency){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$sql = "select exchange from tbl_currency where currency=" . qstr($currency);
		return $this->getOne($sql);
	}

	public function getExchangeFeeRate($sourceCurrency,$targetCurrency){
		if (!Currency::check($sourceCurrency)) throw new TrantorException('Unsupported Currency ' . $sourceCurrency, 2);
		if (!Currency::check($targetCurrency)) throw new TrantorException('Unsupported Currency ' . $targetCurrency, 2);
		$exchange1=$this->getExchangeFeeByCurrency($sourceCurrency);
		$exchange2=$this->getExchangeFeeByCurrency($targetCurrency);
		return $exchange2/$exchange1;
	}

	public function getExchangeFeeByCurrency($currency){
		if (!Currency::check($currency)) throw new TrantorException('Unsupported Currency ' . $currency, 2);
		$sql = "select exchange_fee from tbl_currency where currency=" . qstr($currency);
		return $this->getOne($sql);
	}

}
