<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Pledge List").
($_POST['preselected']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : ""));
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>

<script type="text/JavaScript">
</script>
<?
header2($_GET['nav']);

$sql = "SELECT pl.*, FullName, Furigana, DonationType, SUM(IFNULL(d.Amount,0)) - ".
"(pl.Amount * pl.TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(pl.EndDate IS NULL OR CURDATE()<pl.EndDate, ".
"CURDATE(), pl.EndDate), '%Y%m'), DATE_FORMAT(pl.StartDate, '%Y%m'))) AS Balance, ".
"(SUM(IFNULL(d.Amount,0)) - (pl.Amount * pl.TimesPerYear / 12 * ".
"PERIOD_DIFF(DATE_FORMAT(IF(pl.EndDate IS NULL OR CURDATE()<pl.EndDate, ".
"CURDATE(), pl.EndDate), '%Y%m'), DATE_FORMAT(pl.StartDate, '%Y%m')))) ".
"/ pl.Amount * 12 / pl.TimesPerYear AS Months".
" FROM pledge pl LEFT JOIN person p ON p.PersonID=pl.PersonID".
" LEFT JOIN donationtype dt ON pl.DonationTypeID=dt.DonationTypeID".
" LEFT JOIN donation d ON pl.PledgeID=d.PledgeID";

if ($_POST['closed']!="yes") {
  $sql .= " WHERE pl.EndDate is null OR pl.EndDate > CURDATE()";
}
$sql .= " GROUP BY pl.PledgeID";

//from old form - I'm in the middle of making more options
if ($_POST['psubtotals'] == "yes") {
  $_POST['subtotals'] = "yes";
}

$show_subtotals = 0;
if ($_POST['subtotals']=="yes" and (!$_POST['sort'] or $_POST['sort'] == "DonationType")) {
  $show_subtotals = 1;
  $sql .= " ORDER BY DonationType ".$_POST['desc'].",Furigana";
  $_POST['sort'] = "DonationType";  //for the table heading code to catch
} elseif ($_POST['sort'] and ($_POST['sort'] != "Furigana")) {
  $sql .= " ORDER BY ".$_POST['sort']." ".$_POST['desc'].",Furigana";
} else {
  $sql .= " ORDER BY Furigana ".$_POST['desc'];
  $_POST['sort'] = "Furigana";  //for the table heading code to catch
}
$href = $PHP_SELF."?closed=".$_POST['closed']."&subtotals=".$_POST['subtotals']."&sort=";
if (!$result = mysql_query($sql)) {
  exit("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
}

$period[1] = i18n("year");
$period[4] = i18n("quarter");
$period[12] = i18n("month");
$periods[1] = i18n("years");
$periods[4] = i18n("quarters");
$periods[12] = i18n("months");

echo "<h2 align=center>".($_POST['closed']=="true" ? i18n("All") : i18n("Open")).i18n(" Pledges as of ").
date("Y-m-d",mktime(gmdate("H")+9))."</h2>";
echo "<table border=1 cellspacing=0 cellpadding=1 style=\"empty-cells:show\">\n";
echo "<tr>";
echo "<th><a href=\"".$href."Furigana";
if (($_POST['sort'] == "Furigana") && !$_POST['desc']) echo "&desc=desc";
echo "\">".i18n("Name");
if ($_POST['sort'] == "Furigana") echo $_POST['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."DonationType";
if (($_POST['sort'] == "DonationType") && !$_POST['desc']) echo "&desc=desc";
echo "\">".i18n("Pledge Type");
if ($_POST['sort'] == "DonationType") echo $_POST['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."pl.Amount";
if (($_POST['sort'] == "pl.Amount") && !$_POST['desc']) echo "&desc=desc";
echo "\">".i18n("Amount");
if ($_POST['sort'] == "pl.Amount") echo $_POST['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."StartDate";
if (($_POST['sort'] == "StartDate") && !$_POST['desc']) echo "&desc=desc";
echo "\">".i18n("Dates");
if ($_POST['sort'] == "StartDate") echo $_POST['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."Balance";
if (($_POST['sort'] == "Balance") && !$_POST['desc']) echo "&desc=desc";
echo "\">".i18n("Balance");
if ($_POST['sort'] == "Balance") echo $_POST['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."Months";
if (($_POST['sort'] == "Months") && !$_POST['desc']) echo "&desc=desc";
echo "\">".i18n("Months");
if ($_POST['sort'] == "Months") echo $_POST['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."PledgeDesc";
if (($_POST['sort'] == "PledgeDesc") && !$_POST['desc']) echo "&desc=desc";
echo "\">".i18n("Remarks");
if ($_POST['sort'] == "PledgeDesc") echo $_POST['desc'] ? " &#x25bc" : " &#x25b2";
echo "</a></th>";
echo "</tr>\n";

$prev_donationtype = "";
$total = $subtotal = $subnumber = 0;
while ($row = mysql_fetch_object($result)) {
  if ($show_subtotals && $prev_donationtype != "" && $row->DonationType != $prev_donationtype) {
    echo "<tr><td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".i18n("Subtotal").
    " (".i18n("number: ").$subnumber.")</b></td>\n";
    echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>{$prev_donationtype}</b></td>\n";
    echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
    number_format($subtotal,$_SESSION['currency_decimals'])."</b></td><td bgcolor=\"#FFFFE0\" colspan=\"4\"></td></tr>\n";
    $subtotal = $subnumber = 0;
  }
  echo "<tr><td valign=middle nowrap style=\"padding:2px 4px 2px 4px;\">\n";
  echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
  echo readable_name_2line($row->FullName, $row->Furigana)."</a></td>\n";
  echo "<td valign=middle nowrap style=\"padding:2px 4px 2px 4px;\">{$row->DonationType}</td>\n";
  echo "<td valign=middle align=right nowrap style=\"padding:2px 4px 2px 4px;\">".$_SESSION['currency_mark']." ".
  number_format($row->Amount,$_SESSION['currency_decimals'])."/".
  $period[$row->TimesPerYear]."</td>\n";
  echo "<td valign=middle nowrap style=\"padding:2px 4px 2px 4px;\">".$row->StartDate."&#xFF5E;".
  ($row->EndDate ? $row->EndDate : "")."</td>\n";
  echo "<td valign=middle align=center nowrap style=\"padding:2px 4px 2px 4px;".
  ($row->Balance<0 ? "color:red" : "")."\">".
  $_SESSION['currency_mark']." ".
  number_format($row->Balance,$_SESSION['currency_decimals'])."</td>\n";
  echo "<td valign=middle align=center nowrap style=\"padding:2px 4px 2px 4px;".
  ($row->Balance<0 ? "color:red" : "")."\">";
  echo ceil($row->Months);
  if ($row->TimesPerYear != 12) {
    echo "<br />(".ceil(($row->Months)/12*$row->TimesPerYear)." ".
    $periods[$row->TimesPerYear].")";
  }
  echo "</td>\n";
  echo "<td valign=middle nowrap style=\"padding:2px 4px 2px 4px;\">".
  db2table($row->PledgeDesc)."</td></tr>\n";
  $prev_donationtype = $row->DonationType;
  $subtotal += $row->Amount;
  $subnumber++;
  $total += $row->Amount;
}
if ($show_subtotals && $prev_donationtype != "" && $row->DonationType != $prev_donationtype) {
  echo "<tr><td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".i18n("Subtotal").
  " (".i18n("number: ").$subnumber.")</b></td>\n";
  echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>{$prev_donationtype}</b></td>\n";
  echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
  number_format($subtotal,$_SESSION['currency_decimals'])."</b></td><td bgcolor=\"#FFFFE0\" colspan=\"4\"></td></tr>\n";
  echo "<tr><td bgcolor=\"#F0F0C0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".i18n("Total").
  " (".i18n("total number: ").mysql_numrows($result).")</b></td>\n";
  echo "<td bgcolor=\"#F0F0C0\"></td>\n";
  echo "<td bgcolor=\"#F0F0C0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
  number_format($total,$_SESSION['currency_decimals'])."</b></td><td bgcolor=\"#F0F0C0\" colspan=\"4\"></td></tr>\n";
}
echo "</table>\n";

footer();
?>
