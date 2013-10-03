<?php
include("functions.php");
include("accesscontrol.php");
header1("Select Household");
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/javascript">
function finish(form) {
e = opener.document.forms['editform'];
  e.elements['householdid'].value = form.hhid.value;
  e.elements['updateper'].value = "1";
  e.elements['address'].value = form.addr.value;
  if (form.nonjapan.value == "1") {
    e.elements['nonjapan'].checked = true;
  } else {
    e.elements['nonjapan'].checked = false;
    opener.check_nonjapan();
    e.elements['postalcode'].value = form.pc.value;
<? if ($_SESSION['romajiaddresses'] == "yes") { ?>
      e.elements['romajiaddress'].value = form.romaddr.value;
<? } ?>
  }
  e.elements['labelname'].value = form.ln.value;
  e.elements['phone'].value = form.phone.value;
  e.elements['fax'].value = form.fax.value;
  opener.cleanhhview();
  e.elements['relation'].style.borderColor = "#FF0000";
  e.elements['relation'].style.borderWidth = "5px";
  window.close();
}
</script>

<?
header2(0);
if (preg_match("/^[a-zA-Z]/",$fullname)) {  //name is in English letters
  $arr = explode(" ",$furigana,2);
  $lastname = $arr[1];
} else {  //assuming name is in Japanese
  $arr = explode(" ",$fullname,2);
  if ($arr[1] != "") {   //i.e. the name really was split into myoji and namae
    $lastname = $arr[0];
  } else {
    $lastname = substr($fullname,0,4);
  }
}
$sql = "SELECT household.* FROM household";
$where = " WHERE LabelName LIKE '%".$lastname."%' ORDER BY LabelName";

if (!$result = mysql_query($sql.$where)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql.$where)");
  exit;
}
?>
<table border=1 cellspacing=0 cellpadding=2><thead>
<tr><th>&nbsp;</th>
    <th>Label Name</th>
    <th>Address</th>
    <th>Phone</th></tr></thead>
    <tbody>
<?
$color = "#FFFFFF";
while ($row = mysql_fetch_object($result)) {
  write_row($row,$color);
  $color = ($color=="#FFFFFF")?"#E0FFE0":"#FFFFFF";
}

//now get the rest of the households
$where = " WHERE LabelName NOT LIKE '%".$lastname."%' ORDER BY LabelName";
if (!$result = mysql_query($sql.$where)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql.$where)");
  exit;
}
?>
<tr style="background-color:#808080;height:5px;"><td colspan="100%"></td></tr>
<?
while ($row = mysql_fetch_object($result)) {
  write_row($row,$color);
  $color = ($color=="#FFFFFF")?"#E0FFE0":"#FFFFFF";
}
echo "</tbody>\n</table>\n";

print_footer();

function write_row($row,$color) {
  echo "<tr style=\"background-color:$color;text-align:left\"><td>";
  echo "<form name=\"hh".$row->HouseholdID."\" onsubmit=\"return false;\">\n";
  echo "<input type=button value=\""._("This One")."\" onclick=\"finish(this.form);\">\n";
  echo "<input type=hidden name=hhid value=\"$row->HouseholdID\">\n";
  echo "<input type=hidden name=nonjapan value=\"$row->NonJapan\">\n";
  echo "<input type=hidden name=pc value=\"$row->PostalCode\">\n";
  echo "<input type=hidden name=addr value=\"$row->Address\">\n";
  echo "<input type=hidden name=romaddr value=\"$row->RomajiAddress\">\n";
  echo "<input type=hidden name=phone value=\"$row->Phone\">\n";
  echo "<input type=hidden name=fax value=\"$row->FAX\">\n";
  echo "<input type=hidden name=ln value=\"$row->LabelName\">\n";
  echo "</form></td>\n";
  echo "<td>".d2h($row->LabelName)."</td>\n";
  echo "<td>".d2h($row->AddressComp)."</td>\n";
  echo "<td nowrap>$row->Phone</td>\n</tr>\n";
}
?>