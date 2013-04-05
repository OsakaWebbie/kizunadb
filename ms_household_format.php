<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#FFF0E0",0);
?>
    <h3><font color="#8b4513">Select preset format from list and click the button...</font></h3>
    <form action="household_format.php" method="post" name="optionsform" target="_blank">
      <input type="hidden" name="pid_list" value="<? echo $pid_list; ?>" border="0">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td>Household Data Layout:<select name="household_set" size="1">
          <option value="">Select Format...</option>
<?
$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=1 ORDER BY SetName";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
while ($row = mysql_fetch_object($result)) {
  echo  "                <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
          </select><br>&nbsp;<br>
          <input type=checkbox name=members>Include List of Members -
          Layout:<select name="member_set" size="1">
          <option value="">Select Format...</option>
<?
$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=0 ORDER BY SetName";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
while ($row = mysql_fetch_object($result)) {
  echo  "                <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
          </select>
          <p><input type=checkbox name="xml">Output as XML Data (formatting ignored)</p>
          </td>
          <td><input type="submit" name="submit" value="Make Page to Copy or Print" border="0"></td>
        </tr>
      </table>
    </form>
<? print_footer();
?>