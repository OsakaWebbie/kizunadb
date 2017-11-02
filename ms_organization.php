<?php
include("functions.php");
include("accesscontrol.php");
header1("");

// A REQUEST TO ADD A PERORG RECORD?
if ($_POST['newperorg']) {
  $result = sqlquery_checked("SELECT * FROM person WHERE PersonID=".$_POST['orgid']." AND Organization=1");
  if (mysqli_num_rows($result) == 0) die("This ID does not point to an organization record. Use Browse if you need help.");
  $pidarray = explode(",",$pid_list);
  $added = 0;
  foreach($pidarray as $eachpid) {
    sqlquery_checked("INSERT INTO perorg(PersonID, OrgID, Leader) ".
    "VALUES($eachpid,{$_POST['orgid']},0) ON DUPLICATE KEY UPDATE Leader=Leader");
    if (mysqli_affected_rows($db) == 1)  $added++;
  }
  header2(0);
  echo "<h3>".sprintf(_("%s organization association records added."),$added)."</h3>";
  exit;
}

?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<script src="https://code.jquery.com/jquery-2.2.4.min.js"
        integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44="
        crossorigin="anonymous"></script>
<script type="text/JavaScript" src="js/jquery-ui.js"></script>
<script type="text/javascript">
$(document).ready(function(){
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  }); 

    $("#orgid").keyup(function(){  //display Organization name when applicable ID is typed
    $("#orgname").load("ajax_request.php",{'req':'OrgName','orgid':$("#orgid").val()});
  });
});

</script>
<?php
header2(0);
echo "<h3>"._("Type the ID of an organization or search by name.")."</h3>\n";
echo "<p style=\"margin-bottom:10px\">"._("NOTE: A leader cannot be designated here - do that on the leader's detail page.")."</p>\n";
?>
<form name="orgform" id="orgform" method="POST" action="<?=${PHP_SELF}."?pid=$pid"?>" onSubmit="return ValidateOrg()">
<input type="hidden" name="pid_list" value="<?=$pid_list?>" />
<?=_("Organization ID")?>: <input type="text" name="orgid" id="orgid" style="width:5em;ime-mode:disabled" value="" />
<span id="orgname" style="color:darkred;font-weight:bold"></span><br />
(<label for="orgsearchtxt"><?=_("Search")?>: </label><input type="text" name="orgsearchtxt" id="orgsearchtxt" style="width:10em" value="">
<input type="button" value="<?=_("Search")."/"._("Browse")?>"
onclick="window.open('selectorg.php?txt='+encodeURIComponent(document.getElementById('orgsearchtxt').value),'selectorg','scrollbars=yes,width=800,height=600');">)
<br />
<input type="submit" value="<?=_("Save Organization Assignment")?>" name="newperorg">
</form>
<?php
footer();
?>

