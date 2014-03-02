<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_misc.php');
	
	messageRedis();
	
	
?>