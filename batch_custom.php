<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Custom Report"));
  header2(0);
}
$result = sqlquery_checked("SELECT CustomName FROM custom ORDER BY CustomName");
?>
    <h3><?=_("Select desired report and click the button...")?></h3>
    <form action="custom.php" method="post" name="customform" target="_blank">
      <input type="hidden" name="pids" value="<?=$pid_list?>">
      <label class="label-n-input"><?=_("Layout")?>:<select name="customname" size="1">
        <option value=""><?=_("Select...")?></option>
<?php
while ($row = mysqli_fetch_object($result)) {
  echo  "        <option value=\"".$row->CustomName."\">".$row->CustomName."</option>\n";
}
?>
      </select></label>
      <input type="submit" name="submit" value="<?=_("Make Report")?>">
    </form>
<?php if (!$ajax) footer(); ?>
