<?php
include("functions.php");
include("accesscontrol.php");
header1(_("Action List").(!empty($_POST['preselected']) ? sprintf(_(" (%d People/Orgs Pre-selected)"),
    substr_count($_POST['preselected'],",")+1) : ""));

$listtype = $_POST['listtype'];

if ($listtype != "PersonID") {
  if ($listtype == "Normal") {
    $cols[] = array("personid",1);
    $cols[] = array("name-for-csv",0);
    $cols[] = array("furigana-for-csv",0);
  }
  $cols[] = array("name-for-display",0);
}
if ($listtype == "Normal") {
  $cols[] = array("phone",1);
  $cols[] = array("email",1);
  $cols[] = array("address",1);
  $cols[] = array("remarks",1);
}
$cols[] = array("adate",1);
if ($listtype != "ActionType") $cols[] = array("atype",1);
$cols[] = array("desc",1);
if ($listtype == "Normal") $cols[] = array("selectcol",0);
$colsHidden = $hideInList = "";
foreach($cols as $i=>$col) {
  if ($col[1]==0) $hideInList .= ",".($i+1);
  elseif (stripos(",".$_SESSION['actionlist_showcols'].",",",".$col[0].",") === FALSE)  $colsHidden .= ",".($i+1);
}
$hideInList = substr($hideInList,1);  //to remove the leading comma
$colsHidden = substr($colsHidden,1);  //to remove the leading comma

$tableheads = '';
if ($listtype != "PersonID") {
  if ($listtype == "Normal") {
    $tableheads .= '<th class="personid">'._('ID')."</th>\n";
    $tableheads .= '<th class="name-for-csv" style="display:none">'._('Name')."</th>\n";
    $tableheads .= '<th class="furigana-for-csv" style="display:none">'.($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana'))."</th>\n";
  }
  $tableheads .= "<th class=\"name-for-display\">"._("Name")." (".($_SESSION['furiganaisromaji']=='yes' ? _('Romaji') : _('Furigana')).")</th>\n";
}
if ($listtype == "Normal") {
  $tableheads .= '<th class="phone">'._('Phone')."</th>\n";
  $tableheads .= '<th class="email">'._('Email')."</th>\n";
  $tableheads .= '<th class="address">'._('Address')."</th>\n";
  $tableheads .= '<th class="remarks">'._('Remarks')."</th>\n";
}
$tableheads .= '<th class="adate">'._('Date')."</th>\n";
if ($listtype != 'ActionType') $tableheads .= '<th class="atype">'._('Action Type')."</th>\n";
$tableheads .= '<th class="desc">'._("Description")."</th>\n";
if ($listtype == "Normal") $tableheads .= '<th id="thSelectColumn" class="selectcol">'.
    '<ul id="ulSelectColumn"><li><img src="graphics/selectcol.png" alt="select columns" '.
    'title="select columns" /><ul id="targetall"></ul></li></ul>';
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
  $("#atable").tablesorter({
    sortList:[[3,0],[8,1]],
    headers:{<?=(count($cols)-1)?>:{sorter:false}}
  });
  $(".grouptable").tablesorter({ sortList:[[0,<?=($listtype=="PersonID"?"1":"0],[1,1")?>]] });

  $('#atable').columnManager({listTargetID:'targetall',
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
  $('#csvtext').val($('#atable').table2CSV({delivery:'value'}));
  $(".name-for-csv, .furigana-for-csv").hide();
  $(".name-for-display, .selectcol").show();
}
</script>
<?php
header2($_GET['nav']);
if ($_GET['nav']==1) echo "<h1 id=\"title\">"._("Action List").(!empty($_POST['preselected']) ?
sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : "")."</h1>\n";

$where = '';
if (!empty($_POST['atype'])) $where .= ($where?" AND":" WHERE")." a.ActionTypeID IN (".implode(",",$_POST['atype']).")";
if (!empty($_POST['startdate'])) $where .= ($where?" AND":" WHERE")." ActionDate >= '".$_POST['startdate']."'";
if (!empty($_POST['enddate'])) $where .= ($where?" AND":" WHERE")." ActionDate <= '".$_POST['enddate']."'";
if (!empty($_POST['csearch'])) $where .= ($where?" AND":" WHERE")." Description LIKE '%".$_POST['csearch']."%'";
if (!empty($_POST['preselected'])) $where .= ($where?" AND":" WHERE")." a.PersonID IN (".$_POST['preselected'].")";

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

?>
<div id="actions">
  <form action="multiselect.php" method="post" target="_top">
    <input type="hidden" id="preselected" name="preselected" value="<?=$pids?>">
    <input type="submit" value="<?=_("Go to Multi-Select with these entries preselected")?>">
  </form>
<?php if ($listtype == "Normal") { ?>
  <form action="download.php" method="post" target="_top">
    <input type="hidden" id="csvtext" name="csvtext" value="">
    <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
  </form>
<?php } // if listtype=Normal ?>
</div>
<?php
if ($listtype == "Normal") {
  $tablestart = '<table id="atable" class="tablesorter">';
} else {
  $tablestart = '<table class="grouptable tablesorter">';
}
$tablestart .= "<thead>\n<tr>".$tableheads."</tr>\n</thead><tbody>\n";

$prev_groupfieldvalue = "";
$firstrow = 1; //i.e. true
$sql = 'SELECT a.*,at.ActionType,at.BGColor,p.FullName,p.Furigana';
if ($listtype == 'Normal') $sql .= ',p.Photo,p.CellPhone,p.Email,p.Remarks,h.*,pc.*';
$sql .= ' FROM action a LEFT JOIN person p ON p.PersonID=a.PersonID';
if ($listtype == 'Normal') $sql .= ' LEFT JOIN household h ON p.HouseholdID=h.HouseholdID'.
' LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode';
$sql .= ' LEFT JOIN actiontype at ON a.ActionTypeID=at.ActionTypeID'.$where;
if ($listtype == 'ActionType') {
  $sql .= ' ORDER BY ActionType,Furigana,PersonID,ActionDate DESC';
} else {
  $sql .= ' ORDER BY Furigana,PersonID,ActionDate DESC';
}
$result = sqlquery_checked($sql);
$num_actions = mysqli_num_rows($result);
echo '<h3>'.sprintf(_('%d matching actions (by %d different people/orgs)'), $num_actions, $num_people).'</h3>';

while ($row = mysqli_fetch_object($result)) {
  if ($listtype!="Normal" && $prev_groupfieldvalue!='' && $row->$listtype!=$prev_groupfieldvalue) {  //change of section
    echo "</tbody></table>\n";
    echo "<h3>".($listtype=='PersonID'?'<a href="individual.php?pid='.$row->PersonID.
    '" target="_blank">'.readable_name($row->FullName,$row->Furigana):$row->$listtype)."</a></h3>\n";
    echo $tablestart;
  } elseif ($listtype!='Normal' && $prev_groupfieldvalue=='') {
    echo '<h3>'.($listtype=='PersonID'?'<a href="individual.php?pid='.$row->PersonID.
    '" target="_blank">'.readable_name($row->FullName,$row->Furigana):$row->$listtype)."</a></h3>\n";
  }
  if ($firstrow) {
    echo $tablestart;
    $firstrow = 0;
  }
  echo "<tr>";
  if ($listtype != 'PersonID') {
    if ($listtype == 'Normal') {
      echo '<td class="personid">'.$row->PersonID."</td>\n";
      echo '<td class="name-for-csv" style="display:none">'.$row->FullName."</td>\n";
      echo '<td class="furigana-for-csv" style="display:none">'.$row->Furigana."</td>\n";
    }
    echo '<td class="name-for-display"><span style="display:none">'.$row->Furigana.'</span>';
    echo '<a href="individual.php?pid='.$row->PersonID.'" target="_blank">';
    echo readable_name($row->FullName,$row->Furigana)."</a></td>\n";
  }
  if ($listtype == 'Normal') {
    if ($row->CellPhone && $row->Phone) {
      echo '<td class="phone">'.$row->Phone."<br />".$row->CellPhone."</td>\n";
    } else {
      echo '<td class="phone">'.$row->Phone.$row->CellPhone."</td>\n";
    }
    echo '<td class="email">'.email2link($row->Email)."</td>\n";
    echo '<td class="address">'.$row->PostalCode.$row->Prefecture.$row->ShiKuCho.db2table($row->Address)."</td>\n";
    echo '<td class="remarks">'.email2link(url2link(d2h($row->Remarks)))."</td>\n";
  }
  echo '<td class="adate">'.$row->ActionDate."</td>\n";
  if ($listtype != 'ActionType') {
    echo '<td class="atype" style="background-color:#'.$row->BGColor.'">'.$row->ActionType."</td>\n";
  }
  echo '<td class="desc">'.$row->Description."</td>\n";
  if ($listtype == 'Normal') echo '<td class="selectcol">-</td>';
  echo "</tr>\n";
  if ($listtype != 'Normal') {
    $prev_groupfieldvalue = $row->$listtype;
    $prev_name = readable_name($row->FullName,$row->Furigana);
  }
}
echo "</tbody></table>\n";

if ($_SESSION['userid']== 'dev') {
  //echo 'POST:<pre class="noprint">'.print_r($_POST,true).'</pre>';
  echo 'SQL:<pre class="noprint">'.$sql.'</pre>';
}
footer();
?>
