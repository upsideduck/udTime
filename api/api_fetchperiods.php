<?php 
/************************************************************************
/*
/*	Fetch periods as xml
/*
/*
/*	Requires: 		func_fetch.php
/*					func_misc.php
/*	
/*	Post values:	
/*
/*	Output:			$output_xml - $result_arr as xml
/*
/************************************************************************/
$week = intval(clean($_REQUEST['w'])) < 10 ? "0".intval(clean($_REQUEST['w'])) : intval(clean($_REQUEST['w']));
$month = clean($_REQUEST['m']);
$year = clean($_REQUEST['y']);
$workid = clean($_REQUEST['wid']);
$breakid = clean($_REQUEST['bid']);
$freeid = clean($_REQUEST['fid']);
$vacid = clean($_REQUEST['vid']);

$result_arr = null;
if($workid == "" && $breakid == "" && $freeid == "" && $vacid == ""){
	if($month != "" ){
		$timearray = fetchStartEndTime("month",$year,$month);
	}elseif($week != ""){
		$timearray = fetchStartEndTime("week",$year,"","",$week);
	}else{
		$timearray = array('start' => 0, 'end' => 0);
	}
	$timespan = new timespan($timearray['start'], $timearray['end']);
	$rarray = fetchPeriodsArray($timearray);
}elseif($workid != ""){

	$array = fetchWorkPeriod($workid);
	if($_SESSION['SESS_MEMBER_ID'] != $array[1]->member_id) $array = null;
	else{
		$timearray = array('start'=>$array[1]->starttime,'end'=>$array[1]->endtime);
		$timespan = new timespan($timearray['start'], $timearray['end']);
		$rarray = fetchPeriodsArray($timearray,$workid);
	}

}elseif($breakid != ""){
	$array = fetchBreakPeriod($breakid);
	if($_SESSION['SESS_MEMBER_ID'] != $array[1]->member_id) $array = null;
	else{
		$timearray = array('start'=>$array[1]->starttime,'end'=>$array[1]->endtime);
		$timespan = new timespan($timearray['start'], $timearray['end']);
		$rarray = fetchPeriodsArray($timearray,null,$breakid);
	}
}elseif($freeid != ""){
	$sql = sprintf("SELECT * FROM againstworktime WHERE id = %d", $freeid);
	$result = mysql_query($sql); 
	if(mysql_num_rows($result) == 1) {		// Check if the period was found
		$freePeriod = mysql_fetch_object($result);
		$timearray = array('start'=>$freePeriod->date,'end'=>$freePeriod->date);
		$timespan = new timespan($timearray['start'], $timearray['end']);
	}else{
		$freeid = null;	//nothing found exit api
	}

}elseif($vacid != ""){
	$sql = sprintf("SELECT * FROM asworktime WHERE id = %d", $vacid);
	$result = mysql_query($sql); 
	if(mysql_num_rows($result) == 1) {		// Check if the period was found
		$vacPeriod = mysql_fetch_object($result);
		$timearray = array('start'=>$vacPeriod->date,'end'=>$vacPeriod->date);
		$timespan = new timespan($timearray['start'], $timearray['end']);
	}else{
		$vacid = null;	//nothing found exit api
	}

}
if($year != null || $breakid != "" || $workid != ""){
	$result_arr[0] = true;
	$result_arr[] = "Periods fetched";
	
	$againstworktime = $timespan->getagainstworktime();
	$output->againstworktime[] = $againstworktime;
	$asworktime = $timespan->getasworktime();
	$output->asworktimes[] = $asworktime;
	$output->results['fetchperiods'] = $result_arr;
	$output->periods[] = $rarray;
}elseif($freeid != null){
	$result_arr[0] = true;
	$result_arr[] = "Periods fetched";
	
	$output->results['fetchperiods'] = $result_arr;
	$againstworktime = $timespan->getagainstworktime();
	$output->againstworktime[] = $againstworktime;
}elseif($vacid != null){
	$result_arr[0] = true;
	$result_arr[] = "Periods fetched";
	
	$output->results['fetchperiods'] = $result_arr;
	$asworktime = $timespan->getasworktime();
	$output->asworktimes[] = $asworktime;
	
}elseif($rarray == null || $year == null || ($month == null && $week == null)){
	$result_arr[0] = false;
	$result_arr[] = "No periods fetched";	
	$output->results['fetchperiods'] = $result_arr;
}else{
	$result_arr[0] = true;
	$result_arr[] = "Periods fetched";
	
	$againstworktime = $timespan->getagainstworktime();
	$output->againstworktime[] = $againstworktime;
	$asworktime = $timespan->getasworktime();
	$output->asworktimes[] = $asworktime;
	$output->results['fetchperiods'] = $result_arr;
	$output->periods[] = $rarray;
}


$userinforesults = mysql_query("SELECT statsstartdate,registerdate FROM userdb WHERE member_id = ". $_SESSION['SESS_MEMBER_ID']);
$userinfo = mysql_fetch_assoc($userinforesults);
$output->arrays['userinfo'] = $userinfo;
?>