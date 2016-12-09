<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#E8FFE0",0);

if ($save_cat) {
  if ($cat_id == "new") {  //need to insert the new category record first
    $sql = "INSERT INTO category (Category) VALUES ('$category')";
    $result = sqlquery_checked($sql);
    if (mysqli_affected_rows($db) > 0) {
      $cat_id = mysqli_insert_id($db);
      echo "<h3><font color=\"#449933\">New category successfully added.</font></h3>";
    } else {
      echo "No category record was inserted for some reason.<br>";
      exit;
    }
  }
  $pid_array = explode(",",$pid_list);
  $num_pids = count($pid_array);
  $num_previous = 0;
  for ($i=0; $i<$num_pids; $i++) {
    $sql = "SELECT * FROM percat WHERE PersonID=".$pid_array[$i]." AND CategoryID=$cat_id";
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result) == 1) {
      $num_previous++;
    } else {
      $sql = "INSERT INTO percat (PersonID,CategoryID) VALUES (".
           $pid_array[$i].",$cat_id)";
      $result = sqlquery_checked($sql);
    }
  }
  echo "<h3><font color=\"#449933\">".($num_pids - $num_previous)." new records successfully added.";
  if ($num_previous > 0) {
    echo "<br>&nbsp; ($num_previous people in this list were already in this category.)";
  }
  echo "</font></h3>";
  exit;
}

?>

<SCRIPT language=Javascript>
function validate() {
//If new category, make sure name is not blank
  if (document.catform.cat_id.value == "new" && document.catform.category.value == "") {
    alert("You need to specify a name for the new category.");
    document.catform.category.focus();
    return false;
  } else {
    return true;
  }
}
</SCRIPT>

  <div align="center">
    <font color="#449933" size=4><b>Choose existing category,
        or choose New and fill in new category name:</b></font>
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="catform" target="_self" onsubmit="return validate();">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>" border="0">
      <table border="1" cellspacing="0" cellpadding="4">
        <tr>
          <td nowrap>Category:             <select name="cat_id" size="1">
              <option value="" selected>Select a category...</option>
              <?php /*<option value="new">New Category (input name)</option>*/ ?>
<?php
$sql = "SELECT * FROM category ORDER BY Category";
$result = sqlquery_checked($sql);
while ($row = mysqli_fetch_object($result)) {
  echo "              <option value=\"".$row->CategoryID."\">$row->Category</option>\n";
}
?>
            </select><br>&nbsp;<br>
            New Category Name: <input type="text" name="category" size="45"
            maxlength="50" border="0">
          </td>
          <td align="center" valign="middle" nowrap>
          <input type="submit" name="save_cat" value="Save To This Category" border="0"></td>
        </tr>
      </table>
    </form>
  </div>
<?php print_footer();
?>
