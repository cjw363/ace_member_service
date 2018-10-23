<?php

namespace cache;
use Exception;

/*	本类的写内存相关方法，如果 shmop_open 失败，则会抛出异常，
 *	若不需要这么严格，请进行捕捉处理
 * 
 * 本类使用一个数组来存储所有内存块的大小容量（结构为 {key:capacity}），再把这个数组
 * 写入到共享内存中，
 * 每次写内存的时候，会先取出这个数组，得到本次要写的内存块在上一次的大小，删除掉后再
 * 重新写入。
 * 读内存的时候，通过这个数组取得本次要读的内存块的大小，再根据这个大小把需要的数据从
 * 内存中读出来。
 * 通过这个数组，
 *		可以通过php代码删除掉所有在这个数组中存在的key所代表的内存块
 *		也可以通过这个数组来计算是否有key发生了碰撞
 */
class Shmop {

	//下面两个常量是一起用的，具体请看它们出现的代码

	const SHMOP_KEY = '_shmop_key_';
	const CAPACITY = 4000;

	protected $key;
	protected $prefix;
	
	//如果 shmop_open 失败，是否需要抛出异常的标志，采用递加形式，以应对一次执行过程
	//中需要多次设置这个标志的情况
	//因换了种逻辑，不再需要这个属性
//	protected $doNotThrowException = 0;
	
	function __construct($prefix = 'memcache') {
		$this->prefix = getConf('memcache_key_prefix');
	}
	
	//crc32 产生的数字，是有一定的碰撞率的，因此出现莫名其妙的数据错误时，
	//可以考虑一下是否发生碰撞了，详细请看
	//http://zh.wikipedia.org/wiki/%E5%BE%AA%E7%8E%AF%E5%86%97%E4%BD%99%E6%A0%A1%E9%AA%8C
	protected function crc32($key) {
		return crc32($this->prefix.'.'.$key);
	}

	/* shmop函数的特点:
	 * 如果前次写入的长度 〉下次写入的长度，则只覆盖前面的，多余的内容保留
	 * 如果前次写入的长度〈 下次写入的长度，则多余的内容会自动追加到后面
	 * 如果是打开一块已经存在的内存，则shomp_open的第三第四个参数必须是0
	 * set 函数的逻辑(ls代表前次写入的长度, s代表本次写入的长度):
	 * if (ls > s) {
	 *  delete_mem(key, ls)
	 *  write_mem(key, s)
	 *  sizes[key] = s
	 * } else {
	 *  write_mem(key, s)
	 *  sizes[key] = s
	 * }
	 */
	function set($key, $data) {
		self::checkKey($key);
		$this->key = $key;
		$key = $this->crc32($key);

		$sizes = $this->getSizes();
		$lastSize = $sizes[$this->key];
//		print_r($sizes);
		//if ( ($lastSize - $size > 0) ) {  //这个if条件不要了, 似乎不删除旧的内存块的话,久了总是出问题
		//第一次写这个key的内存，不需要删除
		if ($lastSize) {
			$this->remove($key);
		}

		$str = json_encode($data);
		$size = strlen($str);
//echo $size, '<br/>';
		$this->write($key, $str, $size);

		$sizes[$this->key] = $size;
		$this->saveSizes($sizes);
	}

	function get($key) {
		self::checkKey($key);
		$this->key = $key;
		$sizes = $this->getSizes();
		$rt = $this->read($this->crc32($key), $sizes[$key]);
		if ($rt === false) return $rt;
		return json_decode($rt, true);
	}
	
	//返回的保证至少是一个空数组
	function getArray($key) {
		$rt = $this->get($key);
		if ($rt === false) $rt = array();
		return $rt;
	}
	
	function delete($key) {
		self::checkKey($key);
		$this->key = $key;
		$sizes = $this->getSizes();
		unset($sizes[$this->key]);
		$this->saveSizes($sizes);
		return $this->remove($this->crc32($key));
	}
	
	//方便删除所有在 $sizes 中出现的 key 所指定的内存块
	//只允许手动调用，请尽量不要在程序中使用这个方法
	function deleteAll() {
		$sizes = $this->getSizes();
		foreach ($sizes as $key=>$size) {
			$this->delete($key);
		}
		$sizes = array();
		$this->saveSizes($sizes);
	}
	
	function saveSizes($sizes) {
		$data = json_encode($sizes);
		if (strlen($data) > self::CAPACITY) {
			$this->throwError('Shmop::saveSizes(), the size of data is out of Shmop::CAPACITY(' . self::CAPACITY . ')');
		}
		$this->write($this->crc32(self::SHMOP_KEY), json_encode($sizes), self::CAPACITY);
	}
	
	function getSizes() {
		$data = json_decode(
			$this->read($this->crc32(self::SHMOP_KEY), self::CAPACITY), true
		);
		if (!$data) $data = array();
		return $data;
	}
	
	//打开失败不抛出异常的 open 方法
	function openLoose($key, $flag = 'a', $mode = 0, $size = 0) {
		$shmid = shmop_open($key, $flag, $mode, $size);
		return $shmid;
	}
	
	//写入使用的 open, 打开失败抛出异常
	//参数说明请参看 shmop_open 
	function open($key, $flag, $mode, $size = 0) {
		$shmid = shmop_open($key, $flag, $mode, $size);
		if ($shmid === false) {
			$this->throwError('\cache\Shmop::open(), shmop_open failed, maybe the memory block is not existed,'
				. " key:$this->key($key), flag:$flag, mode:$mode, size:$size");
		}
		return $shmid;
	}

	//返回值情况请看 shmop_write
	//$data 必须是字符串
	function write($key, $data, $size, $offset = 0) {
		$data = str_pad($data, $size);
		//0644：创建共享内存的OS用户拥有读写权限，同组或其他用户只有读权限
		$shmid = $this->open($key, "c", 0644, $size);
		$returnSize = shmop_write($shmid, $data, $offset);		
		if ($returnSize != $size) {
			$this->throwError('\cache\Shmop::set(), $this->write with key (' 
				. $this->key . '), but the return size(' . json_encode($returnSize)
				. ') is not equal to data size(' . $size . ')');
		}
		shmop_close($shmid);
		return $returnSize;
	}
	
	//返回值要么是 false, 要么是字符串
	function read($key, $size, $start = 0) {
		$shmid = $this->openLoose($key);
		if ($shmid === false) return false;
		$data = shmop_read($shmid, $start, $size);
		shmop_close($shmid);
		return $data;
	}
	
	function remove($key) {
//		echo $key, '<br/>';
		$shmid = $this->openLoose($key);
		if ($shmid === false) return false;
//		echo $this->key, $shmid;
		$rt = shmop_delete($shmid);
		shmop_close($shmid);
//		echo json_encode($rt);
		return $rt;
	}
	
	static function checkKey($key) {
		if ($key == self::SHMOP_KEY)
			throw new Exception('the key can\'t be ' . self::SHMOP_KEY . ' which is used by system.');
	}

	function throwError($msg) {
		quicklog('err-shmop', $msg);
		throw new Exception($msg);
	}

}