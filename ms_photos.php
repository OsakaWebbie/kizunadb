<?php
include("functions.php");
include("accesscontrol.php");
print_header("Multiple Selection","#FFFFE0",0);

$sql = "SELECT PhotoPrintName FROM photoprint ORDER BY PhotoPrintName";
$result = sqlquery_checked($sql);
?>
    <h3>Select options for photo printing and click the button...</h3>
    <form action="print_photos.php" method="get" name="optionsform" target="_blank">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>" border="0">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td nowrap><input type="radio" name="data_type" value="person" checked tabindex="1" border="0">Use Individual Photos &amp; Names<br>
            <input type="radio" name="data_type" value="household" border="0">Use Household Photos &amp; Captions</td>
          <td>Layout:<select name="photo_print_name" size="1">
          <?php while ($row = mysqli_fetch_object($result)) {
  echo  "                <option value=\"".$row->PhotoPrintName."\">".$row->PhotoPrintName."</option>\n";
}
?>
        </select><br>
        <input type="checkbox" name="show_blanks">Show name even if no photo</td>
          <td><input type="submit" name="submit" value="Make Printable Label Page" border="0"></td>
        </tr>
      </table>
    </form>
  <?php print_footer();
?>

