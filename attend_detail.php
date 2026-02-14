<?php
include("functions.php");
include("accesscontrol.php");

$date_repeat = 5;
$name_repeat = 15;
$rangesize = 15;

//get the list of people who attended (row headings for table)
$sql = 'SELECT DISTINCT attendance.PersonID,FullName,Furigana,Photo from attendance LEFT JOIN person '.
    'ON attendance.PersonID=person.PersonID WHERE EventID = '.$_GET['eid'];
if (!empty($_GET["startdate"])) $sql .= " AND AttendDate >= '".$_GET["startdate"]."'";
if (!empty($_GET["enddate"])) $sql .= " AND AttendDate <= '".$_GET["enddate"]."'";
if (!empty($_GET['bucket']) && !empty($_SESSION['bucket'])) $sql .= " AND attendance.PersonID IN (".implode(',',$_SESSION['bucket']).")";
$sql .= ' ORDER BY Furigana';
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result) == 0) {
  header1(_('Attendance Detail Chart'));
  header2($_GET['nav']);
  echo _('There are no records matching your criteria.');
  footer();
  exit;
}
$num_people = $num_photos = 0;
$pids = '';
$parray = $darray = array();
while ($row = mysqli_fetch_object($result)) {
  $parray[] = $row;
  $num_people++;
  $num_photos += $row->Photo;
  $pids .= ','.$row->PersonID;
}
$pids = substr($pids,1);

$pstext = '';

header1(_('Attendance Detail Chart').$pstext);

if (!$_GET['eid']) die('No event ID passed.');
$eid = $_GET['eid'];
?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<?php
header2(!empty($_GET['nav']) ? $_GET['nav'] : 0);
if (!empty($_GET['nav']) && $_GET['nav']==1) echo '<h1 id="title">'._('Attendance Detail Chart').$pstext."</h1>\n";
//get the description of the event
$result = sqlquery_checked("SELECT Event,UseTimes,Remarks from event WHERE EventID = $eid");
$event = mysqli_fetch_object($result);
echo '<h3>'.sprintf(_('Event: "%s" (%s)'),$event->Event,$event->Remarks);
if (!empty($_GET["startdate"]) && !empty($_GET["enddate"])) printf(_(", between %s and %s"),$_GET["startdate"],$_GET["enddate"]);
elseif (!empty($_GET["startdate"])) printf(_(", on or after %s"),$_GET["startdate"]);
elseif (!empty($_GET["enddate"])) printf(_(", on or before %s"),$_GET["enddate"]);
echo "</h3>";

//get the list of dates (column headings for table)
if (!empty($_GET['empties'])) {
  $showemptiesform = 1;
  $sql = "SELECT MIN(AttendDate) AS first, MAX(AttendDate) AS last FROM attendance WHERE EventID=$eid AND PersonID IN ($pids)";
  if (!empty($_GET["startdate"])) $sql .= " AND AttendDate >= '".$_GET["startdate"]."'";
  if (!empty($_GET["enddate"])) $sql .= " AND AttendDate <= '".$_GET["enddate"]."'";
  $result = sqlquery_checked($sql);
  $row = mysqli_fetch_object($result);
  for($step = $row->first; $step != $row->last; $step = date('Y-m-d', strtotime("$step +1 day"))) {
    $darray[] = $step;
  }
  $darray[] = $row->last;
} else {
  $sql = "SELECT DISTINCT AttendDate FROM attendance WHERE EventID=$eid AND PersonID IN ($pids)";
  if (!empty($_GET["startdate"])) $sql .= " AND AttendDate >= '".$_GET["startdate"]."'";
  if (!empty($_GET["enddate"])) $sql .= " AND AttendDate <= '".$_GET["enddate"]."'";
  $sql .= " ORDER BY AttendDate";
  $result = sqlquery_checked($sql);
  while ($row = mysqli_fetch_object($result)) {
    $darray[] = $row->AttendDate;
  }
  //only need the emptiesform if range is not consecutive
  $showemptiesform = 0;
  $final = count($darray)-1;
  for($i=0; $i<$final; $i++){
    if(date("Y-m-d",strtotime($darray[$i]." +1 day")) != $darray[$i+1]) {
      $showemptiesform = 1;
      break;
    }
  }
}
$num_dates = count($darray);
if (!empty($_GET['rangeall'])) {  //user requested whole range
  $rangefirst = 0;
  $rangelast = $num_dates-1;
} elseif (!empty($_GET['rangefirst'])) {  //some specification about range besides all
  if (!empty($_GET['rangestart'])) $rangefirst = 0;
  elseif (!empty($_GET['rangeend'])) $rangefirst = max($num_dates-$rangesize,0);
  elseif (!empty($_GET['rangeprev'])) $rangefirst = max($_GET['rangefirst']-$rangesize,0);
  elseif (!empty($_GET['rangenext'])) $rangefirst = min($_GET['rangefirst']+$rangesize,$num_dates-$rangesize);
  else $rangefirst = $_GET['rangefirst'];
  $rangelast = min($rangefirst+$rangesize-1,$num_dates-1);
} else {  //nothing specified, so use end range
  $rangefirst = max($num_dates-$rangesize,0);
  $rangelast = $num_dates-1;
}

if ($showemptiesform == 1) {
  echo '<form id="emptiesform" action="'.$_SERVER['PHP_SELF'].'" method="post" target="_self">'."\n";
  foreach ($_GET as $key => $val) {
    if ($key != 'empties')  echo '<input type="hidden" name="'.$key.'" value="'.$val.'">'."\n";
  }
  if (!empty($_GET['empties'])) {
    echo '<input type="hidden" name="empties" value="0">'."\n";
    echo '<input type="submit" value="'._("Show only dates with attendance")."\">\n";
  } else {
    echo "<input type=\"hidden\" name=\"empties\" value=\"1\">\n";
    echo "<input type=\"submit\" value=\""._("Show all dates in range (even if no attendance)")."\">\n";
  }
  echo "</form>\n"; 
}
echo '<form id="msform" action="multiselect.php" method="get" target="_top">'."\n";
echo '<input type="hidden" name="pids" value="'.$pids."\">\n";
echo '<input type="submit" value="'._('Go to Multi-Select')."\">\n";
echo "</form>\n"; 

echo '<p>'._('To delete entries: click to select a cell, Ctrl-click to select additional cells, and/or drag to select a range. Then click this button:');
echo '<button id="deleteSelected">'._('Delete Selected Cells')."</button>\n";

if ($rangefirst > 0 || $rangelast < $num_dates-1) {
  echo "<form id=\"rangeform\" action=\"".$_SERVER['PHP_SELF'].'" method="post" target="_self">'."\n";
  foreach ($_GET as $key => $val) {
    if (substr($key,0,5)!="range")  echo '<input type="hidden" name="'.$key.'" value="'.$val."\">\n";
  }
  echo '<input type="hidden" name="rangefirst" value="'.$rangefirst."\">\n";
  echo '<input type="submit" name="rangestart" value="'._("<< Beginning of Range").'"'.
  ($rangefirst>0 ? "" : ' style="visibility:hidden"').">\n";
  echo '<input type="submit" name="rangeprev" value="'._("< Earlier Dates").'"'.
  ($rangefirst>0 ? "" : ' style="visibility:hidden"').">\n";
  echo '<input type="submit" name="rangeall" value="'.sprintf(_("Show Whole Range (%s)"),$num_dates)."\">\n";
  echo '<input type="submit" name="rangenext" value="'._("Later Dates >").'"'.
  ($rangelast<$num_dates-1 ? "" : ' style="visibility:hidden"').">\n";
  echo '<input type="submit" name="rangeend" value="'._("End of Range >>").'"'.
  ($rangelast<$num_dates-1 ? "" : ' style="visibility:hidden"').">\n";
  echo "</form>\n";
}

echo '<table id="attendtable" border="1" cellspacing="0" cellpadding="2">';

// loop for rows of the table
for ($r=0; $r<$num_people; $r++) {
  echo "<tr>";
  if (($date_repeat > 0) && ($r % $date_repeat == 0)) {  // repeat header row
    if ($num_photos > 0) echo "<td class=\"photohead\">"._("Photo")."</td>\n";
    for ($c=$rangefirst; $c<=$rangelast; $c++) {
      if (($name_repeat > 0) && (($c-$rangefirst) % $name_repeat == 0)) {  // repeat name title
        echo '<td class="namehead">'._('Name')."</td>\n";
      }
      echo '<td class="';
      switch (date("w",strtotime($darray[$c]))) {
      case 0:
        echo 'sundaydate';
        break;
      case 6:
        echo 'saturdaydate';
        break;
      default:
        echo 'weekdaydate';
      }
      echo '">'.substr($darray[$c],0,4).'<br />'.substr($darray[$c],5)."</td>\n";
    }
    echo "</tr>\n<tr>";
  }

  //query for this person's attendance data and correlate to dates
  $sql = "SELECT AttendDate".($event->UseTimes?",StartTime,EndTime":"").
  " FROM attendance WHERE EventID=$eid and PersonID=".$parray[$r]->PersonID;
  $sql .= " AND AttendDate >= '".$darray[$rangefirst]."' AND AttendDate <= '".$darray[$rangelast]."'";
  $sql .= " ORDER BY AttendDate";
  $result = sqlquery_checked($sql);
  $row = mysqli_fetch_object($result);
  $done = 0;

  // put photo in first column if any are present
  if ($num_photos > 0) {
    echo '<td class="photocell">';
    if ($parray[$r]->Photo == 1) {
      echo '<img border="0" src="photo.php?f=p'.$parray[$r]->PersonID.'" width="50">';
    } else {
      echo '&nbsp;';
    }
    echo "</td>\n";
  }

  // loop for cells in this row
  for ($c=$rangefirst; $c<=$rangelast; $c++) {
    // repeat names
    if (($name_repeat > 0) && (($c-$rangefirst) % $name_repeat == 0)) {
      echo "<td class=\"namecell\"><a href=\"individual.php?pid=".$parray[$r]->PersonID."\" target=\"_blank\">";
      echo readable_name($parray[$r]->FullName, $parray[$r]->Furigana,0,0,"<br />")."</a></td>\n";
    }
    if (($done == 0) && ($row->AttendDate == $darray[$c])) {  //matches date
      echo '<td id="'.$parray[$r]->PersonID."_".$darray[$c].'" class="';
      if ($event->UseTimes && $row->StartTime) echo 'attendtimecell">'.substr($row->StartTime,0,5).'~<br />'.substr($row->EndTime,0,5);
      else echo 'attendcell">*';
      echo "</td>\n";
      if (!$row = mysqli_fetch_object($result)) $done = 1;
    } else {
      echo "<td></td>\n";
    }
  }
  echo "</tr>\n";
}
echo "</table>\n";

load_scripts(['jquery', 'jqueryui']);
?>
<script type="text/JavaScript">
$(document).ready(function(){
  $("#attendtable").selectable({ filter: ".attendcell,.attendtimecell", cancel: "a" });

  $("#deleteSelected").click(function() {
    var IDs = new Array();
    $("td.ui-selected").each(function() {  // for all the selected table cells...
      IDs.push($(this).attr('id'));
    });
    if (IDs.length > 0) {
      var text = '<?=_("Are you sure you want to delete these %d attendance records?")?>';
      if (confirm(text.replace('%d',IDs.length))) {
        $.ajax({
          type: "POST",
          url: "attend_del.php",
          data: "action=AttendDelete&ids="+IDs+"&eid=<?=$eid?>",
          dataType: "text",
          success: function(deleted) {
            if (deleted.substring(0,1) == "#") {  //indication of success
              $(deleted).removeClass('attendcell attendtimecell ui-selected');
              $(deleted).text('');
              alert("<?=_("Attendance records successfully deleted.")?>");
            } else {
              alert("Delete failed: "+deleted);
            }
          }
        });
      }
    } else {
      alert('<?=_("No cells have been selected for deletion.")?>');
    }
  });
});
</script>
<?php
print_footer();
?>
