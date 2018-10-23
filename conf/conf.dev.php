<?php

$_conf_self = array(
	'db_conf'=>array(
		'main'=>array(
			'host'=>'175.100.203.243:3751',
			'user'=>'dev',
			'password'=>'dev20131208',
		),
	),
	'mem_servers' => array(
		array('host'=>'175.100.203.243', 'port'=>12201)
	),
	'socket_servers'=>[
		'cash_deposit'=>'ws://175.100.203.243:9010',
		'cash_withdraw'=>'ws://175.100.203.243:9030',
		'receive_money'=>'ws://175.100.203.243:9050',
		'im'=>'ws://175.100.203.243:9080',
	],
	'login_try'=>99,
	'page_size'=>10,
	'flag_dev'=>1,
);