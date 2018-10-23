<?php
	date_default_timezone_set('Asia/Ho_Chi_Minh');
	require_once('model/Security.php');
	$code = Security::genSecureCode();

	header("location: ./web/?_a=main&_u=$code");


