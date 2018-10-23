<?php

/**
 * Created by YGH.
 * Date: 13-12-25 下午4:25
 */
class Privilege {

	/**
	 * @param bool $mod 为true表示当前动作会修改数据
	 * @throws Exception
	 */
	public static function chkAction($mod = false) {
		$cmd = $_REQUEST['cmd'];
		if (!$cmd || $cmd == "index") return;
		//		$flagDev = getConf("flagDev");

		if ($_SESSION['APK_VERSION']) {
			$vArr = getConf('supported_apk_versions');
			if (!in_array($_SESSION['APK_VERSION'], $vArr)) {
				echo json_encode(array('err_code' => MessageCode::ERR_104_NOT_SUPPORTED_APK_VERSION));
				exit(0);
			}
		}

		$s = new System();
		$u = new User();
		$user = $u->getUserInfo($_SESSION["UID"]);

		if (!$_SESSION["UID"] || $user['session_id_device'] != $_REQUEST['_s'] || !$s->isFunctionRunning(Constant::FUNCTION_102_MEMBER_LOGIN_ANDROID)) {
			echo json_encode(array('err_code' => MessageCode::ERR_102_SESSION_TIMEOUT));
			exit(0);
		}

		if ($user['status'] == User::USER_STATUS_3_INACTIVE) {
			echo json_encode(array('err_code' => MessageCode::ERR_1000_INACTIVE_ACCOUNT));
			exit(0);
		}

		if ($mod && $user['status'] == User::USER_STATUS_2_SUSPENDED) {
			echo json_encode(array('err_code' => MessageCode::ERR_200_NO_PRIVILEGE));
			exit(0);
		}
	}

	public static function chkActionWithoutSession($mod = false) {
		$cmd = $_REQUEST['cmd'];
		if (!$cmd || $cmd == "index") return;

		if ($mod && $_SESSION['APK_VERSION']) {
			$vArr = getConf('supported_apk_versions');
			if (!in_array($_SESSION['APK_VERSION'], $vArr)) {
				echo json_encode(array('err_code' => MessageCode::ERR_104_NOT_SUPPORTED_APK_VERSION));
				exit(0);
			}
		}
	}
}