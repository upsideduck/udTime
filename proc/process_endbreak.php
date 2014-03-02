<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_end.php');
	require_once('../func/func_misc.php');
	

	$output = new output();
	require_once("../api/api_endbreak.php");

	header('Content-type: text/xml'); 
	echo  $output->outputToXml();

    session_write_close();
    exit();
?>