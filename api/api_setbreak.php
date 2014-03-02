<?php 

//Sanitize the POST values
    $starttime = strtotime(clean($_REQUEST["start_time"]));
    $endtime = strtotime(clean($_REQUEST["end_time"]));
    $comment = clean($_REQUEST["comment"]);    
    $parentId = clean($_REQUEST["pid"]);  

    
	$output->results["setbreak"] = addPeriod("break", $starttime, $endtime, $comment);

?>