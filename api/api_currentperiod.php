<?php 
/************************************************************************
/*
/*	Fetch current period as xml
/*
/*
/*	Requires: 		func_fetch.php
/*					func_misc.php
/*	
/*	Post values:	
/*
/*	Output:			$output_xml - $result_arr as xml
/*
/************************************************************************/

$rarray = fetchCurrentPeriod();
$result_arr = null;

if($_SESSION['SESS_ACTIVE_TYPE'] != "") {
	
	switch ($_SESSION['SESS_ACTIVE_TYPE']) {
		case "work":
			$sql = "SELECT * FROM breakdb WHERE parent_id = ".$_SESSION['SESS_ACTIVE_PERIOD'];
			$resultbreaks = mysql_query($sql);
			$allBreakTime = 0;
			while ($break = mysql_fetch_object($resultbreaks)) {
			      $allBreakTime = $allBreakTime + $break->endtime - $break->starttime;
			}
			break;
		case "break":
			$allBreakTime = 0;
			break;
		default:
			break;	            
	}
	
	$rarray["allBreakTime"] = $allBreakTime;
	
	$result_arr[0] = true;
	$result_arr[] = "Period fetched";
}else{
	$result_arr[0] = false;
	$result_arr[] = "No active period";
	$rarray = array("member_id" => $_SESSION['SESS_MEMBER_ID']);		// We need this for main form to now current user id for event control
}

$xml_output .= resultXMLoutput($result_arr, "currentperiod");

$xml_output .= arrayXMLoutput($rarray);

?>