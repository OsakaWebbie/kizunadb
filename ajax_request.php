<?php
include("functions.php");
session_start();
if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN
  die(json_encode(array("alert" => _("Your login has timed out - please refresh the page."))));
}

switch($_REQUEST['req']) {
case 'OrgName':
  if (isset($_REQUEST['orgid']) && $_REQUEST['orgid']!="") {
    $sql = "SELECT FullName,Furigana FROM person WHERE PersonID=".$_REQUEST['orgid']." AND Organization>0";
    $result = sqlquery_checked($sql) or die("SQL Error ".mysqli_errno($db).": ".mysqli_error($db)."</b><br>".$sql);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      echo readable_name($row->FullName,$row->Furigana);
    }
  }
  break;
case 'ActionTemplate':
  if (isset($_REQUEST['atid']) && $_REQUEST['atid']!="") {
    $result = sqlquery_checked("SELECT Template FROM actiontype WHERE ActionTypeID=".$_REQUEST['atid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      echo $row->Template;
    }
  }
  break;
case 'PC':
  if (isset($_REQUEST['pc']) && $_REQUEST['pc']!="") {
    $result = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='".$_REQUEST['pc']."'");
    if (mysqli_num_rows($result)==0) {
      $aux = 1;
      $result = sqlquery_checked("SELECT * FROM auxpostalcode WHERE PostalCode='".$_REQUEST['pc']."'");
      if (mysqli_num_rows($result)==0) {
        die(json_encode(array("alert" => _("Postal Code was not found - please double-check the number using the internet."))));
      } else {
        sqlquery_checked("INSERT INTO postalcode(PostalCode,Prefecture,ShiKuCho)".
        " SELECT PostalCode,Prefecture,ShiKuCho FROM auxpostalcode WHERE PostalCode='".$_REQUEST['pc']."'");
        $result = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='".$_REQUEST['pc']."'");
        if (mysqli_num_rows($result)==0) {
          die(json_encode(array("alert" => "Programming error: Failed to insert new Postal Code data.")));
        }
      }
    }
    $row = mysqli_fetch_object($result);
    $arr = array("prefecture" => $row->Prefecture, "shikucho" => $row->ShiKuCho);
    if ($_SESSION['romajiaddresses'] && !$aux) $arr["romaji"] = d2j($row->Romaji);
    die (json_encode($arr));
  }
  break;
case 'Category':
  if (isset($_REQUEST['catid']) && $_REQUEST['catid']!="") {
    $result = sqlquery_checked("SELECT * FROM category WHERE CategoryID=".$_REQUEST['catid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('catid' => $row->CategoryID, 'category' => $row->Category, 'usefor' => $row->UseFor);
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case 'AType':
  if (isset($_REQUEST['atypeid']) && $_REQUEST['atypeid']!="") {
    $result = sqlquery_checked("SELECT * FROM actiontype WHERE ActionTypeID=".$_REQUEST['atypeid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('atypeid' => $row->ActionTypeID, 'atype' => $row->ActionType,
      'atcolor' => $row->BGColor, 'attemplate' => $row->Template);
      die (json_encode($arr));
    } else {
      die(json_encode(array('alert' => 'Record not found.')));
    }
  }
  break;
case 'DType':
  if (isset($_REQUEST['dtypeid']) && $_REQUEST['dtypeid']!="") {
    $result = sqlquery_checked("SELECT * FROM donationtype WHERE DonationTypeID=".$_REQUEST['dtypeid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('dtypeid' => $row->DonationTypeID, 'dtype' => $row->DonationType, 'dtcolor' => $row->BGColor);
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case 'Event':
  if (isset($_REQUEST['eventid']) && $_REQUEST['eventid']!="") {
    $result = sqlquery_checked("SELECT * FROM event WHERE EventID=".$_REQUEST['eventid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('eventid' => $row->EventID, 'event' => $row->Event, 'eventstartdate' => $row->EventStartDate, 'eventenddate' => $row->EventEndDate, 'remarks' => $row->Remarks);
      //$arr['active'] = $row->Active ? 'checkboxValue' : '';
      $arr['usetimes'] = $row->UseTimes ? 'checkboxValue' : '';
      die (json_encode($arr));
    } else {
      die(json_encode(array('alert' => 'Record not found.')));
    }
  }
  break;
case 'User':
  if (isset($_REQUEST['userid']) && $_REQUEST['userid']!="") {
    $result = sqlquery_checked("SELECT * FROM user WHERE UserID='".$_REQUEST['userid']."'");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('userid' => $row->UserID, 'new_userid' => $row->UserID, 'old_userid' => $row->UserID,
          'username' => $row->UserName, 'language' => $row->Language, 'new_pw1' => '', 'new_pw2' => '',
	        'dashboard' => $row->DashboardCode);
      $arr['admin'] = $row->Admin ? 'checkboxValue' : '';
      $arr['hidedonations'] = $row->HideDonations ? 'checkboxValue' : '';
      die (json_encode($arr));
    } else {
      die(json_encode(array('alert' => 'Record not found.')));
    }
  }
  break;
case 'Unique':
  if (empty($_REQUEST['table'])) die(json_encode(array('alert' => 'Programming error: Table does not exist')));
  $sql = 'SELECT DonationTypeID FROM '.$_REQUEST['table'].' WHERE';
  //$sql = 'SELECT '.(empty($_REQUEST['col'])?'*':$_REQUEST['col']).' FROM '.$_REQUEST['table'].' WHERE';
  $result = sqlquery_checked('SHOW KEYS FROM '.$_REQUEST['table']." WHERE Key_name = 'PRIMARY'");
  while ($key = mysqli_fetch_object($result)) {
    if (empty($_REQUEST[$key->Column_name])) die(json_encode(array('alert' => 'Programming error: AJAX Unique call lacks key value(s)')));
    $sql .= (substr($sql,-5)=='WHERE' ? ' ' : ' AND ').$key->Column_name."='".escape_quotes($_REQUEST[$key->Column_name])."'";
  }
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result)>0) {
    die (json_encode(mysqli_fetch_assoc($result)));
  } else {
    die(json_encode(array('alert' => 'Record not found.')));
  }
  break;
case 'Quicksearch':
  $qs = preg_replace('#[\'"%;]#','',$_GET['qs']);
  $sql = "SELECT count(DISTINCT person.PersonID) hits from person LEFT JOIN household ON person.HouseholdID=household.HouseholdID".
      " WHERE person.FullName LIKE '%".$qs."%' OR person.Furigana LIKE '%".$qs."%'".
      " OR person.Email LIKE '%".$qs."%' OR person.CellPhone LIKE '%".$qs."%'".
      " OR person.Country LIKE '%".$qs."%' OR person.URL LIKE '%".$qs."%'".
      " OR person.Remarks LIKE '%".$qs."%' OR person.Birthdate LIKE '%".$qs."%'".
      " OR household.AddressComp LIKE '%".$qs."%' OR household.RomajiAddressComp LIKE '%".$qs."%'".
      " OR household.Phone LIKE '%".$qs."%' OR household.LabelName LIKE '%".$qs."%'";
    $result = sqlquery_checked($sql);
    $row = mysqli_fetch_object($result);
    die ($row->hits);
    break;
case 'Custom':
  if (isset($_REQUEST['sql']) && stripos($_REQUEST['sql'],'select')==0) {
    $result = sqlquery_checked($_REQUEST['sql']);
    die (json_encode(mysqli_fetch_all($result)));
  }
  break;

default:
  die(json_encode(array('alert' => 'Programming error: NO REQUEST RECOGNIZED')));
}
?>
