<?php
use validation\Validator as V;

function cmdGetTicketType($p) {
	Privilege::chkAction();
	$l = new Lotto90();
	$data = $l->getTicketByType($p['type'], $p['page']);
	return array('msg' => 'OK', 'data' => $data);
}

function cmdGetBetList($p) {
	Privilege::chkAction();
	$l = new Lotto90();
	$data = $l->getBetList($p['page']);
	$time=Utils::getDBNow();
	return array('msg' => 'OK', 'data' => $data,'time'=>$time);
}

function cmdGetTicketDetail($p) {
	Privilege::chkAction();
	$l = new Lotto90();
	$rt = array('msg' => 'OK');
	$rt['ticket_data'] = $l->getTicket($p['id']);
	$rt['ticket_detail_data'] = $l->getTicketDetailByTicketId($p['id']);
	return $rt;
}

function cmdGetProductInfo($p) {
	Privilege::chkAction();
	global $LottoBetAmount;
	$l = new Lotto90();
	$rt = array('msg' => 'OK');
	$product = $l->getProductInfo();
	if (!empty($product)) {
		$rt['product'] = $product;
	}
	$b = new Balance();
	$rt['balance'] = $b->getBalance();
	$rt['bet_amount'] = $LottoBetAmount[Currency::CURRENCY_USD];
	if ($p['type'] == Lotto90::TICKET_TYPE_12_MULTIPLE) {
		$rt['max_number'] = $l->getSettingByCode(212);
	} else if ($p['type'] == Lotto90::TICKET_TYPE_21_SPECIAL) {
		$rt['max_times'] = $l->getSettingByCode(221);
	}
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken(Lotto90::LOTTO90_ACTION_PLACE_ORDERS));
}

function cmdPlaceOrders($p) {
	Privilege::chkAction();
	if (!SecureSubmit::checkToken(Lotto90::LOTTO90_ACTION_PLACE_ORDERS, $p)) return array('error_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken(Lotto90::LOTTO90_ACTION_PLACE_ORDERS));

	$l = new Lotto90();
	$rt = $l->placeOrders($p['product_id'], $p['type'], $p['bet_amount'], $p['bet_list']);
	$rt['unique_token'] = SecureSubmit::genToken(Lotto90::LOTTO90_ACTION_PLACE_ORDERS);
	return $rt;
}

function cmdGetLottoResult($p) {
	Privilege::chkAction();
	$m = new Lotto90();
	$rt["msg"] = "OK";
	$rt["data"] = $m->getLottoResult($p['date'],$p['type']);
	return $rt;
}

function cmdGetWinReport($p) {
	Privilege::chkAction();
	$l = new Lotto90();
	$data = $l->getWinReport($p['page']);
	$time=Utils::getDBNow();
	return array('msg' => 'OK', 'data' => $data,'time'=>$time);
}
