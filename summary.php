<?php
define(__SCRIPT_NAME__,"summary");

require_once('auth.php');

require_once("includes/config.php");
require_once(__SITE_BASE__.'func/func_fetch.php');
require_once(__SITE_BASE__.'func/func_misc.php');
//require_once(__SITE_BASE__.'func/func_stats.php');
require_once('includes/header.php');
//<a href="index.php">Home</a> | <a href="profile.php">My Profile</a> | <a href="summary.php">Summary</a>	

switch ($_REQUEST['summary']) {
	case "week_totals" : 
		include('includes/week_totals.php');
		break;
	case "week_view":
		require_once('includes/week_view.php');
		break;
	case "week_cal":
		require_once('includes/week_cal.php');
		break;
	case "month_totals" : 
		echo "<div class='month_totals_list'>\n";
		include('includes/month_totals.php');
		echo "</div>\n";
		break;
	case "month_view":
		require_once('includes/month_view.php');
		break;
	case "month_cal":
		require_once('includes/month_cal.php');
		break;
	case "day_view":
		break;
	default : 
		echo "Please choose model";
		break;
}
require_once('includes/footer.php');
?>