<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  pageheader(_("Household Info (Text)"), 0);
}
?>
        <div style="text-align:center">&nbsp;<br>&nbsp;<br>Sorry, this is still under construction...</div>
<?php if (!$ajax) footer(); ?>
