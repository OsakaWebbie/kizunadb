<?php
include("functions.php");
include("accesscontrol.php");
if ($xml) {
  header('Content-Type: text/xml');
  header('Content-Disposition: attachment; filename="households.xml"');
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<householdlist>\n";
} else {
  echo "<html><head>";
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['charset']."\">\n";
  echo "<title>Formatted Household Data</title>\n";
  echo "<style>\n";
}

//$pid_array = explode(",",$pid_list);
//$num_pids = count($pid_array);

/*** GET LAYOUT INFO AND MAKE ARRAYS ***/

$sql = "SELECT outputset.*, output.OutputSQL, output.Header FROM outputset ".
 "LEFT JOIN output ON outputset.Class=output.Class ".
 "WHERE SetName='$household_set' AND ForHousehold=1 ORDER BY OrderNum";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
$num_hhclass = 0;
while ($row = mysql_fetch_object($result)) {
  if (!$xml) {
    echo ".".$row->Class." { ".$row->CSS." }\n";
  }
  if ($row->OrderNum == 0) {
    $title_class = "<p class=\"".$row->Class."\">";
  } else {
    $hhclass[$num_hhclass][0] = $row->Class;
    $hhclass[$num_hhclass][1] = $row->OutputSQL;
    $hhclass[$num_hhclass][2] = $row->Header;
    $num_hhclass++;
  }
}
if ($members) {
  $sql = "SELECT outputset.*, output.* FROM outputset LEFT JOIN output ON outputset.Class=output.Class ".
   "WHERE SetName='$member_set' AND ForHousehold=0 ORDER BY OrderNum";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  $num_memclass = 0;
  while ($row = mysql_fetch_object($result)) {
    $memclass[$num_memclass][0] = $row->Class;
    $memclass[$num_memclass][1] = $row->OutputSQL;
    $memclass[$num_memclass][2] = $row->Header;
    if (!$xml) {
      echo ".".$row->Class." { ".$row->CSS." }\n";
    }
    $num_memclass++;
  }
}
if (!$xml) {
?>
table { empty-cells: show }
.nobreak { page-break-inside: avoid; }
.break { page-break-before: always; }
@media screen { .break { border-top:2px green dotted; } }
textarea { font-size: 9pt; }
@media print { .noprint{ display: none; } }
</style>
<script type="text/javascript">
re = / break /;
function init() {
  document.getElementById('output').contentEditable="true";
}
//handle special keystrokes for editing
function keystroke(e) {
  var keynum;
  if(window.event) { // IE
    keynum = e.keyCode;
  } else if(e.which) { // Netscape/Firefox/Opera
    keynum = e.which;
  }

  if (keynum==113) {  //the F2 key - we will use it to toggle the presence of the break class
    var sel = window.getSelection();
    var node = sel.anchorNode.parentNode;
    var nodeclass = ' '+node.className+' ';
    if (re.test(nodeclass)) {
      nodeclass = nodeclass.replace(re,' ');
    } else {
      nodeclass += 'break';
    }
    nodeclass.replace(/\s*$/, '');
    nodeclass.replace(/^\s*/, '');
    node.className = nodeclass;
    return false;
  }
}
</script>
</head><body onload="init()">
<div class="noprint" style="width:100%;margin-bottom:3px;padding-bottom:6px;border-bottom:2px lightgray solid;">
  <div style="color:#C00000;font-style:italic;font-weight:bold;">You can edit the text below temporarily for printing.<br />
To add a page break (or remove one after you have put it in), click the line below and press F2.<br />
You can use Print Preview to check, and return here to edit more if necessary.<br />
To start over, just refresh the page.
  </div>
</div>
<div id="output" onkeydown="keystroke(event)">
<?
}

/*** QUERY FOR HOUSEHOLD RECORDS ***/

$sql = "SELECT DISTINCT person.HouseholdID, ";
for ($index = 0; $index < $num_hhclass; $index++) {
  $sql .= $hhclass[$index][1]." AS Item".$index;
  if ($index < $num_hhclass-1) $sql .= ",";
}
$sql .= " FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
"LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
"WHERE PersonID IN (".$pid_list.") AND person.HouseholdID IS NOT NULL AND person.HouseholdID>0 ".
"ORDER BY FIND_IN_SET(PersonID,'".$pid_list."')";
if (!$hhresult = mysql_query($sql)) {
  echo("SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
  exit;
}
while ($hh = mysql_fetch_array($hhresult)) {

  /*** PRINT HOUSEHOLD INFO ***/

  if ($xml) {
    echo "<household>\n";
  } else {
    echo "<div class=\"nobreak\">\n";
    if (stripos($household_set,"table")!==FALSE) {
      echo "<table border=1 cellspacing=0 cellpadding=2><tr>\n";
      for ($index = 0; $index < $num_hhclass; $index++) {
        echo $hhclass[$index][2]."\n";
      }
      echo "</td></tr>\n";
    }
  }
  if ($title_class) {
    $sql = "SELECT Furigana FROM person WHERE HouseholdID=".$hh[0];
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $main_mem = mysql_fetch_object($result);
    if ($xml) {
      echo "<hhtitle>" . substr($main_mem->Furigana, 0, strpos($main_mem->Furigana, ",")) . "</hhtitle>\n";
    } else {
      echo $title_class . substr($main_mem->Furigana, 0, strpos($main_mem->Furigana, ",")) . "</p>\n";
    }
  }
  for ($index = 1; $index <= $num_hhclass; $index++) {
    if ($xml) {
      echo "<".$hhclass[$index-1][0].">";
      echo preg_replace("/\n$/","",str_replace("&","&amp;",str_replace("&nbsp;"," ",preg_replace("/<[^<>]+>/","",$hh[$index]))));
      echo "</".$hhclass[$index-1][0].">\n";
    } else {
      echo $hh[$index]."\n";
    }
  }

  /*** PRINT MEMBER INFO IF REQUESTED ***/

  if ($members) {
    if (!$xml) {
      if (stripos($member_set,"comma")!==FALSE) {
        echo "<p><font class = \"".$memclass[0][0]."\"><b>Members: </b>";
      } else {
        echo "<p class = \"".$hhclass[0][0]."\">Members:</p>\n";
        if (stripos($member_set,"table")!==FALSE) {
          echo "<table border=1 cellspacing=0 cellpadding=2 style=\"page-break-inside:avoid\"><tr>\n";
          for ($index = 0; $index < $num_memclass; $index++) {
            echo $memclass[$index][2]."\n";
          }
          echo "</td></tr>\n";
        }
      }
    }
    $sql = "SELECT ";
    for ($index = 0; $index < $num_memclass; $index++) {
      $sql .= $memclass[$index][1]." AS Item".$index;
      if ($index < $num_memclass-1) $sql .= ",";
    }
    $sql .= " FROM person WHERE HouseholdID=".$hh[0].
    " ORDER BY FIELD(Relation,'Child','Spouse','Main') DESC, Birthdate";
    if (!$result = mysql_query($sql)) {
      echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
      exit;
    }
    $num_mem = mysql_numrows($result);
    $mem_count = 1;
    if ($xml) {
      echo "<memberlist>";
    }
    while ($mem = mysql_fetch_array($result)) {
      for ($index = 0; $index < $num_memclass; $index++) {
        if (stripos($member_set,"comma")!==FALSE && ($mem_count==$num_mem)) {
          if ($xml) {
            echo preg_replace("/, $/","",$mem[$index]);
          } else {
            echo preg_replace("/, $/","",$mem[$index])."</font></p>\n";
          }
        } else {
          if ($xml) {
            if (stripos($member_set,"comma")!==FALSE) {
              echo $mem[$index];
            } else {
              echo "<".$memclass[$index][0].">";
              echo preg_replace("/\n$/","",str_replace("&nbsp;"," ",preg_replace("/<[^<>]+>/","",$mem[$index])));
              echo "</".$memclass[$index][0].">\n";
            }
          } else {
            echo $mem[$index]."\n";
          }
        }
      }
      $mem_count++;
    }
    if ($xml) {
      echo "</memberlist>\n";
    } else {
      if (stripos($hmember_set,"table")!==FALSE) {
        echo "</table>\n";
      }
    }
  }
  if ($xml) {
    echo "</household>\n";
  } else {
    echo "</div>\n";
  }
}
if ($xml) {
  echo "</householdlist>\n";
} else {
  if (stripos($household_set,"table")!==FALSE && !$xml) {
    echo "</table>\n";
  }
  echo "</div></body></html>";
}
?>
