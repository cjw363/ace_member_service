<?php

function cmdSaveIDVerify($p){
	Privilege::chkAction();
	$action = "VERIFY_CERTIFICATE";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code'=>MessageCode::ERR_103_SUBMIT_REPEAT,'unique_token'=>SecureSubmit::genToken($action));
	}
	$v=new Verify();
	$rt=$v->saveIDVerify($p['certificate_type'],$p['certificate_number'],$p['filename0'],$p['filename1'],$p['sex'],$p['nationality'],$p['birthday']);
	return $rt;
}

function cmdCheckIDVerify(){
	Privilege::chkAction();
	$action = "VERIFY_CERTIFICATE";
	$v=new Verify();
	$rt['verify']=$v->checkIDVerify();
	$u=new User();
	$rt['profile']=$u->getProfile();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}