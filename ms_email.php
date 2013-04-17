<?php
include("functions.php");
include("accesscontrol.php");
print_header("Multiple Selection","#FFFFE0",0);

if ($submit) {
  $sql = "SELECT FullName,Furigana,Email FROM person WHERE PersonID IN (".$pid_list.") ORDER BY Furigana";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
    exit;
  }
  $num_selected = mysql_numrows($result);
  
  while ($row = mysql_fetch_object($result)) {
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
    echo "<b>Out of the $num_selected people you asked for, there were $num_used unique email addresses and $num_dup additional duplicates (which are not repeated in the message window that has probably popped up by now).  The duplicate email addresses were:</b>".$dup_list;
  } else {
    echo "<b>Out of the $num_selected people you asked for, there were $num_used email addresses to include in the message window that has probably popped up by now.</b>";
  }
  /* ACTUAL EMAIL WINDOW COMMAND GOES HERE */
  echo "<script language=Javascript>\n";
  echo "window.onload=function(){ location.href='mailto:'+escape('".$url."') }\n</script>\n";
  /*switch ($field) {
    case "to":
      echo $addr_list."' }\n</script>\n";
      break;
    case "cc":
      echo "karen@proverbs2525.org&CC=".$addr_list."' }\n</script>\n";
      break;
    case "bcc":
      echo "karen@proverbs2525.org&BCC=".$addr_list."' }\n</script>\n";
  }*/
  
  exit;
}


?>

    <center><h3><font color="#8b4513">Select where you want the email addresses and click the button...</font></h3>
    <form action="ms_email.php" method="post" name="optionsform" target="ActionFrame">
      <input type="hidden" name="pid_list" value="<? echo $pid_list; ?>" border="0">
      <table border="0" cellspacing="0" cellpadding="10">
        <tr>
          <td><input type="radio" name="field" value="to" checked tabindex="1" border="0">TO<br> 
            <input type="radio" name="field" value="cc" border="0">CC<br>
            <input type="radio" name="field" value="bcc" border="0">BCC</td>
            <td>(For large lists, it is courteous to use BCC and<br>
               put your own address in TO. But I can't get this<br>
               code to talk correctly with some email software,<br>
               so if you have trouble, select TO here and then<br>
               change to BCC in the email window.)
          </td>
          <td><input type="submit" name="submit" value="Prepare Email Window" border="0"></td>
        </tr>
      </table>
    </form></center>
  <? print_footer();
?>

