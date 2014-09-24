<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Attendance Summary").($_POST['preselected']!="" ?
sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : ""));

?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<?
if ($_POST['preselected']) {
?>
<script type="text/javascript">
$(document).ready(function() {
  $(".eventcell>a, .sumcell>a").click(function(ev) {
    ev.preventDefault();
    $("#filterform").attr("action", $(this).attr("href"));
    $("#filterform").submit();
    return false; 
  }); 
});
</script>
<?
} //end of if there is a preselected list
header2($_GET['nav']);
//echo "<pre>".print_r($_POST,true)."</pre>";
if ($_GET['nav']==1) echo "<h1 id=\"title\">"._("Attendance Summary Chart").($_POST['preselected']!="" ?
sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : "")."</h1>\n";

if (!$_REQUEST['emultiple']) {
  die("Insufficient parameters - please select one or more events.");
}
$eids = implode(",", $_REQUEST['emultiple']);
//get the event info (row headings for table and event names for top of page)
$earray = array();
$result = sqlquery_checked("SELECT EventID,Event,UseTimes,Remarks FROM event WHERE EventID IN ($eids) ORDER BY Event");
while ($row = mysql_fetch_object($result)) {
  $events .= ", \"".$row->Event."\"";
  $earray[] = $row;
}
echo "<h3>"._("Events").": ".substr($events,2);

if ($_REQUEST["startdate"] && $_REQUEST["enddate"]) printf(_(", between %s and %s"),$_REQUEST["startdate"],$_REQUEST["enddate"]);
elseif ($_REQUEST["startdate"]) printf(_(", on or after %s"),$_REQUEST["startdate"]);
elseif ($_REQUEST["enddate"]) printf(_(", on or before %s"),$_REQUEST["enddate"]);
if ($_POST['preselected']) printf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1);
echo "</h3>";

//get the list of dates (column headings for table)
$sql = "SELECT DISTINCT AttendDate FROM attendance WHERE EventID IN ($eids)";
if ($_POST['preselected']) $sql .= " AND PersonID IN (".$_POST['preselected'].")";
if ($_REQUEST["startdate"]) $sql .= " AND AttendDate >= '".$_REQUEST["startdate"]."'";
if ($_REQUEST["enddate"]) $sql .= " AND AttendDate <= '".$_REQUEST["enddate"]."'";
$sql .= " ORDER BY AttendDate";
$result = sqlquery_checked($sql);
if (mysql_numrows($result) == 0) {
  echo "<p>"._("There are no attendance records matching your criteria.")."</p>";
  footer();
  exit;
}
while ($darray[] = mysql_fetch_row($result));

if ($_POST['preselected']) {
  echo "<form id=\"filterform\" method=\"post\" action=\"\" target=\"_blank\">";
  echo "<input type=\"hidden\" id=\"preselected\" name=\"preselected\" value=\"".$_POST['preselected']."\">";
}
echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"3\">\n";

// loop for rows of the table
for ($r=0; $r<(count($earray)); $r++) {
  echo "<tr>";
  if ($r % 7 == 0) {  // repeat header row every 7 rows
    for ($c=0; $c<(count($darray)-1); $c++) {
      if ($c % 15 == 0) {  // repeat event info every 15 columns
        echo "<td class=\"eventhead\">Event</td>";
      }
      echo "<td class=\"";
      switch (date("w",strtotime($darray[$c][0]))) {
      case 0:
        echo "sundaydate";
        break;
      case 6:
        echo "saturdaydate";
        break;
      default:
        echo "weekdaydate";
      }
      echo "\">".substr($darray[$c][0],0,4)."<br />".substr($darray[$c][0],5)."</td>";
    }
    echo "</tr><tr>";
  }

  //query for this event's total attendance numbers according to dates
  $sql = "SELECT AttendDate,COUNT(PersonID) AS count".
  ($earray[$r]->UseTimes ? ",SUM(TIME_TO_SEC(SUBTIME(EndTime,StartTime))) DIV 60 AS minutes" : "").
  " FROM attendance WHERE EventID=".$earray[$r]->EventID;
  if ($_REQUEST["startdate"]) $sql .= " AND AttendDate >= '".$_REQUEST["startdate"]."'";
  if ($_REQUEST["enddate"]) $sql .= " AND AttendDate <= '".$_REQUEST["enddate"]."'";
  if ($_POST['preselected']) $sql .= " AND PersonID IN (".$_POST['preselected'].")";
  $sql .= " GROUP BY AttendDate ORDER BY AttendDate";
  $result = sqlquery_checked($sql); 
  $row = mysql_fetch_object($result);
  $done = 0;

  // loop for cells in this row
  for ($c=0; $c<(count($darray)-1); $c++) {
    // repeat names every 15 columns
    if ($c % 15 == 0) {
      echo "<td class=\"eventcell\"><a href=\"attend_detail.php?preselected=".$_POST['preselected'].
      "&eid=".$earray[$r]->EventID."\" target=\"_blank\"><span title=\"".$earray[$r]->Remarks."\">".
      $earray[$r]->Event."</span></a></td>\n";
    }
    if (($done == 0) && ($row->AttendDate == $darray[$c][0])) {  //matches date
      echo "<td class=\"sumcell\"><a href=\"list.php?eventselect1[]=".$earray[$r]->EventID.
      "&astartdate1=".$darray[$c][0]."&aenddate1=".$darray[$c][0]."\" target=\"_blank\">".$row->count."</a>";
      if ($earray[$r]->UseTimes) echo "<br />[".(($row->minutes-$row->minutes%60)/60).":".sprintf("%02d",$row->minutes%60)."]";
      echo "</td>\n";
      if (!$row = mysql_fetch_object($result)) $done = 1;
    } else {
      echo "<td class=\"zerocell\">0</td>";
    }
  }
  echo "</tr>\n";
}
echo "</table>\n";
if ($_POST['preselected']) echo "</form>\n"; 

footer();
?>
