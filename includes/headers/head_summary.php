<?php
	echo "<script type='text/javascript' src='js/summary.js'></script>\n";
	switch($_GET['summary']){
		case "week_list": 
			echo "<script type='text/javascript' src='js/summary_week_update.js'></script>";
			break;
		default:
			echo "<script type='text/javascript' src='js/summary_totals_update.js'></script>";
			break;
	}
?>	
	
