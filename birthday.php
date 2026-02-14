<?php
include("functions.php");
include("accesscontrol.php");

header1("Birthdays List");
?> <link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<?php
// Need dummy entry because arrays count from zero
$month_array = array("dummy","January","February","March","April","May","June","July",
 "August","September","October","November","December");
?>

<script type="text/JavaScript">
var max_days = new Array(31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

function monthlength(which, index) {
  if (which=="start") {
    obj = document.bform.startday;
  } else {
    obj = document.bform.endday;
  }
  if (obj.options.length > max_days[index]) {
    if (obj.selectedIndex > max_days[index] - 1) {
      obj.selectedIndex = max_days[index] - 1;
    }
    obj.options.length = max_days[index];
  } else if (obj.options.length < max_days[index]) {
    for (step=obj.options.length; step < max_days[index]; step++) {
      obj.options[step] = new Option(step+1,step+1);
    }
    if (document.bform.wholemonth.checked) {
      obj.selectedIndex = max_days[index] - 1;
    }
  }
}

function whole_month() {
  if (document.bform.wholemonth.checked) {
     document.bform.startday.selectedIndex = 0;
     document.bform.startday.disabled = true;
     document.bform.endday.selectedIndex = document.bform.endday.length-1;
     document.bform.endday.disabled = true;
  } else {
     document.bform.startday.disabled = false;
     document.bform.endday.disabled = false;
  }
}

function all_year() {
  document.bform.startmonth.selectedIndex = 0;
  monthlength("start",0);
  document.bform.startday.selectedIndex = 0;
  document.bform.endmonth.selectedIndex = 11;
  monthlength("end",11);
  document.bform.endday.selectedIndex = 30;
}

function make_catlist() {
  document.bform.catlist.value = "";
  for (var i = 0; i < document.bform.cat.length; i++) {
    if (document.bform.cat.options[i].selected) {
      if (document.bform.catlist.value == "") {
        document.bform.catlist.value = document.bform.cat.options[i].value;
      } else {
        document.bform.catlist.value = document.bform.catlist.value + ","+document.bform.cat.options[i].value;
      }
    }
  }
}

window.onload = function() {
  monthlength("start", document.bform.startmonth.selectedIndex);
  monthlength("end", document.bform.endmonth.selectedIndex);
}

</script>
<?php header2(1); ?>
  <h1 class="title">Birthday List</h1>
  <table border=0 width=100%><tr><td align=center width=30%>
  <form name="bform" action="birthday_list.php" target="ResultFrame" method="GET" onSubmit="make_catlist();">
<?php
// Create list of months, selecting the current month
$today_array = explode("-",date("Y-m-d",mktime(gmdate("H")+9)));
$option_text = '';
for ($index=1; $index<13; $index++) {
  $option_text .= "      <option value=\"" . $index."\"";
  if ($index == $today_array[1])  $option_text .= " selected";
  $option_text .= ">" . $month_array[$index] . "</option>\n";
}
$option_text .= "    </select>";

echo "<table border=0 cellspacing=0 cellpadding=2>\n";
echo "  <tr><td align=center colspan=2><input type=button onclick=\"all_year();\" name=allyear value=\"All Year\"></td>";
echo "<td nowrap><input type=checkbox name=wholemonth ".
   "onchange=\"whole_month();\">Whole Month(s)</td></tr>\n";
echo "  <tr><td></td><td align=center><b>Month</b></td><td><b>&nbsp;Day</b></td></tr>\n";
echo "  <tr><td><b>From:</b></td>\n";
echo "    <td><select size=\"1\" name=\"startmonth\"\" ".
   "onchange=\"monthlength('start',this.selectedIndex);\">{$option_text}</td>\n";
echo "    <td><select size=\"1\" name=\"startday\">\n";
for ($i=1; $i<32; $i++) {
  echo "      <option value=\"$i\"";
  if ($i == 1)  echo " selected";
  echo ">{$i}</option>\n";
}
echo "</select></td></tr>\n";
echo "  <tr><td><b>Until:</b></td>\n";
echo "    <td><select size=\"1\" name=\"endmonth\" ".
   "onchange=\"monthlength('end',this.selectedIndex);\">{$option_text}</td>\n";
echo "    <td><select size=\"1\" name=\"endday\">\n";
for ($i=1; $i<32; $i++) {
  echo "      <option value=\"$i\"";
  if ($i == 31)  echo " selected";
  echo ">{$i}</option>\n";
}
echo "
</select></td></tr>\n";
echo "</table>\n";
echo "<hr>\n";

// Create selection box for selecting a specific category if desired
$result = sqlquery_checked("SELECT * FROM category ORDER BY Category");
echo "Optional: To restrict to certain category(s), select them in the list below.";
echo " &nbsp;Use the Ctrl key while clicking to select more than one.<br>\n";
echo "    <select size=6 name=cat multiple>\n";
while ($row = mysqli_fetch_object($result)) {
  echo "      <option value=" . $row->CategoryID . ">" . $row->Category . "</option>\n";
}
?>
    </select><hr>
<?php if (!empty($_SESSION['basket'])) { ?>
    <label class="label-n-input"><input type="checkbox" name="basket" value="1"><?=sprintf(_("Limit to Basket (%d)"), count($_SESSION['basket']))?></label><br>
<?php } ?>
    <input type="submit" name="make_chart" value="List Birthdays">
    <input type="hidden" name="catlist" value="">
  </form>
  </td><td align=center>
  <iframe name="ResultFrame" width="100%" height="400" src="blank.php"></iframe>
</td></tr></table>

<?php
load_scripts(['jquery', 'jqueryui']);
footer();
?>
