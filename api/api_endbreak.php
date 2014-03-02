<?php 
/************************************************************************
/*
/*	End Break
/*
/*
/*	Requires: 		func_end.php
/*					func_misc.php
/*					
/*	
/*	Post values:	type
/*					comment
/*					timestamp
/*
/*	Output:			
/*
/************************************************************************/
$result_arr = null;

//Sanitize the POST values
$type = clean($_REQUEST['type']);
$comment = clean($_REQUEST['comment']);
if ( $_REQUEST['timestamp'] == "") $timestamp = date("U");
elseif (strlen($_REQUEST['timestamp']) == 5) $timestamp = strtotime($_REQUEST['timestamp']);
else $timestamp = $_REQUEST['timestamp'];
  
if($type == "break" || $type == "work") {
	      	
	switch ($type) {
		case "break":                // end break
	        $result_arr = endBreak($comment,$timestamp);
	        $output->results['endbreak'] = $result_arr;
	        break;
	    case "work":
			$result_arr1 = endBreak($comment,$timestamp);
			$result_arr2 = endWork($comment,$timestamp);
			$result_arr[0] = $result_arr1[0] * $result_arr2[0];
			unset($result_arr1[0]);
			unset($result_arr2[0]);
			foreach ($result_arr1 as $s) {
				$result_arr[] = $s;
			}
			foreach ($result_arr2 as $s) {
				$result_arr[] = $s;
			} 		
			$output->results['endwork'] = $result_arr;
			break;   		
	}
} elseif($user->activetype == "break") {	
	$result_arr = endBreak($comment,$timestamp);
    $output->results['endbreak'] = $result_arr;
} else {
	$result_arr[0] = false;
	$result_arr[] = "Type call not valid";
	$output->results[$user->activetype] = $result_arr;
	
}
?>