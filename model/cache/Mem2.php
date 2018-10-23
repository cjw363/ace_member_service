<?php
namespace cache;
use Memcache;

class Mem2 extends Memcache{
	protected $prefix;
	
	function __construct($prefix = 'memcache') {
		$this->prefix = $prefix;
	}
	
	function key($key) {
		return $this->prefix.'.'.$key;
	}

	function add($key,$value,$expiration = null){
		return parent::add($this->key($key), $value, false, $expiration);
	}

	function set($key, $data) {
		parent::set($this->key($key), $data);
	}

	function get($key) {
		return parent::get($this->key($key));
	}
	
	function getArray($key) {
		$rt = $this->get($key);
		if (!$rt) $rt = array();
		return $rt;
	}

	function delete($key) {
		parent::delete($this->key($key));
	}

	function chkMemServer($key){ //检查memcache是否可用
		return parent::add($this->key($key), true, false, 2);
	}

}