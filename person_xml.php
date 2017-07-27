<?php
include("functions.php");
include("accesscontrol.php");

header('Content-Disposition: attachment; filename="custom.xml"');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
echo "<personlist>\n";
$sql = "SELECT person.*, household.*, postalcode.* ".
    "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
    "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
    "WHERE PersonID in ($pid_list) ORDER BY $orderby";
$result = sqlquery_checked($sql);

while($row = mysqli_fetch_object($result)) {
  if ($row->Birthdate == '0000-00-00') $row->Birthdate = "";
  if (substr($row->Birthdate,0,4) == '1900') $row->Birthdate = "????".substr($row->Birthdate,4);
  
  echo "  <person>\n";
  for ($fieldindex=1; ${"field".sprintf("%02d",$fieldindex)}; $fieldindex++) {
    switch (${"field".sprintf("%02d",$fieldindex)}) {
    case "Name-Furigana":
      echo "    <Name-Furigana>".readable_name($row->FullName,$row->Furigana,0,0,"",0)."</Name-Furigana>\n";
      break;
    case "Furigana-Name":
      echo "    <Furigana-Name>".readable_name($row->FullName,$row->Furigana,0,0,"",1)."</Furigana-Name>\n";
      break;
    case "Address":
      if ($row->Address) {
        if (!$row->NonJapan) {
          echo "    <Address>".$row->PostalCode." ".$row->Prefecture.$row->ShiKuCho." ".$row->Address."</Address>\n";
        } else {
          echo "    <Address>".$row->Address."</Address>\n";
        }
      }
      break;
    case "RomajiAddress":
      if ($row->RomajiAddress) {
        echo "    <RomajiAddress>".$row->RomajiAddress." ".$row->Romaji." ".$row->PostalCode."</RomajiAddress>\n";;
      }
      break;
    case "Phone-Cell":
      if ($row->CellPhone && $row->Phone) {
        echo "    <Phone-Cell>".$row->Phone." / ".$row->CellPhone."</Phone-Cell>\n";
      } elseif ($row->CellPhone || $row->Phone) {
        echo "    <Phone-Cell>".$row->Phone.$row->CellPhone."</Phone-Cell>\n";
      }
      break;
    case "Birthday":
      echo "    <Birthday>".substr($row->Birthdate,5)."</Birthday>\n";
      break;
    case "Age":
      echo "    <Age>".(($row->Birthdate && substr($row->Birthdate,0,4) != "1900") ? age($row->Birthdate) : "")."</Age>\n";
      break;
    case "Category":
      $selectvar = "cat".sprintf("%02d",$fieldindex);
      $tagvar = "cattag".sprintf("%02d",$fieldindex);
      $combinevar = "catcombine".sprintf("%02d",$fieldindex);
      $stylevar = "catstyle".sprintf("%02d",$fieldindex);
      $textvar = "cattext".sprintf("%02d",$fieldindex);
      $selectlist = implode(",",$$selectvar);
      $tempresult = sqlquery_checked("SELECT category.Category FROM percat JOIN category ".
          "ON percat.CategoryID=category.CategoryID WHERE PersonID=".$row->PersonID." AND percat.CategoryID IN ($selectlist)");
      if ($$combinevar) {
        if ($$stylevar == "custom") {
          echo "    <".$$tagvar.">".(mysqli_num_rows($tempresult)>0 ? $$textvar : "")."</".$$tagvar.">\n";
        } else {
            while($tmp = mysqli_fetch_object($tempresult)) {
            $tmparray[] = $tmp->Category;
          }
          echo "    <".$$tagvar.">".(isset($tmparray) ? implode(",",$tmparray) : "")."</".$$tagvar.">\n";
          unset($tmparray);
        }
      } else {
        while($tmp = mysqli_fetch_object($tempresult)) {
          echo "    <".$$tagvar.">".$tmp->Category."</".$$tagvar.">\n";
        }
      }
      break;
    case "Action":
      $selectvar = "action".sprintf("%02d",$fieldindex);
      $tagvar = "actiontag".sprintf("%02d",$fieldindex);
      $combinevar = "ctcombine".sprintf("%02d",$fieldindex);
      $stylevar = "actionstyle".sprintf("%02d",$fieldindex);
      $textvar = "actiontext".sprintf("%02d",$fieldindex);
      $selectlist = implode(",",$$selectvar);
      $sql = "SELECT action.*,actiontype.ActionType FROM action JOIN actiontype ".
          "ON action.ActionTypeID=actiontype.ActionTypeID WHERE PersonID=".$row->PersonID.
          " AND action.ActionTypeID IN ($selectlist)";
      $tempresult = sqlquery_checked($sql);
      $separator = ($$combinevar) ? "</".$$tagvar."><".$$tagvar.">" : ",";
      switch ($$stylevar) {
      case "all":
        echo "    <".$$tagvar.">\n";
        while($tmp = mysqli_fetch_object($tempresult)) {
          echo "      <Date>".$tmp->ActionDate."</Date>\n";
          echo "      <ActionType>".$tmp->ActionType."</ActionType>\n";
          echo "      <Description>".$tmp->Description."</Description>\n";
        }
        echo "    </".$$tagvar.">\n";
        break;
      case "type":
        while($tmp = mysqli_fetch_object($tempresult)) {
          $tmparray[] = $tmp->ActionType;
        }
        echo "    <".$$tagvar.">".(isset($tmparray) ? implode($separator,$tmparray) : "")."</".$$tagvar.">\n";
        unset($tmparray);
        break;
      case "desc":
        while($tmp = mysqli_fetch_object($tempresult)) {
          $tmparray[] = $tmp->Description;
        }
        echo "    <".$$tagvar.">".(isset($tmparray) ? implode($separator,$tmparray) : "")."</".$$tagvar.">\n";
        unset($tmparray);
        break;
      case "custom":
        echo "    <".$$tagvar.">".(mysqli_num_rows($tempresult)>0 ? $$textvar : "")."</".$$tagvar.">\n";
      }  // end of switch
      break;
    case "Attendance":
      $selectvar = "attend".sprintf("%02d",$fieldindex);
      $tagvar = "attendtag".sprintf("%02d",$fieldindex);
      $textvar = "attendtext".sprintf("%02d",$fieldindex);
      $selectlist = implode(",",$$selectvar);
      $tempresult = sqlquery_checked("SELECT * FROM attendance WHERE PersonID=".$row->PersonID." AND EventID IN ($selectlist)");
      echo "    <".$$tagvar.">".(mysqli_num_rows($tempresult)>0 ? $$textvar : "")."</".$$tagvar.">\n";
      break;
    case "Members":
      $selectvar = "memberfield".sprintf("%02d",$fieldindex);
      $selectlist = implode(",",$$selectvar);
      $sql = "SELECT person.*, household.*, postalcode.* ".
          "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
          "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
          "WHERE PersonID in (SELECT PersonID FROM perorg WHERE OrgID=".$row->PersonID.") ORDER BY $orderby";
      $tempresult = sqlquery_checked($sql);
      while($tmp = mysqli_fetch_object($tempresult)) {
        if ($tmp->Birthdate == '0000-00-00') $tmp->Birthdate = "";
        if (substr($tmp->Birthdate,0,4) == '1900') $tmp->Birthdate = "????".substr($tmp->Birthdate,4);
        echo "    <member>\n";
        for ($mfindex=0; ${$selectvar}[$mfindex]; $mfindex++) {
          switch (${$selectvar}[$mfindex]) {
          case "Name-Furigana":
            echo "      <Name-Furigana>".readable_name($tmp->FullName,$tmp->Furigana,0,0,"",0)."</Name-Furigana>\n";
            break;
          case "Furigana-Name":
            echo "      <Furigana-Name>".readable_name($tmp->FullName,$tmp->Furigana,0,0,"",1)."</Furigana-Name>\n";
            break;
          case "Address":
            if ($tmp->Address) {
              if (!$tmp->NonJapan) {
                echo "      <Address>".$tmp->PostalCode." ".$tmp->Prefecture.$tmp->ShiKuCho." ".$tmp->Address."</Address>\n";
              } else {
                echo "      <Address>".$tmp->Address."</Address>\n";
              }
            }
            break;
          case "RomajiAddress":
            if ($tmp->RomajiAddress) {
              echo "      <RomajiAddress>".$tmp->RomajiAddress." ".$tmp->Romaji." ".$tmp->PostalCode."</RomajiAddress>\n";;
            }
            break;
          case "Phone-Cell":
            if ($tmp->CellPhone && $tmp->Phone) {
              echo "      <Phone-Cell>".$tmp->Phone." / ".$tmp->CellPhone."</Phone-Cell>\n";
            } elseif ($tmp->CellPhone || $tmp->Phone) {
              echo "      <Phone-Cell>".$tmp->Phone.$tmp->CellPhone."</Phone-Cell>\n";
            }
            break;
          case "Birthday":
            echo "      <Birthday>".substr($tmp->Birthdate,5)."</Birthday>\n";
            break;
          case "Age":
            echo "      <Age>".(($tmp->Birthdate && substr($tmp->Birthdate,0,4) != "1900") ? age($tmp->Birthdate) : "")."</Age>\n";
            break;
          default:
            echo "      <".${$selectvar}[$mfindex].">".$tmp->{${$selectvar}[$mfindex]}."</".${$selectvar}[$mfindex].">\n";
          }  //end of switch statement
        }  // end of for loop through fields
        echo "    </member>\n";
      }  // end of while loop through member records
      break;
    case "Orgs":
      $selectvar = "orgfield".sprintf("%02d",$fieldindex);
      $selectlist = implode(",",$$selectvar);
      $sql = "SELECT person.*, household.*, postalcode.* ".
          "FROM person LEFT JOIN household ON person.HouseholdID=household.HouseholdID ".
          "LEFT JOIN postalcode ON household.PostalCode=postalcode.PostalCode ".
          "WHERE PersonID in (SELECT OrgID FROM perorg WHERE PersonID=".$row->PersonID.") ORDER BY $orderby";
      $tempresult = sqlquery_checked($sql);
      while($tmp = mysqli_fetch_object($tempresult)) {
        echo "    <org>\n";
        for ($mfindex=0; ${$selectvar}[$mfindex]; $mfindex++) {
          switch (${$selectvar}[$mfindex]) {
          case "PersonID":
            echo "      <OrgID>".$tmp->PersonID."</OrgID>\n";
            break;
          case "Name-Furigana":
            echo "      <Name-Furigana>".readable_name($tmp->FullName,$tmp->Furigana,0,0,"",0)."</Name-Furigana>\n";
            break;
          case "Furigana-Name":
            echo "      <Furigana-Name>".readable_name($tmp->FullName,$tmp->Furigana,0,0,"",1)."</Furigana-Name>\n";
            break;
          case "Address":
            if ($tmp->Address) {
              if (!$tmp->NonJapan) {
                echo "      <Address>".$tmp->PostalCode." ".$tmp->Prefecture.$tmp->ShiKuCho." ".$tmp->Address."</Address>\n";
              } else {
                echo "      <Address>".$tmp->Address."</Address>\n";
              }
            }
            break;
          case "RomajiAddress":
            if ($tmp->RomajiAddress) {
              echo "      <RomajiAddress>".$tmp->RomajiAddress." ".$tmp->Romaji." ".$tmp->PostalCode."</RomajiAddress>\n";;
            }
            break;
          case "Phone-Cell":
            if ($tmp->CellPhone && $tmp->Phone) {
              echo "      <Phone-Cell>".$tmp->Phone." / ".$tmp->CellPhone."</Phone-Cell>\n";
            } elseif ($tmp->CellPhone || $tmp->Phone) {
              echo "      <Phone-Cell>".$tmp->Phone.$tmp->CellPhone."</Phone-Cell>\n";
            }
            break;
          default:
            echo "      <".${$selectvar}[$mfindex].">".$tmp->{${$selectvar}[$mfindex]}."</".${$selectvar}[$mfindex].">\n";
          }  //end of switch statement
        }  // end of for loop through fields
        echo "    </org>\n";
      }  // end of while loop through member records
      break;
    default:
      echo "    <".${"field".sprintf("%02d",$fieldindex)}.">".$row->{${"field".sprintf("%02d",$fieldindex)}}."</".${"field".sprintf("%02d",$fieldindex)}.">\n";
    }  //end of switch statement
  }
  echo "  </person>\n";
}
echo "</personlist>\n";
?>