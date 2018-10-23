<?php

function cmdGetToken($p) {
	Privilege::chkAction();
	return array('err_code' =>MessageCode::ERR_0_NO_ERROR, 'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdGetServiceInfo($p) {
	//Privilege::chkAction(); //不检验，未登录
	$rt=array('version' => getConf('version'),'supported'=>in_array($p['version_name'],getConf('supported_apk_versions')),'flag_dev' => getConf("flag_dev"));
	return array('err_code' =>MessageCode::ERR_0_NO_ERROR, 'result' =>$rt);
}

function cmdGetLoginData() {
	$a = new System();
	$countryCode = $a->getCountryCodeList();
	$s = new System();
	$registerIsRunning = $s->isFunctionRunning(Constant::FUNCTION_100_REGISTER);
	$loginIsRunning = $s->isFunctionRunning(Constant::FUNCTION_102_MEMBER_LOGIN_ANDROID);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array('country_code' => $countryCode, 'register_is_running' => $registerIsRunning, 'login_is_running' => $loginIsRunning));
}

function cmdGetCountryCodeList() {
	$a = new System();
	$data = $a->getCountryCodeList();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR,'result'=>$data);
}

function cmdGetLogList($p){
	Privilege::chkAction();
	$b = new Log();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR,'result' =>$b->getLogList($p['page']));
}

function cmdGetTime(){
	Privilege::chkAction();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR,'result'=>array('time' => Utils::getDBNow()));
}

function cmdCheckIsRunningByCode($p){
	Privilege::chkActionWithoutSession();
	$s = new System();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR, 'result'=>array('is_running'=>$s->isFunctionRunning($p["code"])));
}