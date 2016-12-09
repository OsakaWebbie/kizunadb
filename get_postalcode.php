<?php
include("functions.php");
session_start();
if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN
  die("{ \"alert\":\"NOSESSION\" }");
}

if (isset($_GET['pc']) && $_GET['pc']!="") {
  $sql = "SELECT * FROM postalcode WHERE PostalCode='".$_GET['pc']."'";
//echo $sql."<br />";
  $result = sqlquery_checked($sql);
//echo mysqli_num_rows($result)." rows<br />";
  if (mysqli_num_rows($result)>0) {
//echo "found regular one, so will send it back<br />";
    $row = mysqli_fetch_object($result);
    echo "{ \"pref\":\"".$row->Prefecture."\",\"shi\":\"".$row->ShiKuCho."\"";
    if ($_SESSION['romajiaddresses']) echo ",\"rom\":\"".d2j($row->Romaji)."\"";
    die ("}");
  } elseif ($_GET['aux']) {
    $sql = "SELECT * FROM kizuna_common.auxpostalcode WHERE PostalCode='".$_GET['pc']."'";
//echo "gonna try aux - sql is:<br />$sql<br />";
    $result = sqlquery_checked($sql);
//echo mysqli_num_rows($result)." rows<br />";
    if (mysqli_num_rows($result)>0) {
//echo "found aux one, so will send it back<br />";
      $row = mysqli_fetch_object($result);
      echo "{ \"pref\":\"".$row->Prefecture."\",\"shi\":\"".$row->ShiKuCho."\"";
      if ($_SESSION['romajiaddresses']) echo ",\"rom\":\"\"";
      die(",\"fromaux\":\"yes\"}");
    }
  }
  echo "{ \"alert\":\"PCNOTFOUND\"}";
}
?>
