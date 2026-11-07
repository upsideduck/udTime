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
	include(__SITE_BASE__."/includes/connection.php");
	// Check if already synced, in that case, just continue with successfull result
	$sqlSyncCheck = "SELECT * FROM workdb WHERE starttime = {$timestamp} AND  member_id = ".$_SESSION['SESS_MEMBER_ID'];
	$resultSyncCheck = mysqli_query($link, $sqlSyncCheck);
	if (mysqli_num_rows($resultSyncCheck) > 0) {
		$result_arr[0] = true;	 
		$result_arr[] = 'Work period already synced';
		return $result_arr;
	}
	
	
	/* Get next increment */
	$qShowStatus 		=  "SHOW TABLE STATUS LIKE 'workdb'";
	$qShowStatusResult 	= mysqli_query($link, $qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );
	
	$row = mysqli_fetch_assoc($qShowStatusResult);
	$next_increment = $row['Auto_increment'];
	if($type == "work" && $_SESSION['SESS_ACTIVE_PERIOD'] == "")
	{
		
		$sql = "INSERT INTO workdb (member_id, starttime, comment) VALUES (".$_SESSION['SESS_MEMBER_ID'].", $timestamp, '$comment')";
		$success1 = mysqli_query($link, $sql);
		$success2 = mysqli_query($link, "UPDATE userdb SET activeperiod = $next_increment, activetype = '$type' WHERE member_id = ".$_SESSION['SESS_MEMBER_ID']);
		
		$_SESSION['SESS_ACTIVE_PERIOD'] = $next_increment;
		$_SESSION['SESS_ACTIVE_TYPE'] = $type;
		
		if ($success1 && $success2) {
			$result_arr[0] = true;
			$result_arr[] = 'Work period started';
			//messageRedis();
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
	mysqli_close($link);
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
	include(__SITE_BASE__."/includes/connection.php");
	// Check if already synced, in that case, just continue with successfull result
	$sqlSyncCheck = "SELECT * FROM breakdb WHERE starttime = {$timestamp} AND  member_id = ".$_SESSION['SESS_MEMBER_ID'];
	$resultSyncCheck = mysqli_query($link, $sqlSyncCheck);
	if (mysqli_num_rows($resultSyncCheck) > 0) {
		$result_arr[0] = true;	 
		$result_arr[] = 'Break period already synced';
		return $result_arr;
	}
	
	$qShowStatus = "SHOW TABLE STATUS LIKE 'breakdb'";
	
    $qShowStatusResult 	= mysqli_query($link, $qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );
	
    $row = mysqli_fetch_assoc($qShowStatusResult);
    $next_increment = $row['Auto_increment'];
    
    $savedActivePeriod = $_SESSION['SESS_ACTIVE_PERIOD'] ;
    $savedActiveType = $_SESSION['SESS_ACTIVE_TYPE'] ;
	
	// only allow break if it starts after period has started
	$sql0 = "SELECT starttime FROM ".$savedActiveType."db WHERE id = ".$savedActivePeriod;
	$result0 = mysqli_query($link, $sql0);
	$savedActivePeriodInfo = mysqli_fetch_array($result0);
	if($savedActiveType == "break"){
		$result_arr[0] = false;
		$result_arr[] = 'Cannot break from a break';
		return $result_arr;
	}
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
	
	
    $success1 = mysqli_query($link, $sql1);
    $success2 = mysqli_query($link, $sql2);
    $success3 = mysqli_query($link, $sql3);
	
    if ($success1 && $success2 && $success3) {
		$result_arr[0] = true;
		$result_arr[] = "Break started";
		$_SESSION['SESS_ACTIVE_PERIOD'] = $next_increment;		// COULD BE AN ISSUE!!!!!!!!
		$_SESSION['SESS_ACTIVE_TYPE'] = "break";
		//messageRedis();
    } else {
		$result_arr[0] = false;
		$result_arr[] = 'Something when wrong';
    }
	mysqli_close($link);
    return $result_arr;
}
?>