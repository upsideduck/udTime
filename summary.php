<?php
define(__SCRIPT_NAME__,"summary");

require_once('auth.php');

require_once("includes/config.php");
require_once(__SITE_BASE__."func/func_weeksdb.php");
require_once(__SITE_BASE__.'func/func_fetch.php');
require_once(__SITE_BASE__.'func/func_misc.php');
require_once('includes/header.php');

echo "<h1 class='page_title'>Summary</h1>";
//<a href="index.php">Home</a> | <a href="profile.php">My Profile</a> | <a href="summary.php">Summary</a>	
echo "<p>";
	notification();

switch ($_REQUEST['summary']) {
	case "week_view":
		require_once('includes/week_view.php');
		break;
	case "week_list":
		echo "<div class='week_container_list'>";
		include('includes/week_list.php');
		echo "</div>";
		echo "<form id='update_weekForm' name='update_week' method='post' action=''><p>
			<input type='hidden' name='week' id='week' value='".$_REQUEST['w']."'>
			<input type='hidden' name='year' id='year' value='".$_REQUEST['y']."'>
			<input type='submit' name='button' id='button' value='Update' />
			</form>";
		break;
	case "day_view":
		break;
	default : 
		echo "<div class='week_totals_list'>\n";
		include('includes/week_totals.php');
		echo "</div>\n";
		echo "<form id='update_all_weeks' name='update_all_weeks' method='post' action=''>\n";
		echo "<input type='submit' name='button' id='button' value='Update' />\n";
		echo "</form>\n";
		break;
}

echo "<div id='result' class='notification_mainform'></div>";
require_once('includes/footer.php');
?>