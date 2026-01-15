<?php
include("functions.php");
include("accesscontrol.php");

if (!$_POST['pid_list']) {
  die("There were no Person IDs passed.");
}
if (!$_POST['label_type']) {
  die("There was no Label Type passed.");
}

$sql = "SELECT * FROM labelprint WHERE LabelType='".urldecode($_POST['label_type'])."'";
$result = sqlquery_checked($sql);
$print = mysqli_fetch_object($result);

/* some simplification */
$paperwidth = ($print->PaperSize=="letter" ? 215.9 : 210);
$paperheight = ($print->PaperSize=="letter" ? 279.4 : 297);
$addrwidth = $print->LabelWidth - $print->AddrMarginLeft - $print->AddrMarginRight;
$offsetx = $print->LabelWidth + $print->GutterX;
$offsety = $print->LabelHeight + $print->GutterY;
$hanging = floor($print->AddrPointSize * 0.7);

$sql = "SELECT ".($_POST['name_type']=="label" ? "DISTINCT LabelName" :
"IF(NonJapan, CONCAT(Title,' ',FullName), CONCAT(FullName,Title))")." AS Name, NonJapan, postalcode.*, Address ".
"FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
"LEFT JOIN postalcode ON h.PostalCode=postalcode.PostalCode WHERE p.PersonID IN (".$pid_list.") ".
"AND p.HouseholdID>0 AND h.Address!='' ".
"AND (h.NonJapan=1 OR h.PostalCode!='') ORDER BY ".($_POST['nj_separate']=="yes" ? "NonJapan," : "").
"FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);

$tmppath = '/var/www/tmp/';
$fileroot = CLIENT.'-'.$_SESSION['userid'].'-label-'.date('His');

/* PREPARE ARRAYS FOR SPECIAL CHARACTERS */
$search_array = array("&","¡","£","©","®","¸","¿",
    "À","Á","Â","Ã","Ä","Å","Æ","Ç","È","É","Ê","Ë","Ì","Í","Î","Ï","Ñ",
    "Ò","Ó","Ô","Õ","Ö","Ø","Ù","Ú","Û","Ü","Ý","ß","à","á","â","ã","ä","å","æ","ç","è","é","ê","ë","ì","í","î","ï","ñ",
    "ò","ó","ô","õ","ö","ø","ù","ú","û","ü","ý","ÿ");
$replace_array = array("\\&","!`","\\pounds","\\textcopyright","\\textregistered","\\c{}","\\textcopyright",
    "\\`{A}","\\'{A}","\\^{A}","\\~{A}","\\\"{A}","\\AA{}","\\AE{}","\\c{C}","\\`{E}","\\'{E}","\\^{E}","\\\"{E}",
    "\\`{I}","\\'{I}","\\^{I}","\\\"{I}","\\~{N}",
    "\\`{O}","\\'{O}","\\^{O}","\\~{O}","\\\"{O}","\\O","\\`{U}","\\'{U}","\\^{U}","\\\"{U}","\\'{Y}","\\ss{}",
    "\\`{a}","\\'{a}","\\^{a}","\\~{a}","\\\"{a}","\\aa{}","\\ae{}","\\c{c}","\\`{e}","\\'{e}","\\^{e}","\\\"{e}",
    "\\`{i}","\\'{i}","\\^{i}","\\\"{i}","\\~{n}",
    "\\`{o}","\\'{o}","\\^{o}","\\~{o}","\\\"{o}","\\o","\\`{u}","\\'{u}","\\^{u}","\\\"{u}","\\'{y}","\\\"{y}");
/* ALL OUTPUT FROM NOW GOES INTO THE FILE */
ob_start();
echo "\xEF\xBB\xBF";  //UTF-8 Byte Order Mark
?>
\documentclass[<?=$print->PaperSize?$print->PaperSize:'a4'?>paper]{ujarticle}
\usepackage[T1]{fontenc}
\usepackage{lmodern}
\usepackage[absolute]{textpos}

\textblockorigin{<?=$print->PageMarginLeft?>mm}{<?=$print->PageMarginTop?>mm}
\pagestyle{empty}
\begin{document}
\setlength{\unitlength}{1mm}
\setlength{\TPHorizModule}{1mm}
\setlength{\TPVertModule}{1mm}
\noindent
\raggedright
\sffamily
\gtfamily
<?php
$count = 0;
while ($row = mysqli_fetch_object($result)) {
  if ($count == $print->NumRows*$print->NumCols) {
    $count = 0;
    echo "\\end{picture}\\clearpage\n";
  }
  $posx = ($count%$print->NumCols)*$print->LabelWidth + $print->AddrMarginLeft;
  $posy = floor($count/$print->NumCols)*$print->LabelHeight + $print->LabelHeight/2;
?>
\begin{textblock}{<?=$addrwidth?>}[0,0.5](<?=$posx?>,<?=$posy?>)
<?php
  if ($row->NonJapan == 1) {
?>
%% NON-JAPAN ADDRESS %%
\fontsize{<?=$print->NJAddrPointSize?>}{<?=$print->NJAddrPointSize*1.1?>}\selectfont
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=".$hanging."mm\n",str_replace($search_array,$replace_array,$row->Name))."\n\n"?>
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=".$hanging."mm\n",str_replace($search_array,$replace_array,$row->Address))."\n"?>
<?php
  } else {  //Japanese address
?>
%% JAPAN ADDRESS %%
\fontsize{<?=$print->AddrPointSize?>}{<?=$print->AddrPointSize*1.2?>}\selectfont
\hangindent=<?=$hanging?>mm
<?="〒".$row->PostalCode.($_POST['wrap_pc']?"\n\n\\hangindent=".$hanging."mm\n":" ").$row->Prefecture.$row->ShiKuCho.preg_replace("\r\n|\r|\n","\n\n\\hangindent=".$hanging."mm\n",$row->Address)."\n"?>

\vspace{1ex}
\fontsize{<?=$print->NamePointSize?>}{<?=$print->NamePointSize*1.2?>}\selectfont
\hangindent=<?=$hanging?>mm
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=".$hanging."mm\n",str_replace($search_array,$replace_array,$row->Name))."\n"?>
<?php
  }  //end Japanese address
?>
\end{textblock}
<?php
  $count++;
}  //end while looping through addresses
?>
\null\newpage
\end{document}
<?php
file_put_contents($tmppath.$fileroot.".tex",ob_get_contents());
ob_end_clean();

// RUN TEX COMMANDS TO MAKE PDF

if (is_file("/usr/bin/uplatex")) {
  $commandpath = "/usr/bin/";
} elseif (is_file("/usr/local/bin/uplatex")) {
  $commandpath = "/usr/local/bin/";
} else {
  die("Error: cannot find needed commands (uplatex and dvipdfmx) in /usr/bin/ or /usr/local/bin/.");
}
exec("cd $tmppath;{$commandpath}uplatex -interaction=batchmode --output-directory=$tmppath $fileroot", $output, $return);
if (!is_file("$tmppath$fileroot.dvi")) {
  die("Error processing '$tmppath$fileroot.tex':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
//unlink("$tmppath$fileroot.tex");
exec("cd $tmppath;{$commandpath}dvipdfmx $fileroot", $output, $return);
//unlink("$tmppath$fileroot.dvi");
if (!is_file("$tmppath$fileroot.pdf")) {
  die("Error processing '$tmppath$fileroot.dvi':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
/*exec("cd $tmppath;/usr/local/bin/uplatex -interaction=batchmode --output-directory=$tmppath $fileroot", $output, $return);
if (!is_file("$tmppath$fileroot.dvi")) {
  die("Error processing '$tmppath$fileroot.tex':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
//unlink("$tmppath$fileroot.tex");
exec("cd $tmppath;/usr/local/bin/dvipdfmx $fileroot", $output, $return);
//unlink("$tmppath$fileroot.dvi");
if (!is_file("$tmppath$fileroot.pdf")) {
  die("Error processing '$tmppath$fileroot.dvi':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}*/

// DELIVER PDF CONTENT TO BROWSER

header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="labels_'.date('Y-m-d').'.pdf"');
header("Content-Transfer-Encoding: binary");
@readfile("$tmppath$fileroot.pdf");

/*
exec("cd /tmp;/usr/local/texlive/2011/bin/x86_64-linux/uplatex -interaction=batchmode --output-directory=/tmp $fileroot", $output, $return);
if (!is_file("$fileroot.dvi")) {
  die("Error processing '$fileroot.tex':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
exec("cd /tmp;/usr/local/texlive/2011/bin/x86_64-linux/dvipdfmx $fileroot", $output, $return);
if (!is_file("$fileroot.pdf")) {
  die("Error processing '$fileroot.dvi':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}

// DELIVER PDF CONTENT TO BROWSER

header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="labels_'.date('Y-m-d').'.pdf"');
header("Content-Transfer-Encoding: binary");
@readfile("$fileroot.pdf");
*/
?>