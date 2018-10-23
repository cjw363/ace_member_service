<?php
namespace ip;
class IPCheck {

	public static function isVietnameseIP($ip){
		$rt = IP::find($ip);
		$rtStr = '';
		foreach($rt as $t){
			if(!empty($t)) $rtStr .= (empty($rtStr) ? '' : ','). $t;
		}
		$rtStr .= ' - ' . $ip;
		$flag = in_array('越南', $rt);
		if(!$flag) qlog($rtStr);
		return $flag;
	}

	public static function isBlockIP($ip){
		$rt = IP::find($ip);
		$rtStr = '';
		foreach($rt as $t){
			if(!empty($t)) $rtStr .= (empty($rtStr) ? '' : ','). $t;
		}
		$rtStr .= ' - ' . $ip;
		$flag = (in_array('中国', $rt) && !in_array('香港', $rt)) || in_array('台湾', $rt) || in_array('印度', $rt);
		if($flag || !IPCheck::isVietnameseIP($ip)) qlog($rtStr);
		return $flag;
	}

	public static function isValidateIP($areaCode, $ip){
		switch($areaCode){
			case 0 :
				return true;
			case 84 :
				return self::isVietnameseIP($ip);
		}
		return true;
	}
}