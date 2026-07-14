<?php
include("functions.php");
include("accesscontrol.php");

if (empty($_POST['pid_list'])) {
  die(_("There were no Person IDs passed."));
}
if (empty($_POST['addr_print_name'])) {
  die(_("There was no layout type passed."));
}

$sql = "SELECT * FROM addrprint WHERE AddrPrintName='".urldecode($_POST['addr_print_name'])."'";
$result = sqlquery_checked($sql);
$print = mysqli_fetch_object($result);
$ph = $print->PaperHeight;   // positions are mm from the edge each element hugs; picture origin is bottom-left

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
    "NonJapan, postalcode.*, Address, h.HouseholdID ".
"FROM person p LEFT JOIN household h ON p.HouseholdID=h.HouseholdID ".
"LEFT JOIN postalcode ON h.PostalCode=postalcode.PostalCode WHERE p.PersonID IN (".$_POST['pid_list'].") ".
    "AND p.HouseholdID IS NOT NULL AND p.HouseholdID>0 AND h.Address IS NOT NULL AND h.Address!='' ".
    "AND (h.NonJapan=1 OR h.PostalCode!='') ".
    "ORDER BY ".(!empty($_POST['nj_separate']) ? "NonJapan," : "")."FIND_IN_SET(PersonID,'".$_POST['pid_list']."')";
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
\documentclass[dvipdfmx]{ujarticle}
\usepackage{plext}
\usepackage[uplatex]{otf}
\usepackage[T1]{fontenc}
\usepackage{sourcesanspro}
\usepackage[paperwidth=<?=$print->PaperWidth?>mm,paperheight=<?=$print->PaperHeight?>mm,margin=0mm]{geometry}
\usepackage{verbatim}
\usepackage{lscape}
\usepackage{textpos}
\usepackage[dvipdfmx]{graphicx}
\usepackage{bmpsize}
\pagestyle{empty}
\graphicspath {{<?=getcwd()?>/graphics/}{<?=CLIENT_PATH?>/graphics/}}
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
\begin{picture}(<?=$print->PaperWidth?>,<?=$print->PaperHeight?>)
<?php
  if ($row->NonJapan == 1) {
    // Non-Japan is authored in landscape; content is rotated 90deg onto the portrait sheet, so a
    // landscape point (x from left, y from top) is placed with \put(y, x). Rotate the printed sheet
    // clockwise to read it.
    $njrecipient = implode("\\\\\n", array_merge(
      preg_split("/\r\n|\r|\n/", str_replace($search_array,$replace_array,$row->Name)),
      preg_split("/\r\n|\r|\n/", str_replace($search_array,$replace_array,$row->Address))));
    if ($print->NJRetAddrGraphic != '') {
      $njretw = $print->NJRetAddrWidth>0 ? $print->NJRetAddrWidth : 60;
      $njret = "\\includegraphics".($print->NJRetAddrWidth>0 ? "[width=".$print->NJRetAddrWidth."mm]" : "")."{".$print->NJRetAddrGraphic."}";
    } else {
      $njretw = $print->NJRetAddrWidth>0 ? $print->NJRetAddrWidth : $print->PaperHeight-$print->NJRetAddrX;
      $njrettext = implode("\\\\\n", preg_split("/\r\n|\r|\n/", str_replace($search_array,$replace_array,$print->NJRetAddrText)));
      $njret = "\\fontsize{".$print->NJRetAddrPointSize."}{".round($print->NJRetAddrPointSize*1.2,1)."}\\selectfont\\raggedright ".$njrettext;
    }
?>
%% NON-JAPAN PAGE (authored landscape; rotated 90 onto the portrait sheet) %%
%% Return Address %%
\put(<?=$print->NJRetAddrY?>,<?=$print->NJRetAddrX?>){\rotatebox{90}{%
\begin{minipage}[t]{<?=$njretw?>mm}<?=$njret?>\end{minipage}}}
%% Recipient %%
\put(<?=$print->NJRecipY?>,<?=$print->NJRecipX?>){\rotatebox{90}{%
\begin{minipage}[t]{<?=$print->NJRecipWidth?>mm}
\fontsize{<?=$print->NJAddrPointSize?>}{<?=round($print->NJAddrPointSize*1.2,1)?>}\selectfont\raggedright
<?=$njrecipient?>
\end{minipage}}}
<?php
  } else {  //Japanese address
?>
%% JAPAN PAGE %%
<?php
    if (!empty($_POST['po_stamp']) && $_POST['po_stamp']!='none') {  //Post Office stamp requested
      $stampfiles = [
        'betsunou'         => ['po_betsunou.png',         520, 452],
        'yuumail_betsunou' => ['po_yuumail_betsunou.png', 520, 600],
        'kounou'           => ['po_kounou.png',           520, 452],
        'yuumail_kounou'   => ['po_yuumail_kounou.png',   520, 600],
      ];
      if (isset($stampfiles[$_POST['po_stamp']])) {
        [$stampfile, $bbw, $bbh] = $stampfiles[$_POST['po_stamp']];
        $stampw = 30;
        $stamph = $stampw * $bbh / $bbw;
?>
\put(<?=$print->StampX?>,<?=$ph-$print->StampY-$stamph?>){%
\includegraphics[bb=0 0 <?=$bbw?> <?=$bbh?>,width=<?=$stampw?>mm]{<?=$stampfile?>}}
<?php
      }
    }  //end if Post Office stamp requested
?>
<?php
    if (strlen($row->PostalCode)>7) {  //PostalCode is complete
?>
\fontsize{<?=$print->PCPointSize?>}{<?=$print->PCPointSize*1.2?>}\selectfont
\put(<?=$print->PCX?>,<?=$ph-$print->PCY?>){<?=$row->PostalCode[0]?>}
\put(<?=$print->PCX+$print->PCSpacing?>,<?=$ph-$print->PCY?>){<?=$row->PostalCode[1]?>}
\put(<?=$print->PCX+$print->PCSpacing*2?>,<?=$ph-$print->PCY?>){<?=$row->PostalCode[2]?>}
\put(<?=$print->PCX+$print->PCExtraSpace+$print->PCSpacing*3?>,<?=$ph-$print->PCY?>){<?=$row->PostalCode[4]?>}
\put(<?=$print->PCX+$print->PCExtraSpace+$print->PCSpacing*4?>,<?=$ph-$print->PCY?>){<?=$row->PostalCode[5]?>}
\put(<?=$print->PCX+$print->PCExtraSpace+$print->PCSpacing*5?>,<?=$ph-$print->PCY?>){<?=$row->PostalCode[6]?>}
\put(<?=$print->PCX+$print->PCExtraSpace+$print->PCSpacing*6?>,<?=$ph-$print->PCY?>){<?=$row->PostalCode[7]?>}
<?php
    }  //end if PostalCode is complete
    
    if ($print->Tategaki==1) {
      $dir = 't'; $align = 'rt';
      $wrap = $print->RecipHeight;                     // column length (vertical text)
      $hang = round($wrap * 0.4, 1);
    } else {
      $dir = 'y'; $align = 'lt';
      $wrap = $print->RecipWidth;                      // line width (horizontal text)
      $hang = round($wrap * 0.2, 1);
    }
    $addr = !empty($_POST['kanji_numbers']) ? str_replace($number_array,$kanji_array,$row->Address) : $row->Address;
    $addr = preg_replace("/\r\n|\r|\n/","}\n\n\\hangindent=".$hang."mm\n\\mbox{",$addr);
    $name = preg_replace("/\r\n|\r|\n/","\n\n\\hspace*{".$print->NameIndent."mm}",str_replace($search_array,$replace_array,$row->Name));
?>
%% Recipient (address + name in one flowing block) %%
\put(<?=$print->RecipX?>,<?=$ph-$print->RecipY-$print->RecipHeight?>){%
\makebox(<?=$print->RecipWidth?>,<?=$print->RecipHeight?>)[<?=$align?>]{%
\begin{minipage}<<?=$dir?>>[t]{<?=$wrap?>mm}
\fontsize{<?=$print->AddrPointSize?>}{<?=$print->AddrPointSize*1.2?>}\selectfont
\hangindent=<?=$hang?>mm
\mbox{<?=$row->Prefecture.$row->ShiKuCho?>}
\mbox{<?=$addr?>}

\vspace{<?=$print->NameGap?>mm}
{\fontsize{<?=$print->NamePointSize?>}{<?=$print->NamePointSize*1.2?>}\selectfont
\hspace*{<?=$print->NameIndent?>mm}<?=$name?>}
\end{minipage}}}
<?php
    // Return address: a client graphic (if named) else plain text, anchored bottom-left at
    // (RetAddrX from left, RetAddrY from bottom). Width sizes the image / wraps the text; 0 = auto.
    if ($print->RetAddrGraphic != '') {
      $retcontent = "\\includegraphics".($print->RetAddrWidth>0 ? "[width=".$print->RetAddrWidth."mm]" : "")."{".$print->RetAddrGraphic."}";
    } else {
      $retw = $print->RetAddrWidth>0 ? $print->RetAddrWidth : $print->PaperWidth-$print->RetAddrX;
      $rettext = implode("\\\\\n", preg_split("/\r\n|\r|\n/", str_replace($search_array,$replace_array,$print->RetAddrText)));
      $retcontent = "\\begin{minipage}<y>[b]{".$retw."mm}\\fontsize{".$print->RetAddrPointSize."}{".round($print->RetAddrPointSize*1.2,1)."}\\selectfont\\raggedright\n".$rettext."\n\\end{minipage}";
    }
?>
%% Return Address %%
\put(<?=$print->RetAddrX?>,<?=$print->RetAddrY?>){<?=$retcontent?>}
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

// DELIVER PDF CONTENT TO BROWSER

header("Content-Type: application/pdf");
header('Content-Disposition: attachment; filename="envelopes_'.date('Y-m-d').'.pdf"');
header("Content-Transfer-Encoding: binary");
@readfile("$tmppath$fileroot.pdf");
?>