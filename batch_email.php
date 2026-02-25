<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Prepare Email"));
  header2(0);
}

if ($submit) {
  $sql = "SELECT FullName,Furigana,Email FROM person WHERE PersonID IN (".$pid_list.") ORDER BY Furigana";
  $result = sqlquery_checked($sql);
  $num_selected = mysqli_num_rows($result);

  while ($row = mysqli_fetch_object($result)) {
    if (($row->Email) && preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i",$row->Email)) {
      if (preg_match("/^".$row->Email.";/i", $addr_list) || strpos(";".$row->Email.";", $addr_list)) {
        $dup_list .= "<br>&nbsp;&nbsp;&nbsp; ".$row->Email;
        $num_dup++;
      } else {
        if ($num_used == 0) {
          if ($field == "to") {
            $url = $row->Email;
          } else {
            $url = "myself@mydomain.org&".$field."=".$row->Email;
          }
        } else {
          $url .= ",".$row->Email;
        }
        $num_used++;
      }
    }
  }
  if ($num_dup) {
    echo "<b>Out of the $num_selected people you asked for, there were $num_used unique email addresses and $num_dup additional duplicates. The duplicate email addresses were:</b>".$dup_list;
  } else {
    echo "<b>Out of the $num_selected people you asked for, there were $num_used email addresses.</b>";
  }
  if (!empty($url)) {
    echo "<br><a href=\"mailto:".htmlspecialchars($url)."\">Click here to open email window</a>";
  }
  if (!$ajax) footer();
  exit;
}

?>

    <center><h3><font color="#8b4513">Select where you want the email addresses and click the button...</font></h3>
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="optionsform">
      <input type="hidden" name="pid_list" value="<?=$pid_list?>">
      <table border="0" cellspacing="0" cellpadding="10">
        <tr>
          <td><input type="radio" name="field" value="to" checked tabindex="1">TO<br>
            <input type="radio" name="field" value="cc">CC<br>
            <input type="radio" name="field" value="bcc">BCC</td>
            <td>(For large lists, it is courteous to use BCC and<br>
               put your own address in TO. But I can't get this<br>
               code to talk correctly with some email software,<br>
               so if you have trouble, select TO here and then<br>
               change to BCC in the email window.)
          </td>
          <td><input type="submit" name="submit" value="Prepare Email Window"></td>
        </tr>
      </table>
    </form></center>
<?php if (!$ajax) footer(); ?>
