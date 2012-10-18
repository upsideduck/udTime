<?php
/********************************************************************
 *
 *	removeBreak
 *
 *	Incomming: $id - id of break to remove 
 *
*	Outgoing : $result_arr
*		key  : 0 - true-> success, false -> error
*			   1.. - Message
*		  	
 ********************************************************************/ 
function removeBreak($id) {
	$sql = "SELECT * FROM breakdb WHERE id = $id";
	$result = mysql_query($sql);
	$break = mysql_fetch_object($result);
	
	$sql2 = "DELETE FROM breakdb WHERE id = $id";
	$result2 = mysql_query($sql2);
	if($result2) {
		$result_arr = array(0 => true, 1 => "Break removed");
		updateWeekInBook(date("W", $break->starttime), date("y", $break->starttime));
	} else { 
		$result_arr = array(0 => false, 1 => "Break not removed");
	}
	return $result_arr;
}

/********************************************************************
 *
 *	removeWork
 *
 *	Incomming: $id - id of work 
 *
*	Outgoing : $result_arr
*		key  : 0 - true-> success, false -> error
*			   1.. - Message
*		  	
 ********************************************************************/ 
function removeWork($id) {
	$sql = "SELECT * FROM workdb WHERE id = $id";
	$result = mysql_query($sql);
	$work = mysql_fetch_object($result);
	
	$sql2 = "DELETE FROM workdb WHERE id = $id";
	$result2 = mysql_query($sql2);
	$sql3 = "DELETE FROM breakdb WHERE parent_id = $id";
	$result3 = mysql_query($sql3);	
	
	if($result2 && $result3) {
		$result_arr = array(0 => true, 1 => "Work removed");
		$result_arr[] = "Breaks removed";
		updateWeekInBook(date("W", $work->starttime), date("y", $work->starttime));
	} elseif ($result2 && !$result3) { 
		$result_arr = array(0 => true, 1 => "Work removed");
		$result_arr[] = "Breaks not removed";
		updateWeekInBook(date("W", $work->starttime), date("y", $work->starttime));
	} elseif (!$result2 && $result3) { 
		$result_arr = array(0 => false, 1 => "Work not removed");
		$result_arr[] = "Breaks removed";
	} elseif (!$result2 && !$result3) { 
		$result_arr = array(0 => false, 1 => "Work not removed");
		$result_arr[] = "Breaks not removed";
	}
	return $result_arr;
}
/********************************************************************
 *
 *	removeFreeDay
 *
 *	Incomming: $id - id of freeDay 
 *
*	Outgoing : $result_arr
*		key  : 0 - true-> success, false -> error
*			   1.. - Message
*		  	
 ********************************************************************/ 
function removeFreeDay($id) {
	$result_arr[0] = true;
	$sql = "SELECT * FROM timeoff WHERE id = $id";
	$result1 = mysql_query($sql);
	$timeoff = mysql_fetch_object($result1);

	if($timeoff){
	
		$sql2 = "DELETE FROM timeoff WHERE id = $id";
		$result2 = mysql_query($sql2);
		if($result2){
				
			$month = date("m",strtotime($timeoff->date));
			$week = date("W",strtotime($timeoff->date));
			$year = date("Y",strtotime($timeoff->date));
			
			if(timespan::updateWeekStats($year,$week) == false){
				$result_arr[0] = false;
				$result_arr[] = "Week stats not updated";
			}
			
			if(timespan::updateMonthStats($year,$month) == false){
				$result_arr[0] = false;
				$result_arr[] = "Month stats not updated";
			}

			if($result_arr[0]) $result_arr[] = "Freeday removed";
			
		}else{
			$result_arr[0] = false;
			$result_arr[] = "Freeday not deletet";
		}
	}else{
		$result_arr[0] = false;
		$result_arr[] = "Freeday not found";
	}
	return $result_arr;
}

/********************************************************************
 *
 *	removeVacationDay
 *
 *	Incomming: $id - id of vacationDay 
 *
*	Outgoing : $result_arr
*		key  : 0 - true-> success, false -> error
*			   1.. - Message
*		  	
 ********************************************************************/ 
function removeVacationDay($id) {

	$result_arr[0] = true;
	$sql = "SELECT * FROM vacationdays WHERE id = $id";
	$result1 = mysql_query($sql);
	$vacationday = mysql_fetch_object($result1);
	if($vacationday){
		
		$sql2 = "DELETE FROM vacationdays WHERE id = $id";
		$result2 = mysql_query($sql2);
		if($result2){
				
			$month = date("m",strtotime($vacationday->date));
			$week = date("W",strtotime($vacationday->date));
			$year = date("Y",strtotime($vacationday->date));
			
			if(timespan::updateWeekStats($year,$week) == false){
				$result_arr[0] = false;
				$result_arr[] = "Week stats not updated";
			}
			
			if(timespan::updateMonthStats($year,$month) == false){
				$result_arr[0] = false;
				$result_arr[] = "Month stats not updated";
			}
			
			if($result_arr[0]) $result_arr[] = "Vacation day removed";
			
		}else{
			$result_arr[0] = false;
			$result_arr[] = "Vacation day not deletet";
		}
	}else{
		$result_arr[0] = false;
		$result_arr[] = "Vacation day not found";
	}
	return $result_arr;
}
?>