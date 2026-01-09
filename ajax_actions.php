<?php
include("functions.php");
include("accesscontrol.php");

switch($_REQUEST['action']) {
case "DonationProc":
  if (!isset($_POST['checked_ids']) || !isset($_POST['unchecked_ids'])) die("Failed.");
  if ($_POST['checked_ids'] != "") sqlquery_checked("UPDATE donation set Processed=1 WHERE DonationID IN (".$_POST['checked_ids'].")");
  if ($_POST['unchecked_ids'] != "") sqlquery_checked("UPDATE donation set Processed=0 WHERE DonationID IN (".$_POST['unchecked_ids'].")");
  echo "*"._("Update successful.");
  break;
case "AttendDelete":
  if (!isset($_POST['ids']) || $_POST['ids']=="") die("Failed.");
  $text = "";
  $idarray = explode(",",$_POST['ids']);
  foreach ($idarray as $id) {
    $piddate = explode("_",$id);
    sqlquery_checked("DELETE FROM attendance WHERE EventID=".$_POST['eid']." AND PersonID=".$piddate[0]." AND AttendDate='".$piddate[1]."'");
    $text .= ",#".$id;
  }
  echo substr($text,1);
  break;
case "PerOrgDelete":
  if (!isset($_POST['memid']) || $_POST['orgid']=="") die("Failed.");
  sqlquery_checked("DELETE FROM perorg WHERE PersonID=".$_POST['memid']." AND OrgID=".$_POST['orgid']);
  if (mysqli_affected_rows($db) == 1) echo "*"._("Delete successful.");
  else echo _("Record to delete was not found.");
  break;
case "SwitchLang":
  if (!isset($_GET['lang']) || ($_GET['lang']!='en_US' && $_GET['lang']!='ja_JP')) die("Failed.");
  $_SESSION['lang'] = $_GET['lang'];
  setlocale(LC_ALL, $_SESSION['lang'].".utf8");
  break;
default:
  die("Programming error: NO ACTION RECOGNIZED");
}
?>
