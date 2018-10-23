<?php

namespace cache;

class Factory {

	static $caches = array();

	/**
	 * @param null $type
	 * @param null $prefix
	 * @param null $memServers 格式： array(array('host' => '127.0.0.1', 'port' => 11211));
	 * @return bool
	 */
	static function getCache($type = null, $prefix = null, $memServers=array()) {
		if ($type === null) $type = getConf('cache_type');
		if ($prefix === null) $prefix = getConf('memcache_key_prefix');
		if(empty($memServers)) $memServers = getConf('mem_servers');
		if (!isset(self::$caches[$type])) {
			switch ($type) {
				case 'memcached':
					if(!class_exists('Memcached',false)){
						return false;
					}
					self::$caches[$type] = new Mem($memServers, $prefix);
					break;

				case 'memcache':
					if(!class_exists('Memcache',false)){
						return false;
					}
					//这种类型只用于本地开发用途
					$cache = new Mem2($prefix);
					foreach ($memServers as $server) {
						$host = $server['host'];
						$port = $server['port'];
						$cache->addServer($host, $port);
					}
					self::$caches[$type] = $cache;
					break;
					
				case 'shmop':
					self::$caches[$type] = new Shmop($prefix);
					break;
			}
		}
		return self::$caches[$type];
	}


}