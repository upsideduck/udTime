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
?>