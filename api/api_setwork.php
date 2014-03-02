<?php 
/************************************************************************
/*
/*	Adds a asworktime
/*
/*
/*	Requires: 			
/*	
/*	Post values:	
/*
/*	Output:			
/*
/************************************************************************/


//Sanitize the POST values
$starttime = strtotime(clean($_REQUEST["start_time"]));
$endtime = strtotime(clean($_REQUEST["end_time"]));
$comment = clean($_REQUEST["comment"]);    
  
	
$output->results['setwork'] = addPeriod("work", $starttime, $endtime, $comment);

?>