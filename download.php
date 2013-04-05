<?
include("functions.php");
include("accesscontrol.php");

if ($_POST['csvfile']) {
  // CSV FILE OF DONATION DATA
  header("Content-type: application/octet-stream");
  header("Content-Disposition: attachment; filename=\"".pathinfo($_SERVER['HTTP_REFERER'],PATHINFO_FILENAME)."_table.csv\"");
  $data=stripcslashes($_POST['csvtext']);
  //echo mb_convert_encoding($data, "SJIS", "UTF-8");
  echo "\xEF\xBB\xBF".$data;

} elseif ($_GET['uid']) {
  // UPLOADED FILES RECORDED IN DATABASE
  $result = sqlquery_checked("SELECT * FROM upload WHERE UploadID=".$_GET['uid']);
  if (mysql_numrows($result) == 0)  die("Upload record not found for UploadID ".$_GET['uid'].".");
  $upd = mysql_fetch_object($result);
  $ext = strtolower(pathinfo($upd->FileName, PATHINFO_EXTENSION));
  $result = sqlquery_checked("SELECT * FROM uploadtype WHERE Extension='$ext'");
  if (mysql_numrows($result) == 0)  die("File extension '$ext' not found in table of approved file types - query was:<br>".
  "SELECT * FROM uploadtype WHERE Extension='$ext'");
  $type = mysql_fetch_object($result);

  $filepath = "/var/www/".$_SESSION['client']."/uploads/u".$_GET['uid'].".$ext";
  //echo "<pre>".print_r($_SESSION,TRUE);
  //die($filepath);
  if (!is_file($filepath)) {
    header("Location: individual.php?pid=".$upd->PersonID."&msg=".urlencode("File '".$filepath."' is not found.")."#uploads");
    exit;
  }
  if (!is_readable($filepath)) {
    header("Location: individual.php?pid=".$upd->PersonID."&msg=".urlencode("File '".$filepath."' cannot be read.")."#uploads");
    exit;
  }
  if (!$type->InBrowser)  header('Content-Disposition: attachment; filename="'.$upd->FileName.'"');
  header("Content-Type: ".$type->MIME);
  if ($type->BinaryFile)  header("Content-Transfer-Encoding: binary");
  if (!@readfile($filepath)) {
    header("Location: individual.php?pid=".$upd->PersonID."&msg=".urlencode("Failure reading file '".$filepath."'.")."#uploads");
    exit;
  }
}
?>