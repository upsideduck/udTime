<?php
/********************************************************************
 *
 *	goWork - add new period to database
 *			 
 *	Incomming: 	$type - Type of period as string
 *				$comment - comment as string 
 *				$timestamp
 *
 *	Outgoing : $success - bool
 *
 ********************************************************************/
function goWork($type, $comment, $timestamp) {
	  /* Get next increment */
	  $qShowStatus 		=  "SHOW TABLE STATUS LIKE 'workdb'";
	  $qShowStatusResult 	= mysql_query($qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );
	
	  $row = mysql_fetch_assoc($qShowStatusResult);
	  $next_increment = $row['Auto_increment'];
	  if($type == "work" && $_SESSION['SESS_ACTIVE_PERIOD'] == "")
	  {
		  
		  $sql = "INSERT INTO workdb (member_id, starttime, comment) VALUES (".$_SESSION['SESS_MEMBER_ID'].", $timestamp, '$comment')";
		  $success1 = mysql_query($sql);
		  $success2 = mysql_query("UPDATE userdb SET activeperiod = $next_increment, activetype = '$type' WHERE member_id = ".$_SESSION['SESS_MEMBER_ID']);
		  
		  $_SESSION['SESS_ACTIVE_PERIOD'] = $next_increment;
		  $_SESSION['SESS_ACTIVE_TYPE'] = $type;
		  
		  if ($success1 && $success2) {
		 	 $result_arr[0] = true;
		 	 $result_arr[] = 'Work period started';
		  } else {
		  	 $result_arr[0] = false;
		  	 $result_arr[] = 'Something went wrong with the db';
		  }
	  }elseif($_SESSION['SESS_ACTIVE_PERIOD'] != ""){
	  	  $result_arr[0] = false;
	 	  $result_arr[] = 'Already working';
	  }elseif($type != "work"){
	  	  $result_arr[0] = false;
	  	  $result_arr[] = 'Type called not valid';
	  }else{
	      $result_arr[0] = false;
	  	  $result_arr[] = 'Something went wrong';
	  }
	  return $result_arr;
  }
  
/********************************************************************
 *
 *	goOnBreak - add new break to database
 *			 
 *	Incomming: 	$comment - comment as string 
 *				$timestamp
 *
 *	Outgoing : $success - bool
 *
 ********************************************************************/
function goOnBreak($comment, $timestamp) {  
    
	$qShowStatus = "SHOW TABLE STATUS LIKE 'breakdb'";

    $qShowStatusResult 	= mysql_query($qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );

    $row = mysql_fetch_assoc($qShowStatusResult);
    $next_increment = $row['Auto_increment'];
    
    $savedActivePeriod = $_SESSION['SESS_ACTIVE_PERIOD'] ;
    $savedActiveType = $_SESSION['SESS_ACTIVE_TYPE'] ;

	// only allow break if it starts after period has started
	$sql0 = "SELECT starttime FROM ".$savedActiveType."db WHERE id = ".$savedActivePeriod;
	$result0 = mysql_query($sql0);
	$savedActivePeriodInfo = mysql_fetch_array($result0);
	if($savedActivePeriodInfo['starttime'] > $timestamp) {
		$result_arr[0] = false;
		$result_arr[] = 'Break cannot start before actual period';
		return $result_arr;
	}
	
	if(date("U") < $timestamp) {
		$result_arr[0] = false;
		$result_arr[] = 'Break cannot be set to start in the future';
		return $result_arr;
	}
	
	
    $sql1 = "INSERT INTO breakdb (member_id, parent_id, starttime) VALUES (".$_SESSION['SESS_MEMBER_ID'].", ".$_SESSION['SESS_ACTIVE_PERIOD'].", $timestamp)";
    $sql2 = "UPDATE userdb SET activeperiod = $next_increment, activetype = 'break' WHERE member_id = ".$_SESSION['SESS_MEMBER_ID'];
	$sql3 = "UPDATE ".$savedActiveType."db SET comment = '$comment' WHERE member_id = ".$_SESSION['SESS_MEMBER_ID'] ." AND id =  $savedActivePeriod";
    $_SESSION['SESS_ACTIVE_PERIOD'] = $next_increment;
    $_SESSION['SESS_ACTIVE_TYPE'] = "break";
            
    $success1 = mysql_query($sql1);
    $success2 = mysql_query($sql2);
   
    if ($success1 && $success2) {
    	 $result_arr[0] = true;
    	 $result_arr[] = 'Break started';
    } else {
    	 $result_arr[0] = false;
    	 $result_arr[] = 'Something when wrong';
    }
    return $result_arr;
}
?>