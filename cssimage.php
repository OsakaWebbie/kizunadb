<?php
session_start();
if (!isset($_SESSION['userid'])) die;
$path = "/var/www/".$_SESSION['client']."/css/images/";
header("Content-type: image/gif");
readfile($path.$_GET['f']);
