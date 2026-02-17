<?php
include("functions.php");
include("accesscontrol.php");

// Early-exit AJAX handler
if (!empty($_GET['query'])) {
  $startmonth = $_GET['startmonth'] ?? '';
  $startday = $_GET['startday'] ?? '';
  $endmonth = $_GET['endmonth'] ?? '';
  $endday = $_GET['endday'] ?? '';
  $cat = $_GET['cat'] ?? [];
  if (!is_array($cat)) $cat = [];
  $catlist = implode(',', array_map('intval', $cat));
  $basket = $_GET['basket'] ?? '';

  if (!$startmonth) {
    echo "<p>Error: Invalid parameters.</p>";
    exit;
  }

  // Format date range for header
  $gen = new IntlDatePatternGenerator($_SESSION['lang']);
  $fmt_md = new IntlDateFormatter($_SESSION['lang'], IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, $gen->getBestPattern('MMMMd'));
  $sd = min((int)$startday, (int)date('t', mktime(0,0,0,(int)$startmonth,1,2000)));
  $ed = min((int)$endday,   (int)date('t', mktime(0,0,0,(int)$endmonth,1,2000)));
  $start_str = $fmt_md->format(mktime(0,0,0,(int)$startmonth,$sd,2000));
  $end_str   = $fmt_md->format(mktime(0,0,0,(int)$endmonth,$ed,2000));

  // Display header and build SQL statement
  if (!$catlist) {
    echo "<h3>".sprintf(_("All birthdays from %s to %s:"), $start_str, $end_str)."</h3>\n";
    $sql = "SELECT DISTINCT p.PersonID FROM person p WHERE ";
  } else {
    $sql = "SELECT DISTINCT p.PersonID FROM person p, percat c "
    . "WHERE p.PersonID=c.PersonID AND c.CategoryID IN ({$catlist}) AND ";
    $sql2 = "SELECT * FROM category WHERE CategoryID IN ({$catlist}) ORDER BY Category";
    $result = sqlquery_checked($sql2);
    $row = mysqli_fetch_object($result);
    $cat_names = $row->Category;
    while ($row = mysqli_fetch_object($result)) {
      $cat_names .= ", " . $row->Category;
    }
    echo "<h3>".sprintf(_("Birthdays from %1\$s to %2\$s in categories %3\$s:"),
       $start_str, $end_str, "<i>$cat_names</i>")."</h3>\n";
  }
  // Add basket filter
  if (!empty($basket) && !empty($_SESSION['basket'])) {
    $sql .= "p.PersonID IN (" . implode(',', $_SESSION['basket']) . ") AND ";
  }
  // Finish WHERE clause
  $startcombo = str_pad($startmonth,2,"0", STR_PAD_LEFT) . str_pad($startday,2,"0", STR_PAD_LEFT);
  $endcombo = str_pad($endmonth,2,"0", STR_PAD_LEFT) . str_pad($endday,2,"0", STR_PAD_LEFT);
  if (($startmonth>$endmonth) || ($startmonth==$endmonth && $startday>$endday)) {
    // Date range crosses year boundary
    $sql .= "((DATE_FORMAT(Birthdate,'%Y%m%d') >= CONCAT(YEAR(Birthdate),'{$startcombo}') ";
    $sql .= "AND DATE_FORMAT(Birthdate,'%Y%m%d') <= CONCAT(YEAR(Birthdate),'1231')) ";
    $sql .= "OR (DATE_FORMAT(Birthdate,'%Y%m%d') >= CONCAT(YEAR(Birthdate),'0101') ";
    $sql .= "AND DATE_FORMAT(Birthdate,'%Y%m%d') <= CONCAT(YEAR(Birthdate),'{$endcombo}')))";
  } else {
    $sql .= "DATE_FORMAT(Birthdate,'%Y%m%d') >= CONCAT(YEAR(Birthdate),'{$startcombo}') ";
    $sql .= "AND DATE_FORMAT(Birthdate,'%Y%m%d') <= CONCAT(YEAR(Birthdate),'{$endcombo}')";
  }

  $result = sqlquery_checked($sql);
  $person_ids = array();
  while ($row = mysqli_fetch_object($result)) {
    $person_ids[] = $row->PersonID;
  }

  if (count($person_ids) == 0) {
    echo "<h3>"._("There are no records matching your criteria.")."</h3>";
    exit;
  }

  $lookahead_date = date('Y-m-d', mktime(0, 0, 0, date('m')+4, date('d'), date('Y')));
  echo '<p style="font-size:0.85em; color:#666;">* '
    .sprintf(_("\"Age after Birthday\" is calculated as of %s (4 months from today)."), $lookahead_date)
    ."</p>\n";

  // FlexTable
  require_once("flextable.php");

  $showcols = ',' . ($_SESSION['birthdaylist_showcols'] ?? 'name,birthday,ageafterbday') . ',';

  $tableopt = (object) [
    'ids' => implode(',', $person_ids),
    'keyfield' => 'person.PersonID',
    'tableid' => 'birthdaylist',
    'heading' => sprintf(_('%d matching birthdays'), count($person_ids)),
    'order' => 'MONTH(person.Birthdate), DAYOFMONTH(person.Birthdate)',
    'cols' => array()
  ];

  // PersonID
  $tableopt->cols[] = (object) [
    'key' => 'personid',
    'sel' => 'person.PersonID',
    'label' => _('ID'),
    'show' => (stripos($showcols, ',personid,') !== FALSE)
  ];

  // Name (composite FullName+Furigana)
  $tableopt->cols[] = (object) [
    'key' => 'name',
    'sel' => 'person.Name',
    'label' => _('Name'),
    'show' => (stripos($showcols, ',name,') !== FALSE)
  ];

  $tableopt->cols[] = (object) [
    'key' => 'fullname',
    'sel' => 'person.FullName',
    'label' => _('Full Name'),
    'show' => (stripos($showcols, ',fullname,') !== FALSE)
  ];

  $tableopt->cols[] = (object) [
    'key' => 'furigana',
    'sel' => 'person.Furigana',
    'label' => ($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')),
    'show' => (stripos($showcols, ',furigana,') !== FALSE)
  ];

  // Birthday (MM-DD only)
  $tableopt->cols[] = (object) [
    'key' => 'birthday',
    'sel' => "IF(person.Birthdate='0000-00-00','',DATE_FORMAT(person.Birthdate,'%m-%d'))",
    'label' => _('Birthday'),
    'show' => (stripos($showcols, ',birthday,') !== FALSE),
    'classes' => 'center',
    'sort' => 1
  ];

  // Age after Birthday (4-month lookahead)
  $tableopt->cols[] = (object) [
    'key' => 'ageafterbday',
    'sel' => "IF(person.Birthdate='0000-00-00' OR SUBSTRING(person.Birthdate,1,4)='1900','',TIMESTAMPDIFF(YEAR,person.Birthdate,DATE_ADD(CURDATE(),INTERVAL 4 MONTH)))",
    'label' => _('Age after Birthday'),
    'show' => (stripos($showcols, ',ageafterbday,') !== FALSE),
    'classes' => 'center sorter-digit',
    'render' => 'age'
  ];

  // Photo
  $tableopt->cols[] = (object) [
    'key' => 'photo',
    'sel' => 'person.Photo',
    'label' => _('Photo'),
    'show' => (stripos($showcols, ',photo,') !== FALSE),
    'sortable' => false
  ];

  // Phones
  $tableopt->cols[] = (object) [
    'key' => 'phones',
    'sel' => 'Phones',
    'label' => _('Phones'),
    'show' => (stripos($showcols, ',phones,') !== FALSE)
  ];

  // Email
  $tableopt->cols[] = (object) [
    'key' => 'email',
    'sel' => 'person.Email',
    'label' => _('Email'),
    'show' => (stripos($showcols, ',email,') !== FALSE),
    'render' => 'email'
  ];

  // Address
  $tableopt->cols[] = (object) [
    'key' => 'address',
    'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
    'label' => _('Address'),
    'show' => (stripos($showcols, ',address,') !== FALSE),
    'render' => 'multiline',
    'lazy' => TRUE
  ];

  // Age (standard current age)
  $tableopt->cols[] = (object) [
    'key' => 'age',
    'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
    'label' => _('Age'),
    'show' => (stripos($showcols, ',age,') !== FALSE),
    'classes' => 'center',
    'render' => 'age'
  ];

  // Birthdate (full date)
  $tableopt->cols[] = (object) [
    'key' => 'birthdate',
    'sel' => 'person.Birthdate',
    'label' => _('Birthdate'),
    'show' => (stripos($showcols, ',birthdate,') !== FALSE),
    'classes' => 'center',
    'render' => 'birthdate'
  ];

  // Sex
  $tableopt->cols[] = (object) [
    'key' => 'sex',
    'sel' => 'person.Sex',
    'label' => _('Sex'),
    'show' => (stripos($showcols, ',sex,') !== FALSE)
  ];

  // Country
  $tableopt->cols[] = (object) [
    'key' => 'country',
    'sel' => 'person.Country',
    'label' => _('Country'),
    'show' => (stripos($showcols, ',country,') !== FALSE)
  ];

  // URL
  $tableopt->cols[] = (object) [
    'key' => 'url',
    'sel' => 'person.URL',
    'label' => _('URL'),
    'show' => (stripos($showcols, ',url,') !== FALSE)
  ];

  // Categories
  $tableopt->cols[] = (object) [
    'key' => 'categories',
    'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
    'label' => _('Categories'),
    'show' => (stripos($showcols, ',categories,') !== FALSE),
    'lazy' => TRUE,
    'render' => 'multiline',
    'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID'
  ];

  // Events
  $tableopt->cols[] = (object) [
    'key' => 'events',
    'sel' => "e.Events",
    'label' => _('Events'),
    'show' => (stripos($showcols, ',events,') !== FALSE),
    'lazy' => TRUE,
    'render' => 'multiline',
    'join' => "LEFT OUTER JOIN (SELECT aq.PersonID,GROUP_CONCAT(CONCAT(Event,' [',attqty,'x]') ORDER BY Event SEPARATOR '\\n') AS Events ".
        "FROM (SELECT PersonID,Event,COUNT(*) AS attqty FROM attendance AS at INNER JOIN event ev ON ev.EventID = at.EventID ".
        "GROUP BY at.PersonID,at.EventID) AS aq GROUP BY aq.PersonID) AS e ON e.PersonID = person.PersonID"
  ];

  // Remarks
  $tableopt->cols[] = (object) [
    'key' => 'remarks',
    'sel' => 'person.Remarks',
    'label' => _('Remarks'),
    'show' => (stripos($showcols, ',remarks,') !== FALSE)
  ];

  flextable($tableopt);
  exit;
}

// Normal page load - render the form
header1(_("Birthdays"));
?>
<link rel="stylesheet" href="style.php?jquery=1&multiselect=1&table=1" type="text/css" />
<?php
header2(1);

// Build localized month and day options
$gen = new IntlDatePatternGenerator($_SESSION['lang']);
$fmt_month = new IntlDateFormatter($_SESSION['lang'], IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'MMMM');
$day_suffix = str_replace('d', '', $gen->getBestPattern('d'));
$today_month = (int)date('n', mktime(gmdate("H")+9));

$month_options = '';
for ($i = 1; $i <= 12; $i++) {
  $name = $fmt_month->format(mktime(0, 0, 0, $i, 15));
  $sel = ($i == $today_month) ? ' selected' : '';
  $month_options .= "<option value=\"$i\"$sel>$name</option>\n";
}
$day_options_start = '';
$day_options_end = '';
for ($i = 1; $i <= 31; $i++) {
  $label = $i . $day_suffix;
  $day_options_start .= "<option value=\"$i\"" . ($i == 1 ? ' selected' : '') . ">$label</option>\n";
  $day_options_end   .= "<option value=\"$i\"" . ($i == 31 ? ' selected' : '') . ">$label</option>\n";
}

// Build select elements for use in sprintf
$startmonth_sel = '<select id="startmonth" name="startmonth">' . $month_options . '</select>';
$startday_sel   = '<select id="startday" name="startday" class="day-select">' . $day_options_start . '</select>';
$endmonth_sel   = '<select id="endmonth" name="endmonth">' . $month_options . '</select>';
$endday_sel     = '<select id="endday" name="endday" class="day-select">' . $day_options_end . '</select>';
?>

<h1 class="title"><?=_('Birthdays')?></h1>

<form id="bform" method="GET">
  <div style="display:flex; flex-wrap:wrap; gap:1em; align-items:center">
    <button type="button" id="all_year"><?=_("All Year")?></button>
    <label class="label-n-input">
      <input type="checkbox" id="specificdays">
      <?=_("Specific days")?>
    </label>
    <span style="white-space:nowrap"><?php printf(_('From %1$s %2$s'), $startmonth_sel, $startday_sel); ?></span>
    <span style="white-space:nowrap"><?php printf(_('until %1$s %2$s'), $endmonth_sel, $endday_sel); ?></span>
    <label class="label-n-input"><?=_("Categories")?>:
      <select id="cat" name="cat[]" multiple="multiple" size="1">
<?php
$result = sqlquery_checked("SELECT * FROM category WHERE UseFor != 'O' ORDER BY Category");
while ($row = mysqli_fetch_object($result)) {
  echo "      <option value=\"" . (int)$row->CategoryID . "\">" . d2h($row->Category) . "</option>\n";
}
?>
      </select>
    </label>
<?php if (!empty($_SESSION['basket'])) { ?>
    <label class="label-n-input">
      <input type="checkbox" name="basket" value="1">
      <?=sprintf(_("Limit to Basket (%d)"), count($_SESSION['basket']))?>
    </label>
<?php } ?>
  </div>

  <div style="margin-top:1em">
    <button type="submit" id="list_birthdays"><?=_("List Birthdays")?></button>
  </div>
  <input type="hidden" name="query" value="1">
</form>

<div id="ResultFrame"></div>

<?php
load_scripts(['jquery', 'jqueryui', 'multiselect']);
?>
<script type="text/javascript">
var daySuffix = '<?=$day_suffix?>';

function daysInMonth(month) {
  return new Date(2000, month, 0).getDate(); // 2000 = leap year, so Feb allows 29
}

function updateDays(which) {
  var monthVal = (which === 'start') ? +$('#startmonth').val() : +$('#endmonth').val();
  var obj = (which === 'start') ? $('#startday')[0] : $('#endday')[0];
  var max = daysInMonth(monthVal);
  if (obj.options.length > max) {
    if (obj.selectedIndex > max - 1) obj.selectedIndex = max - 1;
    obj.options.length = max;
  } else {
    for (var i = obj.options.length; i < max; i++) {
      obj.options[i] = new Option((i + 1) + daySuffix, i + 1);
    }
  }
}

function toggleDays() {
  if ($('#specificdays').is(':checked')) {
    $('.day-select').show();
    updateDays('start');
    updateDays('end');
  } else {
    $('.day-select').hide();
    $('#startday')[0].selectedIndex = 0;
    $('#endday')[0].selectedIndex = $('#endday')[0].options.length - 1;
  }
}

function allYear() {
  $('#specificdays').prop('checked', false);
  $('.day-select').hide();
  $('#startmonth').val(1);
  updateDays('start');
  $('#startday')[0].selectedIndex = 0;
  $('#endmonth').val(12);
  updateDays('end');
  $('#endday')[0].selectedIndex = 30;
}

$(document).ready(function() {
  $("#cat").multiselect({
    noneSelectedText: '<?=_("Select...")?>',
    selectedText: '<?=_("# selected")?>',
    checkAllText: '<?=_("Check all")?>',
    uncheckAllText: '<?=_("Uncheck all")?>'
  }).multiselectfilter({
    label: '<?=_("Search:")?>'
  });

  $('.day-select').hide();

  $('#startmonth').change(function() { updateDays('start'); });
  $('#endmonth').change(function() { updateDays('end'); });
  $('#specificdays').change(function() { toggleDays(); });
  $('#all_year').click(function() { allYear(); });

  $('#bform').submit(function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    $('#ResultFrame').html('<p style="padding:1em;color:#888"><?=_("Loading...")?></p>');
    $.get('birthday.php', formData, function(response) {
      $('#ResultFrame').html(response);
    });
  });
});
</script>
<?php
footer();
?>
