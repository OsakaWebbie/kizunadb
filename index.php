<?php
include("functions.php");
include("accesscontrol.php");
header("Location: http://".$_SERVER['HTTP_HOST'].($_SESSION['hasdashboard'] ? "/dashboard.php" : "/search.php"));

