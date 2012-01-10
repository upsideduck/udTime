<?php 
/********************************************************************
 *
 *	fetchPeriodsArray - returns array of two arrays with periods
 *						in defined timeperiod
 *
 *	Incoming: $timeArray 
 *		keys : start, end
 *
 *	Outgoing : $return
 * 		keys : 0 , 1
 *		   0 : $allPeriods - all periods in the time interval, 
 *							breaks under same key as periods
 *		   1 : $dayArray - how the periods should be divided by day, 
 *							key 1 is first day and so on
 *
 ********************************************************************/  
function fetchPeriodsArray($timeArray) {
	$sql = "SELECT * FROM workdb WHERE member_id = " . $_SESSION['SESS_MEMBER_ID'] . " AND starttime BETWEEN " . $timeArray['start'] ." AND " . $timeArray['end']." AND endtime IS NOT NULL ORDER BY starttime ASC";
	$result = mysql_query($sql);
	if(!$result) return null;
	
	while($periodsOfTimeArray = mysql_fetch_assoc($result)) {
		$thisPeriod = array();
		$dayArray[date("N",$periodsOfTimeArray['starttime'])][] = count($allPeriods);
		$thisPeriod[] = $periodsOfTimeArray;
		$sql2 = "SELECT * FROM breakdb WHERE parent_id = ".$periodsOfTimeArray['id']." ORDER BY starttime ASC";
		$result2 = mysql_query($sql2);
		while($breaksOfPeriod = mysql_fetch_assoc($result2)) {
			$thisPeriod[] = $breaksOfPeriod;
		}
		$allPeriods[] = $thisPeriod; 
	}
	$return = array();
	$return[0] = $allPeriods;
	$return[1] = $dayArray;
	return $return;
}

/********************************************************************
 *
 *	fetchWorkPeriod - Fetch single work period
 *
 *	Incoming: $workId 
 *
 *	Outgoing : $return
 * 		keys : 0, 1, 2
 *		   0 : $result - 0: bool, true->success, false->error 1-…: Errors as strings
 *		   1 : $workPeriod - work-object
 *		   2 : $breaks_arr - break-objcts array
 *
 ********************************************************************/  
function fetchWorkPeriod($workId) {
	$result_arr[0] = true;
	$breaks_arr = array();
	$return = array();
	
	if ($workId == '') {
		$result_arr[0] = false;
		$result_arr[] = "No period choosen";
		$return[0] = $result_arr;
		return $return; 
	}

	$sql = sprintf("SELECT * FROM workdb WHERE id = %d", $workId);
	$result = mysql_query($sql); 
	if(mysql_num_rows($result) != 1) {		// Check if the period was found
		$result_arr[0] = false;
		$result_arr[] = "Work period could not be found";
		$return[0] = $result_arr;
		return $return; 
	}
	$workPeriod = mysql_fetch_object($result);
	if($workPeriod->member_id != $_SESSION["SESS_MEMBER_ID"]) {
		$result_arr[0] = false;
		$result_arr[] = "Authorization to view this period is missing";
		$return[0] = $result_arr;
		return $return; 
	}
	$sql2 = sprintf("SELECT * FROM breakdb WHERE parent_id = %d ORDER BY starttime ASC", $workPeriod->id);
	$result2 = mysql_query($sql2); 
	
	while($break = mysql_fetch_object($result2)) {
		$breaks_arr[] = $break;
	}
	$return[0] = $result_arr;
	$return[1] = $workPeriod;
	$return[2] = $breaks_arr;
	return $return;
}
/********************************************************************
 *
 *	fetchBreakPeriod - Fetch single break period
 *
 *	Incoming: $breakId 
 *
 *	Outgoing : $return
 * 		keys : 0, 1
 *		   0 : $result - 0: bool, true->success, false->error 1-…: Errors as strings
 *		   1 : $breakPeriod - break-object
 *
 ********************************************************************/  
function fetchBreakPeriod($breakId) {
	$result_arr[0] = true;
	$return = array();
	
	if ($breakId == '') {
		$result_arr[0] = false;
		$result_arr[] = "No period choosen";
		$return[0] = $result_arr;
		return $return; 
	}

	$sql = sprintf("SELECT * FROM breakdb WHERE id = %d", $breakId);
	$result = mysql_query($sql); 
	if(mysql_num_rows($result) != 1) {		// Check if the period was found
		$result_arr[0] = false;
		$result_arr[] = "Break period could not be found";
		$return[0] = $result_arr;
		return $return; 
	}
	$breakPeriod = mysql_fetch_object($result);
	if($breakPeriod->member_id != $_SESSION["SESS_MEMBER_ID"]) {
		$result_arr[0] = false;
		$result_arr[] = "Authorization to view this period is missing";
		$return[0] = $result_arr;
		return $return; 
	}
	
	$return[0] = $result_arr;
	$return[1] = $breakPeriod;
	return $return;
}
/********************************************************************
 *
 *	fetchCurrentPeriod - returns array with current period data
 *
 *	Incomming: none 
 *
 *	Outgoing : $return
 * 		keys : 
 *		   
 *
 ********************************************************************/  
function fetchCurrentPeriod() {
	$pid = $_SESSION['SESS_ACTIVE_PERIOD'];
	$type = $_SESSION['SESS_ACTIVE_TYPE'];
	
	switch ($type) {
		case "work":
			$sql = sprintf("SELECT * FROM workdb WHERE id = %d", $pid);
			break;
		case "break":
			$sql = sprintf("SELECT * FROM breakdb WHERE id = %d", $pid);
			break;
		default:
			$sql = "";
			break;
	}
	if ($sql != "") 
	{
		$result = mysql_query($sql);
		$return = mysql_fetch_assoc($result);
	}
	$return["type"] = $type;
	return $return;
}
/********************************************************************
 *
 *	fetchHoliday - returns holiday
 *
 *	Incomming: week, year 
 *
 *	Outgoing : $return
 * 		
 *		   
 *
 ********************************************************************/  
function fetchHoliday($inWeek, $inYear) {

	$sql = sprintf("SELECT holiday FROM weeksdb WHERE week = %d AND year = %d AND member_id = %d", $inWeek, $inYear,$_SESSION["SESS_MEMBER_ID"]);

	if ($sql != "") 
	{
		$result = mysql_query($sql);
		$return = mysql_fetch_row($result);
	}
	return $return;
}
/********************************************************************
 *
 *	fetchWorkAndBreakTime - returns work and breaks for timearray
 *
 *	Incomming: timearray 
 *
 *	Outgoing : work and break array
 * 		
 *		   
 *
 ********************************************************************/ 
function fetchWorkAndBreakTime($timearray) {
	$worktime = 0;
	$breaktime = 0;
	$periods = fetchPeriodsArray($timearray);
	if($periods[0] != null) {
		foreach($periods[0] as $period) {
			$worktime += $period[0]["endtime"] - $period[0]["starttime"];
			if (count($period) > 0) {
				unset($period[0]);
				foreach($period as $break){
					$breaktime += $break["endtime"] - $break["starttime"];
				}
			}
		}
	}
	return array("worktime" => $worktime-$breaktime, "breaktime" => $breaktime);
}
/********************************************************************
 *
 *	fetchProjects - returns all projects
 *
 *	Incomming:  
 *
 *	Outgoing : array with projects
 * 		
 *		   
 *
 ********************************************************************/ 
function fetchProjects() {
	$sql = "SELECT * FROM projectdb WHERE member_id = {$_SESSION['SESS_MEMBER_ID']} ORDER BY name ASC";
	$result = mysql_query($sql);
	
	if (!$result) return null;
	
	$return = array();
	while($arr = mysql_fetch_assoc($result)){
		$return[] = $arr;	
	}
	return $return;
}
?>