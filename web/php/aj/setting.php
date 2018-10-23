<?php

function cmdSaveGesture($p){
	Privilege::chkAction(true);
	$rt = array('err_code'=>MessageCode::ERR_1_ERROR);
	$b = new User();
	if($b->saveGesture($p['gesture'], $p['action_type'])){
		$rt['err_code'] = MessageCode::ERR_0_NO_ERROR;
	}
	return $rt;
}

function cmdGetGestureConfig(){
	Privilege::chkAction();
	$b = new User();
	$flag = $b->getGestureConfig();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>array('status'=>$flag));
}

function cmdGetTradingPasswordConfig(){
	Privilege::chkAction();
	$b = new User();
	$rt = $b->getTradingPasswordConfig();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdAddFingerprintLog($p){
	Privilege::chkAction(true);
	$b = new User();
	$b->addFingerprintLog($p['flag']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR);
}

function cmdChangeTradingStatus(){
	Privilege::chkAction(true);
	$b = new User();
	$rt = $b->changeTradingStatus();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdAddForgotPasswordLog(){
	Privilege::chkAction(true);
	$b = new User();
	$b->addForgotPasswordLog();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR);
}