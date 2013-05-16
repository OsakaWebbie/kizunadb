<?
session_start();
if (!isset($_SESSION['userid'])) die;
$path = "/var/www/".$_SESSION['client']."/css/";
header("Content-type: text/css");
serve(is_file($path."reset.css") ? $path."reset.css" : "css/reset.css");
if (is_file($path."styles.php")) {
  serve($path."styles.php");
  exit;
} elseif (is_file($path."styles.css")) {
  serve($path."styles.css");
  exit;
} else {
  include(is_file($path."colors.php") ? $path."colors.php" : "css/colors.php");
  // INCLUDE ALL DEFINITIONS HERE (so that colors can be applied)
?>
/* theme layout and styling */

body.full {
  text-align:center;
  background-color: <?=($bodybg?$bodybg:"DarkGrey")?>;
}
body.simple {
  text-align:center;
  background-color: White;
}

body.full div#main-container {
  background:<?=($mainbg?$mainbg:"White")?> url('graphics/kizunadb-logo.png') no-repeat 3px 3px;
  text-align:left;
  width:auto;
  border: 1px solid <?=($mainborder?$mainborder:"Black")?>;
  margin: 10px;
}
body.simple div#main-container {
  text-align:left;
  background-color: White;
}

div#content {
  margin:0 10px 10px 10px;
  background-color: White;
}
table { background-color: White;}

ul.nav {
  background-color:<?=($navbg?$navbg:"#2C2C2C")?>;
  margin:10px 10px 0 58px;
  padding:6px 0px 8px 0px;
  -moz-border-radius: 15px;
  border-radius: 15px;
  text-align: center;
  vertical-align: middle;
  min-height: 40px;
  clear:both;
}  

#scrollnav {
  position: fixed;
  top: -50px;
  transition: top 0.2s ease-in-out 0s;
  width: 100%;
  z-index: 9999;
}
#scrollnav ul.nav {
  background-color: <?=($navbg?rgba($navbg,"0.7"):rgba("#2C2C2C","0.7"))?>;
  margin:0;
  padding:5px;
  -moz-border-radius: 0;
  border-radius: 0;
  min-height: 0;
}
#scrollnav.visible { top: 0; }

#content ul.nav {  /*nav bar in footer*/
  margin:10px 0 0 0;
}

ul.nav li {
  display:inline;
}
ul.nav a {
  padding: 3px 10px 3px 10px;
  margin: 0;
  font-family: arial, helvetica, sans-serif;
  color: <?=($navlink?$navlink:"LightSteelBlue")?>;
  font-weight: bold;
  white-space:nowrap;
}
ul.nav a:hover, ul#scrollnav a:hover {
color: White;
}

/* general purpose typography */

body { font-family:Arial,"ＭＳ Ｐゴシック",sans-serif; }
textarea { font-family:Arial,"ＭＳ Ｐゴシック",sans-serif; } /* because inherit doesn't work in IE <8 */

h1 {
  margin:6px 0 6px 0;
  text-align:center;
  font-size: 1.8em;
  line-height:1;
  font-weight:bold;
  color: <?=($h1?$h1:"Red")?>;
}
h2 {
  text-align:left;
  font-size: 1.5em;
  line-height:1.1;
  color: <?=($h2?$h2:"SteelBlue")?>;
  font-weight:bold;
}
h3 {
  text-align:left;
  font-size: 1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=($h3?$h3:"Black")?>;
  margin:10px 0 4px 0;
}
a:link,a:visited { color:<?=($link?$link:"#333399")?>; }
a:hover,a:active { color:<?=($linkhover?$linkhover:"DarkBlue")?>; }
a.more { cursor:pointer; color:<?=($linkmore?$linkmore:"Black")?>#333399; text-decoration:underline; }

.alert { color:<?=($alert?$alert:"Red")?>; }
.comment { font-size:0.8em; font-style:italic; }
.highlight { background-color:<?=($highlight?$highlight:"LightSteelBlue")?>; }
.validation { background-color:<?=($validation?$validation:"Red")?>; }

/*forms*/

form div { margin-top:0.1em; margin-bottom:0.1em; }
input.text {
  background-color: <?=($inputbg?$inputbg:"White")?>;
  border: <?=($inputborder?$inputborder:"DimGray")?> solid 1px;
}
fieldset,input,select,label,label textarea { vertical-align:top; }
.label-n-input { white-space:nowrap; margin-right:2em;}
td.button-in-table { text-align:center; }

div#actions { margin:8px 0; text-align:center; }
div#actions form { display:inline; margin:2px 15px; }

/* specialized classes and IDs */

div.section  {
  margin: 15px 0 15px 0;
  border: 2px solid <?=($sectionborder?$sectionborder:"DarkRed")?>;
  padding: 5px;
  background-color: White;
}
h3.section-title {
  margin:0 0 3px 5px;
  padding:2px 7px 2px 7px;
  border: 2px solid <?=($sectiontitleborder?$sectiontitleborder:"DarkRed")?>;
  text-align:left;
  display:inline;
  position:relative;
  top:-12px;
  font-size:1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=($sectiontitle?$sectiontitle:"White")?>;
  background-color: <?=($sectiontitlebg?$sectiontitlebg:"DarkRed")?>;
}
fieldset {
  margin: 15px 0 15px 0;
  border: 2px solid <?=($fieldsetborder?$fieldsetborder:"DarkRed")?>;
  padding: 5px;
  background-color: White;
}
fieldset legend {
  margin:0 0 3px 5px;
  padding:2px 7px 2px 7px;
  font-size:1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=($legend?$legend:"White")?>;
  background-color: <?=($legendbg?$legendbg:"DarkRed")?>;
}

h1#title {
  margin: 0 0 0 48px;
  padding:4px 0 10px 0;
  color: <?=($title?$title:"Red")?>;
  background-color:<?=($titlebg?$titlebg:"White")?>;
}

span.inlinelabel {
  font-weight: bold;
  color: <?=($inlinelabel?$inlinelabel:"DarkRed")?>;
}

option.active. li.active { background-color:<?=($activeeventbg?$activeeventbg:"White")?>; }
option.inactive, li.inactive { background-color:<?=($inactiveeventbg?$inactiveeventbg:"#BBBBBB")?>; }

/* AJAX related */

.delconfirm { background-color: <?=($delconfirm?$delconfirm:"#808080")?>; } 
.spinner { background: <?=($delconfirm?$delconfirm:"#808080")?> url('graphics/ajax_loader.gif'); } 

/* specific to search.php */
body.search div#content {
  margin-top:0px;
}
body.search ul.nav {
  margin-bottom:0px;
}
body.search div#opening h1.title { text-align:left; margin-left:280px; margin-top:0px; padding-top:5px;}
body.search div#opening h3 { text-align:left; margin-left:330px; color:white; }
body.search .advanced { display:none; }
body.search .comment { text-align:center; }
body.search div.criteria,body.search div.criteria select {
  vertical-align: middle;
  margin-bottom:3px;
}
body.search span.radiogroup, body.search span.inputgroup {
  display:inline-block;
  vertical-align: middle;
}
body.search h2 span.radiogroup { border:1px solid <?=($h2?$h2:"SteelBlue")?>; }
body.search h2 span.radiogroup label { display:inline-block; margin:3px 5px; }
body.search fieldset span.radiogroup label, body.search fieldset span.inputgroup label { display:block; }
body.search fieldset span.plus {
  font-size:2em;
  width:20px;
  display:inline-block;
}
body.search #showadvanced,body.search #search { display:block; }
body.search #buttonsection {
  border: 1px solid Black;
  padding: 4px 8px;
  background: white;
  position: fixed;
  top: 100px;
  right: 18px;
  text-align: center;
}
body.search #search {
  margin:10px auto 5px auto;
  padding:5px 40px 5px 40px;
  font-size: 1.5em;
  font-weight:bold;
}

/* specific to list.php */
body.list.full div#main-container { width:auto; }
body.list h3 { margin-bottom:0; }
body.list ul#criteria {
  margin-left:30px;
  padding-left:12px;
  list-style-type: disc;
}
body.list table { margin-right:auto; margin-left:auto; }
body.list td.categories { white-space:nowrap; }

/* specific to individual.php */
body.individual div#photo { float:left; width:150px; text-align:center; }
body.individual div#photo img { width:150px; border:1px solid <?=($photoborder?$photoborder:"Gray")?>;}
body.individual div#photo p { margin-top:60px; text-align:center; }
body.individual div#info-block { float:left; }
body.individual div#personal-info,div#household-info {
  border:2px solid <?=($personinfoborder?$personinfoborder:"Red")?>;
  float:left;
  margin-left: 8px;
  padding:5px;
}
body.individual h3.info-title {
  margin:0 0 3px 0;
  padding:0;
  font-size:1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=($personinfotitle?$personinfotitle:"Red")?>;
}
body.individual div#personal-info div,div#household-info div { margin-bottom:0.3em; }
body.individual div#nonjapan-address,div#address,div#romaji-address { padding-left:15px; margin:0em; }
body.individual div#romaji-address { font-style:italic; color:Gray; }
body.individual div.upddate { font-size:0.8em; color:LightGray; }
body.individual p#remarks { clear:both; margin-left: 8px; }
body.individual h2#links { clear:both; text-align:center; padding:20px 0; font-size:1.3em; }
body.individual h2#links a { margin:0 20px; }
body.individual div#cats-button { text-align:center; margin:-10px 0 10px 0; }
body.individual div#cats-in {
  margin:5px 0;
  padding:5px 0;
  border-top: 2px solid <?=($sectionborder?$sectionborder:"DarkRed")?>;
  border-bottom: 2px solid <?=($sectionborder?$sectionborder:"DarkRed")?>;
}
body.individual div#orgsection form.msform { margin-top:15px; }
body.individual form#orgform { margin:-5px auto 0 60px; padding:5px; border:1px solid LightGray; }
body.individual tr.leader { background-color:<?=($leaderbg?$leaderbg:"#FFF0C0")?>; }
body.individual div.section h3 { margin:5px 0 0 0; }
body.individual form#contactform { margin:-5px 0 5px 60px; }
body.individual form#contactform table td { padding:0 12px 8px 0; }
body.individual form#contactform textarea#contactdesc { height:4em; width:400px; }
body.individual form#attendform { margin:3px 0 10px 0; }
body.individual td.categories, body.individual td.events { white-space:nowrap; }
body.individual #dayofweek, body.individual #attend-apply { display:block; }
body.individual #dayofweek label, body.individual #attend-apply label { margin-right:0.5em; }

/* specific to edit.php */
body.edit input { margin-top:5px;}
body.edit div#name_section,body.edit div#furigana_section,body.edit div#title_section {
  float:left;
  vertical-align:top;
  margin:0 15px 10px 0;
}
body.edit div#household_section {
  clear:both;
  border: 1px solid <?=($sectionborder?$sectionborder:"DarkRed")?>;
  padding: 5px;
  margin: 10px 0 5px 0;
}
body.edit div#household_setup { text-align:center; }
body.edit div#household_setup button { margin:0 5px; }
body.edit label#addresslabel,body.edit label#labelnamelabel,body.edit label#romajiaddresslabel { display:block; }

body.edit div#address_display { float:left; margin:10px;}
body.edit div#address_input { float:left; margin:10px;}
body.edit div#jp_address_display,body.edit div#rom_address_display {
  width: 400px;
  margin: 10px 2px;
  padding: 10px;
  vertical-align: top;
}
body.edit div#jp_address_display { border: 1px dashed Gray; font-weight:bold; }
body.edit div#rom_address_display { font-style:italic; }
body.edit span#labelname_display { font-size:1.5em; }
body.edit span#romajiaddress_section { white-space:nowrap; line-height:1.0em; padding-top:0.3em; }

body.edit div#householdfinal_section { clear:both; }

body.edit div#photo_section {
  border: 2px solid Gray;
  background-color: White;
  text-align: center;
  float: right;
}
body.edit #submit_button,body.edit #remarks_label { display:inline-block; vertical-align:top; margin-top:5px; }
body.edit #remarks { width:420px; height:6em; vertical-align:top; margin-bottom:5px; }
body.edit #submit_button {
  margin:20px;
  padding:5px 15px;
  font-size: 1.3em;
  font-weight:bold;
} 
body.edit #duplicates { text-align:left; }
body.edit #duplicates .dup { border:2px solid LightGray; padding:5px;margin:3px 0; }
body.edit #duplicates .name { font-size:1.2em; font-weight:bold; }
body.edit #duplicates .button_section { white-space:nowrap; }
body.edit #duplicates .button_section form { display:inline; }

/* specific to ms_person_xml.php */
body.ms_person_xml span.radiogroup { display:inline-block; }
body.ms_person_xml span.radiogroup span.label-n-input { display:block; }

/* specific to contact.php */
body.contact #listtypes { float:right; }
body.contact #listtypes label { display:block; }
body.contact div.section { float:left; }  /* needed to force the div to fully surround the inner float */
body.contact div.section:after { clear:both; }  /* needed because we had to float the section div */

/* specific to donations.php */
body.donations #typefilter { display:inline-block; vertical-align:middle; border:none; margin:0 20px 5px 0; padding:0; }
body.donations #typefilter label, body.donations #dtselect { display:block; }
body.donations #datefilter { display:inline-block; vertical-align:middle; border:none; margin:0 0 5px 0; padding:0; }
body.donations .actions { border:1px solid <?=($innerborder?$innerborder:"SteelBlue")?>; margin:6px 20px 0 20px; padding:8px; }
body.donations #show_list, body.donations #show_summary { display:inline-block; vertical-align:middle; margin-right:20px; }
body.donations .actiontypes { display:inline-block; vertical-align:middle; margin-right:20px; }
body.donations .proctype, body.donations .actiontype { display:block; }

/* specific to donation_list.php */
body.donation_list.full div#main-container { width:auto; }
body.donation_list ul#criteria {
  margin-left:30px;
  padding-left:12px;
  list-style-type: disc;
}
body.donation_list div#procbuttons { text-align:right; }
body.donation_list div#procbuttons button { margin-left:10px; }
body.donation_list table.sttable td { border:1px solid <?=($innerborder?$innerborder:"SteelBlue")?>; }
body.donation_list table.sttable td { padding:1px 3px 1px 3px; vertical-align:middle; }
body.donation_list td.dtype, td.amount { white-space:nowrap; }
body.donation_list td.amount { text-align:right; }
body.donation_list td.subtotal {
  background-color:#FFFFE0;
  white-space:nowrap;
  font-weight:bold;
}
 
/* specific to attendance charts (also used in dashboards, so "body" omitted to allow div also) */
body.attend_detail.full div#main-container { width:auto; }
body.attend_detail .weekdaydate, body.attend_datesums .weekdaydate {
  white-space:nowrap;
  background-color: <?=($weekdaybg?$weekdaybg:"#FFFFD0")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail .saturdaydate, body.attend_datesums .saturdaydate {
  white-space:nowrap;
  background-color: <?=($saturdaybg?$saturdaybg:"#C0C0E0")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail .sundaydate, body.attend_datesums .sundaydate {
  white-space:nowrap;
  background-color: <?=($sundaybg?$sundaybg:"#FF8080")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail td.photocell, body.attend_detail .photohead {
  text-align:center;
  background-color: <?=($photocellbg?$photocellbg:"#FFFFD0")?>;
}
body.attend_detail td.namecell, body.attend_detail .namehead { white-space:nowrap; background-color: <?=($namecellbg?$namecellbg:"#D0D0F0")?>; }
body.attend_detail .namehead { font-weight:bold; }
body.attend_detail td.attendcell { white-space:nowrap; background: <?=($attendcellbg?$attendcellbg:"#40A060")?> none; text-align:center; }
body.attend_detail td.attendtimecell { white-space:nowrap; background: <?=($attendtimebg?$attendtimebg:"#70E090")?> none; text-align:center; }
body.attend_detail td.ui-selected { background: #808080 url('graphics/delete_icon.png'); }
body.attend_datesums td.datecell { white-space:nowrap; background-color: <?=($datecellbg?$datecellbg:"#FFFFD0")?>; font-size:0.8em; text-align:center; }
body.attend_datesums td.eventcell, body.attend_datesums td.eventhead { white-space:nowrap; background-color: <?=($eventcellbg?$eventcellbg:"#D0D0F0")?>; }
body.attend_datesums td.eventhead { font-weight:bold; }
body.attend_datesums td.sumcell { text-align:center }
body.attend_datesums td.sumcell a { font-weight:bold; font-size:1.2em; }
body.attend_datesums td.zerocell { text-align:center; }

/* specific to maintenance.php */
body.maintenance form span.input {
  white-space:nowrap;
  margin: 3px 10px 3px 0;
  vertical-align: top;
}
body.maintenance form label { vertical-align:top; }
body.maintenance form textarea { vertical-align:top; }
body.maintenance form select { margin: 0px 10px 6px 0; }
body.maintenance p { margin-bottom: 8px; }
body.maintenance input#userid { display:block; }
body.maintenance form#pcform span.romaji { display:block; }
body.maintenance form#loginform span.new_userid { display:block; }
body.maintenance form#loginform span.new_pw1 { display:block; }
body.maintenance form#loginform span.new_pw2 { display:block; }
body.maintenance form#ctform span.ctcolor_button { display:block; }
body.maintenance form#dtform span.dtcolor_button { display:block; }

/* specific to sqlquery.php */
body.sqlquery h2 {
  text-align:center;
  margin-bottom:10px;
}
body.sqlquery input#submit {
  margin:10px auto 5px auto;
  padding:5px 40px 5px 40px;
  font-size: 1.5em;
  font-weight:bold;
}
body.sqlquery form {
  margin:10px auto 5px auto;
}
body.sqlquery #mainTable tbody td { vertical-align:top; }

/* specific to dashboard.php */
body.dashboard .dashboard-item {
  float: left;
  border: 2px solid <?=($sectionborder?$sectionborder:"DarkRed")?>;
  margin: 10px;
  padding: 10px;
}
body.dashboard .item-icon {
  float: left;
  border: none;
  margin: 5px;
}
body.dashboard p, body.dashboard h2, body.dashboard h3, body.dashboard h4  {
  text-align: center;
  /*white-space: nowrap;*/
}
body.dashboard table th, body.dashboard table td {
  border: 1px solid gray;
  padding: 5px;
  margin: 5px;
  white-space: nowrap;
}
body.dashboard table th {
  background-color: #FFFFC0;
  text-align: center;
}
body.dashboard table td.number {
  text-align: center;
  font-weight: bold;
}
body.dashboard #attend_datesums h3 { display:none; }

@media print {
  body.full {
    background:none;
  }
  body.full div#main-container {
    background:none;
    border:none;
  }
  ul.nav { display:none; }
}

<?
} // end IF USING THIS FILE

if (isset($_GET['jquery'])) {
  serve(is_file($path."jquery-ui.css") ? $path."jquery-ui.css" : "css/jquery-ui.css");
  serve(is_file($path."jquery-ui-timepicker.css") ? $path."jquery-ui-timepicker.css" : "css/jquery-ui-timepicker.css");
}
if (isset($_GET['table'])) {
  serve(is_file($path."tablesorter.css") ? $path."tablesorter.css" : "css/tablesorter.css");
  serve(is_file($path."clickmenu4colman.css") ? $path."clickmenu4colman.css" : "css/clickmenu4colman.css");
}
if (isset($_GET['multiselect'])) {
  serve(is_file($path."jquery.multiselect.css") ? $path."jquery.multiselect.css" : "css/jquery.multiselect.css");
  serve(is_file($path."jquery.multiselect.filter.css") ? $path."jquery.multiselect.filter.css" : "css/jquery.multiselect.filter.css");
}
if (is_file($path."custom.css")) serve($path."custom.css");  // if client wants more customization than colors

function serve($source) {
  $stuff = file_get_contents($source);
  echo preg_replace('#url\( *["\']?([^"\'\)]*)["\']? *\)#', 'url("clientfile.php?f=css/$1")', $stuff);
}
function rgba($color,$alpha) {
  if (strtolower(substr($color,0,4)) == "rgba") return preg_replace("/,[0-9\.]+\)$/",",".$alpha.")",$color);
  elseif (strtolower(substr($color,0,3)) == "rgb") return str_replace("rgb","rgba",preg_replace("/(,[0-9\.]+)\)$/","$1,".$alpha.")",$color));
  else {
    if ($color[0] == '#') $color = substr($color,1);
    if (strlen($color) == 6) list($r,$g,$b) = array($color[0].$color[1],$color[2].$color[3],$color[4].$color[5]);
    elseif (strlen($color) == 3) list($r,$g,$b) = array($color[0].$color[0],$color[1].$color[1],$color[2].$color[2]);
    else return false;
    return "rgba(".hexdec($r).",".hexdec($g).",".hexdec($b).",".$alpha.")";
  }
}
