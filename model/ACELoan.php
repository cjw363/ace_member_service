<?php
use validation\Validator as V;

class ACELoan  extends Loan {

	const MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE=1;
	const MEMBER_LOAN_ACE_REPAY_STATUS_2_FINISHED=2;
	const MEMBER_LOAN_ACE_DETAIL_STATUS_1_RUNNING=1;
	const MEMBER_LOAN_ACE_DETAIL_STATUS_2_PAID_OFF=2;



	public function getAceLoanConfig(){
		$sql='select min_loan_credit_per_member as min_loan,max_loan_credit_per_member as max_loan,member_total_loan_limit total_loan_limit,loan_amount_unit,day_interest_rate from tbl_config_ace_lend';
		return $this->getLine($sql);
	}

	public function getInterestRate(){
		$sql="select day_interest_rate from tbl_config_ace_lend";
		return $this->getOne($sql);
	}

	public function getMemberLoanAce($memberID){
		if(!V::over_num($memberID,0)) throw new TrantorException("Member ID Error");
		$sql="select currency,credit,loan,repay_day from tbl_member_loan_ace where member_id=".qstr($memberID);
		return $this->getLine($sql);
	}

	public function getReturnLoan($from, $to) {
		$rt = $this->getLoanAceDetailList($_SESSION['UID'],$from,$to,self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE,'');
		$list['list_detail'] = $rt;
		$rt1 = $this->_getAceLoanRepayAmountByMemberID($_SESSION['UID'],$from,$to);
		$list['capital_amount'] = $rt1['capital_amount'];
		$list['plan_interest_amount'] = $rt1['plan_interest_amount'];
		return $list;
	}

	private function _getAceLoanRepayAmountByMemberID($memberID,$from,$to){
		if(!V::over_num($memberID,0)) throw new TrantorException("Member ID");
		$sql="select sum(capital_amount) capital_amount,sum(plan_interest_amount) plan_interest_amount from tbl_member_loan_ace_detail t left join tbl_member_loan_ace_repay t1 on(t.id=t1.loan_detail_id) where  t.member_id=".qstr($memberID)." and t1.status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE;
		if($from) $sql.=" and t.loan_date>=".qstr($from);
		if($to) $sql.=" and t.loan_date<=".qstr($to);
		return $this->getLine($sql);
	}

	public function getLoanAceDetailList($memberID,$from,$to,$status,$page){
		if(!V::over_num($memberID,0)) throw new TrantorException("Member ID Error");
		$sql="select t.id,t.time,t.currency,t.loan,t.loan_date,t.term,t.status, sum(t1.plan_interest_amount) interest_amount,sum(t1.actual_interest_amount) actual_interest_amount  from tbl_member_loan_ace_detail t  left join tbl_member_loan_ace_repay t1 on(t.id=t1.loan_detail_id) where t.member_id=".qstr($memberID);
		if($status) $sql.=" and t.status=".qstr($status);
		if($from) $sql.=" and t.loan_date>=".qstr($from);
		if($to) $sql.=" and t.loan_date<=".qstr($to);
		$sql.=" group by t.id order by t.time desc";
		if($page){
			return $this->getPageArray($sql,$page);
		}else{
			return $this->getArray($sql);
		}

	}

	public function getRecentLoanAce($memberID){
		if(!V::over_num($memberID,0)) throw new TrantorException("Member ID Error");
		$sql="select id,time,currency,loan,loan_date,term,status from tbl_member_loan_ace_detail where member_id=".qstr($memberID)." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE." order by time desc limit 5";
		return $this->getArray($sql);
	}

	public function getLoanDetailByID($id){
		if(!V::over_num($id,0)) throw new TrantorException("ID");
		$sql="select id,time,currency,loan,loan_date,term,status from tbl_member_loan_ace_detail where id=".qstr($id)." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE;
		return $this->getLine($sql);
	}

	public function getMemberLoanAceDetail($memberID){
		if(!V::over_num($memberID,0)) throw new TrantorException("Member ID Error");
		$sql="select time,currency,loan,loan_date,term from tbl_member_loan_ace_detail  where member_id=".qstr($memberID)." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE." order by time desc";
		return $this->getLine($sql);
	}

	public function checkAceLoan($amount, $term, $repayDay, $list) {
		if (!V::over_num($amount, 0)) throw new TrantorException("Amount Error");
		if (!V::over_num($term, 0) || $term > 6) throw new TrantorException("Repay Error");
		if (count($list) <= 0) throw new TrantorException("Repay List Error");
		if (!V::over_num($repayDay, 0) || $repayDay > 28) throw new TrantorException("Repay day Error");

		$t = new Transaction();
		$pendingAmount = $t->getDepositPendingAmount($_SESSION['UID']);
		$b = new Balance();
		$totalBalance = $b->getTotalBalanceUSD($_SESSION['UID']);
		$temp = $t->getDepositOrWithdrawLimitAmount(Constant::DEPOSIT, Currency::CURRENCY_USD);
		$maxBalance = $temp['max_balance'];
		$cf = $this->getAceLoanConfig();
		$c = new Config();
		$maxLoan = $c->getConfigLimitMaxLoan($_SESSION['LEVEL'], Currency::CURRENCY_USD);
		$memberLoan=$this->getMemberTotalLoan();
		$aceTotal=$this->getTotalAceLoan();
		$maxLoan=$maxLoan-$memberLoan;
		$totalAmount=$cf['total_loan_limit']-$aceTotal;
		$totalLimit=min($maxLoan,$totalAmount);

		if(($totalLimit<$amount))  return array('err_code'=>MessageCode::ERR_1827_OVER_MEMBER_MAX_LIMIT,"result"=>array('amount'=>$totalLimit));

		if ($amount % $cf['loan_amount_unit'] != 0||$amount%$term!=0) return array('err_code'=>MessageCode::ERR_1828_INPUT_AMOUNT_TIMES,'result'=>array('amount'=>$cf['loan_amount_unit']));

		if ($amount < $cf['min_loan'] || $amount > $cf['max_loan']) return array('err_code'=>MessageCode::ERR_1825_OVER_LOAN_CONFIG_LIMIT,'result'=>array('amount'=>$cf['min_loan'],'max_amount'=>$cf['max_loan']));


		$l = $this->getMemberLoanAce($_SESSION['UID']);

		if ($amount > $l['credit'] - $l['loan']) return array('err_code'=>MessageCode::ERR_1826_OVER_LOAN_LIMIT,'result'=>array('amount'=>$l['credit'] - $l['loan']));

		//check max balance
		if ($pendingAmount + $totalBalance + $amount > $maxBalance) return array('err_code'=>MessageCode::ERR_1824_OVER_MAX_BALANCE);

		//check list
		$dayAmount = round($amount / $term, 2);
		$year = Utils::getDBYear();
		$month = Utils::getDBMonth();
		$month1 = $month;
		$day = $l['repay_day'];
		$startDay=0;
		$day1 = Utils::getDBDay();
		if ($day && $day > 0 && $day < $day1) {
			$month1++;
		}
		if ($day==0) {
			if ($day1 > 28) {
				$day = 28;
			} else {
				$day = $day1;
			}
			$startDay=$day;
		}else{
			$startDay=$day1;
		}
		if ($repayDay != $day) throw new TrantorException("repay day Error");
		$time1 = $str = $year . '-' . sprintf("%02d", $month) . '-' . sprintf("%02d", $startDay);
		$arr = $this->_getRepayTimes($year, $month1, $day, $term);
		if (count($arr) != count($list)) throw new TrantorException("Repay List Error");
		if ($term == 1) {
			$time2 = $arr[0];
			$r = $list[0];
			$dayInterestAmount = $cf['day_interest_rate'] * $amount / 100;
			$number = Utils::diffBetweenTwoDays($time1, $time2);
			$amount1 = round($dayInterestAmount * $number, 2);
			if ($time2 != $r['time']) throw new TrantorException("Calculate time Error");
			if ($amount1 != $r['interest']) throw new TrantorException("Calculate Interest Amount Error");
			if ($dayAmount != $r['amount']) throw new TrantorException("Calculate Amount Error");
			if ($dayAmount + $amount1 != $r['total_amount']) throw new TrantorException("Calculate total Amount Error");
		} else {
			for ($i = 0; $i < $term; $i++) {
				$time2 = $arr[$i];
				$r = $list[$i];
				$dayInterestAmount = ($amount - $dayAmount * $i) * $cf['day_interest_rate'] / 100;
				$number = Utils::diffBetweenTwoDays($time1, $time2);
				$amount1 = round($dayInterestAmount * $number, 2);
				if ($time2 != $r['time']) throw new TrantorException("Calculate time Error");
				if ($amount1 != $r['interest']) throw new TrantorException("Calculate Interest Amount Error");
				if ($dayAmount != $r['amount']) throw new TrantorException("Calculate Amount Error");
				if ($dayAmount + $amount1 != $r['total_amount']) throw new TrantorException("Calculate total Amount Error");
				$time1 = $time2;
			}
		}
		return array('err_code'=>MessageCode::ERR_0_NO_ERROR);
	}

	public function getAceLoanData(){
		//credit,loan,rate
		$rt=$this->getMemberLoanAce($_SESSION['UID']);
		$rt['day_interest_rate']=$this->getInterestRate();
		return $rt;
	}


	public function saveAceLoan($amount, $term, $repayDay, $list) {
		if (!V::over_num($amount, 0)) throw new TrantorException("Amount Error");
		if (!V::over_num($term, 0) || $term > 6) throw new TrantorException("Repay Error");
		if (count($list) <= 0) throw new TrantorException("Repay List Error");
		if (!V::over_num($repayDay, 0) || $repayDay > 28) throw new TrantorException("Repay day Error");

		$rt=$this->checkAceLoan($amount, $term, $repayDay, $list);
		$code=$rt['err_code'];
		if($code>0) return $rt;

		$sql = "update tbl_member_loan_ace set loan=loan+" . qstr($amount) . ",repay_day=" . qstr($repayDay) . " where member_id=" . qstr($_SESSION['UID']);
		$this->execute($sql);
		$sql1 = "insert into tbl_member_loan_ace_detail(time,member_id,currency,loan,loan_date,term,status) values(now()," . qstr($_SESSION['UID']) . "," . qstr(Currency::CURRENCY_USD) . "," . qstr($amount) . ",curdate()" . "," . qstr($term) . "," . self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE . ")";
		$this->execute($sql1);
		$id = $this->insert_id();
		foreach ($list as $r) {
			$sql2 = "insert into tbl_member_loan_ace_repay(loan_detail_id,member_id,plan_date,currency,capital_amount,plan_interest_amount,status) values(" . qstr($id) . "," . qstr($_SESSION['UID']) . "," . qstr($r['time']) . "," . qstr(Currency::CURRENCY_USD) . "," . qstr($r['amount']) . "," . qstr($r['interest']) . "," . self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE . ")";
			$this->execute($sql2);
		}
		$vRemark='Loan from ACE #'.$id.", [Member] ".$_SESSION['PHONE'].', ' . Currency::CURRENCY_USD . ' ' . number_format($amount, 2);
		$ac=new Account();
		$voucherID=$ac->keepAccounts(Account::ID_12002_MEMBER_LOAN_FROM_ACE,Account::ID_20000_MEMBER_BALANCE,0,0,Currency::CURRENCY_USD,$amount,$vRemark);
		$t=new Transaction();
		$transactionID=$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW,Transaction::TRANSACTION_SUB_TYPE_232_ACE_LOAN,Currency::CURRENCY_USD,$amount,$id,0,$voucherID,$vRemark);
		$b = new Balance();
		$b->updateCurrentMemberBalance(Constant::DEPOSIT, Currency::CURRENCY_USD, $amount,$transactionID);
		$l = new Log();
		$l->addLog($vRemark);
		return array('err_code'=>MessageCode::ERR_0_NO_ERROR);
	}

	private function _getRepayTimes($year,$month,$day,$repay){
		$arr=array();
		if($repay==1){
			$m=$month+1;
			if($m>12){
				$year+=1;
				$month=1;
			}else{
				$month++;
			}
			$str=$year.'-'.sprintf ( "%02d",$month).'-'.sprintf ( "%02d",$day);
			$arr[0]=$str;
		}else{
			for($i=0;$i<$repay;$i++){
				$m=$month+1;
				if($m>12){
					$year+=1;
					$month=1;
				}else{
					$month++;
				}
				$str=$year.'-'.sprintf ( "%02d",$month).'-'.sprintf ( "%02d",$day);
				$arr[$i]=$str;
			}
		}
		return $arr;
	}

	public function getAceLoanRepay($id){
		if(!V::over_num($id,0)) throw new TrantorException('id');
		$sql="select plan_date,currency,capital_amount,plan_interest_amount,actual_date,actual_interest_amount,actual_penalty_amount,status from tbl_member_loan_ace_repay where loan_detail_id=".qstr($id)." and member_id=".qstr($_SESSION['UID'])." order by plan_date asc";
		return $this->getArray($sql);
	}

	public function getAceLoanRepayAmount($id){
		$sql="select sum(capital_amount) capital_amount,sum(plan_interest_amount) plan_interest_amount from tbl_member_loan_ace_repay where loan_detail_id=".qstr($id)." and member_id=".qstr($_SESSION['UID'])." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE;
		return $this->getLine($sql);
	}

	public function getRepaymentList($list){
		if(empty($list)) throw new TrantorException("list error");
		$list1=array();
		$today=Utils::getDBDate();
		$interestRate=$this->getInterestRate();
		foreach($list as $rt){
			$id=$rt['id'];
			$r=$this->getRepayment($id);
			$numberDay=abs(Utils::diffBetweenTwoDays($r['date'],$today));
			if($numberDay==0) $numberDay=1;
			$interest=round(($r['amount']*$numberDay*$interestRate)/100,2);
			$list1[]=array('amount'=>$r['amount'],'interest'=>$interest,'currency'=>$r['currency'],'loan_date'=>$this->getMinRepaymentDate($id));
		}
		return $list1;
	}

	public function getRepayment($id){
		if(!V::over_num($id,0)) throw new TrantorException("ID");
		$sql="select min(plan_date) date,currency,sum(capital_amount) amount from tbl_member_loan_ace_repay where loan_detail_id=".qstr($id)." and member_id=".qstr($_SESSION['UID'])." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE;
		$l=$this->getLine($sql);
		$today=Utils::getDBDate();
		if(strtotime($today)<strtotime($l['date'])){
			$l['date']=$this->getMinRepaymentDate($id);
		}
		return $l;
	}

	public function getMinRepaymentDate($id){
		if(!V::over_num($id,0)) throw new TrantorException("ID");
		$sql="select loan_date from tbl_member_loan_ace_detail where id=".qstr($id)." and member_id=".qstr($_SESSION['UID'])." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE;
		return $this->getOne($sql);
	}

	public function saveAceLoanPrepayment($list){
		if(empty($list)) throw new TrantorException("list error");
		$rt=$this->getRepaymentList($list);
		$totalAmount=0;
		foreach($rt as $r){
			$amount=$r['amount']+$r['interest'];
			$totalAmount+=$amount;
		}
		//check Balance
		$b=new Balance();
		$balance=$b->getBalanceByCurrency(Currency::CURRENCY_USD);
		if($totalAmount>$balance) return MessageCode::ERR_1709_NOT_ENOUGH_BALANCE;
		$today=Utils::getDBDate();
		$interestRate=$this->getInterestRate();
		$total=0;
		$strID='';
		foreach($list as $rt){
			$id=$rt['id'];
			$r=$this->getRepayment($id);
			$numberDay=abs(Utils::diffBetweenTwoDays($r['date'],$today));
			if($numberDay==0) $numberDay=1;
			$amount=$r['amount']+round($r['amount']*$numberDay*$interestRate/100,2);
			$this->updateLoanRepay($id,$numberDay,$interestRate);
			$this->updateLoanDetail($id);
			$this->updateLoan(-$r['amount']);
			$total+=$amount;
			$strID.=$id.",";
			$vRemark='[Member] Return Loan ACE #'.$id.', Amount ' . Currency::CURRENCY_USD . ' ' . number_format($amount, 2);
			$ac=new Account();
			$voucherID=$ac->keepAccounts(Account::ID_20000_MEMBER_BALANCE,Account::ID_12002_MEMBER_LOAN_FROM_ACE,0,0,Currency::CURRENCY_USD,$amount,$vRemark);
			$t=new Transaction();
			$t->add(Transaction::TRANSACTION_TYPE_2_DEPOSIT_WITHDRAW,Transaction::TRANSACTION_SUB_TYPE_233_ACE_RETURN_LOAN,Currency::CURRENCY_USD,-$amount,$id,0,$voucherID,$vRemark);
		}
		$b->updateCurrentMemberBalance(Constant::WITHDRAW, Currency::CURRENCY_USD, -$total);
		$l = new Log();
		$strID=substr($strID,0,count($strID)-1);
		$vRemark='[Member] Return Loan ACE #('.$strID.'), Amount ' . Currency::CURRENCY_USD . ' ' . number_format($total, 2);
		$l->addLog($vRemark);
		return MessageCode::ERR_0_NO_ERROR;

	}

	public function updateLoanRepay($id,$number,$interest){
		if(!V::over_num($id,0)) throw new TrantorException("ID");
		if(!V::min_num($number,0)) throw new TrantorException("number");
		if(!V::min_num($interest,0)) throw new TrantorException("interest");
		$sql="update tbl_member_loan_ace_repay set actual_date=curdate(),actual_interest_amount=capital_amount*$number*$interest/100,status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_2_FINISHED." where loan_detail_id=".qstr($id)." and member_id=".qstr($_SESSION['UID'])." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE;
		$this->execute($sql);
		return $this->affected_rows();
	}
	public function updateLoanDetail($id){
		$sql="update tbl_member_loan_ace_detail set status=".self::MEMBER_LOAN_ACE_DETAIL_STATUS_2_PAID_OFF." where id=".qstr($id)." and status=".self::MEMBER_LOAN_ACE_DETAIL_STATUS_1_RUNNING;
		$this->execute($sql);
	}

	public function updateLoan($amount){
		if(!V::numeric($amount)|| $amount == 0) throw new TrantorException("amount");
		$sql = "update tbl_member_loan_ace set loan=loan+" . qstr($amount) . " where member_id=" . qstr($_SESSION['UID']);
		$this->execute($sql);
	}

	public function  getRepayList(){
		$sql="select plan_date,currency,sum(capital_amount) capital_amount,sum(plan_interest_amount) plan_interest_amount,status from tbl_member_loan_ace_repay where member_id=".$_SESSION['UID']." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE." group by plan_date";
		return $this->getArray($sql);
	}

	public function  getNextMonthRepayment(){
		$sql="select plan_date,currency,sum(capital_amount) capital_amount,sum(plan_interest_amount) plan_interest_amount,status from tbl_member_loan_ace_repay where member_id=".$_SESSION['UID']." and status=".self::MEMBER_LOAN_ACE_REPAY_STATUS_1_UNDONE." group by plan_date";
		return $this->getLine($sql);
	}

}