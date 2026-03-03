<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Overview Pages"));
  header2(0);
}
?>
<h3><?=_("Besides basic info, include:")?></h3>
<form action="overview.php" method="post" name="overviewform" target="_blank">
  <input type="hidden" name="pid_list" value="<?=$pid_list?>">
  <div>
    <label class="label-n-input"><input type="checkbox" name="categories" checked><?=_("Categories")?></label><br>
    <label class="label-n-input"><input type="checkbox" name="household" checked><?=_("Household member table")?></label><br>
    <label class="label-n-input"><input type="checkbox" name="actions" checked><?=_("Actions:")?></label>
    &nbsp;<label class="label-n-input"><input type="radio" name="action_types" value="key" checked><?=_("only first, last, &amp; key (colored) ones")?></label>,
    <?=_("or")?> <label class="label-n-input"><input type="radio" name="action_types" value="all"><?=_("all actions")?></label><br>
    <label class="label-n-input"><input type="checkbox" name="attendance" checked><?=_("Event attendance")?></label><br>
<?php if ($_SESSION['donations'] == "yes"): ?>
    <label class="label-n-input"><input type="checkbox" name="donations" checked><?=_("Donations &amp; Pledges")?></label><br>
<?php endif; ?>
    <?=_("Between each person:")?> <label class="label-n-input"><input type="radio" name="break" value="page" checked><?=_("page break")?></label>
    <label class="label-n-input"><input type="radio" name="break" value="line"><?=_("just a line")?></label><br>
    <br>
    <input type="submit" name="submit" value="<?=_("Make Overview Pages")?>">
  </div>
</form>
<?php if (!$ajax) load_scripts(['jquery', 'jqueryui']); ?>
<script>
$(function(){ $("input[type=submit]").button(); });
</script>
<?php if (!$ajax) footer(); ?>