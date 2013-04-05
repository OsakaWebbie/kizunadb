<?php
include("functions.php");
include("accesscontrol.php");
print_header("","#F0E0FF",0);
showfile("dates.js");

//get data from event table and build master array

$sql = "SELECT * FROM event ORDER BY IF(EventEndDate>NOW(),0,1), Event";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
echo "var ar = new Array();\n";
$ar_index = 0;
while ($row = mysql_fetch_object($result)) {
  echo "ar[$ar_index] = new Array();\n";
  echo "ar[$ar_index][eid] = \"$row->EventID\";\n";
  echo "ar[$ar_index][event] = \"".escape_quotes($row->Event)."\";\n";
  echo "ar[$ar_index][estartdate] = \"$row->EventStartDate\";\n";
  echo "ar[$ar_index][eenddate] = \"$row->EventEndDate\";\n";
  echo "ar[$ar_index][active] = \"$row->Active\";\n";
  echo "ar[$ar_index][rem] = \"".escape_quotes($row->Remarks)."\";\n";
  $ar_index++;
}
?>

function window.onload() {
  list_active();
  document.attendform.event_date.value = Today();
  document.attendform.attend_date.value = Today();
//  document.attendform.eventcal.visible = false;
}

function list_active() {
  disable_new();
//NOTE: the first option in the list ("Select an event") should remain
  for (var list_index = document.attendform.event_list.length-1; list_index > 0; list_index--) {
    document.attendform.event_list.options[list_index] = null;
  }
  list_index = 1;
  for (var array_index = 0; array_index < ar.length; array_index++) {
    if (ar[array_index][active] == "0") break;  //all the active events are first in the list, so get out now
    document.attendform.event_list.options[list_index] = new Option(ar[array_index][event], array_index);
    list_index++;
  }
}

function list_inactive() {
  disable_new();
//NOTE: the first option in the list ("Select an event") should remain
  for (var list_index = document.attendform.event_list.length-1; list_index > 0; list_index--) {
    document.attendform.event_list.options[list_index] = null;
  }
  list_index = 1;
  for (var array_index = 0; array_index < ar.length; array_index++) {
    if (ar[array_index][active] == "1") continue;  //skip to inactive events
    document.attendform.event_list.options[list_index] = new Option(ar[array_index][event], array_index);
    list_index++;
  }
}

function fill_fields() {
  var el = document.attendform.event_list;
  document.attendform.event_id.value = ar[el.options[el.selectedIndex].value][eid];

  document.attendform.event.value = ar[el.options[el.selectedIndex].value][event];
  document.attendform.eventstartdate.value = ar[el.options[el.selectedIndex].value][estartdate];
  document.attendform.eventenddate.value = ar[el.options[el.selectedIndex].value][eenddate];
  if (ar[el.options[el.selectedIndex].value][active] == "1") {
    document.attendform.active.checked = true;
  } else {
    document.attendform.active.checked = false;
  }
  document.attendform.remarks.value = ar[el.options[el.selectedIndex].value][rem];
}

function new_event() {
  document.attendform.event_list.disabled = true;
  document.attendform.event_id.value = "";
  document.attendform.event.value = "";
  document.attendform.eventstartdate.value = Today();
  document.attendform.eventenddate.value = Today();
  document.attendform.active.checked = false;
  document.attendform.remarks.value = "";
  document.attendform.event.disabled = false;
  document.attendform.eventstartdate.disabled = false;
  document.attendform.eventenddate.disabled = false;
  document.attendform.active.disabled = false;
  document.attendform.remarks.disabled = false;
  document.attendform.event.focus();
}

function disable_new() {
  document.attendform.event.disabled = true;
  document.attendform.eventstartdate.disabled = true;
  document.attendform.eventenddate.disabled = true;
  document.attendform.active.disabled = true;
  document.attendform.remarks.disabled = true;
  document.attendform.event_id.value = "";
  document.attendform.event.value = "";
  document.attendform.eventstartdate.value = "";
  document.attendform.eventenddate.value = "";
  document.attendform.active.checked = false;
  document.attendform.remarks.value = "";
  document.attendform.event_list.disabled = false;
}

function validate() {
//If new event, check for invalid entries
  if (document.attendform.event_select.value == "new") {
    alert("New event...");
    if (document.attendform.event.value == "") {
      alert("You need to specify a name for the new event.");
      document.attendform.event.focus();
      return false;
    }
  }
  if (isDate(document.attendform.attend_date.value,"past")==false){
    document.attendform.attend_date.focus();
    return false;
  }
  return true;
}
</SCRIPT>

  <div align="center">
    <font color="#663399" size=4><b>Choose existing event and enter date,
        or fill in information for a new event:</b></font>
    <form action="<? echo $PHP_SELF; ?>" method="post" name="attendform" target="_self">
      <input type="hidden" name="pid_list" value="<? echo $pid_list; ?>" border="0">
      <input type="hidden" name="event_id" value="" border="0">
      <table border="1" cellspacing="0" cellpadding="4">
        <tr>
          <td nowrap>Event: <select name="event_list" size="1" onchange="fill_fields();">
              <option value="" selected>Select an event...</option>
            </select><br>
            <input type="radio" name="event_select" value="active" checked tabindex="1" border="0"
            onclick="list_active();">Choose from current ongoing events<br>
            <input type="radio" name="event_select" value="inactive" tabindex="2" border="0"
            onclick="list_inactive();">Choose from one-time or old events<br>
            <input type="radio" name="event_select" value="new" tabindex="3" border="0"
            onclick="new_event();">Record a new event (fill in --&gt;)</td>
          <td nowrap>
            <p>Event: <input type="text" name="event" disabled size="45" maxlength="50" border="0"><br>
              Date started: <input type="text" name="eventstartdate" disabled value=""
              size="12" maxlength="10" border="0">&nbsp;&nbsp;
              Date started: <input type="text" name="eventenddate" disabled value=""
              size="12" maxlength="10" border="0"><br>
              <textarea name="remarks" disabled rows="3" cols="45"></textarea></p>
          </td>
          <td align="center" nowrap>Date Attended:<br><input type="text" name="attend_date" value="" size="12"
          maxlength="10" border="0"><br>&nbsp;<br>
          <input type="submit" name="save_attend" value="Save Data" border="0"></td>
        </tr>
      </table>
    </form>
  </div>
    <? print_footer();
?>
