<?php
	require_once('func/func_session.php');
	define("__SCRIPT_NAME__","logout");
	
	//Start session
	session_start();
	$session_username = $_SESSION['SESS_USERNAME'];
	
	close_session();
	require_once('includes/config.php');
	if (isset($_SERVER['HTTP_X_AUTHENTIK_USERNAME']) &&  $_SERVER['HTTP_X_AUTHENTIK_USERNAME'] == $session_username ){ 
		header("Location: ".constant('__SITE_URI__')."/../outpost.goauthentik.io/sign_out");

	exit();
	}
	
?>
<?php
      require_once('includes/header.php');
?>

<div class='row'>
<div class='span10 offset2 marketing'>
<h1>Logout</h1>
<p id='mfheader' class='marketing-byline'>You have been logged out</p> 
</div></div>
<?php
      require_once('includes/footer.php');
?>
