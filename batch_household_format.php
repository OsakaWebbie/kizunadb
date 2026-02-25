<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Household Info (Formatted)"));
  header2(0);
}
?>
    <h3><font color="#8b4513">Select preset format from list and click the button...</font></h3>
    <form action="household_format.php" method="post" name="optionsform" target="_blank">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td>Household Data Layout:<select name="household_set" size="1">
          <option value="">Select Format...</option>
<?php
$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=1 ORDER BY SetName";
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
  echo  "                <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
          </select><br>&nbsp;<br>
          <input type=checkbox name=members>Include List of Members -
          Layout:<select name="member_set" size="1">
          <option value="">Select Format...</option>
<?php
$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=0 ORDER BY SetName";
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
  echo  "                <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
          </select>
          <p><input type=checkbox name="xml">Output as XML Data (formatting ignored)</p>
          </td>
          <td><input type="submit" name="submit" value="Make Page to Copy or Print"></td>
        </tr>
      </table>
    </form>
<?php if (!$ajax) footer(); ?>
