<?php
include("functions.php");
include("accesscontrol.php");

$ajax = !empty($_GET['ajax']);
$summary = $_REQUEST['show_summary'] ?? 0;
$type = $_REQUEST['listtype'] ?? 'Normal';

if (!$ajax) {
  header1(_("Donation List"));
  ?>
  <link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
  <?php
  header2(1);
}
?>
<style>
div#procbuttons { text-align:right; }
div#procbuttons button { margin-left:10px; }
td.dtype, td.amount { white-space:nowrap; }
td.amount { text-align:right; }
td.subtotal { background-color:#FFFFE0; white-space:nowrap; font-weight:bold; }
</style>
<h1 id="title"><?=_("Donation List")?></h1>
<?php

//construct WHERE clause from criteria
$wheredone = 0;
$where = $having = $criteria = '';
if ($_REQUEST['dtype'] ?? false) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationTypeID IN (".implode(",",$_REQUEST['dtype']).")";
  $result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN (".implode(",",$_REQUEST['dtype']).")");
  $dtarray = array();
  while ($row = mysqli_fetch_object($result)) {
    $dtarray[] = $row->DonationType;
  }
  $criteria .= "<li>".sprintf(_("In at least one of these donation types: %s"),implode(",",$dtarray))."</li>\n";
  $wheredone = 1;
}
if ($_REQUEST['start']) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationDate>='".$_REQUEST['start']."'";
  $wheredone = 1;
}
if ($_REQUEST['end']) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationDate<='".$_REQUEST['end']."'";
  $wheredone = 1;
}
if ($_REQUEST['start'] || $_REQUEST['end']) {
  $criteria .= "<li>";
  if ($_REQUEST['start'] && $_REQUEST['end']) $criteria .= sprintf(_("Date between %s and %s"),$_REQUEST['start'],$_REQUEST['end']);
  elseif ($_REQUEST['start']) $criteria .= sprintf(_("Date on or after %s"),$_REQUEST['start']);
  elseif ($_REQUEST['end']) $criteria .= sprintf(_("Date on or before %s"),$_REQUEST['end']);
  $criteria .= "</li>\n";
}
if ($_REQUEST['proc']) {
  $where .= ($wheredone?" AND":" WHERE")." d.Processed=".($_REQUEST['proc']=="proc"?"1":"0");
  $criteria .= "<li>".($_REQUEST['proc']=="proc" ? _("Processed") : _("Unprocessed"))."</li>\n";
  $wheredone = 1;
}
if ($_REQUEST['search']!="") {
  $where .= ($wheredone?" AND":" WHERE")." d.Description LIKE '%".$_REQUEST['search']."%'";
  $criteria .= "<li>".sprintf(_("\"%s\" in Description"), $_REQUEST['search'])."</li>\n";
  $wheredone = 1;
}
if ($_REQUEST['cutoff']!="") {
  if ($summary) $having = " HAVING SUM(d.Amount)".$_REQUEST['cutofftype'].(int)$_REQUEST['cutoff'];
  else $where .= ($wheredone?" AND":" WHERE")." d.Amount".$_REQUEST['cutofftype'].(int)$_REQUEST['cutoff'];
  $criteria .= "<li>".sprintf(_("Amount %s %s"),$_REQUEST['cutofftype'],$_REQUEST['cutoff'])."</li>\n";
  $wheredone = 1;
}
// Basket filtering
if (!empty($_REQUEST['basket']) && !empty($_SESSION['basket'])) {
  $where .= ($wheredone?" AND":" WHERE")." d.PersonID IN (".implode(',',$_SESSION['basket']).")";
  $criteria .= "<li>"._('In the Basket')." (".count($_SESSION['basket']).")</li>\n";
  $wheredone = 1;
}
if (!empty($criteria))  $criteria = "<ul id=\"criteria\">$criteria</ul>";

// Main query for summary, or prep query for lists
if ($type=="DonationType") {
  $sql = "SELECT dt.DonationTypeID, dt.DonationType, d.PersonID, SUM(d.Amount) AS subtotal FROM donationtype dt ".
  "LEFT JOIN donation d ON d.DonationTypeID=dt.DonationTypeID".$where." OR d.DonationDate IS NULL";
  $sql .= " GROUP BY dt.DonationTypeID".$having." ORDER BY ".
    (($_REQUEST['subtotalsort'] ?? false) ? "subtotal DESC," : "")."dt.DonationType,d.DonationTypeID";
} else {  // single list or grouped/summary by person
// in the case of a single list, this query is only to get the IDs for multiselect, so we don't need the other information or subtotals
  $sql = "SELECT ".($type=="Normal" ? "DISTINCT p.PersonID" : "p.PersonID,p.FullName,p.Furigana,SUM(d.Amount) subtotal").
  " FROM donation d LEFT JOIN person p ON p.PersonID=d.PersonID ".
  "LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID".$where;
  if ($type=="Normal") {
    $sql .= " ORDER BY p.PersonID";
  } else {
    $sql .= " GROUP BY p.PersonID".$having." ORDER BY ".
    (($_REQUEST['subtotalsort'] ?? false) || ($summary && ($_REQUEST['limit'] ?? false)) ? "subtotal DESC," : "")."p.Furigana,p.PersonID";
  }
  if ($summary && $type=="PersonID" && $_REQUEST['limit']) $sql .= " LIMIT ".(int)$_REQUEST['limit'];
}
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result) == 0) {
  echo "<h3>"._("There are no records matching your criteria:")."</h3>\n".$criteria;
  if (!$ajax) footer();
  exit;
}
$pidarray = array();
while ($row = mysqli_fetch_object($result)) {
  $pidarray[] = $row->PersonID;
  if ($type=="DonationType") $dtidarray[] = $row->DonationTypeID;
}
$pids = implode(",",$pidarray);
if ($type=="DonationType") $dtids = implode(",",$dtidarray);

if (!$summary) {
  $sql = "SELECT d.DonationID,d.PersonID,d.PledgeID,d.DonationDate,CAST(d.Amount AS DECIMAL(10,".
  $_SESSION['currency_decimals'].")) Amount,d.Description,d.Processed,p.FullName,p.Furigana,".
  "IF(d.PledgeID,pl.DonationTypeID,d.DonationTypeID) DonationTypeID,".
  "IF(d.PledgeID,dt2.DonationType,dt.DonationType) DonationType,pl.PledgeDesc";
  if ($type == "Normal") $sql .= ",p.Photo,p.CellPhone,p.Email,p.Country,p.Remarks,h.*,pc.*";
  $sql .= " FROM donation d LEFT JOIN person p ON p.PersonID=d.PersonID";
  if ($type == "Normal") $sql .= " LEFT JOIN household h ON p.HouseholdID=h.HouseholdID".
  " LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode";
  $sql .= " LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID".
  " LEFT JOIN pledge pl ON d.PledgeID=pl.PledgeID".
  " LEFT JOIN donationtype dt2 ON pl.DonationTypeID=dt2.DonationTypeID".$where;
  if ($type == "PersonID") {
    $sql .= " ORDER BY ".(($_REQUEST['subtotalsort'] ?? false) ? "FIND_IN_SET(d.PersonID, '".$pids."')" : "Furigana,d.PersonID").",d.DonationDate DESC";
  } elseif ($type == "DonationType") {
    $sql .= " ORDER BY ".(($_REQUEST['subtotalsort'] ?? false) ? "FIND_IN_SET(d.DonationTypeID, '".$dtids."')" : "dt.DonationType").",d.DonationDate DESC";
  } else {  // listtype == Normal
    $sql .= " ORDER BY d.DonationDate DESC";
  }
  $result = sqlquery_checked($sql);

  // Collect DonationIDs for flextable
  if ($type == "Normal") {
    // Normal mode - single flat list
    $donation_ids = array();
    while ($row = mysqli_fetch_object($result)) {
      $donation_ids[] = $row->DonationID;
    }
  } else {
    // Grouped mode - collect donations by group (DonationType or PersonID)
    $groups = array();

    while ($row = mysqli_fetch_object($result)) {
      // Use numeric ID as group key (safe for HTML IDs)
      if ($type == "DonationType") {
        $group_key = $row->DonationTypeID;
        $group_name = $row->DonationType;
      } else { // PersonID
        $group_key = $row->PersonID;
        $group_name = ''; // Name stored separately
      }

      if (!isset($groups[$group_key])) {
        $groups[$group_key] = array(
          'ids' => array(),
          'name' => $group_name,
          'fullname' => $row->FullName ?? '',
          'furigana' => $row->Furigana ?? ''
        );
      }
      $groups[$group_key]['ids'][] = $row->DonationID;
    }
  }
}

// Display results count and criteria
if (!$summary) {
  // Count total donations
  if ($type == "Normal") {
    $donation_count = count($donation_ids);
  } else {
    $donation_count = 0;
    foreach ($groups as $group) {
      $donation_count += count($group['ids']);
    }
  }
  if (!empty($criteria)) {
    echo "<h3>".sprintf(_("%d results of these criteria:"),$donation_count)."</h3>\n".$criteria;
  } else {
  echo "<h3>".sprintf(_("%d results (all records)"),$donation_count)."</h3>\n";
  }
}

// FLEXTABLE implementation for all list modes
if (!$summary) {
  require_once("flextable.php");

  $showcols = ',' . ($_SESSION['donationlist_showcols'] ?? 'ddate,name,dtype,pledge,amount,desc,proc') . ',';

  // Build base table options
  $tableopt = (object) [
    'ids' => ($type == "Normal" ? implode(',', $donation_ids) : ''),
    'keyfield' => 'donation.DonationID',
    'tableid' => ($type == "Normal" ? 'donationlist' : ''),
    'heading' => '',
    'order' => 'DonationDate DESC',
    'cols' => array()
  ];

  // Person ID (hidden in PersonID mode)
  $tableopt->cols[] = (object) [
    'key' => 'personid',
    'sel' => 'person.PersonID',
    'label' => _('ID'),
    'show' => ($type != "PersonID" && stripos($showcols, ',personid,') !== FALSE),
    'colsel' => ($type != "PersonID")
  ];

  // Name columns (hidden in PersonID mode)
  $tableopt->cols[] = (object) [
    'key' => 'name',
    'sel' => 'person.Name',
    'label' => _('Name'),
    'show' => ($type != "PersonID" && stripos($showcols, ',name,') !== FALSE),
    'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
    'key' => 'fullname',
    'sel' => 'person.FullName',
    'label' => _('Full Name'),
    'show' => ($type != "PersonID" && stripos($showcols, ',fullname,') !== FALSE),
    'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
    'key' => 'furigana',
    'sel' => 'person.Furigana',
    'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')),
    'show' => ($type != "PersonID" && stripos($showcols, ',furigana,') !== FALSE),
    'colsel' => ($type != "PersonID")
  ];

  // Person information columns (hidden in PersonID mode)
  $tableopt->cols[] = (object) [
    'key' => 'phones',
    'sel' => 'Phones',
    'label' => _('Phone'),
    'show' => ($type != "PersonID" && stripos($showcols, ',phone,') !== FALSE),
    'table' => 'person',
    'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
    'key' => 'email',
    'sel' => 'person.Email',
    'label' => _('Email'),
    'show' => ($type != "PersonID" && stripos($showcols, ',email,') !== FALSE),
    'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
    'key' => 'address',
    'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
    'label' => _('Address'),
    'show' => ($type != "PersonID" && stripos($showcols, ',address,') !== FALSE),
    'render' => 'multiline',
    'table' => 'person',
    'lazy' => TRUE,
    'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
    'key' => 'country',
    'sel' => 'person.Country',
    'label' => _('Home Country'),
    'show' => ($type != "PersonID" && stripos($showcols, ',country,') !== FALSE),
    'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
    'key' => 'remarks',
    'sel' => 'person.Remarks',
    'label' => _('Remarks'),
    'show' => ($type != "PersonID" && stripos($showcols, ',remarks,') !== FALSE),
    'render' => 'remarks',
    'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
      'key' => 'categories',
      'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
      'label' => _('Categories'),
      'show' => ($type != "PersonID" && stripos($showcols, ',categories,') !== FALSE),
      'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID',
      'colsel' => ($type != "PersonID")
  ];

  $tableopt->cols[] = (object) [
      'key' => 'events',
      'sel' => "e.Events",
      'label' => _('Events'),
      'show' => ($type != "PersonID" && stripos($showcols, ',events,') !== FALSE),
      'join' => "LEFT OUTER JOIN (SELECT aq.PersonID,GROUP_CONCAT(CONCAT(Event,' [',attqty,'x]') ORDER BY Event SEPARATOR '\\n') AS Events FROM (SELECT PersonID,Event,COUNT(*) AS attqty FROM attendance AS at INNER JOIN event ev ON ev.EventID = at.EventID GROUP BY at.PersonID,at.EventID) AS aq GROUP BY aq.PersonID) AS e ON e.PersonID = person.PersonID",
      'colsel' => ($type != "PersonID")
  ];

  // Donation columns
  $tableopt->cols[] = (object) [
      'key' => 'donationdate',
      'sel' => 'donation.DonationDate',
      'label' => _('Date'),
      'show' => (stripos($showcols, ',ddate,') !== FALSE),
      'sort' => -1
  ];

  // Donation Type (hidden in grouped DonationType mode)
  $tableopt->cols[] = (object) [
    'key' => 'donationtype',
    'sel' => "IF(donation.PledgeID,dt2.DonationType,dt.DonationType)",
    'label' => _('Donation Type'),
    'show' => ($type != "DonationType" && stripos($showcols, ',dtype,') !== FALSE),
    'join' => 'LEFT JOIN donationtype dt ON donation.DonationTypeID=dt.DonationTypeID LEFT JOIN pledge pl ON donation.PledgeID=pl.PledgeID LEFT JOIN donationtype dt2 ON pl.DonationTypeID=dt2.DonationTypeID',
    'colsel' => ($type != "DonationType")
  ];

  // Pledge description if donation is fulfilling a pledge
  $tableopt->cols[] = (object) [
    'key' => 'pledge',
    'sel' => 'pledge.PledgeDesc',
    'label' => _('Pledge?'),
    'show' => (stripos($showcols, ',pledge,') !== FALSE),
    'join' => 'LEFT JOIN pledge ON donation.PledgeID=pledge.PledgeID'
  ];

  // Amount
  $tableopt->cols[] = (object) [
    'key' => 'amount',
    'sel' => "CONCAT('".$_SESSION['currency_mark']." ',FORMAT(donation.Amount,".$_SESSION['currency_decimals']."))",
    'label' => _('Amount'),
    'show' => (stripos($showcols, ',amount,') !== FALSE),
    'classes' => 'align-right'
  ];

  // Description
  $tableopt->cols[] = (object) [
    'key' => 'description',
    'sel' => 'donation.Description',
    'label' => _('Description'),
    'show' => (stripos($showcols, ',desc,') !== FALSE)
  ];

  // Processed - interactive checkboxes
  $tableopt->cols[] = (object) [
    'key' => 'processed',
    'sel' => 'donation.Processed',
    'label' => _('Proc.'),
    'show' => (stripos($showcols, ',proc,') !== FALSE),
    'sortable' => false,
    'render' => 'checkbox',
    'checkbox_idfield' => 'DonationID',
    'checkbox_action' => 'DonationProc'
  ];

  // Render table(s)
  if ($type == "Normal") {
    flextable($tableopt);
  } else {
    // Grouped mode
    $group_num = 0;
    foreach ($groups as $group_key => $group) {
      if ($group_num > 0) {
        echo '<hr>';
      }
      $group_num++;

      // Calculate subtotal
      $subtotal_sql = "SELECT SUM(Amount) as total, COUNT(*) as count FROM donation WHERE DonationID IN (" . implode(',', array_map('intval', $group['ids'])) . ")";
      $subtotal_result = sqlquery_checked($subtotal_sql);
      $subtotal_row = mysqli_fetch_object($subtotal_result);

      // Display heading
      if ($type == "PersonID") {
        echo '<h3><a href="individual.php?pid=' . $group_key . '" target="_blank">' .
             readable_name($group['fullname'], $group['furigana']) . '</a> (' .
             sprintf(_('%d donations'), $subtotal_row->count) . ', ' .
             _('total') . ' ' . $_SESSION['currency_mark'] .
             number_format($subtotal_row->total, $_SESSION['currency_decimals']) .
             ')</h3>';
      } else {
        echo '<h3>' . htmlspecialchars($group['name']) . ' (' .
             sprintf(_('%d donations'), $subtotal_row->count) . ', ' .
             _('total') . ' ' . $_SESSION['currency_mark'] .
             number_format($subtotal_row->total, $_SESSION['currency_decimals']) .
             ')</h3>';
      }

      // Clone and customize tableopt for this group
      $group_tableopt = clone $tableopt;
      $group_tableopt->ids = implode(',', $group['ids']);
      // Use the numeric group_key (DonationTypeID or PersonID) for valid HTML ID
      $group_tableopt->tableid = 'donations-' . $group_key;
      $group_tableopt->heading = '';

      flextable($group_tableopt);
    }
  }

  // Calculate and display total
  $total_sql = "SELECT SUM(Amount) as total FROM donation d".$where;
  $total_result = sqlquery_checked($total_sql);
  $total_row = mysqli_fetch_object($total_result);
  echo "<h3>"._("Total").": ".$_SESSION['currency_mark']." ".
    number_format($total_row->total,$_SESSION['currency_decimals'])."</h3>\n";

  if (!$ajax) footer();
  exit;
}

// Summary mode - legacy table building
if (!$ajax) {
  ?>
  <link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
  <style>
  td.amount-for-display { text-align:right; }
  </style>
  <?php
}
if ($type == "PersonID") {
  $tableheads = "<th class=\"name-for-csv\" style=\"display:none\">"._("Name")."</th>\n";
  $tableheads .= "<th class=\"furigana-for-csv\" style=\"display:none\">".
  ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana"))."</th>\n";
  $tableheads .= "<th class=\"name-for-display\">"._("Name")." (".
  ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>\n";
} else {
  $tableheads = "<th class=\"dtype\">"._("Donation Type")."</th>\n";
}
$tableheads .= "<th class=\"amount-for-csv\" style=\"display:none\">"._("Amount")."</th>\n";
$tableheads .= "<th class=\"amount-for-display\">"._("Amount")."</th>\n";

echo "<h3>".sprintf(_("%d results of these criteria:"),mysqli_num_rows($result))."</h3>\n";
echo $criteria;
echo "<div id=\"actions\">";
?>
  <form action="download.php" method="post" target="_top">
    <input type="hidden" id="csvtext" name="csvtext" value="">
    <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
  </form>
<?php
echo "</div>";

echo "<table id=\"summarytable\" class=\"tablesorter\">\n<thead>\n<tr>".$tableheads."</tr>\n</thead><tbody>\n";
$total = 0;
mysqli_data_seek($result, 0);
while ($row = mysqli_fetch_object($result)) {
  if ($type == "PersonID") {
    echo "<tr><td class=\"name-for-csv\" style=\"display:none\">".$row->FullName."</td>\n";
    echo "<td class=\"furigana-for-csv\" style=\"display:none\">".$row->Furigana."</td>\n";
    echo "<td class=\"name-for-display\"><span style=\"display:none\">".$row->Furigana."</span>";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name($row->FullName,$row->Furigana)."</a></td>\n";
  } else {
    echo "<tr><td class=\"dtype\">".$row->DonationType."</td>\n";
  }
  echo "<td class=\"amount-for-csv\" style=\"display:none\">".
  number_format($row->subtotal,$_SESSION['currency_decimals'],".","")."</td>\n";
  echo "<td class=\"amount-for-display\"><span style=\"display:none\">".sprintf("%015s",$row->subtotal)."</span>".
  $_SESSION['currency_mark']." ".number_format($row->subtotal,$_SESSION['currency_decimals'])."</td>\n";
  echo "</tr>\n";
  $total += $row->subtotal;
}
echo "</tbody>\n</table>";
echo "<h3>"._("Total").": ".$_SESSION['currency_mark']." ".number_format($total,$_SESSION['currency_decimals'])."</h3>\n";

if (!$ajax) load_scripts(['jquery', 'tablesorter', 'table2csv']);
?>
<script>
$(function() {
  $("#summarytable").tablesorter({ sortList:[[<?=($type=="PersonID"?($_REQUEST['limit']?"1,1":"0,0"):"0,0")?>]] });
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
if (!$ajax) footer();
?>
