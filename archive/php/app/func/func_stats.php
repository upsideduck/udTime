<?php
/********************************************************************
 *
 *	getMonthStats - return month stats
 *
 *	Incomming: $year, $month
 *
 *	Outgoing : false or array(towork=>..,worked=>..)
 *
 ********************************************************************/ 
function getMonthStats($year,$month) {include(__SITE_BASE__."/includes/connection.php");
	$result = mysqli_query($link, "SELECT towork, worked, againstworktime, asworktime FROM monthstats WHERE year = {$year} AND month = {$month} AND member_id=".$_SESSION['SESS_MEMBER_ID']);
	$stats = mysqli_fetch_assoc($result);
	mysqli_close($link);
	return $stats;
}
/********************************************************************
 *
 *	getWeekStats - return week stats
 *
 *	Incomming: $year, $week
 *
 *	Outgoing : false or array(towork=>..,worked=>..)
 *
 ********************************************************************/ 
function getWeekStats($year,$week) {
	include(__SITE_BASE__."/includes/connection.php");
	$result = mysqli_query($link, "SELECT towork, worked, againstworktime, asworktime FROM weekstats WHERE year = {$year} AND week = {$week} AND member_id =".$_SESSION['SESS_MEMBER_ID'] );
	$stats = mysqli_fetch_assoc($result);
	mysqli_close($link);
	return $stats;
}
	
/********************************************************************
 *
 *	balanceUptoWeek - return balance up to week
 *
 *	Incomming: $year, $week
 *
 *	Outgoing : 
 *
 ********************************************************************/ 
function balanceUptoWeek($inyear,$inweek) {
	include(__SITE_BASE__."/includes/connection.php");
	global $user;
	$time = fetchStartEndTime("week", $inyear, $month = null, $day = null, $inweek) ;
	$statsspan = new timespan($user->statsstartdate, $time["end"]);
	
	$weekStats = $statsspan->getWeekStats();
	
	
	$newStats = array();
	$totalDiff = $user->offset;
	$i = 0;
	$j = count($weekStats);
	foreach($weekStats as $week){
		$diff = intval($week['asworktime'])+intval($week['worked']) - intval($week['towork']) + intval($week['againstworktime']);
		$week["workedtime"] = timestampToTime(intval($week['worked']));	// Worked as time
		if($diff > 0) $week["weekdifftime"] = "+".timestampToTime($diff); // diff as time
		else $week["weekdifftime"] = timestampToTime($diff); 
		$week["modifiedtimestamp"] = strtotime($week['modified']) == false ? 0 : strtotime($week['modified']);
		$totalDiff = $totalDiff + $diff;
		$week["totaldifftime"] = timestampToTime($totalDiff);// total diff
		
		$newStats[] = $week;
		$i++;
	}
	
	mysqli_close($link);
	return $newStats;
	
}
/********************************************************************
 *
 *	balanceUptoMonth - return balance up to month
 *
 *	Incomming: $year, $monht
 *
 *	Outgoing : 
 *
 ********************************************************************/ 
function balanceUptoMonth($inyear,$inmonth) {
	include(__SITE_BASE__."/includes/connection.php");
	global $user;
	$time = fetchStartEndTime("month", $inyear, $inmonth);
	$statsspan = new timespan($user->statsstartdate, $time["end"]);
	$monthStats = $statsspan->getMonthStats();
	
	
	$newStats = array();
	$totalDiff = $user->offset;
	foreach($monthStats as $month){
		$diff = intval($month['asworktime'])+intval($month['worked']) - intval($month['towork']) + intval($month['againstworktime']);
		$month["workedtime"] = timestampToTime(intval($month['worked']));	// Worked as time
		if($diff > 0) $month["monthdifftime"] = "+".timestampToTime($diff); // diff as time
		else $month["monthdifftime"] = timestampToTime($diff); 
		$month["modifiedtimestamp"] = strtotime($month['modified']) == false ? 0 : strtotime($month['modified']);
		$totalDiff = $totalDiff + $diff;
		$month["totaldifftime"] = timestampToTime($totalDiff);	// total diff
		$newStats[] = $month;
	}
	mysqli_close($link);
	return $newStats;
}
?>
