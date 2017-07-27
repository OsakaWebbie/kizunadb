<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Event Attendance").($_POST['pid_list']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : "")); ?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/JavaScript" src="js/jquery.ui.datepicker-ja.js"></script>
<script type="text/javascript" src="js/jquery.multiselect-classes.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.filter.js"></script>

<script type="text/javascript">

$(document).ready(function(){
  $("#emultiple").multiselect({
    noneSelectedText: '<?=_("Select...")?>',
    selectedText: '<?=_("# selected")?>',
    checkAllText: '<?=_("Check all")?>',
    uncheckAllText: '<?=_("Uncheck all")?>'
  }).multiselectfilter({
    label: '<?=_("Search:")?>'
  });
<?php if($_SESSION['lang']=="ja_JP") echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n"; ?>
  $("#startdate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#enddate").datepicker({ dateFormat: 'yy-mm-dd' });

  $('input[name=ftarget]').change(function() {
    $('form#eform').attr({target:$('input[name=ftarget]:checked').val()});
  });
  $("#show_detail").click(function(){
    if ($("#eid").val()=="") {
      alert("<?=_("Please select an event.")?>");
    } else {
      $('form#eform').attr({action:"attend_detail.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
      $('form#eform').submit();
    }
  });
  $("#show_aggregate").click(function(){
    if ($("form#eform option:selected").length < 2) {
      alert("<?=_("Please select at least one event.")?>");
    } else {
      $('form#eform').attr({action:"attend_aggregate.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
      $('form#eform').submit();
    }
  });
  $("#show_datesums").click(function(){
    if ($("form#eform option:selected").length < 2) {
      alert("<?=_("Please select at least one event.")?>");
    } else {
      $('form#eform').attr({action:"attend_datesums.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
      $('form#eform').submit();
    }
  });
});
</script>
<?php header2(1);
// Build option list from event table contents
$result = sqlquery_checked("SELECT * FROM event ORDER BY (EventEndDate IS NOT NULL OR EventEndDate<NOW()),Event");
$opts = "";
while ($row = mysqli_fetch_object($result)) {
  $opts .= "    <option value=\"".$row->EventID."\" class=\"".
  ($row->EventEndDate!==NULL && $row->EventEndDate<today() ? "inactive" : "active")."\">".
  $row->Event." (".$row->EventStartDate.($row->EventStartDate!=$row->EventEndDate ? "～".($row->EventEndDate!==NULL ? $row->EventEndDate : "") : "").")</option>\n";
}
?>

<h1 id="title"><?=_("Event Attendance").($_POST['pid_list']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : "")?></h1>
<form id="eform" method="post" action="blank.php" target="ResultFrame">
<input type="hidden" name="preselected" value="<?=$_POST['pid_list']?>">
<div id="dates">
<?php printf(_("Optional Dates: after %s and/or before %s"),
   "<input type=\"text\" name=\"startdate\" id=\"startdate\" style=\"width:6em\" />",
   "<input type=\"text\" name=\"enddate\" id=\"enddate\" style=\"width:6em\" />"); ?>
</div>
<div class="section">
  <label for="eid"><?=_("Single Event, Detail Info")?>: </label>
  <select size="1" id="eid" name="eid">
    <option value=""><?=_("Select an event...")?></option>
<?=$opts?>
  </select>
  <input type="button" id="show_detail" name="show_detail" value="<?=_("Show Detail Chart")?>">
</div>
<div class="section">
  <label for="emultiple"><?=_("Multiple Events, Aggregate Info")?>: </label>
  <select id="emultiple" name="emultiple[]" multiple="multiple" size="9">
<?=$opts?>
  </select>
  ( <?=sprintf(_("Show only attendance of at least %sX"),"<input type=\"text\" name=\"min\" size=\"2\">")?> ) &nbsp;
  <input type="button" id="show_aggregate" name="show_aggregate" value="<?=_("Aggregate List by Attendee")?>">
  <input type="button" id="show_datesums" name="show_datesums" value="<?=_("Number Chart by Event and Date")?>">
</div>
<p><?=sprintf(_("Show in: %sframe below&nbsp; %snew window"),
"<input type=\"radio\" id=\"radio_frame\" name=\"ftarget\" value=\"ResultFrame\" checked>",
"<input type=\"radio\" id=\"radio_window\" name=\"ftarget\" value=\"_blank\">")?></p>
</form>
<iframe name="ResultFrame" width="100%" height="320" src="blank.php"></iframe>

<?php
footer();
?>
