<?php

if($_GET['CSV'] == "Y"){
	 // send response headers to the browser
     header( 'Content-Type: text/csv' );
     $fp = fopen('php://output', 'w');
	
}

$year = $_REQUEST['y'];
$month = $_REQUEST['m'];
$numdays = cal_days_in_month(CAL_GREGORIAN, $month, $year); 
$timeArray = fetchStartEndTime("month",$year,$month);
$return = fetchPeriodsArray($timeArray);
$periodsArray = $return[0];
$daysArray = array_fill(1,$numdays,null);

$monthstats = timespan::updateMonthStats($year,$month);
if(!$monthstats){
	echo "Stats not able to be calulated! Contact webmaster.";
	exit;
}

if($periodsArray){
	foreach($periodsArray as $period){
		$dayToAddTo = date("j",$period[0]["starttime"]);
		$daysArray[$dayToAddTo][] = $period;
	}
}

$prevmonth = date("m", strtotime("{$year}-{$month} - 1 month"));
$prevyear = date("Y", strtotime("{$year}-{$month} - 1 month"));
$nextmonth = date("m", strtotime("{$year}-{$month} + 1 month"));
$nextyear = date("Y", strtotime("{$year}-{$month} + 1 month"));


echo "<div class='list_header'><a href='{$_SERVER['PHP_SELF']}?summary=month_view&m={$prevmonth}&y={$prevyear}'><<</a> {$year} ".date("M", mktime(0, 0, 0, $month, 10))." <a href='{$_SERVER['PHP_SELF']}?summary=month_view&m={$nextmonth}&y={$nextyear}'>>></a> </div>";
echo "<div class='subpage_container'>";
echo "<div class='subpage_month'>";
echo "<div class='month_container_list'><div class='monthlist_item'><div class='monthlist_date bheader'>Date</div><div class='monthlist_day bheader'>Day</div><div class='monthlist_starttime bheader'>Start</div><div class='monthlist_breaks bheader'>Breaks</div><div class='monthlist_endtime bheader'>End</div><div class='monthlist_timeoff bheader'>Free time</div><div class='monthlist_vacation bheader'>Vacation</div></div>";

$tocsvarray = array(); 

for($i = 1; $i <= $numdays; $i++){
	$day = date("D",strtotime("{$year}-{$month}-{$i}"));
	$dayofweek = date("N",strtotime("{$year}-{$month}-{$i}"));
	if($dayofweek >= 6) $rowcolor = "weekendrowcolor";
	elseif($i%2) $rowcolor = "oddrowcolor";
	else $rowcolor = "evenrowcolor";
	
	if($vacationtimestamp = $monthstats['vacation']['days'][$year."-".$month."-".($i < 10 ? '0'.$i : $i)]['time']) $vacationtime = timestampToTime($vacationtimestamp);
	else $vacationtime = "";
	if($timeofftimestamp = $monthstats['timeoff']['days'][$year."-".$month."-".($i < 10 ? '0'.$i : $i)]['time']) $timeofftime = timestampToTime($timeofftimestamp);
	else $timeofftime = "";
	
	$periodsForDay = $daysArray[$i];
	$numPeriodsForDay = count($periodsForDay);
	if($periodsForDay){
		$writedate = true;
		foreach($periodsForDay as $periodForDay){
			$workTimePeriod = 0;
			$starttime = date("H:i",$periodForDay[0]['starttime']);
			$endtime = date("H:i",$periodForDay[0]['endtime']);
			
			$workTimePeriod = $workTimePeriod + ($periodForDay[0]['endtime'] - $periodForDay[0]['starttime']);
			
			$breaks = "";
			for($i2 = 1; $i2 < count($periodForDay); $i2++){
				$breaks .= date("H:i",$periodForDay[$i2]['starttime']) . "--" .  date("H:i",$periodForDay[$i2]['endtime']);
				$workTimePeriod = $workTimePeriod - ($periodForDay[$i2]['endtime'] - $periodForDay[$i2]['starttime']);
				if($i2 < count($periodForDay)) $breaks .= "<br>";
				
				if($i2 == 1)
					$tocsvarray[] = array($i,$day,$starttime,date("H:i",$periodForDay[$i2]['starttime']),date("H:i",$periodForDay[$i2]['endtime']),$endtime,$timeofftime,$vacationtime);
				else
					$tocsvarray[] = array("","","",date("H:i",$periodForDay[$i2]['starttime']),date("H:i",$periodForDay[$i2]['endtime']),"","","");
			}
			
			if($breaks == "") 
				$tocsvarray[] = array($i,$day,$starttime,"","",$endtime,$timeofftime,$vacationtime);

			
			$worktime = timestampToDecTime($workTimePeriod);
			
			$workTimeMonth += $workTimePeriod;
			
			
			if($writedate){
				echo "<div class='monthlist_item {$rowcolor}'><div class='monthlist_date'>{$i}</div><div class='monthlist_day'>{$day}</div><div class='monthlist_starttime'>{$starttime}</div><div class='monthlist_breaks'>{$breaks}</div><div class='monthlist_endtime'>{$endtime}</div><div class='monthlist_timeoff'>{$timeofftime}</div><div class='monthlist_vacation'>{$vacationtime}</div><div class='monthlist_worktime'>{$worktime}</div></div>";
				$writedate = false;
			}else{
				echo "<div class='monthlist_item {$rowcolor}'><div class='monthlist_date'></div><div class='monthlist_day'></div><div class='monthlist_starttime'>{$starttime}</div><div class='monthlist_breaks'>{$breaks}</div><div class='monthlist_endtime'>{$endtime}</div><div class='monthlist_timeoff'></div><div class='monthlist_vacation'></div><div class='monthlist_worktime'>{$worktime}</div></div>";	
			}
			
		}	
	}else{
		echo "<div class='monthlist_item {$rowcolor}'><div class='monthlist_date'>{$i}</div><div class='monthlist_day'>{$day}</div><div class='monthlist_starttime'></div><div class='monthlist_breaks'></div><div class='monthlist_endtime'></div><div class='monthlist_timeoff'>{$timeofftime}</div><div class='monthlist_vacation'>{$vacationtime}</div><div class='monthlist_worktime'></div></div>";
		$tocsvarray[] = array($i,$day,"","","","",$timeofftime,$vacationtime);	
	}
			
 }


echo "</div><div class='monthview_worked'>Worked time: ".timestampToDecTime($monthstats['worked']+$monthstats['vacation']['sum'])." (incl vacation)</div>";

//echo "<pre>";
//var_dump($return);


echo "<div class='monthview_worked'>Time to work: ".timestampToDecTime($monthstats['towork']-$monthstats['timeoff']['sum'])." (incl time off)</div>";

echo "<div id='monthlist_alltotals'>".timestampToDecTime($monthstats['timeoff']['sum'] + $monthstats['worked'] + $monthstats['vacation']['sum'] - $monthstats['towork'] )."</div>";
echo "</div>";
echo "<div class='subpage_menu'>";
echo "<ul class='subpage_menu_list subpage_menu_style'><li class='bheader'>View</li><ul class='subpage_menu_list'><li><a href='summary.php?summary=month_view&m={$_GET['m']}&y={$_GET['y']}'>List</a></li><li><a href='summary.php?summary=month_cal&m={$_GET['m']}&y={$_GET['y']}'>Calendar</a></li></ul><li class='bheader'>Actions</li><ul class='subpage_menu_list'><li><a href='#' id='subpage_menu_add_vac'>Add Vacation</a></li><li><a href='#' id='subpage_menu_add_free'>Add Time off</a></li><li><a href='#' id='subpage_menu_add_work'>Add work</a></li></ul><li class='bheader'>Download</li><ul class='subpage_menu_list'><li><a href='download.php'>CSV</a></li></ul></ul>";
echo "</div>";
echo "</div>";
echo "<div class='clear'></div>";


$fp = fopen(__SITE_BASE__.'tmp/'.$_SESSION['SESS_MEMBER_ID'].'_month.csv', 'w');

foreach ($tocsvarray as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);
include("includes/add_forms.php");

?>