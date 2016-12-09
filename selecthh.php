<?php
include("functions.php");
include("accesscontrol.php");
header1("Select Household");
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<style>
h2 { margin-bottom:8px; }
table th, table td { border:1px solid black; padding:2px; }
table tbody { text-align:left; }
table tbody tr:nth-child(odd) { background-color:#F0FFF0; }
</style>
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
<?php if ($_SESSION['romajiaddresses'] == "yes") { ?>
      e.elements['romajiaddress'].value = form.romaddr.value;
<?php } ?>
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

<?php
header2(0);

$sql = "SELECT household.* FROM household";
if (isset($_GET['getall'])) {
  echo "<h2>"._("Select a Household: All")."</h2>\n";
} else {
  echo "<h2>"._("Select a Household: Possible Matches")."</h2>\n";
  if (mb_strlen($fullname)*1.25 > strlen($fullname)) {  //name is in Western letters (allowing for 25% European characters)
    if (strpos($furigana,",")) {
      $arr = explode(",",$furigana,2);
    } else {
      $arr = explode(" ",$furigana,2);
    }
    $lastname = $arr[0];
  } else {  //assuming name is in Japanese
    $arr = explode(" ",$fullname,2);
    if ($arr[1] != "") {   //i.e. the name really was split into myoji and namae
      $lastname = $arr[0];
    } else {
      $lastname = substr($fullname,0,4);
    }
  }
  $sql .= " WHERE LabelName LIKE '%".$lastname."%' ORDER BY LabelName";
}
$result = sqlquery_checked($sql);
?>
<table><thead>
<tr><th>&nbsp;</th>
    <th>Label Name</th>
    <th>Address</th>
    <th>Phone</th></tr></thead>
    <tbody>
<?php
while ($row = mysqli_fetch_object($result)) {
  echo "<tr><td>";
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
echo "</tbody>\n</table>\n";
if (!isset($_GET['getall'])) {
  echo "<h3><a href=\"".$_SERVER['REQUEST_URI']."&getall=1\">"._("Get All Households")."</a></h3>\n";
}
print_footer();
?>