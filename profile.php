<?php
	define(__SCRIPT_NAME__,"profile");

	require_once('auth.php');
    require_once("includes/config.php");
   	require_once('func/func_misc.php');
    require_once('includes/header.php');
   

echo "<h1>My Profile</h1>";

	echo "<h2>Upload avatar</h2>";
	include_once("includes/avatarupload.php");
	echo "<h2>Update stats</h2>";
	include_once("includes/full_stats_update.php");
    
	echo "<div id='resultContainer'>\n";
	include_once("includes/pans/resultpan.php");
	echo "</div>\n";

    require_once('includes/footer.php');
?>