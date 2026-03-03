<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Household Info (Formatted)"));
  header2(0);
}
?>
<h3><?=_("Select preset format from list and click the button...")?></h3>
<form action="household_format.php" method="post" name="optionsform" target="_blank">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>">
  <label class="label-n-input"><?=_("Household Data Layout")?>: <select name="household_set" size="1">
    <option value=""><?=_("Select Format...")?></option>
<?php
$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=1 ORDER BY SetName";
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
  echo "    <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
  </select></label>
  <br>
  <label class="label-n-input"><input type="checkbox" name="members"><?=_("Include List of Members")?></label>
  <label class="label-n-input"><?=_("Member Layout")?>: <select name="member_set" size="1">
    <option value=""><?=_("Select Format...")?></option>
<?php
$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=0 ORDER BY SetName";
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
  echo "    <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
  </select></label>
  <br>
  <label class="label-n-input"><input type="checkbox" name="xml"><?=_("Output as XML Data (formatting ignored)")?></label>
  <br>
  <input type="submit" name="submit" value="<?=_("Make Page to Copy or Print")?>">
</form>
<?php if (!$ajax) load_scripts(['jquery', 'jqueryui']); ?>
<script>
$(function(){ $("input[type=submit]").button(); });
</script>
<?php if (!$ajax) footer(); ?>