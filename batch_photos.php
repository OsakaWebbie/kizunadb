<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Print Photos"));
  header2(0);
}

$sql = "SELECT PhotoPrintName FROM photoprint ORDER BY PhotoPrintName";
$result = sqlquery_checked($sql);
?>
<h3><?=_("Select options for photo printing and click the button...")?></h3>
<form action="print_photos.php" method="get" name="optionsform" target="_blank">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>">
  <div style="display:flex; flex-wrap:wrap; gap:1em; align-items:flex-start;">
    <div>
      <label class="label-n-input"><input type="radio" name="data_type" value="person" checked><?=_("Use Individual Photos &amp; Names")?></label><br>
      <label class="label-n-input"><input type="radio" name="data_type" value="household"><?=_("Use Household Photos &amp; Captions")?></label>
    </div>
    <div>
      <label class="label-n-input"><?=_("Layout")?>: <select name="photo_print_name" size="1">
<?php while ($row = mysqli_fetch_object($result)) {
  echo "        <option value=\"".$row->PhotoPrintName."\">".$row->PhotoPrintName."</option>\n";
} ?>
      </select></label><br>
      <label class="label-n-input"><input type="checkbox" name="show_blanks"><?=_("Show name even if no photo")?></label>
    </div>
    <input type="submit" name="submit" value="<?=_("Make Printable Page")?>">
  </div>
</form>
<?php if (!$ajax) load_scripts(['jquery', 'jqueryui']); ?>
<script>
$(function(){ $("input[type=submit]").button(); });
</script>
<?php if (!$ajax) footer(); ?>