<?php
/********************************************************************
 *
 *	addProject - Add project to projects db
 *
 *	Incomming: $pname
 *
 *	Outgoing : $result_arr
 *		key  : 0 - true-> success, false -> error
 *			   1.. - Message
 *
 ********************************************************************/ 
function addProject($pname) {
	
	if($pname == null) {
		$result_arr[0] = false;
		$result_arr[] = 'No project name given';
		return $result_arr;
	}
	
	$sql = "INSERT INTO projectdb (member_id, name) VALUES (".$_SESSION['SESS_MEMBER_ID'].", '$pname')";
	$success1 = mysql_query($sql);
	
	if ($success1) {
		$result_arr[0] = true;
		$result_arr[] = 'Project added';
		$result_arr[] = mysql_insert_id();
		$result_arr[] = $pname;
	} else {
	  	$result_arr[0] = false;
		$result_arr[] = 'Something went wrong with the db';
	}

	return $result_arr;
}
/********************************************************************
 *
 *	attachProject - Add project to period
 *
 *	Incomming: $period_id,$project_id
 *
 *	Outgoing : $result_arr
 *		key  : 0 - true-> success, false -> error
 *			   1.. - Message
 *
 ********************************************************************/ 
function attachProject($period_id,$project_id) {
	
	if($project_id == null) {
		$result_arr[0] = false;
		$result_arr[] = 'No project given';
		return $result_arr;
	}elseif($period_id == null){
		$result_arr[0] = false;
		$result_arr[] = 'No period given';
		return $result_arr;
	}
		
	if($project_id == "none"){
		$sql1 = "UPDATE workdb SET project_id = NULL WHERE id = {$period_id}";

	}else{
		$indb = mysql_query("SELECT name FROM projectdb WHERE id = {$project_id} AND member_id = {$_SESSION['SESS_MEMBER_ID']}");
		$rows = mysql_num_rows($indb);
		if($rows == 0){
			$result_arr[0] = false;
			$result_arr[] = 'No project found with that id';
			return $result_arr;
		} else {
			$pname = mysql_result($indb, 0);
		}
		$sql1 = "UPDATE workdb SET project_id = {$project_id} WHERE id = {$period_id}";
	}
	
	$success1 = mysql_query($sql1);
	
	if ($success1 && $project_id != "none") {
		$result_arr[0] = true;
		$result_arr[] = 'Project '.$pname.' set to period';
	} elseif($success1 && $project_id == "none") {
		$result_arr[0] = true;
		$result_arr[] = 'Project removed';
	} else {
	  	$result_arr[0] = false;
		$result_arr[] = 'Something went wrong with the db';
	}

	return $result_arr;
}
?>