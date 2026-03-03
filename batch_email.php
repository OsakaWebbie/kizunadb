<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Prepare Email"));
  header2(0);
}

if (!empty($submit)) {
  $sql = "SELECT FullName,Furigana,Email FROM person WHERE PersonID IN (".$pid_list.") ORDER BY Furigana";
  $result = sqlquery_checked($sql);
  $num_selected = mysqli_num_rows($result);
  $dup_list = "";
  $num_dup = 0;
  $num_used = 0;
  $url = "";
  while ($row = mysqli_fetch_object($result)) {
    if (($row->Email) && preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i",$row->Email)) {
      if (preg_match("/^".$row->Email.";/i", $addr_list) || strpos(";".$row->Email.";", $addr_list)) {
        $dup_list .= "<br>&nbsp;&nbsp;&nbsp; ".$row->Email;
        $num_dup++;
      } else {
        if ($num_used == 0) {
          if ($field == "to") {
            $url = $row->Email;
          } else {
            $url = "myself@mydomain.org&".$field."=".$row->Email;
          }
        } else {
          $url .= ",".$row->Email;
        }
        $num_used++;
      }
    }
  }
  if ($num_dup) {
    echo "<b>Out of the $num_selected people you asked for, there were $num_used unique email addresses and $num_dup additional duplicates. The duplicate email addresses were:</b>".$dup_list;
  } else {
    echo "<b>Out of the $num_selected people you asked for, there were $num_used email addresses.</b>";
  }
  if (!empty($url)) {
    echo "<br><a href=\"mailto:".htmlspecialchars($url)."\">Click here to open email window</a>";
  }
  if (!$ajax) footer();
  exit;
}
?>
<h3><?=_("Select where you want the email addresses and click the button...")?></h3>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="optionsform">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>">
  <div style="display:flex; flex-wrap:wrap; gap:1em; align-items:flex-start;">
    <div>
      <label class="label-n-input"><input type="radio" name="field" value="to" checked>TO</label><br>
      <label class="label-n-input"><input type="radio" name="field" value="cc">CC</label><br>
      <label class="label-n-input"><input type="radio" name="field" value="bcc">BCC</label>
    </div>
    <div class="comment"><?=_("For large lists, it is courteous to use BCC and put your own address in TO. However, some email software may not handle this correctly, so if you have trouble, select TO here and change to BCC in the email window.")?></div>
    <input type="submit" name="submit" value="<?=_("Prepare Email Window")?>">
  </div>
</form>
<?php if (!$ajax) load_scripts(['jquery', 'jqueryui']); ?>
<script>
$(function(){ $("input[type=submit]").button(); });
</script>
<?php if (!$ajax) footer(); ?>