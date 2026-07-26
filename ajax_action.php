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

case 'CategorySave':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $category = trim($_REQUEST['category'] ?? '');
  $usefor   = $_REQUEST['usefor'] ?? '';
  if ($category === '') {
    die(json_encode(array('success' => false, 'error' => _('Category name cannot be blank.'))));
  }
  if (!in_array($usefor, array('P', 'O', 'OP'))) {
    die(json_encode(array('success' => false, 'error' => _('Please choose an application.'))));
  }
  $category_esc = h2d($category);
  $usefor_esc   = h2d($usefor);
  if (($_REQUEST['catid'] ?? '') == 'new') {
    sqlquery_checked("INSERT INTO category (Category,UseFor) VALUES ('$category_esc','$usefor_esc')");
    $newid = mysqli_insert_id($db);
    die(json_encode(array('success' => true, 'catid' => $newid, 'category' => $category, 'usefor' => $usefor,
                          'message' => _('Category successfully added.'))));
  } else {
    $catid = intval($_REQUEST['catid']);
    sqlquery_checked("UPDATE category SET Category='$category_esc',UseFor='$usefor_esc' WHERE CategoryID=$catid");
    die(json_encode(array('success' => true, 'catid' => $catid, 'category' => $category, 'usefor' => $usefor,
                          'message' => _('Category successfully updated.'))));
  }
  break;

case 'CategoryDelete':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $catid = intval($_REQUEST['catid'] ?? 0);
  if ($catid < 1) {
    die(json_encode(array('success' => false, 'error' => _('Invalid category ID'))));
  }
  sqlquery_checked("DELETE FROM percat WHERE CategoryID=$catid");
  $percat_removed = mysqli_affected_rows($db);
  sqlquery_checked("DELETE FROM category WHERE CategoryID=$catid LIMIT 1");
  if (mysqli_affected_rows($db) == 1) {
    die(json_encode(array('success' => true, 'catid' => $catid,
                          'percat_removed' => $percat_removed,
                          'message' => _('Category successfully deleted.'))));
  }
  die(json_encode(array('success' => false, 'error' => _('Category not found.'))));
  break;

case 'EventSave':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $event = trim($_REQUEST['event'] ?? '');
  if ($event === '') {
    die(json_encode(array('success' => false, 'error' => _('Event name cannot be blank.'))));
  }
  $event_esc   = h2d($event);
  $startdate   = mysqli_real_escape_string($db, $_REQUEST['eventstartdate'] ?? '');
  $enddate     = empty($_REQUEST['eventenddate']) ? '0000-00-00' : mysqli_real_escape_string($db, $_REQUEST['eventenddate']);
  $active      = ($enddate === '0000-00-00' || strtotime($enddate) >= strtotime(date('Y-m-d'))) ? 1 : 0;
  $usetimes    = !empty($_REQUEST['usetimes']) ? 1 : 0;
  $remarks_esc = h2d($_REQUEST['remarks'] ?? '');
  if (($_REQUEST['eventid'] ?? '') == 'new') {
    sqlquery_checked("INSERT INTO event (Event,EventStartDate,EventEndDate,UseTimes,Remarks) VALUES ('$event_esc','$startdate','$enddate',$usetimes,'$remarks_esc')");
    $newid = mysqli_insert_id($db);
    die(json_encode(array('success' => true, 'eventid' => $newid, 'event' => $event, 'active' => $active,
                          'message' => _('Event successfully added.'))));
  } else {
    $eventid = intval($_REQUEST['eventid']);
    sqlquery_checked("UPDATE event SET Event='$event_esc',EventStartDate='$startdate',EventEndDate='$enddate',UseTimes=$usetimes,Remarks='$remarks_esc' WHERE EventID=$eventid");
    die(json_encode(array('success' => true, 'eventid' => $eventid, 'event' => $event, 'active' => $active,
                          'message' => _('Event information successfully updated.'))));
  }
  break;

case 'EventDelete':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $eventid = intval($_REQUEST['eventid'] ?? 0);
  if ($eventid < 1) {
    die(json_encode(array('success' => false, 'error' => _('Invalid event ID'))));
  }
  $mode               = $_REQUEST['mode'] ?? 'cascade';
  $attendance_handled = 0;
  if ($mode === 'reassign') {
    $new_eventid = intval($_REQUEST['new_eventid'] ?? 0);
    if ($new_eventid < 1) {
      die(json_encode(array('success' => false, 'error' => _('Please select an event to reassign to.'))));
    }
    sqlquery_checked("UPDATE attendance SET EventID=$new_eventid WHERE EventID=$eventid");
    $attendance_handled = mysqli_affected_rows($db);
  } else {
    sqlquery_checked("DELETE FROM attendance WHERE EventID=$eventid");
    $attendance_handled = mysqli_affected_rows($db);
  }
  sqlquery_checked("DELETE FROM event WHERE EventID=$eventid LIMIT 1");
  if (mysqli_affected_rows($db) == 1) {
    die(json_encode(array('success' => true, 'eventid' => $eventid,
                          'attendance_handled' => $attendance_handled,
                          'message' => _('Event successfully deleted.'))));
  }
  die(json_encode(array('success' => false, 'error' => _('Event not found.'))));
  break;

case 'DonationTypeSave':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $dtype = trim($_REQUEST['dtype'] ?? '');
  if ($dtype === '') {
    die(json_encode(array('success' => false, 'error' => _('Donation Type name cannot be blank.'))));
  }
  $dtype_esc = h2d($dtype);
  $dtcolor   = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $_REQUEST['dtcolor'] ?? 'FFFFFF'));
  if (strlen($dtcolor) !== 6) $dtcolor = 'FFFFFF';
  if (($_REQUEST['dtypeid'] ?? '') == 'new') {
    sqlquery_checked("INSERT INTO donationtype (DonationType,BGColor) VALUES ('$dtype_esc','$dtcolor')");
    $newid = mysqli_insert_id($db);
    die(json_encode(array('success' => true, 'dtypeid' => $newid, 'dtype' => $dtype, 'dtcolor' => $dtcolor,
                          'message' => _('Donation Type successfully added.'))));
  } else {
    $dtypeid = intval($_REQUEST['dtypeid']);
    sqlquery_checked("UPDATE donationtype SET DonationType='$dtype_esc',BGColor='$dtcolor' WHERE DonationTypeID=$dtypeid");
    die(json_encode(array('success' => true, 'dtypeid' => $dtypeid, 'dtype' => $dtype, 'dtcolor' => $dtcolor,
                          'message' => _('Donation Type successfully updated.'))));
  }
  break;

case 'DonationTypeDelete':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $dtypeid = intval($_REQUEST['dtypeid'] ?? 0);
  if ($dtypeid < 1) {
    die(json_encode(array('success' => false, 'error' => _('Invalid Donation Type ID'))));
  }
  $new_dtypeid          = intval($_REQUEST['new_dtypeid'] ?? 0);
  $prepend              = !empty($_REQUEST['prepend']);
  $prepend_text         = h2d($_REQUEST['prepend_text'] ?? '');
  $reassigned_donations = 0;
  $reassigned_pledges   = 0;
  if ($new_dtypeid > 0) {
    if ($prepend && $prepend_text !== '') {
      sqlquery_checked("UPDATE donation SET DonationTypeID=$new_dtypeid, Description=CONCAT('$prepend_text', Description) WHERE DonationTypeID=$dtypeid");
    } else {
      sqlquery_checked("UPDATE donation SET DonationTypeID=$new_dtypeid WHERE DonationTypeID=$dtypeid");
    }
    $reassigned_donations = mysqli_affected_rows($db);
    sqlquery_checked("UPDATE pledge SET DonationTypeID=$new_dtypeid WHERE DonationTypeID=$dtypeid");
    $reassigned_pledges = mysqli_affected_rows($db);
  }
  sqlquery_checked("DELETE FROM donationtype WHERE DonationTypeID=$dtypeid LIMIT 1");
  if (mysqli_affected_rows($db) == 1) {
    die(json_encode(array('success' => true, 'dtypeid' => $dtypeid,
                          'reassigned_donations' => $reassigned_donations,
                          'reassigned_pledges'   => $reassigned_pledges,
                          'message' => _('Donation Type successfully deleted.'))));
  }
  die(json_encode(array('success' => false, 'error' => _('Donation Type not found.'))));
  break;

case 'ActionTypeSave':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $atype = trim($_REQUEST['atype'] ?? '');
  if ($atype === '') {
    die(json_encode(array('success' => false, 'error' => _('Action Type name cannot be blank.'))));
  }
  $atype_esc      = h2d($atype);
  $atcolor        = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $_REQUEST['atcolor'] ?? 'FFFFFF'));
  if (strlen($atcolor) !== 6) $atcolor = 'FFFFFF';
  $attemplate_esc = h2d($_REQUEST['attemplate'] ?? '');
  if (($_REQUEST['atypeid'] ?? '') == 'new') {
    sqlquery_checked("INSERT INTO actiontype (ActionType,BGColor,Template) VALUES ('$atype_esc','$atcolor','$attemplate_esc')");
    $newid = mysqli_insert_id($db);
    die(json_encode(array('success' => true, 'atypeid' => $newid, 'atype' => $atype, 'atcolor' => $atcolor,
                          'message' => _('Action Type successfully added.'))));
  } else {
    $atypeid = intval($_REQUEST['atypeid']);
    sqlquery_checked("UPDATE actiontype SET ActionType='$atype_esc',BGColor='$atcolor',Template='$attemplate_esc' WHERE ActionTypeID=$atypeid");
    die(json_encode(array('success' => true, 'atypeid' => $atypeid, 'atype' => $atype, 'atcolor' => $atcolor,
                          'message' => _('Action Type successfully updated.'))));
  }
  break;

case 'ActionTypeDelete':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $atypeid = intval($_REQUEST['atypeid'] ?? 0);
  if ($atypeid < 1) {
    die(json_encode(array('success' => false, 'error' => _('Invalid Action Type ID'))));
  }
  $new_atypeid  = intval($_REQUEST['new_atypeid'] ?? 0);
  $prepend      = !empty($_REQUEST['prepend']);
  $prepend_text = h2d($_REQUEST['prepend_text'] ?? '');
  $reassigned   = 0;
  if ($new_atypeid > 0) {
    if ($prepend && $prepend_text !== '') {
      sqlquery_checked("UPDATE action SET ActionTypeID=$new_atypeid, Description=CONCAT('$prepend_text', Description) WHERE ActionTypeID=$atypeid");
    } else {
      sqlquery_checked("UPDATE action SET ActionTypeID=$new_atypeid WHERE ActionTypeID=$atypeid");
    }
    $reassigned = mysqli_affected_rows($db);
  }
  sqlquery_checked("DELETE FROM actiontype WHERE ActionTypeID=$atypeid LIMIT 1");
  if (mysqli_affected_rows($db) == 1) {
    die(json_encode(array('success' => true, 'atypeid' => $atypeid, 'reassigned' => $reassigned,
                          'message' => _('Action Type successfully deleted.'))));
  }
  die(json_encode(array('success' => false, 'error' => _('Action Type not found.'))));
  break;

case 'PostalCodeSave':
  if (!isset($_SESSION['userid'])) {
    die(json_encode(array('alert' => 'NOSESSION')));
  }
  $postalcode = h2d(trim($_REQUEST['postalcode'] ?? ''));
  $prefecture = h2d(trim($_REQUEST['prefecture'] ?? ''));
  $shikucho   = h2d(trim($_REQUEST['shikucho'] ?? ''));
  if ($postalcode === '' || $prefecture === '' || $shikucho === '') {
    die(json_encode(array('success' => false, 'error' => _('All fields must be filled in.'))));
  }
  $sql = "UPDATE postalcode SET Prefecture='$prefecture', ShiKuCho='$shikucho'";
  if ($_SESSION['romajiaddresses']) {
    $romaji = h2d(trim($_REQUEST['romaji'] ?? ''));
    $sql .= ", Romaji='$romaji'";
  }
  $sql .= " WHERE PostalCode='$postalcode'";
  sqlquery_checked($sql);
  if (mysqli_affected_rows($db) == 1) {
    die(json_encode(array('success' => true, 'message' => _('Postal Code data successfully updated.'))));
  }
  die(json_encode(array('success' => true, 'message' => _('No data was changed.'))));
  break;

default:
  die("Programming error: NO ACTION RECOGNIZED");
}
?>
