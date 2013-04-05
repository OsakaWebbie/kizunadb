<?php
include("functions.php");
include("accesscontrol.php");
header1("");
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/JavaScript">
function validate() {
  if (document.contactform.ctid.value == "") {
    alert("<?=_("Please select a Contact Type.")?>");
    return false;
  } else {
//Check for old formats (this is temporary)
  if (document.contactform.ctid.value == "") {
    alert("<?=_("Please select a Contact Type.")?>");
    return false;
  } else {
    return true;
  }
}
</script>
<?
header2(0);

/* CHECK FOR RECORDS WITH NO HOUSEHOLD OR ADDRESS */
$sql = "SELECT p.PersonID, FullName, Furigana ".
    "FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "WHERE p.PersonID IN (".$pid_list.") AND (p.HouseholdID IS NULL OR p.HouseholdID=0 ".
    "OR h.Address IS NULL OR h.Address='' OR (h.NonJapan=0 AND h.PostalCode='')) ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);
if ($num = mysql_numrows($result) > 0) {
  echo "<div style=\"float:left;border:2px solid darkred;padding:4px;margin:4px\">"._("The following entries have no address:")."<br />\n";
  echo "<span style=\"text-size:0.8em\">"._("(They will not be printed unless you click on<br />each to add addresses before continuing.)")."</span>\n";
  while ($row = mysql_fetch_object($result)) {
    echo "<br>&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name($row->FullName, $row->Furigana)."</a>\n";
  }
  echo "</div>\n";
}

$sql = "SELECT AddrPrintName FROM addrprint ORDER BY AddrPrintName";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
?>
    <h3><?=_("Select options for address printing and click the button.")?></h3>
    <form action="print_addr.php" method="post" name="optionsform" target="_blank" onsubmit="return validate();">
      <input type="hidden" name="pid_list" value="<? echo $pid_list; ?>" border="0">
      <div style="display:inline-block"><input type="radio" name="name_type" value="ind" tabindex="1" border="0"><?=_("Use Individual Names")?><br />
      <input type="radio" name="name_type" value="label" border="0" checked><?=_("Use Household Label Names")?></div>
      <label class="label-n-input"><?=_("Envelope/Postcard Format")?>: <select name="addr_print_name" size="1">
<?
while ($row = mysql_fetch_object($result)) {
  echo  "                <option value=\"".$row->AddrPrintName."\">".$row->AddrPrintName."</option>\n";
}
?>
      </select></label>
      <input type="submit" name="submit" value="<?=_("Make PDF")?>" border="0">
    </form>
  <? footer();
?>

