<?php
	define(__SCRIPT_NAME__,"profile");

	require_once('auth.php');
    require_once("includes/config.php");
   	require_once('func/func_misc.php');
    require_once('includes/header.php');
   
	echo "<div class='row'>";
	echo "<div class='span10 offset2 marketing'>";
	echo "<h1>Profile</h1>";
	echo "<p class='marketing-byline'>Edit settings</p>\n ";
	echo "</div></div>\n";
	echo "<div class='row'>";
	echo "<div class='span5 offset2'>";
	echo "<div id='resultContainer'>\n";
	include_once("includes/pans/resultpan.php");
	echo "</div></div></div>\n";
	echo "<div class='row'>";
	echo "<div class='span5 offset2'>";
	echo "<div class='well'>";
	echo "<h2>Upload avatar</h2>";
	include_once("includes/avatarupload.php");
	echo "</div></div>";
	if($user->admin):
	echo "<div class='span3'>";
	echo "<div class='well'>";
	echo "<h2>Update stats</h2>";
	include_once("includes/full_stats_update.php");
	echo "</div></div>";
	endif;
	echo "</div>\n";

    require_once('includes/footer.php');
?>