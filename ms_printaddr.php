<?php
include("functions.php");
include("accesscontrol.php");
header1("");
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<?php
header2(0);

/* CHECK FOR RECORDS WITH NO HOUSEHOLD OR ADDRESS */
$sql = "SELECT p.PersonID, FullName, Furigana ".
    "FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND (p.HouseholdID IS NULL OR p.HouseholdID=0 ".
    "OR h.Address IS NULL OR h.Address='' OR (h.NonJapan=0 AND h.PostalCode='')) ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);
if ($num = mysqli_num_rows($result) > 0) {
  echo "<div style=\"float:left;border:2px solid darkred;padding:4px;margin:4px\">"._("The following entries have no address:")."<br />\n";
  echo "<span style=\"text-size:0.8em\">"._("(They will not be printed unless you click on<br />each to add addresses before continuing.)")."</span>\n";
  while ($row = mysqli_fetch_object($result)) {
    echo "<br>&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name($row->FullName, $row->Furigana)."</a>\n";
  }
  echo "</div>\n";
}
/* GET NUMBERS OF ENTRIES THAT WOULD BE PRINTED */
$sql = "SELECT count(PersonID) FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND NOT (p.HouseholdID=0 OR h.Address IS NULL OR h.Address='' ".
    "OR (h.NonJapan=0 AND h.PostalCode=''))";
$result = sqlquery_checked($sql);
$num_individuals = mysqli_result($result,0);
$sql = "SELECT count(DISTINCT h.HouseholdID) FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND NOT (p.HouseholdID=0 OR h.Address IS NULL OR h.Address='' ".
    "OR (h.NonJapan=0 AND h.PostalCode=''))";
$result = sqlquery_checked($sql);
$num_households = mysqli_result($result,0);
?>
<h3><?=_("Select options for address printing and click the button.")?></h3>
<form action="print_addr.php" method="post" name="optionsform" target="_blank" style="text-align:left">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>" border="0">
  <div style="display:inline-block;vertical-align:middle;margin:0 2em">
    <input type="radio" name="name_type" value="ind" tabindex="1" border="0"><?=_("Individuals")." (".$num_individuals.")"?><br />
    <input type="radio" name="name_type" value="label" border="0" checked><?=_("Households")." (".$num_households.")"?>
  </div>
  <div style="display:inline-block;vertical-align:middle">
    <label class="label-n-input"><?=_("Envelope/Postcard Format")?>: <select id="addrprint-select" name="addr_print_name" size="1">
<?php
$result = sqlquery_checked("SELECT AddrPrintName,Tategaki,DefaultStamp FROM addrprint ORDER BY ListOrder,AddrPrintName");
while ($row = mysqli_fetch_object($result)) {
  echo  "      <option value=\"".$row->AddrPrintName."\" data-kanjinumbers=\"".$row->Tategaki."\"".
        " data-stamp=\"".$row->DefaultStamp."\">".$row->AddrPrintName."</option>\n";
}
?>
    </select></label><br>
    <div style="display:inline-block;vertical-align:middle;margin:0 2em">
      <label class="label-n-input"><input type="checkbox" value="yes" name="nj_separate" checked><?=_("Sort by Japan/foreign")?></label><br />
      <label class="label-n-input"><input type="checkbox" value="yes" name="kanji_numbers" id="kanji_numbers" checked><?=_("Use kanji for numbers")?></label>
    </div>
    <div style="display:inline-block;vertical-align:middle;margin:0 2em">
      <h4><?=_("Post office stamp:")?></h4>
      <input type="radio" name="po_stamp" value="none" checked><?=_("None")?><br />
      <input type="radio" name="po_stamp" value="betsunou"><?=_("Standard mail")?><br />
      <input type="radio" name="po_stamp" value="yuumail_betsunou"><?=_("'Yuu-mail'")?><br />
      <input type="radio" name="po_stamp" value="kounou"><?=_("Standard mail w/ contract")?><br />
      <input type="radio" name="po_stamp" value="yuumail_kounou"><?=_("'Yuu-mail' w/ contract")?>
    </div>
  </div>
  <input type="submit" name="submit" value="<?=_("Make PDF")?>" border="0">
</form>

<script type="text/JavaScript" src="js_procedural/jquery.js"></script>
<script type="text/JavaScript" src="js_procedural/jquery-ui.js"></script>
<script type="text/JavaScript">
$(document).ready(function() {
  $('#addrprint-select').change(function() {
    $('#kanji_numbers').prop('checked',$('#addrprint-select option:selected').data("kanjinumbers")==1);
    $('input:radio[name=po_stamp]').filter('[value='+$('#addrprint-select option:selected').data("stamp")+']').prop('checked', true);
  });
  $('#addrprint-select').trigger('change');
});
</script>
  <?php footer();
?>

