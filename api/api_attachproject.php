<?php 
/************************************************************************
 *
 *	Attach project to period
 *
 *
 *	Requires: 		func_misc.php
 *					func_projects.php
 *	
 *	Post values:	$period_id, $project_id
 *
 *	Output:			$output_xml - $result_arr as xml
 *
 ************************************************************************/

require_once("../func/func_projects.php");
	
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

$result_arr = attachProject($period_id,$project_id);
$xml_output .= resultXMLoutput($result_arr, "attachproject");

 ?>