<?php
include("functions.php");
include("accesscontrol.php");

$ajax = !empty($_GET['ajax']);
$type = $_REQUEST['listtype'] ?? 'Normal';

if (!$ajax) {
  pageheader(_("Donation List"), 1);
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

// Process filter parameters
$dtype = isset($_REQUEST['dtype']) && is_array($_REQUEST['dtype']) ? $_REQUEST['dtype'] : array();
$start = isset($_REQUEST['start']) ? h2d($_REQUEST['start']) : '';
$end = isset($_REQUEST['end']) ? h2d($_REQUEST['end']) : '';
$proc = isset($_REQUEST['proc']) ? $_REQUEST['proc'] : '';
$search = isset($_REQUEST['search']) ? h2d($_REQUEST['search']) : '';
$cutoff = isset($_REQUEST['cutoff']) ? $_REQUEST['cutoff'] : '';
$cutofftype = isset($_REQUEST['cutofftype']) ? $_REQUEST['cutofftype'] : '>=';

//construct WHERE clause from criteria
$wheredone = 0;
$where = $criteria = '';
if (!empty($dtype)) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationTypeID IN (".implode(",",$dtype).")";
  $result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN (".implode(",",$dtype).")");
  $dtarray = array();
  while ($row = mysqli_fetch_object($result)) {
    $dtarray[] = $row->DonationType;
  }
  $criteria .= "<li>".sprintf(_("In at least one of these donation types: %s"),implode(",",$dtarray))."</li>\n";
  $wheredone = 1;
}
if ($start) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationDate>='".$start."'";
  $wheredone = 1;
}
if ($end) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationDate<='".$end."'";
  $wheredone = 1;
}
if ($start || $end) {
  $criteria .= "<li>";
  if ($start && $end) $criteria .= sprintf(_("Date between %s and %s"),$start,$end);
  elseif ($start) $criteria .= sprintf(_("Date on or after %s"),$start);
  elseif ($end) $criteria .= sprintf(_("Date on or before %s"),$end);
  $criteria .= "</li>\n";
}
if ($proc) {
  $where .= ($wheredone?" AND":" WHERE")." d.Processed=".($proc=="proc"?"1":"0");
  $criteria .= "<li>".($proc=="proc" ? _("Processed") : _("Unprocessed"))."</li>\n";
  $wheredone = 1;
}
if ($search !== "") {
  $where .= ($wheredone?" AND":" WHERE")." d.Description LIKE '%".$search."%'";
  $criteria .= "<li>".sprintf(_("\"%s\" in Description"), $search)."</li>\n";
  $wheredone = 1;
}
if ($cutoff !== "") {
  $where .= ($wheredone?" AND":" WHERE")." d.Amount".$cutofftype.(int)$cutoff;
  $criteria .= "<li>".sprintf(_("Amount %s %s"),$cutofftype,$cutoff)."</li>\n";
  $wheredone = 1;
}
// Basket filtering
if (!empty($_REQUEST['basket']) && !empty($_SESSION['basket'])) {
  $where .= ($wheredone?" AND":" WHERE")." d.PersonID IN (".implode(',',$_SESSION['basket']).")";
  $criteria .= "<li>"._('In the Basket')." (".count($_SESSION['basket']).")</li>\n";
  $wheredone = 1;
}
if (!empty($criteria))  $criteria = "<ul id=\"criteria\">$criteria</ul>";

// Prep query for list/grouped modes
if ($type == "Normal") {
  // Normal list: single query for DonationIDs only; all display data fetched by flextable
  $sql = "SELECT d.DonationID FROM donation d".$where." ORDER BY d.DonationDate DESC";
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) == 0) {
    echo "<h3>"._("There are no records matching your criteria:")."</h3>\n".$criteria;
    if (!$ajax) { load_scripts(['jquery']); footer(); }
    exit;
  }
  $donation_ids = array();
  while ($row = mysqli_fetch_object($result)) {
    $donation_ids[] = $row->DonationID;
  }
} else {
  // Grouped or summary modes: query for person/type groups
  if ($type=="DonationType") {
    $sql = "SELECT dt.DonationTypeID, dt.DonationType, d.PersonID, SUM(d.Amount) AS subtotal FROM donationtype dt ".
    "LEFT JOIN donation d ON d.DonationTypeID=dt.DonationTypeID".$where." OR d.DonationDate IS NULL";
    $sql .= " GROUP BY dt.DonationTypeID ORDER BY ".
      (($_REQUEST['subtotalsort'] ?? false) ? "subtotal DESC," : "")."dt.DonationType,d.DonationTypeID";
  } else {  // PersonID
    $sql = "SELECT p.PersonID,p.FullName,p.Furigana,SUM(d.Amount) subtotal".
    " FROM donation d LEFT JOIN person p ON p.PersonID=d.PersonID ".
    "LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID".$where;
    $sql .= " GROUP BY p.PersonID ORDER BY ".
    (($_REQUEST['subtotalsort'] ?? false) ? "subtotal DESC," : "")."p.Furigana,p.PersonID";
  }
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) == 0) {
    echo "<h3>"._("There are no records matching your criteria:")."</h3>\n".$criteria;
    if (!$ajax) { load_scripts(['jquery']); footer(); }
    exit;
  }
  $pidarray = array();
  while ($row = mysqli_fetch_object($result)) {
    $pidarray[] = $row->PersonID;
    if ($type=="DonationType") $dtidarray[] = $row->DonationTypeID;
  }
  $pids = implode(",",$pidarray);
  if ($type=="DonationType") $dtids = implode(",",$dtidarray);

  // Second query for grouped modes: get donation details for grouping
  $sql = "SELECT d.DonationID,d.PersonID,d.PledgeID,d.DonationDate,CAST(d.Amount AS DECIMAL(10,".
  $_SESSION['currency_decimals'].")) Amount,d.Description,d.Processed,p.FullName,p.Furigana,".
  "IF(d.PledgeID,pl.DonationTypeID,d.DonationTypeID) DonationTypeID,".
  "IF(d.PledgeID,dt2.DonationType,dt.DonationType) DonationType,pl.PledgeDesc".
  " FROM donation d LEFT JOIN person p ON p.PersonID=d.PersonID".
  " LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID".
  " LEFT JOIN pledge pl ON d.PledgeID=pl.PledgeID".
  " LEFT JOIN donationtype dt2 ON pl.DonationTypeID=dt2.DonationTypeID".$where;
  if ($type == "PersonID") {
    $sql .= " ORDER BY ".(($_REQUEST['subtotalsort'] ?? false) ? "FIND_IN_SET(d.PersonID, '".$pids."')" : "Furigana,d.PersonID").",d.DonationDate DESC";
  } else { // DonationType
    $sql .= " ORDER BY ".(($_REQUEST['subtotalsort'] ?? false) ? "FIND_IN_SET(d.DonationTypeID, '".$dtids."')" : "dt.DonationType").",d.DonationDate DESC";
  }
  $result = sqlquery_checked($sql);

  // Collect donations by group
  $groups = array();
  while ($row = mysqli_fetch_object($result)) {
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

// Display results count and criteria
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

// FLEXTABLE implementation for all list modes
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
    'label' => _('Name (display)'),
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
    'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji Name') : _('Furigana Name')),
    'show' => ($type != "PersonID" && stripos($showcols, ',furigana,') !== FALSE),
    'colsel' => ($type != "PersonID")
  ];

  // Person information columns (hidden in PersonID mode)
  $tableopt->cols[] = (object) [
    'key' => 'phones',
    'sel' => 'Phones',
    'label' => _('Phone'),
    'show' => ($type != "PersonID" && stripos($showcols, ',phone,') !== FALSE),
    'classes' => 'sorter-text',
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
    'join' => 'LEFT JOIN pledge ON donation.PledgeID=pledge.PledgeID',
    'render' => 'remarks'
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
    'show' => (stripos($showcols, ',desc,') !== FALSE),
    'render' => 'remarks'
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
             ruby_name($group['fullname'], $group['furigana']) . '</a> (' .
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
