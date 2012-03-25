<?php 
/************************************************************************
/*
/*	Calculates and returns statistics for type
/*
/*
/*	Requires: 		func_fetch.php, func_misc.php		
/*	
/*	Post values:	type: today, yesterday, thisweek, lastweek,
/*					   	  thismonth, lastmont
/*
/*	Output:			$xml_output - $result_arr as xml
/*
/************************************************************************/

$result_arr = null;

$valid_calls = array("today", "yesterday", "thisweek", "lastweek", "thismonth", "lastmonth");

$type = clean($_REQUEST['type']);

if(in_array($type, $valid_calls)) {
	$result = fetchWorkAndBreakTime(fetchStartEndTime($type));
	$result["type"] = $type;
	$result_arr[0] = true;
	$result_arr[] = "Statistics fetched for: {$type}";
}else{
	$result = false;
	$result_arr[0] = false;
	$result_arr[] = "Statistics could not be fetched for: {$type}. Type not recognized.";
}

$xml_output .= resultXMLoutput($result_arr, "statistics");
if($result) $xml_output .= arrayXMLoutput($result);


?>