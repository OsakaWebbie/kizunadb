<?php
include("functions.php");
include("accesscontrol.php");

$show_nav = !empty($_REQUEST['pledge_tab']) ? 1 : 0;

header1(_("Pledge List"));
?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
<?php
header2($show_nav);
if ($show_nav == 1) echo "<h1 id=\"title\">"._("Pledge List")."</h1>\n";

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

$dtgrouped = !empty($_GET['dtgrouped']) || !empty($_GET['psubtotals']);

// Process filter parameters
$dtype = isset($_REQUEST['dtype']) && is_array($_REQUEST['dtype']) ? $_REQUEST['dtype'] : array();
$start = isset($_REQUEST['start']) ? h2d($_REQUEST['start']) : '';
$end = isset($_REQUEST['end']) ? h2d($_REQUEST['end']) : '';
$search = isset($_REQUEST['search']) ? h2d($_REQUEST['search']) : '';
$cutoff = isset($_REQUEST['cutoff']) ? $_REQUEST['cutoff'] : '';
$cutofftype = isset($_REQUEST['cutofftype']) ? $_REQUEST['cutofftype'] : '>=';

// Build WHERE clause and criteria display
$where_conditions = array();
$criteria = '';

// Existing closed pledge filter
if (empty($_GET['closed'])) {
  $where_conditions[] = "(EndDate='0000-00-00' OR EndDate>CURDATE())";
  $criteria .= "<li>" . _("Open pledges only") . "</li>\n";
}

// Donation type filter
if (!empty($dtype)) {
  $dtype_clean = array_map('intval', $dtype);
  $where_conditions[] = "pledge.DonationTypeID IN (" . implode(',', $dtype_clean) . ")";
  // Fetch donation type names for criteria display
  $dtype_result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN (" . implode(',', $dtype_clean) . ")");
  $dtarray = array();
  while ($dtrow = mysqli_fetch_object($dtype_result)) {
    $dtarray[] = $dtrow->DonationType;
  }
  $criteria .= "<li>" . sprintf(_("In at least one of these donation types: %s"), implode(", ", $dtarray)) . "</li>\n";
}

// Date range - filter by StartDate
if (!empty($start)) {
  $where_conditions[] = "StartDate >= '$start'";
}
if (!empty($end)) {
  $where_conditions[] = "StartDate <= '$end'";
}
if (!empty($start) || !empty($end)) {
  $criteria .= "<li>";
  if (!empty($start) && !empty($end)) {
    $criteria .= sprintf(_("Start date between %s and %s"), $_REQUEST['start'], $_REQUEST['end']);
  } elseif (!empty($start)) {
    $criteria .= sprintf(_("Start date on or after %s"), $_REQUEST['start']);
  } elseif (!empty($end)) {
    $criteria .= sprintf(_("Start date on or before %s"), $_REQUEST['end']);
  }
  $criteria .= "</li>\n";
}

// Description search
if (!empty($search)) {
  $where_conditions[] = "PledgeDesc LIKE '%$search%'";
  $criteria .= "<li>" . sprintf(_("\"%s\" in Description"), htmlspecialchars($_REQUEST['search'])) . "</li>\n";
}

// Amount filter
if ($cutoff !== '') {
  if (!in_array($cutofftype, array('>=', '<=', '='))) {
    $cutofftype = '>=';
  }
  $where_conditions[] = "Amount $cutofftype " . (float)$cutoff;
  $criteria .= "<li>" . sprintf(_("Amount %s %s"), htmlspecialchars($cutofftype), number_format((float)$cutoff)) . "</li>\n";
}

// Basket filter
if (!empty($_REQUEST['basket']) && !empty($_SESSION['basket'])) {
  $where_conditions[] = "pledge.PersonID IN (" . implode(',', $_SESSION['basket']) . ")";
  $criteria .= "<li>" . _('In the Basket') . " (" . count($_SESSION['basket']) . ")</li>\n";
}

// Combine conditions
$where_clause = '';
if (!empty($where_conditions)) {
  $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
}

// Build SQL query
if ($dtgrouped) {
  $sql = "SELECT PledgeID, pledge.DonationTypeID, DonationType FROM pledge " .
      "LEFT JOIN donationtype ON pledge.DonationTypeID=donationtype.DonationTypeID" .
      $where_clause . " ORDER BY DonationType";
} else {
  $sql = "SELECT PledgeID FROM pledge" . $where_clause;
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
  echo "<h3>" . _("There are no records matching your criteria:") . "</h3>\n";
  if (!empty($criteria)) echo "<ul id=\"criteria\">" . $criteria . "</ul>";
  footer();
  exit;
}

// Display criteria summary
if (!empty($criteria)) {
  echo "<h3>" . sprintf(_("%d results of these criteria:"), $num_pledges) . "</h3>\n";
  echo "<ul id=\"criteria\">" . $criteria . "</ul>";
}

// Fallback default if config missing: name,dtype,dates,desc,amount,balance
$showcols = ',' . ($_SESSION['pledgelist_showcols'] ?? 'name,dtype,dates,desc,amount,balance') . ',';

$tableopt = (object) [
  'ids' => '',
  'keyfield' => 'pledge.PledgeID',
  'tableid' => 'pledges',
  'heading' => !empty($_GET['closed']) ? _('All Pledges') : _('Open Pledges').' ('.$num_pledges.')',
  'order' => 'Furigana',
  'cols' => array() ];

// PersonID
$tableopt->cols[] = (object) [ 'key' => 'personid', 'sel' => 'person.PersonID', 'label' => _('ID'), 'show' => (stripos($showcols, ',personid,') !== FALSE) ];

// Name-related columns (all hideable for flexibility)
$tableopt->cols[] = (object) [ 'key' => 'name', 'sel' => 'person.Name', 'label' => _('Name'), 'show'=>(stripos($showcols, ',name,') !== FALSE) ];
$tableopt->cols[] = (object) [ 'key' => 'fullname', 'sel' => 'person.FullName', 'label' => _('Full Name'), 'show'=>(stripos($showcols, ',fullname,') !== FALSE) ];
$tableopt->cols[] = (object) [ 'key' => 'furigana', 'sel' => 'person.Furigana', 'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')), 'show'=>(stripos($showcols, ',furigana,') !== FALSE), 'sort' => 1 ];
$tableopt->cols[] = (object) [ 'key' => 'photo', 'sel' => 'person.Photo', 'label' => _('Photo'), 'show'=>(stripos($showcols, ',photo,') !== FALSE), 'sortable' => false, 'class' => 'align-center' ];

// Contact info
$tableopt->cols[] = (object) [ 'key' => 'phones', 'sel' => 'Phones', 'label' => _('Phones'), 'show'=>(stripos($showcols, ',phones,') !== FALSE) ];
$tableopt->cols[] = (object) [ 'key' => 'email', 'sel' => 'person.Email', 'label' => _('Email'), 'show' => (stripos($showcols, ',email,') !== FALSE) ];
$tableopt->cols[] = (object) [ 'key' => 'address', 'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))", 'label' => _('Address'), 'show' => (stripos($showcols, ',address,') !== FALSE), 'render' => 'multiline', 'table' => 'person', 'lazy' => TRUE ];

// Demographics
$tableopt->cols[] = (object) [ 'key' => 'sex', 'sel' => 'person.Sex', 'label' => _('Sex'), 'show' => (stripos($showcols, ',sex,') !== FALSE) ];
$tableopt->cols[] = (object) [ 'key' => 'age', 'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))", 'label' => _('Age'), 'show' => (stripos($showcols, ',age,') !== FALSE), 'classes' => 'center', 'table' => 'person' ];
$tableopt->cols[] = (object) [ 'key' => 'birthdate', 'sel' => 'person.Birthdate', 'label' => _('Birthdate'), 'show' => (stripos($showcols, ',birthdate,') !== FALSE), 'classes' => 'center' ];
$tableopt->cols[] = (object) [ 'key' => 'country', 'sel' => 'person.Country', 'label' => _('Country'), 'show' => (stripos($showcols, ',country,') !== FALSE) ];
$tableopt->cols[] = (object) [ 'key' => 'url', 'sel' => 'person.URL', 'label' => _('URL'), 'show' => (stripos($showcols, ',url,') !== FALSE) ];
$tableopt->cols[] = (object) [ 'key' => 'remarks', 'sel' => 'person.Remarks', 'label' => _('Remarks'), 'show' => (stripos($showcols, ',remarks,') !== FALSE) ];
if (!$dtgrouped) {
  $tableopt->cols[] = (object) [ 'key' => 'donationtype', 'sel' => 'donationtype.DonationType', 'label' => 'Donation Type', 'show'=>(stripos($showcols, ',dtype,') !== FALSE),
      'join' => 'LEFT JOIN donationtype ON pledge.DonationTypeID=donationtype.DonationTypeID' ];
};
$tableopt->cols[] = (object) [
  'key' => 'dates',
  'sel' => "CONCAT(StartDate,'～',IF(EndDate!='0000-00-00',EndDate,''))",
  'label' => 'Dates',
  'show' => (stripos($showcols, ',dates,') !== FALSE) ];
$tableopt->cols[] = (object) [
  'key' => 'pledgedesc',
  'sel' => 'PledgeDesc',
  'label' => 'Description',
  'show' => (stripos($showcols, ',desc,') !== FALSE) ];
$tableopt->cols[] = (object) [
  'key' => 'amount',
  'sel' => "CONCAT('".$_SESSION['currency_mark']."',FORMAT(pledge.Amount,".$_SESSION['currency_decimals']."))",
  'label' => 'Amount',
  'show' => (stripos($showcols, ',amount,') !== FALSE),
  'class' => 'align-right' ];
$tableopt->cols[] = (object) [
  'key' => 'interval',
  'sel' => "CASE TimesPerYear WHEN 12 THEN 'Month' WHEN 4 THEN 'Quarter' WHEN 1 THEN 'Year' ELSE '' END",
  'label' => 'Interval',
  'show' => (stripos($showcols, ',interval,') !== FALSE),
  'class' => 'align-center' ];
$tableopt->cols[] = (object) [
  'key' => 'balance',
  'sel' => "CONCAT(".
      "IF(SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(TimesPerYear=0, IF(CURDATE()<StartDate,0,1), TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m'))))) < 0, '<span style=\"color:red\">', ''), ".
      "'".$_SESSION['currency_mark']."',FORMAT(SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(TimesPerYear=0, IF(CURDATE()<StartDate,0,1), TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m'))))),".$_SESSION['currency_decimals']."), ".
      "IF(SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(TimesPerYear=0, IF(CURDATE()<StartDate,0,1), TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m'))))) < 0, '</span>', ''))",
  'label' => 'Balance',
  'show' => (stripos($showcols, ',balance,') !== FALSE),
  'join' => 'LEFT JOIN donation ON pledge.PledgeID=donation.PledgeID',
  'class' => 'align-right' ];
$tableopt->cols[] = (object) [
  'key' => 'months',
  'sel' => "CONCAT(".
      "IF((SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m')))) / pledge.Amount * 12 / TimesPerYear < 0, '<span style=\"color:red\">', ''), ".
      "ROUND((SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m')))) / pledge.Amount * 12 / TimesPerYear), ".
      "IF((SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(EndDate='0000-00-00' ".
      "OR CURDATE()<EndDate,CURDATE(), EndDate), '%Y%m'), DATE_FORMAT(StartDate, '%Y%m')))) / pledge.Amount * 12 / TimesPerYear < 0, '</span>', ''))",
  'label' => 'Months',
  'show' => (stripos($showcols, ',months,') !== FALSE),
    'join' => 'LEFT JOIN donation ON pledge.PledgeID=donation.PledgeID',
  'class' => 'align-center' ];
// Lazy-loaded Categories column
$tableopt->cols[] = (object) [
  'key' => 'categories',
  'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
  'label' => 'Categories',
  'show' => (stripos($showcols, ',categories,') !== FALSE),
  'lazy' => TRUE,
  // Don't specify 'table' - let it default to keyfield table (pledge)
  'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID' ];

if (!$dtgrouped) {
  // Normal mode - single table with all pledges
  while ($row = mysqli_fetch_object($result)) {
    $tableopt->ids .= $row->PledgeID.',';
  }
  require_once("flextable.php");
  flextable($tableopt);
} else {
  // Grouped mode - separate table for each donation type
  $groups = array();

  // Collect pledges grouped by donation type
  while ($row = mysqli_fetch_object($result)) {
    if (!isset($groups[$row->DonationType])) {
      $groups[$row->DonationType] = array(
        'ids' => array(),
        'name' => $row->DonationType,
        'dtype_id' => $row->DonationTypeID
      );
    }
    $groups[$row->DonationType]['ids'][] = $row->PledgeID;
  }

  require_once("flextable.php");

  // Display a table for each donation type
  $group_num = 0;
  foreach ($groups as $dtype_name => $group) {
    // Add separator between groups
    if ($group_num > 0) {
      echo '<hr>';
    }
    $group_num++;

    // Calculate subtotal first so we can include it in the heading
    $pledge_ids = implode(',', array_map('intval', $group['ids']));
    $subtotal_sql = "SELECT SUM(Amount) as total, COUNT(*) as count FROM pledge WHERE PledgeID IN ($pledge_ids)";
    $subtotal_result = sqlquery_checked($subtotal_sql);
    $subtotal_row = mysqli_fetch_object($subtotal_result);

    // Display heading with donation type and subtotal
    echo '<h3>' . htmlspecialchars($dtype_name) . ' (' .
         sprintf(_('%d pledges'), $subtotal_row->count) . ', ' .
         _('total') . ' ' . $_SESSION['currency_mark'] .
         number_format($subtotal_row->total, $_SESSION['currency_decimals']) .
         ')</h3>';

    // Create new tableopt for this group with unique table ID
    $group_tableopt = clone $tableopt;
    $group_tableopt->ids = implode(',', $group['ids']);
    $group_tableopt->tableid = 'pledges-' . $group['dtype_id']; // Unique ID per donation type
    $group_tableopt->heading = ''; // Remove heading as we have h3 above

    // Call flextable for this group
    flextable($group_tableopt);
  }
}

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
