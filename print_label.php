<?php
include("functions.php");
include("accesscontrol.php");
mysql_query("set names 'utf8'");

if (!$_POST['pid_list']) {
  die("There were no Person IDs passed.");
}
if (!$_POST['label_type']) {
  die("There was no Label Type passed.");
}

if (!$_POST['confirmed']) {
  /* CHECK FOR RECORDS WITH NO HOUSEHOLD OR ADDRESS */
  $sql = "SELECT person.PersonID, FullName, Furigana ".
      "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
      "WHERE person.PersonID IN (".$pid_list.") AND (person.HouseholdID IS NULL OR person.HouseholdID=0 ".
      "OR household.Address IS NULL OR household.Address='') ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
  $result = sqlquery_checked($sql);
  if ($num = mysql_numrows($result) > 0) {
    echo "<p>The following people have no address on record.</p>\n";
    echo "<p>You can click on each link here and edit them to add addresses, and then refresh this window, ";
    echo "or click 'Print the Rest' and the PDF of the existing addresses will be generated.</p>\n";
    echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\" name=\"confirmform\">";
    echo "<input type=\"hidden\" name=\"pid_list\" value=\"".$_POST['pid_list']."\">";
    echo "<input type=\"hidden\" name=\"label_type\" value=\"".$_POST['label_type']."\">";
    echo "<input type=\"hidden\" name=\"name_type\" value=\"".$_POST['name_type']."\">";
    echo "<input type=\"submit\" name=\"confirmed\" value=\"Print the Rest\"></form>\n";
    while ($row = mysql_fetch_object($result)) {
      echo "<br>&nbsp;&nbsp;&nbsp;";
      echo "<a href=\"individual.php?pid=".$row->PersonID."\" target=\"_BLANK\">";
      echo readable_name($row->FullName, $row->Furigana)."</a>\n";
    }
    exit;
  }
}

/* NOW GET THE REAL RECORDS */
if ($_POST['name_type'] == "label") {
  $sql = "SELECT DISTINCT person.PersonID, LabelName AS Name, ";
} else {
  $sql = "SELECT person.PersonID, IF(NonJapan, CONCAT(Title,' ',FullName), CONCAT(FullName,Title)) AS Name, ";
}
$sql .= "NonJapan, postalcode.*, Address FROM person ".
    "LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
    "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
    "WHERE person.PersonID IN (".$pid_list.") AND person.HouseholdID IS NOT NULL AND person.HouseholdID>0 ".
    "AND household.Address IS NOT NULL AND household.Address!='' ";
//$sql .= "ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
$sql .= "ORDER BY PersonID";
$result = sqlquery_checked($sql);

require('fpdf/mbfpdf.php');
$pdf = new MBFPDF('P', 'mm', 'A4');
$pdf->AddMBFont(PGOTHIC,'SJIS');
$pdf->SetMargins(0,0); 
$pdf->SetAutoPageBreak(false);

switch ($_POST['label_type']) {
case "Askul MA-506TW":
  $num_rows = 8;
  $num_cols = 3;
  $margin_top = 12.7;
  $margin_left = 0;
  $label_width = 70;
  $label_height = 33.9;
  $address_left = 5.2;
  $address_right = 5.2;
  $address_top = 6;
  $address_fontsize = 10;
  $address_height = $address_fontsize * 0.4;
  $name_left = 5.2;
  $name_right = 5.2;
  $name_fontsize = 12;
  $name_height = $name_fontsize * 0.4;
  $postalcode_wrap = 1;
  break;
case "Askul MA-506T":
  $num_rows = 8;
  $num_cols = 3;
  $margin_top = 12.7;
  $margin_left = 0;
  $label_width = 70;
  $label_height = 33.9;
  $address_left = 5.2;
  $address_right = 5.2;
  $address_top = 6;
  $address_fontsize = 10;
  $address_height = $address_fontsize * 0.4;
  $name_left = 5.2;
  $name_right = 5.2;
  $name_fontsize = 12;
  $name_height = $name_fontsize * 0.4;
  $postalcode_wrap = 0;
  break;
case "Kokuyo F7159":
  $num_rows = 8;
  $num_cols = 3;
  $margin_top = 12.9;
  $margin_left = 6.45;
  $label_width = 66.5;
  $label_height = 33.9;
  $address_left = 3;
  $address_right = 5.5;
  $address_top = 4;
  $address_fontsize = 10;
  $address_height = $address_fontsize * 0.4;
  $name_left = 3;
  $name_right = 5.5;
  $name_fontsize = 13;
  $name_height = $name_fontsize * 0.4;
  $postalcode_wrap = 0;
  break;
case "A-One 75312":
  $num_rows = 6;
  $num_cols = 2;
  $margin_top = 21.5;
  $margin_left = 19.3;
  $label_width = 87.6;
  $label_height = 42.3;
  $address_left = 4;
  $address_right = 7.8;
  $address_top = 4;
  $address_fontsize = 10;
  $address_height = $address_fontsize * 0.4;
  $name_left = 4;
  $name_right = 7.8;
  $name_fontsize = 13;
  $name_height = $name_fontsize * 0.4;
  $postalcode_wrap = 0;
  break;
default:
  die("Bad label type.");
}

$count = 0;
while ($row = mysql_fetch_object($result)) {
  if ($count == $num_rows*$num_cols) $count = 0;
  if ($count == 0) {
    $pdf->AddPage();
  }
  if ($row->NonJapan == 1) {
    $text = mb_convert_encoding($row->Name,"SJIS","UTF-8")."\n".trim(mb_convert_encoding($row->Address,"SJIS","UTF-8"));
    $pdf->SetFont(PGOTHIC, 'B', $address_fontsize);
    $pdf->SetXY($margin_left + ($count%$num_cols)*$label_width + $address_left, $margin_top + floor($count/$num_cols)*$label_height + $address_top);
    //$pdf->MultiCell($label_width-$address_left-$address_right, $address_height*(substr_count($text,"\n")+1), $text, 0, "L");
    $pdf->MultiCell($label_width-$address_left-$address_right, $address_height, $text, 0, "L");
  } else {
    $text = mb_convert_encoding("〒","SJIS","UTF-8").$row->PostalCode.($postalcode_wrap?"\n":" ").
    mb_convert_encoding($row->Prefecture,"SJIS","UTF-8").
    mb_convert_encoding($row->ShiKuCho,"SJIS","UTF-8")." ".
    trim(mb_convert_encoding($row->Address,"SJIS","UTF-8"))."\n";
    $pdf->SetFont(PGOTHIC, '', $address_fontsize);
    $pdf->SetXY($margin_left + ($count%$num_cols)*$label_width + $address_left, $margin_top + floor($count/$num_cols)*$label_height + $address_top);
    $pdf->MultiCell($label_width-$address_left-$address_right, $address_height, $text, 0, "L");
    $pdf->SetFont(PGOTHIC, 'B', $name_fontsize);
    $pdf->SetX($margin_left + ($count%$num_cols)*$label_width + $name_left);
    $pdf->MultiCell($label_width-$name_left-$name_right, $name_height, mb_convert_encoding($row->Name,"SJIS","UTF-8"), 0, "L");
  }
  
  $count++;
}
$pdf->Output();
?>