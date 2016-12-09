<?php
include("functions.php");
include("accesscontrol.php");

$summary = $_POST['show_summary'] ? 1 : 0;
$type = $_POST['show_list'] ? $_POST['listtype'] : $_POST['summarytype'];
$title = $_POST['show_list'].$_POST['show_summary'].
  ($_POST['preselected']!="" ? sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1) : "");
header1($title);

if ($summary) {
  if ($type == "PersonID") {
    $tableheads = "<th class=\"name-for-csv\" style=\"display:none\">"._("Name")."</th>\n";
    $tableheads .= "<th class=\"furigana-for-csv\" style=\"display:none\">".
    ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana"))."</th>\n";
    $tableheads .= "<th class=\"name-for-display\">"._("Name")." (".
    ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>\n";
  } else {
    $tableheads .= "<th class=\"dtype\">"._("Donation Type")."</th>\n";
  }
  $tableheads .= "<th class=\"amount-for-csv\" style=\"display:none\">"._("Amount")."</th>\n";
  $tableheads .= "<th class=\"amount-for-display\">"._("Amount")."</th>\n";
} else {    // full list
  $cols[] = array("ddate",1);
  if ($type != "PersonID") {
    if ($type == "Normal") {
      $cols[] = array("personid",1);
      $cols[] = array("name-for-csv",0);
      $cols[] = array("furigana-for-csv",0);
    }
    $cols[] = array("name-for-display",0);
  }
  if ($type == "Normal") {
    $cols[] = array("phone",1);
    $cols[] = array("email",1);
    $cols[] = array("address",1);
    $cols[] = array("country",1);
    $cols[] = array("remarks",1);
  }
  if ($type != "DonationType") $cols[] = array("dtype",1);
  $cols[] = array("pledge",1);
  $cols[] = array("amount-for-csv",1);
  $cols[] = array("amount-for-display",0);
  $cols[] = array("desc",1);
  $cols[] = array("proc",0);
  if ($type == "Normal") $cols[] = array("selectcol",0);
  $colsHidden = $hideInList = "";
  foreach($cols as $i=>$col) {
    if ($col[1]==0) $hideInList .= ",".($i+1);
    elseif (stripos(",".$_SESSION['donationlist_showcols'].",",",".$col[0].",") === FALSE)  $colsHidden .= ",".($i+1);
  }
  $hideInList = substr($hideInList,1);  //to remove the leading comma
  $colsHidden = substr($colsHidden,1);  //to remove the leading comma

  $tableheads = "<th class=\"ddate\">"._("Date")."</th>\n";
  if ($type != "PersonID") {
    if ($type == "Normal") {
      $tableheads .= "<th class=\"personid\">"._("ID")."</th>\n";
      $tableheads .= "<th class=\"name-for-csv\" style=\"display:none\">"._("Name")."</th>\n";
      $tableheads .= "<th class=\"furigana-for-csv\" style=\"display:none\">".
      ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana"))."</th>\n";
    }
    $tableheads .= "<th class=\"name-for-display\">"._("Name")." (".
    ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>\n";
  }
  if ($type == "Normal") {
    $tableheads .= "<th class=\"phone\">"._("Phone")."</th>\n";
    $tableheads .= "<th class=\"email\">"._("Email")."</th>\n";
    $tableheads .= "<th class=\"address\">"._("Address")."</th>\n";
    $tableheads .= "<th class=\"country\">"._("Home Country")."</th>\n";
    $tableheads .= "<th class=\"remarks\">"._("Remarks")."</th>\n";
  }
  if ($type != "DonationType") $tableheads .= "<th class=\"dtype\">"._("Donation Type")."</th>\n";
  $tableheads .= "<th class=\"pledge\">"._("Pledge?")."</th>\n";
  $tableheads .= "<th class=\"amount-for-csv\" style=\"display:none\">"._("Amount")."</th>\n";
  $tableheads .= "<th class=\"amount-for-display\">"._("Amount")."</th>\n";
  $tableheads .= "<th class=\"desc\">"._("Description")."</th>\n";
  $tableheads .= "<th class=\"proc\">"._("Proc.")."</th>\n";
  if ($type == "Normal") $tableheads .= "<th id=\"thSelectColumn\" class=\"selectcol\">".
      "<ul id=\"ulSelectColumn\"><li><img src=\"graphics/selectcol.png\" alt=\"select columns\" ".
      "title=\"select columns\" /><ul id=\"targetall\"></ul></li></ul>";
  $tableheads .= "</th>\n";
}
?>
<link rel="stylesheet" href="style.php?table=1" type="text/css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>
<style>
td.amount-for-display { text-align:right; }
</style>
<script type="text/javascript">
$(document).ready(function() {
<?php if ($summary) { ?>
  $("#summarytable").tablesorter({ sortList:[[<?=($type=="PersonID"?($_POST['limit']?"4,1":"2,0"):"0,0")?>]] });
<?php } else { ?>
  $("#listtable").tablesorter({
    sortList:[[4,0],[0,1]],
    headers:{<?=(count($cols)-2)?>:{sorter:false},<?=(count($cols)-1)?>:{sorter:false}}
  });
  $(".grouptable").tablesorter({
    sortList:[[0,1]],
    headers:{<?=(count($cols)-1)?>:{sorter:false}}
  });

  $('#listtable').columnManager({listTargetID:'targetall',
  onClass: 'advon',
  offClass: 'advoff',
  hideInList: [<?=$hideInList?>],
  colsHidden: [<?=$colsHidden?>],
  saveState: false});
  $('#ulSelectColumn').clickMenu({onClick: function(){}});
  
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  }); 

  $('td.proc input[type=checkbox]').change(function() {
    $("#updateproc").attr("disabled", false);
  });
  $("#allproc").click(function() {
    $('td.proc input[type=checkbox]').prop("checked", true);
    $("#updateproc").attr("disabled", false);
  });
  $("#updateproc").click(function() {
    var proc_on = $('td.proc input[type=checkbox]').filter(":checked")
      .map(function() { return this.id; })
      .get().join(',');
    var proc_off = $('td.proc input[type=checkbox]').not(":checked")
      .map(function() { return this.id; })
      .get().join(',');
    $.post("ajax_actions.php",
      { action:"DonationProc", proc_on:proc_on, proc_off:proc_off },
      function(data) {
        alert(data);
        if (data.substr(0,1) == "*") {  //my clue that the update succeeded
          $("#updateproc").attr("disabled", true);
        }
      }
    );
  });
<?php } ?>
});

function getCSV() {
  $(".name-for-display, .amount-for-display, .selectcol").hide();
  $(".name-for-csv, .amount-for-csv, .furigana-for-csv").show();
  if (document.getElementById('listtable')) {
    $('#csvtext').val($('#listtable').table2CSV({delivery:'value'}));
  } else {
    $('#csvtext').val($('#summarytable').table2CSV({delivery:'value'}));
  }
  $(".name-for-csv, .amount-for-csv, .furigana-for-csv").hide();
  $(".name-for-display, .amount-for-display, .selectcol").show();
}
</script>
<?php
header2($_GET['nav']);
if ($_GET['nav']==1) echo "<h1 id=\"title\">".$title."</h1>\n";
if ($_SESSION['userid']=="karen") echo "<pre>".print_r($_POST,TRUE)."</pre>";

//construct WHERE clause from criteria
$criteria = "<ul id=\"criteria\">";
$wheredone = 0;
if ($_POST['dtype']) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationTypeID IN (".implode(",",$_POST['dtype']).")";
  $result = sqlquery_checked("SELECT DonationType FROM donationtype WHERE DonationTypeID IN (".implode(",",$_POST['dtype']).")");
  $dtarray = array();
  while ($row = mysqli_fetch_object($result)) {
    $dtarray[] = $row->DonationType;
  }
  $criteria .= "<li>".sprintf(_("In at least one of these donation types: %s"),implode(",",$dtarray))."</li>\n";
  $wheredone = 1;
}
if ($_POST['start']) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationDate>='".$_POST['start']."'";
  $wheredone = 1;
}
if ($_POST['end']) {
  $where .= ($wheredone?" AND":" WHERE")." d.DonationDate<='".$_POST['end']."'";
  $wheredone = 1;
}
if ($_POST['start'] || $_POST['end']) {
  $criteria .= "<li>";
  if ($_POST['start'] && $_POST['end']) $criteria .= sprintf(_("Date between %s and %s"),$_POST['start'],$_POST['end']);
  elseif ($_POST['start']) $criteria .= sprintf(_("Date on or after %s"),$_POST['start']);
  elseif ($_POST['end']) $criteria .= sprintf(_("Date on or before %s"),$_POST['end']);
  $criteria .= "</li>\n";
}
if ($_POST['proc']) {
  $where .= ($wheredone?" AND":" WHERE")." d.Processed=".($_POST['proc']=="proc"?"1":"0");
  $criteria .= "<li>".($_POST['proc']=="proc" ? _("Processed") : _("Unprocessed"))."</li>\n";
  $wheredone = 1;
}
if ($_POST['search']!="") {
  $where .= ($wheredone?" AND":" WHERE")." d.Description LIKE '%".$_POST['search']."%'";
  $criteria .= "<li>".sprintf(_("\"%s\" in Description"), $_POST['search'])."</li>\n";
  $wheredone = 1;
}
if ($_POST['cutoff']!="") {
  if ($summary) $having = " HAVING SUM(d.Amount)".$_POST['cutofftype'].(int)$_POST['cutoff'];
  else $where .= ($wheredone?" AND":" WHERE")." d.Amount".$_POST['cutofftype'].(int)$_POST['cutoff'];
  $criteria .= "<li>".sprintf(_("Amount %s %s"),$_POST['cutofftype'],$_POST['cutoff'])."</li>\n";
  $wheredone = 1;
}
if ($_POST['preselected']) {
  $where .= ($wheredone?" AND":" WHERE")." d.PersonID IN (".$_POST['preselected'].")";
  $criteria .= "<li>".sprintf(_(" (%d People/Orgs Pre-selected)"),substr_count($_POST['preselected'],",")+1)."</li>\n";
  $wheredone = 1;
}
$criteria .="</ul>";

// Main query for summary, or prep query for lists
if ($type=="DonationType") {
  $sql = "SELECT dt.DonationTypeID, dt.DonationType, d.PersonID, SUM(d.Amount) AS subtotal FROM donationtype dt ".
  "LEFT JOIN donation d ON d.DonationTypeID=dt.DonationTypeID".$where." OR d.DonationDate IS NULL";
  $sql .= " GROUP BY dt.DonationTypeID".$having." ORDER BY ".
    ($_POST['subtotalsort'] ? "subtotal DESC," : "")."dt.DonationType,d.DonationTypeID";
} else {  // single list or grouped/summary by person
// in the case of a single list, this query is only to get the IDs for multiselect, so we don't need the other information or subtotals
  $sql = "SELECT ".($type=="Normal" ? "DISTINCT p.PersonID" : "p.PersonID,p.FullName,p.Furigana,SUM(d.Amount) subtotal").
  " FROM donation d LEFT JOIN person p ON p.PersonID=d.PersonID ".
  "LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID".$where;
  if ($type=="Normal") {
    $sql .= " ORDER BY p.PersonID";
  } else {
    $sql .= " GROUP BY p.PersonID".$having." ORDER BY ".
    ($_POST['subtotalsort'] || ($summary && $_POST['limit']) ? "subtotal DESC," : "")."p.Furigana,p.PersonID";
  }
  if ($summary && $type=="PersonID" && $_POST['limit']) $sql .= " LIMIT ".(int)$_POST['limit'];
}
if ($_SESSION['userid']=="karen") echo "<p>".$sql."</p>";
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result) == 0) {
  echo "<h3>"._("There are no records matching your criteria.")."</h3>";
  footer();
  exit;
}
//$pidarray = array();
while ($row = mysqli_fetch_object($result)) {
  $pidarray[] = $row->PersonID;
  if ($type=="DonationType") $dtidarray[] = $row->DonationTypeID;
//if ($_SESSION['userid']=="karen") echo "<p>".$row->PersonID.$row->DonationTypeID." - ".$row->Furigana.$row->DonationType.": ".$row->subtotal."</p>";
}
$pids = implode(",",$pidarray);
if ($type=="DonationType") $dtids = implode(",",$dtidarray);
    
if (!$summary) {
  $sql = "SELECT d.DonationID,d.PersonID,d.PledgeID,d.DonationDate,CAST(d.Amount AS DECIMAL(10,".
  $_SESSION['currency_decimals'].")) Amount,d.Description,d.Processed,p.FullName,p.Furigana,".
  "IF(d.PledgeID,pl.DonationTypeID,d.DonationTypeID) DonationTypeID,".
  "IF(d.PledgeID,dt2.DonationType,dt.DonationType) DonationType,pl.PledgeDesc";
  if ($type == "Normal") $sql .= ",p.Photo,p.CellPhone,p.Email,p.Country,p.Remarks,h.*,pc.*";
  $sql .= " FROM donation d LEFT JOIN person p ON p.PersonID=d.PersonID";
  if ($type == "Normal") $sql .= " LEFT JOIN household h ON p.HouseholdID=h.HouseholdID".
  " LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode";
  $sql .= " LEFT JOIN donationtype dt ON d.DonationTypeID=dt.DonationTypeID".
  " LEFT JOIN pledge pl ON d.PledgeID=pl.PledgeID".
  " LEFT JOIN donationtype dt2 ON pl.DonationTypeID=dt2.DonationTypeID".$where;
  if ($type == "PersonID") {
    $sql .= " ORDER BY ".($_POST['subtotalsort'] ? "FIND_IN_SET(d.PersonID, '".$pids."')" : "Furigana,d.PersonID").",d.DonationDate DESC";
  } elseif ($type == "DonationType") {
    $sql .= " ORDER BY ".($_POST['subtotalsort'] ? "FIND_IN_SET(d.DonationTypeID, '".$dtids."')" : "dt.DonationType").",d.DonationDate DESC";
  } else {  // listtype == Normal
    $sql .= " ORDER BY d.DonationDate DESC";
  }
  $result = sqlquery_checked($sql);
}
//if ($_SESSION['userid']=="karen") echo "<p>".$sql."</p>";

if (!$summary) {
  echo "<h3>".sprintf(_("%d results of these criteria:"),mysqli_num_rows($result))."</h3>\n";
  echo $criteria;
}
echo "<div id=\"actions\">";
if (!$summary) {
?>
  <form action="multiselect.php" method="post" target="_top">
    <input type="hidden" id="preselected" name="preselected" value="<?=$pids?>">
    <input type="submit" value="<?=_("Go to Multi-Select with these entries preselected")?>">
  </form>
<?php
} // if list, not summary
if ($summary || $type=="Normal") {
?>
  <form action="download.php" method="post" target="_top">
    <input type="hidden" id="csvtext" name="csvtext" value="">
    <input type="submit" id="csvfile" name="csvfile" value="<?=_("Download a CSV file of this table")?>" onclick="getCSV();">
  </form>
<?php
} // if listtype=Normal
echo "</div>"; //end of actions div (which may or may not have anything in it)
if (!$summary) {
?>
<div id="procbuttons">
  <button id="allproc"><?=_("Check all")?></button>
  <button id="updateproc" disabled><?=_("Save Changes to \"Processed\" Checkboxes")?></button>
</div>
<?php
} // if list, not summary

// build table of data
if ($summary) {
  echo "<table id=\"summarytable\" class=\"tablesorter\">\n<thead>\n<tr>".$tableheads."</tr>\n</thead><tbody>\n";
  $total = 0;
  mysqli_data_seek($result, 0);
  while ($row = mysqli_fetch_object($result)) {
    if ($type == "PersonID") {
      echo "<tr><td class=\"name-for-csv\" style=\"display:none\">".$row->FullName."</td>\n";
      echo "<td class=\"furigana-for-csv\" style=\"display:none\">".$row->Furigana."</td>\n";
      echo "<td class=\"name-for-display\"><span style=\"display:none\">".$row->Furigana."</span>";
      echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
      echo readable_name($row->FullName,$row->Furigana)."</a></td>\n";
    } else {
      echo "<tr><td class=\"dtype\">".$row->DonationType."</td>\n";
    }
    echo "<td class=\"amount-for-csv\" style=\"display:none\">".
    number_format($row->subtotal,$_SESSION['currency_decimals'],".","")."</td>\n";
    echo "<td class=\"amount-for-display\"><span style=\"display:none\">".sprintf("%015s",$row->subtotal)."</span>".
    $_SESSION['currency_mark']." ".number_format($row->subtotal,$_SESSION['currency_decimals'])."</td>\n";
    echo "</tr>\n";
    $total += $row->subtotal;
  }
  echo "</tbody>\n";

} else { // not summary, so full donation list (perhaps grouped, perhaps not)

  if ($type == "Normal") {
    $tablestart = "<table id=\"listtable\" class=\"tablesorter\">\n";
  } else {
    $tablestart = "<table class=\"grouptable tablesorter\">\n";
  }
  $tablestart .= "<thead>\n<tr>".$tableheads."</tr>\n</thead><tbody>\n";

  $prev_groupfieldvalue = "";
  $total = $subtotal = 0;
  $firstrow = 1; //i.e. true
  while ($row = mysqli_fetch_object($result)) {
    if ($type!="Normal" && $prev_groupfieldvalue!="" && $row->$type!=$prev_groupfieldvalue) {  //change of section
      echo "</tbody><tfoot><tr><td colspan=\"2\" class=\"subtotal\">";
      echo ($type=="PersonID"?$prev_name:$prev_groupfieldvalue)." - "._("Subtotal")."</td>\n";
      echo "<td colspan=\"2\" class=\"subtotal amount\">".$_SESSION['currency_mark']." ".
      number_format($subtotal,$_SESSION['currency_decimals']).
      "</td><td colspan=\"2\" style=\"border-right:0;border-bottom:0\"></td></tr></tfoot></table>\n";
      echo "<h3>".($type=="PersonID"?"<a href=\"individual.php?pid=".$row->PersonID.
      "\" target=\"_blank\">".readable_name($row->FullName,$row->Furigana):$row->$type)."</a></h3>\n";
      echo $tablestart;
      $subtotal = 0;
    } elseif ($type!="Normal" && $prev_groupfieldvalue == "") {
      echo "<h3>".($type=="PersonID"?"<a href=\"individual.php?pid=".$row->PersonID.
      "\" target=\"_blank\">".readable_name($row->FullName,$row->Furigana):$row->$type)."</a></h3>\n";
    }
    if ($firstrow) {
      echo $tablestart;
      $firstrow = 0;
    }
    echo "<tr><td class=\"ddate\">".$row->DonationDate."</td>\n";
    if ($type != "PersonID") {
      if ($type == "Normal") {
        echo "<td class=\"personid\">{$row->PersonID}</td>\n";
        echo "<td class=\"name-for-csv\" style=\"display:none\">{$row->FullName}</td>\n";
        echo "<td class=\"furigana-for-csv\" style=\"display:none\">{$row->Furigana}</td>\n";
      }
      echo "<td class=\"name-for-display\"><span style=\"display:none\">".$row->Furigana."</span>";
      echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_blank\">";
      echo readable_name($row->FullName,$row->Furigana)."</a></td>\n";
    }
    if ($type == "Normal") {
      if ($row->CellPhone && $row->Phone) {
        echo "<td class=\"phone\">".$row->Phone."<br>".$row->CellPhone."</td>\n";
      } else {
        echo "<td class=\"phone\">".$row->Phone."".$row->CellPhone."</td>\n";
      }
      echo "<td class=\"email\">".email2link($row->Email)."</td>\n";
      echo "<td class=\"address\">".$row->PostalCode.$row->Prefecture.$row->ShiKuCho.d2h($row->Address)."</td>\n";
      echo "<td class=\"country\">".d2h($row->Country)."</td>\n";
      echo "<td class=\"remarks\">".email2link(url2link(d2h($row->Remarks)))."</td>\n";
    }
    if ($type != "DonationType") {
      echo "<td class=\"dtype\">{$row->DonationType}</td>\n";
    }
    echo "<td class=\"pledge\">".db2table($row->PledgeDesc)."</td>\n";
    echo "<td class=\"amount-for-csv\" style=\"display:none\">".
    number_format($row->Amount,$_SESSION['currency_decimals'],".","")."</td>\n";
    echo "<td class=\"amount-for-display\"><span style=\"display:none\">".sprintf("%012s",$row->Amount)."</span>".
    $_SESSION['currency_mark']." ".number_format($row->Amount,$_SESSION['currency_decimals'])."</td>\n";
    echo "<td class=\"desc\">{$row->Description}</td>\n";
    echo "<td class=\"proc\"><input type=\"checkbox\" id=\"".$row->DonationID."\" name=\"".$row->DonationID."\"".
    ($row->Processed ? " checked" : "")."></td>\n";
    if ($type == "Normal") echo "<td class=\"selectcol\">-</td>\n";
    echo "</tr>\n";
    if ($type!="Normal") {
      $prev_groupfieldvalue = $row->$type;
      $prev_name = readable_name($row->FullName,$row->Furigana);
      $subtotal += $row->Amount;
    }
    $total += $row->Amount;
  }
  if ($type!="Normal" && $prev_groupfieldvalue!="" && $row->$type!=$prev_groupfieldvalue) {  //final subtotal
    echo "</tbody><tfoot><tr><td colspan=\"2\" class=\"subtotal\">";
    echo ($type=="PersonID"?$prev_name:$prev_groupfieldvalue)." - "._("Subtotal")."</td>\n";
    echo "<td colspan=\"2\" class=\"subtotal amount\">".$_SESSION['currency_mark']." ".
    number_format($subtotal,$_SESSION['currency_decimals']).
    "</td><td colspan=\"2\" style=\"border-right:0;border-bottom:0\"></td></tr></tfoot>\n";
  } else {
    echo "</tbody>\n";
  }
}
//close out table and show total
echo "</table><h3>"._("Total").": ".$_SESSION['currency_mark']." ".
number_format($total,$_SESSION['currency_decimals'])."</h3>\n";
footer();
?>
