<?php
/************************************************************************
/*
/*	Week summaries updates
/*
/*
/*	Requires: 		func_misc.php
/*					func_weeksdb.php
/*	
/*	Post values:
/*					
/*
/*	Output:			$xml_output - $result_arr as xml
/*
/************************************************************************/
$result_arr = null;

$weeks_query = fetchWeeksSummary();

while ($week = mysql_fetch_assoc($weeks_query)) {
	$result_arr = updateWeekInBook($week['week'], $week['year']);
	$xml_output .= resultXMLoutput($result_arr, "weekupdate");
}
?>