<?php
include('functions.php');
include('accesscontrol.php');

header1(_('Attendance Summary'));

?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php" type="text/css" />
<?php
header2($_GET['nav']);
if ($_GET['nav']==1) echo '<h1 id="title">'._('Attendance Summary Chart')."</h1>\n";

if (!$_GET['emultiple']) {
  die('Insufficient parameters - please select one or more events.');
}
$eids = implode(',', $_GET['emultiple']);
//get the event info (row headings for table and event names for top of page)
$earray = array();
$events = '';
$result = sqlquery_checked("SELECT EventID,Event,UseTimes,Remarks FROM event WHERE EventID IN ($eids) ORDER BY Event");
while ($row = mysqli_fetch_object($result)) {
  $events .= ', "'.$row->Event.'"';
  $earray[] = $row;
}
echo '<h3>'._('Events').': '.substr($events,2);

if (!empty($_GET['startdate']) && !empty($_GET['enddate'])) printf(_(', between %s and %s'),$_GET['startdate'],$_GET['enddate']);
elseif (!empty($_GET['startdate'])) printf(_(', on or after %s'),$_GET['startdate']);
elseif (!empty($_GET['enddate'])) printf(_(', on or before %s'),$_GET['enddate']);
echo "</h3>";

//get the list of dates (column headings for table)
$sql = "SELECT DISTINCT AttendDate FROM attendance WHERE EventID IN ($eids)";
if (!empty($_GET['basket']) && !empty($_SESSION['basket'])) $sql .= " AND PersonID IN (".implode(',',$_SESSION['basket']).")";
if (!empty($_GET["startdate"])) $sql .= " AND AttendDate >= '".$_GET["startdate"]."'";
if (!empty($_GET["enddate"])) $sql .= " AND AttendDate <= '".$_GET["enddate"]."'";
$sql .= " ORDER BY AttendDate";
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result) == 0) {
  echo "<p>"._("There are no attendance records matching your criteria.")."</p>";
  footer();
  exit;
}
while ($darray[] = mysqli_fetch_row($result));

echo '<table border="1" cellspacing="0" cellpadding="3">'."\n";

// loop for rows of the table
for ($r=0; $r<(count($earray)); $r++) {
  echo '<tr>';
  if ($r % 7 == 0) {  // repeat header row every 7 rows
    for ($c=0; $c<(count($darray)-1); $c++) {
      if ($c % 15 == 0) {  // repeat event info every 15 columns
        echo '<td class="eventhead">Event</td>';
      }
      echo '<td class="';
      switch (date("w",strtotime($darray[$c][0]))) {
      case 0:
        echo 'sundaydate';
        break;
      case 6:
        echo 'saturdaydate';
        break;
      default:
        echo 'weekdaydate';
      }
      echo '">'.substr($darray[$c][0],0,4).'<br />'.substr($darray[$c][0],5).'</td>';
    }
    echo '</tr><tr>';
  }

  //query for this event's total attendance numbers according to dates
  $sql = "SELECT AttendDate,COUNT(PersonID) AS count".
  ($earray[$r]->UseTimes ? ",SUM(TIME_TO_SEC(SUBTIME(EndTime,StartTime))) DIV 60 AS minutes" : "").
  " FROM attendance WHERE EventID=".$earray[$r]->EventID;
  if (!empty($_GET["startdate"])) $sql .= " AND AttendDate >= '".$_GET["startdate"]."'";
  if (!empty($_GET["enddate"])) $sql .= " AND AttendDate <= '".$_GET["enddate"]."'";
  if (!empty($_GET['basket']) && !empty($_SESSION['basket'])) $sql .= " AND PersonID IN (".implode(',',$_SESSION['basket']).")";
  $sql .= " GROUP BY AttendDate ORDER BY AttendDate";
  $result = sqlquery_checked($sql); 
  $row = mysqli_fetch_object($result);
  $done = 0;

  // loop for cells in this row
  for ($c=0; $c<(count($darray)-1); $c++) {
    // repeat names every 15 columns
    if ($c % 15 == 0) {
      echo '<td class="eventcell"><a href="attend_detail.php?eid='.$earray[$r]->EventID.
      '&nav=1" target="_blank"><span title="'.$earray[$r]->Remarks.'">'.
      $earray[$r]->Event."</span></a></td>\n";
    }
    if (($done == 0) && ($row->AttendDate == $darray[$c][0])) {  //matches date
      echo '<td class="sumcell"><a href="list.php?eventselect1[]='.$earray[$r]->EventID.
      '&astartdate1='.$darray[$c][0].'&aenddate1='.$darray[$c][0].'" target="_blank">'.$row->count.'</a>';
      if ($earray[$r]->UseTimes) echo '<br />['.(($row->minutes-$row->minutes%60)/60).':'.sprintf("%02d",$row->minutes%60).']';
      echo "</td>\n";
      if (!$row = mysqli_fetch_object($result)) $done = 1;
    } else {
      echo '<td class="zerocell">0</td>';
    }
  }
  echo "</tr>\n";
}
echo "</table>\n";

load_scripts(['jquery']);
footer();
?>
