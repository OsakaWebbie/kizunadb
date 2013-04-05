<?php
include("functions.php");
include("accesscontrol.php");

$vol = "kizuna_crash";
$don = "kizuna_crashdonors";
$go = ((isset($_GET['go']) && $_GET['go']==1) ? 1 : 0);
header1("Handle donor records created after the split");
?> <link rel="stylesheet" type="text/css" href="style.php" /> <?
header2(1);

$records = sqlquery_checked("select p.*,h.*,pc.* from $don.person p left join $don.household h on p.HouseholdID=h.HouseholdID".
" left join $don.postalcode pc on h.PostalCode=pc.PostalCode where p.PersonID>11684 order by PersonID");

$count_p = $count_same = $count_perhits = $count_hhhits = $count_new = $count_pcadded = $count_hhadded = $count_peradded;
$count_percat = $count_contact = $count_donation = 0;
$starttime = microtime(true);
while ($d = mysql_fetch_object($records)) {
  $pid = $d->PersonID;
  $count_p++;

  $sql = "select p.PersonID, p.FullName, p.HouseholdID, h.LabelName,";
  $sql .= " IF(LOWER(REPLACE(p.FullName,' ',''))!='".h2d(strtolower(str_replace(" ","",$d->FullName)))."'";
  $sql .= " AND LOWER(REPLACE(REPLACE(p.Furigana,' ',''),',',''))!='".h2d(strtolower(str_replace(",","",str_replace(" ","",$d->Furigana))))."'";
  if ($d->CellPhone != "") $sql .= " AND REPLACE(p.CellPhone,'-','')!='".str_replace("-","",$d->Phone)."'";
  if ($d->Email != "") $sql .= " AND LOWER(p.Email)!='".strtolower($d->Email)."'";
  $sql .= ",1,0) AS hhonly from $vol.person p left join $vol.household h on p.HouseholdID=h.HouseholdID";
  $sql .= " WHERE LOWER(REPLACE(p.FullName,' ',''))='".h2d(strtolower(str_replace(" ","",$d->FullName)))."'";
  $sql .= " OR LOWER(REPLACE(REPLACE(p.Furigana,' ',''),',',''))='".h2d(strtolower(str_replace(",","",str_replace(" ","",$d->Furigana))))."'";
  if ($d->LabelName != "") $sql .= " OR LOWER(REPLACE(REPLACE(h.LabelName,' ',''),',','')) LIKE '%".str_replace(",","",str_replace(" ","",h2d($d->LabelName)))."%'";
  if ($d->HouseholdID>0 && $d->Address != "") {
    if ($d->PostalCode!="") {
      $sql .= " OR (h.PostalCode='".$d->PostalCode."' AND h.Address='".h2d($d->Address)."')";
    } else {
      $sql .= " OR h.Address='".h2d($d->Address)."'";
    }
  }
  if ($d->Phone != "") $sql .= " OR REPLACE(h.Phone,'-','')='".str_replace("-","",$d->Phone)."'";
  if ($d->CellPhone != "") $sql .= " OR REPLACE(p.CellPhone,'-','')='".str_replace("-","",$d->CellPhone)."'";
  if ($d->Email != "") $sql .= " OR LOWER(p.Email)='".strtolower($d->Email)."'";
//$time = microtime(true);
  $duptest = sqlquery_checked($sql);
//$time = ceil((microtime(true)-$time)*1000);
//$timetext .= $time."<br>";
//if (microtime(true)-$starttime > 20) die("<h2>Too slow..</h2><p>$sql</p><p>$timetext</p>");

  if (mysql_numrows($duptest)>0) {  //found possible duplicates
    $count_same++;
    $duplinks = "";
    $lasthhid = 0;
    //echo "Dup match #".$count_same." (".$d->PersonID." ".$d->FullName."): ".mysql_numrows($duptest)." records found<br>";
    while ($dup = mysql_fetch_object($duptest)) {
      //if (mysql_numrows($duptest) > 20) die($sql);
      if ($dup->hhonly) {
        if ($dup->HouseholdID!=$lasthhid) {
          $duplinks .= "Household match: ".h2d($dup->LabelName)." &nbsp;<a href=\"http://crash.kizunadb.com/household.php?hhid=".
          $dup->HouseholdID."\" target=\"_blank\">View</a><br>";
          $count_hhhits++;
          $lasthhid = $dup->HouseholdID;
        }
      } else {
        $duplinks .= "Person/org match:".h2d($dup->FullName)." &nbsp;".
        "<a href=\"http://crash.kizunadb.com/individual.php?pid=".$dup->PersonID."\" target=\"_blank\">View</a>".
        " &nbsp;<a href=\"http://crash.kizunadb.com/merge.php?sourcedb=$don&destdb=$vol&sourceid=$pid&destid=".$dup->PersonID."\"".
        " target=\"_blank\">Merge</a><br>";
        $count_perhits++;
      }
    }
    if ($go)  sqlquery_checked("insert into $don.contact(PersonID,ContactTypeID,ContactDate,Description) values($pid,68,CURDATE(),'$duplinks')");
  } else {  //no possible duplicates found, so copy everything as new record
    $count_new++;
    //postalcode
    if ($d->PostalCode!="") {
      $test = sqlquery_checked("SELECT * FROM $vol.postalcode WHERE PostalCode='".$d->PostalCode."'");
      if (mysql_numrows($test) == 0) {
        if ($go)  sqlquery_checked("INSERT INTO $vol.postalcode SELECT * from $don.postalcode WHERE PostalCode='".$d->PostalCode."'");
        $count_pcadded++;
      }
    }
    $newhhid = $newpid = 0;
    //household
    if ($d->HouseholdID) {
      if ($go)  sqlquery_checked("INSERT INTO $vol.household (NonJapan,PostalCode,Address,AddressComp,RomajiAddress,RomajiAddressComp,".
      "Phone,FAX,LabelName,UpdDate) SELECT NonJapan,PostalCode,Address,AddressComp,RomajiAddress,RomajiAddressComp,".
      "Phone,FAX,LabelName,UpdDate FROM $don.household WHERE HouseholdID=".$d->HouseholdID);
      if ($go)  $newhhid = mysql_insert_id();
      $count_hhadded++;
    }
    //person
    if ($go)  sqlquery_checked("INSERT INTO $vol.person (FullName,Furigana,Sex,HouseholdID,Relation,Title,CellPhone,".
    "Email,Birthdate,Country,URL,Organization,Remarks,UpdDate) SELECT FullName,Furigana,Sex,$newhhid,Relation,Title,CellPhone,".
    "Email,Birthdate,Country,URL,Organization,Remarks,UpdDate FROM $don.person WHERE PersonID=$pid");
    if ($go)  $newpid = mysql_insert_id();
    $count_peradded++;
    if ($go && $newpid==0) die("newpid is zero for pid $pid");

    //percat
    $res = sqlquery_checked(($go?"insert into $vol.percat ":"")."select $newpid,CategoryID from $don.percat where PersonID=$pid".
    " and CategoryID in (35,78,77,79,73,76,7,61)");
    $count_percat += ($go? mysql_affected_rows() : mysql_numrows($res));
    //contact
    $res = sqlquery_checked(($go?"insert into $vol.contact(PersonID,ContactTypeID,ContactDate,Description) ":"").
    "select $newpid,ContactTypeID,ContactDate,Description from $don.contact where PersonID=$pid");
    $count_contact += ($go? mysql_affected_rows() : mysql_numrows($res));
    //donation
    $res = sqlquery_checked(($go?"insert into $vol.donation(PersonID,PledgeID,DonationDate,DonationTypeID,Amount,".
    "Description,Processed) ":"")."select $newpid,PledgeID,DonationDate,DonationTypeID,Amount,".
    "Description,Processed from $don.donation where PersonID=$pid");
    $count_donation += ($go? mysql_affected_rows() : mysql_numrows($res));

  }  //end if dups or not
  //if ($count_p > 10) break;
  if (microtime(true)-$starttime > 100) {
    echo "<h2 style=\"color:red\">Too slow - only finished through ID#$pid</h2>";
    break;
  }
}  //end while
  
echo "<h2>".($go?"We actually did the SQL this time!":"Dry run...").
"</h2><pre>Results:\n$count_p total records added\n$count_same records matched at least one possible duplicate, ".
"$count_perhits hits on person info, $count_hhhits hits on household info only\n".
"$count_new new records added ($count_pcadded PC records, $count_hhadded HH records, $count_peradded person records)\n";
echo "$count_percat percat records\n$count_contact contact records\n$count_donation donation records\n";
echo "</pre>";

footer();
?>
