<?php
include("functions.php");
include("accesscontrol.php");

pageheader("Bad URL", 1);

echo "<h2>Bad URL - Error 404 occurred</h2>";
echo "<p>The URL that was requested was:</p><p>".urldecode($REQUEST_URI)."</p>";

footer();
?>
