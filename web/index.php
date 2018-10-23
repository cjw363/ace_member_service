<?php
define("_ROOT_",realpath(dirname(__FILE__)));
require_once("../conf/index.php");

$obj = Security::getInstance();
$code = $obj->getSecureCode();
if(!$code) $obj->genSecureCode();

Controller::run();
