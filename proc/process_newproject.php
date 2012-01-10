<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_misc.php');
	
	$xml_output .= xmlIntro();
	require_once("../api/api_newproject.php");
	$xml_output .= xmlOutro();
	
	echo $xml_output;
 	
 	session_write_close();
 	exit();
?>