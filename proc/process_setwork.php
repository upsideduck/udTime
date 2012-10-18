<?php
	//Start session
	session_start();
	
	require_once('../auth.php');
		
	//Include database connection details
	require_once('../includes/config.php');
	require_once('../func/func_misc.php');
	require_once('../func/func_edit.php');
	require_once('../func/func_fetch.php');	
	
	
	//Sanitize the POST values
    $starttime = strtotime(clean($_REQUEST["start_time"]));
    $endtime = strtotime(clean($_REQUEST["end_time"]));
    $comment = clean($_REQUEST["comment"]);    
  
    $xml_output .= xmlIntro();
	$xml_output .= resultXMLoutput(addPeriod("work", $starttime, $endtime, $comment), "setwork");
	$xml_output .= xmlOutro();
	
	header('Content-type: text/xml'); 
	echo $xml_output;
 	
 	session_write_close();
?>