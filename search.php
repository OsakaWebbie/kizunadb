<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Search").($_POST['pid_list']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : "")); ?>

<meta http-equiv="expires" content="0">
<link rel="stylesheet" type="text/css" href="style.php?page=<?=$_SERVER['PHP_SELF']?>&jquery=1&multiselect=1" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.min.js"></script>
<script type="text/javascript" src="js/jquery.multiselect.filter.js"></script>

<script type="text/JavaScript">
$(document).ready(function(){
  $("#ctstartdate1").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#ctenddate1").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#dtstartdate1").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#dtenddate1").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#astartdate1").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#aenddate1").datepicker({ dateFormat: 'yy-mm-dd' });

  $("#catselect1,#ctselect1,#dtselect1,#eventselect1").multiselect({
    noneSelectedText: '<? echo _("Select..."); ?>',
    selectedText: '<? echo _("# selected"); ?>',
    checkAllText: '<? echo _("Check all"); ?>',
    uncheckAllText: '<? echo _("Uncheck all"); ?>',
    /*close: function(){ alert("Select closed!"); }, PART OF MY FUTURE AJAX COUNT FEATURE */
  }).multiselectfilter({
    label: '<? echo _("Search:"); ?>'
  });

  $("button.dup").click(function(){  //add a new instance of the same type of search option
    var newsearch = $(this).prev().clone();
    var oldnumber = parseInt(newsearch.attr("id").substr(newsearch.attr("id").length-1,1));
    if (oldnumber > 8) {
      $(this).hide();  //hide the button to keep from going into double digits
    }
    newsearch.attr("id",newsearch.attr("id").replace(oldnumber, oldnumber+1));  //the top level div
    newsearch.find("*").each(function() {
      if ($(this).attr("id") != undefined) {
        $(this).attr("id", $(this).attr("id").replace(oldnumber, oldnumber+1));
        // need to delete cloned remnants of jQuery widgets and recreate them; I have no clue why
        if ($(this).attr("id").search("date") != -1) {
          $(this).removeClass("hasDatepicker");
          $(this).datepicker({ dateFormat: 'yy-mm-dd' });
        }
        if ($(this).attr("id").search("select") != -1) {
          $(this).siblings(".ui-multiselect").remove();
          $(this).siblings(".ui-multiselect-menu").remove();
          $(this).multiselect({
            noneSelectedText: 'Select...',
            selectedText: '# selected',
            checkAllText: 'Check all',
            uncheckAllText: 'Uncheck all'
          }).multiselectfilter({
            label: '<? echo _("Search:"); ?>'
          });
        }
      }
      if ($(this).attr("name") != undefined) {
        $(this).attr("name", $(this).attr("name").replace(oldnumber, oldnumber+1));
      }
    });
    if (oldnumber == 1) { var plus = "+"; } else { var plus = "&nbsp;"; }
    newsearch.prepend('<span class="plus">'+plus+'</span>' );
    $(this).before(newsearch);
  });

  $('#showadvanced').click(function(){
    $('.advanced').show();
    $('.simpleonly').hide();
  });
  
  $(':radio.OP').click(function() {
    $('option.useforO').show();
    $('option.useforP').show();
  });
  $(':radio.O').click(function() {
    $('option.useforO').show();
    $('option.useforP:selected').prop('selected', false);
    $('option.useforP').hide();
  });
  $(':radio.P').click(function() {
    $('option.useforO:selected').prop('selected', false);
    $('option.useforO').hide();
    $('option.useforP').show();
  });

  $("#searchform").submit(function() {
    if ($('#preselected').value != "") {  //preselected ID list might be long, so put in POST
alert("preselected = #"+$('#preselected')+value+"#");
      $('#searchform').attr("method", "post");
    }
  });

  document.getElementById("textinput1").focus();
});
</script>
<?
header2(1); ?>
<h1 id="title"><? echo $_SESSION['dbtitle'].": "._("Search").(isset($_POST['pid_list']) ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['pid_list'],",")+1) : ""); ?></h1>
<? if (isset($text)) echo "<h3 class=\"alert\">".urldecode($text)."</h3>"; ?>

<form id="searchform" action="list.php" method="<?=(isset($_POST['pid_list']) ? "post" : "get")?>">
<div id="buttonsection">
  <label class="label-n-input"><input type="checkbox" name="countonly"><?=_("Count Only")?></label>
  <button id="search" type="submit"><?=_("Search!")?></button>
</div>
<input type="hidden" id="preselected" name="preselected" value="<? echo $_POST['pid_list']; ?>">
<h2 class="simpleonly"><? $txt=_("records"); printf(_("Search for %s that..."),$txt); ?></h2>
<h2 class="advanced">
<? $txt="<span class=\"radiogroup\">".
"<label><input type=\"radio\" name=\"filter\" value=\"Records\" checked class=\"OP\" />"._("All Records")."</label>".
"<label><input type=\"radio\" name=\"filter\" value=\"People\" class=\"P\" />"._("Only People")."</label>".
"<label><input type=\"radio\" name=\"filter\" value=\"Organizations\" class=\"O\" />"._("Only Organizations")."</label><br />".
"<label><input type=\"radio\" name=\"filter\" value=\"OrgsOfPeople\" class=\"P\" />"._("Organizations with Members")."</label><br />".
"<label><input type=\"radio\" name=\"filter\" value=\"PeopleOfOrgs\" class=\"O\" />"._("People who belong to Organizations")."</label></span>";
printf(_("Search for %s that..."),$txt);
?></h2>
<fieldset class="simple">
  <div id="text1" class="criteria">
<?
$in = "<span class=\"advanced\"><span class=\"radiogroup\"><label><input type=\"radio\" name=\"textinout1\" value=\"IN\" checked />";
$out = "</label><label><input type=\"radio\" name=\"textinout1\" value=\"OUT\" />";
$inoutfinish = "</label></span></span><span class=\"simpleonly\">"._("...have")."</span>\n";
$text = "<input type=\"text\" name=\"textinput1\" id=\"textinput1\" style=\"width:10em\" />\n";
$target = "<select size=\"1\" name=\"texttarget1\">\n";
$target .= "  <option value=\"Name\">"._("Name")."/".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana"))."/"._("Label Name")."</option>\n";
$target .= "  <option value=\"Address\">"._("Address")."</option>\n";
$target .= "  <option value=\"Phone\">"._("Phone")."/"._("Cell Phone")."/"._("FAX")."</option>\n";
$target .= "  <option value=\"Email\">"._("Email Address")."</option>\n";
$target .= "  <option value=\"URL\">"._("URL")."</option>\n";
$target .= "  <option value=\"Country\">"._("Country")."</option>\n";
$target .= "  <option value=\"Remarks\">"._("Remarks")."</option>\n";
$target .= "  <option value=\"PersonID\">"._("ID (exact match)")."</option>\n";
$target .= "</select>\n";
printf(_("%s...have%s...don't have%s%s in %s"), $in, $out, $inoutfinish, $text, $target);
?>
  </div>
  <button type="button" id="textdup" class="dup advanced"><? echo _("Add another..."); ?></button>
</fieldset>

<fieldset class="advanced"><legend><?=_("Categories")?></legend>
  <div id="cat1" class="criteria">
<?
$in = "<span class=\"radiogroup\"><label><input type=\"radio\" name=\"catinout1\" value=\"IN\" checked />";
$out = "</label><label><input type=\"radio\" name=\"catinout1\" value=\"OUT\" />";
$inoutfinish = "</label></span>\n";
$catselect = "<select name=\"catselect1[]\" id=\"catselect1\" size=\"3\" multiple=\"multiple\">\n";
$result = sqlquery_checked("SELECT * FROM category ORDER BY Category");
while ($row = mysql_fetch_object($result)) {
  $catselect .= "    <option value=\"".$row->CategoryID."\" class=\"usefor".$row->UseFor."\">".d2h($row->Category)."</option>\n";
}
$catselect .= "</select>\n";
printf(_("%s...are in%s...are not in%s one of these categories:%s"), $in, $out, $inoutfinish, $catselect);
?>
  </div>
  <button type="button" id="catdup" class="dup advanced"><? echo _("Add another..."); ?></button>
  <p class="comment"><? echo _("(To select multiple possible categories (\"this one OR that one\"), hold Ctrl key while clicking.)"); ?></p>
</fieldset>

<fieldset class="advanced"><legend><?=_("Contacts")?></legend>
  <div id="contact1" class="criteria">
<?
$in = "<span class=\"radiogroup\"><label><input type=\"radio\" name=\"contactinout1\" value=\"IN\" checked />";
$out = "</label><label><input type=\"radio\" name=\"contactinout1\" value=\"OUT\" />";
$inoutfinish = "</label></span>\n";
$ctselect = "<select size=\"3\" id=\"ctselect1\" name=\"ctselect1[]\" multiple=\"multiple\">\n";
$result = sqlquery_checked("SELECT * FROM contacttype ORDER BY ContactType");
while ($row = mysql_fetch_object($result)) {
  $ctselect .= "    <option value=\"".$row->ContactTypeID."\">".d2h($row->ContactType)."</option>";
}
$ctselect .= "</select>\n";
$ctstartdate = "<input type=\"text\" name=\"ctstartdate1\" id=\"ctstartdate1\" style=\"width:6em\" />";
$ctenddate = "<input type=\"text\" name=\"ctenddate1\" id=\"ctenddate1\" style=\"width:6em\" />";
printf(_("%s...have%s...don't have%s contacts of one of these types:%s ".
"<span class=\"inputgroup\"><label>(after %s)</label><label>(before %s)</label></span>"),
$in, $out, $inoutfinish, $ctselect, $ctstartdate, $ctenddate);
?>
  </div>
  <button type="button" id="contactdup" class="dup advanced"><? echo _("Add another..."); ?></button>
  <p class="comment"><? echo _("(To select multiple possible types (\"this one OR that one\"), hold Ctrl key while clicking.)"); ?></p>
</fieldset>

<? if ($_SESSION['donations']) { ?>
<fieldset class="advanced"><legend><?=_("Donations")?></legend>
  <div id="donation1" class="criteria">
  <?
  $in = "<span class=\"radiogroup\"><label><input type=\"radio\" name=\"donationinout1\" value=\"IN\" checked />";
  $out = "</label><label><input type=\"radio\" name=\"donationinout1\" value=\"OUT\" />";
  $inoutfinish = "</label></span>\n";
  $dtselect = "<select size=\"3\" id=\"dtselect1\" name=\"dtselect1[]\" multiple=\"multiple\">\n";
  $result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
  while ($row = mysql_fetch_object($result)) {
    $dtselect .= "    <option value=\"".$row->DonationTypeID."\">".d2h($row->DonationType)."</option>";
  }
  $dtselect .= "</select>\n";
  $dtstartdate = "<input type=\"text\" name=\"dtstartdate1\" id=\"dtstartdate1\" style=\"width:6em\" />";
  $dtenddate = "<input type=\"text\" name=\"dtenddate1\" id=\"dtenddate1\" style=\"width:6em\" />";
  printf(_("%s...have%s...don't have%s donations of one of these types:%s ".
  "<span class=\"inputgroup\"><label>(after %s)</label><label>(before %s)</label></span>"),
  $in, $out, $inoutfinish, $dtselect, $dtstartdate, $dtenddate);
  ?>
  </div>
  <button type="button" id="donationdup" class="dup advanced"><? echo _("Add another..."); ?></button>
  <p class="comment"><? echo _("(To select multiple possible types (\"this one OR that one\"), hold Ctrl key while clicking.)"); ?></p>
</fieldset>
<? } // end of if donations ?>

<fieldset class="advanced"><legend><?=_("Attendance")?></legend>
  <div id="attend1" class="criteria">
<?
$in = "<span class=\"radiogroup\"><label><input type=\"radio\" name=\"attendinout1\" value=\"IN\" checked />";
$out = "</label><label><input type=\"radio\" name=\"attendinout1\" value=\"OUT\" />";
$inoutfinish = "</label></span>\n";
$eventselect = "<select size=\"3\" id=\"eventselect1\" name=\"eventselect1[]\" multiple=\"multiple\">\n";
$result = sqlquery_checked("SELECT * FROM event ORDER BY Event");
while ($row = mysql_fetch_object($result)) {
  $eventselect .= "    <option value=\"".$row->EventID."\">".d2h($row->Event)."</option>";
}
$eventselect .= "</select>\n";
$astartdate = "<input type=\"text\" name=\"astartdate1\" id=\"astartdate1\" style=\"width:6em\" />";
$aenddate = "<input type=\"text\" name=\"aenddate1\" id=\"aenddate1\" style=\"width:6em\" />";
printf(_("%s...did%s...did not%s attend one of these events:%s ".
"<span class=\"inputgroup\"><label>(after %s)</label><label>(before %s)</label></span>"),
$in, $out, $inoutfinish, $eventselect, $astartdate, $aenddate);
?>
  </div>
  <button type="button" id="attenddup" class="dup advanced"><? echo _("Add another..."); ?></button>
  <p class="comment"><? echo _("(To select multiple possible events (\"this one OR that one\"), hold Ctrl key while clicking.)"); ?></p>
</fieldset>

<fieldset class="advanced"><legend><?=_("Blanks")?></legend>
  <div id="text1" class="criteria">
<?
$in = "<span class=\"radiogroup\"><label><input type=\"radio\" name=\"blankinout1\" value=\"IN\" checked />";
$out = "</label><label><input type=\"radio\" name=\"blankinout1\" value=\"OUT\" />";
$inoutfinish = "</label></span>\n";
$target = "<select size=\"1\" name=\"blanktarget1\">\n";
$target .= "  <option value=\"\">"._("(select if desired...)")."</option>\n";
$target .= "  <option value=\"Address\">"._("Address")."</option>\n";
$target .= "  <option value=\"LabelName\">"._("Label Name")."</option>\n";
$target .= "  <option value=\"Phone\">"._("Landline Phone")."</option>\n";
$target .= "  <option value=\"FAX\">"._("FAX")."</option>\n";
$target .= "  <option value=\"CellPhone\">"._("Cell Phone")."</option>\n";
$target .= "  <option value=\"Email\">"._("Email Address")."</option>\n";
$target .= "  <option value=\"Sex\">"._("Sex")."</option>\n";
$target .= "  <option value=\"Birthdate\">"._("Birthdate")."</option>\n";
$target .= "  <option value=\"URL\">"._("URL")."</option>\n";
$target .= "  <option value=\"Country\">"._("Country")."</option>\n";
$target .= "  <option value=\"Remarks\">"._("Remarks")."</option>\n";
$target .= "</select>\n";
printf(_("...%s%sis%sis not%s<em>blank</em>"), $target, $in, $out, $inoutfinish);
?>
  </div>
  <button type="button" id="blankdup" class="dup advanced"><? echo _("Add another..."); ?></button>
</fieldset>

<?
if ($_SESSION['admin'] == 1) {
?>
<fieldset class="advanced admin">
  <p><? echo _("Freeform SQL"); ?> ...WHERE:</p>
  <textarea name="freesql" style="width:100%;height:3em"></textarea>
</fieldset>
<?
} //end of "if admin"
?>
<button class="simpleonly" id="showadvanced" type="button"><? echo _("Advanced Search Options"); ?></button>
</form>

<?
footer();
?>