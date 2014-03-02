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
		timespan::updateStats(new timespan($break->starttime,$break->endtime));
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
	$numberDelWork = mysql_affected_rows();
	$sql3 = "DELETE FROM breakdb WHERE parent_id = $id";
	$result3 = mysql_query($sql3);	
	$numberDelBreak = mysql_affected_rows();
	
	timespan::updateStats(new timespan($work->starttime,$work->endtime));
		
	if($result2 && $result3) {
		$result_arr = array(0 => true, 1 => $numberDelWork." work periods removed");
		$result_arr[] = $numberDelBreak." break periods removed";
	} elseif ($result2 && !$result3) { 
		$result_arr = array(0 => true, 1 => $numberDelWork." work periods removed");
		$result_arr[] = "Breaks not removed";
	} elseif (!$result2 && $result3) { 
		$result_arr = array(0 => false, 1 => "Work not removed");
		$result_arr[] = $numberDelBreak." break periods removed";
	} elseif (!$result2 && !$result3) { 
		$result_arr = array(0 => false, 1 => "Work not removed");
		$result_arr[] = "Breaks not removed";
	}
	return $result_arr;
}
/********************************************************************
 *
 *	removeagainstworktime
 *
 *	Incomming: $id - id of againstworktime 
 *
*	Outgoing : $result_arr
*		key  : 0 - true-> success, false -> error
*			   1.. - Message
*		  	
 ********************************************************************/ 
function removeagainstworktime($id) {
	$result_arr[0] = true;
	$sql = "SELECT * FROM againstworktime WHERE id = $id";
	$result1 = mysql_query($sql);
	$againstworktime = mysql_fetch_object($result1);

	if($againstworktime){
	
		$sql2 = "DELETE FROM againstworktime WHERE id = $id";
		$result2 = mysql_query($sql2);
		if($result2){
				
			$month = date("m",strtotime($againstworktime->date));
			$week = date("W",strtotime($againstworktime->date));
			$year = date("Y",strtotime($againstworktime->date));
			
			if(timespan::updateWeekStats($year,$week) == false){
				$result_arr[0] = false;
				$result_arr[] = "Week stats not updated";
			}
			
			if(timespan::updateMonthStats($year,$month) == false){
				$result_arr[0] = false;
				$result_arr[] = "Month stats not updated";
			}

			if($result_arr[0]) $result_arr[] = "againstworktime removed";
			
		}else{
			$result_arr[0] = false;
			$result_arr[] = "againstworktime not deletet";
		}
	}else{
		$result_arr[0] = false;
		$result_arr[] = "againstworktime not found";
	}
	return $result_arr;
}

/********************************************************************
 *
 *	removeasworktime
 *
 *	Incomming: $id - id of asworktime 
 *
*	Outgoing : $result_arr
*		key  : 0 - true-> success, false -> error
*			   1.. - Message
*		  	
 ********************************************************************/ 
function removeasworktime($id) {

	$result_arr[0] = true;
	$sql = "SELECT * FROM asworktime WHERE id = $id";
	$result1 = mysql_query($sql);
	$asworktime = mysql_fetch_object($result1);
	if($asworktime){
		
		$sql2 = "DELETE FROM asworktime WHERE id = $id";
		$result2 = mysql_query($sql2);
		if($result2){
				
			$month = date("m",strtotime($asworktime->date));
			$week = date("W",strtotime($asworktime->date));
			$year = date("Y",strtotime($asworktime->date));
			
			if(timespan::updateWeekStats($year,$week) == false){
				$result_arr[0] = false;
				$result_arr[] = "Week stats not updated";
			}
			
			if(timespan::updateMonthStats($year,$month) == false){
				$result_arr[0] = false;
				$result_arr[] = "Month stats not updated";
			}
			
			if($result_arr[0]) $result_arr[] = "asworktime day removed";
			
		}else{
			$result_arr[0] = false;
			$result_arr[] = "asworktime day not deletet";
		}
	}else{
		$result_arr[0] = false;
		$result_arr[] = "asworktime day not found";
	}
	return $result_arr;
}
?>