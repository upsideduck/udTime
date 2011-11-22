<?php
	define(__SCRIPT_NAME__,"profile");

	require_once('auth.php');
    require_once("includes/config.php");
   	require_once('func/func_misc.php');
    require_once('includes/header.php');
   
?>
<h1>My Profile </h1>
<a href="index.php">Home</a> | <a href="profile.php">My Profile</a> | <a href="summary.php">Summary</a>
<?php 
	notification();
?>
<p>This is another secure page. </p>
<?php
      require_once('includes/footer.php');
?>