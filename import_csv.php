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

$data = parse_csv($csv);

if ($_GET['dryrun']) {
  echo "<style>\nth,td { border:1px solid gray; padding:2px; margin:0; }\n</style>\n";
  echo "<table><tr>";
  if (isset($_GET['phone']) || isset($_GET['fax']) || isset($_GET['address'])) {
    echo "<table><tr><th>Address</th><th>Phone</th><th>FAX</th>";
  }
  echo "<th>Full Name</th><th>Furigana</th><th>Cell Phone</th><th>Email</th><th>Birthdate</th><th>URL</th><th>Remarks</th></tr>\n";
}

foreach ($data as $record) {
  if ($_GET['dryrun']) echo "<tr>";
  //die ("<pre>".print_r($record,TRUE)."</pre>");
  
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

  if ((isset($_GET['phone']) && $record[$_GET['phone']]!="") ||
  (isset($_GET['fax']) && $record[$_GET['fax']]!="") || (isset($_GET['address']) && $record[$_GET['address']]!="")) {
    $sql = "INSERT INTO household (Address,Phone,FAX,LabelName,UpdDate) VALUES ('".h2d($record[$_GET['address']])."',".
    "'".h2d($record[$_GET['phone']])."','".h2d($record[$_GET['fax']])."','".h2d($fullname)."',CURDATE())";
    if ($_GET['dryrun']) {
      echo "<td>".$record[$_GET['address']]."</td><td>".$record[$_GET['phone']]."</td><td>".$record[$_GET['fax']]."</td>\n";
    } else {
      $result = sqlquery_checked($sql);
      $householdid = mysql_insert_id();
    }
  } else {
    $householdid = 0;
    if ($_GET['dryrun']) echo "<td></td><td></td><td></td>";
  }

  $sql = "INSERT INTO person (FullName,Furigana,Title,HouseholdID,CellPhone,".
  "Email,Birthdate,URL,Remarks,UpdDate) VALUES ('".h2d($fullname)."','".h2d($furigana)."','様',$householdid,'".
  h2d($record[$_GET['cellphone']])."','".h2d($record[$_GET['email']])."','".h2d($record[$_GET['birthdate']])."',".
  "'".h2d($record[$_GET['url']])."','".h2d($record[$_GET['remarks']])."',CURDATE())";
  if ($_GET['dryrun']) {
    echo "<td>".$fullname."</td><td>".$furigana."</td>";
    echo "<td>".$record[$_GET['cellphone']]."</td><td>".$record[$_GET['email']]."</td><td>".$record[$_GET['birthdate']]."</td>";
    echo "<td>".$record[$_GET['url']]."</td><td>".$record[$_GET['remarks']]."</td></tr>\n";
  } else {
    $result = sqlquery_checked($sql);
  }
  if ($_GET['dryrun']) echo "</tr>\n";
}
if ($_GET['dryrun']) echo "</table>\n";

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
