<?php
include("functions.php");
include("accesscontrol.php");

$text = "<ul id=\"criteria\">";
$sql = "SELECT ".($_REQUEST['countonly'] ?
  "person.PersonID " :
  "person.*, household.AddressComp, household.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories ");
$sql .= "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
    "LEFT JOIN percat ON person.PersonID=percat.PersonID ".
    "LEFT JOIN category ON percat.CategoryID=category.CategoryID";
$wheredone = 0;
$ptable = $grouptable = "person";

if ($_REQUEST['filter'] == "Organizations") {
  $sql .= " WHERE Organization>0";
  $text .= "<li>"._("Organizations only");
  $wheredone = 1;
  $closing = "";
} elseif ($_REQUEST['filter'] == "People") {
  $sql .= " WHERE Organization=0";
  $text .= "<li>"._("People only (no organizations)");
  $wheredone = 1;
  $closing = "";
} elseif ($_REQUEST['filter'] == "OrgsOfPeople") {
    $sql = "SELECT DISTINCT p1.*, h1.AddressComp, h1.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories ".
    "FROM person p1 LEFT JOIN household h1 ON p1.HouseholdID=h1.HouseholdID ".
    "LEFT JOIN percat ON p1.PersonID=percat.PersonID ".
    "LEFT JOIN category ON percat.CategoryID=category.CategoryID ".
    "WHERE p1.PersonID IN (SELECT OrgID FROM perorg po ".
    "INNER JOIN person p2 ON po.PersonID=p2.PersonID ".
    "LEFT JOIN household ON p2.HouseholdID=household.HouseholdID";
  $text .= "<li>"._("Organizations with members who have the following criteria...");
  $wheredone = 0;
  $ptable = "p2";
  $grouptable = "p1";
  $closing = ")";
} elseif ($_REQUEST['filter'] == "PeopleOfOrgs") {
    $sql = "SELECT DISTINCT p1.*, h1.AddressComp, h1.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories ".
    "FROM person p1 LEFT JOIN household h1 ON p1.HouseholdID=h1.HouseholdID ".
    "LEFT JOIN percat ON p1.PersonID=percat.PersonID ".
    "LEFT JOIN category ON percat.CategoryID=category.CategoryID ".
    "WHERE p1.PersonID IN (SELECT po.PersonID FROM perorg po ".
    "INNER JOIN person o ON po.OrgID=o.PersonID ".
    "LEFT JOIN household ON o.HouseholdID=household.HouseholdID";
  $text .= "<li>"._("People whose related organizations have the following criteria...");
  $wheredone = 0;
  $ptable = "o";
  $grouptable = "p1";
  $closing = ")";
}
for ($i=1; isset($_REQUEST["textinput".$i]); $i++) {
$test="<div style=\"border: 2px solid darkgreen;background-color:#e0ffe0;color:darkgreen;padding-left:5px;margin-right:30px\">";
$test.="Text input #".$i." = '".$_REQUEST["textinput".$i]."'";
$test.="<br>Text target #".$i." = '".$_REQUEST["texttarget".$i]."'";
$test.="<br>Text in/out #".$i." = '".$_REQUEST["textinout".$i]."'</div>";
  if ($_REQUEST["textinput".$i] != "") {
    $search = mb_ereg_replace("%","\%",h2d($_REQUEST["textinput".$i]));
    $target = $_REQUEST["texttarget".$i];
    $not = ($_REQUEST["textinout".$i]=="OUT") ? " NOT" : "";
    $sql .= ($wheredone?" AND":" WHERE");
    $wheredone = 1;
    $in = ($not=="") ? _("in") : _("not in");
    switch($target) {
    case "Name":
      $sql .= "$not ($ptable.FullName LIKE '%".$search."%' OR $ptable.Furigana LIKE '%".$search."%' OR LabelName LIKE '%".$search."%')";
      if ($_SESSION['furiganaisromaji']) {
        $text .= "<li>".sprintf(_("\"%s\" $in Name, Romaji, or Label"), $search)."</li>\n";
      } else {
        $text .= "<li>".sprintf(_("\"%s\" $in Name, Furigana, or Label"), $search)."</li>\n";
      }
      break;
    case "Address":
      $sql .= "$not household.AddressComp LIKE '%".$search."%' "
      .($_SESSION['romajiaddresses']=="yes" ? "OR household.RomajiAddressComp LIKE '%".$search."%'" : "");
      $text .= "<li>".sprintf(_("\"%s\" $in Address"), $search)."</li>\n";
      break;
    case "Phone":
      $sql .= "$not (household.Phone LIKE '%".$search."%' OR $ptable.CellPhone LIKE '%".$search."%' OR FAX LIKE '%".$search."%')";
      $text .= "<li>".sprintf(_("\"%s\" $in Phone or FAX"), $search)."</li>\n";
      break;
    case "PersonID":
      $sql .= "$not ($ptable.PersonID = ".$search.")";
      $text .= "<li>".sprintf(_("\"%s\" $in Phone or FAX"), $search)."</li>\n";
      break;
    default:
      $sql .= "$not ($ptable.$target LIKE '%".$search."%')";
      $text .= "<li>".sprintf(_("\"%s\" $in %s"), $search, _($target))."</li>\n";
    }
  }
}

for ($i=1; isset($_REQUEST["catselect".$i]); $i++) {
  $cats = join(",",$_REQUEST["catselect".$i]);
  $not = ($_REQUEST["catinout".$i]=="OUT") ? " NOT" : "";
  $sql .= ($wheredone?" AND":" WHERE")."$not ($ptable.PersonID IN (SELECT PersonID FROM percat WHERE CategoryID IN ($cats)))";
  $wheredone = 1;
  $result = sqlquery_checked("SELECT Category FROM category WHERE CategoryID IN ($cats) ORDER BY Category");
  $catnames = "";
  while ($row = mysql_fetch_object($result)) {
    $catnames .= d2h($row->Category).", ";
  }
  if ($not) {
    $text .= "<li>".sprintf(_("In none of these categories: %s"), mb_substr($catnames,0,mb_strlen($catnames)-2))."</li>\n";
  } else {
    $text .= "<li>".sprintf(_("In at least one of these categories: %s"), mb_substr($catnames,0,mb_strlen($catnames)-2))."</li>\n";
  }
}

for ($i=1; isset($_REQUEST["ctselect".$i]); $i++) {
  $cts = join(",",$_REQUEST["ctselect".$i]);
  $not = ($_REQUEST["contactinout".$i]=="OUT") ? " NOT" : "";
  $sql .= ($wheredone?" AND":" WHERE")."$not ($ptable.PersonID IN (SELECT PersonID FROM contact WHERE ContactTypeID IN ($cts)";
  if ($_REQUEST["ctstartdate".$i]) $sql .= " AND ContactDate >= '".$_REQUEST["ctstartdate".$i]."'";
  if ($_REQUEST["ctenddate".$i]) $sql .= " AND ContactDate <= '".$_REQUEST["ctenddate".$i]."'";
  $sql .= "))";
  $wheredone = 1;
  $result = sqlquery_checked("SELECT ContactType FROM contacttype WHERE ContactTypeID IN ($cts) ORDER BY ContactType");
  $ctnames = "";
  while ($row = mysql_fetch_object($result)) {
    $ctnames .= d2h($row->ContactType).", ";
  }
  if ($not) {
    $text .= "<li>".sprintf(_("Has none of these types of contacts: %s"), mb_substr($ctnames,0,mb_strlen($ctnames)-2));
  } else {
    $text .= "<li>".sprintf(_("Has at least one of these types of contacts: %s"), mb_substr($ctnames,0,mb_strlen($ctnames)-2));
  }
  if ($_REQUEST["ctstartdate".$i] && $_REQUEST["ctenddate".$i]) $text .= sprintf(_(", between %s and %s"),$_REQUEST["ctstartdate".$i],$_REQUEST["ctenddate".$i]);
  elseif ($_REQUEST["ctstartdate".$i]) $text .= sprintf(_(", on or after %s"),$_REQUEST["ctstartdate".$i]);
  elseif ($_REQUEST["ctenddate".$i]) $text .= sprintf(_(", on or before %s"),$_REQUEST["ctenddate".$i]);
  $text .= "</li>\n";
}

for ($i=1; isset($_REQUEST["dtselect".$i]); $i++) {
  $dts = join(",",$_REQUEST["dtselect".$i]);
  $not = ($_REQUEST["donationinout".$i]=="OUT") ? " NOT" : "";
  $sql .= ($wheredone?" AND":" WHERE")."$not ($ptable.PersonID IN (SELECT PersonID FROM donation WHERE DonationTypeID IN ($dts)";
  if ($_REQUEST["dtstartdate".$i]) $sql .= " AND DonationDate >= '".$_REQUEST["dtstartdate".$i]."'";
  if ($_REQUEST["dtenddate".$i]) $sql .= " AND DonationDate <= '".$_REQUEST["dtenddate".$i]."'";
  $sql .= "))";
  $wheredone = 1;
  $result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN ($dts) ORDER BY DonationType");
  $dtnames = "";
  while ($row = mysql_fetch_object($result)) {
    $ctnames .= d2h($row->DonationType).", ";
  }
  if ($not) {
    $text .= "<li>".sprintf(_("Has not donated any of these donation types: %s"), mb_substr($ctnames,0,mb_strlen($dtnames)-2));
  } else {
    $text .= "<li>".sprintf(_("Has donated at least one of these donation types: %s"), mb_substr($ctnames,0,mb_strlen($dtnames)-2));
  }
  if ($_REQUEST["dtstartdate".$i] && $_REQUEST["dtenddate".$i]) $text .= sprintf(_(", between %s and %s"),$_REQUEST["dtstartdate".$i],$_REQUEST["dtenddate".$i]);
  elseif ($_REQUEST["dtstartdate".$i]) $text .= sprintf(_(", on or after %s"),$_REQUEST["dtstartdate".$i]);
  elseif ($_REQUEST["dtenddate".$i]) $text .= sprintf(_(", on or before %s"),$_REQUEST["dtenddate".$i]);
  $text .= "</li>\n";
}

for ($i=1; isset($_REQUEST["eventselect".$i]); $i++) {
  $events = implode(",",$_REQUEST['eventselect'.$i]);
  $not = ($_REQUEST["attendinout".$i]=="OUT") ? " NOT" : "";
  $sql .= ($wheredone?" AND":" WHERE")."$not ($ptable.PersonID IN (SELECT PersonID FROM attendance WHERE EventID IN ($events)";
  if ($_REQUEST["astartdate".$i]) $sql .= " AND AttendDate >= '".$_REQUEST["astartdate".$i]."'";
  if ($_REQUEST["aenddate".$i]) $sql .= " AND AttendDate <= '".$_REQUEST["aenddate".$i]."'";
  $sql .= "))";
  $wheredone = 1;
  $result = sqlquery_checked("SELECT Event FROM event WHERE EventID IN ($events) ORDER BY Event");
  $eventnames = "";
  while ($row = mysql_fetch_object($result)) {
    $eventnames .= d2h($row->Event).", ";
  }
  if ($not) {
    $text .= "<li>".sprintf(_("Has not attended any of these events: %s"), mb_substr($eventnames,0,mb_strlen($eventnames)-2));
  } else {
    $text .= "<li>".sprintf(_("Has attended one or more of these events: %s"), mb_substr($eventnames,0,mb_strlen($eventnames)-2));
  }
  if ($_REQUEST["astartdate".$i] && $_REQUEST["aenddate".$i]) $text .= sprintf(_(", between %s and %s"),$_REQUEST["astartdate".$i],$_REQUEST["aenddate".$i]);
  elseif ($_REQUEST["astartdate".$i]) $text .= sprintf(_(", on or after %s"),$_REQUEST["astartdate".$i]);
  elseif ($_REQUEST["aenddate".$i]) $text .= sprintf(_(", on or before %s"),$_REQUEST["aenddate".$i]);
  $text .= "</li>\n";
}

for ($i=1; isset($_REQUEST["blanktarget".$i]); $i++) {
  if ($_REQUEST["blanktarget".$i] != "") {
    $target = $_REQUEST["blanktarget".$i];
    $not = ($_REQUEST["blankinout".$i]=="OUT") ? " NOT" : "";
    $sql .= ($wheredone?" AND":" WHERE");
    $wheredone = 1;
    switch($target) {
    case "Birthdate":
      $sql .= "$not ($ptable.$target IS NULL OR $ptable.$target='0000-00-00')";
      break;
    case "Address":
    case "LabelName":
    case "Phone":
    case "FAX":
      $sql .= "$not ($target = '')";
      break;
    default:
      $sql .= "$not ($ptable.$target = '')";
    }
    if ($not) {
      $text .= "<li>".sprintf(_("\"%s\" is not blank"), _($target))."</li>\n";
    } else {
      $text .= "<li>".sprintf(_("\"%s\" is blank"), _($target))."</li>\n";
    }
  }
}

if ($_REQUEST['freesql'] != "") {
  $sql .= ($wheredone?" AND ":" WHERE ").$_REQUEST['freesql'];
  $wheredone = 1;
  $text .= "<li>".$_REQUEST['freesql']."</li>\n";
}

if ($_POST['preselected']) {
  $sql .= " AND $grouptable.PersonID IN (".$_POST['preselected'].")";
  $text .= "<li>".($_POST['preselected']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : "")."</li>\n";
}

$sql .= $closing . " GROUP BY $grouptable.PersonID ORDER BY Furigana";
$text .= "</ul>";

if (!$result = mysql_query($sql)) {
  header1(_("Error"));
  header2(1);
  echo $test;
  echo $text;
  echo "<div style=\"border: 2px solid darkred;background-color:#ffe0e0;color:darkred;padding-left:5px\">$sql</div>";
  echo "<div style=\"font-weight:bold;margin:10px 0\">The query had an error:<br>".mysql_errno().": ".mysql_error()."</div>";
  exit;
}

//$result = sqlquery_checked($sql);
if (mysql_num_rows($result) == 0) {
  header("Location: search.php?text=".urlencode(_("Search resulted in no records.".($_SESSION['userid']=="karen"?urlencode("<pre>".$sql."</pre>"):""))));
  exit;
} elseif (mysql_num_rows($result) == 1) {
  $person = mysql_fetch_object($result);
  header("Location: individual.php?pid=".$person->PersonID);
  exit;
}
header1(_("Search Results").($_POST['preselected']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : ""));

$cols[] = array("personid",1,"digit");
$cols[] = array("name-for-csv",0,"text");
$cols[] = array("name-for-display",0,"text");
$cols[] = array("photo",1,"text");
$cols[] = array("phone",1,"text");
$cols[] = array("email",1,"text");
$cols[] = array("address",1,"text");
$cols[] = array("birthdate",1,"isoDate");
$cols[] = array("age",1,"digit");
$cols[] = array("sex",1,"text");
$cols[] = array("country",1,"text");
$cols[] = array("url",1,"url");
$cols[] = array("remarks",1,"text");
$cols[] = array("categories",1,"text");
$cols[] = array("selectcol",0,"");
$colsHidden = $hideInList = "";
foreach($cols as $i=>$col) {
  if ($col[1]==0) $hideInList .= ",".($i+1);
  elseif (stripos(",".$_SESSION['list_showcols'].",",",".$col[0].",") === FALSE)  $colsHidden .= ",".($i+1);
}
$hideInList = substr($hideInList,1);  //to remove the leading comma
$colsHidden = substr($colsHidden,1);  //to remove the leading comma

$tableheads = "<th class=\"personid\">"._("ID")."</th>";
$tableheads .= "<th class=\"name-for-csv\" style=\"display:none\">"._("Name")." (".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>";
$tableheads .= "<th class=\"name-for-display\">"._("Name")." (".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>";
$tableheads .= "<th class=\"photo\">"._("Photo")."</th>\n";
$tableheads .= "<th class=\"phone\">"._("Phone")."</th>\n";
$tableheads .= "<th class=\"email\">"._("Email")."</th>\n";
$tableheads .= "<th class=\"address\">"._("Address")."</th>\n";
$tableheads .= "<th class=\"birthdate\">"._("Born")."</th>\n";
$tableheads .= "<th class=\"age\">"._("Age")."</th>\n";
$tableheads .= "<th class=\"sex\">"._("Sex")."</th>\n";
$tableheads .= "<th class=\"country\">"._("Country")."</th>\n";
$tableheads .= "<th class=\"url\">"._("URL")."</th>\n";
$tableheads .= "<th class=\"remarks\">"._("Remarks")."</th>\n";
$tableheads .= "<th class=\"categories\">"._("Categories")."</th>\n";
$tableheads .= "<th id=\"thSelectColumn\" class=\"selectcol\">";
$tableheads .= "<ul id=\"ulSelectColumn\"><li><img src=\"graphics/selectcol.png\" alt=\"select columns\" ".
        "title=\"select columns\" /><ul id=\"targetall\"></ul></li></ul>";
$tableheads .= "</th>\n";
?>
<link rel="stylesheet" href="style.php?jquery=1&table=1" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#preselected").val($("#pids").text());
  
  $("#mainTable").tablesorter({
    sortList:[[2,0]],
    headers:{14:{sorter:false}}
  });
  $('#mainTable').columnManager({listTargetID:'targetall',
  onClass: 'advon',
  offClass: 'advoff',
  hideInList: [<? echo $hideInList; ?>],
  colsHidden: [<? echo $colsHidden; ?>],
  saveState: false});
  $('#ulSelectColumn').clickMenu({onClick: function(){}});
  
  $('#ulSelectColumn').click(function() {
    alert("fired");
    if (($("div.outerbox").offsetLeft + $("div.outerbox").offsetWidth) > document.body.clientWidth) {
      alert("too wide");
      window.scrollBy(1000,0);  //should be plenty
    }
  });
});

function getCSV() {
  $(".name-for-display, .selectcol").hide();
  $(".name-for-csv").show();
  $('#csvtext').val($('#mainTable').table2CSV({delivery:'value'}));
  $(".name-for-csv").hide();
  $(".name-for-display, .selectcol").show();
}
</script>
<?
header2(1);
echo "<h3>".sprintf(_("%d results of these criteria:"),mysql_num_rows($result))."</h3>\n";
echo $text;
?>
<div id="actions">
  <form action="multiselect.php" method="post" target="_top">
  <input type="hidden" id="preselected" name="preselected" value="">
  <input type="submit" value="<?=_("Go to Multi-Select with these entries preselected")?>">
  </form>
<? if (!$_REQUEST['countonly']) {  //can't do CSV if there is no table ?>
  <form action="download.php" method="post" target="_top">
  <input type="hidden" id="csvtext" name="csvtext" value="">
  <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
  </form>
<? } //end if not count only ?>
</div>
<?
if ($_REQUEST['countonly']) {  //if count only, just get pids for multi-select
  while ($row = mysql_fetch_object($result)) $pid_list .= ",".$row->PersonID;
} else {  //if not count only, build the whole table
  echo "<table id=\"mainTable\" class=\"tablesorter\"><thead>";
  echo "<tr>";
  echo $tableheads;
  echo "</tr></thead><tbody>\n";
  while ($row = mysql_fetch_object($result)) {
    $pid_list .= ",".$row->PersonID;
    echo "<tr>";
    echo "<td class=\"personid\">".$row->PersonID."</td>\n";
    echo "<td class=\"name-for-csv\" style=\"display:none\">".readable_name($row->FullName,$row->Furigana)."</td>";
    echo "<td class=\"name-for-display\" nowrap><span style=\"display:none\">".$row->Furigana."</span>";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\">".
      readable_name($row->FullName,$row->Furigana,0,0,"<br />")."</a></td>\n";
    echo "<td class=\"photo\">";
    echo ($row->Photo == 1) ? "<img border=0 src=\"photo.php?f=p".$row->PersonID."\" width=50>" : "";
    echo "</td>\n";
    if ($row->CellPhone && $row->Phone) {
      echo "<td class=\"phone\">".$row->Phone."<br>".$row->CellPhone."</td>\n";
    } else {
      echo "<td class=\"phone\">".$row->Phone."".$row->CellPhone."</td>\n";
    }
    echo "<td class=\"email\">".email2link($row->Email)."</td>\n";
    echo "<td class=\"address\">".d2h($row->AddressComp)."</td>\n";
    echo "<td class=\"birthdate\">".(($row->Birthdate!="0000-00-00") ? ((substr($row->Birthdate,0,4) == "1900") ? substr($row->Birthdate,5) : $row->Birthdate) : "")."</td>\n";
    echo "<td class=\"age\">".(($row->Birthdate!="0000-00-00") && (substr($row->Birthdate,0,4) != "1900") ? age($row->Birthdate) : "")."</td>\n";
    echo "<td class=\"sex\">".$row->Sex."</td>\n";
    echo "<td class=\"country\">".$row->Country."</td>\n";
    echo "<td class=\"url\">".$row->URL."</td>\n";
    echo "<td class=\"remarks\">".email2link(url2link(d2h($row->Remarks)))."</td>\n";
    echo "<td class=\"categories\">".d2h($row->categories)."</td>\n";
    echo "<td class=\"selectcol\">-</td>\n";
  }
  echo "</tr></tbody></table>\n";
}
echo $_SESSION['userid']=="karen"?"<pre class=\"noprint\">".$sql."</pre>":"";
echo "<div id=\"pids\" style=\"display:none\">".substr($pid_list,1)."</div>\n";
footer(1);
