<?php
include("functions.php");
include("accesscontrol.php");
header1("");
?>
<link rel="stylesheet" href="style.php?jquery=1" type="text/css" />
<?php
header2(0);

if ($save_contact) {
  $pid_array = explode(",",$pid_list);
  $num_pids = count($pid_array);
  $prev_num = 0;
  $prev_pidlist = "";
  $prev_info = "";
  for ($i=0; $i<$num_pids; $i++) {
    $has_prev = 0;
    if (substr($save_contact,0,3) != "Yes") {   //skip check if after confirming similar entries
      $sql = "SELECT contact.*,FullName FROM contact LEFT JOIN person on contact.PersonID=".
      "person.PersonID WHERE contact.PersonID=".$pid_array[$i].
      " AND contact.ContactTypeID=$ctid AND ContactDate='$cdate'";
      $result = sqlquery_checked($sql);
      if (mysqli_num_rows($result) > 0) {
        $has_prev = 1;
        $prev_num++;
        $prev_pidlist .= ",".$pid_array[$i];
        while ($row = mysqli_fetch_object($result)) {
          $prev_info .= "<tr><td><a href=\"individual.php?pid=$pid_array[$i]\" ".
          "target=\"_blank\">$row->FullName</a></td><td>".
          ((strlen($row->Description) > 100)?(substr($row->Description,0,97)."..."):($row->Description)).
          "</td></tr>\n";
        }
      }
    }
    if (!$has_prev) {
      $sql = "INSERT INTO contact (PersonID,ContactTypeID,ContactDate,Description) VALUES (".
           $pid_array[$i].",$ctid,'$cdate','$desc')";
      $result = sqlquery_checked($sql);
    }
  }
  echo "<h3>".sprintf(_("%s new records successfully added."),$num_pids-$prev_num)."</h3>\n";
  if ($prev_num > 0) {
    $prev_pidlist = substr($prev_pidlist,1);  //remove the leading comma
    $sql = "SELECT ContactType FROM contacttype WHERE ContactTypeID=$ctid";
    $tempresult = sqlquery_checked($sql);
    $temprow = mysqli_fetch_object($tempresult);
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="confirmform" target="_self">
<input type="hidden" name="pid_list" value="$prev_pidlist">
<input type="hidden" name="ctid" value="$ctid">
<input type="hidden" name="cdate" value="$cdate">
<input type="hidden" name="desc" value="$desc">
<?php
    echo sprintf(_("However, the following %s people already had a contact of type \"%s\" on %s."),
    $prev_num,$temprow->ContactType,$cdate)."<br />\n";
    echo _("Do you still want the additional records added?");
    echo "<input type=\"submit\" name=\"save_contact\" value=\""._("Yes, add them anyway!")."></form>\n";
    echo _("(You can click on a name to view their individual info - it will open in a new window/tab.)");
    echo "<table><tr><th>"._("Name")."</th><th>"._("Description")."</th></tr>\n".$prev_info."</table>\n";
  }
  exit;
}
?>
  <h3><?=_("Choose contact type and date, and fill in a description if desired:")?></h3>
  <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="contactform" target="_self" onsubmit="return validate();">
    <input type="hidden" name="pid_list" value="<?=$pid_list?>">
    <label class="label-n-input"><?=_("Contact Type")?>: <select id="ctid" name="ctid" size="1">
      <option value="" selected>Please select...</option>
<?php
$result = sqlquery_checked("SELECT * FROM contacttype ORDER BY ContactType");
while ($row = mysqli_fetch_object($result)) {
  echo "      <option value=\"".$row->ContactTypeID."\">".$row->ContactType."</option>\n";
}
?>
    </select></label>
    <label class="label-n-input"><?=_("Date")?>: <input type="text" id="cdate" name="cdate" value="" style="width:6em"></label>
    <label class="label-n-input"><?=_("Description")?>: <textarea id="desc" name="desc" style="height:4em;width:30em"></textarea></label>
    <input type="submit" name="save_contact" value="<?=_("Save Contact Info")?>">
  </form>

<script type="text/JavaScript" src="js_procedural/jquery.js"></script>
<script type="text/JavaScript" src="js_procedural/jquery-ui.js"></script>
<script type="text/JavaScript">
$(document).ready(function(){
  $(document).ajaxError(function(e, xhr, settings, exception) {
    alert('Error calling ' + settings.url + ': ' + exception);
  }); 
<?php
if($_SESSION['lang']=="ja_JP") echo "  $.datepicker.setDefaults( $.datepicker.regional[\"ja\"] );\n";
?>
  $("#cdate").datepicker({ dateFormat: 'yy-mm-dd' });
  if ($("#cdate").val()=="") $("#cdate").datepicker('setDate', new Date());

  $("#ctid").change(function(){  //insert template text in Contact description when applicable ContactType is selected
    if (!$.trim($("#desc").val())) {
      $("#desc").load("ajax_request.php",{'req':'ContactTemplate','ctid':$("#ctid").val()}, function() {
        $(this).change();
      });
    }
  });

});

function validate() {
//Make sure a contact type is selected
  if (document.contactform.ctid.value == "") {
    alert("<?=_("Please select a Contact Type.")?>");
    return false;
  } else {
    return true;
  }
}
</script>
<?php footer();
?>
