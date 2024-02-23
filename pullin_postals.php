<?php
set_time_limit(300);
include("functions.php");
include("accesscontrol.php");
mysqli_select_db($db,"kizuna_common");

setlocale(LC_ALL, 'ja_JP.UTF8');
header1(_("Update of Auxiliary Postal Code Data"));
?> <link rel="stylesheet" type="text/css" href="style.php" /> <?php
header2(1);

$filename = (empty($_GET['file']) ? 'KEN_ALL_ROME' : $_GET['file']);
if (!is_file($filename.'.CSV')) {
  echo "File $filename.CSV does not exist. You might need to:\n";
  echo "<ul><li>Get <a href=\"https://www.post.japanpost.jp/zipcode/download.html\">KEN_ALL_ROME.zip</a> and unzip it</li>\n";
  echo "<li>Put the CSV file in the codebase directory</li>\n";
  exit;
}
if (!is_writable('.')) {
  echo "Code directory needs to be writable to create 'needfix' files.\n";
  echo "Best to run this in dev VM and use an SQL dump (via tool or CLI) to copy the final data.\n";
  exit;
}

if (empty($_GET['dryrun']) && empty($_GET['append'])) {
  sqlquery_checked("DROP TABLE IF EXISTS auxpostalcode");
  $sql = <<<SQL
  CREATE TABLE auxpostalcode (
    PostalCode varchar(8) NOT NULL default '',
    Prefecture varchar(12) NOT NULL default '',
    ShiKuCho varchar(50) NOT NULL default '',
    RomajiPref varchar(30) NOT NULL default '',
    RomajiShiKuCho varchar(255) NOT NULL default '',
    PRIMARY KEY PostalCode (PostalCode)) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SQL;
  sqlquery_checked($sql);
}

define('PC',0);
define('PREF',1);
define('SHI',2);
define('CHO',3);
define('RPREF',4);
define('RSHI',5);
define('RCHO',6);

// prepared statement for inserting data
$insert = mysqli_stmt_init($db);
mysqli_stmt_prepare($insert,"INSERT INTO auxpostalcode(PostalCode,Prefecture,ShiKuCho,RomajiPref,RomajiShiKuCho) VALUES (?,?,?,?,?)");
mysqli_stmt_bind_param($insert, 'sssss', $pc,$pref,$newcho,$rpref,$romaji);

// prepared statements for dup processing
/*$select = mysqli_stmt_init($db);
mysqli_stmt_prepare($select,"SELECT * FROM auxpostalcode WHERE PostalCode=?");
mysqli_stmt_bind_param($select, 's', $data[PC]);

$update = mysqli_stmt_init($db);
mysqli_stmt_prepare($update,"UPDATE auxpostalcode SET ShiKuCho=?, RomajiShiKuCho=? WHERE PostalCode=?");
mysqli_stmt_bind_param($update, 'sss', $newcho, $romaji, $data[PC]);*/


$handle = fopen($filename.'.CSV', "r");
$needjpspaces_handle = fopen($filename.'_needjpspaces.CSV', "w");
$needromspaces_handle = fopen($filename.'_needromspaces.CSV', "w");
$needfix_handle = fopen($filename.'_needfix.CSV', "w");
$suffixes = ['KU','SHI','GUN','CHO','MACHI','SON','MURA'];
$trunc_suffixes = ['MA'=>'町MACHI', 'MAC'=>'町MACHI', 'MACH'=>'町MACHI',
  'SH'=>'市SHI', 'SO'=>'村SON', 'C'=>'町CHO', 'CH'=>'町CHO', 'MU'=>'村MURA', 'MUR'=>'村MURA'];
$max_field_length = 35;  // increase this if PO changes their database

$prev_pc = array();  // to check for duplicate PCs
if (!empty($_GET['append'])) {
  $pcquery = sqlquery_checked('SELECT PostalCode FROM auxpostalcode');
  while ($pc = mysqli_fetch_object($pcquery)) {
    $prev_pc[] = $pc->PostalCode;
  }
}
$num_total = $num_inserted = $num_dups = $num_needjpspaces = $num_needromspaces = $num_needfix = 0;

while (($line = fgets($handle, 1024)) !== FALSE) {
  if (empty($_GET['utf']))  $line = mb_convert_encoding(trim($line),'UTF-8','SJIS');
  $data = mb_split(",",$line);
  if(count($data) < 6)  continue;  //probably a blank line
  $num_total++;

  for ($i=0; $i<7; $i++) {
    $data[$i] = trim($data[$i],'"');
  }

  // postal code: insert dash
  $data[PC] = mb_substr($data[PC],0,3)."-".mb_substr($data[PC],3,4);

  // Handle duplicate postal codes
  $dup = FALSE;
  if (in_array($data[PC],$prev_pc)) {
    $num_dups++;
    if (is_numeric(substr($data[RCHO],0,1))) {
      //just a continuation of a parenthetical phrase
      continue;
    } else {
      $dup = TRUE;  // some other reason for duplication - handle later
    }
  }

  $data[SHI] = str_replace('　','', $data[SHI]);  // remove spaces in Japanese municipality

  // Romaji prefecture: change case and hyphenate
  $data[RPREF] = str_replace(' ','-',ucfirst(strtolower($data[RPREF])));

  // Cho processing
  if ($data[CHO]=='以下に掲載がない場合') {
    $data[CHO] = $data[RCHO] = $romaji = '';
  } elseif (mb_strlen($data[CHO]) < 3) {  // too short for hyphen or space
    $romaji = $data[RCHO].', ';
  } else {  // long enough to process further

    // remove parenthetical bit (which might not have ending parenth)
    $parenth = mb_strpos($data[CHO],'（');
    if ($parenth !== FALSE) {
      $data[CHO] = trim(mb_substr($data[CHO], 0, $parenth), '　');
    }
    $parenth = strpos($data[RCHO],'(');
    if ($parenth !== FALSE) {
      $data[RCHO] = trim(substr($data[RCHO],0,$parenth),' ');
    }

    // Test for mismatch of spaces - write problem entries to new files for human inspection
    if (strpos($data[RCHO],' ') !== FALSE && strpos($data[CHO],'　') === FALSE) {
      $machi_pos = mb_strpos($data[CHO],'町');
      if (preg_match('/[A-Z]+(MACHI|CHO) [A-Z]+/',$data[RCHO]) && $machi_pos !== FALSE
          && $machi_pos > 0 && $machi_pos < mb_strlen($data[CHO]-1)) {
        // machi character in center of string
        $data[CHO] = mb_substr($data[CHO],0,$machi_pos+1).'　'.mb_substr($data[CHO],$machi_pos+1);
      } else {
        fwrite($needjpspaces_handle, $line."\n", 1024);
        $num_needjpspaces++;
        continue;
      }
    } elseif (strpos($data[RCHO],' ') === FALSE && strpos($data[CHO],'　') !== FALSE) {
      fwrite($needromspaces_handle, $line."\n", 1024);
      $num_needromspaces++;
      continue;
    }

    if (mb_strlen($data[CHO]) < 3) {  // now too short for hyphen or space
      $romaji = $data[RCHO].', ';
    } else {  // still long enough to process further

      // make arrays for processing
      $cho_array = explode('　',$data[CHO]); // for reference during romaji cleanup
      $data[CHO] = implode('',$cho_array);  // remove spaces (easy method, since array now exists)
      $rcho_array = explode(' ',$data[RCHO]); // for romaji cleanup

      // check for direction words
      $dir_found = FALSE;
      foreach ($cho_array as $i => $cho_word) {
        if (mb_strlen(mb_ereg_replace('町$','',$cho_word)) > 2) {  // at least 3 non-町 kanji
          if ($cho_word != ($cho_word_split = mb_ereg_replace('^北', '北 ', $cho_word))) {
            $rcho_word_split = preg_replace('/^KITA/', 'KITA ', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          } elseif ($cho_word != ($cho_word_split = mb_ereg_replace('^南', '南 ', $cho_word))) {
            $rcho_word_split = preg_replace('/^MINAMI/', 'MINAMI ', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          } elseif ($cho_word != ($cho_word_split = mb_ereg_replace('^東', '東 ', $cho_word))) {
            $rcho_word_split = preg_replace('/^HIGASHI/', 'HIGASHI ', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          } elseif ($cho_word != ($cho_word_split = mb_ereg_replace('^西', '西 ', $cho_word))) {
            $rcho_word_split = preg_replace('/^NISHI/', 'NISHI ', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          } elseif ($cho_word != ($cho_word_split = mb_ereg_replace('北$', ' 北', $cho_word))) {
            $rcho_word_split = preg_replace('/KITA$/', ' KITA', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          } elseif ($cho_word != ($cho_word_split = mb_ereg_replace('南$', ' 南', $cho_word))) {
            $rcho_word_split = preg_replace('/MINAMI$/', ' MINAMI', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          } elseif ($cho_word != ($cho_word_split = mb_ereg_replace('東$', ' 東', $cho_word))) {
            $rcho_word_split = preg_replace('/HIGASHI$/', ' HIGASHI', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          } elseif ($cho_word != ($cho_word_split = mb_ereg_replace('西$', ' 西', $cho_word))) {
            $rcho_word_split = preg_replace('/NISHI$/', ' NISHI', $rcho_array[$i]);
            if ($rcho_array[$i] != $rcho_word_split) {
              $cho_array[$i] = $cho_word_split;
              $rcho_array[$i] = $rcho_word_split;
              $dir_found = TRUE;
            }
          }
        }
      }
      if ($dir_found) {  // reconstruct arrays after spaces added
        $temp_array = implode(' ',$cho_array);
        $cho_array = explode(' ',$temp_array);
        $temp_array = implode(' ',$rcho_array);
        $rcho_array = explode(' ',$temp_array);
      }

      // check for cho/machi
      foreach ($cho_array as $i => $cho_word) {
        if ((mb_strlen($cho_word) > 2) && (mb_strpos($cho_word.'#','町#')!==FALSE)) {
          $rcho_array[$i] = preg_replace('/MACHI$/', '-MACHI', $rcho_array[$i]);
          $rcho_array[$i] = preg_replace('/CHO$/', '-CHO', $rcho_array[$i]);
        }
      }

      // put it back together (possible to have more romaji "words" than kanji, but it's good enough)
      $romaji = implode(' ',$rcho_array).', ';

    } // end of detailed cho processing with array
  } // end of cho processing

  // Romaji shi, ku, gun, etc.: hyphenate and reverse order with a comma
  $shi_array = explode(' ',$data[RSHI]);
  $i = count($shi_array) - 1;

  // try to complete truncated suffixes
  if ( $i > 1  //not the only word in the string
      && strlen($data[RSHI])>=$max_field_length  //at least as long as the last-known max
      && array_key_exists($shi_array[$i],$trunc_suffixes)  //one of the short bits we can anticipate
      && mb_substr($trunc_suffixes[$shi_array[$i]],0,1)==
          mb_substr($data[SHI],mb_strlen($data[SHI])-1,1)  //last kanji matches
      && !in_array($shi_array[$i-1],$suffixes)) {  //the previous word is not a suffix
    $shi_array[$i] = mb_substr($trunc_suffixes[$shi_array[$i]],1);
  }

  while($i >= 0) {
    if (in_array($shi_array[$i],$suffixes) && $i>0) {  // location-suffix pair
      $romaji .= $shi_array[$i-1] . '-' . $shi_array[$i];
      $i--;
    } else {
      if (strlen($shi_array[$i])<5 && $i>0) {
        echo "<h3>Should '{$shi_array[$i]}' be a suffix? (PC {$data[PC]})</h3>";
        $suffixes[] = $shi_array[$i];
        $romaji .= $shi_array[$i-1] . '-' . $shi_array[$i];
        $i--;
      } else {
        $romaji .= $shi_array[$i];
      }
    }
    if ($i > 0)  $romaji .= ', ';
    $i--;
  }

  $romaji = ucwords(strtolower($romaji));

  //handle duplicate postal codes
  if ($dup) {
    if (!empty($_GET['dryrun']))  continue;  //dup processing depends on database table use

    $dupquery = sqlquery_checked("SELECT * FROM auxpostalcode WHERE PostalCode='{$data[PC]}'");
    /* TEST */ if (mysqli_num_rows($dupquery)==0) echo '<h3>'.$data[PC].' not in DB! (2nd cho="'.$data[CHO].'")</h3>';
    $dup = mysqli_fetch_object($dupquery);
    if (!empty($dup) && $dup->ShiKuCho != '' && mb_strpos($data[SHI].$data[CHO], $dup->ShiKuCho) === FALSE) {  //needs shortening
      $len = mb_strlen($dup->ShiKuCho) - 1;
      while ($len > 0) {  // look for longest piece of Japanese in common
        if (mb_strpos($data[SHI].$data[CHO],mb_substr($dup->ShiKuCho,0,$len)) === 0) {  //short enough
          $newcho = mb_substr($dup->ShiKuCho,0,$len);
          break;
        }
        $len--;
      }
      if ($len==0) $newcho = '';  //nothing matched

      // now do the romaji
      $dbrom_array = explode(',',$dup->RomajiShiKuCho,2);
      $newrom_array = explode(',',$romaji,2);
      $len = strlen($dbrom_array[0]) - 1;
      while ($len > 1) {  // 1 not 0 to avoid coincidentally matching single letter
        if (strpos($newrom_array[0],substr($dbrom_array[0],0,$len)) === 0) {  //short enough
          $newrom_array[0] = trim(substr($dbrom_array[0],0,$len));
          break;
        }
        $len--;
      }
      if ($len < 2) unset($newrom_array[0]);  //nothing matched
      $romaji = trim(implode(',',$newrom_array));
      sqlquery_checked("UPDATE auxpostalcode SET ShiKuCho='$newcho', RomajiShiKuCho='$romaji' WHERE PostalCode='{$data[PC]}'");
    }  // end if existing entry needs to be shortened

  } else {  // not duplicate, so go ahead and insert to the database
    $pc = $data[PC];
    $pref = $data[PREF];
    $newcho = $data[SHI].$data[CHO];
    $rpref = $data[RPREF];
    if (empty($_GET['dryrun'])) {
      if (!mysqli_stmt_execute($insert)) {
        // hmm, insert failed
        printf("<p><br>Insert failed: %s</p>", mysqli_error($db));
        fwrite($needfix_handle, $line."\n", 1024);
        $num_needfix++;
        echo "<p><strong>FIX:</strong> $line</p>";
        continue;
      } else {  // success
        $num_inserted++;
        $prev_pc[] = $data[PC];
      }
    }
  }

}  // end file processing

fclose($handle);
fclose($needjpspaces_handle);
fclose($needromspaces_handle);
fclose($needfix_handle);
if (filesize($filename.'_needjpspaces.CSV') == 0) unlink($filename.'_needjpspaces.CSV');
if (filesize($filename.'_needromspaces.CSV') == 0) unlink($filename.'_needromspaces.CSV');
if (filesize($filename.'_needfix.CSV') == 0) unlink($filename.'_needfix.CSV');
//if (empty($_GET['dryrun']) && empty($_GET['keepfile']))  unlink($filename);

echo "</p><h2><br>In theory, all was completed.</h2><p>".
"$num_total total entries processed.<br>$num_inserted entries inserted into database.<br>".
"$num_dups duplicates found and handled.<br>$num_needjpspaces entries need JP spaces (see CSV).<br>".
"$num_needromspaces entries need romaji spaces (see CSV).<br>$num_needfix entries need other fixes (see CSV).<br>".
'Edit the new CSV files, then run again with "?append=1&utf=1&file=(the-filename-root)".</p>';
footer();
?>
