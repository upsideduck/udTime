<?php
	require_once('func/func_session.php');
	//Start session
	session_start();
	
	close_session();
 
	
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