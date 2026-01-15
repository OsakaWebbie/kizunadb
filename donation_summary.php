<?php
include("functions.php");
include("accesscontrol.php");

$show_nav = !empty($_REQUEST['summary_tab']) ? 1 : 0;
$type = $_REQUEST['summarytype'] ?? 'DonationType';

header1(_("Donation Summary"));
?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
<style>
td.amount-for-display { text-align: right; }
</style>
<?php
header2($show_nav);
if ($show_nav == 1) echo "<h1 id=\"title\">"._("Donation Summary")."</h1>\n";

// Build WHERE clause from filter criteria (matching donation_list.php pattern)
$wheredone = 0;
$where = $having = $criteria = '';

if (!empty($_REQUEST['dtype'])) {
  $where .= ($wheredone ? " AND" : " WHERE") . " d.DonationTypeID IN (" . implode(",", array_map('intval', $_REQUEST['dtype'])) . ")";
  $result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN (" . implode(",", array_map('intval', $_REQUEST['dtype'])) . ")");
  $dtarray = array();
  while ($row = mysqli_fetch_object($result)) {
    $dtarray[] = $row->DonationType;
  }
  $criteria .= "<li>" . sprintf(_("In at least one of these donation types: %s"), implode(", ", $dtarray)) . "</li>\n";
  $wheredone = 1;
}
if (!empty($_REQUEST['start'])) {
  $where .= ($wheredone ? " AND" : " WHERE") . " d.DonationDate>='" . h2d($_REQUEST['start']) . "'";
  $wheredone = 1;
}
if (!empty($_REQUEST['end'])) {
  $where .= ($wheredone ? " AND" : " WHERE") . " d.DonationDate<='" . h2d($_REQUEST['end']) . "'";
  $wheredone = 1;
}
if (!empty($_REQUEST['start']) || !empty($_REQUEST['end'])) {
  $criteria .= "<li>";
  if (!empty($_REQUEST['start']) && !empty($_REQUEST['end'])) {
    $criteria .= sprintf(_("Date between %s and %s"), $_REQUEST['start'], $_REQUEST['end']);
  } elseif (!empty($_REQUEST['start'])) {
    $criteria .= sprintf(_("Date on or after %s"), $_REQUEST['start']);
  } elseif (!empty($_REQUEST['end'])) {
    $criteria .= sprintf(_("Date on or before %s"), $_REQUEST['end']);
  }
  $criteria .= "</li>\n";
}
if (!empty($_REQUEST['proc'])) {
  $where .= ($wheredone ? " AND" : " WHERE") . " d.Processed=" . ($_REQUEST['proc'] == "proc" ? "1" : "0");
  $criteria .= "<li>" . ($_REQUEST['proc'] == "proc" ? _("Processed") : _("Unprocessed")) . "</li>\n";
  $wheredone = 1;
}
if (($_REQUEST['search'] ?? '') !== '') {
  $where .= ($wheredone ? " AND" : " WHERE") . " d.Description LIKE '%" . h2d($_REQUEST['search']) . "%'";
  $criteria .= "<li>" . sprintf(_("\"%s\" in Description"), htmlspecialchars($_REQUEST['search'])) . "</li>\n";
  $wheredone = 1;
}
if (($_REQUEST['cutoff'] ?? '') !== '') {
  $cutofftype = $_REQUEST['cutofftype'] ?? '>=';
  $having = " HAVING SUM(d.Amount)" . $cutofftype . (int)$_REQUEST['cutoff'];
  $criteria .= "<li>" . sprintf(_("Amount %s %s"), htmlspecialchars($cutofftype), number_format((int)$_REQUEST['cutoff'])) . "</li>\n";
}

// Build the summary query
if ($type == "DonationType") {
  $sql = "SELECT dt.DonationTypeID, dt.DonationType, SUM(d.Amount) AS subtotal FROM donation d";
  $sql .= " LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID";
  $sql .= $where;
  $sql .= " GROUP BY d.DonationTypeID";
  $sql .= " HAVING SUM(d.Amount) > 0"; // Only show types with donations
  if ($having) $sql .= " AND SUM(d.Amount)" . ($_REQUEST['cutofftype'] ?? '>=') . (int)($_REQUEST['cutoff'] ?? 0);
  $sql .= " ORDER BY dt.DonationType";
} else {
  // By Person
  $sql = "SELECT p.PersonID, p.FullName, p.Furigana, SUM(d.Amount) AS subtotal FROM donation d";
  $sql .= " LEFT JOIN person p ON p.PersonID=d.PersonID";
  $sql .= $where;
  $sql .= " GROUP BY d.PersonID";
  $sql .= " HAVING SUM(d.Amount) > 0"; // Only show people with donations
  if ($having) $sql .= " AND SUM(d.Amount)" . ($_REQUEST['cutofftype'] ?? '>=') . (int)($_REQUEST['cutoff'] ?? 0);
  // When using limit (top N donors), order by amount descending; otherwise by name
  $sql .= " ORDER BY " . (!empty($_REQUEST['limit']) ? "subtotal DESC" : "p.Furigana, p.PersonID");
  if (!empty($_REQUEST['limit'])) {
    $sql .= " LIMIT " . (int)$_REQUEST['limit'];
  }
}

$result = sqlquery_checked($sql);

if (mysqli_num_rows($result) == 0) {
  echo "<h3>" . _("There are no records matching your criteria:") . "</h3>\n";
  if (!empty($criteria)) echo "<ul id=\"criteria\">" . $criteria . "</ul>";
  footer();
  exit;
}

// Display criteria
if (!empty($criteria)) {
  echo "<h3>" . sprintf(_("%d results of these criteria:"), mysqli_num_rows($result)) . "</h3>\n";
  echo "<ul id=\"criteria\">" . $criteria . "</ul>";
} else {
  echo "<h3>" . sprintf(_("%d results (all records)"), mysqli_num_rows($result)) . "</h3>\n";
}

// CSV download form
echo "<div id=\"actions\">";
?>
<form action="download.php" method="post" target="_top">
  <input type="hidden" id="csvtext" name="csvtext" value="">
  <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
</form>
<?php
echo "</div>";

// Build table headers
if ($type == "PersonID") {
  $tableheads = "<th class=\"name-for-csv\" style=\"display:none\">" . _("Name") . "</th>\n";
  $tableheads .= "<th class=\"furigana-for-csv\" style=\"display:none\">" .
    ($_SESSION['furiganaisromaji'] == "yes" ? _("Romaji") : _("Furigana")) . "</th>\n";
  $tableheads .= "<th class=\"name-for-display\">" . _("Name") . " (" .
    ($_SESSION['furiganaisromaji'] == "yes" ? _("Romaji") : _("Furigana")) . ")</th>\n";
} else {
  $tableheads = "<th class=\"dtype\">" . _("Donation Type") . "</th>\n";
}
$tableheads .= "<th class=\"amount-for-csv\" style=\"display:none\">" . _("Amount") . "</th>\n";
$tableheads .= "<th class=\"amount-for-display\">" . _("Amount") . "</th>\n";

// Output table
echo "<table id=\"summarytable\" class=\"tablesorter\">\n<thead>\n<tr>" . $tableheads . "</tr>\n</thead><tbody>\n";

$total = 0;
while ($row = mysqli_fetch_object($result)) {
  echo "<tr>";
  if ($type == "PersonID") {
    echo "<td class=\"name-for-csv\" style=\"display:none\">" . htmlspecialchars($row->FullName) . "</td>\n";
    echo "<td class=\"furigana-for-csv\" style=\"display:none\">" . htmlspecialchars($row->Furigana) . "</td>\n";
    echo "<td class=\"name-for-display\"><span style=\"display:none\">" . htmlspecialchars($row->Furigana) . "</span>";
    echo "<a href=\"individual.php?pid=" . $row->PersonID . "\" target=\"_blank\">";
    echo readable_name($row->FullName, $row->Furigana) . "</a></td>\n";
  } else {
    echo "<td class=\"dtype\">" . htmlspecialchars($row->DonationType) . "</td>\n";
  }
  echo "<td class=\"amount-for-csv\" style=\"display:none\">" .
    number_format($row->subtotal, $_SESSION['currency_decimals'], ".", "") . "</td>\n";
  echo "<td class=\"amount-for-display\"><span style=\"display:none\">" . sprintf("%015s", $row->subtotal) . "</span>" .
    $_SESSION['currency_mark'] . " " . number_format($row->subtotal, $_SESSION['currency_decimals']) . "</td>\n";
  echo "</tr>\n";
  $total += $row->subtotal;
}
echo "</tbody>\n</table>\n";

// Total outside of table (so it doesn't interfere with tablesorter)
echo "<h3>" . _("Total") . ": " . $_SESSION['currency_mark'] . " " .
  number_format($total, $_SESSION['currency_decimals']) . "</h3>\n";

load_scripts(['jquery', 'tablesorter', 'table2csv']);
?>
<script>
$(function() {
  // PersonID has hidden columns 0,1 (CSV), visible column 2 (name), hidden 3 (CSV), visible 4 (amount)
  // DonationType has visible column 0 (type), hidden 1 (CSV), visible 2 (amount)
  $("#summarytable").tablesorter({ sortList:[[<?=($type=="PersonID" ? (!empty($_REQUEST['limit']) ? "4,1" : "2,0") : "0,0")?>]] });
});

function getCSV() {
  $(".name-for-display, .amount-for-display").hide();
  $(".name-for-csv, .amount-for-csv, .furigana-for-csv").show();
  $('#csvtext').val($('#summarytable').table2CSV({delivery:'value'}));
  $(".name-for-csv, .amount-for-csv, .furigana-for-csv").hide();
  $(".name-for-display, .amount-for-display").show();
}
</script>
<?php
footer();
?>
