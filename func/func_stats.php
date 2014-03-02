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
function getMonthStats($year,$month) {
	$result = mysql_query("SELECT towork, worked, againstworktime, asworktime FROM monthstats WHERE year = {$year} AND month = {$month} AND member_id=".$_SESSION['SESS_MEMBER_ID']);
	$stats = mysql_fetch_assoc($result);
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
	$result = mysql_query("SELECT towork, worked, againstworktime, asworktime FROM weekstats WHERE year = {$year} AND week = {$week} AND member_id =".$_SESSION['SESS_MEMBER_ID'] );
	$stats = mysql_fetch_assoc($result);
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
	$time = fetchStartEndTime("week", $inyear, $month = null, $day = null, $inweek) ;
	$statsspan = new timespan($user->statsspan, $time["end"]);
	$weekStats = $statsspan->getWeekStats();
	
	global $user;

	$newStats = array();
	$totalDiff = $user->offset;
	foreach($weekStats as $week){
		$diff = intval($week['asworktime'])+intval($week['worked']) - intval($week['towork']) + intval($week['againstworktime']);
		$week["workedtime"] = timestampToTime(intval($week['worked']));	// Worked as time
		if($diff > 0) $week["weekdifftime"] = "+".timestampToTime($diff); // diff as time
		else $week["weekdifftime"] = timestampToTime($diff); 
		$totalDiff = $totalDiff + $diff;
		$week["totaldifftime"] = timestampToTime($totalDiff);	// total diff
		$newStats[] = $week;
	}
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
	$time = fetchStartEndTime("month", $inyear, $inmonth);
	$statsspan = new timespan($user->statsspan, $time["end"]);
	$monthStats = $statsspan->getMonthStats();
	
	global $user;
	
	$newStats = array();
	$totalDiff = $user->offset;
	foreach($monthStats as $month){
		$diff = intval($month['asworktime'])+intval($month['worked']) - intval($month['towork']) + intval($month['againstworktime']);
		$month["workedtime"] = timestampToTime(intval($month['worked']));	// Worked as time
		if($diff > 0) $month["monthdifftime"] = "+".timestampToTime($diff); // diff as time
		else $month["monthdifftime"] = timestampToTime($diff); 
		$totalDiff = $totalDiff + $diff;
		$month["totaldifftime"] = timestampToTime($totalDiff);	// total diff
		$newStats[] = $month;
	}
	return $newStats;
}
?>