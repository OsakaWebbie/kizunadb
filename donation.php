<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Donations & Pledges").(isset($_POST['pid_list']) ?
sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : "")); ?>
<meta http-equiv="expires" content="0">
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1&table=1" type="text/css" />
<?php header2(1);
// Build option list from donationtype table contents
$result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
$opts = "";
while ($row = mysqli_fetch_object($result)) {
  //$opts .= "    <option value=\"".$row->DonationTypeID."\" style=\"background-color:#".$row->BackgroundColor."\">".
  //$row->DonationType."</option>\n";
  $opts .= "    <option value=\"".$row->DonationTypeID."\">".$row->DonationType."</option>\n";
}
?>

<h1 id="title"><?=_("Donations & Pledges").(isset($_POST['pid_list']) ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : "")?></h1>
<form id="dform" method="get">
<fieldset><legend><?=_('Search Criteria').' ('._('optional').')'?></legend>
  <div id="typefilter">
    <label><?=_("Donation Types")?>: </label>
    <select id="dtselect" name="dtype[]" multiple="multiple" size="1">
    <?=$opts?>
    </select>
  </div>
  <div id="datefilter">
    <div id="dates">
    <?php printf(_("Optional Dates: after %s and/or before %s"),
       "<input type=\"text\" name=\"start\" id=\"startdate\" style=\"width:6em\" />",
       "<input type=\"text\" name=\"end\" id=\"enddate\" style=\"width:6em\" />"); ?>
    </div>
    <div id="datefillers">
      <button type="button" onclick="set_month(1);"><?=_("Last Month")?></button>
      <button type="button" onclick="set_month(0);"><?=_("This Month-to-Date")?></button>
      <button type="button" onclick="set_year(1);"><?=_("Last Year")?></button>
      <button type="button" onclick="set_year(0);"><?=_("This Year-to-Date")?></button>
    </div>
  </div>
  <div id="searchbox">
    <label class="label-n-input"><?=_("Search in description")?>: <input type="text" name="search" style="width:10em" /></label>
    <span style="white-space:nowrap"><?=sprintf(_("Donation or summary amount: ".
    "<label>%sAt least</label><label>%sNo more than</label><label>%sExactly</label> %s"),
    "<input type=\"radio\" name=\"cutofftype\" value=\">=\" checked>",
    "<input type=\"radio\" name=\"cutofftype\" value=\"<=\">",
    "<input type=\"radio\" name=\"cutofftype\" value=\"=\">",
    "￥<input type=\"text\" name=\"cutoff\" style=\"width:6em\">")?></span>
  </div>
  <div id="basketfilter">
    <label class="label-n-input"<?=(empty($_SESSION['basket'])?' style="color:#BBB"':'')?>><input type="checkbox" name="basket" value="1"<?=(empty($_SESSION['basket'])?' disabled':'')?>><?=sprintf(_("in Basket only (%d)"), count($_SESSION['basket']))?></label>
  </div>
</fieldset>
<fieldset><legend><?=_("Donation List")?></legend>
  <span class="actiontypes">
    <label class="proctype"><input type="radio" name="proc" value="" checked><?=_("All donations")?></label>
    <label class="proctype"><input type="radio" name="proc" value="proc"><?=_("Processed only")?></label>
    <label class="proctype"><input type="radio" name="proc" value="unproc"><?=_("Unprocessed only")?></label>
  </span>
  <span class="actiontypes">
    <label class="actiontype"><input type="radio" name="listtype" value="Normal" checked><?=_("Continuous List (can sort freely)")?></label>
    <label class="actiontype"><input type="radio" name="listtype" value="DonationType"><?=_("Group by Donation Type w/ subtotals")?></label>
    <label class="actiontype"><input type="radio" name="listtype" value="PersonID"><?=_("Group by Person/Org w/ subtotals")?></label>
    <label class="actiontype" style="margin-left:3em"><input type="checkbox" name="subtotalsort"><?=_("Order groups by subtotal")?></label>
  </span>
  <input type="button" id="show_don_frame" value="<?=_("Donation List").' ('._('below').')'?>">
  <input type="submit" id="show_don_tab" name="don_tab" value="<?=_("Donation List").' ('._('new tab').')'?>" formaction="donation_list.php" formtarget="_blank">
</fieldset>
<fieldset><legend><?=_("Donation Summary")?></legend>
  <span class="actiontypes">
    <label class="actiontype"><input type="radio" name="summarytype" value="DonationType" checked><?=_("By Donation Type")?></label>
    <span style="display:block"><label class="actiontype" style="display:inline"><input type="radio"
    name="summarytype" value="PersonID"><?=_("By Person/Org")?></label>
    <label style="display:inline;margin-left:1em"><?=sprintf(_("(top %s donors)"),"<input type=\"text\" name=\"limit\"".
    " style=\"width:2em\">")?></label></span>
  </span>
  <input type="button" id="show_summary_frame" value="<?=_("Donation Summary").' ('._('below').')'?>">
  <input type="submit" id="show_summary_tab" name="summary_tab" value="<?=_("Donation Summary").' ('._('new tab').')'?>" formaction="donation_summary.php" formtarget="_blank">
</fieldset>
<fieldset><legend><?=_("Pledges")?></legend>
  <label class="label-n-input"><input type="checkbox" name="closed" value="yes"><?=_("Include closed pledges")?></label>
  <label class="label-n-input"><input type="checkbox" name="psubtotals" value="yes"><?=_("Donation-Type Subtotals")?></label>
  <input type="button" id="show_pledges_frame" value="<?=_("Pledge List").' ('._('below').')'?>">
  <input type="submit" id="show_pledges_tab" name="pledge_tab" value="<?=_("Pledge List").' ('._('new tab').')'?>" formaction="pledge_list.php" formtarget="_blank">
</fieldset>
</form>
<div id="ResultFrame"></div>

<?php
$scripts = ['jquery', 'jqueryui', 'multiselect'];
if ($_SESSION['lang']=="ja_JP") $scripts[] = 'datepicker-ja';
load_scripts($scripts);
?>

<script>
  $(function() {
    $("#dtselect").multiselect({
      noneSelectedText: '<?=_("Select...")?>',
      selectedText: '<?=_("# selected")?>',
      checkAllText: '<?=_("Check all")?>',
      uncheckAllText: '<?=_("Uncheck all")?>'
//   }).multiselectfilter({
//    label: '<?=_("Search:")?>'
    });
    $("#startdate").datepicker({ dateFormat: 'yy-mm-dd' });
    $("#enddate").datepicker({ dateFormat: 'yy-mm-dd' });

    // AJAX handlers for "below" buttons
    $("#show_don_frame").click(function(){
      var formData = $('#dform').serialize() + '&ajax=1';
      $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
      $.get('donation_list.php', formData, function(response) {
        $('#ResultFrame').html(response);
      });
    });

    $("#show_summary_frame").click(function(){
      var formData = $('#dform').serialize() + '&ajax=1';
      $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
      $.get('donation_summary.php', formData, function(response) {
        $('#ResultFrame').html(response);
      });
    });

    $("#show_pledges_frame").click(function(){
      var formData = $('#dform').serialize() + '&ajax=1';
      // For pledges, need to add dtgrouped if psubtotals is checked
      if ($('input[name=psubtotals]').is(':checked')) formData += '&dtgrouped=1';
      $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
      $.get('pledge_list.php', formData, function(response) {
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


/*$('#dform').submit(function(e) {
    e.preventDefault(); //prevent default action
    var post_url = $(this).attr("action"); //get form action url
    var form_data = $(this).serialize(); //Encode form elements for submission

    $.get( post_url, form_data, function( response ) {
      $("#server-results").html( response );
    });
  });*/
</script>
<?php
footer();
?>
