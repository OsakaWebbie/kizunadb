<?php
include('functions.php');
include('accesscontrol.php');

header1(_('Event Attendance')); ?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1&table=1" type="text/css" />
<?php header2(1);
// Build option list from event table contents
$result = sqlquery_checked("SELECT * FROM event ORDER BY (EventEndDate!='0000-00-00' AND EventEndDate<NOW()),Event");
$opts = '';
while ($row = mysqli_fetch_object($result)) {
  $opts .= '    <option value="'.$row->EventID.'" class="'.
  ($row->EventEndDate!=='0000-00-00' && $row->EventEndDate<today() ? 'inactive' : 'active')."\">".
  $row->Event.' ('.$row->EventStartDate.($row->EventStartDate!=$row->EventEndDate ? '～'.($row->EventEndDate!=='0000-00-00' ? $row->EventEndDate : '') : '').")</option>\n";
}
?>

<h1 id="title"><?=_('Event Attendance')?></h1>
<form id="eform" method="get" action="blank.php">
<div id="filter">
<?php
printf(_('Optional Dates: after %s and/or before %s'),
   '<input type="text" name="startdate" id="startdate" style="width:6em" />',
   '<input type="text" name="enddate" id="enddate" style="width:6em" />');
?>
  <label class="label-n-input basketfilter"<?=(empty($_SESSION['basket'])?' style="color:#BBB"':'')?>><input type="checkbox" name="basket" value="1"<?=(empty($_SESSION['basket'])?' disabled':'')?>><?=sprintf(_("in Basket only (%d)"), count($_SESSION['basket']))?></label>
</div>
<div class="section">
  <label for="eid"><?=_("Single Event, Detail Info")?>: </label>
  <select size="1" id="eid" name="eid">
    <option value=""><?=_("Select an event...")?></option>
<?=$opts?>
  </select>
  <input type="button" id="show_detail_below" value="<?=_("Show Detail Chart").' ('._('below').')'?>">
  <input type="submit" id="show_detail_tab" value="<?=_("Show Detail Chart").' ('._('new tab').')'?>" formaction="attend_detail.php" formtarget="_blank">
</div>
<div class="section">
  <label for="emultiple"><?=_("Multiple Events, Aggregate Info")?>: </label>
  <select id="emultiple" name="emultiple[]" multiple="multiple" size="9">
<?=$opts?>
  </select>
  ( <?=sprintf(_("Show only attendance of at least %sX"),"<input type=\"text\" name=\"min\" size=\"2\">")?> ) &nbsp;
  <div>
    <input type="button" id="show_aggregate_below" value="<?=_("Aggregate List by Attendee").' ('._('below').')'?>">
    <input type="submit" id="show_aggregate_tab" value="<?=_("Aggregate List by Attendee").' ('._('new tab').')'?>" formaction="attend_aggregate.php" formtarget="_blank">
  </div>
  <div>
    <input type="button" id="show_datesums_below" value="<?=_("Number Chart by Event and Date").' ('._('below').')'?>">
    <input type="submit" id="show_datesums_tab" value="<?=_("Number Chart by Event and Date").' ('._('new tab').')'?>" formaction="attend_datesums.php" formtarget="_blank">
  </div>
</div>
</form>
<div id="ResultFrame"></div>

<?php
$scripts = ['jquery', 'jqueryui', 'multiselect-classes'];
if ($_SESSION['lang']=='ja_JP') $scripts[] = 'datepicker-ja';
load_scripts($scripts);
?>
<script type="text/javascript">

  $(document).ready(function(){
    $("#emultiple").multiselect({
      noneSelectedText: '<?=_('Select...')?>',
      selectedText: '<?=_('# selected')?>',
      checkAllText: '<?=_('Check all')?>',
      uncheckAllText: '<?=_('Uncheck all')?>',
      show: null,
      hide: null
    }).multiselectfilter({
      label: '<?=_('Search:')?>'
    });
    $('#startdate').datepicker({ dateFormat: 'yy-mm-dd' });
    $('#enddate').datepicker({ dateFormat: 'yy-mm-dd' });

    // AJAX handlers for "below" buttons
    $('#show_detail_below').click(function(){
      if ($('#eid').val()=='') {
        alert("<?=_('Please select an event.')?>");
      } else {
        var formData = $('#eform').serialize() + '&ajax=1';
        $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
        $.get('attend_detail.php', formData, function(response) {
          $('#ResultFrame').html(response);
        });
      }
    });

    $('#show_aggregate_below').click(function(){
      if ($("form#eform option:selected").length < 2) {
        alert("<?=_("Please select at least one event.")?>");
      } else {
        var formData = $('#eform').serialize() + '&ajax=1';
        $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
        $.get('attend_aggregate.php', formData, function(response) {
          $('#ResultFrame').html(response);
        });
      }
    });

    $('#show_datesums_below').click(function(){
      if ($("form#eform option:selected").length < 2) {
        alert("<?=_("Please select at least one event.")?>");
      } else {
        var formData = $('#eform').serialize() + '&ajax=1';
        $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
        $.get('attend_datesums.php', formData, function(response) {
          $('#ResultFrame').html(response);
        });
      }
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
