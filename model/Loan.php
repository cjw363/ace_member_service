<?php
use validation\Validator as V;

class Loan extends Base {
	const DB_RUN = "loan_run";

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function getMemberTotalLoan() {
		$aceLoan = $this->getTotalAceLoan();
		$salaryLoan = $this->getTotalSalaryLoan();
		$partnerLoan = $this->getTotalPartnerLoan();
		return $salaryLoan + $aceLoan + $partnerLoan;
	}

	public function getTotalAceLoan() {
		$sql = 'select sum(loan) from tbl_member_loan_ace';
		return $this->getOne($sql);
	}

	public function getTotalSalaryLoan() {
		$sql = 'select sum(loan) from tbl_member_loan_salary';
		return $this->getOne($sql);
	}

	public function getTotalPartnerLoan() {
		$sql = 'select sum(loan) from tbl_member_loan_partner';
		return $this->getOne($sql);
	}

}