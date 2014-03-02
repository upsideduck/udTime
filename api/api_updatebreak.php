<?php 
//Sanitize the POST values
    $starttime = strtotime(clean($_REQUEST["start_time"]));
    $endtime = strtotime(clean($_REQUEST["end_time"]));
    $periodId = clean($_REQUEST['id']);

    $orignalPeriod = fetchBreakPeriod($periodId);
    $workPeriod = fetchWorkPeriod($orignalPeriod[1]->parent_id);

    $toptime = $workPeriod[1]->endtime;
	$bottomtime = $workPeriod[1]->starttime;

	$result_arr = updatePeriod("break", $periodId, $starttime, $endtime, $orignalPeriod[1]->comment, $toptime, $bottomtime, $orignalPeriod[1]->starttime);
	$output->results['updatebreak'] = $result_arr;

?>