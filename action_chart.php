<?php
include("functions.php");
include("accesscontrol.php");

$listtype = $_POST['listtype'] ?? 'Normal';

header1(_("Action List").(!empty($_POST['preselected']) ? sprintf(_(" (%d People/Orgs Pre-selected)"),
    substr_count($_POST['preselected'],",")+1) : ""));
?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
<?php
header2($_GET['nav'] ?? 0);
if (($_GET['nav'] ?? 0)==1) echo "<h1 id=\"title\">"._("Action List").(!empty($_POST['preselected']) ?
sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : "")."</h1>\n";

$where = '';
if (!empty($_POST['atype'])) $where .= ($where?" AND":" WHERE")." a.ActionTypeID IN (".implode(",",$_POST['atype']).")";
if (!empty($_POST['startdate'])) $where .= ($where?" AND":" WHERE")." ActionDate >= '".$_POST['startdate']."'";
if (!empty($_POST['enddate'])) $where .= ($where?" AND":" WHERE")." ActionDate <= '".$_POST['enddate']."'";
if (!empty($_POST['csearch'])) $where .= ($where?" AND":" WHERE")." Description LIKE '%".$_POST['csearch']."%'";
if (!empty($_POST['preselected'])) $where .= ($where?" AND":" WHERE")." a.PersonID IN (".$_POST['preselected'].")";

// Get ActionIDs for flextable
if ($listtype == 'Normal') {
  $sql = "SELECT ActionID FROM action a".$where." ORDER BY ActionID";
  $result = sqlquery_checked($sql);
  $num_actions = mysqli_num_rows($result);
  if ($num_actions == 0) {
    echo "<h3>"._("There are no records matching your criteria.")."</h3>";
    footer();
    exit;
  }
  $action_ids = array();
  $pidarray = array();  // For multi-select button
  while ($row = mysqli_fetch_object($result)) {
    $action_ids[] = $row->ActionID;
  }
  // Also get distinct PersonIDs for multi-select
  $sql2 = "SELECT DISTINCT PersonID FROM action a".$where;
  $result2 = sqlquery_checked($sql2);
  while ($row = mysqli_fetch_object($result2)) {
    $pidarray[] = $row->PersonID;
  }
  $pids = implode(",",$pidarray);
} else {
  $sql = "SELECT DISTINCT PersonID FROM action a".$where." ORDER BY PersonID";
  $result = sqlquery_checked($sql);
  $num_people = mysqli_num_rows($result);
  if ($num_people == 0) {
    echo "<h3>"._("There are no records matching your criteria.")."</h3>";
    footer();
    exit;
  }
  $pidarray = array();
  while ($row = mysqli_fetch_object($result)) {
    $pidarray[] = $row->PersonID;
  }
  $pids = implode(",",$pidarray);
}

// For grouped modes, collect ActionIDs by group
if ($listtype != 'Normal') {
  $sql_grouped = 'SELECT a.ActionID, a.ActionTypeID, at.ActionType, a.PersonID, p.FullName, p.Furigana ';
  $sql_grouped .= 'FROM action a ';
  $sql_grouped .= 'LEFT JOIN person p ON a.PersonID=p.PersonID ';
  $sql_grouped .= 'LEFT JOIN actiontype at ON a.ActionTypeID=at.ActionTypeID';
  $sql_grouped .= $where;

  if ($listtype == 'ActionType') {
    $sql_grouped .= ' ORDER BY ActionType, Furigana, PersonID, ActionDate DESC';
  } else { // PersonID
    $sql_grouped .= ' ORDER BY Furigana, PersonID, ActionDate DESC';
  }

  $result_grouped = sqlquery_checked($sql_grouped);

  $groups = array();
  while ($row = mysqli_fetch_object($result_grouped)) {
    if ($listtype == 'ActionType') {
      $group_key = $row->ActionTypeID;
      $group_name = $row->ActionType;
    } else { // PersonID
      $group_key = $row->PersonID;
      $group_name = readable_name($row->FullName, $row->Furigana);
      $group_pid = $row->PersonID;
    }

    if (!isset($groups[$group_key])) {
      $groups[$group_key] = array(
        'name' => $group_name,
        'ids' => array()
      );
      if ($listtype == 'PersonID') {
        $groups[$group_key]['pid'] = $group_pid;
      }
    }
    $groups[$group_key]['ids'][] = $row->ActionID;
  }
}

if ($listtype == 'Normal') {
  // FlexTable implementation for Normal mode
  require_once("flextable.php");

  // Fallback default if config missing: name,adate,atype,desc
  $showcols = ',' . ($_SESSION['actionlist_showcols'] ?? 'name,adate,atype,desc') . ',';

  $tableopt = (object) [
    'ids' => implode(',', $action_ids),
    'keyfield' => 'action.ActionID',
    'tableid' => 'actionlist',
    'heading' => sprintf(_('%d matching actions'), $num_actions),
    'order' => 'ActionDate DESC',
    'cols' => array()
  ];

  // 1. Person-related columns first (in standard order)
  // Note: person/household JOINs auto-added by flextable when keyfield != person.PersonID

  // PersonID
  $tableopt->cols[] = (object) [
    'key' => 'personid',
    'sel' => 'person.PersonID',
    'label' => _('ID'),
    'show' => (stripos($showcols, ',personid,') !== FALSE),
    'table' => 'person'
  ];

  // Name-related columns (all hideable for flexibility)
  $tableopt->cols[] = (object) [
    'key' => 'name',
    'sel' => 'person.Name',
    'label' => _('Name'),
    'show' => (stripos($showcols, ',name,') !== FALSE),
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'fullname',
    'sel' => 'person.FullName',
    'label' => _('Full Name'),
    'show' => (stripos($showcols, ',fullname,') !== FALSE),
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'furigana',
    'sel' => 'person.Furigana',
    'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')),
    'show' => (stripos($showcols, ',furigana,') !== FALSE),
    'table' => 'person'
  ];

  // 2. Action-related columns

  // Column: Action Date
  $tableopt->cols[] = (object) [
    'key' => 'actiondate',
    'sel' => 'action.ActionDate',
    'label' => _('Date'),
    'show' => (stripos($showcols, ',adate,') !== FALSE),
    'classes' => 'center',
    'sort' => -1
  ];

  // Column: Action Type
  $tableopt->cols[] = (object) [
    'key' => 'actiontype',
    'sel' => 'actiontype.ActionType',
    'label' => _('Action Type'),
    'show' => (stripos($showcols, ',atype,') !== FALSE),
    'join' => 'LEFT JOIN actiontype ON action.ActionTypeID=actiontype.ActionTypeID'
  ];

  // Column: Description
  $tableopt->cols[] = (object) [
    'key' => 'description',
    'sel' => 'action.Description',
    'label' => _('Description'),
    'show' => (stripos($showcols, ',desc,') !== FALSE)
  ];

  // Photo
  $tableopt->cols[] = (object) [
    'key' => 'photo',
    'sel' => 'person.Photo',
    'label' => _('Photo'),
    'show' => (stripos($showcols, ',photo,') !== FALSE),
    'sortable' => false,
    'table' => 'person'
  ];

  // Contact info
  $tableopt->cols[] = (object) [
    'key' => 'phones',
    'sel' => 'Phones',
    'label' => _('Phones'),
    'show' => (stripos($showcols, ',phones,') !== FALSE),
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'email',
    'sel' => 'person.Email',
    'label' => _('Email'),
    'show' => (stripos($showcols, ',email,') !== FALSE),
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'address',
    'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
    'label' => _('Address'),
    'show' => (stripos($showcols, ',address,') !== FALSE),
    'render' => 'multiline',
    'table' => 'person',
    'lazy' => TRUE
  ];

  // Other person fields
  $tableopt->cols[] = (object) [
    'key' => 'age',
    'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
    'label' => _('Age'),
    'show' => (stripos($showcols, ',age,') !== FALSE),
    'classes' => 'center',
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'birthdate',
    'sel' => 'person.Birthdate',
    'label' => _('Birthdate'),
    'show' => (stripos($showcols, ',birthdate,') !== FALSE),
    'classes' => 'center',
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'sex',
    'sel' => 'person.Sex',
    'label' => _('Sex'),
    'show' => (stripos($showcols, ',sex,') !== FALSE),
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'country',
    'sel' => 'person.Country',
    'label' => _('Country'),
    'show' => (stripos($showcols, ',country,') !== FALSE),
    'table' => 'person'
  ];

  $tableopt->cols[] = (object) [
    'key' => 'url',
    'sel' => 'person.URL',
    'label' => _('URL'),
    'show' => (stripos($showcols, ',url,') !== FALSE),
    'table' => 'person'
  ];

  // Categories
  $tableopt->cols[] = (object) [
    'key' => 'categories',
    'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
    'label' => _('Categories'),
    'show' => (stripos($showcols, ',categories,') !== FALSE),
    'lazy' => TRUE,
    'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID'
  ];

  // Events
  $tableopt->cols[] = (object) [
    'key' => 'events',
    'sel' => "e.Events",
    'label' => _('Events'),
    'show' => (stripos($showcols, ',events,') !== FALSE),
    'lazy' => TRUE,
    'join' => "LEFT OUTER JOIN (SELECT aq.PersonID,GROUP_CONCAT(CONCAT(Event,' [',attqty,'x]') ORDER BY Event SEPARATOR '\\n') AS Events FROM (SELECT PersonID,Event,COUNT(*) AS attqty FROM attendance AS at INNER JOIN event ev ON ev.EventID = at.EventID GROUP BY at.PersonID,at.EventID) AS aq GROUP BY aq.PersonID) AS e ON e.PersonID = person.PersonID"
  ];

  // Remarks last
  $tableopt->cols[] = (object) [
    'key' => 'remarks',
    'sel' => 'person.Remarks',
    'label' => _('Remarks'),
    'show' => (stripos($showcols, ',remarks,') !== FALSE),
    'table' => 'person'
  ];

  flextable($tableopt);

  footer();
  exit;
}

// Grouped modes - NOW USING FLEXTABLE
require_once("flextable.php");

$showcols = ',' . ($_SESSION['actionlist_showcols'] ?? 'name,adate,atype,desc') . ',';

// Display one flextable per group
foreach ($groups as $group_key => $group) {
  // Heading with group name
  if ($listtype == 'PersonID') {
    echo '<h3><a href="individual.php?pid='.$group['pid'].'" target="_blank">'.$group['name'].'</a></h3>'."\n";
  } else { // ActionType
    echo '<h3>'.$group['name'].'</h3>'."\n";
  }

  // Build flextable options for this group
  $tableopt = (object) [
    'ids' => implode(',', $group['ids']),
    'keyfield' => 'action.ActionID',
    'tableid' => 'actionlist-' . $group_key,
    'heading' => sprintf(_('%d actions'), count($group['ids'])),
    'order' => 'ActionDate DESC',
    'cols' => array()
  ];

  // Column definitions (same as Normal mode but with conditional visibility)

  // Person-related columns (hide in PersonID mode)
  $tableopt->cols[] = (object) [
    'key' => 'personid',
    'sel' => 'person.PersonID',
    'label' => _('ID'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',personid,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'name',
    'sel' => 'person.Name',
    'label' => _('Name'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',name,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'fullname',
    'sel' => 'person.FullName',
    'label' => _('Full Name'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',fullname,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'furigana',
    'sel' => 'person.Furigana',
    'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',furigana,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'photo',
    'sel' => 'person.Photo',
    'label' => _('Photo'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',photo,') !== FALSE),
    'sortable' => false,
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'phones',
    'sel' => 'Phones',
    'label' => _('Phones'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',phones,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'email',
    'sel' => 'person.Email',
    'label' => _('Email'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',email,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'address',
    'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
    'label' => _('Address'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',address,') !== FALSE),
    'render' => 'multiline',
    'table' => 'person',
    'lazy' => TRUE,
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'age',
    'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
    'label' => _('Age'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',age,') !== FALSE),
    'classes' => 'center',
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'birthdate',
    'sel' => 'person.Birthdate',
    'label' => _('Birthdate'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',birthdate,') !== FALSE),
    'classes' => 'center',
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'sex',
    'sel' => 'person.Sex',
    'label' => _('Sex'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',sex,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'country',
    'sel' => 'person.Country',
    'label' => _('Country'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',country,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'url',
    'sel' => 'person.URL',
    'label' => _('URL'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',url,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'remarks',
    'sel' => 'person.Remarks',
    'label' => _('Remarks'),
    'show' => ($listtype != 'PersonID' && stripos($showcols, ',remarks,') !== FALSE),
    'table' => 'person',
    'colsel' => ($listtype != 'PersonID')
  ];

  // Action-related columns
  $tableopt->cols[] = (object) [
    'key' => 'actiondate',
    'sel' => 'action.ActionDate',
    'label' => _('Date'),
    'show' => (stripos($showcols, ',adate,') !== FALSE),
    'classes' => 'center',
    'sort' => -1
  ];

  // Action Type (hide in ActionType mode)
  $tableopt->cols[] = (object) [
    'key' => 'actiontype',
    'sel' => 'actiontype.ActionType',
    'label' => _('Action Type'),
    'show' => ($listtype != 'ActionType' && stripos($showcols, ',atype,') !== FALSE),
    'join' => 'LEFT JOIN actiontype ON action.ActionTypeID=actiontype.ActionTypeID',
    'colsel' => ($listtype != 'ActionType')
  ];

  $tableopt->cols[] = (object) [
    'key' => 'description',
    'sel' => 'action.Description',
    'label' => _('Description'),
    'show' => (stripos($showcols, ',desc,') !== FALSE)
  ];

  // Render this group's table
  flextable($tableopt);
}

if ($_SESSION['userid'] == 'dev') {
  echo 'SQL:<pre class="noprint">'.$sql_grouped.'</pre>';
}
footer();
?>
