<?php
include("functions.php");
include("accesscontrol.php");
print_header("Select Household","#F8F8F8",0);
?>
<script language=Javascript>
function finish(form) {
e = opener.document.forms['editform'];
  e.elements['householdid'].value = form.hhid.value;
  e.elements['updateper'].value = "1";
  if (form.nonjapan.value == "1") {
    e.elements['nonjapan'].checked = true;
    opener.check_nonjapan();
  } else {
    e.elements['nonjapan'].checked = false;
    opener.check_nonjapan();
    e.elements['postalcode'].value = form.pc.value;
    e.elements['prefecture'].value = form.pref.value;
    e.elements['shikucho'].value = form.shi.value;
<?
if ($_SESSION['romajiaddresses'] == "yes") {
echo "    e.elements['romaji'].value = form.romaji.value;\n";
echo "      e.elements['romajiaddress'].value = form.romaddr.value;\n";
}
?>
    e.elements['prefecture'].defaultValue = form.pref.value;
    e.elements['shikucho'].defaultValue = form.shi.value;
  }
  e.elements['address'].value = form.addr.value;
  e.elements['phone'].value = form.phone.value;
  e.elements['fax'].value = form.fax.value;
  e.elements['labelname'].value = form.ln.value;
  window.close();
}
</script>

<?
//$sql = "SELECT HouseholdID,LabelName,household.PostalCode,Prefecture,ShiKuCho,Address,Phone,".
//"MATCH(LabelName) AGAINST('$fullname') AS score FROM household ".
//"LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ORDER BY score DESC";
if (preg_match("/^[a-zA-Z]/",$fullname)) {  //name is in English letters
//echo "I think '$fullname' is in English - parse '$furigana'<br>";
  $arr = explode(" ",$furigana,2);
//echo "Array is '$arr[0]' and '$arr[1]'<br>";
  $lastname = $arr[1];
} else {  //assuming name is in Japanese
//echo "I think '$fullname' is in Japanese<br>";
  $arr = explode(" ",$fullname,2);
//echo "Array is '$arr[0]' and '$arr[1]'<br>";
//currently this doesn't work, nor does mb_split; someday get the 2 ASCII codes for a Japanese space?
  if ($arr[1] != "") {   //i.e. the name really was split into myoji and namae
    $lastname = $arr[0];
  } else {
    $lastname = substr($fullname,0,4);
  }
}
//echo "Lastname is '$lastname'<br>";
$sql = "SELECT household.*,Prefecture,ShiKuCho,Romaji FROM household ".
"LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode";
$where = " WHERE LabelName LIKE '%".$lastname."%' ORDER BY LabelName";

if (!$result = mysql_query($sql.$where)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql.$where)");
  exit;
}
echo "<table cellpadding=2 cellspacing=2 border=0 valign=middle>\n";
echo "<tr><td bgcolor=#E0E0FF align=center>&nbsp;</td>\n";
echo "    <td bgcolor=#E0E0FF align=center>Label Name</td>\n";
echo "    <td bgcolor=#E0E0FF align=center>Address</td>\n";
echo "    <td bgcolor=#E0E0FF align=center>Phone</td></tr>\n";

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
echo "<tr bgcolor=#808080><td><font size=1>&nbsp;</font></td>\n";
echo "    <td><font size=1>&nbsp;</font></td>\n";
echo "    <td><font size=1>&nbsp;</font></td>\n";
echo "    <td><font size=1>&nbsp;</font></td></tr>\n";

while ($row = mysql_fetch_object($result)) {
  write_row($row,$color);
  $color = ($color=="#FFFFFF")?"#E0FFE0":"#FFFFFF";
}
echo "</table>\n";

print_footer();

function write_row($row,$color) {
  echo "<tr bgcolor=\"$color\"><td>";
  echo "<form name=\"hh".$row->HouseholdID."\" onsubmit=\"return false;\">\n";
  echo "<input type=button value=\"This One\" onclick=\"finish(this.form);\">\n";
  echo "<input type=hidden name=hhid value=\"$row->HouseholdID\">\n";
  echo "<input type=hidden name=nonjapan value=\"$row->NonJapan\">\n";
  echo "<input type=hidden name=pc value=\"$row->PostalCode\">\n";
  echo "<input type=hidden name=pref value=\"$row->Prefecture\">\n";
  echo "<input type=hidden name=shi value=\"$row->ShiKuCho\">\n";
  echo "<input type=hidden name=romaji value=\"$row->Romaji\">\n";
  echo "<input type=hidden name=addr value=\"$row->Address\">\n";
  echo "<input type=hidden name=romaddr value=\"$row->RomajiAddress\">\n";
  echo "<input type=hidden name=phone value=\"$row->Phone\">\n";
  echo "<input type=hidden name=fax value=\"$row->FAX\">\n";
  echo "<input type=hidden name=ln value=\"$row->LabelName\">\n";
  echo "</form></td>\n  ";
  echo "<td>".db2table($row->LabelName)."</td>\n  <td>";
//  if ($row->PostalCode != "") echo "§";
  echo $row->PostalCode." ".$row->Prefecture.$row->ShiKuCho." ".db2table($row->Address)."</td>\n";
  echo "  <td nowrap>$row->Phone</td>\n</tr>\n";
}
?>