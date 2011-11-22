<?php 
/************************************************************************
/*
/*	Modify ongoing period period
/*
/*
/*	Requires: 		func_start.php
/*					func_end.php
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
else $timestamp = clean($_REQUEST['timestamp']);

$savedActivePeriod = $_SESSION['SESS_ACTIVE_PERIOD'] ;
$savedActiveType = $_SESSION['SESS_ACTIVE_TYPE'] ;

/* Get next increment */
switch($type) {
	case "work": 
		$result_arr = endWork($comment,$timestamp);
		$xml_output .= resultXMLoutput($result_arr, "endwork");
	  	break;
	case "break": 
		$result_arr = goOnBreak($comment,$timestamp);
		$xml_output .= resultXMLoutput($result_arr, "newbreak");
	 	break;
	 default:
	 	$result_arr[0] = false;
	 	$result_arr[] = "Type call not valid";
	 	$xml_output .= resultXMLoutput($result_arr, "undefined");
	 	break;	 		
}


 ?>