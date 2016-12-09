<?php
include("functions.php");
include("accesscontrol.php");
if ($_GET['xml']) {
  header('Content-Type: text/xml');
  header('Content-Disposition: attachment; filename="households.xml"');
  echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<householdlist>\n";
} else {
?>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=<?=$_SESSION['charset']?>">
<title>Formatted Household Data</title>
<style>
<?php
} //end of if xml else

//$pid_array = explode(",",$pid_list);
//$num_pids = count($pid_array);

/*** GET LAYOUT INFO AND MAKE ARRAYS ***/

$sql = "SELECT outputset.*, output.OutputSQL, output.Header FROM outputset ".
 "LEFT JOIN output ON outputset.Class=output.Class ".
 "WHERE SetName='".$_GET['household_set']."' AND ForHousehold=1 ORDER BY OrderNum";
$result = sqlquery_checked($sql);
$num_hhclass = 0;
while ($row = mysqli_fetch_object($result)) {
  if (!$_GET['xml']) {
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
if ($_GET['members']) {
  $sql = "SELECT outputset.*, output.* FROM outputset LEFT JOIN output ON outputset.Class=output.Class ".
   "WHERE SetName='".$_GET['member_set']."' AND ForHousehold=0 ORDER BY OrderNum";
  $result = sqlquery_checked($sql);
  $num_memclass = 0;
  while ($row = mysqli_fetch_object($result)) {
    $memclass[$num_memclass][0] = $row->Class;
    $memclass[$num_memclass][1] = $row->OutputSQL;
    $memclass[$num_memclass][2] = $row->Header;
    if (!$_GET['xml']) {
      echo ".".$row->Class." { ".$row->CSS." }\n";
    }
    $num_memclass++;
  }
}
if (!$_GET['xml']) {
?>
table { empty-cells: show }
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
<?php
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
$hhresult = sqlquery_checked($sql);
while ($hh = mysqli_fetch_array($hhresult)) {

  /*** PRINT HOUSEHOLD INFO ***/

  if ($_GET['xml']) {
    echo "<household>\n";
  } else {
    echo "<div class=\"nobreak\">\n";
    if (stripos($_GET['household_set'],"table")!==FALSE) {
      echo "<table border=1 cellspacing=0 cellpadding=2><tr>\n";
      for ($index = 0; $index < $num_hhclass; $index++) {
        echo $hhclass[$index][2]."\n";
      }
      echo "</td></tr>\n";
    }
  }
  if ($title_class) {
    $sql = "SELECT Furigana FROM person WHERE HouseholdID=".$hh[0];
    $result = sqlquery_checked($sql);
    $main_mem = mysqli_fetch_object($result);
    if ($_GET['xml']) {
      echo "<hhtitle>" . substr($main_mem->Furigana, 0, strpos($main_mem->Furigana, ",")) . "</hhtitle>\n";
    } else {
      echo $title_class . substr($main_mem->Furigana, 0, strpos($main_mem->Furigana, ",")) . "</p>\n";
    }
  }
  for ($index = 1; $index <= $num_hhclass; $index++) {
    if ($_GET['xml']) {
      echo "<".$hhclass[$index-1][0].">";
      echo preg_replace("/\n$/","",str_replace("&","&amp;",str_replace("&nbsp;"," ",preg_replace("/<[^<>]+>/","",$hh[$index]))));
      echo "</".$hhclass[$index-1][0].">\n";
    } else {
      echo $hh[$index]."\n";
    }
  }

  /*** PRINT MEMBER INFO IF REQUESTED ***/

  if ($_GET['members']) {
    if (!$_GET['xml']) {
      if (stripos($_GET['member_set'],"comma")!==FALSE) {
        echo "<p><font class = \"".$memclass[0][0]."\"><b>Members: </b>";
      } else {
        echo "<p class = \"".$hhclass[0][0]."\">Members:</p>\n";
        if (stripos($_GET['member_set'],"table")!==FALSE) {
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
    $result = sqlquery_checked($sql);
    $num_mem = mysqli_num_rows($result);
    $mem_count = 1;
    if ($_GET['xml']) {
      echo "<memberlist>";
    }
    while ($mem = mysqli_fetch_array($result)) {
      for ($index = 0; $index < $num_memclass; $index++) {
        if (stripos($_GET['member_set'],"comma")!==FALSE && ($mem_count==$num_mem)) {
          if ($_GET['xml']) {
            echo preg_replace("/, $/","",$mem[$index]);
          } else {
            echo preg_replace("/, $/","",$mem[$index])."</font></p>\n";
          }
        } else {
          if ($_GET['xml']) {
            if (stripos($_GET['member_set'],"comma")!==FALSE) {
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
    if ($_GET['xml']) {
      echo "</memberlist>\n";
    } else {
      if (stripos($hmember_set,"table")!==FALSE) {
        echo "</table>\n";
      }
    }
  }
  if ($_GET['xml']) {
    echo "</household>\n";
  } else {
    echo "</div>\n";
  }
}
if ($_GET['xml']) {
  echo "</householdlist>\n";
} else {
  if (stripos($_GET['household_set'],"table")!==FALSE && !$_GET['xml']) {
    echo "</table>\n";
  }
  echo "</div></body></html>";
}
?>
