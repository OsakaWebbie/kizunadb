<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Pledge List").
(!empty($_POST['preselected']) ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : ""));
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>

<script type="text/JavaScript">
</script>
<?php
header2($_GET['nav']);

$sql = "SELECT pl.*, FullName, Furigana, DonationType, SUM(IFNULL(d.Amount,0)) - ".
"(pl.Amount * pl.TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(pl.EndDate='0000-00-00' OR CURDATE()<pl.EndDate, ".
"CURDATE(), pl.EndDate), '%Y%m'), DATE_FORMAT(pl.StartDate, '%Y%m'))) AS Balance, ".
"(SUM(IFNULL(d.Amount,0)) - (pl.Amount * pl.TimesPerYear / 12 * ".
"PERIOD_DIFF(DATE_FORMAT(IF(pl.EndDate='0000-00-00' OR CURDATE()<pl.EndDate, ".
"CURDATE(), pl.EndDate), '%Y%m'), DATE_FORMAT(pl.StartDate, '%Y%m')))) ".
"/ pl.Amount * 12 / pl.TimesPerYear AS Months".
" FROM pledge pl LEFT JOIN person p ON p.PersonID=pl.PersonID".
" LEFT JOIN donationtype dt ON pl.DonationTypeID=dt.DonationTypeID".
" LEFT JOIN donation d ON pl.PledgeID=d.PledgeID";

$closed = isset($_POST['closed']) ? $_POST['closed'] : '';
$subtotals = isset($_POST['subtotals']) ? $_POST['subtotals'] : '';
$sort = isset($_POST['sort']) ? $_POST['sort'] : '';
$desc = isset($_POST['desc']) ? $_POST['desc'] : '';
$psubtotals = isset($_POST['psubtotals']) ? $_POST['psubtotals'] : '';

if (!$closed) {
  $sql .= " WHERE pl.EndDate is null OR pl.EndDate > CURDATE()";
}
$sql .= " GROUP BY pl.PledgeID";

//from old form - I'm in the middle of making more options
if ($psubtotals) {
  $subtotals = "yes";
}

$show_subtotals = 0;
if ($subtotals && ($sort || $sort == "DonationType")) {
  $show_subtotals = 1;
  $sql .= " ORDER BY DonationType $desc, Furigana";
  $sort = "DonationType";  //for the table heading code to catch
} elseif ($sort && $sort != "Furigana") {
  $sql .= " ORDER BY $sort $desc, Furigana";
} else {
  $sql .= " ORDER BY Furigana $desc";
  $sort = "Furigana";  //for the table heading code to catch
}
$href = $_SERVER['PHP_SELF']."?closed=$closed&subtotals=$subtotals&sort=";
$result = sqlquery_checked($sql);

$period[0] = '';
$period[1] = _("year");
$period[4] = _("quarter");
$period[12] = _("month");
$periods[0] = '';
$periods[1] = _("years");
$periods[4] = _("quarters");
$periods[12] = _("months");

echo "<h2 align=center>".sprintf($closed ? _("All Pledges as of %s") : _("Open Pledges as of %s"),
date("Y-m-d",mktime(gmdate("H")+9)))."</h2>";
echo "<table border=1 cellspacing=0 cellpadding=1 style=\"empty-cells:show\">\n";
echo "<tr>";
echo "<th><a href=\"".$href."Furigana";
if (($sort == "Furigana") && !$desc) echo "&desc=desc";
echo "\">"._("Name");
if ($sort == "Furigana") echo $desc ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."DonationType";
if (($sort == "DonationType") && !$desc) echo "&desc=desc";
echo "\">"._("Pledge Type");
if ($sort == "DonationType") echo $desc ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."pl.Amount";
if (($sort == "pl.Amount") && !$desc) echo "&desc=desc";
echo "\">"._("Amount");
if ($sort == "pl.Amount") echo $desc ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."StartDate";
if (($sort == "StartDate") && !$desc) echo "&desc=desc";
echo "\">"._("Dates");
if ($sort == "StartDate") echo $desc ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."Balance";
if (($sort == "Balance") && !$desc) echo "&desc=desc";
echo "\">"._("Balance");
if ($sort == "Balance") echo $desc ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."Months";
if (($sort == "Months") && !$desc) echo "&desc=desc";
echo "\">"._("Months");
if ($sort == "Months") echo $desc ? " &#x25bc" : " &#x25b2";
echo "</a></th>";

echo "<th><a href=\"".$href."PledgeDesc";
if (($sort == "PledgeDesc") && !$desc) echo "&desc=desc";
echo "\">"._("Remarks");
if ($sort == "PledgeDesc") echo $desc ? " &#x25bc" : " &#x25b2";
echo "</a></th>";
echo "</tr>\n";

$prev_donationtype = "";
$total = $subtotal = $subnumber = 0;
while ($row = mysqli_fetch_object($result)) {
  if ($show_subtotals && $prev_donationtype != "" && $row->DonationType != $prev_donationtype) {
    echo "<tr><td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>"._("Subtotal").
    " ("._("number: ").$subnumber.")</b></td>\n";
    echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>{$prev_donationtype}</b></td>\n";
    echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
    number_format($subtotal,$_SESSION['currency_decimals'])."</b></td><td bgcolor=\"#FFFFE0\" colspan=\"4\"></td></tr>\n";
    $subtotal = $subnumber = 0;
  }
  echo "<tr><td valign=middle nowrap style=\"padding:2px 4px 2px 4px;\">\n";
  echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
  echo readable_name($row->FullName, $row->Furigana,0,0,"<br />")."</a></td>\n";
  echo "<td valign=middle nowrap style=\"padding:2px 4px 2px 4px;\">{$row->DonationType}</td>\n";
  echo "<td valign=middle align=right nowrap style=\"padding:2px 4px 2px 4px;\">".$_SESSION['currency_mark']." ".
  number_format($row->Amount,$_SESSION['currency_decimals'])."/".
  $period[$row->TimesPerYear]."</td>\n";
  echo "<td valign=middle nowrap style=\"padding:2px 4px 2px 4px;\">".$row->StartDate."&#xFF5E;".
  ($row->EndDate!='0000-00-00' ? $row->EndDate : '')."</td>\n";
  echo "<td valign=middle align=center nowrap style=\"padding:2px 4px 2px 4px;".
  ($row->Balance<0 ? "color:red" : "")."\">".
  $_SESSION['currency_mark']." ".
  number_format($row->Balance,$_SESSION['currency_decimals'])."</td>\n";
  echo "<td valign=middle align=center nowrap style=\"padding:2px 4px 2px 4px;".
  ($row->Balance<0 ? "color:red" : "")."\">";
  echo ceil($row->Months);
  if ($row->TimesPerYear == 0) {

  }
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
  echo "<tr><td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>"._("Subtotal").
  " ("._("number: ").$subnumber.")</b></td>\n";
  echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>{$prev_donationtype}</b></td>\n";
  echo "<td bgcolor=\"#FFFFE0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
  number_format($subtotal,$_SESSION['currency_decimals'])."</b></td><td bgcolor=\"#FFFFE0\" colspan=\"4\"></td></tr>\n";
  echo "<tr><td bgcolor=\"#F0F0C0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>"._("Total").
  " ("._("total number: ").mysqli_num_rows($result).")</b></td>\n";
  echo "<td bgcolor=\"#F0F0C0\"></td>\n";
  echo "<td bgcolor=\"#F0F0C0\" valign=middle nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
  number_format($total,$_SESSION['currency_decimals'])."</b></td><td bgcolor=\"#F0F0C0\" colspan=\"4\"></td></tr>\n";
}
echo "</table>\n";

footer();
?>
