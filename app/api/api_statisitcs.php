<?php 
/************************************************************************
/*
/*	Calculates and returns statistics for type
/*
/*
/*	Requires: 		func_fetch.php, func_misc.php		
/*	
/*	Post values:	stattype: today, yesterday, thisweek, lastweek,
/*					   	  thismonth, lastmont, month (with specified data), 
/*						  week (with specified data), 
/*						  weekbalance(with specified data), 
/*						  monthbalance(with specified data), 
/*
/*	Output:			
/*
/************************************************************************/

$result_arr = null;

$valid_calls = array("today", "yesterday", "thisweek", "lastweek", "thismonth", "lastmonth");

$clean_type = clean($_REQUEST['stattype']);
$statweek = clean($_REQUEST['statweek']);
$statmonth = clean($_REQUEST['statmonth']);
$statyear = clean($_REQUEST['statyear']);
$statyearforweekofyear = clean($_REQUEST['statyearforweekofyear']);

if($statyearforweekofyear == "") $statyearforweekofyear = $statyear;

$stattypes = explode(",", $clean_type);

foreach($stattypes as $stattype){
	$result = false;
	if(in_array($stattype, $valid_calls)) {
		$result = fetchWorkAndBreakTime(fetchStartEndTime($stattype));
		$result["type"] = $stattype;
		$result_arr[0] = true;
		$result_arr[] = "Statistics fetched for: {$stattype}";
	}elseif($stattype == "month" && is_numeric($statmonth) && is_numeric($statyear)){
		$result = getMonthStats($statyear,$statmonth);
		if($result != false){
			$result["type"] = $stattype;
			$result_arr[0] = true;
			$result_arr[] = "Statistics fetched for Year: {$statyear} Month: {$statmonth}";
		}else{
			$result_arr[0] = false;
			$result_arr[] = "Statistics for Year: {$statyear} Month: {$statmonth} not found";
		}
	}elseif($stattype == "week"  && is_numeric($statweek) && is_numeric($statyearforweekofyear)){
		$result = getWeekStats($statyearforweekofyear,$statweek);
		if($result != false){
			$result["type"] = $stattype;
			$result_arr[0] = true;
			$result_arr[] = "Statistics fetched for Year: {$statyearforweekofyear} Week: {$statweek}";
		}else{
			$result_arr[0] = false;
			$result_arr[] = "Statistics for Year: {$statyearforweekofyear} Week: {$statweek} not found";
		}
	}elseif($stattype == "monthbalance" && is_numeric($statmonth) && is_numeric($statyear)){
		$stats = balanceUptoMonth($statyear,$statmonth);
		if($stats != false){;
			$result = end($stats);
			$result_arr[0] = true;
			$result_arr[] = "Balance fetched for Year: {$statyear} Month: {$statmonth}";
		}else{
			$result_arr[0] = false;
			$result_arr[] = "Balance for Year: {$statyear} Week: {$statmonth} not found";
		}
	}elseif($stattype == "weekbalance"  && is_numeric($statweek) && is_numeric($statyearforweekofyear)){
		$stats =  balanceUptoWeek($statyearforweekofyear,$statweek);
		if($stats != false){
			$result = end($stats);
			$result_arr[0] = true;
			$result_arr[] = "Balance fetched for Year: {$statyearforweekofyear} Week: {$statweek}";
		}else{
			$result_arr[0] = false;
			$result_arr[] = "Balance for Year: {$statyearforweekofyear} Week: {$statweek} not found";
		}	
	}else{
		$result = false;
		$result_arr[0] = false;
		$result_arr[] = "Statistics could not be fetched for: {$stattype}. Type not recognized.";
	}
	
	
	$output->results['statistics'] = $result_arr;
	if($result) {
		$output->arrays["stats"]["{$stattype}"] = $result;
	
	}
	
}

?>