<?php
include("functions.php");
include("accesscontrol.php");

$criterialist = "<ul id=\"criteria\">";
$sql = "SELECT person.PersonID ";
$sql .= "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ";
$join = $where = "";
$ptable = $grouptable = "person";
$closing = '';

if (!isset($_REQUEST['filter'])) $_REQUEST['filter'] = 'Records';
if (!isset($_REQUEST['textinout1'])) $_REQUEST['textinout1'] = 'IN';
if (!isset($_REQUEST['texttarget1'])) $_REQUEST['texttarget1'] = 'Name';
if (!isset($_REQUEST['catinout1'])) $_REQUEST['catinout1'] = 'IN';
if (!isset($_REQUEST['actioninout1'])) $_REQUEST['actioninout1'] = 'IN';
if (!isset($_REQUEST['ctstartdate1'])) $_REQUEST['ctstartdate1'] = '';
if (!isset($_REQUEST['ctenddate1'])) $_REQUEST['ctenddate1'] = '';
if (!isset($_REQUEST['seqorder1'])) $_REQUEST['seqorder1'] = 'AFTER';
if (!isset($_REQUEST['donationinout1'])) $_REQUEST['donationinout1'] = 'IN';
if (!isset($_REQUEST['dtstartdate1'])) $_REQUEST['dtstartdate1'] = '';
if (!isset($_REQUEST['dtenddate1'])) $_REQUEST['dtenddate1'] = '';
if (!isset($_REQUEST['attendinout1'])) $_REQUEST['attendinout1'] = 'IN';
if (!isset($_REQUEST['astartdate1'])) $_REQUEST['astartdate1'] = '';
if (!isset($_REQUEST['aenddate1'])) $_REQUEST['aenddate1'] = '';
if (!isset($_REQUEST['blanktarget1'])) $_REQUEST['blanktarget1'] = '';
if (!isset($_REQUEST['freesql'])) $_REQUEST['freesql'] = '';

if (!empty($_REQUEST['qs'])) {
  // Escape LIKE wildcards so they're treated as literal characters, then properly escape for SQL
  $qs = str_replace(array('%', '_'), array('\%', '\_'), $_REQUEST['qs']);
  $qs = h2d($qs);
  $where .= " WHERE person.FullName LIKE '%".$qs."%' OR person.Furigana LIKE '%".$qs."%'".
      " OR person.Email LIKE '%".$qs."%' OR person.CellPhone LIKE '%".$qs."%'".
      " OR person.Country LIKE '%".$qs."%' OR person.URL LIKE '%".$qs."%'".
      " OR person.Remarks LIKE '%".$qs."%' OR person.Birthdate LIKE '%".$qs."%'".
      " OR household.AddressComp LIKE '%".$qs."%' OR household.RomajiAddressComp LIKE '%".$qs."%'".
      " OR household.Phone LIKE '%".$qs."%' OR household.LabelName LIKE '%".$qs."%'";
  $criterialist .= '<li>'.sprintf(_('Quick search: "%s" in any of multiple fields'), $_REQUEST['qs'])."</li>\n";
}
if ($_REQUEST['filter'] == "Organizations") {
  $where .= " WHERE Organization>0";
  $criterialist .= "<li>"._("Organizations only");
} elseif ($_REQUEST['filter'] == "People") {
  $where .= " WHERE Organization=0";
  $criterialist .= "<li>"._("People only (no organizations)");
} elseif ($_REQUEST['filter'] == "OrgsOfPeople") {
    $sql = "SELECT DISTINCT p1.*, h1.AddressComp, h1.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories ".
    "FROM person p1 LEFT JOIN household h1 ON p1.HouseholdID=h1.HouseholdID ".
    "LEFT JOIN percat ON p1.PersonID=percat.PersonID ".
    "LEFT JOIN category ON percat.CategoryID=category.CategoryID WHERE p1.PersonID IN (SELECT OrgID FROM perorg po ".
    "INNER JOIN person p2 ON po.PersonID=p2.PersonID LEFT JOIN household ON p2.HouseholdID=household.HouseholdID";
  $criterialist .= "<li>"._("Organizations with members who have the following criteria...");
  $ptable = "p2";
  $grouptable = "p1";
  $closing = ")";
} elseif ($_REQUEST['filter'] == "PeopleOfOrgs") {
    $sql = "SELECT DISTINCT p1.*, h1.AddressComp, h1.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories ".
    "FROM person p1 LEFT JOIN household h1 ON p1.HouseholdID=h1.HouseholdID ".
    "LEFT JOIN percat ON p1.PersonID=percat.PersonID ".
    "LEFT JOIN category ON percat.CategoryID=category.CategoryID WHERE p1.PersonID IN (SELECT po.PersonID FROM perorg po ".
    "INNER JOIN person o ON po.OrgID=o.PersonID LEFT JOIN household ON o.HouseholdID=household.HouseholdID";
  $criterialist .= "<li>"._("People whose related organizations have the following criteria...");
  $ptable = "o";
  $grouptable = "p1";
  $closing = ")";
}
for ($i=1; isset($_REQUEST["textinput".$i]); $i++) {
  if ($_REQUEST["textinput".$i] != "") {
    $search = str_replace("%","\%",h2d($_REQUEST["textinput".$i]));
    $target = $_REQUEST["texttarget".$i];
    $not = ($_REQUEST["textinout".$i]=="OUT") ? " NOT" : "";
    $where .= ($where!=""?" AND":" WHERE");
    $in = ($not=="") ? _("in") : _("not in");
    switch($target) {
    case "Name":
      $where .= "$not ($ptable.FullName LIKE '%".$search."%' OR $ptable.Furigana LIKE '%".$search."%' OR LabelName LIKE '%".$search."%')";
      if ($_SESSION['furiganaisromaji']) {
        $criterialist .= "<li>".sprintf(_("\"%s\" $in Name, Romaji, or Label"), $search)."</li>\n";
      } else {
        $criterialist .= "<li>".sprintf(_("\"%s\" $in Name, Furigana, or Label"), $search)."</li>\n";
      }
      break;
    case "Address":
      $where .= "$not (household.AddressComp LIKE '%".$search."%' "
      .($_SESSION['romajiaddresses']=="yes" ? "OR household.RomajiAddressComp LIKE '%".$search."%')" : ")");
      $criterialist .= "<li>".sprintf(_("\"%s\" $in Address"), $search)."</li>\n";
      break;
    case "Phone":
      $where .= "$not (household.Phone LIKE '%".$search."%' OR $ptable.CellPhone LIKE '%".$search."%' OR FAX LIKE '%".$search."%')";
      $criterialist .= "<li>".sprintf(_("\"%s\" $in Phone or FAX"), $search)."</li>\n";
      break;
    case "PersonID":
      $where .= "$not ($ptable.PersonID = ".$search.")";
      $criterialist .= "<li>".sprintf(_("\"%s\" $in Phone or FAX"), $search)."</li>\n";
      break;
    default:
      $where .= "$not ($ptable.$target LIKE '%".$search."%')";
      $criterialist .= "<li>".sprintf(_("\"%s\" $in %s"), $search, _($target))."</li>\n";
    }
  }
}

for ($i=1; !empty($_GET['catselect'.$i]); $i++) {
  $cats = implode(',',$_GET['catselect'.$i]);
  $not = ($_GET['catinout'.$i]=='OUT') ? ' NOT' : '';
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM percat WHERE CategoryID IN ($cats)))";
  $result = sqlquery_checked("SELECT Category FROM category WHERE CategoryID IN ($cats) ORDER BY Category");
  $catnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $catnames .= d2h($row->Category).', ';
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('In none of these categories: %s'), mb_substr($catnames,0,mb_strlen($catnames)-2))."</li>\n";
  } else {
    $criterialist .= '<li>'.sprintf(_('In at least one of these categories: %s'), mb_substr($catnames,0,mb_strlen($catnames)-2))."</li>\n";
  }
}

for ($i=1; !empty($_GET['ctselect'.$i]); $i++) {
  $cts = implode(',',$_GET['ctselect'.$i]);
  $not = ($_GET['actioninout'.$i]=="OUT") ? " NOT" : "";
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM action WHERE ActionTypeID IN ($cts)";
  if (!empty($_GET['ctstartdate'.$i])) $where .= " AND ActionDate >= '".$_GET['ctstartdate'.$i]."'";
  if (!empty($_GET['ctenddate'.$i])) $where .= " AND ActionDate <= '".$_GET['ctenddate'.$i]."'";
  $where .= '))';
  $result = sqlquery_checked("SELECT ActionType FROM actiontype WHERE ActionTypeID IN ($cts) ORDER BY ActionType");
  $ctnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $ctnames .= d2h($row->ActionType).', ';
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('Has none of these types of actions: %s'), mb_substr($ctnames,0,mb_strlen($ctnames)-2));
  } else {
    $criterialist .= '<li>'.sprintf(_('Has at least one of these types of actions: %s'), mb_substr($ctnames,0,mb_strlen($ctnames)-2));
  }
  if (!empty($_GET['ctstartdate'.$i]) && !empty($_GET['ctenddate'.$i])) $criterialist .= sprintf(_(', between %s and %s'),$_GET['ctstartdate'.$i],$_GET['ctenddate'.$i]);
  elseif (!empty($_GET['ctstartdate'.$i])) $criterialist .= sprintf(_(', on or after %s'),$_GET['ctstartdate'.$i]);
  elseif (!empty($_GET['ctenddate'.$i])) $criterialist .= sprintf(_(', on or before %s'),$_GET['ctenddate'.$i]);
  $criterialist .= "</li>\n";
}

for ($i=1; isset($_GET['seqctqual'.$i]) && isset($_GET['seqctelim'.$i]); $i++) {
  $qualcts = implode(',',$_GET['seqctqual'.$i]);
  $elimcts = implode(',',$_GET['seqctelim'.$i]);
  $minmax = ($_GET['seqorder'.$i]=='AFTER') ? 'MAX' : 'MIN';
  $operator = ($_GET["seqorder".$i]=="AFTER") ? ">" : "<";
  $join = " inner join (select pq.PersonID,$minmax(ActionDate) as qualdate from person pq".
  " inner join action aq on pq.PersonID = aq.PersonID where aq.ActionTypeID in ($qualcts) group by pq.PersonID) qual".
  " on $ptable.PersonID=qual.PersonID left outer join (select pe.personID,$minmax(ActionDate) as elimdate from person pe".
  " inner join action ae on pe.PersonID = ae.PersonID where ae.ActionTypeID in ($elimcts) group by pe.PersonID) elim".
  " on qual.PersonID=elim.PersonID";
  $where .= ($where==''?' WHERE ':' AND ')." (elim.elimdate is null or qual.qualdate $operator elim.elimdate)";
  $result = sqlquery_checked("SELECT ActionType FROM actiontype WHERE ActionTypeID IN ($qualcts) ORDER BY ActionType");
  $ctqualnames = "";
  while ($row = mysqli_fetch_object($result)) {
    $ctqualnames .= d2h($row->ActionType).", ";
  }
  $result = sqlquery_checked("SELECT ActionType FROM actiontype WHERE ActionTypeID IN ($elimcts) ORDER BY ActionType");
  $ctelimnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $ctelimnames .= d2h($row->ActionType).', ';
  }
  if ($_GET['seqorder'.$i]=='AFTER') {
    $criterialist .= '<li>'.sprintf(_('Has at least one action of type(s) [%s] and none later of type(s) [%s]'),
    mb_substr($ctqualnames,0,mb_strlen($ctqualnames)-2), mb_substr($ctelimnames,0,mb_strlen($ctelimnames)-2))."</li>\n";
  } else {
    $criterialist .= '<li>'.sprintf(_('Has at least one action of type(s) [%s] and none earlier of type(s) [%s]'),
    mb_substr($ctqualnames,0,mb_strlen($ctqualnames)-2), mb_substr($ctelimnames,0,mb_strlen($ctelimnames)-2))."</li>\n";
  }
}

for ($i=1; isset($_GET['dtselect'.$i]); $i++) {
  $dts = implode(',',$_GET['dtselect'.$i]);
  $not = ($_GET['donationinout'.$i]=="OUT") ? " NOT" : "";
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM donation WHERE DonationTypeID IN ($dts)";
  if ($_GET['dtstartdate'.$i]) $where .= " AND DonationDate >= '".$_GET['dtstartdate'.$i]."'";
  if ($_GET['dtenddate'.$i]) $where .= " AND DonationDate <= '".$_GET['dtenddate'.$i]."'";
  $where .= '))';
  $result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN ($dts) ORDER BY DonationType");
  $dtnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $ctnames .= d2h($row->DonationType).', ';
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('Has not donated any of these donation types: %s'), mb_substr($ctnames,0,mb_strlen($dtnames)-2));
  } else {
    $criterialist .= '<li>'.sprintf(_('Has donated at least one of these donation types: %s'), mb_substr($ctnames,0,mb_strlen($dtnames)-2));
  }
  if ($_GET['dtstartdate'.$i] && $_GET['dtenddate'.$i]) $criterialist .= sprintf(_(', between %s and %s'),$_GET['dtstartdate'.$i],$_GET['dtenddate'.$i]);
  elseif ($_GET['dtstartdate'.$i]) $criterialist .= sprintf(_(', on or after %s'),$_GET["dtstartdate".$i]);
  elseif ($_GET['dtenddate'.$i]) $criterialist .= sprintf(_(', on or before %s'),$_GET["dtenddate".$i]);
  $criterialist .= "</li>\n";
}

for ($i=1; isset($_GET['eventselect'.$i]); $i++) {
  $events = implode(',',$_GET['eventselect'.$i]);
  $not = ($_GET['attendinout'.$i]=='OUT') ? ' NOT' : '';
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM attendance WHERE EventID IN ($events)";
  if ($_GET['astartdate'.$i]) $where .= " AND AttendDate >= '".$_GET['astartdate'.$i]."'";
  if ($_GET['aenddate'.$i]) $where .= " AND AttendDate <= '".$_GET['aenddate'.$i]."'";
  $where .= '))';
  $result = sqlquery_checked("SELECT Event FROM event WHERE EventID IN ($events) ORDER BY Event");
  $eventnames = "";
  while ($row = mysqli_fetch_object($result)) {
    $eventnames .= d2h($row->Event).", ";
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('Has not attended any of these events: %s'), mb_substr($eventnames,0,mb_strlen($eventnames)-2));
  } else {
    $criterialist .= '<li>'.sprintf(_('Has attended one or more of these events: %s'), mb_substr($eventnames,0,mb_strlen($eventnames)-2));
  }
  if ($_GET['astartdate'.$i] && $_GET['aenddate'.$i]) $criterialist .= sprintf(_(', between %s and %s'),$_GET['astartdate'.$i],$_GET['aenddate'.$i]);
  elseif ($_GET['astartdate'.$i]) $criterialist .= sprintf(_(', on or after %s'),$_GET['astartdate'.$i]);
  elseif ($_GET['aenddate'.$i]) $criterialist .= sprintf(_(', on or before %s'),$_GET['aenddate'.$i]);
  $criterialist .= "</li>\n";
}

for ($i=1; isset($_GET['blanktarget'.$i]); $i++) {
  if ($_GET['blanktarget'.$i] != '') {
    $target = $_GET['blanktarget'.$i];
    $not = ($_GET['blankinout'.$i]=='OUT') ? ' NOT' : '';
    $where .= $where==''?' WHERE ':' AND ';
    switch($target) {
    case 'Birthdate':
      $where .= "$not $ptable.$target='0000-00-00'";
      break;
    case 'Address':
    case 'LabelName':
    case 'Phone':
    case 'FAX':
      $where .= "$not $target=''";
      break;
    default:
      $where .= "$not ($ptable.$target = '')";
    }
    if ($not) {
      $criterialist .= '<li>'.sprintf(_('"%s" is not blank'), _($target))."</li>\n";
    } else {
      $criterialist .= '<li>'.sprintf(_('"%s" is blank'), _($target))."</li>\n";
    }
  }
}

if (!empty($_GET['freesql'])) {
  $where .= ($where==''?' WHERE ':' AND ').$_GET['freesql'];
  $criterialist .= "<li>".$_GET['freesql']."</li>\n";
}

if (!empty($_GET['bucket']) && $_SESSION['bucket']) {
  $where .= ($where==''?' WHERE ':' AND ').$grouptable.'.PersonID IN ('.implode(',',$_SESSION['bucket']).')';
  $criterialist .= '<li>'._('In the Bucket')."</li>\n";
}

$sql .= $join . $where . $closing . " GROUP BY $grouptable.PersonID";
$criterialist .= "</ul>\n";

if (!$result = mysqli_query($db, $sql)) {
  header1(_('Error'));
  echo '<link rel="stylesheet" href="style.php" type="text/css" />';
  header2(1);
  echo $criterialist;
  echo "<div style=\"border: 2px solid darkred;background-color:#ffe0e0;color:darkred;padding-left:5px;margin:20px 0;\">$sql</div>";
  echo "<div style=\"font-weight:bold;margin:10px 0\">The query had an error:<br>".mysqli_errno($db).": ".mysqli_error($db)."</div>";
  exit;
}

if (mysqli_num_rows($result) == 0) {
  header("Location: search.php?text=".urlencode(_("Search resulted in no records.".($_SESSION['userid']=="dev"?urlencode("<pre>".$sql."</pre>"):""))));
  exit;
} elseif (mysqli_num_rows($result) == 1) {
  $person = mysqli_fetch_object($result);
  header("Location: individual.php?pid=".$person->PersonID);
  exit;
}
header1(_("Search Results").(!empty($_POST['preselected']) ? sprintf(_(" (%d People/Orgs Pre-selected)"),$psnum) : ""));

?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
<?php
header2(1);

// Build flextable options
require_once("flextable.php");

// Collect PersonIDs
$person_ids = [];
while ($row = mysqli_fetch_object($result)) {
  $person_ids[] = $row->PersonID;
}

$tableopt = (object)[
  'ids' => implode(',', $person_ids),
  'keyfield' => 'person.PersonID',
  'tableid' => 'searchresults',
  'heading' => '',
  'order' => 'Furigana',
  'cols' => []
];

$showcols = ',' . $_SESSION['list_showcols'] . ',';

// PersonID
$tableopt->cols[] = (object)[
  'key' => 'personid',
  'sel' => 'person.PersonID',
  'label' => _('ID'),
  'show' => (stripos($showcols, ',personid,') !== FALSE),
  'classes' => 'personid'
];

// Name (composite of FullName + Furigana with link and hidden Furigana for sorting)
$tableopt->cols[] = (object)[
  'key' => 'name',
  'sel' => 'person.Name',
  'label' => _('Name'),
  'show' => (stripos($showcols, ',name,') !== FALSE),
  'sort' => 1,
  'classes' => 'name-for-display'  // Used by CSV export to hide before export
];

// Furigana (clean, usable for both display and CSV)
$tableopt->cols[] = (object)[
  'key' => 'furigana',
  'sel' => 'person.Furigana',
  'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')),
  'show' => (stripos($showcols, ',furigana,') !== FALSE),
  'colsel' => TRUE
];

// FullName (clean, for CSV export - hidden, lazy loaded)
$tableopt->cols[] = (object)[
  'key' => 'fullname',
  'sel' => 'person.FullName',
  'label' => _('Full Name'),
  'show' => (stripos($showcols, ',fullname,') !== FALSE)
];

// Photo
$tableopt->cols[] = (object)[
  'key' => 'photo',
  'sel' => 'person.Photo',
  'label' => _('Photo'),
  'show' => (stripos($showcols, ',photo,') !== FALSE),
  'sortable' => false
];

// Phones (composite of household.Phone + person.CellPhone)
$tableopt->cols[] = (object)[
  'key' => 'phones',
  'sel' => 'Phones',
  'label' => _('Phones'),
  'show' => (stripos($showcols, ',phones,') !== FALSE),
  'table' => 'person'
];

// Email
$tableopt->cols[] = (object)[
  'key' => 'email',
  'sel' => 'person.Email',
  'label' => _('Email'),
  'show' => (stripos($showcols, ',email,') !== FALSE)
];

// Address - computed from postalcode + household data
$tableopt->cols[] = (object)[
  'key' => 'address',
  'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
  'label' => _('Address'),
  'show' => (stripos($showcols, ',address,') !== FALSE),
  'render' => 'multiline',
  'table' => 'person',
  'lazy' => FALSE
];

// Birthdate
$tableopt->cols[] = (object)[
  'key' => 'birthdate',
  'sel' => 'person.Birthdate',
  'label' => _('Born'),
  'show' => (stripos($showcols, ',birthdate,') !== FALSE),
  'classes' => 'center',
  'render' => 'birthdate'  // Handles 1900 prefix (show MM-DD only)
];

// Age (calculated from Birthdate)
$tableopt->cols[] = (object)[
  'key' => 'age',
  'sel' => "IF(person.Birthdate='0000-00-00' OR SUBSTRING(person.Birthdate,1,4)='1900', '', TIMESTAMPDIFF(YEAR, person.Birthdate, CURDATE()))",
  'label' => _('Age'),
  'show' => (stripos($showcols, ',age,') !== FALSE),
  'classes' => 'center',
  'render' => 'age'  // Hides age if birth year is 1900
];

// Sex
$tableopt->cols[] = (object)[
  'key' => 'sex',
  'sel' => 'person.Sex',
  'label' => _('Sex'),
  'show' => (stripos($showcols, ',sex,') !== FALSE)
];

// Country
$tableopt->cols[] = (object)[
  'key' => 'country',
  'sel' => 'person.Country',
  'label' => _('Country'),
  'show' => (stripos($showcols, ',country,') !== FALSE)
];

// URL
$tableopt->cols[] = (object)[
  'key' => 'url',
  'sel' => 'person.URL',
  'label' => _('URL'),
  'show' => (stripos($showcols, ',url,') !== FALSE),
  'render' => 'url'  // Applies url2link
];

// Remarks
$tableopt->cols[] = (object)[
  'key' => 'remarks',
  'sel' => 'person.Remarks',
  'label' => _('Remarks'),
  'show' => (stripos($showcols, ',remarks,') !== FALSE),
  'render' => 'remarks',  // Applies email2link and url2link
  'classes' => 'readmore',
  'lazy' => TRUE
];

// Categories (lazy loaded)
$tableopt->cols[] = (object)[
  'key' => 'categories',
  'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
  'label' => _('Categories'),
  'show' => (stripos($showcols, ',categories,') !== FALSE),
  'lazy' => TRUE,
  'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID'
];

// Events (lazy loaded)
$tableopt->cols[] = (object)[
  'key' => 'events',
  'sel' => "GROUP_CONCAT(DISTINCT Event ORDER BY Event SEPARATOR '\\n')",
  'label' => _('Events'),
  'show' => (stripos($showcols, ',events,') !== FALSE),
  'lazy' => TRUE,
  'join' => 'LEFT JOIN attendance ON person.PersonID=attendance.PersonID LEFT JOIN event ON attendance.EventID=event.EventID'
];

// Display heading and criteria list
echo '<div style="float:left; vertical-align:bottom">';
echo '<h3>' . sprintf(_('%d results of these criteria:'), count($person_ids)) . '</h3>';
echo $criterialist;
echo '</div>';
echo '<div style="clear:both"></div>';

flextable($tableopt);

footer();
