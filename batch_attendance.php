<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) header1(_("Record Attendance"));

// A REQUEST TO ADD ATTENDANCE RECORD(S)?
if (!empty($_POST['newattendance'])) {
  $pidarray = explode(",",$_POST['pid_list']);
  //make array of dates (single or range)
  $datearray = array();
  if (!empty($_POST['enddate'])) {  //need to do a range of dates
    if ($_POST['date'] > $_POST['enddate']) die("Error: End Date is earlier than Start Date.");
    for ($day=$_POST['date']; $day<=$_POST['enddate']; $day=date('Y-m-d', strtotime("$day +1 day"))) {
      if ($_POST['dow'.date("w",strtotime($day))]) {
        $datearray[] = $day;
      }
    }
  } else {
    $datearray[] = $_POST['date'];
  }
  //insert for each date and pid (might be only one of each, but...)
  //not combined into a single "insert...select" query because the ON DUPLICATE KEY UPDATE won't add the non-dups in the list
  $added = 0;
  $updated = 0;
  foreach ($datearray as $eachdate) {
    foreach ($pidarray as $eachpid) {
      if (!empty($_POST['starttime'])) {
        sqlquery_checked("INSERT INTO attendance(PersonID,EventID,AttendDate,StartTime,EndTime) ".
        "VALUES($eachpid,{$_POST['eid']},'$eachdate','{$_POST['starttime']}:00','{$_POST['endtime']}:00') ".
        "ON DUPLICATE KEY UPDATE StartTime='{$_POST['starttime']}:00', EndTime='{$_POST['endtime']}:00'");
      } else {
        sqlquery_checked("INSERT INTO attendance(PersonID,EventID,AttendDate) ".
        "VALUES($eachpid,{$_POST['eid']},'$eachdate') ON DUPLICATE KEY UPDATE AttendDate=AttendDate");
      }
      $affected = mysqli_affected_rows($db);
      if ($affected == 2)  $updated++;
      elseif ($affected == 1)  $added++;
    }
  }
  if (!$ajax) header2(0);
  echo '<h3>'.sprintf(_('%s attendance records added.'),$added);
  if ($updated > 0) echo '<br>'.sprintf(_('%s existing records had times updated.'),$updated);
  if ($added+$updated > count($datearray)*count($pidarray)) echo '<br>'.sprintf(_('%s existing records were unchanged.'),
      count($datearray)*count($pidarray) - $updated - $added);
  echo '</h3>';
  if (!$ajax) footer();
  exit;
}
if (!$ajax) {
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<style>
body { margin:20px; }
#eventselect label.label-n-input { margin-right:0; }
#dayofweek label { margin-right: 0.5em; }
</style>
<?php
}
if (!$ajax) header2(0);
?>
<h3><?=_("Select an event and at least one date, and click the button.")?></h3>
<form name="attendform" method="post" action="<?=$_SERVER['PHP_SELF']?>" onSubmit="return ValidateAttendance()">
  <input type="hidden" name="pid_list" value="<?=$_POST['pid_list']?>" />
  <div id="eventselect">
    <label class="label-n-input"><?=_("Event")?>:
      <select size="1" id="eventid" name="eid">
        <option value="0" selected><?=_("Select...")?></option>
<?php
$result = sqlquery_checked("SELECT EventID,Event,UseTimes,IF(EventEndDate AND EventEndDate<CURDATE(),'inactive','active') AS Active FROM event ORDER BY Event");
while ($row = mysqli_fetch_object($result)) {
  echo '        <option value="'.$row->EventID.'" class="'.(($row->UseTimes==1)?"times ":"days ").$row->Active.'">'.
      $row->Event."</option>\n";
}
?>
      </select>
    </label>
    <span id="currentevents" style="display:none">
      <span class="comment"><?=("(Showing only current events)")?></span> <a id="showpast" href="#"><?=("Show All")?></a>
    </span>
    <span id="allevents" style="display:none"><a id="hidepast" href="#"><?=("Hide Past Events")?></a></span>
  </div>
  <div id="dates">
    <label class="label-n-input"><?=_("Date")?>:
    <input type="text" name="date" id="attenddate" style="width:6em" value="" /></label>
    <label class="label-n-input date"><?=_("Optional End Date")?>:
    <input type="text" name="enddate" id="attendenddate" style="width:6em" value="" /></label>
  </div>
  <div id="dayofweek">
    <?=_("Days of week for date range")?>:
    <label class="label-n-input"><input type="checkbox" name="dow0" checked /><?=_("Sunday")?></label>
    <label class="label-n-input"><input type="checkbox" name="dow1" checked /><?=_("Monday")?></label>
    <label class="label-n-input"><input type="checkbox" name="dow2" checked /><?=_("Tuesday")?></label>
    <label class="label-n-input"><input type="checkbox" name="dow3" checked /><?=_("Wednesday")?></label>
    <label class="label-n-input"><input type="checkbox" name="dow4" checked /><?=_("Thursday")?></label>
    <label class="label-n-input"><input type="checkbox" name="dow5" checked /><?=_("Friday")?></label>
    <label class="label-n-input"><input type="checkbox" name="dow6" checked /><?=_("Saturday")?></label>
  </div>
  <div id="times">
    <label class="label-n-input times" style="display:none"><?=_("Start Time")?>:
    <input type="text" name="starttime" id="attendstarttime" style="width:4em" value="" /></label>
    <label class="label-n-input times" style="display:none"><?=_("End Time")?>:
    <input type="text" name="endtime" id="attendendtime" style="width:4em" value="" /></label>
  </div>
  <input type="submit" value="<?=_("Save Attendance Entries")?>" name="newattendance" />
</form>

<?php
$scripts = ['jquery', 'jqueryui'];
if ($_SESSION['lang']=="ja_JP") $scripts[] = 'datepicker-ja';
if (!$ajax) load_scripts($scripts);
?>
<script>
$(document).ready(function(){
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  });

/* initially hide past events in dropdown list, but allow toggling */
  $("a#showpast").click(function(e) {
    e.preventDefault();
    $("#currentevents").hide();
    $("#allevents, #eventid option.inactive").show();
  });
  $("a#hidepast").click(function(e) {
    e.preventDefault();
    $("#allevents, #eventid option.inactive").hide();
    $("#currentevents").show();
  });
  $("a#hidepast").click();
  $("#attenddate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#attendenddate").datepicker({ dateFormat: 'yy-mm-dd' });

  $("#eventid").change(function(){  //display form stuff based on type of event selected
    if ($("#eventid option:selected").hasClass('times')) {
      $("label.times").show();
    } else {
      $("label.times").hide();
      $("label.times > input").val("");
    }
  });
});

function ValidateAttendance(){
  if (document.attendform.eid.selectedIndex == 0) {
    alert('<?=_("You must select an event.")?>');
    return false;
  }
  if ($('#attenddate').val() == '') {
    alert('<?=_("You must enter a date.")?>');
    $('#attenddate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#attenddate').val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    $('#attenddate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#attendenddate').val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    $('#attendenddate').click();
    return false;
  }
  // Validate times if event requires them
  if ($("#eventid option:selected").hasClass('times')) {
    if ($('#attendstarttime').val() == '' || $('#attendendtime').val() == '') {
      alert('<?=_("You must enter both start and end times for this event.")?>');
      return false;
    }
    // Validate time format (HH:MM)
    var timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
    if (!timeRegex.test($('#attendstarttime').val())) {
      alert('<?=_("Start time must be in HH:MM format (e.g., 09:30 or 14:00).")?>');
      $('#attendstarttime').focus();
      return false;
    }
    if (!timeRegex.test($('#attendendtime').val())) {
      alert('<?=_("End time must be in HH:MM format (e.g., 09:30 or 14:00).")?>');
      $('#attendendtime').focus();
      return false;
    }
  }
  return true;
}
</script>

<?php
if (!$ajax) footer();
?>
