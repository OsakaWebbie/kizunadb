<?php
/* Things yet to be done:
    + Handle addresses in normalized forms (perhaps even including putting postalcode in correct field)
    + Detect Japanese addresses, which would then affect:
      - NonJapan field
      - Title field
      - putting SAMA on end of LabelName
*/

include("functions.php");
include("accesscontrol.php");

header1(_("CSV Import"));
?> <link rel="stylesheet" type="text/css" href="style.php" /> <?
header2(1);

if ($_GET['file']) { //file pre-placed on server
  if (!is_file("/var/www/".$_SESSION['client']."/".$_GET['file'].".csv")) {
    die("File "."/var/www/".$_SESSION['client']."/".$_GET['file'].".csv"." not found.");
  }
  $csv = file_get_contents("/var/www/".$_SESSION['client']."/".$_GET['file'].".csv")
    or die("Failed to read file '/var/www/".$_SESSION['client']."/".$_GET['file'].".csv'.");
} else {
  //EXPECT UPLOAD
  echo "I would process an upload here.";
}

if ($_GET['encoding'] && $_GET['encoding']!="UTF-8") {
  $csv = mb_convert_encoding($csv,"UTF-8",$_GET['encoding']);
}
$data = parse_csv($csv);
if ($_GET['titlerow']) {
  $titlerow = array_shift($data);
  foreach($titlerow as $column => $title) {
    if (strtolower(substr($title,0,7)) == 'remarks') {
      $tempremarks = explode(':',$title);
      $remarkscolumnarray[] = $column . (array_key_exists(1,$tempremarks) ? ":".$tempremarks[1] : '');
    } else {
      $_GET[strtolower($title)] = $column;
    }
  }
  echo("<pre>Remarks Column Array:\n".print_r($remarkscolumnarray,TRUE)."</pre>");
} else {
  $remarkscolumnarray = explode(',',$_GET['remarks']);
}

if ($_GET['dryrun']) {
  $num = $_GET['titlerow'] ? 1 : 0;
  echo "<style>\nth,td { border:1px solid gray; padding:2px; margin:0; }\n</style>\n";
  echo "<h3>Dry Run - all entries will be ".($_GET['org']?"organizations":"people")."</h3>\n<table><tr>";
  if (isset($_GET['phone']) || isset($_GET['fax']) || isset($_GET['address'])) {
    echo "<th>Row#</th><th>PostalCode</th><th>Address</th><th>Phone</th><th>FAX</th>";
  }
  echo "<th>Full Name</th><th>Furigana</th><th>Cell Phone</th><th>Email</th><th>Birthdate</th>".
  "<th>Sex</th><th>URL</th><th>Remarks</th></tr>\n";
}

foreach ($data as $record) {
  if ($_GET['dryrun']) {
    echo "<tr>";
    $num++;
    echo "<td>$num</td>";
    //die ("<pre>".print_r($record,TRUE)."</pre>");
  }
  
  if (isset($_GET['fullname'])) {
    //echo "</table><pre>There's a FullName column</pre>\n";
    $fullname = $record[$_GET['fullname']];
    if (isset($_GET['furigana'])) {
      $furigana = $record[$_GET['furigana']];
    } else {
      mb_ereg("([^ 　]+)[ 　]*(.*)",$fullname,$namearray);  //break on ASCII space or multibyte space
      //echo "<pre>Namearray:\n".print_r($namearray,TRUE)."</pre>";
      if (!$namearray[2]) {  //no space in name so can't separate
        $furigana = $fullname;
      } else {
        if (strlen($namearray[1]) > mb_strlen($namearray[1])+2) {  //multibyte name (more than just a couple European characters)
          $furigana = $namearray[1]." ".$namearray[2];
        } else {
          $furigana = $namearray[2].", ".$namearray[1];
        }
      }
      //echo "<pre>Fullname:\n".print_r($fullname,TRUE)."</pre>";
      //echo "<pre>Furigana:\n".print_r($furigana,TRUE)."</pre>";
      //exit;
    }
  } elseif (isset($_GET['firstname']) && isset($_GET['lastname'])) {
    //echo "</table><pre>There are first and last name columns</pre>\n";
    if ($record[$_GET['firstname']]!="" && $record[$_GET['lastname']]!="") {
      $fullname_separator = " ";
      $furigana_separator = ", ";
    } else {
      $fullname_separator = $furigana_separator = "";
    }
    if (mb_strlen($record[$_GET['firstname']].$record[$_GET['lastname']],"8bit")>mb_strlen($record[$_GET['firstname']].$record[$_GET['lastname']])) {
    // there are multi-byte characters in name, so assume last name first
      $fullname = $record[$_GET['lastname']].$fullname_separator.$record[$_GET['firstname']];
    } else {
      $fullname = $record[$_GET['firstname']].$fullname_separator.$record[$_GET['lastname']];
    }
    $furigana = $record[$_GET['lastname']].$furigana_separator.$record[$_GET['firstname']];
  } else {
    echo("</table><div style=\"color:red\">You must specify the column numbers for either 'fullname' or 'firstname' & 'lastname'.</div>\n");
    echo("<pre>".print_r($_GET,TRUE)."</pre>");
    exit;
    }
  if ($_GET['birthdate']) {
    $birthdate = str_replace('/','-',$record[$_GET['birthdate']]);
  } elseif ($_GET['age']) {
    $birthdate = date("Y")-$record[$_GET['age']].'-01-01';
  } elseif ($_GET['birthday']) {
    $birthdate = '1900-'.str_replace('/','-',$record[$_GET['birthday']]);
  }
  $remarks = "";
  foreach($remarkscolumnarray as $remarkscolumn) {
    $remarkscolumnsplit = explode(':',$remarkscolumn);
    if ($record[$remarkscolumnsplit[0]]) {
      $remarks .= strlen($remarks)?"\n":"";
      if (array_key_exists(1,$remarkscolumnsplit)) {
        $remarks .= $remarkscolumnsplit[1].': ';
      }
      $remarks .= trim($record[$remarkscolumnsplit[0]]);
    }
  }

  if ((isset($_GET['phone']) && $record[$_GET['phone']]!="") ||
  (isset($_GET['fax']) && $record[$_GET['fax']]!="") || (isset($_GET['address']) && $record[$_GET['address']]!="")) {
    //we do need a household record
    if (isset($_GET['address'])) {
      if (isset($_GET['postalcode'])) {
        $postalcode = $record[$_GET['postalcode']];
        $address_to_reduce = $record[$_GET['address']];
      } elseif (mb_ereg('^〒?(\d{3}-\d{4})[ 　]*(.+)$',$record[$_GET['address']],$addr_array)) {
        $postalcode = $addr_array[1];
        $address_to_reduce = $addr_array[2];
      } elseif (mb_ereg('^〒?(\d{7})[ 　]*(.+)$',$record[$_GET['address']],$addr_array)) {
        $postalcode = substr($addr_array[1],0,3).'-'.substr($addr_array[1],3,4);
        $address_to_reduce = $addr_array[2];
      } else {
        $postalcode = '';
        $address = $record[$_GET['address']];
        $address_to_reduce = '';
      }
      if ($address_to_reduce) {
        $addrcheck = sqlquery_checked("SELECT * from postalcode WHERE PostalCode='".$postalcode."'");
        if (!$pc = mysql_fetch_object($addrcheck)) { //not in client table, so need to check aux
          $addrcheck = sqlquery_checked("SELECT * from kizuna_common.auxpostalcode WHERE PostalCode='".$postalcode."'");
          if ($pc = mysql_fetch_object($addrcheck)) { //found in aux, so copy record
            if (!$_GET['dryrun']) sqlquery_checked("INSERT INTO postalcode(PostalCode,Prefecture,ShiKuCho,Romaji)".
            " SELECT PostalCode,Prefecture,ShiKuCho,'".($_SESSION['romajiaddresses']=="yes"?"(edit on DB Maint. page)":"").
            "' FROM kizuna_common.auxpostalcode WHERE PostalCode='".$postalcode."' LIMIT 1");
          } else { //not in aux either
            $postalcode = '';
            $address = '(FIX ME)'.$record[$_GET['address']];
          }
        }
        if ($pc) {
          if (mb_ereg('^'.$pc->Prefecture.$pc->ShiKuCho.'(.*)',$address_to_reduce,$regex_array)) {
            $address = $regex_array[1];
          } else {
            $address = '(FIX ME)'.$address_to_reduce;
          }
        }
      }
    }
      
    $sql = "INSERT INTO household (PostalCode,Address,Phone,FAX,LabelName,UpdDate) VALUES ('".h2d($postalcode)."',".
    "'".h2d($address)."','".h2d($record[$_GET['phone']])."','".h2d($record[$_GET['fax']])."','".h2d($fullname)."',CURDATE())";
    if ($_GET['dryrun']) {
      echo "<td>$postalcode</td><td>".d2h($address)."</td><td>".$record[$_GET['phone']]."</td><td>".$record[$_GET['fax']]."</td>\n";
    } else {
      $result = sqlquery_checked($sql);
      $householdid = mysql_insert_id();
    }
  } else {
    $householdid = 0;
    if ($_GET['dryrun']) echo "<td></td><td></td><td></td><td></td>";
  }

  $sql = "INSERT INTO person (FullName,Furigana,Organization,Title,HouseholdID,CellPhone,".
  "Email,Birthdate,Sex,URL,Remarks,UpdDate) VALUES ('".h2d($fullname)."','".h2d($furigana)."',".
  ($_GET['org']?"1":"0").",'様',$householdid,'".
  h2d($record[$_GET['cellphone']])."','".h2d($record[$_GET['email']])."','".h2d($birthdate)."',".
  "'".h2d($record[$_GET['sex']])."','".h2d($record[$_GET['url']])."','".h2d($remarks)."',CURDATE())";
  if ($_GET['dryrun']) {
    echo "<td>".$fullname."</td><td>".$furigana."</td>";
    echo "<td>".$record[$_GET['cellphone']]."</td><td>".$record[$_GET['email']]."</td><td>".$birthdate."</td>";
    echo "<td>".$record[$_GET['sex']]."</td><td>".$record[$_GET['url']]."</td><td>".d2h($remarks)."</td></tr>\n";
  } else {
    sqlquery_checked($sql);
    echo $fullname." successfully added.<br />";
  }
  if ($_GET['dryrun']) echo "</tr>\n";
}
if ($_GET['dryrun']) {
  echo "</table>\n";
} else {
  $sql = "UPDATE household h LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode SET h.AddressComp=CONCAT(h.PostalCode,pc.
Prefecture,pc.ShiKuCho,h.Address)";
  sqlquery_checked($sql);
  if ($_SESSION['romajiaddresses']=='yes') {
    $sql = "UPDATE household h LEFT JOIN postalcode pc ON h.PostalCode=pc.PostalCode SET h.RomajiAddressComp=CONCAT(h.Addr
ess,' ',pc.Romaji,' ',pc.PostalCode)";
    sqlquery_checked($sql);
  }
}

echo "<h2>In theory, all was completed.</h2>";

footer();

// FROM http://www.php.net/manual/en/function.str-getcsv.php#113220

//parse a CSV file into a two-dimensional array
//this seems as simple as splitting a string by lines and commas, but this only works if tricks are performed
//to ensure that you do NOT split on lines and commas that are inside of double quotes.
function parse_csv($str)
{
    //match all the non-quoted text and one series of quoted text (or the end of the string)
    //each group of matches will be parsed with the callback, with $matches[1] containing all the non-quoted text,
    //and $matches[3] containing everything inside the quotes
    $str = preg_replace_callback('/([^"]*)("((""|[^"])*)"|$)/s', 'parse_csv_quotes', $str);

    //remove the very last newline to prevent a 0-field array for the last line
    $str = preg_replace('/\n$/', '', $str);

    //split on LF and parse each line with a callback
    return array_map('parse_csv_line', explode("\n", $str));
}

//replace all the csv-special characters inside double quotes with markers using an escape sequence
function parse_csv_quotes($matches)
{
    //anything inside the quotes that might be used to split the string into lines and fields later,
    //needs to be quoted. The only character we can guarantee as safe to use, because it will never appear in the unquoted text, is a CR
    //So we're going to use CR as a marker to make escape sequences for CR, LF, Quotes, and Commas.
    $str = str_replace("\r", "\rR", $matches[3]);
    $str = str_replace("\n", "\rN", $str);
    $str = str_replace('""', "\rQ", $str);
    $str = str_replace(',', "\rC", $str);

    //The unquoted text is where commas and newlines are allowed, and where the splits will happen
    //We're going to remove all CRs from the unquoted text, by normalizing all line endings to just LF
    //This ensures us that the only place CR is used, is as the escape sequences for quoted text
    return preg_replace('/\r\n?/', "\n", $matches[1]) . $str;
}

//split on comma and parse each field with a callback
function parse_csv_line($line)
{
    return array_map('parse_csv_field', explode(',', $line));
}

//restore any csv-special characters that are part of the data
function parse_csv_field($field) {
    $field = str_replace("\rC", ',', $field);
    $field = str_replace("\rQ", '"', $field);
    $field = str_replace("\rN", "\n", $field);
    $field = str_replace("\rR", "\r", $field);
    return $field;
}
?>
