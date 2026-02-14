<?php
session_start();
$hostarray = explode(".",$_SERVER['HTTP_HOST']);
$path = "/var/www/kizunadb/client/".$hostarray[0]."/css/";

header("Content-type: text/css");
serve("css/reset.css");
if (is_file($path."styles.php")) {
  serve($path."styles.php");
  exit;
} elseif (is_file($path."styles.css")) {
  serve($path."styles.css");
  exit;
} else {
  if (is_file($path."colors.php")) {
    include($path."colors.php");
  } else {
    include("css/colors.php");  // default colors
  }
  // INCLUDE ALL DEFINITIONS HERE (so that colors can be applied)
?>
/* theme layout and styling */

body.full {
  text-align:center;
  background-color: <?=(!empty($bodybg)?$bodybg:"DarkGrey")?>;
}
body.simple {
  text-align:center;
  background-color: White;
}

body.full div#main-container {
  background:<?=(!empty($mainbg)?$mainbg:"White")?> url('graphics/kizunadb-logo.png') no-repeat 3px 3px;
  text-align:left;
  width:auto;
  border: 1px solid <?=(!empty($mainborder)?$mainborder:"Black")?>;
  margin: 10px;
}
body.simple div#main-container, body.simple div#content {
  text-align:left;
  background-color: White;
}

div#content {
  margin:0 10px 10px 10px;
  background-color: White;
  z-index: 1;
}

/* MAIN MENU (WIDE SCREENS) */
ul.nav {
  background-color:<?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
  list-style-type: none;
  margin:10px 10px 0 58px;
  padding:7px 0 7px 0;
  border-radius: 15px;
  text-align: center;
  vertical-align: middle;
  min-height: 30px;
}
ul.nav li, div.hassub {
  display: inline-block;
  position: relative;
  color: <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
}
ul.nav-sub { /* second level menus */
  display: none;
  position: absolute;
  z-index:100;
  background-color:<?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
  margin: -2px 0 0 15px;
  border: 1px solid <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  padding: 0;
  border-radius: 0;
  text-align: left;
  min-height: 0;
}
ul.nav-sub li {
  display: block;
}
ul.nav a, div.hassub a {
  display: block;
  color: <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  padding: 10px 15px;
  margin: 0;
  font-family: arial, helvetica, sans-serif;
  font-weight: bold;
  text-decoration: none;
  white-space:nowrap;
}
ul.nav span.username { font-weight:normal; white-space:wrap; }
ul.nav a:hover, div.hassub a:hover {
  background-color: <?=(!empty($navbghover)?$navbghover:"rgb(132,78,12)")?>;
  color: <?=(!empty($navlinkhover)?$navlinkhover:"White")?>;
}

ul.nav a.disabledlink, div.hassub a.disabledlink {
  opacity: 0.5;
  pointer-events: none;
}
ul.nav a.disabledlink:hover, div.hassub a.disabledlink:hover {
  background-color:<?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
  color: <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
}

/* MENU THAT APPEARS WHEN SCROLLING (WIDE SCREENS) */
#scrollnav {
  position: fixed;
  top: -100px;
  transition: top 0.5s ease-in-out 0s;
  width: 100%;
  z-index: 9999;
}
#scrollnav ul.nav {
  background-color: <?=(!empty($navbg)?rgba($navbg,"0.7"):rgba("#2C2C2C","0.7"))?>;
  margin:0;
  padding:5px;
  -moz-border-radius: 0;
  border-radius: 0;
  min-height: 0;
}
#scrollnav ul a {
  padding: 3px 10px 3px 10px;
}
#scrollnav.visible {
  top: 0;
}

/* TRIGGER (BUTTON) FOR MOBILE MENU */
#nav-trigger {
  display: none;
  text-align: center;
  background-color:<?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
}
#nav-trigger img {
  float:left;
  width:24px;
  padding:3px;
  background-color:White;
  border-radius:7px;
  margin:3px;
}
#nav-trigger span {
  display: inline-block;
  padding: 10px 30px;
  color: <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  cursor: pointer;
  font-family: arial, helvetica, sans-serif;
  font-size: 120%;
  font-weight: bold;
}
#nav-trigger span:after {
  display: inline-block;
  box-sizing: border-box;
  margin-left: 10px;
  width: 20px;
  height: 10px;
  content: "";
  border-left: solid 10px transparent;
  border-top: solid 10px <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  border-right: solid 10px transparent;
}
#nav-trigger.open { background-color: <?=(!empty($navbghover)?$navbghover:"rgb(132,78,12)")?>; }
#nav-trigger.open span { color:<?=(!empty($navlinkhover)?$navlinkhover:"White")?>; }
#nav-trigger.open span:after {
  border-left: solid 10px transparent;
  border-top: none;
  border-bottom: solid 10px <?=(!empty($navlinkhover)?$navlinkhover:"White")?>;
  border-right: solid 10px transparent;
}

/* MOBILE MENU */
#nav-mobile {
  position: relative;
  display: none;
  margin-left:35px;
  background-color: <?=(!empty($navbg)?$navbg:"rgb(88,57,7)")?>;
}
#nav-mobile ul.nav {
  display: none;
  list-style-type: none;
  position: absolute;
  z-index: 9998;
  border-radius: 0;
  left: 0;
  right: 0;
  margin: 0 auto;
  padding: 0;
  text-align: left;
}
#nav-mobile ul.nav li {
  display: block;
  padding: 5px 0 5px 10px;
  margin: 0 5px;
  border-bottom: solid 1px <?=(!empty($primarymedium)?$primarymedium:"SteelBlue")?>;
}
#nav-mobile ul.nav-sub { /* second level menus */
  display: block;
  position: static;
  padding: 0 0 0 20px;
  min-height: 0;
  border: none;
}
#nav-mobile ul.nav-sub li {
  display: block;
}
nav#nav-mobile ul.nav li:last-child { border-bottom: none; }
nav#nav-mobile a {
  display: block;
  color: <?=(!empty($navlink)?$navlink:"LightSteelBlue")?>;
  padding: 8px 0;
  font-family: arial, helvetica, sans-serif;
  font-size: 120%;
  font-weight: bold;
}
nav#nav-mobile li.menu-usersettings a span { font-weight:normal; white-space:wrap; }
nav#nav-mobile a:hover, nav#nav-mobile a:active {
  background-color: <?=(!empty($navbghover)?$navbghover:"#583907")?>;
  color: <?=(!empty($navlinkhover)?$navlinkhover:"White")?>;
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
  color: <?=(!empty($h1)?$h1:"#CC9944")?>;
}
h2 {
  text-align:left;
  font-size: 1.5em;
  line-height:1.1;
  color: <?=(!empty($h2)?$h2:"SteelBlue")?>;
  font-weight:bold;
}
h3 {
  text-align:left;
  font-size: 1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=(!empty($h3)?$h3:"Black")?>;
  margin:10px 0 4px 0;
}
a:link,a:visited { color:<?=(!empty($link)?$link:"#333399")?>; }
a:hover,a:active { color:<?=(!empty($linkhover)?$linkhover:"DarkBlue")?>; }
a.more { cursor:pointer; color:<?=(!empty($linkmore)?$linkmore:"Black")?>; text-decoration:underline; }

.dropdown-closed::after { content: ' ▼'; }
.dropdown-open::after { content: ' ▲'; }

.alert { color:<?=(!empty($alert)?$alert:"Red")?>; }
.comment { font-size:0.8em; font-style:italic; }
.highlight { background-color:<?=(!empty($highlight)?$highlight:"LightSteelBlue")?>; }
.validation { background-color:<?=(!empty($validation)?$validation:"Red")?>; }

/* Readmore fadeout effect */
td.readmore-wrapper, div.readmore {
  position: relative;
}
td.readmore-wrapper div.readmore {
  display: block;
  width: 100%;
}
div.readmore[data-readmore] {
  overflow: hidden;
}
div.readmore.readmore-collapsed::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 40px;
  background: linear-gradient(to bottom, transparent, white);
  pointer-events: none;
}

/*forms*/

form div { margin-top:0.1em; margin-bottom:0.1em; }
input.text {
  background-color: <?=(!empty($inputbg)?$inputbg:"White")?>;
  border: <?=(!empty($inputborder)?$inputborder:"DimGray")?> solid 1px;
}
fieldset,input,select,label,label textarea { vertical-align:top; }
.label-n-input { white-space:nowrap; margin-right:2em;}
td.button-in-table { text-align:center; }

/* div#actions { margin:8px 0; text-align:center; } *** I don't know why this was here *** */
/* div#actions form { display:inline; margin:2px 15px; } *** I don't know why this was here *** */

/* TABLES (including tablesorter) */

table { background-color: White; text-align: left; }
table tbody td {
  padding: 4px;
  vertical-align: top;
  border: 1px solid <?=(!empty($primarylight)?$primarylight:"LightSteelBlue")?>;
}

table.tablesorter thead tr th.tablesorter-header,
table.tablesorter tfoot tr th.tablesorter-footer {
  background-color: <?=(!empty($primarylight)?$primarylight:"LightSteelBlue")?>;
  border: 1px solid White;
  background-repeat: no-repeat;
  background-position: center right;
  padding: 4px 20px 4px 4px;
}
table.tablesorter thead tr th.tablesorter-headerUnSorted {
  background-image: url(css/images/tablesorter_bg.gif);
  cursor: pointer;
}
table.tablesorter thead tr th.tablesorter-headerAsc {
  background-image: url(css/images/tablesorter_asc.gif);
}
table.tablesorter thead tr th.tablesorter-headerDesc {
  background-image: url(css/images/tablesorter_desc.gif);
}
table.tablesorter thead tr th.tablesorter-headerAsc,
table.tablesorter thead tr th.tablesorter-headerDesc {
  background-color: <?=(!empty($secondarydark)?$secondarydark:"rgb(132,78,12)")?>;
  color: White;
}

/* specialized classes and IDs */

div.section  {
  margin: 15px 0 15px 0;
  border: 2px solid <?=(!empty($sectionborder)?$sectionborder:"DarkRed")?>;
  padding: 5px;
  background-color: White;
}
h3.section-title {
  margin:0 0 3px 5px;
  padding:2px 7px 2px 7px;
  border: 2px solid <?=(!empty($sectiontitleborder)?$sectiontitleborder:"DarkRed")?>;
  text-align:left;
  display:inline;
  position:relative;
  top:-12px;
  font-size:1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=(!empty($sectiontitle)?$sectiontitle:"White")?>;
  background-color: <?=(!empty($sectiontitlebg)?$sectiontitlebg:"DarkRed")?>;
}
fieldset {
  margin: 15px 0 15px 0;
  border: 2px solid <?=(!empty($fieldsetborder)?$fieldsetborder:"DarkRed")?>;
  padding: 5px;
  background-color: White;
}
fieldset legend {
  margin:0 0 3px 5px;
  padding:2px 7px 2px 7px;
  font-size:1.2em;
  font-weight:bold;
  font-style:italic;
  color: <?=(!empty($legend)?$legend:"White")?>;
  background-color: <?=(!empty($legendbg)?$legendbg:"DarkRed")?>;
}

h1#title {
  margin: 0 0 0 48px;
  padding:4px 0 10px 0;
  color: <?=(!empty($title)?$title:"Red")?>;
  background-color:<?=(!empty($titlebg)?$titlebg:"White")?>;
}

span.inlinelabel {
  font-weight: bold;
  color: <?=(!empty($inlinelabel)?$inlinelabel:"DarkRed")?>;
}

option.active. li.active { background-color:<?=(!empty($activeeventbg)?$activeeventbg:"White")?>; }
option.inactive, li.inactive { background-color:<?=(!empty($inactiveeventbg)?$inactiveeventbg:"#BBBBBB")?>; }

/* MOBILE MEDIA QUERIES */

@media screen and (max-width: 900px) {
  body.full div#main-container {
    border: none;
    margin: 0;
  }
  body.full div#main-container { background-image:none; }
  #nav-trigger { display: block; }
  nav#nav-main { display: none; }
  nav#nav-mobile { display: block; }
  #scrollnav { display: none; }
  ul.nav li.menu-user a { white-space:wrap; }
  h1#title { margin:0; }
}
@media screen and (orientation:landscape) {
  #nav-trigger span, nav#nav-mobile a { font-size: 100%; }
  #nav-trigger img { width:20px; }
}

/* AJAX related */

.delconfirm { background-color: <?=(!empty($delconfirm)?$delconfirm:"#808080")?>; }
.spinner { background: <?=(!empty($delconfirm)?$delconfirm:"#808080")?> url('graphics/ajax_loader.gif'); }

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
body.search h2 span.radiogroup {
  border:1px solid <?=(!empty($h2)?$h2:"SteelBlue")?>;
  font-size: 0.8em;
}
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
  padding: 10px 8px;
  background: white;
  position: fixed;
  top: 100px;
  right: 18px;
  text-align: center;
}
body.search #buttonsection label.label-n-input { margin-right:0; }
body.search #search {
  margin:0 auto;
  padding:5px 40px 5px 40px;
  font-size: 1.5em;
  font-weight:bold;
}
@media screen and (max-width: 900px) {
  body.search fieldset {
    margin: 8px 0;
  }
  body.search div.criteria,body.search div.criteria select { line-height: 3em; }
  body.search div.criteria span.radiogroup label { line-height: 1.5em; }
  body.search #showadvanced {
    display:inline-block;
    margin-bottom: 10px;
  }
  body.search #buttonsection {
    display:inline-block;
    position: static;
    margin-bottom: 10px;
  }
  body.search #search {
    display:inline-block;
  }
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
body.individual div#photo img { width:150px; border:1px solid <?=(!empty($photoborder)?$photoborder:"Gray")?>;}
body.individual div#photo p { margin-top:60px; text-align:center; }
body.individual div#info-block { float:left; }
body.individual div#personal-info,div#household-info {
  border:2px solid <?=(!empty($personinfoborder)?$personinfoborder:"Red")?>;
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
  color: <?=(!empty($personinfotitle)?$personinfotitle:"Red")?>;
}
body.individual div#cats-in {
  margin:5px 0;
  padding:5px 0;
  border-top: 2px solid <?=(!empty($sectionborder)?$sectionborder:"DarkRed")?>;
  border-bottom: 2px solid <?=(!empty($sectionborder)?$sectionborder:"DarkRed")?>;
}
body.individual div#orgsection form.msform { margin-top:15px; }
body.individual tr.leader { background-color:<?=(!empty($leaderbg)?$leaderbg:"#FFF0C0")?>; }
body.individual div.section h3 { margin:5px 0 0 0; }
body.individual form#orgform,
body.individual form#actionform,
body.individual form#donationform,
body.individual form#pledgeform,
body.individual form#attendform { margin:0 0 5px 30px; padding:5px; border:1px solid LightGray; }
body.individual form#actionform textarea { height:2em; }
body.individual td.categories, body.individual td.events { white-space:nowrap; }
body.individual #dayofweek, body.individual #attend-apply { display:block; }
body.individual #dayofweek label, body.individual #attend-apply label { margin-right:0.5em; }

/* specific to edit.php */
body.edit #editform input { margin-bottom:5px;}
body.edit div#name_section,body.edit div#furigana_section,body.edit div#title_section {
  float:left;
  vertical-align:top;
  margin:0 15px 10px 0;
}
body.edit div#household_section {
  clear:both;
  border: 1px solid <?=(!empty($sectionborder)?$sectionborder:"DarkRed")?>;
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

/* specific to action.php */
body.action #listtypes { float:right; }
body.action #listtypes label { display:block; }
body.action div.section { float:left; }  /* needed to force the div to fully surround the inner float */
body.action div.section:after { clear:both; }  /* needed because we had to float the section div */

/* specific to donations.php */
body.donations #typefilter { display:inline-block; vertical-align:middle; border:none; margin:0 20px 5px 0; padding:0; }
body.donations #typefilter label, body.donations #dtselect { display:block; }
body.donations #datefilter { display:inline-block; vertical-align:middle; border:none; margin:0 0 5px 0; padding:0; }
body.donations .actions { border:1px solid <?=(!empty($innerborder)?$innerborder:"SteelBlue")?>; margin:6px 20px 0 20px; padding:8px; }
body.donations #show_list, body.donations #show_summary { display:inline-block; vertical-align:middle; margin-right:20px; }
body.donations .actiontypes { display:inline-block; vertical-align:middle; margin-right:20px; }
body.donations .proctype, body.donations .actiontype { display:block; }

/* specific to donation_list.php */
/*body.donation_list.full div#main-container, body.donation_summary.full div#main-container {
  width:auto;
}*/
body.donation_list ul#criteria, body.donation_summary ul#criteria, body.pledge_list ul#criteria {
  margin-left:30px;
  padding-left:12px;
  list-style-type: disc;
}
body.donation_list div#procbuttons { text-align:right; }
body.donation_list div#procbuttons button { margin-left:10px; }
/*body.donation_list table.sttable td { border:1px solid <?=(!empty($innerborder)?$innerborder:"SteelBlue")?>; }*/
/*body.donation_list table.sttable td { padding:1px 3px 1px 3px; vertical-align:middle; }*/
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
  background-color: <?=(!empty($weekdaybg)?$weekdaybg:"#FFFFD0")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail .saturdaydate, body.attend_datesums .saturdaydate {
  white-space:nowrap;
  background-color: <?=(!empty($saturdaybg)?$saturdaybg:"#C0C0E0")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail .sundaydate, body.attend_datesums .sundaydate {
  white-space:nowrap;
  background-color: <?=(!empty($sundaybg)?$sundaybg:"#FF8080")?>;
  font-size:0.8em;
  text-align:center;
}
body.attend_detail td.photocell, body.attend_detail .photohead {
  text-align:center;
  background-color: <?=(!empty($photocellbg)?$photocellbg:"#FFFFD0")?>;
}
body.attend_detail td.namecell, body.attend_detail .namehead { white-space:nowrap; background-color: <?=(!empty($namecellbg)?$namecellbg:"#D0D0F0")?>; }
body.attend_detail .namehead { font-weight:bold; }
body.attend_detail td.attendcell { white-space:nowrap; background: <?=(!empty($attendcellbg)?$attendcellbg:"#40A060")?> none; text-align:center; }
body.attend_detail td.attendtimecell { white-space:nowrap; background: <?=(!empty($attendtimebg)?$attendtimebg:"#70E090")?> none; text-align:center; }
body.attend_detail td.ui-selected { background: #808080 url('graphics/delete_icon.png'); }
body.attend_datesums td.datecell { white-space:nowrap; background-color: <?=(!empty($datecellbg)?$datecellbg:"#FFFFD0")?>; font-size:0.8em; text-align:center; }
body.attend_datesums td.eventcell, body.attend_datesums td.eventhead { white-space:nowrap; background-color: <?=(!empty($eventcellbg)?$eventcellbg:"#D0D0F0")?>; }
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
body.maintenance form#userform span.new_userid { display:block; }
body.maintenance form#userform span.new_pw1 { display:block; }
body.maintenance form#userform span.new_pw2 { display:block; }
body.maintenance form#atform span.ctcolor_button { display:block; }
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
  border: 2px solid <?=(!empty($sectionborder)?$sectionborder:"DarkRed")?>;
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

  /*** jQuery UI CSS exceptions ***/
  .ui-dialog .ui-dialog-content {
  overflow:visible;
  text-align:left;
  }

<?php
} // end IF USING THIS FILE

if (isset($_GET['jquery'])) {
  serve(is_file($path."jquery-ui-13.css") ? $path."jquery-ui-13.css" : "css/jquery-ui-13.min.css");
}
if (isset($_GET['table'])) {
  serve(is_file($path."tablesorter.css") ? $path."tablesorter.css" : "css/tablesorter.css");
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