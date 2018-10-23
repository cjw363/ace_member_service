<?php

//Lotto游戏基类
abstract class Lotto extends Base {

	const TYPE_SUB_1_A = 1;
	const TYPE_SUB_2_B = 2;
	const TYPE_SUB_3_C = 3;
	const TYPE_SUB_4_D = 4;
	const TYPE_SUB_9_ROLL = 9;

	const FLAG_MIX_1 = 1;
	const FLAG_MIX_2 = 2;

	const PRODUCT_STATUS_1_CREATE = 1;
	const TICKET_TYPE_12_MULTIPLE = 12;
	const TICKET_TYPE_11_SINGLE = 11;
	const TICKET_TYPE_21_SPECIAL = 21;
	const TICKET_TYPE_22_SPECIAL = 22;
	const CANCEL_TYPE_0 = 0; // 无CancelType
	const CANCEL_TYPE_4_TIMEOUT = 4; // 超时
	const STS_9_JUST_CREATE = 9; // 新建未确认, 9=>7, 9=>8(Sports ONLY)
	const STS_2_CANCELLED = 2; // 取消

	const STATUS_9_CREATING = 9;
	const STATUS_7_ACCEPTED = 7;
	const STATUS_1_SETTLED = 1;

	public function __construct($DB) {
		parent::__construct($DB);
	}

	public function getProductInfo() {
		$sql = 'select id, TIMESTAMPDIFF(SECOND,now(),close_time) diff_to_close from tbl_product where status = ' . self::PRODUCT_STATUS_1_CREATE . ' and date = curdate()';
		return $this->getLine($sql);
	}

	public function _getProductByID($ID) {
		$sql = 'select *, unix_timestamp()-unix_timestamp(close_time) as time_left from tbl_product where id =	' . qstr($ID);
		return $this->getLine($sql);
	}

	public function getBetList($page) {
		$sql = "select id,ticket_sn,ticket_time,type,turnover,currency,status from tbl_ticket where  member_id=" . qstr($_SESSION['UID']) . " and status=" . self::STATUS_7_ACCEPTED . " order by ticket_time DESC";
		$rt = $this->getPageArray($sql, $page);
		$data = Utils::simplifyData($rt['list']);
		return array('list' => $data['list'], 'keys' => $data['keys'], "total" => $rt['total'], "page" => $rt['page'], "size" => $rt['size']);
	}

	public function getTicket($id) {
		$sql = "select id,ticket_sn,ticket_time,type,sum_details,currency,turnover,status from tbl_ticket where id=" . qstr($id);
		return $this->getLine($sql);
	}

	public function getTicketDetailByTicketId($ticketID) {
		$sql = "select type,type_sub,number,times,flag_mix,times from tbl_ticket_detail where ticket_id=" . qstr($ticketID);
		$rt = $this->getArray($sql);
		$data = Utils::simplifyData($rt);
		return array('list' => $data['list'], 'keys' => $data['keys']);
	}

	public function getSettingByCode($code) {
		$sql = "select amount from tbl_setting where code=" . qstr($code);
		return $this->getOne($sql);
	}


	public function getLottoResult($date,$isFirstType = 0) {
		if (!$date){
			$date = "curdate()";
		}
		$result=$this->_getResult($date);
		if($isFirstType == 1 && empty($result)){
			$times=strtotime($date)-3600*24;
			$date=date('Y-m-d',$times);
			$result=$this->_getResult($date);
		}
		if(empty($result)){
			return array('date'=>$date,'result'=>'');
		}else{
			return array('date'=>$date,'result'=>$result);
		}
	}

	private function _getResult($date){
		$sql = "select result from tbl_product where date =".qstr($date);
		return $this->getOne($sql);
	}



	//子类重载
	abstract public function checkBeforeBet($type, $details, $betAmount, $totalAmount);

	abstract protected function checkNumber($type, $detail);

	abstract protected function updateVolume($type, $number, $mix, $typeSub, $currency, $times, $betAmount);

	protected  function splitDetailNumber($detailID, $productID, $type, $typeSub, $amount, $number, $mix) {}


	public function numberCombination($arr, $size = 1) {
		$len = count($arr);
		$max = pow(2,$len) - pow(2,$len-$size);
		$min = pow(2,$size)-1;
		$rtArr = array();
		for ($i=$min; $i <= $max; $i++) {
			$count = 0;
			$tempArr = array();
			for ($j = 0; $j < $len; $j++) {
				$a = pow(2, $j);
				$t = $i & $a;
				if($t == $a){
					$tempArr[] = $arr[$j];
					$count++;
				}
			}
			if($count == $size){
				$rtArr[] = $tempArr;
			}
		}
		return $rtArr;
	}

	//组合算法m中n个的组合个数
	public static function combination($m,$n){
		if($n==0||$m==0||$n==$m){
			return 1;
		}
		if($m<$n){
			return 0;
		}
		return self::combination($m - 1, $n) + self::combination($m - 1, $n - 1);
	}
	//组合算法m中n个的组合个数,count组合总数
//	public function oneNumberCount($count,$m,$n){
//
//	}

	/** 返回 Special 2D / 3D 的数字的所有组合的总数
	 * @param $number
	 * @param $type
	 * @param $flagMix
	 * @return int
	 */
	public function getSpecialTotalCount($number, $type, $flagMix) {
		return count(self::getSpecialTotal($number, $type, $flagMix));
	}

	/* 生成打字后的所有排列(不重复) */
	public function rankNumber($num, $gameType, $flagMix) {
		$num = "" . $num;
		if ($flagMix != \Constant::YES || ($gameType != 2 && $gameType != 3 && $gameType != 4)) {
			return array($num);
		}

		$numbers = array();
		if ($gameType == 2) {
			for ($i = 0; $i < 2; $i++) {
				$j = 1 - $i;
				$numbers[$num[$i] . $num[$j]] = 1;
			}
		}
		if ($gameType == 3) {
			for ($i = 0; $i < 3; $i++) {
				for ($j = 0; $j < 3; $j++) {
					if ($i != $j) {
						$k = 3 - $i - $j;
						$numbers[$num[$i] . $num[$j] . $num[$k]] = 1;
					}
				}
			}
		}
		if ($gameType == 4) {
			for ($i = 0; $i < 4; $i++) {
				for ($j = 0; $j < 4; $j++) {
					if ($i != $j) {
						for ($k = 0; $k < 4; $k++) {
							if ($i != $k && $j != $k) {
								$l = 6 - $i - $j - $k;
								$numbers[$num[$i] . $num[$j] . $num[$k] . $num[$l]] = 1;
							}
						}
					}
				}
			}
		}
		$rt = array();
		foreach ($numbers as $key => $value) {
			$rt[] = $key;
		}
		return $rt;
	}

	/**
	 * @param $number
	 * @param $type
	 * @return bool
	 */
	public function checkSpecialNumber($number, $type) {
		if ($type == self::TICKET_TYPE_21_SPECIAL) {
			$result = preg_match('/^(\*\d{1}|\d{1}\*|\d{2}|\d{2}-\d{2}|BIG|SMALL|ODD|EVEN|IN|OUT|SMALL_ODD|SMALL_EVEN|BIG_ODD|BIG_EVEN|ODD_ODD|EVEN_EVEN|ODD_EVEN|EVEN_ODD)$/', $number);
			if ($result) {
				if (!(strpos($number, "-") === false)) {
					$arr = explode('-', $number);
					if ($arr[0] > $arr[1]) {
						return false;
					}
				}
				return true;
			}
		}
		if ($type == self::TICKET_TYPE_22_SPECIAL) {
			$result = preg_match('/^(\d{3}|\*\d{2}|\d{1}\*\d{1}|\d{2}\*|\d{3}-\d{3})$/', $number);
			if ($result) {
				if (!(strpos($number, "-") === false)) {
					$arr = explode('-', $number);
					if ($arr[0] > $arr[1]) {
						return false;
					}
				}
				return true;
			}
		}
		return false;
	}

	/** 获取数字里 * 的 总数
	 * @param $number
	 * @return int
	 */
	public function getStartCount($number) {
		$len = strlen($number);
		$count = 0;
		for ($i = 0; $i < $len; $i++) {
			if ($number[$i] == '*') {
				$count++;
			}
		}
		return $count;
	}

	public function getSpecialTotal($number, $type, $flagMix) {
		$rtArr = array();
		$gameType = 0;
		if ($type == self::TICKET_TYPE_21_SPECIAL) {
			$gameType = 2;
		}
		if ($type == self::TICKET_TYPE_22_SPECIAL) {
			$gameType = 3;
		}
		$nums = self::splitNumber($number, $gameType);
		foreach ($nums as $n) {
			$rtArr = array_merge($rtArr, self::rankNumber($n, $gameType, $flagMix));
		}
		return $rtArr;
	}


	public static function getSpecialResultArr($a,$start){
		$l=[];
		foreach($a as $number){
			$l[]=substr($number,$start,strlen($number)-1);
		}
		return $l;
	}

	/** 将下注区的number进行分解/解析通配符，返回$gameType的所有合法数字串 */
	public function splitNumber($number, $gameType) {
		$result = array();
		$startH = 0;
		$startL = 0;
		// small/big/odd/even
		if ($gameType == 2) {
			$start = -1;
			$end = 0;
			$step = 0;

			if (in_array($number, array('BIG', 'SMALL', 'ODD', 'EVEN', 'IN', 'SMALL_ODD', 'SMALL_EVEN', 'BIG_ODD', 'BIG_EVEN'))) {
				if ($number == "SMALL") {
					$start = 0;
					$end = 50;
					$step = 1;
				} else if ($number == "BIG") {
					$start = 50;
					$end = 100;
					$step = 1;
				} else if ($number == "ODD") {
					$start = 1;
					$end = 100;
					$step = 2;
				} else if ($number == "EVEN") {
					$start = 0;
					$end = 100;
					$step = 2;
				} else if ($number == 'IN') {
					$start = 25;
					$end = 75;
					$step = 1;
				} else if ($number == 'SMALL_ODD') {
					$start = 1;
					$end = 50;
					$step = 2;
				} else if ($number == 'SMALL_EVEN') {
					$start = 0;
					$end = 50;
					$step = 2;
				} else if ($number == 'BIG_ODD') {
					$start = 51;
					$end = 100;
					$step = 2;
				} else if ($number == 'BIG_EVEN') {
					$start = 50;
					$end = 100;
					$step = 2;
				}
				if ($start != -1) {
					for ($i = $start; $i < $end; $i = $i + $step) {
						$result[] =Utils::fillPreZero($i, $gameType);
					}
				}
			} else if ($number == "OUT") {
				for ($i = 0; $i < 25; $i++) {
					$result[] = Utils::fillPreZero($i, $gameType);
				}
				for ($i = 75; $i < 100; $i++) {
					$result[] = Utils::fillPreZero($i, $gameType);
				}
			} else if (in_array($number, array('ODD_ODD', 'EVEN_EVEN', 'EVEN_ODD', 'ODD_EVEN'))) {
				if ($number == 'ODD_ODD') {
					$startH = 1;
					$startL = 1;
				} else if ($number == 'EVEN_EVEN') {
					$startH = 0;
					$startL = 0;
				} else if ($number == 'ODD_EVEN') {
					$startH = 1;
					$startL = 0;
				} else if ($number == 'EVEN_ODD') {
					$startH = 0;
					$startL = 1;
				}
				for ($i = $startH; $i < 10; $i += 2) {
					for ($j = $startL; $j < 10; $j += 2) {
						$result[] = Utils::fillPreZero($i . $j, $gameType);
					}
				}
			}
			if ($result)
				return $result;
		}
		// xx-yy, xxx-yyy
		if (strpos($number, "-") > 0) {
			if ($gameType * 2 + 1 == strlen($number) && strpos($number, "-") > 0) {
				$numArray = explode("-", $number);
				for ($i = $numArray[0]; $i <= $numArray[1]; $i++) {
					$result[] = Utils::fillPreZero($i, $gameType);
				}
			}
			return $result;
		}
		//*x,*xx,x*,x*x,xx*
		if (!(strpos($number, "*") === false)) {
			if ($gameType <= strlen($number)) {
				$number2 = substr($number, -$gameType);

				if (!(strpos($number2, "*") === false)) {
					for ($i = 0; $i <= 9; $i++) {
						$k = str_replace("*", $i, $number2);
						$result[] = Utils::fillPreZero($k, $gameType);
						;
					}
				} else {
					$result[] = $number2;
				}
			}
			return $result;
		}

		if (!preg_match('/^\d+$/', $number))
			return $result;
		if (strlen($number) < $gameType)
			return $result;
		return array(substr($number, -$gameType));
	}

}