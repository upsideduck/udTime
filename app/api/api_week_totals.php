<?php 

$result_arr[0] = true;
$result_arr[] = 'Week stats gathered';

$newStats = balanceUptoWeek(date("o"),date("W"));
#$newStats = balanceUptoWeek(2020,8);


$newStats[count($newStats)-1]["weekdifftime"] = "ongoing...";
$newStats[count($newStats)-1]["totaldifftime"] = "";

$output->results['weekstats'] = $result_arr;
$output->stats["weektotal"] = $newStats;

?>
