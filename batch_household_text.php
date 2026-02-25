<?php
include("functions.php");
include("accesscontrol.php");
$ajax = !empty($_REQUEST['ajax']);

if (!$ajax) {
  header1(_("Household Info (Text)"));
  header2(0);
}
?>
        <div align=center>&nbsp;<br>&nbsp;<br>Sorry, this is still under construction...</div>
<?php if (!$ajax) footer(); ?>
