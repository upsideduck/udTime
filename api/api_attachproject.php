<?php 
/************************************************************************
 *
 *	Attach project to period
 *
 *
 *	Requires: 		func_misc.php
 *					func_projects.php
 *					func_start.php
 *					func_end.php
 *					func_weekdb.php
 *	
 *	Post values:	$period_id
 *					$project_id
 *					$action - update, add, newperiod
 *
 *	Output:			$output_xml - $result_arr as xml
 *
 ************************************************************************/
	
$result_arr = null;

//Sanitize the POST values
$project_id = clean($_REQUEST['project_id']);
if($_REQUEST['period_id'] == null) {
	if($_SESSION['SESS_ACTIVE_PERIOD'] != null && $_SESSION['SESS_ACTIVE_TYPE'] != "break"){
		$period_id = $_SESSION['SESS_ACTIVE_PERIOD'];
	}elseif($_SESSION['SESS_ACTIVE_PERIOD'] != null && $_SESSION['SESS_ACTIVE_TYPE'] == "break"){
		$result = mysql_query("SELECT parent_id FROM breakdb WHERE id = {$_SESSION['SESS_ACTIVE_PERIOD']}");
		$period_id = mysql_result($result,0);
	}
}else{
	$period_id = clean($_REQUEST['period_id']);
}

$action = clean($_REQUEST['action']);
switch($action) {
	case("update"):
		$result_arr = attachProject($period_id,$project_id);
		$output->results['attachproject'] = $result_arr;
		break;
	case("add"):
		$result_arr = attachProject($period_id,$project_id);
		$output->results['attachproject'] = $result_arr;
		break;
	case("newperiod"):
		$timestamp = date("U");
		if($_SESSION['SESS_ACTIVE_TYPE'] == "work") {
			$result_arr = endWork("NOCHANGE",$timestamp);
			$output->results['endwork'] = $result_arr;
			if(!$result_arr[0]) break;
			
			$result_arr = goWork("work", "",$timestamp);
			$output->results['newperiod'] = $result_arr;
			if(!$result_arr[0]) break;
			
			$result_arr = attachProject($_SESSION['SESS_ACTIVE_PERIOD'],$project_id);
			$output->results['attachproject'] = $result_arr;
		} elseif ($_SESSION['SESS_ACTIVE_TYPE'] == "break") {
			$result_arr1 = endBreak("NOCHANGE",$timestamp);
			$result_arr2 = endWork("NOCHANGE",$timestamp);
			$result_arr[0] = $result_arr1[0] * $result_arr2[0];
			unset($result_arr1[0]);
			unset($result_arr2[0]);
			foreach ($result_arr1 as $s) {
				$result_arr[] = $s;
			}
			foreach ($result_arr2 as $s) {
				$result_arr[] = $s;
			} 		
			$output->results['endwork'] = $result_arr;
			if(!$result_arr[0]) break;
			
			$result_arr = goWork("work", "",$timestamp);
			$output->results['newperiod'] = $result_arr;
			if(!$result_arr[0]) break;
			
			$result_arr = attachProject($_SESSION['SESS_ACTIVE_PERIOD'],$project_id);
			$output->results['attachproject'] = $result_arr;
		} else {
			$result_arr[0] = false;
			$result_arr[] = 'No active period';
			$output->results['attachproject'] = $result_arr;
		}
		break;
	default:
		$result_arr[0] = false;
		$result_arr[] = 'No action given';
		$output->results['attachproject'] = $result_arr;
		break;
}

 ?>