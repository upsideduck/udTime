<?php
	echo "<script type='text/javascript' src='js/jquery.getUrlParam.js'></script>\n";
	echo "<script type='text/javascript' src='js/summary.js'></script>\n";
	echo "<script type='text/javascript' src='js/jquery.maskedinput-1.3.min.js'></script>";
	echo "<script type='text/javascript' src='js/jquery-ui-timepicker-addon.js'></script>";
	echo "<script type='text/javascript' src='js/add_forms.js'></script>";	
	switch($_GET['summary']){
		case "week_view": 
			break;
		case "week_cal":
			echo "<link rel='stylesheet' type='text/css' href='css/subpagemenu.css' />";
			echo "<link rel='stylesheet' type='text/css' href='css/fullcalendar.css' />";
			echo "<script type='text/javascript' src='js/fullcalendar.min.js'></script>";
			echo "<script type='text/javascript' src='js/prototype.getweek.js'></script>";
			echo "<script type='text/javascript' src='js/summary_week_cal.js'></script>";
			break;
		case "week_totals": 
			echo "<script type='text/javascript' src='js/summary_week_totals.js'></script>";			
			break;
		case "month_totals": 
			echo "<script type='text/javascript' src='js/summary_month_totals.js'></script>";			
			break;
		case "month_view": 		
			break;
		case "month_cal":
			echo "<link rel='stylesheet' type='text/css' href='css/subpagemenu.css' />";
			echo "<link rel='stylesheet' type='text/css' href='css/fullcalendar.css' />";
			echo "<script type='text/javascript' src='js/fullcalendar.min.js'></script>";
			echo "<script type='text/javascript' src='js/summary_month_cal.js'></script>";
			break;
		default:
			echo "<script type='text/javascript' src='js/summary_totals_update.js'></script>";
			break;
	}
?>	
	
