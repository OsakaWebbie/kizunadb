<?php

function header1($title) {
  echo "<!doctype html>\n<html>\n<head>\n<meta charset=\"utf-8\">\n";
  echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
  echo "<link rel=\"icon\" type=\"image/x-icon\" href=\"/kizunadb.ico\">\n";
  echo "<title>{$_SESSION['dbtitle']}: $title</title>\n";
}

function header2($nav=0) {
  echo "<link rel=\"stylesheet\" href=\"css/print.css\" type=\"text/css\" media=\"print\">\n";
  echo "</head>\n";
  $fileroot = substr($_SERVER['php_self'],(strrpos($_SERVER['php_self'],"/")+1),(strrpos($_SERVER['php_self'],".")-strrpos($_SERVER['php_self'],"/")-1));
  echo "<body class=\"".$fileroot.($nav?" full":" simple")."\">\n";

  if ($nav) {
    $navmarkup = "<ul class=\"nav\">\n";
    if ($_SESSION['hasdashboard']) {
      $navmarkup .= "  <li><a href=\"dashboard.php\" target=\"_top\">"._("dashboard")."</a></li>\n";
    }
    $navmarkup .= "  <li><a href=\"search.php\" target=\"_top\">"._("search")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"edit.php\" target=\"_top\">"._("new person/org")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"multiselect.php\" target=\"_top\">"._("multi-select")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"contact.php\" target=\"_top\">"._("contacts")."</a></li>\n";
    if ($_SESSION['donations'] == "yes") {
      $navmarkup .= "  <li><a href=\"donations.php\" target=\"_top\">"._("donations &amp; pledges")."</a></li>\n";
    }
    $navmarkup .= "  <li><a href=\"event_attend.php\" target=\"_top\">"._("event attendance")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"birthday.php\" target=\"_top\">"._("birthdays")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"db_settings.php\" target=\"_top\">"._("db settings")."</a></li>\n";
    if ($_SESSION['admin'] == 1) {
      $navmarkup .= "  <li><a href=\"sqlquery.php\" target=\"_top\">"._("(freeform sql)")."</a></li>\n";
    }
    $navmarkup .= "  <li><a class=\"switchlang\" href=\"#\">".
    ($_SESSION['lang']=='en_us'?'日本語':'english')."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-usersettings\"><a href=\"user_settings.php\" target=\"_top\">"._("user settings")."<span> (".$_SESSION['username'].")</span></a></li>\n";
    $navmarkup .= "  <li><a href=\"index.php?logout=1\" target=\"_top\">"._("log out")."</a></li>\n</ul>\n";
    echo "<nav id=\"scrollnav\"></nav>\n";  //only appears when scrolled

    echo "<div id=\"main-container\">\n";
    echo "<nav id=\"nav-main\">\n$navmarkup</nav>\n";  //main nav for large screens
    echo "<div id=\"nav-trigger\"><img src=\"graphics/kizunadb-logo.png\" alt=\"logo\"><span>menu</span></div>\n";  //button for narrow screens
    echo "<nav id=\"nav-mobile\"></nav>\n";  //vertical menu for narrow screens
  }
  echo "<div id=\"content\">\n";
}

// function footer: sends final html and javascript
function footer() {
  echo "  <div style=\"clear:both\"></div>\n";
  echo "</div>\n"; //end of content div
  echo "</div>\n"; //end of main-container div

  // announcements about new features, etc., if any
  if (isset($_SESSION['announcements'])) {
    echo "<style>\n  div.announcement { border:solid 2px lightgray; padding:5px; margin:5px; }\n";
    echo "  h4.announcedate,p.announcetext,body.dashboard h4.announcedate,body.dashboard p.announcetext { text-align:left; }\n</style>\n";
    echo '<div id="announcements" title="'._("recent announcements")."\">\n";
    foreach($_SESSION['announcements'] as $announcement) {
      echo "  <div class=\"announcement\">\n";
      echo "    <h4 class=\"announcedate\">".substr($announcement->announcetime,0,10)."</h4>\n";
      echo "    <p class=\"announcetext\">".$announcement->html."</p>\n";
      echo "  </div>\n";
    }
    echo "</div>\n";
  } //end if announcements
?>
<script type="text/javascript">
if (window.jquery) { //really simple files that don't have jquery don't need this stuff either
  $(function() {
    $(window).scroll(function() {
      if ($(this).scrolltop() > 150 && !$('#scrollnav').hasclass('visible')) {
        $('#scrollnav').addclass('visible');
      } else if ($(this).scrolltop() <= 150 && $('#scrollnav').hasclass('visible')) {
        $('#scrollnav').removeclass('visible');
      }
    });

    $("#nav-mobile").html($("#nav-main").html());
    $("#scrollnav").html($("#nav-main").html());
    $("#nav-trigger").click(function(){
      if ($("nav#nav-mobile ul").hasclass("expanded")) {
        $("nav#nav-mobile ul.expanded").removeclass("expanded").slideup(250);
        $(this).removeclass("open");
      } else {
        $("nav#nav-mobile ul").addclass("expanded").slidedown(250);
        $(this).addclass("open");
      }
    });

    $('.switchlang').click(function(event) {
      event.preventdefault();
      $.ajax({
        type: "post",
        url: "ajax_actions.php?action=switchlang&lang=<?=$_SESSION['lang']=='en_us'?'ja_jp':'en_us'?>",
        success: function() {
          location.reload(true);
        }
      });
    });
<?php if (isset($_SESSION['announcements'])) { ?>
    $('#announcements').dialog({
      modal: true,
      buttons: [{
        text: "<?=_("ok, i got it!")?>",
        click: function() {
          $( this ).dialog( "close" );
        }
      }],
      width: 460
    });
<?php
  unset($_SESSION['announcements']); //now that it's shown, get rid of it
}
?>
  });
}
</script>
</body>
</html>
<?php
}

//deprecated
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav);
  echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"white\"><tr><td>";
}

//deprecated
function print_footer() {
  echo "</td></tr></table>";
  footer();
}

// function sqlquery_checked: shorten the repeated checks for sql errors
function sqlquery_checked($sql) {
  global $db;
  $result = mysqli_query($db, $sql);
  if ($result === false ){
     die("<pre style=\"font-size:15px;\"><strong>sql error ".mysql_errno($db)." in file ".$_SERVER['php_self'].": ".mysqli_error($db)."</strong><br>$sql</pre>");
  }
  return $result;
}

function today() {
return date("y-m-d",mktime(gmdate("h")+9));
}

// function showfile: for "including" a non-php file (html, js, etc.)
function showfile($filename) {
  if (!$file = fopen($filename,"r")) {
    echo "<br><font color=red>could not open file '$filename'!</font><br>";
  } else {
    fpassthru($file);
  }
}

// deprecated
function db2table($text) {
//  $text = ereg_replace("\r\n|\n|\r","<br>",$text);
//  $text = ereg_replace("<br> ","<br>&nbsp;",$text);
  return d2h($text);
}

function d2h($text) {
  return nl2br(htmlspecialchars($text, ent_quotes, mb_internal_encoding()));
}

function d2f($text) {
  return str_replace('"','\"',$text);
}

function d2j($text) {  //makes carriage returns safe for json
  return preg_replace("/\r\n|\n|\r/","\\n",$text);
}

// deprecated
function post2form($text) {
  return stripslashes($text);
}

function h2d($text) {
  global $db;
  return mysqli_real_escape_string($db, $text);
}

function escape_quotes($text) {
  $text = str_replace("\"","\\\"",$text);
  return $text;
}

// function readable_name: returns name and optionally id, adding "furigana" if the first character is not roman alphabet and breaking if desired
function readable_name($name,$furigana,$pid=0,$org=0,$break="",$reverse=0) {
  if ($pid && ($_SESSION['showid']=="yes" || $org)) {
    $text = ($reverse?$furigana:$name)." ["._("id").": ".$pid."]";
  } else {
    $text = ($reverse?$furigana:$name);
  }
  if (mb_strlen($name) != strlen($name)) {  //name has multi-byte characters in it
    $text .= $break." (".($reverse?$name:$furigana).")";
    if (strpos($break,"<span>")) $text .= "</span>";
    if (strpos($break,"<div>")) $text .= "</div>";
  }
  return $text;
}

// deprecated
function readable_name_2line($name,$furigana) {
  return readable_name($name,$furigana,0,0,"<br />");
}

// function age: takes birthdate in the form yyyy-mm-dd as argument, returns age
function age($birthdate) {
  if ($birthdate=='' || $birthdate=='0000-00-00') return '';
  $ba = explode("-",$birthdate);
  if ($ba[0]=='1900') return '?';
  $ta = explode("-",date("y-m-d",mktime(gmdate("h")+9)));
  $age = $ta[0] - $ba[0];
  if (($ba[1] > $ta[1]) || (($ba[1] == $ta[1]) && ($ba[2] > $ta[2]))) --$age;
  return $age;
}

// function code_display: escapes html tag codes so that html code can be displayed as is
function code_display($code) {
  $code = str_replace("\<","&lt;",$code);
  $code = str_replace("\>","&gt;",$code);
  return "<code>$code</code>";
}

function url2link($text) {
  return preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*((\?|#)[^<\.,;\s]+)?)?)?)@', '<a href="$1">$1</a>', $text);
}

function email2link($text) {
  return preg_replace('/\b([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4})\b/i', '<a href="mailto:$0">$0</a>', $text);
}

// STUFF THAT GETS RUN RIGHT AWAY

// Get client login credentials and connect to client database
$hostarray = explode(".",$_SERVER['HTTP_HOST']);
include("/var/www/kizunadb/client/".$hostarray[0]."/kizuna_connect.php");
$db = mysqli_connect("localhost", "kz_".$client, $pass, "kizuna_".$client) or die("Failed to connect user "."kz_".$client." (".$pass.").");

// for certain versions of SQL, this next is needed, but others would give an error,
// so no error checking is done
//mysqli_query($db, "set names 'utf8'");
//newer style - hopefully no errors at all
mysqli_set_charset($db, "utf8");

// Set internal character encoding to UTF-8
mb_internal_encoding("UTF-8");
mb_regex_encoding("UTF-8");

sqlquery_checked("SET SESSION group_concat_max_len = 4096");
