<?php 
//Sanitize the POST values
    $periodId = clean($_REQUEST['id']);
    $time = clean($_REQUEST['time']);
    $type = clean($_REQUEST['type']);

    
	$result_arr = updateasworktime($periodId, $time, $type);
	$output->results['updateasworktime'] = $result_arr;

?>