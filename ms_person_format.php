<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#FFF0E0",0);

$sql = "SELECT DISTINCT SetName FROM outputset WHERE ForHousehold=0 ORDER BY SetName";
$result = sqlquery_checked($sql);
?>
    <h3><font color="#8b4513">Select preset format from list and click the button...</font></h3>
    <form action="person_format.php" method="post" name="optionsform" target="_blank">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td><label>Layout:<select name="outputset_name" size="1">
          <option value="">Select Format...</option>
          <?php while ($row = mysqli_fetch_object($result)) {
  echo  "                <option value=\"".$row->SetName."\">".$row->SetName."</option>\n";
}
?>
              </select></label>
              <label>Sort by:<select name="orderby" size="1">
          <option value="Furigana">Name (by Furigana/Romaji)</option>
          <option value="substring(Birthdate,6)">Birthday</option>
          <option value="household.PostalCode">Postal Code</option>
          </select></label></td>
          <td><input type="submit" name="submit" value="Make Page to Copy or Print"></td>
        </tr>
      </table>
    </form>
<?php print_footer();
?>