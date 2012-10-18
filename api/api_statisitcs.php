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
$week = clean($_REQUEST['week']);
$month = clean($_REQUEST['month']);
$year = clean($_REQUEST['year']);

if(in_array($type, $valid_calls)) {
	$result = fetchWorkAndBreakTime(fetchStartEndTime($type));
	$result["type"] = $type;
	$result_arr[0] = true;
	$result_arr[] = "Statistics fetched for: {$type}";
}elseif($type == "month" && is_numeric($month) && is_numeric($year)){
	$result = getMonthStats($year,$month);
	if($result != false){
		$result["type"] = $type;
		$result_arr[0] = true;
		$result_arr[] = "Statistics fetched for Year: {$year} Month: {$month}";
	}else{
		$result_arr[0] = false;
		$result_arr[] = "Statistics for Year: {$year} Month: {$month} not found";
	}
}elseif($type == "week"  && is_numeric($week) && is_numeric($year)){
	$result = getWeekStats($year,$week);
	$result["type"] = $type;	
	$result_arr[0] = true;
	$result_arr[] = "Statistics fetched for Year: {$year} Week: {$week}";
}else{
	$result = false;
	$result_arr[0] = false;
	$result_arr[] = "Statistics could not be fetched for: {$type}. Type not recognized.";
}


$xml_output .= resultXMLoutput($result_arr, "statistics");
if($result) $xml_output .= arrayXMLoutput($result,"{$type}stats");


?>