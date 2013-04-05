<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#FFF0E0",0);

$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=0 ORDER BY SetName";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
?>
    <h3><font color="#8b4513">Select preset format from list and click the button...</font></h3>
    <form action="person_format.php" method="post" name="optionsform" target="_blank">
      <input type="hidden" name="pid_list" value="<? echo $pid_list; ?>" border="0">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td>Layout:<select name="outputset_name" size="1">
          <option value="">Select Format...</option>
          <? while ($row = mysql_fetch_object($result)) {
  echo  "                <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
          </select><br>&nbsp;<br>
          Sort by:<select name="orderby" size="1">
          <option value="Furigana">Name (by Furigana/Romaji)</option>
          <option value="substring(Birthdate,6)">Birthday</option>
          <option value="household.PostalCode">Postal Code</option>
          </select></td>
          <td><input type="submit" name="submit" value="Make Page to Copy or Print" border="0"></td>
        </tr>
      </table>
    </form>
<? print_footer();
?>