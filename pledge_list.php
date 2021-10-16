<?php
include("functions.php");
include("accesscontrol.php");

header1(_("Pledge List"));
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<?php
header2(!empty($_GET['pledge_tab'])?1:0);

/* $sql = "SELECT pl.*, FullName, Furigana, DonationType,
SUM(IFNULL(d.Amount,0)) - (pl.Amount * pl.TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(pl.EndDate='0000-00-00' OR CURDATE()<pl.EndDate,CURDATE(), pl.EndDate), '%Y%m'), DATE_FORMAT(pl.StartDate, '%Y%m'))) AS Balance,

(SUM(IFNULL(d.Amount,0)) - (pl.Amount * pl.TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(pl.EndDate='0000-00-00' OR CURDATE()<pl.EndDate,CURDATE(), pl.EndDate), '%Y%m'), DATE_FORMAT(pl.StartDate, '%Y%m')))) / pl.Amount * 12 / pl.TimesPerYear AS Months

" FROM pledge pl LEFT JOIN person p ON p.PersonID=pl.PersonID".
" LEFT JOIN donationtype dt ON pl.DonationTypeID=dt.DonationTypeID".
" LEFT JOIN donation d ON pl.PledgeID=d.PledgeID";

$closed = isset($_GET['closed']) ? $_GET['closed'] : '';
$subtotals = isset($_GET['subtotals']) ? $_GET['subtotals'] : '';
$desc = isset($_GET['desc']) ? $_GET['desc'] : '';
$psubtotals = isset($_GET['psubtotals']) ? $_GET['psubtotals'] : ''; */

$dtgrouped = !empty($_GET['dtgrouped']);
if ($dtgrouped) {
  $sql = "SELECT PledgeID,pledge.DonationTypeID,DonationType FROM pledge LEFT JOIN donationtype ON pledge.DonationTypeID=donationtype.DonationTypeID" .
      (empty($_GET['closed'])?" WHERE EndDate='0000-00-00' OR EndDate>CURDATE()":'') . " ORDER BY DonationType";
} else {
  $sql = "SELECT PledgeID FROM pledge" . (empty($_GET['closed'])?" WHERE EndDate='0000-00-00' OR EndDate>CURDATE()":'');
}

/*$show_subtotals = 0;
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
$href = $_SERVER['PHP_SELF']."?closed=$closed&subtotals=$subtotals&sort=";*/

$result = sqlquery_checked($sql);
$num_pledges = mysqli_num_rows($result);
if ($num_pledges == 0) {
  echo _('There are no records matching your criteria.');
  footer();
  exit;
}

$tableopt = (object) [
  'ids' => '',
  'keyfield' => 'pledge.PledgeID',
  'joins' => 'LEFT JOIN person ON person.PersonID=pledge.PersonID '.
      'LEFT JOIN donationtype ON pledge.DonationTypeID=donationtype.DonationTypeID '.
      'LEFT JOIN donation ON pledge.PledgeID=donation.PledgeID',
  'tableid' => 'pledges',
  'heading' => !empty($_GET['closed']) ? _('All Pledges') : _('Open Pledges').' ('.$num_pledges.')',
  'cols' => array() ];
$tableopt->cols[] = (object) [
  'sel' => 'person.NameCombo',
  'label' => _('Name')
];
if ($dtgrouped) {
  $tableopt->cols[] = (object) [ 'sel' => 'donationtype.DonationType', 'label' => _('Donation Type') ];
};
$tableopt->cols[] = (object) [
  'sel' => "CONCAT(StartDate,'～',IF(EndDate!='0000-00-00',EndDate,''))",
  'label' => _('Dates') ];
$tableopt->cols[] = (object) [
  'sel' => 'PledgeDesc',
  'label' => _('Description') ];
$tableopt->cols[] = (object) [
  'sel' => "CONCAT('".$_SESSION['currency_mark']."',FORMAT(pledge.Amount,".$_SESSION['currency_decimals']."))",
  'label' => _('Amount'),
  'classes' => 'right' ];
$tableopt->cols[] = (object) [
  'sel' => "CASE TimesPerYear WHEN 12 THEN 'Month' WHEN 4 THEN 'Quarter' WHEN 1 THEN 'Year' ELSE '' END",
  'label' => _("Interval"),
  'classes' => 'center' ];
$tableopt->cols[] = (object) [
  'sel' => "CONCAT('".$_SESSION['currency_mark']."',FORMAT(SUM(donation.Amount) - (pledge.Amount * TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m'))),".$_SESSION['currency_decimals']."))",
  'label' => _('Balance'),
  'classes' => 'right' ];
$tableopt->cols[] = (object) [
  'sel' => "ROUND((SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m')))) / pledge.Amount * 12 / TimesPerYear)",
  'label' => _('Months'),
  'classes' => 'center' ];

$current_dtype = '';
while ($row = mysqli_fetch_object($result)) {
  if ($dtgrouped && $row->DonationType!=$current_dtype && $current_dtype!='') {
    // We're doing subtotals and we've come to the end of a donation type

  }
  $tableopt->ids .= $row->PledgeID.',';
  if ($dtgrouped) $current_dtype = $row->DonationType;
}
require_once("flextable.php");
flextable($tableopt);

footer();
exit;
?>
<h2><?=($_GET['closed']) ? _("All Pledges") : _("Open Pledges")?></h2>
<?php exit(); ?>

<table style="empty-cells:show"><thead><tr>
<th><?=_('Name')?></th>
<th><?=_('Pledge Type')?></th>
<th><?=_('Amount')?></th>
<th><?=_('Dates')?></th>
<th><?=_('Balance')?></th>
<th><?=_('Months')?></th>
<th><?=_('Description')?></th>
</tr></thead><tbody>
<?php
$prev_donationtype = "";
$total = $subtotal = $subnumber = 0;
while ($row = mysqli_fetch_object($result)) {
  if ($show_subtotals && $prev_donationtype != "" && $row->DonationType != $prev_donationtype) {
    echo "<tr><td nowrap style=\"padding:2px 4px 2px 4px;\"><b>"._("Subtotal").
    " ("._("number: ").$subnumber.")</b></td>\n";
    echo "<td nowrap style=\"padding:2px 4px 2px 4px;\"><b>{$prev_donationtype}</b></td>\n";
    echo "<td nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
    number_format($subtotal,$_SESSION['currency_decimals'])."</b></td><td colspan=\"4\"></td></tr>\n";
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
  echo "<td nowrap style=\"padding:2px 4px 2px 4px;\">".
  db2table($row->PledgeDesc)."</td></tr>\n";
  $prev_donationtype = $row->DonationType;
  $subtotal += $row->Amount;
  $subnumber++;
  $total += $row->Amount;
}
if ($show_subtotals && $prev_donationtype != "" && $row->DonationType != $prev_donationtype) {
  echo "<tr><td nowrap style=\"padding:2px 4px 2px 4px;\"><b>"._("Subtotal").
  " ("._("number: ").$subnumber.")</b></td>\n";
  echo "<td nowrap style=\"padding:2px 4px 2px 4px;\"><b>{$prev_donationtype}</b></td>\n";
  echo "<td nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
  number_format($subtotal,$_SESSION['currency_decimals'])."</b></td><td colspan=\"4\"></td></tr>\n";
  echo "<tr><td nowrap style=\"padding:2px 4px 2px 4px;\"><b>"._("Total").
  " ("._("total number: ").mysqli_num_rows($result).")</b></td>\n";
  echo "<td></td>\n";
  echo "<td nowrap style=\"padding:2px 4px 2px 4px;\"><b>".$_SESSION['currency_mark']." ".
  number_format($total,$_SESSION['currency_decimals'])."</b></td><td colspan=\"4\"></td></tr>\n";
}
echo "</table>\n";

footer();
?>
