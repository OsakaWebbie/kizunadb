<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Add All to a Category"));
  header2(0);
}

if (!empty($_POST['save_cat'])) {
  $pid_array = explode(",",$pid_list);
  $num_pids = count($pid_array);
  $num_previous = 0;
  for ($i=0; $i<$num_pids; $i++) {
    $sql = "SELECT * FROM percat WHERE PersonID=".$pid_array[$i]." AND CategoryID={$_POST['cat_id']}";
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result) == 1) {
      $num_previous++;
    } else {
      $sql = "INSERT INTO percat (PersonID,CategoryID) VALUES (".$pid_array[$i].",{$_POST['cat_id']})";
      $result = sqlquery_checked($sql);
    }
  }
  echo '<h3>'.sprintf(_('%d new records successfully added.'),$num_pids - $num_previous).'</h3>';
  if ($num_previous > 0) {
    echo '<h4>'.sprintf(_('(%d people in this list were already in this category.)'), $num_previous).'</h4>';
  }
  if (!$ajax) footer();
  exit;
}

?>

<script>
function validate() {
  if (document.catform.cat_id.value == "") {
    alert("<?=_('Please select a category.')?>");
    return false;
  } else {
    return true;
  }
}
</script>

  <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="catform" onsubmit="return validate();">
    <input type="hidden" name="pid_list" value="<?=$_POST['pid_list']?>">
    <div>
      <label>Category: <select name="cat_id" size="1">
        <option value="" selected>Select a category...</option>
<?php
$result = sqlquery_checked("SELECT * FROM category ORDER BY Category");
while ($row = mysqli_fetch_object($result)) {
  echo '        <option value="'.$row->CategoryID.'">'.$row->Category.'</option>'."\n";
}
?>
      </select>
      </label>
    </div>
    <div>
      <input type="submit" name="save_cat" value="<?=_('Save To This Category')?>">
    </div>
  </form>
<?php if (!$ajax) footer(); ?>
