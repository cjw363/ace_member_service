<?php

function cmdSaveWaterSupply($p){
	Privilege::chkAction(true);
	$action = "SAVE_WATER_SUPPLY";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code'=>MessageCode::ERR_103_SUBMIT_REPEAT,'unique_token'=>SecureSubmit::genToken($action));
	}
	$pm = new Payment();
	$rt = $pm->saveBillWsa($p['type'], $p['bill_number'], $p['customer_phone'], $p['amount'], $p['fee'],$p['image'],$p['applicant_remark']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdSaveElectricity($p){
	Privilege::chkAction(true);
	$action = "SAVE_ELECTRICITY_SUPPLY";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code'=>MessageCode::ERR_103_SUBMIT_REPEAT,'unique_token'=>SecureSubmit::genToken($action));
	}
	$pm = new Payment();
	$rt = $pm->saveBillEdc($p['type'], $p['consumer_number'], $p['customer_phone'], $p['amount'], $p['fee'],$p['image'],$p['applicant_remark']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetConfigEdc() {
	Privilege::chkAction();
	$action="SAVE_ELECTRICITY_SUPPLY";
	$rt=array();
	$pm = new Payment();
	$rt['config'] = $pm->getConfigEdc();
	$rt['edc_bill']=$pm->getLatestEdcBill();
	$s=new System();
	$rt['function_edc']=$s->isFunctionRunning(Constant::FUNCTION_131_MEMBER_PAY_EDC_BILL);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetConfigWsa() {
	Privilege::chkAction();
	$action="SAVE_WATER_SUPPLY";
	$rt=array();
	$pm = new Payment();
	$rt['config'] = $pm->getConfigWsa();
	$rt['wsa_bill']=$pm->getLatestWsaBill();
	$s=new System();
	$rt['function_wsa']=$s->isFunctionRunning(Constant::FUNCTION_132_MEMBER_PAY_WSA_BILL);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetEdcRecent(){
	Privilege::chkAction();
	$pm = new Payment();
	$rt=$pm->getEdcRecent();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetWsaRecent(){
	Privilege::chkAction();
	$pm = new Payment();
	$rt=$pm->getWsaRecent();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetEdcBill($p){
	Privilege::chkAction();
	$pm = new Payment();
	$bill=$pm->getEdcBillByID($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$bill);
}

function cmdGetWsaBill($p){
	Privilege::chkAction();
	$pm = new Payment();
	$bill=$pm->getWsaBillByID($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$bill);
}

function cmdGetEdcBillList($p){
	Privilege::chkAction();
	$pm = new Payment();
	$rt=$pm->getEdcBillList($p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetWsaBillList($p){
	Privilege::chkAction();
	$pm = new Payment();
	$rt=$pm->getWsaBillList($p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetPaymentHistory($p){
	Privilege::chkAction();
	$pm = new Payment();
	$data = $pm->getPaymentHistory($p['from'],$p['to'],$p['type'],$p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$data);
}

function cmdGetBiller($p){
	Privilege::chkAction();
	$pm=new BillPayment();
	$list=$pm->getBiller($p['type']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$list);
}

function cmdGetBillerConfig($p){
	Privilege::chkAction();
	$pm=new BillPayment();
	$b=$pm->getBillerByID($p['biller_id']);
	$list['biller']=$b;
	$list['config']=$pm->getBillerConfig($b['partner_id'],$b['id']);
	$s=new System();
	$list['is_function']=$s->isFunctionRunning(Constant::FUNCTION_135_PAY_BILL_TO_PARTNER);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$list);
}

function cmdCheckBillPayment($p){
	Privilege::chkAction();
	$pm=new BillPayment();
	$code=$pm->checkBillPayment($p['partner_id'],$p['id'],$p['currency'],$p['amount'],$p['fee'],$p['type'],$p['bill_id']);
	$list=array();
	if($code>0){
		$b=$pm->getBillerByID($p['id']);
		$list['biller']=$b;
		$list['config']=$pm->getBillerConfig($b['partner_id'],$b['id']);
	}
	return array('err_code'=>$code,'result'=>$list);
}

function cmdSaveBillPayment($p){
	Privilege::chkAction(true);
	$action="BILL_PAYMENT";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code'=>MessageCode::ERR_103_SUBMIT_REPEAT,'unique_token'=>SecureSubmit::genToken($action));
	}
	$pm=new BillPayment();
	$code=$pm->saveBillPayment($p['partner_id'],$p['id'],$p['currency'],$p['amount'],$p['fee'],$p['type'],$p['bill_id'],$p['remark']);
	$list=array();
	if($code>0){
		$b=$pm->getBillerByID($p['id']);
		$list['biller']=$b;
		$list['config']=$pm->getBillerConfig($b['partner_id'],$b['id']);
	}
	return array('err_code'=>$code,'result'=>$list,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetBillPaymentHistoryList($p){
	Privilege::chkAction();
	$pm=new BillPayment();
	$list=$pm->getBillPaymentHistoryList($p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$list);
}

function cmdGetBillPaymentDetail($p){
	Privilege::chkAction();
	$pm=new BillPayment();
	$list=$pm->getBillPaymentDetail($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$list);
}
