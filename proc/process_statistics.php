<?php
	//Start session
	session_start();
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_fetch.php');
	require_once('../func/func_misc.php');
	
	$xml_output .= xmlIntro();
	require_once("../api/api_statisitcs.php");
	$xml_output .= xmlOutro();
	
	header('Content-type: text/xml'); 
	echo $xml_output;
	session_write_close();
	exit();

?>