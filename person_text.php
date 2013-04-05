<?php
include("functions.php");
include("accesscontrol.php");
if ($format == "xml") {
  echo "<?xml version=\"1.0\" encoding=\"".$_SESSION['charset']."\" ?>\n<personlist>\n";
} elseif ($format == "html") {
  echo "<html><head>";
  echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_SESSION['charset']."\">\n";
  echo "<style type=\"text/css\">p {margin-bottom: 0; margin-top: 0;}</style>";
  echo "</head><body>";
}

$pid_array = split(",",$pid_list);
$num_pids = count($pid_array);
for ($pid_index=0; $pid_index<$num_pids; $pid_index++) {
  $sql = "SELECT FullName,Furigana,Sex,Birthdate,CellPhone,Email,URL,Country,person.Photo,Remarks,".
  "NonJapan,Address,RomajiAddress,Phone,FAX,postalcode.* ".
    "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
    "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
    "WHERE PersonID=$pid_array[$pid_index] ORDER BY Furigana";
  if (!$result = mysql_query($sql)) {
    echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
    exit;
  }
  $row = mysql_fetch_object($result);
  if ($xml) {
    echo "<person>\n";
  }
  for ($i=1; $i<7; $i++) {
    if (${"field".$i} != "") {
      $text = "";
      switch (${"field".$i}) {
        case "readable":
          $text = readable_name($row->FullName,$row->Furigana);
          break;
        case "furigana":
          if (ereg("^[a-zA-Z]",$row->FullName)) {  //name is in English letters
            $text = $row->Furigana;
          } else {
            $text = $row->Furigana." (".$row->FullName.")";
          }
          break;
        case "address":
          if ($row->Address) {
            if (!$row->NonJapan) {
              $text = $row->PostalCode." ".$row->Prefecture.$row->ShiKuCho." ".$row->Address;
            } else {
              $text = $row->Address;
            }
          }
          break;
        case "romajiaddress":
          if ($row->RomajiAddress) {
            $text = $row->RomajiAddress." ".$row->Romaji." ".$row->PostalCode;
          }
          break;
        case "postalcode":
          if ($row->Address) {
            if (!$row->NonJapan) {
              $text = $row->PostalCode;
            } else {
              $text = "non-Japan";
            }
          }
          break;
        case "phones":
          if ($row->CellPhone && $row->Phone) {
            if ($format == "tab") {
              $text = $row->Phone." or ".$row->CellPhone;
            } else {
              $text = "Phone: ".$row->Phone." or ".$row->CellPhone;
            }
          } elseif ($row->CellPhone || $row->Phone)  {
            if ($format == "tab") {
              $text = $row->Phone.$row->CellPhone;
            } else {
              $text = "Phone: ".$row->Phone.$row->CellPhone;
            }
          }
          break;
        case "fax":
          if ($row->FAX) {
            if ($format == "tab") {
              $text = $row->FAX;
            } else {
              $text = "FAX: ".$row->FAX;
            }
          }
          break;
        case "birthday":
          if ($row->Birthdate && $row->Birthdate != '0000-00-00') {
            if ($format == "tab") {
              $text = substr($row->Birthdate,5);
            } else {
              $text = "Birthday: ".substr($row->Birthdate,5);
            }
          }
          break;
        case "birthdate":
          if ($row->Birthdate && $row->Birthdate != '0000-00-00') {
            if (substr($row->Birthdate,0,4) == "1900") {
              $text = "????" . substr($row->Birthdate,5);
            } else {
              $text = $row->Birthdate;
            }
          }
          break;
        case "age":
          if ($row->Birthdate && $row->Birthdate != '0000-00-00' && substr($row->Birthdate,0,4) != "1900") {
            $text = age($row->Birthdate);
          }
          break;
        case "birthdate-age":
          if ($row->Birthdate && $row->Birthdate != '0000-00-00') {
            if (substr($row->Birthdate,0,4) == "1900") {
              $text = (($format!="tab")? "Birthday " : "") . substr($row->Birthdate,5) . " [age ?]";
            } else {
              $text = (($format!="tab")? "Born " : "") . $row->Birthdate." [age ".age($row->Birthdate)."]";
            }
          }
          break;
        case "photo":
          if ($row->Photo) {
            $text = "photos/p".$pid_array[$pid_index].".jpg";
          } elseif ($include_empties) {
            $text = "photos/no_photo.gif";
          }
          break;
        case "category":
          $array = split(" ",${"cat".$i});
          $where1 = split("=",$array[0]);
          $where = (($where1[0]=="in")?"":"NOT ") . "CategoryID=" . $where1[1];
          if (count($array) == 3) {
            $where2 = split("=",$array[2]);
            $where .= " " . $array[1] . " " . (($where2[0]=="in")?"":"NOT ") . "CategoryID=" . $where2[1];
            if ($array[1] == "OR")  $where = "(".$where.")";
          }
          $sql = "SELECT * from percat WHERE PersonID=".$pid_array[$pid_index]." AND " . $where;
          if (!$result = mysql_query($sql)) {
            echo("<b>SQL Error: ".mysql_errno().": ".mysql_error()."</b><br>($sql)");
            exit;
          }
          if (mysql_num_rows($result) > 0) {
            $text = ${"mark".$i};
          }
          break;
        case " ";
          $text = " ";
          break;
        default:
          $text = $row->{${"field".$i}};
      }  //end of switch statement
      if ($text || $include_empties || $format == "tab") {
        if ($format == "xml") {
          $text = ereg_replace("&","&amp;",$text);
          $text = ereg_replace("'","&apos;",$text);
          if (${"field".$i} == "category") {
            echo "<".${"tag".$i}.">".$text."</".${"tag".$i}.">\n";
          } else {
            echo "<".${"field".$i}.">".$text."</".${"field".$i}.">\n";
          }
        } elseif ($format == "tab") {
          $text = ereg_replace("\r\n|\n|\r","<br>",$text);
          echo ($i == 1) ? $text : "\t".$text;
        } else {
          if (${"field".$i} == "photo" && $row->Photo) {
            $text = "<img width=\"150\" src=\"".$text."\" />";
          }
          $text = ereg_replace("\r\n|\n|\r","<br>\n",$text);
          echo ${"layout".$i} . $text;
          if (substr(${"layout".$i},-1) == "(")  echo ")";
        }
      }
    }
  }
  if ($format == "xml") {
    echo "</person>\n";
  } elseif ($format == "tab") {
    echo "\n";
  }
}
if ($format == "xml") {
  echo "</personlist>\n";
} elseif ($format == "html") {
  echo "</body></html>";
}
?>
