<?php
include("functions.php");
include("accesscontrol.php");
print_header("Multiple Selection","#FFFFE0",0);

$sql = "SELECT PhotoPrintName FROM photoprint ORDER BY PhotoPrintName";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
?>
    <h3><font color="#8b4513">Select options for photo printing and click the button...</font></h3>
    <form action="print_photos.php" method="post" name="optionsform" target="_blank">
      <input type="hidden" name="pid_list" value="<? echo $pid_list; ?>" border="0">
      <table width="639" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td nowrap><input type="radio" name="data_type" value="person" checked tabindex="1" border="0">Use Individual Photos &amp; Names<br><input type="radio" name="data_type" value="household" border="0">Use Household Photos &amp; Captions</td>
          <td>Layout:<select name="photo_print_name" size="1">
          <? while ($row = mysql_fetch_object($result)) {
  echo  "                <option value=\"".$row->PhotoPrintName."\">".$row->PhotoPrintName."</option>\n";
}
?>
        </select><br>
        <input type="checkbox" name="show_blanks">Show name even if no photo</td>
          <td><input type="submit" name="submit" value="Make Printable Label Page" border="0"></td>
        </tr>
      </table>
    </form>
  <? print_footer();
?>

