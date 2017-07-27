<?php
include("functions.php");
include("accesscontrol.php");
echo "<html><head>";
echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['charset']."\">\n";
?>
<title>Multiple Person Overviews</title>
<style>
body,div,p,td,th { font-size:10pt; font-family:sans-serif;}
th { font-weight:bold; }
h1.name { margin:0 0 8px 0; font-size:20pt;}
h2.hhtitle, h2.contitle, h2.atttitle, h2.dontitle, h2.pltitle {
  margin:15px 0 5px 0; font-weight:bold; font-size:16pt; font-style: italic;}
table { border-collapse:collapse; empty-cells:show; border:2px solid black;}
td, th { border:1px solid black; padding:2px 5px;}
th { border-bottom:2px; background-color:#E0E0E0;}
table.maininfo { border:0;}
table.maininfo td { border:0; padding:0; font-size:11pt; vertical-align:top; }
table.maininfo td.photocell { border:0; padding-right:15px;}
table.maininfo td.photocell img { border:2px solid black; }
span.romajiaddr { font-style:italic; }
p.cat { font-size:11pt; }
span.label { font-weight:bold; }
<?php
if ($_POST['break']=="line") {
  echo "div.personbreak { font-size:2px; line-height:2px; border-bottom:3px solid black;";
  echo " margin:10px 0 12px 0; }\n";
} else {
  echo "div.personbreak { page-break-after:always; font-size:2px; line-height:2px; margin:0; padding:0; }\n";
  echo "@media screen {\n";
  echo "  div.personbreak { border-bottom:1px dashed black; margin:8px 0 10px 0; }\n";
  echo "}\n";
}
?>
</style>
</head><body>
<?php
$pid_array = explode(",",$pid_list);
$num_pids = count($pid_array);
for ($pid_index=0; $pid_index<$num_pids; $pid_index++) {
  $sql = "SELECT person.*,NonJapan,Address,Phone,FAX,RomajiAddress,postalcode.* ".
    "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
    "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
    "WHERE PersonID=$pid_array[$pid_index] ORDER BY Furigana";
  $result_per = sqlquery_checked($sql);
  $person = mysqli_fetch_object($result_per);

  echo "<div class=\"person\">\n";
  echo "<table class=\"maininfo\"><tr>";
  if ($person->Photo) {
    echo "<td class=\"photocell\"><img class=\"photo\" src=\"photos/p".$person->PersonID.".jpg\" width=100></td>\n";
  }
  echo "<td>";
  echo "<h1 class=\"name\">".readable_name($person->FullName,$person->Furigana)."</h1>\n";
  $text = "";
  if ($person->Sex) {
    $text .= (($person->Sex=="F")?_("Female"):_("Male"));
  }
  if ($person->Birthdate && (substr($person->Birthdate,0,4) != "0000")) {
    if (substr($person->Birthdate,0,4) == "1900") {
      $text .= ($text?", ":"") . "<span class=\"label\">"._("Birthday").":</span> ".substr($person->Birthdate,5);
    } else {
      $text .= ($text?", ":"") . "<span class=\"label\">"._("Age").":</span> ".age($person->Birthdate)." ("._("born")." ".$person->Birthdate.")";
    }
  }
  if ($person->Country) $text .= ($text?", ":"") . "<span class=\"label\">"._("Home Country").":</span> ".$person->Country;
  if ($person->URL) $text .= ($text?", ":"") . "<span class=\"label\">"._("URL").":</span> ".$person->URL;
  if ($text) {
    echo $text . "<br />\n";
    $text = "";
  }
  if ($person->CellPhone) $text .= "<span class=\"label\">"._("Cell Phone").":</span> ".$person->CellPhone;
  if ($person->Email) $text .= ($text?", ":"") . "<span class=\"label\">"._("Email").":</span> ".$person->Email;
  if ($text) {
    echo $text . "<br />\n";
    $text = "";
  }
  if ($person->Address) {
    $text .= $person->PostalCode.$person->Prefecture.$person->ShiKuCho.db2table($person->Address);
    if (!$person->NonJapan && ($_SESSION['romajiaddresses']=="yes")) {
      $text .= "<br />\n<span class=\"romajiaddr\">".db2table($person->RomajiAddress)." "
      .db2table($person->Romaji)." &nbsp;".$person->PostalCode."</span>\n";
    }
    echo $text . "<br />\n";
    $text = "";
  }
  if ($person->Phone) $text .= "<span class=\"label\">"._("Phone").":</span> ".$person->Phone;
  if ($person->FAX) $text .= ($text?", ":"") . "<span class=\"label\">"._("FAX").":</span> ".$person->FAX;
  echo "</td></tr></table>\n";
  if ($person->Remarks) {
    echo "<table class=\"maininfo\"><tr>";
    echo "<td><span class=\"label\">"._("Remarks").":</span>&nbsp;</td>\n";
    echo "<td>".db2table($person->Remarks)."</td></tr>\n";
    echo "</table>\n";
  }

/*** CATEGORIES ***/

  if ($_POST['categories']) {
    $sql = "SELECT Category FROM percat JOIN category ON percat.CategoryID=category.CategoryID "
    ."WHERE PersonID=".$person->PersonID." ORDER BY Category";
    $result = sqlquery_checked($sql);
    echo "<p class=\"cat\"><span class=\"label\">"._("Categories").":</span> ";
    if (mysqli_num_rows($result) == 0) {
      echo "none</p>\n";
    } else {
      $text = "";
      while ($row = mysqli_fetch_object($result)) {
        $text .= ($text?" / </span>":"") . "<span nowrap>".$row->Category;
      }
      echo $text."</span></p>\n";
    }
  }

/*** HOUSEHOLD MEMBER INFO ***/

  if ($_POST['household'] && $person->HouseholdID) {
    $sql = "SELECT * FROM person WHERE HouseholdID = ".$person->HouseholdID
    ." AND PersonID != ".$person->PersonID
    ." ORDER BY FIELD(Relation,'Child','Spouse','Main') DESC, Birthdate";
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result) > 0) {
      echo "<h2 class=\"hhtitle\">"._("Other Members of Household")."</h2>\n";
      echo "<table class=\"hhtable\">";
      echo "<tr><th>"._("Name")."</th>";
      echo "<th>"._("Photo")."</th>";
      echo "<th>"._("Household")." "._("Relation")."</th>";
      echo "<th>"._("Sex")."</th>";
      echo "<th>"._("Birthday")." ("._("Age").")</th></tr>";
      while ($row = mysqli_fetch_object($result)) {
        echo "<tr><td nowrap>".readable_name($row->FullName,$row->Furigana);
        echo "</td>\n<td align=center>";
        if ($row->Photo == 1) echo "<img border=0 src=\"photos/p".$row->PersonID.".jpg\" width=40>";
        echo "</td>\n<td align=center>";
        if ($row->Relation) echo $row->Relation;
        echo "</td>\n<td align=center>";
        if ($row->Sex) echo $row->Sex;
        echo "</td>\n<td align=center>";
        if ($row->Birthdate && $row->Birthdate != "0000-00-00") {
          if (preg_match("/^1900-/",$row->Birthdate)) {
            echo substr($row->Birthdate,5);
          } else {
            echo $row->Birthdate." ("._("Age")." ".age($row->Birthdate).")";
          }
        }
        echo "</td>\n</tr>";
      }
      echo "  </table>";
    }
  } //if household to be printed

/*** CONTACTS ***/

  if ($_POST['actions']) {
    $sql = "SELECT action.*, actiontype.ActionType, actiontype.BGColor FROM action"
    ." JOIN actiontype ON action.ActionTypeID=actiontype.ActionTypeID"
    ." WHERE action.PersonID=".$person->PersonID." ORDER BY action.ActionDate DESC";
    $result = sqlquery_checked($sql);
    if (($numrows = mysqli_num_rows($result)) > 0) {
      echo "<h2 class=\"contitle\">"._("Actions");
      if ($_POST['action_types']!="all") echo " ("._("first, last, and key actions").")</h2>\n";
      echo "<table class=\"contable\">";
      echo "<tr><th>"._("Date")."</th>";
      echo "<th>"._("Type")."</th>";
      echo "<th>"._("Description")."</th></tr>";
      $rownum = 1;
      while ($row = mysqli_fetch_object($result)) {
        if (($_POST['action_types']=="all") || ($rownum==1) || ($rownum==$numrows) || ($row->BGColor!="FFFFFF")) {
          echo "<tr><td align=center style=\"background-color:#".$row->BGColor."\">".$row->ActionDate."</td>\n";
          echo "<td align=center style=\"background-color:#".$row->BGColor."\">".$row->ActionType."</td>\n";
          echo "<td align=left style=\"background-color:#".$row->BGColor."\">".$row->Description."</td></tr>\n";
        }
        $rownum++;
      }
      echo "  </table>";
    }
  } //if actions to be printed

/*** EVENT ATTENDANCE ***/

  if ($_POST['attendance']) {
    $sql = "SELECT e.Event, min(a.AttendDate) AS first, max(a.AttendDate) AS last,".
    " COUNT(a.AttendDate) AS times, e.Remarks FROM event e, attendance a WHERE e.EventID = a.EventID".
    " AND a.PersonID = ".$person->PersonID." GROUP BY e.Event ORDER BY last";
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result) > 0) {
      echo "<h2 class=\"attendtitle\">"._("Event Attendance")."</h2>\n";
      echo "<table class=\"attendtable\">";
      echo "<tr><th>"._("Event")."</th>";
      echo "<th>"._("Date")."</th>";
      echo "<th>"._("Description")."</th></tr>";
      while ($row = mysqli_fetch_object($result)) {
        echo "<tr><td nowrap>".$row->Event."</td><td nowrap>";
        if ($row->first == $row->last) {
          echo $row->first;
        } else {
          echo $row->first." to<br>".$row->last." (".$row->times."x)";
        }
        echo "</td><td>".$row->Remarks."</td></tr>";
      }
      echo "  </table>";
    }
  } //if attendance to be printed

/*** DONATIONS & PLEDGES ***/

#### THIS SECTION UNDER CONSTRUCTION ####
  if ($_POST['donations'] && $_SESSION['donations'] == "yes") {
    $sql = "SELECT pl.PledgeID, pl.DonationTypeID, pl.PledgeDesc, dt.BGColor FROM pledge pl ".
      "LEFT JOIN donationtype dt ON pl.DonationTypeID=dt.DonationTypeID WHERE PersonID=".$person->PersonID.
      " AND (EndDate IS NULL OR EndDate>CURDATE()) ORDER BY PledgeDesc";
    $result = sqlquery_checked($sql);
    if (mysqli_num_rows($result) > 0) {
      echo "<h2 class=\"pledgetitle\">"._("Pledges")."</h2>\n";
      echo "<table class=\"pledgetable\">";
      echo "<tr><th>"._("Event")."</th>";
      echo "<th>"._("Date")."</th>";
      echo "<th>"._("Description")."</th></tr>";
      while ($row = mysqli_fetch_object($result)) {
        echo "<tr><td nowrap>".$row->Event."</td><td nowrap>";
        if ($row->first == $row->last) {
          echo $row->first;
        } else {
          echo $row->first." to<br>".$row->last." (".$row->times."x)";
        }
        echo "</td><td>".$row->Remarks."</td></tr>";
      }
      echo "  </table>";
    }
  } //if donations to be printed

  echo "</div>\n"; //person
  if ($pid_index < $num_pids-1) echo "<div class=\"personbreak\"></div>\n";
} //loop through pids
echo "</body></html>";
?>
