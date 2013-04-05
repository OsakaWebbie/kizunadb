<?
session_start();
if (!isset($_SESSION['userid'])) die;
$mime_map = array(
  'gif' => 'image/gif',
  'ico' => 'image/x-ico',
  'jpg' => 'image/jpeg',
  'png' => 'image/png',
);
$fullpath = "/var/www/".$_SESSION['client']."/".$_GET['f'];
if(!is_file($fullpath)) {  //if there is no client version, check for a default
  if (is_file($_GET['f'])) {
    $fullpath = $_GET['f'];  //found default (relative path), so set path to that
  } else {
    die("Bad path: ".$fullpath);
  }
}
$modified = gmdate('D, d M Y H:i:s',filemtime($fullpath)).' GMT';
if ($_SERVER['HTTP_IF_MODIFIED_SINCE'] && $_SERVER['HTTP_IF_MODIFIED_SINCE']==$modified) {
  header('HTTP/1.1 304 Not Modified');
  exit;
}
header("Content-type: ".$mime_map[strtolower(pathinfo($_GET['f'], PATHINFO_EXTENSION))]);
header("Last-Modified: $modified");
readfile($fullpath);
