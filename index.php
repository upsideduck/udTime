<?php
	define(__SCRIPT_NAME__,"index");
	
	session_start();
      //Include database connection details
	require_once('includes/config.php');
	require_once(__SITE_BASE__ . 'func/func_misc.php');		
	require_once('includes/header.php');
	
	
	if(!isset($_SESSION['SESS_MEMBER_ID'])) 
	{
		require_once("includes/login.php");
		echo "<div id='loading'>
		  <p><img src='images/loading.gif' /> Please Wait</p>
		</div>";
	} else {
		echo "<h1>Welcome " . $_SESSION['SESS_USERNAME'] . " </h1> <a href='index.php'>Home</a> | <a href='profile.php'>My Profile</a> | <a href='summary.php'>Summary</a><p>\n";
		echo "<div id='formContainer'>\n";
		require_once("includes/main_form.php");
		echo "</div>\n";
	}
?>
<?php
	require_once('includes/footer.php');
?>

