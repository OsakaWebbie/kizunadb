<?php
include("functions.php");
session_start();
if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN
  die(json_encode(array("alert" => _("Your login has timed out - please refresh the page."))));
}

switch($_REQUEST['req']) {
case "OrgName":
  if (isset($_REQUEST['orgid']) && $_REQUEST['orgid']!="") {
    $sql = "SELECT FullName,Furigana FROM person WHERE PersonID=".$_REQUEST['orgid']." AND Organization>0";
    $result = sqlquery_checked($sql) or die("SQL Error ".mysql_errno($db).": ".mysqli_error($db)."</b><br>".$sql);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      echo readable_name($row->FullName,$row->Furigana);
    }
  }
  break;
case "ContactTemplate":
  if (isset($_REQUEST['ctid']) && $_REQUEST['ctid']!="") {
    $result = sqlquery_checked("SELECT Template FROM contacttype WHERE ContactTypeID=".$_REQUEST['ctid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      echo $row->Template;
    }
  }
  break;
case "PC":
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
case "Category":
  if (isset($_REQUEST['catid']) && $_REQUEST['catid']!="") {
    $result = sqlquery_checked("SELECT * FROM category WHERE CategoryID=".$_REQUEST['catid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array("catid" => $row->CategoryID, "category" => $row->Category, "usefor" => $row->UseFor);
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case "CType":
  if (isset($_REQUEST['ctypeid']) && $_REQUEST['ctypeid']!="") {
    $result = sqlquery_checked("SELECT * FROM contacttype WHERE ContactTypeID=".$_REQUEST['ctypeid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array("ctypeid" => $row->ContactTypeID, "ctype" => $row->ContactType,
      "ctcolor" => $row->BGColor, "cttemplate" => $row->Template);
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case "DType":
  if (isset($_REQUEST['dtypeid']) && $_REQUEST['dtypeid']!="") {
    $result = sqlquery_checked("SELECT * FROM donationtype WHERE DonationTypeID=".$_REQUEST['dtypeid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array("dtypeid" => $row->DonationTypeID, "dtype" => $row->DonationType, "dtcolor" => $row->BGColor);
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case "Event":
  if (isset($_REQUEST['eventid']) && $_REQUEST['eventid']!="") {
    $result = sqlquery_checked("SELECT * FROM event WHERE EventID=".$_REQUEST['eventid']);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array("eventid" => $row->EventID, "event" => $row->Event, "eventstartdate" => $row->EventStartDate, "eventenddate" => $row->EventEndDate, "remarks" => $row->Remarks);
      //$arr["active"] = $row->Active ? "checkboxValue" : "";
      $arr["usetimes"] = $row->UseTimes ? "checkboxValue" : "";
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case "Login":
  if (isset($_REQUEST['userid']) && $_REQUEST['userid']!="") {
    $result = sqlquery_checked("SELECT * FROM user WHERE UserID='".$_REQUEST['userid']."'");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array("userid" => $row->UserID, "new_userid" => $row->UserID, "old_userid" => $row->UserID,
      "username" => $row->UserName, "language" => $row->Language, "new_pw1" => "", "new_pw2" => "",
	  "dashboardhead" => $row->DashboardHead, "dashboardbody" => $row->DashboardBody);
      $arr["admin"] = $row->Admin ? "checkboxValue" : "";
      $arr["hidedonations"] = $row->HideDonations ? "checkboxValue" : "";
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case "Single":
  if (isset($_REQUEST['sql']) && stripos($_REQUEST['sql'],"select")==0) {
    $result = sqlquery_checked(stripslashes($_REQUEST['sql']));
    $row = mysqli_fetch_array($result);
    die ($row[0]);
  }
  break;
case "Custom":
  if (isset($_REQUEST['sql']) && stripos($_REQUEST['sql'],"select")==0) {
    $result = sqlquery_checked($_REQUEST['sql']);
    $fields = mysqli_num_fields($result);
    $rows = mysqli_num_rows($result);
    while ($row_array = mysqli_fetch_row($result)) {
      for ($field=0; $field<$fields; $field++) {
        $arr[][mysqli_field_name($result,$field)] = $row_array[$field];
      }
    }
    die (json_encode($arr));
  }
  break;

default:
  die(json_encode(array("alert" => "Programming error: NO REQUEST RECOGNIZED")));
}
?>
