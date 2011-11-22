<?php
/********************************************************************
 *
 *	fetchWeekStartEndTime - returns array of two arrays with periods
 *						in defined timeperiod
 *
 *	Incomming: $week, $year 
 *
 *	Outgoing : $timeArray
 * 		keys : start - timestamp beginning
 *	   		   end   - timestamp out 
 *
 ********************************************************************/ 
function fetchWeekStartEndTime($week, $year) {
	$timeArray = array("start"=>0,"end"=>0);
	$timeArray["start"] = strtotime("20".$year."W".$week);
	$timeArray["end"] = $timeArray["start"] + (60*60*24*7) - 1;
	return $timeArray;
}

/********************************************************************
 *
 *	clean - Sanitize values received from the form. 
 * 				Prevents SQL injections
 *
 *	Incomming: $str
 *
 *	Outgoing : $st
 *
 ********************************************************************/ 
function clean($str) {
	$str = @trim($str);
	if(get_magic_quotes_gpc()) {
		$str = stripslashes($str);
	}
	return mysql_real_escape_string($str);
}
/********************************************************************
 *
 *	notification - Show message
 *
 *	Incomming: 
 *
 *	Outgoing : 
 *
 ********************************************************************/ 
function notification() {
	echo "<ul class='notification'>";
	if( isset($_SESSION['ERRMSG_ARR']) && is_array($_SESSION['ERRMSG_ARR']) && count($_SESSION['ERRMSG_ARR']) >0) {
		foreach($_SESSION['ERRMSG_ARR'] as $msg) {
			echo "<li class='err'>'",$msg,"</li>"; 
		}
		unset($_SESSION['ERRMSG_ARR']);
	} 
	if ( isset($_SESSION['SUCCESS_ARR']) && is_array($_SESSION['SUCCESS_ARR']) && count($_SESSION['SUCCESS_ARR']) >0 ) {
		foreach($_SESSION['SUCCESS_ARR'] as $msg) {
			echo "<li class='succ'>",$msg,"</li>"; 
		}
		unset($_SESSION['SUCCESS_ARR']);
	}
	echo '</ul>';
}
/********************************************************************
 *
 *	xmlIntro - starting of xml output
 *
 *	Incomming : 
 *		
 *	Outgoing : $xml_output
 *
 ********************************************************************/ 
function xmlIntro() {
	$xml_output = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	$xml_output .= "<udtime>\n";
	return $xml_output;
}
/********************************************************************
 *
 *	xmlOutro - end of xml output
 *
 *	Incomming : 
 *		
 *	Outgoing : $xml_output
 *
 ********************************************************************/ 
function xmlOutro() {
	$xml_output = "</udtime>\n";
	return $xml_output;
}
/********************************************************************
 *
 *	resultXMLoutput - Take result_arr and transform into xml code
 *
 *	Incomming : $result_arr
 *		key  : 0 - true-> success, false -> error
 *			   1.. - Message
 *
 *	Outgoing : $xml_output
 *
 ********************************************************************/ 
function resultXMLoutput($result_arr, $task) {
	$local_xml_output = "";
	if($result_arr[0]) $local_xml_output .= "\t<result success=\"true\" action=\"$task\">\n";
	else $local_xml_output .= "\t<result success=\"false\" action=\"$task\">\n";
	unset($result_arr[0]);
	foreach ($result_arr as $s) {
		$local_xml_output .= "\t\t<message>".$s."</message>\n";
	} 
	$local_xml_output .= "\t</result>\n";
	//header('Content-type: text/xml'); 
	return $local_xml_output;
}
/********************************************************************
 *
 *	arrayXMLoutput - Take array and transform into xml code
 *
 *	Incomming : $rarray
 *		key  : ….
 *
 *	Outgoing : $xml_output
 *
 ********************************************************************/ 
function arrayXMLoutput($rarray) {
	$local_xml_output = "";
	$local_xml_output .= "\t<period>\n";
	foreach (array_keys($rarray) as $key) {
		$local_xml_output .= "\t\t<$key>".$rarray[$key]."</$key>\n";
	} 
	$local_xml_output .= "\t</period>";
	return $local_xml_output;
}
/********************************************************************
 *
 *	timestampToTime - Transform timestamp to readable format
 *
 *	Incomming : $timestamp
 *		
 *	Outgoing : $time_string
 *
 ********************************************************************/ 
function timestampToTime($timestamp) {
	
    $hours = intval(floor($timestamp / 3600));
    if ($hours < 10) $hours = "0$hours"; 
    $timestamp = $timestamp % 3600;
    $minutes = intval(floor($timestamp / 60)); 
    if ($minutes < 10) $minutes = "0$minutes";
    $timestamp = $timestamp % 60;
    $seconds = $timestamp;
    if ($seconds < 10) $seconds = "0$seconds";
	return "$hours:$minutes:$seconds";
}
/********************************************************************
 *
 *	timeToTimestamp - Transform timestamp to readable format
 *
 *	Incomming : $time (string 00:00:00)
 *		
 *	Outgoing : $timestamp  
 *
 ********************************************************************/ 
function timeToTimestamp($time) {
	$time_parts = explode(":", $time);
	if (count($time_parts) == 2) $time_parts[2] = 0;
	return $time_parts[0]*60*60+$time_parts[1]*60+$time_parts[2];
}
/********************************************************************
 *
 *	updateSession - update Session variables
 *
 *	Incomming : 
 *		
 *	Outgoing : 
 *
 ********************************************************************/ 
function updateSession($member) {
	$_SESSION['SESS_ACTIVE_PERIOD'] = $member->activeperiod;	
	$_SESSION['SESS_ACTIVE_TYPE'] = $member->activetype;

	}
?>