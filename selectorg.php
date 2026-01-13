<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Select Organization"));
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<?php load_scripts(['jquery', 'tablesorter']); ?>
<script language=Javascript>
$(document).ready(function() {
  $("#mainTable").tablesorter({ sortList:[[3,0]], headers:{0:{sorter:false}}
  });
}); 

function finish(form) {
  opener.document.forms['orgform'].elements['orgid'].value = form.id.value;
  opener.document.getElementById('orgname').innerHTML = form.name.value;
  window.close();
}
</script>
<?php
header2();

$sql = "SELECT FullName,Furigana,PersonID,household.PostalCode,Address,Prefecture,ShiKuCho FROM person ".
"LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
"LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode WHERE Organization>0";
if ($_GET['txt']!="") $sql .= " AND (person.FullName LIKE '%".$_GET['txt']."%' OR person.Furigana LIKE '%".$_GET['txt']."%')";
$sql .= " ORDER BY Furigana";

$result = sqlquery_checked($sql);
?>
<table id="mainTable" class="tablesorter" valign="middle">
  <thead><tr>
    <th align="center">&nbsp;</th>
    <th align="center"><?=_("ID")?></th>
    <th><?=_("Name")?></th>
    <th><?=($_SESSION['furiganaisromaji']=="yes"?_("Romaji"):_("Furigana"))?></th>
    <th><?=_("Address")?></th>
  </tr></thead><tbody>
<?php
while ($row = mysqli_fetch_object($result)) {
  echo "<tr><td class=\"button-in-table\"><form name=\"org".$row->PersonID."\" onsubmit=\"return false;\">\n";
  echo "<input type=button value=\"This One\" onclick=\"finish(this.form);\">\n";
  echo "<input type=hidden name=id value=\"".$row->PersonID."\">\n";
  echo "<input type=hidden name=name value=\"".readable_name($row->FullName,$row->Furigana)."\">\n";
  echo "</form></td>\n";
  echo "<td>".$row->PersonID."</td>\n";
  echo "<td>".$row->FullName."</td>\n";
  echo "<td>".$row->Furigana."</td>\n";
  echo "<td>".$row->PostalCode." ".$row->Prefecture.$row->ShiKuCho." ".db2table($row->Address)."</td></tr>\n";  
}
echo "</tbody></table>\n";

footer();
?>