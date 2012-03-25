<?php
 include_once("includes/config.php");

if($_REQUEST['id'] == null) $activeperiod = "-1";
else {
	$result = mysql_query("SELECT activeperiod,activetype FROM userdb WHERE member_id = {$_REQUEST['id']}");
	$activeperiod = mysql_result($result,0,0);
	$activetype = mysql_result($result,0,1);
}

$time = time();
while((time() - $time) < 30) {
	if($_REQUEST['id'] == null) 
		$currentactiveperiod = "-1";
	else {
    	$result = mysql_query("SELECT activeperiod,activetype FROM userdb WHERE member_id = {$_REQUEST['id']}");
    	$currentactiveperiod = mysql_result($result,0,0);
    	$currentactivetype = mysql_result($result,0,1);
    }
    
    // if we have new data return it
    if($activeperiod != $currentactiveperiod) {
    	header('Cache-Control: no-cache, must-revalidate');
		header('Content-type: application/json');
    	if($currentactiveperiod == null || ($currentactiveperiod != $activeperiod && $activetype == $currentactivetype)) {
    		echo json_encode(array("event" => "period ended"));
        	break;
    	}
        echo json_encode(array("event" => "period updated"));
        break;
    }
 	sleep(1);
    //usleep(25000);
}

?>