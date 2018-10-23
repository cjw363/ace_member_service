<?php

use validation\Validator as V;

class SMSNotification extends \Base {

	const PHONE_TYPE_0_NOT_REGISTER = 0;
	const PHONE_TYPE_1_MEMBER = 1;
	const PHONE_TYPE_2_AGENT = 2;
	const PHONE_TYPE_3_PARTNER = 3;
	const PHONE_TYPE_4_BRANCH = 4;

	const STATUS_1_PENDING = 1;

	//验证码过期时间300s
	const VERIFY_CODE_EXPIRED = 300;
	//重发验证码时间120s
	const VERIFY_CODE_SEND_INTERVAL = 120;

	const SMS_CONFIG_CODE_9001_IP_LIMIT_PER_DAY = 9001;
	const SMS_CONFIG_CODE_9002_PHONE_LIMIT_PER_DAY = 9002;
	const SMS_CONFIG_CODE_9003_DEVICE_LIMIT_PER_DAY = 9003;

	const SMS_RESULT_1_SUCCESS = 1;
	const SMS_RESULT_2_FAILED_TIME_OUT = 2;//失败(超时)
	const SMS_RESULT_3_FAILED_NOT_MATCH = 3;//失败(不匹配)
	const SMS_RESULT_4_FAILED_NOT_EXIST = 4;//失败(不存在)
	const SMS_RESULT_5_FAILED_USED = 5;//失败(已使用)

	const SMS_TYPE_1_REGISTER = 1;
	const SMS_TYPE_2_PASSWORD = 2;
	const SMS_TYPE_3_CASH = 3;
	const SMS_TYPE_7_ONLINE_PAY = 7;
	const SMS_TYPE_8_OFFLINE_PAY = 8;

	const ACTION_TYPE_1_RESET_TRADING_PASSWORD = 1;//重置交易密码
	const ACTION_TYPE_2_FORGOT_PASSWORD = 2;//忘记登录密码
	const ACTION_TYPE_3_FORGOT_TRADING_PASSWORD = 3;//忘记交易密码
	const ACTION_TYPE_4_TO_SET_TRADING_PASSWORD = 4;//待设置交易密码
	const ACTION_TYPE_5_REGISTER = 5;//注册
	const ACTION_TYPE_6_TO_VERITY_CERTIFICATE = 6;//待身份验证

	const DB_RUN = "assist_run";

	public static $smsTypeArr=array(self::SMS_TYPE_1_REGISTER,self::SMS_TYPE_2_PASSWORD,self::SMS_TYPE_3_CASH,self::SMS_TYPE_7_ONLINE_PAY,self::SMS_TYPE_8_OFFLINE_PAY);

	public function __construct() {
		parent::__construct(self::DB_RUN);
	}

	public function addPhone($phone, $type, $msg, $code, $deviceID) {
		if (!V::max_length($phone, 20)) throw new TrantorException("Phone");
		if (!in_array($type, array(SMSNotification::SMS_TYPE_1_REGISTER, SMSNotification::SMS_TYPE_2_PASSWORD, SMSNotification::SMS_TYPE_3_CASH, SMSNotification::SMS_TYPE_7_ONLINE_PAY, SMSNotification::SMS_TYPE_8_OFFLINE_PAY, ))) throw new TrantorException('SMS Type');
		if (!V::verificationCode($code)) throw new TrantorException('Verification Code');
		$phone = Utils::formatPhone2($phone);
		$ip = getIP();
		$sql = 'INSERT INTO tbl_phone_verify(time,phone,type,verify_code,message,time_invalid,status,flag_used,ip,device_id) VALUES(now(),' . qstr($phone) . ',' . $type . ',' . qstr($code) . ',' . qstr($msg) . ',DATE_ADD(now(),INTERVAL +' . self::VERIFY_CODE_EXPIRED . ' SECOND),' . self::STATUS_1_PENDING . ',' . Constant::NO . ',' . qstr($ip) . ',' . qstr($deviceID) . ')';
		$this->execute($sql);
	}

	public function addPhone2($phone, $msg) {
		if (!V::max_length($phone, 20)) throw new TrantorException("Phone");
		$phone = Utils::formatPhone2($phone);
		$sql = 'INSERT INTO db_system_run.tbl_sms_notification(time,user_type,user_id,phone,message,status) VALUES(now(),'.User::USER_TYPE_1_MEMBER.','.qstr($_SESSION['UID']).',' . qstr($phone) . ',' . qstr($msg) .','. self::STATUS_1_PENDING . ')';
		$this->execute($sql);
	}

	//验证号码的最新一条验证码信息是否超过重新发送的时间
	private function _checkLastRecord($phone, $type, $deviceID, $actionType) {
		if (!V::max_length($phone, 20)) throw new TrantorException("Phone");
		if (!in_array($type, array(SMSNotification::SMS_TYPE_1_REGISTER, SMSNotification::SMS_TYPE_2_PASSWORD, SMSNotification::SMS_TYPE_3_CASH, SMSNotification::SMS_TYPE_7_ONLINE_PAY, SMSNotification::SMS_TYPE_8_OFFLINE_PAY ))) throw new TrantorException('SMS Type');
		if (!V::required($deviceID)) throw new TrantorException('Device ID');
		if (!in_array($actionType, array(SMSNotification::ACTION_TYPE_1_RESET_TRADING_PASSWORD, SMSNotification::ACTION_TYPE_2_FORGOT_PASSWORD, SMSNotification::ACTION_TYPE_3_FORGOT_TRADING_PASSWORD, SMSNotification::ACTION_TYPE_4_TO_SET_TRADING_PASSWORD, SMSNotification::ACTION_TYPE_5_REGISTER,SMSNotification::ACTION_TYPE_6_TO_VERITY_CERTIFICATE ))) throw new TrantorException('Action Type');
		$phone = Utils::formatPhone2($phone);
		$this->checkAction($phone, $deviceID);
		//获取验证码发送时间
		$sql = 'select max(time) from tbl_phone_verify where phone= ' . qstr($phone) . ' and type = ' . $type . ' and flag_used = ' . Constant::NO;
		$time = $this->getOne($sql);
		//获得时间差
		$timeDiff = Utils::timeDiff($time, Utils::getDBNow());
		if (self::VERIFY_CODE_SEND_INTERVAL <= $timeDiff) {
			//验证码超过重发时间
			return -1;
		} else {
			//验证码未超过重发时间,返回剩余时间
			return self::VERIFY_CODE_SEND_INTERVAL - $timeDiff;
		}
	}

	//检查手机号,IP,设备号,是否超过每日限制
	private function checkAction($phone, $deviceID) {
		if (!V::max_length($phone, 20)) throw new TrantorException("Phone");
		if (!V::required($deviceID)) throw new TrantorException('Device ID');
		$sql = 'SELECT status FROM db_system_run.tbl_member WHERE phone = ' . qstr($phone);
		if ($this->getOne($sql) == User::USER_STATUS_3_INACTIVE) {
			echo json_encode(array('err_code' => MessageCode::ERR_1000_INACTIVE_ACCOUNT));
			exit(0);
		}
		$phoneLimit = $this->getLimitAmount(self::SMS_CONFIG_CODE_9002_PHONE_LIMIT_PER_DAY);
		$sql = 'SELECT COUNT(tpv.id) FROM tbl_phone_verify tpv WHERE tpv.phone= ' . qstr($phone) . ' AND tpv.time>= ' . qstr(Utils::getToday()[0]);
		if ($this->getOne($sql) > $phoneLimit) {
			echo json_encode(array('err_code' => MessageCode::ERR_1711_SMS_OVER_DAILY_LIMIT));
			exit(0);
		}
		$ip = getIP();
		$IpLimit = $this->getLimitAmount(self::SMS_CONFIG_CODE_9001_IP_LIMIT_PER_DAY);
		$sql = 'SELECT COUNT(tpv.id) FROM tbl_phone_verify tpv WHERE tpv.ip= ' . qstr($ip) . ' AND tpv.time>= ' . qstr(Utils::getToday()[0]);
		if ($this->getOne($sql) > $IpLimit) {
			echo json_encode(array('err_code' => MessageCode::ERR_1711_SMS_OVER_DAILY_LIMIT));
			exit(0);
		}
		$deviceIDLimit = $this->getLimitAmount(self::SMS_CONFIG_CODE_9003_DEVICE_LIMIT_PER_DAY);
		$sql = 'SELECT COUNT(tpv.id) FROM tbl_phone_verify tpv WHERE tpv.device_id= ' . qstr($deviceID) . ' AND tpv.time>= ' . qstr(Utils::getToday()[0]);
		if ($this->getOne($sql) > $deviceIDLimit) {
			echo json_encode(array('err_code' => MessageCode::ERR_1711_SMS_OVER_DAILY_LIMIT));
			exit(0);
		}
	}

	private function getLimitAmount($code) {
		if ($code != self::SMS_CONFIG_CODE_9001_IP_LIMIT_PER_DAY && $code != self::SMS_CONFIG_CODE_9002_PHONE_LIMIT_PER_DAY && $code != self::SMS_CONFIG_CODE_9003_DEVICE_LIMIT_PER_DAY) {
			return 0;
		}
		$sql = 'SELECT amount FROM db_system_run.tbl_config_global WHERE code = ' . $code;
		return $this->getOne($sql);
	}

	public function getResendTime($phone, $type, $deviceID, $actionType) {
		$rs = $this->_checkLastRecord($phone, $type, $deviceID, $actionType);
		if ($rs < 0) {
			//生成四位验证码
			$code = Utils::getBarCode(4, "0123456789");
			//重发时间已过,重新发送验证短信
			if ($type == SMSNotification::SMS_TYPE_2_PASSWORD){
				if ($actionType == SMSNotification::ACTION_TYPE_2_FORGOT_PASSWORD || $actionType == SMSNotification::ACTION_TYPE_5_REGISTER){
					$msg = '[Asia Weiluy] ' . $code . ' Member Reset Password';
				}else{
					$msg = '[Asia Weiluy] ' . $code . ' Member Set Trading Password';
				}
			} else if ($type == SMSNotification::SMS_TYPE_1_REGISTER){
				$s = new System();
				if (!$s->isFunctionRunning(Constant::FUNCTION_100_REGISTER)) return array('err_code'=>MessageCode::ERR_505_FUNCTION_NOT_RUNNING);
				$msg = '[Asia Weiluy] ' . $code . ' Member Register';
			} else{
				$msg = '[Asia Weiluy] ' . 'Member Verification Code ' . $code;
			}
			$this->addPhone($phone, $type, $msg, $code, $deviceID);
			return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>array('time'=>SMSNotification::VERIFY_CODE_SEND_INTERVAL));
		} else {
			//重发时间未到,返回剩余时间
			return array('err_code'=>MessageCode::ERR_0_NO_ERROR,'result'=>array('time'=>$rs));
		}
	}

	//获取号码身份
	public function checkPhone($phone, $deviceID) {
		if (!V::max_length($phone, 20)) throw new TrantorException('Phone');
		if (!V::required($deviceID)) throw new TrantorException('Device ID');
		$phone = Utils::formatPhone2($phone);
		$this->checkAction($phone, $deviceID);
		$s = new System();
		$rs = array('phone_type' => self::PHONE_TYPE_0_NOT_REGISTER);
		if ($s->isDuplicated('tbl_agent', array('phone' => $phone))) {
			$rs['phone_type'] = self::PHONE_TYPE_2_AGENT;
		} else if ($s->isDuplicated('tbl_member', array('phone' => $phone))) {
			$rs['phone_type'] = self::PHONE_TYPE_1_MEMBER;
		} else if ($s->isDuplicated('tbl_partner_staff', array('phone' => $phone))) {
			$rs['phone_type'] = self::PHONE_TYPE_3_PARTNER;
		} else if ($s->isDuplicated('tbl_branch_phone', array('phone' => $phone))) {
			$rs['phone_type'] = self::PHONE_TYPE_4_BRANCH;
		}
		return $rs;
	}

	//检验验证码
	public function verifyCode($phone, $type, $code) {
		if (!V::max_length($phone, 20)) throw new TrantorException("Phone");
		if (!in_array($type, array(SMSNotification::SMS_TYPE_1_REGISTER, SMSNotification::SMS_TYPE_2_PASSWORD, SMSNotification::SMS_TYPE_3_CASH, SMSNotification::SMS_TYPE_7_ONLINE_PAY, SMSNotification::SMS_TYPE_8_OFFLINE_PAY))) throw new TrantorException('SMS Type');
		if (!V::required($code)) throw new TrantorException("Verify Code");
		$phone = Utils::formatPhone2($phone);
		$sql = 'SELECT id,time,phone,type,verify_code,time_invalid,flag_used,ip,device_id FROM tbl_phone_verify WHERE phone= ' . qstr($phone) . ' AND type = ' . $type . ' ORDER BY time DESC';
		$rt = $this->getLine($sql);

		$result = self::SMS_RESULT_1_SUCCESS;
		$isOK = true;
		if (empty($rt['id'])) {
			//找不到该电话
			$result = self::SMS_RESULT_4_FAILED_NOT_EXIST;
			$isOK = false;
		} else if (Utils::timeCompare($rt['time_invalid'], Utils::getDBNow()) == -1) {
			//验证码超时
			$result = self::SMS_RESULT_2_FAILED_TIME_OUT;
			$isOK = false;
		} else if ($rt['verify_code'] != $code) {
			//验证码不匹配
			$result = self::SMS_RESULT_3_FAILED_NOT_MATCH;
			$isOK = false;
		} else if ($rt['flag_used'] == Constant::YES) {
			//验证码已被使用
			$result = self::SMS_RESULT_5_FAILED_USED;
			$isOK = false;
		}
		//更新验证信息
		if ($isOK == true){
			$this->_updatePhoneVerifyFlagUsed($rt['id'], Constant::YES);
		}
		//记录验证信息
		$sql = 'INSERT INTO tbl_phone_verify_try(time,phone,type,verify_code,ip,device_id,result) VALUES(now(),' . qstr($phone) . ',' . qstr($rt['type']) . ',' . qstr($code) . ',' . qstr($rt['ip']) . ',' . qstr($rt['device_id']) . ',' . $result . ')';
		$this->execute($sql);
		return $isOK;
	}

	private function _updatePhoneVerifyFlagUsed($id, $flagUsed){
		$sql = 'UPDATE tbl_phone_verify SET flag_used = '.qstr($flagUsed) .' WHERE id = '.qstr($id);
		return $this->execute($sql);
	}
}