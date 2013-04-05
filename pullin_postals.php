<?php
include("functions.php");
include("accesscontrol.php");
mysql_select_db("kizuna_common");

setlocale(LC_ALL, 'ja_JP.UTF8');
header1(_("Update of Auxiliary Postal Code Data"));
?> <link rel="stylesheet" type="text/css" href="style.php" /> <?
header2(1);

//if (!is_file("http://www.post.japanpost.jp/zipcode/dl/kogaki/lzh/ken_all.lzh")) die("Can't find file on post office website.");

if (!is_file("ken_all.csv")) {
  echo "You need to:\n";
  echo "<ul><li>Get <a href=\"http://www.post.japanpost.jp/zipcode/dl/kogaki/lzh/ken_all.lzh\">the file</li>\n";
  echo "<li>Put it in the codebase directory</li>\n";
  echo "<li>Extract it (lha -e ken_all.lzh)</li></ul>\n";
  exit;
  //if(!copy('http://www.post.japanpost.jp/zipcode/dl/kogaki/lzh/ken_all.lzh','temp.lzh')) {
    //$errors= error_get_last();
    //echo "COPY ERROR: ".$errors['type'];
    //echo "<br />\n".$errors['message'];
    //exit;
  //}
  //if (!is_file("temp.lzh")) die("Failed to copy file from post office website.");
  //echo "file copied.\n";
  //exec("lha -e . temp.lzh") or die("unzip failed.");
  //if (!is_file("ken_all.csv")) die("Failed to extract ken_all.csv.");
  //unlink("temp.lzh");
  //exit;
//get absolute path
//$path = substr($_SERVER["SCRIPT_FILENAME"],0,strrpos($_SERVER["SCRIPT_FILENAME"],"/")+1);

}  //end of section that gets file externally (which doesn't currently work)

sqlquery_checked("DROP TABLE IF EXISTS auxpostalcode");
$sql = "CREATE TABLE auxpostalcode (`PostalCode` varchar(8) NOT NULL default '',";
$sql .= "`Prefecture` varchar(12) NOT NULL default '',`ShiKuCho` varchar(54) NOT NULL default '',";
$sql .= "`KataPref` varchar(20) NOT NULL,`KataShi` varchar(120) NOT NULL,";
$sql .= "`KataCho` varchar(120) NOT NULL, KEY `PostalCode` (`PostalCode`)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
sqlquery_checked($sql);

$handle = fopen("ken_all.csv", "r");
$count = 0;
$sql = "INSERT into auxpostalcode(PostalCode,Prefecture,ShiKuCho,KataPref,KataShi,KataCho) values";
while (($line = fgets($handle, 1024)) !== FALSE) {
  $data = mb_split(",",mb_convert_encoding($line,"UTF-8","SJIS"));
  
  if ($data[8]=="\"以下に掲載がない場合\"") $data[5]=$data[8]="\"\"";
  if ($count > 1000) { //so that we don't overrun the MySQL max packet size
    $sql = substr($sql,0,strlen($sql)-1); //remove the last comma
    sqlquery_checked($sql);
    $count = 0;
    $sql = "INSERT into auxpostalcode(PostalCode,Prefecture,ShiKuCho,KataPref,KataShi,KataCho) values";
  }
  $sql .= "('".mb_substr($data[2],1,3)."-".mb_substr($data[2],4,4)."','".
  mb_substr($data[6],1,mb_strlen($data[6])-2)."','".
  mb_substr($data[7],1,mb_strlen($data[7])-2).
  mb_ereg_replace("（.*）","",mb_substr($data[8],1,mb_strlen($data[8])-2))."','".
  mb_substr($data[3],1,mb_strlen($data[3])-2)."','".
  mb_substr($data[4],1,mb_strlen($data[4])-2)."','".
  mb_ereg_replace("\(.*\)","",mb_substr($data[5],1,mb_strlen($data[5])-2))."'),";
  $count++;
}
$sql = substr($sql,0,strlen($sql)-1); //remove the last comma
sqlquery_checked($sql);

fclose($handle);
unlink("ken_all.csv");
echo "In theory, all was completed.";

footer();
?>
