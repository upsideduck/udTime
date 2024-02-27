<?php
	define("__SCRIPT_NAME__","index");
	
	session_start();
      //Include database connection details
	require_once('includes/config.php');
	require_once(__SITE_BASE__ . 'func/func_misc.php');		
	require_once('includes/header.php');
	
	
	if(!isset($_SESSION['SESS_MEMBER_ID'])) 
	{ ?>
		<div class='row'>
		<div class='span12 marketing text-center'>
		<h1>Please login</h1>
		<?php if(isset($_SERVER['HTTP_X_AUTHENTIK_USERNAME'])) : ?>
			<h4>You are logged in as <?php echo $_SERVER['HTTP_X_AUTHENTIK_USERNAME'] ?> through Upsideduck authentication.</h4>
			<h4>This user does not seem to have an account in udTime.</h4>
		<?php endif; ?>
		</div>
		
	</div>
	<?php 
	} else {
		echo "<div class='row'>";
		echo "<div class='span10 offset2 marketing'>";
		echo "<h1>Welcome " . $_SESSION['SESS_USERNAME'] ."</h1>";
		echo "<p id='mfheader' class='marketing-byline'></p>\n ";
		echo "</div></div>\n";
		//<a href='index.php'>Home</a> | <a href='profile.php'>My Profile</a> | <a href='summary.php'>Summary</a><p>\n";
		echo "<div class='row'>";
		echo "<div class='span3 offset2'>";
		echo "<div class='well' id='formContainer'>\n";
		include_once("includes/main_form.php");
		echo "</div></div>\n";
		echo "<div class='span4'>";
		echo "<div class='' id='userContainer'>\n";
		include_once("includes/pans/userpan.php");
		echo "</div></div></div>\n";
		echo "<div class='row'>";
		echo "<div class='span7 offset2'>";
		echo "<div class='well well-small' id='projectsContainer'>\n";
		include_once("includes/pans/projectspan.php");
		echo "</div></div></div>\n";
		echo "<div class='row'>";
		echo "<div class='span7 offset2' id='resultContainer'>\n";
		include_once("includes/pans/resultpan.php");
		echo "</div></div>\n";
	}

	require_once('includes/footer.php');
?>

