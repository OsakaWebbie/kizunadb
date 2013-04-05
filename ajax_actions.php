<?php
include("functions.php");
include("accesscontrol.php");

switch($_REQUEST['action']) {
case "DonationProc":
  if (!isset($_POST['proc_off']) || !isset($_POST['proc_on'])) die("Failed.");
  if ($_POST['proc_on'] != "") sqlquery_checked("UPDATE donation set Processed=1 WHERE DonationID IN (".$_POST['proc_on'].")");
  if ($_POST['proc_off'] != "") sqlquery_checked("UPDATE donation set Processed=0 WHERE DonationID IN (".$_POST['proc_off'].")");
  echo "*"._("Update successful.");
  break;
case "AttendDelete":
  if (!isset($_POST['ids']) || $_POST['ids']=="") die("Failed.");
  $text = "";
  $idarray = split(",",$_POST['ids']);
  foreach ($idarray as $id) {
    $piddate = split("_",$id);
    sqlquery_checked("DELETE FROM attendance WHERE EventID=".$_POST['eid']." AND PersonID=".$piddate[0]." AND AttendDate='".$piddate[1]."'");
    $text .= ",#".$id;
  }
  echo substr($text,1);
  break;
case "PerOrgDelete":
  if (!isset($_POST['memid']) || $_POST['orgid']=="") die("Failed.");
  sqlquery_checked("DELETE FROM perorg WHERE PersonID=".$_POST['memid']." AND OrgID=".$_POST['orgid']);
  if (mysql_affected_rows() == 1) echo "*"._("Delete successful.");
  else echo _("Record to delete was not found.");
  break;
default:
  die("Programming error: NO ACTION RECOGNIZED");
}
?>
