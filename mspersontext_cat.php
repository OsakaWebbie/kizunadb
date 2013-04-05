<?php
include("functions.php");
include("accesscontrol.php");
print_header("Category Indicators","#FFF0E0",0);
?>
<script type="text/javascript">
function fill_parent(pc,pref,shi,rom) {
  thisform = document.forms['catform'];
  parentform = window.opener.document.forms['optionsform'];
  selected = 0;
  for (i=1; i<7; i++) {
    if (thisform.elements["select"+i+"A"]) {
      selected = 1;
      val = (thisform.elements["choice"+i+"A"][0].checked) ? thisform.elements["choice"+i+"A"][0].value : thisform.elements["choice"+i+"A"][1].value;
      val += "=" + thisform.elements["select"+i+"A"].value;
      if (thisform.elements["select"+i+"B"].value) {
        val += (thisform.elements["logical"+i][0].checked) ? thisform.elements["logical"+i][0].value : thisform.elements["logical"+i][1].value;
        val += (thisform.elements["choice"+i+"B"][0].checked) ? thisform.elements["choice"+i+"B"][0].value : thisform.elements["choice"+i+"B"][1].value;
        val += "=" + thisform.elements["select"+i+"B"].value;
      }
      parentform.elements["cat"+i].value = val;
      parentform.elements["mark"+i].value = thisform.elements["mark"+i].value;
      parentform.elements["tag"+i].value = thisform.elements["tag"+i].value;
    }
  }
  if (selected == 0) {
    alert("You must at least choose one category. If you want to remove 'Category' from your field selections, press Cancel.");
  } else {
alert("For now, you'll need to hit the submit button again in the other window after this window closes.");
    //parentform.submit();
    window.close();
  }
}
</script>
<h3>Please specify preferences for category fields:</h3>
<form name="catform" onsubmit="return false;">

<?
$sql = "SELECT * from category ORDER BY Category";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error: ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
while ($row = mysql_fetch_object($result)) {
  $option_text .= "<option value=\"".$row->CategoryID."\">".$row->Category."</option>\n";
}

for ($str_index=0; $str_index<strlen($fields); $str_index++) {
  $i = substr($fields,$str_index,1);
  echo "  <p>Put <input type=text name=\"mark{$i}\" value=\"*\" size=5>";
  echo " (XML tag <input type=text name=\"tag{$i}\" value=\"cat{$i}\" size=5>) in Field $i if:<br>\n";
  echo "  &nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"choice{$i}A\" value=\"in\" checked>In\n";
  echo "  <input type=\"radio\" name=\"choice{$i}A\" value=\"out\">Not In:&nbsp;&nbsp;\n";
  echo "  <select name=\"select{$i}A\" size=\"1\">\n";
  echo "    <option value=\"\">Select a Category...</option>\n";
  echo $option_text;
  echo "  </select><br>&nbsp;&nbsp;&nbsp;";
  echo "  (option) <input type=\"radio\" name=\"logical{$i}\" value=\" OR \" checked>Or\n";
  echo "  <input type=\"radio\" name=\"logical{$i}\" value=\" AND \">And:<br>\n";
  echo "  &nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"choice{$i}B\" value=\"in\" checked>In\n";
  echo "  <input type=\"radio\" name=\"choice{$i}B\" value=\"out\">Not In:&nbsp;&nbsp;\n";
  echo "  <select name=\"select{$i}B\" size=\"1\">\n";
  echo "    <option value=\"\"> </option>\n";
  echo $option_text;
  echo "  </select><br>&nbsp;&nbsp;&nbsp;";
}
?>
  <p>&nbsp;&nbsp;<button name="go" type="button" onclick="fill_parent();">Submit These Choices</button>
  &nbsp;&nbsp;&nbsp;<button name="cancel" type="button" onclick="window.close();">Cancel</button></p>
</form>
<? print_footer(); ?>
