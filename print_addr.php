<?php
include("functions.php");
include("accesscontrol.php");

if (!$_POST['pid_list']) {
  die(_("There were no Person IDs passed."));
}
if (!$_POST['addr_print_name']) {
  die(_("There was no layout type passed."));
}

$sql = "SELECT * FROM addrprint WHERE AddrPrintName='".urldecode($_POST['addr_print_name'])."'";
$result = sqlquery_checked($sql);
$print = mysqli_fetch_object($result);

// check for custom code - each file must have a function with the same name to be called inside the loop
if (!empty($print->Custom)) {
  $customfiles = explode(',', $print->Custom);
  foreach ($customfiles as $key=>$file) {
    if (file_exists(CLIENT_PATH . '/dashboard/' . $file . '.php')) {
      include(CLIENT_PATH . '/dashboard/' . $file . '.php');
    } else {
      unset($customfiles[$key]);
    }
  }
}
$sql = "SELECT ".($_POST['name_type']=="label" ? "DISTINCT LabelName" :
"IF(NonJapan, CONCAT(Title,' ',FullName), CONCAT(FullName,Title))")." AS Name, ".
    "NonJapan, postalcode.*, Address, PersonID, h.HouseholdID ".
"FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
"LEFT JOIN postalcode ON h.PostalCode=postalcode.PostalCode WHERE p.PersonID IN (".$_POST['pid_list'].") ".
    "AND p.HouseholdID IS NOT NULL AND p.HouseholdID>0 AND h.Address IS NOT NULL AND h.Address!='' ".
    "AND (h.NonJapan=1 OR h.PostalCode!='') ".
    "ORDER BY ".($_POST['nj_separate']=="yes" ? "NonJapan," : "")."FIND_IN_SET(PersonID,'".$_POST['pid_list']."')";
$result = sqlquery_checked($sql);
if (mysqli_num_rows($result)==0) {
  die(_("No addresses to print. Just close this tab and check your selection."));
}

$tmppath = '/var/www/tmp/';
$fileroot = CLIENT.'-'.$_SESSION['userid'].'-env-'.date('His');

/* PREPARE ARRAYS FOR ADDRESS NUMBERS */
$number_array = array("0","1","2","3","4","5","6","7","8","9","-");
$kanji_array = array("〇","一","二","三","四","五","六","七","八","九","の");
/* PREPARE ARRAYS FOR SPECIAL CHARACTERS */
$search_array = array("&","¡","£","©","®","¸","¿",
    "À","Á","Â","Ã","Ä","Å","Æ","Ç","È","É","Ê","Ë","Ì","Í","Î","Ï","Ñ",
    "Ò","Ó","Ô","Õ","Ö","Ø","Ù","Ú","Û","Ü","Ý","ß","à","á","â","ã","ä","å","æ","ç","è","é","ê","ë","ì","í","î","ï","ñ",
    "ò","ó","ô","õ","ö","ø","ù","ú","û","ü","ý","ÿ",'御中','先生');
$replace_array = array("\\&","!`","\\pounds","\\textcopyright","\\textregistered","\\c{}","\\textcopyright",
    "\\`{A}","\\'{A}","\\^{A}","\\~{A}","\\\"{A}","\\AA{}","\\AE{}","\\c{C}","\\`{E}","\\'{E}","\\^{E}","\\\"{E}",
    "\\`{I}","\\'{I}","\\^{I}","\\\"{I}","\\~{N}",
    "\\`{O}","\\'{O}","\\^{O}","\\~{O}","\\\"{O}","\\O","\\`{U}","\\'{U}","\\^{U}","\\\"{U}","\\'{Y}","\\ss{}",
    "\\`{a}","\\'{a}","\\^{a}","\\~{a}","\\\"{a}","\\aa{}","\\ae{}","\\c{c}","\\`{e}","\\'{e}","\\^{e}","\\\"{e}",
    "\\`{i}","\\'{i}","\\^{i}","\\\"{i}","\\~{n}",
    "\\`{o}","\\'{o}","\\^{o}","\\~{o}","\\\"{o}","\\o","\\`{u}","\\'{u}","\\^{u}","\\\"{u}","\\'{y}","\\\"{y}",'\mbox{御中}','\mbox{先生}');
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
\graphicspath {{<?=getcwd()?>/graphics/}}
\begin{document}
\setlength{\unitlength}{1mm}
\noindent
\raggedright
\sffamily
\gtfamily
<?php
while ($row = mysqli_fetch_object($result)) {

  // call function(s) in custom code
  if (!empty($customfiles)) {
    foreach ($customfiles as $file) {
      if (is_callable($file.'_loop_start')) {
        $function = $file.'_loop_start';
        if ($function() == 'SKIP') continue 2;
      }
    }
  }
?>
\begin{picture}(<?=$print->PaperWidth?>,<?=$print->PaperHeight?>)(3,3)
<?php
  if ($row->NonJapan == 1) {
?>
%% NON-JAPAN PAGE %%
%% Return Address %%
\put(<?=$print->NJRetAddrLeftMargin?>,<?=$print->NJRetAddrTopMargin?>){%
<?=$print->NJRetAddrContent?>}
%% Address %%
\put(<?=$print->PaperLeftMargin?>,<?=$print->NJAddrPositionY-$print->NJAddrHeight?>)%
{\makebox(<?=$print->NJAddrPositionX-$print->PaperLeftMargin?>,<?=$print->NJAddrHeight?>)[rt]{
\begin{minipage}<t>[t]{<?=$print->NJAddrHeight?>mm}%
\fontsize{<?=$print->NJAddrPointSize?>}{<?=$print->NJAddrPointSize*1.1?>}\selectfont
<?=preg_replace("/\r\n|\r|\n/","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Name))."\n\n"?>
<?=preg_replace("/\r\n|\r|\n/","\n\n\\hangindent=10mm\n",str_replace($search_array,$replace_array,$row->Address))."\n"?>
\end{minipage}}}
<?php
  } else {  //Japanese address
?>
%% JAPAN PAGE %%
<?php
    if ($_POST['po_stamp']!='none') {  //Post Office stamp requested
      if ($_POST['po_stamp']=='betsunou') {
?>
\put(<?=$print->PaperLeftMargin?>,<?=$print->PCTopMargin-18?>){%
\includegraphics[bb=0 0 520 452,width=30mm]{po_betsunou.png}}
<?php
      } elseif ($_POST['po_stamp']=='yuumail_betsunou') {
?>
\put(<?=$print->PaperLeftMargin?>,<?=$print->PCTopMargin-22?>){%
\includegraphics[bb=0 0 520 600,width=30mm]{po_yuumail_betsunou.png}}
<?php
      } elseif ($_POST['po_stamp']=='kounou') {
?>
\put(<?=$print->PaperLeftMargin?>,<?=$print->PCTopMargin-18?>){%
\includegraphics[bb=0 0 520 452,width=30mm]{po_kounou.png}}
<?php
      } elseif ($_POST['po_stamp']=='yuumail_kounou') {
?>
\put(<?=$print->PaperLeftMargin?>,<?=$print->PCTopMargin-22?>){%
\includegraphics[bb=0 0 520 600,width=30mm]{po_yuumail_kounou.png}}
<?php
      }
    }  //end if Post Office stamp requested
?>
<?php
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
<?php
    }  //end if PostalCode is complete
    
    if ($print->Tategaki==1) {
?>
%% Address %%
\put(<?=$print->PaperLeftMargin?>,<?=$print->AddrPositionY-$print->AddrLineLength?>){%
\makebox(<?=$print->AddrPositionX-$print->PaperLeftMargin?>,<?=$print->AddrLineLength?>)[rt]{%
\begin{minipage}<t>[t]{<?=$print->AddrLineLength?>mm}
\fontsize{<?=$print->AddrPointSize?>}{<?=$print->AddrPointSize*1.2?>}\selectfont
\hangindent=<?=($print->AddrLineLength*0.4)?>mm
\mbox{<?=$row->Prefecture.$row->ShiKuCho?>}
\mbox{<?=preg_replace("/\r\n|\r|\n/","}\n\n\\hangindent=".($print->AddrLineLength*0.4)."mm\n\\mbox{",
(!empty($_POST['kanji_numbers']) ? str_replace($number_array,$kanji_array,$row->Address) : $row->Address))?>}
\end{minipage}}}

%% Name %%
\put(<?=$print->NamePositionX-($print->NameWidth/2)?>,<?=$print->NamePositionY-$print->NameLineLength?>){%
\makebox(<?=$print->NameWidth?>,<?=$print->NameLineLength?>)[ct]{%
\begin{minipage}<t>[t]{<?=$print->NameLineLength?>mm}
\fontsize{<?=$print->NamePointSize?>}{<?=$print->NamePointSize*1.2?>}\selectfont
\hangindent=<?=($print->NameLineLength*0.1)?>mm
<?=preg_replace("/\r\n|\r|\n/","\n\n\\hangindent=".($print->NameLineLength*0.1)."mm\n",
str_replace($search_array,$replace_array,$row->Name))?>
\end{minipage}}}
<?php
    } else { //yokogaki
      $addrheight = $print->AddrPositionY-$print->NamePositionX;
      $nameheight = $print->PaperHeight/2; //arbitrary, just because I need a number
?>
%% Address %%
\put(<?=$print->AddrPositionX?>,<?=$print->AddrPositionY-$addrheight?>){%
\makebox(<?=$print->AddrLineLength?>,<?=$addrheight?>)[rt]{%
\begin{minipage}<y>[t]{<?=$print->AddrLineLength?>mm}
\fontsize{<?=$print->AddrPointSize?>}{<?=$print->AddrPointSize*1.2?>}\selectfont
\hangindent=<?=($print->AddrLineLength*0.2)?>mm
\mbox{<?=$row->Prefecture.$row->ShiKuCho?>}
\mbox{<?=preg_replace("/\r\n|\r|\n/","}\n\n\\hangindent=".($print->AddrLineLength*0.2)."mm\n\\mbox{",
(!empty($_POST['kanji_numbers']) ? str_replace($number_array,$kanji_array,$row->Address) : $row->Address))?>}
\end{minipage}}}

%% Name %%
\put(<?=$print->NamePositionX?>,<?=$print->NamePositionY-$nameheight?>){%
\makebox(<?=$print->NameLineLength?>,<?=$nameheight?>)[rt]{%
\begin{minipage}<y>[t]{<?=$print->NameLineLength?>mm}
\fontsize{<?=$print->NamePointSize?>}{<?=$print->NamePointSize*1.2?>}\selectfont
\hangindent=<?=($print->NameLineLength*0.1)?>mm
<?=preg_replace("/\r\n|\r|\n/","\n\n\\hangindent=".($print->NameLineLength*0.1)."mm\n",
str_replace($search_array,$replace_array,$row->Name))?>
\end{minipage}}}
<?php
    }
?>
%% Return Address %%
\put(<?=$print->PaperLeftMargin?>,<?=$print->PaperBottomMargin?>){%
<?=$print->RetAddrContent?>}
<?php
  }  //end Japanese address

  // call function(s) in custom code
  if (!empty($customfiles)) {
    foreach ($customfiles as $file) {
      if (is_callable($file.'_page_end')) {
        $function = $file.'_page_end';
        $function();
      }
    }
  }

?>
\end{picture}
\clearpage
<?php
}  //end while looping through addresses
?>
\end{document}
<?php
file_put_contents($tmppath.$fileroot.".tex",ob_get_contents());
ob_end_clean();

// RUN TEX COMMANDS TO MAKE PDF

exec("cd $tmppath;/usr/local/bin/uplatex -interaction=batchmode --output-directory=$tmppath $fileroot", $output, $return);
if (!is_file("$tmppath$fileroot.dvi")) {
  die("Error processing '$tmppath$fileroot.tex':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}
//unlink("$tmppath$fileroot.tex");
exec("cd $tmppath;/usr/local/bin/dvipdfmx $fileroot", $output, $return);
//unlink("$tmppath$fileroot.dvi");
if (!is_file("$tmppath$fileroot.pdf")) {
  die("Error processing '$tmppath$fileroot.dvi':<br /><br /><pre>".print_r($output,TRUE)."</pre>");
}

// DELIVER PDF CONTENT TO BROWSER

header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="envelopes_'.date('Y-m-d').'.pdf"');
header("Content-Transfer-Encoding: binary");
@readfile("$tmppath$fileroot.pdf");
?>