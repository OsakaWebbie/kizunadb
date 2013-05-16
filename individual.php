<?php
include("functions.php");
include("accesscontrol.php");
$recent=10;
$_SESSION['displaydefault_contactnum'] = 5;
$_SESSION['displaydefault_contactsize'] = 200;

// so that upload file timestamps always work as in Japan, rather than wherever the server is...
sqlquery_checked("SET time_zone='+09:00'");

// A REQUEST TO ADD A PERORG RECORD?
if ($_POST['newperorg']) {
  $result = sqlquery_checked("SELECT * FROM person WHERE PersonID=".$_POST['orgid']." AND Organization=1");
  if (mysql_num_rows($result) == 0) {  // either no record with that ID or Organization is FALSE
    $msg = "This ID does not point to an organization record. Use Browse if you need help.";
  } else {
    sqlquery_checked("REPLACE INTO perorg(PersonID, OrgID, Leader)".
    "VALUES(".$_POST['pid'].", ".$_POST['orgid'].", ".($_POST['leader']?"1":"0").")");
    header("Location: individual.php?pid=".$_POST['pid']."#org");
    exit;
  }
}

// A REQUEST TO ADD A CONTACT RECORD?
if ($newcontact) {
  $result = sqlquery_checked("SELECT * FROM contact WHERE PersonID=${pid} AND ContactTypeID=${ctype} ".
    "AND ContactDate='${date}' AND Description= '".h2d($desc)."'");
  if (mysql_num_rows($result) == 0) {  // making sure this isn't an accidental repeat entry
    if (!$result = mysql_query("INSERT INTO contact(PersonID, ContactTypeID, ContactDate, Description) ".
        "VALUES($pid, $ctype, '$date', '".h2d($desc)."')")) {
      echo "<b>SQL Error ".mysql_errno()." while inserting Contact record: ".mysql_error()."</b>";
    } else {
      header("Location: individual.php?pid=".$_POST['pid']."#contacts");
      exit;
    }
  }
}

// A REQUEST TO DELETE A CONTACT RECORD?
if ($delcontact) {
  if (!$result = mysql_query("DELETE FROM contact WHERE ContactID=$cid")) {
    echo "<b>SQL Error ".mysql_errno()." while deleting Contact record: ".mysql_error()."</b>";
  } else {
    header("Location: individual.php?pid=".$_POST['pid']."#contacts");
  }
  exit;
}

// A REQUEST TO UPDATE A CONTACT RECORD?
if ($editcontactsave) {
  if (!$result = mysql_query("UPDATE contact SET ContactTypeID=$ctype, ContactDate='$date', ".
    "Description='".h2d($desc)."' WHERE ContactID=$cid")) {
    echo "<b>SQL Error ".mysql_errno()." while updating Contact record: ".mysql_error()."</b>";
  } else {
    header("Location: individual.php?pid=".$_POST['pid']."#contacts");
  }
  exit;
}

// A REQUEST TO ADD A DONATION RECORD?
if ($newdonation) {
  if (strpos($_POST['plid'],":") > 0) {
    $dtype = substr($_POST['plid'],strpos($_POST['plid'],":")+1);
    $plid = substr($_POST['plid'],0,strpos($_POST['plid'],":"));
  } else {
    $dtype = $_POST['dtype'];
  }
  $sql = "SELECT * FROM donation WHERE PersonID=".$_POST['pid']." AND DonationTypeID=$dtype ".
    "AND DonationDate='".$_POST['date']."' AND PledgeID".($plid ? "=".$plid : " IS NULL").
    " AND Amount= ".ereg_replace(",","",$_POST['amount']);
  $result = sqlquery_checked($sql);
  if (mysql_num_rows($result) == 0) {  // making sure this isn't an accidental repeat entry
    $sql = "INSERT INTO donation(PersonID,".(plid?"PledgeID,":"")."DonationTypeID, DonationDate,".
    "Amount, Description, Processed) VALUES(".$_POST['pid'].",".($plid?$plid.",":"").$dtype.",".
    "'".$_POST['date']."',".ereg_replace(",","",$_POST['amount']).",'".h2d($_POST['desc'])."',".($_POST['proc']?"1":"0").")";
    if (!$result = mysql_query($sql)) {
      echo "<b>SQL Error ".mysql_errno()." while inserting Donation record: ".mysql_error()."</b><br>($sql)";
    } else {
      header("Location: individual.php?pid=".$_POST['pid']."#donations");
    }
  } else {
      echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
      echo "alert('A donation on that date for that purpose and amount has already been recorded.')\n";
      echo "window.location = \"individual.php?pid=".$_POST['pid']."#donations\";\n";
      echo "</SCRIPT>\n";
  }
  exit;
}

// A REQUEST TO DELETE A DONATION RECORD?
if ($deldonation) {
  if (!$result = mysql_query("DELETE FROM donation WHERE DonationID=$did")) {
    echo "<b>SQL Error ".mysql_errno()." while deleting Donation record: ".mysql_error()."</b>";
  } else {
    header("Location: individual.php?pid=".$_POST['pid']."#donations");
  }
  exit;
}

// A REQUEST TO UPDATE A DONATION RECORD?
if ($editdonationsave) {
  if (strpos($_POST['plid'],":") > 0) {
    $dtype = substr($_POST['plid'],strpos($_POST['plid'],":")+1);
    $plid = substr($_POST['plid'],0,strpos($_POST['plid'],":"));
  } else {
    $dtype = $_POST['dtype'];
  }
  $sql = "UPDATE donation SET PledgeID=".($plid?$plid:"NULL").
      ",DonationTypeID=".$dtype.",DonationDate='".$_POST['date']."',".
      "Amount=".ereg_replace(",","",$_POST['amount']).",Description='".h2d($_POST['desc'])."',".
      "Processed=".($_POST['proc']?"1":"0")." WHERE DonationID=$did";
  if (!$result = mysql_query($sql)) {
    echo "<b>SQL Error ".mysql_errno()." while updating Donation record: ".
    mysql_error()."</b><br>$sql";
  } else {
    header("Location: individual.php?pid=".$_POST['pid']."#donations");
  }
  exit;
}

// A REQUEST TO ADD ADDENDANCE RECORD(S)?
if ($newattendance) {
  //make array of pids (single and/or org members)
  $pidarray = array();
  if (!$_POST["apply"] || !(strpos($_POST["apply"],"org")===false)) $pidarray[] = $_POST['pid'];
  if (!(strpos($_POST["apply"],"mem")===false)) {
    $result = sqlquery_checked("SELECT PersonID from perorg where OrgID=".$_POST['pid']);
    while ($row = mysql_fetch_object($result)) $pidarray[] = $row->PersonID;
  }
  //make array of dates (single or range)
  $datearray = array();
  if ($_POST["enddate"] != "") {  //need to do a range of dates
    if ($_POST["date"] > $_POST["enddate"]) die("Error: End Date is earlier than Start Date.");
    for ($day=$_POST["date"]; $day<=$_POST["enddate"]; $day=strftime("%Y-%m-%d", strtotime("$day +1 day"))) {
      if ($_POST["dow".date("w",strtotime($day))]) {
        $datearray[] = $day;
      }
    }
  } else {
    $datearray[] = $_POST["date"];
  }
  //insert for each date and pid (might be only one of each, but...)
  //not combined into a single "insert...select" query because the ON DUPLICATE KEY UPDATE won't add the non-dups in the list
  foreach ($datearray as $eachdate) {
    foreach ($pidarray as $eachpid) {
      if ($_POST["starttime"] != "") {
        sqlquery_checked("INSERT INTO attendance(PersonID,EventID,AttendDate,StartTime,EndTime) ".
        "VALUES($eachpid,{$_POST["eid"]},'$eachdate','".$_POST["starttime"].":00','".$_POST["endtime"].":00') ".
        "ON DUPLICATE KEY UPDATE StartTime='".$_POST["starttime"].":00', EndTime='".$_POST["endtime"].":00'");
      } else {
        sqlquery_checked("INSERT INTO attendance(PersonID,EventID,AttendDate) ".
        "VALUES($eachpid,{$_POST["eid"]},'$eachdate') ON DUPLICATE KEY UPDATE AttendDate=AttendDate");
      }
    }
  }
  header("Location: individual.php?pid=".$_POST['pid']."#attendance");
  exit;
}

// A REQUEST TO DELETE A SET OF ATTENDANCE RECORDS?
if ($delattendance) {
  $result = sqlquery_checked("DELETE FROM attendance WHERE PersonID=".$_POST['pid']." AND EventID=".$_POST['eid']);
  header("Location: individual.php?pid=".$_POST['pid']."#attendance");
  exit;
}

// A REQUEST TO UPLOAD A FILE?
if ($newupload) {
  if (is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['uploadfile']['name'], PATHINFO_EXTENSION));
    $result = sqlquery_checked("SELECT Extension FROM uploadtype WHERE Extension='$ext'");
    if (mysql_num_rows($result) == 0) {
      $msg = "This file type is not approved for uploads.";
    } else {
      sqlquery_checked("INSERT INTO upload(PersonID,UploadTime,FileName,Description)"."VALUES($pid,NOW(),'".h2d($_FILES['uploadfile']['name'])."','".h2d($_POST['uploaddesc'])."')");
      $uid = mysql_insert_id();
      if (!move_uploaded_file($_FILES['uploadfile']['tmp_name'], "/var/www/".$_SESSION['client']."/uploads/u$uid.$ext")) {
        sqlquery_checked("DELETE FROM upload WHERE UploadID=$uid");
        echo "File upload failed.  Here's some debugging info:\n<pre>";
        print_r($_FILES);
        exit;
      } else {
      }
      header("Location: individual.php?pid=".$_POST['pid']."#uploads");
      exit;
    }
  } else {
    switch ($code) { 
      case UPLOAD_ERR_INI_SIZE: 
        die("FILE UPLOAD ERROR: The uploaded file exceeds the upload_max_filesize directive in php.ini");
      case UPLOAD_ERR_FORM_SIZE: 
        die("FILE UPLOAD ERROR: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form");
      case UPLOAD_ERR_PARTIAL: 
        die("FILE UPLOAD ERROR: The uploaded file was only partially uploaded");
      case UPLOAD_ERR_NO_FILE: 
        die("FILE UPLOAD ERROR: No file was uploaded");
      case UPLOAD_ERR_NO_TMP_DIR: 
        die("FILE UPLOAD ERROR: Missing a temporary folder");
      case UPLOAD_ERR_CANT_WRITE: 
        die("FILE UPLOAD ERROR: Failed to write file to disk");
      case UPLOAD_ERR_EXTENSION: 
        die("FILE UPLOAD ERROR: File upload stopped by extension"); 
      default: 
        die("FILE UPLOAD ERROR: Unknown upload error");
    } 
  }
}

// A REQUEST TO DELETE AN UPLOADED FILE?
if ($delupload) {
  if (!unlink("/var/www/".$_SESSION['client']."/uploads/u".$_POST['uid'].".".$_POST['ext'])) die("Failed to delete file.");
  $result = sqlquery_checked("DELETE FROM upload WHERE UploadID=".$_POST['uid']);
  header("Location: individual.php?pid=".$_POST['pid']."#uploads");
  exit;
}

// NOT AN EDIT SITUATION, SO GATHER AND DISPLAY THE INFORMATION
if (!$pid) {
  echo "PersonID not passed.";
  exit;
}
$result = sqlquery_checked("SELECT * FROM person WHERE PersonID=$pid");
if (mysql_num_rows($result) == 0) {
  echo("<b>Failed to find a record for PersonID $pid.</b>");
  exit;
}
$per = mysql_fetch_object($result);
header1("$per->FullName");

//array of column id, whether to hide in column picker, and whether to disable in sorter
$cols[] = array("personid",1,1);
$cols[] = array("name-for-csv",0,0);
$cols[] = array("name-for-display",0,1);
$cols[] = array("photo",1,1);
$cols[] = array("phone",1,1);
$cols[] = array("email",1,1);
$cols[] = array("address",1,1);
$cols[] = array("birthdate",1,1);
$cols[] = array("age",1,1);
$cols[] = array("sex",1,1);
$cols[] = array("country",1,1);
$cols[] = array("url",1,1);
$cols[] = array("remarks",1,1);
$cols[] = array("categories",1,1);
$cols[] = array("events",1,1);
$cols[] = array("selectcol",0,0);
$cols[] = array("delete",0,0);
$colsHidden = $hideInList = "";
foreach($cols as $i=>$col) {
  if ($col[1]==0) {
    $hideInList .= ",".($i+1);
  } else {
    if (stripos(",".$_SESSION['org_showcols'].",",",".$col[0].",") === FALSE)  $orgColsHidden .= ",".($i+1);
    if (stripos(",".$_SESSION['member_showcols'].",",",".$col[0].",") === FALSE)  $memberColsHidden .= ",".($i+1);
  }
  if ($col[2]==0)  $sorterHeaders .= ",".$i.":{sorter:false}";
}
//remove leading commas
$hideInList = substr($hideInList,1);
$orgColsHidden = substr($orgColsHidden,1);
$memberColsHidden = substr($memberColsHidden,1);
$sorterHeaders = substr($sorterHeaders,1);

$tableheads = "<th class=\"personid\">"._("ID")."</th>";
$tableheads .= "<th class=\"name-for-csv\" style=\"display:none\">"._("Name")." (".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>";
$tableheads .= "<th class=\"name-for-display\">"._("Name")." (".($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")).")</th>";
$tableheads .= "<th class=\"photo\">"._("Photo")."</th>\n";
$tableheads .= "<th class=\"phone\">"._("Phone")."</th>\n";
$tableheads .= "<th class=\"email\">"._("Email")."</th>\n";
$tableheads .= "<th class=\"address\">"._("Address")."</th>\n";
$tableheads .= "<th class=\"birthdate\">"._("Born")."</th>\n";
$tableheads .= "<th class=\"age\">"._("Age")."</th>\n";
$tableheads .= "<th class=\"sex\">"._("Sex")."</th>\n";
$tableheads .= "<th class=\"country\">"._("Country")."</th>\n";
$tableheads .= "<th class=\"url\">"._("URL")."</th>\n";
$tableheads .= "<th class=\"remarks\">"._("Remarks")."</th>\n";
$tableheads .= "<th class=\"categories\">"._("Categories")."</th>\n";
$tableheads .= "<th class=\"events\">"._("Events")."</th>\n";
$tableheads .= "<th id=\"thSelectColumn\" class=\"selectcol\">";
$tableheads .= "<ul id=\"ulSelectColumn\"><li><img src=\"graphics/selectcol.png\" alt=\"select columns\" ".
        "title=\"select columns\" /><ul id=\"target\"></ul></li></ul>";
$tableheads .= "</th>\n";
$tableheads .= "<th></th>\n";  // for the Delete button
?>

<meta http-equiv="expires" content="0">
<link rel="stylesheet" type="text/css" href="style.php?jquery=1&table=1" />
<? header2(1);
if ($per->Organization) $break = "<br /><span class=\"smaller\">";
echo "<h1 id=\"title\">".readable_name($per->FullName,$per->Furigana,$per->PersonID,$per->Organization,$break)."</h1>";
if ($per->Photo) echo "<div id=\"photo\"><img src=\"photo.php?f=p".$pid."\" width=\"150\" /></div>\n";
echo "<div id=\"info-block\">";

// ********** PERSONAL INFORMATION **********

echo "\n\n<!-- Personal Information Section -->\n\n";
echo "<div id=\"personal-info\"><h3 class=\"info-title\">"._("Personal Information")."</h3>";
if ($per->Organization) {
  echo _("Organization");
} else {
  if ($per->Sex) {
    echo "<div id=\"sex\">".(($per->Sex=="F")?_("Female"):_("Male"))."</div>\n";
  }
  if ($per->Birthdate && (substr($per->Birthdate,0,4) != "0000")) {
    echo "<div id=\"birthdate\">";
    if (substr($per->Birthdate,0,4) == "1900") {
      echo _("Birthday").": ".substr($per->Birthdate,5);
    } else {
      echo _("Age").": ".age($per->Birthdate)." ("._("birthdate")." ".$per->Birthdate.")";
    }
    echo "</div>";
  }
}
if ($per->Email) echo "<div id=\"email\">"._("Email").": ".email2link($per->Email)."</div>\n";
if ($per->CellPhone) echo "<div id=\"cellphone\">"._("Cell Phone").": ".$per->CellPhone."</div>\n";
if ($per->Country) echo "<div id=\"country\">"._("Home Country").": ".$per->Country."</div>\n";
if ($per->URL) echo "<div id=\"URL\">"._("URL").": ".url2link($per->URL)."</div>\n";
if ($_SESSION['admin']==1) echo "<div class=\"upddate\">("._("Ind. info or Remarks last edited")." ".$per->UpdDate.")</div>\n";

echo "</div>";

// ********** HOUSEHOLD INFORMATION **********

echo "\n\n<!-- Household Information Section -->\n\n<div id=\"household-info\">";
if ($per->HouseholdID) {    // There is a household record, so let's get its data
  $result = mysql_query("SELECT * FROM household WHERE HouseholdID=$per->HouseholdID") or die("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
  if (mysql_num_rows($result) == 0) {
    printf(_("Failed to find a record for HouseholdID %s."),$per->HouseholdID);
  } else {    // Query is okay, so fetch and display info
    echo "<h3 class=\"info-title\">"._("Household Information")."</h3>";
    $house = mysql_fetch_object($result);
    if ($house->Phone) echo "<div id=\"phone\">"._("Landline Phone").": ".$house->Phone."</div>\n";
    if ($house->FAX) echo "<div id=\"fax\">"._("FAX").": ".$house->FAX."</div>\n";
//    if ($house->Phone or $house->FAX) echo "&nbsp;<br>";
    echo "<div id=\"address-block\">"._("Address").":\n";
    if ($house->NonJapan) {    // It's a non-Japanese address
      echo "<div id=\"nonjapan-address\">".d2h($house->LabelName)."<br />".d2h($house->Address)."</div>\n";
    } elseif ($house->PostalCode || $house->Address) {    // There is a Japanese address
      if ($house->PostalCode) {
        $result = sqlquery_checked("SELECT CONCAT(Prefecture,ShiKuCho) as text, Romaji FROM postalcode".
            " WHERE PostalCode = '".$house->PostalCode."'");
        if (mysql_num_rows($result) == 0) {
          echo("<strong>".sprintf(_("Missing record for Postal Code %s"),$house->PostalCode).".</strong>");
        } else {
          $postal = mysql_fetch_object($result);
          echo "<div id=\"address\">".$house->PostalCode." ".$postal->text." ".d2h($house->Address)."<br />";
          echo d2h($house->LabelName)."</div>";
          if ($_SESSION['romajiaddresses'] == "yes") {
            echo "<div id=\"romaji-address\">".d2h($house->RomajiAddress)." ".
            d2h($postal->Romaji)." ".$house->PostalCode."</div>";
          }
        }
      } else {
          echo "<div id=\"address\">（郵便番号不明） ".d2h($house->Address)."<br />";
          echo d2h($house->LabelName)."</div>";
          if ($_SESSION['romajiaddresses'] == "yes") {
            echo "<div id=\"romaji-address\">".d2h($house->RomajiAddress)." (no postal code)</div>";
          }
      }
    } else {
      echo "<div id=\"address\">("._("No address listed.").")<br />";
      echo d2h($house->LabelName)."</div>";
    }
    if ($_SESSION['admin']==1) echo "<div class=\"upddate\">("._("Household info last edited")." ".$house->UpdDate.")</div>\n";
    echo "</div>\n"; //end of address-block
    // *** get names of others in household, print it along with relation
  }
} else {
  echo _("No household information.");
}
echo "</div>\n"; //end of household-info
echo "</div>\n"; //end if info-block

if ($per->Remarks) echo "<p id=\"remarks\"><span class=\"inlinelabel\">"._("Remarks").":</span> ".email2link(url2link(d2h($per->Remarks)))."</p>\n";
echo "<h2 id=\"links\"><a href=\"edit.php?pid=".$pid."\">"._("Edit This Record")."</a>";
if ($per->HouseholdID) {
  echo "<a href=\"household.php?hhid=".$per->HouseholdID."\">"._("Go to Household Page")."</a>";
}
echo "<a href=\"multiselect.php?pspid=$pid\">"._("Go to Multi-Select")."</a>";
echo "</h2>";
?>

<!-- Categories Section -->

<div class="section">
<h3 class="section-title"><? echo _("Categories"); ?></h3>
<form action="<? echo $PHP_SELF."?pid=$pid"; ?>" method="post">
<div id="cats-button">
<input type="submit" value="<? echo _("Save Category Changes"); ?>" name="newcategory" />
<input type="hidden" name="pid" value="<? echo $pid; ?>"></div>
<?
// Coming from changing categories?
if ($newcategory) {
$msg .= "category change<br>";
  if (!$result = mysql_query("SELECT c.CategoryID, c.Category, p.PersonID ".
      "FROM category c LEFT JOIN percat p ON c.CategoryID=p.CategoryID and p.PersonID=$pid ".
      "ORDER BY case when p.PersonID is null then 1 else 0 end, c.Category")) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>");
  } else {
    while ($row = mysql_fetch_object($result)) {
      $catid = $row->CategoryID;
      if ($row->PersonID && !($_POST[$catid])) {
        if (!mysql_query("DELETE from percat WHERE CategoryID=$catid and PersonID=$pid")) {
          echo("<b>SQL Error ".mysql_errno()." during DELETE FROM percat: ".mysql_error()."</b>");
        }
      } elseif (!$row->PersonID && $_POST[$catid]) {
        if (!mysql_query("INSERT INTO percat(CategoryID,PersonID) VALUES($catid,$pid)")) {
          echo("<b>SQL Error ".mysql_errno()." during INSERT INTO percat: ".mysql_error()."</b>");
        }
      }
    }
  }
}

$result = sqlquery_checked("SELECT c.CategoryID, c.Category, p.PersonID ".
    "FROM category c LEFT JOIN percat p ON c.CategoryID=p.CategoryID AND p.PersonID=$pid ".
    "WHERE c.UseFor LIKE '%".($per->Organization ? "O" : "P")."%' ".
    "ORDER BY case when p.PersonID is null then 1 else 0 end, c.Category");
echo "<div id=\"cats-in\">";
while ($row = mysql_fetch_object($result)) {
  if (!($row->PersonID)) {
    echo "</div><div id=\"cats-out\">";
    echo "<label for=\"".$row->CategoryID."\" class=\"label-n-input\"><input type=\"checkbox\" name=\"".$row->CategoryID."\">".$row->Category."</label>\n";
    break;
  }
  echo "<label for=\"".$row->CategoryID."\" class=\"label-n-input\"><input type=\"checkbox\" name=\"".$row->CategoryID."\" checked>".$row->Category."</label>\n";
}
while ($row = mysql_fetch_object($result)) {
  echo "<label for=\"".$row->CategoryID."\" class=\"label-n-input\"><input type=\"checkbox\" name=\"".$row->CategoryID."\">".$row->Category."</label>\n";
}
echo "</div>";  //end of cats-out
?>
</form>
</div>

<!-- Organization Section -->
<? //if ($per->Organization==0) { ?>

<a name="org"></a>
<div class="section" id="orgsection">
<h3 class="section-title"><? echo _("Related Organizations"); ?></h3>

<? // FORM FOR ADDING ORGS ?>
<form name="orgform" id="orgform" method="POST" action="<? echo ${PHP_SELF}."?pid=$pid"; ?>" onSubmit="return ValidateOrg()">
<input type="hidden" name="pid" value="<? echo $pid; ?>" />
<? echo _("Organization ID"); ?>: <input type="text" name="orgid" id="orgid" style="width:5em;ime-mode:disabled" value="" />
<span id="orgname" style="color:darkred;font-weight:bold"></span><br />
(<label for="orgsearchtxt"><? echo _("Search"); ?>: </label><input type="text" name="orgsearchtxt" id="orgsearchtxt" style="width:7em" value="">
<input type="button" value="<? echo _("Search")."/"._("Browse"); ?>"
onclick="window.open('selectorg.php?txt='+encodeURIComponent(document.getElementById('orgsearchtxt').value),'selectorg','scrollbars=yes,width=800,height=600');">)
<br />
<label class="label-n-input"><input type="checkbox" name="leader"><?=_("Leader")?></label>
<input type="submit" value="<? echo _("Save Organization Assignment"); ?>" name="newperorg">
</form>

<?
// TABLE OF ORGANIZATIONS
$sql = "SELECT person.*,perorg.Leader,household.Address,postalcode.*,".
    "ca.Categories, e.Events".
    " FROM person INNER JOIN perorg on person.PersonID=perorg.OrgID".
    " LEFT JOIN household ON person.HouseholdID=household.HouseholdID".
    " LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode".
    " LEFT OUTER JOIN (SELECT pc.PersonID,GROUP_CONCAT(cat.Category ORDER BY cat.Category SEPARATOR '\\n')".
    " AS Categories FROM percat AS pc".
    " INNER JOIN category AS cat ON cat.CategoryID = pc.CategoryID GROUP BY pc.PersonID) AS ca".
    " ON ca.PersonID = person.PersonID".
    " LEFT OUTER JOIN (SELECT aq.PersonID,GROUP_CONCAT(CONCAT(Event,' [',attqty,'x]')".
    " ORDER BY Event SEPARATOR '\\n') AS Events FROM (SELECT PersonID,Event,COUNT(*) AS attqty FROM attendance AS at".
    " INNER JOIN event ev ON ev.EventID = at.EventID GROUP BY at.PersonID,at.EventID) AS aq".
    " GROUP BY aq.PersonID) AS e ON e.PersonID = person.PersonID".
    " WHERE perorg.PersonID=".$pid." ORDER BY person.Furigana";
//echo "<pre>$sql</pre>";
$result = sqlquery_checked($sql);
if (mysql_num_rows($result) == 0) {
  echo "<h3>"._("Current Organizations")."</h3>";
  echo "<p>"._("No organization associations. (You can add them here or in Multi-Select.)")."</p>";
} else {
  echo "<form class=\"msform\" action=\"multiselect.php\" method=\"post\" target=\"_top\">\n";
  echo "<h3 style=\"display:inline;margin-right:20px;\">"._("Current Organizations")." (".mysql_num_rows($result).")</h3>";
  echo "  <input type=\"hidden\" id=\"org_preselected\" name=\"preselected\" value=\"\">\n";
  echo "  <input type=\"submit\" value=\""._("Go to Multi-Select with these entries preselected")."\">\n";
  echo "</form>\n";
  echo "<table id=\"org-table\" class=\"tablesorter\" width=\"100%\" border=\"1\">";
  echo "<thead><tr>".str_replace("target","targetOrg",
  str_replace("ulSelectColumn","ulSelectColumnOrg",$tableheads))."</tr></thead>\n<tbody>";
  while ($row = mysql_fetch_object($result)) {
    $org_pids .= ",".$row->PersonID;
    echo "<tr".($row->Leader ? " class=\"leader\"" : "").">";
    echo "<td class=\"personid\">".$row->PersonID."</td>\n";
    echo "<td class=\"name-for-csv\" style=\"display:none\">".readable_name($row->FullName,$row->Furigana)."</td>";
    echo "<td class=\"name-for-display\" nowrap><span style=\"display:none\">".$row->Furigana."</span>";
    echo "<a href=\"individual.php?pid=".$row->PersonID."\">".
      readable_name($row->FullName,$row->Furigana,0,0,"<br />")."</a>".($row->Leader ? _(" [Leader]") : "")."</td>\n";
    echo "<td class=\"photo\">";
    echo ($row->Photo == 1) ? "<img border=0 src=\"photo.php?f=p".$row->PersonID."\" width=50>" : "";
    echo "</td>\n";
    if ($row->CellPhone && $row->Phone) {
      echo "<td class=\"phone\">".$row->Phone."<br>".$row->CellPhone."</td>\n";
    } else {
      echo "<td class=\"phone\">".$row->Phone."".$row->CellPhone."</td>\n";
    }
    echo "<td class=\"email\">".email2link($row->Email)."</td>\n";
    echo "<td class=\"address\">".$row->PostalCode.$row->Prefecture.$row->ShiKuCho.db2table($row->Address)."</td>\n";
    echo "<td class=\"birthdate\">".(($row->Birthdate!="0000-00-00") ? ((substr($row->Birthdate,0,4) == "1900") ? substr($row->Birthdate,5) : $row->Birthdate) : "")."</td>\n";
    echo "<td class=\"age\">".(($row->Birthdate!="0000-00-00") && (substr($row->Birthdate,0,4) != "1900") ? age($row->Birthdate) : "")."</td>\n";
    echo "<td class=\"sex\">".$row->Sex."</td>\n";
    echo "<td class=\"country\">".$row->Country."</td>\n";
    echo "<td class=\"url\">".$row->URL."</td>\n";
    echo "<td class=\"remarks\">".email2link(url2link(d2h($row->Remarks)))."</td>\n";
    echo "<td class=\"categories\">".d2h($row->Categories)."</td>\n";
    echo "<td class=\"events\">".d2h($row->Events)."</td>\n";
    echo "<td class=\"selectcol\">-</td>\n";
    echo "<td class=\"delete\"><button id=\"action-PerOrgDelete_memid-".$pid."_orgid-".$row->PersonID."\">"._("Del")."</button></td>\n";
    echo "</tr>\n";
  }
  echo "  </tbody></table>";
}

// TABLE OF MEMBERS
if ($per->Organization) {
  $sql = "SELECT person.*,perorg.Leader,household.Address,postalcode.*,".
  "ca.Categories,e.Events ".
  " FROM person INNER JOIN perorg on person.PersonID=perorg.PersonID".
  " LEFT JOIN household ON person.HouseholdID=household.HouseholdID".
  " LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode".
  " LEFT OUTER JOIN (SELECT pc.PersonID,GROUP_CONCAT(cat.Category ORDER BY cat.Category SEPARATOR '\\n') AS Categories".
  " FROM percat AS pc INNER JOIN category AS cat ON cat.CategoryID = pc.CategoryID GROUP BY pc.PersonID) AS ca".
  " ON ca.PersonID = person.PersonID".
  " LEFT OUTER JOIN (SELECT aq.PersonID,GROUP_CONCAT(CONCAT(aq.Event,' [',aq.attqty,'x]')".
  " ORDER BY Event SEPARATOR '\\n') AS Events FROM (SELECT PersonID,Event,COUNT(*) AS attqty FROM attendance at".
  " INNER JOIN event ev ON ev.EventID = at.EventID GROUP BY at.PersonID,at.EventID) AS aq GROUP BY aq.PersonID) AS e".
  " ON e.PersonID = person.PersonID".
  " WHERE perorg.OrgID=".$pid." ORDER BY person.Furigana";
//echo "<pre>$sql</pre>";
  $result = sqlquery_checked($sql);
  if (mysql_num_rows($result) == 0) {
    echo "<h3>"._("Current Members")."</h3>";
    echo "<p>"._("No members. (Add them on a member's personal page or in Multi-Select.)")."</p>";
  } else {
    echo "<form class=\"msform\" action=\"multiselect.php\" method=\"post\" target=\"_top\">\n";
    echo "  <h3 style=\"display:inline;margin-right:20px;\">"._("Current Members")." (".mysql_num_rows($result).")</h3>";
    echo "  <input type=\"hidden\" id=\"mem_preselected\" name=\"preselected\" value=\"\">\n";
    echo "  <input type=\"submit\" value=\""._("Go to Multi-Select with these entries preselected")."\">\n";
    echo "</form>\n";
    echo "<table id=\"member-table\" class=\"tablesorter\" width=\"100%\" border=\"1\">";
    echo "<thead><tr>".str_replace("target","targetMember",
    str_replace("ulSelectColumn","ulSelectColumnMember",$tableheads))."</tr></thead>\n<tbody>";
    while ($row = mysql_fetch_object($result)) {
      $mem_pids .= ",".$row->PersonID;
      echo "<tr".($row->Leader ? " class=\"leader\"" : "").">";
      echo "<td class=\"personid\">".$row->PersonID."</td>\n";
      echo "<td class=\"name-for-csv\" style=\"display:none\">".readable_name($row->FullName,$row->Furigana)."</td>";
      echo "<td class=\"name-for-display\" nowrap><span style=\"display:none\">".$row->Furigana."</span>";
      echo "<a href=\"individual.php?pid=".$row->PersonID."\">".
        readable_name($row->FullName,$row->Furigana,0,0,"<br />")."</a>".($row->Leader ? _(" [Leader]") : "")."</td>\n";
      echo "<td class=\"photo\">";
      echo ($row->Photo == 1) ? "<img border=0 src=\"photo.php?f=p".$row->PersonID."\" width=50>" : "";
      echo "</td>\n";
      if ($row->CellPhone && $row->Phone) {
        echo "<td class=\"phone\">".$row->Phone."<br>".$row->CellPhone."</td>\n";
      } else {
        echo "<td class=\"phone\">".$row->Phone."".$row->CellPhone."</td>\n";
      }
      echo "<td class=\"email\">".email2link($row->Email)."</td>\n";
      echo "<td class=\"address\">".$row->PostalCode.$row->Prefecture.$row->ShiKuCho.db2table($row->Address)."</td>\n";
      echo "<td class=\"birthdate\">".(($row->Birthdate!="0000-00-00") ? ((substr($row->Birthdate,0,4) == "1900") ? substr($row->Birthdate,5) : $row->Birthdate) : "")."</td>\n";
      echo "<td class=\"age\">".(($row->Birthdate!="0000-00-00") && (substr($row->Birthdate,0,4) != "1900") ? age($row->Birthdate) : "")."</td>\n";
      echo "<td class=\"sex\">".$row->Sex."</td>\n";
      echo "<td class=\"country\">".$row->Country."</td>\n";
      echo "<td class=\"url\">".$row->URL."</td>\n";
      echo "<td class=\"remarks\">".email2link(url2link(d2h($row->Remarks)))."</td>\n";
      echo "<td class=\"categories\">".d2h($row->Categories)."</td>\n";
      echo "<td class=\"events\">".d2h($row->Events)."</td>\n";
      echo "<td class=\"selectcol\">-</td>\n";
      echo "<td class=\"delete\"><button id=\"action-PerOrgDelete_memid-".$row->PersonID."_orgid-".$pid."\">"._("Del")."</button></td>\n";
      echo "</tr>\n";
    }
    echo "  </tbody></table>";
  }
} //end of "if this is an organization"
echo "</div>";

//} // end of "if not organization"  (commented out because I'm considering letting orgs belong to orgs)
?>

<!-- Contacts Section -->

<a name="contacts"></a>
<div class="section">
<?
echo "<h3 class=\"section-title\">"._("Contacts")."</h3>\n";

  // FORM FOR ADD OR EDIT OF A CONTACT
if ($editcontact) {   // A CONTACT IN THE TABLE IS TO BE EDITED
  echo "<p class=\"alert\">"._("Edit fields as needed and press 'Save Contact Entry'")."</h3>";
}
?>
  <form name="contactform" id="contactform" method="post" action="<? echo $PHP_SELF."?pid=$pid"; ?>#contacts" onSubmit="return ValidateContact()">
  <input type="hidden" name="pid" value="<? echo $pid; ?>" />
<? if ($editcontact) echo "  <input type=\"hidden\" name=\"cid\" value=\"$cid\">\n"; ?>
  <table><tr><td>
    <? echo _("Date"); ?>: <input type="text" name="date" id="contactdate" style="width:6em"
    value="<? echo ($editcontact ? $date : ""); ?>">
<br />
<?
$result = mysql_query("SELECT * FROM contacttype ORDER BY ContactType") or die("SQL Error ".mysql_errno().": ".mysql_error());
echo "<span style=\"white-space:nowrap\">"._("Type").": <select size=\"1\" id=\"ctype\" name=\"ctype\"><option value=\"NULL\">"._("Select...")."</option>\n";
while ($row = mysql_fetch_object($result)) {
  echo "<option value=\"".$row->ContactTypeID."\"".(($editcontact && $row->ContactTypeID==$ctype)?
        " selected":"")." style=\"background-color:#".$row->BGColor."\">".$row->ContactType."</option>\n";
}
echo "</select></span>\n";

if ($editcontact) {
  echo "<br>\n<input type=\"submit\" value=\""._("Save Changes")."\" name=\"editcontactsave\">";
} else {
  echo "<br>\n<input type=\"submit\" value=\""._("Save Contact Entry")."\" name=\"newcontact\">";
}
?>
</td><td>
<textarea id="contactdesc" name="desc" wrap="virtual">
<? if ($editcontact) echo preg_replace("=<br */?>=i", "", $desc); ?></textarea></td></tr></table>
</form>

<?
// TABLE OF CONTACT HISTORY
$result = sqlquery_checked("SELECT c.ContactID,c.ContactTypeID,t.ContactType,ContactDate,".
    "c.Description,t.BGColor FROM contact c,contacttype t WHERE c.ContactTypeID=t.ContactTypeID ".
    "AND c.PersonID=$pid ORDER BY c.ContactDate DESC, ContactID DESC");
if (mysql_num_rows($result) == 0) {
  echo("<p>No previous contacts recorded.</p>");
} else {
  echo "<table id=\"contact-table\" class=\"tablesorter\" width=\"100%\" border=\"1\"><thead><tr>";
  echo "<th>"._("Date")."</th><th>"._("Contact Type")."</th><th>"._("Description")."</th><th></th><th></th>\n";
  echo "</tr></thead><tbody>\n";
  $rownum = 0;
  while ($row = mysql_fetch_object($result)) {
    $rownum++;
    if ($editcontact && ($row->ContactID==$cid)) {
      $fcstart = "<span style=\"color:#FFFFFF\">";
      $fcend = "</span>";
    } else {
      $fcstart = $fcend = "";
    }
    echo "<tr bgcolor=\"#".($editcontact&&($row->ContactID==$cid)?"404040":$row->BGColor)."\"";
    if ($rownum > $_SESSION['displaydefault_contactnum']) echo " class=\"oldcontact\" style=\"display:none\"";
    echo "><td nowrap>".$fcstart;
    echo $row->ContactDate."<span style=\"display:none\">".$row->ContactID."</span>".$fcend."</td>\n";
    echo "<td nowrap>".$fcstart.$row->ContactType.$fcend."</td>\n";
    echo "<td>".$fcstart."<span class=\"readmore\">".url2link(d2h($row->Description))."</span>".$fcend."</td>\n";
    echo "<form method=\"post\" action=\"${PHP_SELF}?pid=$pid#contacts\">\n";
    echo "<td class=\"button-in-table\">";
    echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\">";
    echo "<input type=\"hidden\" name=\"cid\" value=\"$row->ContactID\">\n";
    echo "<input type=\"hidden\" name=\"ctype\" value=\"$row->ContactTypeID\">";
    echo "<input type=\"hidden\" name=\"date\" value=\"$row->ContactDate\">\n";
    echo "<input type=\"hidden\" name=\"desc\" value=\"".d2h($row->Description)."\">\n";
    echo "<input type=\"submit\" name=\"editcontact\" value=\""._("Edit")."\"></td></form>\n";
    echo "<form method=\"post\" action=\"${PHP_SELF}?pid=$pid#contacts\" onSubmit=";
    echo "\"return confirm('Are you sure you want to delete record of ".$row->ContactType;
    echo " on ".$row->ContactDate."?')\">\n";
    echo "<td class=\"button-in-table\">";
    echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";
    echo "<input type=\"hidden\" name=\"cid\" value=\"$row->ContactID\">";
    echo "<input type=\"submit\" name=\"delcontact\" value=\""._("Del")."\">";
    echo "</td>\n</form></tr>\n";
  }
  echo "</tbody></table>";
  if (mysql_num_rows($result) > $_SESSION['displaydefault_contactnum']) {
    echo "<div style=\"text-align:left\">";
    echo "<button id=\"oldcontact_show\" onclick=\"$('#oldcontact_show').hide();$('.oldcontact').show();\">"._("Show Older Records Also")."</button>";
    echo "<button class=\"oldcontact\" onclick=\"$('.oldcontact').hide();$('#oldcontact_show').show();\" style=\"display:none\">"._("Hide Older Records")."</button>";
    echo "</div>\n";
  }
}
echo "</div>";

if ($_SESSION['donations'] == "yes") {   // covers both DONATIONS and PLEDGES sections
?>

<!-- Donations Section -->

<a name="donations"></a>
<div class="section">
<?
  echo "<h3 class=\"section-title\">"._("Donations")."</h3>\n";

  // FORM FOR ADD OR EDIT OF A DONATION
  if ($editdonation) {   // A DONATION IN THE TABLE IS TO BE EDITED
    echo "<font color=\"red\"><b>Edit any fields you want to change, and Press 'SAVE' to save changes</b></font><br>";
  }
  echo "<form name=\"donationform\" method=\"POST\" action=\"${PHP_SELF}?pid=$pid#donations\" onSubmit=\"return ValidateDonation()\">\n";
  echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";
  if ($editdonation) echo "<input type=\"hidden\" name=\"did\" value=\"$did\">\n";
  echo "<label class=\"label-n-input\">"._("Date").
  ": <input type=\"text\" name=\"date\" id=\"donationdate\" style=\"width:6em\" value=\"".
      ($editdonation ? $date : "")."\"></label>\n";
  $sql = "SELECT pl.PledgeID, pl.DonationTypeID, pl.PledgeDesc, dt.BGColor FROM pledge pl ".
      "LEFT JOIN donationtype dt ON pl.DonationTypeID=dt.DonationTypeID WHERE PersonID=$pid ".
      "AND (EndDate IS NULL OR EndDate>CURDATE()) ORDER BY PledgeDesc";
  $result = sqlquery_checked($sql);
  echo "<label class=\"label-n-input\" class=\"pledges\">"._("Pledge").": ";
  echo "<select size=\"1\" name=\"plid\" onChange=\"SetDtypeSelect();\">";
  echo "<option value=\"NULL\">Select if pledge...</option>";
  while ($row = mysql_fetch_object($result)) {
    echo "<option value=\"".$row->PledgeID.":".$row->DonationTypeID."\"".(($editdonation && $row->PledgeID==$plid)?
        " selected":"")." style=\"background-color:#".$row->BGColor."\">$row->PledgeDesc</option>";
  }
  echo "</select></label>\n";
$result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
  echo "<label class=\"label-n-input\">"._("Donation Type").": ";
  echo "<select size=\"1\" name=\"dtype\"".(($editdonation && $plid>0)?" disabled":"").">";
  echo "<option value=\"NULL\">Select if not pledge...</option>";
  while ($row = mysql_fetch_object($result)) {
    echo "<option value=\"".$row->DonationTypeID."\"".(($editdonation && $row->DonationTypeID==$dtype)?
        " selected":"")." style=\"background-color:#".$row->BGColor."\">$row->DonationType</option>";
  }
  echo "</select></label>\n";
  echo "<label class=\"label-n-input\">"._("Amount").": ".$_SESSION['currency_mark'];
  echo "<input type=\"text\" name=\"amount\" style=\"width:6em\" value=\"".($editdonation?$amount:"")."\"></label>";
  echo "<label class=\"label-n-input\">"._("Description").": ";
  echo "<input type=\"text\" name=\"desc\" style=\"width:12em\" value=\"".($editdonation?$desc:"")."\"></label>";
  echo "<label class=\"label-n-input\">";
  echo "<input type=\"checkbox\" name=\"proc\"".($editdonation?($proc?" checked":""):"").">"._("Processed")."</label>";
  if ($editdonation) {
    echo "<input type=\"submit\" value=\"Save Changes\" name=\"editdonationsave\">";
  } else {
    echo "<input type=\"submit\" value=\"Save Donation Entry\" name=\"newdonation\">";
  }
  echo "</form>\n";

  // TABLE OF DONATIONS
  $sql = "SELECT d.*, dt.*, pl.PledgeDesc FROM donation d LEFT JOIN donationtype dt ".
      "ON d.DonationTypeID=dt.DonationTypeID LEFT JOIN pledge pl ON d.PledgeID=pl.PledgeID ".
      "WHERE d.PersonID=$pid ORDER BY d.DonationDate DESC, d.DonationTypeID";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  } elseif (mysql_num_rows($result) == 0) {
    echo("<p>No previous donations recorded.</p>");
  } else {
    echo "<table id=\"donation-table\" class=\"tablesorter\" width=\"100%\" border=\"1\"><thead>";
    echo "<tr><th>"._("Date")."</th><th>"._("Pledge or Donation Type")."</th><th>"._("Amount")."</th><th>"._("Description").
    "</th><th>"._("Proc.")."</th><th></th><th></th></tr>\n</thead><tbody>\n";
    $row_index = 0;
    while ($row = mysql_fetch_object($result)) {
      if ($editdonation && ($row->DonationID==$did)) {
        $fcstart = "<font color=#FFFFFF>";
        $fcend = "</font>";
      } else {
        $fcstart = $fcend = "";
      }
      echo "<tr bgcolor=#".($editdonation&&($row->DonationID==$did)?"404040":$row->BGColor).">";
      echo "<td align=\"center\" \"nowrap\">".$fcstart.$row->DonationDate.$fcend."</td>\n";
      if ($row->PledgeID) {
        echo "<td align=\"center\" \"nowrap\">".$fcstart.$row->PledgeDesc.$fcend."</td>\n";
      } else {
        echo "<td align=\"center\" \"nowrap\">".$fcstart.$row->DonationType.$fcend."</td>\n";
      }
      echo "<td align=\"center\" \"nowrap\">".$fcstart.$_SESSION['currency_mark']." ".
          number_format($row->Amount,$_SESSION['currency_decimals']).$fcend."</td>\n";
      echo "<td align=\"center\">".$fcstart.$row->Description.$fcend."</td>\n";
      echo "<td align=\"center\">".$fcstart.($row->Processed ? "〇" : "").$fcend."</td>\n";
      echo "<form method=\"POST\" action=\"${PHP_SELF}?pid=$pid#donations\">\n<td align=\"center\">";
      echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\">";
      echo "<input type=\"hidden\" name=\"did\" value=\"$row->DonationID\">\n";
      echo "<input type=\"hidden\" name=\"plid\" value=\"$row->PledgeID\">";
      echo "<input type=\"hidden\" name=\"dtype\" value=\"$row->DonationTypeID\">";
      echo "<input type=\"hidden\" name=\"date\" value=\"$row->DonationDate\">\n";
      echo "<input type=\"hidden\" name=\"amount\" value=\"".number_format($row->Amount,$_SESSION['currency_decimals'])."\">\n";
      echo "<input type=\"hidden\" name=\"desc\" value=\"$row->Description\">\n";
      echo "<input type=\"hidden\" name=\"proc\" value=\"$row->Processed\">\n";
      echo "<input type=\"submit\" name=\"editdonation\" value=\"Edit\"></td></form>\n";
      echo "<form method=\"POST\" action=\"${PHP_SELF}?pid=$pid#donations\" onSubmit=";
      echo "\"return confirm('Are you sure you want to delete record of ".
          $_SESSION['currency_mark'].number_format($row->Amount,$_SESSION['currency_decimals']).
          " on ".$row->DonationDate."?')\">\n<td align=center>";
      echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";
      echo "<input type=\"hidden\" name=\"did\" value=\"$row->DonationID\">";
      echo "<input type=\"submit\" name=\"deldonation\" value=\""._("Del")."\">";
      echo "</td>\n</form></tr>\n";
      //$row_index++;
      if (!$_GET['showalldonations'] && ++$row_index==$recent) {
        break;
      }
    }
    echo "</tbody></table>";
    if (mysql_num_rows($result) > $recent) {
      if ($_GET['showalldonations']) {
        echo "<div align=\"center\"><a href=\"individual.php?pid=".$pid."#donations\">Show Only Recent Donations</a></div>";
      } else {
        echo "<div align=\"center\"><a href=\"individual.php?pid=".$pid."&showalldonations=1#donations\">Show All Donations</a></div>";
      }
    }
  }
  echo "</div>\n";

  // ********** PLEDGES **********

  $period[1] = " "._("(one time)");
  $period[1] = "/"._("year");
  $period[4] = "/"._("quarter");
  $period[12] = "/"._("month");
?> 

<!-- Pledges Section -->

<a name="pledges"></a>
<div class="section">
<?
  echo "<h3 class=\"section-title\">"._("Pledges")."</h3>\n";

  $sql = "SELECT pl.*, dt.DonationType, SUM(IFNULL(d.Amount,0)) - (pl.Amount * (IF(pl.TimesPerYear=0,".
      "IF(CURDATE()<pl.EndDate,0,1),pl.TimesPerYear / 12 * PERIOD_DIFF(DATE_FORMAT(IF(pl.EndDate IS NULL ".
      "OR CURDATE( )<pl.EndDate,CURDATE( ), pl.EndDate), '%Y%m'), DATE_FORMAT(pl.StartDate, '%Y%m')))))".
      "Balance FROM pledge pl LEFT JOIN donationtype dt ON pl.DonationTypeID=dt.DonationTypeID ".
      "LEFT JOIN donation d ON pl.PledgeID=d.PledgeID ".
      "WHERE pl.PersonID=$pid GROUP BY pl.PledgeID ".
      "ORDER BY pl.StartDate DESC";
  if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  } elseif (mysql_num_rows($result) == 0) {
    echo("<p align=\"center\">No pledges. &nbsp; &nbsp; &nbsp;<a href=\"edit_pledge.php?pid=$pid\">Create New Pledge</a></p>");
  } else {
    echo "<table width=\"100%\" border=\"1\"><thead>\n";
    echo "<tr><th>"._("Type")."</th><th>"._("Description")."</th><th>"._("Amount")."</th><th>"._("Dates")."</th>";
    echo "<th>Balance</th><th></th></tr>\n</thead><tbody>\n";
    while ($row = mysql_fetch_object($result)) {
      echo "<tr>\n";
      echo "<td align=\"center\">".$row->DonationType."</td>\n";
      echo "<td align=\"center\">".db2table($row->PledgeDesc)."</td>\n";
      echo "<td align=\"center\" nowrap>".$_SESSION['currency_mark']." ".
          number_format($row->Amount,$_SESSION['currency_decimals']).$period[$row->TimesPerYear]."</td>\n";
      echo "<td align=\"center\" nowrap>".$row->StartDate."&#xFF5E;".($row->EndDate ? $row->EndDate : "")."</td>\n";
      echo "<td align=\"center\" nowrap".($row->Balance<0 ? " style=\"color:red\"" : "").">".
          $_SESSION['currency_mark']." ".number_format($row->Balance,$_SESSION['currency_decimals']).
          (($row->Balance<0 && $row->TimesPerYear>0) ? "<br>(".number_format(((0-$row->Balance)/$row->Amount*12/$row->TimesPerYear),0)." months)" : "")."</td>\n";
      echo "<td align=\"center\" nowrap><a href=\"edit_pledge.php?plid=".$row->PledgeID."\">"._("Edit/Del")."</a></td>\n";
      echo "</tr>\n";
    }
    echo "</tbody></table><a href=\"edit_pledge.php?pid=$pid\">"._("Create New Pledge")."</a>";
  }
  echo "</div>";

}  // end of donation & pledge section (conditional, only if set in config record)
?>

<!-- Attendance Section -->

<a name="attendance"></a>
<div class="section">
<?
echo "<h3 class=\"section-title\">"._("Event Attendance")."</h3>\n";

// FORM FOR ADDING ATTENDANCE
echo "<form name=\"attendform\" method=\"post\" action=\"${PHP_SELF}?pid=$pid#attendance\" onSubmit=\"return ValidateAttendance()\">\n";
echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
$result = sqlquery_checked("SELECT EventID,Event,UseTimes,IF(EventEndDate AND EventEndDate<CURDATE(),'inactive','active') AS Active FROM event ORDER BY Event");
//echo "<div style=\"display:inline-block\">\n";
echo "  <label class=\"label-n-input\">"._("Event").": ";
echo "    <select size=\"1\" id=\"eventid\" name=\"eid\">\n";
echo "      <option value=\"NULL\" selected>"._("Select...")."</option>\n";
while ($row = mysql_fetch_object($result)) {
//  echo "      <option value=\"".$row->EventID."\" class=\"".(($row->UseTimes==1)?"times ":"days ").$row->Active."\"".
//  ($row->Active=="active"?"":" style=\"display:none\"").">".$row->Event."</option>\n";
  echo "      <option value=\"".$row->EventID."\" class=\"".(($row->UseTimes==1)?"times ":"days ").$row->Active."\"".
  ">".$row->Event."</option>\n";
}
echo "    </select>\n  </label><br />\n";
//echo "<button id=\"activeevents\">"._("Hide Active")."</button><button id=\"oldevents\">"._("Show Old")."</button>\n</div>";
echo "<label class=\"label-n-input\">"._("Date").
": <input type=\"text\" name=\"date\" id=\"attenddate\" style=\"width:6em\" value=\"\" /></label>\n";
echo "<label class=\"label-n-input date\">"._("Optional End Date").": ".
"<input type=\"text\" name=\"enddate\" id=\"attendenddate\" style=\"width:6em\" value=\"\" /></label>\n";
echo "<span id=\"dayofweek\">"._("Days of week for date range").": ";
echo "<label class=\"label-n-input\"><input type=\"checkbox\" name=\"dow0\" checked />"._("Sunday")."</label>\n";
echo "<label class=\"label-n-input\"><input type=\"checkbox\" name=\"dow1\" checked />"._("Monday")."</label>\n";
echo "<label class=\"label-n-input\"><input type=\"checkbox\" name=\"dow2\" checked />"._("Tuesday")."</label>\n";
echo "<label class=\"label-n-input\"><input type=\"checkbox\" name=\"dow3\" checked />"._("Wednesday")."</label>\n";
echo "<label class=\"label-n-input\"><input type=\"checkbox\" name=\"dow4\" checked />"._("Thursday")."</label>\n";
echo "<label class=\"label-n-input\"><input type=\"checkbox\" name=\"dow5\" checked />"._("Friday")."</label>\n";
echo "<label class=\"label-n-input\"><input type=\"checkbox\" name=\"dow6\" checked />"._("Saturday")."</label></span>\n";
echo "<label class=\"label-n-input times\" style=\"display:none\">"._("Start Time").": ".
"<input type=\"text\" name=\"starttime\" id=\"attendstarttime\" style=\"width:4em\" value=\"\" /></label>\n";
echo "<label class=\"label-n-input times\" style=\"display:none\">"._("End Time").": ".
"<input type=\"text\" name=\"endtime\" id=\"attendendtime\" style=\"width:4em\" value=\"\" /></label>\n";
if ($per->Organization) {
  echo "<span id=\"attend-apply\">".
  sprintf(_("Record for: %sthis org only&nbsp; %sonly its members&nbsp; %sboth org and members"),
  "<label for=\"apply-org\" class=\"label-n-input\"><input type=\"radio\" id=\"apply-org\" name=\"apply\" value=\"org\" checked />",
  "</label><label for=\"apply-mem\" class=\"label-n-input\"><input type=\"radio\" id=\"apply-mem\" name=\"apply\" value=\"mem\" />",
  "</label><label for=\"apply-orgmem\" class=\"label-n-input\"><input type=\"radio\" id=\"apply-orgmem\" name=\"apply\" value=\"orgmem\" />")."</label>\n";
}
echo "<input type=\"submit\" value=\""._("Save Attendance Entry")."\" name=\"newattendance\" />\n";
echo "</form>\n";

// TABLE OF ATTENDANCE HISTORY
$result = sqlquery_checked("SELECT e.Event, e.EventID, e.Remarks, min(a.AttendDate) AS first, max(a.AttendDate) AS last,".
" COUNT(a.AttendDate) AS times, IF(e.UseTimes=1,SUM(TIME_TO_SEC(SUBTIME(a.EndTime,a.StartTime))) DIV 60,-1) AS minutes".
" FROM event e, attendance a WHERE e.EventID=a.EventID AND a.PersonID=".$pid." GROUP BY e.EventID ORDER BY first DESC");
if (mysql_num_rows($result) == 0) {
  echo "<p>No attendance records. (You can add records here or in Multi-Select.)</p>";
} else {
  echo "<table id=\"attend-table\" class=\"tablesorter\" width=\"100%\"><thead><tr>";
  echo "<th>"._("Event")."</th><th>"._("Dates")."</th><th>"._("Event Description")."</th><th></th>\n";
  echo "</tr></thead><tbody>\n";
  while ($row = mysql_fetch_object($result)) {
    echo "<tr><td nowrap><a href=\"attend_detail.php?nav=1&pidlist=$pid&eid=".$row->EventID."\">".d2h($row->Event)."</a></td>";
    if ($row->first == $row->last) {
      echo "<td nowrap>".$row->first;
    } else {
      echo "<td nowrap>".$row->first."～<br />".$row->last." [".$row->times."x]";
    }
    if ($row->minutes != -1) {
      echo "<br />".sprintf(_("[Total time %s]"),(($row->minutes-$row->minutes%60)/60).":".sprintf("%02d",$row->minutes%60));
    }
    echo "</td><td>".d2h($row->Remarks)."</td>\n";
    echo "<form method=\"POST\" action=\"${PHP_SELF}?pid=$pid\" onSubmit=";
    echo "\"return confirm('".sprintf(_("Are you sure you want to delete these %s attendance records?"),
    $row->times)."')\"><td class=\"button-in-table\">";
    echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";
    echo "<input type=\"hidden\" name=\"eid\" value=\"".$row->EventID."\">\n";
    echo "<input type=\"submit\" name=\"delattendance\" value=\""._("Del")."\">";
    echo "</td>\n</form></tr>";
  }
  echo "  </tbody></table>";
  echo _("(Note: to remove records, use Event Attendance's Detail Chart.)");
}
?>
</div>

<!-- Uploads Section -->
<a name="uploads"></a>
<div class="section">
<?
echo "<h3 class=\"section-title\">"._("Uploaded Files")."</h3>\n";

// FORM FOR UPLOADING FILES
echo "<form name=\"uploadform\" method=\"post\" action=\"${PHP_SELF}?pid=$pid#uploads\" enctype=\"multipart/form-data\" onSubmit=\"return ValidateUpload()\">\n";
echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\" />\n";
//echo "<input type=\"hidden\" id=\"uploadtime\" name=\"uploadtime\" value=\"".date()."\" />\n";
echo "<label for=\"uploadfile\" class=\"label-n-input\">"._("File").": ";
echo "<input id=\"uploadfile\" name=\"uploadfile\" type=\"file\" style=\"width:20em\" /></label>\n";
echo "<label for=\"uploaddesc\" class=\"label-n-input\">"._("Description").": ";
echo "<input id=\"uploaddesc\" name=\"uploaddesc\" type=\"text\" style=\"width:85%\" /></label>\n";
echo "<input type=\"submit\" value=\""._("Upload File and Save Entry")."\" name=\"newupload\" />\n";
echo "</form>\n";

// TABLE OF UPLOADED FILES
echo "<h3>"._("Uploaded Files")."</h3>\n";
if (!$result = mysql_query("SELECT *,DATE(UploadTime) AS UploadDate FROM upload WHERE PersonID = ".$pid." ORDER BY UploadTime DESC")) {
  echo "<b>SQL Error ".mysql_errno().": ".mysql_error()."</b>";
} elseif (mysql_num_rows($result) == 0) {
  echo "<p>"._("No uploaded files")."</p>";
} else {
  echo "<table id=\"upload-table\" class=\"tablesorter\" width=\"100%\" border=\"1\">";
  echo "<thead><tr><th>"._("Upload Date")."</th><th>"._("File Name")."</th><th>"._("Description")."</th><th>&nbsp;</th></tr></thead>\n<tbody>";
  while ($row = mysql_fetch_object($result)) {
    echo "<tr><td nowrap><span style=\"display:none\">".$row->UploadTime."</span>".$row->UploadDate."</td>\n";
    echo "<td><a href=\"download.php?uid=".$row->UploadID."\">".$row->FileName."</a></td>\n";
    echo "<td>".$row->Description."</td>\n";
    echo "<form method=\"POST\" action=\"${PHP_SELF}?pid=$pid\" onSubmit=";
    echo "\"return confirm('"._("Are you sure you want to delete this file?")."')\"><td class=\"button-in-table\">";
    echo "<input type=\"hidden\" name=\"pid\" value=\"$pid\">\n";
    echo "<input type=\"hidden\" name=\"uid\" value=\"".$row->UploadID."\">\n";
    echo "<input type=\"hidden\" name=\"ext\" value=\"".strtolower(pathinfo($row->FileName, PATHINFO_EXTENSION))."\">\n";
    echo "<input type=\"submit\" name=\"delupload\" value=\""._("Del")."\">";
    echo "</td>\n</form></tr>\n";
  }
  echo "  </tbody></table>";
}
echo "</div>";
echo "<script>\n$(\"#org_preselected\").val(\"".substr($org_pids,1)."\");\n</script>\n";
if ($per->Organization) echo "<script>\n$(\"#mem_preselected\").val(\"".substr($mem_pids,1)."\");\n</script>\n";
mysql_free_result($result);
?>

<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/JavaScript" src="js/jquery.ui.timepicker.js"></script>
<script type="text/JavaScript" src="js/jquery.readmore.js"></script>
<script type="text/JavaScript" src="js/tablesorter.js"></script>
<script type="text/javascript" src="js/table2CSV.js"></script>
<script type="text/javascript" src="js/jquery.columnmanager.pack.js"></script>
<script type="text/javascript" src="js/jquery.clickmenu.js"></script>

<script type="text/JavaScript">

$(document).ready(function(){
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  }); 

  <?
if($_SESSION['lang']=="ja_JP") {
  echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n";
  echo "  $.timepicker.setDefaults( $.timepicker.regional[\"ja\"] );\n";
}
?>
  $("#orgstartdate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#orgenddate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#contactdate").datepicker({ dateFormat: 'yy-mm-dd', maxDate: 0 });
  if ($("#contactdate").val()=="") $("#contactdate").datepicker('setDate', new Date());
  $("#donationdate").datepicker({ dateFormat: 'yy-mm-dd', maxDate: 0 });
  if ($("#contactdate").val()=="") $("#donationdate").datepicker('setDate', new Date());
  $("#attenddate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#attendenddate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#attendstarttime").timepicker();
  $("#attendendtime").timepicker();

  $("#contact-table").tablesorter({ sortList:[[0,1]], headers:{3:{sorter:false},4:{sorter:false}} });
  $("#attend-table").tablesorter({ sortList:[[1,1]], headers:{3:{sorter:false}} });
  $("#upload-table").tablesorter({ sortList:[[0,1]], headers:{3:{sorter:false}} });

  $("#org-table").tablesorter({ sortList:[[2,0]], headers:{<?=$sorterHeaders?>} });
  $('#org-table').columnManager({listTargetID:'targetOrg',
  onClass: 'advon',
  offClass: 'advoff',
  hideInList: [<? echo $hideInList; ?>],
  colsHidden: [<? echo $orgColsHidden; ?>],
  saveState: false});
  $('#ulSelectColumnOrg').clickMenu({onClick: function(){}});

  $("#member-table").tablesorter({ sortList:[[2,0]], headers:{<?=$sorterHeaders?>} });
  $('#member-table').columnManager({listTargetID:'targetMember',
  onClass: 'advon',
  offClass: 'advoff',
  hideInList: [<? echo $hideInList; ?>],
  colsHidden: [<? echo $memberColsHidden; ?>],
  saveState: false});
  $('#ulSelectColumnMember').clickMenu({onClick: function(){}});
  
  $("#orgid").keyup(function(){  //display Organization name when applicable ID is typed
    $("#orgname").load("ajax_request.php",{'req':'OrgName','orgid':$("#orgid").val()});
  });

  $("#ctype").change(function(){  //insert template text in Contact description when applicable ContactType is selected
    if (!$.trim($("#contactdesc").val())) {
      $("#contactdesc").load("ajax_request.php",{'req':'ContactTemplate','ctid':$("#ctype").val()});
    }
  });

  $.fn.readmore.defaults.substr_len = <? echo $_SESSION['displaydefault_contactsize']; ?>;
  $.fn.readmore.defaults.more_link = '<a class="more"><? echo _("[Read more]"); ?></a>';
  $(".readmore").readmore();
  
  $("#activeevents").click(function(){  //show or hide active events
    if ($("#activeevents").val()=="<?=_("Show Active")?>") {
      $("#eventid.active").show();
      $("#activeevents").val("<?=_("Hide Active")?>");
    } else {
      $("#eventid.active").hide();
      $("#activeevents").val("<?=_("Show Active")?>");
    }
  });
  $("#oldevents").click(function(){  //show or hide old events
    if ($("#oldevents").val()=="<?=_("Show Old")?>") {
      $("#eventid.old").show();
      $("#oldevents").val("<?=_("Hide Old")?>");
    } else {
      $("#eventid.old").hide();
      $("#oldevents").val("<?=_("Show Old")?>");
    }
  });
  $("#eventid").change(function(){  //display form stuff based on type of event selected
    if ($("#eventid option:selected").hasClass('times')) {
      $("label.times").show();
      //$("label.date").hide();
      //$("label.date > input").val("");
    } else {
      //$("label.date").show();
      $("label.times").hide();
      $("label.times > input").val("");
    }
  });

  // AJAX CALLS TO UPDATE DATABASE
  
  $("#orgsection").delegate("td.delete button", "click", function() {
    var row = $(this).closest('tr');
    row.addClass("delconfirm");
    if (confirm("<?=_("Are you sure you want to delete this record?")?>")) {
      row.addClass("delwait");
      row.removeClass("delconfirm");
      var parameters = $(this).attr("id").replace(/-/g,"=").replace(/_/g,"&");
      $.post("ajax_actions.php", parameters, function(data) {
        //alert(data);
        if (data.substr(0,1) == "*") {  //my clue that the delete succeeded
          row.remove();
        } else {
          row.addClass("delwait");
          alert(data);
        }
      });
    } else {
      row.removeClass("delconfirm");
    }
  });
  
<? if ($_GET['msg']) echo "  alert('".$_GET['msg']."');\n"; ?>
});

function ValidateOrg(){
  if ($('#orgid').val==""){
    alert('<? echo _("You must fill in an Organization ID or use Search/Browse."); ?>');
    document.orgform.orgid.focus();
    return false;
  }
  if ($('#orgname').text()=="") {
    alert('<? echo _("Not a valid Organization ID. If you\'re not sure, try Search/Browse."); ?>');
    document.orgform.orgid.focus();
    return false;
  }
  return true;
}

function ValidateContact(){
  if ($('#contactdate').val() == '') {
    alert('<? echo _("You must enter a date."); ?>');
    $('#contactdate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#contactdate').val()); }
  catch(error) {
    alert('<? echo _("Date is invalid."); ?>');
    return false;
  }
  if (document.contactform.ctype.selectedIndex == 0) {
  alert('<? echo _("You must select a Contact Type."); ?>');
    return false;
  }
  return true;
}

function ValidateDonation() {
  if ($('#donationdate').val() == '') {
    alert('<? echo _("You must enter a date."); ?>');
    $('#donationdate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#donationdate').val()); }
  catch(error) {
    alert('<? echo _("Date is invalid."); ?>');
    return false;
  }
  if (isDate(document.donationform.date.value,"past")==false){
    alert('<? echo _("Date cannot be in the future."); ?>');
    document.donationform.date.focus();
    return false;
  }
  if ((document.donationform.plid.selectedIndex == 0) && (document.donationform.dtype.selectedIndex == 0)) {
  alert('<? echo _("You must select either a Pledge or a Donation Type."); ?>');
    return false;
  }
  return true;
}

function ValidateAttendance(){
  if (document.attendform.eid.selectedIndex == 0) {
    alert('<? echo _("You must select an event."); ?>');
    return false;
  }
  if ($('#attenddate').val() == '') {
    alert('<? echo _("You must enter a date."); ?>');
    $('#attenddate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#attenddate').val()); }
  catch(error) {
    alert('<? echo _("Date is invalid."); ?>');
    $('#attenddate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#attendenddate').val()); }
  catch(error) {
    alert('<? echo _("Date is invalid."); ?>');
    $('#attendenddate').click();
    return false;
  }
  return true;
}

function ValidateUpload(){
  if ($('#uploadfile').val() == '') {
    alert('<? echo _("You must select a file."); ?>');
    return false;
  }
  $('#uploadtime').val(Date().getTimezoneOffset()/60);
  return true;
}

function SetDtypeSelect() {
  if (document.donationform.plid.selectedIndex == 0) {
    document.donationform.dtype.disabled = false;
  } else {
    document.donationform.dtype.disabled = true;
    var dtype = document.donationform.plid.options[document.donationform.plid.selectedIndex].value;
    dtype = dtype.substr(dtype.indexOf(':')+1);
    var i = 0;
    while (document.donationform.dtype.options[i].value != dtype) i++;
    document.donationform.dtype.selectedIndex = i;
  }
}
</script>
<? footer(1); ?>
