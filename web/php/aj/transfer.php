<?php

function cmdGetReceiveMoneyResult(){
	Privilege::chkAction();
	$t = new Transfer();
	$rt = $t->getReceiveMoneyResult();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

function cmdGetDepositCashResult($p) {
	Privilege::chkAction();
	$t = new Transfer();
	$rt = $t->getDepositCashResult($p['time']);
	$rt['time'] = Utils::getDBNow();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

function cmdGetWithdrawCashResult($p) {
	Privilege::chkAction();
	$t = new Transfer();
	$rt = $t->getWithdrawCashResult();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

function cmdGetWithdrawCashResultByID($p) {
	Privilege::chkAction();
	$t = new Transfer();
	$rt = $t->getWithdrawCashResultByID($p['id']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

function cmdConfirmWithdrawCash($p) {
	Privilege::chkAction(true);
	$t = new Transfer();
	return $t->confirmWithdrawCash($p['id']);
}


function cmdGetToMemberConfig($p) {
	Privilege::chkAction();
	$rt = array();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt,'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdGetToNonMemberConfig($p){
	Privilege::chkAction();
	$rt = array();
	$s = new System();
	$rt['function_member_to_non_member'] = $s->isFunctionRunning(Constant::FUNCTION_114_MEMBER_TO_NON_MEMBER);
	$t = new Transfer();
	$rt['config_list'] = $t->getTransferCashConfigList();

	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt,'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdCheckToNonMember($p){
	Privilege::chkAction(true);
	$t = new Transfer;
	$rt=$t->checkToNonMember($p['currency'], $p['amount'], $p['fee']);
	if($rt == MessageCode::ERR_0_NO_ERROR) return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	else return $rt;
}

function cmdSaveToNonMember($p) {
	Privilege::chkAction(true);
	$action = "TRANSFER_TO_NONMEMBER";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$t = new Transfer();
	$rt = $t->transferToNonMember($p['target_country_code'], $p['target_phone'], $p['currency'], $p['amount'], $p['fee'], $p['remark']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdSaveToMember($p) {
	Privilege::chkAction(true);
	$action = "TRANSFER_TO_MEMBER";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$t = new Transfer();
	$rt = $t->transferToMember($p['target_country_code'], $p['target_phone'], $p['currency'], $p['amount'], $p['remark']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetTransferRecent() {
	Privilege::chkAction();
	$t = new Transfer();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $t->getTransferRecent());
}

function cmdGetMemberTransferHistoryList($p) {
	Privilege::chkAction();
	$t = new Transfer();
	$data = $t->getMemberTransferHistoryList($p['page']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
}

function cmdGetNonMemberTransferHistoryList($p) {
	Privilege::chkAction();
	$t = new Transfer();
	$data = $t->getNonMemberTransferHistoryList($p['page']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
}

function cmdGetTransferRecentDetail($p) {
	Privilege::chkAction();
	$t = new Transfer();
	$data = $t->getTransferRecentDetail($p['id'], $p['source']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
}


function cmdGetTransferCashRecord($p) {
	Privilege::chkAction();
	$b = new Transfer();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $b->getTransferCashRecord($p['page']));
}

function cmdGetTransferCashDetailByID($p) {
	Privilege::chkAction();
	$b = new Transfer();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $b->getTransferCashDetailByID($p['id']));
}

function cmdGetR2AConfig($p){
	Privilege::chkAction();
	$action = 'RECEIVE_TO_DEPOSIT';
	$s=new System();
	$rt['is_function']=$s->isFunctionRunning(Constant::FUNCTION_118_RECEIVE_TO_ACCOUNT);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdReceiveToAcct($p) {
	Privilege::chkAction(true);

	$action = 'RECEIVE_TO_DEPOSIT';
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$mt = new Transfer();
	$rt = $mt->receiveToAcct($p['security_code']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetWithdrawViaAgentDetail($p) {
	$t = new Transfer();
	$rt = $t->getWithdrawViaAgentInfo($p['application_id']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

function cmdGetCashWithdrawFee($p){
	Privilege::chkAction();
	$t = new Transaction();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR,'result'=>$t->getCashWithdrawFee($p['application_id']));
}

function cmdGetToPartnerConfig($p){
	Privilege::chkAction();
	$rt = array();
	$s = new System();
	$rt['function_member_to_partner'] = $s->isFunctionRunning(Constant::FUNCTION_115_MEMBER_TO_PARTNER);
	$c = new Config();
	$rt['withdraw_config']=$c->getWithdrawConfig();

	$b = new Currency();
	$rt['exchange_fee'] = $b->getExchangeFee();

	$t = new Transfer();
	$rt['partner_info'] = $t->getPartnerInfo();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt,'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdCheckToPartner($p){
	Privilege::chkAction(true);
	$t = new Transfer;
	$rt=$t->checkToPartner($p['currency'], $p['amount'], $p['fee']);
	if($rt == MessageCode::ERR_0_NO_ERROR) return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	else return $rt;
}

function cmdSaveToPartner($p){
	Privilege::chkAction(true);
	$action = "TRANSFER_TO_PARTNER";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$t = new Transfer();
	$rt = $t->transferToPartner($p['currency'], $p['amount'], $p['fee'], $p['remark']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetPartnerTransferHistoryList($p){
	Privilege::chkAction();
	$t = new Transfer();
	$data = $t->getPartnerTransferHistoryList($p['page']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
}

function cmdGetDepositViaAgentDetail($p){
	Privilege::chkAction();
	$ph = new Transfer();
	$data = $ph->getDepositViaAgentDetail($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$data);
}

function cmdGetToMerchantConfig($p){
	Privilege::chkAction();
	$rt = array();
	$s = new System();
	$rt['function_member_to_merchant'] = $s->isFunctionRunning(Constant::FUNCTION_116_MEMBER_TO_MERCHANT);
	$c = new Config();
	$rt['withdraw_config']=$c->getWithdrawConfig();

	$b = new Currency();
	$rt['exchange_fee'] = $b->getExchangeFee();

	$t = new Transfer();
	$rt['merchant_info'] = $t->getMerchantInfo();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt,'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdCheckToMerchant($p){
	Privilege::chkAction(true);
	$t = new Transfer;
	$rt=$t->checkToMerchant($p['currency'], $p['amount'], $p['fee']);
	if($rt == MessageCode::ERR_0_NO_ERROR) return array('err_code' => MessageCode::ERR_0_NO_ERROR);
	else return $rt;
}

function cmdSaveToMerchant($p){
	Privilege::chkAction(true);
	$action = "TRANSFER_TO_MERCHANT";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$t = new Transfer();
	$rt = $t->transferToMerchant($p['currency'], $p['amount'], $p['fee'], $p['remark']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetMerchantTransferHistoryList($p){
	Privilege::chkAction();
	$t = new Transfer();
	$data = $t->getMerchantTransferHistoryList($p['page']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $data);
}