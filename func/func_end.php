<?php
/********************************************************************
 *
 *	endWork - end work
 *			 
 *	Incomming: 	$comment - comment as string 
 *				$timestamp
 *
 *
 ********************************************************************/
function endWork($comment, $timestamp) {  
	$ext = "";
	if($comment != "NOCHANGE") $ext .= ", comment = '$comment'";
   /* Get next increment */
	if ($_SESSION['SESS_ACTIVE_TYPE'] == "work") {
		$qShowStatus = "SHOW TABLE STATUS LIKE 'workdb'";	  	  	
	    $qShowStatusResult 	= mysql_query($qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );
	
	    $row = mysql_fetch_assoc($qShowStatusResult);
	    $next_increment = $row['Auto_increment'];
	  
	    // only allow end if it is after period has started
  	 	$sql_req1 = "SELECT starttime FROM workdb WHERE id = ".$_SESSION['SESS_ACTIVE_PERIOD'];
 		$result_req1 = mysql_query($sql_req1);
	  	$activeWorkInfo = mysql_fetch_array($result_req1);
	  	if($activeWorkInfo['starttime'] > $timestamp) {
	  		$result_arr[0] = false;
	  		$result_arr[] = 'Work cannot end before it starts';
	  		return $result_arr;
	  	}
	  	
	  	// only allow end if it is after last break ends
  		$sql_req2 = "SELECT * FROM breakdb WHERE parent_id = ".$_SESSION['SESS_ACTIVE_PERIOD']." ORDER BY starttime DESC";
  		
  		$nrOfBreaks = 0;
  		
  		if ( $result_req2 = mysql_query($sql_req2) ) {
	  	    while($break = mysql_fetch_array( $result_req2 )) {
		  		if($break['endtime'] == null) {
		  			$result_arr[0] = false;
		  			$result_arr[] = 'Break is still active';
		  			return $result_arr;
		  		} elseif ($break['endtime'] > $timestamp) {
		  			$result_arr[0] = false;
		  			$result_arr[] = 'Work period cannot end before last break has ended';
		  			return $result_arr;
		  		}
		  		$nrOfBreaks++;
	  		}
	  	}
	 	$sql1 = "UPDATE workdb SET member_id = ".$_SESSION['SESS_MEMBER_ID'].", endtime = $timestamp{$ext} WHERE id =".$_SESSION['SESS_ACTIVE_PERIOD'];
	  	$sql2 = "UPDATE userdb SET activeperiod = NULL, activetype = NULL WHERE member_id = ".$_SESSION['SESS_MEMBER_ID'];
	  	
	  	$success1 = mysql_query($sql1);
	  	$success2 = mysql_query($sql2);
	  	
	  	addNewWorkPeriodToBook($_SESSION['SESS_ACTIVE_PERIOD']);
	 	
	 	if ($success1 && $success2) {
	 		$_SESSION['SESS_ACTIVE_PERIOD'] = null;
	  		$_SESSION['SESS_ACTIVE_TYPE'] = null;
	  		$_SESSION['SESS_ACTIVE_PROJECT'] = null;
			$result_arr[0] = true;
		  	$result_arr[] = 'Work ended '.date("H:i:s",$activeWorkInfo['starttime'])." - ".date("H:i:s",$timestamp).", including ".$nrOfBreaks." breaks";
 		} else {
	 		$result_arr[0] = false;
	 		$result_arr[] = 'Something went wrong when ending work';
		}
	} else {
		$result_arr[0] = false;
		$result_arr[] = 'No work period active or possible active break which needs to be quit first $result_req2';
	}
    return $result_arr;
}

/********************************************************************
 *
 *	endBreak - end Break
 *			 
 *	Incomming: 	$comment - comment as string 
 *				$timestamp
 *
 *
 ********************************************************************/
function endBreak($comment, $timestamp) {  
	$ext = "";
	if($comment != "NOCHANGE") $ext .= ", comment = '$comment'";
	
	if($_SESSION['SESS_ACTIVE_TYPE'] == "break"){
		$qShowStatus = "SHOW TABLE STATUS LIKE 'breakdb'";
		$qShowStatusResult = mysql_query($qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );
	
	    $row = mysql_fetch_assoc($qShowStatusResult);
	    $next_increment = $row['Auto_increment'];   
	      
	    $sql = "SELECT * FROM breakdb WHERE id = ".$_SESSION['SESS_ACTIVE_PERIOD'];
	    $resultbreak = mysql_query($sql);
	    $break = mysql_fetch_object($resultbreak); 
	    if($break->starttime > $timestamp) {
	    	$result_arr[0] = false;
	    	$result_arr[] = 'Break cannot end before it starts';
	    	$result_arr[] = 'Break start: '.$break->starttime.' Break end: '.$timestamp;
	    	return $result_arr;
	    }
	      
	    $sql1 = "UPDATE breakdb SET endtime = $timestamp {$ext} WHERE id = ".$_SESSION['SESS_ACTIVE_PERIOD'];
	    $success1 = mysql_query($sql1);

	    if ($success1) {
			$sql2 = "UPDATE userdb SET activeperiod = $break->parent_id, activetype = 'work' WHERE member_id = ".$_SESSION['SESS_MEMBER_ID'];
	    	if($success2 = mysql_query($sql2)){
	    		$_SESSION['SESS_ACTIVE_PERIOD'] = $break->parent_id;
	   			$_SESSION['SESS_ACTIVE_TYPE'] = "work";
   		 		$result_arr[0] = true;
		 		$result_arr[] = 'Break ended';
	    	} else {
	    		$result_arr[0] = false;
		  	 	$result_arr[] = 'Something when updating user status';
	  		}	
	 		
	    } else {
	    	 $result_arr[0] = false;
		  	 $result_arr[] = 'Something when wrong when ending break';
	  	}
	}else {
	 	$result_arr[0] = false;
	  	$result_arr[] = 'Not on break';
    }
	    
    return $result_arr;
    
}
?>