<?php

function cmdGetLoanDate() {
	Privilege::chkAction();
	$mlp = new MemberLoanPartner();
	$rt['credit_loan']=$mlp->getSamrithisakCreditAndLoan();
	$rt['latest_bill']=$mlp->getLatestBill();
	$temp=$mlp->getSamrithisakServiceCharge();
	$rt['service_charge_rate']=$temp['service_charge_rate'];
	$rt['service_charge_min_amount']=$temp['service_charge_min_amount'];
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetPartnerCreditLoan(){
	Privilege::chkAction();
	$action="LOAN";
	$mlp = new MemberLoanPartner();
	$rt=$mlp->getSamrithisakCreditAndLoan();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetPartnerLoanConfig() {
	Privilege::chkAction();
	$action="LOAN";
	$mlp = new MemberLoanPartner();
	$rt=$mlp->getSamrithisakLoanConfig();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdCheckPartnerLoanConfig($p){
	Privilege::chkAction();
	$action="LOAN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$mlp = new MemberLoanPartner();
	$rt=$mlp->checkMemberLoanPartner($p['amount'],$p['service_charge']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdCheckPartnerReturnLoan($p){
	Privilege::chkAction();
	$action="LOAN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$mlp=new MemberLoanPartner();
	$t= $mlp->checkReturnLoan($p['amount']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdLoanPartner($p){
	Privilege::chkAction();
	$action="LOAN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$mlp=new MemberLoanPartner();
	$rt=$mlp->loanFromSamrithisak($p['amount'],$p['service_charge']);
	return array('err_code' =>$rt['code'], 'result'=>array('id'=>$rt['id']),'unique_token' => SecureSubmit::genToken($action));
}

function cmdReturnPartnerLoan($p){
	Privilege::chkAction();
	$action="LOAN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$mlp=new MemberLoanPartner();
	$rt=$mlp->ReturnSamrithisakLoan($p['amount']);
	return array('err_code' =>$rt['code'], 'result'=>array('id'=>$rt['id']),'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetPartnerLoanHistory($p) {
	Privilege::chkAction();
	$ph = new MemberLoanPartner();
	$rt = $ph->getSamrithisakLoanHistory($p['page']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}


function cmdGetAceLoan() {
	Privilege::chkAction();
	$al = new ACELoan();
	$rt = $al->getMemberLoanAce($_SESSION['UID']);
	$rate = $al->getInterestRate();
	if ($rate) $rt['day_interest_rate'] = $rate;
	$list['loan_ace'] = $rt;
	$LoanList = $al->getRecentLoanAce($_SESSION['UID']);
	$list['loan_ace_list'] = $LoanList;
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $list);
}

function cmdGetSalaryLoan(){
	Privilege::chkAction();
	$l = new SalaryLoan();
	$rt = array();
	$rt['loan_config'] = $l->getSalaryLoanConfig();
	$rt['credit_loan'] = $l->getSalaryCreditLoan($_SESSION['UID']);
	$rt['loan_flow'] = $l->getSalaryLoanRecentFlow($_SESSION['UID']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt);
}

function cmdGetAceLoanData() {
	Privilege::chkAction();
	$action="MEMBER_ACE_LOAN";
	$al=new ACELoan();
	$rt['loan_ace']=$al->getAceLoanData();
	$rt['repayment']=$al->getNextMonthRepayment();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdSaveAceLoan($p) {
	Privilege::chkAction(true);
	$action="MEMBER_ACE_LOAN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$al = new ACELoan();
	$list=json_decode(htmlspecialchars_decode($p['list']), true);
	$rt=$al->saveAceLoan($p['amount'],$p['term'],$p['repay_day'],$list);
	$rt['unique_token']=SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetAceLoanRepay($p){
	Privilege::chkAction();
	$al=new ACELoan();
	$list['list_repay']=$al->getAceLoanRepay($p['id']);
	$rt=$al->getAceLoanRepayAmount($p['id']);
	$list['capital_amount']=$rt['capital_amount'];
	$list['plan_interest_amount']=$rt['plan_interest_amount'];
	$list['interest_rate']=$al->getInterestRate();
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$list);
}

function cmdGetSalaryLoanConfig() {
	Privilege::chkAction();
	$action="SALARY_LOAN";
	$t = new Transaction();
	$rt['pending_amount']=$t->getDepositPendingAmount($_SESSION['UID']);
	$b = new Balance();
	$rt['total_balance']=$b->getTotalBalanceUSD($_SESSION['UID']);
	$temp=$t->getDepositOrWithdrawLimitAmount(Constant::DEPOSIT,Currency::CURRENCY_USD);
	$rt['max_balance']=$temp['max_balance'];
	$l = new SalaryLoan();
	$rt['loan_config'] = $l->getSalaryLoanConfig();
	$rt['credit_loan']=$l->getSalaryCreditLoan($_SESSION['UID']);
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdGetSalaryLoanDetail($p) {
	Privilege::chkAction();
	$l = new SalaryLoan();
	$rt = $l->getSalaryLoanFlowDetail($_SESSION['UID'], $p['flow_id']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

function cmdGetSalaryLoanHistory($p){
	Privilege::chkAction();
	$l = new SalaryLoan();
	$rt = $l->getSalaryLoanHistory($_SESSION['UID'], $p['page']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
}

function cmdCheckSalaryLoan($p){
	Privilege::chkAction();
	$action="SALARY_LOAN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$l=new SalaryLoan();
	$rt = $l->checkSalaryLoan($p['amount'],$p['charge']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdSalaryLoan($p){
	Privilege::chkAction();
	$action="SALARY_LOAN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$l=new SalaryLoan();
	$rt = $l->salaryLoan($p['amount'],$p['charge']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdGetSalaryLoanReturnConfig() {
	Privilege::chkAction();
	$action="SALARY_LOAN_RETURN";
	$b = new Balance();
	$rt['usd_balance']=$b->getBalanceByCurrency(Currency::CURRENCY_USD);
	$l = new SalaryLoan();
	$rt['current_loan']=$l->getSalaryCreditLoan($_SESSION['UID'])['loan'];
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdCheckSalaryLoanReturn($p){
	Privilege::chkAction();
	$action="SALARY_LOAN_RETURN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$l=new SalaryLoan();
	$rt = $l->checkSalaryLoanReturn($p['amount']);
	$rt['unique_token'] = SecureSubmit::genToken($action);
	return $rt;
}

function cmdSalaryLoanReturn($p){
	Privilege::chkAction();
	$action="SALARY_LOAN_RETURN";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$l=new SalaryLoan();
	return $l->salaryLoanReturn($p['amount']);
}

function cmdGetLoanAceDetailList($p) {
	Privilege::chkAction();
	$al = new ACELoan();
	$list = $al->getLoanAceDetailList($_SESSION['UID'],$p['from_time'],$p['to_time'],'',$p['page']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $list);
}

function cmdGetLoanAceTermDetail($p){
	Privilege::chkAction();
	$al = new ACELoan();
	$list = $al->getAceLoanRepay($p['id']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $list);
}

function cmdGetReturnLoanList($p){
	Privilege::chkAction();
	$al = new ACELoan();
	$list=$al->getReturnLoan($p['from_time'],$p['to_time']);
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $list);
}

function cmdGetAceLoanPrepayment($p){
	Privilege::chkAction();
	$action="MEMBER_ACE_LOAN_PREPAYMENT";
	$al = new ACELoan();
	$list = json_decode(htmlspecialchars_decode($p['list']), true);
	$rt['loan_list']=$al->getRepaymentList($list);
	$b=new Balance();
	$rt['balance_list']=$b->getBalance();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt,'unique_token' => SecureSubmit::genToken($action));
}

function cmdSaveAceLoanPrepayment($p){
	Privilege::chkAction(true);
	$action="MEMBER_ACE_LOAN_PREPAYMENT";
	if (!SecureSubmit::checkToken($action, $p)) {
		return array('err_code' => MessageCode::ERR_103_SUBMIT_REPEAT, 'unique_token' => SecureSubmit::genToken($action));
	}
	$al = new ACELoan();
	$list = json_decode(htmlspecialchars_decode($p['list']), true);
	$code=$al->saveAceLoanPrepayment($list);
	return array('err_code' => $code,'result'=>$al->getMemberLoanAce($_SESSION['UID']),'unique_token' => SecureSubmit::genToken($action));
}

function cmdCheckAceLoan($p){
	Privilege::chkAction();
	$al = new ACELoan();
	$list = json_decode(htmlspecialchars_decode($p['list']), true);
	$rt=$al->checkAceLoan($p['amount'],$p['term'],$p['repay_day'],$list);
	return $rt;
}

function cmdGetAceLoanPlayDetail($p){
	Privilege::chkAction();
	$al=new ACELoan();
	$list=$al->getLoanDetailByID($p['id']);
	$rt=$al->getAceLoanRepayAmount($p['id']);
	$list['interest_amount']=$rt['plan_interest_amount'];
	return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>$list);
}

function cmdGetAceLoanRepayList() {
	Privilege::chkAction();
	$al = new ACELoan();
	$list = $al->getRepayList();
	return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $list);
}