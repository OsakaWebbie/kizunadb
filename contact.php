<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Contact List").
($_POST['pid_list']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : "")); ?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/JavaScript" src="js/jquery.ui.datepicker-ja.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.min.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.filter.js"></script>

<script type="text/javascript">

$(document).ready(function(){
  $("#ctype").multiselect({
    noneSelectedText: '<? echo _("Select..."); ?>',
    selectedText: '<? echo _("# selected"); ?>',
    checkAllText: '<? echo _("Check all"); ?>',
    uncheckAllText: '<? echo _("Uncheck all"); ?>'
  }).multiselectfilter({
    label: '<? echo _("Search:"); ?>'
  });
<? if($_SESSION['lang']=="ja_JP") echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n"; ?>
  $("#startdate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#enddate").datepicker({ dateFormat: 'yy-mm-dd' });

  $('input[name=ftarget]').change(function() {
    $('form#cform').attr({target:$('input[name=ftarget]:checked').val()});
  });
  $("#show_contacts").click(function(){
    $('#cform').attr({action:"contact_chart.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
    $('#cform').submit();
  });
});
</script>
<? header2(1); ?>
<h1 id="title"><?=_("Contact List").($_POST['pid_list']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),
substr_count($_POST['pid_list'],",")+1) : "")?></h1>

<form id="cform" method="post" action="blank.html" target="ResultFrame">
  <input type="hidden" name="preselected" value="<? echo $_POST['pid_list']; ?>">
  <div class="section">
    <div id="listtypes">
      <label class="label-n-input"><input type="radio" name="listtype" value="Normal" checked><? echo _("Continuous List (can sort freely)"); ?></label>
      <label class="label-n-input"><input type="radio" name="listtype" value="ContactType"><? echo _("Group by Contact Type"); ?></label>
      <label class="label-n-input"><input type="radio" name="listtype" value="PersonID"><? echo _("Group by Person"); ?></label>
    </div>
    <label class="label-n-input"><?=_("Contact Type")?>: <select id="ctype" name="ctype[]" multiple="multiple" size="1">
<?
$result = sqlquery_checked("SELECT * FROM contacttype ORDER BY ContactType");
while ($row = mysql_fetch_object($result)) {
  echo "    <option value=\"".$row->ContactTypeID."\">".$row->ContactType."</option>\n";
}
?>
    </select></label>
    <span class="label-n-input"><? printf(_("Optional Dates: after %s and/or before %s"),
    "<input type=\"text\" name=\"startdate\" id=\"startdate\" style=\"width:6em\" />",
    "<input type=\"text\" name=\"enddate\" id=\"enddate\" style=\"width:6em\" />"); ?>
    </span>
    <label class="label-n-input"><?=_("Search")?>: <input type="text" name="csearch" style="width:10em"></label>
    <input type="button" id="show_contacts" name="show_contacts" value="<? echo _("Show List"); ?>">
  </div>
  <p><? echo sprintf(_("Show in: %sframe below&nbsp; %snew window"),
  "<input type=\"radio\" id=\"radio_frame\" name=\"ftarget\" value=\"ResultFrame\" checked>",
  "<input type=\"radio\" id=\"radio_window\" name=\"ftarget\" value=\"_blank\">"); ?></p>
</form>
<iframe name="ResultFrame" width="100%" height="320" src="blank.html"></iframe>

<? footer(); ?>
