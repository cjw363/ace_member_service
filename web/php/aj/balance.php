<?php

function cmdDeposit($p) {
	Privilege::chkAction(true);
	$action = "BALANCE_DEPOSIT";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$s = new System();
	if (!$s->isFunctionRunning(Constant::FUNCTION_111_MEMBER_DEPOSIT)) {
		return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING, 'unique_token' => SecureSubmit::genToken($action));
	}
	$t = new Transaction();
	$rt=$t->deposit($p['currency'], $p['amount'], $p['member_bank'], $p['member_bank_account_no'], $p['company_bank_account_no'], $p['remark']);
	return array('err_code' => $rt['code'], 'result'=>array('id'=>$rt['id']),'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetBalance($p) {
	Privilege::chkAction();
	$b = new Balance();
	$balanceList = $b->getBalance();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$balanceList,'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdGetBalanceFlowList($p) {
	Privilege::chkAction();
	$b = new Balance();
	$rt = $b->getBalanceFlowList($p['currency'],$p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdGetBalanceAndStatus() {
	Privilege::chkAction();
	$b = new Balance();
	$rt = array();
	$rt['balance_list'] = $b->getBalance();
	$u = new User();
	$rt['phone_verified']=$u->checkUserExtendVerified($_SESSION['UID'], Constant::VERIFIED_TYPE_1_PHONE);
	$rt['id_verified']=$u->checkUserExtendVerified($_SESSION['UID'], Constant::VERIFIED_TYPE_3_ID);
	$rt['fingerprint_verified'] = $u->checkUserExtendVerified($_SESSION['UID'], Constant::VERIFIED_TYPE_4_FINGERPRINT);
	$rt['portrait']=$u->getPortrait();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetBalanceRecord($p) {
	Privilege::chkAction();
	$t = new Transaction();
	$rt = $t->getUserBankTransAppRecordByID($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetBalanceRecordList($p) {
	Privilege::chkAction();
	$rt=array();
	$b = new Balance();
	$rt['balance']=array('amount'=>$b->getBalanceByCurrency($p['currency']),'currency'=>$p['currency']);
	$t = new Transaction();
	$rt['record_list'] = $t->getUserBankTransAppRecordList($p['currency']);
	$s = new System();
	$rt['function_deposit'] = $s->isFunctionRunning(Constant::FUNCTION_111_MEMBER_DEPOSIT);
	$rt['function_withdraw'] = $s->isFunctionRunning(Constant::FUNCTION_112_MEMBER_WITHDRAW);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetExchangeInfo(){
	Privilege::chkAction();
	$action = "BALANCE_EXCHANGE";
	$b = new Balance();
	$rt=[];
	$rt['balance'] = $b->getBalance();
	$c = new Currency();
	$rt['currency'] = $c->getCurrencyList();
	$s = new System();
	$rt['function'] = $s->isFunctionRunning(Constant::FUNCTION_119_MEMBER_EXCHANGE);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetExchangeRate() {
	Privilege::chkAction();
	$rt['msg'] = "OK";
	$b = new Currency();
	$exchange = $b->getExchangeRate();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$exchange);
}

function cmdGetExchangeRecent($p) {
	Privilege::chkAction();
	$u = new Currency();
	$rt = $u->getExchangeHistory($p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdExchange($p) {
	Privilege::chkAction(true);
	$action = "BALANCE_EXCHANGE";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code'=>MessageCode::ERR_103_SUBMIT_REPEAT,'unique_token'=>SecureSubmit::genToken($action));
	}
	$t = new Transaction();
	$rt = $t->exchange($p['source_currency'],$p['amount'],$p['target_currency']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdCheckExchange($p) {
	Privilege::chkAction(true);
	$t = new Transaction();
	$code = $t->checkExchange($p['source_currency'],$p['amount'],$p['target_currency']);
	return array('err_code'=>$code);
}

function cmdWithdrawToLocalBank($p) {
	Privilege::chkAction(true);
	$action = "BALANCE_WITHDRAW";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$s = new System();
	if (!$s->isFunctionRunning(Constant::FUNCTION_112_MEMBER_WITHDRAW)) {
		return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING, 'unique_token' => SecureSubmit::genToken($action));
	}

	$t = new Transaction();
	$rt = $t->withdraw($p['currency'], $p['amount'], $p['bank'], $p['bank_account_no'], $p['remark']);
	return array('err_code' =>$rt['code'], 'result'=>array('id'=>$rt['id']),'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetStatement($p){
	Privilege::chkAction();
	$t = new Transaction();
	$rt = $t->getStatement($p['page'], $p['date_start'],$p['date_end'], $p['currency']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetTransaction($p){
	Privilege::chkAction();
	$a=new Transaction();
	$rt=$a->getTransaction($p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetBankWithdrawFee($p) {
	Privilege::chkAction();
	$rt = array('err_code' => MessageCode::ERR_0_NO_ERROR);
	$t = new Transaction();
	$rt['result'] = $t->getBankWithdrawFee($p['application_id']);
	return $rt;
}

function cmdGetWithdrawBankFee($p) {
	Privilege::chkAction();
	$rt = array('err_code' => MessageCode::ERR_0_NO_ERROR);
	$t = new Transaction();
	$rt['result'] = $t->getWithdrawBankFee($p['application_id']);
	return $rt;
}

function cmdGetBalanceAndOutstanding($p){
	$marketID=$p['market_id'];
	$b=new Balance();
	$rt['balance']=$b->getBalance();
	$rt['outstanding']=$b->getOutstanding($marketID,Currency::CURRENCY_USD);
	if(empty($rt['outstanding'])) $rt['outstanding']=0;
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetExchangeDetail($p){
	Privilege::chkAction();
	$u = new User();
	$rt =  $u->getExchangeDetail($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);

}