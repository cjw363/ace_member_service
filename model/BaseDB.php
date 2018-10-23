<?php
/**
 * 20081224 Wrapper for the database access
 * 20101112 Support read/write split, NO transaction, Support mysql(dbtype) ONLY. (FOR 234VN PROJECT)
 */
include_once(_LIB_ . "/lib.adodb5.strip/adodb.inc.php");
class BaseDB {
	private $_db = null;
	private $_db_name = "";
	private $_affected_rows = 0;
	private static $_instance = array();
	private $_db_host = '';

	function __construct($dns,$dbConf) {
		if(!$dbConf) $dbConf = getConf('db_conf');
		if ($dbConf) {
			$dbMain = $dbConf["main"];
			if ($dbMain == null) throw new Exception("BaseDB::BaseDB($dns) Error, Config Error for \$dbConf[main]");
			if (!isset($this->_db) || $this->_db == null) {
				$this->_db = self::_getDB($dbMain["host"], $dbMain["user"], $dbMain["password"], "db_" . $dns);
				$this->_db_host = $dbMain['host'];
				$this->_db_name = "db_" . $dns;
			}
		}
		if (!isset($this->_db) || !$this->_db || $this->_db == null) throw new Exception("BaseDB::BaseDB($dns) Error, Initialize Connection Fail");
	}

	public static function _getDB($host, $user, $pwd, $name) {
		if($host&&$user&&$pwd){
			$db = NewADOConnection("mysql");
			if (!$db) {
				throw new Exception("BaseDB::_getDB($host,$user,$pwd,$name) Error, Get NewADOConnection for MYSQL Error");
			} else {
				$db->Connect($host, $user, $pwd, $name, true);
				$db->Execute("set names utf8");
				$db->SetFetchMode(ADODB_FETCH_ASSOC);
			}
			if ($db->errorMsg()) {
				$errNo = $db->errorNo();
				quicklog('err-db', "BaseDB::_getDB($host, $name) Failed, Error No: $errNo" . '   ' . $db->errorMsg());
				throw new Exception(" BaseDB::_getDB($host, $name) Failed, Error No:$errNo"); //$db->errorMsg() 如果返回的信息是中文时整条异常信息无法显示
			}
		}else{//错误连接
			quicklog('err-db', "BaseDB::_getDB($host, $name) Failed, Invalid Params");
			$db = new ADODB_mysql();
		}

		return $db;
	}

	/**
	 * 由于同时可能连接多台服务器（可能有不同的host，相同的db name），所以这里需要添加上host的标记
	 * @param $dns
	 * @param $conf
	 * @return mixed
	 */
	public static function getInstance($dns,$dbConf) {
		if(!$dbConf) $dbConf = getConf('db_conf');
		$dbMain = $dbConf["main"];
		$dbHost = $dbMain['host'];
		$insKey = $dbHost.':'.$dns;
		if (!self::$_instance[$insKey]) {
			self::$_instance[$insKey] = new BaseDB($dns,$dbConf);
		}
		return self::$_instance[$insKey];
	}

	function affected_rows() {
		return $this->_affected_rows;
	}

	function logSQL($sql, $result, $time, $flag) {
		$AUID = $_SESSION["UID"];
		$REMOTE_ADDR = @$_SERVER["REMOTE_ADDR"];
		$REMOTE_HOST = getenv("HTTP_X_FORWARDED_FOR");
		$sql = trim($sql);
		$flagSelect = strtoupper(substr(ltrim($sql), 0, 6)) == "SELECT";
		if (!$flagSelect) {
			$this->_affected_rows = $this->_db->Affected_Rows();
			$s = "\n $sql\n return" . (($result) ? ("=" . $this->_affected_rows) : (" " . $this->_db->ErrorMsg())) . ", " . ($time) . ", /" . $REMOTE_ADDR . "/" . $REMOTE_HOST . "\n";
			if ($AUID != "") {
				quicklog("sql-$AUID", $s);
			}
			quicklog("sql", $s);
		}
		if (!$result) {
			$msg = $this->_db->ErrorMsg();
			quicklog("err-sql", ' '.$msg . "\n $sql\n");
			if(getConf('flag_dev')==1){
				throw new Exception("BaseDB::execute/pageExecute SQL Error. $msg");
			}else{
				throw new Exception("SQL Error.");
			}
		} else {
			if ($time > 2) quicklog("time_sql-$AUID", ' '.$time . "\n $sql\n");
		}
	}

	function getLine($sql, $flag = 1) {
		$result = $this->Execute($sql, $flag);
		if ($result != false) {
			if (!$result->EOF) {
				$result = $result->fields;
			} else {
				$result = null;
			}
		}
		return $result;
	}

	/** $flag 1 表示使用 master 的数据库连接 */
	function execute($sql, $flag = 1) {
		return $this->_execute($sql, $flag);
	}

	/** $flag 1 表示使用 master 的数据库连接 */
	private function _execute($sql, $flag = 1) {
		$time = microtime(true);
		$flagSelect = strtoupper(substr(ltrim($sql), 0, 6)) == "SELECT";
		$result = $this->_db->Execute($sql);
		$time = microtime(true) - $time;
		$this->logSQL($sql, $result, $time, $flag);
		return $result;
	}

	/** $flag 1 表示使用 master 的数据库连接 */
	function getOne($sql, $flag = 1) {
		$result = $this->Execute($sql, $flag);
		if ($result != false) {
			if (!$result->EOF && count($result->fields) > 0) {
				$result = array_pop($result->fields);
			} else {
				$result = null;
			}
		}
		return $result;
	}

	function pageExecute($sql, $pageSize, &$page, &$total) {
		$time = microtime(true);
		$result = $this->_db->PageExecute($sql, $pageSize, $page);
		if ($result) {
			$total = $result->_maxRecordCount;
			$page = $result->AbsolutePage();
		} else {
			$total = 0;
			$page = 0;
		}
		$time = microtime(true) - $time;
		$this->logSQL($sql, $result, $time, '');
		return $result;
	}

	function insert_id() {
		return $this->_db->Insert_ID();
	}

	function getName() {
		return $this->_db_name;
	}

	function getDBHost() {
		return $this->_db_host;
	}

	function myUpdateBlobFile($table, $column, $path, $where, $blobtype) {
		return $this->_db->UpdateBlobFile($table, $column, $path, $where, $blobtype);
	}

	public function loopRows($sql, $callback, $flag = 1) {
		$result = $this->Execute($sql, $flag);
		$cnt = 0;
		while(!$result->EOF && $row = $result->fields){
			call_user_func($callback, $row, $cnt++);
			$result->MoveNext();
		}
	}

}
