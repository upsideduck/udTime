<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
		
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_misc.php');
	require_once('../func/func_edit.php');
	require_once('../func/func_fetch.php');	
	require_once('../func/func_weeksdb.php');
	
	
	//Sanitize the POST values
    $starttime = strtotime(clean($_REQUEST["start_date"]) . " " . clean($_REQUEST["start_hour"]) . ":" . clean($_REQUEST["start_min"]) . ":" . clean($_REQUEST["start_sec"]));
    $endtime = strtotime(clean($_POST["end_date"]) . " " . clean($_REQUEST["end_hour"]) . ":" . clean($_REQUEST["end_min"]) . ":" . clean($_REQUEST["end_sec"]));
    $comment = clean($_REQUEST["comment"]);    
    $type = clean($_REQUEST["type"]);
    $action = clean($_REQUEST["a"]);
    
    if($action  == "edit") {
    //Post values
    	$period = unserialize($_REQUEST["fetched_period"]);
    	$toptime = $period[2][0]->starttime;
	    $bottomtime = $period[2][0]->endtime;
	    $result_arr = updatePeriod($type, $_GET['p'], $starttime, $endtime, $comment, $toptime, $bottomtime, $period[1]->starttime);
    } elseif($action  == "new") {
    //Post values
        $result_arr = addPeriod($type, $starttime, $endtime, $comment);
    } else {
     	$result_arr[0] = false;
      	$result_arr[] = "No action";
    }
	//If there are input validations, redirect back to the login form
	if(!$result_arr[0]) {
		
		unset($result_arr[0]);
		$_SESSION['ERRMSG_ARR'] = $result_arr;
	} else {
		$action = "edit";
		unset($result_arr[0]);
		$_SESSION['SUCCESS_ARR'] = $result_arr;
	}
  
	session_write_close();
	header("location: ../edit.php?p=".$_GET['p']."&type=".$_REQUEST['type']."&a=".$action);
	exit();
?>