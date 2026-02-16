<?php
include("functions.php");
include("accesscontrol.php");

$ajax = !empty($_GET['ajax']);

if (!$_GET['emultiple']) {
  die("Insufficient parameters.");
}
$eids = implode(",", $_GET['emultiple']);

// Get the event info
$result = sqlquery_checked("SELECT EventID,Event,UseTimes FROM event WHERE EventID IN ($eids) ORDER BY Event");
$event_names = "";
$usetimes = 0;
while ($row = mysqli_fetch_object($result)) {
  $earray[] = $row;
  $event_names .= ", ".$row->Event;
  if ($row->UseTimes) $usetimes = 1;
}
$event_names = substr($event_names,2);

if (!isset($_SESSION['attendaggr_showcols']))  $_SESSION['attendaggr_showcols'] = "name,event,first,last,count";

if (!$ajax) {
  header1(_("Aggregate Attendance Data"));
  ?>
  <link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
  <?php
  header2(1);
}
echo "<h1 id=\"title\">"._("Aggregate Attendance Data")."</h1>\n";
echo "<h3>"._("Aggregate Data for Events").": ".$event_names;
if (!empty($_GET["startdate"]) && !empty($_GET["enddate"])) printf(_(", between %s and %s"),$_GET["startdate"],$_GET["enddate"]);
elseif (!empty($_GET["startdate"])) printf(_(", on or after %s"),$_GET["startdate"]);
elseif (!empty($_GET["enddate"])) printf(_(", on or before %s"),$_GET["enddate"]);
if (!empty($_GET['min'])) printf(_(" (Minimum attendance %d times)"),$_GET['min']);
echo "</h3>\n";

// Build WHERE and HAVING clauses
$where = $having = '';
if (!empty($_GET["startdate"])) $where .= " AND a.AttendDate >= '".$_GET["startdate"]."'";
if (!empty($_GET["enddate"])) $where .= " AND a.AttendDate <= '".$_GET["enddate"]."'";
if (!empty($_GET["min"])) $having .= " HAVING attendnum >= ".$_GET["min"];
if (!empty($_GET['basket']) && !empty($_SESSION['basket'])) $where .= " AND a.PersonID IN (".implode(',',$_SESSION['basket']).")";

// Run aggregate query to collect PersonIDs
$sql = "SELECT DISTINCT a.PersonID, COUNT(a.AttendDate) AS attendnum FROM attendance a ".
  "LEFT JOIN event e on e.EventID=a.EventID ".
  "WHERE a.EventID IN ($eids) $where ".
  "GROUP BY a.PersonID, a.EventID $having";
$result = sqlquery_checked($sql);

if (mysqli_num_rows($result) == 0) {
  echo "<p>"._("There are no attendance records matching your criteria.")."</p>";
  if (!$ajax) footer();
  exit;
}

$person_ids = array();
while ($row = mysqli_fetch_object($result)) {
  $person_ids[] = $row->PersonID;
}
$person_ids = array_unique($person_ids);

// Build flextable
require_once("flextable.php");

$showcols = ',' . $_SESSION['attendaggr_showcols'] . ',';

$tableopt = (object) [
  'ids' => implode(',', $person_ids),
  'keyfield' => 'person.PersonID',
  'tableid' => 'attendaggregate',
  'heading' => sprintf(_('%d people/orgs'), count($person_ids)),
  'order' => 'Furigana, event.Event',
  'groupby' => 'attendance.PersonID, attendance.EventID',
  'cols' => array()
];

// Person-related columns
$tableopt->cols[] = (object) [
  'key' => 'personid',
  'sel' => 'person.PersonID',
  'label' => _('ID'),
  'show' => (stripos($showcols, ',personid,') !== FALSE)
];

$tableopt->cols[] = (object) [
  'key' => 'name',
  'sel' => 'person.Name',
  'label' => _('Name'),
  'show' => (stripos($showcols, ',name,') !== FALSE),
  'sort' => 1
];

$tableopt->cols[] = (object) [
  'key' => 'fullname',
  'sel' => 'person.FullName',
  'label' => _('Full Name'),
  'show' => (stripos($showcols, ',fullname,') !== FALSE)
];

$tableopt->cols[] = (object) [
  'key' => 'furigana',
  'sel' => 'person.Furigana',
  'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')),
  'show' => (stripos($showcols, ',furigana,') !== FALSE)
];

$tableopt->cols[] = (object) [
  'key' => 'photo',
  'sel' => 'person.Photo',
  'label' => _('Photo'),
  'show' => (stripos($showcols, ',photo,') !== FALSE),
  'sortable' => false
];

$tableopt->cols[] = (object) [
  'key' => 'phones',
  'sel' => 'Phones',
  'label' => _('Phones'),
  'show' => (stripos($showcols, ',phones,') !== FALSE),
  'table' => 'person',
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'email',
  'sel' => 'person.Email',
  'label' => _('Email'),
  'show' => (stripos($showcols, ',email,') !== FALSE),
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'address',
  'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
  'label' => _('Address'),
  'show' => (stripos($showcols, ',address,') !== FALSE),
  'render' => 'multiline',
  'table' => 'person',
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'birthdate',
  'sel' => 'person.Birthdate',
  'label' => _('Born'),
  'show' => (stripos($showcols, ',birthdate,') !== FALSE),
  'classes' => 'center',
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'age',
  'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
  'label' => _('Age'),
  'show' => (stripos($showcols, ',age,') !== FALSE),
  'classes' => 'center',
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'sex',
  'sel' => 'person.Sex',
  'label' => _('Sex'),
  'show' => (stripos($showcols, ',sex,') !== FALSE),
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'country',
  'sel' => 'person.Country',
  'label' => _('Country'),
  'show' => (stripos($showcols, ',country,') !== FALSE),
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'url',
  'sel' => 'person.URL',
  'label' => _('URL'),
  'show' => (stripos($showcols, ',url,') !== FALSE),
  'lazy' => TRUE
];

$tableopt->cols[] = (object) [
  'key' => 'remarks',
  'sel' => 'person.Remarks',
  'label' => _('Remarks'),
  'show' => (stripos($showcols, ',remarks,') !== FALSE),
  'lazy' => TRUE
];

// Attendance aggregate columns - must be non-lazy (vary by PersonID+EventID)
$tableopt->cols[] = (object) [
  'key' => 'event',
  'sel' => 'event.Event',
  'label' => _('Event'),
  'show' => (stripos($showcols, ',event,') !== FALSE),
  'join' => 'LEFT JOIN attendance ON attendance.PersonID=person.PersonID LEFT JOIN event ON attendance.EventID=event.EventID'
];

$tableopt->cols[] = (object) [
  'key' => 'first',
  'sel' => 'MIN(attendance.AttendDate)',
  'label' => _('First'),
  'show' => (stripos($showcols, ',first,') !== FALSE),
  'classes' => 'center'
];

$tableopt->cols[] = (object) [
  'key' => 'last',
  'sel' => 'MAX(attendance.AttendDate)',
  'label' => _('Last'),
  'show' => (stripos($showcols, ',last,') !== FALSE),
  'classes' => 'center'
];

$tableopt->cols[] = (object) [
  'key' => 'count',
  'sel' => 'COUNT(attendance.AttendDate)',
  'label' => _('# Times'),
  'show' => (stripos($showcols, ',count,') !== FALSE),
  'classes' => 'center'
];

if ($usetimes) {
  $tableopt->cols[] = (object) [
    'key' => 'hours',
    'sel' => 'IF(event.UseTimes=1,SUM(TIME_TO_SEC(SUBTIME(attendance.EndTime,attendance.StartTime))) DIV 60,-1)',
    'label' => _('Total Hours'),
    'show' => (stripos($showcols, ',hours,') !== FALSE),
    'classes' => 'center'
  ];
}

flextable($tableopt);

if (!$ajax) footer();
?>
