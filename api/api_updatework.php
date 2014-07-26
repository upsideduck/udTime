<?php 
//Sanitize the POST values
	if(!is_numeric($_REQUEST["start_time"]) && !is_numeric($_REQUEST["end_time"])){	
   	 	$starttime = strtotime(clean($_REQUEST["start_time"]));
   	 	$endtime = strtotime(clean($_REQUEST["end_time"]));
   	}else{
   		$starttime = clean($_REQUEST["start_time"]);
   	 	$endtime = clean($_REQUEST["end_time"]);
   	}
    $periodId = clean($_REQUEST['id']);

    $orignalPeriod = fetchWorkPeriod($inputId);
    $toptime = $orignalPeriod[2][0]->starttime;
	$bottomtime = $orignalPeriod[2][0]->endtime;
	$result_arr = updatePeriod("work", $periodId, $starttime, $endtime, $orignalPeriod[1]->comment, $toptime, $bottomtime, $orignalPeriod[1]->starttime);
	$output->results['updatework'] = $result_arr;

?>