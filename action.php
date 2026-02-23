<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Action List")); ?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1&table=1" type="text/css" />

<?php header2(1); ?>
<h1 id="title"><?=_("Action List")?></h1>

<form id="aform" method="get" action="blank.php">
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
    <label class="label-n-input"<?=(empty($_SESSION['basket'])?' style="color:#BBB"':'')?>><input type="checkbox" name="basket" value="1"<?=(empty($_SESSION['basket'])?' disabled':'')?>><?=sprintf(_("in Basket only (%d)"), count($_SESSION['basket']))?></label>
    <input type="button" id="show_actions_below" value="<?=_("Show List").' ('._('below').')'?>">
    <input type="submit" value="<?=_("Show List").' ('._('new tab').')'?>" formaction="action_list.php" formtarget="_blank">
  </div>
</form>
<div id="ResultFrame"></div>

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

  // AJAX handler for "below" button
  $("#show_actions_below").click(function(){
    var formData = $('#aform').serialize() + '&ajax=1';
    $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
    $.get('action_list.php', formData, function(response) {
      $('#ResultFrame').html(response);
    });
  });

  // Event delegation for forms inside result div
  $('#ResultFrame').on('submit', 'form', function(e) {
    var target = $(this).attr('target') || '';
    if (target === '_blank' || target === '_top') return; // let these navigate normally
    e.preventDefault();
    var url = $(this).attr('action');
    var data = $(this).serialize();
    if (data) data += '&ajax=1'; else data = 'ajax=1';
    $.get(url, data, function(r) { $('#ResultFrame').html(r); });
  });
});
</script>
<?php
footer();
?>
