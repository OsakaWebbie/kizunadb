<?php
 include("functions.php");
 include("accesscontrol.php");

header1(""); ?>
<link rel="stylesheet" href="style.php" type="text/css" />
<?php
header2(0);

if ($_GET['summarytype'] == "DonationType") {
  $sql = "SELECT dt.DonationType, SUM(d.Amount) AS Amounts FROM donationtype dt";
  $sql .= " LEFT JOIN donation d ON d.DonationTypeID=dt.DonationTypeID";
  $sql .= " AND (d.DonationDate BETWEEN '".$_GET['start']."' AND '".$_GET['end']."')";
  $sql .= " OR d.DonationDate IS NULL";
  $sql .= " GROUP BY dt.DonationType";
} else {  //by person
  $sql = "SELECT d.PersonID, p.FullName, p.Furigana, SUM(d.Amount) AS Amounts FROM donation d";
  $sql .= " LEFT JOIN person p ON p.PersonID=d.PersonID";
  $sql .= " WHERE (d.DonationDate BETWEEN '".$_GET['start']."' AND '".$_GET['end']."')";
  $sql .= " GROUP BY d.PersonID";
}

if ($_GET['sort']=="Amounts") {
  $sql .= " ORDER BY Amounts ".$_GET['desc'].",".$_GET['summarytype'];
} else {
  $sql .= " ORDER BY ".$_GET['summarytype']." ".$_GET['desc'];
  $_GET['sort'] = $_GET['summarytype'];  //just to make sure it has a value, for the table heading code to catch
}
$href = $_SERVER['PHP_SELF']."?start=".$_GET['start']."&end=".$_GET['end']."&summarytype=".$_GET['summarytype']."&sort=";

$result = sqlquery_checked($sql);

echo "<h2 align=center>"._("Donation Summary").": ".$_GET['start']." to ".$_GET['end']."</h2>";
echo "<div align=center>";
echo "  <table border=1 cellspacing=0 cellpadding=1 style=\"empty-cells:show;\">\n";
echo "  <tr>";
echo "    <th style=\"padding:0 6px 0 6px;\"><a href=\"".$href.$_GET['summarytype'];
if (($_GET['sort'] == $_GET['summarytype']) && !$_GET['desc']) echo "&desc=desc";
echo "\">".($_GET['summarytype']=="DonationType"?_("Donation Type"):_("Name"));
if ($_GET['sort'] == $_GET['summarytype']) echo $_GET['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "    <th style=\"padding:0 6px 0 6px;\"><a href=\"".$href."Amounts";
if (($_GET['sort'] == "Amounts") && !$_GET['desc']) echo "&desc=desc";
echo "\">"._("Amount");
if ($_GET['sort'] == "Amounts") echo $_GET['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";
echo "  </tr>\n";

$total = 0;
while ($row = mysqli_fetch_object($result)) {
  echo "    <td valign=middle nowrap style=\"padding:6px;\">";
  if ($_GET['summarytype']=="DonationType") {
    echo $row->DonationType;
  } else {
    echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name($row->FullName,$row->Furigana)."</a>";
  }
  echo "</td>\n";
  echo "    <td valign=middle align=right nowrap style=\"padding:6px;text-align:right;\">".$_SESSION['currency_mark']." ".
  number_format($row->Amounts,$_SESSION['currency_decimals'])."</td></tr>\n";
  $total += $row->Amounts;
}
echo "  <tr>\n    <th style=\"padding:0 6px 0 6px;\">&nbsp;&nbsp;Total</th>\n";
echo "    <th align=right style=\"padding:0 6px 0 6px;text-align:right\">".$_SESSION['currency_mark']." ".
number_format($total,$_SESSION['currency_decimals'])."</th></tr>";
echo "  </table>\n</div>\n";

print_footer();
?>
