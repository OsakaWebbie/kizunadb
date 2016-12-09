<?php
include("functions.php");
session_start();
if (!isset($_SESSION['userid'])) {      // NOT YET LOGGED IN
  die("NOSESSION");
}

$fullname = h2d(mb_ereg_replace(" ","",$_POST['fullname']));
$furigana = h2d(mb_ereg_replace(",","",mb_ereg_replace(" ","",$_POST['furigana'])));
$sql = "SELECT DISTINCT person.*, household.*, postalcode.* FROM person ".
    "LEFT JOIN household ON person.HouseholdID=household.HouseholdID LEFT JOIN postalcode ".
    "ON household.PostalCode=postalcode.PostalCode";
$sql .= " WHERE (LOWER(REPLACE(FullName,' ',''))=LOWER(REPLACE('".$fullname."',' ',''))";
$sql .= " OR LOWER(REPLACE(REPLACE(Furigana,' ',''),',',''))=LOWER(REPLACE(REPLACE('".$furigana."',' ',''),',','')))";
if ($_REQUEST['postalcode'] != "") {
  $sql .= " AND (household.PostalCode='".$_REQUEST['postalcode']."' OR household.PostalCode IS NULL OR household.PostalCode = '')";
}
if ($_REQUEST['cellphone'] != "") {
  $sql .= " OR (REPLACE(person.CellPhone,'-','')=REPLACE('".$_REQUEST['cellphone']."','-',''))";
}
if ($_REQUEST['email'] != "") {
  $sql .= " OR (LOWER(person.Email)=LOWER('".$_REQUEST['email']."'))";
}
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result)==0) {
  die("NODUPS");
} else {
  echo "<h3>"._("Is one of these existing entries the same<br />as the new one you are entering?")."</h3>";
  echo "<p class=\"comment\">"._("(click on a name to see details in a new window/tab)")."</p>";
  while ($row = mysqli_fetch_object($result)) {
    echo "<div class=\"dup\">\n";
    echo "  <div class=\"name\"><a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">".
    readable_name($row->FullName,$row->Furigana)."</a></div>\n";
    if ($row->Address) {
      echo "  <div class=\"address\">〒".$row->PostalCode.$row->Prefecture.$row->ShiKuCho." ".d2h($row->Address)."</div>\n";
    }
    if ($row->Phone) echo "    <div class=\"phone\"\">"._("Phone").":".$row->Phone."</div>\n";
    if ($row->CellPhone) echo "    <div class=\"cellphone\">"._("Cell Phone").":".$row->CellPhone."</div>\n";
    if ($row->Email) echo "    <div class=\"email\">"._("Email").":".$row->Email."</div>\n";
    echo "  <div class=\"button_section\">\n";
    echo "    <form action=\"individual.php\" method=\"get\">\n";
    echo "    <input type=\"hidden\" name=\"pid\" value=\"".$row->PersonID."\">\n";
    echo "    <button type=\"submit\">"._("Yes, this is it")."</button></form>\n";
    echo "    <form action=\"edit.php\" method=\"get\" target=\"_blank\">\n";
    echo "    <input type=\"hidden\" name=\"pid\" value=\"".$row->PersonID."\">\n";
    echo "    <button type=\"submit\">"._("Yes, this is it, but I want to edit it")."</button><br>\n";
    echo "    <div class=\"comment\" style=\"text-align:right\">".    _("(will open edit page in separate tab)")."</div>\n";
    echo "    </form>\n";
    echo "  </div>\n</div>";
  }
  echo "  <button type=\"button\" id=\"continue\">"._("No, it's none of these, so please submit the new entry")."</button>";
  
}
?>
