<?php
include("functions.php");
include("accesscontrol.php");

$sql = "SELECT * FROM addrprint WHERE AddrPrintName='".urldecode($addr_print_name)."'";
$result = sqlquery_checked($sql);
$print = mysqli_fetch_object($result);
$block_height = $print->PaperHeight - $print->PaperTopMargin - $print->PaperBottomMargin - 2;  //just a touch less
$block_width = $print->PaperWidth - $print->PaperLeftMargin - $print->PaperRightMargin - 2;  //just a touch less
echo <<<HEADER
<html>
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8">
<title></title>
<style>
.firstpage {
    position: relative;
    display: block;
    height: {$block_height}mm;
    width: {$block_width}mm;
}
.newpage {
    page-break-before: always;
    position: relative;
    display: block;
    height: {$block_height}mm;
    width: {$block_width}mm;
}
.envelope {
    page-break-after: always;
    position: relative;
    display: block;
    height: {$block_height}mm;
    width: {$block_width}mm;
}
.postalcode {
    position: relative;
    float: left;
    display: block;
    left: {$print->PCLeftMargin}mm;
    top: {$print->PCTopMargin}mm;
    font-family: "{$print->Font}";
    font-size: {$print->PCPointSize}px;    
    line-height: {$print->PCSpacing}mm;
}
.postalcode span {
    margin-bottom: {$print->PCExtraSpace}mm;
    display: block;
}
.address {
    position: relative;
    float: left;
    display: block;
    left: {$print->AddrLeftMargin}mm;
    top: {$print->AddrTopMargin}mm;
    font-family: "{$print->Font}";
    font-size: {$print->AddrPointSize}px;
}
.name {
    font-weight: bold;
    font-size: {$print->NamePointSize}px;
}
.returnaddress {
    position: absolute;
    display: block;
    right: 0mm;
    bottom: 0mm;
}
.nj_returnaddress {
    position: relative;
    float: left;
    display: block;
    left: {$print->NJRetAddrLeftMargin}mm;
    top: {$print->NJRetAddrTopMargin}mm;
    font-family: "{$print->NJFont}";
    font-size: {$print->NJRetAddrPointSize}px;
}
.nj_address {
    position: relative;
    float: left;
    display: block;
    left: {$print->NJAddrLeftMargin}mm;
    top: {$print->NJAddrTopMargin}mm;
    font-family: "{$print->NJFont}";
    font-size: {$print->NJAddrPointSize}px;
}
</style>
</head>
<body bgcolor="#ffffff">
HEADER;

// build array to use for postal code digits
$digit_array = array ("&#xFF10;", "&#xFF11;", "&#xFF12;", "&#xFF13;", "&#xFF14;", "&#xFF15;",
"&#xFF16;", "&#xFF17;", "&#xFF18;", "&#xFF19;");

$num_no_addr = 0;
$list_no_addr = "";
$first = 1;

if (!$pid_list || $pid_list == "") {
  echo "No list of Person ID's passed from previous screen.";
  exit;
}

/* CHECK FOR RECORDS WITH NO HOUSEHOLD OR ADDRESS */

$sql = "SELECT person.PersonID, FullName, Furigana ".
      "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
      "WHERE person.PersonID IN (".$pid_list.") AND (person.HouseholdID IS NULL OR person.HouseholdID=0 ".
      "OR household.Address IS NULL OR household.Address='') ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);
if ($num = mysqli_num_rows($result) > 0) {
  echo "<p>The following people have no address on record.</p>\n";
  echo "<p>You can (a) click on each link here and edit them to add addresses, and then refresh this window, ";
  echo "or (b) close this window, remove the names in Multi-Select and click \"<b>Print Addresses</b>\" again, ";
  echo "or (c) print what you have now (skip the first page when printing).\n";
  while ($row = mysqli_fetch_object($result)) {
    echo "<br>&nbsp;&nbsp;&nbsp;";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=_BLANK>";
    echo readable_name($row->FullName, $row->Furigana)."</a>\n";
  }
  $first = 0;  // to force a page break before the rest of the content
}

/* NOW GET THE REAL RECORDS */

if ($name_type == "label") {
  $sql = "SELECT DISTINCT person.HouseholdID, LabelName AS Name, ";
} else {
  $sql = "SELECT person.PersonID, IF(NonJapan, CONCAT(Title,' ',FullName), CONCAT(FullName,Title)) AS Name, ";
}
$sql .= "NonJapan, postalcode.*, Address FROM person ".
    "LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
    "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
    "WHERE person.PersonID IN (".$pid_list.") AND person.HouseholdID IS NOT NULL AND person.HouseholdID>0 ".
    "AND household.Address IS NOT NULL AND household.Address!='' ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
  if ($row->NonJapan == 1) {
  
    $name = db2table($row->Name);
    $address = db2table($row->Address);
/*    if ($first) {
      echo "<div class=\"firstpage\">\n";
      $first = 0;
    } else {
      echo "<div class=\"newpage\">\n";
    } */
    echo <<<NONJAPAN
  <div class="envelope">
    <div class="nj_returnaddress">
      {$print->NJRetAddrContent}
    </div>
    <div class="nj_address">
      {$name}<br>
      {$address}<br>
    </div><span style=display:none">&nbsp;</span>
  </div>

NONJAPAN;

  } else {  //Japanese address
  
    $name = db2table($row->Name);
    $address = $row->Prefecture.$row->ShiKuCho." ".db2table($row->Address);
/*    if ($first) {
      echo "<div class=\"firstpage\">\n";
      $first = 0;
    } else {
      echo "<div class=\"newpage\">\n";
    } */
    echo "  <div class=\"envelope\">\n";
    echo "    <div class=\"postalcode\">\n";
    echo "      ".$digit_array[substr($row->PostalCode,7,1)]."<br />\n";
    echo "      ".$digit_array[substr($row->PostalCode,6,1)]."<br />\n";
    echo "      ".$digit_array[substr($row->PostalCode,5,1)]."<br />\n";
    echo "      <span>".$digit_array[substr($row->PostalCode,4,1)]."</span>\n";
    echo "      ".$digit_array[substr($row->PostalCode,2,1)]."<br />\n";
    echo "      ".$digit_array[substr($row->PostalCode,1,1)]."<br />\n";
    echo "      ".$digit_array[substr($row->PostalCode,0,1)]."<br />\n";
    echo "    "."</div>\n";

    echo <<<JAPAN
    <div class="address">
      {$address}<br />&nbsp;<br />
      <span class="name">{$name} </span>
    </div> 
    <div class="returnaddress">
      {$print->RetAddrContent}
    </div><span style=display:none">&nbsp;</span>
  </div>

JAPAN;
  }
}

?>
</body>
</html>
