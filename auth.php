<?php
	//Start session
	session_start();
	require_once("includes/config.php");
	
	//Check whether the session variable SESS_MEMBER_ID is present or not 
	if(!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
		header("location: access-denied.php");
		exit();
	}
?>