<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors',0);

function header1($title) {
  ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/x-icon" href="kizunadb.ico">
<title><?=(isset($_SESSION['dbtitle']) ? $_SESSION['dbtitle'].': ' : '').$title?></title>
  <?php
}

function header2($nav=0) {
  echo "</head>\n";
  $fileroot = substr($_SERVER['PHP_SELF'],(strrpos($_SERVER['PHP_SELF'],"/")+1),(strrpos($_SERVER['PHP_SELF'],".")-strrpos($_SERVER['PHP_SELF'],"/")-1));
  echo "<body class=\"".$fileroot.($nav?" full":" simple")."\">\n";

  if ($nav) {
    $navmarkup = "<ul class=\"nav\">\n";
    if ($_SESSION['hasdashboard']) {
      $navmarkup .= "  <li><a href=\"dashboard.php\" target=\"_top\">"._("Dashboard")."</a></li>\n";
    }
    $navmarkup .= "  <li><a href=\"search.php\" target=\"_top\">"._("Search")."</a></li>\n";
    $navmarkup .= "  <li><form action='list.php'><input name='textinput1' placeholder='"._('(quick search)')."' style='width:7em'></form></li>\n";
    $navmarkup .= "  <li><a href=\"edit.php\" target=\"_top\">"._("New Person/Org")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"multiselect.php\" target=\"_top\">"._("Multi-Select")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"action.php\" target=\"_top\">"._("Actions")."</a></li>\n";
    if (isset ($_SESSION['donations']) && $_SESSION['donations'] == "yes") {
      $navmarkup .= "  <li><a href=\"donations.php\" target=\"_top\">"._("Donations &amp; Pledges")."</a></li>\n";
    }
    $navmarkup .= "  <li><a href=\"event_attend.php\" target=\"_top\">"._("Event Attendance")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"birthday.php\" target=\"_top\">"._("Birthdays")."</a></li>\n";
    $navmarkup .= "  <li><a href=\"db_settings.php\" target=\"_top\">"._("DB Settings")."</a></li>\n";
    if (isset ($_SESSION['admin']) && $_SESSION['admin'] == 1) {
      $navmarkup .= "  <li><a href=\"sqlquery.php\" target=\"_top\">"._("(Freeform SQL)")."</a></li>\n";
    }
    $navmarkup .= "  <li><a class=\"switchlang\" href=\"#\">".
        ($_SESSION['lang']=='en_US'?'日本語':'English')."</a></li>\n";
    $navmarkup .= "  <li class=\"menu-usersettings\"><a href=\"user_settings.php\" target=\"_top\">"._("User Settings")."<span> (".$_SESSION['username'].")</span></a></li>\n";
    $navmarkup .= "  <li><a href=\"index.php?logout=1\" target=\"_top\">"._("Log Out")."</a></li>\n</ul>\n";
    echo "<nav id=\"scrollnav\"></nav>\n";  //only appears when scrolled

    echo "<div id=\"main-container\">\n";
    echo "<nav id=\"nav-main\">\n$navmarkup</nav>\n";  //main nav for large screens
    echo "<div id=\"nav-trigger\"><img src=\"graphics/kizunadb-logo.png\" alt=\"Logo\"><span>Menu</span></div>\n";  //button for narrow screens
    echo "<nav id=\"nav-mobile\"></nav>\n";  //vertical menu for narrow screens
  }
  echo "<div id=\"content\">\n";
}

// Function footer: sends final html
function footer($nav=0) {
  echo "  <div style=\"clear:both\"></div>\n";
  echo "</div>\n"; //end of content div
  echo "</div>\n"; //end of main-container div

  // ANNOUNCEMENTS ABOUT NEW FEATURES, ETC., IF ANY
  if (isset($_SESSION['announcements'])) {
    echo "<style>\n  div.announcement { border:solid 2px lightgray; padding:5px; margin:5px; }\n";
    echo "  h4.announcedate,p.announcetext,body.dashboard h4.announcedate,body.dashboard p.announcetext { text-align:left; }\n</style>\n";
    echo '<div id="announcements" title="'._("Recent Announcements")."\">\n";
    foreach($_SESSION['announcements'] as $announcement) {
      echo "  <div class=\"announcement\">\n";
      echo "    <h4 class=\"announcedate\">".substr($announcement->AnnounceTime,0,10)."</h4>\n";
      echo "    <p class=\"announcetext\">".$announcement->HTML."</p>\n";
      echo "  </div>\n";
    }
    echo "</div>\n";
  } //end if announcements
?>
    <script type="text/javascript">
        if (window.jQuery) { //really simple files that don't have jQuery don't need this stuff either
            $(function() {
                $(window).scroll(function() {
                    if ($(this).scrollTop() > 150 && !$('#scrollnav').hasClass('visible')) {
                        $('#scrollnav').addClass('visible');
                    } else if ($(this).scrollTop() <= 150 && $('#scrollnav').hasClass('visible')) {
                        $('#scrollnav').removeClass('visible');
                    }
                });

                $("#nav-mobile").html($("#nav-main").html());
                $("#scrollnav").html($("#nav-main").html());
                $("#nav-trigger").click(function(){
                    if ($("nav#nav-mobile ul").hasClass("expanded")) {
                        $("nav#nav-mobile ul.expanded").removeClass("expanded").slideUp(250);
                        $(this).removeClass("open");
                    } else {
                        $("nav#nav-mobile ul").addClass("expanded").slideDown(250);
                        $(this).addClass("open");
                    }
                });

                $('.switchlang').click(function(event) {
                    event.preventDefault();
                    $.ajax({
                        type: "POST",
                        url: "ajax_actions.php?action=SwitchLang&lang=<?=$_SESSION['lang']=='en_US'?'ja_JP':'en_US' ?>",
                        success: function() {
                            location.reload(true);
                        }
                    });
                });
              <?php if (isset($_SESSION['announcements'])) { ?>
                $('#announcements').dialog({
                    modal: true,
                    buttons: [{
                        text: "<?=_("OK, I got it!") ?>",
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

//DEPRECATED
function print_header($title,$color,$nav) {
  header1($title);
  header2($nav);
  echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" bgcolor=\"white\"><tr><td>";
}

//DEPRECATED
function print_footer() {
  echo "</td></tr></table>";
  footer();
}

// function sqlquery_checked: shorten the repeated checks for SQL errors
function sqlquery_checked($sql) {
  global $db;
  $result = mysqli_query($db, $sql);
  if ($result === false ){
     die("<pre style=\"font-size:15px;\"><strong>SQL Error in file ".$_SERVER['PHP_SELF'].": ".mysqli_error($db)."</strong><br>$sql</pre>");
  }
  return $result;
}

function today() {
  return date("Y-m-d",mktime(gmdate("H")+9));
}

// DEPRECATED
function db2table($text) {
  return d2h($text);
}

function d2h($text) {
  return nl2br(htmlspecialchars($text, ENT_QUOTES, mb_internal_encoding()));
}

function d2f($text) {
  return str_replace('"','\"',$text);
}

function d2j($text) {  //makes carriage returns safe for JSON
  return preg_replace("/\r\n|\n|\r/","\\n",$text);
}

function h2d($text) {
  global $db;
  return mysqli_real_escape_string($db, $text);
}

function escape_quotes($text) {
  $text = str_replace("\"","\\\"",$text);
  return $text;
}

// Function readable_name: returns name and optionally ID, adding "furigana" if the first character is not Roman alphabet and breaking if desired
function readable_name($name,$furigana,$pid=0,$org=0,$break="",$reverse=0) {
  if ($pid && ($_SESSION['showid']=="yes" || $org)) {
    $text = ($reverse?$furigana:$name)." ["._("ID").": ".$pid."]";
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

// Function age: takes birthdate in the form YYYY-MM-DD as argument, returns age
function age($birthdate) {
  if ($birthdate=='' || $birthdate=='0000-00-00') return '';
  $ba = explode("-",$birthdate);
  if ($ba[0]=='1900') return '?';
  $ta = explode("-",date("Y-m-d",mktime(gmdate("H")+9)));
  $age = $ta[0] - $ba[0];
  if (($ba[1] > $ta[1]) || (($ba[1] == $ta[1]) && ($ba[2] > $ta[2]))) --$age;
  return $age;
}

// Function code_display: escapes HTML tag codes so that HTML code can be displayed as is
function code_display($code) {
  $code = str_replace("\<","&lt;",$code);
  $code = str_replace("\>","&gt;",$code);
  return "<code>$code</code>";
}

function url2link($text) {
  // I have no idea how this works - I got it from https://gist.github.com/winzig/8894715 (2017/11/07)
  // removing the part that looks for URLs with no protocol (because that was too greedy).
  // I don't know why this matches on a multibyte domain name, but it does.
  return preg_replace('~\b((?:https?:(?:/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:[a-z]{2,13})/)'.
      '(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|'.
      '\([^\s]+?\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))~iu',
      '<a href="$1">$1</a>', $text);
}

function email2link($text) {
  return preg_replace('/\b([a-z0-9._%+-]+@[\w.-]+\.[a-z]{2,13})\b/iu', '<a href="mailto:$0">$0</a>', $text);
}

// STUFF THAT GETS RUN RIGHT AWAY

$hostarray = explode(".",$_SERVER['HTTP_HOST']);
define('CLIENT',$hostarray[0]);
define('CLIENT_PATH',"/var/www/kizunadb/client/".CLIENT);
// Get client login credentials and connect to client database
$configfile = CLIENT_PATH."/kizunadb.ini";
if (!is_readable($configfile)) die("No KizunaDB configuration file. Notify the developer.");
$config = parse_ini_file($configfile);
$db = mysqli_connect("localhost", "kizuna_".CLIENT, $config['password'], "kizuna_".CLIENT)
    or die("Failed to connect to database. Notify the developer.");

mysqli_set_charset($db, "utf8mb4");

// Set internal character encoding to UTF-8
//die('current internal_encoding is '.mb_internal_encoding().' and current regex_encoding is '.mb_regex_encoding());
//mb_internal_encoding("UTF-8");
//mb_regex_encoding("UTF-8");

//sqlquery_checked("SET SESSION group_concat_max_len = 4096");
