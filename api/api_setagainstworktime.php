<?php 
/************************************************************************
/*
/*	Adds free days
/*
/*
/*	Requires: 			
/*	
/*	Post values:	
/*
/*	Output:
/*
/************************************************************************/

$result_arr = null;

$starttime = clean($_REQUEST['starttime']);
$endtime = clean($_REQUEST['endtime']);
$type = clean($_REQUEST['type']);
if (strlen($_REQUEST['time']) == 8 && strpos($_REQUEST['time'],":") != false) $time = strtotime($_REQUEST['time']);
else $time = intval(clean($_REQUEST['time']));
if($time == 0 || !is_integer($time)) $time = 0;

$timespan = new timespan($starttime, $endtime);
$result_free = $timespan->setagainstworktime($time,$type);
if($result_free){
	foreach($result_free as $result){
		$result_arr[0] = true;
		$result_arr[] = $result[1]." - ".$result[2];
	}
}else{
	$result_arr[0] = false;
	$result_arr[] = "Nothing set";
}
$output->results['setagainstworktime'] = $result_arr;

?>