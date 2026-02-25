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
      $result = sqlquery_checked("SELECT * FROM kizuna_common.auxpostalcode WHERE PostalCode='".$_REQUEST['pc']."'");
      if (mysqli_num_rows($result)==0) {
        die(json_encode(array("alert" => _("Postal Code was not found - please double-check the number using the internet."))));
      } else {
        sqlquery_checked("INSERT INTO postalcode(PostalCode,Prefecture,ShiKuCho,Romaji)".
        " SELECT PostalCode,Prefecture,CONCAT(ShiKu,Cho),CONCAT(RomajiShiKuCho,', ',RomajiPref) FROM kizuna_common.auxpostalcode WHERE PostalCode='".$_REQUEST['pc']."'");
        $result = sqlquery_checked("SELECT * FROM postalcode WHERE PostalCode='".$_REQUEST['pc']."'");
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
      $arr['active'] = $row->Active;
      $arr['usetimes'] = $row->UseTimes;
      die (json_encode($arr));
    } else {
      die(json_encode(array('alert' => 'Record not found.')));
    }
  }
  break;
case 'User':
  if (isset($_REQUEST['userid']) && $_REQUEST['userid']!="") {
    $sql = "SELECT user.*, YEAR(LoginTime) loginyear, MAX(LoginTime) loginlast, COUNT(LoginTime) loginnum ".
           "FROM user LEFT JOIN loginlog ON user.UserID=loginlog.UserID ".
           "WHERE user.UserID='".$_REQUEST['userid']."' ".
           "GROUP BY user.UserID, YEAR(LoginTime) ORDER BY YEAR(LoginTime) DESC";
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result)>0) {
      $arr = null;
      $totalLogins = 0;
      $yearStats = [];
      $lastLogin = null;

      while ($row = mysqli_fetch_object($result)) {
        if ($arr === null) {
          // Get user data from first row
          $arr = array('userid' => $row->UserID, 'new_userid' => $row->UserID, 'old_userid' => $row->UserID,
              'username' => $row->UserName, 'language' => $row->Language, 'new_pw1' => '', 'new_pw2' => '',
              'dashboard' => $row->Dashboard);
          $arr['admin'] = $row->Admin;
          $arr['hidedonations'] = $row->HideDonations;
          $lastLogin = $row->loginlast;
        }
        if ($row->loginyear !== null) {
          $totalLogins += $row->loginnum;
          $yearStats[] = $row->loginyear . ": " . $row->loginnum;
        }
      }

      // Build login stats string
      if ($lastLogin === null) {
        $arr['loginstats'] = _("Never logged in");
      } else {
        $loginStats = sprintf(_("Last login: %s"), $lastLogin);
        $loginStats .= " &bull; " . sprintf(_("Total: %d"), $totalLogins);
        if (count($yearStats) > 1) {
          $loginStats .= " (" . implode(", ", $yearStats) . ")";
        }
        $arr['loginstats'] = $loginStats;
      }

      die(json_encode($arr));
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
      " WHERE person.FullName LIKE '%".$qs."%' OR person.Furigana LIKE '%".$qs."%'".
      " OR person.Email LIKE '%".$qs."%' OR person.CellPhone LIKE '%".$qs."%'".
      " OR person.Country LIKE '%".$qs."%' OR person.URL LIKE '%".$qs."%'".
      " OR person.Remarks LIKE '%".$qs."%' OR person.Birthdate LIKE '%".$qs."%'".
      " OR household.AddressComp LIKE '%".$qs."%' OR household.RomajiAddressComp LIKE '%".$qs."%'".
      " OR household.Phone LIKE '%".$qs."%' OR household.LabelName LIKE '%".$qs."%'";
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
