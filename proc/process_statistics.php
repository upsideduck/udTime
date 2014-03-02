<?php
	//Start session
	session_start();
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_fetch.php');
	require_once('../func/func_misc.php');
	require_once('../func/func_stats.php');
	
	$output = new output();
	require_once("../api/api_statisitcs.php");

	header('Content-type: text/xml'); 
	echo  $output->outputToXml();
	session_write_close();
	exit();

?>