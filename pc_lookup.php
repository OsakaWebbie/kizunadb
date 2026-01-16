<?php
include("functions.php");
include("accesscontrol.php");

echo "<html><head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['charset']."\">\n";
?>
<meta http-equiv="Content-Script-Type" content="text/javascript">
<title>Postal Code Lookup</title>
<STYLE TYPE="TEXT/CSS">
<!--
body,p,div,span,td,input,textarea {font-family: arial, helvetica, sans-serif; font-size: 10pt;}
-->
</STYLE>
</head>
<script type=text/javascript>
function fill_parent(pc,pref,shi,rom)
{
opener.document.forms['editform'].elements['postalcode'].value = pc;
opener.document.forms['editform'].elements['prefecture'].value = pref;
opener.document.forms['editform'].elements['shikucho'].value = shi;
opener.document.forms['editform'].elements['prefecture'].defaultValue = pref;
opener.document.forms['editform'].elements['shikucho'].defaultValue = shi;
if (rom != '') {
  opener.document.forms['editform'].elements['romaji'].value = rom;
}
opener.document.forms['editform'].elements['pclookup'].value = "found";
opener.document.forms['editform'].elements['address'].select();
opener.disallow_prefshi();
window.close();
}
</script>

<?php
if ($_GET['selected_from_list']) {   // *** User just finishing clicking on one of a multiple list ***
  process_selection($_POST['pc'], $_POST['pref'], $_POST['shi'], "", $maindb);
  exit;
} elseif ($entered_romaji) {   // *** User just finished entering romaji ***
  process_selection($_POST['pc'], $_POST['pref'], $_POST['shi'], $_POST['romajitext'], $maindb);
  exit;
}

// ***** Build SQL Statement for Search *****

if ($_GET['pc']) {
  $where = "WHERE PostalCode='".$_GET['pc']."'";
} elseif ($_GET['pref'] or $_GET['shi']) {
  if ($_GET['pref'] and $_GET['shi']) {
    $where = "WHERE Prefecture LIKE '%".$_GET['pref']."%' AND ShiKuCho LIKE '%".$_GET['shi']."%'";
  } elseif ($pref) {
    $where = "WHERE Prefecture LIKE '%".$_GET['pref']."%'";
  } else {
    $where = "WHERE ShiKuCho LIKE '%".$_GET['shi']."%'";
  }
} else {
  $msg = "'To look up a postal code, you must specify\\nat least part of the prefecture and/or city.'";
  echo "<body onload=\"window.close();opener.alert($msg);\"></body></html>";
  exit;
}

// ***** Connect to common database for auxpostalcode table *****
//if (!$commondb = mysqli_connect("localhost", "kz_".$client, $pass, "kizuna_common")) {
//  echo("<body onload=\"window.focus();\"><b>SQL Error: "
//    .mysqli_error($db)."</b><br>(attempting to connect to kizuna_common databasel)</body></html>");
//  exit;
//}
$sql = "SELECT * FROM kizuna_common.auxpostalcode $where";
if (!$aux = mysqli_query($commondb, $sql)) {
  echo("<body onload=\"window.focus();\"><b>SQL Error: "
  .mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)</body></html>");
  exit;
}

if (mysqli_num_rows($aux) == 0) {

  echo "<script type=\"text/javascript\">\n";
  echo "function nomatch()\n";
  echo "{\n";
  echo "opener.document.forms['editform'].elements['postalcode'].select();\n";
  echo "opener.document.forms['editform'].elements['pclookup'].value = \"notfound\";\n";
  echo "window.close();\n";
  echo "opener.alert(\"No match was found, even in the official Japan Post Office database.\\n\"+\n";
  echo "\"Please double-check your information, or remove some text from your\\n\"+\n";
  echo "\"search and choose from the list of choices.  If it's really a brand new postal code,\\n\"+\n";
  echo "\"use the Update Postal Code Data page to add it before referencing it here.\");\n";
  echo "}\n";
  echo "</script>\n";
  echo "<body onload=\"nomatch();\"></body></html>";
  
} elseif (mysqli_num_rows($aux) == 1) {   // *** unique record found ***

  $rec = mysqli_fetch_object($aux);
  process_selection($rec->PostalCode, $rec->Prefecture, $rec->ShiKuCho,"", $maindb);
    
} else {   // *** found multiple records; ask user to select ***

  echo "<body onload=\"window.focus();\"><div align=center>\n";
  echo "<table border=0 cellpadding=0 cellspacing=0><tr><td>\n";
  echo "I found multiple postal codes matching your search criteria.<br>\n";
  echo "Please select the one you want:<br>&nbsp;<br>\n";
  echo "<table cellpadding=2 cellspacing=2 border=0 valign=middle>\n";
  echo "<tr><td bgcolor=#E0E0FF align=center>&nbsp;</td><td bgcolor=#E0E0FF align=center>Postal Code</td>\n";
  echo "<td bgcolor=#E0E0FF align=center>Pref.</td><td bgcolor=#E0E0FF align=center>City etc.</td></tr>\n";

  while ($row = mysqli_fetch_object($aux)) {
    echo "<tr><td>";
    echo "<form method=POST action=\"pc_lookup.php\">\n";
    echo "<input type=hidden name=pc value=\"$row->PostalCode\">\n";
    echo "<input type=hidden name=pref value=\"$row->Prefecture\">\n";
    echo "<input type=hidden name=shi value=\"$row->ShiKuCho\">\n";
    echo "<input type=submit name=\"selected_from_list\" value=\"This One\">\n";
    echo "</form></td>\n";
    echo "<td>$row->PostalCode</td><td>$row->Prefecture</td><td>$row->ShiKuCho</td></tr>\n";
  }
  echo "</table></body></html>\n";
}

//******************************************************************

function process_selection($pc, $pref, $shi, $rom, $maindb) {

  // ***** CHECK POSTALCODE TABLE FOR ENTRY *****
  $sql = "SELECT * FROM postalcode WHERE PostalCode='".$pc."'";
  if (!$main = mysqli_query($db, $sql)) {
    echo("<body onload=\"window.focus();\"><b>SQL Error: ".mysqli_error($db)."</b><br>($sql)</body></html>");
    exit;
  }

  if (mysqli_num_rows($main) == 0) {  // *** NO PREVIOUS ENTRY IN POSTALCODE TABLE ***
  
    // ***** IF ROMAJI NEEDED, BUILD ROMAJI REQUEST FORM AND EXIT *****
    if (($rom == "") && ($_SESSION['romajiaddresses'] == "yes")) {
      echo <<<ECHOEND
<script type="text/javascript">
function validate() {
if (romajitext == '') {
  alert('I know it is mendokusai, but please enter the romaji for this address.');
  return(false);
} else {
  return(true);
}
</script>
<body onload="window.focus();document.forms['romajiform'].elements['romajitext'].select();"><div align=center>
<b>To maintain a romaji version of addresses, please<br>
fill in appropriate romaji for $pref$shi :</b><br>
<form name=romajiform method=POST action="{$_SERVER['PHP_SELF']}" onsubmit="validate();">
<input type=hidden name=pc value="$pc">
<input type=hidden name=pref value="$pref">
<input type=hidden name=shi value="$shi">
<table border=0><tr><td valign=middle>
  <textarea name="romajitext" style="height:3em;width:300px"></textarea>
</td><td valign=middle>
  <input type=submit name="entered_romaji" value="Save">
</td></tr></table><br></div>
<font color=green>Hints: Imagine the numbers part of the address before this, and the postal code
after it, so the order of the items should be reversed.  Also, start a new
line after the first item.&nbsp;&nbsp;For example:<br>
&nbsp;&nbsp;&nbsp;Ikeda-cho<br>
&nbsp;&nbsp;&nbsp;Kita-ku, Osaka</font>
</form></body></html>
ECHOEND;
      exit;
    } else {  // NO NEED FOR ROMAJI, BUT MUST INSERT NEW RECORD
      $sql = "INSERT INTO postalcode(PostalCode,Prefecture,ShiKuCho,Romaji)".
      " VALUES('$pc','$pref','$shi','$rom')";
      if (!$aux = mysqli_query($db, $sql)) {
        echo("<body onload=\"window.focus();\"><b>SQL Error: "
        .mysqli_errno($db).": ".mysqli_error($db)."</b><br>($sql)</body></html>");
        exit;
      }
    }
  } else {  // *** Record was found in postalcode table, so grab its romaji
    $rec = mysqli_fetch_object($main);
    $rom = $rec->Romaji;
  }
  // ***** FINALLY, BUILD JAVASCRIPT CALL TO FILL IN PARENT FIELDS *****
  echo "<body onload=\"fill_parent('$pc','$pref','$shi','".str_replace("\r\n","\\n",$rom)."');\">";
  echo "</body></html>\n";
}

?>