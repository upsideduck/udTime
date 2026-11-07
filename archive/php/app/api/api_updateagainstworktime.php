<?php 
//Sanitize the POST values
    $periodId = clean($_REQUEST['id']);
    $time = clean($_REQUEST['time']);
	$type = clean($_REQUEST['type']);
    
	$result_arr = updateagainstworktime($periodId, $time, $type);
	$output->results['updateagainstworktime'] = $result_arr;

?>