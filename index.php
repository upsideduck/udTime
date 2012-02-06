<<<<<<< HEAD
<<<<<<< HEAD
=======
<<<<<<< HEAD
>>>>>>> parent of 86fefc5... Clean up problems
=======
>>>>>>> parent of 86fefc5... Clean up problems
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
		echo "<h1>Welcome " . $_SESSION['SESS_USERNAME'] . " </h1>\n ";
		//<a href='index.php'>Home</a> | <a href='profile.php'>My Profile</a> | <a href='summary.php'>Summary</a><p>\n";
		echo "<div id='formContainer'>\n";
		include_once("includes/main_form.php");
		echo "</div>\n";
		echo "<div id='userContainer'>\n";
		include_once("includes/pans/userpan.php");
		echo "</div>\n";
		echo "<div id='projectsContainer'>\n";
		include_once("includes/pans/projectspan.php");
		echo "</div>\n";
		echo "<div id='resultContainer'>\n";
		include_once("includes/pans/resultpan.php");
		echo "</div>\n";
	}

	require_once('includes/footer.php');
?>

<<<<<<< HEAD
<<<<<<< HEAD
=======
=======
>>>>>>> parent of 86fefc5... Clean up problems
=======
<html>
<head>
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/update.js"></script>
<link href="css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div class="cssbox">
	<div class="cssbox_head">
		<h3>Status</h3>
		</div>
		<div class="cssbox_body">
			<div class="under_header">MAX I</div>
			<div id="max1"><div id="current"></div><div id="lifetime"></div></div>
			<div class="under_header">MAX II</div>
			<div id="max2"><div id="current"></div><div id="lifetime"></div></div>
			<div class="under_header">MAX III</div>
			<div id="max3"><div id="current"></div><div id="lifetime"></div></div>
			<div id="current_time"></div>
		</div>
</div>
</body>
</html>
>>>>>>> First commit
<<<<<<< HEAD
>>>>>>> parent of 86fefc5... Clean up problems
=======
>>>>>>> parent of 86fefc5... Clean up problems
