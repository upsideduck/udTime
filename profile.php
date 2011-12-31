<?php
	define(__SCRIPT_NAME__,"profile");

	require_once('auth.php');
    require_once("includes/config.php");
   	require_once('func/func_misc.php');
    require_once('includes/header.php');
   

echo "<h1>My Profile</h1>";
//<a href="index.php">Home</a> | <a href="profile.php">My Profile</a> | <a href="summary.php">Summary</a>
	notification();
	
	include_once("includes/avatarupload.php");
	
      require_once('includes/footer.php');
?>