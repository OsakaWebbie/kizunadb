<?php
include("functions.php");
include("accesscontrol.php");

if (!isset($_POST['ids']) || $_POST['ids']=="") die("Failed.");

$text = "";
$idarray = split(",",$_POST['ids']);
foreach ($idarray as $id) {
  $piddate = split("_",$id);
  sqlquery_checked("DELETE FROM attendance WHERE EventID=".$_POST['eid']." AND PersonID=".$piddate[0]." AND AttendDate='".$piddate[1]."'");
  $text .= ",#".$id;
}
echo substr($text,1);
?>
