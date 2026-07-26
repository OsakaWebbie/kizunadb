<?php
include("functions.php");
session_start();
if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN
  die(json_encode(array("alert" => "NOSESSION")));
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
case 'SelectOrg':
  // Live name/furigana search for the Related Organizations picker (individual.php, batch_organization.php).
  $q = trim($_REQUEST['q'] ?? '');
  if (mb_strlen($q) < 2) die(json_encode(array('hits' => 0, 'rows' => array())));
  // Escape LIKE wildcards (literal %/_), then escape for SQL — same approach as Quicksearch.
  $like = h2d(str_replace(array('%', '_'), array('\%', '\_'), $q));
  $sql = "SELECT PersonID,FullName,Furigana FROM person".
    " WHERE Organization>0 AND (FullName LIKE '%".$like."%' OR Furigana LIKE '%".$like."%')".
    " ORDER BY Furigana";
  $result = sqlquery_checked($sql);
  $rows = array();
  while ($row = mysqli_fetch_object($result)) {
    $rows[] = array(
      'pid'  => (int)$row->PersonID,
      'name' => readable_name($row->FullName, $row->Furigana)   // "Name (reading)"; used for the row label and for #orgname. The row appends the ID separately.
    );
  }
  die(json_encode(array('hits' => count($rows), 'rows' => $rows)));
  break;
case 'OrgDetail':
  // Lazy per-org detail for the picker's info toggle: Categories + Address for one organization.
  $orgid = intval($_REQUEST['orgid'] ?? 0);
  if (!$orgid) die(json_encode(array('categories' => '', 'address' => '')));
  $sql = "SELECT household.PostalCode,postalcode.Prefecture,postalcode.ShiKuCho,household.Address,".
    "GROUP_CONCAT(DISTINCT category.Category ORDER BY category.Category SEPARATOR ', ') AS cats".
    " FROM person".
    " LEFT JOIN household ON person.HouseholdID=household.HouseholdID".
    " LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode".
    " LEFT JOIN percat ON percat.PersonID=person.PersonID".
    " LEFT JOIN category ON percat.CategoryID=category.CategoryID".
    " WHERE person.PersonID=$orgid GROUP BY person.PersonID";
  $result = sqlquery_checked($sql);
  if ($row = mysqli_fetch_object($result)) {
    $address = trim($row->PostalCode." ".$row->Prefecture.$row->ShiKuCho." ".$row->Address);
    die(json_encode(array('categories' => $row->cats ?? '', 'address' => $address)));
  }
  die(json_encode(array('categories' => '', 'address' => '')));
  break;
case 'Households':
  // Household picker: a search term narrows by label name or address; an empty term returns all households.
  $q = trim($_REQUEST['q'] ?? '');
  $like = h2d(str_replace(array('%', '_'), array('\%', '\_'), $q));
  $sql = "SELECT household.HouseholdID,household.NonJapan,household.PostalCode,household.Address,".
    "household.RomajiAddress,household.Phone,household.FAX,household.LabelName,".
    addr_comp_sql()." AS AddressDisplay".
    " FROM household LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode".
    ($q!=='' ? " WHERE household.LabelName LIKE '%".$like."%' OR ".addr_comp_sql()." LIKE '%".$like."%' OR household.Phone LIKE '%".$like."%'" : "").
    " ORDER BY household.LabelName";
  $result = sqlquery_checked($sql);
  $rows = array();
  while ($row = mysqli_fetch_object($result)) {
    $rows[] = array(
      'hhid'          => (int)$row->HouseholdID,
      'nonjapan'      => (int)$row->NonJapan,
      'postalcode'    => $row->PostalCode,
      'address'       => $row->Address,
      'romajiaddress' => $row->RomajiAddress,
      'phone'         => $row->Phone,
      'fax'           => $row->FAX,
      'labelname'     => $row->LabelName,
      'display'       => $row->AddressDisplay
    );
  }
  die(json_encode(array('hits' => count($rows), 'rows' => $rows)));
  break;
case 'PersonName':
  // readable name for any person/org by id (used by merge_duplicates.php id-entry preview)
  if (isset($_REQUEST['pid']) && $_REQUEST['pid']!="") {
    $result = sqlquery_checked("SELECT FullName,Furigana FROM person WHERE PersonID=".intval($_REQUEST['pid']));
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      echo readable_name($row->FullName,$row->Furigana,intval($_REQUEST['pid']));
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
  // Postal-code lookup for the db_settings editor. If local row is not found, kizuna_common.auxpostalcode
  // is searched, and if found, row is copied to postalcode table, so PostalCodeSave (an UPDATE) has a row to edit.
  $pc = mysqli_real_escape_string($db, $_REQUEST['pc'] ?? '');
  if ($pc != "") {
    $result = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='$pc'");
    if (mysqli_num_rows($result)==0) {
      $result = sqlquery_checked("SELECT * FROM kizuna_common.auxpostalcode WHERE PostalCode='$pc'");
      if (mysqli_num_rows($result)==0) {
        die(json_encode(array("alert" => _("Postal Code was not found - please double-check the number using the internet."))));
      } else {
        sqlquery_checked("INSERT INTO postalcode(PostalCode,Prefecture,ShiKuCho,Romaji)".
        " SELECT PostalCode,Prefecture,CONCAT(ShiKu,Cho),CONCAT(RomajiShiKuCho,', ',RomajiPref) FROM kizuna_common.auxpostalcode WHERE PostalCode='$pc'");
        $result = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='$pc'");
        if (mysqli_num_rows($result)==0) {
          die(json_encode(array("alert" => "Programming error: Failed to insert new Postal Code data.")));
        }
      }
    }
    $row = mysqli_fetch_object($result);
    $arr = array("prefecture" => $row->Prefecture, "shikucho" => $row->ShiKuCho);
    if ($_SESSION['romajiaddresses']) $arr["romaji"] = d2j($row->Romaji);
    die (json_encode($arr));
  }
  break;
case 'PostalCodeText':
  // Read-only postal-code lookup for edit.php: returns the client's row, or the shared kizuna_common.auxpostalcode row (flagged "fromaux").
  // Copying an aux code into the client postalcode table is deferred to do_edit.php, which inserts it only on save.
  $pc = mysqli_real_escape_string($db, $_GET['pc'] ?? '');
  if ($pc != "") {
    $result = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='$pc'");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      echo '{ "pref":"'.$row->Prefecture.'","shi":"'.$row->ShiKuCho.'"';
      if ($_SESSION['romajiaddresses']) echo ',"rom":"'.d2j($row->Romaji).'"';
      die('}');
    } elseif ($_GET['aux']) {
      $result = sqlquery_checked("SELECT * FROM kizuna_common.auxpostalcode WHERE PostalCode='$pc'");
      if (mysqli_num_rows($result)>0) {
        $row = mysqli_fetch_object($result);
        echo '{ "pref":"'.$row->Prefecture.'","shi":"'.$row->ShiKu.$row->Cho.'"';
        if ($_SESSION['romajiaddresses']) echo ',"rom":"'.$row->RomajiShiKuCho.', '.$row->RomajiPref.'"';
        die(',"fromaux":"yes"}');
      }
    }
    die('{ "alert":"PCNOTFOUND" }');
  }
  break;
case 'Category':
  if (isset($_REQUEST['catid']) && $_REQUEST['catid']!="") {
    $catid = intval($_REQUEST['catid']);
    $result = sqlquery_checked("SELECT c.*, COUNT(pc.CategoryID) AS percat_count FROM category c LEFT JOIN percat pc ON c.CategoryID=pc.CategoryID WHERE c.CategoryID=$catid GROUP BY c.CategoryID");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('catid' => $row->CategoryID, 'category' => $row->Category, 'usefor' => $row->UseFor, 'percat_count' => (int)$row->percat_count);
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case 'AType':
  if (isset($_REQUEST['atypeid']) && $_REQUEST['atypeid']!="") {
    $atypeid = intval($_REQUEST['atypeid']);
    $result = sqlquery_checked("SELECT atype.*, COUNT(ac.ActionTypeID) AS action_count FROM actiontype atype LEFT JOIN action ac ON atype.ActionTypeID=ac.ActionTypeID WHERE atype.ActionTypeID=$atypeid GROUP BY atype.ActionTypeID");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('atypeid' => $row->ActionTypeID, 'atype' => $row->ActionType,
      'atcolor' => $row->BGColor, 'attemplate' => $row->Template, 'action_count' => (int)$row->action_count);
      die (json_encode($arr));
    } else {
      die(json_encode(array('alert' => 'Record not found.')));
    }
  }
  break;
case 'DType':
  if (isset($_REQUEST['dtypeid']) && $_REQUEST['dtypeid']!="") {
    $dtypeid = intval($_REQUEST['dtypeid']);
    $result = sqlquery_checked("SELECT dt.*, (SELECT COUNT(*) FROM donation WHERE DonationTypeID=dt.DonationTypeID) AS donation_count, (SELECT COUNT(*) FROM pledge WHERE DonationTypeID=dt.DonationTypeID) AS pledge_count FROM donationtype dt WHERE dt.DonationTypeID=$dtypeid");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('dtypeid' => $row->DonationTypeID, 'dtype' => $row->DonationType, 'dtcolor' => $row->BGColor, 'donation_count' => (int)$row->donation_count, 'pledge_count' => (int)$row->pledge_count);
      die (json_encode($arr));
    } else {
      die(json_encode(array("alert" => "Record not found.")));
    }
  }
  break;
case 'Event':
  if (isset($_REQUEST['eventid']) && $_REQUEST['eventid']!="") {
    $eventid = intval($_REQUEST['eventid']);
    $result = sqlquery_checked("SELECT e.*, COUNT(a.EventID) AS attendance_count, MIN(a.AttendDate) AS attend_first, MAX(a.AttendDate) AS attend_last FROM event e LEFT JOIN attendance a ON e.EventID=a.EventID WHERE e.EventID=$eventid GROUP BY e.EventID");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_object($result);
      $arr = array('eventid' => $row->EventID, 'event' => $row->Event, 'eventstartdate' => $row->EventStartDate, 'eventenddate' => $row->EventEndDate, 'remarks' => $row->Remarks);
      $arr['usetimes'] = $row->UseTimes;
      $arr['attendance_count'] = (int)$row->attendance_count;
      $arr['attend_first'] = $row->attend_first;
      $arr['attend_last'] = $row->attend_last;
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
  // Escape LIKE wildcards so they're treated as literal characters, then properly escape for SQL
  $qs = str_replace(array('%', '_'), array('\%', '\_'), $_GET['qs']);
  $qs = h2d($qs);
  $sql = "SELECT count(DISTINCT person.PersonID) hits from person LEFT JOIN household ON person.HouseholdID=household.HouseholdID".
      " LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode".
      " WHERE ".quicksearch_where($qs);
    $result = sqlquery_checked($sql);
    $row = mysqli_fetch_object($result);
    die(json_encode(array('hits' => $row->hits)));
    break;
case 'Custom':
  if (isset($_REQUEST['sql']) && stripos($_REQUEST['sql'],'select')==0) {
    $result = sqlquery_checked($_REQUEST['sql']);
    die (json_encode(mysqli_fetch_all($result)));
  }
  break;

case 'Action':
  if (isset($_REQUEST['id']) && $_REQUEST['id']!="") {
    $actionid = intval($_REQUEST['id']);
    $result = sqlquery_checked("SELECT * FROM action WHERE ActionID=$actionid");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_assoc($result);
      die(json_encode($row));
    } else {
      die(json_encode(array('alert' => _('Record not found.'))));
    }
  }
  break;

case 'ActionDup':
  // Duplicate check for individual.php: does this person already have an identical action
  // (same type + date + description)? `exclude` skips the row being edited. \r is stripped on
  // both sides so a stored CRLF description still matches a LF one from the browser.
  $pid   = intval($_REQUEST['pid'] ?? 0);
  $atype = intval($_REQUEST['atype'] ?? 0);
  $date  = h2d($_REQUEST['date'] ?? '');
  $desc  = h2d(str_replace("\r", "", $_REQUEST['desc'] ?? ''));
  $exclude = intval($_REQUEST['exclude'] ?? 0);
  $sql = "SELECT ActionID FROM action WHERE PersonID=$pid AND ActionTypeID=$atype".
         " AND ActionDate='$date' AND REPLACE(Description, CHAR(13), '')='$desc'";
  if ($exclude) $sql .= " AND ActionID<>$exclude";
  $result = sqlquery_checked($sql);
  die(json_encode(array('dup' => mysqli_num_rows($result) > 0)));
  break;

case 'Donation':
  if (isset($_REQUEST['id']) && $_REQUEST['id']!="") {
    $donid = intval($_REQUEST['id']);
    $result = sqlquery_checked("SELECT * FROM donation WHERE DonationID=$donid");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_assoc($result);
      die(json_encode($row));
    } else {
      die(json_encode(array('alert' => _('Record not found.'))));
    }
  }
  break;

case 'Pledge':
  if (isset($_REQUEST['id']) && $_REQUEST['id']!="") {
    $pledgeid = intval($_REQUEST['id']);
    $result = sqlquery_checked("SELECT * FROM pledge WHERE PledgeID=$pledgeid");
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_assoc($result);
      die(json_encode($row));
    } else {
      die(json_encode(array('alert' => _('Record not found.'))));
    }
  }
  break;

case 'PledgesForPerson':
  if (isset($_REQUEST['pid']) && $_REQUEST['pid']!="") {
    $pid = intval($_REQUEST['pid']);
    $result = sqlquery_checked("SELECT PledgeID, PledgeDesc FROM pledge WHERE PersonID=$pid ORDER BY StartDate DESC");
    $pledges = [];
    while ($row = mysqli_fetch_assoc($result)) $pledges[] = $row;
    die(json_encode($pledges));
  }
  break;

case 'PledgeBalance':
  if (isset($_REQUEST['id']) && $_REQUEST['id']!="") {
    $pledgeid = intval($_REQUEST['id']);
    $sql = "SELECT pledge.Amount, pledge.TimesPerYear, pledge.StartDate, pledge.EndDate, ".
      "SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(pledge.TimesPerYear=0, ".
      "IF(CURDATE()<pledge.StartDate,0,1), pledge.TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(".
      "IF(pledge.EndDate='0000-00-00' OR CURDATE()<pledge.EndDate,CURDATE(), pledge.EndDate), '%Y%m'), ".
      "DATE_FORMAT(pledge.StartDate, '%Y%m'))))) AS Balance ".
      "FROM pledge LEFT JOIN donation ON pledge.PledgeID=donation.PledgeID ".
      "WHERE pledge.PledgeID=$pledgeid GROUP BY pledge.PledgeID";
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result)>0) {
      $row = mysqli_fetch_assoc($result);
      // Calculate months behind if negative balance
      $months = '';
      if ($row['Balance'] < 0 && $row['TimesPerYear'] > 0) {
        $monthsBehind = round((0 - $row['Balance']) / $row['Amount'] * 12 / $row['TimesPerYear']);
        $months = $monthsBehind;
      }
      die(json_encode(array('balance' => $row['Balance'], 'months' => $months)));
    } else {
      die(json_encode(array('alert' => _('Record not found.'))));
    }
  }
  break;

case 'PledgeDonationCount':
  if (isset($_REQUEST['id']) && $_REQUEST['id']!="") {
    $pledgeid = intval($_REQUEST['id']);
    $result = sqlquery_checked("SELECT COUNT(DonationID) AS count FROM donation WHERE PledgeID=$pledgeid");
    $row = mysqli_fetch_assoc($result);
    die(json_encode(array('count' => $row['count'])));
  }
  break;

case 'BatchPersonSearch':
  $results = [];
  if (!empty($_REQUEST['catid'])) {
    $catid = intval($_REQUEST['catid']);
    $sql = "SELECT person.PersonID, person.FullName, person.Furigana FROM person ".
           "INNER JOIN percat ON person.PersonID = percat.PersonID ".
           "WHERE percat.CategoryID = $catid ".
           "ORDER BY person.Furigana, person.PersonID";
  } elseif (isset($_REQUEST['q']) && strlen($_REQUEST['q']) >= 2) {
    $q = h2d($_REQUEST['q']);
    $sql = "SELECT person.PersonID, person.FullName, person.Furigana FROM person ".
           "WHERE person.FullName LIKE '%$q%' OR person.Furigana LIKE '%$q%' ".
           "ORDER BY person.Furigana, person.PersonID";
  } else {
    die(json_encode(array('results' => [])));
  }
  $result = sqlquery_checked($sql);
  while ($row = mysqli_fetch_object($result)) {
    $results[] = array('pid' => (int)$row->PersonID, 'name' => readable_name($row->FullName, $row->Furigana));
  }
  die(json_encode(array('results' => $results)));
  break;

default:
  die(json_encode(array('alert' => 'Programming error: NO REQUEST RECOGNIZED')));
}
?>
