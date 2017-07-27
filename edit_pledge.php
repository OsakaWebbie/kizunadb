<?php
include("functions.php");
include("accesscontrol.php");

if (isset($_POST['edit'])) {
  if (isset($_POST['plid'])) {
    $sql = "UPDATE pledge SET DonationTypeID=".$_POST['dtype'].",PledgeDesc='".$_POST['desc']."',".
    "StartDate='".$_POST['startdate']."',EndDate=".($_POST['enddate']?"'".$_POST['enddate']."'":"NULL").",".
    "Amount=".str_replace(",","",$_POST['amount']).",TimesPerYear=".$_POST['tpy']." ".
    "WHERE PledgeID=".$_POST['plid']." LIMIT 1";
    $result = sqlquery_checked($sql);
    $sql = "UPDATE donation SET DonationTypeID=".$_POST['dtype']." WHERE PledgeID=".$_POST['plid'];
    $result = sqlquery_checked($sql);
  } else {
    $sql = "INSERT INTO pledge (PersonID,DonationTypeID,PledgeDesc,StartDate,EndDate,Amount,TimesPerYear) ".
    "VALUES (".$_POST['pid'].",'".$_POST['dtype']."','".$_POST['desc']."','".$_POST['startdate']."',".
    ($_POST['enddate']?"'".$_POST['enddate']."'":"NULL").",".$_POST['amount'].",".$_POST['tpy'].")";
    $result = sqlquery_checked($sql);
  }
  echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
  echo "window.location = \"individual.php?pid=".$_POST['pid']."#pledges\";\n";
  echo "</SCRIPT>\n";
  exit;
} elseif (isset($_POST['del'])) {
  if (!$_POST['plid']) die("No Pledge ID.");
  $sql = "SELECT DonationID FROM donation WHERE PledgeID=".$_POST['plid'];
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) > 0) {
    $message = sprintf(_("There are %s donations applied to this pledge - please <a href=\"individual.php?".
    "pid=%s#donations\">go back</a> and reassign them before deleting."),mysqli_num_rows($result),$_POST['pid']);
    $_GET['plid'] = $_POST['plid'];
  } else {
    $sql = "DELETE FROM pledge WHERE PledgeID=".$_POST['plid'];
    $result = sqlquery_checked($sql);
    echo "<SCRIPT FOR=window EVENT=onload LANGUAGE=\"Javascript\">\n";
    echo "window.location = \"individual.php?pid=".$_POST['pid']."#pledges\";\n";
    echo "</SCRIPT>\n";
    exit;
  }
}

if (isset($_GET['plid'])) {
  $sql = "SELECT pledge.*, FullName, Furigana FROM pledge LEFT JOIN person ON person.PersonID=pledge.PersonID ".
      "WHERE PledgeID=".$_GET['plid'];
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) == 0) die("<b>Failed to find a record for Pledge ID ".$_GET['plid'].".</b>");
  $old = mysqli_fetch_object($result);
  $result = sqlquery_checked("SELECT COUNT(DonationID) count, MIN(DonationDate) first, MAX(DonationDate) last ".
  "FROM donation WHERE PledgeID=".$_GET['plid']);
  $donation = mysqli_fetch_object($result);
} else if (!isset($_GET['pid'])) {
  die("You cannot call this page directly.");
  exit;
} elseif ($_SESSION['donations'] != "yes") {
  die("Pledge and Donation functionality is not available.");
  exit;
} else {
  $sql = "SELECT FullName, Furigana FROM person WHERE PersonID=".$_GET['pid'];
  $result = sqlquery_checked($sql);
  if (mysqli_num_rows($result) == 0) die("<b>Failed to find a record for Person ID ".$_GET['pid'].".</b>");
  $rec = mysqli_fetch_object($result);
}

header1(sprintf($plid?_("Edit Pledge for %s"):_("New Pledge Entry for %s"),readable_name($old->FullName,$old->Furigana)));
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/javascript">
$(document).ready(function(){
  <?php
if($_SESSION['lang']=="ja_JP") {
  echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n";
}
?>
  $("#startdate").datepicker({ dateFormat: 'yy-mm-dd' });
  if ($("#startdate").val()=="") $("#startdate").datepicker('setDate', new Date());
  $("#enddate").datepicker({ dateFormat: 'yy-mm-dd' });
});

date_regexp = /^\d\d\d\d-\d{1,2}-\d{1,2}$/;

function edit_validate() {
  f = document.editform;  //just an abbreviation
  f.edit.disable = true;  //to prevent double submit

  if (f.dtype.value == "NULL") {
    alert("<?=_("Please choose a Donation Type.")?>");
    f.dtype.select();
    return false;
  }
  if (f.startdate.value.length == 0) {
    alert("<?=_("Please fill in a Start Date.")?>");
    f.startdate.select();
    return false;
  }

  if (f.amount.value.length == 0) {
    alert("<?=_("Please fill in the Amount.")?>");
    f.amount.select();
    return false;
  }

  if (!date_regexp.test(f.startdate.value)) {
    alert("<?=_("Start Date must be in the form of YYYY-MM-DD.")?>");
    f.startdate.select();
    return false;
  }

  if (f.enddate.value && !date_regexp.test(f.enddate.value)) {
    alert("<?=_("End Date must be in the form of YYYY-MM-DD.")?>");
    f.enddate.select();
    return false;
  }
<?php if ($donation->count > 0) { ?>
  if (f.dtype.value != "<?=$old->DonationTypeID?>") {
    if (!confirm("<?=sprintf(_("Changing the Donation Type will also change the Donation Type for the %s donations recorded in fulfillment of this pledge (from %s to %s). Okay to do this?"),$donation->count,$donation->first,$donation->last)?>")) {
      $("#dtype").val(<?=$old->DonationTypeID?>);
      return false;
    }
  }
<?php } ?>

  return true;  //everything is cool
}

function del_validate() {
  $("#orgname").load("ajax_request.php",{'req':'DonationCount','plid':$("#plid").val()});
}
</script>
<?php
header2(1);
echo "<pre>".print_r($donation,true)."</pre>";
if ($message) echo "<h3>$message</h3>\n";
?>
<form name="editform" enctype="multipart/form-data" method="post" action="<?=$_SERVER['PHP_SELF']?>"
onsubmit="return edit_validate();"><br>
<input type="hidden" name="pid" value="<?=($_GET['plid'] ? $old->PersonID : $_GET['pid'])?>">
<?php if ($_GET['plid']) echo "<input type=\"hidden\" name=\"plid\" value=\"".$_GET['plid']."\">"; ?>
<input type="hidden" name="<?=($_GET['plid'] ? "updatepledge" : "insertpledge")?> value="yes">

<label class="label-n-input"><?=_("Donation Type")?>: <select id="dtype" name="dtype" size="1"><?php
if (!$plid) {
  echo "<option value=\"NULL\">Select...</option>\n";
}
$result = sqlquery_checked("SELECT * FROM donationtype ORDER BY DonationType");
while ($row = mysqli_fetch_object($result)) {
    echo "<option value=\"".$row->DonationTypeID."\"".(($_GET['plid'] && $row->DonationTypeID==$old->DonationTypeID)?
          " selected":"")." style=\"background-color:#".$row->BGColor."\">".$row->DonationType."</option>\n";
}
?></select></label>
<label class="label-n-input"><?=_("Description")?>: <input id="desc" name="desc" type="text" style="width:30em"
maxlength="50" value="<?=$old->PledgeDesc?>" /></label>
<label class="label-n-input"><?=_("Start Date")?>: <input type="text" id="startdate" name="startdate" style="width:6em"
value="<?=$_GET['plid'] ? $old->StartDate : date("Y-m-d",mktime(gmdate("H")+9))?>" /></label>
<label class="label-n-input"><?=_("End Date")?>: <input type="text" id="enddate" name="enddate" style="width:6em"
value="<?=$old->EndDate?>"> <span class="comment"><?=_("(leave blank if no specified end to pledge)")?></span></label>
<span style="white-space:nowrap"><label class="label-n-input" style="margin-right:0"><?=_("Amount")?>: <?=$_SESSION['currency_mark']?><input id="amount"
name="amount" type="text" style="width:8em" maxlength="12" value="<?php
if ($_GET['plid']) echo number_format($old->Amount,$_SESSION['currency_decimals']); ?>" /></label>
 / <select name="tpy" size="1">
<?php
if ($_GET['plid']) {
  $tpy = $old->TimesPerYear;
} elseif (isset($_SESSION['pledge-tpy'])) {
  $tpy = $_SESSION['pledge-tpy'];
} else {
  $tpy=12;
} ?>
<option value="12"<?php if ($tpy==12) echo " selected"; ?> ><?=_("month")?></option>
<option value="4"<?php if ($tpy==4) echo " selected"; ?> ><?=_("quarter")?></option>
<option value="1"<?php if ($tpy==1) echo " selected"; ?> ><?=_("year")?></option>
<option value="0"<?php if ($tpy==0) echo " selected"; ?> ><?=_("(one time)")?></option>
</select></span>
<div><input type="submit" value="<?=_("Save Changes")?>" name="edit" /></div>
</form>
<?php if ($_GET['plid']) { //delete form only applies if edit rather than new
?>
<form name="delform" enctype="multipart/form-data" method="post" action="<?=$_SERVER["PHP_SELF"]?>"
onsubmit="return del_validate();">
<input type="hidden" name="plid" value="<?=$_GET['plid']?>">
<input type="hidden" name="pid" value="<?=$old->PersonID?>">
<span class="comment"><?=_("To finish the pledge, just add an end date. But if the whole pledge is a mistake...")?></span>
<input type="submit" value="<?=_("Delete This Pledge")?>" name="del" />
</form>
<?php
} // endif plid
footer();
?>
