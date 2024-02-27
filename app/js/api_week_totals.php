<?php 

$regiserspan = new timespan($user->registerdate, time());
$weekStats = $regiserspan->getWeekStats();

$result_arr[0] = true;
$result_arr[] = 'Week stats gathered';

$newStats = array();
$totalDiff = $user->offset;
foreach($weekStats as $week){
	$diff = intval($week['asworktime'])+intval($week['worked']) - intval($week['towork']) - intval($week['againstworktime']);
	$week["workedtime"] = timestampToTime(intval($week['worked']));	// Worked as time
	if($diff > 0) $week["weekdifftime"] = "+".timestampToTime($diff); // diff as time
	else $week["weekdifftime"] = timestampToTime($diff); 
	$totalDiff = $totalDiff + $diff;
	$week["totaldifftime"] = timestampToTime($totalDiff);	// total diff
	$newStats[] = $week;
}
$newStats[count($newStats)-1]["weekdifftime"] = "ongoing...";
$newStats[count($newStats)-1]["totaldifftime"] = "";

$output->results['weekstats'] = $result_arr;
$output->stats["weektotal"] = $newStats;

?>