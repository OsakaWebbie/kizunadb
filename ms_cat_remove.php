<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#E8FFE0",0);

if (!empty($remove_cat)) {
  
  $sql = "DELETE FROM percat WHERE CategoryID=".$_POST['cat_id']." AND PersonID IN ($pid_list)";
  sqlquery_checked($sql);
  $num_affected = mysqli_affected_rows($db);
  $num_unaffected = substr_count($pid_list,",") + 1 - $num_affected;
  echo "<h3><font color=\"#993333\">".($num_affected)." records removed from category '".$_POST['category']."'.</font></h3>";
  if ($num_unaffected > 0) {
    echo "<h4>(".$num_unaffected." in this list were not in the category to start with.)</h4>";
  }
  exit;
}

?>

<SCRIPT language=Javascript>
function validate() {
//make sure they select a category
  if (document.catform.cat_id.value == "") {
    alert("You must select a category.");
    return false;
  }
  document.catform.category.value = document.catform.cat_id.options[document.catform.cat_id.selectedIndex].text;
  if (!confirm("Are you sure you want to remove all these records from Category '"+document.catform.category.value+"'?")) {
    return false;
  }
  return true;
}
</SCRIPT>

  <div align="center">
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="catform" target="_self" onsubmit="return validate();">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>">
      <input type="hidden" name="category" value="">
      <p>Category: <select name="cat_id" size="1">
        <option value="" selected>Select category...</option>
<?php
$result = sqlquery_checked("SELECT * FROM category ORDER BY Category");
while ($row = mysqli_fetch_object($result)) {
  echo "        <option value=\"".$row->CategoryID."\">$row->Category</option>\n";
}
?>
      </select></p>
      <p><input type="submit" name="remove_cat" value="Remove From This Category"></p>
    </form>
  </div>
<?php print_footer();
?>
