<?php
	define(__SCRIPT_NAME__,"index");
	
	session_start();
      //Include database connection details
	require_once('includes/config.php');
	require_once(__SITE_BASE__ . 'func/func_misc.php');		
	require_once('includes/header.php');
	
	
	if(!isset($_SESSION['SESS_MEMBER_ID'])) 
	{ ?>
		<div class='row'>
		<div class='span12 marketing text-center'>
		<h1>Why udTime?</h1>
		<p class='marketing-byline'>Time tracking done simple</p>
		</div></div>
		<div class='row'>
			<div class='span8 offset2'>
			<h4>Easy to use</h4>
			<p>udTime is tailored to be simple and easy to use. Easily check in/out when you arrive at work, when you go on break and when you leave for the day. Report asworktime days and other free days provided by your employer. View weekly or monthly summaries of your work time and export reports for official time reporting. Everything done through an easy to use interface with both list and calendar views that are highly interactive.</p>
			<p>iOS app is also provided for easy check in/out.</p>
			</div>
		</div>
		<div class='row'>
			<div class='span3 offset2'>
			<h4>Web control</h4>
			<p><img class="img-polaroid" src="img/web.png"></p>
			</div>
			<div class='span3 offset2'>
			<h4>List view</h4>
			<p><img class="img-polaroid" src="img/list.png"></p>
			</div>
		</div>
		<div class='row'>
			<div class='span3 offset2'>
			<h4>Calendar view</h4>
			<p><img class="img-polaroid" src="img/cal.png"></p>
			</div>
			<div class='span3 offset2'>
			<h4>iOS app</h4>
			<p><img class="img-polaroid" src="img/ios.png"></p>
			</div>
		</div
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

