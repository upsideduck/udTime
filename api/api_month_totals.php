<?php 

$result_arr[0] = true;
$result_arr[] = 'Month stats gathered';

$newStats = balanceUptoMonth(date("Y"),date("m"));

$newStats[count($newStats)-1]["monthdifftime"] = "ongoing...";
$newStats[count($newStats)-1]["totaldifftime"] = "";

$output->results['monthstats'] = $result_arr;
$output->stats["monthtotal"] = $newStats;

?>