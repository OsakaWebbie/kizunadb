<?php
include("functions.php");
include("accesscontrol.php");

if (empty($_SESSION['displaydefault_actionnum'])) $_SESSION['displaydefault_actionnum'] = 5;
if (empty($_SESSION['displaydefault_actionsize'])) $_SESSION['displaydefault_actionsize'] = 200;
if (empty($_SESSION['displaydefault_donationnum'])) $_SESSION['displaydefault_donationnum'] = 10;

// so that upload file timestamps always work as in Japan, rather than wherever the server is...
sqlquery_checked("SET time_zone='+09:00'");

// REQUEST TO SAVE CATEGORY CHANGES
if (!empty($_POST['newcategory'])) {
  $result = sqlquery_checked("SELECT c.CategoryID, c.Category, p.PersonID ".
      "FROM category c LEFT JOIN percat p ON c.CategoryID=p.CategoryID and p.PersonID={$_POST['pid']} ".
      "ORDER BY case when p.PersonID is null then 1 else 0 end, c.Category");
  while ($row = mysqli_fetch_object($result)) {
    if ($row->PersonID && !isset($_POST['cat'.$row->CategoryID])) {
      sqlquery_checked("DELETE FROM percat WHERE CategoryID=".$row->CategoryID." AND PersonID={$_POST['pid']}");
    } elseif (!$row->PersonID && isset($_POST['cat'.$row->CategoryID])) {
      sqlquery_checked("INSERT INTO percat(CategoryID,PersonID) VALUES(".$row->CategoryID.",{$_POST['pid']})");
    }
  }
}

// A REQUEST TO ADD A PERORG RECORD?
if (!empty($_POST['newperorg'])) {
  $result = sqlquery_checked("SELECT * FROM person WHERE PersonID=".$_POST['orgid']." AND Organization=1");
  if (mysqli_num_rows($result) == 1) {
    sqlquery_checked("REPLACE INTO perorg(PersonID, OrgID, Leader)".
    "VALUES(".$_POST['pid'].", ".$_POST['orgid'].", ".($_POST['leader']?"1":"0").")");
    header("Location: individual.php?pid=".$_POST['pid']."#org");
    exit;
  }
}

// A REQUEST TO ADD AN ACTION RECORD?
if (!empty($_POST['newaction'])) {
  $result = sqlquery_checked("SELECT * FROM action WHERE PersonID={$_POST['pid']} AND ActionTypeID={$_POST['atype']} ".
    "AND ActionDate='{$_POST['date']}' AND Description= '".h2d($_POST['desc'])."'");
  if (mysqli_num_rows($result) == 0) {  // making sure this isn't an accidental repeat entry
    $result = sqlquery_checked("INSERT INTO action(PersonID, ActionTypeID, ActionDate, Description) ".
        "VALUES({$_POST['pid']}, {$_POST['atype']}, '{$_POST['date']}', '".h2d($_POST['desc'])."')");
    header("Location: individual.php?pid=".$_POST['pid']."#actions");
    exit;
  }
}

// A REQUEST TO DELETE AN ACTION RECORD?
if (!empty($_POST['delaction'])) {
  $result = sqlquery_checked("DELETE FROM action WHERE ActionID={$_POST['aid']}");
  header("Location: individual.php?pid=".$_POST['pid']."#actions");
  exit;
}

// A REQUEST TO UPDATE AN ACTION RECORD?
if (!empty($_POST['editactionsave'])) {
  $result = sqlquery_checked("UPDATE action SET ActionTypeID={$_POST['atype']}, ActionDate='{$_POST['date']}', ".
    "Description='".h2d($_POST['desc'])."' WHERE ActionID={$_POST['aid']}");
  header("Location: individual.php?pid=".$_POST['pid']."#actions");
  exit;
}

// A REQUEST TO ADD A DONATION RECORD?
if (!empty($_POST['newdonation'])) {
  $sql = "SELECT * FROM donation WHERE PersonID=".$_POST['pid']." AND DonationTypeID=".$_POST['dtype'].
    " AND DonationDate='".$_POST['date']."' AND PledgeID=".$_POST['plid'].
    " AND Amount=".str_replace(",","",$_POST['amount']." AND Description='".h2d($_POST['desc'])."'");
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) == 0) {  // making sure this isn't an accidental repeat entry
    $sql = "INSERT INTO donation(PersonID, PledgeID, DonationTypeID, DonationDate, ".
    "Amount, Description, Processed) VALUES(".$_POST['pid'].",".$_POST['plid'].",".$_POST['dtype'].",".
    "'".$_POST['date']."',".str_replace(",","",$_POST['amount']).",'".h2d($_POST['desc'])."',".($_POST['proc']?"1":"0").")";
    $result = sqlquery_checked($sql);
    header("Location: individual.php?pid=".$_POST['pid']."#donations");
  } else {
      echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
      echo "alert('"._("A donation on that date for that purpose and amount has already been recorded.")."')\n";
      echo "window.location = \"individual.php?pid=".$_POST['pid']."#donations\";\n";
      echo "</SCRIPT>\n";
  }
  exit;
}

// A REQUEST TO DELETE A DONATION RECORD?
if (!empty($_POST['deldonation'])) {
  $result = sqlquery_checked("DELETE FROM donation WHERE DonationID=".$_POST['did']);
  header("Location: individual.php?pid=".$_POST['pid']."#donations");
  exit;
}

// A REQUEST TO UPDATE A DONATION RECORD?
if (!empty($_POST['editdonationsave'])) {
  $sql = "UPDATE donation SET PledgeID=".$_POST['plid'].",DonationTypeID=".$_POST['dtype'].",DonationDate='".$_POST['date']."',".
      "Amount=".str_replace(",","",$_POST['amount']).",Description='".h2d($_POST['desc'])."',".
      "Processed=".($_POST['proc']?"1":"0")." WHERE DonationID={$_POST['did']}";
  $result = sqlquery_checked($sql);
  header("Location: individual.php?pid=".$_POST['pid']."#donations");
  exit;
}

// A REQUEST TO ADD A PLEDGE RECORD?
if (!empty($_POST['newpledge'])) {
  $sql = "SELECT * FROM pledge WHERE PersonID=".$_POST['pid']." AND DonationTypeID=".$_POST['dtype'].
    " AND StartDate='".$_POST['startdate']."' AND EndDate='".(!empty($_POST['enddate'])?$_POST['enddate']:'0000-00-00')."'".
    " AND Amount=".str_replace(",","",$_POST['amount'])." AND TimesPerYear=".$_POST['tpy'].
    " AND PledgeDesc='".h2d($_POST['pledgedesc'])."'";
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) == 0) {  // making sure this isn't an accidental repeat entry
    $sql = "INSERT INTO pledge(PersonID, DonationTypeID, PledgeDesc, StartDate, EndDate, Amount, TimesPerYear) ".
    "VALUES(".$_POST['pid'].",".$_POST['dtype'].",'".h2d($_POST['pledgedesc'])."','".$_POST['startdate']."','".
    (!empty($_POST['enddate'])?$_POST['enddate']:'0000-00-00')."',".str_replace(",","",$_POST['amount']).",".$_POST['tpy'].")";
    $result = sqlquery_checked($sql);
    header("Location: individual.php?pid=".$_POST['pid']."#pledges");
  } else {
      echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
      echo "alert('"._("A pledge with that description, dates, and amount has already been recorded.")."')\n";
      echo "window.location = \"individual.php?pid=".$_POST['pid']."#pledges\";\n";
      echo "</SCRIPT>\n";
  }
  exit;
}

// A REQUEST TO ADD ATTENDANCE RECORD(S)?
if (!empty($_POST['newattendance'])) {
  //make array of pids (single and/or org members)
  $pidarray = array();
  if (empty($_POST["apply"]) || !(strpos($_POST["apply"],"org")===false)) $pidarray[] = $_POST['pid'];
  if (!(strpos($_POST["apply"],"mem")===false)) {
    $result = sqlquery_checked("SELECT PersonID from perorg where OrgID=".$_POST['pid']);
    while ($row = mysqli_fetch_object($result)) $pidarray[] = $row->PersonID;
  }
  //make array of dates (single or range)
  $datearray = array();
  if ($_POST["enddate"] != "") {  //need to do a range of dates
    if ($_POST["date"] > $_POST["enddate"]) die("Error: End Date is earlier than Start Date.");
    for ($day=$_POST["date"]; $day<=$_POST["enddate"]; $day=date("Y-m-d", strtotime("$day +1 day"))) {
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
if (!empty($_POST['delattendance'])) {
  $result = sqlquery_checked("DELETE FROM attendance WHERE PersonID=".$_POST['pid']." AND EventID=".$_POST['eid']);
  header("Location: individual.php?pid=".$_POST['pid']."#attendance");
  exit;
}

// A REQUEST TO UPLOAD A FILE?
if (!empty($_POST['newupload'])) {
  if (is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['uploadfile']['name'], PATHINFO_EXTENSION));
    $result = sqlquery_checked("SELECT Extension FROM uploadtype WHERE Extension='$ext'");
    if (mysqli_num_rows($result) == 1) {
      sqlquery_checked("INSERT INTO upload(PersonID,UploadTime,FileName,Description)".
          "VALUES({$_POST['pid']},NOW(),'".h2d($_FILES['uploadfile']['name'])."','".h2d($_POST['uploaddesc'])."')");
      $uid = mysqli_insert_id($db);
      if (!move_uploaded_file($_FILES['uploadfile']['tmp_name'], CLIENT_PATH."/uploads/u$uid.$ext")) {
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
    switch ($_FILES['uploadfile']['error']) {
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
if (!empty($_POST['delupload'])) {
  if (!unlink(CLIENT_PATH."/uploads/u".$_POST['uid'].".".$_POST['ext'])) die("Failed to delete file.");
  $result = sqlquery_checked("DELETE FROM upload WHERE UploadID=".$_POST['uid']);
  header("Location: individual.php?pid=".$_POST['pid']."#uploads");
  exit;
}

// NOT AN EDIT SITUATION, SO GATHER AND DISPLAY THE INFORMATION
if (empty($_GET['pid'])) {
  echo "PersonID not passed.";
  exit;
}
$result = sqlquery_checked("SELECT * FROM person WHERE PersonID=".$_GET['pid']);
if (mysqli_num_rows($result) == 0) {
  echo("<b>Failed to find a record for PersonID {$_GET['pid']}.</b>");
  exit;
}
$per = mysqli_fetch_object($result);
header1("$per->FullName");

// Legacy column definitions removed - tables now use flextable
?>

<link rel="stylesheet" type="text/css" href="style.php?jquery=1&table=1" />
<?php header2(1);
echo "<h1 id=\"title\">".readable_name($per->FullName,$per->Furigana,$per->PersonID,$per->Organization,
    ($per->Organization?'<br /><span class="smaller">':'')).($per->Organization?'</span>':'')."</h1>";
if ($per->Photo) echo "<div id=\"photo\"><img src=\"photo.php?f=p".$_GET['pid']."\" width=\"150\" /></div>\n";
echo "<div id=\"info-block\">";

// ********** PERSONAL INFORMATION **********

echo "\n\n<!-- Personal Information Section -->\n\n";
echo "<div id=\"personal-info\"><h3 class=\"info-title\">"._("Personal Information")."</h3>";
if ($per->Organization) {
  echo "<div id=\"organization\">"._("Organization")."</div>\n";
}
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
if ($per->Email) echo "<div id=\"email\">"._("Email").": ".email2link($per->Email)."</div>\n";
if ($per->CellPhone) echo "<div id=\"cellphone\">"._("Cell Phone").": ".$per->CellPhone."</div>\n";
if ($per->Country) echo "<div id=\"country\">"._("Home Country").": ".$per->Country."</div>\n";
if ($per->URL) echo "<div id=\"URL\">"._("URL").": ".url2link($per->URL)."</div>\n";
echo "<div class=\"upddate\">("._("Ind. info or Remarks last edited")." ".$per->UpdDate.")</div>\n";

echo "</div>";

// ********** HOUSEHOLD INFORMATION **********

echo "\n\n<!-- Household Information Section -->\n\n<div id=\"household-info\">";
if ($per->HouseholdID) {    // There is a household record, so let's get its data
  $result = sqlquery_checked("SELECT * FROM household WHERE HouseholdID=$per->HouseholdID");
  if (mysqli_num_rows($result) == 0) {
    printf(_("Failed to find a record for HouseholdID %s."),$per->HouseholdID);
  } else {    // Query is okay, so fetch and display info
    echo "<h3 class=\"info-title\">"._("Household Information")."</h3>";
    $house = mysqli_fetch_object($result);
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
        if (mysqli_num_rows($result) == 0) {
          echo("<strong>".sprintf(_("Missing record for Postal Code %s"),$house->PostalCode).".</strong>");
        } else {
          $postal = mysqli_fetch_object($result);
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
    echo "<div class=\"upddate\">("._("Household info last edited")." ".$house->UpdDate.")</div>\n";
    echo "</div>\n"; //end of address-block
    // *** get names of others in household, print it along with relation
  }
} else {
  echo _("No household information.");
}
echo "</div>\n"; //end of household-info
echo "</div>\n"; //end if info-block

if ($per->Remarks) echo "<p id=\"remarks\"><span class=\"inlinelabel\">"._("Remarks").":</span> ".email2link(url2link(d2h($per->Remarks)))."</p>\n";
echo "<h2 id=\"links\"><a href=\"edit.php?pid=".$_GET['pid']."\">"._("Edit This Record")."</a>";
if ($per->HouseholdID) {
  echo "<a href=\"household.php?hhid=".$per->HouseholdID."\">"._("Go to Household Page")."</a>";
}
echo "<a href=\"multiselect.php?preselected={$_GET['pid']}\">"._("Go to Multi-Select")."</a>";
echo "</h2>";
?>

<!-- Categories Section -->

<div class="section">
<h3 class="section-title"><?=_("Categories")?></h3>
<form action="<?=$_SERVER['PHP_SELF']."?pid=".$_GET['pid']?>" method="post">
<div id="cats-button">
<input type="submit" value="<?=_("Save Category Changes")?>" name="newcategory" />
<input type="hidden" name="pid" value="<?=$_GET['pid']?>"></div>
<?php
$result = sqlquery_checked("SELECT c.CategoryID, c.Category, p.PersonID ".
    "FROM category c LEFT JOIN percat p ON c.CategoryID=p.CategoryID AND p.PersonID={$_GET['pid']} ".
    "WHERE c.UseFor LIKE '%".($per->Organization ? "O" : "P")."%' ".
    "ORDER BY case when p.PersonID is null then 1 else 0 end, c.Category");
echo "<div id=\"cats-in\">";
while ($row = mysqli_fetch_object($result)) {
  if (!($row->PersonID)) {
    echo "</div><div id=\"cats-out\">";
    echo "<label for=\"".$row->CategoryID."\" class=\"label-n-input\"><input type=\"checkbox\" id=\"".$row->CategoryID."\" name=\"cat".$row->CategoryID."\">".$row->Category."</label>\n";
    break;
  }
  echo "<label for=\"".$row->CategoryID."\" class=\"label-n-input\"><input type=\"checkbox\" id=\"".$row->CategoryID."\" name=\"cat".$row->CategoryID."\" checked>".$row->Category."</label>\n";
}
while ($row = mysqli_fetch_object($result)) {
  echo "<label for=\"".$row->CategoryID."\" class=\"label-n-input\"><input type=\"checkbox\" id=\"".$row->CategoryID."\" name=\"cat".$row->CategoryID."\">".$row->Category."</label>\n";
}
echo "</div>";  //end of cats-out
?>
</form>
</div>

<!-- Organization Section -->
<?php //if ($per->Organization==0) { ?>

<a id="org"></a>
<div class="section" id="orgsection">
<h3 class="section-title"><?=_("Related Organizations")?></h3>

<?php // FORM FOR ADDING ORGS ?>
<form name="orgform" id="orgform" method="POST" action="<?=$_SERVER['PHP_SELF']."?pid=".$_GET['pid']?>" onSubmit="return ValidateOrg()">
<input type="hidden" name="pid" value="<?=$_GET['pid']?>" />
<label class="label-n-input"><?=_("Organization ID")?>: <input type="text" name="orgid" id="orgid" style="width:5em;ime-mode:disabled" value="" /><span id="orgname" style="color:darkred;font-weight:bold"></span></label>
(<label class="label-n-input"><?=_("Search")?>: <input type="text" name="orgsearchtxt" id="orgsearchtxt" style="width:7em" value=""></label>
<input type="button" value="<?=_("Search")."/"._("Browse")?>"
onclick="window.open('selectorg.php?txt='+encodeURIComponent(document.getElementById('orgsearchtxt').value),'selectorg','scrollbars=yes,width=800,height=600');">)
<br />
<label class="label-n-input"><input type="checkbox" name="leader"><?=_("Leader")?></label>
<input type="submit" value="<?=_("Save Organization Assignment")?>" name="newperorg">
</form>

<?php
// Include flextable for both organizations and members tables
require_once("flextable.php");

// TABLE OF ORGANIZATIONS
// Get organization IDs where this person is a member
$result = sqlquery_checked("SELECT OrgID FROM perorg WHERE PersonID=".$_GET['pid']);
$org_pids = array();
while ($row = mysqli_fetch_object($result)) {
  $org_pids[] = $row->OrgID;
}

if (count($org_pids) == 0) {
  echo "<h3>"._("Current Organizations")."</h3>";
  echo "<p>"._("No organization associations. (You can add them here or in Multi-Select.)")."</p>";
} else {
  echo "<form class=\"msform\" action=\"multiselect.php\" method=\"post\" target=\"_top\">\n";
  echo "<h3 style=\"display:inline;margin-right:20px;\">"._("Current Organizations")." (".count($org_pids).")</h3>";
  echo "  <input type=\"hidden\" id=\"org_preselected\" name=\"preselected\" value=\"".implode(',', $org_pids)."\">\n";
  echo "  <input type=\"submit\" value=\""._("Go to Multi-Select with these entries preselected")."\">\n";
  echo "</form>\n";

  $showcols = ",".$_SESSION['org_showcols'].",";

  $tableopt = (object)[
    'ids' => implode(',', $org_pids),
    'keyfield' => 'person.PersonID',
    'tableid' => 'org',
    'order' => 'Furigana',
    'cols' => []
  ];

  // PersonID
  $tableopt->cols[] = (object)[
    'key' => 'personid',
    'sel' => 'person.PersonID',
    'label' => _('ID'),
    'show' => (stripos($showcols, ',personid,') !== FALSE)
  ];

  // Name columns
  $tableopt->cols[] = (object)[
    'key' => 'name',
    'sel' => 'person.Name',
    'label' => _('Name'),
    'show' => (stripos($showcols, ',name,') !== FALSE)
  ];

  $tableopt->cols[] = (object)[
    'key' => 'fullname',
    'sel' => 'person.FullName',
    'label' => _('Full Name'),
    'show' => (stripos($showcols, ',fullname,') !== FALSE)
  ];

  $tableopt->cols[] = (object)[
    'key' => 'furigana',
    'sel' => 'person.Furigana',
    'label' => ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")),
    'show' => (stripos($showcols, ',furigana,') !== FALSE),
    'sort' => 1
  ];

  // Photo
  $tableopt->cols[] = (object)[
    'key' => 'photo',
    'sel' => 'person.Photo',
    'label' => _('Photo'),
    'show' => (stripos($showcols, ',photo,') !== FALSE),
    'sortable' => false
  ];

  // Contact info
  $tableopt->cols[] = (object)[
    'key' => 'phones',
    'sel' => 'Phones',
    'label' => _('Phones'),
    'show' => (stripos($showcols, ',phones,') !== FALSE),
    'table' => 'person'
  ];

  $tableopt->cols[] = (object)[
    'key' => 'email',
    'sel' => 'person.Email',
    'label' => _('Email'),
    'show' => (stripos($showcols, ',email,') !== FALSE)
  ];

  // Address - computed from postalcode + household data
  $tableopt->cols[] = (object)[
    'key' => 'address',
    'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
    'label' => _('Address'),
    'show' => (stripos($showcols, ',address,') !== FALSE),
    'render' => 'multiline',
    'table' => 'person',
    'lazy' => TRUE
  ];

  // Demographics
  $tableopt->cols[] = (object)[
    'key' => 'birthdate',
    'sel' => 'person.Birthdate',
    'label' => _('Born'),
    'show' => (stripos($showcols, ',birthdate,') !== FALSE),
    'classes' => 'center'
  ];

  $tableopt->cols[] = (object)[
    'key' => 'age',
    'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
    'label' => _('Age'),
    'show' => (stripos($showcols, ',age,') !== FALSE),
    'classes' => 'center',
    'table' => 'person'
  ];

  $tableopt->cols[] = (object)[
    'key' => 'sex',
    'sel' => 'person.Sex',
    'label' => _('Sex'),
    'show' => (stripos($showcols, ',sex,') !== FALSE)
  ];

  $tableopt->cols[] = (object)[
    'key' => 'country',
    'sel' => 'person.Country',
    'label' => _('Country'),
    'show' => (stripos($showcols, ',country,') !== FALSE)
  ];

  $tableopt->cols[] = (object)[
    'key' => 'url',
    'sel' => 'person.URL',
    'label' => _('URL'),
    'show' => (stripos($showcols, ',url,') !== FALSE)
  ];

  $tableopt->cols[] = (object)[
    'key' => 'remarks',
    'sel' => 'person.Remarks',
    'label' => _('Remarks'),
    'show' => (stripos($showcols, ',remarks,') !== FALSE)
  ];

  // Categories
  $tableopt->cols[] = (object)[
    'key' => 'categories',
    'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
    'label' => _('Categories'),
    'show' => (stripos($showcols, ',categories,') !== FALSE),
    'lazy' => TRUE,
    'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID'
  ];

  // Events - complex GROUP_CONCAT with attendance counts
  $tableopt->cols[] = (object)[
    'key' => 'events',
    'sel' => "e.Events",
    'label' => _('Events'),
    'show' => (stripos($showcols, ',events,') !== FALSE),
    'lazy' => TRUE,
    'join' => "LEFT OUTER JOIN (SELECT aq.PersonID,GROUP_CONCAT(CONCAT(Event,' [',attqty,'x]') ORDER BY Event SEPARATOR '\\n') AS Events FROM (SELECT PersonID,Event,COUNT(*) AS attqty FROM attendance AS at INNER JOIN event ev ON ev.EventID = at.EventID GROUP BY at.PersonID,at.EventID) AS aq GROUP BY aq.PersonID) AS e ON e.PersonID = person.PersonID"
  ];

  // Leader indicator - join perorg to check if current person is leader of this org
  $tableopt->cols[] = (object)[
    'key' => 'leader',
    'sel' => 'perorg.Leader',
    'label' => 'Leader',
    'show' => FALSE,
    'colsel' => FALSE,
    'join' => 'LEFT JOIN perorg ON person.PersonID=perorg.OrgID AND perorg.PersonID='.$_GET['pid']
  ];

  // Delete button
  $tableopt->cols[] = (object)[
    'key' => 'del',
    'sel' => "CONCAT('<button id=\"action-PerOrgDelete_memid-".$_GET['pid']."_orgid-',person.PersonID,'\">"._("Del")."</button>')",
    'label' => ' ',
    'show' => TRUE,
    'sortable' => false,
    'colsel' => FALSE,
    'classes' => 'delete',
    'csv' => FALSE
  ];

  flextable($tableopt);
}

// TABLE OF MEMBERS
if ($per->Organization) {
  // Get member PersonIDs for this organization
  $result = sqlquery_checked("SELECT PersonID FROM perorg WHERE OrgID=".$_GET['pid']);
  $mem_pids = array();
  while ($row = mysqli_fetch_object($result)) {
    $mem_pids[] = $row->PersonID;
  }

  if (count($mem_pids) == 0) {
    echo "<h3>"._("Current Members")."</h3>";
    echo "<p>"._("No members. (Add them on a member's personal page or in Multi-Select.)")."</p>";
  } else {
    echo "<form class=\"msform\" action=\"multiselect.php\" method=\"post\" target=\"_top\">\n";
    echo "  <h3 style=\"display:inline;margin-right:20px;\">"._("Current Members")." (".count($mem_pids).")</h3>";
    echo "  <input type=\"hidden\" id=\"mem_preselected\" name=\"preselected\" value=\"".implode(',', $mem_pids)."\">\n";
    echo "  <input type=\"submit\" value=\""._("Go to Multi-Select with these entries preselected")."\">\n";
    echo "</form>\n";

    $showcols = ",".$_SESSION['member_showcols'].",";

    $tableopt = (object)[
      'ids' => implode(',', $mem_pids),
      'keyfield' => 'person.PersonID',
      'tableid' => 'member',
      'order' => 'Furigana',
      'cols' => []
    ];

    // PersonID
    $tableopt->cols[] = (object)[
      'key' => 'personid',
      'sel' => 'person.PersonID',
      'label' => _('ID'),
      'show' => (stripos($showcols, ',personid,') !== FALSE)
    ];

    // Name columns
    $tableopt->cols[] = (object)[
      'key' => 'name',
      'sel' => 'person.Name',
      'label' => _('Name'),
      'show' => (stripos($showcols, ',name,') !== FALSE)
    ];

    $tableopt->cols[] = (object)[
      'key' => 'fullname',
      'sel' => 'person.FullName',
      'label' => _('Full Name'),
      'show' => (stripos($showcols, ',fullname,') !== FALSE)
    ];

    $tableopt->cols[] = (object)[
      'key' => 'furigana',
      'sel' => 'person.Furigana',
      'label' => ($_SESSION['furiganaisromaji']=="yes" ? _("Romaji") : _("Furigana")),
      'show' => (stripos($showcols, ',furigana,') !== FALSE),
      'sort' => 1
    ];

    // Photo
    $tableopt->cols[] = (object)[
      'key' => 'photo',
      'sel' => 'person.Photo',
      'label' => _('Photo'),
      'show' => (stripos($showcols, ',photo,') !== FALSE),
      'sortable' => false
    ];

    // Contact info
    $tableopt->cols[] = (object)[
      'key' => 'phones',
      'sel' => 'Phones',
      'label' => _('Phones'),
      'show' => (stripos($showcols, ',phones,') !== FALSE),
      'table' => 'person'
    ];

    $tableopt->cols[] = (object)[
      'key' => 'email',
      'sel' => 'person.Email',
      'label' => _('Email'),
      'show' => (stripos($showcols, ',email,') !== FALSE)
    ];

    // Address - computed from postalcode + household data
    $tableopt->cols[] = (object)[
      'key' => 'address',
      'sel' => "CONCAT(IFNULL(household.PostalCode,''), IFNULL(postalcode.Prefecture,''), IFNULL(postalcode.ShiKuCho,''), IFNULL(household.Address,''))",
      'label' => _('Address'),
      'show' => (stripos($showcols, ',address,') !== FALSE),
      'render' => 'multiline',
      'table' => 'person',
      'lazy' => TRUE
    ];

    // Demographics
    $tableopt->cols[] = (object)[
      'key' => 'birthdate',
      'sel' => 'person.Birthdate',
      'label' => _('Born'),
      'show' => (stripos($showcols, ',birthdate,') !== FALSE),
      'classes' => 'center'
    ];

    $tableopt->cols[] = (object)[
      'key' => 'age',
      'sel' => "IF(person.Birthdate='0000-00-00','',TIMESTAMPDIFF(YEAR,person.Birthdate,CURDATE()))",
      'label' => _('Age'),
      'show' => (stripos($showcols, ',age,') !== FALSE),
      'classes' => 'center',
      'table' => 'person'
    ];

    $tableopt->cols[] = (object)[
      'key' => 'sex',
      'sel' => 'person.Sex',
      'label' => _('Sex'),
      'show' => (stripos($showcols, ',sex,') !== FALSE)
    ];

    $tableopt->cols[] = (object)[
      'key' => 'country',
      'sel' => 'person.Country',
      'label' => _('Country'),
      'show' => (stripos($showcols, ',country,') !== FALSE)
    ];

    $tableopt->cols[] = (object)[
      'key' => 'url',
      'sel' => 'person.URL',
      'label' => _('URL'),
      'show' => (stripos($showcols, ',url,') !== FALSE)
    ];

    $tableopt->cols[] = (object)[
      'key' => 'remarks',
      'sel' => 'person.Remarks',
      'label' => _('Remarks'),
      'show' => (stripos($showcols, ',remarks,') !== FALSE)
    ];

    // Categories
    $tableopt->cols[] = (object)[
      'key' => 'categories',
      'sel' => "GROUP_CONCAT(Category ORDER BY Category SEPARATOR '\\n')",
      'label' => _('Categories'),
      'show' => (stripos($showcols, ',categories,') !== FALSE),
      'lazy' => TRUE,
      'join' => 'LEFT JOIN percat ON person.PersonID=percat.PersonID LEFT JOIN category ON percat.CategoryID=category.CategoryID'
    ];

    // Events - complex GROUP_CONCAT with attendance counts
    $tableopt->cols[] = (object)[
      'key' => 'events',
      'sel' => "e.Events",
      'label' => _('Events'),
      'show' => (stripos($showcols, ',events,') !== FALSE),
      'lazy' => TRUE,
      'join' => "LEFT OUTER JOIN (SELECT aq.PersonID,GROUP_CONCAT(CONCAT(Event,' [',attqty,'x]') ORDER BY Event SEPARATOR '\\n') AS Events FROM (SELECT PersonID,Event,COUNT(*) AS attqty FROM attendance AS at INNER JOIN event ev ON ev.EventID = at.EventID GROUP BY at.PersonID,at.EventID) AS aq GROUP BY aq.PersonID) AS e ON e.PersonID = person.PersonID"
    ];

    // Leader indicator - join perorg to check if this person is a leader of this org
    $tableopt->cols[] = (object)[
      'key' => 'leader',
      'sel' => 'perorg.Leader',
      'label' => 'Leader',
      'show' => FALSE,
      'colsel' => FALSE,
      'join' => 'LEFT JOIN perorg ON person.PersonID=perorg.PersonID AND perorg.OrgID='.$_GET['pid']
    ];

    // Delete button
    $tableopt->cols[] = (object)[
      'key' => 'del',
      'sel' => "CONCAT('<button id=\"action-PerOrgDelete_memid-',person.PersonID,'_orgid-".$_GET['pid']."\">"._("Del")."</button>')",
      'label' => ' ',
      'show' => TRUE,
      'sortable' => false,
      'colsel' => FALSE,
      'classes' => 'delete',
      'csv' => FALSE
    ];

    flextable($tableopt);
  }
} //end of "if this is an organization"
echo "</div>";

?>

<!-- Actions Section -->

<a id="actions"></a>
<?php
// Actions section (no access control - visible to all users)
echo '<div id="actions" class="section"><h3 class="section-title">'._("Actions").'</h3>'."\n";

// Keep existing form for adding new actions
if (!empty($_GET['editaction'])) {
  echo "<p class=\"alert\">"._("Edit fields as needed and press 'Save Action Entry'")."</p>";
}
?>
<form name="actionform" id="actionform" method="post" action="<?=$_SERVER['PHP_SELF']."?pid=".$_GET['pid']?>#actions" onSubmit="return ValidateAction()">
  <input type="hidden" name="pid" value="<?=$_GET['pid']?>" />
<?php if (!empty($_GET['editaction'])) echo "  <input type=\"hidden\" name=\"aid\" value=\"{$_GET['aid']}\">\n"; ?>
  <label class="label-n-input"><?=_("Date")?>: <input type="text" name="date" id="actiondate" style="width:6em"
    value="<?=(!empty($_GET['editaction']) ? $_GET['date'] : "")?>"></label>
  <label class="label-n-input"><?=_("Type")?>: <select size="1" id="atype" name="atype"><option value="0"><?=_("Select...")?></option>
<?php
$result = sqlquery_checked("SELECT * FROM actiontype ORDER BY ActionType");
while ($row = mysqli_fetch_object($result)) {
  echo '<option value="'.$row->ActionTypeID.'"'.((!empty($_GET['editaction']) && $row->ActionTypeID==$_GET['atype'])?
        ' selected':'').' style="background-color:#'.$row->BGColor.'">'.$row->ActionType."</option>\n";
}
?>
</select></label>
<textarea id="actiondesc" name="desc" class="expanding">
<?php if (!empty($_GET['editaction'])) echo preg_replace("=<br */?>=i", "", $_GET['desc']); ?></textarea>
<?php if (!empty($_GET['editaction'])) {
  echo "<input type=\"submit\" value=\""._("Save Changes")."\" name=\"editactionsave\">";
} else {
  echo "<input type=\"submit\" value=\""._("Save Action Entry")."\" name=\"newaction\">";
} ?>
</form>

<?php
// Get action IDs
$result = sqlquery_checked("SELECT ActionID FROM action WHERE PersonID=".$_GET['pid']." ORDER BY ActionDate DESC");
$ids = [];
while ($row = mysqli_fetch_object($result)) $ids[] = $row->ActionID;

if (count($ids) == 0) {
  echo "<p>"._("No actions recorded.")."</p>";
} else {
  $tableopt = (object)[
    'ids' => implode(',', $ids),
    'keyfield' => 'action.ActionID',
    'tableid' => 'actions',
    'order' => 'action.ActionDate DESC',
    'showColumnSelector' => FALSE,  // Simple table
    'showBucket' => FALSE,           // Not person records
    'showCSV' => TRUE,               // Could be useful
    'maxnum' => 5,                   // Show only 5 initially, then "Show More Records"
    'cols' => [
      (object)[
        'key' => 'date',
        'sel' => 'action.ActionDate',
        'label' => _('Date'),
        'show' => TRUE,
        'classes' => 'nowrap'
      ],
      (object)[
        'key' => 'atype',
        'sel' => 'actiontype.ActionType',
        'label' => _('Action Type'),
        'show' => TRUE,
        'join' => 'LEFT JOIN actiontype ON actiontype.ActionTypeID=action.ActionTypeID',
        'classes' => 'nowrap'
      ],
      (object)[
        'key' => 'description',
        'sel' => 'action.Description',
        'label' => _('Description'),
        'show' => TRUE,
        'render' => 'remarks',
        'classes' => 'readmore'  // Flextable handles readmore!
      ],
      (object)[
        'key' => 'edit',
        'sel' => "CONCAT('<button class=\"action-edit-btn\" data-id=\"', action.ActionID, '\">"._('Edit')."</button>')",
        'label' => 'Edit',
        'show' => TRUE,
        'sortable' => FALSE,
        'colsel' => FALSE,
        'csv' => FALSE
      ],
      (object)[
        'key' => 'delete',
        'sel' => "CONCAT('<button class=\"action-delete-btn\" data-id=\"', action.ActionID, '\">"._('Del')."</button>')",
        'label' => 'Del',
        'show' => TRUE,
        'sortable' => FALSE,
        'colsel' => FALSE,
        'csv' => FALSE
      ]
    ]
  ];

  flextable($tableopt);
}
echo "</div>\n";
?>

<!-- Edit Action Dialog -->
<div id="action-edit-dialog" style="display:none">
  <form id="action-edit-form">
    <input type="hidden" name="id" id="action-edit-id">
    <label><?=_("Date")?>:
      <input type="text" name="ActionDate" id="action-edit-ActionDate" required>
    </label><br>
    <label><?=_("Type")?>:
      <select name="ActionTypeID" id="action-edit-ActionTypeID" required>
        <option value="">...</option>
        <?php
        $result = sqlquery_checked("SELECT * FROM actiontype ORDER BY ActionType");
        while ($row = mysqli_fetch_object($result)) {
          echo '<option value="'.$row->ActionTypeID.'" style="background-color:#'.$row->BGColor.'">'.$row->ActionType.'</option>';
        }
        ?>
      </select>
    </label><br>
    <label><?=_("Description")?>:<br>
      <textarea name="Description" id="action-edit-Description" style="width:100%;height:8em"></textarea>
    </label>
  </form>
</div>

<?php
if ($_SESSION['donations'] == "yes") {   // covers both DONATIONS and PLEDGES sections
?>

<!-- Donations Section -->

<a id="donations"></a>
<div class="section">
<h3 class="section-title"><?=_('Donations')?></h3>
<?php
  // FORM FOR ADD OR EDIT OF A DONATION
  if (!empty($_GET['editdonation'])) {   // A DONATION IN THE TABLE IS TO BE EDITED
    echo '<span class="alert"><b>'._('Edit any fields you want to change, and Press "SAVE" to save changes').'</b></span><br>';
  }
  echo "<form name=\"donationform\" id=\"donationform\" method=\"POST\" action=\"{$_SERVER['PHP_SELF']}?pid={$_GET['pid']}#donations\" onSubmit=\"return ValidateDonation()\">\n";
  echo "<input type=\"hidden\" name=\"pid\" value=\"{$_GET['pid']}\">\n";
  if (!empty($_GET['editdonation'])) echo "<input type=\"hidden\" name=\"did\" value=\"{$_GET['did']}\">\n";
  echo "<label class=\"label-n-input\">"._("Date").
  ": <input type=\"text\" name=\"date\" id=\"donationdate\" style=\"width:6em\" value=\"".
      (!empty($_GET['editdonation']) ? $_GET['date'] : "")."\"></label>\n";
  $sql = "SELECT pl.PledgeID, pl.DonationTypeID, pl.PledgeDesc, dt.BGColor FROM pledge pl ".
      "LEFT JOIN donationtype dt ON pl.DonationTypeID=dt.DonationTypeID WHERE PersonID={$_REQUEST['pid']} ".
      "AND (EndDate='0000-00-00' OR EndDate>CURDATE()) ORDER BY PledgeDesc";
  $result = sqlquery_checked($sql);
  echo "<label class=\"label-n-input pledges\">"._("Pledge").": ";
  echo "<select size=\"1\" name=\"plid\" id=\"plid\">\n";
  echo "  <option value=\"0\">"._("Select if pledge...")."</option>\n";
  while ($row = mysqli_fetch_object($result)) {
    echo "  <option value=\"".$row->PledgeID."\"".((!empty($_GET['editdonation']) && $row->PledgeID==$_GET['plid'])?
        " selected":"")." style=\"background-color:#".$row->BGColor."\">$row->PledgeDesc</option>\n";
  }
  echo "</select></label>\n";
$result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
  echo "<label class=\"label-n-input\">"._("Donation Type").": ";
  echo "<select size=\"1\" name=\"dtype\" id=\"dtype\"".((!empty($_GET['editdonation']) && $_GET['plid']>0)?" disabled":"").">";
  echo "<option value=\"0\">"._("Select if not pledge...")."</option>";
  while ($row = mysqli_fetch_object($result)) {
    echo "<option value=\"".$row->DonationTypeID."\"".((!empty($_GET['editdonation']) && $row->DonationTypeID==$_GET['dtype'])?
        " selected":"")." style=\"background-color:#".$row->BGColor."\">$row->DonationType</option>";
  }
  echo "</select></label>\n";
  echo "<label class=\"label-n-input\">"._("Amount").": ".$_SESSION['currency_mark'];
  echo "<input type=\"text\" name=\"amount\" style=\"width:6em\" value=\"".(!empty($_GET['editdonation'])?$_GET['amount']:"")."\"></label>";
  echo "<label class=\"label-n-input\">"._("Description").": ";
  echo "<input type=\"text\" name=\"desc\" style=\"width:30em\" value=\"".(!empty($_GET['editdonation'])?$_GET['desc']:"")."\"></label>";
  echo "<label class=\"label-n-input\">";
  echo "<input type=\"checkbox\" name=\"proc\"".(!empty($_GET['editdonation'])?($_GET['proc']?" checked":""):"").">"._("Processed")."</label>";
  if (!empty($_GET['editdonation'])) {
    echo "<input type=\"submit\" value=\""._("Save Changes")."\" name=\"editdonationsave\">";
  } else {
    echo "<input type=\"submit\" value=\""._("Save Donation Entry")."\" name=\"newdonation\">";
  }
  echo "</form>\n";

  // TABLE OF DONATIONS - Convert to flextable
  $result = sqlquery_checked("SELECT DonationID FROM donation WHERE PersonID=".$_GET['pid']." ORDER BY DonationDate DESC");
  $ids = [];
  while ($row = mysqli_fetch_object($result)) $ids[] = $row->DonationID;

  if (count($ids) == 0) {
    echo "<p>"._('No donations recorded.')."</p>";
  } else {
    $tableopt = (object)[
      'ids' => implode(',', $ids),
      'keyfield' => 'donation.DonationID',
      'tableid' => 'donations',
      'order' => 'donation.DonationDate DESC',
      'showColumnSelector' => FALSE,
      'showBucket' => FALSE,
      'showCSV' => TRUE,
      'maxnum' => 10,  // Show only 10 initially
      'cols' => [
        (object)[
          'key' => 'date',
          'sel' => 'donation.DonationDate',
          'label' => _('Date'),
          'show' => TRUE,
          'classes' => 'nowrap'
        ],
        (object)[
          'key' => 'type',
          'sel' => "IF(donation.PledgeID > 0, pledge.PledgeDesc, donationtype.DonationType)",
          'label' => _('Pledge or Donation Type'),
          'show' => TRUE,
          'join' => 'LEFT JOIN donationtype ON donation.DonationTypeID=donationtype.DonationTypeID LEFT JOIN pledge ON donation.PledgeID=pledge.PledgeID',
          'classes' => 'nowrap'
        ],
        (object)[
          'key' => 'amount',
          'sel' => "CONCAT('".$_SESSION['currency_mark']." ', FORMAT(donation.Amount, ".$_SESSION['currency_decimals']."))",
          'label' => _('Amount'),
          'show' => TRUE,
          'classes' => 'nowrap'
        ],
        (object)[
          'key' => 'description',
          'sel' => 'donation.Description',
          'label' => _('Description'),
          'show' => TRUE
        ],
        (object)[
          'key' => 'processed',
          'sel' => "IF(donation.Processed, '〇', '')",
          'label' => _('Proc.'),
          'show' => TRUE
        ],
        (object)[
          'key' => 'edit',
          'sel' => "CONCAT('<button class=\"donation-edit-btn\" data-id=\"', donation.DonationID, '\">"._('Edit')."</button>')",
          'label' => 'Edit',
          'show' => TRUE,
          'sortable' => FALSE,
          'colsel' => FALSE,
          'csv' => FALSE
        ],
        (object)[
          'key' => 'delete',
          'sel' => "CONCAT('<button class=\"donation-delete-btn\" data-id=\"', donation.DonationID, '\">"._('Del')."</button>')",
          'label' => 'Del',
          'show' => TRUE,
          'sortable' => FALSE,
          'colsel' => FALSE,
          'csv' => FALSE
        ]
      ]
    ];

    flextable($tableopt);
  }
  echo "</div>\n";

?>
<!-- Edit Donation Dialog -->
<div id="donation-edit-dialog" style="display:none">
  <form id="donation-edit-form">
    <input type="hidden" name="id" id="donation-edit-id">
    <label><?=_("Date")?>:
      <input type="text" name="DonationDate" id="donation-edit-DonationDate" required>
    </label><br>
    <label><?=_("Pledge")?>:
      <select name="PledgeID" id="donation-edit-PledgeID">
        <option value="0"><?=_("Select if pledge...")?></option>
      </select>
    </label><br>
    <label><?=_("Donation Type")?>:
      <select name="DonationTypeID" id="donation-edit-DonationTypeID">
        <option value="0"><?=_("Select if not pledge...")?></option>
        <?php
        $result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
        while ($row = mysqli_fetch_object($result)) {
          echo '<option value="'.$row->DonationTypeID.'" style="background-color:#'.$row->BGColor.'">'.$row->DonationType.'</option>';
        }
        ?>
      </select>
    </label><br>
    <label><?=_("Amount")?>: <?=$_SESSION['currency_mark']?>
      <input type="text" name="Amount" id="donation-edit-Amount" style="width:6em" required>
    </label><br>
    <label><?=_("Description")?>:
      <input type="text" name="Description" id="donation-edit-Description" style="width:30em">
    </label><br>
    <label>
      <input type="checkbox" name="Processed" id="donation-edit-Processed"> <?=_("Processed")?>
    </label>
  </form>
</div>
<?php

  // ********** PLEDGES **********
?>

<!-- Pledges Section -->

<a id="pledges"></a>
<div class="section">
<h3 class="section-title"><?=_('Pledges')?></h3>
<?php
  // FORM FOR ADD NEW PLEDGE
  echo "<form name=\"pledgeform\" id=\"pledgeform\" method=\"POST\" action=\"{$_SERVER['PHP_SELF']}?pid={$_GET['pid']}#pledges\" onSubmit=\"return ValidatePledge()\">\n";
  echo "<input type=\"hidden\" name=\"pid\" value=\"{$_GET['pid']}\">\n";

  // Donation Type
  $result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
  echo "<label class=\"label-n-input\">"._("Donation Type").": ";
  echo "<select size=\"1\" name=\"dtype\" id=\"pledge-dtype\">";
  echo "<option value=\"0\">"._("Select...")."</option>";
  while ($row = mysqli_fetch_object($result)) {
    echo "<option value=\"".$row->DonationTypeID."\" style=\"background-color:#".$row->BGColor."\">$row->DonationType</option>";
  }
  echo "</select></label>\n";

  // Description
  echo "<label class=\"label-n-input\">"._("Description").": ";
  echo "<input type=\"text\" name=\"pledgedesc\" id=\"pledge-desc\" style=\"width:30em\" maxlength=\"150\"></label>\n";

  // Start Date
  echo "<label class=\"label-n-input\">"._("Start Date").": ";
  echo "<input type=\"text\" name=\"startdate\" id=\"pledge-startdate\" style=\"width:6em\" value=\"".date("Y-m-d",mktime(gmdate("H")+9))."\"></label>\n";

  // End Date
  echo "<div style='display:inline-block'><label class=\"label-n-input\">"._("End Date").": ";
  echo "<input type=\"text\" name=\"enddate\" id=\"pledge-enddate\" style=\"width:6em\"></label><br>";
  echo "<span class=\"comment\">"._("(leave blank if no specified end to pledge)")."</span></div>\n";

  // Amount and Interval
  echo "<span style=\"white-space:nowrap\"><label class=\"label-n-input\" style=\"margin-right:0\">"._("Amount").": ".$_SESSION['currency_mark'];
  echo "<input type=\"text\" name=\"amount\" id=\"pledge-amount\" style=\"width:8em\" maxlength=\"12\"></label> / \n";
  echo "<select name=\"tpy\" id=\"pledge-tpy\" size=\"1\">\n";
  echo "<option value=\"12\" selected>"._("month")."</option>\n";
  echo "<option value=\"4\">"._("quarter")."</option>\n";
  echo "<option value=\"1\">"._("year")."</option>\n";
  echo "<option value=\"0\">"._("(one time)")."</option>\n";
  echo "</select></span>\n";

  echo "<input type=\"submit\" value=\""._("Save Pledge Entry")."\" name=\"newpledge\">\n";
  echo "</form>\n";

  // TABLE OF PLEDGES - Convert to flextable
  $result = sqlquery_checked("SELECT PledgeID FROM pledge WHERE PersonID=".$_GET['pid']." ORDER BY CASE WHEN EndDate='0000-00-00' THEN 1 ELSE 2 END, StartDate DESC");
  $ids = [];
  while ($row = mysqli_fetch_object($result)) $ids[] = $row->PledgeID;

  if (count($ids) == 0) {
    echo "<p>"._('No pledges recorded.')."</p>";
  } else {
    $tableopt = (object)[
      'ids' => implode(',', $ids),
      'keyfield' => 'pledge.PledgeID',
      'tableid' => 'pledges',
      'order' => 'CASE WHEN pledge.EndDate=\'0000-00-00\' THEN 1 ELSE 2 END, pledge.StartDate DESC',
      'showColumnSelector' => FALSE,
      'showBucket' => FALSE,
      'showCSV' => TRUE,
      'maxnum' => 10,
      'cols' => [
        (object)[
          'key' => 'dtype',
          'sel' => 'donationtype.DonationType',
          'label' => _('Donation Type'),
          'show' => TRUE,
          'join' => 'LEFT JOIN donationtype ON pledge.DonationTypeID=donationtype.DonationTypeID',
          'classes' => 'nowrap dtype'
        ],
        (object)[
          'key' => 'description',
          'sel' => 'pledge.PledgeDesc',
          'label' => _('Description'),
          'show' => TRUE,
          'classes' => 'description'
        ],
        (object)[
          'key' => 'amount',
          'sel' => "CONCAT('".$_SESSION['currency_mark']." ', FORMAT(pledge.Amount, ".$_SESSION['currency_decimals']."), CASE pledge.TimesPerYear WHEN 0 THEN ' "._("(one time)")."' WHEN 1 THEN '/"._("year")."' WHEN 4 THEN '/"._("quarter")."' WHEN 12 THEN '/"._("month")."' END)",
          'label' => _('Amount'),
          'show' => TRUE,
          'classes' => 'nowrap amount'
        ],
        (object)[
          'key' => 'dates',
          'sel' => "CONCAT(pledge.StartDate, IF(pledge.TimesPerYear!=0, CONCAT('～', IF(pledge.EndDate!='0000-00-00', pledge.EndDate, '')), ''))",
          'label' => _('Dates'),
          'show' => TRUE,
          'classes' => 'nowrap dates'
        ],
        (object)[
          'key' => 'balance',
          'sel' => "CONCAT(IF(SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(pledge.TimesPerYear=0, IF(CURDATE()<pledge.StartDate,0,1), pledge.TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(pledge.EndDate='0000-00-00' OR CURDATE()<pledge.EndDate,CURDATE(), pledge.EndDate), '%Y%m'), DATE_FORMAT(pledge.StartDate, '%Y%m'))))) < 0, '<span style=\"color:red\">', ''), '".$_SESSION['currency_mark']." ', FORMAT(SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(pledge.TimesPerYear=0, IF(CURDATE()<pledge.StartDate,0,1), pledge.TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(pledge.EndDate='0000-00-00' OR CURDATE()<pledge.EndDate,CURDATE(), pledge.EndDate), '%Y%m'), DATE_FORMAT(pledge.StartDate, '%Y%m'))))), ".$_SESSION['currency_decimals']."), IF(SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(pledge.TimesPerYear=0, IF(CURDATE()<pledge.StartDate,0,1), pledge.TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(pledge.EndDate='0000-00-00' OR CURDATE()<pledge.EndDate,CURDATE(), pledge.EndDate), '%Y%m'), DATE_FORMAT(pledge.StartDate, '%Y%m'))))) < 0 AND pledge.TimesPerYear>0, CONCAT('<br>(', ROUND((0-SUM(IFNULL(donation.Amount,0)) + (pledge.Amount * pledge.TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(pledge.EndDate='0000-00-00' OR CURDATE()<pledge.EndDate,CURDATE(), pledge.EndDate), '%Y%m'), DATE_FORMAT(pledge.StartDate, '%Y%m'))))/pledge.Amount*12/pledge.TimesPerYear), ' months)'), ''), IF(SUM(IFNULL(donation.Amount,0)) - (pledge.Amount * (IF(pledge.TimesPerYear=0, IF(CURDATE()<pledge.StartDate,0,1), pledge.TimesPerYear/12 * PERIOD_DIFF(DATE_FORMAT(IF(pledge.EndDate='0000-00-00' OR CURDATE()<pledge.EndDate,CURDATE(), pledge.EndDate), '%Y%m'), DATE_FORMAT(pledge.StartDate, '%Y%m'))))) < 0, '</span>', ''))",
          'label' => _('Balance'),
          'show' => TRUE,
          'join' => 'LEFT JOIN donation ON pledge.PledgeID=donation.PledgeID',
          'classes' => 'nowrap balance'
        ],
        (object)[
          'key' => 'edit',
          'sel' => "CONCAT('<button class=\"pledge-edit-btn\" data-id=\"', pledge.PledgeID, '\">"._('Edit')."</button>')",
          'label' => 'Edit',
          'show' => TRUE,
          'sortable' => FALSE,
          'colsel' => FALSE,
          'csv' => FALSE
        ],
        (object)[
          'key' => 'delete',
          'sel' => "CONCAT('<button class=\"pledge-delete-btn\" data-id=\"', pledge.PledgeID, '\">"._('Del')."</button>')",
          'label' => 'Del',
          'show' => TRUE,
          'sortable' => FALSE,
          'colsel' => FALSE,
          'csv' => FALSE
        ]
      ]
    ];

    require_once('flextable.php');
    flextable($tableopt);
  }
  echo "</div>\n";
?>

<!-- Pledge Edit Dialog -->
<div id="pledge-edit-dialog" style="display:none">
  <form id="pledge-edit-form">
    <input type="hidden" name="id" id="pledge-edit-id">
    <input type="hidden" name="PersonID" id="pledge-edit-PersonID" value="<?=$_GET['pid']?>">

    <label class="label-n-input"><?=_("Donation Type")?>:
      <select name="DonationTypeID" id="pledge-edit-DonationTypeID" required>
        <option value="0"><?=_("Select...")?></option>
        <?php
        $result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
        while ($row = mysqli_fetch_object($result)) {
          echo "<option value=\"".$row->DonationTypeID."\" style=\"background-color:#".$row->BGColor."\">$row->DonationType</option>\n";
        }
        ?>
      </select>
    </label><br>

    <label class="label-n-input"><?=_("Description")?>:
      <input type="text" name="PledgeDesc" id="pledge-edit-PledgeDesc" style="width:30em" maxlength="150" required>
    </label><br>

    <label class="label-n-input"><?=_("Start Date")?>:
      <input type="text" name="StartDate" id="pledge-edit-StartDate" style="width:6em" required>
    </label><br>

    <label class="label-n-input"><?=_("End Date")?>:
      <input type="text" name="EndDate" id="pledge-edit-EndDate" style="width:6em">
      <span class="comment"><?=_("(leave blank if no specified end to pledge)")?></span>
    </label><br>

    <label class="label-n-input"><?=_("Amount")?>: <?=$_SESSION['currency_mark']?>
      <input type="text" name="Amount" id="pledge-edit-Amount" style="width:8em" maxlength="12" required>
    </label> /
    <select name="TimesPerYear" id="pledge-edit-TimesPerYear">
      <option value="12"><?=_("month")?></option>
      <option value="4"><?=_("quarter")?></option>
      <option value="1"><?=_("year")?></option>
      <option value="0"><?=_("(one time)")?></option>
    </select>
  </form>
</div>

<?php
}  // end of donation & pledge section (conditional, only if set in config record and permitted for this user)
?>

<!-- Attendance Section -->

<a id="attendance"></a>
<div class="section">
<?php
echo "<h3 class=\"section-title\">"._("Event Attendance")."</h3>\n";

// FORM FOR ADDING ATTENDANCE
echo "<form name=\"attendform\" id=\"attendform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}?pid={$_GET['pid']}#attendance\" onSubmit=\"return ValidateAttendance()\">\n";
echo "<input type=\"hidden\" name=\"pid\" value=\"{$_GET['pid']}\" />\n";
$result = sqlquery_checked("SELECT EventID,Event,UseTimes,IF(EventEndDate AND EventEndDate<CURDATE(),'inactive','active') AS Active FROM event ORDER BY Event");
//echo "<div style=\"display:inline-block\">\n";
echo "  <label class=\"label-n-input\">"._("Event").": ";
echo "    <select size=\"1\" id=\"eventid\" name=\"eid\">\n";
echo "      <option value=\"0\" selected>"._("Select...")."</option>\n";
while ($row = mysqli_fetch_object($result)) {
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
" FROM event e, attendance a WHERE e.EventID=a.EventID AND a.PersonID=".$_GET['pid']." GROUP BY e.EventID ORDER BY first DESC");
if (mysqli_num_rows($result) == 0) {
  echo "<p>"._("No attendance records. (You can add records here or in Multi-Select.)")."</p>";
} else {
  echo "<table id=\"attend-table\" class=\"tablesorter\" width=\"100%\"><thead><tr>";
  echo "<th>"._("Event")."</th><th>"._("Dates")."</th><th>"._("Event Description")."</th><th></th>\n";
  echo "</tr></thead><tbody>\n";
  while ($row = mysqli_fetch_object($result)) {
    echo "<tr><td nowrap><a href=\"attend_detail.php?nav=1&pidlist={$_GET['pid']}&eid=".$row->EventID."\">".d2h($row->Event)."</a></td>";
    if ($row->first == $row->last) {
      echo "<td nowrap>".$row->first;
    } else {
      echo "<td nowrap>".$row->first."～<br />".$row->last." [".$row->times."x]";
    }
    if ($row->minutes != -1) {
      echo "<br />".sprintf(_("[Total time %s]"),(($row->minutes-$row->minutes%60)/60).":".sprintf("%02d",$row->minutes%60));
    }
    echo "</td><td>".d2h($row->Remarks)."</td>\n";
    echo "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}?pid={$_GET['pid']}\" onSubmit=";
    echo "\"return confirm('".sprintf(_("Are you sure you want to delete these %s attendance records?"),
    $row->times)."')\"><td class=\"button-in-table\">";
    echo "<input type=\"hidden\" name=\"pid\" value=\"{$_GET['pid']}\">\n";
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
<a id="uploads"></a>
<div class="section">
<?php
echo "<h3 class=\"section-title\">"._("Uploaded Files")."</h3>\n";

// FORM FOR UPLOADING FILES
echo "<form name=\"uploadform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}?pid={$_GET['pid']}#uploads\" enctype=\"multipart/form-data\" onSubmit=\"return ValidateUpload()\">\n";
echo "<input type=\"hidden\" name=\"pid\" value=\"{$_GET['pid']}\" />\n";
echo "<label for=\"uploadfile\" class=\"label-n-input\">"._("File")._(" (max 8MB)").": ";
echo "<input id=\"uploadfile\" name=\"uploadfile\" type=\"file\" style=\"width:20em\" /></label>\n";
echo "<label for=\"uploaddesc\" class=\"label-n-input\">"._("Description").": ";
echo "<input id=\"uploaddesc\" name=\"uploaddesc\" type=\"text\" style=\"width:85%\" /></label>\n";
echo "<input type=\"submit\" value=\""._("Upload File and Save Entry")."\" name=\"newupload\" />\n";
echo "</form>\n";

// TABLE OF UPLOADED FILES
echo "<h3>"._("Uploaded Files")."</h3>\n";
$result = sqlquery_checked("SELECT *,DATE(UploadTime) AS UploadDate FROM upload WHERE PersonID = ".$_GET['pid']." ORDER BY UploadTime DESC");
if (mysqli_num_rows($result) == 0) {
  echo "<p>"._("No uploaded files")."</p>";
} else {
  echo "<table id=\"upload-table\" class=\"tablesorter\" width=\"100%\" border=\"1\">";
  echo "<thead><tr><th>"._("Upload Date")."</th><th>"._("File Name")."</th><th>"._("Description")."</th><th>&nbsp;</th></tr></thead>\n<tbody>";
  while ($row = mysqli_fetch_object($result)) {
    echo "<tr><td nowrap><span style=\"display:none\">".$row->UploadTime."</span>".$row->UploadDate."</td>\n";
    echo "<td><a href=\"download.php?uid=".$row->UploadID."\">".$row->FileName."</a></td>\n";
    echo "<td>".$row->Description."</td>\n";
    echo "<form method=\"POST\" action=\"{$_SERVER['PHP_SELF']}?pid={$_GET['pid']}\" onSubmit=";
    echo "\"return confirm('"._("Are you sure you want to delete this file?")."')\"><td class=\"button-in-table\">";
    echo "<input type=\"hidden\" name=\"pid\" value=\"{$_GET['pid']}\">\n";
    echo "<input type=\"hidden\" name=\"uid\" value=\"".$row->UploadID."\">\n";
    echo "<input type=\"hidden\" name=\"ext\" value=\"".strtolower(pathinfo($row->FileName, PATHINFO_EXTENSION))."\">\n";
    echo "<input type=\"submit\" name=\"delupload\" value=\""._("Del")."\">";
    echo "</td>\n</form></tr>\n";
  }
  echo "  </tbody></table>";
}
echo "</div>";
mysqli_free_result($result);
?>

<?php
// Load additional scripts needed by individual.php
// (Include jquery, jqueryui, tablesorter here in case flextable isn't called - load_scripts prevents double-loading)
load_scripts(['jquery', 'jqueryui', 'tablesorter', 'readmore', 'expanding']);
?>

<script type="text/javascript">
$(document).ready(function(){
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  });

  // Apply leader highlighting and text marking to org and member tables
  function applyLeaderMarking(tableId) {
    $('#' + tableId + ' tbody tr').each(function() {
      var leaderCell = $(this).find('td.leader');
      if (leaderCell.length && leaderCell.text() == '1') {
        // Add leader class to row for background color
        $(this).addClass('leader');

        // Find the name cell - it's the one with a link to individual.php
        // (Works regardless of which name column is visible or what language the interface is in)
        var nameCell = $(this).find('td:visible a[href*="individual.php"]').first().parent();
        if (nameCell.length) {
          // Only add [Leader] if it's not already there
          var cellHtml = nameCell.html();
          if (cellHtml.indexOf('[Leader]') === -1) {
            nameCell.append(' <?=_("[Leader]")?>');
          }
        }
      }
    });
  }

  // Apply on page load
  applyLeaderMarking('org-table');
  applyLeaderMarking('member-table');

  <?php
if($_SESSION['lang']=="ja_JP") {
  echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n";
}
?>
  $("#actiondate").datepicker({ dateFormat: 'yy-mm-dd' });
  if ($("#actiondate").val()=="") $("#actiondate").datepicker('setDate', new Date());
  $("#donationdate").datepicker({ dateFormat: 'yy-mm-dd', maxDate: 0 });
  if ($("#actiondate").val()=="") $("#donationdate").datepicker('setDate', new Date());
  $("#pledge-startdate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#pledge-enddate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#attenddate").datepicker({ dateFormat: 'yy-mm-dd' });
  $("#attendenddate").datepicker({ dateFormat: 'yy-mm-dd' });

  $("#action-table").tablesorter({ sortList:[[0,1]], headers:{3:{sorter:false},4:{sorter:false}} });
  $("#donation-table").tablesorter({ sortList:[[0,1]], headers:{5:{sorter:false},6:{sorter:false}} });
  $("#pledge-table").tablesorter({ headers:{5:{sorter:false}} });
  $("#attend-table").tablesorter({ sortList:[[1,1]], headers:{3:{sorter:false}} });
  $("#upload-table").tablesorter({ sortList:[[0,1]], headers:{3:{sorter:false}} });

  // Legacy columnmanager code removed - tables now use flextable
  
  $("#orgid").on('input propertychange', function(e){  //display Organization name when applicable ID is typed
    if (/\D/g.test(this.value))  {
      // Filter non-digits from input value.
      this.value = this.value.replace(/\D/g, '');
    }
    if (this.value != '') {
      $("#orgname").load("ajax_request.php",{'req':'OrgName','orgid':$("#orgid").val()});
    } else {
      $("#orgname").empty();
    }
  });

  $("#atype").change(function(){  //insert template text in Action description when applicable ActionType is selected
    if (!$.trim($("#actiondesc").val())) {
      $("#actiondesc").load("ajax_request.php",{'req':'ActionTemplate','atid':$("#atype").val()}, function() {
        $(this).change();
      });
    }
  });

  $("#plid").change(function(){  //select matching DonationType when Pledge is selected
    if ($(this).val() == 0) {
      $("#dtype").prop( "disabled", false ).val(0);
    } else {
      $.getJSON("ajax_request.php", {
        'req':'Unique',
        'table':'pledge',
        'col':'DonationTypeID',
        'PledgeID':$("#plid").val()
      }, function(data) {
        //console.log(data);
        if (data.alert) {
          alert(data.alert);
        } else {
          $("#dtype").val(data.DonationTypeID).prop( "disabled", true );
        }
      })
      .fail(function(jqXHR, textStatus, errorThrown) { alert('getJSON request failed! ' + textStatus); })
    }
  });

  $(".readmore").readmore({
    speed: 75,
    collapsedHeight: 100,
    heightMargin: 0,
    moreLink: '<a href="#"><?=_("[Read more]")?></a>',
    lessLink: '<a href="#"><?=_("[Close]")?></a>',
    blockProcessed: function(element, collapsible) {
      if (collapsible) {
        element.addClass('readmore-collapsed');
      }
    }
  });

  // Work around broken afterToggle callback by manually toggling the class
  $(document).on('click', '[data-readmore-toggle]', function() {
    var targetId = $(this).attr('aria-controls');
    var $target = $('#' + targetId);
    // Toggle happens after click, so we need to check current state and flip it
    if ($target.hasClass('readmore-collapsed')) {
      $target.removeClass('readmore-collapsed');
    } else {
      $target.addClass('readmore-collapsed');
    }
  });

  // Action Edit Dialog
  $('#action-edit-dialog').dialog({
    autoOpen: false,
    modal: true,
    width: 500,
    title: '<?=_("Edit Action")?>',
    buttons: [{
      text: '<?=_("Save")?>',
      click: function() {
        if (validateActionEdit()) {
          saveAction();
        }
      }
    }, {
      text: '<?=_("Cancel")?>',
      click: function() {
        $(this).dialog('close');
      }
    }]
  });

  // Edit button handler
  $(document).on('click', '.action-edit-btn', function() {
    var id = $(this).data('id');

    $.post('ajax_request.php', {req: 'Action', id: id}, function(data) {
      if (data.alert) {
        alert(data.alert);
        return;
      }

      $('#action-edit-id').val(data.ActionID);
      $('#action-edit-ActionDate').val(data.ActionDate);
      $('#action-edit-ActionTypeID').val(data.ActionTypeID);
      $('#action-edit-Description').val(data.Description);

      $('#action-edit-dialog').dialog('open');
    }, 'json');
  });

  // Delete button handler
  $(document).on('click', '.action-delete-btn', function() {
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var id = $btn.data('id');

    $row.addClass('delconfirm');

    if (confirm('<?=_("Are you sure you want to delete this record?")?>')) {
      $row.addClass('delwait').removeClass('delconfirm');

      $.post('ajax_actions.php', {action: 'ActionDelete', id: id}, function(data) {
        if (data.substr(0,1) == '*') {
          $row.remove();
          $('#actions-table').trigger('update');
        } else {
          $row.removeClass('delwait');
          alert(data);
        }
      });
    } else {
      $row.removeClass('delconfirm');
    }
  });

  // Validation (shared with existing add form if possible)
  function validateActionEdit() {
    if (!$('#action-edit-ActionDate').val()) {
      alert('<?=_("You must enter a date.")?>');
      return false;
    }
    if (!$('#action-edit-ActionTypeID').val()) {
      alert('<?=_("You must select an action type.")?>');
      return false;
    }
    return true;
  }

  // Save function
  function saveAction() {
    var formData = $('#action-edit-form').serializeArray();
    formData.push({name: 'action', value: 'ActionSave'});
    formData.push({name: 'PersonID', value: <?=$_GET['pid']?>}); // Add from page context

    $.post('ajax_actions.php', formData, function(data) {
      if (data.substr(0,1) == '*') {
        $('#action-edit-dialog').dialog('close');

        // Update the row in the table without page refresh
        var id = $('#action-edit-id').val();
        var $row = $('.action-edit-btn[data-id="' + id + '"]').closest('tr');

        // Update date cell
        $row.find('td.date').text($('#action-edit-ActionDate').val());

        // Update action type cell (get the selected option's text)
        var actionTypeText = $('#action-edit-ActionTypeID option:selected').text();
        $row.find('td.atype').text(actionTypeText);

        // Update description cell (preserve readmore structure)
        var newDesc = $('#action-edit-Description').val().replace(/\n/g, '<br>');
        $row.find('td.description .readmore').html(newDesc);
        // Re-initialize readmore on this cell
        $row.find('td.description .readmore').readmore('destroy').readmore({
          speed: 75,
          collapsedHeight: 100,
          heightMargin: 0,
          moreLink: '<a href="#"><?=_("[Read more]")?></a>',
          lessLink: '<a href="#"><?=_("[Close]")?></a>',
          blockProcessed: function(element, collapsible) {
            if (collapsible) {
              element.addClass('readmore-collapsed');
            }
          }
        });

        // Update tablesorter
        $('#actions-table').trigger('update');
      } else {
        alert(data);
      }
    });
  }

  // Donation Edit Dialog
  $('#donation-edit-dialog').dialog({
    autoOpen: false,
    modal: true,
    width: 550,
    title: '<?=_("Edit Donation")?>',
    buttons: [{
      text: '<?=_("Save")?>',
      click: function() {
        if (validateDonationEdit()) {
          saveDonation();
        }
      }
    }, {
      text: '<?=_("Cancel")?>',
      click: function() {
        $(this).dialog('close');
      }
    }]
  });

  // Handle pledge selection change in edit dialog (same as add form)
  $("#donation-edit-PledgeID").change(function(){
    if ($(this).val() == 0) {
      $("#donation-edit-DonationTypeID").prop("disabled", false).val(0);
    } else {
      $.getJSON("ajax_request.php", {
        'req':'Unique',
        'table':'pledge',
        'col':'DonationTypeID',
        'PledgeID':$("#donation-edit-PledgeID").val()
      }, function(data) {
        if (data.alert) {
          alert(data.alert);
        } else {
          $("#donation-edit-DonationTypeID").val(data.DonationTypeID).prop("disabled", true);
        }
      })
      .fail(function(jqXHR, textStatus, errorThrown) { alert('getJSON request failed! ' + textStatus); })
    }
  });

  // Edit button handler for donations
  $(document).on('click', '.donation-edit-btn', function() {
    var id = $(this).data('id');
    var pid = <?=$_GET['pid']?>;

    // Fetch donation data
    $.post('ajax_request.php', {req: 'Donation', id: id}, function(data) {
      if (data.alert) {
        alert(data.alert);
        return;
      }

      // Populate form
      $('#donation-edit-id').val(data.DonationID);
      // Store old pledge ID for later balance update
      $('#donation-edit-id').data('old-pledge-id', data.PledgeID);
      $('#donation-edit-DonationDate').val(data.DonationDate);
      $('#donation-edit-Amount').val(parseFloat(data.Amount).toFixed(<?=$_SESSION['currency_decimals']?>));
      $('#donation-edit-Description').val(data.Description);
      $('#donation-edit-Processed').prop('checked', data.Processed == 1);

      // Fetch pledges for this person
      $.post('ajax_request.php', {req: 'PledgesForPerson', pid: pid}, function(pledges) {
        var $select = $('#donation-edit-PledgeID');
        $select.empty().append('<option value="0"><?=_("Select if pledge...")?></option>');
        pledges.forEach(function(pledge) {
          $select.append('<option value="' + pledge.PledgeID + '">' + pledge.PledgeDesc + '</option>');
        });
        $select.val(data.PledgeID);

        // Set donation type and enable/disable based on pledge selection
        $('#donation-edit-DonationTypeID').val(data.DonationTypeID);
        if (data.PledgeID > 0) {
          $('#donation-edit-DonationTypeID').prop('disabled', true);
        } else {
          $('#donation-edit-DonationTypeID').prop('disabled', false);
        }
      }, 'json');

      $('#donation-edit-dialog').dialog('open');
    }, 'json');
  });

  // Delete button handler for donations
  $(document).on('click', '.donation-delete-btn', function() {
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var id = $btn.data('id');

    $row.addClass('delconfirm');

    if (confirm('<?=_("Are you sure you want to delete this record?")?>')) {
      $row.addClass('delwait').removeClass('delconfirm');

      // Get pledge ID before deleting (need to fetch it first)
      $.post('ajax_request.php', {req: 'Donation', id: id}, function(donationData) {
        var pledgeId = donationData.PledgeID;

        $.post('ajax_actions.php', {action: 'DonationDelete', id: id}, function(data) {
          if (data.substr(0,1) == '*') {
            $row.remove();
            $('#donations-table').trigger('update');

            // Update pledge balance if donation was connected to a pledge
            if (pledgeId > 0) {
              updatePledgeBalance(pledgeId);
            }
          } else {
            $row.removeClass('delwait');
            alert(data);
          }
        });
      }, 'json');
    } else {
      $row.removeClass('delconfirm');
    }
  });

  // Validation for donation edit
  function validateDonationEdit() {
    if (!$('#donation-edit-DonationDate').val()) {
      alert('<?=_("You must enter a date.")?>');
      return false;
    }
    if (!$('#donation-edit-Amount').val()) {
      alert('<?=_("You must enter an amount.")?>');
      return false;
    }
    // Must have either pledge or donation type
    if ($('#donation-edit-PledgeID').val() == 0 && $('#donation-edit-DonationTypeID').val() == 0) {
      alert('<?=_("You must select either a pledge or a donation type.")?>');
      return false;
    }
    return true;
  }

  // Save function for donations
  function saveDonation() {
    var formData = $('#donation-edit-form').serializeArray();
    formData.push({name: 'action', value: 'DonationSave'});
    formData.push({name: 'PersonID', value: <?=$_GET['pid']?>});

    // Always include DonationTypeID even if disabled (disabled fields don't serialize)
    formData.push({name: 'DonationTypeID', value: $('#donation-edit-DonationTypeID').val()});

    // Store old pledge ID to update balance if changed
    var oldPledgeId = $('#donation-edit-id').data('old-pledge-id');
    var newPledgeId = $('#donation-edit-PledgeID').val();

    $.post('ajax_actions.php', formData, function(data) {
      if (data.substr(0,1) == '*') {
        $('#donation-edit-dialog').dialog('close');

        // Update the row in the table without page refresh
        var id = $('#donation-edit-id').val();
        var $row = $('.donation-edit-btn[data-id="' + id + '"]').closest('tr');

        // Update date cell
        $row.find('td.date').text($('#donation-edit-DonationDate').val());

        // Update type cell (pledge desc or donation type)
        var pledgeId = $('#donation-edit-PledgeID').val();
        if (pledgeId > 0) {
          $row.find('td.type').text($('#donation-edit-PledgeID option:selected').text());
        } else {
          $row.find('td.type').text($('#donation-edit-DonationTypeID option:selected').text());
        }

        // Update amount cell (with currency formatting and comma separator)
        var amount = parseFloat($('#donation-edit-Amount').val().replace(/,/g, ''));
        var formattedAmount = amount.toLocaleString('en-US', {minimumFractionDigits: <?=$_SESSION['currency_decimals']?>, maximumFractionDigits: <?=$_SESSION['currency_decimals']?>});
        $row.find('td.amount').html('<?=$_SESSION['currency_mark']?> ' + formattedAmount);

        // Update description cell
        $row.find('td.description').text($('#donation-edit-Description').val());

        // Update processed cell
        $row.find('td.processed').text($('#donation-edit-Processed').is(':checked') ? '〇' : '');

        // Update tablesorter
        $('#donations-table').trigger('update');

        // Update pledge balance(s) if donation is connected to pledge(s)
        if (oldPledgeId > 0) {
          updatePledgeBalance(oldPledgeId);
        }
        if (newPledgeId > 0 && newPledgeId != oldPledgeId) {
          updatePledgeBalance(newPledgeId);
        }
      } else {
        alert(data);
      }
    });
  }

  // ========== PLEDGE HANDLERS ==========

  // Initialize pledge edit dialog
  $('#pledge-edit-dialog').dialog({
    autoOpen: false,
    modal: true,
    width: 500,
    title: '<?=_("Edit Pledge")?>',
    buttons: [{
      text: '<?=_("Save Changes")?>',
      click: function() {
        if (validatePledgeEdit()) {
          savePledge();
        }
      }
    }, {
      text: '<?=_("Cancel")?>',
      click: function() {
        $(this).dialog('close');
      }
    }]
  });

  // Initialize datepickers for pledge edit dialog
  $('#pledge-edit-StartDate').datepicker({ dateFormat: 'yy-mm-dd' });
  $('#pledge-edit-EndDate').datepicker({ dateFormat: 'yy-mm-dd' });

  // Edit button handler for pledges
  $(document).on('click', '.pledge-edit-btn', function() {
    var id = $(this).data('id');

    // Fetch pledge data
    $.post('ajax_request.php', {req: 'Pledge', id: id}, function(data) {
      if (data.alert) {
        alert(data.alert);
        return;
      }

      // Populate form
      $('#pledge-edit-id').val(data.PledgeID);
      $('#pledge-edit-DonationTypeID').val(data.DonationTypeID);
      $('#pledge-edit-PledgeDesc').val(data.PledgeDesc);
      $('#pledge-edit-StartDate').val(data.StartDate);
      $('#pledge-edit-EndDate').val(data.EndDate == '0000-00-00' ? '' : data.EndDate);
      $('#pledge-edit-Amount').val(parseFloat(data.Amount).toFixed(<?=$_SESSION['currency_decimals']?>));
      $('#pledge-edit-TimesPerYear').val(data.TimesPerYear);

      $('#pledge-edit-dialog').dialog('open');
    }, 'json')
    .fail(function(jqXHR, textStatus, errorThrown) {
      alert('Error calling ajax_request.php: ' + textStatus);
    });
  });

  // Delete button handler for pledges
  $(document).on('click', '.pledge-delete-btn', function() {
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var id = $btn.data('id');

    $row.addClass('delconfirm');

    // Check if pledge has donations before confirming
    $.post('ajax_request.php', {req: 'PledgeDonationCount', id: id}, function(data) {
      if (data.count > 0) {
        $row.removeClass('delconfirm');
        alert('<?=sprintf(_("There are %s donations applied to this pledge - please reassign the donations before deleting the pledge."), "' + data.count + '")?>'.replace('%s', data.count));
      } else {
        if (confirm('<?=_("Are you sure you want to delete this record?")?>')) {
          $row.addClass('delwait').removeClass('delconfirm');

          $.post('ajax_actions.php', {action: 'PledgeDelete', id: id, pid: <?=$_GET['pid']?>}, function(data) {
            if (data.substr(0,1) == '*') {
              $row.remove();
              $('#pledges-table').trigger('update');
            } else {
              $row.removeClass('delwait');
              alert(data);
            }
          });
        } else {
          $row.removeClass('delconfirm');
        }
      }
    }, 'json');
  });

  // Validation for pledge edit
  function validatePledgeEdit() {
    var date_regexp = /^\d\d\d\d-\d{1,2}-\d{1,2}$/;

    if ($('#pledge-edit-DonationTypeID').val() == '0') {
      alert('<?=_("Please choose a Donation Type.")?>');
      return false;
    }
    if (!$('#pledge-edit-PledgeDesc').val()) {
      alert('<?=_("Please fill in the Description.")?>');
      return false;
    }
    if (!$('#pledge-edit-StartDate').val()) {
      alert('<?=_("Please fill in a Start Date.")?>');
      return false;
    }
    if (!date_regexp.test($('#pledge-edit-StartDate').val())) {
      alert('<?=_("Start Date must be in the form of YYYY-MM-DD.")?>');
      return false;
    }
    var endDate = $('#pledge-edit-EndDate').val();
    if (endDate && !date_regexp.test(endDate)) {
      alert('<?=_("End Date must be in the form of YYYY-MM-DD.")?>');
      return false;
    }
    if (!$('#pledge-edit-Amount').val()) {
      alert('<?=_("Please fill in the Amount.")?>');
      return false;
    }
    return true;
  }

  // Save function for pledges
  function savePledge() {
    var formData = $('#pledge-edit-form').serializeArray();
    formData.push({name: 'action', value: 'PledgeSave'});
    formData.push({name: 'PersonID', value: <?=$_GET['pid']?>});

    $.post('ajax_actions.php', formData, function(data) {
      if (data.substr(0,1) == '*') {
        $('#pledge-edit-dialog').dialog('close');

        // Update the row in the table without page refresh
        var id = $('#pledge-edit-id').val();
        var $row = $('.pledge-edit-btn[data-id="' + id + '"]').closest('tr');

        // Update donation type cell
        $row.find('td.dtype').text($('#pledge-edit-DonationTypeID option:selected').text());

        // Update description cell
        $row.find('td.description').text($('#pledge-edit-PledgeDesc').val());

        // Update amount cell (with interval)
        var amount = parseFloat($('#pledge-edit-Amount').val().replace(/,/g, ''));
        var formattedAmount = amount.toLocaleString('en-US', {minimumFractionDigits: <?=$_SESSION['currency_decimals']?>, maximumFractionDigits: <?=$_SESSION['currency_decimals']?>});
        var tpy = $('#pledge-edit-TimesPerYear').val();
        var interval = '';
        switch(tpy) {
          case '0': interval = ' <?=_("(one time)")?>'; break;
          case '1': interval = '/<?=_("year")?>'; break;
          case '4': interval = '/<?=_("quarter")?>'; break;
          case '12': interval = '/<?=_("month")?>'; break;
        }
        $row.find('td.amount').html('<?=$_SESSION['currency_mark']?> ' + formattedAmount + interval);

        // Update dates cell
        var startDate = $('#pledge-edit-StartDate').val();
        var endDate = $('#pledge-edit-EndDate').val();
        var datesText = startDate;
        if (tpy != '0') {
          datesText += '～' + (endDate ? endDate : '');
        }
        $row.find('td.dates').text(datesText);

        // Update balance cell via AJAX
        updatePledgeBalance(id);

        // Update tablesorter
        $('#pledges-table').trigger('update');
      } else {
        alert(data);
      }
    });
  }

  // Update pledge balance cell
  function updatePledgeBalance(pledgeId) {
    $.post('ajax_request.php', {req: 'PledgeBalance', id: pledgeId}, function(data) {
      if (data.alert) {
        console.error('Error updating pledge balance:', data.alert);
        return;
      }

      var $row = $('.pledge-edit-btn[data-id="' + pledgeId + '"]').closest('tr');
      var balanceHTML = '';

      if (data.balance < 0) {
        balanceHTML = '<span style="color:red"><?=$_SESSION['currency_mark']?> ' +
          parseFloat(data.balance).toLocaleString('en-US', {
            minimumFractionDigits: <?=$_SESSION['currency_decimals']?>,
            maximumFractionDigits: <?=$_SESSION['currency_decimals']?>
          });

        if (data.months !== '') {
          balanceHTML += '<br>(' + data.months + ' months)';
        }
        balanceHTML += '</span>';
      } else {
        balanceHTML = '<?=$_SESSION['currency_mark']?> ' +
          parseFloat(data.balance).toLocaleString('en-US', {
            minimumFractionDigits: <?=$_SESSION['currency_decimals']?>,
            maximumFractionDigits: <?=$_SESSION['currency_decimals']?>
          });
      }

      $row.find('td.balance').html(balanceHTML);
    }, 'json');
  }

  // Apply gray background to closed pledges
  function applyClosedPledgeStyle() {
    var today = new Date();
    today.setHours(0, 0, 0, 0); // Midnight

    $('#pledges-table tbody tr').each(function() {
      var datesCell = $(this).find('td.dates');
      if (datesCell.length) {
        var datesText = datesCell.text();
        // Check if there's an end date (contains ～)
        if (datesText.indexOf('～') !== -1) {
          var parts = datesText.split('～');
          if (parts.length === 2 && parts[1].trim() !== '') {
            // There's an end date
            var endDate = new Date(parts[1].trim());
            if (endDate < today) {
              // Pledge is closed
              $(this).css('background-color', '#E0E0E0');
            }
          }
        }
      }
    });
  }

  // Apply on page load
  if ($('#pledges-table').length) {
    applyClosedPledgeStyle();
    // Re-apply after table sorting
    $('#pledges-table').on('sortEnd', function() {
      applyClosedPledgeStyle();
    });
  }

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
  
<?php if (!empty($_GET['msg'])) echo "  alert('".$_GET['msg']."');\n"; ?>
});

function ValidateOrg(){
  if ($('#orgid').val==""){
    alert('<?=_("You must fill in an Organization ID or use Search/Browse.")?>');
    document.orgform.orgid.focus();
    return false;
  }
  if ($('#orgname').text()=="") {
    alert('<?=_("Not a valid Organization ID. If you\'re not sure, try Search/Browse.")?>');
    document.orgform.orgid.focus();
    return false;
  }
  return true;
}

function ValidateAction(){
  if ($('#actiondate').val() == '') {
    alert('<?=_("You must enter a date.")?>');
    $('#actiondate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#actiondate').val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    return false;
  }
  if (document.actionform.atype.selectedIndex == 0) {
  alert('<?=_("You must select a Action Type.")?>');
    return false;
  }
  return true;
}

function ValidateDonation() {
  if ($('#donationdate').val() == '') {
    alert('<?=_("You must enter a date.")?>');
    $('#donationdate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#donationdate').val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    return false;
  }
  // I gave up for now - I can't get this to work, and it jumps past all other checks
  /*if (new Date(document.donationform.date.value) > new Date(y,m,d)) {
    alert('<?=_("Date cannot be in the future.")?>'+Date.parse(document.donationform.date.value)+" > "+today);
    document.donationform.date.focus();
    return false;
  }*/
  if ((document.donationform.plid.selectedIndex == 0) && (document.donationform.dtype.selectedIndex == 0)) {
  alert('<?=_("You must select either a Pledge or a Donation Type.")?>');
    return false;
  }
  if (document.donationform.amount.value == ""){
    alert('<?=_("You must enter an amount.")?>');
    document.donationform.amount.focus();
    return false;
  }
/*
  if (isNaN(document.donationform.amount.value)){
    alert('<?=_("Amount must be a number.")?>');
    document.donationform.amount.focus();
    return false;
  }
*/
  $("#dtype").prop( "disabled", false );
  return true;
}

function ValidatePledge() {
  var date_regexp = /^\d\d\d\d-\d{1,2}-\d{1,2}$/;

  if ($('#pledge-dtype').val() == '0') {
    alert('<?=_("Please choose a Donation Type.")?>');
    $('#pledge-dtype').focus();
    return false;
  }
  if ($('#pledge-desc').val() == '') {
    alert('<?=_("Please fill in the Description.")?>');
    $('#pledge-desc').focus();
    return false;
  }
  if ($('#pledge-startdate').val() == '') {
    alert('<?=_("Please fill in a Start Date.")?>');
    $('#pledge-startdate').focus();
    return false;
  }
  if (!date_regexp.test($('#pledge-startdate').val())) {
    alert('<?=_("Start Date must be in the form of YYYY-MM-DD.")?>');
    $('#pledge-startdate').focus();
    return false;
  }
  var endDate = $('#pledge-enddate').val();
  if (endDate && !date_regexp.test(endDate)) {
    alert('<?=_("End Date must be in the form of YYYY-MM-DD.")?>');
    $('#pledge-enddate').focus();
    return false;
  }
  if ($('#pledge-amount').val() == '') {
    alert('<?=_("Please fill in the Amount.")?>');
    $('#pledge-amount').focus();
    return false;
  }
  return true;
}

function ValidateAttendance(){
  if (document.attendform.eid.selectedIndex == 0) {
    alert('<?=_("You must select an event.")?>');
    return false;
  }
  if ($('#attenddate').val() == '') {
    alert('<?=_("You must enter a date.")?>');
    $('#attenddate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#attenddate').val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    $('#attenddate').click();
    return false;
  }
  try { $.datepicker.parseDate('yy-mm-dd', $('#attendenddate').val()); }
  catch(error) {
    alert('<?=_("Date is invalid.")?>');
    $('#attendenddate').click();
    return false;
  }
  // Validate times if event requires them
  if ($("#eventid option:selected").hasClass('times')) {
    if ($('#attendstarttime').val() == '' || $('#attendendtime').val() == '') {
      alert('<?=_("You must enter both start and end times for this event.")?>');
      return false;
    }
    // Validate time format (HH:MM)
    var timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
    if (!timeRegex.test($('#attendstarttime').val())) {
      alert('<?=_("Start time must be in HH:MM format (e.g., 09:30 or 14:00).")?>');
      $('#attendstarttime').focus();
      return false;
    }
    if (!timeRegex.test($('#attendendtime').val())) {
      alert('<?=_("End time must be in HH:MM format (e.g., 09:30 or 14:00).")?>');
      $('#attendendtime').focus();
      return false;
    }
  }
  return true;
}

function ValidateUpload() {
  if ($('#uploadfile').val() == '') {
      alert('<?=_("You must select a file.")?>');
      return false;
  }
  if (document.getElementById('uploadfile').files[0].size > 8*1024*1024) {
      alert('<?=_("File size cannot exceed 8MB. Yours is: ")?>'+Math.round(document.getElementById('uploadfile').files[0].size/1024/1024*100)/100+"MB");
      return false;
  }
  return true;
}
</script>
<?php footer(); ?>
