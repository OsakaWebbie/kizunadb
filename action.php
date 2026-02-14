<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Action List")); ?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1" type="text/css" />

<?php header2(1); ?>
<h1 id="title"><?=_("Action List")?></h1>

<form id="aform" method="get" action="blank.php" target="ResultFrame">
  <div class="section">
    <div id="listtypes">
      <label class="label-n-input"><input type="radio" name="listtype" value="Normal" checked><?=_("Continuous List (can sort freely)")?></label>
      <label class="label-n-input"><input type="radio" name="listtype" value="ActionType"><?=_("Group by Action Type")?></label>
      <label class="label-n-input"><input type="radio" name="listtype" value="PersonID"><?=_("Group by Person")?></label>
    </div>
    <label class="label-n-input"><?=_("Action Type")?>: <select id="atype" name="atype[]" multiple="multiple" size="1">
<?php
$result = sqlquery_checked("SELECT * FROM actiontype ORDER BY ActionType");
while ($row = mysqli_fetch_object($result)) {
  echo "    <option value=\"".$row->ActionTypeID."\">".$row->ActionType."</option>\n";
}
?>
    </select></label>
    <span class="label-n-input"><?php printf(_("Optional Dates: after %s and/or before %s"),
    "<input type=\"text\" name=\"startdate\" id=\"startdate\" style=\"width:6em\" />",
    "<input type=\"text\" name=\"enddate\" id=\"enddate\" style=\"width:6em\" />");
    /* span, not label, because a single label around both fields breaks datepicker */ ?>
    </span>
    <label class="label-n-input"><?=_("Search")?>: <input type="text" name="csearch" style="width:10em"></label>
<?php if (!empty($_SESSION['bucket'])) { ?>
    <label class="label-n-input"><input type="checkbox" name="bucket" value="1"><?=sprintf(_("Limit to Bucket (%d)"), count($_SESSION['bucket']))?></label>
<?php } ?>
    <input type="button" id="show_actions" name="show_actions" value="<?=_("Show List")?>">
  </div>
  <p style="clear:both"><?=sprintf(_("Show in: %sframe below&nbsp; %snew window"),
  "<input type=\"radio\" id=\"radio_frame\" name=\"ftarget\" value=\"ResultFrame\" checked>",
  "<input type=\"radio\" id=\"radio_window\" name=\"ftarget\" value=\"_blank\">")?></p>
</form>
<iframe name="ResultFrame" width="100%" height="320" src="blank.php"></iframe>

<?php
$scripts = ['jquery', 'jqueryui', 'multiselect'];
if ($_SESSION['lang']=="ja_JP") $scripts[] = 'datepicker-ja';
load_scripts($scripts);
?>
<script type="text/javascript">

$(document).ready(function(){
  $("#atype").multiselect({
    noneSelectedText: '<?=_("Select...")?>',
    selectedText: '<?=_("# selected")?>',
    checkAllText: '<?=_("Check all")?>',
    uncheckAllText: '<?=_("Uncheck all")?>'
  }).multiselectfilter({
    label: '<?=_("Search:")?>'
  });
  $("#startdate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#enddate").datepicker({ dateFormat: 'yy-mm-dd' });

  $('input[name=ftarget]').change(function() {
    $('form#aform').attr({target:$('input[name=ftarget]:checked').val()});
  });
  $("#show_actions").click(function(){
    $('#aform').attr({action:"action_chart.php?nav="+(($('input[name=ftarget]:checked').val()=="_blank")?"1":"0")});
    $('#aform').submit();
  });
});
</script>
<?php
footer();
?>
