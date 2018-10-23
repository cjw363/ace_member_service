<?php

class Utils {

	public static function getBarCode($defaultLen = 23, $seed = '0123456789ABCDEF') {
		$code = "";
		$len = strlen($seed) - 1;
		for ($i = 0; $i < $defaultLen; $i++) {
			$code .= substr($seed, mt_rand(0, $len), 1);
		}
		return $code;
	}

	/*
			获取数据库日期
			$offset为数字时，指偏移天数
			$offset字符串时，可接收类似：+ INTERVAL 2 WEEK 的字符串
	*/
	public static function getDBDate($offset = 0, $dbObj = null) {
		if (!$dbObj) {
			$dbObj = getDB(Constant::MAIN_DB_RUN);
		}
		$sql = "select CURDATE()";
		if (is_numeric($offset)) {
			if ($offset > 0) {
				$sql .= ' + INTERVAL ' . $offset . ' DAY';
			} else if ($offset < 0) {
				$sql .= ' - INTERVAL ' . -$offset . ' DAY';
			}
		} else {
			$sql .= ' ' . $offset;
		}
		return $dbObj->getOne($sql);
	}


	/**
	 * @param $date: 接收date格式的字符串
	 * @return string
	 */
	public static function maxTime($date) {
		return $date . ' 23:59:59';
	}

	/** 将指定时间转换为时间戳形式，如过未传入参数则获取的是应用服务器的时间
	 * @param null $t
	 * @return false|int
	 */
	public static function getTimestamp($t = null) {
		if (!$t) $t = date("Y-m-d H:i:s");
		return strtotime($t);
	}

	public static function writeDataIntoMemory($key, $data) {
		$key = $key . ""; //转换为字符串格式
		$cache = \cache\Factory::getCache();
		$cache->set($key, $data);
	}

	public static function getDataFromMemory($key) {
		$key = $key . ""; //转换为字符串格式
		$cache = \cache\Factory::getCache();
		return $cache->getArray($key);
	}

	public static function keepDBAlive() {
		$db = getDB(Constant::MAIN_DB_RUN);
		// master/slave的连接都keep alive
		$sql = "select 1";
		$db->execute($sql);
		$db->execute($sql, 2);
		print("\n" . date("Y-m-d H:i:s") . " Keep DB System Alive");
	}

	/** 获取数据库当前时间  */
	public static function getDBNow() {
		$db = getDB(Constant::MAIN_DB_RUN);
		$sql = "select now()";
		return $db->getOne($sql);
	}

	public static function getDBDay($dbObj = null) {
		if (!$dbObj) {
			$dbObj = getDB(Constant::MAIN_DB_RUN);
		}
		return $dbObj->getOne("select day(curdate())");
	}

	public static function getDBMonth($dbObj = null) {
		if (!$dbObj) {
			$dbObj = getDB(Constant::MAIN_DB_RUN);
		}
		return $dbObj->getOne("select month(curdate())");
	}
	public static function getDBYear($dbObj = null) {
		if (!$dbObj) {
			$dbObj = getDB(Constant::MAIN_DB_RUN);
		}
		return $dbObj->getOne("select year(curdate())");
	}

	/** 获取数据库当前时间的时间戳 */
	public static function getDBNowTimestamp() {
		return self::getTimestamp(self::getDBNow());
	}

	/** 获取随机4位数字 */
	public static function getRandomCode() {
		$code = mt_rand(1000, 9999);
		return $code;
	}

	/**递归地将字符串(数组)转为html实体
	 * @param $result
	 * @return array|int|string
	 */
	public static function html2Str($result){
		if(!is_numeric($result) && is_string($result)){
			$result = htmlspecialchars($result);
		}elseif(is_array($result)) {
			foreach($result as $key => $val){
				$result[$key] = self::html2Str($val);
			}
		}
		return $result;
	}

	public static function formatFromTo(&$from, &$to) {
		if ($from != '') $from = self::_formatDate($from);
		if ($to == '') $to = Utils::getDBDate();
		$to = self::_formatDate($to);
		if ($from > $to) {
			$date = $from;
			$from = $to;
			$to = $date;
		}
	}

	private function _formatDate($date) {
		$dt = new DateTime($date);
		return $dt->format('Y-m-d');
	}

	public static function trimParams($p){
		$np = array();
		foreach($p as $k=>$v){
			if(is_string($v)){
				$np[$k] = trim($v);
			}else{
				$np[$k] = $v;
			}
		}
		return $np;
	}

	public static function randomHex($size) {
		$str = '';
		for($i=0;$i<$size;$i++){
			$str.=dechex(mt_rand(0,15));
		}
		return $str;
	}

	public static function getLastMonth($year,$month) {
		if ($year<2015) throw new Exception('Utils::getLastMonth Parameter Error Year');
		if ($month<1 || $month>12) throw new Exception('Utils::getLastMonth Parameter Error Month');

		$month--;
		if ($month==0) {
			$month=12; $year--;
		}
		return array('year'=>$year, 'month'=>$month);
	}

	public static function getNextMonthFirstDay($year,$month) {
		if ($year<2015) throw new Exception('Utils::getLastMonth Parameter Error Year');
		if ($month<1 || $month>12) throw new Exception('Utils::getLastMonth Parameter Error Month');

		$month++;
		if ($month==13) {
			$month=1; $year++;
		}
		return date("Y-m-d",mktime(0,0,0,$month,1,$year));
	}

	public static function getRandomString($length){
		$strCode = '';
		for($i=0; $i<$length; $i++){
			$strCode .= dechex(mt_rand(0,15));
		}
		return $strCode;
	}

	public static function bytesToStr($bytes) {
		$str = '';
		foreach($bytes as $ch) {
			$str .= chr($ch);
		}
		return $str;
	}

	public static function formatPhone2($phone) {
		$phoneArr = explode('-',$phone);
		if(!count($phoneArr)>1)return $phone;
		$phone = preg_replace('/^0+/', '', $phoneArr[1]);
		if ($phone) {
			$phone = $phoneArr[0] . '-' . $phone;
		}
		return $phone;
	}

	public static function formatPhones($phone){
		$p=preg_split("/[,;\/]/",$phone);
		return $p;
	}

	public static function formatDoublePhone($phone){
		if($phone=='') return '';
		$str="";
		$p=preg_split("/[,;\/]/",$phone);
		foreach($p as $v){
			if($v){
				$p1=self::formatPhone($v);
				if(!empty($p1)) $str.=$p1." , ";
			}
		}
		return substr($str,0,strlen($str)-3);
	}

	public  static  function formatPhone($phone){
		$len=strlen($phone);
		if($len==0) return '';
		$newPhone='';
		if($len<10){
			if($len<6){
				$newPhone = substr($phone,0,3)." ".substr($phone,3);
			}else{
				$newPhone = substr($phone,0,3)." ".substr($phone,3,3)." ".substr($phone,6);
			}
		}else if($len>=10){
			$newPhone = substr($phone,0,3)." ".substr($phone,3,4)." ".substr($phone,7);
		}
		return $newPhone;
	}

	public static function formatPhone3($countryCode, $phone) {
		$phone = preg_replace('/^0+/', '', $phone);
		if ($phone) {
			$phone = '+' . $countryCode . '-' . $phone;
		}
		return $phone;
	}

	public static function getToday(){
		$rt[]=date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d"),date("Y")));
		$rt[]=date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d"),date("Y")));
		return $rt;
	}

	public static function getThisWeek(){
		$rt[]=date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
		$rt[]=date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")));
		return $rt;
}

	public static function getThisMonth(){
		$rt[]=date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
		$rt[]=date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y")));
		return $rt;
	}

	//return 1:$time1>$time2, 0:$time1 = $time2 , -1: $time1 <$time2
	public static function timeCompare($time1,$time2){
		$diff = strtotime($time1) - strtotime($time2);
		if($diff>0) return 1;
		else if($diff<0) return -1;
		return 0;
	}

	/**
	 * 简化数据
	 * @param $list : array(array('type'=>1,'name'=>'t22','amount'=>67),array('type'=>1,'name'=>'t22','amount'=>67))
	 * @param $column
	 * @param array $keys
	 * @return mixed array('keys'=>array('type','name','amount'), 'list'=>array(array("1","t22","433"),array("2","t23","67")))
	 */
	public static function simplifyData($list,$column=null,$keys=array()){
		$rt = array('keys'=>array(),'list'=>array());
		if(empty($list)) return $rt;
		$pos = 0;
		if(empty($keys)){
			$r = $list[0];
			$keys = array();
			foreach($r as $k=>$a){
				$keys[] = $k;
			}
		}
		$rt['keys'] = $keys;

		if($column) $pos = array_search($column,$keys);
		if($pos<0) return false;

		$rows=array();
		foreach($list as $l){
			$row=array();
			foreach($l as $a){
				$row[] = $a;
			}
			if($column){
				print_r($row);
				$rows[$row[$pos]]=$row;
			}else{
				$rows[]=$row;
			}
		}
		$rt['list'] = $rows;
		return $rt;
	}

	/**
	 * 按指定的值的顺序重排数组，有未指定的则接在数组后面
	 * 如Currency想按['USD','KHR']顺序排序
	 * $arr array(0=>array('currency'=>'KHR','test'=>1),1=>array('currency'=>'USD','test'=>2),2=>array('currency'=>'VND','test'=>3))
	 * $aKey currency
	 * $keys ['USD','KHR']
	 * 排序后变为：array(0=>array('currency'=>'USD','test'=>2),1=>array('currency'=>'KHR','test'=>1),2=>array('currency'=>'VND','test'=>3))
	 */
	public static function sortBySpecifiedValues($arr, $aKey, $keys) {
		$newArr = [];
		if (!is_array($keys)) return $arr;
		foreach ($keys as $k) {
			foreach ($arr as $k2 => $a) {
				if ($a[$aKey] == $k) {
					$newArr[] = $a;
					array_splice($arr, $k2, 1);
				}
			}
		}
		return array_merge($newArr, $arr);
	}

	/**
	 * $arr array(0=>array('currency_source'=>'KHR','currency_target'=>'VND','test'=>1),1=>array(currency_source'=>'USD','currency_target'=>'KHR','test'=>2),2=>array(currency_source'=>'USD','currency_target'=>'VND','test'=>3))
	 * $aKey ['currency_source','currency_target']
	 * $keys ['USD','KHR']
	 * 排序后变为：array(0=>array(urrency_source'=>'USD','currency_target'=>'KHR','test'=>2),1=>array(currency_source'=>'USD','currency_target'=>'VND','test'=>3),2=>array('currency_source'=>'KHR','currency_target'=>'VND','test'=>1))
	 */
	public static function sortBySpecifiedValues2($arr, $aKey, $keys) {
		$newArr = [];
		if (!is_array($keys)||count($aKey)<2) return $arr;
		foreach ($keys as $k) {
			$arrTemp = [];
			$temp = 0;
			foreach ($arr as $k2 => $a) {
				if ($a[$aKey[0]] == $k) {
					$arrTemp[] = $a;
					array_splice($arr, $k2-$temp, 1);
					$temp++;
				}
			}
			$arrTemp = self::sortBySpecifiedValues($arrTemp,$aKey[1],$keys);
			$newArr = array_merge($newArr,$arrTemp);
		}
		return array_merge($newArr,$arr);
	}

	//计算两个时间相差
	public static function timeDiff($beginTime, $endTime) {
		return abs(strtotime($endTime) - strtotime($beginTime));
	}

	/**
	 * 简化数据
	 * @param $list array(array('key'=>'USD';,'value'=>'100.00'),array('key'=>'KHE','value'=>67.00))
	 * @return array array(array('USD' => 100.00), array('KHE' => 67.00))
	 */
	public static function simplifyKeyValue($list){
		$newArr = array();
		$key = array_keys($list[0])[0];
		$value = array_keys($list[0])[1];
		foreach ($list as $l){
			$newKey = $l[$key];
			$newValue = $l[$value];
			$newArr[$newKey] = $newValue;
		}
		return $newArr;
	}


	/**
	 * 简化货币数据, VND 和 KHR 类型的货币不保留小数位
	 */
	public static function simplifyAmount($currency, $amount){
		if ($currency == Currency::CURRENCY_VND || $currency == Currency::CURRENCY_KHR){
			return round($amount,0);
		}
		return round($amount, 2);
	}

	/**
	 * 求两个日期之间相差的天数
	 * (针对1970年1月1日之后，求之前可以采用泰勒公式)
	 * @param string $day1
	 * @param string $day2
	 * @return number
	 */
	public static function diffBetweenTwoDays($day1, $day2) {
		$second1 = strtotime($day1);
		$second2 = strtotime($day2);
		if ($second1 < $second2) {
			$tmp = $second2;
			$second2 = $second1;
			$second1 = $tmp;
		}
		return ($second1 - $second2) / 86400;
	}


	static function fillPreZero($num, $size) {
		$str = "" . $num;
		while (strlen($str) < $size)
			$str = "0" . $str;
		return $str;
	}

}
