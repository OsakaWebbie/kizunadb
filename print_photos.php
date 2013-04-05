<?php
include("functions.php");
include("accesscontrol.php");
print_header("Photo Printing","#FFFFE0",0);

$sql = "SELECT * FROM photoprint WHERE PhotoPrintName='$photo_print_name'";
if (!$result = mysql_query($sql)) {
  echo("<b>SQL Error ".mysql_errno().": ".mysql_error()."</b><br>($sql)<br>");
  exit;
}
$row = mysql_fetch_object($result);
?>

<!-- MeadCo ScriptX Control -->
<object id="factory" style="display:none" viewastext
classid="clsid:1663ed61-23eb-11d2-b92f-008048fdd814"
codebase="smsx.cab#Version=6,2,433,14">
</object>

<script defer>
function window.onload() {
  factory.printing.header = "";
  factory.printing.footer = "";
  factory.printing.portrait = <? echo (($row->PaperHeight>$row->PaperWidth)?"true":"false"); ?>;
  factory.printing.topMargin = <? echo $row->PaperTopMargin; ?>;
  factory.printing.bottomMargin = <? echo $row->PaperBottomMargin; ?>;
  factory.printing.leftMargin = <? echo $row->PaperLeftMargin; ?>;
  factory.printing.rightMargin = <? echo $row->PaperRightMargin; ?>;

  // enable print button
  if (factory.printing.IsTemplateSupported()) {
   document.controlform.print.disabled = false;
  }
}
</script>

<body><div align="center">
  <h2><font color="#8b0000">Ready to Print</font></h2>
</div>
<p>If the content in the frame below looks good, click the Print button (not the menu item), and when the
 print dialog comes up, go to the settings and change the paper size to <font 
 color="red"><b>&quot;<? echo $row->PaperSizeName."&quot; (".$row->PaperHeight."mm x ".$row->PaperWidth."mm)";
 ?></b></font>, and don't change anything else. &nbsp;
 Close the window when you are done printing. <font color=blue><i>(If the print button doesn't become available after a few seconds, your computer may not have the proper plugin installed.  If there is a message about an ActiveX plugin at the top of your IE window, you can click on it to install the Meadco ScriptX plugin, or ask your system administrator.  Unfortunately, this feature only works with Internet Explorer.)</i></font></p>
<div align="center">
<form name="controlform">
<input disabled type="button" width=150 name="print" value="   Print   " onclick="factory.printing.Print(true, ContentFrame)">
<input type=button width=150 name="close" value="Close Window" onclick="window.close();">
</div>
<iframe name="ContentFrame" width="100%" height="400" src="printphotos_iframe.php?pid_list=<? echo $pid_list.
"&data_type=".$data_type."&show_blanks=".$show_blanks."&photo_print_name=".urlencode($photo_print_name); ?>">
</iframe>
<? print_footer(); ?>