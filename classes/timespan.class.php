<?php

require_once(__SITE_BASE__."func/func_fetch.php");

class timespan {
	public $starttime;
	public $endtime;

	function __construct($st,$et){
		if(is_numeric($st))	$this->starttime = $st;
		else $this->starttime = strtotime($st);
		if(is_numeric($et)) $this->endtime = $et;
		else $this->endtime = strtotime($et);
	}

	function getSeconds(){
		return $this->endtime - $this->starttime;
	}
	
	function weeksInSpan(){
		$weeks = array();
		$localStarttime = $this->starttime;
		
		while ($localStarttime < $this->endtime) {  
			$weeks[] = array("year" => date('Y', $localStarttime), "week" => date('W', $localStarttime)); 
			$localStarttime += strtotime('+1 week', 0);
		}
		return $weeks;
	}
	
	function monthsInSpan(){
		$months = array();
		$localStarttime = $this->starttime;
		while ($localStarttime < $this->endtime) {  
			$months[] = array("year" => date('Y', $localStarttime), "month" => date('m', $localStarttime)); 
			$localStarttime += strtotime('+1 month', 0);
		}
		return $months;
	}
	
	function daysInSpan(){
		$days = array();
		$localStarttime = $this->starttime;
		
		while ($localStarttime < $this->endtime) {  
			$days[] = date("Y-m-d", $localStarttime); 
			$localStarttime += strtotime('+1 days', 0);
		}
		return $days;
	}
	
	function getWorkingDays(){
	    // do strtotime calculations just once
	    $startDate = $this->starttime;
	    $endDate = $this->endtime;
	
	
	    //The total number of days between the two dates. We compute the no. of seconds and divide it to 60*60*24
	    //We add one to inlude both dates in the interval.
	    $days = ($endDate - $startDate) / 86400 + 1;
	
	    $no_full_weeks = floor($days / 7);
	    $no_remaining_days = fmod($days, 7);
	
	    //It will return 1 if it's Monday,.. ,7 for Sunday
	    $the_first_day_of_week = date("N", $startDate);
	    $the_last_day_of_week = date("N", $endDate);
	
	    //---->The two can be equal in leap years when february has 29 days, the equal sign is added here
	    //In the first case the whole interval is within a week, in the second case the interval falls in two weeks.
	    if ($the_first_day_of_week <= $the_last_day_of_week) {
	        if ($the_first_day_of_week <= 6 && 6 <= $the_last_day_of_week) $no_remaining_days--;
	        if ($the_first_day_of_week <= 7 && 7 <= $the_last_day_of_week) $no_remaining_days--;
	    }
	    else {
	        // (edit by Tokes to fix an edge case where the start day was a Sunday
	        // and the end day was NOT a Saturday)
	
	        // the day of the week for start is later than the day of the week for end
	        if ($the_first_day_of_week == 7) {
	            // if the start date is a Sunday, then we definitely subtract 1 day
	            $no_remaining_days--;
	
	            if ($the_last_day_of_week == 6) {
	                // if the end date is a Saturday, then we subtract another day
	                $no_remaining_days--;
	            }
	        }
	        else {
	            // the start date was a Saturday (or earlier), and the end date was (Mon..Fri)
	            // so we skip an entire weekend and subtract 2 days
	            $no_remaining_days -= 2;
	        }
	    }
	
	    //The no. of business days is: (number of weeks between the two dates) * (5 working days) + the remainder
	    //---->february in none leap years gave a remainder of 0 but still calculated weekends between first and last day, this is one way to fix it
	   $workingDays = $no_full_weeks * 5;
	    if ($no_remaining_days > 0 )
	    {
	      $workingDays += $no_remaining_days;
	    }
	
	   
	    return $workingDays;
	}
	
	function setagainstworktime($time = 0, $type = 1){
		
		if($time == 0) {			//if no time for day set, use standard workday
			$luser = mysql_fetch_object(mysql_query("SELECT dworkweek,daysinworkweek FROM userdb WHERE member_id = ". $_SESSION['SESS_MEMBER_ID']));
			$time = $luser->dworkweek/$luser->daysinworkweek; 
		}
		
		$days = $this->daysInSpan();
		foreach($days as $day){
			if(date('N', strtotime($day)) < 6){
				if(mysql_num_rows(mysql_query("SELECT id FROM againstworktime WHERE date = '{$day}' AND member_id = " . $_SESSION['SESS_MEMBER_ID'])) == 0){
					//if(mysql_num_rows(mysql_query("SELECT id FROM asworktime WHERE date = '{$day}' AND member_id = " . $_SESSION['SESS_MEMBER_ID'])) == 0){
						$resultarray[] = array(true, $day,"Day now set as day off");
						mysql_query("INSERT INTO againstworktime (member_id, date, time, type) VALUES (".$_SESSION['SESS_MEMBER_ID'].",'{$day}','{$time}', {$type})");
					//} else {
					//	$resultarray[] = array(false, $day,"Day marked as asworktime day");
					//}	
				} else {
					$resultarray[] = array(false, $day,"Already marked as day off");
				}
			}else{
				$resultarray[] = array(false, $day,"Weekend");
			}
			
		}	
		self::updateStats($this);
		return $resultarray;
	}
	
	function setasworktime($time = 0, $type = 1){
		
		if($time == 0) {			//if no time for day set, use standard workday
			$luser = mysql_fetch_object(mysql_query("SELECT dworkweek,daysinworkweek FROM userdb WHERE member_id = ". $_SESSION['SESS_MEMBER_ID']));
			$time = $luser->dworkweek/$luser->daysinworkweek; 
		}
		$days = $this->daysInSpan();
		foreach($days as $day){
			if(date('N', strtotime($day)) < 6){
				if(mysql_num_rows(mysql_query("SELECT id FROM asworktime WHERE date = '{$day}' AND member_id = " . $_SESSION['SESS_MEMBER_ID'])) == 0){
					//if(mysql_num_rows(mysql_query("SELECT id FROM againstworktime WHERE date = '{$day}' AND member_id = " . $_SESSION['SESS_MEMBER_ID'])) == 0){
						$resultarray[] = array(true, $day,"Day now set as asworktime");
						mysql_query("INSERT INTO asworktime (member_id, date, time, type) VALUES (".$_SESSION['SESS_MEMBER_ID'].",'{$day}','{$time}',{$type})");
					//} else {
					//	$resultarray[] = array(false, $day,"Day is already marked as a day off");
					//}	
				} else {
					$resultarray[] = array(false, $day,"Day already marked as asworktime");
				}
			}else{
				$resultarray[] = array(false, $day,"Weekend");
			}
			
		}

		self::updateStats($this);
		return $resultarray;
	}
	
	function getagainstworktime(){
		$startdate = date("Y-m-d", $this->starttime);
		$enddate = date("Y-m-d", $this->endtime);
		$resultarray = array('sum'=>0, 'days'=>array());
		$result = mysql_query("SELECT a.*, lookup.name AS typelabel FROM againstworktime a INNER JOIN lookup ON a.type = lookup.code WHERE a.member_id = {$_SESSION['SESS_MEMBER_ID']} AND a.date BETWEEN '{$startdate}' AND '{$enddate}' AND lookup.type = 'againstworktime' ORDER BY a.date ASC");
		while($row = mysql_fetch_array($result)) {
			$resultarray['sum'] += $row['time'];
			$resultarray['days'][$row['date']]['id'] = $row['id'];
			$resultarray['days'][$row['date']]['timestamp'] = $row['time'];
			$resultarray['days'][$row['date']]['time'] = timestampToTime($row['time']);
			$resultarray['days'][$row['date']]['type'] = $row['type'];
			$resultarray['days'][$row['date']]['typelabel'] = $row['typelabel'];
		}
		return $resultarray;
	}

	function getasworktime(){
		$startdate = date("Y-m-d", $this->starttime);
		$enddate = date("Y-m-d", $this->endtime);
		$resultarray = array('sum'=>0, 'days'=>array());
		$result = mysql_query("SELECT a.*, lookup.name AS typelabel FROM asworktime a INNER JOIN lookup ON a.type = lookup.code WHERE a.member_id = {$_SESSION['SESS_MEMBER_ID']} AND a.date BETWEEN '{$startdate}' AND '{$enddate}' AND lookup.type = 'asworktime' ORDER BY a.date ASC");
		while($row = mysql_fetch_array($result)) {
			$resultarray['sum'] += $row['time'];
			$resultarray['days'][$row['date']]['id'] = $row['id'];
			$resultarray['days'][$row['date']]['timestamp'] = $row['time'];
			$resultarray['days'][$row['date']]['time'] = timestampToTime($row['time']);
			$resultarray['days'][$row['date']]['type'] = $row['type'];
			$resultarray['days'][$row['date']]['typelabel'] = $row['typelabel'];
		}
		return $resultarray;
	}
	
	
	function getWeekStats(){
		$startyear = date("Y", $this->starttime);
		$startweek = date("W", $this->starttime);
		$endyear = date("Y", $this->endtime);
		$endweek = date("W", $this->endtime-60*60); //Added -60*60 to compensate for summertime
		$result = mysql_query("SELECT * FROM weekstats WHERE STR_TO_DATE(CONCAT(CONCAT(year,week),' Monday'),'%X%V %W') BETWEEN STR_TO_DATE(CONCAT({$startyear},{$startweek},' Monday'),'%X%V %W')  AND STR_TO_DATE(CONCAT({$endyear},{$endweek},' Friday'),'%X%V %W') AND  member_id = ". $_SESSION['SESS_MEMBER_ID'] ." ORDER BY year, week ASC");
		$resultarray = array();
		while($row = mysql_fetch_assoc($result)) {
			$resultarray[] = $row;
		}
		return $resultarray;
	}

	function getMonthStats(){
		$startyear = date("Y", $this->starttime);
		$startmonth = date("m", $this->starttime);
		$endyear = date("Y", $this->endtime);
		$endmonth = date("m", $this->endtime);
		$result = mysql_query("SELECT * FROM monthstats WHERE STR_TO_DATE(CONCAT(year,' ',month,' 1'),'%Y %m %d') BETWEEN STR_TO_DATE(CONCAT({$startyear},' ',{$startmonth},' 1'),'%Y %m %d') AND LAST_DAY(STR_TO_DATE(CONCAT({$endyear},' ',{$endmonth}),'%Y %m')) AND  member_id = ". $_SESSION['SESS_MEMBER_ID'] ." ORDER BY year, month ASC");
		$resultarray = array();
		if($result){
			while($row = mysql_fetch_assoc($result)) {
				$resultarray[] = $row;
			}
		}
		return $resultarray;
	}
	
	public static function updateStats($timeObj){
		foreach($timeObj->weeksInSpan() as $weekArr){
			$resultweek = self::updateWeekStats($weekArr['year'],$weekArr['week']);
		}
		foreach($timeObj->monthsInSpan() as $monthArr){
			$resultmonth = self::updateMonthStats($monthArr['year'],$monthArr['month']);
		}
		if($resultweek == false && $resultmonth == false) {
			$resultarray = array(false,"Neither week or month stats updated");			
		}elseif($resultweek == false && $resultmonth != false) {
			$resultarray = array(false,"Week stats not updated");	
		}elseif($resultweek != false && $resultmonth == false) {
			$resultarray = array(false,"Month stats not updated");	
		}else{
			$resultarray = array(true,"Stats updated");		
		}
		return $resultarray;
	}
	
	public static function updateWeekStats($year,$week) {
		if(strlen($year == 2)) $year = str_pad($year, 4, "20", STR_PAD_LEFT);
		$resulttimeworkweek = mysql_query("SELECT dworkweek,daysinworkweek,statsstartdate FROM userdb WHERE member_id = " . $_SESSION['SESS_MEMBER_ID']);
		$workweek = mysql_fetch_object($resulttimeworkweek);
		
		$weektimearray = fetchStartEndTime("week",$year,null,null,$week);	
		if($weektimearray['start'] < $workweek->statsstartdate) {
			$weektimearray['start'] = $workweek->statsstartdate;
			if(date("Y",$workweek->statsstartdate) == $year && date("W",$workweek->statsstartdate) == $week ){
				$workdaysforweek = 7 - date("N",$workweek->statsstartdate) - 1;
				if($workdaysforweek < 0) $workdaysforweek = 0;
			}else{
				$workdaysforweek = 0;
			}
		}else{
			$workdaysforweek = $workweek->daysinworkweek;
		}

		$worktimeforweek = $workdaysforweek * ($workweek->dworkweek/$workweek->daysinworkweek);
		
	 	$weekworkbreaktime = fetchWorkAndBreakTime($weektimearray);
		$result = mysql_query("SELECT id FROM weekstats WHERE year = {$year} AND week = {$week} AND member_id = {$_SESSION['SESS_MEMBER_ID']}");
		
		$timespan = new static($weektimearray['start'],$weektimearray['end']);
		$asworktime = $timespan->getasworktime();
		$againstworktime = $timespan->getagainstworktime();
		$weekworkbreaktime['asworktime'] = $asworktime['sum'];
	
		if(mysql_num_rows($result) > 0){
			$resultupdate = mysql_query("UPDATE weekstats SET towork = {$worktimeforweek}, againstworktime = {$againstworktime['sum']}, worked = {$weekworkbreaktime['worktime']},asworktime = {$asworktime['sum']} WHERE  year = {$year} AND week = {$week} AND member_id = {$_SESSION['SESS_MEMBER_ID']}");
		}else{
			$resultinsert = mysql_query("INSERT INTO weekstats (member_id,year,week,towork,againstworktime,worked,asworktime,modified) VALUES ({$_SESSION['SESS_MEMBER_ID']},{$year},{$week},{$worktimeforweek},{$againstworktime['sum']},{$weekworkbreaktime['worktime']},{$asworktime['sum']},NOW())");
		}
		if($resultupdate == true || $resultinsert == true){
			return array('towork'=>$worktimeforweek, 'worked'=>$weekworkbreaktime['worktime'],'againstworktime'=>$againstworktime,'asworktime'=>$asworktime);
		}else{
			return false;
		}
	}
	
	public static function updateMonthStats($year,$month) {
		if(strlen($year == 2)) $year = str_pad($year, 4, "20", STR_PAD_LEFT);
		$resulttimeworkweek = mysql_query("SELECT dworkweek,daysinworkweek,statsstartdate FROM userdb WHERE member_id = " . $_SESSION['SESS_MEMBER_ID']);
		$workweek = mysql_fetch_object($resulttimeworkweek);
		
		$monthtimearray = fetchStartEndTime("month",$year,$month);
		if($monthtimearray['start'] < $workweek->statsstartdate) $monthtimearray['start'] = $workweek->statsstartdate;
		
		$worktimeformonth = workingdaysmonth($month,$year)*($workweek->dworkweek)/$workweek->daysinworkweek;
		$monthworkbreaktime = fetchWorkAndBreakTime($monthtimearray);
		
		$result = mysql_query("SELECT id FROM monthstats WHERE year = {$year} AND month = {$month} AND member_id = {$_SESSION['SESS_MEMBER_ID']}");
		
		$timespan = new static($monthtimearray['start'],$monthtimearray['end']);
		$asworktime = $timespan->getasworktime();
		$againstworktime = $timespan->getagainstworktime();
		$monthworkbreaktime['asworktime'] = $asworktime['sum'];
		
		if(mysql_num_rows($result) > 0){
			$resultupdate = mysql_query("UPDATE monthstats SET towork = {$worktimeformonth}, againstworktime = {$againstworktime['sum']}, worked = {$monthworkbreaktime['worktime']}, asworktime = {$asworktime['sum']} WHERE  year = {$year} AND month = {$month} AND member_id = {$_SESSION['SESS_MEMBER_ID']}");
		}else{
			$resultinsert = mysql_query("INSERT INTO monthstats (member_id,year,month,towork,againstworktime,worked,asworktime,modified) VALUES ({$_SESSION['SESS_MEMBER_ID']},{$year},{$month},{$worktimeformonth},{$againstworktime['sum']},{$monthworkbreaktime['worktime']},{$asworktime['sum']},NOW())");
		}
		if($resultupdate == true || $resultinsert == true){
			return array('towork'=>$worktimeformonth, 'worked'=>$monthworkbreaktime['worktime'],'againstworktime'=>$againstworktime,'asworktime'=>$asworktime);
		}else{
			return false;
		}
	}


}


?>