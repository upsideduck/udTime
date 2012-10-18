<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
	
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_misc.php');
		
	$xml_output .= xmlIntro();
	require_once("../api/api_setvacation.php");
	$xml_output .= xmlOutro();
	
	header('Content-type: text/xml'); 
	echo $xml_output;
 	
 	session_write_close();
 	//header("location: ../edit.php?&type=".$_REQUEST['type']."&w=".$_REQUEST['week']."&y=".$_REQUEST['year']."&a=".$_REQUEST['a']);
 	
?>