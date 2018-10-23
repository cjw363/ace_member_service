<?php

$_conf_self = array(
	'db_conf'=>array(
		'main'=>array(
			'host'=>'127.0.0.1:3751',
			'user'=>'dev',
			'password'=>'dev88',
		),
	),
	'mem_servers' => array(
		array('host' => '127.0.0.1', 'port' => 11211)
	),
	'socket_servers'=>[
		'cash_deposit'=>'ws://175.100.203.243:9010',
		'cash_withdraw'=>'ws://175.100.203.243:9030',
		'im'=>'ws://175.100.203.243:9080',
	],
	'SERVER_TIMEZONE'=>'Asia/Hong_Kong',
	'flag_timezone_hk'=>1,
	'login_try'=>99,
	'page_size'=>10,
	'cache_type' => 'memcache',
	'flag_dev'=>1,
);