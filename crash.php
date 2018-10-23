<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
$content = "--" . date('ymd His') . ": [".$_REQUEST['ver']."]\r\n	".$_REQUEST['msg']."\r\n";
$file = "app-crash-" . date('Ymd').'.log';
file_put_contents("logs/$file", $content, FILE_APPEND);
