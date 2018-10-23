<?php
namespace cache;
use Memcached;
//Memcached 的封装
class Mem {

	const LOCK_EXPIRATION = 60; //锁的过期时间，单位：秒

	protected $cache;
	protected $prefix;

	function __construct($serversConf, $prefix = 'memcache') {
		$this->cache = new Memcached();
		$this->cache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_JSON_ARRAY); //序列化器
		$this->cache->setOption(Memcached::OPT_TCP_NODELAY, true); //启用tcp_nodelay
		$this->cache->setOption(Memcached::OPT_NO_BLOCK, true); //启用异步IO
		$this->cache->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT); //分布式策略
		$this->cache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true); //分布式服务组分散.推荐开启
		
		$this->cache->addServers($serversConf);
		$this->prefix = $prefix;
	}
	
	function key($key) {
		return $this->prefix.'.'.$key;
	}

	function add($key,$data, $expiration = null){
		$rt = $this->cache->add($this->key($key), $data, $expiration);
		if ($rt === false) {
			$code = $this->cache->getResultCode();
			$this->throwError($code,$key,'add',$data); //如果key存在，则$code会得到RES_NOTSTORED，写入log
			return false;
		}
		return true;
	}

	function set($key, $data, $expiration=null) {
		$rt = $this->cache->set($this->key($key), $data,$expiration);
		if ($rt === false) {
			$code = $this->cache->getResultCode();
			$this->throwError($code,$key,'set',$data);
		}
	}

	function get($key) {
		$rt = $this->cache->get($this->key($key));
		if ($rt === false) {
			$code = $this->cache->getResultCode();
			if ($code == Memcached::RES_SUCCESS) {
				return $rt;
			} else {
				$this->throwError($code,$key,'get');
			}
		}
		return $rt;
	}

	function getArray($key) {
		$rt = $this->get($key);
		if (!$rt)
			$rt = array();
		return $rt;
	}

	function delete($key) {
		$rt = $this->cache->delete($this->key($key));
		if ($rt === false) {
			$code = $this->cache->getResultCode();
			if ($code != Memcached::RES_NOTFOUND) {
				$this->throwError($code,$key,'delete');
			}
		}
	}

	//加锁，成功返回 true, 失败返回 false
	function lock($key) {
		return $this->cache->add($this->key($key), true, self::LOCK_EXPIRATION);
	}

	//释放锁，成功返回 true, 失败返回 false
	function unlock($key) {
		return $this->cache->delete($this->key($key));
	}

	function throwError($code,$key,$op,$data=null) {
		$a = new \ReflectionClass('Memcached');
		$cs = $a->getConstants();
		$msg = "Exception:";
		foreach ($cs as $c => $v) {
			if ($code == $v && substr($c, 0, 4) == 'RES_')
				$msg .= "  Memcached::$c";
		}
		$str = json_encode($data);
		quicklog('err-memcached', "$msg( when $op $key, related Data: $str )");
		//throw new \Exception($msg, $code); //不抛出异常，防止影响程序正常运行
	}

	function chkMemServer($key){ //检查memcache是否可用
		return $this->add($key, true, 2);
	}

}