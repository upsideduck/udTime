<?php 
/************************************************************************
/*
/*	End Break
/*
/*
/*	Requires: 		func_end.php
/*					func_misc.php
/*					func_weeksdb.php
/*	
/*	Post values:	type
/*					comment
/*					timestamp
/*
/*	Output:			$output_xml - $result_arr as xml
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
	        $xml_output .= resultXMLoutput($result_arr, "endbreak");
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
			$xml_output .= resultXMLoutput($result_arr, "endwork");
			break;   
		default:
			$result_arr[0] = false;
			$result_arr[] = "Type call not valid";
			$xml_output .= resultXMLoutput($result_arr, "undefined");
			break;	 		
	}
} else {
	$result_arr[0] = false;
	$result_arr[] = "Type call not valid";
	$xml_output .= resultXMLoutput($result_arr, "undefined");
	
}
?>