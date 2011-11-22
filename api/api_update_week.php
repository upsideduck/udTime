<?php
/************************************************************************
/*
/*	Week summaries updates
/*
/*
/*	Requires: 		func_misc.php
/*					func_weeksdb.php
/*	
/*	Post values:	week
/*					year
/*
/*	Output:			$xml_output - $result_arr as xml
/*
/************************************************************************/
$result_arr = null;

$week = clean($_REQUEST['week']);
$year = clean($_REQUEST['year']);

$result_arr = updateWeekInBook($week, $year);

$xml_output .= resultXMLoutput($result_arr, "weekupdate");

?>