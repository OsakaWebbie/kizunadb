<?php
include("functions.php");
include("accesscontrol.php");
print_header("Multiple Selection","#FFE0E0",0);

?>
    <h3><font color="#8b4513">Select options for address labels and click the button...</font></h3>
    <form action="print_label.php" method="post" name="optionsform" target="_blank">
      <input type="hidden" name="pid_list" value="<? echo $_POST['pid_list']; ?>">
      <table width="642" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td width="249"><input type="radio" name="name_type" value="ind" tabindex="1" border="0">Use Individual Names 
            <br><input type="radio" name="name_type" value="label" border="0" checked>Use Household Label Names
          </td>
          <td width="184">
            <h4>Label Type:<select name="label_type" size="1">
<?
echo  "                <option value=\"Askul MA-506TW\">Askul MA-506T (24) 郵便番号折</option>\n";
echo  "                <option value=\"Askul MA-506T\">Askul MA-506T (24) 郵便番号無折</option>\n";
echo  "                <option value=\"Kokuyo F7159\">Kokuyo F7159 (24)</option>\n";
echo  "                <option value=\"A-One 75312\">A-One 75312/Askul MA-513T (12)</option>\n";
foreach ($PDF_Label->_Avery_Labels as $key => $value) {
  echo  "                <option value=\"".$key."\">".$key."</option>\n";
}
?>
              </select></h4>
          </td>
          <td><input type="submit" name="submit" value="Make PDF of Labels" border="0"></td>
        </tr>
      </table>
    </form>
  <? print_footer();
?>
