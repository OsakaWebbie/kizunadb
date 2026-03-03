<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Person Info (Formatted)"));
  header2(0);
}

$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=0 ORDER BY SetName";
$result = sqlquery_checked($sql);
?>
<h3><?=_("Select preset format from list and click the button...")?></h3>
<form action="person_format.php" method="post" name="optionsform" target="_blank">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>">
  <label class="label-n-input"><?=_("Layout")?>: <select name="outputset_name" size="1">
    <option value=""><?=_("Select Format...")?></option>
<?php while ($row = mysqli_fetch_object($result)) {
  echo "    <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
} ?>
  </select></label>
  <label class="label-n-input"><?=_("Sort by")?>: <select name="orderby" size="1">
    <option value="Furigana"><?=_("Name (by Furigana/Romaji)")?></option>
    <option value="substring(Birthdate,6)"><?=_("Birthday")?></option>
    <option value="household.PostalCode"><?=_("Postal Code")?></option>
  </select></label>
  <input type="submit" name="submit" value="<?=_("Make Page to Copy or Print")?>">
</form>
<?php if (!$ajax) load_scripts(['jquery', 'jqueryui']); ?>
<script>
$(function(){ $("input[type=submit]").button(); });
</script>
<?php if (!$ajax) footer(); ?>