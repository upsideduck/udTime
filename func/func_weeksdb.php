<?php
/********************************************************************
 *
 *	addNewWorkPeriodToBook - Add period to weeksdb
 *
 *	Incomming: $inPeriod - period id
 *
 *	Outgoing : none 
 *
 ********************************************************************/  
 function addNewWorkPeriodToBook($inPeriod) { 
    $sql = "SELECT * FROM workdb WHERE id = $inPeriod";
    $result = mysql_query($sql);
    $period = mysql_fetch_object($result);
    $periodTime = 0;
    $periodTime = $periodTime + $period->endtime - $period->starttime;
    $periodStartTimestamp = $period->starttime;

	$sql = "SELECT * FROM breakdb WHERE parent_id = $inPeriod";
	$result = mysql_query($sql);
	while ($loopBreak = mysql_fetch_object($result)) {
    	$periodTime = $periodTime - ($loopBreak->endtime - $loopBreak->starttime);  
    }
    
    $year = Date("y", $periodStartTimestamp);
    $week = Date("W", $periodStartTimestamp);
    $day = Date("l", $periodStartTimestamp);
    $day_num = Date("N", $periodStartTimestamp) + 3;
    
    //Check if instance exist in database weeksdb
    $sql = "SELECT * FROM weeksdb WHERE year = $year AND week = $week AND member_id = " . $_SESSION['SESS_MEMBER_ID'];
    $result = mysql_query($sql);
    $row = mysql_fetch_row($result);    

    if($row != null) {
          $row[$day_num] = $row[$day_num]  + $periodTime;
          $total = $row[4]+$row[5]+$row[6]+$row[7]+$row[8]+$row[9]+$row[10];
          $sql = "UPDATE weeksdb SET $day = $row[$day_num], total = $total WHERE id = " . $row[0]; 
          $resultIn = mysql_query($sql);
    } else {
          $sql = "INSERT INTO weeksdb (member_id, year, week, $day, total) VALUES (".$_SESSION['SESS_MEMBER_ID'].", $year, $week, $periodTime, $periodTime)";
          $resultIn = mysql_query($sql);
    }
    
    if ($resultIn) {
           $sql1 = "UPDATE workdb SET logged = 1 WHERE id = $inPeriod";
           $sql2 = "UPDATE breakdb SET logged = 1 WHERE parent_id = $inPeriod";			// fix to one statement someday	
		   mysql_query($sql1);
           mysql_query($sql2);
    }
    
}
/********************************************************************
 *
 *	updateWeekInBook - Update week in weeksdb
 *
 *	Incomming: $year
 *			 : $week
 *
 *	Outgoing : $result_arr 
 *
 ********************************************************************/  
 function updateWeekInBook($week, $year) { 
 	
 	$timeArray = fetchWeekStartEndTime($week, $year);
 	$fetchedPeriodArray = fetchPeriodsArray($timeArray);
 	
 	//Check if instance exist in database weeksdb
 	$sql = "SELECT * FROM weeksdb WHERE year = $year AND week = $week AND member_id = " . $_SESSION['SESS_MEMBER_ID'];
 	$result = mysql_query($sql);
 	$row = mysql_fetch_row($result); 
 	 if($row != null) {
 	 	$sql = "INSERT INTO weeksdb (member_id, year, week, $day, total) VALUES (".$_SESSION['SESS_MEMBER_ID'].", $year, $week, 0, 0)";
 	 	mysql_query($sql);
 	 }
 	
 	$days = array();
 	for ($i = 1; $i < 8; $i++) {
 	$days_totals[$i] = 0;
 		if ($fetchedPeriodArray[1][$i]) {
	 		foreach($fetchedPeriodArray[1][$i] as $periods) {
 				$days_totals[$i] = $days_totals[$i] + $fetchedPeriodArray[0][$periods][0]["endtime"] - $fetchedPeriodArray[0][$periods][0]["starttime"];
 				unset($fetchedPeriodArray[0][$periods][0]);
 				if (count($fetchedPeriodArray[0][$periods]) > 0) {
 					foreach ($fetchedPeriodArray[0][$periods] as $breaks) {
 						$days_totals[$i] = $days_totals[$i] - ($breaks["endtime"] - $breaks["starttime"]);
 					}
 				}
 			}
		}
 	}
 	$all_totals = array_sum($days_totals);
 	$sql = "UPDATE weeksdb SET Monday = $days_totals[1], Tuesday = $days_totals[2], Wednesday = $days_totals[3], Thursday = $days_totals[4], Friday = $days_totals[5], Saturday = $days_totals[6], Sunday = $days_totals[7], total = $all_totals WHERE year = $year AND week = $week AND member_id = ".$_SESSION['SESS_MEMBER_ID'];
	$success = mysql_query($sql);
	if($success) {
		$result_arr[0] = true;
		$result_arr[] = 'Record for w'.$week.' - '.$year.' updated';
	} else {
		$result_arr[0] = false;
		$result_arr[] = 'Weekly record not updated';
	}
	return $result_arr;
 }

/********************************************************************
 *
 *	fetchWeeksSummary - fetch week detail
 *
 *	Incomming: none
 *
 *	Outgoing : $result - result of week query 
 *
 ********************************************************************/  
function fetchWeeksSummary() {
	$sql = "SELECT year,week,total,holiday FROM weeksdb WHERE member_id = " . $_SESSION['SESS_MEMBER_ID'] . " ORDER BY year,week ASC";
	
	$result = mysql_query($sql);
	return $result; 
}
/********************************************************************
 *
 *	updateHoliday - updateHoliday record
 *
 *	Incomming: week, year, timestamp
 *
 *	Outgoing : $result_arr 
 *
 ********************************************************************/  
function updateHoliday($inWeek, $inYear, $inTimestamp) {
	// Start positive
	$result_arr[0] = true;
	// Verify validity of invariables
	if(!is_numeric($inWeek) || $inWeek < 1 || $inWeek > 52 || $inWeek == null) {
		$result_arr[0] = false;
		$result_arr[] = 'Week value not valid';
	}
	if (!is_numeric($inYear) || $inYear < 0 || $inWeek > 99 || $inYear == null) {
		$result_arr[0] = false;
		$result_arr[] = 'Year value not valid';
	}
	if (!is_numeric($inTimestamp) || $inTimestamp < 0) {
		$result_arr[0] = false;
		$result_arr[] = 'Time not valid';
	}
	
	if ($result_arr[0] == true) {
		$test_array = mysql_fetch_row(mysql_query("SELECT EXISTS(SELECT * FROM weeksdb WHERE week = ".$inWeek." AND year = ".$inYear." AND member_id = " . $_SESSION['SESS_MEMBER_ID'].")"));
		if ($test_array[0] == 1) {
			
			$result = mysql_query("UPDATE weeksdb SET holiday = $inTimestamp WHERE year = $inYear AND week = $inWeek AND member_id = ".$_SESSION['SESS_MEMBER_ID']);
			if ($result) {
				$result_arr[0] = true;
				$result_arr[] = 'Holiday/vacation updated';	
			}else {
				$result_arr[0] = false;
				$result_arr[] = 'Something went wrong when updating the database';	
			}
		} else {
			$result = mysql_query("INSERT INTO weeksdb (week, year, member_id, holiday) VALUES ($inWeek, $inYear,".$_SESSION['SESS_MEMBER_ID'].", $inTimestamp)");
			if ($result) {
				$result_arr[0] = true;
				$result_arr[] = 'Holiday/vacation added';	
			}else {
				$result_arr[0] = false;
				$result_arr[] = 'Something went wrong when adding to database';	
			}
		}
	}
	
	return $result_arr; 
}
?>