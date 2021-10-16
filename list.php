<?php
include("functions.php");
include("accesscontrol.php");

$criterialist = "<ul id=\"criteria\">";
$sql = "SELECT ".(!empty($_GET['countonly']) ?
  "person.PersonID " :
  "person.*, household.AddressComp, household.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories ");
$sql .= "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".(!empty($_GET['countonly']) ? "" :
    "LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID");
$join = $where = "";
$ptable = $grouptable = "person";
$closing = '';
if (!empty($_GET['qs'])) {
  $where .= " WHERE person.FullName LIKE '%".$_GET['qs']."%' OR person.Furigana LIKE '%".$_GET['qs']."%'".
      " OR person.Email LIKE '%".$_GET['qs']."%' OR person.CellPhone LIKE '%".$_GET['qs']."%'".
      " OR person.Country LIKE '%".$_GET['qs']."%' OR person.URL LIKE '%".$_GET['qs']."%'".
      " OR person.Remarks LIKE '%".$_GET['qs']."%' OR person.Birthdate LIKE '%".$_GET['qs']."%'".
      " OR household.AddressComp LIKE '%".$_GET['qs']."%' OR household.RomajiAddressComp LIKE '%".$_GET['qs']."%'".
      " OR household.Phone LIKE '%".$_GET['qs']."%' OR household.LabelName LIKE '%".$_GET['qs']."%'";
}
if (!empty($_GET['filter'])) {
  if ($_GET['filter'] == "Organizations") {
    $where .= " WHERE Organization>0";
    $criterialist .= "<li>" . _("Organizations only");
  } elseif ($_GET['filter'] == "People") {
    $where .= " WHERE Organization=0";
    $criterialist .= "<li>" . _("People only (no organizations)");
  } elseif ($_GET['filter'] == "OrgsOfPeople") {
    $sql = "SELECT DISTINCT p1.*, h1.AddressComp, h1.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories " .
        "FROM person p1 LEFT JOIN household h1 ON p1.HouseholdID=h1.HouseholdID " .
        "LEFT JOIN percat ON p1.PersonID=percat.PersonID " .
        "LEFT JOIN category ON percat.CategoryID=category.CategoryID WHERE p1.PersonID IN (SELECT OrgID FROM perorg po " .
        "INNER JOIN person p2 ON po.PersonID=p2.PersonID LEFT JOIN household ON p2.HouseholdID=household.HouseholdID";
    $criterialist .= "<li>" . _("Organizations with members who have the following criteria...");
    $ptable = "p2";
    $grouptable = "p1";
    $closing = ")";
  } elseif ($_GET['filter'] == "PeopleOfOrgs") {
    $sql = "SELECT DISTINCT p1.*, h1.AddressComp, h1.Phone, GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n') AS categories " .
        "FROM person p1 LEFT JOIN household h1 ON p1.HouseholdID=h1.HouseholdID " .
        "LEFT JOIN percat ON p1.PersonID=percat.PersonID " .
        "LEFT JOIN category ON percat.CategoryID=category.CategoryID WHERE p1.PersonID IN (SELECT po.PersonID FROM perorg po " .
        "INNER JOIN person o ON po.OrgID=o.PersonID LEFT JOIN household ON o.HouseholdID=household.HouseholdID";
    $criterialist .= "<li>" . _("People whose related organizations have the following criteria...");
    $ptable = "o";
    $grouptable = "p1";
    $closing = ")";
  }
}
for ($i=1; !empty($_GET['textinput'.$i]); $i++) {
  $search = str_replace("%","\%",h2d($_GET['textinput'.$i]));
  $target = empty($_GET['texttarget'.$i]) ? 'Name' : $_GET['texttarget'.$i];
  $not = (!empty($_GET['textinout'.$i]) && $_GET['textinout'.$i]=="OUT") ? ' NOT' : "";
  $where .= $where==''?' WHERE ':' AND ';
  $in = $not ? _('in') : _('not in');
  switch($target) {
  case 'Name':
    $where .= "$not ($ptable.FullName LIKE '%".$search."%' OR $ptable.Furigana LIKE '%".$search."%' OR LabelName LIKE '%".$search."%')";
    if ($_SESSION['furiganaisromaji']) {
      $criterialist .= "<li>".sprintf(_("\"%s\" $in Name, Romaji, or Label"), $search)."</li>\n";
    } else {
      $criterialist .= "<li>".sprintf(_("\"%s\" $in Name, Furigana, or Label"), $search)."</li>\n";
    }
    break;
  case 'Address':
    $where .= "$not household.AddressComp LIKE '%".$search."%' "
    .($_SESSION['romajiaddresses']=="yes" ? "OR household.RomajiAddressComp LIKE '%".$search."%'" : "");
    $criterialist .= "<li>".sprintf(_("\"%s\" $in Address"), $search)."</li>\n";
    break;
  case 'Phone':
    $where .= "$not (household.Phone LIKE '%".$search."%' OR $ptable.CellPhone LIKE '%".$search."%' OR FAX LIKE '%".$search."%')";
    $criterialist .= "<li>".sprintf(_("\"%s\" $in Phone or FAX"), $search)."</li>\n";
    break;
  case 'PersonID':
    $where .= "$not ($ptable.PersonID = ".$search.")";
    $criterialist .= "<li>".sprintf(_("\"%s\" $in Phone or FAX"), $search)."</li>\n";
    break;
  default:
    $where .= "$not ($ptable.$target LIKE '%".$search."%')";
    $criterialist .= "<li>".sprintf(_("\"%s\" $in %s"), $search, _($target))."</li>\n";
  }
}

for ($i=1; !empty($_GET['catselect'.$i]); $i++) {
  $cats = implode(',',$_GET['catselect'.$i]);
  $not = ($_GET['catinout'.$i]=='OUT') ? ' NOT' : '';
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM percat WHERE CategoryID IN ($cats)))";
  $result = sqlquery_checked("SELECT Category FROM category WHERE CategoryID IN ($cats) ORDER BY Category");
  $catnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $catnames .= d2h($row->Category).', ';
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('In none of these categories: %s'), mb_substr($catnames,0,mb_strlen($catnames)-2))."</li>\n";
  } else {
    $criterialist .= '<li>'.sprintf(_('In at least one of these categories: %s'), mb_substr($catnames,0,mb_strlen($catnames)-2))."</li>\n";
  }
}

for ($i=1; !empty($_GET['ctselect'.$i]); $i++) {
  $cts = implode(',',$_GET['ctselect'.$i]);
  $not = ($_GET['actioninout'.$i]=="OUT") ? " NOT" : "";
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM action WHERE ActionTypeID IN ($cts)";
  if (!empty($_GET['ctstartdate'.$i])) $where .= " AND ActionDate >= '".$_GET['ctstartdate'.$i]."'";
  if (!empty($_GET['ctenddate'.$i])) $where .= " AND ActionDate <= '".$_GET['ctenddate'.$i]."'";
  $where .= '))';
  $result = sqlquery_checked("SELECT ActionType FROM actiontype WHERE ActionTypeID IN ($cts) ORDER BY ActionType");
  $ctnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $ctnames .= d2h($row->ActionType).', ';
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('Has none of these types of actions: %s'), mb_substr($ctnames,0,mb_strlen($ctnames)-2));
  } else {
    $criterialist .= '<li>'.sprintf(_('Has at least one of these types of actions: %s'), mb_substr($ctnames,0,mb_strlen($ctnames)-2));
  }
  if (!empty($_GET['ctstartdate'.$i]) && !empty($_GET['ctenddate'.$i])) $criterialist .= sprintf(_(', between %s and %s'),$_GET['ctstartdate'.$i],$_GET['ctenddate'.$i]);
  elseif (!empty($_GET['ctstartdate'.$i])) $criterialist .= sprintf(_(', on or after %s'),$_GET['ctstartdate'.$i]);
  elseif (!empty($_GET['ctenddate'.$i])) $criterialist .= sprintf(_(', on or before %s'),$_GET['ctenddate'.$i]);
  $criterialist .= "</li>\n";
}

for ($i=1; isset($_GET['seqctqual'.$i]) && isset($_GET['seqctelim'.$i]); $i++) {
  $qualcts = implode(',',$_GET['seqctqual'.$i]);
  $elimcts = implode(',',$_GET['seqctelim'.$i]);
  $minmax = ($_GET['seqorder'.$i]=='AFTER') ? 'MAX' : 'MIN';
  $operator = ($_GET["seqorder".$i]=="AFTER") ? ">" : "<";
  $join = " inner join (select pq.PersonID,$minmax(ActionDate) as qualdate from person pq".
  " inner join action aq on pq.PersonID = aq.PersonID where aq.ActionTypeID in ($qualcts) group by pq.PersonID) qual".
  " on $ptable.PersonID=qual.PersonID left outer join (select pe.personID,$minmax(ActionDate) as elimdate from person pe".
  " inner join action ae on pe.PersonID = ae.PersonID where ae.ActionTypeID in ($elimcts) group by pe.PersonID) elim".
  " on qual.PersonID=elim.PersonID";
  $where .= ($where==''?' WHERE ':' AND ')." (elim.elimdate is null or qual.qualdate $operator elim.elimdate)";
  $result = sqlquery_checked("SELECT ActionType FROM actiontype WHERE ActionTypeID IN ($qualcts) ORDER BY ActionType");
  $ctqualnames = "";
  while ($row = mysqli_fetch_object($result)) {
    $ctqualnames .= d2h($row->ActionType).", ";
  }
  $result = sqlquery_checked("SELECT ActionType FROM actiontype WHERE ActionTypeID IN ($elimcts) ORDER BY ActionType");
  $ctelimnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $ctelimnames .= d2h($row->ActionType).', ';
  }
  if ($_GET['seqorder'.$i]=='AFTER') {
    $criterialist .= '<li>'.sprintf(_('Has at least one action of type(s) [%s] and none later of type(s) [%s]'),
    mb_substr($ctqualnames,0,mb_strlen($ctqualnames)-2), mb_substr($ctelimnames,0,mb_strlen($ctelimnames)-2))."</li>\n";
  } else {
    $criterialist .= '<li>'.sprintf(_('Has at least one action of type(s) [%s] and none earlier of type(s) [%s]'),
    mb_substr($ctqualnames,0,mb_strlen($ctqualnames)-2), mb_substr($ctelimnames,0,mb_strlen($ctelimnames)-2))."</li>\n";
  }
}

for ($i=1; isset($_GET['dtselect'.$i]); $i++) {
  $dts = implode(',',$_GET['dtselect'.$i]);
  $not = ($_GET['donationinout'.$i]=="OUT") ? " NOT" : "";
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM donation WHERE DonationTypeID IN ($dts)";
  if ($_GET['dtstartdate'.$i]) $where .= " AND DonationDate >= '".$_GET['dtstartdate'.$i]."'";
  if ($_GET['dtenddate'.$i]) $where .= " AND DonationDate <= '".$_GET['dtenddate'.$i]."'";
  $where .= '))';
  $result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN ($dts) ORDER BY DonationType");
  $dtnames = '';
  while ($row = mysqli_fetch_object($result)) {
    $ctnames .= d2h($row->DonationType).', ';
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('Has not donated any of these donation types: %s'), mb_substr($ctnames,0,mb_strlen($dtnames)-2));
  } else {
    $criterialist .= '<li>'.sprintf(_('Has donated at least one of these donation types: %s'), mb_substr($ctnames,0,mb_strlen($dtnames)-2));
  }
  if ($_GET['dtstartdate'.$i] && $_GET['dtenddate'.$i]) $criterialist .= sprintf(_(', between %s and %s'),$_GET['dtstartdate'.$i],$_GET['dtenddate'.$i]);
  elseif ($_GET['dtstartdate'.$i]) $criterialist .= sprintf(_(', on or after %s'),$_GET["dtstartdate".$i]);
  elseif ($_GET['dtenddate'.$i]) $criterialist .= sprintf(_(', on or before %s'),$_GET["dtenddate".$i]);
  $criterialist .= "</li>\n";
}

for ($i=1; isset($_GET['eventselect'.$i]); $i++) {
  $events = implode(',',$_GET['eventselect'.$i]);
  $not = ($_GET['attendinout'.$i]=='OUT') ? ' NOT' : '';
  $where .= ($where==''?' WHERE ':' AND ')." $not ($ptable.PersonID IN (SELECT PersonID FROM attendance WHERE EventID IN ($events)";
  if ($_GET['astartdate'.$i]) $where .= " AND AttendDate >= '".$_GET['astartdate'.$i]."'";
  if ($_GET['aenddate'.$i]) $where .= " AND AttendDate <= '".$_GET['aenddate'.$i]."'";
  $where .= '))';
  $result = sqlquery_checked("SELECT Event FROM event WHERE EventID IN ($events) ORDER BY Event");
  $eventnames = "";
  while ($row = mysqli_fetch_object($result)) {
    $eventnames .= d2h($row->Event).", ";
  }
  if ($not) {
    $criterialist .= '<li>'.sprintf(_('Has not attended any of these events: %s'), mb_substr($eventnames,0,mb_strlen($eventnames)-2));
  } else {
    $criterialist .= '<li>'.sprintf(_('Has attended one or more of these events: %s'), mb_substr($eventnames,0,mb_strlen($eventnames)-2));
  }
  if ($_GET['astartdate'.$i] && $_GET['aenddate'.$i]) $criterialist .= sprintf(_(', between %s and %s'),$_GET['astartdate'.$i],$_GET['aenddate'.$i]);
  elseif ($_GET['astartdate'.$i]) $criterialist .= sprintf(_(', on or after %s'),$_GET['astartdate'.$i]);
  elseif ($_GET['aenddate'.$i]) $criterialist .= sprintf(_(', on or before %s'),$_GET['aenddate'.$i]);
  $criterialist .= "</li>\n";
}

for ($i=1; isset($_GET['blanktarget'.$i]); $i++) {
  if ($_GET['blanktarget'.$i] != '') {
    $target = $_GET['blanktarget'.$i];
    $not = ($_GET['blankinout'.$i]=='OUT') ? ' NOT' : '';
    $where .= $where==''?' WHERE ':' AND ';
    switch($target) {
    case 'Birthdate':
      $where .= "$not $ptable.$target='0000-00-00'";
      break;
    case 'Address':
    case 'LabelName':
    case 'Phone':
    case 'FAX':
      $where .= "$not $target=''";
      break;
    default:
      $where .= "$not ($ptable.$target = '')";
    }
    if ($not) {
      $criterialist .= '<li>'.sprintf(_('"%s" is not blank'), _($target))."</li>\n";
    } else {
      $criterialist .= '<li>'.sprintf(_('"%s" is blank'), _($target))."</li>\n";
    }
  }
}

if (!empty($_GET['freesql'])) {
  $where .= ($where==''?' WHERE ':' AND ').$_GET['freesql'];
  $criterialist .= "<li>".$_GET['freesql']."</li>\n";
}

if (!empty($_GET['bucket']) && $_SESSION['bucket']) {
  $where .= ($where==''?' WHERE ':' AND ').$grouptable.'.PersonID IN ('.implode(',',$_SESSION['bucket']).')';
  $criterialist .= '<li>'._('In the Bucket')."</li>\n";
}

$sql .= $join . $where . $closing . " GROUP BY $grouptable.PersonID ORDER BY Furigana";
$criterialist .= "</ul>\n";

if (!$result = mysqli_query($db, $sql)) {
  header1(_('Error'));
  echo '<link rel="stylesheet" href="style.php" type="text/css" />';
  header2(1);
  echo $criterialist;
  echo "<div style=\"border: 2px solid darkred;background-color:#ffe0e0;color:darkred;padding-left:5px;margin:20px 0;\">$sql</div>";
  echo "<div style=\"font-weight:bold;margin:10px 0\">The query had an error:<br>".mysqli_errno($db).": ".mysqli_error($db)."</div>";
  exit;
}

if (mysqli_num_rows($result) == 0) {
  header("Location: search.php?text=".urlencode(_("Search resulted in no records.".($_SESSION['userid']=="dev"?urlencode("<pre>".$sql."</pre>"):""))));
  exit;
} elseif (mysqli_num_rows($result) == 1) {
  $person = mysqli_fetch_object($result);
  header("Location: individual.php?pid=".$person->PersonID);
  exit;
}
header1(_("Search Results").(!empty($_POST['preselected']) ? sprintf(_(" (%d People/Orgs Pre-selected)"),$psnum) : ""));

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
<?php
header2(1);
?>
<div style="float:left; vertical-align:bottom">
<?php
echo "<h3>".sprintf(_("%d results of these criteria:"),mysqli_num_rows($result)).(!empty($_GET['countonly']) ? "&nbsp;&nbsp;&nbsp;<a href=\"".
str_replace("countonly=yes","countonly=",$_SERVER['REQUEST_URI'])."\">"._("(Show results)")."</a>" : "")."</h3>\n";
echo $criterialist;
?>
</div>
<div style="float:right; vertical-align:bottom">
<?php if (empty($_GET['countonly'])) {  //can't do CSV if there is no table ?>
  <form action="download.php" method="post" target="_top">
  <input type="hidden" id="csvtext" name="csvtext" value="">
  <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
  </form>
<?php } //end if not count only ?>
</div>
<div style="clear:both"></div>
<?php
$pids = array();
if (!empty($_GET['countonly'])) {  //if count only, just get pids for multi-select
  while ($row = mysqli_fetch_object($result)) $pids[] = $row->PersonID;
} else {  //if not count only, build the whole table
  echo "<table id=\"mainTable\" class=\"tablesorter\"><thead>";
  echo "<tr>";
  echo $tableheads;
  echo "</tr></thead><tbody>\n";
  while ($row = mysqli_fetch_object($result)) {
    $pids[] = $row->PersonID;
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
echo $_SESSION['userid']=="dev"?"<pre class=\"noprint\">SQL: ".$sql."\n".
    "PIDS: ".implode(',',$pids)."\nBUCKET: ".implode(',',$_SESSION['bucket'])."</pre>":"";
?>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>
<script type="text/javascript">
$(document).ready(function() {
  $("#preselected").val($("#pids").text());  //to be deprecated
  $('#pids-for-bucket').val('<?=implode(',',$pids)?>');
  
  $("#mainTable").tablesorter({
    sortList:[[2,0]],
    headers:{14:{sorter:false}}
  });
  $('#mainTable').columnManager({listTargetID:'targetall',
  onClass: 'advon',
  offClass: 'advoff',
  hideInList: [<?=$hideInList?>],
  colsHidden: [<?=$colsHidden?>],
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
<?php
footer();
