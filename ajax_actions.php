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

// Actions
case "ActionSave":
  $actionid = intval($_POST['id']);
  $pid = intval($_POST['PersonID']);
  $atype = intval($_POST['ActionTypeID']);
  $date = mysqli_real_escape_string($db, $_POST['ActionDate']);
  $desc = h2d($_POST['Description']);
  sqlquery_checked("UPDATE action SET PersonID=$pid, ActionTypeID=$atype, ActionDate='$date', Description='$desc' WHERE ActionID=$actionid");
  echo (mysqli_affected_rows($db) >= 0) ? "*"._("Changes saved.") : _("Failed to save.");
  break;

case "ActionDelete":
  $actionid = intval($_POST['id']);
  sqlquery_checked("DELETE FROM action WHERE ActionID=$actionid");
  echo (mysqli_affected_rows($db) == 1) ? "*"._("Deleted.") : _("Not found.");
  break;

// Donations
case "DonationSave":
  $donid = intval($_POST['id']);
  $pid = intval($_POST['PersonID']);
  $dtype = intval($_POST['DonationTypeID']);
  $pledgeid = intval($_POST['PledgeID']);
  $date = mysqli_real_escape_string($db, $_POST['DonationDate']);
  $amount = floatval(str_replace(',', '', $_POST['Amount']));
  $desc = h2d($_POST['Description']);
  $proc = isset($_POST['Processed']) ? 1 : 0;
  sqlquery_checked("UPDATE donation SET PersonID=$pid, DonationTypeID=$dtype, PledgeID=$pledgeid, DonationDate='$date', Amount=$amount, Description='$desc', Processed=$proc WHERE DonationID=$donid");
  echo (mysqli_affected_rows($db) >= 0) ? "*"._("Changes saved.") : _("Failed to save.");
  break;

case "DonationDelete":
  $donid = intval($_POST['id']);
  sqlquery_checked("DELETE FROM donation WHERE DonationID=$donid");
  echo (mysqli_affected_rows($db) == 1) ? "*"._("Deleted.") : _("Not found.");
  break;

// Pledges
case "PledgeSave":
  $pledgeid = intval($_POST['id']);
  $pid = intval($_POST['PersonID']);
  $ptype = intval($_POST['DonationTypeID']);
  $start = mysqli_real_escape_string($db, $_POST['StartDate']);
  $end = empty($_POST['EndDate']) ? '0000-00-00' : mysqli_real_escape_string($db, $_POST['EndDate']);
  $amount = floatval(str_replace(',', '', $_POST['Amount']));
  $tpy = intval($_POST['TimesPerYear']);
  $desc = h2d($_POST['PledgeDesc']);

  sqlquery_checked("UPDATE pledge SET PersonID=$pid, DonationTypeID=$ptype, StartDate='$start', EndDate='$end', Amount=$amount, TimesPerYear=$tpy, PledgeDesc='$desc' WHERE PledgeID=$pledgeid");
  echo (mysqli_affected_rows($db) >= 0) ? "*"._("Changes saved.") : _("Failed to save.");
  break;

case "PledgeDelete":
  $pledgeid = intval($_POST['id']);
  // Validation check is done in JavaScript before this is called
  sqlquery_checked("DELETE FROM pledge WHERE PledgeID=$pledgeid");
  echo (mysqli_affected_rows($db) == 1) ? "*"._("Deleted.") : _("Not found.");
  break;

default:
  die("Programming error: NO ACTION RECOGNIZED");
}
?>
