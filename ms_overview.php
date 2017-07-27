<?php
include("functions.php");
include("accesscontrol.php");
print_header("Multiple Selection","#FFFFE0",0);

?>
    <h3><font color="#8b4513">Besides basic info, include:</font></h3>
    <form action="overview.php" method="post" name="overviewform" target="_blank">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>">
      <table width="642" border="0" cellspacing="0" cellpadding="5">
        <tr>
          <td>
            <input type="checkbox" name="categories" checked>Categories
            <br><input type="checkbox" name="household" checked>Household member table
            <br><input type="checkbox" name="actions" checked>Actions:
            &nbsp;<input type="radio" name="action_types" value="key" checked>only first, last, & key (colored) ones, or
            <input type="radio" name="action_types" value="all">all actions
            <br><input type="checkbox" name="attendance" checked>Event attendance
<?php if ($_SESSION['donations'] == "yes") echo "            <br><input type=\"checkbox\" name=\"donations\" checked>Donations & Pledges\n"; ?>
            <br>Between each person: <input type="radio" name="break" value="page" checked>page break
            <input type="radio" name="break" value="line">just a line
          </td>
          <td><input type="submit" name="submit" value="Make Overview Pages"></td>
        </tr>
      </table>
    </form>
  <?php print_footer();
?>

