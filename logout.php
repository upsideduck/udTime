<?php
	require_once('func/func_session.php');
	//Start session
	session_start();
	
	close_session();
 
	
?>
<?php
      require_once('includes/header.php');
?>
<h1>Logout </h1>
<p align="center">&nbsp;</p>
<h4 align="center" class="err">You have been logged out.</h4>
<p align="center">Click here to <a href="index.php">Login</a></p>
<?php
      require_once('includes/footer.php');
?>