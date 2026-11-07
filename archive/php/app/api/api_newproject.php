<?php 
/************************************************************************
 *
 *	Add new project
 *
 *
 *	Requires: 		func_misc.php
 *					func_projects.php
 *	
 *	Post values:	
 *
 *	Output:			$output_xml - $result_arr as xml
 *
 ************************************************************************/

require_once("../func/func_projects.php");
	
$result_arr = null;

//Sanitize the POST values
$pname = clean($_REQUEST['pname']);

$result_arr = addProject($pname);
$output->results['newproject'] = $result_arr;

 ?>