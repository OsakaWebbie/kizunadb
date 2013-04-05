<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Contact List").($_POST['preselected']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),
    substr_count($_POST['preselected'],",")+1) : ""));

if ($_POST['listtype'] != "PersonID") {
  if ($_POST['listtype'] == "Normal") {
    $cols[] = array("personid",1);
    $cols[] = array("name-for-csv",0);
    $cols[] = array("furigana-for-csv",0);
  }
  $cols[] = array("name-for-display",0);
}
if ($_POST['listtype'] == "Normal") {
  $cols[] = array("phone",1);
  $cols[] = array("email",1);
  $cols[] = array("address",1);
  $cols[] = array("remarks",1);
}
$cols[] = array("cdate",1);
if ($_POST['listtype'] != "ContactType") $cols[] = array("ctype",1);
$cols[] = array("desc",1);
if ($_POST['listtype'] == "Normal") $cols[] = array("selectcol",0);
$colsHidden = $hideInList = "";
foreach($cols as $i=>$col) {
  if ($col[1]==0) $hideInList .= ",".($i+1);
  elseif (stripos(",".$_SESSION['contactlist_showcols'].",",",".$col[0].",") === FALSE)  $colsHidden .= ",".($i+1);
}
$hideInList = substr($hideInList,1);  //to remove the leading comma
$colsHidden = substr($colsHidden,1);  //to remove the leading comma

if ($_POST['listtype'] != "PersonID") {
  if ($_POST['listtype'] == "Normal") {
    $tableheads .= "<th class=\"personid\">"._("ID")."</th>\n";
    $tableheads .= "<th class=\"name-for-csv\" style=\"display:none\">"._("Name")."</th>\n";
    $tableheads .= "<th class=\"furigana-for-csv\" style=\"display:none\">".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana"))."</th>\n";
  }
  $tableheads .= "<th class=\"name-for-display\">"._("Name")." (".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>\n";
}
if ($_POST['listtype'] == "Normal") {
  $tableheads .= "<th class=\"phone\">"._("Phone")."</th>\n";
  $tableheads .= "<th class=\"email\">"._("Email")."</th>\n";
  $tableheads .= "<th class=\"address\">"._("Address")."</th>\n";
  $tableheads .= "<th class=\"remarks\">"._("Remarks")."</th>\n";
}
$tableheads .= "<th class=\"cdate\">"._("Date")."</th>\n";
if ($_POST['listtype'] != "ContactType") $tableheads .= "<th class=\"ctype\">"._("Contact Type")."</th>\n";
$tableheads .= "<th class=\"desc\">"._("Description")."</th>\n";
if ($_POST['listtype'] == "Normal") $tableheads .= "<th id=\"thSelectColumn\" class=\"selectcol\">".
    "<ul id=\"ulSelectColumn\"><li><img src=\"graphics/selectcol.png\" alt=\"select columns\" ".
    "title=\"select columns\" /><ul id=\"targetall\"></ul></li></ul>";
$tableheads .= "</th>\n";
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#ctable").tablesorter({
    sortList:[[3,0],[8,1]],
    headers:{<?=(count($cols)-1)?>:{sorter:false}}
  });
  $(".grouptable").tablesorter({ sortList:[[0,<?=($_POST['listtype']=="PersonID"?"1":"0],[1,1")?>]] });

  $('#ctable').columnManager({listTargetID:'targetall',
  onClass: 'advon',
  offClass: 'advoff',
  hideInList: [<?=$hideInList?>],
  colsHidden: [<?=$colsHidden?>],
  saveState: false});
  $('#ulSelectColumn').clickMenu({onClick: function(){}});
});

function getCSV() {
  $(".name-for-display, .selectcol").hide();
  $(".name-for-csv, .furigana-for-csv").show();
  $('#csvtext').val($('#ctable').table2CSV({delivery:'value'}));
  $(".name-for-csv, .furigana-for-csv").hide();
  $(".name-for-display, .selectcol").show();
}
</script>
<?
header2($_GET['nav']);
if ($_GET['nav']==1) echo "<h1 id=\"title\">"._("Contact List").($_POST['preselected']!="" ?
sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : "")."</h1>\n";

if ($_POST['ctype']) $where .= ($where?" AND":" WHERE")." c.ContactTypeID IN (".implode(",",$_POST['ctype']).")";
if ($_POST['startdate']) $where .= ($where?" AND":" WHERE")." ContactDate >= '".$_POST['startdate']."'";
if ($_POST['enddate']) $where .= ($where?" AND":" WHERE")." ContactDate <= '".$_POST['enddate']."'";
if ($_POST['csearch']) $where .= ($where?" AND":" WHERE")." Description LIKE '%".$_POST['csearch']."%'";
if ($_POST['preselected']) $where .= ($where?" AND":" WHERE")." c.PersonID IN (".$_POST['preselected'].")";

$sql = "SELECT DISTINCT PersonID FROM contact c".$where." ORDER BY PersonID";
$result = sqlquery_checked($sql);
if (mysql_numrows($result) == 0) {
  echo "<h3>"._("There are no records matching your criteria.")."</h3>";
  footer();
  exit;
}
$pidarray = array();
while ($row = mysql_fetch_object($result)) {
  $pidarray[] = $row->PersonID;
}
$pids = implode(",",$pidarray);

?>
<div id="actions">
  <form action="multiselect.php" method="post" target="_top">
    <input type="hidden" id="preselected" name="preselected" value="<?=$pids?>">
    <input type="submit" value="<? echo _("Go to Multi-Select with these entries preselected"); ?>">
  </form>
<? if ($_POST['listtype'] == "Normal") { ?>
  <form action="download.php" method="post" target="_top">
    <input type="hidden" id="csvtext" name="csvtext" value="">
    <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
  </form>
<? } // if listtype=Normal ?>
</div>
<?
if ($_POST['listtype'] == "Normal") {
  $tablestart = "<table id=\"ctable\" class=\"tablesorter\">\n";
} else {
  $tablestart = "<table class=\"grouptable tablesorter\">\n";
}
$tablestart .= "<thead>\n<tr>".$tableheads."</tr>\n</thead><tbody>\n";

$prev_groupfieldvalue = "";
$firstrow = 1; //i.e. true
$sql = "SELECT c.*,ct.ContactType,p.FullName,p.Furigana";
if ($_POST['listtype'] == "Normal") $sql .= ",p.Photo,p.CellPhone,p.Email,p.Remarks,h.*,pc.*";
$sql .= " FROM contact c LEFT JOIN person p ON p.PersonID=c.PersonID";
if ($_POST['listtype'] == "Normal") $sql .= " LEFT JOIN household h ON p.HouseholdID=h.HouseholdID".
" LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode";
$sql .= " LEFT JOIN contacttype ct ON c.ContactTypeID=ct.ContactTypeID".$where;
if ($_POST['listtype'] == "ContactType") {
  $sql .= " ORDER BY ContactType,Furigana,PersonID,ContactDate DESC";
} else {
  $sql .= " ORDER BY Furigana,PersonID,ContactDate DESC";
}
$result = sqlquery_checked($sql);
while ($row = mysql_fetch_object($result)) {
  if ($_POST['listtype']!="Normal" && $prev_groupfieldvalue!="" && $row->$_POST['listtype']!=$prev_groupfieldvalue) {  //change of section
    echo "</tbody></table>\n";
    echo "<h3>".($_POST['listtype']=="PersonID"?"<a href=\"individual.php?pid=".$row->PersonID.
    "\" target=\"_blank\">".readable_name($row->FullName,$row->Furigana):$row->$_POST['listtype'])."</a></h3>\n";
    echo $tablestart;
  } elseif ($_POST['listtype']!="Normal" && $prev_groupfieldvalue == "") {
    echo "<h3>".($_POST['listtype']=="PersonID"?"<a href=\"individual.php?pid=".$row->PersonID.
    "\" target=\"_blank\">".readable_name($row->FullName,$row->Furigana):$row->$_POST['listtype'])."</a></h3>\n";
  }
  if ($firstrow) {
    echo $tablestart;
    $firstrow = 0;
  }
  echo "<tr>";
  if ($_POST['listtype'] != "PersonID") {
    if ($_POST['listtype'] == "Normal") {
      echo "<td class=\"personid\">".$row->PersonID."</td>\n";
      echo "<td class=\"name-for-csv\" style=\"display:none\">".$row->FullName."</td>\n";
      echo "<td class=\"furigana-for-csv\" style=\"display:none\">".$row->Furigana."</td>\n";
    }
    echo "<td class=\"name-for-display\"><span style=\"display:none\">".$row->Furigana."</span>";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name($row->FullName,$row->Furigana)."</a></td>\n";
  }
  if ($_POST['listtype'] == "Normal") {
    if ($row->CellPhone && $row->Phone) {
      echo "<td class=\"phone\">".$row->Phone."<br />".$row->CellPhone."</td>\n";
    } else {
      echo "<td class=\"phone\">".$row->Phone."".$row->CellPhone."</td>\n";
    }
    echo "<td class=\"email\">".email2link($row->Email)."</td>\n";
    echo "<td class=\"address\">".$row->PostalCode.$row->Prefecture.$row->ShiKuCho.db2table($row->Address)."</td>\n";
    echo "<td class=\"remarks\">".email2link(url2link(d2h($row->Remarks)))."</td>\n";
  }
  echo "<td class=\"cdate\">".$row->ContactDate."</td>\n";
  if ($_POST['listtype'] != "ContactType") {
    echo "<td class=\"ctype\" style=\"background-color:".$row->BGColor."\">".$row->ContactType."</td>\n";
  }
  echo "<td class=\"desc\">".$row->Description."</td>\n";
  ($row->Processed ? " checked" : "")."></td>\n";
  if ($_POST['listtype'] == "Normal") echo "<td class=\"selectcol\">-</td>\n";
  echo "</tr>\n";
  if ($_POST['listtype']!="Normal") {
    $prev_groupfieldvalue = $row->$_POST['listtype'];
    $prev_name = readable_name($row->FullName,$row->Furigana);
  }
}
echo "</tbody></table>\n";
echo $_SESSION['userid']=="karen"?"<pre class=\"noprint\">".$sql."</pre>":"";

/*
echo "<table border=1 cellspacing=0 cellpadding=1 style=\"empty-cells:show\">\n";
echo "<tr><th>Name</th><th>Photo</th><th>Date and Description</th></tr>\n";

$sql = "SELECT contact.PersonID,FullName,Furigana,Photo,ContactDate,Description,ContactType,BGColor".
" FROM contact LEFT JOIN person ON person.PersonID=contact.PersonID".
" LEFT JOIN contacttype ON contact.ContactTypeID=contacttype.ContactTypeID".$where;
$sql .= " ORDER BY Furigana,contact.PersonID,ContactDate";
if (!$result = mysql_query($sql)) {
  exit("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
}
$prev_pid = 0;
while ($row = mysql_fetch_object($result)) {
  if ($row->PersonID != $prev_pid) {
    if ($prev_pid != 0) {  // Not first one, so need to close table and row
      echo "</table>\n</td></tr>\n";
    }
    echo "<tr><td valign=middle nowrap><a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
    echo readable_name_2line($row->FullName, $row->Furigana)."</a></td>\n";
    if ($row->Photo == 1) {
      echo "<td align=center><img border=0 src=\"photos/p".$row->PersonID.".jpg\" width=50></td>\n";
    } else {
      echo "<td></td>\n";
    }
    echo "<td><table border=0 cellspacing=0 cellpadding=1>\n";
    $prev_pid = $row->PersonID;
  }
  echo "<tr><td valign=middle nowrap>{$row->ContactDate}&nbsp;&nbsp;</td>\n";
  if ($_POST['ctype']) {
    echo "<td>{$row->Description}</td></tr>\n";
  } else {
    echo "<td bgcolor=\"{$row->BGColor}\">[{$row->ContactType}] {$row->Description}</td></tr>\n";
  }
}
echo "</table>\n</td></tr></table>\n";
*/
footer();
?>
