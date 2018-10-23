<?php

class PlatformTicket extends \Base {

	const STATUS_1_LOCK_CREDIT = 1;
	const STATUS_2_BUY_TICKET = 2;
	const STATUS_4_SYSTEM_UNLOCK_CREDIT = 4;

	public function __construct() {
		parent::__construct(System::DB_RUN);
	}

	public function generate($partnerID, $marketID, $productID, $currency, $amount) {
		$sql = 'insert into tbl_platform_ticket_id (time, partner_id, market_id, product_id, currency, amount, status) value (now(),' . qstr($partnerID) . ',' . qstr($marketID) . ',' . qstr($productID) . ',' . qstr($currency) . ',' . qstr($amount) . ',' . self::STATUS_1_LOCK_CREDIT . ')';
		$this->execute($sql);
		return $this->insert_id();
	}

	public function get($id) {
		return $this->getLine('select id, time, partner_id, market_id, product_id, currency, amount, status from tbl_platform_ticket_id where id=' . qstr($id));
	}

	public function updateStatus($id, $status) {
		$this->execute('update tbl_platform_ticket_id set status=' . qstr($status) . ' where id=' . qstr($id));
	}

}