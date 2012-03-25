<?php
	//Start session
	session_start();
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_session.php');
	require_once('../func/func_misc.php');
	require_once('../func/func_user.php');
	
	require_once("../api/api_login.php");
	
	echo $xml_output;
	session_write_close();
	exit();

?>