<?php
session_start();
if (!isset($_SESSION['userid'])) die;
$path = CLIENT_PATH."/css/images/";
header("Content-type: image/gif");
readfile($path.$_GET['f']);
