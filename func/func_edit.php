<?php
/********************************************************************
 *
 *	updatePeriod - manually update period
 *			 
 *	Incomming: $type - work, break …
 *			 : $id - id to update in database $type
 *			 : $newStartTime
 *			 : $newEndTime
 *			 : $newComment
 *			 : $topTime
 *			 : $bottomTime
 *			 : $oldStartTime
 *
 *	Outgoing : $result_arr
 *		key  : 0 - true-> success, false -> error
 *			   1.. - Message
 *		  	
 ********************************************************************/
function updatePeriod($type, $id, $newStartTime, $newEndTime, $newComment, $topTime, $bottomTime, $oldStartTime) {
	$result_arr[0] = true;
	
	//Input Validations
	if($newStartTime == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Start time missing or invalid';
	}
	if($newEndTime == '') {
		$result_arr[0] = false;
		$result_arr[] = 'End time missing or invalid';
	}
	if($type == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Type';
	}
	if($newStartTime > $topTime && $topTime != '') { //only if there are breaks
		$result_arr[0] = false;
		$result_arr[] = 'You cannot start working after your first breaks starts!';
	}
	if($newEndTime < $bottomTime && $topTime != '') {//only if there are breaks
		$result_arr[0] = false;
		$result_arr[] = 'You cannot stop working after your last breaks is finished!';
	}
	if($newEndTime < $newStartTime) {
		$result_arr[0] = false;
		$result_arr[] = 'You cannot end before you start';
	}
	
	if($result_arr[0]) {
		//Create query
		$qry="UPDATE ".$type."db SET starttime = $newStartTime, endtime = $newEndTime, comment = '$newComment', logged = 0 WHERE id = $id";
		$result=mysql_query($qry);
	
		if(!$result) {
			$result_arr[0] = false;
			$result_arr[] = 'Update unsuccessful';
		}
	}
	
	if(count($result_arr) == 1){ 
		$result_arr[] = 'Period successfully updated';
		
		$old_week["week"] = Date("W", $newStartTime);
		$old_week["year"] = Date("y", $newStartTime);
		$new_week["week"] = Date("W", $oldStartTime);
		$new_week["year"] = Date("y", $oldStartTime);
		
	  	timespan::updateStats(new timespan($newStartTime, $newEndTime));
		if($old_week != $new_week) {
			timespan::updateStats(new timespan($oldStartTime, $oldStartTime+1));
		}
		
	}
	
	return $result_arr;
}

/********************************************************************
 *
 *	updateasworktime - manually update asworktime
 *			 
 *		  	
 ********************************************************************/
function updateasworktime($id, $time, $type) {
	$result_arr[0] = true;
	
	//Input Validations
	if($id == '') {
		$result_arr[0] = false;
		$result_arr[] = 'No asworktime period';
	}
	if($time == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Time empty';
	}
	
	if($result_arr[0]) {
		//Create query
		$qry="UPDATE asworktime SET time = {$time}, type = {$type} WHERE id = $id";
		$result=mysql_query($qry);
	
		if(!$result) {
			$result_arr[0] = false;
			$result_arr[] = 'Update unsuccessful';
		}
	}
	
	if(count($result_arr) == 1){ 
		$result_arr[] = 'asworktime successfully updated';
		
		$qry="SELECT date FROM asworktime WHERE id = $id";
		$result=mysql_query($qry);
		$vac = mysql_fetch_object($result);
		
		$vacTime = strtotime($vac->date);
		
	  	timespan::updateStats(new timespan($vacTime, $vacTime+60*60*24+1));
		
	}
	
	return $result_arr;
}

/********************************************************************
 *
 *	updateagainstworktime - manually update againstworktime
 *			 
 *		  	
 ********************************************************************/
function updateagainstworktime($id, $time, $type) {
	$result_arr[0] = true;
	
	//Input Validations
	if($id == '') {
		$result_arr[0] = false;
		$result_arr[] = 'No asworktime period';
	}
	if($time == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Time empty';
	}
	
	if($result_arr[0]) {
		//Create query
		$qry="UPDATE againstworktime SET time = {$time}, type = {$type} WHERE id = $id";
		$result=mysql_query($qry);
	
		if(!$result) {
			$result_arr[0] = false;
			$result_arr[] = 'Update unsuccessful';
		}
	}
	
	if(count($result_arr) == 1){ 
		$result_arr[] = 'againstworktime successfully updated';
		
		$qry="SELECT date FROM againstworktime WHERE id = $id";
		$result=mysql_query($qry);
		$free = mysql_fetch_object($result);
		
		$againstworktime = strtotime($free->date);
		
	  	timespan::updateStats(new timespan($againstworktime, $againstworktime+60*60*24+1));
		
	}
	
	return $result_arr;
}

/********************************************************************
 *
 *	AddPeriod - manually add period
 *			 
 *	Incomming: $type - work, break …
 *			 : $newStartTime
 *			 : $newEndTime
 *			 : $newComment
 *
 *	Outgoing : $result_arr
 *		key  : 0 - true-> success, false -> error
 *			   1.. - Message
 *		  	
 ********************************************************************/
function addPeriod($type,$newStartTime, $newEndTime, $newComment) {
	$result_arr[0] = true;
	//Input Validations
	if($newStartTime == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Start time missing or invalid';
	}
	if($type == '') {
		$result_arr[0] = false;
		$result_arr[] = 'Type';
	}
	if($newEndTime == '') {
		$result_arr[0] = false;
		$result_arr[] = 'End time missing or invalid';
	}
	if($newEndTime < $newStartTime) {
		$result_arr[0] = false;
		$result_arr[] = 'You cannot end before you start';
	}
	if($type == 'break') {
		$qry="SELECT id FROM workdb WHERE starttime <= $newStartTime AND endtime >= $newEndTime AND member_id = ".$_SESSION['SESS_MEMBER_ID']."";
		$result=mysql_query($qry);
		$parent = mysql_fetch_assoc($result);
		if(!$parent){
			$result_arr[0] = false;
			$result_arr[] = 'Break not within any work period';
		}else{
			$parentId = $parent['id'];
		}
	}

	
	if($result_arr[0]) {
		//Create query
		$qShowStatus 		=  "SHOW TABLE STATUS LIKE '".$type."db'";
		$qShowStatusResult 	= mysql_query($qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );
		
		$row = mysql_fetch_assoc($qShowStatusResult);
		$next_increment = $row['Auto_increment'];
		
		if($type == 'break') {
			$insertQry="INSERT INTO breakdb (member_id, starttime, endtime, comment, logged, parent_id) VALUES (".$_SESSION['SESS_MEMBER_ID'].",$newStartTime, $newEndTime, '$newComment', 0, $parentId)";

		}elseif($type == 'work'){
			$insertQry="INSERT INTO workdb (member_id, starttime, endtime, comment, logged) VALUES (".$_SESSION['SESS_MEMBER_ID'].",$newStartTime, $newEndTime, '$newComment', 0)";
		}
		$result=mysql_query($insertQry);
	
		if(!$result) {
			$result_arr[0] = false;
			$result_arr[] = 'Update unsuccessful, database error';
		}
		else {
			$result_arr[0] = true;
			$result_arr[] = ucfirst($type).' successfully added';
			
			//$week = Date("W", $newStartTime);
			//$year = Date("y", $newStartTime);
			timespan::updateStats(new timespan($newStartTime,$newEndTime));
		}
	}
	
	return $result_arr;
}

?>