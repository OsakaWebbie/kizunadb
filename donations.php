<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Donations & Pledges").($_POST['pid_list']!="" ?
sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : "")); ?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/JavaScript" src="js/jquery.ui.datepicker-ja.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.min.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.filter.js"></script>

<script type="text/JavaScript">

$(document).ready(function(){
  $("#dtselect").multiselect({
    noneSelectedText: '<? echo _("Select..."); ?>',
    selectedText: '<? echo _("# selected"); ?>',
    checkAllText: '<? echo _("Check all"); ?>',
    uncheckAllText: '<? echo _("Uncheck all"); ?>'
//   }).multiselectfilter({
//    label: '<? echo _("Search:"); ?>'
   });
<? if($_SESSION['lang']=="ja_JP") echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n"; ?>
  $("#startdate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#enddate").datepicker({ dateFormat: 'yy-mm-dd' });

  $('input[name=ftarget]').change(function() {
    $('#dform').attr({target:$('input[name=ftarget]:checked').val()});
  });
  $("#show_list").click(function(){
    $('#dform').attr({action:"donation_list<?=($_SESSION['userid']=="karen" && $_SESSION['client']=="dev"?"_new":"")?>.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
    $('#dform').submit();
  });
  $("#show_summary").click(function(){
    $('#dform').attr({action:"donation_list<?=($_SESSION['userid']=="karen" && $_SESSION['client']=="dev"?"_new":"")?>.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
    $('#dform').submit();
  });
  $("#show_pledges").click(function(){
    $('#dform').attr({action:"pledge_list.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
    $('#dform').submit();
  });
});

function set_year(subtractor) {
  var today = new Date();
  var year = today.getFullYear() - subtractor;
  $("#startdate").val(year+"-1-1");
  if (subtractor == 0)  $("#enddate").val(year+"-"+(today.getMonth()+1)+"-"+today.getDate());
  else  $("#enddate").val(year+"-12-31");
}

function set_month(subtractor) {
  var today = new Date();
  if (subtractor == 0) {
    $("#startdate").val(today.getFullYear()+"-"+(today.getMonth()+1)+"-1");
    $("#enddate").val(today.getFullYear()+"-"+(today.getMonth()+1)+"-"+today.getDate());
  } else {
    var year = today.getFullYear();
    var month = today.getMonth();
    for (var i=subtractor; i>0; i--) {
      month--;
      if (month == -1) {
        month = 11;
        year = year - 1;
      }
    }
    month++; //needed for both the display and the trick to get lastday
    $("#startdate").val(year+"-"+month+"-1");
    var lastday = new Date(year, month, 0).getDate(); //trick found on internet
    $("#enddate").val(year+"-"+month+"-"+lastday);
  }
}

</script>
<? header2(1);
// Build option list from donationtype table contents
$result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
$opts = "";
while ($row = mysql_fetch_object($result)) {
  $opts .= "    <option value=\"".$row->DonationTypeID."\" style=\"background-color:#".$row->BackgroundColor."\">".
  $row->DonationType."</option>\n";
}
?>

<h1 id="title"><? echo _("Donations & Pledges").($_POST['pid_list']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : ""); ?></h1>
<form id="dform" method="post" action="blank.html" target="ResultFrame">
<input type="hidden" name="preselected" value="<? echo $_POST['pid_list']; ?>">
<fieldset><legend><? echo _("Donations"); ?></legend>
  <div id="typefilter">
    <label><? echo _("Donation Types"); ?>: </label>
    <select id="dtselect" name="dtype[]" multiple="multiple" size="1">
    <? echo $opts; ?>
    </select>
  </div>
  <div id="datefilter">
    <div id="dates">
    <? printf(_("Optional Dates: after %s and/or before %s"),
       "<input type=\"text\" name=\"start\" id=\"startdate\" style=\"width:6em\" />",
       "<input type=\"text\" name=\"end\" id=\"enddate\" style=\"width:6em\" />"); ?>
    </div>
    <div id="datefillers">
      <button type="button" onclick="set_month(1);"><? echo _("Last Month"); ?></button>
      <button type="button" onclick="set_month(0);"><? echo _("This Month-to-Date"); ?></button>
      <button type="button" onclick="set_year(1);"><? echo _("Last Year"); ?></button>
      <button type="button" onclick="set_year(0);"><? echo _("This Year-to-Date"); ?></button>
    </div>
  </div>
  <div id="searchbox">
    <label class="label-n-input"><?=_("Search in description")?>: <input type="text" name="search"
    　　　　style="width:10em" /></label>
    <span style="white-space:nowrap"><?=sprintf(_("Donation or summary amount: ".
    "<label>%sAt least</label><label>%sNo more than</label><label>%sExactly</label> %s"),
    "<input type=\"radio\" name=\"cutofftype\" value=\">=\" checked>",
    "<input type=\"radio\" name=\"cutofftype\" value=\"<=\">",
    "<input type=\"radio\" name=\"cutofftype\" value=\"=\">",
    "￥<input type=\"text\" name=\"cutoff\" style=\"width:6em\">")?></span>
  </div>
  <div class="actions">
    <span class="actiontypes">
      <label class="proctype"><input type="radio" name="proc" value="" checked><? echo _("All donations"); ?></label>
      <label class="proctype"><input type="radio" name="proc" value="proc"><? echo _("Processed only"); ?></label>
      <label class="proctype"><input type="radio" name="proc" value="unproc"><? echo _("Unprocessed only"); ?></label>
    </span>
    <span class="actiontypes">
      <label class="actiontype"><input type="radio" name="listtype" value="Normal" checked><? echo _("Continuous List (can sort freely)"); ?></label>
      <label class="actiontype"><input type="radio" name="listtype" value="DonationType"><? echo _("Group by Donation Type w/ subtotals"); ?></label>
      <label class="actiontype"><input type="radio" name="listtype" value="PersonID"><? echo _("Group by Person/Org w/ subtotals"); ?></label>
      <label class="actiontype" style="margin-left:3em"><input type="checkbox" name="subtotalsort"><?=_("Order groups by subtotal")?></label>
    </span>
    <input type="submit" id="show_list" name="show_list" value="<? echo _("Donation List"); ?>" />
  </div>
  <div class="actions">
    <span class="actiontypes">
      <label class="actiontype"><input type="radio" name="summarytype" value="DonationType" checked><? echo _("By Donation Type"); ?></label>
      <span style="display:block"><label class="actiontype" style="display:inline"><input type="radio"
      name="summarytype" value="PersonID"><? echo _("By Person/Org"); ?></label>
      <label style="display:inline;margin-left:1em"><?=sprintf(_("(top %s donors)"),"<input type=\"text\" name=\"limit\"".
      " style=\"width:2em\">")?></label></span>
    </span>
    <input type="submit" id="show_summary" name="show_summary" value="<? echo _("Donation Summary"); ?>">
  </div>
</fieldset>
<fieldset><legend><? echo _("Pledges"); ?></legend>
  <label class="label-n-input"><input type="checkbox" name="closed" value="yes"><? echo _("Include closed pledges"); ?></label>
  <label class="label-n-input"><input type="checkbox" name="psubtotals" value="yes"><? echo _("Donation-Type Subtotals"); ?></label>
  <input type="submit" id="show_pledges" name="show_p" value="<? echo _("Pledge List"); ?>">
</fieldset>
<p><? echo sprintf(_("Show in: %sframe below&nbsp; %snew window"),
"<input type=\"radio\" id=\"radio_frame\" name=\"ftarget\" value=\"ResultFrame\" checked>",
"<input type=\"radio\" id=\"radio_window\" name=\"ftarget\" value=\"_blank\">"); ?></p>
</form>
<iframe name="ResultFrame" width="100%" height="320" src="blank.html"></iframe>
<?
footer();
?>
