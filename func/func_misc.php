<?php
/********************************************************************
 *
 *	fetchStartEndTime - returns array of two arrays with periods
 *						in defined timeperiod
 *
 *	Incomming: $type - year, month, day, week, today, 
 * 					   yesterday, thisweek, lastweek,
 *					   thismonth, lastmonth
 *			   $year
 *			   $month
 *			   $day
 *			   $week 
 *
 *	Outgoing : $timeArray
 * 		keys : start - timestamp beginning
 *	   		   end   - timestamp out 
 *
 ********************************************************************/ 
function fetchStartEndTime($type, $year = null, $month = null, $day = null, $week = null) {
	$timeArray = array("start"=>0,"end"=>0);
	$week = str_pad($week, 2, "0", STR_PAD_LEFT);
	if(strlen($year == 2)) $year = str_pad($year, 4, "20", STR_PAD_LEFT);
	switch ($type) {
		case ("year"): 
			$timeArray["start"] = mktime(0, 0, 0, 1, 1, $year);
			$timeArray["end"] =  mktime(0, 0, 0-1, 1, 1, $year+1);
			break;
		case ("month"):
			$timeArray["start"] = mktime(0, 0, 0, $month, 1, $year);
			$timeArray["end"] =  mktime(0, 0, 0-1, $month+1, 1, $year); 
			break;
		case ("day"): 
			$timeArray["start"] = mktime(0, 0, 0, $month, $day, $year);
			$timeArray["end"] =  mktime(0, 0, 0-1, $month, $day+1, $year); 
			break;
		case ("week"):
			$timeArray["start"] = strtotime($year."W".$week);
			$timeArray["end"] = $timeArray["start"] + (60*60*24*7) - 1; 
			break;
		case ("today"):
			$timeArray["start"] = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
			$timeArray["end"] =  mktime(0, 0, 0-1, date("m"), date("d")+1, date("Y"));
			break;	
		case ("yesterday"):
			$timeArray["start"] = mktime(0, 0, 0, date("m"), date("d")-1, date("Y"));
			$timeArray["end"] =  mktime(0, 0, 0-1, date("m"), date("d"), date("Y"));
			break;
		case ("thisweek"):
			$timeArray["start"] = strtotime(date("Y")."W".date("W"));
			$timeArray["end"] = $timeArray["start"] + (60*60*24*7) - 1; 
			break;
		case ("lastweek"):
			$timeArray["start"] = strtotime(date("Y")."W".date("W"))-(60*60*24*7);
			$timeArray["end"] = $timeArray["start"] + (60*60*24*7) - 1; 
			break;
		case ("thismonth"):
			$timeArray["start"] = mktime(0, 0, 0, date("m"), 1, date("Y"));
			$timeArray["end"] =  mktime(0, 0, 0-1, date("m")+1, 1, date("Y"));
			break;
		case ("lasmonth"):
			$timeArray["start"] = mktime(0, 0, 0, date("m")-1, 1, date("Y"));
			$timeArray["end"] =  mktime(0, 0, 0-1, date("m"), 1, date("Y"));
			break;
		default:
			break;
	}
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
 *	timestampToTime - Transform timestamp to readable format
 *
 *	Incomming : $timestamp
 *		
 *	Outgoing : $time_string
 *
 ********************************************************************/ 
function timestampToTime($timestamp) {
	if($timestamp < 0) {
		$neg = true;
		$timestamp = abs($timestamp);
	}
    $hours = intval(floor($timestamp / 3600));
    if ($hours < 10) $hours = "0{$hours}"; 
    $timestamp = $timestamp % 3600;
    $minutes = intval(floor($timestamp / 60)); 
    if ($minutes < 10) $minutes = "0$minutes";
    $timestamp = $timestamp % 60;
    $seconds = $timestamp;
    if ($seconds < 10) $seconds = "0$seconds";
    
    if($neg) return "-$hours:$minutes:$seconds";
	else return "$hours:$minutes:$seconds";
}

/********************************************************************
 *
 *	timestampToDecTime - Transform timestamp to H:Dec(min) format
 *
 *	Incomming : $timestamp
 *		
 *	Outgoing : $time_string
 *
 ********************************************************************/ 
function timestampToDecTime($timestamp) {
	
	$hours = floor($timestamp/60/60);
	$min = floor(($timestamp - $hours*60*60)/60);
	$sec = $timestamp- $hours*60*60 - $min*60;
	$mmin = Round($min/60*100,0) < 10 ? 0 . Round($min/60*100,0) : Round($min/60*100,0);
    
	return "$hours.$mmin";
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
/********************************************************************
 *
 *	workingdaysmonth - working days of month
 *
 *	Incomming : $month,$year)
 *		
 *	Outgoing : days or false
 *
 ********************************************************************/ 
function workingdaysmonth($month,$year) {
	if(is_numeric($month) && is_numeric($year)) { //need to be a numbers
		if($month > 12) {
			return false;
		}
		
		$statsstartdateresult = mysql_query("SELECT statsstartdate FROM userdb WHERE member_id = ". $_SESSION['SESS_MEMBER_ID']);
		$statsstartdate = mysql_result($statsstartdateresult,0);
		
		$sstatsyear = date("Y",$statsstartdate);
		$sstatsmonth = date("m",$statsstartdate);
		$sstatsday = date("d",$statsstartdate);
		
		//Check if set "start of stats calculation" is before current month, if not use that as basis
		if(mktime(0,0,0,$month,1,$year) < mktime(0,0,0,$sstatsmonth,$sstatsday,$sstatsyear)){
			$start = mktime(0,0,0,$sstatsmonth,$sstatsday,$sstatsyear);
		}else{
			$start = mktime(0,0,0,$month,1,$year);
		}
		$end = ($month ==12) ? mktime(0,0,0,1,1,$year+1):
		mktime(0,0,0,$month+1,1,$year);
		$bet = ($end - $start)/86400;
		$days = 0;
		for($i=0;$i<$bet;$i++) {
			$newday = date("w",($start + ($i*86400))); // increment day of week
			if($newday != 0 && $newday != 6) { // if not sat. or sun.
				$days++; // increment days
			}
		}
		return $days;
	}
	return false;
}
/********************************************************************
 *
 *	messageRedis - 
 *
 *	Incomming : 
 *		
 *	Outgoing : 
 *
 ********************************************************************/ 
function messageRedis() {
	$redis = new Credis_Client('doop.johanadell.com');
	$redis->publish('udtime', json_encode(array("uid"=>$_SESSION['SESS_MEMBER_ID'],"msg"=>"My message")));
}

/********************************************************************
 *
 *	lookupTable - 
 *
 *	Incomming : type 
 *		
 *	Outgoing : 
 *
 ********************************************************************/ 
function lookupTable($type) {
	$lookupresult = mysql_query("SELECT * FROM lookup WHERE type = '{$type}'");
	$lookup_arr= array();
	while ($obj = mysql_fetch_object($lookupresult)) {
	    $lookup_arr[] = $obj;
	}
	return $lookup_arr;
}

?>