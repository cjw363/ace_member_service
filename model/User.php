<?php

use validation\Validator as V;

class User extends \Base {

	const USER_STATUS_1_ACTIVE = 1;
	const USER_STATUS_2_SUSPENDED = 2;
	const USER_STATUS_3_INACTIVE = 3;

	const USER_TYPE_1_MEMBER = 1;
	const USER_TYPE_2_AGENT = 2;
	const USER_TYPE_3_PARTNER = 3;
	const USER_TYPE_4_MERCHANT = 4;

	const USER_LEVEL_1 = 1;

	const EMAIL_1_VERIFIED = 1;
	const EMAIL_2_NOT_VERIFIED = 2;

	const DATE_OF_BIRTH_1_VERIFIED = 1;
	const DATE_OF_BIRTH_2_NOT_VERIFIED = 2;

	const CODE_2011_MAX_FAIL_TRADING_COUNT = 2011;

	const STATUS_TRADING_PASSWORD_1_ACTIVE = 1;
	const STATUS_TRADING_PASSWORD_4_TO_SET = 4;

	const FLAG_RESET_LOGIN_PASSWORD_YES = 1;
	const FLAG_RESET_LOGIN_PASSWORD_NO = 2;

	const ACTION_TYPE_1_RESET_TRADING_PASSWORD = 1;
	const ACTION_TYPE_2_FORGOT_PASSWORD = 2;
	const ACTION_TYPE_3_FORGOT_TRADING_PASSWORD = 3;

	const ACTION_TYPE_4_TO_SET_GESTURE = 4;//手势设置
	const ACTION_TYPE_5_TO_RESET_GESTURE = 5;//手势重置
	const ACTION_TYPE_6_FORGOT_GESTURE = 6;//忘记手势
	const ACTION_TYPE_7_CLOSE_GESTURE = 7;//取消手势
	const ACTION_TYPE_8_CLEAR_GESTURE = 8;//清除手势

	const ACTION_TYPE_2_SWITCH_ON = 2;
	const ACTION_TYPE_3_SWITCH_OFF = 3;
	const ACTION_TYPE_9_FINGER_PAY_ON = 9;
	const ACTION_TYPE_10_FINGER_PAY_OFF = 10;

	public static $PAYING_ACCOUNT = [1 => 20001, 2 => 22001, 3 => 23001];
	public static $BALANCE_TABLE = [1 => 'tbl_member_balance', 2 => 'tbl_agent_balance', 3 => 'tbl_partner_balance'];
	public static $FLOW_TABLE = [1 => 'tbl_member_balance_flow', 2 => 'tbl_agent_balance_flow', 3 => 'tbl_partner_balance_flow'];
	public static $DB_CHAR_ARR = [1 => 'member_id', 2 => 'agent_id', 3 => 'partner_id'];
	//TODO delete 没有用到？
	//	private static $TYPE_TABLE = [1 => 'tbl_member', 2 => 'tbl_agent', 3 => 'tbl_partner_staff'];
	//	private static $TYPE_NAME = [1 => 'Member', 2 => 'Agent', 3 => 'Partner'];
	protected $SID;

	public function __construct($db = null) {
		parent::__construct($db ? $db : Constant::MAIN_DB_RUN);
	}

	public function checkPassword($pwd) {
		if (!$pwd || !V::max_length($pwd, 24)) throw new TrantorException("Password");
		$code = MessageCode::ERR_0_NO_ERROR;
		$chk = \validation\Validator::checkPassword($pwd);
		if ($chk == 1) {
			$code = MessageCode::ERR_1006_PASSWORD_INVALID;
		} else if ($chk == 2) {
			$code = MessageCode::ERR_1008_NEW_PASSWORD_TOO_SIMPLE;;
		}
		return $code;
	}

	/** 判断sid是否已经存在，用于防止同一个sid登录两个用户
	 * @param $sid
	 * @return bool
	 */
	public function isSIDConflict($sid) {
		if (!$sid) return false;
		$sql = "select count(*) from tbl_member where session_id_device=" . qstr($sid);
		return ($this->getOne($sql)) > 0; // 获取sid信息
	}

	public function getStaffInfo($id) {
		$sql = 'select id, id account, name, session_id, status from tbl_user where 1 and id=' . qstr($id);
		return $this->getLine($sql);
	}

	/* 登录，更新数据库相关字段，更新Session相关字段
   * param $phone: 帐号
   * param $password: 密码
	 */
	public function login($phone, $password, $gesture, $fingerprint, $ver, $deviceID, $isDevice) {
		global $SID;
		if (!$this->_getPhone($phone)) return \MessageCode::ERR_1002_LOGIN_FAIL;
		$row = $this->_getUserByPhone($phone); //$account\
		$oldDID = $row['device_id'];
		$uid = $row['id'];
		$ip = getIP();
		$phone = $row['phone'];
		$pwd = $row['login_password'];
		$dbGesture = $row['gesture_password'];
		$status = $row['status'];
		$failLoginCount = $row['fail_login_count'];
		$e = new Event();
		if (!$uid) {
			$e->add("Android Login,Wrong Phone[$phone]");
			return \MessageCode::ERR_1002_LOGIN_FAIL;
		}
		//检查用户状态
		if ($status == self::USER_STATUS_3_INACTIVE) {
			return \MessageCode::ERR_1000_INACTIVE_ACCOUNT;
		}
		//检查密码
		if ($fingerprint) {
			if (!getConf('flag_dev') == 1 && $oldDID != $deviceID) return \MessageCode::ERR_1002_LOGIN_FAIL;

		} else if ($gesture && $gesture != $dbGesture) {
			$_SESSION['GESTURE_EXPIRED'] = \Constant::YES;
			return \MessageCode::ERR_1004_GESTURE_EXPIRED;
		} else if ((!$gesture && !\Security::comparePassword($pwd, $phone . $password))) {
			$e->add("Android Login,Wrong Password for $phone");
			$failLoginCount++;
			if ($failLoginCount >= getConf("login_try")) {
				$sql = 'update tbl_member set status=' . self::USER_STATUS_2_SUSPENDED . ' where id=' . qstr($uid); //
			} else {
				$sql = "update tbl_user_extend_password set last_fail_login_ip=" . qstr($ip) . ",last_fail_login_time=CURRENT_TIMESTAMP,fail_login_count=" . qstr($failLoginCount) . " where user_id =" . qstr($uid) . " and user_type = " . self::USER_TYPE_1_MEMBER;
			}
			$this->execute($sql);
			return \MessageCode::ERR_1002_LOGIN_FAIL;
		}
		if (!empty($oldDID) && $oldDID != $deviceID && $isDevice == 0) {
			return MessageCode::ERR_1001_LOGGED_ON_OTHER_DEVICE;
		}

		// 更新用户登录状态
		$sql1 = "update tbl_user_extend set last_login_ip=" . qstr($ip) . ",last_login_time=CURRENT_TIMESTAMP where user_id=" . qstr($uid) . " and user_type = " . self::USER_TYPE_1_MEMBER;
		$this->execute($sql1);
		$sql2 = "update tbl_user_extend_password set fail_login_count='0' where user_id =" . qstr($uid) . " and user_type = " . self::USER_TYPE_1_MEMBER;
		$this->execute($sql2);
		$sql3 = "update tbl_member set session_id_device=" . qstr($SID) . ",device_id=" . qstr($deviceID) . " where id=" . qstr($uid);
		$this->execute($sql3);

		$this->_saveUserSession($phone, $ver);

		if ($fingerprint) {
			if ($dbGesture) {
				$this->saveGesture("", self::ACTION_TYPE_7_CLOSE_GESTURE);
			}
			$remark = 'Login Android via Fingerprint Password(' . $ver . ' [' . getConf('version') . '])';
		} else if ($gesture) {
			$remark = 'Login Android via Gesture Password(' . $ver . ' [' . getConf('version') . '])';
		} else {
			$remark = 'Login Android via Login Password(' . $ver . ' [' . getConf('version') . '])';
		}

		//写log
		$l = new Log();
		$l->addLog($remark);

		return MessageCode::ERR_0_NO_ERROR;
	}

	private function _getPhone($phone) {
		$sql = "select phone from tbl_member where phone=" . qstr($phone);
		return $this->getOne($sql);
	}

	private function _getUserByPhone($phone) {
		if (!V::phone($phone) || !V::max_length($phone, 20)) throw new TrantorException('Phone');
		$sql = 'SELECT tm.device_id,tm.id,tm.phone,tm.level,tm.country_code,tm.name,tm.session_id_device,tm.status,tuep.login_password,tm.phone,tm.lmt,tue.user_type,tue.affiliate_id,tuep.flag_reset_login_password,tue.last_login_time,tue.last_login_ip,tuep.fail_login_count,tuep.status_trading_password,tuep.gesture_password FROM tbl_member tm LEFT JOIN tbl_user_extend tue ON tue.user_id = tm.id AND tue.user_type = ' . self::USER_TYPE_1_MEMBER . ' LEFT JOIN tbl_user_extend_password tuep ON tuep.user_id = tm.id AND tuep.user_type = ' . self::USER_TYPE_1_MEMBER . ' WHERE tm.phone = ' . qstr($phone) . ' GROUP BY tm.id  ';
		return $this->getLine($sql);
	}

	private function _saveUserSession($phone, $ver) {
		$r = $this->_getUserByPhone($phone);
		$_SESSION['SID'] = $r['session_id_device'];
		$_SESSION['UID'] = $r['id'];
		$_SESSION['NAME'] = $r['name'];
		$_SESSION['TYPE'] = $r['user_type'];
		$_SESSION['PHONE'] = $r['phone'];
		$_SESSION['GESTURE_PASSWORD'] = $r['gesture_password'];
		$_SESSION['STATUS'] = $r['status'];
		$_SESSION['CODE'] = $r['code'];
		$_SESSION['SESSION_EXPIRE_TIME'] = time() + getConf('session_expire_time');
		$_SESSION['APK_VERSION'] = $ver;
		$_SESSION['FLAG_RESET_PASSWORD'] = $r['flag_reset_login_password'] == \Constant::YES;
		$_SESSION['AFFILIATE_ID'] = $r['affiliate_id'];
		$_SESSION['LMT'] = $r['lmt'];
		$_SESSION['CURRENCY'] = $r['currency'];
		$_SESSION['BALANCE'] = $r['balance'] ? $r['balance'] : 0;
		$_SESSION['CUSTOMER_SERVICE'] = getConf('customer_service');
		$_SESSION['LEVEL'] = $r['level'];
		$_SESSION['STATUS_TRADING_PASSWORD'] = $r['status_trading_password'];
	}

	public function saveGesture($gesture, $actionType) {
		if (!in_array($actionType, array(self::ACTION_TYPE_4_TO_SET_GESTURE, self::ACTION_TYPE_5_TO_RESET_GESTURE, self::ACTION_TYPE_6_FORGOT_GESTURE, self::ACTION_TYPE_7_CLOSE_GESTURE, self::ACTION_TYPE_8_CLEAR_GESTURE))) throw new TrantorException("Action Type Error");
		$sql = 'update tbl_user_extend_password set gesture_password = ' . qstr($gesture) . ' where user_id = ' . qstr($_SESSION['UID']) . ' and user_type = ' . User::USER_TYPE_1_MEMBER;
		$this->execute($sql);
		$remark = "";
		$l = new Log();
		if ($actionType == self::ACTION_TYPE_4_TO_SET_GESTURE) {
			$remark = "Set Gesture Password";
		} else if ($actionType == self::ACTION_TYPE_5_TO_RESET_GESTURE) {
			$remark = "Reset Gesture Password";
		} elseif ($actionType == self::ACTION_TYPE_6_FORGOT_GESTURE) {
			$remark = "Forgot Gesture Password";
		} elseif ($actionType == self::ACTION_TYPE_7_CLOSE_GESTURE) {
			$remark = "Close Gesture Password";
		} else if ($actionType == self::ACTION_TYPE_8_CLEAR_GESTURE) {
			$remark = "Clear Gesture Password";
		}
		$l->addLog($remark);
		return true;
	}

	public function clearGesture() {
		$sql = 'update tbl_user_extend_password set gesture_password = ' . qstr("") . ' where user_id = ' . qstr($_SESSION['UID']) . ' and user_type = ' . User::USER_TYPE_1_MEMBER;
		$this->execute($sql);
	}

	/** 退出
	 * @param $uid
	 * @throws Exception
	 */
	public function logout($uid) {
		if (!V::over_num($uid, 0)) throw new Exception('User::logout Parameter Error uid');
		$sql = "update tbl_member set session_id_device = '',device_id = '' where id = '$uid'";
		$this->execute($sql);
		$l = new Log();
		$l->addLog('Logout Android');
		session_unset();
	}

	public function updatePassword($pwd) {
		if (!$pwd || !V::max_length($pwd, 24)) throw new TrantorException("Password");
		$uid = $_SESSION['UID'];
		if (!$uid) return false;
		$pwd = \Security::safeEncrypt($_SESSION['PHONE'] . $pwd);
		$sql = "update tbl_user_extend_password set login_password = " . qstr($pwd) . " ,flag_reset_login_password = 2 where user_id = " . qstr($uid) . ' and user_type = ' . self::USER_TYPE_1_MEMBER;
		$this->execute($sql);
		$af = $this->affected_rows();
		$l = new Log();
		$l->addLog('Update Password');
		return $af == 1;
	}

	public function resetPassword($uid, $phone, $pwd, $deviceID, $ver) {
		$phone = Utils::formatPhone2($phone);
		if (!V::required($uid)) throw new TrantorException('UID');
		if (!V::phone($phone) || !V::max_length($phone, 20)) throw new TrantorException('Phone');
		if (!V::required($pwd) || !V::max_length($pwd, 24)) throw new TrantorException("Password");
		if (!V::required($deviceID)) throw new TrantorException('DeviceID');
		$pwd = \Security::safeEncrypt($phone . $pwd);
		$sql = "update tbl_user_extend_password set login_password = " . qstr($pwd) . " ,flag_reset_login_password = 2 where user_id = " . qstr($uid) . ' and user_type = ' . self::USER_TYPE_1_MEMBER;
		$this->execute($sql);
		$af = $this->affected_rows();
		global $SID;
		$sql = "update tbl_member set session_id_device=" . qstr($SID) . ",device_id=" . qstr($deviceID) . " where id=" . qstr($uid);
		$this->execute($sql);
		$this->_saveUserSession($phone, $ver);
		//清除手势密码
		$_SESSION['FLAG_USE_GESTURE_PASSWORD'] = false;
		$_SESSION['GESTURE_PASSWORD'] = '';
		$l = new Log();
		$l->addLog('Forgot Password');
		return $af == 1;
	}

	public function register($name, $countryCode, $phone, $pwd, $deviceID, $ver) {
		$phone = Utils::formatPhone2($phone);
		if (!V::nameMember($name)) throw new TrantorException('Name');
		if (!V::phone($phone) || !V::max_length($phone, 20)) throw new TrantorException('Phone');
		if (!V::required($countryCode)) throw new TrantorException('CountryCode');
		if (!V::required($deviceID)) throw new TrantorException('DeviceID');
		if ($this->_isPhoneDuplicated($phone)) throw new TrantorException('Phone is duplicate');
		if (!$pwd || !V::max_length($pwd, 24)) throw new TrantorException("Password");
		$s = new System();
		if (!$s->isFunctionRunning(Constant::FUNCTION_100_REGISTER)) return array('err_code' => MessageCode::ERR_505_FUNCTION_NOT_RUNNING);
		$pwd = \Security::safeEncrypt($phone . $pwd);
		global $SID;
		$sql = "insert into tbl_member(name,country_code,phone,level,session_id_device,device_id,status) values(" . qstr(strtoupper($name)) . "," . qstr($countryCode) . "," . qstr($phone) . "," . self::USER_LEVEL_1 . "," . qstr($SID) . "," . qstr($deviceID) . "," . self::USER_STATUS_1_ACTIVE . ")";
		$this->execute($sql);
		$memberID = $this->insert_id();
		$sql = "insert into tbl_user_extend(user_type,user_id,register_time,is_email_verified,is_date_of_birth_verified) values(" . self::USER_TYPE_1_MEMBER . "," . qstr($memberID) . ",now()," . self::EMAIL_2_NOT_VERIFIED . "," . self::DATE_OF_BIRTH_2_NOT_VERIFIED . ")";
		$this->execute($sql);
		$sql = 'insert into tbl_user_extend_password(user_type, user_id, status_trading_password) values(' . self::USER_TYPE_1_MEMBER . ',' . qstr($memberID) . ',' . self::STATUS_TRADING_PASSWORD_4_TO_SET . ')';
		$this->execute($sql);
		$sql = "update tbl_user_extend_password set login_password = " . qstr($pwd) . " ,flag_reset_login_password = 2 where user_id = " . qstr($memberID) . ' and user_type = ' . self::USER_TYPE_1_MEMBER;
		$this->execute($sql);
		$this->_saveUserSession($phone, $ver);
		$remark = 'Register Member ' . $phone;
		$s = new Log();
		$s->addLog($remark, $memberID);

		$this->addUserExtendVerified($memberID, Constant::VERIFIED_TYPE_1_PHONE);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $this->getLoginData());
	}

	private function _isPhoneDuplicated($phone) {
		if (!V::phone($phone) || !V::max_length($phone, 20)) throw new TrantorException('Phone');
		$s = new System();
		return $s->isDuplicated('tbl_member', array('phone' => $phone)) || $s->isDuplicated('tbl_agent', array('phone' => $phone)) || $s->isDuplicated('tbl_partner', array('phone_contact' => $phone)) || $s->isDuplicated('tbl_branch_phone', array('phone' => $phone));
	}

	public function addUserExtendVerified($userID, $type) {
		if (!V::over_num($userID, 0)) throw new TrantorException('UserID');
		if (!in_array($type, array(Constant::VERIFIED_TYPE_1_PHONE, Constant::VERIFIED_TYPE_2_PHONE_CONTACT, Constant::VERIFIED_TYPE_3_ID, Constant::VERIFIED_TYPE_4_FINGERPRINT))) throw new TrantorException("Verified Type");
		$sql = 'INSERT INTO tbl_user_extend_verified (user_type,user_id,type) VALUES (' . User::USER_TYPE_1_MEMBER . ',' . qstr($userID) . ',' . qstr($type) . ')';
		$this->execute($sql);
	}

	public function getLoginData() {
		global $SID;
		$rt = array();
		$u = array();
		$u['id'] = $_SESSION['UID'];
		$u['name'] = $_SESSION['NAME'];
		$u['type'] = $_SESSION['TYPE'];
		$u['phone'] = $_SESSION['PHONE'];
		$u['gesture_password'] = $_SESSION['GESTURE_PASSWORD'];
		$u["reset_password"] = $_SESSION['FLAG_RESET_PASSWORD'];
		$u['status'] = $_SESSION['STATUS'];
		$u['level'] = $_SESSION['LEVEL'];
		$u['status_trading_password'] = $_SESSION['STATUS_TRADING_PASSWORD'];
		$u['portrait'] = $this->getPortrait();
		$rt['user'] = $u;
		$rt['sid'] = $SID;
		$rt['customer_service'] = $_SESSION['CUSTOMER_SERVICE'];
		$c = new Currency();
		$rt['exchange_list'] = $c->getCurrencyList();
		$s = new System();
		$rt['country_code_list'] = $s->getCountryCodeList();
		$rt["flag_dev"] = getConf("flag_dev");
		$rt['socket_servers'] = getConf('socket_servers');
		return $rt;
	}

	public function isValidPassword($pwd) {
		if (!$pwd || !V::max_length($pwd, 24)) throw new TrantorException("Password");
		$uid = $_SESSION['UID'];
		if (!$uid) return false;
		$sql = "select login_password from tbl_user_extend_password where user_id= " . qstr($uid) . ' and user_type = ' . self::USER_TYPE_1_MEMBER;
		$dbPwd = $this->getOne($sql);
		return \Security::comparePassword($dbPwd, $_SESSION['PHONE'] . $pwd);
	}

	public function getLoginUser() {
		$sql = "select id,status,IFNULL(balance,0) balance,IFNULL(currency,'USD') currency from tbl_member tm left join tbl_member_balance tmb on(tm.id=tmb.member_id) where tm.id=" . qstr($_SESSION['UID']);
		return $this->getLine($sql);
	}

	public function getUserInfo($id) {
		$sql = 'select id, name, session_id_device, status from tbl_member where 1 and id=' . qstr($id);
		return $this->getLine($sql);
	}

	public function getUserIDByPhone($phone) {
		$phone = Utils::formatPhone2($phone);
		if (!V::phone($phone) || !V::max_length($phone, 20)) throw new TrantorException('Phone');
		$sql = 'SELECT id FROM tbl_member WHERE phone = ' . qstr($phone);
		return $this->getOne($sql);
	}

	public function clearDeviceID($phone) {
		if (!V::required($phone)) throw new TrantorException("Phone Error");
		$sql = "update tbl_member set device_id='' where phone=" . qstr($phone);
		$this->execute($sql);
	}

	public function getExchangeDetail($exchangeID) {
		if (!V::over_num($exchangeID, 0)) throw new TrantorException('$exchangeID');
		$sql = 'select id,time,currency_source,currency_target,exchange,amount_source,amount_target,remark from tbl_user_currency_exchange where id = ' . qstr($exchangeID);
		return $this->getLine($sql);
	}

	public function checkTradingPassword($tradingPassword) {
		if (!V::required($tradingPassword)) throw new TrantorException("TradingPassword");
		$a = $this->checkTradingPasswordStatus(true);
		$r = $a['result'];
		$maxTradingCount = $this->_getMaxFailTradingCount();
		if ($r['is_valid']) {
			if (!Security::comparePassword($r['trading_password'], $_SESSION['PHONE'] . $tradingPassword)) {
				$r['remain'] -= 1;
				$sql = "update tbl_user_extend_password set fail_trading_count=fail_trading_count+1,fail_trading_date=now() where user_id=" . qstr($_SESSION['UID']) . " and user_type=" . User::USER_TYPE_1_MEMBER;
				$this->execute($sql);
				$r['is_valid'] = false;
			} else {
				if ($maxTradingCount > $r['remain']) {
					$sql = "update tbl_user_extend_password set fail_trading_count=0 where user_id=" . qstr($_SESSION['UID']) . " and user_type=" . User::USER_TYPE_1_MEMBER;
					$this->execute($sql);
				}
				$r['is_valid'] = true;
			}
		}
		unset($r['trading_password']);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $r);
	}

	public function checkTradingPasswordStatus($getPwd = false) {
		$maxTradingCount = $this->_getMaxFailTradingCount();
		$sql = 'select trading_password,status_trading_password,fail_trading_count,fail_trading_date from tbl_user_extend_password where user_id = ' . qstr($_SESSION['UID']) . ' and user_type = ' . User::USER_TYPE_1_MEMBER;
		$rt = $this->getLine($sql);

		$a['status'] = $rt['status_trading_password'];

		$lastDay = strtotime($rt['fail_trading_date']);
		$failCount = $rt['fail_trading_count'];
		$currentDay = strtotime(Utils::getDBDate());

		if ($rt['trading_password'] && $rt['status_trading_password'] == self::STATUS_TRADING_PASSWORD_1_ACTIVE) {
			if ($currentDay - $lastDay > 0) {
				$a['is_valid'] = true;
				if ($failCount != 0) $this->_resetTradingCount();
				$a['remain'] = $maxTradingCount;
			} else {
				$a['is_valid'] = $maxTradingCount - $failCount > 0;
				$a['remain'] = $maxTradingCount - $failCount;
			}
		} else {
			$a['is_valid'] = false;
			$a['remain'] = $maxTradingCount - $failCount;
		}
		if ($getPwd) $a['trading_password'] = $rt['trading_password'];
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $a);
	}

	private function _getMaxFailTradingCount() {
		$sql = "select amount from tbl_config_global where code=" . self::CODE_2011_MAX_FAIL_TRADING_COUNT;
		return $this->getOne($sql);
	}

	private function _resetTradingCount() {
		$sql = "update tbl_user_extend_password set fail_trading_count=0 where user_type=" . User::USER_TYPE_1_MEMBER . " and user_id=" . $_SESSION['UID'];
		$this->execute($sql);
	}

	public function getGestureConfig() {
		$sql = "select gesture_password from tbl_user_extend_password where user_id=" . $_SESSION['UID'] . " and user_type=" . User::USER_TYPE_1_MEMBER;
		$gesture = $this->getOne($sql);
		if (empty($gesture)) {
			return false;
		}
		return true;
	}

	public function updateTradingPassword($pwdNew, $pwdOld, $actionType) {
		if (!is_numeric($pwdNew)) throw new TrantorException("New Password");
		if ($pwdOld) if (!is_numeric($pwdOld)) throw new TrantorException("Old Password");
		if (!in_array($actionType, array(1, 3, 4, 6))) throw new TrantorException("Action Type Error");
		$r = $this->getTradingPasswordConfig();
		$tradingStatus = $r['status_trading_password'];
		if (!($tradingStatus == Constant::STATUS_1_ACTIVE || $tradingStatus == Constant::STATUS_4_TO_SET || Constant::STATUS_6_TO_VERIFY_CERTIFICATE)) throw new TrantorException("Trading Status Error");
		if (($tradingStatus == Constant::STATUS_1_ACTIVE && $actionType == self::ACTION_TYPE_1_RESET_TRADING_PASSWORD) || ($tradingStatus == Constant::STATUS_6_TO_VERIFY_CERTIFICATE && $actionType != self::ACTION_TYPE_3_FORGOT_TRADING_PASSWORD)) {

			if (!$this->isValidTradingPassword($pwdOld)) return array('err_code' => MessageCode::ERR_1006_PASSWORD_INVALID);

		}
		$chk = \validation\Validator::checkTradingPassword($pwdNew);
		if ($chk == 1) {
			return array('err_code' => MessageCode::ERR_1007_NEW_PASSWORD_INVALID);
		}

		$pwd = \Security::safeEncrypt($_SESSION['PHONE'] . $pwdNew);
		$rt = array();
		if ($actionType == self::ACTION_TYPE_3_FORGOT_TRADING_PASSWORD || $tradingStatus == Constant::STATUS_6_TO_VERIFY_CERTIFICATE) {
			$sql = "update tbl_user_extend_password set trading_password = " . qstr($pwd) . " ,status_trading_password = " . Constant::STATUS_6_TO_VERIFY_CERTIFICATE . ",fail_trading_count=0 where user_id = " . qstr($_SESSION['UID']) . ' and user_type = ' . self::USER_TYPE_1_MEMBER;
			$rt['status_trading_password'] = Constant::STATUS_6_TO_VERIFY_CERTIFICATE;
			$remark = "Reset Trading Password";

		} else {
			if ($actionType == self::ACTION_TYPE_1_RESET_TRADING_PASSWORD) {
				$remark = "Reset Trading Password";
			} else {
				$remark = "Set Trading Password";
			}
			$sql = "update tbl_user_extend_password set trading_password = " . qstr($pwd) . " ,status_trading_password = " . Constant::STATUS_1_ACTIVE . " ,fail_trading_count=0 where user_id = " . qstr($_SESSION['UID']) . ' and user_type = ' . self::USER_TYPE_1_MEMBER;
			$rt['status_trading_password'] = Constant::STATUS_1_ACTIVE;
		}

		$this->execute($sql);
		$l = new Log();
		$l->addLog($remark);
		return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
	}

	public function getTradingPasswordConfig() {
		$sql = 'SELECT tue.status_trading_password, tuc.status, tuc.remark FROM db_system_run.tbl_user_extend_password tue LEFT JOIN db_assist_run.tbl_user_certificate_to_verify tuc ON tuc.user_id = tue.user_id and tuc.user_type = tue.user_type WHERE tue.user_id = ' . qstr($_SESSION['UID']) . ' and tue.user_type = ' . User::USER_TYPE_1_MEMBER . ' ORDER BY tuc.time DESC';
		return $this->getLine($sql);
	}

	//暂时只支持member，agent，partner,返回 [member] +855-12323231 szs
	//TODO delete 没有调用？
	//	public function getUserPhoneName($userType, $userID) {
	//		if (!V::over_num($userID, 0)) throw new TrantorException('UserID');
	//		if (!V::between_num($userType, 1, 3)) throw new TrantorException('UserType');
	//
	//		$userTable = self::$TYPE_TABLE[$userType];
	//		return '[' . self::$TYPE_NAME[$userType] . '] ' . $this->getOne("select concat(phone,' ',name) name from $userTable where id=" . qstr($userID));
	//	}

	public function isValidTradingPassword($pwd) {
		if (!$pwd || !V::max_length($pwd, 24)) throw new TrantorException("Password");
		$uid = $_SESSION['UID'];
		if (!$uid) return false;
		$sql = "select trading_password from tbl_user_extend_password where user_id= " . qstr($uid) . ' and user_type = ' . self::USER_TYPE_1_MEMBER;
		$dbPwd = $this->getOne($sql);
		return \Security::comparePassword($dbPwd, $_SESSION['PHONE'] . $pwd);
	}

	public function addFingerprintLog($flag) {
		if (!in_array($flag, array(2, 3, 9, 10))) throw new TrantorException("FLAG Error");
		$l = new Log();
		if ($flag == self::ACTION_TYPE_2_SWITCH_ON) {
			$remark = "Set Fingerprint Login Password";
		} else if ($flag == self::ACTION_TYPE_3_SWITCH_OFF) {
			$remark = "Close Fingerprint Login Password";
		} else if ($flag == self::ACTION_TYPE_9_FINGER_PAY_ON) {
			$remark = "Set Fingerprint Trading Password";
		} else {
			$remark = "Close Fingerprint Trading Password";
		}
		$l->addLog($remark);
	}

	public function checkMemberFingerprintExists() {
		$sql = 'select fingerprint from tbl_user_fingerprint where user_type = ' . self::USER_TYPE_1_MEMBER . ' and user_id = ' . qstr($_SESSION['UID']);
		return $this->getOne($sql) != null;
	}

	public function changeTradingStatus() {
		$sql = 'update tbl_user_extend_password set status_trading_password = ' . qstr(Constant::STATUS_4_TO_SET) . ' where user_id = ' . qstr($_SESSION['UID']) . ' and user_type = ' . User::USER_TYPE_1_MEMBER;
		$this->execute($sql);
		$sql1 = 'SELECT status_trading_password status FROM db_system_run.tbl_user_extend_password WHERE user_id = ' . qstr($_SESSION['UID']) . ' and user_type = ' . User::USER_TYPE_1_MEMBER;
		return $this->getLine($sql1);
	}

	public function checkUserExtendVerified($userID, $type) {
		if (!V::over_num($userID, 0)) throw new TrantorException('UserID');
		if (!in_array($type, array(Constant::VERIFIED_TYPE_1_PHONE, Constant::VERIFIED_TYPE_2_PHONE_CONTACT, Constant::VERIFIED_TYPE_3_ID, Constant::VERIFIED_TYPE_4_FINGERPRINT))) throw new TrantorException("Verified Type");
		$sql = 'SELECT 1 FROM tbl_user_extend_verified WHERE user_type=' . User::USER_TYPE_1_MEMBER . ' AND user_id=' . qstr($userID) . ' AND type=' . qstr($type);
		return $this->getOne($sql) > 0;
	}

	public function getMemberLevelByID($id) {
		if (!V::over_num($id, 0)) throw new TrantorException('ID');
		$sql = 'SELECT level FROM tbl_member WHERE id = ' . qstr($id);
		return $this->getOne($sql);
	}

	public function getPartnerLevelByID($id) {
		if (!V::over_num($id, 0)) throw new TrantorException('ID');
		$sql = 'SELECT level FROM tbl_partner WHERE id = ' . qstr($id);
		return $this->getOne($sql);
	}

	public function getMerchantLevelByID($id) {
		if (!V::over_num($id, 0)) throw new TrantorException('ID');
		$sql = 'SELECT level FROM tbl_merchant WHERE id = ' . qstr($id);
		return $this->getOne($sql);
	}

	public function addForgotPasswordLog() {
		$log = new Log();
		$log->addLog("Forgot Trading Password");
		$sql = 'update tbl_user_extend_password set status_trading_password = ' . qstr(Constant::STATUS_6_TO_VERIFY_CERTIFICATE) . ' where user_id = ' . qstr($_SESSION['UID']) . ' and user_type = ' . User::USER_TYPE_1_MEMBER;
		$this->execute($sql);

	}

	public function getPartnerIDByCode($code) {
		if (!V::over_num($code, 0)) throw new TrantorException('Code');
		$sql = 'select ifnull (id,0) from tbl_partner where code=' . qstr($code);
		return $this->getOne($sql);
	}

	public function getProfile($memberID = 0) {
		$id = $memberID == 0 ? $_SESSION['UID'] : $memberID;
		if (!V::over_num($id, 0)) throw new TrantorException('MemberID');
		$sql = 'select sex,nationality,date_of_birth,flag_lock from tbl_user_profile WHERE user_type=' . User::USER_TYPE_1_MEMBER . ' and user_id=' . qstr($id);
		return $this->getLine($sql);
	}

	public function checkTransfer() {
		$isRelateMerchant = $this->getOne('select 1 from tbl_merchant where relate_member_id = ' . qstr($_SESSION['UID']));
		$isRelatePartner = $this->getOne('select 1 from tbl_partner where relate_member_id = ' . qstr($_SESSION['UID']));
		return array('is_relate_merchant' => $isRelateMerchant, 'is_relate_partner' => $isRelatePartner);
	}

	public function setProfile($sex, $nationality, $birthday, $memberID = 0) {
		$id = $memberID == 0 ? $_SESSION['UID'] : $memberID;
		if (!V::over_num($id, 0)) throw new TrantorException('MemberID');
		if ($this->checkIsLock() == Constant::YES) throw new TrantorException('Profile Locked');
		if (!in_array($sex, [Constant::SEX_1_MALE, Constant::SEX_2_FEMALE])) throw new TrantorException('Sex');
		if (!in_array($nationality, [Constant::NATIONALITY_855_CAMBODIANS, Constant::NATIONALITY_84_VIETNAMESE, Constant::NATIONALITY_86_CHINESE, Constant::NATIONALITY_66_THAIS, Constant::NATIONALITY_60_MALAYSIANS, Constant::NATIONALITY_62_INDONESIANS, Constant::NATIONALITY_33_FRENCH_CITIZENS])) throw new TrantorException('Nationality');
		if (!V::date($birthday)) throw new TrantorException('Birthday');
		$sql = 'insert into tbl_user_profile(user_type,user_id,sex,nationality,date_of_birth)VALUES (' . qstr(User::USER_TYPE_1_MEMBER) . ',' . qstr($id) . ',' . qstr($sex) . ',' . qstr($nationality) . ',' . qstr($birthday) . ')' . ' ON DUPLICATE KEY update sex=' . qstr($sex) . ',' . 'nationality=' . qstr($nationality) . ',' . 'date_of_birth=' . qstr($birthday);
		$this->execute($sql);
	}

	public function checkIsLock($memberID = 0) {
		$id = $memberID == 0 ? $_SESSION['UID'] : $memberID;
		if (!V::over_num($id, 0)) throw new TrantorException('MemberID');
		$sql = 'select ifnull(flag_lock,0) from tbl_user_profile WHERE user_type=' . User::USER_TYPE_1_MEMBER . ' and user_id=' . qstr($id);
		return $this->getOne($sql);
	}

	public function checkIsMember($phone) {
		if (!V::required($phone)) throw new \TrantorException('Phone');
		$phone = Utils::formatPhone2($phone);
		$sql = 'select ifnull(name,"") name from tbl_member where phone = ' . qstr($phone) . ' and status = ' . Constant::STATUS_1_ACTIVE;
		$name = $this->getOne($sql);
		if (!$name) {
			return array('err_code' => MessageCode::ERR_1708_CANNOT_FIND_THE_MEMBER);
		} else {
			return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => array('name' => $name));
		}
	}

	public function getMemberByID($memberID) {
		if (!V::over_num($memberID, 0)) throw new TrantorException('ID');
		$sql = 'select ifnull(name,"") name,phone from tbl_member where id = ' . qstr($memberID) . ' and status = ' . Constant::STATUS_1_ACTIVE;
		$rt = $this->getLine($sql);
		if (!$rt) {
			return array('err_code' => MessageCode::ERR_1708_CANNOT_FIND_THE_MEMBER);
		} else {
			return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
		}
	}

	public function getMemberByPhone($phone) {
		if (!V::required($phone)) throw new TrantorException('Phone');
		$sql = 'select name from tbl_member where phone = ' . qstr($phone) . ' and status = ' . Constant::STATUS_1_ACTIVE;
		$rt['user'] = $this->getLine($sql);
		if (!$rt['user']) {
			return array('err_code' => MessageCode::ERR_1708_CANNOT_FIND_THE_MEMBER);
		} else {
			return array('err_code' => MessageCode::ERR_0_NO_ERROR, 'result' => $rt);
		}
	}

	public function getPortrait() {
		$sql = 'select portrait from tbl_user_portrait where user_type=' . qstr(User::USER_TYPE_1_MEMBER) . ' and user_id=' . qstr($_SESSION['UID']);
		return $this->getOne($sql);
	}

	public function updatePortrait($image) {
		if (!V::required($image)) throw new TrantorException('Image');
		$sql = 'insert into tbl_user_portrait (user_type,user_id,portrait)VALUES (' . qstr(User::USER_TYPE_1_MEMBER) . ',' . qstr($_SESSION['UID']) . ',' . qstr($image) . ') on duplicate key update portrait=' . qstr($image);
		$this->execute($sql);
		$this->execute("update db_system_run.tbl_member set lmt=now() where id=". qstr($_SESSION['UID']));
	}

	public function getCouponList($status, $page) {
		if(!V::over_num($status,0)) throw new TrantorException('Status');
		if($status == Constant::COUPON_STATUS_1_UNUSED){
			$sql='select id,time,date_expire,type from tbl_coupon where member_id='.qstr($_SESSION['UID']);
		}else{
			$sql='select id,time,date_expire,type,time_use from tbl_coupon2 where member_id='.qstr($_SESSION['UID']);
		}
		return $this->getPageArray($sql,$page);
 	}

}