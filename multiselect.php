<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Multiple Selection/Action"));
?>
<link rel="stylesheet" href="style.php" type="text/css" />
<style>
div.buttongroup { border:1px solid gray; margin:6px 0 0 0; padding:0 3px 3px 3px; }
div.buttongroup h3 { margin:0; }
</style>
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/javascript" language="JavaScript">
$(document).ready(function(){
});

//indexes for arrays (just to keep sane)
var pid = 0;
var name = 1;
var cat = 2;
var choice = 3;
var sel = 4;

<?
//get data from person and percat tables and build master array

$sql = "SELECT PersonID, FullName, Furigana FROM person ORDER BY Furigana, PersonID";
if (!$person = mysql_query($sql)) {
  exit("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b> ($sql)");
}
$sql = "SELECT percat.PersonID, CategoryID, Furigana FROM percat, person".
" WHERE percat.PersonID = person.PersonID ORDER BY Furigana, percat.PersonID, CategoryID";
if (!$percat = mysql_query($sql)) {
  exit("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b> ($sql)");
}

echo "var ar = new Array();\n";
$ar_index = 0;
$presel_html = "";
$presel_num = 0;

$pc = mysql_fetch_object($percat);  //pull first one to get started
while ($per = mysql_fetch_object($person)) {
  echo "ar[$ar_index] = new Array();\n";
  echo "ar[$ar_index][pid] = \"$per->PersonID\";\n";
  echo "ar[$ar_index][name] = \"".d2h(readable_name($per->FullName,$per->Furigana))."\";\n";
  $str = ",";
  while ($pc && ($pc->PersonID == $per->PersonID)) {
    $str = $str.$pc->CategoryID.",";
    $pc = mysql_fetch_object($percat);
  }
  echo "ar[$ar_index][cat] = \"$str\";\n";
  echo "ar[$ar_index][choice] = 0;\n";
  if (ereg(",".$per->PersonID.",",",".$_REQUEST['preselected'].",")) {
    $presel_html .= "<option value=$ar_index>".d2h(readable_name($per->FullName,$per->Furigana))."</option>\n";
    $presel_num++;
    echo "ar[$ar_index][sel] = 1;\n";
  } else {
    echo "ar[$ar_index][sel] = 0;\n";
  }
  $ar_index++;
}
?>

var cat_regexp = new RegExp(",0,");

function new_cat() {
  empty(document.cform.choices);
  var catid = document.cform.catlist.options[document.cform.catlist.selectedIndex].value;
  cat_regexp.compile(","+catid+",")
  var list_index = 0;
  for (var array_index = 0; array_index < ar.length; array_index++) {
    if ((catid == "all") || (cat_regexp.test(ar[array_index][cat]))) {
      ar[array_index][choice] = 1;
      if (!ar[array_index][sel]) {
        document.cform.choices.options[list_index] = new Option(ar[array_index][name], array_index);
        list_index++;
      }
    } else {
      ar[array_index][choice] = 0;
    }
  }
}

function move(add_flag, all_flag) {
//add_flag is 1 for add, 0 for remove
//all_flag is 1 if double arrow was clicked
  from = (add_flag ? document.cform.choices : document.cform.selection);
//update array to show new status for for selected items
  for(var i = 0; i < from.length; i++) {
    if(all_flag || from.options[i].selected) {
      ar[from.options[i].value][sel] = add_flag;
    }
  }
  empty(document.cform.choices);
  empty(document.cform.selection);
  var n_index = 0;
  var s_index = 0;
  for (var array_index = 0; array_index < ar.length; array_index++) {
    if (ar[array_index][sel]) {
      document.cform.selection.options[s_index] = new Option(ar[array_index][name], array_index);
      s_index++;
    } else if (ar[array_index][choice]) {
      document.cform.choices.options[n_index] = new Option(ar[array_index][name], array_index);
      n_index++;
    }
  }
  $('#selection_count').text($('#selection option').size());
  parent.ActionFrame.location.href="blank.html";
}

function empty(list) {
   for (var i = list.length-1; i >= 0; i--) {
     list.options[i] = null;
   }
}

function make_list() {
  document.sform.pid_list.value = "";
  for (var array_index = 0; array_index < ar.length; array_index++) {
    if (ar[array_index][sel]) {
      if (document.sform.pid_list.value == "") {
        document.sform.pid_list.value = ar[array_index][pid];
      } else {
        document.sform.pid_list.value = document.sform.pid_list.value + "," + ar[array_index][pid];
      }
    }
  }
}
</script>

<? header2(1); ?>

  <table border="0" cellspacing="0" cellpadding="8">
    <tr>
      <td valign="top">
        <form action="(EmptyReference!)" method="get" name="cform">
          <table width="187" border="0" cellspacing="0" cellpadding="4" bgcolor="white">
            <tr height="35">
              <td colspan="3" height="35"><select name="catlist" size="1" onchange="new_cat();">
                <option value=""><? echo _("Choose a category..."); ?></option>
<?
//get category list from database and fill rest of select box
$sql = "SELECT * FROM category ORDER BY Category";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b> ($sql)");
  exit;
}
while ($cat = mysql_fetch_object($result)) {
  echo "                <option value=\"$cat->CategoryID\">$cat->Category</option>\n";
}
?>
                <option value="all"><? echo _("All Categories"); ?></option>
                </select><br /><font size=2 color=red><? echo _("Highlight and press > or
                select all with >>"); ?></font></td>
            </tr>
            <tr height="35">
              <td height="35"><select style="width:220px;" name="choices" size="15" multiple></select></td>
              <td align="center" height="35">
                <img onclick="move(1,0);" src="graphics/right.gif" alt="add selected people" name="add" border="0"><br>
                <img onclick="move(1,1);" src="graphics/right_double.gif" alt="add all people" name="add_all" border="0"><br>
                <img onclick="move(0,0);" src="graphics/left.gif" alt="remove selected people" name="remove" border="0"><br>
                <img onclick="move(0,1);" src="graphics/left_double.gif" alt="remove all people" name="remove_all" border="0">
              </td>
              <td align="center" height="35">
                <select style="width:220px;" id="selection" name="selection" size="15" multiple><? echo $presel_html; ?></select>
              </td>
            </tr>
          </table>
        </form>
      </td>
      <td valign="top">
        <form action="blank.html" method="post" name="sform" onsubmit="make_list();" target="ActionFrame">
          <input type="hidden" name="pid_list" value="" border="0" />
          <b><? echo sprintf(_("%s Entries Selected"),"<span id=\"selection_count\">$presel_num</span>"); ?></b><br />
          <b><? echo _("Choose an Action:"); ?></b><br />
          <div class="buttongroup">
            <h3><?=_("Batch Data Entry")?></h3>
            <input type="submit" name="ms_attendance" value="<? echo _("Record Attendance"); ?>" border="0"
                onclick="document.sform.action='ms_attendance.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_contacts" value="<? echo _("Add a Contact for All"); ?>" border="0"
                onclick="document.sform.action='ms_contact.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_category" value="<? echo _("Add All to a Category"); ?>" border="0"
                onclick="document.sform.action='ms_category.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_organization" value="<? echo _("Connect All to an Organization"); ?>" border="0"
                onclick="document.sform.action='ms_organization.php';document.sform.target='ActionFrame';">
          </div>
          <div class="buttongroup">
            <h3><?=_("Reports")?></h3>
            <input type="submit" name="ms_person_text" value="<? echo _("Person Info (Text)"); ?>"
                border="0" onclick="document.sform.action='ms_person_text.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_custom" value="<? echo _("Custom Report"); ?>"
                border="0" onclick="document.sform.action='ms_custom.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_person_xml" value="<? echo _("Person Info (XML)"); ?>"
                border="0" onclick="document.sform.action='ms_person_xml.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_person_format" value="<? echo _("Person Info (Formatted)"); ?>"
                border="0" onclick="document.sform.action='ms_person_format.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_household_text" value="<? echo _("Household Info (Text)"); ?>"
                border="0" onclick="document.sform.action='ms_household_text.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_household_format" value="<? echo _("Household Info (Formatted)"); ?>"
                border="0" onclick="document.sform.action='ms_household_format.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_overview" value="<? echo _("Overview Pages"); ?>"
                border="0" onclick="document.sform.action='ms_overview.php';document.sform.target='ActionFrame';">
          </div>
          <div class="buttongroup">
            <h3><?=_("Pre-Filtering Search Pages")?></h3>
            <input type="submit" name="ms_blank" value="<? echo _("Pre-Filter Main Search"); ?>"
                border="0" onclick="document.sform.action='search.php';document.sform.target='_self';">
            <input type="submit" name="ms_blank" value="<? echo _("Pre-Filter Attendance Chart"); ?>"
                border="0" onclick="document.sform.action='event_attend.php';document.sform.target='_blank';">
            <input type="submit" name="ms_blank" value="<? echo _("Pre-Filter Donation/Pledge Reports"); ?>"
                border="0" onclick="document.sform.action='donations.php';document.sform.target='_blank';">
          </div>
          <div class="buttongroup">
            <h3><?=_("Specialized Output")?></h3>
            <input type="submit" name="ms_printaddr" value="<? echo _("Print Labels"); ?>" border="0"
                onclick="document.sform.action='ms_label.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_printaddr" value="<? echo _("Print Envelopes/Postcards"); ?>" border="0"
                onclick="document.sform.action='ms_printaddr.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_photos" value="<? echo _("Print Photos"); ?>" border="0"
                onclick="document.sform.action='ms_photos.php';document.sform.target='ActionFrame';">
            <input type="submit" name="ms_email" value="<? echo _("Prepare Email"); ?>" border="0"
                onclick="document.sform.action='ms_email.php';document.sform.target='ActionFrame';">
          </div>
        </form>
      </td>
    </tr>
  </table>
<iframe name="ActionFrame" style="width:100%;height:400px" src="blank.html">
</iframe>
<? footer(); ?>