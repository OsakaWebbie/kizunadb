<?
session_start();
if (!isset($_SESSION['userid'])) die;
$path = "/var/www/".$_SESSION['client']."/photos/";
//die($path.$_GET['f'].".jpg");
header("Content-type: image/jpeg");
readfile($path.(is_file($path.$_GET['f'].".jpg") ? $_GET['f'] : "missing_file").".jpg");

