<?php
/**
 * Just wanna make it run.
 *     -- Wanjo Chan
 */
//*********************************************************************************************

if (!defined("_ROOT_")) {
	throw new Exception("//_ROOT_ is not defined??");
}

if (!defined("_LIB_")) {
	define("_LIB_", realpath(dirname(__FILE__) . "/../"));
}

if (!defined("_LOG_")) {
	define("_LOG_", realpath(_ROOT_ . "/../logs/"));
}

if (!defined("_TMP_")) {
	throw new Exception("//_TMP_ is not defined.");
}

require(_PLATFORM_ . '/inc.logger.php');
require(_PLATFORM_ . '/inc.func.php');
require(_PLATFORM_ . '/inc.safe.php');
require(_PLATFORM_ . '/inc.lang.php');


class Controller {

	public static $_conf_ = array();

	public static function getConf() {
		return Controller::$_conf_;
	}

	public static function gzip($buffer) {
		$len = strlen($buffer);
		if (strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip,deflate')) {
			$gzbuffer = gzencode($buffer);
			$gzlen = strlen($gzbuffer);
			if ($len > $gzlen) {
				header("Content-Length: $gzlen");
				header("Content-Encoding: gzip");
				return $gzbuffer;
			}
		}
		header("Content-Length: $len");
		return $buffer;
	}

	public static function run($rpc=null) {
		//quicklog("DEBUG","controller::run");
		try {
			//if (isset($_REQUEST['phprpc_func'])) {
			//}eles{
			$ACT = self::$_conf_['ACT'];
			$GLOBALS['ACT'] = $ACT;
			//if($ACT=='')$ACT="_a";

			$_S = self::$_conf_['_S'];
			$GLOBALS['_S'] = $_S;
			//if($_S=='')$_S="_s";

			$MODEL = self::$_conf_['MODEL'];
			$GLOBALS['MODEL'] = $MODEL;
			//if($MODEL=='')$MODEL="php/action";

			$CTRL = self::$_conf_['CTRL'];
			if ($CTRL == "") {
				throw new Exception("CTRL not config");
			}
			$GLOBALS['CTRL'] = $CTRL;

			$ACTVAL = trim(@$_REQUEST[$ACT]);

			if ($ACTVAL == '') {
				if (isset($_REQUEST['phprpc_func']) || isset($_REQUEST['phprpc_encode'])) {
					//skip
				} else {
					reset($_GET);
					$_tmp = key($_GET);
					if ($_GET[$_tmp] == "")
						$ACTVAL = $_tmp;
				}
			}else {
				str_replace("$ACTVAL", ".", ""); //hacker defender
			}
			$GLOBALS['ACTVAL'] = $ACTVAL;
			$SID = $_REQUEST[$_S];
			if ($SID != '') {
				session_id($SID);
			}
			//}
			//*********************************************************************************************
			// Time Zone & Local Time
			$SERVER_TIMEZONE = self::$_conf_['SERVER_TIMEZONE']; //$GLOBALS['SERVER_TIMEZONE'];
			if ($SERVER_TIMEZONE == '') {
				throw new Exception("FTL00002_SERVER_TIMEZONE_must_be_config");
			} else if ($SERVER_TIMEZONE != date_default_timezone_get()) {
				date_default_timezone_set("$SERVER_TIMEZONE");
			}

			//$PHP_TIMESTAMP = time();
			session_start();

			chkSession();
			chkSecurity();

			$SID = session_id();
			//quicklog("DEBUG","    $SID");
			$GLOBALS['SID'] = $SID;

			setSessionLang();

			if (@$_SESSION['_ip'] == "")
				$_SESSION['_ip'] = getIP();

			$_php_start_time = microtime();
		} catch (Exception $ex) {
			$err_msg = "Exception($ACTVAL)=" . $ex->getMessage();
			quicklog("err-php", $err_msg."\n");
			throw $ex;
		}
		if ($ACTVAL != "") {
			try {
				//mode v3
				$_b = trim($_REQUEST['_b']);
				if ($_b != "") {
					$_b = str_replace(".", "", $_b);
					$_fn = "php/$_b/$ACTVAL.php";
					if (file_exists(_ROOT_ . "/$_fn")) {
						$_e = trim($_REQUEST['_e']); //encrypt
						@ob_start();
						$err_code = 0;
						include_once(_ROOT_ . "/$_fn");
						//self::$_conf_['ACTION']
						if ($err_code > 0) {
							throw new Exception('Your account has logged in somewhere else.', $err_code);
						}
						if ($_b != "action") {
							if ($_e != "") {
								//debug("Controller: _request ".$_REQUEST['_d']);
								//debug("Controller: _request ".base64_decode($_REQUEST['_d']));
								//debug("Controller: _request ".appUtil::unescape(base64_decode($_REQUEST['_d'])));
								$_d = json_decode(base64_decode($_REQUEST['_d']), true); //params
								//$_d=my_json_decode(appUtil::unescape(base64_decode($_REQUEST['_d'])));//params
								//TODO decrypt with key?
								$_rt = runCmd($_d);
							}else
								$_rt = runCmd($_REQUEST);
						}
						print "$_rt";
						$output = ob_get_clean();
						//TODO xx_tea
						$_g = trim($_REQUEST['_g']); //gzip
						$nogzip = trim($_REQUEST['nogzip']);
						if ($_e != "") {
							$_key = $_SESSION[$_e]['key']; //= str_pad($key, 16, "\0", STR_PAD_LEFT);
							//if($_key=="") throw new Exception("Empty Key");
							//$output=xxtea_encrypt($output,$key);//TODO
							$output = base64_encode($output);
						}
						if ($_g == "0" || "1" == $nogzip) {

						}else
							$out = self::gzip($output);
						echo $out;
						ob_end_flush();
					}else {
						die("'$_b/$ACTVAL'404");
					}
				} else {
					//mode action: just action
					$_fn = self::$_conf_['ACTION'] . "/$ACTVAL.php";
					if (file_exists(_ROOT_ . "/$_fn")) {
						include_once(_ROOT_ . "/$_fn");
					} else {
						die("'$ACTVAL' 404");
					}
				}
			} catch (Exception $ex) {
				ob_get_clean();
				if(getConf('flag_dev')==1){
//					print json_encode(array('msg'=>'SYSTEM: FATAL ERROR','error'=>$ex->getMessage()));
					print json_encode(array('err_code'=>MessageCode::ERR_100_SYSTEM_ERROR,'err_message'=>$ex->getMessage()));
				}else{
//					print json_encode(array('msg'=>'SYSTEM: FATAL ERROR'));
					print json_encode(array('err_code'=>MessageCode::ERR_100_SYSTEM_ERROR));
				}
				ob_end_flush();

				$err_msg = " [$ACTVAL] " . $ex->getMessage() . "\n";
				$err_msg.=$ex->getTraceAsString();
				quicklog("err-php", $err_msg."\n");
			}
		} else if ($ACTVAL == "") {
			if (file_exists(_ROOT_ . "/default.php")) {
				include(_ROOT_ . "/default.php");
			} else {
				//print "NO DEFAULT"._ROOT_ . "/default.php";
			}
			//$neuron = new Mega_Neuron_Server_Web();
			//$neuron->setCharset('UTF-8');
			////$neuron->setCharset('ISO8859-1');
			////$neuron->setDebugMode(true);
			////$neuron->setEnableGZIP(true);//shit, failed in Firefox in nginx(build by self) dunno why...
			//$neuron->start($rpc,self::$_conf_['ACTION']);
		}

		$_php_end_time = microtime();
		$_php_exec_time = $_php_end_time - $_php_start_time;
		if ($_php_exec_time > 1) {//for big execution time, wj log down exec time>1 second, TODO getConf("php_time_trigger_time")
			quicklog("php_time", "$ACTVAL=>\$_php_exec_time=" . $_php_exec_time);
		}
	}
}

