<?php
include("functions.php");
include("accesscontrol.php");

if (!$_POST['pid_list']) {
  die("There were no Person IDs passed.");
}
if (!$_POST['label_type']) {
  die("There was no Label Type passed.");
}

//$sql = "SELECT * FROM labelprint WHERE LabelType='".urldecode($_POST['label_type'])."'";
//$result = sqlquery_checked($sql);
//$print = mysql_fetch_object($result);
$print = new stdClass();
switch ($_POST['label_type']) {
case "Askul MA-506TW":
  $print->PaperSize = "a4";
  $print->NumRows = 8;
  $print->NumCols = 3;
  $print->PageMarginTop = 12.7;
  $print->PageMarginLeft = 0;
  $print->LabelWidth = 70;
  $print->LabelHeight = 33.9;
  $print->GutterX = 0;
  $print->GutterY = 0;
  $print->AddrMarginLeft = 5.2;
  $print->AddrMarginRight = 5.2;
  $print->AddrMarginTop = 6;
  $print->AddrPointSize = $print->NJAddrPointSize = 10;
  $print->NamePointSize = 12;
  $print->PostalcodeWrap = 1;
  break;
default:
  die("Bad label type: ".$_POST['label_type']);
}
/* some simplification */
$paperwidth = ($print->PaperSize=="letter" ? 215.9 : 210);
$paperheight = ($print->PaperSize=="letter" ? 279.4 : 297);
$addrwidth = $print->LabelWidth - $print->AddrMarginLeft - $print->AddrMarginRight;
$addrheight = $print->LabelHeight - 2*$print->AddrMarginTop;  //assume top and bottom equal (we hope not to hit the bottom anyway)
$offsetx = $print->LabelWidth + $print->GutterX;
$offsety = $print->LabelHeight + $print->GutterY;

if ($_POST['name_type'] == "label") {
  $sql = "SELECT DISTINCT p.PersonID, LabelName AS Name, ";
} else {
  $sql = "SELECT p.PersonID, IF(NonJapan, CONCAT(Title,' ',FullName), CONCAT(FullName,Title)) AS Name, ";
}
$sql .= "NonJapan, postalcode.*, Address FROM person p ".
    "LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
    "LEFT JOIN postalcode ON h.PostalCode=postalcode.PostalCode WHERE p.PersonID IN (".$pid_list.") ".
    "AND p.HouseholdID IS NOT NULL AND p.HouseholdID>0 AND h.Address IS NOT NULL AND h.Address!='' ".
    "AND (h.NonJapan=1 OR h.PostalCode!='') ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);

$fileroot = "/tmp/label".getmypid();  //the process ID

/* PREPARE ARRAYS FOR SPECIAL CHARACTERS */
$search_array = array("¡","£","©","®","¸","¿",
    "À","Á","Â","Ã","Ä","Å","Æ","Ç","È","É","Ê","Ë","Ì","Í","Î","Ï","Ñ",
    "Ò","Ó","Ô","Õ","Ö","Ø","Ù","Ú","Û","Ü","Ý","ß","à","á","â","ã","ä","å","æ","ç","è","é","ê","ë","ì","í","î","ï","ñ",
    "ò","ó","ô","õ","ö","ø","ù","ú","û","ü","ý","ÿ");
$replace_array = array("!`","\\pounds","\\textcopyright","\\textregistered","\\c{}","\\textcopyright",
    "\\`{A}","\\'{A}","\\^{A}","\\~{A}","\\\"{A}","\\AA{}","\\AE{}","\\c{C}","\\`{E}","\\'{E}","\\^{E}","\\\"{E}",
    "\\`{I}","\\'{I}","\\^{I}","\\\"{I}","\\~{N}",
    "\\`{O}","\\'{O}","\\^{O}","\\~{O}","\\\"{O}","\\O","\\`{U}","\\'{U}","\\^{U}","\\\"{U}","\\'{Y}","\\ss{}",
    "\\`{a}","\\'{a}","\\^{a}","\\~{a}","\\\"{a}","\\aa{}","\\ae{}","\\c{c}","\\`{e}","\\'{e}","\\^{e}","\\\"{e}",
    "\\`{i}","\\'{i}","\\^{i}","\\\"{i}","\\~{n}",
    "\\`{o}","\\'{o}","\\^{o}","\\~{o}","\\\"{o}","\\o","\\`{u}","\\'{u}","\\^{u}","\\\"{u}","\\'{y}","\\\"{y}");
//echo "<pre>".print_r($search_array,TRUE)."\n\n".print_r($replace_array,TRUE)."\n\n";
//echo str_replace($search_array, $replace_array, "Test")."</pre>";
//exit;
/* ALL OUTPUT FROM NOW GOES INTO THE FILE */
ob_start();
echo "\xEF\xBB\xBF";  //UTF-8 Byte Order Mark
?>
\documentclass[<?=$print->PaperSize?$print->PaperSize:'a4'?>paper]{ujarticle}
\usepackage[T1]{fontenc}
\usepackage{lmodern}

\pagestyle{empty}
\begin{document}
\setlength{\unitlength}{1mm}
\noindent
\raggedright
\sffamily
\gtfamily
\begin{picture}(<?=$paperwidth?>,<?=$paperheight?>)
<?
$count = 0;
while ($row = mysql_fetch_object($result)) {
  if ($count == $print->NumRows*$print->NumCols) {
    $count = 0;
    echo "\end{picture}\clearpage\n";
  }
  $posx = $print->PageMarginLeft + ($count%$print->NumCols)*$print->LabelWidth + $print->AddrMarginLeft;
  $posy = $paperheight - ($print->PageMarginTop + floor($count/$print->NumCols)*$print->LabelHeight + $print->AddrMarginTop);
?>
\put(<?=$posx?>,<?=$posy?>){%
\makebox(<?=$addrwidth?>,<?=$addrheight?>)[lt]{
\begin{minipage}[t][<?=$addrheight?>]{<?=$addrwidth?>mm}%
<?
  if ($row->NonJapan == 1) {
?>
%% NON-JAPAN ADDRESS %%
\fontsize{<?=$print->NJAddrPointSize?>}{<?=$print->NJAddrPointSize*1.1?>}\selectfont
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Name))."\n\n"?>
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Address))."\n"?>
<?
  } else {  //Japanese address
?>
%% JAPAN ADDRESS %%
\fontsize{<?=$print->AddrPointSize?>}{<?=$print->AddrPointSize*1.2?>}\selectfont
\hangindent=10mm
<?="〒".$row->PostalCode." ".$row->Prefecture.$row->ShiKuCho.preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",$row->Address)."\n"?>

\vspace{1ex}
\fontsize{<?=$print->NamePointSize?>}{<?=$print->NamePointSize*1.2?>}\selectfont
\hangindent=10mm
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Name))."\n"?>
<?
  }  //end Japanese address
?>
\end{minipage}}}
<?
}  //end while looping through addresses
?>
\end{picture}
\clearpage
\end{document}
<?
file_put_contents($fileroot.".tex",ob_get_contents());
ob_end_clean();

// RUN TEX COMMANDS TO MAKE PDF

exec("cd /tmp;/usr/local/texlive/2011/bin/x86_64-linux/uplatex -interaction=batchmode --output-directory=/tmp $fileroot", $output, $return);
//exec("cd /tmp;uplatex -interaction=batchmode --output-directory=/tmp $fileroot", $output, $return);
if (!is_file("$fileroot.dvi")) {
  die("Error processing '$fileroot.tex':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
//unlink("$fileroot.tex");
exec("cd /tmp;/usr/local/texlive/2011/bin/x86_64-linux/dvipdfmx $fileroot", $output, $return);
//unlink("$fileroot.dvi");
if (!is_file("$fileroot.pdf")) {
  die("Error processing '$fileroot.dvi':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}

// DELIVER PDF CONTENT TO BROWSER

header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="labels_'.date('Y-m-d').'.pdf"');
header("Content-Transfer-Encoding: binary");
@readfile("$fileroot.pdf");
?>