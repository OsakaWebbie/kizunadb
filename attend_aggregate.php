<?php
include("functions.php");
include("accesscontrol.php");

if (!$_POST['emultiple']) {
  die("Insufficient parameters.");
}
$eids = implode(",", $_POST['emultiple']);
//get the event info
$result = sqlquery_checked("SELECT EventID,Event,UseTimes FROM event WHERE EventID IN ($eids) ORDER BY Event");
$event_names = "";
$usetimes = 0;
while ($row = mysqli_fetch_object($result)) {
  $earray[] = $row;
  $event_names .= ", ".$row->Event;
  if ($row->UseTimes) $usetimes = 1;
}
$event_names = substr($event_names,2);

if (!isset($_SESSION['attendaggr_showcols']))  $_SESSION['attendaggr_showcols'] = "event,first,last,attendnum,attendtime";

//array of column id, whether to hide in column picker, and whether to disable in sorter
$cols[] = array("personid",1);
$cols[] = array("name-for-csv",0);
$cols[] = array("name-for-display",0);
$cols[] = array("photo",1);
$cols[] = array("phone",1);
$cols[] = array("email",1);
$cols[] = array("address",1);
$cols[] = array("birthdate",1);
$cols[] = array("age",1);
$cols[] = array("sex",1);
$cols[] = array("country",1);
$cols[] = array("url",1);
$cols[] = array("event",1);
$cols[] = array("first",1);
$cols[] = array("last",1);
$cols[] = array("attendnum",1);
if ($usetimes) $cols[] = array("attendtime",1);
$cols[] = array("selectcol",0);
$colsHidden = $hideInList = "";

foreach($cols as $i=>$col) {
  if ($col[1]==0) $hideInList .= ",".($i+1);
  elseif (stripos(",".$_SESSION['attendaggr_showcols'].",",",".$col[0].",") === FALSE)  $colsHidden .= ",".($i+1);
}
$hideInList = substr($hideInList,1);  //to remove the leading comma
$colsHidden = substr($colsHidden,1);  //to remove the leading comma

$tableheads = "<th class=\"personid\">"._("ID")."</th>";
$tableheads .= "<th class=\"name-for-csv\" style=\"display:none\">"._("Name")." (".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>";
$tableheads .= "<th class=\"name-for-display\">"._("Name")." (".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>";
$tableheads .= "<th class=\"photo\">"._("Photo")."</th>\n";
$tableheads .= "<th class=\"phone\">"._("Phone")."</th>\n";
$tableheads .= "<th class=\"email\">"._("Email")."</th>\n";
$tableheads .= "<th class=\"address\">"._("Address")."</th>\n";
$tableheads .= "<th class=\"birthdate\">"._("Born")."</th>\n";
$tableheads .= "<th class=\"age\">"._("Age")."</th>\n";
$tableheads .= "<th class=\"sex\">"._("Sex")."</th>\n";
$tableheads .= "<th class=\"country\">"._("Country")."</th>\n";
$tableheads .= "<th class=\"url\">"._("URL")."</th>\n";
$tableheads .= "<th class=\"event\">"._("Event")."</th>\n";
$tableheads .= "<th class=\"first\">"._("First")."</th>\n";
$tableheads .= "<th class=\"last\">"._("Last")."</th>\n";
$tableheads .= "<th class=\"attendnum\">"._("# Times")."</th>\n";
if ($usetimes) $tableheads .= "<th class=\"attendtime\">"._("Total Hours")."</th>\n";
$tableheads .= "<th id=\"thSelectColumn\" class=\"selectcol\">";
$tableheads .= "<ul id=\"ulSelectColumn\"><li><img src=\"graphics/selectcol.png\" alt=\"select columns\" ".
        "title=\"select columns\" /><ul id=\"targetall\"></ul></li></ul>";
$tableheads .= "</th>\n";

header1("Aggregate Attendance Data");
?>
<style>
td.attendnum, th.attendnum {text-align:center;}
td.attendtime, th.attendtime{text-align:center;}
</style>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#preselected").val($("#pids").text());
  
  $("#mainTable").tablesorter({
    sortList:[[2,0]],
    headers:{<?=($usetimes?"17":"16")?>:{sorter:false}}
  });
  $('#mainTable').columnManager({listTargetID:'targetall',
  onClass: 'advon',
  offClass: 'advoff',
  hideInList: [<?=$hideInList?>],
  colsHidden: [<?=$colsHidden?>],
  saveState: false});
  $('#ulSelectColumn').clickMenu({onClick: function(){}});
  
  $('#ulSelectColumn').click(function() {
    alert("fired");
    if (($("div.outerbox").offsetLeft + $("div.outerbox").offsetWidth) > document.body.clientWidth) {
      alert("too wide");
      window.scrollBy(1000,0);  //should be plenty
    }
  });
});

function getCSV() {
  $(".name-for-display, .selectcol").hide();
  $(".name-for-csv").show();
  $('#csvtext').val($('#mainTable').table2CSV({delivery:'value'}));
  $(".name-for-csv").hide();
  $(".name-for-display, .selectcol").show();
}
</script>
<?php
header2($_GET['nav']);
echo "<h3>Aggregate Data for Events: ".$event_names;
if ($_POST["startdate"] && $_POST["enddate"]) printf(_(", between %s and %s"),$_POST["startdate"],$_POST["enddate"]);
elseif ($_POST["startdate"]) printf(_(", on or after %s"),$_POST["startdate"]);
elseif ($_POST["enddate"]) printf(_(", on or before %s"),$_POST["enddate"]);
if ($_POST['min']) printf(_(" (Minimum attendance %d times)"),$_POST['min']);
if ($_POST['preselected']) printf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1);
echo "</h3>";

$sql = "SELECT p.*, h.Address, h.Phone, pc.*, a.EventID, e.Event, MIN(a.AttendDate) AS first, ".
  "MAX(a.AttendDate) AS last, COUNT(a.AttendDate) AS attendnum, ".
  "IF(e.UseTimes=1,SUM(TIME_TO_SEC(SUBTIME(a.EndTime,a.StartTime))) DIV 60,-1) AS minutes ".
  "FROM attendance a LEFT JOIN person p ON p.PersonID=a.PersonID LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
  "LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode LEFT JOIN event e on e.EventID=a.EventID ".
  "WHERE a.EventID in ($eids)";
if ($_POST["startdate"]) $sql .= " AND a.AttendDate >= '".$_POST["startdate"]."'";
if ($_POST["enddate"]) $sql .= " AND a.AttendDate <= '".$_POST["enddate"]."'";
if ($_POST["min"]) $sql .= " AND attendnum >= ".$_POST["min"];
if ($_POST['preselected']) $sql .= " AND a.PersonID IN (".$_POST['preselected'].")";
$sql .= " GROUP BY a.PersonID,a.EventID ORDER BY p.Furigana, e.Event";
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result) == 0) {
  echo "<p>"._("There are no attendance records matching your criteria.")."</p>";
  footer();
  exit;
}
?>
<div id="actions">
<form action="multiselect.php" method="post" target="_top">
<input type="hidden" id="preselected" name="preselected" value="">
<input type="submit" value="<?=_("Go to Multi-Select with these entries preselected")?>">
</form>
<form action="download.php" method="post" target="_top">
<input type="hidden" id="csvtext" name="csvtext" value="">
<input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
</form>
</div>
<?php
echo "<table id=\"mainTable\" class=\"tablesorter\"><thead>";
echo "<tr>";
echo $tableheads;
echo "</tr></thead>\n";

$prev_pid = $pidnum = 0;
$pid_list = "";
$tbody = "<tbody>";
while ($row = mysqli_fetch_object($result)) {
  if ($row->PersonID != $prev_pid) {
    $prev_pid = $row->PersonID;
    $pid_list .= ",".$row->PersonID;
    $pidnum++;
  }
  $tbody .= "<tr>";
  $tbody .= "<td class=\"personid\">".$row->PersonID."</td>\n";
  $tbody .= "<td class=\"name-for-csv\" style=\"display:none\">".readable_name($row->FullName,$row->Furigana)."</td>";
  $tbody .= "<td class=\"name-for-display\" nowrap><span style=\"display:none\">".$row->Furigana."</span>";
  $tbody .= "<a href=\"individual.php?pid=".$row->PersonID."\">".
    readable_name($row->FullName,$row->Furigana,0,0,"<br />")."</a></td>\n";
  $tbody .= "<td class=\"photo\">";
  $tbody .= ($row->Photo == 1) ? "<img border=0 src=\"photo.php?f=p".$row->PersonID."\" width=50>" : "";
  $tbody .= "</td>\n";
  if ($row->CellPhone && $row->Phone) {
    $tbody .= "<td class=\"phone\">".$row->Phone."<br>".$row->CellPhone."</td>\n";
  } else {
    $tbody .= "<td class=\"phone\">".$row->Phone."".$row->CellPhone."</td>\n";
  }
  $tbody .= "<td class=\"email\">".email2link($row->Email)."</td>\n";
  $tbody .= "<td class=\"address\">".$row->PostalCode.$row->Prefecture.$row->ShiKuCho.db2table($row->Address)."</td>\n";
  $tbody .= "<td class=\"birthdate\">".(($row->Birthdate!="0000-00-00") ? ((substr($row->Birthdate,0,4) == "1900") ? substr($row->Birthdate,5) : $row->Birthdate) : "")."</td>\n";
  $tbody .= "<td class=\"age\">".(($row->Birthdate!="0000-00-00") && (substr($row->Birthdate,0,4) != "1900") ? age($row->Birthdate) : "")."</td>\n";
  $tbody .= "<td class=\"sex\">".$row->Sex."</td>\n";
  $tbody .= "<td class=\"country\">".$row->Country."</td>\n";
  $tbody .= "<td class=\"url\">".$row->URL."</td>\n";
  $tbody .= "<td class=\"event\">".$row->Event."</td>\n";
  $tbody .= "<td class=\"first\">".$row->first."</td>\n";
  $tbody .= "<td class=\"last\">".$row->last."</td>\n";
  $tbody .= "<td class=\"attendnum\">".$row->attendnum."</td>\n";
  $totalnum += $row->attendnum;
  if ($usetimes) {
    $tbody .= "<td class=\"attendtime\">";
    if ($row->minutes!=-1) {
      $tbody .= "<span style=\"display:none\">".sprintf("%06d",($row->minutes-$row->minutes%60)/60).":".sprintf("%02d",$row->minutes%60)."</span>";
      $tbody .= (($row->minutes-$row->minutes%60)/60).":".sprintf("%02d",$row->minutes%60);
      $totalminutes += $row->minutes;
    }
    $tbody .= "</td>\n";
  }
  $tbody .= "<td class=\"selectcol\">-</td>\n";
  $tbody .= "</tr>\n";
}
echo "<tfoot>";
echo "<th colspan=\"14\">".sprintf(_("Totals: %d People/Orgs"),$pidnum)."</th>\n";
echo "<th class=\"attendnum\">".$totalnum."</th>\n";
if ($usetimes) echo "<th class=\"attendtime\">".(($totalminutes-$totalminutes%60)/60).":".sprintf("%02d",$totalminutes%60)."</th>\n";
echo "<th class=\"selectcol\">-</th>\n";
echo "</tfoot>\n".$tbody."</tbody></table>\n";
echo "<div id=\"pids\" style=\"display:none\">".substr($pid_list,1)."</div>\n";
echo "</form>\n"; 

print_footer();
?>
