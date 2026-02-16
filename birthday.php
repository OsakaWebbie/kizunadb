<?php
include("functions.php");
include("accesscontrol.php");

// Early-exit AJAX handler - merge of birthday_list.php content
if (!empty($_GET['query'])) {
  $startmonth = $_GET['startmonth'] ?? '';
  $startday = $_GET['startday'] ?? '';
  $endmonth = $_GET['endmonth'] ?? '';
  $endday = $_GET['endday'] ?? '';
  $wholemonth = $_GET['wholemonth'] ?? '';
  $catlist = $_GET['catlist'] ?? '';
  $basket = $_GET['basket'] ?? '';

  if (!$startmonth) {
    echo "<p>Error: Invalid parameters.</p>";
    exit;
  }

  // fill in days in case of whole_month
  if ($wholemonth) {
    $startday = 1;
    $max_days = array(31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $endday = $max_days[$endmonth-1];
  }

  // Display header and build SQL statement
  if (!$catlist) {
    echo "<h3>All birthdays between {$startmonth}/{$startday} and {$endmonth}/{$endday}:</h3>\n";
    $sql = "SELECT PersonID, FullName, Furigana, Photo, Birthdate FROM person p WHERE ";
  } else {
    // Add to SQL statement to limit to selected categories
    $sql = "SELECT DISTINCT p.PersonID, p.FullName, p.Furigana, p.Photo, p.Birthdate FROM person p, percat c "
    . "WHERE p.PersonID=c.PersonID AND c.CategoryID IN ({$catlist}) AND ";
    // List categories by name for use in header
    $sql2 = "SELECT * FROM category WHERE CategoryID IN ({$catlist}) ORDER BY Category";
    $result = sqlquery_checked($sql2);
    $row = mysqli_fetch_object($result);  // Get the first one; no preceding comma
    $cat_names = $row->Category;
    while ($row = mysqli_fetch_object($result)) {
      $cat_names .= ", " . $row->Category;
    }
    echo "<h3>Birthdays between {$startmonth}/{$startday} and {$endmonth}/{$endday} ".
       "for those in categories <i>$cat_names</i>:</h3>\n";
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

  $sql .= " ORDER BY month(Birthdate), dayofmonth(Birthdate)";
  $result = sqlquery_checked($sql);
  echo "<form action=\"multiselect.php\" method=\"GET\" target=\"_top\">\n";
  echo "<p style=\"text-align:center\"><input type=\"submit\" value=\"Go to Multi-Select\"></p>";

  // Create table
  echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\"><thead><tr><th>Name</th><th>Photo</th>";
  echo "<th>Birthdate</th><th>Age after<br>Birthday</th></tr>\n</thead><tbody>\n";
  $pid_list = '';
  while ($row = mysqli_fetch_object($result)) {
    echo "<tr><td nowrap><a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name($row->FullName, $row->Furigana)."</a></td><td align=\"center\">";
    echo ($row->Photo == 1) ? "<img border=\"0\" src=\"photos/p".$row->PersonID.".jpg\" width=\"50\">" : "&nbsp;";
    echo "</td>\n";
    if (substr($row->Birthdate,0,4) == "1900") {  // Year born is not known
      echo "<td align=\"center\">????" . substr($row->Birthdate,4) . "</td><td>&nbsp;</td></tr>\n";
    } else {
      $ba = explode("-",$row->Birthdate);
      $ta = explode("-",date("Y-m-d",mktime(0, 0, 0, date("m")+4, date("d"),  date("Y"))));
      $age = $ta[0] - $ba[0];
      if (($ba[1] > $ta[1]) || (($ba[1] == $ta[1]) && ($ba[2] > $ta[2]))) --$age;
      echo "<td align=\"center\">" . $row->Birthdate . "</td><td align=\"center\">" . $age . "</td></tr>\n";
    }
    echo "</tr>\n";
    $pid_list .= $row->PersonID.",";
  }
  echo "</tbody></table>\n";
  echo "<input type=\"hidden\" name=\"pids\" value=\"".rtrim($pid_list,',')."\"></form>\n";

  exit;
}

// Normal page load - render the form
header1("Birthdays List");
?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
<?php
header2(1);

// Need dummy entry because arrays count from zero
$month_array = array("dummy","January","February","March","April","May","June","July",
 "August","September","October","November","December");
?>

<h1 class="title">Birthday List</h1>

<form id="bform" method="GET">
  <fieldset>
    <legend>Date Range</legend>
    <div style="margin-bottom:1em">
      <button type="button" id="all_year">All Year</button>
      <label style="margin-left:2em">
        <input type="checkbox" name="wholemonth" id="wholemonth">
        Whole Month(s)
      </label>
    </div>

    <div style="display:grid; grid-template-columns: auto 150px 100px; gap:0.5em; align-items:center; max-width:400px">
      <div></div>
      <div style="font-weight:bold">Month</div>
      <div style="font-weight:bold">Day</div>

      <div style="font-weight:bold">From:</div>
      <div>
        <select id="startmonth" name="startmonth" style="width:100%">
<?php
// Create list of months, selecting the current month
$today_array = explode("-",date("Y-m-d",mktime(gmdate("H")+9)));
for ($index=1; $index<13; $index++) {
  echo "          <option value=\"" . $index."\"";
  if ($index == $today_array[1])  echo " selected";
  echo ">" . $month_array[$index] . "</option>\n";
}
?>
        </select>
      </div>
      <div>
        <select id="startday" name="startday" style="width:100%">
<?php
for ($i=1; $i<32; $i++) {
  echo "          <option value=\"$i\"";
  if ($i == 1)  echo " selected";
  echo ">{$i}</option>\n";
}
?>
        </select>
      </div>

      <div style="font-weight:bold">Until:</div>
      <div>
        <select id="endmonth" name="endmonth" style="width:100%">
<?php
for ($index=1; $index<13; $index++) {
  echo "          <option value=\"" . $index."\"";
  if ($index == $today_array[1])  echo " selected";
  echo ">" . $month_array[$index] . "</option>\n";
}
?>
        </select>
      </div>
      <div>
        <select id="endday" name="endday" style="width:100%">
<?php
for ($i=1; $i<32; $i++) {
  echo "          <option value=\"$i\"";
  if ($i == 31)  echo " selected";
  echo ">{$i}</option>\n";
}
?>
        </select>
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>Optional Filters</legend>
    <div style="margin-bottom:0.5em">
      <label>To restrict to certain category(s), select them in the list below.</label>
      <div style="font-size:0.9em;color:#666">Use the Ctrl key while clicking to select more than one.</div>
    </div>
    <select id="cat" name="cat" multiple size="6" style="width:100%;max-width:400px">
<?php
$result = sqlquery_checked("SELECT * FROM category ORDER BY Category");
while ($row = mysqli_fetch_object($result)) {
  echo "      <option value=" . $row->CategoryID . ">" . $row->Category . "</option>\n";
}
?>
    </select>
<?php if (!empty($_SESSION['basket'])) { ?>
    <div style="margin-top:0.5em">
      <label>
        <input type="checkbox" name="basket" value="1">
        <?=sprintf(_("Limit to Basket (%d)"), count($_SESSION['basket']))?>
      </label>
    </div>
<?php } ?>
  </fieldset>

  <div style="margin-top:1em">
    <button type="submit" id="list_birthdays">List Birthdays</button>
  </div>

  <input type="hidden" id="catlist" name="catlist" value="">
  <input type="hidden" name="query" value="1">
</form>

<div id="ResultFrame"></div>

<?php
load_scripts(['jquery', 'jqueryui']);
?>
<script type="text/javascript">
var max_days = new Array(31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

function monthlength(which, index) {
  var obj = (which=="start") ? $('#startday')[0] : $('#endday')[0];

  if (obj.options.length > max_days[index]) {
    if (obj.selectedIndex > max_days[index] - 1) {
      obj.selectedIndex = max_days[index] - 1;
    }
    obj.options.length = max_days[index];
  } else if (obj.options.length < max_days[index]) {
    for (var step=obj.options.length; step < max_days[index]; step++) {
      obj.options[step] = new Option(step+1,step+1);
    }
    if ($('#wholemonth').is(':checked')) {
      obj.selectedIndex = max_days[index] - 1;
    }
  }
}

function whole_month() {
  if ($('#wholemonth').is(':checked')) {
    $('#startday')[0].selectedIndex = 0;
    $('#startday').prop('disabled', true);
    $('#endday')[0].selectedIndex = $('#endday')[0].length-1;
    $('#endday').prop('disabled', true);
  } else {
    $('#startday').prop('disabled', false);
    $('#endday').prop('disabled', false);
  }
}

function all_year() {
  $('#startmonth')[0].selectedIndex = 0;
  monthlength("start",0);
  $('#startday')[0].selectedIndex = 0;
  $('#endmonth')[0].selectedIndex = 11;
  monthlength("end",11);
  $('#endday')[0].selectedIndex = 30;
}

function make_catlist() {
  $('#catlist').val('');
  var cats = [];
  $('#cat option:selected').each(function() {
    cats.push($(this).val());
  });
  $('#catlist').val(cats.join(','));
}

$(document).ready(function() {
  monthlength("start", $('#startmonth')[0].selectedIndex);
  monthlength("end", $('#endmonth')[0].selectedIndex);

  $('#startmonth').change(function() {
    monthlength('start', this.selectedIndex);
  });

  $('#endmonth').change(function() {
    monthlength('end', this.selectedIndex);
  });

  $('#wholemonth').change(function() {
    whole_month();
  });

  $('#all_year').click(function() {
    all_year();
  });

  $('#bform').submit(function(e) {
    e.preventDefault();
    make_catlist();
    var formData = $(this).serialize();
    $('#ResultFrame').html('<p style="padding:1em;color:#888">Loading...</p>');
    $.get('birthday.php', formData, function(response) {
      $('#ResultFrame').html(response);
    });
  });
});
</script>
<?php
footer();
?>
