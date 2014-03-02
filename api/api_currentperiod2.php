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

$result_arr = null;
$current_arr  = null;

if($_SESSION['SESS_ACTIVE_TYPE'] != "") {
	if($_SESSION['SESS_ACTIVE_TYPE'] == "break"){
		$sql = "SELECT parent_id FROM breakdb WHERE id = ".$_SESSION['SESS_ACTIVE_PERIOD'];
		$result = mysql_query($sql);
		$row = mysql_fetch_row($result);
		$workid = $row[0];	
	}elseif($_SESSION['SESS_ACTIVE_TYPE'] == "work"){
		$workid = $_SESSION['SESS_ACTIVE_PERIOD'];	
	}else{
		$workid = 0;
	}
	$result_arr[0] = true;
	$result_arr[] = "Period active";
	
	$sql2 = "SELECT id,modified,type,starttime,endtime
			  FROM
			   (
			     SELECT id,modified,'work' AS 'type',starttime,endtime
			       FROM workdb
			       where id = {$workid}
			      GROUP BY id
			   UNION ALL
			     SELECT id,modified,'break' AS 'type',starttime,endtime
			       FROM breakdb
			       WHERE parent_id= {$workid}
			      GROUP BY id
			   )a
			GROUP BY id
			ORDER BY modified DESC";
	
	$result2 = mysql_query($sql2);
	$lastModified = 0;
	while($arr = mysql_fetch_assoc($result2)){
		$current_arr[$arr["type"]."_".$arr["id"]] = $arr;
		$timestamp = strtotime($arr["modified"]);
		if($timestamp > $lastModified) $lastModified = $timestamp;
	}
	
	$rarray = array("member_id" => $_SESSION['SESS_MEMBER_ID'], "type" => $_SESSION['SESS_ACTIVE_TYPE'], "current_id" => $_SESSION['SESS_ACTIVE_PERIOD'],"workid" => $workid, "lastmodified" => $lastModified);		// We need this for main form to now current user id for event control

	$output->arrays['info'] = $rarray;
	$output->arrays['current'] = $current_arr;
	
}else{
	$result_arr[0] = false;
	$result_arr[] = "No active period";
	
	$sql3 = "SELECT IF(a.modified > b. modified, a.modified, b.modified) AS lastmodified
				FROM (
				
				SELECT id, MAX( modified ) modified
				FROM workdb
				WHERE member_id = ".$_SESSION['SESS_MEMBER_ID']."
				) AS a, (
				
				SELECT id, MAX( modified ) modified
				FROM breakdb
				WHERE member_id = ".$_SESSION['SESS_MEMBER_ID']."
				) AS b";
				
	$result3 = mysql_query($sql3);
	$arr = mysql_fetch_assoc($result3);
	$rarray = array("member_id" => $_SESSION['SESS_MEMBER_ID'], "lastmodified" => strtotime($arr['lastmodified']), "workweek" => $user->dworkweek);		// We need this for main form to know current user id for event control

	$output->arrays['info'] = $rarray;

}

$output->results['currentperiod'] = $result_arr;

?>