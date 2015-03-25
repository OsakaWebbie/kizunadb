<?php
include("functions.php");
include("accesscontrol.php");

if (!$_POST['pid_list']) {
  die("There were no Person IDs passed.");
}
if (!$_POST['addr_print_name']) {
  die("There was no layout type passed.");
}

$sql = "SELECT * FROM addrprint WHERE AddrPrintName='".urldecode($_POST['addr_print_name'])."'";
$result = sqlquery_checked($sql);
$print = mysql_fetch_object($result);

$sql = "SELECT ".($_POST['name_type']=="label" ? "DISTINCT LabelName" :
"IF(NonJapan, CONCAT(Title,' ',FullName), CONCAT(FullName,Title))")." AS Name, NonJapan, postalcode.*, Address ".
"FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
"LEFT JOIN postalcode ON h.PostalCode=postalcode.PostalCode WHERE p.PersonID IN (".$pid_list.") ".
"AND p.HouseholdID IS NOT NULL AND p.HouseholdID>0 AND h.Address IS NOT NULL AND h.Address!='' ".
"AND (h.NonJapan=1 OR h.PostalCode!='') ORDER BY ".($_POST['nj_separate']=="yes" ? "NonJapan," : "").
"FIND_IN_SET(PersonID,'".$pid_list."')";
$result = sqlquery_checked($sql);

$fileroot = "/tmp/addr".getmypid();

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
//echo "<pre>".print_r($search_array,TRUE)."\n\n".print_r($replace_array,TRUE)."\n\n";
//echo str_replace($search_array, $replace_array, "Test")."</pre>";
//exit;
/* ALL OUTPUT FROM NOW GOES INTO THE FILE */
ob_start();
echo "\xEF\xBB\xBF";  //UTF-8 Byte Order Mark
?>
\documentclass{ujarticle}
\usepackage{plext}
\usepackage[uplatex]{otf}
\usepackage[T1]{fontenc}
\usepackage{lmodern}
\usepackage[paperwidth=<?=$print->PaperWidth?>mm,paperheight=<?=$print->PaperHeight?>mm,margin=0mm]{geometry}
\usepackage{verbatim}
\usepackage{lscape}
\usepackage{textpos}
\usepackage[dvipdfmx]{graphicx}
\pagestyle{empty}
\begin{document}
\setlength{\unitlength}{1mm}
\noindent
\raggedright
\sffamily
\gtfamily
<?
while ($row = mysql_fetch_object($result)) {
  if ($row->NonJapan == 1) {
?>
%% NON-JAPAN PAGE %%
\begin{picture}(<?=$print->PaperWidth?>,<?=$print->PaperHeight?>)(3,3)
%% Return Address %%
\put(<?=$print->NJRetAddrLeftMargin?>,<?=$print->NJRetAddrTopMargin?>){%
<?=$print->NJRetAddrContent?>}
%% Address %%
\put(<?=$print->PaperLeftMargin?>,<?=$print->NJAddrPositionY-$print->NJAddrHeight?>)%
{\makebox(<?=$print->NJAddrPositionX-$print->PaperLeftMargin?>,<?=$print->NJAddrHeight?>)[rt]{
\begin{minipage}<t>[t]{<?=$print->NJAddrHeight?>mm}%
\fontsize{<?=$print->NJAddrPointSize?>}{<?=$print->NJAddrPointSize*1.1?>}\selectfont
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Name))."\n\n"?>
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Address))."\n"?>
\end{minipage}}}
\end{picture}
\clearpage  
<?
  } else {  //Japanese address
?>
%% JAPAN PAGE %%
\begin{picture}(<?=$print->PaperWidth?>,<?=$print->PaperHeight?>)(3,3)
<?
    if ($_POST['po_stamp']=='yes') {  //Post Office stamp requested
?>
\put(<?=$print->PaperLeftMargin?>,<?=$print->PCTopMargin-18?>){%
\includegraphics[bb=0 0 300 300,width=28.5mm]{/var/www/kizunadb/codebase/graphics/po_stamp.png}}
<?
    }  //end if Post Office stamp requested
?>
<?
    if (strlen($row->PostalCode)>7) {  //PostalCode is complete
?>
\fontsize{<?=$print->PCPointSize?>}{<?=$print->PCPointSize*1.2?>}\selectfont
\put(<?=$print->PCLeftMargin?>,<?=$print->PCTopMargin?>){<?=$row->PostalCode[0]?>}
\put(<?=$print->PCLeftMargin+$print->PCSpacing?>,<?=$print->PCTopMargin?>){<?=$row->PostalCode[1]?>}
\put(<?=$print->PCLeftMargin+$print->PCSpacing*2?>,<?=$print->PCTopMargin?>){<?=$row->PostalCode[2]?>}
\put(<?=$print->PCLeftMargin+$print->PCExtraSpace+$print->PCSpacing*3?>,<?=$print->PCTopMargin?>){<?=$row->PostalCode[4]?>}
\put(<?=$print->PCLeftMargin+$print->PCExtraSpace+$print->PCSpacing*4?>,<?=$print->PCTopMargin?>){<?=$row->PostalCode[5]?>}
\put(<?=$print->PCLeftMargin+$print->PCExtraSpace+$print->PCSpacing*5?>,<?=$print->PCTopMargin?>){<?=$row->PostalCode[6]?>}
\put(<?=$print->PCLeftMargin+$print->PCExtraSpace+$print->PCSpacing*6?>,<?=$print->PCTopMargin?>){<?=$row->PostalCode[7]?>}
<?
    }  //end if PostalCode is complete
?>
%% Address and Name %%
\put(<?=$print->PaperLeftMargin?>,<?=$print->AddrPositionY-$print->AddrHeight?>){%
\makebox(<?=$print->AddrPositionX-$print->PaperLeftMargin?>,<?=$print->AddrHeight?>)[rt]{%
\begin{minipage}<t>[t]{<?=$print->AddrHeight?>mm}
\fontsize{<?=$print->AddrPointSize?>}{<?=$print->AddrPointSize*1.2?>}\selectfont
\hangindent=10mm
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",$row->Prefecture.$row->ShiKuCho.$row->Address)."\n"?>

\vspace{1ex}
\fontsize{<?=$print->NamePointSize?>}{<?=$print->NamePointSize*1.2?>}\selectfont
\hangindent=10mm
<?=preg_replace("\r\n|\r|\n","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Name))."\n"?>
\end{minipage}}}
%% Return Address %%
\put(<?=$print->PaperLeftMargin?>,<?=$print->PaperBottomMargin?>){%
<?=$print->RetAddrContent?>}
\end{picture}
\clearpage
<?
  }  //end Japanese address
}  //end while looping through addresses
?>
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
header('Content-Disposition: attachment; filename="envelopes_'.date('Y-m-d').'.pdf"');
header("Content-Transfer-Encoding: binary");
@readfile("$fileroot.pdf");
?>