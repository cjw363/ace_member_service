<?php

$v = 1;

$name = "ace-member.apk";
$info = array(
	'name'=>$name,
	'code'=>$v,
	'url'=>"https://175.100.203.243:8761/apk/prod/$name"
);
$info['msg']="
ver 1.0.1
1、说明文字

";
echo json_encode($info);