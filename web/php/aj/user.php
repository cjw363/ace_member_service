<?php

use validation\Validator as V;

function getSID() {
	$user = new User();
	do {
		$sid = Utils::getBarCode(26, "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz");
	} while ($user->isSIDConflict($sid));
	return $sid;
}

function cmdLogin($p) {
	$s = new System();
	if(!$s->isFunctionRunning(Constant::FUNCTION_102_MEMBER_LOGIN_ANDROID))return array("err_code" => MessageCode::ERR_500_NOT_ALLOW_LOGIN);

	if (!V::max_length($p['phone'], 20) || !V::max_length($p['password'], 24)) return array("err_code" => MessageCode::ERR_1002_LOGIN_FAIL);
	$rt = array("err_code" => MessageCode::ERR_0_NO_ERROR);
	$phone = Utils::formatPhone2($p['phone']);
	$password = $p["password"];
	$gesture = $p['gesture'];
	$fingerprint = $p['use_fingerprint'];
	$ver = $p['version_name'];
	$deviceID = $p['device_id'];
	$isDevice = $p['is_device'];
	$vArr = getConf('supported_apk_versions');
	if (!in_array($ver, $vArr)) return array("err_code" => MessageCode::ERR_104_NOT_SUPPORTED_APK_VERSION);

	global $SID;
	$SID = getSID();
	session_id($SID);
	session_start();

	$u = new User();
	if (!$phone || (!$gesture && !$password && !$fingerprint)) {
		return array('err_code'=>MessageCode::ERR_1002_LOGIN_FAIL);
	} else {
		$errCode = $u->login($phone, $password, $gesture, $fingerprint, $ver, $deviceID, $isDevice);
		if ($errCode > 0) {
			return array('err_code'=>$errCode);
		}
	}
	$rt['result']=$u->getLoginData();
	return $rt;
}


function cmdUpdatePassword($p) {
	Privilege::chkAction(true);
	$rt = array('err_code' => MessageCode::ERR_1_ERROR);
	$pwdNew = $p['new_password'];
	$pwdOld = $p['old_password'];
	$u = new User();
	if (!$u->isValidPassword($pwdOld)) {
		return array('err_code'=>MessageCode::ERR_1006_PASSWORD_INVALID);
	} else if ($pwdNew == $pwdOld || $pwdNew == $_SESSION['PHONE']) {
		return array('err_code'=>MessageCode::ERR_1007_NEW_PASSWORD_INVALID);
	}

	if (!$pwdNew) return $rt;
	if (!V::max_length($pwdNew, 24)) return $rt;
	$chk = \validation\Validator::checkPassword($pwdNew);
	if ($chk == 1) {
		return array('err_code'=>MessageCode::ERR_1007_NEW_PASSWORD_INVALID);
	} else if ($chk == 2) {
		return array('err_code'=>MessageCode::ERR_1008_NEW_PASSWORD_TOO_SIMPLE);
	}
	if ($u->updatePassword($pwdNew)) {
		$rt['err_code'] = MessageCode::ERR_0_NO_ERROR;
	}
	return $rt;
}

function cmdResetPassword($p) {
	Privilege::chkActionWithoutSession(true);
	$u = new User();
	$code = $u->checkPassword($p['password']);
	if ($code != 0) {
		return array('err_code' => $code);
	}
	global $SID;
	$SID = getSID();
	session_id($SID);
	session_start();
	$rt = array();
	if ($u->resetPassword($p['user_id'], $p['phone'], $p['password'], $p['device_id'], $p['version_name'])) {
		$u->clearGesture();
		$rt=$u->getLoginData();
		$code = MessageCode::ERR_0_NO_ERROR;
	} else {
		$code = MessageCode::ERR_1_ERROR;
	}
	return array('err_code'=>$code,'result'=>$rt);
}

function cmdConfirmPassword($p) {
	Privilege::chkAction();
	$pwd = $p['pwd'];
	$code=MessageCode::ERR_0_NO_ERROR;
	$u = new User();
	if (!$u->isValidPassword($pwd)) {
		$code= MessageCode::ERR_1006_PASSWORD_INVALID;
	}
	return array('err_code'=>$code);
}

function cmdLogout($p) {
	if (V::max_num($p['id'], 0)) {
		return array('err_code'=>MessageCode::ERR_101_INVALID);
	}
	$u = new User();
	$u->logout($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR);
}

function cmdClearDeviceID($p) {
	$m = new User();
	$m->clearDeviceID($p['phone']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR);
}

function cmdGetServicePoint() {
	$rt = array();
	$a = new Site();
	$rt['site_data'] = $a->getSite();
	$rt['agent_count'] = $a->getActiveAgentCount();
	$rt['branch_count'] = $a->getActiveBranchCount();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetDepositData($p) {
	Privilege::chkAction();
	$action="BALANCE_DEPOSIT";
	$rt = array();
	$l = new LocalBank();
	$rt['company_bank_list'] = $l->getBankAccountList($p['currency'], Constant::YES);
	$rt['member_bank_list'] = $l->getMemberBankAccountList($p['currency']);
	$t = new Transaction();
	$rt['day_amount'] = $t->getOneDayAmount(Constant::DEPOSIT, $p['currency']);
	$c=new Currency();
	$depositPendingAmount=$t->getDepositPendingAmount($_SESSION['UID']);
	$rt['pending_amount']=$c->transferAmount($depositPendingAmount,Currency::CURRENCY_USD,$p['currency']);
	$b = new Balance();
	$rt['total_balance']=$b->getTotalBalanceByCurrency($p['currency']);
	$s=new System();
	$rt['function_deposit'] = $s->isFunctionRunning(Constant::FUNCTION_111_MEMBER_DEPOSIT);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetDepositLimit($p){
	Privilege::chkAction();
	$t = new Transaction();
	$rt= $t->getDepositOrWithdrawLimitAmount(Constant::DEPOSIT,$p['currency']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetWithdrawData($p) {
	Privilege::chkAction();
	$action="BALANCE_WITHDRAW";
	$l = new LocalBank();
	$rt=array();
	$rt['company_bank_list'] = $l->getBankAccountList($p['currency']);
	$rt['member_bank_list'] = $l->getMemberBankAccountList($p['currency']);
	$rt['bank_config_list'] = $l->getBankConfigOfMemberBankAccount($p['currency']);
	$rt['bank_withdraw_amount_list']=$l->getBankOneDayAmountList(Constant::WITHDRAW,$p['currency']);
	$c = new Config();
	$rt['withdraw_config']=$c->getWithdrawConfig();
	$t = new Transaction();
	$rt['day_amount'] = $t->getOneDayAmount(Constant::WITHDRAW, $p['currency']);
	$c = new Currency();
	$rt['exchange_info'] = $c->getCurrency($p['currency']);
	$s=new System();
	$rt['function_withdraw'] = $s->isFunctionRunning(Constant::FUNCTION_112_MEMBER_WITHDRAW);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetWithdrawLimit($p){
	Privilege::chkAction();
	$t = new Transaction();
	$rt= $t->getDepositOrWithdrawLimitAmount(Constant::WITHDRAW,$p['currency']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdUpdateTradingPassword($p) {
	Privilege::chkAction(true);
	$u = new User();
	$rt = $u->updateTradingPassword($p['new_password'], $p['old_password'], $p['action_type']);
	return $rt;
}

function cmdRegister($p) {
	Privilege::chkActionWithoutSession(true);
	$u = new User();
	$code = $u->checkPassword($p['password']);
	if ($code != 0) {
		return array('err_code'=>$code);
	}
	global $SID;
	$SID = getSID();
	session_id($SID);
	session_start();
	return $u->register($p['user_name'], $p['country_code'], $p['phone'], $p['password'], $p['device_id'], $p['version_name']);
}

function cmdAddMemberBankAccount($p) {
	Privilege::chkAction(true);
	$lb = new LocalBank();
	$code = $lb->addMemberBankAccount($p['currency'], $p['bank_code'], $p['bank_account_no']);
	return array('err_code'=>$code);
}

function cmdDeleteMemberBankInfo($p) {
	Privilege::chkAction(true);
	$l = new LocalBank();
	$code = $l->deleteMemberBankAccount($p['bank_id']);
	return array('err_code'=>$code);
}

function cmdGetSmsConfig($p) {
	$a = new System();
	$countryCode = $a->getCountryCodeList();
	if ($p['action_type'] == SMSNotification::ACTION_TYPE_5_REGISTER){
		$s = new System();
		$registerIsRunning = $s->isFunctionRunning(Constant::FUNCTION_100_REGISTER);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array('country_code' => $countryCode, 'register_is_running' => $registerIsRunning));
	}
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array('country_code' => $countryCode));
}

function cmdCheckPhone($p) {
	Privilege::chkActionWithoutSession();
	$s = new SMSNotification();
	$rt = $s->checkPhone($p['phone'], $p['device_id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdSendVerificationCode($p) {
	Privilege::chkActionWithoutSession(true);
	$s = new SMSNotification();
	return $s->getResendTime($p['phone'], $p['type'], $p['device_id'],$p['action_type']);
}

function cmdVerifyCode($p) {
	Privilege::chkActionWithoutSession();
	$s = new SMSNotification();
	if (!$s->verifyCode($p['phone'], $p['type'], $p['code'])){
		return array('err_code'=>MessageCode::ERR_1_ERROR);
	}
	$u = new User();
	//验证成功,返回用户ID
	$userID = $u->getUserIDByPhone($p['phone']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>array('user_id'=>$userID));
}

function cmdGetPhoneCompanyList() {
	Privilege::chkAction();
	$pc = new PhoneCompany();
	$data = $pc->getActiveCompanyList();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$data);
}

function cmdGetFaceValueByCompanyID($p) {
	Privilege::chkAction();
	$rt = array('msg' => 'OK');
	$ph = new Phone();
	$rt['face_value_data'] = $ph->getFaceValueByCompanyID($p['company_id']);
	return $rt;
}

function cmdGetTopUpDataByCompanyID($p) {
	Privilege::chkAction();
	$action = "TOP_UP";
	$rt = array();
	$pc = new PhoneCompany();
	$rt['phone_company'] = $pc->getCompanyById($p['company_id']);
	$ph = new Phone();
	$rt['face_value_list'] = $ph->getFaceValueByCompanyID($p['company_id']);
	$s = new System();
	$rt['function_show_pincode'] = $s->isFunctionRunning(Constant::FUNCTION_121_MEMBER_TOP_UP_SHOW_PIN_CODE);
	$rt['function_send_sms'] = $s->isFunctionRunning(Constant::FUNCTION_122_MEMBER_TOP_UP_SEND_SMS);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetTopUpOrderList($p) {
	Privilege::chkAction();
	$ph = new Phone();
	$order = $ph->getTopUpOrderList($p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$order);
}

function cmdCheckTradingPasswordStatus() {
	Privilege::chkAction();
	$s = new User();
	$rt=$s->checkTradingPasswordStatus();
	return $rt;
}

function cmdCheckTradingPassword($p) {
	Privilege::chkAction();
	$s = new User();
	$rt= $s->checkTradingPassword(Rsa::privDecrypt($p['pwd']));
	return $rt;
}

function cmdTopUp($p) {
	Privilege::chkAction(true);
	$action = "TOP_UP";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code'=>MessageCode::ERR_103_SUBMIT_REPEAT,'unique_token'=>SecureSubmit::genToken($action));
	}
	$ph = new Phone();
	$rt = $ph->topUp($p['company_id'], $p['face_value'],$p['price'], $p['currency'],$p['type'],$p['phone']);
	$rt['unique_token']= SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetTopUpOrder($p) {
	Privilege::chkAction();
	$ph = new Phone();
	$order = $ph->getTopUpOrder($p['id']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$order);
}

function cmdCheckTransfer($p) {
	Privilege::chkAction();
	$s = new User();
	$rt= $s->checkTransfer();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetMemberTransferLimit($p){
	Privilege::chkAction();
	$t = new Transaction();
	$rt= $t->getMemberTransferLimitAmount($p['currency']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdCheckIsMember($p) {
	Privilege::chkAction();
	$s = new User();
	$rt = $s->checkIsMember($p['member_phone']);
	return $rt;
}

function cmdGetMemberByID($p) {
	Privilege::chkAction();
	$s = new User();
	$rt = $s->getMemberByID($p['member_id']);
	return $rt;
}

function cmdGetInfoForTransferToMember($p) {
	Privilege::chkAction();
	$b = new Balance();
	$rt['balance_list'] = $b->getBalance();
	$s = new System();
	$rt['function_member_to_member'] = $s->isFunctionRunning(Constant::FUNCTION_113_MEMBER_TO_MEMBER);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($p['token_action']));
}

function cmdGetPayData($p){
	Privilege::chkAction();
	$action='PAY';
	$u = new User();
	$rt = $u->getMemberByPhone($p['phone']);
	$s=new System();
	$rt['result']['function_running']=$s->isFunctionRunning(Constant::FUNCTION_133_MEMBER_PAY_ONLINE);
	$rt['unique_token']= SecureSubmit::genToken($action);
	return $rt;
}

function cmdUpdatePortrait($p){
	Privilege::chkAction();
	$action='PORTRAIT';
	$u = new User();
	$u->updatePortrait($p['image']);
	$rt['unique_token']= SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetPortrait(){
	Privilege::chkAction();
	$action='PORTRAIT';
	$u = new User();
	$rt['portrait']=$u->getPortrait();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetCouponList($p){
	Privilege::chkAction();
	$u=new User();
	$rt=$u->getCouponList($p['status'],$p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}
