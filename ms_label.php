<?php
include("functions.php");
include("accesscontrol.php");
header1("");
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<script type="text/JavaScript" src="js_procedural/jquery.js"></script>
<script type="text/JavaScript" src="js_procedural/jquery-ui.js"></script>
<?php
header2(0);

/* CHECK FOR RECORDS WITH NO HOUSEHOLD OR ADDRESS */
$sql = "SELECT p.PersonID, FullName, Furigana ".
    "FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND (p.HouseholdID=0 OR h.Address IS NULL OR h.Address='' ".
    "OR (h.NonJapan=0 AND h.PostalCode='')) ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
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
    <h3><?=_("Select options for label printing and click the button.")?></h3>
    <form action="print_label.php" method="post" name="optionsform" target="_blank" style="text-align:left">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>" border="0">
      <div style="display:inline-block;vertical-align:middle;margin:0 2em">
        <input type="radio" name="name_type" value="ind" tabindex="1" border="0"><?=_("Individuals")." (".$num_individuals.")"?><br />
        <input type="radio" name="name_type" value="label" border="0" checked><?=_("Households")." (".$num_households.")"?>
      </div>
      <div style="display:inline-block;vertical-align:middle">
        <label class="label-n-input"><?=_("Label Type")?>: <select name="label_type" size="1">
<?php
$result = sqlquery_checked("SELECT LabelType FROM labelprint ORDER BY LabelType");
while ($row = mysqli_fetch_object($result)) {
  echo  "                  <option value=\"".$row->LabelType."\">".$row->LabelType."</option>\n";
}
?>
        </select></label><br>
        <label class="label-n-input"><input type="checkbox" value="yes" name="wrap_pc" checked><?=_("Japan postal code on its own line")?></label><br>
        <label class="label-n-input"><input type="checkbox" value="yes" name="nj_separate" checked><?=_("Sort by Japan/foreign")?></label>
      </div>
      <input type="submit" name="submit" value="<?=_("Make PDF")?>" border="0">
    </form>
  <?php footer();
?>

