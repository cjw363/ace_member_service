<?php

use validation\Validator as V;

class Lotto90 extends Lotto {
	const DB_RUN = "lotto90_run";
	const LOTTO90_ACTION_PLACE_ORDERS = "PLACE_ORDERS";
	const LOTTO_90_700_MARKET = 700;

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function getTicketByType($type, $page) {
		$sql = "select id,ticket_sn,ticket_time,turnover,status from tbl_ticket where type=" . qstr($type) . " and member_id=" . qstr($_SESSION['UID']);
		$rt = $this->getPageArray($sql, $page);
		$data = Utils::simplifyData($rt['list']);
		return array('list' => $data['list'], 'keys' => $data['keys'], "total" => $rt['total'], "page" => $rt['page'], "size" => $rt['size']);
	}

	public function placeOrders($productID, $type, $totalAmount, $betList) {
		if (!V::over_num($totalAmount, 0)) throw new TrantorException("BetAmount");

		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_141_MEMBER_BUY_LOTTO)) return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);

		$b = new Balance();
		$balance = $b->getBalanceByCurrency(Currency::CURRENCY_USD);
		if ($totalAmount > $balance) return array("err_code" => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$l = new Lotto90();
		$betList = json_decode(htmlspecialchars_decode($betList), true);
		global $LottoBetAmount;
		$betAmount = $LottoBetAmount[Currency::CURRENCY_USD];
		$errCode = $l->checkBeforeBet($type, $betList, $betAmount, $totalAmount);
		if ($errCode > 0) return array("err_code" => $errCode);

		$cancelType = self::CANCEL_TYPE_0;
		$status = self::STATUS_7_ACCEPTED;
		$product = self::_getProductByID($productID);
		if ($product) {
			if ($product['status'] != self::PRODUCT_STATUS_1_CREATE) $cancelType = self::CANCEL_TYPE_4_TIMEOUT;
		} else {
			throw new TrantorException("Lotto90::_placeOrder Cannot find product(ID:$productID)");
		}
		if ($cancelType > 0) $status = self::STS_2_CANCELLED;

		$c = new Balance();

		if (!$c->lockBalance($totalAmount)) return array("err_code" => MessageCode::ERR_1709_NOT_ENOUGH_BALANCE);

		$dbNow = Utils::getDBNow();
		$formatDate = date('ymd', strtotime($dbNow));
		$p = new PlatformTicket();
		$platformTicketID = $p->generate(0, self::LOTTO_90_700_MARKET, $productID, Currency::CURRENCY_USD, $totalAmount);
		$ticketSN = $formatDate . \Utils::fillPreZero(0, 2) . \Utils::fillPreZero($platformTicketID, 8);
		$sql = "insert into tbl_ticket (id,ticket_sn,ticket_date,ticket_time,product_id,member_id,type,sum_details,currency,turnover,status,ip) values (".qstr($platformTicketID).",".qstr($ticketSN).",curdate()," . qstr($dbNow) . "," . qstr($productID) . "," . qstr($_SESSION['UID']) . "," . qstr($type) . "," . qstr(count($betList)) . "," . qstr(Currency::CURRENCY_USD) . "," . qstr($totalAmount) . "," . qstr($status) . "," . qstr(getIP()) . ")";
		$this->execute($sql);
		$p->updateStatus($platformTicketID, PlatformTicket::STATUS_2_BUY_TICKET);

		$accepted = 0;
		foreach ($betList as $d) {
			$number = $this->_formNumber($d['number']);
			$typeSub = $d['type_sub'];
			$times = $d['times'];
			$times2 = 1;
			if ($type == self::TICKET_TYPE_12_MULTIPLE) {
				$numberArr = explode(',', $d['number']);
				$numberCount = count($numberArr);
				$times2 = $this->combination($numberCount, 5);//count(self::numberCombination(explode(',', $d['number']), 5));
			}
			$sql1 = 'insert into tbl_ticket_detail (ticket_id, product_id, type, type_sub, number, currency, amount, times2, total_amount, times, turnover, status) values (' . qstr($platformTicketID) . ',' . qstr($productID) . ',' . qstr($type) . ',' . qstr($typeSub) . ',' . qstr($number) . ',' . qstr(Currency::CURRENCY_USD) . ',' . qstr($betAmount) . ',' . qstr($times2) . ',' . qstr($betAmount * $times2) . ',' . qstr($times) . ',' . qstr($betAmount * $times2 * $times) . ',' . self::STATUS_7_ACCEPTED . ')';
			$this->execute($sql1);
			$this->updateVolume($type, $number, 2, $typeSub, Currency::CURRENCY_USD, $times, $betAmount);
			$accepted += $betAmount * $times * $times2;
		}

		$c->unlockBalance($totalAmount, $accepted, self::LOTTO_90_700_MARKET);
		$b = new Balance();
		return array("result" => array('accepted' => $accepted, 'currency' => Currency::CURRENCY_USD, 'balance' => $b->getBalance()), 'err_code' => MessageCode::ERR_0_NO_ERROR);
	}

	//
	//	public function getTicketDetailByTicketId($ticketID) {
	//		$sql = "select type,type_sub,number,times,times from tbl_ticket_detail where ticket_id=" . qstr($ticketID);
	//		$rt = $this->getArray($sql);
	//		$data = Utils::simplifyData($rt);
	//		return array('list' => $data['list'], 'keys' => $data['keys']);
	//	}
	//
	protected function updateVolume($type, $number, $mix, $typeSub, $currency, $times, $betAmount) {
		if ($type == self::TICKET_TYPE_11_SINGLE) {
			$numberArr = explode(',', $number);
			$sqlSetArr = array();
			foreach ($numberArr as $na) {
				$sqlSetArr[] = '(curdate(),' . qstr($na) . ',1)';
			}
			$sql = 'insert into tbl_volume (date, number, times) values ' . implode(',', $sqlSetArr) . ' on duplicate key update times=times+values(times)';
			$this->execute($sql);
		}

		if ($type == self::TICKET_TYPE_12_MULTIPLE) {
			$numberArr = explode(',', $number);
			$numberCount = count($numberArr);
			$len = self::combination($numberCount, 5);
			$na = $len * 5 / $numberCount;
			$sqlSetArr = array();
			foreach ($numberArr as $n) {
				$sqlSetArr[] = '(curdate(),' . qstr($n) . ',' . $na . ')';
			}
			$sql = 'insert into tbl_volume (date, number, times) values ' . implode(',', $sqlSetArr) . ' on duplicate key update times=times+values(times)';
			$this->execute($sql);
		}

		if ($type == self::TICKET_TYPE_21_SPECIAL) {
			$sql = 'insert into tbl_volume_special (date, type, type_sub, number, currency, amount) values  (curdate(),' . qstr($type) . ',' . qstr($typeSub) . ',' . qstr($number) . ',' . qstr($currency) . ',' . qstr($betAmount * $times) . ') on duplicate key update amount=amount+values(amount)';
			$this->execute($sql);
		}
	}

	private function _formNumber($number) {
		$str = '';
		$numberArr = explode(',', $number);
		foreach ($numberArr as $n) {
			if ($n >= 10) {
				$str .= $n . ",";
			} else {
				$str .= "0" . $n . ",";
			}
		}
		return substr($str, 0, strlen($str) - 1);
	}

	//	public function getLotto90Result($date) {
	//		if (!$date)
	//			$date = "curdate()"; else $date = qstr($date);
	//		$sql = "select id,date,close_time,status,result,(close_time - now()) time_left,curdate() today from tbl_product where date =$date";
	//		return $this->getLine($sql);
	//	}

	//	public function getWinReport($page) {
	//		$sql = "select t.id,t.ticket_time time,t.ticket_sn,t.ticket_date,t1.id id2,t1.number,t2.result,t1.type,t1.currency,t1.turnover amount,t1.return_amount,t1.type_sub from tbl_ticket t left join tbl_ticket_detail t1 on(t1.ticket_id=t.id) LEFT JOIN tbl_product t2 ON(t2.id=t1.product_id) where t1.return_amount>0 and t.member_id=" . qstr($_SESSION['UID']) . " order by t.ticket_time desc";
	//		$rt = $this->getPageArray($sql, $page);
	//		$data = Utils::simplifyData($rt['list']);
	//		return array('list' => $data['list'], 'keys' => $data['keys'], "total" => $rt['total'], "page" => $rt['page'], "size" => $rt['size']);
	//	}

	//	public function getLottoResult($date) {
	//		if (!$date){
	//			$date = "curdate()";
	//		}
	//		$sql = "select id,date,close_time,status,result,(close_time - now()) time_left,curdate() today from tbl_product where date =".qstr($date);
	//		return $this->getLine($sql);
	//	}
	//
	public function checkBeforeBet($type, $details, $betAmount, $totalAmount) {
		if ($type != self::TICKET_TYPE_11_SINGLE && $type != self::TICKET_TYPE_12_MULTIPLE && $type != self::TICKET_TYPE_21_SPECIAL) {
			return MessageCode::ERR_1913_INVALID_TYPE;
		}

		$detailCount = count($details);
		if (!($detailCount > 0)) {
			return MessageCode::ERR_1912_DETAILS_MISMATCH;
		}
		if (($type == self::TICKET_TYPE_11_SINGLE && $detailCount > 10) || (($type == self::TICKET_TYPE_12_MULTIPLE || $type == self::TICKET_TYPE_21_SPECIAL) && $detailCount != 1)) {
			return MessageCode::ERR_1912_DETAILS_MISMATCH;
		}

		$maxTimes = $this->getSettingByCode(221);
		$detailAmount = 0;
		foreach ($details as $d) {
			if ($type == self::TICKET_TYPE_11_SINGLE || $type == self::TICKET_TYPE_12_MULTIPLE) {
				if (isset($d['type_sub']) && $d['type_sub'] != 0) {
					return MessageCode::ERR_1913_INVALID_TYPE;
				}
			}
			if (!V::min_num($d['times'], 1)) {
				return MessageCode::ERR_1914_WRONG_TIMES;
			}

			if ($type == self::TICKET_TYPE_21_SPECIAL) {
				if ($d['type_sub'] != 1 && $d['type_sub'] != 2 && $d['type_sub'] != 3 && $d['type_sub'] != 4 && $d['type_sub'] != 9) return MessageCode::ERR_1913_INVALID_TYPE;
				if ($d['times'] > $maxTimes) return MessageCode::ERR_1915_OVER_SPECIAL_MAX_TIMES;
			}

			$rtCode = $this->checkNumber($type, $d);
			if ($rtCode > 0) {
				return $rtCode;
			}

			if (($type == self::TICKET_TYPE_11_SINGLE || $type == self::TICKET_TYPE_12_MULTIPLE) && $d['times'] != 1) {
				return MessageCode::ERR_1916_BETTING_AMOUNT_MISMATCH;
			}
			if ($type == self::TICKET_TYPE_11_SINGLE || $type == self::TICKET_TYPE_21_SPECIAL) {
				$detailAmount += $betAmount * $d['times'];
			}
			if ($type == self::TICKET_TYPE_12_MULTIPLE) {
				$numberArr = explode(',', $d['number']);
				$n = self::combination(count($numberArr), 5);
				$detailAmount += $betAmount * $d['times'] * $n;
			}
		}
		if ($totalAmount != $detailAmount) {
			return MessageCode::ERR_1916_BETTING_AMOUNT_MISMATCH;
		}
		return 0;
	}

	protected function checkNumber($type, $detail) {
		$number = $detail['number'];
		$numberArr = explode(',', $number);
		$numberCount = count($numberArr);
		if (count($numberArr) != count(array_unique($numberArr))) {
			return MessageCode::ERR_1910_INVALID_NUMBER;
		}

		foreach ($numberArr as $n) {
			$len = strlen($n);
			if ($n < 1 || $n > 90 || $len < 1 || $len > 2) {
				return MessageCode::ERR_1917_WRONG_DETAIL_NUMBER;
			}
		}
		if ($type == self::TICKET_TYPE_11_SINGLE && $numberCount != 5) {
			return MessageCode::ERR_1917_WRONG_DETAIL_NUMBER;
		}

		$maxCount = $this->getSettingByCode(212);
		if ($type == self::TICKET_TYPE_12_MULTIPLE) {
			if (!($numberCount > 5 && $numberCount <= $maxCount)) {
				return MessageCode::ERR_1918_OVER_MULTIPLE_MAX_NUMBER;
			}
		}
		if ($type == self::TICKET_TYPE_21_SPECIAL) {
			if ($numberCount != 1) {
				return MessageCode::ERR_1917_WRONG_DETAIL_NUMBER;
			}
		}
		return 0;
	}

}