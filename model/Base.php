<?php

class Base {

	protected $dbName;
	protected $db;
	protected $pageSize;

	public function __construct($dbName = null) {
		if (!$dbName) throw new Exception('Base::__construct Parameter Error DBName');

		$this->pageSize = getConf('page_size');
		$this->dbName = $dbName;
		$this->db = getDB($dbName);
	}

	protected function execute($sql) {
		return $this->db->execute($sql);
	}

	protected function getArray($sql) {
		$rs = $this->execute($sql);
		if (!$rs || $rs->EOF) return array();
		return $rs->getArray();
	}

	public function affected_rows() {
		return $this->db->affected_rows();
	}

	protected function insert_id() {
		return $this->db->Insert_ID();
	}

	public function getLine($sql) {
		return $this->db->getLine($sql);
	}

	protected function getPageArray($sql, $page, $size = null) {
		$total = 0;
		if (!$page) $page = 1;
		if (!isset($size)||$size==0) $size = $this->pageSize;
		$rs = $this->db->PageExecute($sql, $size, $page, $total);
		return array("total" => $total, "page" => $page * ($total > 0), "size" => $size, "list" => $rs->getArray());
	}

	/**
	 * 检查记录是否存在
	 */
	public function hasRecord($table, $array) {
		$sql = "select 1 from $table where 1";
		foreach ($array as $k => $v) {
			$sql .= " and $k=" . qstr($v);
		}
		return $this->getOne($sql);
	}

	public function isDuplicated($table, $array) {
		$sql = "select 1 from $table where 1";
		foreach ($array as $k => $v) {
			$sql .= " and $k=" . qstr($v);
		}
		return $this->getOne($sql);
	}

	protected function getOne($sql, $allowHtml = false) {
		$rt = $this->db->getOne($sql);
		if ($rt && !$allowHtml) $rt = htmlspecialchars($rt, ENT_NOQUOTES);
		return $rt;
	}

	public function loopRows($sql, $callback, $flag = 1) {
		return $this->db->loopRows($sql, $callback, $flag);
	}

	protected function getDBName() {
		return $this->dbName;
	}

	protected function myUpdateBlobFile($table, $column, $path, $where, $blobType = '') {
		return $this->db->myUpdateBlobFile($table, $column, $path, $where, $blobType);
	}

}
